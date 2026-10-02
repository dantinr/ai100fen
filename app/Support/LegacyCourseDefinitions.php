<?php

namespace App\Support;

class LegacyCourseDefinitions
{
    /** Reviewed by user intent and final result, independently of the old build/work tags. */
    public static function constitution(): array
    {
        return [
            'build-a-website' => [
                'category' => 'create',
                'completion_criteria' => ['自己的域名能打开网站，HTTPS连接有效且HTTP正确跳转', '首页、导航和手机布局可用，页面包含自己的内容', '在独立测试环境成功恢复备份，并记录文件、数据与配置位置'],
                'agent_role' => ['检查测试环境并部署页面与Web服务', '配置域名、HTTPS并修改网站内容', '诊断异常并协助备份与恢复演练'],
                'human_judgment_required' => ['选择网站用途、内容与测试环境', '确认端口、服务和现有站点变更的影响与费用', '亲自核对访问、手机布局与恢复结果'],
            ],
            'add-website-support' => [
                'category' => 'solve',
                'completion_criteria' => ['网站桌面端与手机端均能通过客服入口提问', '事先选定的常见问题测试集得到与业务资料一致的回答', '未知问题不会编造承诺，并提供可用的人工联系入口'],
                'agent_role' => ['整理客服资料与测试问答', '接入网站客服入口并检查异常流程', '分析测试回答并修改配置与资料'],
                'human_judgment_required' => ['核实业务资料与可公开内容', '选择客服服务、成本和数据处理范围', '确认回答准确性与转人工条件'],
            ],
            'build-a-miniapp' => [
                'category' => 'create',
                'completion_criteria' => ['小程序在目标平台测试环境可运行', '事先选定的核心页面、输入与数据交互通过测试', '提交审核所需配置与资料齐全，并保留源码和启动说明；不保证平台审核时间'],
                'agent_role' => ['生成小程序页面与核心交互', '连接接口并运行正常、异常样例', '整理发布配置与使用说明'],
                'human_judgment_required' => ['选择平台与首版核心需求', '确认账号、用户数据及接口权限范围', '核对实际运行结果与提交资料'],
            ],
            'analyze-your-content' => [
                'category' => 'solve',
                'completion_criteria' => ['账号诊断报告引用同周期数据，关键统计能回到原始数据核对', '内容表现差异与受众反馈有具体证据，明确样本局限', '输出与分析对应的下一阶段选题清单，区分证据与待验证假设'],
                'agent_role' => ['清洗账号数据并计算内容表现指标', '归纳评论反馈与内容差异', '生成有证据引用的诊断与选题建议'],
                'human_judgment_required' => ['确定分析周期、账号目标与脱敏范围', '核对统计口径和样本偏差', '选择后续选题，不把相关性当成因果关系'],
            ],
            'plan-your-next-creation' => [
                'category' => 'solve',
                'completion_criteria' => ['复盘引用自己的作品、评论及同周期播放数据，可核对出处', '资讯线索保留来源与日期，事实和推测清楚区分', '选题与行动计划说明依据、执行顺序和可检查的下一步'],
                'agent_role' => ['分析作品结构与评论需求', '比较播放、完播和互动表现', '整理资讯来源并起草创作建议与行动计划'],
                'human_judgment_required' => ['明确创作方向与受众目标', '核对资讯可信度、评论样本与数据口径', '选择符合自己风格与资源的选题'],
            ],
            'merge-excel-files' => [
                'category' => 'solve',
                'completion_criteria' => ['汇总表按约定规则合并选定文件，列结构正确且异常记录可追溯', '行数、去重结果与汇总金额能和原始数据核对', '处理脚本可在同类样例再次运行，不覆盖原始文件'],
                'agent_role' => ['编写合并、清洗与汇总脚本', '运行样例并整理异常记录', '生成核对报表与再次运行说明'],
                'human_judgment_required' => ['定义合并规则、重复记录处理与统计口径', '决定文件是否允许用于练习及脱敏方式', '用原始数据核对汇总结果'],
            ],
            'create-a-presentation' => [
                'category' => 'create',
                'completion_criteria' => ['生成可打开且可编辑的PPT文件', '汇报结构回答预先明确的核心问题，图表与结论可回到资料核对', '按预计汇报时长完成一次讲述演练，并保留讲述提纲'],
                'agent_role' => ['整理资料并生成汇报结构', '制作可编辑页面与图表', '根据讲述反馈修改内容与版面'],
                'human_judgment_required' => ['决定受众、汇报目标与重点', '确认事实、数据来源与保密范围', '验收可编辑性与实际讲述效果'],
            ],
            'build-a-work-tool' => [
                'category' => 'create',
                'completion_criteria' => ['本地工具可运行并完成约定的输入、处理和导出流程', '正常与异常样例结果符合事先定义的预期', '保留源码和启动说明，可再次执行同类任务'],
                'agent_role' => ['将核心需求实现为界面或命令行工具', '运行测试样例并修复问题', '整理源码、操作说明与结果文件'],
                'human_judgment_required' => ['选择一个可在首版完成的重复工作场景', '定义输入与预期结果，确认本地文件操作边界', '亲自运行样例并验收输出'],
            ],
            'build-your-own-software' => [
                'category' => 'create',
                'completion_criteria' => ['所选软件原型能在目标系统启动；不要求同时制作下载器和输入法', '核心流程通过事先选定的样例：下载器核对任务、进度、取消与重试，或输入法核对输入、候选与上屏', '源码、测试记录与使用说明可交付，明确原型的未实现功能'],
                'agent_role' => ['生成所选软件的界面与核心功能', '在独立测试环境运行和修复样例', '整理源码、启动方式与限制说明'],
                'human_judgment_required' => ['选择一个软件方向、目标系统及原型范围', '确认下载来源、系统集成权限与测试环境', '验收核心功能并决定原型是否适合实际使用'],
            ],
            'build-your-own-agent' => [
                'category' => 'create',
                'completion_criteria' => ['Agent在约定测试环境能完成一个指定任务，输出满足预先写好的完成标准', '正常、缺少信息和工具失败样例均有可核对的执行记录', '配置、工具权限与启动维护说明可复用，危险动作保留人工确认'],
                'agent_role' => ['生成角色指令与参考资料配置', '接入限定范围的工具并运行测试', '分析执行记录并修订指令与流程'],
                'human_judgment_required' => ['选择具体任务、输入与完成标准', '决定工具访问、凭据和人工确认边界', '判断执行结果是否可靠且可重复使用'],
            ],
            'remix-a-game' => [
                'category' => 'create',
                'completion_criteria' => ['修改后的小游戏可运行，核心开始、游玩与结束流程正常', '事先选定的玩法或关卡变化能在试玩中验证', '保留可运行版本、修改记录及源码使用许可说明'],
                'agent_role' => ['读取游戏项目并运行原版', '修改约定的玩法、关卡或界面效果', '修复试玩问题并整理可运行版本'],
                'human_judgment_required' => ['确认源码和素材允许修改', '选择有意义且可实现的玩法变化', '亲自试玩并验收难度、反馈与核心流程'],
            ],
            'organize-your-files' => [
                'category' => 'solve',
                'completion_criteria' => ['样本目录按认可的规则命名和分类，指定文件能被找到', '整理前后文件数量与内容核对一致，无意外丢失或覆盖', '变更记录可追溯，并在副本上成功撤回一次整理操作'],
                'agent_role' => ['生成整理预览与变更清单', '在副本中执行批量命名和分类', '核对数量、内容与撤回记录'],
                'human_judgment_required' => ['认可命名分类规则并限定允许操作的目录', '审阅预览后确认执行', '亲自核对文件完整性与撤回结果'],
            ],
            'write-a-research-report' => [
                'category' => 'create',
                'completion_criteria' => ['报告文件可打开，明确回答选定的调研问题', '关键事实和引用标注可访问来源及采集日期', '结论区分证据与判断，说明信息局限和下一步建议'],
                'agent_role' => ['收集允许范围的资料并建立证据清单', '对照来源整理事实与不确定项', '生成报告初稿并按反馈修改'],
                'human_judgment_required' => ['选择可回答的调研问题与资料范围', '核对来源可信度、引用与信息时效', '判断结论是否得到证据支持'],
            ],
            'build-personal-digital-assets' => [
                'category' => 'create',
                'completion_criteria' => ['资产库包含约定样本的原始文件、来源、版本与入口索引', '公开展示与私人资料有明确边界，能查找到指定资产', '在独立位置完成备份并恢复一组样本，保留持续更新清单'],
                'agent_role' => ['盘点作品、文档、代码与账号入口', '整理目录、版本与资产索引', '导出样本并协助备份、迁移和恢复验收'],
                'human_judgment_required' => ['决定哪些内容属于自己的资产及允许导出范围', '区分公开展示、私人资料与账号凭据', '核对查找和恢复结果并制定维护频率'],
            ],
            'build-a-knowledge-library' => [
                'category' => 'solve',
                'completion_criteria' => ['约定文档建立可维护的检索索引', '事先选定的查找问题能定位相关信息与原文出处', '回答可回到原文核对，缺少证据时明确说明无法确认'],
                'agent_role' => ['整理文档与检索索引', '执行查找样例并提供来源', '根据核对结果修正索引与回答流程'],
                'human_judgment_required' => ['决定文档使用权限、脱敏范围与查找目标', '选择真实问题及可接受的检索效果', '核对原文，不接受没有依据的回答'],
            ],
            'turn-meetings-into-actions' => [
                'category' => 'solve',
                'completion_criteria' => ['会议纪要清楚区分讨论、决定与待确认事项', '每项行动记录负责人、截止时间与下一步，缺失信息标记待确认而非编造', '关键决定和任务均能回到会议原文核对'],
                'agent_role' => ['读取允许处理的会议记录', '提取决定、待确认事项与行动项', '生成纪要和可检查的行动清单'],
                'human_judgment_required' => ['确认记录使用权限与参会角色', '核对决定、责任分配与时间约定', '确认待办是否可执行，并补充尚未明确的信息'],
            ],
        ];
    }

