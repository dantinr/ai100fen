<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;

class CommitHistory
{
    private const TIMEZONE = 'Asia/Shanghai';

    public function sync(): int
    {
        $output = Process::path(base_path())->timeout(30)->run([
            'git', '-c', 'safe.directory='.base_path(), '--no-pager', 'log', 'HEAD',
            '--format=%H%x00%cI%x00%an%x00%s', '--no-show-signature',
        ])->throw()->output();
        $commits = [];
        foreach (explode("\n", trim($output)) as $line) {
            if ($line === '') {
                continue;
            }
            $fields = explode("\0", rtrim($line, "\r"), 4);
            if (count($fields) !== 4) {
                throw new RuntimeException('Invalid Git history format.');
            }
            $commit = array_combine(['hash', 'time', 'author', 'subject'], $fields);
            if (! $this->validCommit($commit)) {
                throw new RuntimeException('Invalid Git commit metadata.');
            }
            $commits[] = $commit;
        }

        $snapshot = ['generated_at' => CarbonImmutable::now(self::TIMEZONE)->toIso8601String(), 'commits' => $commits];
        $path = Storage::disk('local')->path('commit-history.json');
        File::ensureDirectoryExists(dirname($path), 0750);
        File::replace($path, json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 0640);

        return count($commits);
    }

    public function read(): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists('commit-history.json')) {
            return null;
        }
        try {
            $contents = $disk->get('commit-history.json');
            if (! is_string($contents)) {
                return null;
            }
            $snapshot = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($snapshot) || ! is_array($snapshot['commits'] ?? null) || ! $this->validTime($snapshot['generated_at'] ?? null)) {
                return null;
            }
            foreach ($snapshot['commits'] as $commit) {
                if (! $this->validCommit($commit)) {
                    return null;
                }
            }

            return $snapshot;
        } catch (JsonException) {
            return null;
        }
    }

    public function calendar(?array $snapshot): array
    {
        $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $start = $today->subDays(364);
        $commits = collect($snapshot['commits'] ?? [])->map(function (array $commit): array {
            $time = CarbonImmutable::parse($commit['time'])->setTimezone(self::TIMEZONE);

            return [...$commit, 'date' => $time->toDateString(), 'display_time' => $time->format('Y-m-d H:i'), 'timestamp' => $time->getTimestamp()];
        })->sortByDesc('timestamp')->values();
        $counts = $commits->countBy('date');
        $weeks = [];
        $week = $start->startOfWeek(CarbonImmutable::SUNDAY);
        while ($week <= $today) {
            $days = [];
            $month = '';
            for ($offset = 0; $offset < 7; $offset++) {
                $day = $week->addDays($offset);
                $inRange = $day >= $start && $day <= $today;
                $date = $day->toDateString();
                $count = $inRange ? ($counts[$date] ?? 0) : 0;
                $days[] = ['date' => $date, 'count' => $count, 'in_range' => $inRange,
                    'label' => $day->format('Y年n月j日').' · '.$count.'次提交',
                    'level' => match (true) {
                        $count === 0 => 0, $count <= 2 => 1, $count <= 5 => 2, $count <= 9 => 3, default => 4
                    }];
                if ($inRange && ($day->day === 1 || $day->equalTo($start))) {
                    $month = $day->format('n月');
                }
            }
            $weeks[] = ['month' => $month, 'days' => $days];
            $week = $week->addWeek();
        }
        $recentCounts = $counts->filter(fn (int $count, string $date) => $date >= $start->toDateString() && $date <= $today->toDateString());

        return ['weeks' => $weeks, 'commits' => $commits, 'total' => $recentCounts->sum(),
            'active_days' => $recentCounts->count(), 'start' => $start->toDateString(), 'end' => $today->toDateString(),
            'available' => $snapshot !== null,
            'generated_at' => $snapshot ? CarbonImmutable::parse($snapshot['generated_at'])->setTimezone(self::TIMEZONE)->format('Y-m-d H:i') : null];
    }

    private function validCommit(mixed $commit): bool
    {
        return is_array($commit) && is_string($commit['hash'] ?? null)
            && preg_match('/\A(?:[0-9a-f]{40}|[0-9a-f]{64})\z/', $commit['hash'])
            && $this->validTime($commit['time'] ?? null)
            && is_string($commit['author'] ?? null) && is_string($commit['subject'] ?? null);
    }

    private function validTime(mixed $value): bool
    {
        if (! is_string($value) || ! preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}\z/', $value)) {
            return false;
        }
        try {
            return CarbonImmutable::parse($value)->format('c') === $value;
        } catch (\Exception) {
            return false;
        }
    }
}
