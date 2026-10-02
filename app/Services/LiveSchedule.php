<?php

namespace App\Services;

use App\Models\LiveSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

class LiveSchedule
{
    public function forMonth(?string $requestedMonth): array
    {
        $hasTable = Schema::hasTable('live_sessions');
        $hasPublished = $hasTable && LiveSession::where('access_type', 'public')->where('status', '!=', 'draft')->exists();
        $isPreview = ! $hasPublished;
        $month = $isPreview ? CarbonImmutable::create(2026, 10, 1, 0, 0, 0, 'Asia/Shanghai') : $this->month($requestedMonth);

        if ($isPreview) {
            $sessions = collect([
                $this->previewSession(8, '与收费软件说 ByeBye', 'lime'),
                $this->previewSession(15, '制作自己的云盘', 'yellow'),
                $this->previewSession(22, '如何制作公众号', 'lime'),
                $this->previewSession(29, '我的第一个小程序', 'yellow'),
            ]);
            $replays = collect();
        } else {
            $rows = LiveSession::where('access_type', 'public')->where('status', '!=', 'draft')
                ->where('starts_at', '>=', $month->utc()->format('Y-m-d H:i:s'))
                ->where('starts_at', '<', $month->addMonth()->utc()->format('Y-m-d H:i:s'))
                ->orderBy('starts_at')->orderBy('id')->get();
            $sessions = $rows->values()->map(fn (LiveSession $session, int $index) => $this->present($session, $index));
            $replays = LiveSession::where('access_type', 'public')->where('status', 'replay')->latest('starts_at')->limit(5)->get()
                ->map(fn (LiveSession $session) => $this->present($session, 0));
        }

        $firstWeekday = $month->dayOfWeekIso - 1;

        return [
            'month' => $month,
            'monthKey' => $month->format('Y-m'),
            'previousMonth' => $month->subMonth()->format('Y-m'),
            'nextMonth' => $month->addMonth()->format('Y-m'),
            'daysInMonth' => $month->daysInMonth,
            'firstWeekday' => $firstWeekday,
            'cellCount' => (int) (ceil(($firstWeekday + $month->daysInMonth) / 7) * 7),
            'sessions' => $sessions,
            'sessionsByDay' => $sessions->groupBy('day'),
            'replays' => $replays,
            'isPreview' => $isPreview,
            'today' => CarbonImmutable::now('Asia/Shanghai')->format('Y-m-d'),
        ];
    }

    private function month(?string $requestedMonth): CarbonImmutable
    {
        if (is_string($requestedMonth) && preg_match('/\A20\d{2}-(0[1-9]|1[0-2])\z/', $requestedMonth)) {
            return CarbonImmutable::parse($requestedMonth.'-01', 'Asia/Shanghai')->startOfMonth();
        }

        $currentMonth = CarbonImmutable::now('Asia/Shanghai')->startOfMonth();
        $currentStart = $currentMonth->utc()->format('Y-m-d H:i:s');
        $nearest = LiveSession::where('access_type', 'public')->where('status', '!=', 'draft')
            ->where('starts_at', '>=', $currentStart)->orderBy('starts_at')->first()
            ?? LiveSession::where('access_type', 'public')->where('status', '!=', 'draft')
                ->orderByDesc('starts_at')->first();

        return $nearest ? CarbonImmutable::instance($nearest->starts_at)->setTimezone('Asia/Shanghai')->startOfMonth() : $currentMonth;
    }

    private function previewSession(int $day, string $title, string $tone): array
    {
        return [
            'key' => 'preview-'.$day, 'day' => $day, 'date' => sprintf('2026-10-%02d', $day), 'shortDate' => sprintf('10月%02d日', $day),
            'weekdayLabel' => '周四', 'dateLabel' => "10月{$day}日 · 周四 · 20:00–21:00",
            'timeLabel' => '20:00–21:00', 'title' => $title, 'tone' => $tone,
            'statusLabel' => '计划中', 'entryLabel' => '入口待公布', 'entryUrl' => null,
        ];
    }

    private function present(LiveSession $session, int $index): array
    {
        $start = $session->starts_at->setTimezone('Asia/Shanghai');
        $end = $session->ends_at->setTimezone('Asia/Shanghai');
        $weekdays = ['日', '一', '二', '三', '四', '五', '六'];
        $time = $start->format('H:i').'–'.$end->format('H:i');
        $entryUrl = match ($session->status) {
            'live' => filled($session->meeting_url) ? route('live.enter', $session->slug) : null,
            'replay' => filled($session->replay_url) ? route('live.replay', $session->slug) : null,
            default => null,
        };

        return [
            'key' => (string) $session->id, 'day' => $start->day, 'date' => $start->format('Y-m-d'),
            'shortDate' => $start->format('n月d日'), 'weekdayLabel' => '周'.$weekdays[$start->dayOfWeek],
            'dateLabel' => $start->format('n月j日').' · 周'.$weekdays[$start->dayOfWeek].' · '.$time,
            'timeLabel' => $time, 'title' => $session->title,
            'tone' => $index % 2 === 0 ? 'lime' : 'yellow',
            'statusLabel' => [
                'scheduled' => '已排期', 'live' => '直播中', 'processing' => '回放整理中',
                'replay' => '可看回放', 'cancelled' => '已取消',
            ][$session->status],
            'entryLabel' => $session->status === 'replay' ? '观看回放' : ($entryUrl ? '进入直播' : '入口待公布'),
            'entryUrl' => $entryUrl,
        ];
    }
}
