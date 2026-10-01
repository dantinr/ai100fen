# AI100分 COURSE_CONSTITUTION.md

## 1. 文档定位

本文件是 AI100分 的课程体系最高规则。

它用于约束：

- CourseSeries 的创建与修改；
- 课程选题；
- 内容分类；
- 首页与课程发现页的信息架构；
- 推荐逻辑；
- 课程完成标准；
- Agent 在课程体系相关任务中的判断。

涉及课程、Series、课程分类、课程推荐、课程首页入口、课程命名时，应优先遵守本文件。

---

# 2. AI100分课程宪法

AI100分 的所有课程，只围绕三类价值展开：

## 2.1 解决一个问题

英文：

```text
Solve
```

定义：

帮助用户解决一个真实存在、可以描述、可以验证的问题。

典型例子：

- 100分钟搭建一个真正的网站；
- 100分钟自动处理100份Excel；
- 100分钟分析自己的自媒体账号；
- 100分钟把本地项目部署到服务器；
- 100分钟完成一次网站故障排查。

完成标准：

> 原始问题被实际解决，并且用户能够验证结果。

---

## 2.2 创作一个作品

英文：

```text
Create
```

定义：

帮助用户从零完成一个真实、可见、可用、可运行、可发布或可展示的作品。

典型例子：

- 100分钟做一个小游戏；
- 100分钟做一个个人网站；
- 100分钟制作一个小程序；
- 100分钟做一个自己的软件；
- 100分钟制作一个完整短视频作品。

完成标准：

> 课程结束时，必须存在一个真实成品。

成品至少满足一种：

- 可以使用；
- 可以运行；
- 可以打开；
- 可以发布；
- 可以展示；
- 可以交付。

---

## 2.3 探索一个可能

英文：

```text
Explore
```

定义：

使用 AI / Agent 去尝试一件过去成本过高、门槛过高、难以验证，或者尚无明确答案的事情。

典型例子：

- 100分钟探索 Agent 能否独立运营一个网站；
- 100分钟探索个人能否做自己的浏览器；
- 100分钟探索 AI 能否重构一个真实工作流；
- 100分钟探索 Agent 能否完成一个小型软件项目；
- 100分钟探索 AI 能否承担原本需要多人协作的任务。

完成标准：

> 完成一次真实实验，并得到一个明确、可解释、有证据支持的结论。

注意：

Explore 不要求实验必须成功。

失败但得到明确结论，同样视为完成。

例如：

> “完整开发浏览器不值得，但基于 WebView2 构建个人浏览器壳是可行的。”

这是一个有效的探索结果。

---

# 3. AI100分不是什么

AI100分 不以“学习知识本身”为课程终点。

不优先创建以下类型课程：

```text
100分钟学Python
100分钟学Laravel
100分钟学Codex
100分钟学Prompt Engineering
100分钟学Excel
100分钟学某个AI模型
```

这些名称的问题在于：

> 工具成为了目标。

AI100分 的原则是：

> 工具只是手段，结果才是课程主体。

---

# 4. 正确的课程命名方式

错误：

```text
100分钟学Python
```

正确：

```text
100分钟做一个批量文件处理工具
```

---

错误：

```text
100分钟学Excel
```

正确：

```text
100分钟自动处理100份Excel
```

---

错误：

```text
100分钟学Codex
```

正确：

```text
100分钟用Agent完成一个真实网站
```

---

错误：

```text
100分钟学Laravel
```

正确：

```text
100分钟做一个带用户系统的网站
```

---

# 5. 核心判断原则

创建任何 CourseSeries 前，必须回答：

## 5.1 它属于哪一类？

只能选择一个主要类别：

```text
solve
create
explore
```

如果无法明确归类，应重新设计课程。

---

## 5.2 用户最终获得什么？

必须回答：

> 100分钟结束以后，什么东西发生了变化？

可能是：

- 一个问题被解决；
- 一个作品被完成；
- 一个可能被验证。

如果答案只是：

> “用户学会了一些知识。”

则不满足 AI100分 课程标准。

---

## 5.3 如何判断100分？

必须存在可验证的完成标准。

100分不是考试成绩。

100分代表：

> 这个任务真正完成了。

---

# 6. Agent 与人的职责

AI100分 不训练用户替 Agent 做工作。

AI100分训练用户：

> 正确使用 Agent 完成真实任务。

基本职责：

## 人负责

```text
目标
判断
选择
风险
观察
验收
```

## Agent负责

```text
执行
生成
修改
运行
部署
分析
重复劳动
```

核心原则：

> 人负责目标和验收，Agent负责执行。

---

# 7. 每个 CourseSeries 必须定义的内容

每个 CourseSeries 至少需要以下字段：

```text
category
user_intent
final_outcome
completion_criteria
agent_role
human_judgment_required
```

---

## category

允许值：

```text
solve
create
explore
```

---

## user_intent

描述用户为什么开始这个任务。

例如：

```text
我想拥有一个真正可以访问的网站。
```

---

## final_outcome

描述课程完成以后产生什么结果。

例如：

```text
一个拥有域名、HTTPS、可访问、可维护的网站。
```

---

## completion_criteria

描述怎样才算完成。

例如：

```text
- 域名可以正常访问；
- HTTPS有效；
- 网站在手机和桌面端可访问；
- 用户知道网站文件和数据库在哪里；
- 已完成一次可恢复备份。
```

---

## agent_role

描述 Agent 在整个任务中的执行职责。

例如：

