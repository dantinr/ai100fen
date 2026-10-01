# AI100分

10分钟，解决一步。100分钟，解决一个真实问题。

## 当前版本

Laravel / Blade 前台 MVP 预览，采用 Tailwind CSS / Vite 与 Lucide 图标。包括首页、问题筛选与搜索、系列详情、免费第一课图文学习、验收清单下载、本机学习记录、购买订阅说明和直播准备状态。

当前目录包含16个真实问题：网站、网站智能客服、小程序、自媒体分析、创作者Agent创作计划、Excel处理、PPT汇报、工作小工具、个人软件制作（下载软件、输入法等示例）、自己的Agent、AI爆改游戏、文件整理、调研报告、个人数字资产、个人资料库和会议行动清单。网站第一课可试看，其余十五个系列规划中。

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

## 全站主题

默认使用Pop，设置`.env`中的`APP_THEME=pop`或`APP_THEME=future`后执行`php artisan config:clear`；生产环境使用`php artisan config:cache`刷新配置。Future目前为主题机制验证骨架。未知配置回退默认Pop，不通过网址参数或浏览器偏好切换。

主题注册表为`config/themes.php`。共享结构、外观和页面浓度分别在`resources/css/base.css`、`components.css`、`variants.css`，主题Token在`resources/css/themes`，图案在`resources/themes`。Vite构建两套轻量主题，页面只加载选中的一套。新增主题需注册并添加Vite入口、实现同一Token契约，详细规则见`docs/THEME_SYSTEM.md`。

## 验证

```shell
php artisan test
```

测试覆盖前台页面、免费正文与下载、未发布资料的服务端限制，以及 Series 内的 Lesson 定位。未来业务功能接入时，继续补齐项目约定的权限、支付、订阅与进度测试。

## 临时预览部署（2026-10-01）

- 访问地址：https://ai100.aicsi.cn；服务器：39.106.113.140。
- 项目目录：`/var/www/ai100fen`；Nginx仅公开`/var/www/ai100fen/public`。
- 服务：Nginx与PHP 8.5 FPM，套用`deploy/nginx/ai100.aicsi.cn.conf`。独立域名配置，HTTP跳转HTTPS。
- 使用服务器已有Certbot账号申请证书，已有`certbot.timer`自动续期及Nginx重载钩子。
- 环境参考`deploy/preview.env.example`；服务器生成独立APP_KEY，APP_DEBUG=false，文件会话与缓存，同步队列。前台预览不连接业务数据库、不执行迁移。
- robots.txt、页面noindex与Nginx的X-Robots-Tag继续禁止抓取与索引。

前端资源在本机构建，服务器不安装Node或执行npm构建。后续更新时，先确保源码提交已推送，并在同一版本运行测试与构建：

```powershell
php artisan test
npm run build
tar -czf .local/deploy/ai100fen-preview-build.tar.gz -C public build
scp .local/deploy/ai100fen-preview-build.tar.gz root@39.106.113.140:/tmp/
```

服务器确认将要部署的提交与本机构建版本一致；更新前备份代码、public/build及运行时配置，保留原APP_KEY：

```bash
set -e
cd /var/www/ai100fen
git pull --ff-only origin main
tar -xzf /tmp/ai100fen-preview-build.tar.gz -C public
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R u=rwX,g=rwX,o= storage bootstrap/cache
```

`.env`由root拥有、www-data组可读、权限640；仅storage和bootstrap/cache需要Web进程写入。密钥不上传至Git。Nginx首次启用或配置变更时先`nginx -t`，再重载服务。当前HTTP配置备份位于`/var/backups/ai100fen/nginx-http-20261001.conf`。

部署后检查首页、课程列表、试看正文与清单、构建资源、robots.txt，以及`.env`、`.git`、日志和未开放资料的访问限制。当前服务仍是前台预览，账号、购买、订阅与后台待实现。

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
