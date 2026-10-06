# 本地 push，服务器 pull

当前预览站：`https://ai100.aicsi.cn`，服务器目录：`/var/www/ai100fen`。

前端在开发机执行`npm run build`，将`public/build`与源码一起提交、推送。服务器直接使用仓库中的构建产物，不需要Node或手工上传资源。

## 本地推送

完成代码检查和适用测试；涉及前端变更时先构建，再提交并推送两个仓库：

```sh
git push origin main
git push gitee HEAD:master
```

GitHub使用main，Gitee使用master，两者应包含同一个已验证提交。推送完成后，在服务器执行下文的`git pull`发布。

## 服务器一次性配置

当前服务器已配置。D-033使用`dante:dante`管理项目，`www-data`加入`dante`组；父目录`/var/www`保留`root:root 755`。新建同等环境的检出后，先以root执行一次权限配置：

```sh
apt-get install -y --no-install-recommends acl
sh deploy/enable-deploy-user.sh /var/www/ai100fen
```

脚本限定此项目，备份原归属、权限、ACL、Git配置和环境文件校验值至仅root可访问的`/var/backups/ai100fen/deploy-user-*`。保留已有`dante`账号和SSH密钥；账号不存在时才创建，公钥文件不存在时才从root的授权公钥初始化，不复制私钥。重载工作进程以更新组成员，不更改Nginx配置。

源码目录750、文件640（保留必要的可执行位），默认ACL确保新文件对组只读；`.git`仅部署用户可访问，`.env`保持640且内容不变。`storage`和`bootstrap/cache`采用2770目录及共享写入默认ACL，使部署用户和Web进程都能写入；Web进程不能修改源码、依赖、构建资源或Git。运行文件由实际创建者拥有，不要求所有运行文件都属于dante。不得对整个`/var/www`递归改归属或设置777。

`/etc/sudoers.d/ai100fen-deploy`只新增`dante`免密执行`/usr/bin/systemctl reload php8.5-fpm`，不加入sudo组、不新增任意命令权限；现有dante账号原本的需密码sudo规则保留。

随后以`dante`登录并配置Git更新：

```sh
ssh dante@39.106.113.140
cd /var/www/ai100fen
sh deploy/enable-git-pull.sh /var/www/ai100fen
```

这会添加Gitee HTTPS远程并将服务器当前分支的上游设为`gitee/master`，同时启用`deploy/hooks`、`ai100fen.deploy=true`及仅快进更新。保留原GitHub origin与服务器本地分支名称（当前为main）；服务器近期访问GitHub过慢，日常pull固定从Gitee拉取。已有自定义Hook、不同的同名远程或分叉历史时安装会停止，避免覆盖。其他开发检出默认不执行上线操作。

## 账号系统首次初始化

D-026接入真实账号。首次发布前需为项目配置MySQL数据库`ai100fen`及仅访问此库的账号，按`deploy/preview.env.example`补齐`DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`。密码只存服务器受保护的`.env`，保留现有APP_KEY、HTTPS安全Cookie及其他站点配置；先备份`.env`，检查目标库与初始迁移，确认没有同名表冲突。

这一步独立于上线Hook，由运维手动执行：

```sh
php artisan config:cache
php artisan migrate --force --no-interaction
php artisan migrate:status
```

当前只执行框架已存在的三份初始迁移，建立用户、密码重置Token、会话、缓存和队列表；账号会话与缓存继续使用文件。不要运行`migrate:fresh`、`db:seed`或示例账号Seeder。后续无数据库/环境变化的发布仍只需`git pull`；新迁移必须另行审阅与手动执行。

目前未配置真实邮件投递，不开放忘记密码或邮箱验证；已登录用户可在个人中心修改密码。D-031免费任务账号进度已接入；旧付费课程、购买和订阅仍未接入。

## 日常更新

以`dante`登录服务器进入项目目录后，只需：

