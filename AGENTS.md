# AI100分 AGENTS.md

> 本文件用于约束 Codex 及其他 Coding Agent 在 AI100分 项目中的开发行为。  
> 目标：让 Agent 在长期开发中保持产品方向、代码结构、文档和实际实现一致。

---

## 1. 开发前必须阅读

涉及课程创建或修改、CourseSeries、课程分类、课程命名、Series页面、首页课程入口、课程发现或推荐时，必须先阅读：

```text
docs/COURSE_CONSTITUTION.md
```

这是课程体系的最高规则。仓库中的实际路径位于`docs/`，不得因根目录不存在同名文件而跳过。实现便利、旧分类或工具选择不能覆盖宪章；修改宪章属于产品级决策，必须记录到`DECISIONS.md`。

开始任何中等或大型任务前，按以下顺序读取：

```text
PROJECT_CONTEXT.md
PRODUCT.md
MVP.md
DATA_MODEL.md
DECISIONS.md
TODO.md
```

如果某文件不存在，可以跳过，但不得因此忽略其他文件。

如果产品文档、决策文档和现有代码存在冲突：

1. 先识别冲突；
2. 不要静默猜测；
3. 优先遵循最新明确的产品决策；
4. 检查 `DECISIONS.md` 是否已有覆盖旧决策的新决策；
5. 必要时更新文档；
6. 不得擅自扩大产品范围。

课程价值、分类、命名、首页课程入口和完成标准发生冲突时，以课程宪章为准。`PRODUCT.md`负责产品范围，`DATA_MODEL.md`负责字段结构；发现代码尚未符合宪章时，应明确记录差距与迁移任务，不把待实现规则写成已实现功能。

---

## 2. 项目核心目标

AI100分不是传统网课平台。

项目核心闭环：

```text
访问
→ 试看
→ 注册
→ 购买 / 订阅
→ 学习
→ 完成100分
→ 得到可验收的真实结果（解决问题 / 完成作品 / 得到实验结论）
```

任何开发任务都应优先服务这一闭环。

核心产品原则：

```text
10分钟，解决一步。
100分钟，解决一个完整问题。
100分，代表真正完成。
```

产品最终标准：

> 不是看完了，而是做成了。

课程只围绕Solve、Create、Explore三类价值组织。沿用“10分钟，解决一步；100分钟，解决一个完整问题”的品牌表达时，不得据此排除作品与探索类课程。

---

## 3. 当前技术栈

默认技术栈：

```text
Laravel
PHP
MySQL
Blade
Tailwind CSS
Nginx
Redis（按需）
```

优先使用 Laravel 原生能力。

未经明确需求，不主动引入：

```text
Vue
React
Next.js
微服务
Kafka
Kubernetes
CQRS
Event Sourcing
复杂DDD框架
复杂前后端分离架构
```

只有在以下情况下才允许引入额外复杂度：

- 当前任务明确要求；
- 已经出现真实技术瓶颈；
- 对产品价值有明确收益；
- 在 `DECISIONS.md` 中记录原因。

---

## 4. 开发原则

### 4.1 简单优先

优先：

```text
清晰
可读
可测试
Laravel惯用写法
易维护
```

避免：

```text
炫技
过度抽象
过早泛化
为未来不存在的需求设计
```

### 4.2 Controller 保持薄

Controller 主要负责：

- Request；
- Validation；
- 调用 Service；
- 返回 View / Response。

核心业务逻辑不要大量写在 Controller。

### 4.3 业务逻辑集中

建议集中到：

```text
CourseAccessService
PurchaseService
SubscriptionService
PaymentService
ProgressService
LiveAccessService
```

避免同一权限、支付、进度逻辑复制到多个位置。

---

## 5. 产品边界

MVP 当前重点：

```text
内容骨架
用户登录
课程访问
学习进度
100元单Series购买
299元基础订阅
直播入口
录播
课程资料
```

当前不主动开发：

```text
社区
私信
复杂评论
复杂作业
证书
排行榜
积分商城
分销
多讲师结算
企业组织账号
复杂CRM
AI助教
自研直播
App
复杂优惠券
```

新增大功能前必须先判断：

> 不做这个功能，当前付费学习闭环是否仍然可以成立？

如果可以：

> 默认后置。

