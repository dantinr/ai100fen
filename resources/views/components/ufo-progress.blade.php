@props(['series', 'label' => '当前完成度'])
<div class="series-progress" data-progress-for="{{ $series }}">
    <div><span>{{ $label }}</span><strong><span data-score>0</span><small> / 100</small></strong></div>
    <div class="ufo-path" data-ufo-path style="--ufo-progress:0" role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-valuetext="当前完成0分，共100分">
        <div class="progress-segments" aria-hidden="true">@for($i=0;$i<10;$i++)<span></span>@endfor</div>
        <div class="ufo-path-nodes" aria-hidden="true">@for($i=0;$i<=10;$i++)<span @class(['is-reached' => $i === 0])></span>@endfor</div>
        <div class="ufo-path-marker"><x-ufo-widget size="small" /></div>
        <div class="ufo-path-labels" aria-hidden="true"><span>0分 · 开始</span><span>100分 · 做成</span></div>
    </div>
    <p class="ufo-completion" data-ufo-completion hidden><strong>100分完成！</strong><span>一个真实问题，做成了。</span></p>
</div>
