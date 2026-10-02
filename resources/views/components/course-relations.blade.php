@props(['groups' => []])
@if($groups)
<section class="course-relations" aria-labelledby="course-relations-title" data-course-relations>
    <div class="course-relations-heading"><span class="eyebrow">围绕你的下一个真实结果</span><h2 id="course-relations-title">把任务连起来</h2><p>按自己的目标选择。前置课程是准备建议，可以直接查看当前任务。</p></div>
    @foreach($groups as $group)
    <section class="course-relation-group" aria-labelledby="relation-{{ $group['type'] }}-title">
        <h3 id="relation-{{ $group['type'] }}-title">{{ $group['label'] }}</h3>
        <ul class="course-relation-list">
            @foreach($group['links'] as $link)
            <li class="course-relation-card">
                <div class="course-relation-meta"><span>{{ $link['access'] }}</span><span>约{{ $link['minutes'] }}分钟</span>@if($link['status'])<span>{{ $link['status'] }} · 管理员预览</span>@endif</div>
                <h4><a href="{{ $link['url'] }}">{{ $link['title'] }}<i data-lucide="arrow-up-right" aria-hidden="true"></i></a></h4>
                <p>{{ $link['outcome'] }}</p>
                @if($link['reason'])<p class="course-relation-reason">{{ $link['reason'] }}</p>@endif
            </li>
            @endforeach
        </ul>
    </section>
    @endforeach
</section>
@endif
