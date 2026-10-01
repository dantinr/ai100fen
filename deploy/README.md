# Git pull 更新上线

当前预览站：`https://ai100.aicsi.cn`，服务器目录：`/var/www/ai100fen`。

前端在开发机执行`npm run build`，将`public/build`与源码一起提交、推送。服务器直接使用仓库中的构建产物，不需要Node或手工上传资源。

## 服务器一次性配置

当前服务器已配置。新建同等环境的生产检出后，以root执行：

```sh
sh deploy/enable-git-pull.sh /var/www/ai100fen
```

这会在该仓库的本地Git配置中启用`deploy/hooks`、`ai100fen.deploy=true`及仅快进更新。已有自定义Hook时安装会停止，避免覆盖。其他开发检出默认不执行上线操作。

## 账号系统首次初始化

D-026接入真实账号。首次发布前需为项目配置MySQL数据库`ai100fen`及仅访问此库的账号，按`deploy/preview.env.example`补齐`DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`。密码只存服务器受保护的`.env`，保留现有APP_KEY、HTTPS安全Cookie及其他站点配置；先备份`.env`，检查目标库与初始迁移，确认没有同名表冲突。

这一步独立于上线Hook，由运维手动执行：

```sh
php artisan config:cache
php artisan migrate --force --no-interaction
php artisan migrate:status
```

当前只执行框架已存在的三份初始迁移，建立用户、密码重置Token、会话、缓存和队列表；账号会话与缓存继续使用文件。不要运行`migrate:fresh`、`db:seed`或示例账号Seeder。后续无数据库/环境变化的发布仍只需`git pull`；新迁移必须另行审阅与手动执行。

目前未配置真实邮件投递，不开放忘记密码或邮箱验证；已登录用户可在个人中心修改密码。服务端学习记录、购买和订阅尚未接入。

## 日常更新

以root进入项目目录后，只需：

```sh
git pull
```

有新提交时，post-merge自动校验资源、按锁文件安装生产PHP依赖，刷新Laravel配置、路由与视图缓存，恢复运行目录权限并平滑重载PHP 8.5 FPM。

脚本不修改`.env`或APP_KEY，不执行数据库迁移，不更改Nginx配置。PHP扩展、新服务、环境变量或数据库结构变化仍须单独评审处理。

## 发布与故障处理

- 发布前运行`npm run build`及`php artisan test`，检查并提交最新`public/build`；不要提交`.env`、`vendor`、`node_modules`或`public/hot`。
- 保持服务器检出干净；发现本地业务修改时先保留并处理，不使用强制重置覆盖。
- Git更新和Hook部署不是原子操作；Hook报错不会回滚已经更新的源码。检查错误后执行`sh deploy/refresh.sh`重试，不要把“Already up to date”当作部署重试。
- 回滚先明确要恢复的提交，再按对应锁文件、构建产物与缓存恢复；脚本不自动回滚业务数据。

## 当前服务器验证（2026-10-01）

- 账号发布前已配置独立`ai100fen`数据库及项目账号，三份框架初始迁移均已完成，未执行Seeder；APP_KEY及非数据库环境值保持不变。账号初始化前的`.env`保存于仅root可访问的`/var/backups/ai100fen/accounts-20261001-162053`。
- 已在`/var/www/ai100fen`启用Hook，并通过实际`git pull`验证自动安装依赖、刷新缓存与FPM重载。
- 首页、问题池、价格页、免费第一课和清单下载正常；未开放清单与`.env`、`.git`继续拒绝访问，robots.txt继续禁止抓取。
- 已核对全部6份构建资源的SHA256与本地一致，确认`.env`字节未变。
- 切换前的构建产物、bootstrap缓存与`.env`保存在仅root可访问的`/var/backups/ai100fen/git-pull-20261001-154705`，旧构建遗留文件另行归档，不覆盖业务数据。
