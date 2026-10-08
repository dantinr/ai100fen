<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Models\Question;
use App\Models\User;
use App\Services\FreeLabInstaller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuestionCollectionTest extends TestCase
{
    use RefreshDatabase;

    private function card(): array
    {
        return ['submission_key' => (string) Str::uuid(), 'title' => '合并订单表', 'category' => 'solve',
            'goal' => '核对三份订单', 'scope' => '只处理本地CSV', 'outcome' => '可核对的汇总表',
            'completion_criteria' => ['没有丢失订单', '汇总金额可核对'], 'confirmed' => true];
    }

    public function test_collection_is_private_and_only_confirmed_task_fields_are_saved(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $data = $this->card() + ['user_id' => $other->id, 'visibility' => 'public', 'status' => 'published', 'messages' => ['私密聊天全文']];
        $response = $this->actingAs($owner)->postJson('/questions', $data)->assertCreated()->assertJsonPath('message', '问题已收集。仅你可见。');
        $question = Question::sole();
        $this->assertSame($owner->id, $question->user_id);
        $this->assertSame($data['completion_criteria'], $question->completion_criteria);
        $this->assertArrayNotHasKey('messages', $question->getAttributes());
        $this->assertArrayNotHasKey('visibility', $question->getAttributes());
        $this->get($response->json('url'))->assertOk()->assertSee('合并订单表')->assertDontSee('私密聊天全文')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/questions/mine')->assertOk()->assertSee('合并订单表')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/me')->assertOk()->assertSee('/questions/mine', false);
        $this->get('/questions')->assertOk()->assertDontSee('核对三份订单');
        $this->actingAs($other)->get($response->json('url'))->assertNotFound();
        $this->get('/questions/mine')->assertOk()->assertDontSee('合并订单表');
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get($response->json('url'))->assertNotFound();
    }

    public function test_guests_cannot_store_or_read_private_questions(): void
    {
        $this->postJson('/questions', $this->card())->assertUnauthorized();
        $this->get('/questions/mine')->assertRedirect('/login');
        $this->getJson('/questions/1')->assertUnauthorized();
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_confirmation_and_complete_card_are_required(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['confirmed' => false, 'title' => '', 'category' => 'unknown', 'goal' => '', 'outcome' => '', 'submission_key' => 'invalid', 'completion_criteria' => [], 'scope' => str_repeat('x', 1501)] as $field => $value) {
            $data = $this->card();
            $data[$field] = $value;
            $this->postJson('/questions', $data)->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $data = $this->card();
        $data['completion_criteria'] = array_fill(0, 9, '标准');
        $this->postJson('/questions', $data)->assertUnprocessable()->assertJsonValidationErrors('completion_criteria');
        $data['completion_criteria'] = [str_repeat('x', 301)];
        $this->postJson('/questions', $data)->assertUnprocessable()->assertJsonValidationErrors('completion_criteria.0');
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_retries_are_idempotent_and_key_conflicts_do_not_overwrite(): void
    {
        $first = User::factory()->create();
        $data = $this->card();
        $id = $this->actingAs($first)->postJson('/questions', $data)->assertCreated()->json('id');
        $this->postJson('/questions', $data)->assertOk()->assertJsonPath('id', $id);
        $changed = $data;
        $changed['outcome'] = '另一个结果';
        $this->postJson('/questions', $changed)->assertConflict();
        $this->assertDatabaseCount('questions', 1);
        $this->assertSame($data['outcome'], Question::sole()->outcome);
        $this->actingAs(User::factory()->create())->postJson('/questions', $data)->assertCreated();
        $this->assertDatabaseCount('questions', 2);
    }

    public function test_private_values_are_escaped_in_owner_views(): void
    {
        $data = $this->card();
        $data['title'] = '<script>alert(1)</script>';
        $data['goal'] = '<img src=x onerror=alert(1)>';
        $id = $this->actingAs(User::factory()->create())->postJson('/questions', $data)->assertCreated()->json('id');
        $this->get('/questions/'.$id)->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x onerror=alert(1)>', false);
        $this->get('/questions/mine')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_unavailable_storage_and_failed_writes_never_return_a_success_receipt(): void
    {
        $this->actingAs(User::factory()->create());
        $event = 'eloquent.creating: '.Question::class;
        Event::listen($event, fn () => throw new \RuntimeException('Simulated storage failure'));
        try {
            $this->postJson('/questions', $this->card())->assertStatus(500)->assertJsonMissingPath('id');
            $this->assertDatabaseCount('questions', 0);
        } finally {
            Event::forget($event);
        }
        Schema::drop('questions');
        $this->postJson('/questions', $this->card())->assertStatus(503)->assertJsonMissingPath('id');
    }

    public function test_submission_is_rate_limited_and_requires_csrf(): void
    {
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/questions', $this->card())->assertCreated();
        }
        $this->postJson('/questions', $this->card())->assertTooManyRequests();
        $this->assertDatabaseCount('questions', 10);
        $this->app['env'] = 'local';
        $this->postJson('/questions', $this->card())->assertStatus(419);
        $this->postJson('/questions/recommendations', ['q' => '网页'])->assertStatus(419);
    }

    public function test_recommendation_reuses_published_free_courses_without_saving_questions(): void
    {
        app(FreeLabInstaller::class)->install();
        $this->postJson('/questions/recommendations', ['q' => '个人介绍网页', 'category' => 'create'])->assertOk()
            ->assertJsonPath('recommendations.0.title', '做一个能打开的个人介绍网页')
            ->assertJsonPath('recommendations.0.url', route('free.lesson', ['personal-intro-page', 'make-and-check']));
        CourseSeries::where('slug', 'personal-intro-page')->update(['status' => 'draft']);
        $this->postJson('/questions/recommendations', ['q' => '个人介绍网页'])->assertOk()->assertExactJson(['recommendations' => []]);
        $this->postJson('/questions/recommendations', ['q' => '<script>alert(1)</script>'])->assertOk()->assertExactJson(['recommendations' => []]);
        $this->postJson('/questions/recommendations', ['q' => '网页', 'category' => 'build'])->assertUnprocessable();
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_navigation_changes_do_not_remove_home_and_course_ufos_or_topic_browsing(): void
    {
        app(FreeLabInstaller::class)->install();
        foreach (['pop', 'future'] as $theme) {
            config(['themes.active' => $theme]);
            $this->get('/questions?topic=memory')->assertOk()->assertSee('black-hole-nav-link')->assertDontSee('ufo-nav-scene')
                ->assertSee('data-question-lab', false)->assertDontSee('data-paul-widget', false)->assertSee('规则引导')->assertSee('Agent 为什么忘记目标');
            $this->get('/')->assertOk()->assertSee('data-ufo', false)->assertSee('data-paul-widget', false);
            $this->get('/series/personal-intro-page')->assertOk()->assertSee('data-ufo-path', false);
        }
    }
}
