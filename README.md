# AI100分

10分钟，解决一步。100分钟，解决一个真实问题。

## 当前版本

Laravel / Blade 前台 MVP 预览，采用 Tailwind CSS / Vite 与 Lucide 图标。包括首页、问题筛选与搜索、系列详情、免费第一课图文学习、验收清单下载、本机学习记录、购买订阅说明和直播准备状态。

当前目录包含12个真实问题：网站、小程序、自媒体分析、创作者Agent创作计划、Excel处理、PPT汇报、工作小工具、个人软件制作（下载软件、输入法等示例）、文件整理、调研报告、个人资料库和会议行动清单。网站第一课可试看，其余十一个系列规划中。

后台、真实账号、支付、订阅、录播视频和服务端学习进度尚未接入。浏览器学习记录不参与权限判断。查看 `MVP.md`、`DECISIONS.md` 与 `TODO.md` 了解完整目标及本轮范围。

## 本地运行

需要 PHP 8.3+、Composer 和支持 Vite 8 的 Node.js；当前开发环境为 PHP 8.5.7、Node.js 22.13.0。

```shell
composer install
npm ci
npm run build
```

新环境先从 `.env.example` 建立 `.env` 并执行 `php artisan key:generate`。前台预览使用 `SESSION_DRIVER=file`，无需执行数据库迁移。本地域名已配置为 [ai100fen.local](http://ai100fen.local)，`APP_URL=http://ai100fen.local`，Web服务入口为项目的 `public` 目录。

未配置本地域名时，也可执行 `php artisan serve --host=127.0.0.1 --port=8000`，通过 http://127.0.0.1:8000 预览；对应环境的 `APP_URL` 设置为该地址。

## 验证

```shell
php artisan test
```

测试覆盖前台页面、免费正文与下载、未发布资料的服务端限制，以及 Series 内的 Lesson 定位。未来业务功能接入时，继续补齐项目约定的权限、支付、订阅与进度测试。

## Laravel

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
