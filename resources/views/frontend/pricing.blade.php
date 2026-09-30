@extends('layouts.frontend')
@section('title', '购买与订阅 · AI100分')
@section('content')
<div class="shell page-main pricing-main">
    <div class="page-heading centered">
        <span class="eyebrow">从一个问题，到更多可能</span>
        <h1>一门100元，订阅随便看。</h1>
        <p>选一门解决眼前的问题，或以299元/月订阅全部课程。</p>
    </div>
    <div class="pricing-grid">
        <article class="price-card">
            <span class="eyebrow">解决一个具体问题</span>
            <h2>单门课程</h2>
            <p>一个100分钟系列，围绕一个真实问题。</p>
            <div class="plan-price">¥100<small> / 门</small></div>
            <ul class="check-list">
                <li><i data-lucide="check"></i>所购课程的完整内容</li>
                <li><i data-lucide="check"></i>配套 Prompt、代码与资料</li>
                <li><i data-lucide="check"></i>每一步的目标与验收方式</li>
            </ul>
            <button class="button button-outline full-width" data-availability="purchase">购买暂未开放<i data-lucide="arrow-right"></i></button>
        </article>
        <article class="price-card subscription-card">
            <span class="plan-label">全部课程</span>
            <span class="eyebrow">持续把真实问题做成</span>
            <h2>AI100分订阅</h2>
            <p>订阅有效期内，全部已发布课程随便看。</p>
            <div class="plan-price">¥299<small> / 月</small></div>
            <ul class="check-list">
                <li><i data-lucide="check"></i>全部已发布课程，不限观看次数</li>
                <li><i data-lucide="check"></i>订阅期间新发布的课程也包含在内</li>
                <li><i data-lucide="check"></i>会员直播与历史回放</li>
                <li><i data-lucide="check"></i>配套 Prompt、代码与资料</li>
            </ul>
            <button class="button button-primary full-width" data-availability="subscription">订阅暂未开放<i data-lucide="arrow-right"></i></button>
        </article>
    </div>
    <p class="pricing-note"><i data-lucide="info"></i>课程与支付服务正在准备中，当前不收取费用。</p>
    <section class="faq-section">
        <h2>开始前，你可能想知道</h2>
        <details>
            <summary>订阅后，还需要单独买课吗？<i data-lucide="plus"></i></summary>
            <p>不需要。299元/月订阅有效期内，全部已发布课程都可以观看，不限观看次数；期间新发布的课程也包含在内。筹备中的课程发布后即可观看。</p>
        </details>
        <details>
            <summary>100元是一节课，还是一个完整课程？<i data-lucide="plus"></i></summary>
            <p>100元购买一门完整课程，即一个100分钟系列。标准系列包含10个约10分钟的步骤，围绕一个真实问题完成学习和验收。</p>
        </details>
        <details>
            <summary>100分代表什么？<i data-lucide="plus"></i></summary>
            <p>100分代表一个系列的完成度。每完成并验收一个标准 Lesson，增加10分；完成10个步骤，达到100分。它不是考试成绩，也不是职业认证。</p>
        </details>
        <details>
            <summary>需要先学会编程吗？<i data-lucide="plus"></i></summary>
            <p>你负责明确目标、观察过程与验收结果，Agent 协助执行。每个系列都会列出所需环境和前置条件，可以先通过免费第一课判断是否适合自己。</p>
        </details>
        <details>
            <summary>100分钟包含全部操作时间吗？<i data-lucide="plus"></i></summary>
            <p>100分钟是标准内容时长。准备环境、实际操作和外部审核等等待时间，会因具体问题与环境而不同。</p>
        </details>
    </section>
</div>
@endsection
