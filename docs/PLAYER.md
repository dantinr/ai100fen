# 课时视频播放器（D-053）

当前为Aliplayer Web 2.39.0接入预览，用户已追加授权提交并推送，服务器尚未上线。该版本于2026-09-01发布，已核对[官方发布历史](https://help.aliyun.com/zh/vod/developer-reference/release-notes-for-apsaravideo-player-sdk-for-web)、官方在线示例及npm latest。版本固定在`config/player.php`，未来升级须重新验证，不使用浮动latest CDN地址。

## 首屏布局

有视频的学习页先显示返回入口、课程名、当前课时标题和时长/分值，再显示播放器；完整目标、说明、分享、操作步骤、Prompt及验收位于视频下方。播放器使用紧凑栏头，避免重复课时大标题。Z角色和本人完成标记保留在课时标题中；没有视频的学习页保留原图文开场。

手机维持16:9画面，较矮桌面视口限制容器高度，媒体通过object-fit:contain保持原始比例。首屏验证从页面顶部开始，不使用锚点或自动滚动制造可见效果；浏览器字号、视口高度和不同课时标题会影响可见范围。演示封面复用本地website.svg，避免第三方封面不可访问时只显示黑屏；该封面是预览占位，演示片源仍明确标记。

## 配置

在未跟踪的`.env`中配置：

```dotenv
ALIYUN_PLAYER_LICENSE_DOMAIN=你的授权域名
ALIYUN_PLAYER_LICENSE_KEY=你的Web播放器LicenseKey
ALIYUN_PLAYER_DEMO=false
```

域名不带协议、路径或通配符，须与License应用匹配；绑定主域名可以覆盖其子域名，实际以阿里云授权校验为准。Web License配置按SDK要求传给浏览器，不属于VOD服务端AccessKey；任何真实Key都不写入源码、示例值或构建产物。后台课时“视频地址”填写HTTPS直连HLS/MP4等媒体地址，不能填写一般网页或视频网站观看页；HLS清单及分片须可访问且满足HTTPS/CORS要求。

SDK的JS/CSS来自官方CDN，仅在含视频的课时接近视口时加载。默认不自动播放、不预载视频媒体，用户自行点击播放；错误提示包含重试和图文入口。SDK接入无需npm播放器依赖；新增后台播放器封面设置的字段迁移见下文。

## 课时播放器封面（2026-10-05）

后台课时创建/编辑的“视频播放器”区新增可选图片上传，保存至`lessons.video_poster`。支持JPG、PNG、WebP，最大2 MB，提供16:9裁剪，建议1600×900；采用public磁盘`lesson-video-posters`目录，文件不进入Git。保存后通过Aliplayer的cover选项显示，不自动播放；学习页与管理员预览共用同一逻辑。

优先级：有效课时封面 → 有效课程封面 → 默认本地演示图（仅演示时）。无真实视频且未启用本地演示时，单独设置封面不会生成播放器。更换或移除后保留原文件，学习进度及课程发布状态不变；仅允许管理员修改，模型拒绝外部URL和不安全文件路径。图片为公开封面，不能存放需授权的课程资料。

本地已独立执行非破坏性迁移`2026_10_05_010000_add_lesson_video_poster.php`，既有课时值为null。线上须运维独立审阅和执行，git pull Hook不会迁移。无新增环境变量、依赖或第三方服务；无需更改License或播放器版本。

## 本地效果预览

已有License绑定`aicsi.cn`，用户选择子域名本地预览：

1. 本机Nginx既有ai100fen站点增加`ai100fen-preview.aicsi.cn`别名，指向同一`D:/www/ai100fen/public`；修改前备份，通过`nginx -t`后reload。
2. Windows hosts添加`127.0.0.1 ai100fen-preview.aicsi.cn`，仅改变本机解析，不修改公网DNS；系统文件需要管理员写权限，由用户完成。
3. 本地`.env`设置`ALIYUN_PLAYER_DEMO=true`；若本机使用Laravel配置缓存，运行`php artisan config:clear`。
4. 打开`http://ai100fen-preview.aicsi.cn/lab/build-a-website/first-website`。

没有真实视频地址的课时使用公开Big Buck Bunny HLS测试片源，明确标记“演示视频 · 非课程录播”，不写入数据库。只有local环境可显示演示，生产环境即使误开DEMO也不会使用测试视频；有真实video_url时始终优先播放真实视频。

本机Nginx和hosts原始备份位于被Git忽略的`.local/player-preview/local-config-backup/`。如不再需要预览，关闭DEMO并仅移除本次新增的站点别名、hosts行；不要用旧备份覆盖后续其他配置。

## 权限与完成

播放器只能出现在既有服务端允许访问的课时或管理员预览中；公开课程介绍不输出视频播放地址。SDK License仅授权播放器运行，不代表用户购买权限，也不能防止他人复制公开媒体URL。当前不接入阿里云VOD AccessKey、VID/PlayAuth、STS、URL签名或DRM，不宣称提供付费视频保护。

观看结束不保存进度，不勾选验收、不授予分值；沿用现有真实成果验收。当前不增加视频断点的账号保存接口。正式课时录播内容仍待提供。
