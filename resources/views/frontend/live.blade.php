@extends('layouts.frontend')
@section('title', '直播安排 · AI100分')
@section('content')
@php $firstSession = $sessions->first(); @endphp
<div class="shell page-main live-main" data-live-schedule>
    <section class="live-hero" aria-labelledby="live-title">
        <div class="live-hero-copy">
            <h1 id="live-title">一起把想法做出来。</h1>
            <p>跟着直播，完成一个真实作品。</p>
        </div>
        <div class="live-hero-art"><x-ufo-widget state="idle" size="small" /></div>
    </section>

    <section class="live-schedule" aria-labelledby="live-schedule-title">
        <div class="live-schedule-heading">
            <h2 id="live-schedule-title">{{ $isPreview || $month->isSameMonth(now('Asia/Shanghai')) ? '本月直播安排' : '直播安排' }}</h2>
            @if($isPreview)<span class="live-preview-label">示例日程 · 非正式安排</span>@endif
        </div>
        <div class="live-month-toolbar">
            <p class="live-month-label"><strong>{{ $month->format('Y年n月') }}</strong><span>北京时间</span></p>
            @unless($isPreview)
                <nav class="live-month-nav" aria-label="切换直播月份">
                    <a href="{{ route('live', ['month' => $previousMonth]) }}" aria-label="上一个月" title="上一个月"><i data-lucide="arrow-left"></i></a>
                    <a href="{{ route('live', ['month' => $nextMonth]) }}" aria-label="下一个月" title="下一个月"><i data-lucide="chevron-right"></i></a>
                </nav>
            @endunless
        </div>

        <div class="live-calendar" role="group" aria-label="{{ $month->format('Y年n月') }}直播日历">
            <div class="live-calendar-weekdays" aria-hidden="true">
                @foreach(['一', '二', '三', '四', '五', '六', '日'] as $weekday)<span>{{ $weekday }}</span>@endforeach
            </div>
            <div class="live-calendar-grid">
                @for($cell = 0; $cell < $cellCount; $cell++)
                    @php $day = $cell - $firstWeekday + 1; @endphp
                    <div @class(['live-calendar-day', 'is-empty' => $day < 1 || $day > $daysInMonth]) @if($day < 1 || $day > $daysInMonth) aria-hidden="true" @endif>
                        @if($day >= 1 && $day <= $daysInMonth)
                            <div class="live-day-heading">
                                <time datetime="{{ $month->format('Y-m') }}-{{ str_pad($day, 2, '0', STR_PAD_LEFT) }}">{{ $day }}</time>
                                @if($today === $month->format('Y-m').'-'.str_pad($day, 2, '0', STR_PAD_LEFT))<span class="live-today">今天</span>@endif
                            </div>
                            @foreach($sessionsByDay->get($day, []) as $session)
                                <button class="live-calendar-event live-tone-{{ $session['tone'] }}" type="button" data-live-select
                                    data-live-date="{{ $session['dateLabel'] }}" data-live-title="{{ $session['title'] }}"
                                    data-live-entry-label="{{ $session['entryLabel'] }}" data-live-entry-url="{{ $session['entryUrl'] }}"
                                    aria-label="{{ $session['dateLabel'] }}，{{ $session['title'] }}，{{ $session['statusLabel'] }}"
                                    aria-pressed="{{ $firstSession['key'] === $session['key'] ? 'true' : 'false' }}">
                                    <span class="live-event-meta">{{ $session['timeLabel'] }} <span>{{ $session['statusLabel'] }}</span></span>
                                    <strong>{{ $session['title'] }}</strong>
                                </button>
                            @endforeach
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        @if($firstSession)
            <div class="live-selected-row">
                <div class="live-selected-detail" data-live-detail aria-live="polite">
                    <div>
                        <p data-live-detail-date>{{ $firstSession['dateLabel'] }}</p>
                        <h3 data-live-detail-title>{{ $firstSession['title'] }}</h3>
                        @if($isPreview)<span class="live-detail-label">示例场次</span>@endif
                    </div>
                    <a class="live-entry-button" data-live-detail-link href="{{ $firstSession['entryUrl'] ?? '#' }}" @unless($firstSession['entryUrl']) hidden @endunless>{{ $firstSession['entryLabel'] }}</a>
                    <button class="live-entry-button" data-live-detail-disabled type="button" disabled @if($firstSession['entryUrl']) hidden @endif>{{ $firstSession['entryLabel'] }}</button>
                </div>
                <div class="live-guide-art" aria-hidden="true"><x-paul-avatar state="idle" size="small" /><span>Z</span></div>
            </div>
        @else
            <p class="live-empty-state">这个月还没有直播安排。</p>
        @endif

        <div class="live-mobile-list" aria-label="{{ $month->format('n月') }}直播场次">
            @forelse($sessions as $session)
                <article class="live-session-card">
                    <h3><time datetime="{{ $session['date'] }}">{{ $session['shortDate'] }}</time><span>{{ $session['weekdayLabel'] }}</span></h3>
                    <div class="live-session-content live-tone-{{ $session['tone'] }}">
                        <div class="live-session-meta"><span>{{ $session['timeLabel'] }}</span><span class="live-session-status">{{ $session['statusLabel'] }}</span></div>
                        <strong>{{ $session['title'] }}</strong>
                    </div>
                    @if($session['entryUrl'])
                        <a class="live-entry-button" href="{{ $session['entryUrl'] }}">{{ $session['entryLabel'] }}</a>
                    @else
                        <button class="live-entry-button" type="button" disabled>{{ $session['entryLabel'] }}</button>
                    @endif
                </article>
            @empty
                <p class="live-mobile-empty">这个月还没有直播安排。</p>
            @endforelse
        </div>
    </section>

    <section class="live-replays" aria-labelledby="live-replays-title">
        <h2 id="live-replays-title">历史回放</h2>
        @if($replays->isEmpty())
            <p>首场直播结束后，可在这里回看。</p>
        @else
            <ul class="live-replay-list">
                @foreach($replays as $replay)
                    <li><span>{{ $replay['dateLabel'] }}</span><a href="{{ $replay['entryUrl'] }}">{{ $replay['title'] }} &rarr;</a></li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