```sh
ssh dante@39.106.113.140
cd /var/www/ai100fen
git pull
```

有新提交时，post-merge以`dante`校验资源、按锁文件安装生产PHP依赖，刷新Laravel配置、路由与视图缓存，通过受限sudo平滑重载PHP 8.5 FPM。普通发布不再以root执行Composer、Laravel或修复权限；运行目录依靠一次性配置的默认ACL保持共享写入。若权限被外部操作破坏，由运维审阅后重新运行权限配置，不能临时放宽源码写入权限。

同时执行`php artisan project:sync-history`，将当前Git HEAD的真实提交记录保存到私有`storage/app/private/commit-history.json`，供`/commits`页面读取。运行环境须可执行Git并保留完整仓库历史；无需GitHub Token、API或数据库。不会公开作者邮箱、完整提交正文或差异。同步失败会保留旧快照并中止本次刷新，修复后重跑`sh deploy/refresh.sh`。

本地开发完成提交后执行同一命令更新预览数据。快照不进入Git，页面未同步时会明确提示；后续没有新提交而只想刷新记录，可以单独执行该命令。

脚本不修改`.env`或APP_KEY，不执行数据库迁移，不更改Nginx配置。PHP扩展、新服务、环境变量或数据库结构变化仍须单独评审处理。

## 发布与故障处理

- 本地运行适用测试；前端变更须运行`npm run build`，检查并提交最新`public/build`，再push；不要提交`.env`、`vendor`、`node_modules`或`public/hot`。
- 日常发布遵循本地push、服务器pull，pull成功后检查Hook结果和线上页面；手工上传资源或fetch/merge不作为常规上线流程。
- 保持服务器检出干净；发现本地业务修改时先保留并处理，不使用强制重置覆盖。
- Git更新和Hook部署不是原子操作；Hook报错不会回滚已经更新的源码。检查错误后执行`sh deploy/refresh.sh`重试，不要把“Already up to date”当作部署重试。
- 回滚先明确要恢复的提交，再按对应锁文件、构建产物与缓存恢复；脚本不自动回滚业务数据。

## 当前服务器验证（2026-10-01）

- 账号发布前已配置独立`ai100fen`数据库及项目账号，三份框架初始迁移均已完成，未执行Seeder；APP_KEY及非数据库环境值保持不变。账号初始化前的`.env`保存于仅root可访问的`/var/backups/ai100fen/accounts-20261001-162053`。
- 已在`/var/www/ai100fen`启用Hook，并通过实际`git pull`验证自动安装依赖、刷新缓存与FPM重载。
- 依据D-029，服务器当前main分支跟踪`gitee/master`，已核对两个仓库同一提交，并按本地push→服务器git pull验证发布。
- 首页、问题池、价格页、免费第一课和清单下载正常；未开放清单与`.env`、`.git`继续拒绝访问。D-030将测试域名robots改为允许抓取，HTTP/HTTPS均使用noindex响应头；环境隔离、Nginx安装及Cloudflare人工清单见[CRAWLER_POLICY.md](CRAWLER_POLICY.md)。
- 已核对全部6份构建资源的SHA256与本地一致，确认`.env`字节未变。
- 切换前的构建产物、bootstrap缓存与`.env`保存在仅root可访问的`/var/backups/ai100fen/git-pull-20261001-154705`，旧构建遗留文件另行归档，不覆盖业务数据。

## 普通用户发布验证（2026-10-02）

