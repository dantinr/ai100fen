@extends('layouts.frontend')
@section('title', $series['title'].' · AI100分')
@section('content')
@php($fullFree = $series['is_free'] ?? false)
@php($firstFreeLesson = collect($series['lessons'])->firstWhere('is_free', true))
@php($learningAvailable = $firstFreeLesson !== null)
<div class="shell page-main series-main" data-series="{{ $series['slug'] }}">
    <nav class="breadcrumb" aria-label="面包屑"><a href="{{ route('series.index') }}">全部课程</a><i data-lucide="chevron-right"></i><span>{{ $series['tag'] }}</span></nav>
    <div class="series-heading">
        <div>
            <h1>{{ $series['title'] }}</h1>
            <div class="heading-meta">
                <span><i data-lucide="clock-3"></i>约{{ $series['minutes'] ?? 100 }}分钟</span>
                <span><i data-lucide="list-checks"></i>{{ $series['available'] ? (count($series['lessons']).'个步骤') : '大纲准备中' }}</span>
                <span><i data-lucide="flag"></i>一个可验收的结果</span>
            </div>
        </div>
        <div class="course-heading-actions">
            <x-course-share :url="route('series.show', $series['slug'])" :title="$series['title']" :text="$series['outcome']" /></div>
    </div>
    <div class="series-layout">
        <div class="series-content">
            <x-course-goal-cover :goal="$series['outcome']">
                <ul class="check-list">@foreach($series['deliverables'] as $item)<li><i data-lucide="flag" aria-hidden="true"></i>{{ $item }}</li>@endforeach</ul>
            </x-course-goal-cover>
            <section class="lesson-outline">
                @forelse($series['lessons'] as $lesson)
                    <a @class(['outline-row', 'is-completed' => in_array($lesson['slug'], $completedLessonSlugs, true)]) @if($lesson['is_free']) href="{{ route('lessons.show', ['slug' => $series['slug'], 'lessonSlug' => $lesson['slug']]) }}" @else aria-disabled="true" @endif data-lesson-row="{{ $lesson['slug'] }}">
                        <span class="outline-index" aria-label="第{{ $loop->iteration }}课">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <div><h3 class="lesson-title-with-agent"><span>{{ $lesson['title'] }}</span><x-lesson-agent-mark :course="$series['slug']" :lesson="$lesson['slug']" /><x-lesson-completion :completed="in_array($lesson['slug'], $completedLessonSlugs, true)" /></h3><p>{{ $lesson['summary'] }}</p></div>
                        <span class="outline-state {{ $lesson['is_free'] ? 'free' : '' }}">{{ $fullFree ? '免费学习' : ($lesson['is_free'] ? '免费试看' : '付费内容') }}</span>
                        <i data-lucide="{{ $lesson['is_free'] ? 'play' : 'lock-keyhole' }}" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="preparing-outline"><i data-lucide="notebook-pen"></i><h3>先把问题拆清楚，再带你做成。</h3><p>这个系列正在准备目标、步骤与验收方式，暂未开放购买。</p></div>
                @endforelse
            </section>
        </div>
        <aside class="series-sidebar">
            <div class="purchase-panel">
                <span class="eyebrow">{{ $learningAvailable ? '先完成第一课' : '课程准备中' }}</span>
                <h2>{{ $learningAvailable ? '从这里，开始做成。' : '课程正在更新。' }}</h2>
                <p>{{ $series['description'] }}</p>
                <x-ufo-progress :series="$series['slug']" :score="$serverScore ?? null" label="当前完成度" />
                @if($learningAvailable)
                    <a class="button button-primary full-width" href="{{ route('lessons.show', ['slug' => $series['slug'], 'lessonSlug' => $firstFreeLesson['slug']]) }}">{{ $fullFree ? '免费学习' : '免费试看' }}<i data-lucide="arrow-right"></i></a>
                @else
                    <span class="button button-disabled full-width">正在筹备</span>
                @endif
                <div class="purchase-price"><span>一门完整课程</span><strong>@if($fullFree)免费@else¥{{ $series['price'] }}<small> / 门</small>@endif</strong></div>
                @if($fullFree)
                    <p class="muted">{{ $learningAvailable ? '全部课时免费开放，登录后可保存验收进度。' : '这门课程免费，内容更新完成后开放学习。' }}</p>
                @else
                    <button class="button button-outline full-width" data-availability="purchase">购买暂未开放<i data-lucide="lock-keyhole"></i></button>
                    <a class="membership-link" href="{{ route('pricing') }}">订阅 ¥299 / 月，全部课程随便看<i data-lucide="chevron-right"></i></a>
                @endif
            </div>
            @if($series['detail_description'])<section class="prerequisites"><h3>课程说明</h3><div class="free-markdown">{!! \Illuminate\Support\Str::markdown($series['detail_description'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div></section>@endif
            @if($series['prerequisites'])<section class="prerequisites">
                <h3>开始之前，准备好</h3>
                <ul>@foreach($series['prerequisites'] as $item)<li><i data-lucide="check"></i>{{ $item }}</li>@endforeach</ul>
                <p>实际操作和外部等待时间因环境而异。</p>
            </section>@endif
        </aside>
    </div>
    <x-course-relations :groups="$relationGroups ?? []" />
</div>
@endsection
