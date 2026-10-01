@extends('layouts.frontend')
@section('content')
<div class="shell home-main">
    <section class="home-heading ufo-home-heading">
        <div class="home-heading-copy">
            <div class="eyebrow"><span class="status-dot"></span>从一个真实问题开始</div>
            <h1>你现在想解决<br><span class="headline-mark">什么问题<span class="heading-question">？</span></span></h1>
            <p>用 AI 帮你解决{{ count($series) }}个真实问题。每门课程100元，订阅299元/月，全部课程随便看。</p>
            <div class="heading-meta"><span><i data-lucide="timer"></i>10分钟，解决一步</span><span><i data-lucide="flag"></i>100分钟，完成一个问题</span><span><i data-lucide="circle-check"></i>每一步，都有结果</span></div>
            <a class="button button-dark ufo-home-start" href="{{ route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) }}">免费开始第一个10分<i data-lucide="arrow-right"></i></a>
        </div>
        <div class="ufo-explorer">
            <span class="ufo-sticker" aria-hidden="true">LET’S MAKE IT!</span>
            <x-ufo-widget size="large" />
            <p>先完成一小步。<span>再把一个问题做成。</span></p>
            <x-app-score-emblem label="100分" caption="代表完成" />
        </div>
    </section>
    <section class="catalog-section" aria-label="选择要解决的问题" data-catalog>
        <div class="catalog-heading"><h2>选一个问题，开始做成。</h2><a class="text-link" href="{{ route('questions') }}">看看问题池<i data-lucide="arrow-up-right"></i></a></div>
        <div class="catalog-toolbar"><div class="filter-tabs" role="group" aria-label="问题类型"><button class="active" data-filter="all" aria-pressed="true">全部问题<span>{{ str_pad((string) count($series), 2, '0', STR_PAD_LEFT) }}</span></button><button data-filter="build" aria-pressed="false">创造一个作品</button><button data-filter="work" aria-pressed="false">解决工作问题</button></div><a class="text-link all-series" href="{{ route('series.index') }}">全部100分钟<i data-lucide="arrow-right"></i></a></div>
        <div class="course-grid">@foreach($series as $course)<x-course-card :course="$course" />@endforeach</div>
        <div class="empty-results" hidden><i data-lucide="search-x"></i><h3>暂时没有匹配的问题</h3><button class="text-button" data-reset-filters>查看全部问题</button></div>
    </section>
    <section class="first-step-band"><div class="first-step-icon"><i data-lucide="play"></i></div><div><span class="eyebrow">先试着做成一小步</span><h2>用10分钟，让第一个网页出现在互联网上。</h2><p>服务器与 IP · 图文试看 · 无需登录</p></div><a class="button button-dark" href="{{ route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) }}">开始免费试看<i data-lucide="arrow-right"></i></a></section>
    <section class="home-bottom"><div class="home-method"><div class="section-heading"><span class="eyebrow">不是看完了，而是做成了</span><h2>从0分，到自己的100分。</h2></div><div class="method-steps"><div><span class="method-number">01</span><h3>选一个问题</h3><p>从你真正想完成的事情开始。</p></div><div><span class="method-number">02</span><h3>完成一小步</h3><p>给 Agent 明确目标，观察它执行。</p></div><div><span class="method-number">03</span><h3>验收你的结果</h3><p>每完成一步，离做成更近一点。</p></div></div></div><div class="home-live"><div class="section-heading row-heading"><h2>一起解决真实问题</h2><a class="text-link" href="{{ route('live') }}">直播<i data-lucide="arrow-up-right"></i></a></div><span class="live-status"><span class="status-dot"></span>首场直播筹备中</span><h3>把一个网站，从想法做到上线</h3><p>人负责目标与验收，Agent 负责执行。</p><a class="text-link" href="{{ route('live') }}">查看直播安排<i data-lucide="arrow-right"></i></a></div></section>
</div>
@endsection