```text
- 生成代码；
- 配置服务器；
- 安装软件；
- 检查服务状态；
- 分析日志；
- 修改配置；
- 辅助验证。
```

---

## human_judgment_required

描述用户必须自己判断的内容。

例如：

```text
- 是否选择当前技术方案；
- 是否允许危险操作；
- 网站是否符合需求；
- 是否达到验收标准；
- 是否接受安全和成本风险。
```

---

# 8. 课程创建前的强制检查

Codex 或后台创建 CourseSeries 前，必须检查：

1. 这是在解决问题、创作作品，还是探索可能？
2. 用户最后会得到什么真实结果？
3. 结果是否可以验证？
4. AI / Agent 是否显著降低了完成门槛、时间或成本？
5. 用户是否仍然需要做关键判断？
6. 是否把工具本身错误地当成了课程目标？
7. 课程是否可以拆成明确的阶段性结果？
8. 100分钟是否足以完成一个有意义的闭环？

如果第6项为：

```text
是
```

应优先重新设计课程。

---

# 9. 100分钟与100分

AI100分的标准结构：

```text
10 × 10分钟
≈
100分钟
```

课程进度：

```text
10分
20分
30分
40分
50分
60分
70分
80分
90分
100分
```

每10分代表：

> 完成一个可见步骤。

100分代表：

> 整个真实任务完成。

---

# 10. 三类课程的完成逻辑

## Solve

结构：

```text
问题
↓
分析
↓
执行
↓
验证
↓
问题解决
```

100分标准：

> 问题已经不存在，或者已经达到预先定义的目标状态。

---

## Create

结构：

```text
想法
↓
定义作品
↓
制作
↓
修改
↓
发布 / 运行 / 展示
```

100分标准：

> 成品真实存在。

---

## Explore

结构：

```text
提出可能
↓
形成假设
↓
设计实验
↓
让Agent执行
↓
观察结果
↓
形成结论
```

100分标准：

> 得到明确的实验结论。

---

# 11. 首页信息架构

首页课程入口优先围绕：

```text
你今天想做什么？
```

三个一级入口：

```text
解决一个问题
创作一个作品
探索一个可能
```

英文辅助表达可使用：

```text
SOLVE
CREATE
EXPLORE
```

---

# 12. 首页文案建议

主标题：

```text
别再学AI了。
先用AI把一件事做成。
```

三类入口：

## 解决一个问题

```text
把一个现实中的麻烦真正解决掉。
```

## 创作一个作品

```text
从零做出一个可以使用、发布或展示的东西。
```

## 探索一个可能

```text
用AI去尝试一件过去成本太高、门槛太高，甚至不敢想的事。
```

---

# 13. 数据模型要求

CourseSeries 应支持：

```text
category enum('solve', 'create', 'explore')
```

建议逐步支持：

```text
user_intent
final_outcome
completion_criteria
agent_role
human_judgment_required
```

后台创建或编辑 Series 时：

> category 必填。

不允许未分类的正式 CourseSeries 发布。

---

# 14. Codex 执行规则

涉及以下任务时：

```text
课程创建
课程修改
课程分类
Series 页面
首页课程入口
课程推荐
课程命名
课程发现逻辑
```

Codex 必须先阅读：

```text
COURSE_CONSTITUTION.md
```

然后再执行任务。

建议在 AGENTS.md 中加入：

```md
## Course Constitution

Before any work involving courses, CourseSeries, curriculum,
course taxonomy, course discovery, recommendations, or course-related
homepage structure:

READ COURSE_CONSTITUTION.md FIRST.

COURSE_CONSTITUTION.md defines the highest-level curriculum rules
for AI100.

Implementation convenience must not override it.
```

---

# 15. 与 PRODUCT.md 的关系

PRODUCT.md 定义：

> AI100分是什么产品。

COURSE_CONSTITUTION.md 定义：

> AI100分允许生产什么样的课程。

两者职责不同。

COURSE_CONSTITUTION.md 不应频繁修改。

如需修改，应视为产品级决策，并记录到：

```text
DECISIONS.md
```

---

# 16. 与 DATA_MODEL.md 的关系

本文件定义业务约束。

DATA_MODEL.md 负责记录实际数据结构。

如果本文件新增了必要字段，应同步检查：

```text
DATA_MODEL.md
```

但不应为了同步文档而制造无意义 diff。

---

# 17. Codex 完成任务后的自检

涉及课程体系的任务完成后，Codex 必须报告：

```text
Constitution category:
Solve / Create / Explore

User intent:
...

Final outcome:
...

Completion criteria:
...

Agent role:
...

Human judgment:
...
```

同时检查：

- 是否违反“工具不是课程目标”的原则；
- 是否具有真实结果；
- 是否可以验证完成；
- 是否需要更新 PRODUCT.md；
- 是否需要更新 DATA_MODEL.md；
- 是否需要记录 DECISIONS.md；
- 是否需要更新 TODO.md。

---

# 18. 最终判断

AI100分 不应该问：

> 今天教什么？

而应该问：

> 今天帮助用户解决什么？
> 今天帮助用户创造什么？
> 今天和用户一起探索什么？

因此，AI100分 的课程体系最终只有三个动作：

# SOLVE

解决一个问题。

# CREATE

创作一个作品。

# EXPLORE

探索一个可能。

---

# 19. 核心表达

AI100分不是一个“学习AI知识”的网站。

它是：

> 一个让人使用 AI / Agent 去解决问题、创造作品、探索可能的行动系统。

最终原则：

> 不以知道为完成。
>
> 以做成为100分。