- 项目与Git由`dante:dante`管理，`www-data`加入dante组；已确认Nginx和PHP-FPM新工作进程加载该组，`/var/www`保持root:root 755。
- 原权限及ACL、Git配置与.env校验值备份至`/var/backups/ai100fen/deploy-user-Ykiqv3dC`；.env内容校验一致，没有执行迁移或改动业务数据。
- 用dante实际git pull，自动完成Composer、配置/路由/视图缓存、提交快照与受限sudo重载；服务器检出保持干净。普通命令的免密sudo被拒绝，原有需密码管理规则未改动。
- 实测Web可读源码、不可写源码/.env/.git/依赖/构建；运行目录双方新建文件并交叉写入成功，默认ACL保证新源码文件640、运行文件660，测试文件已清理。
- 首页、三个免费任务、课程页、问题池、账号入口、提交墙、robots与全部构建资源正常；个人中心继续要求登录，私有路径403、后台404，测试站Allow规则与noindex响应头保持。


## Filament课程管理首次初始化（D-034）

`git pull`只发布代码与资源，不自动增加数据库字段或授予管理员权限。新增迁移与后台使用说明见[docs/ADMIN.md](../docs/ADMIN.md)。首次启用须先备份并审阅目标数据库，再用dante独立执行`php artisan migrate --force --no-interaction`，核对迁移状态；随后对人类明确指定的已有账号运行`php artisan admin:set 已注册邮箱`并确认。保留已有密码与.env，不创建默认管理员。后台路径为`/galaxy`。

Filament的Composer post-autoload-dump会执行`filament:upgrade`，发布被Git忽略的官方CSS/JS/字体；这些由dante写入并继承源码组只读ACL，不手工上传、不改为777。后台主题选择存数据库，前台新请求生效；选择“跟随APP_THEME”可恢复环境配置。后续无数据库变化时仍只需git pull。

## 课程封面公开存储（D-046）

封面使用`public`磁盘，文件由Filament写到`storage/app/public/course-covers`，不进入Git。代码发布后先审阅`2026_10_02_200000_add_course_series_cover.php`和目标库，再以`dante`独立执行：

```sh
php artisan migrate --path=database/migrations/2026_10_02_200000_add_course_series_cover.php --force --no-interaction
php artisan storage:link
```

若链接已存在，`storage:link`应保持现状；核对`public/storage`确实指向项目`storage/app/public`，且Web进程能够读取上传文件。`storage`沿用D-033的组权限和默认ACL，不给源码目录额外写权限，不使用777。公开目录项的封面在管理员保存后即可显示；其他课程仍遵守既有发布过滤。git pull Hook不会迁移、创建链接或复制用户上传文件，备份策略应包含`storage/app/public`。

## 现有课程入库（D-036）

与日常git pull分开，新增课程内容前保存私有课程/课时/学习记录快照，执行`php artisan courses:import-legacy --dry-run`核对新增与跳过清单，再独立运行`php artisan courses:import-legacy`。不需要新迁移或环境变量。全程仅新增缺少的课程slug，遇到同slug整门跳过；重复运行新增0。导入16门草稿与网站已有10课时，不补造其他15门的大纲，不发布或收费，不覆盖既有数据。审核见[docs/LEGACY_COURSE_IMPORT.md](../docs/LEGACY_COURSE_IMPORT.md)。Hook继续不自动导入。

## Free Lab首次初始化（D-031）

本地push、服务器pull后，新增路由需要三张课程学习表。审阅`2026_10_01_180000_create_course_learning_tables`，确认目标项目数据库和迁移状态，再独立执行一次：

```sh
php artisan migrate --force --no-interaction
php artisan free-lab:install
```

迁移只建course_series、lessons、lesson_progress，不修改users或原课程数组。内容命令只新增不存在的首批三个slug；不覆盖编辑、不重置记录、不写示例用户，重复执行新增0。不能用migrate:fresh、通用db:seed代替。后续无数据库/新内容变更时仍只需git pull；Hook不自动运行上述命令。

验证`/lab`、三个任务页面、Paul推荐、下载及未登录进度保存拒绝；当前测试域名继续允许抓取并返回noindex响应头。登录验收的跨用户隔离与幂等由测试覆盖，不在线上创建测试账号。

## 累计代码与既有迁移上线（2026-10-05）