---

## 6. CourseSeries 规则

标准 Series：

```text
1 个真实任务（问题 / 作品 / 探索）
= 10 个 Lesson
= 每个约 10 分钟
= 总计约 100 分钟
```

标准分值：

```text
10
20
30
40
50
60
70
80
90
100
```

但数据库不能硬编码：

```text
Series 必须永远只有10课
```

“10×10”是标准产品格式，不应阻塞未来特殊系列。

### 6.1 课程宪章约束

- 每个Series只选一个主要`category`：`solve`、`create`、`explore`。根据用户意图和最终成果判断，不按工具、技术栈或用户职业分类；网站、软件等同一题材可因目标不同归入不同类别。
- 内容定义必须包含`category`、`user_intent`、`final_outcome`、`completion_criteria`、`agent_role`、`human_judgment_required`。具体类型与草稿/发布校验见`DATA_MODEL.md`；后台创建或编辑时category必填，正式发布前六项必须完整。
- Solve：真实问题已解决或达到事先约定的目标状态，用户能验证。
- Create：真实成品存在，至少可使用、运行、打开、发布、展示或交付一种。
- Explore：完成真实实验，得到明确、可解释、有证据支持的结论。实验失败或证伪也可完成，不承诺必然成功。
- 课程命名描述真实结果，如“100分钟做一个批量文件处理工具”。不得以“学Python / Laravel / Codex / Excel / 某模型”等工具知识作为课程终点。
- 人负责目标、判断、选择、风险、观察与验收；Agent负责执行、生成、修改、运行、部署、分析和重复劳动。`human_judgment_required`描述具体判断事项，不能简化为一个布尔开关。

### 6.2 创建前检查与结束报告

创建或修改Series前逐项检查：主要类别是否唯一；最终成果是否真实；完成标准是否可验证；AI/Agent是否显著降低门槛、时间或成本；是否保留人的关键判断；是否错误地把工具当成目标；是否能拆成明确阶段成果；约100分钟是否能形成有意义的闭环。把工具当目标或无法归类时，先重新设计课程。

课程相关任务结束时报告：Constitution category、User intent、Final outcome、Completion criteria、Agent role、Human judgment。仅同步规范或架构、没有创建或修改具体Series时，说明适用范围与尚未实现项，不虚构某门课程的分类或成果。

---

## 7. Lesson 规则

Lesson 是核心学习单位。

每个 Lesson 应至少支持：

- 标题；
- 所属 Series；
- 分值；
- 视频；
- 简介；
- 学习目标；
- 正文；
- Prompt；
- 代码；
- 资源附件；
- 验收方式；
- 学习进度。

首版不要开发复杂编辑器。

优先：

```text
Markdown
或简单富文本
```

---

## 8. 用户权限

课程访问权限必须服务端判断。

禁止依赖：

```text
前端隐藏按钮
前端返回的“已购买”
前端传入的会员状态
```

建议统一通过：

```text
CourseAccessService
```

判断：

```text
is_free
purchased
subscription
admin
```

访问规则：

```text
if lesson.is_free:
    allow

elif user purchased the series:
    allow

elif user has active subscription:
    allow

else:
    deny
```

---

## 9. 支付规则

真实支付未接入前：

统一通过：

```text
PaymentGateway
```

开发环境允许：

```text
FakePaymentGateway
```

真实支付必须遵循：

1. 服务端创建订单；
2. 服务端生成支付参数；
3. 服务端接收支付回调；
4. 校验签名；
5. 幂等处理；
6. 更新订单；
7. 发放权限；
8. 记录支付事件。

禁止：

```text
前端点击“支付成功”
→ 直接授权课程
```

订单金额使用：

```text
decimal
```

不要使用：

```text
float
```

---

## 10. 学习进度

首版优先支持：

```text
progress_percent
last_position_seconds
completed_at
```

Series 完成度：

```text
完成1个标准Lesson = 10分
完成10个标准Lesson = 100分
```

100分代表：

> 完成度。

每个标准Lesson的10分表示一个可见步骤已经验收。最终步骤必须按Series的`completion_criteria`验收整个任务，100分不能仅由观看时长、知识问答或点击“看完”获得。Explore按实验与结论验收，不能因实验失败而禁止完成。非标准Series仍按课程配置计算，不硬编码永远10课。

