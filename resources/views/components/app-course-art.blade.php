@props(['course'])
@if($course['cover_url'] ?? null)
    <img src="{{ $course['cover_url'] }}" alt="" loading="lazy" decoding="async">
@else
@php
    $icon = match ($course['image']) {
        'website' => 'globe',
        'support', 'knowledge' => 'notebook-pen',
        'miniapp', 'software', 'work-tool' => 'monitor',
        'analysis', 'creator' => 'sprout',
        'spreadsheet', 'report' => 'list-checks',
        'presentation', 'meeting' => 'clapperboard',
        'game' => 'play',
        'files', 'digital-assets' => 'download',
        'agent' => 'user-round',
        default => 'flag',
    };
@endphp
<div @class(['app-course-art', 'art-work' => $course['category'] === 'solve']) role="img" aria-label="{{ $course['outcome'] }}">
    <span class="art-time" aria-hidden="true">{{ $course['minutes'] }}<small>MINUTES</small></span>
    <span class="art-icon" aria-hidden="true"><i data-lucide="{{ $icon }}"></i></span>
    <span class="art-caption" aria-hidden="true">{{ $course['tag'] }}<span>一个真实问题 ↗</span></span>
</div>
@endif
