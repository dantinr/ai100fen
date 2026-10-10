# AI100分 DATA_MODEL.md

> 本文定义MVP领域模型和推荐数据库结构。  
> 原则：清晰、简单、可扩展，但不提前复杂化。

课程业务约束以[`docs/COURSE_CONSTITUTION.md`](docs/COURSE_CONSTITUTION.md)为准，字段同步决策见D-023。D-031新增真实`course_series`、`lessons`、`lesson_progress`及对应Eloquent模型；D-044新增公开直播`live_sessions`。D-057前台课程全部读取数据库，`FrontendCatalog`只供独立历史导入使用。下文的支付、受限直播等结构和课程扩展字段仍是目标，当前实际字段见3.4及第10节。

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

`email_verified_at`保留为空，当前未接入邮箱验证；注册不授予管理、购买或订阅权限。`password_reset_tokens`为框架预留表，找回密码流程尚未启用；默认文件会话，`sessions`表仅为切换数据库会话时的框架预留。D-031免费任务进度关联当前User；旧网站试看记录保留在浏览器，D-057起不展示且不自动导入账号。

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
sort_order           unsigned int default 1000
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

### 3.3 历史导入与展示演进（D-057替代静态展示）

D-057当前目录使用CourseCatalog读取已发布、未删除且至少有一个已发布课时的CourseSeries。首页、课程列表、搜索、介绍、大纲和学习入口均使用数据库；草稿、归档及回收站记录不公开，新slug无需登记静态数组。排序按sort_order/id升序，标签来自recommendation_keywords，空值仅以主要category语义作展示回退；封面及任务定义来自当前记录。公开大纲只取已发布课时的标题、目标、时长和有效免费标志，不输出课时正文、Prompt、代码、视频或资料。下面的旧目录叠加规则是历史记录，已由本决策替代。

`FrontendCatalog`的`category=build/work`仍属于旧公共页面筛选。D-036已逐门审核并将16门课程以草稿导入共用模型：question→user_intent、outcome→final_outcome、deliverables→objectives；description与prerequisites组合为Markdown介绍。completion_criteria、agent_role、human_judgment_required逐门补充，category独立判断为Create 9/Solve 7，不盲目转换旧类别。图标、旧公开筛选与主要页面内容仍来自FrontendCatalog；按slug读取数据库的排序、D-046封面和课程标签等展示元信息。

D-023当时只同步字段设计；D-031新增免费任务业务表；D-036提供`courses:import-legacy`独立事务命令及只读--dry-run，仅新增不存在的课程slug，遇到同slug整门跳过，保留全部既有字段与关联记录。网站10课时为D-036历史导入状态。D-049后首次导入网站采用五节独立正文/Prompt/验收，各2分钟/20分，课程仍为草稿、首课发布/其余草稿；已有slug不覆盖。其他15门不生成不存在的课时。导入不触发购买权限或正式发布。审核与逐门六项报告见`docs/LEGACY_COURSE_IMPORT.md`。

D-046新增`course_series.cover`可空字符串，仅存`public`磁盘中`course-covers/`下的图片路径。管理员上传JPG/PNG/WebP（最多2 MB），统一按16:9展示；无图或文件缺失时沿用既有海报/图标。旧目录按slug读取封面、排序和课程标签，不读取数据库草稿正文或改变访问权限。图片文件存于`storage/app/public`，不提交Git；部署环境需建立`public/storage`链接。新增迁移只加可空字段，生产须审阅后独立执行。

2026-10-07课程标签复用既有`recommendation_keywords`（JSON字符串数组），后台名称为“课程标签 / 关键词”，不新增标签表或字段。首页和课程列表卡片底部显示其内容，列表搜索也包含这些关键词；为空或课程未入库时回退到原目录的`tag`。它属于公开展示元信息：已在策划目录展示的课程即使数据库记录仍为草稿，保存的关键词也立即展示；不会自动公开新草稿课程或其正文。标签不替代Solve/Create/Explore主要类别，不改变发布状态、权限或验收；Z免费任务推荐继续使用同一字段。

不要把Series永久写死为10课。

“10×10”是标准产品规则，不应该破坏未来扩展能力。

---

### 3.4 D-031当前实际落地

迁移`2026_10_01_180000_create_course_learning_tables`仅新增三张表，不回填或删除既有用户；生产须审阅后手动迁移，日常pull Hook不执行迁移。

