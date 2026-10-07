# AI100分 THEME_SYSTEM.md

## 1. 文档定位

本文件定义 AI100分 的主题系统（Theme System）架构。

目标：

> 页面结构、业务逻辑、组件语义保持稳定，视觉风格可以整体替换。

当前默认主题：

```text
pop
```

未来可新增：

```text
future
minimal
retro
dark
```

主题切换不应要求重写页面业务逻辑，也不应要求大规模修改 Blade 模板。

---

# 2. 核心原则

AI100分 的主题系统遵循：

```text
Business
↓
Component
↓
Semantic Token
↓
Theme
↓
Final Visual
```

页面只表达：

```text
按钮
卡片
进度
输入框
状态
UFO
```

页面不应该直接表达：

```text
黄色按钮
黑色粗边框
6px硬阴影
青色荧光边框
Future Glow
```

这些具体视觉由 Theme 决定。

核心原则：

> 页面定义“是什么”，主题定义“长什么样”。

---

# 3. 主题架构

主题系统分为三层。

## 3.1 Base Layer

Base 层负责长期稳定的系统规则：

- 页面布局；
- Grid；
- Responsive；
- Accessibility；
- 组件结构；
- 状态语义；
- spacing；
- 基础 typography；
- focus 行为；
- reduced motion；
- z-index；
- interaction contract。

Base Layer 不包含具体视觉风格。

---

## 3.2 Theme Layer

Theme 层负责：

- 颜色；
- 字体风格；
- 边框；
- 圆角；
- 阴影；
- 背景纹理；
- 插图；
- 图标表现；
- UFO 风格；
- 动画语言；
- 页面视觉气质。

例如：

```text
pop
future
minimal
```

---

## 3.3 Page Variant Layer

Page Variant 负责控制同一主题下不同页面的视觉浓度。

例如：

```text
home
question-pool
series
lesson
account
checkout
admin
```

Page Variant 不能重新定义主题，只能控制主题元素的使用强度。

---

# 4. 不允许把 Pop 写死在组件名称中

现有命名如果包括：

```text
PopButton
PopCard
PopBadge
PopInput
PopPanel
PopProgress
```

应逐步迁移为：

```text
AppButton
AppCard
AppBadge
AppInput
AppPanel
AppProgress
AppScore
AppCallout
```

原因：

`PopButton` 把具体视觉风格绑定到了组件。

正确关系：

```text
AppButton
+
theme=pop
=
Pop 风格按钮
```

未来：

```text
AppButton
+
theme=future
=
Future 风格按钮
```

组件语义不变。

---

# 5. Semantic Design Tokens

所有主题必须通过统一语义 Token 驱动。

禁止页面和通用组件直接写固定视觉值。

建议 Token：

## 5.1 Colors

```css
--color-bg;
--color-surface;
--color-surface-alt;

--color-primary;
--color-secondary;
--color-accent;

--color-text;
--color-text-muted;
--color-text-inverse;

--color-success;
--color-warning;
--color-danger;
--color-info;

--color-border;
--color-border-strong;
```

---

## 5.2 Borders

```css
--border-width-default;
--border-width-strong;
--border-style-default;
```

---

## 5.3 Radius

```css
--radius-xs;
--radius-sm;
--radius-md;
--radius-lg;

--radius-button;
--radius-card;
--radius-input;
--radius-badge;
```

---

## 5.4 Shadows

```css
--shadow-sm;
--shadow-md;
--shadow-lg;

--shadow-button;
--shadow-card;
--shadow-panel;
```

---

## 5.5 Typography

```css
--font-heading;
--font-body;
--font-mono;

--weight-heading;
--weight-body;
```

---

## 5.6 Motion

```css
--motion-fast;
--motion-normal;
--motion-slow;

--ease-default;
--ease-emphasis;
```

---

## 5.7 Decorative

```css
--pattern-opacity;
--grid-opacity;
--glow-strength;
--scan-opacity;
```

---

# 6. Pop Theme 示例

