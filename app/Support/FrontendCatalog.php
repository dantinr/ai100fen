<?php

namespace App\Support;

use App\Services\CourseAccessService;
use App\Models\CourseSeries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Curated preview content, independent of future persisted courses and entitlements. */
class FrontendCatalog
{
    public function all(): array
    {
        $hidden = $this->hiddenSlugs();

        return array_values(array_filter($this->curated(), fn (array $course) => ! in_array($course['slug'], $hidden, true)));
    }

    public function isVisible(string $slug): bool
    {
        return collect($this->all())->contains('slug', $slug);
    }

    private function hiddenSlugs(): array
    {
        if (! Schema::hasColumn('course_series', 'deleted_at')) {
            return [];
        }

        return CourseSeries::onlyTrashed()->pluck('slug')->merge(DB::table('course_catalog_suppressions')->pluck('slug'))->all();
    }

    public function curated(): array
    {
        return [
            [
                'slug' => 'build-a-website',
                'title' => '10分钟搭建.com网站',
                'question' => '我想拥有自己的网站',
                'description' => '五节各约2分钟，人与 Agent 协作，让自己的.com网站上线并持续更新。',
                'outcome' => '一个能用自己的.com域名访问、有首页和导航、可以继续修改发布的网站。',
                'minutes' => 10,
                'category' => 'build',
                'category_label' => '创造一个作品',
                'tag' => '网站搭建',
                'image' => 'website',
                'icon' => 'globe',
                'available' => true,
                'price' => '100',
                'prerequisites' => ['一台可以联网的电脑', '能够使用的 Coding Agent', '可管理服务器和域名的账号及购买预算'],
                'deliverables' => ['自己的.com域名能打开网站', '首页、另一个页面与导航可用', '完成一次内容修改并重新发布'],
                'lessons' => $this->websiteLessons(),
            ],
            [
                'slug' => 'add-website-support',
                'title' => '给网站接入智能客服',
                'question' => '我想让网站随时回答客户的问题',
                'description' => '整理业务资料与常见问题，让AI回答有依据，让客户随时找到下一步。',
                'outcome' => '一个可用的智能客服入口，能回答常见问题并引导人工联系。',
                'category' => 'build',
                'category_label' => '创造一个作品',
                'tag' => '智能客服',
                'image' => 'support',
                'icon' => 'messages-square',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一个可修改的测试网站', '可用于回答问题的业务资料与常见问答', '可用于测试的智能客服服务', '明确的人工联系方式'],
                'deliverables' => ['整理产品、服务与常见问题，建立可核对的客服资料', '接入聊天入口，让桌面端与手机端都能顺畅提问', '用真实问题测试回答，核对内容与资料是否一致', '遇到不确定或需人工处理的问题，引导客户联系人工', '完成正常与异常流程验收，留下配置与资料维护说明'],
                'lessons' => [],
            ],
            [
                'slug' => 'build-a-miniapp',
                'title' => '100分钟制作小程序',
                'question' => '我想把一个点子做成小程序',
                'description' => '从一个具体需求开始，把页面、数据和交互连接起来。',
                'outcome' => '一个能够运行，并具备提交审核条件的小程序。',
                'category' => 'build',
                'category_label' => '创造一个作品',
                'tag' => '小程序',
                'image' => 'miniapp',
                'icon' => 'smartphone',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一个明确的小程序需求', '对应平台的开发者账号', '开发工具与 Coding Agent'],
                'deliverables' => ['把一个真实需求变成可运行的页面', '连接后端接口与数据', '完成发布前的功能验收'],
                'lessons' => [],
            ],
            [
                'slug' => 'analyze-your-content',
                'title' => '100分钟自媒体创作分析',
                'question' => '我想知道下一条内容该做什么',
                'description' => '让真实账号数据，成为下一次创作的依据。',
                'outcome' => '一份账号诊断报告，以及下一阶段的选题方向。',
                'category' => 'work',
                'category_label' => '解决工作问题',
                'tag' => '内容分析',
                'image' => 'analysis',
                'icon' => 'chart-no-axes-combined',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['可导出的真实账号数据', '一份待分析的数据文件', '能够处理文件的 AI 工具'],
                'deliverables' => ['找出内容表现的关键差异', '识别受众问题与反馈', '形成可以执行的选题清单'],
                'lessons' => [],
            ],
            [
                'slug' => 'plan-your-next-creation',
                'title' => '100分钟用Agent制定创作计划',
                'question' => '我想让Agent帮我找到创作方向',
                'description' => '分析作品、评论与播放数据，找选题、获得建议，把资讯变成创作线索。',
                'outcome' => '一份有依据的创作复盘、选题清单和下一阶段行动计划。',
                'category' => 'work',
                'category_label' => '面向创作者',
                'tag' => '创作者',
                'image' => 'creator',
                'icon' => 'notebook-pen',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一组自己的作品或文稿', '脱敏的评论样本与同周期播放数据', '关注的领域与可核对的资讯来源', '能够处理资料的Agent'],
                'deliverables' => ['分析作品的主题、结构与表达，整理改进空间', '归纳评论中的真实问题、需求与反馈', '比较播放、完播与互动数据，找出表现差异', '结合受众需求与创作目标，形成有依据的选题清单', '获得可执行的创作建议，明确下一步与验收方式', '整理相关资讯，保留来源和日期，筛选可用的创作线索'],
                'lessons' => [],
            ],
            [
                'slug' => 'merge-excel-files',
                'title' => '100分钟批量处理Excel',
                'question' => '我想把100份Excel合成一份报表',
                'description' => '从重复复制粘贴开始，把合并、清洗和汇总交给 AI。',
                'outcome' => '一份核对过的汇总报表，以及可以重复运行的处理脚本。',
                'category' => 'work',
                'category_label' => '解决工作问题',
                'tag' => 'Excel处理',
                'image' => 'spreadsheet',
                'icon' => 'table-2',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一组可用于练习的Excel文件', '明确的合并与汇总规则', '能够处理文件的AI工具'],
                'deliverables' => ['自动合并多份同类表格', '处理重复记录与格式差异', '用原始数据核对汇总结果'],
                'lessons' => [],
            ],
            [
                'slug' => 'create-a-presentation',
                'title' => '100分钟完成一次PPT汇报',
                'question' => '我想把一堆资料变成一份清楚的PPT',
                'description' => '先讲清楚要说什么，再让 AI 协助整理结构与页面。',
                'outcome' => '一份逻辑清晰、可以编辑和演讲的汇报PPT。',
                'category' => 'work',
                'category_label' => '解决工作问题',
                'tag' => 'PPT汇报',
                'image' => 'presentation',
                'icon' => 'presentation',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一份真实的汇报任务', '可用于汇报的资料与数据', '能够编辑PPT的工具'],
                'deliverables' => ['确定汇报目标与叙事顺序', '完成可编辑的页面与图表', '核对事实并准备讲述提纲'],
                'lessons' => [],
            ],
            [
                'slug' => 'build-a-work-tool',
                'title' => '100分钟做一个自己的小工具',
                'question' => '我想做一个省掉重复工作的小工具',
                'description' => '从一个每天都要重复的动作，做出真正能用的小工具。',
                'outcome' => '一个能够输入、处理并导出结果的本地工作工具。',
                'category' => 'build',
                'category_label' => '创造一个作品',
                'tag' => '工作工具',
                'image' => 'work-tool',
                'icon' => 'wrench',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一个具体的重复工作场景', '脱敏的输入样例与预期结果', '能够修改代码的Coding Agent'],
                'deliverables' => ['把需求拆成输入、处理和输出', '完成一个可运行的小工具', '使用正常和异常样例验收结果'],
                'lessons' => [],
            ],
            [
                'slug' => 'build-your-own-software',
                'title' => '100分钟用Agent制作自己的软件',
                'question' => '我想做一款自己用得上的软件',
                'description' => '以下载软件、输入法等为例，让Agent帮你做成能运行的软件原型。',
                'outcome' => '一款在自己电脑运行的软件原型，附核心功能验收清单。',
                'category' => 'build',
                'category_label' => '创造一个作品',
                'tag' => '个人软件',
                'image' => 'software',
                'icon' => 'app-window',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一个自己确实会用到的软件需求', '目标操作系统与测试环境', '核心功能的输入样例和预期结果', '能够修改和运行代码的Agent'],
                'deliverables' => ['选定一个软件方向，明确第一版的核心功能', '把需求拆成界面、输入、处理和输出', '下载软件示例：添加任务、查看进度、取消与失败重试', '输入法示例：文字输入、候选词和上屏流程', '运行并验收所选软件的核心功能，保留源码与使用说明'],
                'lessons' => [],
            ],
            [
                'slug' => 'build-your-own-agent',
                'title' => '制作自己的Agent',
                'question' => '我想做一个能帮我完成具体任务的Agent',
                'description' => '从一个常用任务出发，配好指令、资料与工具，测试执行过程，把自己的Agent真正用起来。',
                'outcome' => '一个能完成指定任务的Agent，附配置与验收清单。',
                'category' => 'build',
                'category_label' => '创造一个作品',
                'tag' => 'Agent制作',
                'image' => 'agent',
                'icon' => 'bot',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一个具体且可验收的任务', '脱敏的输入样例、参考资料与预期结果', '可用于练习的Agent制作工具', '独立测试环境与所需工具的访问范围'],
                'deliverables' => ['选定一个任务，写清输入、输出和完成标准', '配置角色、指令与参考资料，让Agent理解任务', '接入任务所需工具，明确可执行动作与人工确认边界', '测试正常、缺少信息与工具失败场景，核对执行结果', '保存可复用的配置与测试样例，留下启动和维护说明'],
                'lessons' => [],
            ],
            [
                'slug' => 'remix-a-game',
                'title' => 'AI爆改游戏',
                'question' => '我想把小游戏改成自己的玩法',
                'description' => '让Agent读懂小游戏，修改玩法、关卡、画面和效果，做出自己的版本。',
                'outcome' => '一个可运行、玩法有变化的小游戏，附修改记录与验收清单。',
                'category' => 'build',
                'category_label' => '创造一个作品',
                'tag' => '游戏改造',
                'image' => 'game',
                'icon' => 'gamepad-2',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['自己制作或允许修改的小游戏源码', '能运行原版游戏的测试环境', '一个明确的玩法或风格改造想法', '能够修改和运行代码的Agent'],
                'deliverables' => ['让Agent读懂项目，完成原版游戏试玩', '明确改造目标，把玩法变化拆成可执行任务', '调整规则、角色、关卡和难度，试玩核心流程', '修改界面、画面与交互效果，形成自己的风格', '对照改造目标验收结果，保留修改记录与可运行版本'],
                'lessons' => [],
            ],
            [
                'slug' => 'organize-your-files',
                'title' => '100分钟整理自己的文件',
                'question' => '我想把杂乱文件整理得一目了然',
                'description' => '建立适合自己的命名和分类规则，让文件有处可找。',
                'outcome' => '一个清晰的文件目录，以及可核对、可撤回的整理记录。',
                'category' => 'work',
                'category_label' => '解决工作问题',
                'tag' => '文件整理',
                'image' => 'files',
                'icon' => 'folder-open',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一份待整理目录的副本', '自己认可的命名与分类规则', '能够操作本地文件的AI工具'],
                'deliverables' => ['先预览整理方案，再执行变更', '批量命名并建立清晰目录', '核对文件数量并保留撤回记录'],
                'lessons' => [],
            ],
            [
                'slug' => 'write-a-research-report',
                'title' => '100分钟完成一份调研报告',
                'question' => '我想把零散资料变成有依据的报告',
                'description' => '带着一个明确问题收集资料，把事实、判断和结论分清楚。',
                'outcome' => '一份有来源、有结论，并说明信息局限的调研报告。',
                'category' => 'work',
                'category_label' => '解决工作问题',
                'tag' => '调研报告',
                'image' => 'report',
                'icon' => 'file-text',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一个可回答的调研问题', '允许使用的资料与来源范围', '能够阅读资料的AI工具'],
                'deliverables' => ['按问题建立资料与证据清单', '区分已知事实和待验证判断', '输出带来源的报告与下一步建议'],
                'lessons' => [],
            ],
            [
                'slug' => 'build-personal-digital-assets',
                'title' => '如何建立个人数字资产',
                'question' => '我想把作品和资料变成自己的长期积累',
                'description' => '让Agent帮你盘点作品、文档、代码与账号入口，建立能保存、能迁移、能持续更新的个人资产库。',
                'outcome' => '一套自己的数字资产目录，附备份、恢复与持续维护清单。',
                'category' => 'build',
                'category_label' => '创造一个作品',
                'tag' => '数字资产',
                'image' => 'digital-assets',
                'icon' => 'folder-archive',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一组自己的作品、文档或代码样本', '已有网站、域名和平台账号的入口清单', '可用于练习的本地目录与独立备份位置', '能够整理文件的Agent'],
                'deliverables' => ['盘点作品、文档、代码、域名与账号入口，建立资产清单', '保留原始文件、来源与版本，建立统一命名和分类目录', '区分公开作品与私人资料，整理自己的展示入口', '导出一组平台内容，建立独立备份并演练恢复', '完成查找、迁移与恢复验收，制定持续更新清单'],
                'lessons' => [],
            ],
            [
                'slug' => 'build-a-knowledge-library',
                'title' => '100分钟建立自己的资料库',
                'question' => '我想从自己的资料里快速找到答案',
                'description' => '整理已有文档，让 AI 帮你定位信息，并回到原文核对。',
                'outcome' => '一个可检索的个人资料库，以及带原文出处的回答流程。',
                'category' => 'work',
                'category_label' => '解决工作问题',
                'tag' => '资料检索',
                'image' => 'knowledge',
                'icon' => 'book-open',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一组允许用于练习的文档', '几个真实的查找问题', '能够阅读和检索文件的AI工具'],
                'deliverables' => ['建立可维护的资料索引', '用真实问题测试检索效果', '每条回答都能回到原始来源核对'],
                'lessons' => [],
            ],
            [
                'slug' => 'turn-meetings-into-actions',
                'title' => '100分钟把会议记录变成行动清单',
                'question' => '我想让会议结束后每件事都有下一步',
                'description' => '从一份会议记录开始，整理决定、负责人和待确认事项。',
                'outcome' => '一份经过核对的会议纪要，以及明确责任与时间的行动清单。',
                'category' => 'work',
                'category_label' => '解决工作问题',
                'tag' => '会议纪要',
                'image' => 'meeting',
                'icon' => 'list-checks',
                'available' => false,
                'price' => '100',
                'prerequisites' => ['一份允许处理的会议记录', '清楚的参会角色和任务背景', '能够整理文本的AI工具'],
                'deliverables' => ['区分讨论、决定与待确认事项', '整理负责人、截止时间和下一步', '回到会议原文核对每项行动'],
                'lessons' => [],
            ],
        ];
    }

    public function find(string $slug): array
    {
        return collect($this->all())->firstWhere('slug', $slug) ?? abort(404);
    }

    public function findLesson(array $series, string $slug): array
    {
        return collect($series['lessons'])->firstWhere('slug', $slug) ?? abort(404);
    }

    public function canPreview(array $series, array $lesson): bool
    {
        return app(CourseAccessService::class)->canPreviewLegacy($series, $lesson);
    }

    public function previewContent(array $series, array $lesson): array
    {
        abort_unless($this->canPreview($series, $lesson), 403);

        return WebsiteFirstLessonContent::current();
    }

    private function websiteLessons(): array
    {
        return array_map(fn (array $lesson, int $index) => [
            'slug' => $lesson['slug'], 'title' => $lesson['title'], 'summary' => $lesson['goal'],
            'goal' => $lesson['goal'], 'score' => $lesson['score'], 'points' => $lesson['points'],
            'minutes' => $lesson['minutes'], 'is_free' => $index === 0,
        ], WebsiteSetupLessons::all(), array_keys(WebsiteSetupLessons::all()));
    }
}