- 用户明确要求执行上线。以`dante`核对服务器干净检出、原HEAD `447dee3`及迁移状态，独立审阅`2026_10_04_010000_add_course_recycle_bin`与`2026_10_05_010000_add_lesson_video_poster`；前者只增加可空删除时间及空的slug屏蔽表，后者只增加可空封面路径，不执行课程删除或内容同步。
- 在维护模式下将应用库结构、数据及触发器、`.env`和既有业务记录校验快照备份到私有`/home/dante/ai100fen-backups/deploy-20261005-150856`；目录0700、数据库和环境备份0600。数据库账号无EVENT导出权限，首次尝试失败后立即恢复旧站，再用既有权限完成应用库备份，不扩大数据库权限。
- 本次先临时跳过单次pull的post-merge，快进到代码提交`81d3fa7`，再按各自`--path`独立执行上述两份迁移（批次9、10），最后运行`sh deploy/refresh.sh`并退出维护。全部12份迁移为Ran，依赖安装、配置/路由/视图缓存、提交记录快照与PHP 8.5 FPM重载成功。Git已存储的Hook配置保持不变，后续无新迁移的更新恢复普通`git pull`；Hook仍不执行迁移。
- 备份SHA256、`.env`字节与全部既有业务行的原字段内容校验一致；仍为2个用户、20门课程、13个课时，学习记录等其他既有业务数据保持不变。没有执行Seeder、导入/安装命令、管理员授权或课程删除，没有修改Nginx、环境配置或上传文件。
- 12个公开页面返回200并保持noindex，后台和个人中心继续要求登录；`.env`、`.git`及私有提交快照拒绝访问。全部6份构建资源SHA256与本地一致，1440/390/320px验证目标清单16px、顶部关于及选中状态、五行课时编号/播放或锁定图标，无横向溢出或JS错误。发布前108项测试/1669个断言及Vite构建已通过。
- 本次只发布代码与数据库结构。线上网站课程仍为第一课试看、其余四节锁定，本地五节完整免费课程内容和发布状态未同步；正式录播及License配置另行处理，本次不以演示片源代替真实录播，也未验证线上真实视频播放。后台删除等写入功能沿用隔离库测试，不操作线上实际业务数据。

## 网站课程内容独立同步（2026-10-06，D-054）

- 用户明确授权同步本地《10分钟搭建.com网站》，并选择0元、五节全部免费；先核对线上课程无学习记录、本地有一条记录，学习记录不跨环境同步。
- 以`dante`将应用数据库、环境文件与课程预检保存至私有`/home/dante/ai100fen-backups/course-sync-20261006-025948-e5c59f`，另存事务前后内容快照及同步清单；目录0700、备份0600，数据库SHA256与.env校验通过。备份使用既有数据库权限，不导出EVENT或增加权限。
- 同步脚本仅存私有备份目录，不进入项目源码或日常Hook。先演练预检、同步、幂等和已有学习记录拒绝，再在线上预检；实际写入通过管理员Policy、目标课程/课时行锁、事务及模型校验。复用四课并新增Agent授权课，旧六节归档，保留原内容与服务器ID、slug、封面和排序；全站仍20门课程，课时从13增至14，学习记录仍0。其他课程、用户、关系、主题等业务记录及.env逐字段核对不变。
- 重复执行返回unchanged。五节旧地址跳转`/lab`，实际学习/资料下载正常，归档地址404；1440/390/320px验证五个播放入口、全免费、课程目标、正文/Prompt/验收及手机布局，无溢出或JS错误。35项测试/740个断言通过，未在线上创建测试账号或保存验收进度。
- 本次没有业务代码、迁移、环境变量或构建变化，无须刷新应用缓存或重载FPM；记录文档按正常push/pull同步。没有导入本地账号、学习记录、.env、License或演示视频，没有删除课程。正式录播仍待配置，日常Git更新继续不自动写业务内容。