D-038新增`course_series.sort_order`（unsigned integer、默认1000、有索引），模型及后台校验0–999999整数。D-057首页、目录及完整免费实验室均按数据库sort_order/id升序，仅显示符合公开条件的课程。只改展示排序不改变状态、Lesson.position、价格、权限或验收；既有迁移保留，日常Hook不迁移。

D-045拖动排序复用同一`sort_order`列，无新表或字段。管理员提交完整课程ID顺序，服务端在事务中锁定课程、校验ID集合及逐门更新权限，再将位置重排为1至N；筛选或搜索后的部分列表不能保存为全局顺序。原数值设置仍可用于精确调整。

- `course_series`：id、唯一slug、title、category（varchar，模型白名单）、user_intent、final_outcome、三个宪章JSON字符串数组、recommendation_keywords（JSON）、minutes、price（decimal10,2）、is_free（默认false）、status（默认draft）、时间戳。没有新增FreeCourse模型。D-031初版要求完整提交；D-034已支持逐步保存草稿，新增字段见第4节。D-047正式发布校验六项定义和至少一个已发布Lesson，免费与付费均支持逐课发布。D-057 Free Lab只额外要求全部当前未归档、未删除Lesson published，不再检查points合计或最终score。
- `lessons`：id、course_series_id、slug、title、position、score与points（历史兼容列，D-057起不再参与显示、计算、发布或访问判断，后台移除编辑与列表列）、minutes、is_free（默认false，仅代表付费课程单课试看）、status、intro、goal、steps（JSON标题/正文）、prompt、可空code/code_filename、resources（JSON文件名/说明/文本）、checks（JSON验收清单）、时间戳。唯一(course_series_id,slug)，无课数硬限制。视频及播放器封面扩展见第4节，没有独立附件表。
- `lesson_progress`：id、user_id、lesson_id、checks（JSON布尔数组）、progress_percent（0–100整数）、last_position_seconds（预留0）、completed_at、时间戳。唯一(user_id,lesson_id)，外键限制删除。ProgressService在事务中锁当前用户，幂等保存本人记录；不接受客户端user_id、分数、完成时间或会员状态。

Series.is_free开放该系列全部已发布Lesson，不要求修改Lesson.is_free；Lesson.is_free只用于付费课程的单课试看。CourseAccessService读取当前数据库设置并统一服务端检查所属关系、软删除与发布状态。`/series/{slug}/lessons/{lessonSlug}`复用同一学习/进度/下载组件，整门免费可逐课发布学习；`/lab`及Z免费任务推荐额外限制整门免费且所有当前步骤已发布。`/courses/{series}`只作公开介绍的规范地址跳转。不回退旧静态权限或内容。

课时验收百分比=已确认清单项/全部项（整数向下取整），全部明确确认才写completed_at；取消任一项撤销完成状态。D-057课程完成度=已验收published Lesson数 / 当前未归档、未删除Lesson数 ×100%，由ProgressService.seriesPercent实时计算，不新增存储列。显示一位小数，零课时为0%，只有全部当前课时验收为100%，避免四舍五入提前完成。草稿计入分母但不计完成；归档、删除排除当前计算且保留历史。课时增减不修改LessonProgress或旧points/score。账号课程进度只读取本人记录，旧浏览器分值不导入或展示。保存先锁用户、课程及当前课时，再校验权限和当前验收项，拒绝过时清单；不接受客户端分值、完成时间或用户ID。最后一节仍按课程完成标准验收整个任务。进度JSON返回series_percent，不再返回series_score。

`free-lab:install`在事务中只新增不存在的三个固定slug，不覆盖人工编辑、不重置学习记录、不创建账号；重跑新增数为0。素材暂以可迁移文本保存在Lesson.resources内，由已校验权限的下载响应提供；不拼接磁盘路径。未来大文件存储与支付模型另行设计。

---

## 4. lessons

### D-034内容管理扩展（当前实现）

迁移`2026_10_02_120000_add_course_admin_and_theme_settings`只增加列与表，不改写课程、用户或进度记录：

