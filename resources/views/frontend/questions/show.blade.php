@extends('layouts.frontend')
@section('title', '私人问题 · AI100分')
@section('content')
<div class="shell page-main question-private-main">
    <a class="text-link" href="{{ route('questions.mine') }}">返回我的私人问题<i data-lucide="arrow-left"></i></a>
    <div class="page-heading"><span class="eyebrow">问题 #{{ $question->id }} · 仅你可见</span><h1>{{ $question->title }}</h1><p>问题已收集。尚未公开发布，不代表问题已解决或课程已安排。</p></div>
    <article class="question-private-card">
        <h2>{{ ['solve'=>'Solve · 解决一个问题','create'=>'Create · 创作一个作品','explore'=>'Explore · 探索一个可能'][$question->category] }}</h2>
        <h3>想达到的目标</h3><p>{{ $question->goal }}</p>
        @if($question->scope)<h3>范围与限制</h3><p>{{ $question->scope }}</p>@endif
        <h3>期望产出</h3><p>{{ $question->outcome }}</p>
        <h3>验收标准</h3><ul>@foreach($question->completion_criteria as $criterion)<li>{{ $criterion }}</li>@endforeach</ul>
        <small>收集于 {{ $question->created_at->timezone('Asia/Shanghai')->format('Y-m-d H:i') }} · 未保存聊天记录</small>
    </article>
</div>
@endsection
