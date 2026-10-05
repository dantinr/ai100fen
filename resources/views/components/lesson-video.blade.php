@props(['source' => null, 'poster' => null, 'title' => '本课视频', 'stepsHref' => '#lesson-steps', 'compact' => false])
@php
    $isDemo = !$source && app()->environment('local') && config('player.demo_enabled');
    $videoSource = $isDemo ? config('player.demo_source') : $source;
    $validSource = is_string($videoSource) && filter_var($videoSource, FILTER_VALIDATE_URL) && parse_url($videoSource, PHP_URL_SCHEME) === 'https';
    $playerId = 'lesson-player-'.\Illuminate\Support\Str::uuid();
    $options = [
        'version' => config('player.sdk_version'),
        'source' => $videoSource,
        'cover' => $poster ?: ($isDemo ? config('player.demo_poster') : null),
        'license' => ['domain' => config('player.license_domain'), 'key' => config('player.license_key')],
    ];
@endphp
@if($validSource)
<section class="lesson-video" id="lesson-video" aria-labelledby="{{ $playerId }}-title" data-lesson-video data-player-options="{{ json_encode($options, JSON_UNESCAPED_SLASHES) }}">
    <header @class(['lesson-video-heading', 'lesson-video-heading-compact' => $compact])>
        <div><span class="lesson-section-kicker"><i data-lucide="clapperboard"></i>视频演示</span><h2 id="{{ $playerId }}-title" @if($compact) class="sr-only" @endif>{{ $title }}</h2></div>
        @if($isDemo)<span class="lesson-video-badge">演示视频 · 非课程录播</span>@endif
    </header>
    <div class="lesson-video-stage">
        <div id="{{ $playerId }}" class="lesson-video-mount prism-player" data-video-mount aria-label="{{ $title }}播放器"></div>
        <div class="lesson-video-placeholder" data-video-placeholder>
            <i data-lucide="video" aria-hidden="true"></i>
            <p data-video-message role="status">正在准备视频…</p>
            <button class="button button-outline" type="button" data-video-retry hidden>重试加载<i data-lucide="arrow-right"></i></button>
            <a class="text-link" href="{{ $stepsHref }}" data-video-fallback hidden>先看图文步骤<i data-lucide="arrow-down"></i></a>
        </div>
    </div>
    <div class="lesson-video-footer">
        <p>{{ $isDemo ? '当前为公开测试片源，仅用于预览播放器效果。' : '边看边做，完成后亲自验收。' }}</p>
        <span><i data-lucide="circle-check"></i>验收做成的结果</span>
    </div>
    <noscript><p class="free-note">请启用 JavaScript 播放视频，也可以继续阅读图文步骤。</p></noscript>
</section>
@endif