- `course_series.description`：nullable text，Markdown课程简介；`objectives`：nullable JSON字符串数组，阶段成果清单。D-059后台“课程目标”复用`final_outcome`，不新增重复目标字段；沿用六项宪章字段，不以阶段清单代替最终验收。
- `lessons.objectives`：nullable JSON字符串数组；`content`：nullable longtext，Markdown正文；`video_url`：nullable text，校验HTTPS链接，D-053在可访问课时页通过Aliplayer内嵌播放（HLS/MP4等直连媒体地址），公开介绍页不输出播放地址。无新增视频字段，PlayAuth/签名及DRM未实现；本地演示片源不入库、不代替真实录播。`goal`仍是课时最终目标，`steps`仍是有序标题/正文数组，作为可编辑课时大纲；不新增重复outline表或字段。
- 课程大纲直接按未归档Lesson.position/id读取标题、目标、状态与当前完成占比；后台课时计数和排序集合同样排除归档课。后台排序服务授权、锁课程与课时、校验完整未归档ID集合及所属关系，不允许跨课程排序。主课时列表默认未归档，可切换归档筛选；管理员明确指定归档课地址仍可只读预览。
- `lessons.video_poster`：nullable varchar(255)，由非破坏性迁移`2026_10_05_010000_add_lesson_video_poster`新增。保存public磁盘`lesson-video-posters/`下JPG/PNG/WebP图片路径；后台单图上传、16:9裁剪、最大2 MB，模型拒绝外部URL、路径穿越或其他目录/类型。可访问课时及管理员预览优先使用该图，未设置或文件缺失时回退课程封面，本地演示最后回退默认演示图。移除、更换或最终删除课程均保留原上传文件，不修改课时发布状态、验收或进度；封面是公开展示图片，不作为受保护的视频/资料附件。
- 草稿的旧非空文本列保存空字符串，JSON清单保存空数组，新增扩展字段可以为空；数据库不必放宽旧约束。按D-047，课程发布需完整定义与至少一个已发布课时；D-057取消全部分值门槛。单课发布仍校验目标、步骤、Prompt与验收。课时结构/状态变更后课程退回草稿，需重新发布。
- 课时已有LessonProgress时禁止直接修改checks，所有已有课时禁止移到另一课程；不删除或重置学习记录。旧points/score不再有业务效力。后台归档通过status实现。D-050课程删除使用独立deleted_at回收站；D-055增加课时批量软删除与恢复，任何学习记录阻止该批删除，不提供课时最终删除。D-060允许有学习记录的回收站课程经管理员确认整体最终删除，保留验收数据及名称快照；课时独立删除保护不变。
- `theme_settings`：id、nullable theme、timestamps。只管理id=1的全站配置；null表示跟随APP_THEME，白名单来自config/themes.php，后台显式选择优先，非法值回退注册表默认。尚未迁移时前台继续用环境配置。不是用户偏好表；新增主题及Token仍通过代码登记。

### D-059课程编辑与课前准备（当前实现）

迁移`2026_10_10_120000_add_course_series_prerequisites`只新增`course_series.prerequisites`（nullable JSON有序字符串数组），不迁移、解析或改写旧简介、课程关系与学习记录。模型按array转换，拒绝非字符串或空白事项；空值展示为空清单。后台“课前准备”存工具、账号、文件等准备事项，公开CourseCatalog读取该字段并复用原准备区域，不回退静态内容。

八项常用内容对应title、cover、description、final_outcome、recommendation_keywords、prerequisites，以及course_relations的prerequisite/next。前置和后续在表单内编排，保留关联ID、理由与排序，写入仍经CourseRelationService；没有新增重复关系列，recommended与其他课程不受影响。课程属性及两个关系类型整单事务保存，共享图锁优先；锁定的服务器快照拒绝过时表单覆盖外部关联修改。取消移除不改变数据，确认移除后仍须保存；只删连接，不删课程或进度。主要类别、状态、定价、展示排序及宪章定义移入可折叠区域，发布校验不变。

### D-050课程回收站（当前实现）

迁移`2026_10_04_010000_add_course_recycle_bin`增加可空且有索引的`course_series.deleted_at`，所有既有记录初始为null；Lesson和LessonProgress结构不变。CourseSeries使用SoftDeletes，正常查询与路由绑定排除回收站课程，后台回收站显式onlyTrashed。软删除不改写课时、记录或关系；恢复统一为draft，需重新发布。

D-060替代旧学习记录禁令：最终删除事务锁课程与全部课时（含归档及D-055软删除课时），校验当前管理员及回收站状态；先为全部LessonProgress保存最小名称/ID快照并解绑lesson_id，不删除验收记录。D-058按用户要求取消slug输入，单门及批量都由确认弹窗触发；批量只处理明确选择的ID，检查选择完整性并逐课授权，任一失败整批回滚。条件满足后经CourseRelationService清理出入向连接、永久删除全部课时，最后forceDelete课程；条件失败保持全部记录。课时外键仍restrictOnDelete，不改为自动级联；D-060迁移仅放宽为nullable，以便在删除服务保存快照后明确解绑。学习记录不能因删除课程被连带删除，上传封面文件不删除。

