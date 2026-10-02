# AI100分 DATA_MODEL.md

> 本文定义MVP领域模型和推荐数据库结构。  
> 原则：清晰、简单、可扩展，但不提前复杂化。

课程业务约束以[`docs/COURSE_CONSTITUTION.md`](docs/COURSE_CONSTITUTION.md)为准，字段同步决策见D-023。D-031新增真实`course_series`、`lessons`、`lesson_progress`及对应Eloquent模型，当前承载三个免费完整任务。旧16个课程方向仍用`App\Support\FrontendCatalog`数组。下文的完整支付/直播等结构和课程扩展字段仍是目标，当前实际字段见3.4。

---

## 1. 核心关系

```text
User
├── Orders
├── Subscriptions
├── LessonProgress
└── SeriesPurchases（通过OrderItem推导或单独授权表）

CourseSeries
├── Lessons
└── Resources

Lesson
├── LessonProgress
└── Resources

LiveSession
└── Resources（可选）
```

---

## 2. users

D-034新增`is_admin boolean default false`，不在User公开fillable字段中。普通注册及资料修改不能设置此标志；FilamentUser只允许明确授权的管理员进入galaxy，模型Policy另行校验课程与课时操作。CLI `admin:set`仅操作已有邮箱账号且要求确认，支持撤销；不新增默认管理员、默认密码或公开权限接口。

```text
id
name
email
email_verified_at
password
status
remember_token
created_at
updated_at
```

当前账号实现（D-026）沿用Laravel初始迁移的`users`：除上述`status`外其余字段已存在；`email`唯一并在注册/登录时去空格、转小写，`password`通过模型hashed cast加密，`remember_token`用于记住登录。昵称最多50个字符，密码至少8个字符且遵守bcrypt的72字节输入限制，不存储明文密码。

`email_verified_at`保留为空，当前未接入邮箱验证；注册不授予管理、购买或订阅权限。`password_reset_tokens`为框架预留表，找回密码流程尚未启用；默认文件会话，`sessions`表仅为切换数据库会话时的框架预留。D-031免费任务进度关联当前User；旧网站第一课记录仍保存在浏览器，未自动导入账号。

`status`是未来账号停用功能的目标字段，本次不创建该字段、不模拟管理能力。未来值：

```text
active
disabled
```

可后续扩展：

```text
phone
wechat_openid
douyin_openid
avatar
```

MVP不要过早依赖第三方身份。

---

## 3. course_series

表名建议：

```text
course_series
```

字段：

```text
id                  bigint
title               varchar
slug                varchar unique
subtitle            varchar nullable
description         text nullable
category            enum('solve', 'create', 'explore') not null
user_intent         text nullable
problem_statement   text nullable
final_outcome       text nullable
completion_criteria json nullable
agent_role          json nullable
human_judgment_required json nullable
cover                varchar nullable
price                decimal(10,2) default 100.00
status               varchar
sort_order           int default 0
published_at         timestamp nullable
created_at
updated_at
```

status：

```text
draft
published
archived
```

### 3.1 宪章字段语义

| 字段 | 内容与约束 |
| --- | --- |
| `category` | 唯一主要价值类别：solve、create、explore；创建/编辑必填，不设置默认类别，也不按工具或职业自动推断 |
| `user_intent` | 用户为什么开始任务，包括待解决的问题、待创作的作品或待验证的可能 |
| `final_outcome` | 课程结束后的真实成果或有证据支持的实验结论 |
| `completion_criteria` | 可逐项核对的最终验收条件；Explore需验收真实实验与证据结论，不强制实验成功 |
| `agent_role` | Agent具体执行职责，如生成、修改、运行、部署或分析 |
| `human_judgment_required` | 人必须作出的目标、选择、风险、观察和验收判断事项；是内容清单，不是boolean |

后三个JSON字段采用有序字符串数组，Laravel实现时按`array`转换，先支持简单清单，不为本轮增加独立规则表或复杂编辑器。`problem_statement`保留为可选的问题背景，不能替代涵盖三类意图的`user_intent`；`final_outcome`复用原字段，不重复建列。

### 3.2 草稿与发布校验

