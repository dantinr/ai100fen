<section class="free-account-progress" aria-labelledby="free-account-title">
    <div class="me-section-title"><h2 id="free-account-title">课时验收记录</h2><a class="text-link" href="{{ route('free.index') }}">开始一个小任务<i data-lucide="plus"></i></a></div>
    <p class="free-note">这些验收记录已保存到你的账号，可跨设备继续。</p>
    @forelse($freeProgress as $record)
        <article class="free-progress-row"><div><x-lesson-completion :completed="(bool) $record['completed_at']" />@if(!$record['available'])<span class="free-badge">{{ $record['notice'] }}</span>@elseif(!$record['completed_at'])<span class="free-badge">继续验收</span>@endif<h3>{{ $record['course_title'] }}</h3><p>{{ $record['lesson_title'] }} · 验收进度 {{ $record['progress_percent'] }}%</p>@if($record['available'])<x-course-share :url="route('series.show', $record['course'])" :title="$record['course_title']" :text="$record['course']->final_outcome" />@endif</div><a class="button button-dark" href="{{ $record['url'] }}">{{ !$record['available'] ? '查看记录' : ($record['completed_at'] ? '查看成果清单' : '继续任务') }}<i data-lucide="arrow-right"></i></a></article>
    @empty
        <p>还没有保存免费任务进度。先做一件小事，再回来看看。</p>
    @endforelse
</section>
