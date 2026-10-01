@extends('layouts.frontend')
@section('title', '问题池 · AI100分')
@section('content')
<div class="shell page-main questions-main">
    <div class="page-heading">
        <span class="eyebrow">从真实问题，找到下一步</span>
        <h1>问题池</h1>
        <p>收集想解决的问题，逐步把它们做成直播与100分钟课程。</p>
    </div>
    <section class="me-empty" aria-labelledby="questions-status">
        <x-ufo-widget class="ufo-empty" />
        <h2 id="questions-status">问题池正在准备中</h2>
        <p>问题提交暂未开放。先看看已有的问题，找到你想做成的一件事。</p>
        <a class="button button-dark" href="{{ route('series.index') }}">看看已有的问题<i data-lucide="arrow-right"></i></a>
    </section>
</div>
@endsection
