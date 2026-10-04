<?php

namespace App\Console\Commands;

use App\Models\CourseSeries;
use App\Support\WebsiteFirstLessonContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncWebsiteFirstLesson extends Command
{
    protected $signature = 'courses:sync-website-first-lesson {--apply : 检查原始版本后更新课时内容}';

    protected $description = '预检或更新已入库的网站第一课，不覆盖管理员编辑与学习记录';

    public function handle(): int
    {
        try {
            $result = DB::transaction(function (): string {
                $series = CourseSeries::where('slug', 'build-a-website')->first();
                $lesson = $series?->lessons()->where('slug', 'server-and-ip')->lockForUpdate()->first();
                if (! $lesson) {
                    throw new RuntimeException('未找到已入库的网站第一课；请先检查课程数据。');
                }

                $original = WebsiteFirstLessonContent::original();
                // This historical sync preserves the original IP-only acceptance checks.
                $current = WebsiteFirstLessonContent::ipPreview();
                $changes = [
                    'goal' => $current['goal'],
                    'intro' => $current['intro'],
                    'objectives' => [$current['goal']],
                    'content' => $current['content'],
                    'steps' => $current['steps'],
                    'prompt' => $current['prompt'],
                ];
                if (collect($changes)->every(fn ($value, $field) => $this->matches($lesson->$field, $value))) {
                    return 'already-current';
                }

                $expected = [
                    'goal' => $original['goal'],
                    'intro' => $original['intro'],
                    'objectives' => [$original['goal']],
                    'content' => null,
                    'steps' => $original['steps'],
                    'prompt' => $original['prompt'],
                    'code' => $original['code'],
                    'checks' => $original['checks'],
                ];
                if ($lesson->status !== 'published' || ! $lesson->is_free ||
                    collect($expected)->contains(fn ($value, $field) => ! $this->matches($lesson->$field, $value))) {
                    throw new RuntimeException('课时已有人工编辑或状态变化；未覆盖。请在后台核对并手动合并内容。');
                }

                if (! $this->option('apply')) {
                    return 'ready';
                }

                $lesson->update($changes);

                return 'updated';
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result === 'ready') {
            $this->info('预检通过：可使用 --apply 更新目标、正文、步骤与 Prompt；验收项、分值、状态和进度保持不变。');
        } elseif ($result === 'updated') {
            $this->info('网站第一课内容已更新；验收项、分值、状态和进度保持不变。');
        } else {
            $this->info('网站第一课已是当前版本，无需更新。');
        }

        return self::SUCCESS;
    }

    private function matches(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual) || is_array($expected)) {
            return is_array($actual) && is_array($expected) && $actual == $expected;
        }

        return $actual === $expected;
    }
}
