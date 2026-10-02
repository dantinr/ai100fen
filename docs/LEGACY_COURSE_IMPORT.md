# 现有16门课程入库审核（D-036）

日期：2026-10-02。范围：FrontendCatalog已有的16门课程与网站系列已有10课时，不创建新的课程方向或补造其余15门的大纲。已有3个免费任务与人工课程保持原样。

所有导入Series保留原名称、slug、100元价格和约100分钟格式，状态为draft。课程定义可编辑，准备事项保存在Markdown介绍中，原成果清单保存在objectives。封面、图标与公共目录仍由原前台代码展示；本次入库不切换公共目录数据源。

创建前检查：按真实意图与最终结果分别选定唯一类别（Create 9、Solve 7，不强制补Explore）；六项定义完整，验收要求实际结果，工具不作为终点，执行劳动交给Agent，选择与验收留给人。网站已有阶段大纲；其余15门缺少阶段成果与课时，约100分钟闭环仍须用样本实践与完整大纲验证，不能因入库而正式发布。原网站首课具有完整正文，其余9课仅保留已有大纲并标为草稿。

执行：`php artisan courses:import-legacy --dry-run`先检查，`php artisan courses:import-legacy`独立执行。事务中只新增不存在的slug；遇到同slug整门跳过，不给现有课程补课时、不覆盖字段或重置进度。生产执行前保留私有内容快照，git pull Hook不自动导入。

首课文字内容在现有导入之后补全：公开试看与新导入课时共用`WebsiteFirstLessonContent`。已有数据库课时不会因Git pull或再次导入而自动覆盖。先运行`php artisan courses:sync-website-first-lesson`预检；仅在显示原始导入版本且审阅内容后，独立运行`php artisan courses:sync-website-first-lesson --apply`。命令只更新目标、简介、正文、步骤和Prompt，保留三项验收、分值、示例代码、发布状态及学习记录；发现人工编辑时拒绝覆盖，应在后台手动合并。首课视频尚未录制。

## 100分钟搭建网站

- Slug：`build-a-website`
- Constitution category：create
- User intent：我想拥有自己的网站
- Final outcome：一个可访问、有域名和 HTTPS、能够备份恢复的网站。

**Completion criteria**

- 自己的域名能打开网站，HTTPS连接有效且HTTP正确跳转
- 首页、导航和手机布局可用，页面包含自己的内容
- 在独立测试环境成功恢复备份，并记录文件、数据与配置位置

**Agent role**

- 检查测试环境并部署页面与Web服务
- 配置域名、HTTPS并修改网站内容
- 诊断异常并协助备份与恢复演练

**Human judgment**

- 选择网站用途、内容与测试环境
- 确认端口、服务和现有站点变更的影响与费用
- 亲自核对访问、手机布局与恢复结果

## 给网站接入智能客服

- Slug：`add-website-support`
- Constitution category：solve
- User intent：我想让网站随时回答客户的问题
- Final outcome：一个可用的智能客服入口，能回答常见问题并引导人工联系。

**Completion criteria**

- 网站桌面端与手机端均能通过客服入口提问
- 事先选定的常见问题测试集得到与业务资料一致的回答
- 未知问题不会编造承诺，并提供可用的人工联系入口

**Agent role**

- 整理客服资料与测试问答
- 接入网站客服入口并检查异常流程
- 分析测试回答并修改配置与资料

**Human judgment**

- 核实业务资料与可公开内容
- 选择客服服务、成本和数据处理范围
- 确认回答准确性与转人工条件

## 100分钟制作小程序

- Slug：`build-a-miniapp`
- Constitution category：create
- User intent：我想把一个点子做成小程序
- Final outcome：一个能够运行，并具备提交审核条件的小程序。

**Completion criteria**

- 小程序在目标平台测试环境可运行
- 事先选定的核心页面、输入与数据交互通过测试
- 提交审核所需配置与资料齐全，并保留源码和启动说明；不保证平台审核时间

**Agent role**

- 生成小程序页面与核心交互
- 连接接口并运行正常、异常样例
- 整理发布配置与使用说明

**Human judgment**

- 选择平台与首版核心需求
- 确认账号、用户数据及接口权限范围
- 核对实际运行结果与提交资料

## 100分钟自媒体创作分析

- Slug：`analyze-your-content`
- Constitution category：solve
- User intent：我想知道下一条内容该做什么
- Final outcome：一份账号诊断报告，以及下一阶段的选题方向。

**Completion criteria**

- 账号诊断报告引用同周期数据，关键统计能回到原始数据核对
- 内容表现差异与受众反馈有具体证据，明确样本局限
- 输出与分析对应的下一阶段选题清单，区分证据与待验证假设

**Agent role**

