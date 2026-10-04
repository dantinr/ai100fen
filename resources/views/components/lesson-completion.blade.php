@props(['completed' => false, 'lesson' => null])
<span class="lesson-completion" @if($lesson) data-lesson-completion="{{ $lesson }}" @endif @if(!$completed) hidden @endif><i data-lucide="check" aria-hidden="true"></i>已完成</span>
