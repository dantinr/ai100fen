@props(['course', 'lesson'])
@php($role = $course === 'build-a-website' ? \App\Support\WebsiteSetupLessons::roleFor($lesson) : null)
@if($role && str_contains($role, 'Agent'))
    <span class="lesson-agent-mark" data-lesson-agent="{{ $lesson }}" role="img" aria-label="{{ $role === 'Agent' ? 'Agent执行' : '人与Agent协作' }}" title="{{ $role === 'Agent' ? 'Agent执行' : '人与Agent协作' }}">
        <x-paul-avatar size="small" :portrait="true" />
        <span class="lesson-agent-name" aria-hidden="true">Z</span>
    </span>
@endif
