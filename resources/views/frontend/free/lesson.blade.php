@extends('layouts.frontend')
@php($isPreview = $isPreview ?? false)
@section('title', $series->title.($isPreview ? ' · 前台预览' : ' · 免费实验室').' · AI100分')
@section('content')
<div class="shell page-main free-learning">
    <a class="text-link" href="{{ $isPreview ? \App\Filament\Resources\CourseSeries\CourseSeriesResource::getUrl('edit', ['record' => $series]) : route('free.index') }}"><i data-lucide="arrow-left"></i>{{ $isPreview ? '返回课程编辑' : '免费实验室' }}</a>
    @if($isPreview)<p class="free-panel" role="status">管理员前台预览 · {{ ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$series->status] }} · 当前课时：{{ ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$lesson->status] }}。预览不发布课程、不保存进度；资料仅显示名称。</p>@endif
    <header class="free-lesson-heading"><span class="free-badge">{{ $series->is_free ? '完整免费' : '付费课程 · ¥'.$series->price }} · {{ strtoupper($series->category) }}</span><h1>{{ $series->title }}</h1><p>{{ $series->final_outcome }}</p><span class="free-note">约 {{ $series->minutes }} 分钟 · 图文实践 · 验收后完成100分</span></header>
    <div class="free-learning-grid">
        <article class="free-lesson-content">
            <section class="free-panel"><h2 class="lesson-title-with-agent"><span>{{ $lesson->title }}</span><x-lesson-agent-mark :course="$series->slug" :lesson="$lesson->slug" /></h2><p class="free-note">第{{ $lesson->position }}节 · 约{{ $lesson->minutes }}分钟 · {{ $lesson->points }}分</p><h3>你要做成什么？</h3><p>{{ $lesson->goal }}</p><p>{{ $lesson->intro }}</p>
                @if($lesson->objectives)<ul>@foreach($lesson->objectives as $objective)<li>{{ $objective }}</li>@endforeach</ul>@endif
                @if($lesson->video_url)<a class="text-link" href="{{ $lesson->video_url }}" target="_blank" rel="noopener noreferrer">观看本课视频<i data-lucide="external-link"></i></a>@endif
            </section>
            @if($lesson->content)<section class="free-panel free-markdown">{!! \Illuminate\Support\Str::markdown($lesson->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</section>@endif
            <section class="free-panel"><h2>跟着这几步做</h2>@foreach($lesson->steps as $step)<div class="free-step"><h3>{{ $step['title'] }}</h3><p>{{ $step['body'] }}</p></div>@endforeach</section>
            <section class="free-panel"><div class="free-section-heading"><h2>交给 Agent 的 Prompt</h2><button class="text-button" type="button" data-copy="free-prompt">复制 Prompt<i data-lucide="copy"></i></button></div><pre id="free-prompt" class="free-code">{{ $lesson->prompt }}</pre></section>
            @if($lesson->code)
                <details class="free-panel free-source"><summary>查看完整示例：{{ $lesson->code_filename }}</summary><button class="text-button" type="button" data-copy="free-code">复制代码<i data-lucide="copy"></i></button><pre id="free-code" class="free-code">{{ $lesson->code }}</pre></details>
            @endif
            <section class="free-panel"><h2>资料与可运行起点</h2><div class="free-downloads">@foreach($lesson->resources as $resource)
                @if($isPreview)<span>{{ $resource['label'] }} <small>{{ $resource['name'] }}</small></span>
                @else<a class="text-link" href="{{ route('free.resource', [$series, $lesson->slug, $resource['name']]) }}"><i data-lucide="download"></i>{{ $resource['label'] }}<small>{{ $resource['name'] }}</small></a>@endif
            @endforeach</div></section>
            <section class="free-panel" aria-labelledby="free-check-title">
                <h2 id="free-check-title">亲自验收，才算做成</h2>
                @if($isPreview)
                    <p>验收清单预览，学习进度不会保存。</p>
                    @foreach($lesson->checks as $check)<label class="free-check"><input type="checkbox" disabled><span>{{ $check }}</span></label>@endforeach
                @else
                <p>逐项检查实际成果后再勾选。这里只保存你的验收确认，不会自动检查电脑里的文件。</p>
                <form data-free-progress action="{{ route('free.progress', [$series, $lesson->slug]) }}" method="post">
                    @csrf
                    @foreach($lesson->checks as $index => $check)
                        <label class="free-check"><input type="hidden" name="checks[{{ $index }}]" value="0"><input type="checkbox" name="checks[{{ $index }}]" value="1" @checked($progress?->checks[$index] ?? false)><span>{{ $check }}</span></label>
                    @endforeach
                    @auth
                        <button class="button button-primary" type="submit" data-free-save>保存验收进度<i data-lucide="check"></i></button>
                        <p class="free-note" data-free-status role="status">{{ session('free-progress-saved') ? '已保存到你的账号。' : '进度保存到当前账号，可跨设备继续；全部验收后完成100分。' }}</p>
                    @else
                        <p class="free-note">你可以直接完成全部任务。访客的勾选仅在当前页面生效；登录后可保存到账号。</p><a class="button button-dark" href="{{ route('login', ['redirect' => route('free.lesson', [$series, $lesson->slug], false)]) }}">登录并保存进度<i data-lucide="arrow-right"></i></a>
                    @endauth
                    @error('checks')<p class="free-error" role="alert">{{ $message }}</p>@enderror
                </form>
                @endif
            </section>
        </article>
        <aside class="free-sidebar"><div class="free-panel">
            <span class="eyebrow">你的任务路线</span><h2>{{ $lesson->title }}</h2>
            @if(!$isPreview)<p>已验收进度：<strong data-free-percent>{{ $progress?->progress_percent ?? 0 }}%</strong></p><p class="free-note">这是当前步骤的验收进度；完整任务全部通过才达到100分。</p>
            <p>任务完成：<strong data-free-score>{{ $score }}</strong> / 100分</p>
            @endif
            @foreach($lessons as $item)<a class="free-outline-link" href="{{ route($isPreview ? 'courses.preview' : 'free.lesson', [$series, $item->slug]) }}" @if($item->id === $lesson->id) aria-current="page" @endif><span class="lesson-title-with-agent"><span>{{ $item->position }}. {{ $item->title }}</span><x-lesson-agent-mark :course="$series->slug" :lesson="$item->slug" /></span></a>@endforeach
            @if($series->description)<div class="free-markdown">{!! \Illuminate\Support\Str::markdown($series->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>@endif
            @if($series->objectives)<details class="free-roles"><summary>课程目标</summary><ul>@foreach($series->objectives as $objective)<li>{{ $objective }}</li>@endforeach</ul></details>@endif
            <details class="free-roles"><summary>整个任务怎么验收？</summary><ul>@foreach($series->completion_criteria as $criterion)<li>{{ $criterion }}</li>@endforeach</ul></details>
            <details class="free-roles"><summary>Agent 做什么，人判断什么？</summary><h3>Agent 负责</h3><ul>@foreach($series->agent_role as $item)<li>{{ $item }}</li>@endforeach</ul><h3>你来判断</h3><ul>@foreach($series->human_judgment_required as $item)<li>{{ $item }}</li>@endforeach</ul></details>
        </div></aside>
    </div>
    <x-course-relations :groups="$relationGroups ?? []" />
</div>
@endsection