Pop Theme 可以定义：

```css
[data-theme="pop"] {
    --color-bg: #FFF9E8;
    --color-surface: #FFFFFF;
    --color-surface-alt: #FFD84D;

    --color-primary: #FFD84D;
    --color-secondary: #FF8A3D;
    --color-accent: #B8EF55;

    --color-text: #111111;
    --color-text-muted: #5B5B5B;
    --color-border: #111111;
    --color-border-strong: #111111;

    --border-width-default: 2px;
    --border-width-strong: 3px;

    --radius-button: 8px;
    --radius-card: 8px;
    --radius-input: 6px;
    --radius-badge: 4px;

    --shadow-button: 4px 4px 0 #111111;
    --shadow-card: 6px 6px 0 #111111;
    --shadow-panel: 8px 8px 0 #111111;

    --pattern-opacity: 0.15;
    --grid-opacity: 0;
    --glow-strength: 0;
}
```

视觉语言：

- 粗黑描边；
- 硬阴影；
- 高对比；
- 半调网点；
- StarBurst；
- 漫画贴纸；
- 复古未来感；
- 漫画 UFO。

---

# 7. Future Theme 示例

Future Theme 可定义：

```css
[data-theme="future"] {
    --color-bg: #050816;
    --color-surface: #0C1224;
    --color-surface-alt: #111A30;

    --color-primary: #65F4FF;
    --color-secondary: #8C7CFF;
    --color-accent: #A7FF6A;

    --color-text: #F5F7FF;
    --color-text-muted: #9EA8BF;
    --color-border: rgba(101, 244, 255, 0.28);
    --color-border-strong: rgba(101, 244, 255, 0.55);

    --border-width-default: 1px;
    --border-width-strong: 1px;

    --radius-button: 14px;
    --radius-card: 18px;
    --radius-input: 12px;
    --radius-badge: 999px;

    --shadow-button: 0 0 16px rgba(101,244,255,.12);
    --shadow-card: 0 0 24px rgba(101,244,255,.14);
    --shadow-panel: 0 0 36px rgba(101,244,255,.16);

    --pattern-opacity: 0;
    --grid-opacity: 0.2;
    --glow-strength: 1;
}
```

视觉语言：

- 深色背景；
- HUD；
- Grid；
- Glow；
- 扫描线；
- 半透明面板；
- Future UFO；
- 克制的荧光效果。

注意：

Future 不等于蓝色科技大屏。

依然必须保持 AI100分 的产品人格。

---

# 8. Component Contract

所有通用组件必须依赖语义 Token。

例如：

```css
.ui-card {
    background: var(--color-surface);
    color: var(--color-text);
    border:
        var(--border-width-strong)
        solid
        var(--color-border-strong);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
}
```

禁止：

```css
.ui-card {
    background: #FFD84D;
    border: 3px solid #111;
    box-shadow: 6px 6px 0 #111;
}
```

因为这会把 Pop 视觉写死。

---

# 9. Tailwind 使用规则

如果项目使用 Tailwind：

避免在大量页面直接出现：

```text
bg-yellow-400
border-black
shadow-[6px_6px_0_#111]
rounded-lg
text-cyan-300
```

优先：

```text
ui-card
ui-button
ui-panel
ui-badge
```

或使用基于 CSS Variables 的 Theme Token。

目标：

> 页面模板不感知具体主题配色。

---

# 10. 主题资源目录

建议目录：

```text
resources/
├── css/
│   ├── base.css
│   ├── components.css
│   └── themes/
│       ├── pop.css
│       ├── future.css
│       └── minimal.css
│
├── themes/
│   ├── pop/
│   │   ├── ufo.svg
│   │   ├── patterns/
│   │   └── illustrations/
│   │
│   ├── future/
│   │   ├── ufo.svg
│   │   ├── grids/
│   │   └── illustrations/
│   │
│   └── minimal/
│       └── ...
```

如果实际项目已有前端目录规范，应遵循现有架构，不为了匹配本文强行迁移整个项目。

