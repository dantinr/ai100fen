<?php

namespace Tests\Feature;

use App\Filament\Resources\LiveSessions\Pages\CreateLiveSession;
use App\Filament\Resources\LiveSessions\Pages\EditLiveSession;
use App\Models\LiveSession;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LiveManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('galaxy'));
        Filament::bootCurrentPanel();
    }

    public function test_preview_is_explicit_and_live_admin_requires_permission(): void
    {
        $this->get('/live')->assertOk()->assertSee('示例日程 · 非正式安排')
            ->assertSee('与收费软件说 ByeBye')->assertSee('我的第一个小程序')
            ->assertSee('type="button" disabled>入口待公布</button>', false);
        $this->get('/galaxy/live-sessions')->assertRedirect('/galaxy/login');
        $this->actingAs(User::factory()->create())->get('/galaxy/live-sessions')->assertForbidden();
        $this->administrator();
        $this->get('/galaxy/live-sessions')->assertOk()->assertSee('直播管理');
        $this->get('/galaxy/live-sessions/create')->assertOk()->assertSee('场次安排');
    }

    public function test_admin_publishes_a_session_and_public_entry_follows_status(): void
    {
        $this->administrator();
        Livewire::test(CreateLiveSession::class)->fillForm([
            'title' => '把创意做成网站', 'starts_at' => '2026-10-08 20:00:00',
            'ends_at' => '2026-10-08 21:00:00', 'status' => 'draft',
        ])->call('create')->assertHasNoFormErrors();
        $session = LiveSession::firstOrFail();
        $this->assertSame('2026-10-08 12:00', $session->starts_at->format('Y-m-d H:i'));
        $this->get('/live?month=2026-10')->assertDontSee('把创意做成网站');
        $this->get(route('live.enter', $session->slug))->assertNotFound();

        Livewire::test(EditLiveSession::class, ['record' => $session->id])->fillForm([
            'status' => 'scheduled', 'meeting_url' => 'https://example.test/room',
        ])->call('save')->assertHasNoFormErrors();
        $this->get('/live?month=2026-10')->assertOk()->assertSee('把创意做成网站')
            ->assertDontSee('示例日程 · 非正式安排')->assertDontSee('https://example.test/room');
        $this->get(route('live.enter', $session->slug))->assertNotFound();

        Livewire::test(EditLiveSession::class, ['record' => $session->id])->fillForm(['status' => 'live'])
            ->call('save')->assertHasNoFormErrors();
        $this->get('/live?month=2026-10')->assertSee(route('live.enter', $session->slug));
        $this->get(route('live.enter', $session->slug))->assertRedirect('https://example.test/room');

        Livewire::test(EditLiveSession::class, ['record' => $session->id])->fillForm([
            'status' => 'replay', 'replay_url' => 'https://example.test/replay',
        ])->call('save')->assertHasNoFormErrors();
        $this->get(route('live.enter', $session->slug))->assertNotFound();
        $this->get(route('live.replay', $session->slug))->assertRedirect('https://example.test/replay');
        $this->get('/live?month=2026-10')->assertSee('历史回放')->assertSee(route('live.replay', $session->slug));
    }

    public function test_invalid_links_and_unsupported_paid_access_cannot_be_saved(): void
    {
        foreach ([
            ['status' => 'live', 'meeting_url' => 'http://example.test/room'],
            ['status' => 'live', 'meeting_url' => null],
            ['status' => 'scheduled', 'access_type' => 'subscriber'],
        ] as $invalid) {
            try {
                LiveSession::create(array_merge([
                    'title' => '待验证场次', 'starts_at' => '2026-10-08 12:00:00',
                    'ends_at' => '2026-10-08 13:00:00',
                ], $invalid));
                $this->fail('Invalid live session was saved.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('live_sessions', 0);
            }
        }
    }

    public function test_calendar_uses_beijing_dates_and_invalid_month_inputs_fall_back(): void
    {
        LiveSession::create([
            'title' => '跨月午夜场', 'starts_at' => '2026-09-30 16:30:00',
            'ends_at' => '2026-09-30 17:30:00', 'status' => 'scheduled',
        ]);

        $this->get('/live?month=2026-10')->assertOk()->assertSee('跨月午夜场')->assertSee('2026年10月')
            ->assertSee('10月1日 · 周四 · 00:30–01:30');
        $this->get('/live?month=2026-09')->assertOk()->assertDontSee('跨月午夜场');
        $this->get('/live?month[]=2026-10')->assertOk();
    }
}
