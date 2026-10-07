@props(['course'])
@php($keywords = $course['keywords'] ?? [$course['tag']])
<article class="course-card" data-course-card data-category="{{ $course['category'] }}" data-search="{{ $course['title'] }} {{ $course['question'] }} {{ $course['tag'] }} {{ $course['description'] }} {{ implode(' ', $keywords) }}">
    <a class="course-visual visual-{{ $course['image'] }}" href="{{ route('series.show', $course['slug']) }}" aria-label="查看{{ $course['title'] }}">
        <x-app-course-art :course="$course" />
    </a>
    <div class="course-card-content">
        <div class="course-kicker"><span>{{ $course['tag'] }}</span><span>{{ ($course['is_free'] ?? false) ? '完整免费' : '¥'.$course['price'].' / 门' }}</span></div>
        <h3><a href="{{ route('series.show', $course['slug']) }}">{{ $course['question'] }}</a></h3>
        <p>{{ $course['description'] }}</p>
        <div class="course-card-bottom">
            <span class="course-card-keywords" aria-label="课程标签">@foreach($keywords as $keyword)<span class="course-keyword">{{ $keyword }}</span>@endforeach</span>
            <a class="course-arrow" href="{{ route('series.show', $course['slug']) }}" aria-label="查看{{ $course['title'] }}" title="查看系列"><i data-lucide="arrow-up-right"></i></a>
        </div>
    </div>
</article>