不代表：

- 考试成绩；
- 职业资格；
- 技能认证。

---

## 11. UI 与产品语言

页面设计统一遵循：

> 简洁、明了、体验流畅。

- 简洁：布局和视觉保持克制，优先展示当前任务所需的信息与操作。
- 明了：信息层级清晰，文案直接，让用户容易识别要解决的问题、当前进度和下一步。
- 体验流畅：导航与操作保持一致，减少不必要的跳转和重复输入，及时反馈加载、成功和失败状态。
- 桌面端和移动端都应易读、易操作，避免内容遮挡、布局跳动和操作中断。

AI100分 的语言优先使用：

```text
问题
解决
创作
探索
做成
完成
验收
10分
100分
```

避免传统培训营销语言：

```text
大师课
赋能
秒变高手
零基础月入
高薪秘籍
权威认证
```

Series 页面必须优先回答：

```text
你属于哪条路径：解决问题、创作作品还是探索可能？
你想完成什么真实任务？
100分钟后能得到什么？
怎么验收才算做成？
现在完成多少分？
```

首页优先展示：

```text
你今天想做什么？
解决一个问题 / 创作一个作品 / 探索一个可能
```

三个一级入口表达用户意图，不按工具或技术栈组织；进入路径后再展示具体任务、最终成果和下一步。推荐应依据主要category、用户意图与可验收结果，不能只按热门工具推荐。

不要把首页做成普通课程商城。

---

## 12. 安全规则

必须遵守：

1. 所有课程权限必须服务端校验；
2. 支付结果必须由服务端确认；
3. 管理后台必须认证；
4. 文件下载也要检查访问权限；
5. 密钥、Token、Secret 禁止提交 Git；
6. 密码使用 Laravel 标准 Hash；
7. 高风险操作必须显式确认；
8. 删除业务数据优先软删除或归档；
9. 数据库迁移涉及破坏性变更时必须谨慎；
10. 生产环境不可自动执行不可逆操作。

---

## 13. 测试要求

核心业务必须优先覆盖测试。

### 访问权限

至少测试：

- 免费 Lesson 可访问；
- 未购买付费 Lesson 不可访问；
- 已购买 Series 可访问；
- 有效订阅可访问；
- 订阅过期不可访问。

### 支付

至少测试：

- pending 不授权；
- paid 成功授权；
- 重复回调不重复授权；
- 金额异常不授权；
- 退款后状态正确。

### 订阅

至少测试：

- 开通；
- 生效；
- 到期；
- 续费；
- 取消。

### 学习进度

至少测试：

- Lesson 完成；
- Series 分数计算；
- 重复提交幂等。

---

# 14. Documentation Sync Policy

文档是项目的一部分，不是一次性说明文件。

每次完成任务后，Agent 必须检查本次变更是否影响以下文档：

```text
TODO.md
DECISIONS.md
DATA_MODEL.md
MVP.md
PRODUCT.md
PROJECT_CONTEXT.md
```

---

## 14.1 TODO.md

更新频率：

> 高。

以下情况必须更新：

- 任务完成；
- 新增任务；
- 任务取消；
- 优先级变化；
- Sprint 状态变化；
- 开发中发现新的待办。

例如：

```text
- [ ] Series CRUD
```

完成后：

```text
- [x] Series CRUD
```

如果开发中发现新问题：

```text
- [ ] Lesson增加排序能力
```

Agent 可以主动维护 `TODO.md`。

---

## 14.2 DECISIONS.md

更新频率：

> 高，但只记录重要决策。

以下情况需要新增决策：

- 产品规则变化；
- 技术架构选择；
- 支付方案变化；
- 权限模型变化；
- 数据模型重大取舍；
- 第三方服务选择；
- 价格或订阅结构变化；
- 产品边界变化。

原则：

> 尽量追加，不随意重写历史。

旧决策被推翻时：

```text
Status: Superseded
Superseded by: D-XXX
```

然后新增新决策。

不要删除历史决策，以保留项目演进过程。

---

## 14.3 DATA_MODEL.md

更新频率：

> 中。

以下情况需要检查并更新：

- 新增核心模型；
- 删除核心模型；
- 表关系变化；
- 关键字段变化；
- 权限数据结构变化；
- 支付数据结构变化；
- Series / Lesson 核心规则变化。

