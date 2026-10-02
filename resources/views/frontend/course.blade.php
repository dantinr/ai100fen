@extends('layouts.frontend')
@section('title', $series->title.' · AI100分')
@section('content')
<div class="shell page-main course-overview">
    <nav class="breadcrumb" aria-label="面包屑"><a href="{{ route($series->is_free ? 'free.index' : 'series.index') }}">{{ $series->is_free ? '免费实验室' : '100分钟课程' }}</a><i data-lucide="chevron-right"></i><span>课程介绍</span></nav>
    <header class="free-lesson-heading"><span class="eyebrow">{{ ['solve' => 'SOLVE · 解决一个问题', 'create' => 'CREATE · 创作一个作品', 'explore' => 'EXPLORE · 探索一个可能'][$series->category] }}</span><h1>{{ $series->title }}</h1><p>{{ $series->final_outcome }}</p><span class="free-note">约{{ $series->minutes }}分钟 · {{ $lessons->count() }}个步骤 · {{ $series->is_free ? '完整免费' : '完整课程 ¥'.$series->price }}</span></header>
    <section class="free-panel"><h2>你想完成什么？</h2><p>{{ $series->user_intent }}</p>
        @if($series->description)<div class="free-markdown">{!! \Illuminate\Support\Str::markdown($series->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>@endif
        @if($series->objectives)<ul>@foreach($series->objectives as $objective)<li>{{ $objective }}</li>@endforeach</ul>@endif
    </section>
    <section class="free-panel"><h2>你的任务步骤</h2><ol class="course-overview-outline">@foreach($lessons as $item)<li><h3>{{ $item->title }}</h3><p>{{ $item->goal }}</p><span class="free-note">约{{ $item->minutes }}分钟</span></li>@endforeach</ol>
        @if($series->is_free)<a class="button button-primary" href="{{ route('free.lesson', [$series, $lessons->first()->slug]) }}">免费开始这个任务<i data-lucide="arrow-right"></i></a>
        @else<p class="free-note">完整课程学习与购买暂未开放，可以先从免费的关联任务开始。</p><a class="text-link" href="{{ route('free.index') }}">去免费实验室<i data-lucide="arrow-right"></i></a>@endif
    </section>
    <section class="free-panel"><h2>怎样才算做成？</h2><ul class="course-overview-checks">@foreach($series->completion_criteria as $criterion)<li>{{ $criterion }}</li>@endforeach</ul></section>
    <x-course-relations :groups="$relationGroups" />
</div>
@endsection
