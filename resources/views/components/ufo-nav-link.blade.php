@props(['href', 'active' => false])
<a href="{{ $href }}" {{ $attributes->class(['ufo-nav-link', 'active' => $active]) }} @if($active) aria-current="page" @endif>
    <div class="ufo-nav-scene" aria-hidden="true"><x-ufo-widget size="small" /></div>
    <span class="ufo-nav-label">{{ $slot }}</span>
</a>
