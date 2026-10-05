@props(['objectives' => [], 'goal' => null])
@php($items = $objectives ?: array_filter([$goal]))
@if($items)
<ol class="lesson-objectives" aria-label="本课目标">
    @foreach($items as $objective)
        <li><strong>目标 {{ $loop->iteration }}</strong><p>{{ $objective }}</p></li>
    @endforeach
</ol>
@endif
