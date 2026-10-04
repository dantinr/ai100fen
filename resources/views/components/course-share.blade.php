@props(['url', 'title', 'text' => '和 AI / Agent 一起，把一件事做成。'])
<div class="course-share" data-course-share data-share-url="{{ $url }}" data-share-title="{{ $title }}" data-share-text="{{ $text }}">
    <button class="text-button" type="button" data-share-trigger><i data-lucide="share-2" aria-hidden="true"></i>分享课程</button>
    <div class="course-share-fallback" data-share-fallback hidden>
        <label>课程链接<input type="url" value="{{ $url }}" readonly data-share-link></label>
        <button class="text-button" type="button" data-share-copy><i data-lucide="copy" aria-hidden="true"></i>复制链接</button>
    </div>
    <span class="course-share-status" data-share-status role="status"></span>
</div>