    public static function courses(FrontendCatalog $catalog): array
    {
        $definitions = [];
        $reviewed = self::constitution();
        foreach ($catalog->all() as $source) {
            $constitution = $reviewed[$source['slug']] ?? throw new \LogicException('课程尚未进行宪章审核：'.$source['slug']);
            $lessons = [];
            foreach ($source['lessons'] as $index => $outline) {
                $content = $catalog->canPreview($source, $outline) ? $catalog->previewContent($source, $outline) : [];
                $lessons[] = [
                    'slug' => $outline['slug'], 'title' => $outline['title'], 'position' => $index + 1,
                    'score' => $outline['score'], 'points' => 10, 'minutes' => $outline['minutes'], 'is_free' => $outline['is_free'],
                    'intro' => $content['intro'] ?? $outline['summary'], 'goal' => $content['goal'] ?? $outline['goal'],
                    'objectives' => [$content['goal'] ?? $outline['goal']], 'steps' => $content['steps'] ?? [], 'prompt' => $content['prompt'] ?? '',
                    'code' => $content['code'] ?? null, 'code_filename' => isset($content['code']) ? 'hello.html' : null,
                    'checks' => $content['checks'] ?? [],
                    'resources' => $content ? [[
                        'name' => 'lesson-checklist.md', 'label' => '本课验收清单',
                        'content' => '# '.$outline['title']."：验收清单\n\n".$content['goal']."\n\n".implode("\n", array_map(fn ($check) => '- [ ] '.$check, $content['checks']))."\n",
                    ]] : [], 'status' => $content ? 'published' : 'draft',
                ];
            }
            $definitions[] = $constitution + [
                'slug' => $source['slug'], 'title' => $source['title'], 'user_intent' => $source['question'],
                'final_outcome' => $source['outcome'], 'objectives' => $source['deliverables'],
                'description' => $source['description']."\n\n## 开始前准备\n\n".implode("\n", array_map(fn ($item) => '- '.$item, $source['prerequisites'])),
                'recommendation_keywords' => [$source['tag'], $source['question']], 'price' => '100.00', 'minutes' => 100,
                'is_free' => false, 'status' => 'draft', 'lessons' => $lessons,
            ];
        }

        return $definitions;
    }
}
