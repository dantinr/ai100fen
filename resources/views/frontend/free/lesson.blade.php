@extends('layouts.frontend')
@php
    $isPreview = $isPreview ?? false;
    $learningRoute = request()->routeIs('free.*') ? 'free.lesson' : 'lessons.show';
    $progressRoute = request()->routeIs('free.*') ? 'free.progress' : 'lessons.progress';
    $resourceRoute = request()->routeIs('free.*') ? 'free.resource' : 'lessons.resource';
    $lessonIndex = ($lessons->search(fn ($item) => $item->id === $lesson->id) ?: 0) + 1;
    $lessonRole = $series->slug === 'build-a-website' ? \App\Support\WebsiteSetupLessons::roleFor($lesson->slug) : null;
    $hasAgent = $lessonRole && str_contains($lessonRole, 'Agent');
    $hasVideo = (bool) $lesson->video_url || (app()->environment('local') && config('player.demo_enabled'));
    $lessonIcon = match ($lesson->slug) {
        'server-and-ip' => 'server', 'authorize-agent' => 'terminal',
        'first-website' => 'layout-template', 'domain' => 'globe',
        'customize' => 'panels-top-left', default => 'flag',
    };
@endphp
@section('title', $series->title.($isPreview ? ' · 前台预览' : ' · 学习').' · AI100分')
@section('content')
<div @class(['shell page-main free-learning', 'lesson-with-video' => $hasVideo])>
    @if($hasVideo)<div class="lesson-learning-bar">@endif
    <a class="text-link" href="{{ $isPreview ? \App\Filament\Resources\CourseSeries\CourseSeriesResource::getUrl('edit', ['record' => $series]) : route('series.show', $series->slug) }}"><i data-lucide="arrow-left"></i>{{ $isPreview ? '返回课程编辑' : '返回课程' }}</a>
    @if($hasVideo)<a class="text-link lesson-course-link" href="{{ $isPreview ? \App\Filament\Resources\CourseSeries\CourseSeriesResource::getUrl('edit', ['record' => $series]) : route('series.show', $series->slug) }}" title="{{ $series->title }}">{{ $series->title }}<i data-lucide="chevron-right"></i></a></div>@endif
    @if($isPreview)<p class="free-panel" role="status">管理员前台预览 · {{ ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$series->status] }} · 当前课时：{{ ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$lesson->status] }}。预览不发布课程、不保存进度。</p>@endif
    @if($hasVideo)
    <header class="lesson-video-intro">
        <h1 class="lesson-title-with-agent"><span>{{ $lesson->title }}</span><x-lesson-agent-mark :course="$series->slug" :lesson="$lesson->slug" /><x-lesson-completion :completed="!$isPreview && (bool) $progress?->completed_at" :lesson="$lesson->id" /></h1>
        <div class="lesson-compact-meta"><span class="free-badge">{{ $series->is_free ? '完整免费' : '付费课程 · ¥'.$series->price }} · {{ strtoupper($series->category) }}</span><span>第{{ $lessonIndex }}节 / {{ $lessons->count() }}节</span><span>约{{ $lesson->minutes }}分钟 · 完成占比 {{ $lessonPercent }}%</span></div>
    </header>
    @else
    <header class="free-lesson-heading lesson-hero">
        <div class="lesson-hero-copy">
            <div class="lesson-hero-kicker"><span class="free-badge">{{ $series->is_free ? '完整免费' : '付费课程 · ¥'.$series->price }} · {{ strtoupper($series->category) }}</span><span class="eyebrow">第{{ $lessonIndex }}节 / {{ $lessons->count() }}节</span></div>
            <h1>{{ $series->title }}</h1>
            <p>{{ $series->final_outcome }}</p>
            <span class="free-note">约 {{ $series->minutes }} 分钟 · 图文实践 · 验收后完成100%</span>
            @if(!$isPreview)<x-course-share :url="route('series.show', $series->slug)" :title="$series->title" :text="$series->final_outcome" />@endif
            <nav class="lesson-quick-nav" aria-label="本课内容">@if($lesson->video_url || (app()->environment('local') && config('player.demo_enabled')))<a href="#lesson-video"><i data-lucide="video"></i>视频</a>@endif<a href="#lesson-steps"><i data-lucide="list-checks"></i>操作步骤</a><a href="#lesson-prompt-section"><i data-lucide="terminal"></i>Prompt</a><a href="#lesson-acceptance"><i data-lucide="circle-check"></i>验收清单</a></nav>
        </div>
        <div class="lesson-hero-art" aria-hidden="true">
            <span class="lesson-art-orbit"></span><span class="lesson-art-orbit lesson-art-orbit-inner"></span>
            <span class="lesson-art-symbol"><i data-lucide="{{ $lessonIcon }}"></i></span>
            <span class="lesson-art-index">{{ str_pad($lessonIndex, 2, '0', STR_PAD_LEFT) }}</span>
            <div class="lesson-art-character">@if($hasAgent)<x-paul-avatar size="large" />@else<x-ufo-widget />@endif</div>
            <span class="lesson-art-sticker"><i data-lucide="sparkles"></i>{{ $hasAgent ? '和 Z 一起做成' : '先把这一步做成' }}</span>
            <span class="lesson-art-points">{{ $lessonPercent }}%<small>完成占比</small></span>
        </div>
    </header>
    @endif
    <div class="free-learning-grid">
        <article class="free-lesson-content">
            @if($hasVideo)
                <x-lesson-video :source="$lesson->video_url" :poster="$lesson->videoPosterUrl() ?? $series->coverUrl()" :title="$lesson->title" :compact="true" />
                <nav class="lesson-quick-nav" aria-label="本课内容"><a href="#lesson-steps"><i data-lucide="list-checks"></i>操作步骤</a><a href="#lesson-prompt-section"><i data-lucide="terminal"></i>Prompt</a><a href="#lesson-acceptance"><i data-lucide="circle-check"></i>验收清单</a></nav>
            @endif
            <section class="free-panel lesson-goal-panel"><div class="lesson-section-kicker"><i data-lucide="flag"></i>本课的小目标</div>
                @if($hasVideo)<h2>你要做成什么？</h2>@else<h2 class="lesson-title-with-agent"><span>{{ $lesson->title }}</span><x-lesson-agent-mark :course="$series->slug" :lesson="$lesson->slug" /><x-lesson-completion :completed="!$isPreview && (bool) $progress?->completed_at" :lesson="$lesson->id" /></h2><p class="free-note">第{{ $lessonIndex }}节 · 约{{ $lesson->minutes }}分钟 · 完成占比 {{ $lessonPercent }}%</p><h3>你要做成什么？</h3>@endif
                <p class="lesson-goal-result">{{ $lesson->goal }}</p><p>{{ $lesson->intro }}</p>
                @if($lesson->objectives)<div class="lesson-objectives-inline"><x-lesson-objectives :objectives="$lesson->objectives" /></div>@endif
                @if($hasVideo && !$isPreview)<x-course-share :url="route('series.show', $series->slug)" :title="$series->title" :text="$series->final_outcome" />@endif
            </section>
            @if($lesson->content)<section class="free-panel lesson-reading-panel"><div class="lesson-section-kicker"><i data-lucide="book-open"></i>先看清楚，再开始</div><div class="free-markdown">{!! \Illuminate\Support\Str::markdown($lesson->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div></section>@endif
            <section class="free-panel lesson-steps-panel" id="lesson-steps"><div class="lesson-section-kicker"><i data-lucide="route"></i>一步一步，做出结果</div><h2>跟着这几步做</h2><ol class="lesson-step-list">@foreach($lesson->steps as $step)<li class="free-step"><span class="lesson-step-number" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h3>{{ $step['title'] }}</h3><p>{{ $step['body'] }}</p></div></li>@endforeach</ol></section>
            <section class="free-panel lesson-prompt-panel" id="lesson-prompt-section"><div class="lesson-section-kicker"><i data-lucide="terminal"></i>把执行交给 Agent</div><h2>交给 Agent 的 Prompt</h2><x-agent-prompt :prompt="$lesson->prompt" id="free-prompt" /></section>
            @if($lesson->code)
                <details class="free-panel free-source"><summary>查看完整示例：{{ $lesson->code_filename }}</summary><button class="text-button" type="button" data-copy="free-code">复制代码<i data-lucide="copy"></i></button><pre id="free-code" class="free-code">{{ $lesson->code }}</pre></details>
            @endif
            @if($lesson->resources)<section class="free-panel"><h2>本课资料</h2>@foreach($lesson->resources as $resource)@if($isPreview)<p>{{ $resource['label'] }} · 预览不开放下载</p>@else<a class="text-link" href="{{ route($resourceRoute, [$series, $lesson->slug, $resource['name']]) }}">{{ $resource['label'] }}<i data-lucide="download"></i></a>@endif@endforeach</section>@endif
            <section class="free-panel lesson-acceptance-panel" id="lesson-acceptance" aria-labelledby="free-check-title">
                <div class="lesson-section-kicker"><i data-lucide="badge-check"></i>用真实结果，完成这一步</div>
                <h2 id="free-check-title">亲自验收，才算做成</h2>
                @if($isPreview)
                    <p>验收清单预览，学习进度不会保存。</p>
                    @foreach($lesson->checks as $check)<label class="free-check"><input type="checkbox" disabled><span>{{ $check }}</span></label>@endforeach
                @else
                <p>逐项检查实际成果后再勾选。这里只保存你的验收确认，不会自动检查电脑里的文件。</p>
                <form data-free-progress data-lesson-id="{{ $lesson->id }}" action="{{ route($progressRoute, [$series, $lesson->slug]) }}" method="post">
                    @csrf
                    @foreach($lesson->checks as $index => $check)
                        <label class="free-check"><input type="hidden" name="checks[{{ $index }}]" value="0"><input type="checkbox" name="checks[{{ $index }}]" value="1" @checked($progress?->checks[$index] ?? false)><span>{{ $check }}</span></label>
                    @endforeach
                    @auth
                        <button class="button button-primary" type="submit" data-free-save>保存验收进度<i data-lucide="check"></i></button>
                        <p class="free-note" data-free-status role="status">{{ session('free-progress-saved') ? '已保存到你的账号。' : '进度保存到当前账号，可跨设备继续；全部验收后完成100%。' }}</p>
                    @else
                        <p class="free-note">你可以直接学习当前开放的课时。访客的勾选仅在当前页面生效；登录后可保存到账号。</p><a class="button button-dark" href="{{ route('login', ['redirect' => route($learningRoute, [$series, $lesson->slug], false)]) }}">登录并保存进度<i data-lucide="arrow-right"></i></a>
                    @endauth
                    @error('checks')<p class="free-error" role="alert">{{ $message }}</p>@enderror
                </form>
                @endif
            </section>
        </article>
        <aside class="free-sidebar" aria-label="当前课时">
            <div class="free-panel lesson-focus-panel">
                <div class="lesson-objectives-sidebar"><div class="lesson-section-kicker"><i data-lucide="flag"></i>这一课，要做成什么</div><h2>本课目标</h2><x-lesson-objectives :objectives="$lesson->objectives" :goal="$lesson->goal" /></div>
                <p class="free-note">第{{ $lessonIndex }}节 · 约{{ $lesson->minutes }}分钟 · 完成占比 {{ $lessonPercent }}%</p>
                @if(!$isPreview)<p>本课已验收：<strong data-free-percent>{{ $progress?->progress_percent ?? 0 }}%</strong></p>@endif
                <a class="text-link lesson-focus-action" href="#lesson-acceptance">{{ $isPreview ? '查看本课验收' : '去验收本课' }}<i data-lucide="arrow-down"></i></a>
            </div>
        </aside>
    </div>
    <section class="free-panel lesson-route-panel" aria-labelledby="lesson-course-outline-title">
            <span class="eyebrow">整门课程</span><h2 id="lesson-course-outline-title">{{ $series->title }}</h2>
            @if(!$isPreview)<p>任务完成：<strong data-free-score>{{ $score }}</strong>%</p>
            @endif
            @foreach($lessons as $item)<a class="free-outline-link" @if($isPreview || $series->is_free || $item->is_free) href="{{ route($isPreview ? 'courses.preview' : $learningRoute, [$series, $item->slug]) }}" @else aria-disabled="true" @endif @if($item->id === $lesson->id) aria-current="page" @endif><span class="lesson-title-with-agent"><span>{{ $loop->iteration }}. {{ $item->title }}</span><x-lesson-agent-mark :course="$series->slug" :lesson="$item->slug" /><x-lesson-completion :completed="!$isPreview && in_array($item->id, $completedLessonIds ?? [], true)" :lesson="$item->id" /></span>@if(!$isPreview && !$series->is_free && !$item->is_free)<span>付费内容</span>@endif</a>@endforeach
            @if($series->description)<details class="free-roles"><summary>课程说明</summary><div class="free-markdown">{!! \Illuminate\Support\Str::markdown($series->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div></details>@endif
            @if($series->objectives)<details class="free-roles"><summary>课程目标</summary><ul>@foreach($series->objectives as $objective)<li>{{ $objective }}</li>@endforeach</ul></details>@endif
            <details class="free-roles"><summary>整个任务怎么验收？</summary><ul>@foreach($series->completion_criteria as $criterion)<li>{{ $criterion }}</li>@endforeach</ul></details>
            <details class="free-roles"><summary>Agent 做什么，人判断什么？</summary><h3>Agent 负责</h3><ul>@foreach($series->agent_role as $item)<li>{{ $item }}</li>@endforeach</ul><h3>你来判断</h3><ul>@foreach($series->human_judgment_required as $item)<li>{{ $item }}</li>@endforeach</ul></details>
    </section>
    <x-course-relations :groups="$relationGroups ?? []" />
</div>
@endsection
