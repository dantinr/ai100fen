@extends('layouts.frontend')
@section('title', '问题池 · AI100分')
@section('content')
<div class="shell page-main questions-main" data-question-topics>
    <header class="question-hero">
        <div class="page-heading">
            <span class="eyebrow">问题池 / AI + AGENT</span>
            <h1>把问题扔进黑洞<span class="heading-question">.</span></h1>
            <p>告诉 Z，你遇到了什么问题。你来确认，黑洞负责收集。</p>
            <a class="button button-primary question-chat-entry" href="#question-lab">开始与 Z 对话<i data-lucide="arrow-right"></i></a>
        </div>
        <div class="question-hero-art" aria-hidden="true">
            <x-black-hole variant="hero" />
            <span>收集未知，先想清楚。</span>
        </div>
    </header>
    <x-question-workspace />
    <div class="question-explorer">
        <section class="question-topic-board" aria-labelledby="question-topics-title">
            <div class="question-section-heading">
                <h2 id="question-topics-title">大家正在讨论什么？</h2>
                <span>点击一个话题</span>
            </div>
            <nav class="question-topic-cloud" aria-label="AI 与 Agent 话题">
                @foreach ($topics as $id => $topic)
                    <a class="question-topic-tag" data-topic-link="{{ $id }}" data-tone="{{ $topic['tone'] }}"
                       href="{{ route('questions', ['topic' => $id]) }}#question-detail"
                       aria-controls="topic-{{ $id }}" @if ($selectedTopic === $id) aria-current="true" @endif>
                        <span class="question-tag-mark" aria-hidden="true">#</span>
                        {{ $topic['label'] }}<i data-lucide="arrow-up-right" aria-hidden="true"></i>
                    </a>
                @endforeach
            </nav>
            <p class="question-topic-note">近期讨论话题 · 人工整理 · <time datetime="{{ $reviewedOn }}">{{ $reviewedOn }}</time></p>
            <p class="sr-only" data-topic-status role="status" aria-live="polite"></p>
        </section>
        <section class="question-topic-detail" id="question-detail" aria-label="选中的问题与第一步">
            @foreach ($topics as $id => $topic)
                <article id="topic-{{ $id }}" class="question-topic-panel" data-topic-panel="{{ $id }}" @if ($selectedTopic !== $id) hidden @endif>
                    <span class="eyebrow">一个问题，一次小尝试</span>
                    <h2>{{ $topic['question'] }}</h2>
                    <p class="question-topic-context">{{ $topic['context'] }}</p>
                    <div class="question-first-step">
                        <span class="question-step-number" aria-hidden="true">01</span>
                        <div><h3>先从这里开始</h3><p>{{ $topic['step'] }}</p></div>
                    </div>
                    <a class="button button-primary" href="{{ route('free.index', ['category' => $topic['category']]) }}">做一个免费小任务<i data-lucide="arrow-right" aria-hidden="true"></i></a>
                    @if ($topic['course'])
                        <a class="text-link question-course-link" href="{{ route('series.show', ['slug' => $topic['course']]) }}">看看相关课程方向<i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
                    @endif
                    <p class="question-source-link">话题参考：<a href="{{ $sources[$topic['source']]['url'] }}" target="_blank" rel="noopener noreferrer">{{ $sources[$topic['source']]['title'] }}<span class="sr-only">（新窗口）</span></a></p>
                </article>
            @endforeach
        </section>
    </div>
    <aside class="question-submit-note">
        <i data-lucide="info" aria-hidden="true"></i>
        <p>也可以先从话题找灵感。私人问题仅本人可见，公开提交与投票暂未开放。</p>
        <a class="text-link" href="{{ route('series.index') }}">看看全部课程<i data-lucide="arrow-right" aria-hidden="true"></i></a>
    </aside>
    <details class="question-research-notes">
        <summary>话题从哪里来？</summary>
        <p>依据近期公开的 Agent 产品、使用研究与工程文章整理，问题由本站归纳，不代表原文提问、全网热度排名或实时统计。整理日期：{{ $reviewedOn }}。</p>
        <ul>@foreach ($sources as $source)<li><a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['title'] }}<span class="sr-only">（新窗口）</span></a></li>@endforeach</ul>
    </details>
</div>
@endsection
