<?php

namespace App\Support;

/** Curated preview content, independent of future persisted courses and entitlements. */
class FrontendCatalog
{
    public function all(): array
    {
        return [
            [
                'slug' => 'build-a-website',
                'title' => '100分钟搭建网站',
                'question' => '我想拥有自己的网站',
                'description' => '从一个公网 IP 开始，让自己的想法真正出现在互联网上。',
                'outcome' => '一个可访问、有域名和 HTTPS、能够备份恢复的网站。',
                'category' => 'build',
                'category_label' => '创造一个作品',
                'tag' => '网站搭建',
                'image' => 'website',
                'icon' => 'globe',
                'available' => true,
                'price' => '100',
                'prerequisites' => ['一台可以联网的电脑', '能够使用的 Coding Agent', '自己的测试服务器（按需准备）'],
                'deliverables' => ['用自己的域名访问网站', '完成 HTTPS 与移动端检查', '保留一份可恢复的网站备份'],
                'lessons' => $this->websiteLessons(),
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
        return $series['available'] && $lesson['is_free'];
    }

    public function previewContent(array $series, array $lesson): array
    {
        abort_unless($this->canPreview($series, $lesson), 403);

        return [
            'goal' => '让一个 hello.html 页面，通过服务器的公网 IP 被浏览器访问。',
            'intro' => '网站的第一步，是把一个简单页面放到互联网上。这一课先完成最小结果：输入公网 IP，就能看到自己的页面。',
            'steps' => [
                ['title' => '确认你的测试环境', 'body' => '准备自己的测试服务器，记录公网 IP，并确认可以安全登录。首次配置请使用测试环境，妥善保管登录凭据，不要把密码或私钥粘贴到公开对话。'],
                ['title' => '先把目标告诉 Agent', 'body' => '复制本节 Prompt。请 Agent 先说明计划、需要修改的文件与服务，再确认执行。目标只有一个：部署 hello.html，并通过公网 IP 访问。'],
                ['title' => '创建并部署第一个页面', 'body' => '让 Agent 检查现有服务，把示例 HTML 放到正确的网站目录。端口或服务需要变更时，先看清影响再确认，不要覆盖现有网站。'],
                ['title' => '打开浏览器，验收结果', 'body' => '在地址栏输入 http://你的公网IP，确认页面能打开。如果失败，让 Agent 先检查服务、端口和日志，给出原因后再修改。'],
            ],
            'prompt' => "我想把一个 hello.html 页面部署到我自己的测试服务器，并通过公网 IP 访问。\n\n请先检查环境，说明你的计划，以及需要修改的文件和服务。不要覆盖已有网站，不要输出密码或私钥。涉及安装软件、变更端口或服务时，先等我确认。\n\n页面内容是：Hello，世界。这是我的第一个网站。\n\n完成后请告诉我访问地址，并给出浏览器验收步骤。如果访问失败，先诊断，再提出修改建议。",
            'code' => "<!doctype html>\n<html lang=\"zh-CN\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n  <title>我的第一个网站</title>\n</head>\n<body>\n  <h1>Hello，世界。</h1>\n  <p>这是我的第一个网站。</p>\n</body>\n</html>",
            'checks' => ['通过公网 IP 能打开页面', '页面显示了我自己的内容', '我能找到网站文件所在的位置'],
        ];
    }

    private function websiteLessons(): array
    {
        $outline = [
            ['server-and-ip', '服务器与 IP', '让第一个页面通过公网 IP 被访问。', '认识公网 IP，把 hello.html 部署到测试服务器。'],
            ['domain', '域名', '用一个好记的名字找到你的网站。', '认识 DNS 和 A 记录，将自己的域名指向服务器。'],
            ['https', 'HTTPS', '让浏览器安全地打开你的网站。', '配置证书与 HTTPS，验收安全连接和跳转。'],
            ['first-website', '第一个网站', '从一张页面，变成一个有结构的网站。', '完成首页、关于页、导航和移动端布局。'],
            ['open-source', '使用开源程序', '站在成熟项目的基础上开始。', '理解开源程序，用 Agent 安装并验证基础功能。'],
            ['customize', '网站 DIY', '让网站有自己的内容与样子。', '修改标识、导航、颜色与内容，检查手机端。'],
            ['architecture', '网站结构', '知道一次访问经过了哪些地方。', '认识浏览器、DNS、Web 服务、应用与数据库。'],
            ['troubleshooting', '网站出问题怎么办', '先找到原因，再动手修改。', '通过状态码、服务与日志定位访问异常。'],
            ['backup', '备份与恢复', '不只保存一份文件，还要能恢复。', '备份网站与数据，在测试环境完成恢复演练。'],
            ['acceptance', '完整验收', '把一个真实网站，完整地做成。', '检查域名、HTTPS、页面、移动端与恢复能力。'],
        ];

        return array_map(fn (array $item, int $index) => [
            'slug' => $item[0], 'title' => $item[1], 'summary' => $item[2],
            'goal' => $item[3], 'score' => ($index + 1) * 10,
            'minutes' => 10, 'is_free' => $index === 0,
        ], $outline, array_keys($outline));
    }
}
