@props(['state' => 'idle', 'progress' => 0, 'size' => 'medium'])
@php
    $state = in_array($state, ['idle', 'flying', 'scanning', 'completed'], true) ? $state : 'idle';
    $size = in_array($size, ['small', 'medium', 'large'], true) ? $size : 'medium';
    $progress = max(0, min(100, (int) $progress));
@endphp
<div {{ $attributes->class(['ufo-widget', 'ufo-size-'.$size]) }} data-ufo data-state="{{ $state }}" data-progress="{{ $progress }}" aria-hidden="true">
    <svg viewBox="0 0 320 240" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
        <g class="ufo-sparks" stroke="#111111" stroke-width="3" stroke-linecap="round">
            <path d="M48 39v16m-8-8h16M274 64v12m-6-6h12"/>
            <path d="m259 25 4 10 10 4-10 4-4 10-4-10-10-4 10-4z" fill="#ffd84d"/>
            <circle cx="73" cy="83" r="3" fill="#ff8a3d" stroke="none"/>
        </g>
        <ellipse class="ufo-shadow" cx="160" cy="224" rx="66" ry="7" fill="#111111" opacity=".12"/>
        <path class="ufo-beam" d="m132 153-37 61q65 21 130 0l-37-61z" fill="#ffd84d" opacity=".22"/>
        <ellipse class="ufo-halo" cx="160" cy="116" rx="125" ry="75" fill="#ffd84d" opacity="0"/>
        <g class="ufo-ship" stroke="#111111" stroke-width="4.5" stroke-linejoin="round">
            <path d="m87 132-22 26 35-4m133-22 22 26-35-4" fill="#ff8a3d"/>
            <path d="M51 120q109-34 218 0v16q-109 68-218 0z" fill="#ff8a3d"/>
            <ellipse cx="160" cy="120" rx="109" ry="31" fill="#ffd84d"/>
            <path d="M111 109c-1-78 99-78 98 0q-49 21-98 0z" fill="#b8ef55"/>
            <path d="M128 90q-1-29 25-39m13-4 9 2" stroke="#ffffff" stroke-width="7" stroke-linecap="round"/>
            <path d="M116 112q44 15 88 0" stroke-width="3"/>
            <path d="M69 127q91 44 182 0" stroke-width="3"/>
            <g class="ufo-lights" fill="#ffffff" stroke-width="3">
                <ellipse cx="88" cy="124" rx="8" ry="4" transform="rotate(14 88 124)"/>
                <ellipse cx="123" cy="135" rx="8" ry="4"/>
                <ellipse cx="197" cy="135" rx="8" ry="4"/>
                <ellipse cx="232" cy="124" rx="8" ry="4" transform="rotate(-14 232 124)"/>
            </g>
            <path d="M145 161h30l-5 10h-20z" fill="#111111" stroke-width="3"/>
            <rect x="139" y="113" width="42" height="29" rx="6" fill="#ffffff" stroke-width="3"/>
            <text x="160" y="133" text-anchor="middle" fill="#111111" stroke="none" font-family="Arial,sans-serif" font-size="16" font-weight="900">100</text>
        </g>
    </svg>
</div>