普通实现细节或无业务意义的小字段，不需要为了形式强行更新。

---

## 14.4 MVP.md

更新频率：

> 中。

只有当 MVP 范围发生变化时更新。

例如：

```text
新增必须上线的功能
删除MVP功能
某功能从P1升为P0
某功能明确推迟
```

不要因为普通代码重构修改 `MVP.md`。

---

## 14.5 PRODUCT.md

更新频率：

> 低。

只有出现产品级变化时更新：

- 产品定位；
- 定价；
- 会员体系；
- 课程结构；
- 商业模式；
- 首发内容；
- 用户群变化；
- 产品核心流程变化。

`PRODUCT.md` 属于项目核心产品定义。

不要因为普通开发任务频繁修改。

如果 Agent 修改 `PRODUCT.md`：

> 必须在任务总结中明确说明修改原因和内容。

---

## 14.6 PROJECT_CONTEXT.md

更新频率：

> 很低。

只有以下情况才更新：

- 项目核心方向变化；
- 公司主体变化；
- 核心技术路线变化；
- PDSI / CSI / 其他项目关系变化；
- 项目背景发生长期变化。

不要把普通开发记录写入 `PROJECT_CONTEXT.md`。

如果 Agent 修改：

> 必须明确报告。

---

## 14.7 文档同步判断原则

每次任务完成后，依次检查：

```text
代码发生了什么变化？
↓
这是实现细节还是产品变化？
↓
影响哪个文档？
↓
只更新真正受影响的文档
```

不要为了“文档同步”而无意义修改所有文件。

---

## 14.8 任务结束时必须报告

每次完成中等或大型任务后，总结必须包含：

```text
Code changes
Tests
Migrations
Environment variables
Updated docs
Docs checked but unchanged
Open issues
```

示例：

```text
Updated docs:
- TODO.md
- DATA_MODEL.md
- DECISIONS.md

Docs checked but unchanged:
- PRODUCT.md
- MVP.md
- PROJECT_CONTEXT.md
```

---

## 15. Git 与文档一致性

如果存在 Git Hook / CI 检查，可以提醒：

```text
database/migrations/发生重大变化
但DATA_MODEL.md未更新
```

或：

```text
核心业务完成
但TODO.md未同步
```

但自动化检查默认：

> 只提醒，不自动重写产品文档。

特别是：

```text
PRODUCT.md
PROJECT_CONTEXT.md
```

禁止由脚本自动生成或整体覆盖。

---

## 16. DECISIONS 编号规则

建议：

```text
D-001
D-002
D-003
...
```

每条至少包括：

```text
标题
日期
状态
决策
原因
影响
```

状态：

```text
Proposed
Accepted
Superseded
Rejected
```

---

## 17. 开发任务结束流程

每次任务结束前，按以下顺序：

```text
1. 检查功能是否完成
2. 运行测试
3. 检查权限
4. 检查迁移
5. 检查环境变量
6. 检查TODO
7. 检查DECISIONS
8. 检查DATA_MODEL
9. 检查MVP
10. 检查PRODUCT
11. 检查PROJECT_CONTEXT
12. 汇报结果
```

---

## 18. 不允许的行为

未经明确要求，不要：

- 重写整个项目；
- 更换框架；
- 大规模重构稳定代码；
- 删除已有业务逻辑；
- 修改真实生产数据；
- 自动创建真实云资源；
- 自动执行危险数据库操作；
- 把密钥写入仓库；
- 增加MVP外大型功能；
- 自动重写所有项目文档；
- 为了“保持同步”制造无意义文档Diff。

---

## 19. 遇到不确定性

### 低风险实现细节

采用：

```text
最简单
Laravel惯用
易维护
```

的方案。

### 高影响问题

如果涉及：

```text
产品边界
价格
课程结构
权限
支付
用户隐私
公司主体
备案
数据删除
外部API
```

不要自行假设。

应：

1. 保持当前行为；
2. 记录到 `TODO.md` 或 `DECISIONS.md`；
3. 明确标记待决策。

---

## 20. 当前优先级