---

# 11. UFO 主题化

UFO 是 AI100分 的品牌角色。

UFO 的“角色语义”保持稳定：

```text
探索
扫描
收集问题
进度
完成
```

但视觉表现由主题决定。

## Pop UFO

- 黑色描边；
- 漫画造型；
- 黄色 / 橙色光束；
- 轻微弹性；
- 贴纸感。

## Future UFO

- 更轻薄；
- HUD 元素；
- Cyan / Green 扫描；
- Glow；
- 更平滑运动；
- 未来仪器感。

重要：

> UFO 不能因为换主题而变成完全不同的角色。

品牌识别应该保留。

---

# 12. 动画主题化

动画同样不能写死。

建议抽象动画语义：

```text
--motion-enter
--motion-hover
--motion-press
--motion-scan
--motion-progress
--motion-complete
```

Pop：

```text
button = press
card = offset
ufo = comic-float
complete = pop-burst
```

Future：

```text
button = glow
card = float
ufo = hover-scan
complete = pulse
```

---

# 13. 页面视觉浓度

不同主题使用同一套页面浓度概念。

例如：

```text
首页              80%
问题池            90%
Series详情        60%
我的100分         60%
直播页            40%
Lesson正文        25%
登录              20%
支付              15%
后台               5%
```

Pop 主题：

```text
90% Pop
```

Future 主题：

```text
90% Future
```

Visual Intensity 是独立概念，不绑定具体主题。

---

# 14. Theme Registry

建议建立统一 Theme Registry。

概念示例：

```php
return [
    'default' => 'pop',

    'themes' => [
        'pop' => [
            'name' => 'Pop',
            'stylesheet' => 'themes/pop.css',
        ],

        'future' => [
            'name' => 'Future',
            'stylesheet' => 'themes/future.css',
        ],
    ],
];
```

实际实现可根据当前 Laravel 架构调整。

---

# 15. Theme Selection

第一阶段只实现：

```text
全站单一主题
```

例如：

```text
theme = pop
```

不要第一版实现用户级主题切换。

后续可以扩展：

```text
user_preferences.theme
```

但这不是 MVP。

---

# 16. HTML Theme Hook

推荐：

```html
<html data-theme="pop">
```

未来：

```html
<html data-theme="future">
```

组件无需变化。

---

# 17. 后台主题设置

未来后台可以提供：

```text
系统设置
↓
当前主题
↓
Pop
Future
Minimal
```

切换后：

- 页面结构不变；
- 数据不变；
- 业务逻辑不变；
- Theme Token 和 Theme Assets 改变。

---

# 18. 文档拆分建议

现有：

```text
POP_DESIGN_SYSTEM.md
```

建议长期拆成：

```text
DESIGN_SYSTEM.md
THEME_POP.md
THEME_FUTURE.md
```

职责：

## DESIGN_SYSTEM.md

定义：

- Theme 架构；
- Token；
- Component Contract；
- Responsive；
- Accessibility；
- 主题切换规则。

## THEME_POP.md

定义：

- Pop 配色；
- 边框；
- 阴影；
- Halftone；
- StarBurst；
- Pop UFO；
- Pop Motion。

## THEME_FUTURE.md

定义：

- Future 配色；
- Grid；
- Glow；
- HUD；
- Future UFO；
- Future Motion。

本文件可作为主题系统迁移的执行规范。

---

# 19. 现有 Pop 页面迁移原则

当前已经存在的 Pop 视觉不要推倒重做。

迁移目标：

```text
Hard-coded Pop
↓
Semantic Token
↓
Theme Pop
```

优先抽取：

1. Colors
2. Borders
3. Shadows
4. Radius
5. Typography
6. Buttons
7. Cards
8. Inputs
9. Progress
10. UFO

---

# 20. Codex 执行任务

Codex 应按以下顺序执行。

## Phase 1：审计

检查：

- Blade；
- CSS；
- Tailwind；
- JS；
- Components。

