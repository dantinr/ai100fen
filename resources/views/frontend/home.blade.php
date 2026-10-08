@extends('layouts.frontend')
@section('content')
<div class="shell home-main">
    <section class="home-heading ufo-home-heading">
        <div class="home-heading-copy" data-typewriter>
            <h1 class="home-title-caret" data-typewriter-text data-typewriter-speed="75">用AI<span class="headline-mark">做点什么<span class="heading-question">？</span></span></h1>
            <section class="home-intents" aria-label="选择你想做的事">
                <a class="home-intent-card" data-intent="solve" href="{{ route('free.index', ['category' => 'solve']) }}" aria-label="Solve：解决一个问题">
                    <div class="home-intent-kicker"><span>SOLVE</span><i data-lucide="list-checks"></i></div><h2>解决一个问题</h2><p>把现实中的麻烦解决掉，拿到能核对的结果。</p>
                </a>
                <a class="home-intent-card" data-intent="create" href="{{ route('free.index', ['category' => 'create']) }}" aria-label="Create：创作一个作品">
                    <div class="home-intent-kicker"><span>CREATE</span><i data-lucide="layout-template"></i></div><h2>创作一个作品</h2><p>把想法变成一个能打开、使用或展示的成品。</p>
                </a>
                <a class="home-intent-card" data-intent="explore" href="{{ route('free.index', ['category' => 'explore']) }}" aria-label="Explore：探索一个可能">
                    <div class="home-intent-kicker"><span>EXPLORE</span><i data-lucide="flask-conical"></i></div><h2>探索一个可能</h2><p>做一次真实实验，得到有证据的结论。</p>
                </a>
            </section>
        </div>
        <div class="ufo-explorer">
            <span class="ufo-sticker" aria-hidden="true">LET’S MAKE IT!</span>
            <x-ufo-widget size="large" />
            <p>你的一小步，AI的一大步</p>
        </div>
    </section>
    <section class="catalog-section" aria-label="100分钟课程方向">
        <div class="catalog-heading"><h2>挑一个感兴趣的任务。</h2><a class="text-link" href="{{ route('series.index') }}">全部课程<i data-lucide="arrow-right"></i></a></div>
        <div class="course-grid">@forelse($series as $course)<x-course-card :course="$course" />@empty<p>课程正在准备，发布后会显示在这里。</p>@endforelse</div>
    </section>
    @if($starterTask = app(\App\Services\CourseCatalog::class)->starter())
    <section class="first-step-band"><div class="first-step-icon"><i data-lucide="play"></i></div><div><span class="eyebrow">先试着做成一小步</span><h2>{{ $starterTask['title'] }}</h2><p>人与 Agent 协作 · 亲自验收 · 无需登录</p></div><a class="button button-dark" href="{{ $starterTask['url'] }}">开始免费学习<i data-lucide="arrow-right"></i></a></section>
    @endif
    <section class="home-bottom"><div class="home-method"><div class="section-heading"><span class="eyebrow">不是看完了，而是做成了</span><h2>从第一步，到真正做成。</h2></div><div class="method-steps"><div><span class="method-number">01</span><h3>选一个任务</h3><p>从你真正想完成的事情开始。</p></div><div><span class="method-number">02</span><h3>完成一小步</h3><p>给 Agent 明确目标，观察它执行。</p></div><div><span class="method-number">03</span><h3>验收你的结果</h3><p>每完成一步，离做成更近一点。</p></div></div></div><div class="home-live"><div class="section-heading row-heading"><h2>一起解决真实问题</h2><a class="text-link" href="{{ route('live') }}">直播<i data-lucide="arrow-up-right"></i></a></div><span class="live-status"><span class="status-dot"></span>首场直播筹备中</span><h3>把一个网站，从想法做到上线</h3><p>人负责目标与验收，Agent 负责执行。</p><a class="text-link" href="{{ route('live') }}">查看直播安排<i data-lucide="arrow-right"></i></a></div></section>
</div>
@endsection