- 创建或编辑时服务端检查category属于允许的三个值；正式CourseSeries不接受`build/work`、空值或多个主要类别。
- 五个内容字段的nullable仅支持草稿逐步填写；进入`published`前六项必须完整，文本非空，三个JSON清单须为非空数组且每项为非空字符串。字段结构校验之外，内容审核仍须确认真实成果、可验证标准及人的关键判断。
- Solve验收目标状态；Create验收实际成品；Explore验收实验过程、证据与明确结论，允许失败或证伪。不额外添加“实验成功才可完成”的布尔门槛。
- 标准Series最终Lesson须对应整个Series验收；Lesson进度字段表示过程完成度，不能只凭播放时长自动认定任务达成。首版可由用户对照清单明确确认，无需增加复杂作业、考试或证书模型。
- 未来权限与学习进度仍由服务端校验；category和验收内容不是购买或订阅权限依据。

### 3.3 当前示例与迁移差距

`FrontendCatalog`的`category=build/work`仍属于旧公共页面筛选。D-036已逐门审核并将16门课程以草稿导入共用模型：question→user_intent、outcome→final_outcome、deliverables→objectives；description与prerequisites组合为Markdown介绍。completion_criteria、agent_role、human_judgment_required逐门补充，category独立判断为Create 9/Solve 7，不盲目转换旧类别。封面、图标、旧公开筛选与页面数据源暂仍保留在FrontendCatalog。

D-023当时只同步字段设计；D-031新增免费任务业务表；D-036提供`courses:import-legacy`独立事务命令及只读--dry-run，仅新增不存在的课程slug，遇到同slug整门跳过，保留全部既有字段与关联记录。网站已有10课时按position/score/points导入，首课已有Prompt、步骤、代码与验收清单，其余9课保留目标与简介并标记草稿；其他15门不生成不存在的课时。无新表或迁移，导入不触发购买权限或正式发布。审核与逐门六项报告见`docs/LEGACY_COURSE_IMPORT.md`。

不要把Series永久写死为10课。

“10×10”是标准产品规则，不应该破坏未来扩展能力。

---

### 3.4 D-031当前实际落地

迁移`2026_10_01_180000_create_course_learning_tables`仅新增三张表，不回填或删除既有用户；生产须审阅后手动迁移，日常pull Hook不执行迁移。

- `course_series`：id、唯一slug、title、category（varchar，模型白名单）、user_intent、final_outcome、三个宪章JSON字符串数组、recommendation_keywords（JSON）、minutes、price（decimal10,2）、is_free（默认false）、status（默认draft）、时间戳。没有新增FreeCourse模型。D-031初版要求完整提交；D-034已支持逐步保存草稿，新增字段见第4节。正式发布校验六项定义和至少一个完整Lesson。完整免费发布要求全部Lesson published且points合计100。
- `lessons`：id、course_series_id、slug、title、position、score（累计展示分值）、points（本步验收权重）、minutes、is_free（默认false，仅代表该Lesson试看）、status、intro、goal、steps（JSON标题/正文）、prompt、可空code/code_filename、resources（JSON文件名/说明/文本）、checks（JSON验收清单）、时间戳。唯一(course_series_id,slug)，无课数硬限制。当前图文内容未建视频或独立附件表。
- `lesson_progress`：id、user_id、lesson_id、checks（JSON布尔数组）、progress_percent（0–100整数）、last_position_seconds（预留0）、completed_at、时间戳。唯一(user_id,lesson_id)，外键限制删除。ProgressService在事务中锁当前用户，幂等保存本人记录；不接受客户端user_id、分数、完成时间或会员状态。

Series.is_free开放该系列全部已发布Lesson；Lesson.is_free只开放一个付费试看步骤。CourseAccessService统一服务端检查所属关系、发布状态与免费配置，旧数组试看经兼容方法保持原规则。Free Lab读取/下载/保存额外限制完整免费系列；Paul只查询已发布且所有步骤已发布的完整免费课程，不推荐付费试看。

进度百分比=已确认清单项/全部项，只有全部明确确认才写completed_at；取消任一项撤销完成状态。系列分数=已验收published Lesson的points / 全系列points ×100，未发布步骤不获得分数，不硬编码10课。首批每Series一Lesson，score与points均100；标准10课可配置points各10、score依次10至100。