找出：

- 写死颜色；
- 写死边框；
- 写死阴影；
- 写死圆角；
- Pop 命名组件；
- 页面级重复样式。

输出审计结果。

---

## Phase 2：建立 Token

创建基础 Theme Token。

不要立即改变视觉。

目标：

> 迁移前后默认 Pop 主题视觉尽量保持一致。

---

## Phase 3：组件语义化

逐步：

```text
PopButton → AppButton
PopCard → AppCard
PopBadge → AppBadge
...
```

如果重命名会造成大量风险，可先保留兼容 Alias，再逐步迁移。

---

## Phase 4：建立 Pop Theme

将当前 Pop 视觉值迁移至：

```text
theme=pop
```

保证：

> Pop 仍然是默认主题。

---

## Phase 5：建立 Future Theme 骨架

只创建：

- Token；
- CSS；
- Theme Registry；
- Asset 路径。

不要一开始做完整 Future 页面设计。

目标：

> 验证主题机制确实可切换。

---

## Phase 6：主题切换验证

至少验证：

- 首页；
- Series 列表；
- Lesson；
- 问题池；
- 登录。

切换：

```text
pop
↓
future
↓
pop
```

不应：

- 改变业务逻辑；
- 破坏布局；
- 导致按钮失效；
- 影响表单；
- 影响移动端。

---

# 21. 不允许的实现

禁止：

## 21.1 页面内大量主题判断

避免：

```php
@if ($theme === 'pop')
...
@elseif ($theme === 'future')
...
@endif
```

除非是必须替换的大型主题资产。

---

## 21.2 每个主题复制一套页面

禁止：

```text
home-pop.blade.php
home-future.blade.php
lesson-pop.blade.php
lesson-future.blade.php
```

主题不应导致页面代码分叉。

---

## 21.3 颜色替换等于主题

主题必须包含：

- Color
- Typography
- Border
- Radius
- Shadow
- Pattern
- Illustration
- Motion
- Asset

不能只做：

```text
Yellow → Cyan
```

---

# 22. Accessibility

所有 Theme 必须保证：

- 文字对比度；
- Focus 状态；
- Keyboard Navigation；
- Reduced Motion；
- 状态不能只靠颜色表达；
- 表单错误清楚；
- Lesson 阅读体验稳定。

主题切换不能破坏可访问性。

---

# 23. Performance

主题系统应保持轻量。

原则：

- CSS Variables 优先；
- SVG 优先；
- 避免为主题加载大型 JS；
- 不加载未使用主题的大型图片资源；
- 不因为动画增加明显首屏成本。

---

# 24. 测试要求

至少增加或执行以下验证：

```text
默认主题可加载
非法主题回退默认主题
Pop 页面正常
Future 页面正常
移动端正常
表单正常
Lesson 可读
UFO 不阻塞点击
reduced-motion 正常
```

---

# 25. 文档同步

完成 Theme System 改造后检查：

```text
AGENTS.md
PRODUCT.md
DECISIONS.md
TODO.md
DATA_MODEL.md
```

只有必要时更新。

重点：

- AGENTS.md 增加 Theme System 开发约束；
- DECISIONS.md 记录主题架构决策；
- TODO.md 更新迁移状态。

Theme 是前端架构，不一定需要修改 DATA_MODEL.md。

---

# 26. Codex 最终报告格式

完成任务后输出：

```text
Theme architecture:
...

New tokens:
...

Components migrated:
...

Hard-coded styles removed:
...

Pop compatibility:
...

Future theme skeleton:
...

Tests:
...

Updated docs:
...

Checked but unchanged:
...

Remaining migration:
...
```

---

# 27. 最终原则

AI100分 的主题系统不是：

> 给网站换一个皮肤。

而是：

> 将视觉语言从业务页面中解耦。

最终关系：

