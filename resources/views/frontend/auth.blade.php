@extends('layouts.frontend')
@section('title', ($register ? '注册' : '登录').' · AI100分')
@section('content')
<div class="shell auth-main"><div class="auth-surface"><span class="eyebrow">你的每一步，都值得记录</span><h1>{{ $register ? '开始你的100分。' : '继续把问题做成。' }}</h1><p>{{ $register ? '账号注册正在准备中。' : '账号登录正在准备中。' }}</p><div class="auth-unavailable"><i data-lucide="user-round"></i><h2>账号服务尚未开放</h2><p>现在可以免费体验第一课，学习记录会保存在当前浏览器。</p></div><a class="button button-primary full-width" href="{{ route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) }}">先开始免费试看<i data-lucide="arrow-right"></i></a><a class="text-link auth-secondary" href="{{ route('me') }}">查看本机学习记录<i data-lucide="arrow-right"></i></a></div></div>
@endsection
