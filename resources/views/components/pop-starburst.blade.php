@props(['label', 'caption' => ''])
<div {{ $attributes->class(['pop-starburst']) }}>
    <div><strong>{{ $label }}</strong>@if($caption)<span>{{ $caption }}</span>@endif</div>
</div>
