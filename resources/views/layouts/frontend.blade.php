<!doctype html>
<html lang="zh-CN" data-theme="{{ $theme['id'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="AI100分，从真实问题出发。10分钟解决一步，100分钟做成一个可验收的结果。">
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
                <a href="{{ route('series.index') }}" @class(['active' => request()->routeIs('series.*', 'lessons.*')]) @if(request()->routeIs('series.*', 'lessons.*')) aria-current="page" @endif>100分钟</a>
                <x-ufo-nav-link :href="route('questions')" :active="request()->routeIs('questions')">问题池</x-ufo-nav-link>
                <a href="{{ route('live') }}" @class(['active' => request()->routeIs('live')]) @if(request()->routeIs('live')) aria-current="page" @endif>直播</a>
                <a href="{{ route('me') }}" @class(['active' => request()->routeIs('me')]) @if(request()->routeIs('me')) aria-current="page" @endif>我的100分</a>
            </nav>
            <div class="header-actions"><a class="login-link" href="{{ auth()->check() ? route('me') : route('login') }}">{{ auth()->check() ? '个人中心' : '登录' }}</a><a class="button button-small button-dark" href="{{ route('series.index') }}">开始解决问题<i data-lucide="arrow-up-right"></i></a><button class="icon-button menu-toggle" aria-label="展开导航" aria-expanded="false" aria-controls="main-navigation"><i data-lucide="menu"></i></button></div>
        </div>
    </header>
    <main id="main">@yield('content')</main>
    <footer class="site-footer">
        <div class="shell footer-inner">
            <a class="footer-brand" href="{{ route('home') }}">AI100分<span class="brand-dot">.</span></a>
            <span>10分钟，解决一步。100分，代表完成。</span>
            <div>
                <a href="{{ route('series.index') }}">100分钟</a>
                <a href="{{ route('pricing') }}">购买与订阅</a>
                <a href="{{ route('commits') }}">提交记录</a>
                <button class="footer-paul-link" type="button" data-paul-open hidden>Paul向导</button>
                <a class="footer-repo-link" href="https://github.com/dantinr/ai100fen" target="_blank" rel="noopener noreferrer" aria-label="AI100分 GitHub 仓库（新标签页打开）">
                    <svg viewBox="0 0 32 32" width="16" height="16" fill="currentColor" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M16 0C7.16 0 0 7.16 0 16C0 23.08 4.58 29.06 10.94 31.18C11.74 31.32 12.04 30.84 12.04 30.42C12.04 30.04 12.02 28.78 12.02 27.44C8 28.18 6.96 26.46 6.64 25.56C6.46 25.1 5.68 23.68 5 23.3C4.44 23 3.64 22.26 4.98 22.24C6.24 22.22 7.14 23.4 7.44 23.88C8.88 26.3 11.18 25.62 12.1 25.2C12.24 24.16 12.66 23.46 13.12 23.06C9.56 22.66 5.84 21.28 5.84 15.16C5.84 13.42 6.46 11.98 7.48 10.86C7.32 10.46 6.76 8.82 7.64 6.62C7.64 6.62 8.98 6.2 12.04 8.26C13.32 7.9 14.68 7.72 16.04 7.72C17.4 7.72 18.76 7.9 20.04 8.26C23.1 6.18 24.44 6.62 24.44 6.62C25.32 8.82 24.76 10.46 24.6 10.86C25.62 11.98 26.24 13.4 26.24 15.16C26.24 21.3 22.5 22.66 18.94 23.06C19.52 23.56 20.02 24.52 20.02 26.02C20.02 28.16 20 29.88 20 30.42C20 30.84 20.3 31.34 21.1 31.18C27.42 29.06 32 23.06 32 16C32 7.16 24.84 0 16 0V0Z"/>
                    </svg>
                    <span>GitHub</span>
                </a>
                <span>© {{ date('Y') }} AI100分</span>
            </div>
        </div>
    </footer>
    <dialog id="availability-dialog" class="availability-dialog" aria-labelledby="dialog-title"><button class="icon-button dialog-close" data-close-dialog aria-label="关闭"><i data-lucide="x"></i></button><span class="dialog-symbol"><i data-lucide="clock-3"></i></span><h2 id="dialog-title">购买暂未开放</h2><p id="dialog-message">课程与支付服务正在准备中。你可以先体验免费的第一课。</p><a class="button button-primary" href="{{ route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']) }}">先完成第一个10分<i data-lucide="arrow-right"></i></a><button class="text-button" data-close-dialog>继续浏览</button></dialog>
    <div id="toast" class="toast" role="status" aria-live="polite" hidden></div>
    <x-paul-widget />
</body>
</html>