```text
P0
内容骨架
用户登录
课程访问
学习进度
100元Series购买闭环

P1
299订阅
直播入口
资料下载
真实支付

P2
问题池
课程更新日志
更多登录方式
SEO增强

P3
社区
多讲师
企业版
AI助教
复杂增长系统
```

---

## 21. Agent 自检问题

任何中等或大型改动完成前，至少问自己：

```text
这是否属于当前MVP？
是否引入了不必要复杂度？
课程权限是否服务端校验？
支付是否可信？
是否需要迁移？
是否需要测试？
是否需要更新TODO？
是否形成了新的决策？
是否改变数据模型？
是否改变MVP？
是否改变产品？
是否改变项目背景？
```

---

## 22. 最终原则

AI100分不是为了让用户“学会更多知识”。

而是让用户：

> **知道第一步从哪里开始，并最终把一个真实问题做成。**

开发也遵循同样原则：

> **不要一次把所有事情做完，先把当前这一步做成。**

---

## 23. Theme System 开发约束

- 前台视觉变更先阅读 `docs/THEME_SYSTEM.md`；默认主题为 `pop`，通过 `APP_THEME` 全站选择，非法值回退注册表默认主题。
- 布局、响应式和交互结构放在 `resources/css/base.css`；通用外观放在 `components.css`；页面装饰浓度放在 `variants.css`；具体视觉值放在 `resources/css/themes/{theme}.css`。
- 通用组件使用语义名称与 Token。不要新增 Pop 命名组件，不在业务模板写固定配色、边框、阴影或大量主题判断，也不要为每个主题复制页面。
- 新主题登记到 `config/themes.php` 和 Vite 入口，实现现有 Token 契约；主题资源放在 `resources/themes/{theme}`。页面仅加载当前主题样式。
- UFO 保持角色、状态与进度语义一致；外观和动画参数由主题控制。主题不得改变课程权限、价格、验收或进度计算。
- D-034按用户明确要求增加Filament后台全站主题配置，`theme_settings`单例覆盖APP_THEME；“跟随APP_THEME”恢复环境配置，非法值回退注册表默认主题。仅管理员可写，选项来自注册表，不接受任意CSS、文件路径或代码。仍不增加用户主题偏好；Future 当前为机制验证骨架，完整设计后置。
- 切换主题时检查首页、Series、Lesson、问题池、账号、搜索/弹窗、键盘聚焦、手机布局和减少动态效果；阅读页控制装饰浓度。

---

## 24. Git pull 发布约束

- 当前预览站采用D-025：开发机运行`npm run build`，把`public/build`与源码一起提交、推送，服务器通过`git pull`更新；不要再单独上传构建压缩包。
- D-029明确日常流程：本地完成检查后`git push origin main`及`git push gitee HEAD:master`，服务器在`/var/www/ai100fen`执行`git pull`。服务器当前main跟踪`gitee/master`，保留GitHub origin；日常不使用手工fetch/merge或上传文件代替pull。push完成不代表已上线，须确认服务器pull及Hook成功并验证页面。
- 涉及前台模板、CSS、JS、主题资源、Vite或npm依赖的变更，发布前必须重新构建并提交最新manifest及资源，运行适用测试。不得提交`.env`、`vendor`、`node_modules`或`public/hot`。
- 只有配置`ai100fen.deploy=true`的生产检出执行post-merge上线Hook；其他本地检出保持关闭。一次性配置、日常更新及错误重试见`deploy/README.md`。
- D-033规定服务器项目由`dante:dante`管理，`www-data`加入`dante`组；`/var/www`保留`root:root`。日常SSH、git pull、Composer和Artisan使用`dante`，Git本地配置`ai100fen.deployUser=dante`；root仅用于一次性系统配置。源码对Web进程只读，只有`storage`与`bootstrap/cache`共享可写，默认ACL维持新文件权限；不得使用777或给予部署用户任意sudo权限。
- 上线脚本只校验构建产物、安装锁定PHP依赖、刷新缓存、同步公开提交记录快照及通过受限sudo重载PHP FPM；运行目录权限在一次性配置中设置，常规Hook不再依赖root修复。不得自动生成密钥、覆盖环境变量、执行数据库迁移或修改真实业务数据。
- 提交记录遵循D-027：使用`php artisan project:sync-history`从当前Git HEAD生成`storage/app/private/commit-history.json`，生产git pull Hook自动执行，本地完成提交后手动同步。页面只读取快照，禁止依据请求参数执行Git或公开作者邮箱、完整提交正文和差异；缺失快照明确显示未同步。
- Git更新与Hook执行不是原子发布，报错须明确报告并处理；不能将Git已更新当作部署成功，也不得用强制重置清除服务器本地修改。

