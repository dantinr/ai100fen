# 问题池话题维护

`/questions`展示人工整理的AI/Agent关注问题，数据集中于`app/Support/QuestionTopics.php`。这些是本站归纳的问题与尝试建议，不是全网排名、实时趋势、原文引用或用户提交记录。

## 首批来源（整理于2026-10-02）

| 来源 | 发布日期 | 话题依据 |
| --- | --- | --- |
| [OpenAI · How agents are transforming work](https://openai.com/index/how-agents-are-transforming-work/) | 2026-06-25 | 软件制作、重复工作、创作资料分析 |
| [Anthropic · Measuring AI agent autonomy in practice](https://www.anthropic.com/research/measuring-agent-autonomy) | 2026-02-18 | 聊天与行动、自主性与人的介入 |
| [OpenAI · Introducing Agents API](https://openai.com/index/introducing-the-agents-api/) | 2026-09-10 | 长任务环境、工具与多Agent协作 |
| [Anthropic · Effective context engineering for AI agents](https://www.anthropic.com/engineering/effective-context-engineering-for-ai-agents) | 2025-09-29 | 有限上下文、阶段记录与调用开销 |
| [Anthropic · Trustworthy agents in practice](https://www.anthropic.com/research/trustworthy-agents) | 2026-04-09 | 结果验证、权限与数据范围、提示注入 |

## 更新方式

- 先阅读原始文章，归纳用户会问的具体问题；不要把宣传效果写成普遍保证。
- 为每项填写标签、具体问题、背景、第一步与来源。背景是编辑归纳，第一步是本站建议，避免过量引用。
- `category`仅控制免费实验室的Solve/Create/Explore学习方向，按这一步的目标判断；`course`仅链接已存在的课程方向，可为空，不代表课程已发布或已可付费学习。
- 保持话题ID稳定，已有`?topic=memory`等分享地址继续可用。未知或非字符串参数回退首项。
- 实际重新核查来源后才更新`REVIEWED_ON`；不得自动刷新日期，伪装为实时更新。
- 修改后运行适用前台/主题测试及`npm run build`，检查手机、键盘、链接、返回操作与减少动态效果。

目前无自动抓取、提交、投票、热度统计或聊天服务。后续业务见TODO；本次未创建或修改任何CourseSeries/Lesson。
