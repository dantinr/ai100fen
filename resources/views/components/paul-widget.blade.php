@php($websiteVisible = app(\App\Support\FrontendCatalog::class)->isVisible('build-a-website'))
<div class="paul-widget" data-paul-widget>
    <details class="paul-disclosure">
        <summary class="paul-trigger" aria-label="Z外星向导" aria-controls="paul-panel"><x-paul-avatar size="small" /><span class="paul-trigger-label">Z</span></summary>
        <section class="paul-panel" id="paul-panel" aria-label="Z外星向导">
            <header class="paul-panel-header"><div><h2>Z<span>外星向导</span></h2><p>先把第一步做成。</p></div><button class="icon-button paul-close" type="button" data-paul-close aria-label="关闭Z面板" hidden><i data-lucide="x"></i></button></header>
            <div class="paul-conversation"><x-paul-avatar size="large" /><p class="paul-reply" data-paul-reply aria-live="polite" aria-atomic="true">你今天想干嘛？解决一个问题，做个作品，还是探索新可能？</p></div>
            <div class="paul-quick-actions" aria-label="选择你想做的事" data-paul-choices hidden>
                <button type="button" data-paul-choice="solve" aria-pressed="false">解决一个问题</button>
                <button type="button" data-paul-choice="create" aria-pressed="false">创作一个作品</button>
                <button type="button" data-paul-choice="explore" aria-pressed="false">探索一个可能</button>
                <button type="button" data-paul-choice="other" aria-pressed="false">找不到对应课程</button>
            </div>
            <div class="paul-next-actions" data-paul-next><a href="{{ $websiteVisible ? route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) : route('free.index') }}">{{ $websiteVisible ? '先免费体验第一课' : '找一个免费任务' }}</a><a href="{{ route('series.index') }}">看看100分钟课程</a></div>
            <form class="free-paul-form" action="{{ route('free.index') }}" method="get">
                <label for="paul-free-question">想做什么？我帮你找免费完整任务</label>
                <input id="paul-free-question" name="q" maxlength="300" required placeholder="比如：把订单表合并汇总">
                <button class="button button-small button-primary" type="submit">找免费任务<i data-lucide="arrow-right"></i></button>
            </form>
            <footer class="paul-panel-footer"><span>每一小步，都算数。</span><button type="button" data-paul-hide hidden>暂时隐藏向导</button></footer>
        </section>
    </details>
    <template data-paul-response="solve" data-state="got_it"><p>懂了。先选一个具体任务，再拆成小步。还不知道从哪开始，可以先试试免费的第一课。</p><a href="{{ $websiteVisible ? route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) : route('free.index') }}">{{ $websiteVisible ? '免费开始第一课' : '找一个免费任务' }}</a><a href="{{ route('series.index') }}">找一个想解决的问题</a></template>
    <template data-paul-response="create" data-state="idea"><p>有了。先做一个能看见的作品，比如自己的网站。从让它能访问这一步开始。</p><a href="{{ $websiteVisible ? route('series.show', 'build-a-website') : route('free.index', ['category' => 'create']) }}">{{ $websiteVisible ? '看看网站这条路径' : '找一个创作任务' }}</a><a href="{{ route('series.index') }}">看看其他作品方向</a></template>
    <template data-paul-response="explore" data-state="so_so"><p>这个我喜欢，但还差一个具体目标。先想清楚要验证什么，再找最小的实验。别一上来就造火箭。</p><a href="{{ route('series.index') }}">看看已有的课程方向</a><a href="{{ route('questions') }}">看看问题池（筹备中）</a></template>
    <template data-paul-response="other" data-state="awkward"><p>呃，这个我暂时有点接不住。问题池还在准备，先看看已有的课程方向。</p><a href="{{ route('series.index') }}">看看已有课程</a><a href="{{ route('questions') }}">看看问题池（筹备中）</a></template>
</div>
