# AI100分 DATA_MODEL.md

> 本文定义MVP领域模型和推荐数据库结构。  
> 原则：清晰、简单、可扩展，但不提前复杂化。

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

status：

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
problem_statement   text nullable
final_outcome       text nullable
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

不要把Series永久写死为10课。

“10×10”是标准产品规则，不应该破坏未来扩展能力。

---

## 4. lessons

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

完成规则首版：

```text
用户主动标记完成
或
progress_percent >= 指定阈值
```

建议先采用显式“完成”按钮，避免视频进度带来复杂边界。

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
