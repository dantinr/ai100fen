@extends('layouts.frontend')
@section('title', '我的私人问题 · AI100分')
@section('content')
<div class="shell page-main question-private-main">
    <div class="page-heading"><span class="eyebrow">仅你可见</span><h1>我的私人问题<span class="heading-question">.</span></h1><p>这里只收集你确认的任务卡，不包含聊天记录。收集不代表问题已解决或课程已安排。</p></div>
    <a class="button button-primary" href="{{ route('questions') }}#question-lab">定义一个新问题<i data-lucide="plus"></i></a>
    <div class="question-private-list">
    @forelse($questions ?? [] as $question)
        <article class="question-private-card"><span class="eyebrow">{{ ['solve'=>'Solve · 解决问题','create'=>'Create · 创作作品','explore'=>'Explore · 探索可能'][$question->category] }}</span><h2><a href="{{ route('questions.show', $question) }}">{{ $question->title }}</a></h2><p>{{ $question->outcome }}</p><small>收集于 {{ $question->created_at->timezone('Asia/Shanghai')->format('Y-m-d H:i') }}</small></article>
    @empty<p>还没有已收集的问题。先和 Z 把一个问题想清楚。</p>@endforelse
    </div>
    @if($questions){{ $questions->links() }}@endif
</div>
@endsection
