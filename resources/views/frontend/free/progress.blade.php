<section class="free-account-progress" aria-labelledby="free-account-title">
    <div class="me-section-title"><h2 id="free-account-title">免费实验室 · 账号进度</h2><a class="text-link" href="{{ route('free.index') }}">开始一个小任务<i data-lucide="plus"></i></a></div>
    <p class="free-note">这些验收记录已保存到账号，与下面的本机试看记录分开。</p>
    @forelse($freeProgress as $record)
        <article class="free-progress-row"><div><span class="free-badge">{{ $record->completed_at ? '本步骤已验收' : '继续验收' }}</span><h3>{{ $record->lesson->series->title }}</h3><p>{{ $record->lesson->title }} · 验收进度 {{ $record->progress_percent }}%</p></div><a class="button button-dark" href="{{ route('free.lesson', [$record->lesson->series, $record->lesson->slug]) }}">{{ $record->completed_at ? '查看成果清单' : '继续任务' }}<i data-lucide="arrow-right"></i></a></article>
    @empty
        <p>还没有保存免费任务进度。先做一件小事，再回来看看。</p>
    @endforelse
</section>
