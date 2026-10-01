<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_normalizes_email_hashes_password_and_logs_in(): void
    {
        $oldSession = session()->getId();
        $this->post('/register', [
            'name' => ' 小步 ', 'email' => ' Learner@Example.com ',
            'password' => 'safe-password', 'password_confirmation' => 'safe-password',
            'admin' => true, 'subscription' => 'active',
        ])->assertRedirect('/me')->assertSessionHasNoErrors();

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('小步', $user->name);
        $this->assertSame('learner@example.com', $user->email);
        $this->assertTrue(Hash::check('safe-password', $user->password));
        $this->assertNotSame('safe-password', $user->password);
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertNull($user->email_verified_at);
        $this->get('/me')->assertOk()->assertSee('个人中心')->assertSee($user->email)
            ->assertSee('这些体验记录尚未同步到账号。');
    }

    public function test_invalid_registration_and_duplicate_email_do_not_create_accounts(): void
    {
        $this->from('/register')->post('/register', [
            'name' => '', 'email' => 'invalid', 'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertRedirect('/register')->assertSessionHasErrors(['name', 'email', 'password'])
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->assertDatabaseCount('users', 0);

        User::factory()->create(['email' => 'learner@example.com']);
        $this->post('/register', [
            'name' => '重复', 'email' => ' LEARNER@EXAMPLE.COM ',
            'password' => 'safe-password', 'password_confirmation' => 'safe-password',
        ])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_passwords_that_bcrypt_cannot_store_are_rejected(): void
    {
        foreach ([str_repeat('密', 25), "safe-password\0invalid"] as $password) {
            $this->post('/register', [
                'name' => '小步', 'email' => 'learner@example.com',
                'password' => $password, 'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_guests_cannot_view_or_modify_a_profile_and_login_returns_to_intended_page(): void
    {
        $this->get('/me')->assertRedirect('/login');
        $this->patch('/me/profile', ['name' => '修改'])->assertRedirect('/login');
        $this->put('/me/password', [])->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');
        $this->get('/logout')->assertMethodNotAllowed();
        $user = User::factory()->create(['email' => 'learner@example.com', 'password' => 'safe-password']);
        $oldSession = session()->getId();
        $this->post('/login', ['email' => ' LEARNER@EXAMPLE.COM ', 'password' => 'safe-password', 'remember' => '1'])
            ->assertRedirect('/me')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertNotEmpty($user->fresh()->remember_token);
    }

    public function test_login_rejects_passwords_with_null_bytes_or_extra_bytes(): void
    {
        User::factory()->create(['email' => 'learner@example.com', 'password' => str_repeat('a', 72)]);
        foreach ([str_repeat('a', 73), str_repeat('a', 72)."\0"] as $password) {
            $this->post('/login', ['email' => 'learner@example.com', 'password' => $password])
                ->assertSessionHasErrors('password');
            $this->assertGuest();
        }
    }

    public function test_invalid_login_uses_a_generic_error_and_locks_out_after_five_failures(): void
    {
        User::factory()->create(['email' => 'learner@example.com', 'password' => 'safe-password']);
        foreach (['missing@example.com', 'learner@example.com'] as $email) {
            $this->from('/login')->post('/login', ['email' => $email, 'password' => 'wrong-password'])
                ->assertRedirect('/login')->assertSessionHasErrors(['email' => '邮箱或密码不正确。'])
                ->assertSessionMissing('_old_input.password');
        }
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post('/login', ['email' => 'learner@example.com', 'password' => 'wrong-password']);
        }
        $this->post('/login', ['email' => 'LEARNER@EXAMPLE.COM', 'password' => 'safe-password'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('尝试次数过多', session('errors')->first('email'));
        $this->assertGuest();

        RateLimiter::clear(hash('sha256', 'learner@example.com|127.0.0.1'));
        $this->post('/login', ['email' => 'learner@example.com', 'password' => 'safe-password'])
            ->assertRedirect('/me')->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_registration_endpoint_limits_repeated_submissions(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/register', [])->assertSessionHasErrors();
        }
        $this->post('/register', [])->assertTooManyRequests();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_authenticated_users_are_redirected_from_auth_pages_and_logout_clears_the_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/login')->assertRedirect('/me');
        $this->get('/register')->assertRedirect('/me');
        $this->withSession(['private_marker' => 'private']);
        $token = session()->token();
        $this->post('/logout')->assertRedirect('/')->assertSessionMissing('private_marker');
        $this->assertGuest();
        $this->assertNotSame($token, session()->token());
        $this->get('/me')->assertRedirect('/login');
    }

    public function test_profile_updates_only_the_current_users_nickname_and_escapes_html(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $password = $user->password;
        $this->actingAs($user)->patch('/me/profile', [
            'name' => '<script>alert(1)</script>', 'id' => $other->id,
            'email' => 'hijack@example.com', 'password' => 'hijacked-password', 'admin' => true,
        ])->assertRedirect('/me')->assertSessionHas('status', 'profile-updated');
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame($other->name, $other->fresh()->name);
        $this->get('/me')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->patch('/me/profile', ['name' => ' '])->assertSessionHasErrors('name');
    }

    public function test_password_change_requires_current_password_and_matching_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $hash = $user->password;
        $this->actingAs($user)->from('/me')->put('/me/password', [
            'current_password' => 'incorrect', 'password' => 'new-password', 'password_confirmation' => 'different',
        ])->assertRedirect('/me')->assertSessionHasErrors(['current_password', 'password'])
            ->assertSessionMissing('_old_input.current_password')->assertSessionMissing('_old_input.password');
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_password_change_preserves_current_login_and_invalidates_previous_session_hash(): void
    {
        $user = User::factory()->create(['password' => 'old-password', 'remember_token' => 'old-token']);
        $oldHash = $user->password;
        $this->actingAs($user)->get('/me')->assertOk();
        $this->from('/me')->put('/me/password', [
            'current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertRedirect('/me')->assertSessionHasNoErrors()->assertSessionHas('status', 'password-updated');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
        $this->get('/me')->assertOk();
        $this->assertAuthenticated();

        Auth::forgetGuards();
        $this->withSession(['password_hash_web' => $oldHash])->get('/me')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_registration_does_not_grant_access_to_unpublished_lessons_or_files(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/series/build-a-website/lessons/domain?purchased=1&subscription=active')
            ->assertOk()->assertSee('这一步，正在准备中。')->assertDontSee('lesson-prompt');
        $this->get('/series/build-a-website/lessons/domain/checklist?purchased=1')->assertForbidden();
    }

    public function test_account_post_requests_require_csrf_protection_outside_the_test_environment(): void
    {
        $this->app['env'] = 'production';
        $this->post('/register', [])->assertStatus(419);
        $this->post('/login', [])->assertStatus(419);
        $this->actingAs(User::factory()->create());
        $this->patch('/me/profile', ['name' => '修改'])->assertStatus(419);
        $this->put('/me/password', [])->assertStatus(419);
        $this->post('/logout')->assertStatus(419);
    }
}