`free-lab:install`在事务中只新增不存在的三个固定slug，不覆盖人工编辑、不重置学习记录、不创建账号；重跑新增数为0。素材暂以可迁移文本保存在Lesson.resources内，由已校验权限的下载响应提供；不拼接磁盘路径。未来大文件存储与支付模型另行设计。

---

## 4. lessons

### D-034内容管理扩展（当前实现）

迁移`2026_10_02_120000_add_course_admin_and_theme_settings`只增加列与表，不改写课程、用户或进度记录：

- `course_series.description`：nullable text，Markdown课程介绍；`objectives`：nullable JSON字符串数组，课程目标清单。沿用六项宪章字段，不以目标清单代替最终验收。
- `lessons.objectives`：nullable JSON字符串数组；`content`：nullable longtext，Markdown正文；`video_url`：nullable text，校验HTTPS链接，前台提供跳转入口。`goal`仍是课时最终目标，`steps`仍是有序标题/正文数组，作为可编辑课时大纲；不新增重复outline表或字段。
- 课程大纲直接按Lesson.position/id读取标题、目标、状态与分值。后台排序服务授权、锁课程与课时、校验完整ID集合及所属关系，不允许跨课程排序。
- 草稿的旧非空文本列保存空字符串，JSON清单保存空数组，新增扩展字段可以为空；数据库不必放宽旧约束。发布需完整定义与已发布课时，最后课时score=100；完整免费还需全部课时发布且points合计100。课时结构/状态/分值变更后课程退回草稿，需重新发布。
- 课时已有LessonProgress时禁止直接修改checks/points/score，所有已有课时禁止移到另一课程；不删除或重置学习记录。后台归档通过status实现，Policy禁用硬删除。
- `theme_settings`：id、nullable theme、timestamps。只管理id=1的全站配置；null表示跟随APP_THEME，白名单来自config/themes.php，后台显式选择优先，非法值回退注册表默认。尚未迁移时前台继续用环境配置。不是用户偏好表；新增主题及Token仍通过代码登记。

下列Lesson字段列表仍包含尚未实现的长期设计，以D-031及此节当前字段为准。

```text
id
course_series_id    bigint fk
title               varchar
slug                varchar
order_no             int
score                int
summary              text nullable
learning_goal        text nullable
content              longtext nullable
video_provider       varchar nullable
video_id             varchar nullable
video_url            text nullable
duration_seconds     int nullable
is_free              boolean default false
status               varchar
published_at         timestamp nullable
created_at
updated_at
```

推荐唯一约束：

```text
(course_series_id, slug)
(course_series_id, order_no)
```

标准Series：

```text
order_no: 1..10
score: 10,20,...100
```

---

## 5. lesson_progress

```text
id
user_id              bigint fk
lesson_id             bigint fk
progress_percent      decimal(5,2) default 0
last_position_seconds int default 0
completed_at          timestamp nullable
created_at
updated_at
```

唯一约束：

```text
(user_id, lesson_id)
```

完成规则首版：用户对照Lesson的可见阶段成果与验收方式，显式确认完成；最终Lesson同时核对整个Series的`completion_criteria`。达到播放/阅读进度阈值只表示过程进度，不能单独填写`completed_at`或授予100分。

标准Series每个已验收Lesson计10分，全部阶段及最终任务验收完成才到100分；非标准Series按配置计算。Solve依据问题目标状态，Create依据真实成品，Explore依据实验与证据结论，不能将实验失败视为未完成。

D-031已对免费任务实现服务端LessonProgress与清单验收。网站第一课的旧浏览器记录仍为0或10分，与账号进度独立；旧付费Series完整验收尚未实现。

---

## 6. orders

```text
id
user_id              bigint fk
order_no              varchar unique
type                  varchar
currency              varchar default CNY
amount                decimal(10,2)
status                varchar
payment_provider      varchar nullable
payment_trade_no      varchar nullable
paid_at               timestamp nullable
cancelled_at          timestamp nullable
refunded_at           timestamp nullable
metadata              json nullable
created_at
updated_at
```

type：

```text
series
subscription
```

status：

```text
pending
paid
cancelled
failed
refunded
partial_refund
```

支付状态必须服务端可信。

---

## 7. order_items

