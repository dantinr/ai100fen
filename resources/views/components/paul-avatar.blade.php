@props(['state' => 'idle', 'size' => 'medium'])
@php
    $state = in_array($state, ['idle', 'idea', 'got_it', 'so_so', 'awkward'], true) ? $state : 'idle';
    $size = in_array($size, ['small', 'medium', 'large'], true) ? $size : 'medium';
@endphp
<span {{ $attributes->class(['paul-avatar', 'paul-size-'.$size]) }} data-paul-avatar data-state="{{ $state }}" aria-hidden="true">
    <svg viewBox="0 0 180 200" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
        <ellipse cx="90" cy="189" rx="43" ry="6" fill="var(--paul-stroke)" opacity=".12"/>
        <g class="paul-character" stroke="var(--paul-stroke)" stroke-width="var(--paul-stroke-width)" stroke-linecap="round" stroke-linejoin="round">
            <g class="paul-body">
                <path d="M63 132q-16 13-14 44l8 8h67l8-8q2-31-17-44z" fill="var(--paul-suit)"/>
                <path d="m70 130 20 17 20-17v-12H70z" fill="var(--paul-skin)"/>
                <path d="m62 132 18 19 10-4 10 4 18-19" fill="var(--paul-suit-accent)"/>
                <path d="M90 149v31" stroke-width="1.5"/>
                <rect x="100" y="153" width="20" height="17" rx="4" fill="var(--paul-suit-accent)"/>
                <path d="m110 157 1.5 3 3.5.5-2.5 2.5.5 3.5-3-1.5-3 1.5.5-3.5-2.5-2.5 3.5-.5z" fill="var(--paul-light)" stroke="none"/>
            </g>
            <g class="paul-arm paul-arm-left">
                <path d="M55 144q-10 11-22 15l-4 10q20-1 31-14" fill="var(--paul-suit)"/>
                <path d="M34 159q-1-10-6-7l-1 7-8-2q-5-1-4 4l8 3-7 2q-4 2-1 5l10-2q10 4 12-2z" fill="var(--paul-skin)"/>
            </g>
            <g class="paul-arm paul-arm-right">
                <path d="M126 144q10 11 22 15l4 10q-20-1-31-14" fill="var(--paul-suit)"/>
                <path d="M146 159q1-10 6-7l1 7 8-2q5-1 4 4l-8 3 7 2q4 2 1 5l-10-2q-10 4-12-2z" fill="var(--paul-skin)"/>
            </g>
            <g class="paul-head">
                <path d="M66 52q-13-13-7-27m53 27q15-10 13-22" stroke="var(--paul-skin-shadow)"/>
                <circle cx="60" cy="21" r="6" fill="var(--paul-suit-accent)"/>
                <circle cx="125" cy="25" r="5" fill="var(--paul-suit-accent)"/>
                <path d="m46 74-18 8 12 20 12-7m83-21 18 8-12 20-12-7" fill="var(--paul-skin-shadow)"/>
                <path d="M41 77c0-28 25-39 49-36 30-3 49 12 49 39 0 26-18 44-34 51q-15 9-31-1C55 120 39 105 41 77z" fill="var(--paul-skin)"/>
                <path d="M49 68q12-18 29-18" stroke="var(--paul-light)" opacity=".55"/>
                <path d="m88 59 4-4 4 4-4 4z" fill="var(--paul-skin-shadow)" stroke="none"/>
                <g class="paul-eyes" fill="var(--paul-eye)">
                    <ellipse cx="69" cy="89" rx="14" ry="19" transform="rotate(-13 69 89)"/>
                    <ellipse cx="111" cy="89" rx="14" ry="19" transform="rotate(13 111 89)"/>
                    <g class="paul-eye-shine" fill="var(--paul-light)" stroke="none"><ellipse cx="64" cy="82" rx="4" ry="5"/><ellipse cx="106" cy="82" rx="4" ry="5"/><circle cx="75" cy="96" r="2"/><circle cx="117" cy="96" r="2"/></g>
                </g>
                <g class="paul-eyelids" stroke="var(--paul-skin-shadow)" stroke-width="3"><path d="m56 75 24 6m19 0 24-6"/></g>
                <path d="M86 108h4m6 0h1" stroke="var(--paul-skin-shadow)" stroke-width="2"/>
                <path class="paul-mouth paul-mouth-smile" d="M78 115q12 10 25-1"/>
                <path class="paul-mouth paul-mouth-flat" d="M79 119h22"/>
                <path class="paul-mouth paul-mouth-awkward" d="M79 119q6-5 12 0t12-2"/>
                <g class="paul-fx paul-sweat"><path d="M139 92q-11 14-6 18 12 6 6-18z" fill="var(--paul-sweat)"/><path d="m137 102-1 3" stroke="var(--paul-light)" stroke-width="2"/></g>
            </g>
            <g class="paul-fx paul-sparkles" fill="var(--paul-fx)"><path d="m148 35 3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/><path d="M33 33v10m-5-5h10" stroke="var(--paul-fx)"/></g>
        </g>
    </svg>
</span>
