@props(['course'])
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
<div @class(['pop-course-art', 'pop-art-work' => $course['category'] === 'work']) role="img" aria-label="{{ $course['outcome'] }}">
    <span class="pop-art-time" aria-hidden="true">100<small>MINUTES</small></span>
    <span class="pop-art-icon" aria-hidden="true"><i data-lucide="{{ $icon }}"></i></span>
    <span class="pop-art-caption" aria-hidden="true">{{ $course['tag'] }}<span>一个真实问题 ↗</span></span>
</div>
