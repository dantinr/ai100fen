@extends('layouts.frontend')
@section('title', '个人中心 · AI100分')
@section('content')
<div class="shell page-main me-main">
    <div class="page-heading"><span class="eyebrow">每一小步，都算数</span><h1>个人中心<span class="heading-question">.</span></h1><p>管理你的账号，继续把事情做成。</p></div>
    @include('frontend.profile')
    <p class="question-account-link"><a class="text-link" href="{{ route('questions.mine') }}">查看我的私人问题<i data-lucide="arrow-up-right"></i></a></p>
    <div class="me-section-title"><h2>我的学习进度</h2><a class="text-link" href="{{ route('series.index') }}">开始一个新任务<i data-lucide="plus"></i></a></div>
    <p class="free-note">进度按当前课程课时数动态计算。增减课时后比例会变化，已保存的验收记录保留。</p>
    @forelse($courseProgress as $entry)
        <article class="free-progress-row"><div><h3><a href="{{ $entry['available'] ? route('series.show', $entry['course']->slug) : $entry['url'] }}">{{ $entry['course_title'] }}</a></h3><p>最近学习：{{ $entry['lesson_title'] }}</p>@if($entry['available'])<x-ufo-progress :series="$entry['course']->slug" :score="$entry['percent']" />@else<span class="free-badge">{{ $entry['notice'] }}</span><p class="free-note">学习和验收记录已保留。</p>@endif</div><a class="button button-dark" href="{{ $entry['url'] }}">{{ $entry['available'] ? '继续学习' : '查看记录' }}<i data-lucide="arrow-right"></i></a></article>
    @empty
        <div class="me-empty"><x-ufo-widget class="ufo-empty" /><h2>从第一步开始。</h2><p>完成课时验收并保存后，进度会显示在这里。</p><a class="button button-primary" href="{{ app(\App\Services\CourseCatalog::class)->starter()['url'] ?? route('series.index') }}">开始学习<i data-lucide="arrow-right"></i></a></div>
    @endforelse
    @include('frontend.free.progress')
</div>
@endsection