```text
AI100分
├── Product Identity
│   ├── Solve
│   ├── Create
│   └── Explore
│
├── Components
│   ├── Button
│   ├── Card
│   ├── Progress
│   ├── Question
│   └── UFO
│
└── Themes
    ├── Pop
    ├── Future
    └── ...
```

品牌内核不变。

视觉表达可以进化。

一句话：

> 业务写一次，主题随时换。

---

# 28. 当前落地（2026-10-01，D-022）

## 审计与迁移

此前`app.css`、`pop.css`和`ufo.css`混合布局与视觉，两套Root颜色和大量页面覆盖并存；海报与StarBurst采用Pop命名，UFO SVG包含固定颜色，进度动画计时写在JS中。

现将共享结构保留在`base.css`，外观迁至`components.css`，页面浓度迁至`variants.css`。配色、边框、圆角、阴影、字族/字重、图案、徽记形状、倾斜、UFO颜色/描边/扫描及动画时长由统一语义Token驱动。课程正文中的示例代码属于教学内容，不随主题改写；未使用的Laravel欢迎模板与旧示例SVG资源保留，不属于当前前台主题组件。

`app-course-art`替代`pop-course-art`，由目录、详情和学习记录复用；`app-score-emblem`替代`pop-starburst`。原按钮、卡片、搜索、Badge、面板、进度及UFO保留语义与行为，不复制主题页面。

## 配置与加载

- `config/themes.php`注册`pop`和`future`，包括样式入口及资源目录；`APP_THEME`选择全站主题，非法值回退默认主题。
- `App\Support\FrontendTheme`统一解析主题和页面类型，Layout Composer注入布局；根元素使用`data-theme`，Body使用独立的`data-page`。
- 页面类型：home、question-pool、series、lesson、account、checkout、progress、live、default。读取页、账号、购买说明控制装饰，Lesson内关闭UFO循环动画。
- Vite分别构建两个主题CSS；页面只加载共享CSS和选中主题CSS，对应SVG图案随主题加载。UFO保留同一内联SVG几何，以Token改变表现。
- 更换主题：设置`.env`中的`APP_THEME=future`，本地执行`php artisan config:clear`；生产使用`php artisan config:cache`。恢复时设为`pop`并刷新配置。初次运行须先`npm run build`。
- 新主题应登记注册表与Vite入口，复制现有Token契约后填入自身视觉值，并在`resources/themes/{theme}`提供被CSS引用的资产；无需修改业务页面或Controller。
- D-027提交方块墙新增`--color-activity-0`至`--color-activity-4`五级强度Token，Pop和Future均已实现；新主题须实现这些Token，提交数量与统计规则不随主题变化。
- D-028的外星向导现按D-040更名为Z，复用同一分层SVG及状态接口；`paul-*`内部标识与Token保留兼容，不用作对外显示名；新主题须实现`--paul-skin`、`--paul-skin-shadow`、`--paul-stroke`、`--paul-stroke-width`、`--paul-eye`、`--paul-suit`、`--paul-suit-accent`、`--paul-light`、`--paul-fx`、`--paul-sweat`及`--paul-breathe-duration`，其他动作时长使用共享Motion Token。结构与响应式放在base，外观放在components，低浓度页的idle限制放在variants，不复制主题组件。
- 本轮不增加主题选择器、用户偏好、业务表、数据库迁移或后台设置。

## Pop兼容与Future骨架

Pop默认保留纸白、黄橙/酸绿、黑描边、硬阴影、海报和漫画UFO；布局和响应式沿用当前试版。Future骨架提供深色、不同标题字族/字重、轻边框、大圆角、光晕、网格、圆形完成标记和更平滑的UFO表现。两者实现同一Token集合。

Future用于验证机制，主题专属插图和完整页面细节尚未定稿。后续增加视觉资产仍应复用相同角色和组件契约。

## 验证