## 25. 测试站爬虫与SEO隔离

- D-030仅作用于`ai100.aicsi.cn`测试域名：Nginx精确location提供`deploy/robots-testing.txt`的Allow规则，HTTP/HTTPS统一`X-Robots-Tag: noindex, nofollow`，优先复用已有响应头，避免重复。
- 该预览站`APP_ENV=production`是运行安全设置，不代表这套robots策略适用于所有生产域名。不要为测试站改写共享`public/robots.txt`、应用SEO、业务权限、后台认证或必要安全防护。
- Nginx系统配置变更须单独备份、限定测试站点、通过`nginx -t`后reload；常规git pull Hook继续不修改`/etc/nginx`。验证及Cloudflare人工清单见`deploy/CRAWLER_POLICY.md`。

## 26. Filament后台（D-034）

- 后台路径`/galaxy`，User实现FilamentUser，仅`is_admin=true`可进入，开发环境也不能放开普通账号。注册、个人资料及其他公开接口不得设置is_admin；使用`admin:set`对明确指定的已有账号确认授权或撤销，禁止公开注册管理员或提供默认密码。
- CourseSeries/Lesson Policy在服务端检查管理员权限；自定义排序和Theme保存也必须授权。首版通过状态归档保留内容与学习记录，不提供硬删除或批量删除。
- 课程大纲来自关联Lesson的position/title/goal，不另存重复JSON。课程六项定义及课时目标/步骤/Prompt/验收在发布前校验，不能绕过模型校验；课时结构、状态或分值调整使课程回到草稿，需重新审核发布。
- 已有进度的Lesson不能直接改验收项、分值或所属课程；不要清空真实学习记录来绕过限制。版本化内容迁移另行设计。
- 本地新增非破坏性迁移可按需求执行；线上迁移与管理员授权必须由运维独立审阅和执行，常规git pull Hook继续不执行迁移或权限授权。


## 27. 课程关系（D-039）

- 关系只连接既有CourseSeries，不复制内容或新增重复category/outcome，会员权益不作课程类型。
- 写入统一使用CourseRelationService，校验管理员更新源课程及关系归属，拒绝自身、重复、无效类型和前置循环。固定课程行锁串行化图写入；不能绕过服务直接写关系。
- 前置只作建议，不强制锁课；recommended/next为单向链接，不自动反向生成。关系内sort_order独立于首页排序和课时position。
- 移除关系须确认，只删除连接；不得删除课程或学习记录。公开源/目标必须符合CourseRelationPresenter.publicCourses过滤，草稿/归档仅管理员预览。
- `/courses/{series}`为公开元信息与已发布大纲，不得渲染付费课时正文、Prompt、代码或附件。原FrontendCatalog目录与试看权限维持；level、标签、Z路径推荐和自动规划后置。

## 28. 问题黑洞与私人收集（D-041）

- 问题池黑洞与探索UFO分别复用独立组件/Token，不得为更换导航删除全站UFO。问题页用内联Z，其他页保留悬浮向导。
- 当前Z工作台是明确标注的规则引导，聊天/未确认草稿仅在页面内存；不得宣称已连接LLM或已有聊天记录。
- 用户选择仅登录后私人收集；questions只保存确认的任务卡，不保存聊天全文、不公开记录。所有查看按当前用户查询，即使管理员也不能通过公开接口读取他人私人问题。
- 提交沿用Session认证、CSRF、限流和字段校验，QuestionSubmissionService以用户行锁和UUID保证幂等；不得用客户端user_id或visibility授权，冲突不能覆盖原记录。
- 吸入仅在有效服务端成功回执后作用于装饰副本，原卡保留；失败/超时保留数据且不显示成功。减少动态效果直接静态反馈，主题不改变私人/公开边界。
- 公开发布、审核、删除/保留期限、模型与聊天持久化另行决策；复用现有questions与推荐服务，不新建重复问题池或聊天后端。
