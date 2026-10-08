@extends('layouts.frontend')
@section('title', '全部课程 · AI100分')
@section('content')
<div class="shell page-main" data-catalog>
    <div class="catalog-toolbar">
        <div class="filter-tabs" role="group" aria-label="问题类型">
            <button class="active" data-filter="all" aria-pressed="true">全部课程<span>{{ str_pad((string) count($series), 2, '0', STR_PAD_LEFT) }}</span></button>
            <button data-filter="create" aria-pressed="false">创作一个作品</button>
            <button data-filter="solve" aria-pressed="false">解决一个问题</button>
            <button data-filter="explore" aria-pressed="false">探索一个可能</button>
        </div>
        <label class="search-field"><i data-lucide="search"></i><input type="search" placeholder="搜索你想解决的问题" aria-label="搜索你想解决的问题" data-course-search></label>
    </div>
    <div class="course-grid">@forelse($series as $course)<x-course-card :course="$course" />@empty<p class="free-note">课程正在准备中，发布后会在这里展示。</p>@endforelse</div><div class="empty-results" hidden><i data-lucide="search-x"></i><h3>暂时没有匹配的问题</h3><p>换个关键词，或从已有的问题开始。</p><button class="text-button" data-reset-filters>查看全部问题</button></div><div class="catalog-note"><i data-lucide="sprout"></i><p>新的问题，正在变成新的100分钟。<span>从网站开始，一步一步做成。</span></p></div></div>
@endsection
