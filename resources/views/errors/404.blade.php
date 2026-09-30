@extends('layouts.frontend')
@section('title', '页面未找到 · AI100分')
@section('content')
<div class="shell error-main"><span class="eyebrow">404</span><h1>这条路径，暂时还没有。</h1><p>回到已有的问题，找到你的下一步。</p><a class="button button-primary" href="{{ route('series.index') }}">查看100分钟系列<i data-lucide="arrow-right"></i></a></div>
@endsection
