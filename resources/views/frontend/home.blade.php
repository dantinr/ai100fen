@extends('layouts.frontend')
@section('content')
<div class="shell home-main">
    <section class="home-heading ufo-home-heading">
        <div class="home-heading-copy">
            <div class="eyebrow"><span class="status-dot"></span>先用 AI，把一件事做成</div>
            <h1>你今天想<br><span class="headline-mark">做什么<span class="heading-question">？</span></span></h1>
            <p>解决一个问题、创作一个作品、探索一个可能。<br>从一个免费小任务开始，拿到属于你的真实结果。</p>
            <div class="heading-meta"><span><i data-lucide="timer"></i>10分钟，解决一步</span><span><i data-lucide="flag"></i>100分钟，做成一个任务</span><span><i data-lucide="circle-check"></i>100分，代表完成</span></div>
            <a class="button button-dark ufo-home-start" href="{{ route('free.index') }}">从免费实验室开始<i data-lucide="arrow-right"></i></a>
        </div>
        <div class="ufo-explorer">
            <span class="ufo-sticker" aria-hidden="true">LET’S MAKE IT!</span>
            <x-ufo-widget size="large" />
            <p>先完成一小步。<span>再把一件事做成。</span></p>
            <x-app-score-emblem label="100分" caption="代表完成" />
        </div>
    </section>
    <section class="home-intents" aria-label="选择你想做的事">
        <a class="home-intent-card" data-intent="solve" href="{{ route('free.index', ['category' => 'solve']) }}" aria-label="Solve：解决一个问题">
            <div class="home-intent-kicker"><span>SOLVE</span><i data-lucide="list-checks"></i></div><h2>解决一个问题</h2><p>把现实中的麻烦解决掉，拿到能核对的结果。</p><span class="home-intent-action">从免费任务开始<i data-lucide="arrow-right"></i></span>
        </a>
        <a class="home-intent-card" data-intent="create" href="{{ route('free.index', ['category' => 'create']) }}" aria-label="Create：创作一个作品">
            <div class="home-intent-kicker"><span>CREATE</span><i data-lucide="layout-template"></i></div><h2>创作一个作品</h2><p>把想法变成一个能打开、使用或展示的成品。</p><span class="home-intent-action">从免费任务开始<i data-lucide="arrow-right"></i></span>
        </a>
        <a class="home-intent-card" data-intent="explore" href="{{ route('free.index', ['category' => 'explore']) }}" aria-label="Explore：探索一个可能">
            <div class="home-intent-kicker"><span>EXPLORE</span><i data-lucide="flask-conical"></i></div><h2>探索一个可能</h2><p>做一次真实实验，得到有证据的结论。</p><span class="home-intent-action">从免费任务开始<i data-lucide="arrow-right"></i></span>
        </a>
    </section>
    <section class="catalog-section" aria-label="100分钟课程方向">
        <div class="catalog-heading"><h2>接下来，挑战一个100分钟任务。</h2><a class="text-link" href="{{ route('series.index') }}">全部100分钟<i data-lucide="arrow-right"></i></a></div>
        <div class="course-grid">@foreach($series as $course)<x-course-card :course="$course" />@endforeach</div>
    </section>
    @if(collect($series)->contains('slug', 'build-a-website'))
    <section class="first-step-band"><div class="first-step-icon"><i data-lucide="play"></i></div><div><span class="eyebrow">先试着做成一小步</span><h2>10分钟搭建.com网站</h2><p>人与 Agent 协作 · 图文试看 · 无需登录</p></div><a class="button button-dark" href="{{ route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) }}">开始免费试看<i data-lucide="arrow-right"></i></a></section>
    @endif
    <section class="home-bottom"><div class="home-method"><div class="section-heading"><span class="eyebrow">不是看完了，而是做成了</span><h2>从0分，到自己的100分。</h2></div><div class="method-steps"><div><span class="method-number">01</span><h3>选一个任务</h3><p>从你真正想完成的事情开始。</p></div><div><span class="method-number">02</span><h3>完成一小步</h3><p>给 Agent 明确目标，观察它执行。</p></div><div><span class="method-number">03</span><h3>验收你的结果</h3><p>每完成一步，离做成更近一点。</p></div></div></div><div class="home-live"><div class="section-heading row-heading"><h2>一起解决真实问题</h2><a class="text-link" href="{{ route('live') }}">直播<i data-lucide="arrow-up-right"></i></a></div><span class="live-status"><span class="status-dot"></span>首场直播筹备中</span><h3>把一个网站，从想法做到上线</h3><p>人负责目标与验收，Agent 负责执行。</p><a class="text-link" href="{{ route('live') }}">查看直播安排<i data-lucide="arrow-right"></i></a></div></section>
</div>
@endsection
