<?php

namespace App\Support;

final class WebsiteSetupLessons
{
    public static function all(): array
    {
        $combined = WebsiteFirstLessonContent::combined();
        $definitions = [
            [
                'slug' => 'server-and-ip', 'title' => '购买服务器', 'role' => '人',
                'goal' => '拥有一台自己可登录、有公网 IP 的服务器，并确认配置和费用。',
                'intro' => '你负责注册账号、选择配置、支付购买，并亲自确认能登录服务器。',
                'steps' => [
                    ['title' => '注册账号并准备费用', 'body' => '注册阿里云账号，完成所需认证与充值，确认本次预算。'],
                    ['title' => '选择配置并购买', 'body' => '选择有公网 IP 的 Linux 服务器，核对地域、配置、费用和计费方式后下单，记录公网 IP。'],
                    ['title' => '用 SSH 登录', 'body' => 'root 是服务器管理员账号。通过控制台设置首次登录方式，再用 SSH 或控制台终端登录自己的服务器，确认当前主机。'],
                ],
                'prompt' => "我准备购买一台有公网 IP 的 Linux 测试服务器。预算：[填写]；用途：搭建自己的.com网站。\n请帮我核对配置、地域、计费方式及预计费用，解释root和SSH登录所需信息，给出登录后的只读核对步骤。购买、支付及凭据设置由我执行；不要替我下单，不要索取或输出密码、私钥。",
                'checks' => ['我已确认服务器配置、计费方式和费用', '服务器已运行，我已记录公网 IP', '我能通过 SSH 或控制台登录并确认是自己的服务器'],
            ],
            [
                'slug' => 'authorize-agent', 'title' => '授权Agent管理服务器', 'role' => 'Agent',
                'goal' => 'Agent 能用公钥连接已购买的服务器，Nginx 可从公网 IP 访问。',
                'intro' => '你提供首次连接和明确授权，Agent 配置公钥登录并安装 Nginx。',
                'steps' => [
                    ['title' => '配置公钥登录', 'body' => '利用上一课已建立的连接，检查本地密钥，必要时生成专用密钥，将公钥追加到服务器授权文件，保留原有授权并验证连接。'],
                    ['title' => '安装和检查 Nginx', 'body' => '人确认执行范围后，让 Agent 安装 Nginx 并检查服务、监听端口和站点目录。'],
                    ['title' => '从浏览器访问 IP', 'body' => '在浏览器打开 http://<公网IP>；需要配置安全组时由人操作或明确授权已有工具，不要关闭防护。'],
                ],
                'prompt' => "服务器 IP：[填写]；已可用 SSH 连接：[填写本地配置名称]。\n请检查已有密钥，在我提供的首次连接基础上配置公钥登录，保留已有授权并验证。确认计划后安装并配置 Nginx，检查公网 HTTP 访问；需要设置云安全组时给我具体规则，由我操作或明确授权。请总结连接方式、Nginx状态和访问地址，不输出密码或私钥。",
                'checks' => ['Agent 使用公钥能成功连接服务器，原有登录仍可用', 'Nginx 已运行并能响应 HTTP 请求', '我在服务器之外的浏览器通过公网 IP 看到了 Nginx 页面'],
            ],
            [
                'slug' => 'first-website', 'title' => '制作第一个页面', 'role' => 'Agent',
                'goal' => '做出一张包含自己内容的页面，本地预览和公网 IP 访问均可用。',
                'intro' => '你说明想展示什么，Agent 在本地制作页面；你确认预览后再让它发布。',
                'steps' => [
                    ['title' => '说明页面需求', 'body' => '给出网站用途、标题和真实内容，让 Agent 在本地项目目录制作 hello.html。'],
                    ['title' => '本地预览并确认', 'body' => '在浏览器预览，检查内容与手机布局，提出修改并确认。'],
                    ['title' => '部署并用 IP 验收', 'body' => '发布到上一课确认的站点目录，打开 http://<公网IP>/hello.html，核对页面并记录本地和服务器路径。'],
                ],
                'prompt' => "网站用途：[填写]；标题与内容：[填写]；本地目录：[填写]；服务器连接：[填写]。\n请制作 hello.html，让我先本地预览并确认，再部署到已确认的 Nginx 站点目录。不要覆盖已有站点。完成后给出公网 IP 访问地址、本地与服务器文件路径，请我核对内容和手机布局。",
                'checks' => ['本地页面能打开并显示我确认的内容', '公网 IP 的 /hello.html 能打开同一个页面', '我知道页面在本地与服务器的路径，并已检查手机布局'],
                'code' => WebsiteFirstLessonContent::original()['code'], 'code_filename' => 'hello.html',
            ],
            [
                'slug' => 'domain', 'title' => '域名解析', 'role' => '人',
                'goal' => '自己的.com域名通过 A记录指向服务器，浏览器能用域名打开页面。',
                'intro' => '你管理域名和 DNS，Agent 协助配置站点；最终由你验证域名访问。',
                'steps' => [
                    ['title' => '准备自己管理的域名', 'body' => '选择并注册自己的.com域名，或使用已有域名，确认可以修改其 DNS。注册和支付由人执行。'],
                    ['title' => '填写 A记录', 'body' => '@ 指向服务器公网 IP；需要 www 时增加 www 的 A记录。.com 是顶级域名，example.com 是二级域名，www.example.com 是它下面的子域名。'],
                    ['title' => '配置站点并验收域名', 'body' => '把域名交给 Agent 配置 Nginx，等待解析生效后打开 http://<域名>/hello.html，核对页面。'],
                ],
                'prompt' => "我的.com域名：[填写]；服务器 IP：[填写]；现有页面：/hello.html。\n请给出 @ 和可选 www 的 A记录填法，等我在 DNS 控制台设置后，再配置 Nginx 站点域名并检查配置。不要假称已修改 DNS。给出解析验证方法和域名访问地址，失败时分别检查 DNS、站点和访问结果。HTTPS属于后续学习方向，本课程先验收HTTP访问。",
                'checks' => ['我能管理域名 DNS，并核对 @ 与需要的子域名记录', '域名解析结果指向自己的服务器公网 IP', '我从浏览器通过自己的.com域名打开了上一课的页面'],
            ],
            [
                'slug' => 'customize', 'title' => '丰富网站内容', 'role' => '人+Agent',
                'goal' => '网站有首页、另一个页面及可用导航，并完成一次内容修改和重新发布。',
                'intro' => '你确定网站内容和修改要求，Agent 制作更多页面并部署，你逐项验收。',
                'steps' => [
                    ['title' => '确定更多页面的内容', 'body' => '给出首页与关于页等页面的真实内容和导航要求。'],
                    ['title' => '制作并发布', 'body' => '让 Agent 生成 index.html、about.html 和导航，本地预览确认后发布，检查域名下首页和链接。'],
                    ['title' => '完成一次修改', 'body' => '提出一个具体修改，重新预览、发布并核对在线生效，记录地址、文件位置和实际改动。'],
                ],
                'prompt' => "现有网站域名：[填写]；本地与服务器目录：[填写]；新增页面内容：[填写]。\n请制作首页、关于页及页面导航。先让我本地预览确认，再发布。然后按我的具体修改要求：[填写]，完成一次重新发布。请验证域名下各页与链接，提供实际改动和验证结果，由我最终验收。",
                'checks' => ['域名能打开包含自己内容的首页', '导航能进入至少另一个页面并返回', '一次内容修改已重新发布并在线生效，我保留了改动记录', '我已按整门课程完成标准核对域名访问、页面内容、导航与重新发布，确认网站确实可用'],
            ],
        ];
        foreach ($definitions as $index => &$definition) {
            $number = $index + 1;
            if (! preg_match('/^## '.$number.'、[^\n]+\n(.*?)(?=^## [1-5]、|\z)/ms', $combined['content'], $section)) {
                throw new \RuntimeException('Missing section '.$number);
            }
            $definition['content'] = '## '.$definition['title'].' ['.$definition['role']."]\n\n".trim($section[1]);
            $definition['content'] = str_replace('完成本节10分', '完成整门课程验收', $definition['content']);
            $definition['content'] = str_replace('HTTPS 在后续课时完成', 'HTTPS 属于后续学习方向，本课程先验收 HTTP 访问', $definition['content']);
            $definition['intro'] .= ' 本节约2分钟为演示与核心操作目标；账号注册、购买、DNS生效及平台审核等外部等待另计。';
            $definition['minutes'] = 2;
            $definition['points'] = 20;
            $definition['score'] = $number * 20;
            $definition['code'] ??= null;
            $definition['code_filename'] ??= null;
        }
        unset($definition);
        return $definitions;
    }
}
