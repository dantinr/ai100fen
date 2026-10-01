@props(['label', 'caption' => ''])
<div {{ $attributes->class(['app-score-emblem']) }}>
    <div><strong>{{ $label }}</strong>@if($caption)<span>{{ $caption }}</span>@endif</div>
</div>
