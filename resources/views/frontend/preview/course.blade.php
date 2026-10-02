@extends('layouts.frontend')
@section('title', $series->title.' · 前台预览 · AI100分')
@section('content')
<div class="shell page-main free-learning">
    <a class="text-link" href="{{ \App\Filament\Resources\CourseSeries\CourseSeriesResource::getUrl('edit', ['record' => $series]) }}"><i data-lucide="arrow-left"></i>返回课程编辑</a>
    <p class="free-panel" role="status">管理员前台预览 · {{ ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$series->status] }}。此页面仅管理员可访问。</p>
    <header class="free-lesson-heading"><span class="free-badge">{{ $series->is_free ? '完整免费' : '付费课程 · ¥'.$series->price }} · {{ strtoupper($series->category) }}</span><h1>{{ $series->title }}</h1><p>{{ $series->final_outcome }}</p><span class="free-note">约 {{ $series->minutes }} 分钟</span></header>
    @if($series->description)<section class="free-panel free-markdown">{!! \Illuminate\Support\Str::markdown($series->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</section>@endif
    @if($series->objectives)<section class="free-panel"><h2>课程目标</h2><ul>@foreach($series->objectives as $objective)<li>{{ $objective }}</li>@endforeach</ul></section>@endif
    <section class="free-panel"><h2>课程大纲</h2><p>还没有课时，添加后可以预览课时正文与完整路线。</p><a class="button button-primary" href="{{ \App\Filament\Resources\Lessons\LessonResource::getUrl('create', ['series' => $series->id]) }}">添加课时<i data-lucide="plus"></i></a></section>
    @if($series->completion_criteria)<section class="free-panel"><h2>整个任务怎么验收？</h2><ul>@foreach($series->completion_criteria as $criterion)<li>{{ $criterion }}</li>@endforeach</ul></section>@endif
    <x-course-relations :groups="$relationGroups ?? []" />
</div>
@endsection