```text
id
order_id              bigint fk
purchasable_type      varchar
purchasable_id        bigint
title_snapshot        varchar
price                 decimal(10,2)
quantity              int default 1
created_at
updated_at
```

采用polymorphic purchase：

```text
CourseSeries
SubscriptionPlan（未来）
```

MVP也可以只支持CourseSeries与Subscription。

---

## 8. entitlements（推荐）

为了避免每次访问课程都复杂推导订单，建议使用显式授权表：

```text
id
user_id
entitlement_type
entitlement_id
source_type
source_id
starts_at
expires_at nullable
status
created_at
updated_at
```

例如：

```text
entitlement_type = course_series
entitlement_id = 12

source_type = order
source_id = 1001
```

或：

```text
source_type = subscription
```

优点：

- 权限查询简单；
- 退款/撤销方便；
- 后续赠送课程方便；
- 不需要把订单逻辑耦合到播放器。

如果Codex判断首版过重，可以先不实现，但权限层必须保持可替换性。

---

## 9. subscriptions

```text
id
user_id              bigint fk
plan                  varchar
amount                decimal(10,2)
starts_at             timestamp
expires_at            timestamp
status                varchar
source_order_id       bigint nullable
created_at
updated_at
```

plan首版：

```text
basic_299
```

status：

```text
active
expired
cancelled
pending
```

有效性：

```text
status = active
AND starts_at <= now
AND expires_at > now
```

---

## 10. live_sessions

```text
id
title                 varchar
slug                  varchar unique
description           text nullable
starts_at             timestamp
ends_at               timestamp nullable
access_type            varchar
meeting_provider       varchar nullable
meeting_url            text nullable
replay_url             text nullable
status                 varchar
created_at
updated_at
```

access_type：

```text
public
subscriber
series
```

如果：

```text
series
```

建议额外关联：

```text
live_session_course_series
```

---

## 11. resources

Polymorphic：

```text
id
resourceable_type
resourceable_id
title
type
url
file_path
sort_order
metadata json nullable
created_at
updated_at
```

type：

```text
file
markdown
prompt
source_code
link
pdf
zip
dataset
```

关联对象：

```text
CourseSeries
Lesson
LiveSession
```

---

## 12. payment_events

强烈建议记录支付事件：

```text
id
provider
event_id
event_type
payload json
processed_at nullable
status
created_at
updated_at
```

目的：

- 幂等；
- 排障；
- 支付回调审计；
- 防止重复授权。

---

## 13. series_updates（可后置）

用于“课程持续更新”：

```text
id
course_series_id
version
title
description
published_at
created_at
updated_at
```

例如：

```text
v1.0 首发
v1.1 更新HTTPS流程
v1.2 增加新Agent演示
```

MVP可先使用普通Markdown字段，后续再独立表。

---

## 14. questions（后续）

问题池：

```text
id
user_id
title
description
status
vote_count
selected_for_live_at nullable
created_at
updated_at
```

status：

```text
open
selected
solved
archived
```

不属于MVP。

---

## 15. 推荐服务层

避免Controller直接堆业务：

```text
CourseAccessService
PurchaseService
SubscriptionService
ProgressService
PaymentService
LiveAccessService
```

重点：

### CourseAccessService

统一判断：

```text
is_free
purchased
subscription
admin
```

不要在多个Controller复制权限逻辑。

---

## 16. 关键约束

1. `slug`用于URL，必须唯一或Series内唯一；
2. 支付回调必须幂等；
3. 订单金额使用decimal，不使用float；
4. 密钥不存普通配置表；
5. 视频URL不要假定永久公开；
6. LessonProgress必须有唯一约束；
7. 删除Series优先软删除或归档；
8. 已产生订单的商品不要物理删除；
9. OrderItem保存title/price快照；
10. 权限永远服务端判断。

---

## 17. 数据库首版最小表

如果追求最快上线，第一阶段最少：

```text
users
course_series
lessons
lesson_progress
orders
order_items
subscriptions
live_sessions
resources
```

支付接入时增加：

```text
payment_events
```

授权复杂后再增加：

```text
entitlements
```

---

## 18. 数据模型原则

> 课程结构简单，但订单和权限必须正确。

> 产品可以迭代，支付数据不能乱。

> 不用为了未来十年提前建五十张表。
