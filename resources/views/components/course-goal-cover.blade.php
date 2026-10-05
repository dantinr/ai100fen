@props(['goal'])
<section class="course-goal-cover" data-course-goal aria-label="课程目标">
    <h2><i data-lucide="flag" aria-hidden="true"></i>课程目标</h2>
    <p>{{ $goal }}</p>
    {{ $slot }}
</section>
