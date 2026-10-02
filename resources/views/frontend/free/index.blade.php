@extends('layouts.frontend')
@section('title', '免费实验室 · AI100分')
@section('content')
<div class="shell page-main free-main">
    <section class="free-hero">
        <div><span class="eyebrow">FREE LAB · 先把一件小事做成</span><h1>免费实验室<span class="heading-question">.</span></h1><p>从一个完整小任务开始。带走作品、结果，或有证据的结论。</p><div class="free-facts"><span>完整免费</span><span>无需登录即可学习</span><span>登录后保存进度</span></div></div>
        <x-paul-avatar state="idea" size="large" />
    </section>
    <section class="free-panel" aria-labelledby="free-paul-title">
        <span class="eyebrow">Z 帮你选第一步</span><h2 id="free-paul-title">你现在想做什么？</h2>
        <form class="free-question-form" action="{{ route('free.index') }}" method="get">
            @if($category)<input type="hidden" name="category" value="{{ $category }}">@endif
            <label class="sr-only" for="free-question">描述你想完成的任务</label>
            <input id="free-question" name="q" value="{{ $question }}" maxlength="300" placeholder="比如：我想做一份个人介绍网页" required>
            <button class="button button-dark" type="submit">请 Z 推荐<i data-lucide="arrow-right"></i></button>
        </form>
        @error('q')<p class="free-error" role="alert">{{ $message }}</p>@enderror
        <p class="free-note">按问题关键词匹配已发布的免费任务；当前不是实时 AI 对话。请勿填写私人资料。</p>
        @if($question !== '')
            <div class="free-recommendations" role="status">
            @forelse($recommendations as $match)
                <p><strong>Z：可以从「{{ $match['course']->title }}」开始。</strong><br>{{ $match['reason'] }}</p>
                <a class="text-link" href="{{ route('free.lesson', [$match['course'], $match['course']->lessons->first()->slug]) }}">开始这个免费任务<i data-lucide="arrow-right"></i></a>
            @empty
                <p>{{ $category ? 'Z：这个方向暂时没有匹配的免费任务。可以切换方向，或看看更多课程。' : 'Z：目前这三个任务还接不住你的问题。可以先从下面选一个，也可以去看看更多课程方向。' }}</p><a class="text-link" href="{{ route('series.index') }}">看看100分钟课程</a>
            @endforelse
            </div>
        @endif
    </section>
    <div class="me-section-title"><h2>{{ $category ? ['solve' => 'Solve · 解决一个问题', 'create' => 'Create · 创作一个作品', 'explore' => 'Explore · 探索一个可能'][$category] : '三个入口，一次完整体验' }}</h2><span class="free-note">图文任务 · 自己动手 · 结果验收</span></div>
    <nav class="free-categories" aria-label="选择任务方向">
        <a href="{{ route('free.index') }}" @if(!$category) aria-current="page" @endif>全部任务</a>
        @foreach(['solve' => '解决一个问题', 'create' => '创作一个作品', 'explore' => '探索一个可能'] as $value => $label)<a href="{{ route('free.index', ['category' => $value]) }}" @if($category === $value) aria-current="page" @endif>{{ $label }}</a>@endforeach
    </nav>
    <div class="free-grid">
        @forelse($courses as $course)
            <article class="free-card">
                <div class="free-card-top"><span class="eyebrow">{{ ['create' => 'CREATE · 创作作品', 'solve' => 'SOLVE · 解决问题', 'explore' => 'EXPLORE · 探索可能'][$course->category] }}</span><span class="free-badge">完整免费</span></div>
                <span class="free-task-icon" aria-hidden="true"><i data-lucide="{{ ['create' => 'layout-template', 'solve' => 'table-2', 'explore' => 'flask-conical'][$course->category] }}"></i></span>
                <h3>{{ $course->title }}</h3><p>{{ $course->final_outcome }}</p>
                <div class="free-card-meta"><span>{{ $course->minutes }} 分钟左右</span><span>{{ $course->lessons->count() }} 个完整步骤</span></div>
                <a class="button button-primary" href="{{ route('free.lesson', [$course, $course->lessons->first()->slug]) }}">免费开始<i data-lucide="arrow-right"></i></a>
            </article>
        @empty
            <p>这个方向的免费任务正在准备，发布后会出现在这里。</p>
        @endforelse
    </div>
    <aside class="free-preview-note"><h2>完整免费，与免费试看有什么不同？</h2><p>这里的任务从开始到最终验收都免费，包含全部已发布步骤和资料。100分钟付费课程的免费试看只开放指定 Lesson；单课程 ¥100，订阅 ¥299/月，购买尚未开放。</p><a class="text-link" href="{{ route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) }}">体验付费课程的免费试看<i data-lucide="arrow-right"></i></a></aside>
</div>
@endsection
