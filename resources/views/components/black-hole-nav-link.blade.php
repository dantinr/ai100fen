@props(['href', 'active' => false])
<a href="{{ $href }}" {{ $attributes->class(['black-hole-nav-link', 'active' => $active]) }} @if($active) aria-current="page" @endif>
    <span class="black-hole-nav-scene" aria-hidden="true"><x-black-hole /></span>
    <span class="black-hole-nav-label">{{ $slot }}</span>
</a>
