@extends('layouts.frontend')
@section('title', $entry['notice'].' · AI100分')
@section('content')
<div class="shell page-main me-main">
    <div class="page-heading"><span class="eyebrow">学习记录</span><h1>{{ $entry['notice'] }}</h1><p>这门课程当前无法学习，你的学习和验收记录已保留。</p></div>
    <section class="free-panel">
        <h2>{{ $entry['course_title'] }}</h2>
        <p>{{ $entry['lesson_title'] }}</p>
        <p>验收进度 {{ $entry['progress_percent'] }}% · {{ $entry['completed_at'] ? '已完成课时验收' : '尚未完成课时验收' }}</p>
        @if($entry['notes'] ?? null)<h3>你的课堂笔记</h3><p class="lesson-notes-history">{{ $entry['notes'] }}</p>@endif
        <a class="button button-primary" href="{{ route('me') }}">返回个人中心<i data-lucide="arrow-left" aria-hidden="true"></i></a>
        <a class="text-link" href="{{ route('series.index') }}">看看其他课程<i data-lucide="arrow-right" aria-hidden="true"></i></a>
    </section>
</div>
@endsection
