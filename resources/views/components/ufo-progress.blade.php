@props(['series', 'label' => '当前完成度', 'score' => null])
@php($currentScore = max(0, min(100, (float) $score)))
<div class="series-progress" data-progress-for="{{ $series }}" @if($score !== null) data-server-score="{{ $currentScore }}" @endif>
    <div><span>{{ $label }}</span><strong><span data-score>{{ $currentScore }}</span><small>%</small></strong></div>
    <div class="ufo-path" data-ufo-path style="--ufo-progress:{{ $currentScore / 100 }}" role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $currentScore }}" aria-valuetext="当前完成{{ $currentScore }}%">
        <div class="progress-segments" aria-hidden="true">@for($i=0;$i<10;$i++)<span @class(['filled' => $i < $currentScore / 10])></span>@endfor</div>
        <div class="ufo-path-nodes" aria-hidden="true">@for($i=0;$i<=10;$i++)<span @class(['is-reached' => $i * 10 <= $currentScore])></span>@endfor</div>
        <div class="ufo-path-marker"><x-ufo-widget size="small" /></div>
        <div class="ufo-path-labels" aria-hidden="true"><span>0% · 开始</span><span>100% · 做成</span></div>
    </div>
    <p class="ufo-completion" data-ufo-completion @if($currentScore < 100) hidden @endif><strong>100%完成！</strong><span>一个真实任务，做成了。</span></p>
</div>