`course_catalog_suppressions`只含slug（varchar150主键）、created_at、updated_at；保存已最终删除slug，阻止旧静态目录/兼容路由和安装命令重新显示或导入，不保存课程内容或个人信息。管理员明确新建同slug课程时删除标记。自动导入同时检查withTrashed记录和该表。回收站课程也不参与课时管理、展示排序、关系目标选择、Free Lab/推荐和学习资料访问；原学习记录仍保留，重新发布后可继续读取。

### D-055课时回收站与批量管理（当前实现）

迁移`2026_10_07_180000_add_lesson_soft_deletes`为lessons增加nullable timestamp `deleted_at`及索引，既有课时初始null，不改写状态、内容或进度。Lesson使用SoftDeletes，deleted_at禁止公开批量赋值；常规Eloquent查询、课程关联和路由排除软删除课时。主课时列表onlyTrashed显示回收站，不提供编辑或最终删除；恢复复用原ID和slug，状态统一draft，原上传文件保留。归档仍使用status，两者不混用；旧静态目录同时过滤匹配课程/slug的删除课时，避免再次开放试看或清单下载。

批量管理通过LessonBulkActionService：管理员权限重新查询、参数/失效选择校验、所属课程及课时行锁、逐课Gate授权和模型发布校验、整批事务；课时有任何学习记录时阻止整批软删除。常规大纲、课时计数、完整免费筛选和进度分母不包含软删除课时，状态/结构变化后课程退回草稿，恢复也须审核后分别发布。D-060课程最终删除显式withTrashed为全部课时进度保存快照、解绑后清理课时；独立课时删除仍保留进度保护，nullable课时外键继续restrictOnDelete。

下列Lesson字段列表仍包含尚未实现的长期设计，以D-031、D-034、D-055及D-057当前字段为准。

```text
id
course_series_id    bigint fk
title               varchar
slug                varchar
order_no             int
score                int（历史列，不参与当前业务）
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

标准Series参考顺序，课数可按任务调整：

```text
order_no: 1..10
不使用累计score或points定义课程进度
```

---

## 5. lesson_progress

### D-060删除后的学习记录（当前实现）

迁移`2026_10_10_140000_preserve_progress_after_course_deletion`增加`deleted_course_snapshot`（nullable JSON），并将lesson_id改为nullable unsigned bigint。课时外键保留RESTRICT，user_id外键及`(user_id, lesson_id)`唯一索引不变；不能直接级联删除进度。多个解绑历史记录的lesson_id为null，仍保留独立的原记录ID。

快照仅含course_id/course_title/course_slug/lesson_id/lesson_title/lesson_slug，不保留正文、Prompt、代码或资料。CourseDeletionService在同一课程/课时锁事务内生成快照并明确解绑，再清理课程内容；任一失败全部回滚。checks、progress_percent、last_position_seconds、completed_at、created_at、updated_at和user_id不改动，不用重新计算历史课程完成百分比。快照字段受guard保护，不由公开进度请求写入；活跃内容与正常进度计算仍使用真实课时ID。

LearningHistoryService只查询当前用户记录，并以显式withTrashed读取最小课程/课时元信息；不会改变正常学习关系或授权。`/me/learning/{progress}`按本人查找记录，普通用户及管理员都不能越权读取他人历史；删除后HTTP 410显示课程已下架，其他暂不可访问内容不跳转学习。记录页与个人中心private/no-store，不渲染已删除正文。恢复并发布可继续原ID进度；最终删除后即使复用slug也不绑定新课。

本地迁移前保存私有表结构/进度备份，迁移后旧字段与业务记录数保持一致。线上由运维独立审阅及执行，Hook不迁移或清理记录。存在已解绑历史记录时down显式拒绝回滚，不能通过重新设置非空列或删除快照丢失历史数据。

```text
id
user_id              bigint fk
lesson_id             bigint fk nullable（D-060）
deleted_course_snapshot JSON nullable（D-060，最小名称/ID快照）
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

所有Series均按已验收的已发布课时数÷当前未归档、未删除课时数计算百分比，全部阶段及最终任务验收完成才到100%；课数变化后动态重算，不改写已有记录。Solve依据问题目标状态，Create依据真实成品，Explore依据实验与证据结论，不能将实验失败视为未完成。

