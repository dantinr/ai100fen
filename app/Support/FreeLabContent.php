<?php

namespace App\Support;

class FreeLabContent
{
    public static function courses(): array
    {
        $html = <<<'HTML'
<!doctype html>
<html lang="zh-CN">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>小林的个人介绍</title>
<style>
body{margin:0;padding:32px 20px;background:#fff9e8;color:#222;font:18px/1.7 system-ui}
main{max-width:680px;margin:auto}h1{font-size:clamp(32px,8vw,56px)}
section{padding:20px;background:white;border:2px solid;margin:24px 0}
a{color:#734300}a:focus-visible{outline:3px solid #734300;outline-offset:4px}
</style>
<main>
<p>你好，欢迎认识我。</p><h1>我是小林</h1>
<p>我喜欢把复杂的事情讲清楚，也在尝试用 AI 完成自己的作品。</p>
<section><h2>我正在做什么</h2><ul><li>记录一个小项目的成长</li><li>整理自己的作品与想法</li></ul></section>
<section><h2>联系我</h2><p>欢迎交流项目与想法。</p><a href="mailto:hello@example.com">给我写邮件</a></section>
<p>这是我的第一份网页作品。</p>
</main>
</html>
HTML;
        $python = <<<'PY'
from pathlib import Path
import csv
from decimal import Decimal, InvalidOperation

root = Path(__file__).resolve().parent
files = sorted((root / "input").glob("*.csv"))
if not files:
    raise SystemExit("请先在 input 文件夹放入两份示例 CSV。")
rows, seen, total = [], set(), Decimal("0")
for path in files:
    with path.open(encoding="utf-8-sig", newline="") as source:
        reader = csv.DictReader(source)
        if reader.fieldnames != ["order_id", "amount"]:
            raise SystemExit(f"列名不一致：{path.name}")
        for row in reader:
            if not row["order_id"] or row["order_id"] in seen:
                raise SystemExit(f"空编号或重复订单：{path.name}")
            try:
                amount = Decimal(row["amount"])
            except (InvalidOperation, TypeError):
                raise SystemExit(f"金额格式错误：{path.name}")
            if not amount.is_finite() or amount < 0 or amount != amount.quantize(Decimal("0.01")):
                raise SystemExit(f"无效金额或超过两位小数：{path.name}")
            seen.add(row["order_id"])
            rows.append(row)
            total += amount
output = root / "result"
if output.exists():
    raise SystemExit("result 已存在，请先检查结果并将旧结果文件夹改名，避免覆盖。")
output.mkdir()
with (output / "merged.csv").open("w", encoding="utf-8-sig", newline="") as target:
    writer = csv.DictWriter(target, fieldnames=["order_id", "amount"])
    writer.writeheader()
    writer.writerows(rows)
summary = f"文件数：{len(files)}\n订单数：{len(rows)}\n总金额：{total:.2f}\n"
(output / "summary.txt").write_text(summary, encoding="utf-8")
print(summary)
PY;
        $experiment = <<<'MD'
# 我的 Prompt 对照实验

目标：将商品资料转成三字段 JSON，缺失价格不猜测。
模型/工具：____    日期：____
固定设置（模型版本、温度等；无法设置时注明）：____
每次使用独立新对话，仅粘贴一份 Prompt 和一条样本。

## Prompt A
把以下商品资料整理成 JSON，包含名称、价格和是否有货：

## Prompt B
只输出合法 JSON，不使用 Markdown 或解释。键固定为 name、price、in_stock。
name 为字符串；price 为数字，原文未给价格时为 null；in_stock 为布尔值。
仅依据原文，不推测价格或库存。资料：

## 固定样本与期望结果
1. 红色杯子，价格 29.9 元，有货。
   {"name":"红色杯子","price":29.9,"in_stock":true}
2. 蓝色笔记本，暂无库存，未标价格。
   {"name":"蓝色笔记本","price":null,"in_stock":false}
3. 木质书签，12 元，现货。
   {"name":"木质书签","price":12,"in_stock":true}

## 原始输出（六份全部保留）
A1：____    A2：____    A3：____
B1：____    B2：____    B3：____

## 人工评分（每项通过记 1，否则 0）
| 输出 | 合法 JSON 且只有三个指定键 | 类型符合约定 | 数值及库存忠于样本 | 总分 /3 |
| --- | --- | --- | --- | --- |
| A1 | | | | |
| A2 | | | | |
| A3 | | | | |
| B1 | | | | |
| B2 | | | | |
| B3 | | | | |

A 总分 /9：____   B 总分 /9：____
结论（A 更合适 / B 更合适 / 暂不能区分）：____
引用一份实际输出解释判断：____
局限：只有三个样本、一次运行，不能推广到所有任务或模型。
下一步（另选样本或重复实验）：____
MD;

        return [
            [
                'slug' => 'personal-intro-page', 'title' => '做一个能打开的个人介绍网页', 'category' => 'create', 'minutes' => 10,
                'user_intent' => '我想有一份可以展示自己的网页作品，但不知道从哪里开始。',
                'final_outcome' => '一份可在浏览器打开、内容属于你自己的 index.html。',
                'completion_criteria' => ['本地双击 index.html 可打开，断网刷新仍能阅读。', '姓名、介绍和作品已替换为自己愿意公开的内容，没有示例占位。', '窄窗口可阅读，联系链接指向你确认过的地址。'],
                'agent_role' => ['生成单文件网页并按要求修改内容与布局。', '解释如何打开文件，协助排查打不开和排版问题。'],
                'human_judgment_required' => ['选择愿意展示的内容，不填家庭地址等私人信息。', '亲自打开页面并检查文字、布局与联系地址。'],
                'recommendation_keywords' => ['网页', '网站', '个人介绍', '主页', '作品集', '展示', '名片', '自我介绍', 'html', '创作'],
                'lesson' => [
                    'slug' => 'make-and-check', 'title' => '生成、修改并验收你的网页',
                    'intro' => '用你已有的 AI 对话工具或文件 Agent 即可。不需要服务器、域名或付费服务；这次交付的是本地网页，不包含上线。',
                    'goal' => '把一份示例改成属于你的网页，并在真实浏览器中打开它。',
                    'steps' => [
                        ['title' => '1. 定好内容 · 2分钟', 'body' => '写下一个公开昵称、一句话介绍、两件正在做的事和你愿意公开的联系地址。先用虚构资料练习也可以，但须标注。'],
                        ['title' => '2. 让 Agent 生成文件 · 3分钟', 'body' => '复制下面的 Prompt，补齐方括号内容。能操作文件的 Agent 直接保存 index.html；普通对话工具返回 HTML 后，用纯文本编辑器另存为 UTF-8 的 index.html，确认不是 index.html.txt。'],
                        ['title' => '3. 打开并修改 · 3分钟', 'body' => '双击文件，用浏览器查看。让 Agent 根据你的具体反馈修改，例如“标题小一点”“手机宽度不要横向滚动”。下面提供可下载的完整起点，可在生成失败时继续。'],
                        ['title' => '4. 验收成品 · 2分钟', 'body' => '检查是否仍有“小林”和示例邮箱，拉窄窗口，断网刷新，点击联系链接检查地址。没有邮件客户端也可查看链接地址。逐项通过后再勾选验收。'],
                    ],
                    'prompt' => '请生成一份可直接双击打开的个人介绍网页 index.html，UTF-8，所有样式内嵌，不加载远程字体、脚本或图片。包含昵称[你的昵称]、介绍[一句话]、正在做的两件事[事项]、联系地址[公开邮箱]。适配手机，文字清晰，链接可聚焦。不要添加分析追踪、表单或未经我确认的信息。能写文件就保存，否则返回完整 HTML，并说明如何另存为 .html。',
                    'code' => $html, 'code_filename' => 'index.html',
                    'resources' => [['name' => 'index.html', 'label' => '完整网页起点', 'content' => $html]],
                ],
            ],
            [
                'slug' => 'merge-csv-report', 'title' => '把几份 CSV 合成一张汇总表', 'category' => 'solve', 'minutes' => 15,
                'user_intent' => '订单分散在几份表里，我想避免手工复制并核对总金额。',
                'final_outcome' => '一份包含六笔示例订单的 merged.csv，以及总金额 700.00 的 summary.txt。',
                'completion_criteria' => ['merged.csv 有六笔订单，编号 1 至 6，无遗漏或重复。', 'summary.txt 写明文件数 2、订单数 6、总金额 700.00。', '两份输入文件未被改动，已人工抽查至少两笔订单。'],
                'agent_role' => ['读取示例表结构，生成并运行合并脚本。', '检查重复、列名和金额格式，协助解释报错。'],
                'human_judgment_required' => ['核对列含义与金额单位，不把私人客户数据发送到外部 AI。', '对照原始表抽查，决定结果是否可信。'],
                'recommendation_keywords' => ['csv', '表格', '汇总', '订单', '金额', '合并', '数据', '报表', 'excel', '重复', '解决'],
                'lesson' => [
                    'slug' => 'merge-and-verify', 'title' => '合并示例订单并核对结果',
                    'intro' => '使用两份虚构订单练习。需要可运行 Python 3 的电脑或 Agent 工作区；脚本只使用标准库。这里只处理列名一致的 CSV，不宣称能直接合并任意 Excel。',
                    'goal' => '得到可复查的汇总文件，用原始数据验证它没有丢单或算错。',
                    'steps' => [
                        ['title' => '1. 放好样例 · 3分钟', 'body' => '新建 csv-lab 文件夹，在里面新建 input。下载下面两份 CSV，放入 input，保留原名。只使用这些虚构样例，不用公司的原始客户表。'],
                        ['title' => '2. 请 Agent 执行 · 5分钟', 'body' => '将 Prompt 交给能运行代码的 Agent。也可以下载 merge.py 放在 csv-lab 内，让 Agent 在该目录运行 python merge.py（部分系统为 python3）。如果没有 Python，先让 Agent 帮你确认已有运行环境，勿盲目安装不明软件。'],
                        ['title' => '3. 看结果，不只看回复 · 4分钟', 'body' => '打开 result/merged.csv 与 result/summary.txt。六笔金额应为 120、80、200、50、150、100，总计 700.00；抽查订单 2 与 5 是否分别为 80、150。'],
                        ['title' => '4. 复核输入 · 3分钟', 'body' => '确认 input 中的两份表仍各有三笔订单。脚本遇到重复编号、列名不同或无效金额会停止。再次运行前先检查并重命名旧 result 文件夹，脚本不会覆盖旧结果；真实业务另需确认退单、币种和去重规则。'],
                    ],
                    'prompt' => '请在 csv-lab 工作目录合并 input 中的 orders-a.csv 与 orders-b.csv。列固定为 order_id,amount，编号唯一、金额使用十进制定点数。不要修改输入，不联网，不覆盖已有 result。先检查列名、重复编号和金额，再生成 result/merged.csv 和 summary.txt。用 Python 标准库执行，最后报告实际生成文件路径、6笔订单和总额700.00的核对结果；如不能执行要明确告诉我，不要假装已生成。',
                    'code' => $python, 'code_filename' => 'merge.py',
                    'resources' => [
                        ['name' => 'orders-a.csv', 'label' => '示例订单 A', 'content' => "order_id,amount\n1,120\n2,80\n3,200\n"],
                        ['name' => 'orders-b.csv', 'label' => '示例订单 B', 'content' => "order_id,amount\n4,50\n5,150\n6,100\n"],
                        ['name' => 'merge.py', 'label' => '可运行合并脚本', 'content' => $python],
                    ],
                ],
            ],
            [
                'slug' => 'compare-prompts', 'title' => '用对照实验选出更合适的 Prompt', 'category' => 'explore', 'minutes' => 15,
                'user_intent' => '我想知道具体约束是否让商品资料整理更可靠，而不是只凭感觉选 Prompt。',
                'final_outcome' => '一份保留六次原始输出、评分和有证据结论的 experiment.md。',
                'completion_criteria' => ['同一模型、同样三个样本分别测试 A / B，各次在独立新对话中运行，保存六份原始输出。', '按预设三项规则人工评分并汇总 A / B 的分数。', '结论引用实际输出并说明样本限制；平局或失败有证据也算完成。'],
                'agent_role' => ['执行结构化资料整理，产生可比较的原始输出。', '协助整理实验记录和评分差异，不替人作最终判断。'],
                'human_judgment_required' => ['固定模型与样本，检查实验是否公平。', '独立核查 JSON 与事实，用证据判断并保留不确定性。'],
                'recommendation_keywords' => ['prompt', '提示词', '对照', '实验', '验证', '比较', '准确', '可靠', '模型', '探索', 'json'],
                'lesson' => [
                    'slug' => 'test-and-conclude', 'title' => '完成一次公平对照并写下结论',
                    'intro' => '使用你已有的 AI 对话工具，不要求某一家模型。样例不含私人资料。实验可能得出 B 更好、A 更好或暂不能区分；都需要实际证据。',
                    'goal' => '把“这个 Prompt 好像更好”变成别人可以复查的实验结论。',
                    'steps' => [
                        ['title' => '1. 固定规则 · 3分钟', 'body' => '下载 experiment.md，记录模型、日期和可设置的参数。先读三条样本和期望结果，评分规则固定为：JSON 合法且键正确、类型正确、事实正确；每项 1 分。不要看到结果后修改规则。'],
                        ['title' => '2. 执行六次 · 6分钟', 'body' => '对每条样本分别运行 Prompt A 和 B。每次开独立新对话，粘贴一个 Prompt 和一个样本；模型和设置保持相同。原始输出直接复制到记录中，不修正模型犯的错。'],
                        ['title' => '3. 人工评分 · 4分钟', 'body' => '按模板逐份评分。合法 JSON 可交给 Agent 或 JSON 校验工具核验，但必须对照期望结果检查价格、库存和缺失值。A / B 各最多 9 分；为每个不通过项留下具体原因。'],
                        ['title' => '4. 写可复查结论 · 2分钟', 'body' => '比较总分并引用一份输出说明判断。如果平局就写暂不能区分；两者都失败也如实记录。注明只有三个样本、一次运行，不能外推到所有模型。写下下一次要增加的样本或重复实验。'],
                    ],
                    'prompt' => "Prompt A：\n把以下商品资料整理成 JSON，包含名称、价格和是否有货：\n[粘贴一条样本]\n\nPrompt B：\n只输出合法 JSON，不使用 Markdown 或解释。键固定为 name、price、in_stock。name 为字符串；price 为数字，原文未给价格时为 null；in_stock 为布尔值。仅依据原文，不推测价格或库存。资料：\n[粘贴同一条样本]\n\nA 和 B 分别在独立的新对话中执行，不要一起发送。",
                    'code' => null, 'code_filename' => null,
                    'resources' => [['name' => 'experiment.md', 'label' => '样本、评分与结论模板', 'content' => $experiment]],
                ],
            ],
        ];
    }
}