- 清洗账号数据并计算内容表现指标
- 归纳评论反馈与内容差异
- 生成有证据引用的诊断与选题建议

**Human judgment**

- 确定分析周期、账号目标与脱敏范围
- 核对统计口径和样本偏差
- 选择后续选题，不把相关性当成因果关系

## 100分钟用Agent制定创作计划

- Slug：`plan-your-next-creation`
- Constitution category：solve
- User intent：我想让Agent帮我找到创作方向
- Final outcome：一份有依据的创作复盘、选题清单和下一阶段行动计划。

**Completion criteria**

- 复盘引用自己的作品、评论及同周期播放数据，可核对出处
- 资讯线索保留来源与日期，事实和推测清楚区分
- 选题与行动计划说明依据、执行顺序和可检查的下一步

**Agent role**

- 分析作品结构与评论需求
- 比较播放、完播和互动表现
- 整理资讯来源并起草创作建议与行动计划

**Human judgment**

- 明确创作方向与受众目标
- 核对资讯可信度、评论样本与数据口径
- 选择符合自己风格与资源的选题

## 100分钟批量处理Excel

- Slug：`merge-excel-files`
- Constitution category：solve
- User intent：我想把100份Excel合成一份报表
- Final outcome：一份核对过的汇总报表，以及可以重复运行的处理脚本。

**Completion criteria**

- 汇总表按约定规则合并选定文件，列结构正确且异常记录可追溯
- 行数、去重结果与汇总金额能和原始数据核对
- 处理脚本可在同类样例再次运行，不覆盖原始文件

**Agent role**

- 编写合并、清洗与汇总脚本
- 运行样例并整理异常记录
- 生成核对报表与再次运行说明

**Human judgment**

- 定义合并规则、重复记录处理与统计口径
- 决定文件是否允许用于练习及脱敏方式
- 用原始数据核对汇总结果

## 100分钟完成一次PPT汇报

- Slug：`create-a-presentation`
- Constitution category：create
- User intent：我想把一堆资料变成一份清楚的PPT
- Final outcome：一份逻辑清晰、可以编辑和演讲的汇报PPT。

**Completion criteria**

- 生成可打开且可编辑的PPT文件
- 汇报结构回答预先明确的核心问题，图表与结论可回到资料核对
- 按预计汇报时长完成一次讲述演练，并保留讲述提纲

**Agent role**

- 整理资料并生成汇报结构
- 制作可编辑页面与图表
- 根据讲述反馈修改内容与版面

**Human judgment**

- 决定受众、汇报目标与重点
- 确认事实、数据来源与保密范围
- 验收可编辑性与实际讲述效果

## 100分钟做一个自己的小工具

- Slug：`build-a-work-tool`
- Constitution category：create
- User intent：我想做一个省掉重复工作的小工具
- Final outcome：一个能够输入、处理并导出结果的本地工作工具。

**Completion criteria**

- 本地工具可运行并完成约定的输入、处理和导出流程
- 正常与异常样例结果符合事先定义的预期
- 保留源码和启动说明，可再次执行同类任务

**Agent role**

- 将核心需求实现为界面或命令行工具
- 运行测试样例并修复问题
- 整理源码、操作说明与结果文件

**Human judgment**

- 选择一个可在首版完成的重复工作场景
- 定义输入与预期结果，确认本地文件操作边界
- 亲自运行样例并验收输出

## 100分钟用Agent制作自己的软件

- Slug：`build-your-own-software`
- Constitution category：create
- User intent：我想做一款自己用得上的软件
- Final outcome：一款在自己电脑运行的软件原型，附核心功能验收清单。

**Completion criteria**

- 所选软件原型能在目标系统启动；不要求同时制作下载器和输入法
- 核心流程通过事先选定的样例：下载器核对任务、进度、取消与重试，或输入法核对输入、候选与上屏
- 源码、测试记录与使用说明可交付，明确原型的未实现功能

**Agent role**

- 生成所选软件的界面与核心功能
- 在独立测试环境运行和修复样例
- 整理源码、启动方式与限制说明

**Human judgment**

- 选择一个软件方向、目标系统及原型范围
- 确认下载来源、系统集成权限与测试环境
- 验收核心功能并决定原型是否适合实际使用

## 制作自己的Agent

- Slug：`build-your-own-agent`
- Constitution category：create
- User intent：我想做一个能帮我完成具体任务的Agent
- Final outcome：一个能完成指定任务的Agent，附配置与验收清单。

**Completion criteria**

- Agent在约定测试环境能完成一个指定任务，输出满足预先写好的完成标准
- 正常、缺少信息和工具失败样例均有可核对的执行记录
- 配置、工具权限与启动维护说明可复用，危险动作保留人工确认

**Agent role**

- 生成角色指令与参考资料配置
- 接入限定范围的工具并运行测试
- 分析执行记录并修订指令与流程