D-031已对免费任务实现服务端LessonProgress与清单验收。D-057整门免费与付费课程的免费试看复用同一账号进度；网站旧v1/v2/v3浏览器分值保留但不再显示或自动迁移。付费内容的购买/订阅授权与完整学习仍待实现。

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

D-044已实现此表：`slug`为自动生成的UUID；`ends_at`当前必填，时间以UTC存储、前台按北京时间展示。`status`实际支持`draft`、`scheduled`、`live`、`processing`、`replay`、`cancelled`；仅非草稿的公开场次可见，进入课堂只允许`live`且有HTTPS meeting_url，回放只允许`replay`且有HTTPS replay_url。当前`access_type`固定`public`，下方subscriber/series是待实现的目标，不可由后台选择；尚无课程关联表。

```text
id
title                 varchar
slug                  varchar unique
description           text nullable
starts_at             timestamp
ends_at               timestamp
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


## course_relations（D-039，当前实现）

迁移`2026_10_02_160000_create_course_relations_table`仅新建表，保留全部现有课程、课时、用户及进度；不自动创建课程关系。

| 字段 | 类型与约束 |
| --- | --- |
| id | 主键 |
| course_series_id | 所属CourseSeries外键，禁止关联课程被硬删除 |
| related_course_series_id | 目标CourseSeries外键，禁止关联课程被硬删除 |
| relation_type | varchar(24)，服务端白名单prerequisite/recommended/next |
| sort_order | unsigned integer，默认1000，服务端整数0–999999 |
| description | nullable varchar(500)，向用户解释关联理由，安全转义 |
| created_at / updated_at | 时间戳 |

唯一约束：`(course_series_id, related_course_series_id, relation_type)`；展示索引：`(course_series_id, relation_type, sort_order)`。CourseSeries.courseRelations为出向hasMany，CourseRelation.course/relatedCourse为belongsTo。关系是单向的；同一目标可有不同类型，推荐不自动双向。前置关系方向为“当前课程建议先完成目标课程”，服务拒绝自身、重复及直接/间接前置循环；recommended与next可以回链，不自动递归遍历。

所有关系写入通过CourseRelationService：授权管理员更新源课程，拒绝跨源关系ID；事务中先锁固定的最小ID课程行，保证并发图写入的循环检查一致。数字排序修改与移除连接也使用该服务；仅删除关系记录，保留实际内容及进度。关系内排序同值按关系ID升序，与CourseSeries.sort_order和Lesson.position独立。

公开介绍`/courses/{series}`及公开关联目标要求课程published且有published课时；完整免费须满足Free Lab过滤规则。课程元信息与已发布大纲可公开，课时正文/Prompt/代码/附件不随路径输出，付费学习仍未开放。草稿/归档源或目标不出现在关系模块；管理员只读预览可查看这些连接，并只跳转到受保护预览。

本阶段不新增level、outcome或course_type：已有category/final_outcome及is_free继续使用；会员访问权益不能由课程类型替代。



## questions（D-041，私人问题收集，当前实现）

审计前没有问题池提交模型。本次仅建立一张questions表；未来公开问题池须评估扩展此表，不平行新建重复任务卡系统。

- id、user_id（users外键，限制硬删除）、submission_key（UUID）、content_hash（SHA-256）；唯一(user_id,submission_key)，索引(user_id,created_at)。
- title varchar(160)、category varchar(16)白名单solve/create/explore、goal text、scope nullable text、outcome text、completion_criteria JSON字符串清单、时间戳。
- 请求限制：goal/scope/outcome各1500字，验收1–8条且每条最多300字；confirmed必须accepted。归属来自认证用户，输入user_id/visibility/status/messages不写入。
- 没有公开状态、聊天消息、会员字段或课程完成分值：所有记录均为本人私人收集。创建不发布、不授权课程、不标记100分；管理员不能查看他人的私人记录。
- QuestionSubmissionService事务先锁用户；同一UUID及内容返回原记录，编号冲突409、不覆盖。仅保存白名单任务卡，聊天与未确认草稿只在页面内存。
- POST /questions保存；GET /questions/mine分页本人记录，GET /questions/{id}归属不符404；全部依赖现有Session认证，写入有CSRF/限流，查看与成功JSON设置private/no-store。POST /questions/recommendations不持久化，复用完整免费课程过滤和推荐服务。
- 迁移2026_10_02_170000_create_questions_table只新增表，不触及已有课程/课时/关系/用户/进度。公开发布、保留期限和用户删除流程仍待另行设计。
