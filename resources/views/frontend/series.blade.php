@extends('layouts.frontend')
@section('title', $series['title'].' · AI100分')
@section('content')
@php($fullFree = $series['is_free'] ?? false)
<div class="shell page-main series-main" data-series="{{ $series['slug'] }}">
    <nav class="breadcrumb" aria-label="面包屑"><a href="{{ route('series.index') }}">100分钟</a><i data-lucide="chevron-right"></i><span>{{ $series['tag'] }}</span></nav>
    <div class="series-heading">
        <div>
            <span class="eyebrow">{{ $series['category_label'] }} · {{ $fullFree ? '完整免费' : ($series['available'] ? '首发系列' : '筹备中') }}</span>
            <h1>{{ $series['title'] }}</h1>
            <p class="series-question">{{ $series['question'] }}，从哪里开始？</p>
            <div class="heading-meta">
                <span><i data-lucide="clock-3"></i>约{{ $series['minutes'] ?? 100 }}分钟</span>
                <span><i data-lucide="list-checks"></i>{{ $series['available'] ? (count($series['lessons']).'个步骤') : '大纲准备中' }}</span>
                <span><i data-lucide="flag"></i>一个可验收的结果</span>
            </div>
        </div>
        <a class="text-link" href="{{ route('series.index') }}">看看其他问题<i data-lucide="arrow-up-right"></i></a>
    </div>
    <div class="series-layout">
        <div class="series-content">
            <div class="series-image visual-{{ $series['image'] }}"><x-app-course-art :course="$series" /></div>
            <section class="outcome-section">
                <span class="eyebrow">最后，你会得到什么</span>
                <h2>{{ $series['outcome'] }}</h2>
                <ul class="check-list">@foreach($series['deliverables'] as $item)<li><i data-lucide="circle-check"></i>{{ $item }}</li>@endforeach</ul>
            </section>
            <section class="lesson-outline">
                <div class="section-heading row-heading"><h2>你的100分路径</h2><span class="muted">{{ $series['available'] ? ('每一步，约'.($series['lessons'][0]['minutes'] ?? 10).'分钟') : '内容正在细化' }}</span></div>
                @forelse($series['lessons'] as $lesson)
                    <a class="outline-row" href="{{ $fullFree ? route('free.lesson', [$series['slug'], $lesson['slug']]) : route('lessons.show', ['slug' => $series['slug'], 'lessonSlug' => $lesson['slug']]) }}" data-lesson-row="{{ $lesson['slug'] }}">
                        <span class="outline-score">{{ $lesson['score'] }}<small>分</small></span>
                        <div><h3>{{ $lesson['title'] }}</h3><p>{{ $lesson['summary'] }}</p></div>
                        <span class="outline-state {{ $lesson['is_free'] ? 'free' : '' }}">{{ $fullFree ? '免费学习' : ($lesson['is_free'] ? '免费试看' : '待发布') }}</span>
                        <i data-lucide="{{ $lesson['is_free'] ? 'arrow-up-right' : 'lock-keyhole' }}"></i>
                    </a>
                @empty
                    <div class="preparing-outline"><i data-lucide="notebook-pen"></i><h3>先把问题拆清楚，再带你做成。</h3><p>这个系列正在准备目标、步骤与验收方式，暂未开放购买。</p></div>
                @endforelse
            </section>
        </div>
        <aside class="series-sidebar">
            <div class="purchase-panel">
                <span class="eyebrow">{{ $series['available'] ? '先完成第一课' : '下一个100分钟' }}</span>
                <h2>{{ $series['available'] ? '从这里，开始做成。' : '一个新问题，正在准备。' }}</h2>
                <p>{{ $series['description'] }}</p>
                <x-ufo-progress :series="$series['slug']" :score="$serverScore ?? null" label="当前完成度" />
                @if($series['available'])
                    <a class="button button-primary full-width" href="{{ $fullFree ? route('free.lesson', [$series['slug'], $series['lessons'][0]['slug']]) : route('lessons.show', ['slug' => $series['slug'], 'lessonSlug' => 'server-and-ip']) }}">{{ $fullFree ? '开始免费学习' : '免费试看第一课' }}<i data-lucide="arrow-right"></i></a>
                @else
                    <span class="button button-disabled full-width">正在筹备</span>
                @endif
                <div class="purchase-price"><span>一门完整课程</span><strong>@if($fullFree)免费@else¥{{ $series['price'] }}<small> / 门</small>@endif</strong></div>
                @if($fullFree)
                    <p class="muted">全部课时免费开放，登录后可保存验收进度。</p>
                @else
                    <button class="button button-outline full-width" data-availability="purchase">购买暂未开放<i data-lucide="lock-keyhole"></i></button>
                    <a class="membership-link" href="{{ route('pricing') }}">订阅 ¥299 / 月，全部课程随便看<i data-lucide="chevron-right"></i></a>
                @endif
            </div>
            <section class="prerequisites">
                <h3>开始之前，准备好</h3>
                <ul>@foreach($series['prerequisites'] as $item)<li><i data-lucide="check"></i>{{ $item }}</li>@endforeach</ul>
                <p>实际操作和外部等待时间因环境而异。</p>
            </section>
        </aside>
    </div>
    <x-course-relations :groups="$relationGroups ?? []" />
</div>
@endsection
