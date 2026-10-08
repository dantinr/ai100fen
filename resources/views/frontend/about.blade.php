@extends('layouts.frontend')
@section('title', '关于AI100分 · 用AI，把一件事做成')
@section('description', '了解AI100分：从真实任务出发，用AI解决问题、创作作品、探索可能。由激怒李维斯发起并维护，人负责目标与验收，Agent负责执行。')
@section('content')
<div class="shell page-main about-main">
    <nav class="about-anchor-nav" aria-label="关于页内容导航">
        <a href="#about-what-title">项目介绍</a>
        <a href="#about-why-title">发起原因</a>
        <a href="#about-help-title">能做什么</a>
        <a href="#about-maintainer-title">维护者</a>
    </nav>

    <div class="about-content">
    <header class="about-hero">
        <h1><span>关于 AI100分</span></h1>
        <aside class="about-making" aria-labelledby="about-making-title">
            <span class="about-making-icon" aria-hidden="true"><i data-lucide="sparkles"></i></span>
            <div>
                <h2 id="about-making-title">本网站全部使用 AI 制作</h2>
                <p>人负责目标、关键判断与最终验收，AI / Agent 负责执行。</p>
            </div>
        </aside>
        <p class="about-lead">AI100分，就是一个用 AI 把想法做成真实作品的实践。我们从你想完成的事情出发，把目标拆成能够操作、能够检查的小步骤，让你在AI的帮助下解决问题、完成作品、探索新的可能。</p>
    </header>

    <section class="about-section" aria-labelledby="about-what-title">
        <div class="section-heading"><h2 id="about-what-title">AI100分是什么？</h2></div>
        <div class="about-copy">
            <p>你可以带着一个具体目标来到这里：搭建一个网站、制作一个软件、处理一批数据，Blabla...</p>
            <p>每个任务都需要讲清楚三件事：你要完成什么、怎么开始、怎样判断结果达标。</p>
            <p>我们用三个方向组织学习内容：</p>
        </div>
        <div class="about-directions">
            <a class="about-direction" href="{{ route('free.index', ['category' => 'solve']) }}">
                <span class="about-direction-icon" aria-hidden="true"><i data-lucide="list-checks"></i></span>
                <span class="eyebrow">Solve</span>
                <h3>解决一个问题</h3>
                <p>从一个真实困扰开始，得到可以使用的解决办法。</p>
                <span class="text-link">从解决问题开始<i data-lucide="arrow-right" aria-hidden="true"></i></span>
            </a>
            <a class="about-direction about-direction-create" href="{{ route('free.index', ['category' => 'create']) }}">
                <span class="about-direction-icon" aria-hidden="true"><i data-lucide="layout-template"></i></span>
                <span class="eyebrow">Create</span>
                <h3>创作一个作品</h3>
                <p>把想法变成网站、小程序、内容或工具，留下自己的成果。</p>
                <span class="text-link">从创作作品开始<i data-lucide="arrow-right" aria-hidden="true"></i></span>
            </a>
            <a class="about-direction about-direction-explore" href="{{ route('free.index', ['category' => 'explore']) }}">
                <span class="about-direction-icon" aria-hidden="true"><i data-lucide="flask-conical"></i></span>
                <span class="eyebrow">Explore</span>
                <h3>探索一个可能</h3>
                <p>通过小规模实践，验证AI能帮助你做到什么。</p>
                <span class="text-link">从探索可能开始<i data-lucide="arrow-right" aria-hidden="true"></i></span>
            </a>
        </div>
    </section>

    <section class="about-section about-story" aria-labelledby="about-why-title">
        <div class="section-heading about-story-heading">
            <h2 id="about-why-title">为什么要做AI100分？</h2>
            <img class="about-story-image" src="{{ asset('images/about/why.png') }}" width="300" height="452" alt="" loading="lazy" decoding="async">
        </div>
        <div class="about-copy">
            <p>我在开发和创作中反复遇到一个问题：知道AI能做什么，与真正让它帮自己完成一件事，中间还有一段距离。</p>
            <p>一个想法需要变成明确的目标，一段生成的代码需要运行起来，一个看起来正确的结果需要经过验证。真正的难点常常出现在这些连接处：需求说不清楚、环境没有准备好、执行遇到错误，或者完成后不知道该如何检查。</p>
            <p>AI100分希望把这段过程整理出来：从真实需求开始，展示操作、保留排错过程、说明前置条件，并给出明确的验收方法。</p>
            <p>我们希望每一次学习，都能留下一个具体结果，以及下次遇到类似问题时可以再次使用的方法。</p>
        </div>
    </section>

    <section class="about-section" aria-labelledby="about-help-title">
        <div class="section-heading"><h2 id="about-help-title">AI100分能帮助你解决什么问题？</h2></div>
        <div class="about-tasks">
            <article class="about-task">
                <span class="about-task-number" aria-hidden="true">01</span>
                <h3>把模糊想法变成可执行任务。</h3>
                <p>明确使用场景、输入、输出和完成条件，知道第一步应该做什么。</p>
            </article>
            <article class="about-task">
                <span class="about-task-number" aria-hidden="true">02</span>
                <h3>把重复工作整理成可复用流程。</h3>
                <p>从文件、表格、资料和日常操作中找到具体问题，借助AI制作适合自己的工具和方法。</p>
            </article>
            <article class="about-task">
                <span class="about-task-number" aria-hidden="true">03</span>
                <h3>把一个作品从想法推进到实际使用。</h3>
                <p>完成界面、数据、发布和测试，让网站、小程序或工具在真实环境中运行。</p>
            </article>
            <article class="about-task">
                <span class="about-task-number" aria-hidden="true">04</span>
                <h3>判断结果是否可靠，并继续修改。</h3>
                <p>用实际任务检查成果，理解失败发生在哪里，保留版本、素材和必要说明。</p>
            </article>
        </div>
    </section>

    <section class="about-section" aria-labelledby="about-maintainer-title">
        <div class="section-heading"><h2 id="about-maintainer-title">谁在维护AI100分？</h2></div>
        <div class="about-maintainer">
            <div class="about-maintainer-identity">
                <x-maintainer-avatar />
                <div class="about-maintainer-name"><span class="eyebrow">发起与维护</span><strong>激怒李维斯</strong></div>
            </div>
            <div class="about-copy">
                <p>我是一名经验丰富的程序员，也是一名内容创作者，关注系统设计、AI辅助开发和个人数字资产。</p>
                <p>我负责项目方向、课程实践、内容审核与最终验收，网站的设计与开发交给 AI / Agent 执行，并根据实际使用反馈持续调整。</p>
                <p>这里的选题来自实际需求，课程需要经过实做和检验。项目会随着真实使用中的问题持续调整，逐步把零散经验整理成可复用的学习路径。</p>
                <div class="about-social-links" aria-label="维护者相关入口">
                    <button class="about-social-link" type="button" popovertarget="about-douyin-codes">抖音<i data-lucide="arrow-down" aria-hidden="true"></i></button>
                    @if($profileUrl = config('about.maintainer.github'))
                        <a class="about-social-link" href="{{ $profileUrl }}" target="_blank" rel="noopener noreferrer"><span class="about-social-avatar" aria-hidden="true"><x-paul-avatar :portrait="true" /></span><span>GitHub</span><i data-lucide="arrow-up-right" aria-hidden="true"></i><span class="sr-only">（维护者主页，新标签页打开）</span></a>
                    @endif
                    <button class="about-social-link" type="button" popovertarget="about-sovereignty">个人数字主权<i data-lucide="arrow-down" aria-hidden="true"></i></button>
                    <a class="about-social-link" href="{{ config('about.maintainer.aicsi') }}" target="_blank" rel="noopener noreferrer">内容结构指数<i data-lucide="arrow-up-right" aria-hidden="true"></i><span class="sr-only">（新标签页打开）</span></a>
                </div>
                <div class="about-douyin-panel" id="about-douyin-codes" popover role="dialog" aria-labelledby="about-douyin-title">
                    <div class="about-douyin-heading">
                        <h3 id="about-douyin-title">抖音主页</h3>
                        <button class="icon-button" type="button" popovertarget="about-douyin-codes" popovertargetaction="hide" aria-label="关闭抖音二维码" autofocus><i data-lucide="x" aria-hidden="true"></i></button>
                    </div>
                    <div class="about-douyin-codes">
                        <figure>
                            <a href="{{ asset('images/about/douyin-gnlws.png') }}" target="_blank" rel="noopener noreferrer" aria-label="查看激怒李维斯抖音二维码大图（新标签页打开）"><img src="{{ asset('images/about/douyin-gnlws.png') }}" width="758" height="758" alt="激怒李维斯的抖音二维码" loading="lazy"></a>
                            <figcaption>激怒李维斯</figcaption>
                        </figure>
                        <figure>
                            <a href="{{ asset('images/about/douyin-llditou.png') }}" target="_blank" rel="noopener noreferrer" aria-label="查看流浪地头抖音二维码大图（新标签页打开）"><img src="{{ asset('images/about/douyin-llditou.png') }}" width="1508" height="1508" alt="流浪地头的抖音二维码" loading="lazy"></a>
                            <figcaption>流浪地头</figcaption>
                        </figure>
                    </div>
                    <p class="about-douyin-hint">打开抖音扫一扫，或点击二维码查看大图。</p>
                </div>
                <div class="about-sovereignty-panel" id="about-sovereignty" popover role="dialog" aria-labelledby="about-sovereignty-title">
                    <div class="about-sovereignty-heading">
                        <h3 id="about-sovereignty-title">个人数字主权</h3>
                        <button class="icon-button" type="button" popovertarget="about-sovereignty" popovertargetaction="hide" aria-label="关闭个人数字主权介绍" autofocus><i data-lucide="x" aria-hidden="true"></i></button>
                    </div>
                    <div class="about-sovereignty-projects">
                        <article>
                            <h4>PDSI · 个人数字主权计划</h4>
                            <p>研究个人数字主权的定义、框架与方法，让个人在数字生活中保有迁移、替换、恢复和退出的选择。</p>
                            <a class="text-link" href="{{ config('about.sovereignty.pdsi') }}" target="_blank" rel="noopener noreferrer">访问 PDSI<i data-lucide="arrow-up-right" aria-hidden="true"></i><span class="sr-only">（新标签页打开）</span></a>
                        </article>
                        <article>
                            <h4>CDSI · 创作者数字主权基础设施</h4>
                            <p>通过 Anchor 建立自有基础设施，通过 Beacon 管理、备份与发布数字资产，让内容和数据可迁移、可恢复、可替换。</p>
                            <a class="text-link" href="{{ config('about.sovereignty.cdsi') }}" target="_blank" rel="noopener noreferrer">访问 CDSI<i data-lucide="arrow-up-right" aria-hidden="true"></i><span class="sr-only">（新标签页打开）</span></a>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-start" aria-labelledby="about-start-title">
        <h2 id="about-start-title">从你想做的那件事开始。</h2>
    </section>
    </div>
</div>
@endsection