- 自动测试覆盖默认主题、非法配置回退、Future公共页面、独立页面类型、选中主题样式加载及服务端资料限制；原前台测试继续执行。
- 浏览器验证Pop→Future→Pop，检查首页、Series列表/详情、Lesson、问题池、账号、价格及学习记录，覆盖桌面与390px/320px手机布局。
- 验证搜索、购买未开放弹窗、导航悬停/点击和键盘聚焦；当前学习进度保持已有本机10分。
- 减少动态效果沿用CSS媒体查询和JS系统偏好判断；当前浏览器测试环境不提供该系统偏好模拟，采用源码检查，不声称完成OS偏好端到端验证。
- 数据模型、MVP范围、产品定义和项目背景均未变更。

# 29. 后台全站配置（2026-10-02，D-034）

用户明确推进后台Theme配置后，Filament的`/galaxy/theme-settings`允许管理员选择Pop/Future或跟随APP_THEME。新增`theme_settings`单例id=1，显式后台选择优先，空值跟随环境，非法值回退注册表默认；尚未迁移时沿用APP_THEME。新请求读配置即生效，不改.env、不要求清缓存，不增加用户偏好或任意CSS编辑器。

主题注册表、Token、Vite按需加载与前台语义组件保持原架构，Future仍为骨架。Filament后台使用自身官方样式；前台Tailwind显式扫描前台视图/组件/JS，不扫描编译缓存中的后台模板。新课程Markdown沿用共享布局和主题外观。

此决策推进原第一阶段“后台主题管理后置”的范围，仅由已授权管理员操作，保存及每次Livewire动作检查权限。数据库字段、安装和验证见DATA_MODEL、docs/ADMIN.md，线上迁移仍不进入git pull Hook。



## 黑洞组件（D-041）

black-hole SVG及black-hole-nav-link独立于UFO；组件复用nav/hero/submission外观，颜色/描边/进入/旋转/吸入时间均由Pop/Future的`--black-hole-*` Token控制。新主题须补齐core、outline、disk、accent、detail、glow、glow-opacity、line-width、enter、orbit-duration、receive-duration；业务模板不写固定颜色。结构/响应式/几何动画在base，通用外观在components，具体值在主题；原UFO与paul-* Token契约保持。

导航只有Hover或focus-visible激活旋转，触控/窄屏不展示导航图形，hero/提交图形默认静态；减少动态效果同时在CSS和JS取消吸入。问题页内联Z避免与悬浮向导重复；其他页面保持原Z/UFO。私有问题列表与详情采用question-pool页面类型，复用主题。

## 课时阅读页视觉（2026-10-04）

实际课程学习页及管理员课时预览共用`frontend/free/lesson`：开头采用任务编号、课时图标与Z/UFO插画；目标区突出可验收结果，操作步骤为编号时间线，Prompt采用共享`agent-prompt`对话框，验收勾选显示明确的选中反馈。按用户要求移除独立“资料与可运行起点”及验收清单下载卡片，不删除已配置附件或改变下载授权；勾选验收与进度保存入口仍在。对话框用右侧“你→Agent”气泡呈现原始Prompt，左侧Z气泡明确为使用提示，底部复制原文；不显示虚构执行状态、不发送消息或接入在线聊天。操作步骤、Prompt、验收可通过原生页内链接和键盘进入；课程说明在侧栏可展开。

几何与响应式放在base，通用外观放在components，阅读页装饰浓度和单次入场动画放在variants。全部复用现有配色、图案、倾斜、阴影、字体、角色与Motion Token，不新增主题判断或位图资源；Pop保留贴纸与网点，Future沿用既有网格/光晕。插画不拦截操作、不播放循环动画；减少动态效果关闭入场与反馈过渡，正文保持低装饰浓度。

此次只调整展示，不修改课程内容、发布过滤、服务端访问/资料权限、验收标准或进度计算；管理员预览仍不保存进度。旧静态试看保留既有模板与代码区，仅Prompt复用相同对话框组件；布局和响应式使用base，通用外观使用components与既有Token。