**Human judgment**

- 选择具体任务、输入与完成标准
- 决定工具访问、凭据和人工确认边界
- 判断执行结果是否可靠且可重复使用

## AI爆改游戏

- Slug：`remix-a-game`
- Constitution category：create
- User intent：我想把小游戏改成自己的玩法
- Final outcome：一个可运行、玩法有变化的小游戏，附修改记录与验收清单。

**Completion criteria**

- 修改后的小游戏可运行，核心开始、游玩与结束流程正常
- 事先选定的玩法或关卡变化能在试玩中验证
- 保留可运行版本、修改记录及源码使用许可说明

**Agent role**

- 读取游戏项目并运行原版
- 修改约定的玩法、关卡或界面效果
- 修复试玩问题并整理可运行版本

**Human judgment**

- 确认源码和素材允许修改
- 选择有意义且可实现的玩法变化
- 亲自试玩并验收难度、反馈与核心流程

## 100分钟整理自己的文件

- Slug：`organize-your-files`
- Constitution category：solve
- User intent：我想把杂乱文件整理得一目了然
- Final outcome：一个清晰的文件目录，以及可核对、可撤回的整理记录。

**Completion criteria**

- 样本目录按认可的规则命名和分类，指定文件能被找到
- 整理前后文件数量与内容核对一致，无意外丢失或覆盖
- 变更记录可追溯，并在副本上成功撤回一次整理操作

**Agent role**

- 生成整理预览与变更清单
- 在副本中执行批量命名和分类
- 核对数量、内容与撤回记录

**Human judgment**

- 认可命名分类规则并限定允许操作的目录
- 审阅预览后确认执行
- 亲自核对文件完整性与撤回结果

## 100分钟完成一份调研报告

- Slug：`write-a-research-report`
- Constitution category：create
- User intent：我想把零散资料变成有依据的报告
- Final outcome：一份有来源、有结论，并说明信息局限的调研报告。

**Completion criteria**

- 报告文件可打开，明确回答选定的调研问题
- 关键事实和引用标注可访问来源及采集日期
- 结论区分证据与判断，说明信息局限和下一步建议

**Agent role**

- 收集允许范围的资料并建立证据清单
- 对照来源整理事实与不确定项
- 生成报告初稿并按反馈修改

**Human judgment**

- 选择可回答的调研问题与资料范围
- 核对来源可信度、引用与信息时效
- 判断结论是否得到证据支持

## 如何建立个人数字资产

- Slug：`build-personal-digital-assets`
- Constitution category：create
- User intent：我想把作品和资料变成自己的长期积累
- Final outcome：一套自己的数字资产目录，附备份、恢复与持续维护清单。

**Completion criteria**

- 资产库包含约定样本的原始文件、来源、版本与入口索引
- 公开展示与私人资料有明确边界，能查找到指定资产
- 在独立位置完成备份并恢复一组样本，保留持续更新清单

**Agent role**

- 盘点作品、文档、代码与账号入口
- 整理目录、版本与资产索引
- 导出样本并协助备份、迁移和恢复验收

**Human judgment**

- 决定哪些内容属于自己的资产及允许导出范围
- 区分公开展示、私人资料与账号凭据
- 核对查找和恢复结果并制定维护频率

## 100分钟建立自己的资料库

- Slug：`build-a-knowledge-library`
- Constitution category：solve
- User intent：我想从自己的资料里快速找到答案
- Final outcome：一个可检索的个人资料库，以及带原文出处的回答流程。

**Completion criteria**

- 约定文档建立可维护的检索索引
- 事先选定的查找问题能定位相关信息与原文出处
- 回答可回到原文核对，缺少证据时明确说明无法确认

**Agent role**

- 整理文档与检索索引
- 执行查找样例并提供来源
- 根据核对结果修正索引与回答流程

**Human judgment**

- 决定文档使用权限、脱敏范围与查找目标
- 选择真实问题及可接受的检索效果
- 核对原文，不接受没有依据的回答

## 100分钟把会议记录变成行动清单

- Slug：`turn-meetings-into-actions`
- Constitution category：solve
- User intent：我想让会议结束后每件事都有下一步
- Final outcome：一份经过核对的会议纪要，以及明确责任与时间的行动清单。

**Completion criteria**

- 会议纪要清楚区分讨论、决定与待确认事项
- 每项行动记录负责人、截止时间与下一步，缺失信息标记待确认而非编造
- 关键决定和任务均能回到会议原文核对

**Agent role**

- 读取允许处理的会议记录
- 提取决定、待确认事项与行动项
- 生成纪要和可检查的行动清单

**Human judgment**

- 确认记录使用权限与参会角色
- 核对决定、责任分配与时间约定
- 确认待办是否可执行，并补充尚未明确的信息
