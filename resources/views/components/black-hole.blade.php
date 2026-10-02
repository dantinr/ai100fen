@props(['variant' => 'nav'])
<span {{ $attributes->class(['black-hole', 'black-hole-'.$variant]) }} aria-hidden="true">
    <svg viewBox="0 0 96 72" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
        <ellipse class="black-hole-halo" cx="48" cy="36" rx="40" ry="24" fill="var(--black-hole-glow)" opacity="var(--black-hole-glow-opacity)"/>
        <g class="black-hole-disk" stroke-width="var(--black-hole-line-width)" stroke-linecap="round">
            <ellipse cx="48" cy="36" rx="37" ry="13" transform="rotate(-18 48 36)" stroke="var(--black-hole-disk)"/>
            <ellipse cx="48" cy="36" rx="30" ry="10" transform="rotate(-18 48 36)" stroke="var(--black-hole-accent)" stroke-dasharray="36 10 12 8"/>
            <path d="M13 38q-2 13 28 14M72 17l7 1M20 21l-4-3" stroke="var(--black-hole-detail)"/>
        </g>
        <circle cx="48" cy="36" r="14" fill="var(--black-hole-core)" stroke="var(--black-hole-outline)" stroke-width="var(--black-hole-line-width)"/>
        <path d="M15 44q30 12 66-8" stroke="var(--black-hole-disk)" stroke-width="var(--black-hole-line-width)" stroke-linecap="round"/>
    </svg>
</span>
