<!doctype html>
<html lang="zh-CN" data-theme="{{ $theme['id'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="@yield('description', 'AI100分，从真实问题出发。10分钟解决一步，100分钟做成一个可验收的结果。')">
    <title>@yield('title', 'AI100分 · 把一个真实问题做成')</title>
    @vite(['resources/css/app.css', $theme['stylesheet'], 'resources/js/app.js'])
</head>
<body class="app-ui" data-page="{{ $pageVariant }}">
    <a class="skip-link" href="#main">跳到正文</a>
    <header class="site-header">
        <div class="shell header-inner">
            <a class="brand" href="{{ route('home') }}" aria-label="AI100分首页"><span class="brand-mark">100<span>↗</span></span><span>AI100分<span class="brand-dot">.</span></span></a>
            <nav class="main-nav" aria-label="主导航" id="main-navigation">
                <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')]) @if(request()->routeIs('home')) aria-current="page" @endif>首页</a>
                <a href="{{ route('series.index') }}" @class(['active' => request()->routeIs('series.*', 'lessons.*', 'courses.show')]) @if(request()->routeIs('series.*', 'lessons.*', 'courses.show')) aria-current="page" @endif>全部课程</a>
                <a href="{{ route('live') }}" @class(['active' => request()->routeIs('live')]) @if(request()->routeIs('live')) aria-current="page" @endif>直播</a>
                <x-black-hole-nav-link :href="route('questions')" :active="request()->routeIs('questions', 'questions.*')">问题池</x-black-hole-nav-link>
                <a href="{{ route('about') }}" @class(['active' => request()->routeIs('about')]) @if(request()->routeIs('about')) aria-current="page" @endif>关于</a>
            </nav>
            <div class="header-actions"><a class="login-link" href="{{ auth()->check() ? route('me') : route('login') }}">{{ auth()->check() ? '个人中心' : '登录' }}</a><a class="button button-small button-dark" href="{{ route('series.index') }}">开始解决问题<i data-lucide="arrow-up-right"></i></a><button class="icon-button menu-toggle" aria-label="展开导航" aria-expanded="false" aria-controls="main-navigation"><i data-lucide="menu"></i></button></div>
        </div>
    </header>
    <main id="main">@yield('content')</main>
    <footer class="site-footer">
        <div class="shell footer-inner">
            <a class="footer-brand" href="{{ route('home') }}">AI100分<span class="brand-dot">.</span></a>
            <div>
                <a href="{{ route('pricing') }}">购买与订阅</a>
                <a href="{{ route('commits') }}">提交记录</a>
                @if(request()->routeIs('questions', 'questions.*'))<a href="{{ route('questions') }}#question-lab">Z向导</a>@else<button class="footer-paul-link" type="button" data-paul-open hidden>Z向导</button>@endif
                <a class="footer-repo-link" href="https://github.com/dantinr/ai100fen" target="_blank" rel="noopener noreferrer" aria-label="AI100分 GitHub 仓库（新标签页打开）">
                    <span class="footer-repo-icon" aria-hidden="true"><x-paul-avatar :portrait="true" /></span>
                    <span>GitHub</span>
                </a>
                <span>© {{ date('Y') }} AI100分</span>
            </div>
        </div>
    </footer>
    <dialog id="availability-dialog" class="availability-dialog" aria-labelledby="dialog-title"><button class="icon-button dialog-close" data-close-dialog aria-label="关闭"><i data-lucide="x"></i></button><span class="dialog-symbol"><i data-lucide="clock-3"></i></span><h2 id="dialog-title">购买暂未开放</h2><p id="dialog-message">课程与支付服务正在准备中。你可以先体验免费的第一课。</p><a class="button button-primary" href="{{ app(\App\Support\FrontendCatalog::class)->isVisible('build-a-website') ? route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) : route('free.index') }}">先做成一小步<i data-lucide="arrow-right"></i></a><button class="text-button" data-close-dialog>继续浏览</button></dialog>
    <div id="toast" class="toast" role="status" aria-live="polite" hidden></div>
    @unless(request()->routeIs('questions', 'questions.*'))<x-paul-widget />@endunless
</body>
</html>
