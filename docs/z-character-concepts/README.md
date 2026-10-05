# Z 外星向导形象候选

日期：2026-10-04。状态：六款原创视觉候选，尚未选定正式角色。

本次按用户要求，延续现有 Z 的矢量插画方式，探索不同头型、眼睛数量、身体轮廓和服装。使用原生 SVG 绘制与浏览器预览，PNG 从 SVG 导出；未调用图片生成 API。当前网站仍使用原有 `paul-avatar`，本目录是独立设计稿。

## 查看与下载

直接打开 [preview.html](preview.html)，可切换深浅背景、放大形象、查看 72 / 48 / 32px 头像，并下载 SVG 或透明 PNG。页面不需要构建或网络服务。

| 编号 | 候选 | 特征 | SVG | 透明 PNG |
| --- | --- | --- | --- | --- |
| 01 | 豆豆 Z / Scout | 绿皮肤、双触角、橙色飞行服；延续现有角色识别 | [原稿](z-01-scout.svg) | [PNG](z-01-scout.png) |
| 02 | 独眼 Z / Orbit | 奶黄圆头、单眼、环形触角；轮廓鲜明 | [原稿](z-02-orbit.svg) | [PNG](z-02-orbit.png) |
| 03 | 星耳 Z / Echo | 薄荷蓝、宽星耳、珊瑚围巾；温柔专注 | [原稿](z-03-echo.svg) | [PNG](z-03-echo.png) |
| 04 | 软糖 Z / Jelly | 紫色软体、三眼、小挎包；松弛灵动 | [原稿](z-04-jelly.svg) | [PNG](z-04-jelly.png) |
| 05 | 彗星 Z / Comet | 桃色长头、后掠触角、悬浮姿态；轻巧利落 | [原稿](z-05-comet.svg) | [PNG](z-05-comet.png) |
| 06 | 宇航 Z / Cosmo | 圆头盔、绿皮肤、深蓝宇航服；探索伙伴 | [原稿](z-06-cosmo.svg) | [PNG](z-06-cosmo.png) |

总览：[浅底](contact-sheet.png)、[深底](contact-sheet-dark.png)。手机验证：[整页](preview-mobile.png)、[放大详情](detail-mobile.png)。

## 素材约定

- SVG 全身画布为 `320 × 360`，含描述与独立透明背景，无外部字体、脚本或图片依赖。
- PNG 为 `960 × 1080` RGBA，保留透明背景及轻微地面阴影。
- 胸章/挎包的 Z 使用路径绘制，不依赖字体。
- 各 SVG 用 `--z-ink`、`--z-skin`、`--z-skin-shadow`、`--z-suit`、`--z-trim`、`--z-eye`、`--z-light`、`--z-accent` 定义语义颜色；宇航款另有 `--z-helmet`。未传入变量时使用当前候选配色。
- 外部 `img` 加载的 SVG 使用自身默认值。若以后接入主题，应内联 SVG 并在主题 CSS 中映射现有角色 Token；不在业务模板固定颜色，也不为每个主题复制页面。
- 预览中的深底只用于检查同一素材，不代表完整 Future 主题适配。

## 本次交付检查

- **Code changes**：新增六款 SVG、六款透明 PNG、独立 HTML 预览及视觉验证截图；未修改应用运行代码。
- **Tests**：Edge/Playwright 检查六款素材加载、深浅背景、六个放大弹窗、三种头像尺寸、下载目标、Escape 关闭及焦点返回、键盘 Enter 操作；`1440px` 桌面与 `390px` 手机布局通过，无横向溢出和浏览器脚本错误。人工检查浅底、深底、手机详情和 PNG 导出效果；PNG 尺寸及 alpha 元数据通过。应用代码未变，本次未运行 PHP 业务测试或 Vite 构建。
- **Migrations**：无。
- **Environment variables**：无。
- **Updated docs**：本说明、`TODO.md`。
- **Docs checked but unchanged**：`DECISIONS.md`、`DATA_MODEL.md`、`MVP.md`、`PRODUCT.md`、`PROJECT_CONTEXT.md`。候选设计没有形成新的产品、技术架构或数据规则。
- **Open issues**：最终角色选择及接入网站待后续确定；选定后再补齐 `idle / idea / got_it / so_so / awkward` 表情与 Pop/Future 主题适配。当前稿仅有静态默认表情，不宣称已有新增聊天或 Agent 执行能力。

参考：D-028 原创角色与表情、D-040 对外命名 Z、`docs/THEME_SYSTEM.md`、`docs/产品核心方法论.md`。这些候选是同一角色的外观方向，不是六个不同功能的助手。
