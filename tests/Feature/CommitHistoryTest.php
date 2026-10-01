<?php

namespace Tests\Feature;

use App\Support\CommitHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommitHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-10-01T12:00:00+08:00'));
    }

    private function commit(int $id, string $time, string $subject = '完成一个可见步骤'): array
    {
        return ['hash' => str_pad(dechex($id), 40, '0', STR_PAD_LEFT), 'time' => $time, 'author' => '开发者', 'subject' => $subject];
    }

    private function snapshot(array $commits): void
    {
        Storage::disk('local')->put('commit-history.json', json_encode([
            'generated_at' => '2026-10-01T12:00:00+08:00', 'commits' => $commits,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function test_calendar_counts_in_shanghai_and_includes_exactly_365_days(): void
    {
        $this->snapshot([
            $this->commit(1, '2026-09-30T16:15:00+00:00'),
            $this->commit(2, '2026-10-01T09:00:00+08:00'),
            $this->commit(3, '2025-10-02T00:00:00+08:00'),
            $this->commit(4, '2025-10-01T23:59:59+08:00'),
        ]);
        $history = app(CommitHistory::class);
        $calendar = $history->calendar($history->read());
        $days = collect($calendar['weeks'])->pluck('days')->flatten(1)->where('in_range', true);
        $this->assertCount(365, $days);
        $this->assertSame('2025-10-02', $calendar['start']);
        $this->assertSame('2026-10-01', $calendar['end']);
        $this->assertSame(3, $calendar['total']);
        $this->assertSame(2, $calendar['active_days']);
        $this->assertSame(2, $days->firstWhere('date', '2026-10-01')['count']);
        $this->assertSame(str_pad('2', 40, '0', STR_PAD_LEFT), $calendar['commits']->first()['hash']);

        $this->travelTo(CarbonImmutable::parse('2024-03-01T12:00:00+08:00'));
        $leapDays = collect($history->calendar(null)['weeks'])->pluck('days')->flatten(1)->where('in_range', true);
        $this->assertCount(365, $leapDays);
        $this->assertNotNull($leapDays->firstWhere('date', '2024-02-29'));
    }

    public function test_page_filters_by_date_and_does_not_execute_git_or_expose_email(): void
    {
        Process::fake();
        $this->snapshot([
            $this->commit(1, '2026-10-01T09:00:00+08:00', '当天更新'),
            $this->commit(2, '2026-09-30T09:00:00+08:00', '昨天更新'),
        ]);
        $this->get('/commits?date=2026-10-01')->assertOk()->assertSee('当天更新')->assertDontSee('昨天更新')
            ->assertSee('2026-10-01的提交')->assertSee('共1条记录')
            ->assertSee('aria-current="date"', false)->assertSee('noindex, nofollow');
        $this->get('/commits?date=2026-09-29')->assertOk()->assertSee('这一天没有提交');
        $this->get('/commits')->assertOk()->assertSee('昨天更新')->assertSee('共2条记录');
        Process::assertNothingRan();
    }

    public function test_history_paginates_without_omitting_records_and_preserves_the_day_filter(): void
    {
        $this->snapshot(array_map(fn ($id) => $this->commit($id, '2026-10-01T09:00:00+08:00', '记录'.$id), range(1, 21)));
        $this->get('/commits?date=2026-10-01')->assertOk()->assertSee('共21条记录')->assertSee('下一页')
            ->assertSee('date=2026-10-01&amp;page=2', false)->assertDontSee('>记录21<', false);
        $this->get('/commits?date=2026-10-01&page=2')->assertOk()->assertSee('记录21')->assertSee('上一页')
            ->assertDontSee('下一页')->assertDontSee('>记录1<', false);
        $this->get('/commits?page=9223372036854775807')->assertOk();
    }

    public function test_invalid_dates_are_rejected_and_commit_metadata_is_escaped(): void
    {
        $this->snapshot([$this->commit(1, '2026-10-01T09:00:00+08:00', '<script>alert(1)</script>')]);
        $this->get('/commits')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        foreach (['2026-02-30', '2026-10-02', '2025-10-01', '../../.env', '2026-10-01;git status'] as $date) {
            $this->get('/commits?date='.urlencode($date))->assertNotFound();
        }
        $this->get('/commits?date[]=2026-10-01')->assertNotFound();
    }

    public function test_missing_or_invalid_snapshots_show_an_explicit_unavailable_state(): void
    {
        $this->get('/commits')->assertOk()->assertSee('提交记录暂未同步')->assertDontSee('data-contribution-cell ', false);
        foreach (['broken json', '{"commits":[]}', json_encode(['generated_at' => '2026-10-01T12:00:00+08:00', 'commits' => [
            [...$this->commit(1, '2026-10-01T09:00:00+08:00'), 'hash' => '../../.env'],
        ]])] as $invalid) {
            Storage::disk('local')->put('commit-history.json', $invalid);
            $this->get('/commits')->assertOk()->assertSee('提交记录暂未同步');
        }
    }

    public function test_sync_exports_real_commit_fields_to_a_private_snapshot_without_email(): void
    {
        $hash = str_repeat('a', 40);
        Process::fake(['*' => Process::result(output: $hash."\0".'2026-10-01T09:00:00+08:00'."\0开发者\0完成一步\n")]);
        $this->artisan('project:sync-history')->expectsOutput('Synced 1 project commits.')->assertSuccessful();
        $snapshot = app(CommitHistory::class)->read();
        $this->assertSame(['hash', 'time', 'author', 'subject'], array_keys($snapshot['commits'][0]));
        $this->assertSame('完成一步', $snapshot['commits'][0]['subject']);
        $this->assertSame($hash, $snapshot['commits'][0]['hash']);
        Process::assertRan(fn ($process) => is_array($process->command) && in_array('--format=%H%x00%cI%x00%an%x00%s', $process->command));
    }

    public function test_failed_sync_preserves_the_last_good_snapshot(): void
    {
        $this->snapshot([$this->commit(1, '2026-10-01T09:00:00+08:00')]);
        $original = Storage::disk('local')->get('commit-history.json');
        Process::fake(['*' => Process::result(exitCode: 1)]);
        $this->artisan('project:sync-history')->assertFailed();
        $this->assertSame($original, Storage::disk('local')->get('commit-history.json'));
        Process::fake(['*' => Process::result(output: 'unexpected format')]);
        $this->artisan('project:sync-history')->assertFailed();
        $this->assertSame($original, Storage::disk('local')->get('commit-history.json'));
        Process::fake(['*' => Process::result(output: "invalid-hash\0invalid-time\0author\0subject\n")]);
        $this->artisan('project:sync-history')->assertFailed();
        $this->assertSame($original, Storage::disk('local')->get('commit-history.json'));
    }
}
