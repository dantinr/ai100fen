@extends('layouts.frontend')
@section('title', '提交记录 · AI100分')
@section('content')
<div class="shell page-main commits-main">
    <div class="page-heading commits-heading">
        <div><span class="eyebrow">每一小步，都让它更好</span><h1>提交记录<span class="heading-question">.</span></h1><p>记录AI100分从第一步到现在，每一次真实的更新。</p></div>
        <a class="button button-outline" href="https://github.com/dantinr/ai100fen" target="_blank" rel="noopener noreferrer">查看源码<i data-lucide="arrow-up-right"></i></a>
    </div>
    <section class="contribution-panel" aria-labelledby="contribution-title">
        <div class="contribution-heading">
            <div><span class="eyebrow">过去365天</span><h2 id="contribution-title">{{ $calendar['available'] ? number_format($calendar['total']) : '—' }}<small>次提交</small></h2></div>
            <p>{{ $calendar['available'] ? $calendar['active_days'].'个活跃日' : '提交记录正在同步' }}<span>{{ $calendar['start'] }} — {{ $calendar['end'] }}</span></p>
        </div>
        @if(! $calendar['available'])<p class="history-unavailable" role="status">提交记录暂未同步，请稍后再来。</p>@endif
        <div class="contribution-scroll" tabindex="0" role="region" aria-label="每日提交方块墙，可左右滚动，聚焦方块后使用方向键浏览" data-contribution-scroll>
            <div class="contribution-calendar">
                <div class="contribution-weekdays" aria-hidden="true"><span></span>@foreach(['', '一', '', '三', '', '五', ''] as $day)<span>{{ $day }}</span>@endforeach</div>
                <div class="contribution-weeks" data-contribution-grid>
                    @foreach($calendar['weeks'] as $week)
                        <div class="contribution-week"><span class="contribution-month" aria-hidden="true">{{ $week['month'] }}</span>
                            @foreach($week['days'] as $day)
                                @if($day['in_range'] && $calendar['available'])
                                    <a class="contribution-cell" data-level="{{ $day['level'] }}" data-contribution-cell data-label="{{ $day['label'] }}" href="{{ route('commits', ['date' => $day['date']]) }}#commit-list" title="{{ $day['label'] }}" aria-label="{{ $day['label'] }}" @if($selectedDate === $day['date']) aria-current="date" @endif tabindex="{{ ($selectedDate ?? $calendar['end']) === $day['date'] ? '0' : '-1' }}"></a>
                                @else
                                    <span class="contribution-cell" data-level="0" @if(! $day['in_range']) data-outside @endif aria-hidden="true"></span>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="contribution-footer"><p data-contribution-detail>点击方块，查看当天提交。方向键可浏览日期。</p><div class="contribution-legend" aria-label="颜色从浅到深表示提交从少到多"><span>少</span>@for($level = 0; $level <= 4; $level++)<span class="contribution-cell" data-level="{{ $level }}" aria-hidden="true"></span>@endfor<span>多</span></div></div>
    </section>
    <section class="commit-list-section" id="commit-list" aria-labelledby="commit-list-title">
        <div class="commit-list-heading"><div><h2 id="commit-list-title">{{ $selectedDate ? $selectedDate.'的提交' : '最近提交' }}</h2><p>{{ $calendar['available'] ? '共'.$commits->total().'条记录 · 时间以北京时间显示' : '记录同步后将在这里展示' }}</p></div>@if($selectedDate)<a class="text-link" href="{{ route('commits') }}#commit-list">查看全部<i data-lucide="arrow-right"></i></a>@endif</div>
        <ol class="commit-list">
            @forelse($commits as $commit)
                <li class="commit-item"><span class="commit-dot" aria-hidden="true"></span><div class="commit-content"><h3><a href="https://github.com/dantinr/ai100fen/commit/{{ $commit['hash'] }}" target="_blank" rel="noopener noreferrer">{{ $commit['subject'] }}</a></h3><p><span>{{ $commit['author'] }}</span><time datetime="{{ $commit['time'] }}">{{ $commit['display_time'] }}</time></p></div><a class="commit-hash" href="https://github.com/dantinr/ai100fen/commit/{{ $commit['hash'] }}" target="_blank" rel="noopener noreferrer" aria-label="在GitHub查看提交{{ substr($commit['hash'], 0, 7) }}">{{ substr($commit['hash'], 0, 7) }}<i data-lucide="arrow-up-right"></i></a></li>
            @empty
                <li class="commit-empty">{{ $calendar['available'] ? '这一天没有提交。每一小步，都需要一点时间。' : '提交记录正在准备中。' }}</li>
            @endforelse
        </ol>
        @if($commits->hasPages())
            <nav class="commit-pagination" aria-label="提交记录分页"><span>第{{ $commits->currentPage() }} / {{ $commits->lastPage() }}页</span><div>@if(! $commits->onFirstPage())<a class="button button-outline button-small" href="{{ $commits->previousPageUrl() }}">上一页</a>@endif @if($commits->hasMorePages())<a class="button button-outline button-small" href="{{ $commits->nextPageUrl() }}">下一页</a>@endif</div></nav>
        @endif
    </section>
    @if($calendar['available'])<p class="commit-sync-note">每个方块记录一天的提交次数。最近同步：{{ $calendar['generated_at'] }}（北京时间）。</p>@endif
</div>
@endsection
