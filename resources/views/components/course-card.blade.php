@props(['course'])
<article class="course-card" data-course-card data-category="{{ $course['category'] }}" data-search="{{ $course['title'] }} {{ $course['question'] }} {{ $course['tag'] }} {{ $course['description'] }}">
    <a class="course-visual visual-{{ $course['image'] }}" href="{{ route('series.show', $course['slug']) }}" aria-label="查看{{ $course['title'] }}">
        <x-pop-course-art :course="$course" />
        <span class="visual-label">{{ $course['available'] ? '第一课免费' : '正在筹备' }}</span>
    </a>
    <div class="course-card-content"><div class="course-kicker"><span>{{ $course['tag'] }}</span><span>¥{{ $course['price'] }} / 门</span></div><h3><a href="{{ route('series.show', $course['slug']) }}">{{ $course['question'] }}</a></h3><p>{{ $course['description'] }}</p><div class="course-card-bottom"><span>{{ $course['title'] }}</span><a class="course-arrow" href="{{ route('series.show', $course['slug']) }}" aria-label="查看{{ $course['title'] }}" title="查看系列"><i data-lucide="arrow-up-right"></i></a></div></div>
</article>
