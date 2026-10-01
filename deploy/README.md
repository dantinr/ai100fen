# Git pull 更新上线

当前预览站：`https://ai100.aicsi.cn`，服务器目录：`/var/www/ai100fen`。

前端在开发机执行`npm run build`，将`public/build`与源码一起提交、推送。服务器直接使用仓库中的构建产物，不需要Node或手工上传资源。

## 服务器一次性配置

当前服务器已配置。新建同等环境的生产检出后，以root执行：

```sh
sh deploy/enable-git-pull.sh /var/www/ai100fen
```

这会在该仓库的本地Git配置中启用`deploy/hooks`、`ai100fen.deploy=true`及仅快进更新。已有自定义Hook时安装会停止，避免覆盖。其他开发检出默认不执行上线操作。

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
