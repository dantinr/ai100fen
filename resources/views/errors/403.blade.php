@extends('layouts.frontend')
@section('title', '内容暂未开放 · AI100分')
@section('content')
<div class="shell error-main"><span class="eyebrow">403</span><h1>这份内容，暂时还不能访问。</h1><p>从免费的第一课开始，先完成一小步。</p><a class="button button-primary" href="{{ route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) }}">开始免费试看<i data-lucide="arrow-right"></i></a></div>
@endsection