Prompt、代码与课程链接共用`resources/js/clipboard.js`复制能力：安全上下文优先使用Clipboard API，HTTP或权限拒绝时使用临时文本框兼容复制，成功后清理并恢复焦点和原选区。临时文本框的不可见布局位于base；两种方式均失败时才保留原文选中和手动复制提示。验证应包含真实HTTP页面复制及粘贴，不能只模拟navigator.clipboard。

D-053的`lesson-video`播放器共用组件在有视频的学习页紧接紧凑课时标题和时长/分值信息，完整目标说明、分享与快捷导航位于视频下方；无视频的学习页继续使用原场景开场。有Agent的课时保留标题中的Z角色标识，进度标记仍按本人验收显示。播放器compact模式保留可访问的标题标签，避免重复大标题；外层卡片、演示标记、提示与视频下说明复用现有Token，16:9布局、桌面视口限高（媒体仍按原比例显示）、加载/错误状态、移动端倍速菜单滚动放在base；SDK只在含视频的课时接近视口时加载，同一版本资源复用。保留SDK标准操作控件，用code背景/文字Token保证浅色视频画面上的控制栏可读；不为不同主题复制播放器，也不影响课程权限和验收。

2026-10-05课时右侧聚焦当前课时：编号目标使用共享`lesson-objectives`组件，读取已保存的Lesson.objectives；未填写时仅显示已有goal，不补写课程内容。旁边保留本课时长、验收进度和验收入口，整门课程大纲、总分与说明移到学习内容之后。手机在正文开头显示同一目标清单，隐藏侧栏的重复目标，播放器仍在目标前；旧试看使用现有可公开目标，不开放受限正文。结构、编号和响应式在base，分隔线与目标标注复用components和既有Token，不新增主题分支、颜色或动画；管理员预览不保存进度。

## 课程介绍目标封面（2026-10-05）

`/series`使用`course-goal-cover`组件替换静态封面展示，呈现旗帜、课程目标标题、当前课程最终成果和原成果清单。清单通过组件插槽放进卡片，保留16px与旗帜图标，多行文字与图标顶部对齐；移除外部outcome-section空壳，卡片与大纲保持间距。完整免费网站读取数据库final_outcome与completion_criteria，其他旧目录使用已有公开outcome与deliverables；不生成课程内容，卡片、通用课程页和播放器保留上传封面。

布局、字号、自动换行与手机间距放在base，外观在components，复用已有颜色、边框、阴影和字重Token。按用户最终要求移除对话角色、收获气泡及全部播放逻辑，目标完整静态展示，不加载额外JS或动画，也不增加阅读区内部滚动。

Series课时大纲可学习入口的右侧使用Lucide播放图标，未开放课时保留锁定图标；按既有公开访问状态选择，不检查或输出视频地址。图标为装饰并使用aria-hidden，固定尺寸及防收缩放在base，颜色沿用组件Token；编号、Z角色、完成标识和课时链接保持原结构。

## 首页任务方向卡片动效（2026-10-07）

`home-intents`沿用三个方向链接与原有结构，纯CSS实现单次错峰浮入；只在桌面精确指针下循环播放小图标轻漂浮。悬停或键盘焦点时卡片微抬、图标轻转、箭头前移；手机保留单次入场和按压反馈。动画不改变布局尺寸、不等待打字效果、不拦截点击，无JS仍可访问任务；减少动态效果时关闭动画、位移及过渡，保留键盘焦点标识。

结构与卡片序号放在base，通用反馈放在components，首页装饰动画放在variants。新增五个语义Token：`--intent-entry-stagger`、`--intent-entry-offset`、`--intent-hover-transform`、`--intent-icon-offset`、`--intent-icon-tilt`；Pop/Future均提供，新主题须实现。时长与曲线复用既有`--motion-enter`、`--motion-fast`、`--motion-idle`、`--ease-emphasis`、`--ease-default`。Pop卡片250ms入场、相邻延迟70ms、悬停上移4px并轻微倾斜，图标上下2px；Future采用300ms、60ms、3px与1px，更克制。不增加主题分支、JS或动画库，不改变分类、权限及学习进度。
