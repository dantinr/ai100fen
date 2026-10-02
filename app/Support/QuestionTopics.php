<?php

namespace App\Support;

/** Editorial questions, not measured rankings or user submissions. */
class QuestionTopics
{
    public const REVIEWED_ON = '2026-10-02';

    public static function sources(): array
    {
        return [
            'work' => ['title' => 'OpenAI · Agents 如何改变工作', 'url' => 'https://openai.com/index/how-agents-are-transforming-work/'],
            'autonomy' => ['title' => 'Anthropic · Agent 的实际自主性', 'url' => 'https://www.anthropic.com/research/measuring-agent-autonomy'],
            'harness' => ['title' => 'OpenAI · 长任务、工具与多 Agent', 'url' => 'https://openai.com/index/introducing-the-agents-api/'],
            'context' => ['title' => 'Anthropic · Agent 上下文工程', 'url' => 'https://www.anthropic.com/engineering/effective-context-engineering-for-ai-agents'],
            'trust' => ['title' => 'Anthropic · 可信 Agent 的实践', 'url' => 'https://www.anthropic.com/research/trustworthy-agents'],
        ];
    }

    public static function all(): array
    {
        return [
            'make-software' => [
                'label' => '不会编程，也能做软件？', 'tone' => 'highlight',
                'question' => '没有编程基础，能让 Agent 做出一个真正能用的小软件吗？',
                'context' => 'Coding Agent 正从写代码走向运行、修改和验证。第一次动手时，关键是把范围收小，并亲自检查结果。',
                'step' => '先做一页个人介绍网页：写清要展示什么，让 Agent 生成文件，再用浏览器打开验收。',
                'category' => 'create', 'course' => 'build-your-own-software', 'source' => 'work',
            ],
            'automate-work' => [
                'label' => 'Agent 能替我做哪些工作？', 'tone' => 'success',
                'question' => '哪些重复工作可以交给 Agent，哪些判断还应该自己做？',
                'context' => 'Agent 的应用已延伸到文件处理、资料整理和分析。先拆出一个明确步骤，比直接交出整个岗位更容易验证效果。',
                'step' => '挑一项重复任务，例如合并几份表格；约定输入、输出和核对方法，先用脱敏样本试一次。',
                'category' => 'solve', 'course' => 'merge-excel-files', 'source' => 'work',
            ],
            'chat-or-agent' => [
                'label' => 'AI 聊天 ≠ Agent？', 'tone' => 'plain',
                'question' => '让 AI 给建议，和让 Agent 把事情做成，有什么不同？',
                'context' => '聊天通常给出文字答案；Agent 还可以调用工具、操作环境并检查结果。先确认它实际获得了哪些能力。',
                'step' => '用同一个小任务做对照：一次只要建议，一次要求生成可打开的文件，比较最终拿到的结果。',
                'category' => 'explore', 'course' => 'build-your-own-agent', 'source' => 'autonomy',
            ],
            'long-tasks' => [
                'label' => '交代完，它能自己做多久？', 'tone' => 'plain',
                'question' => 'Agent 能持续做一个长任务，还是需要我不断提醒？',
                'context' => '长任务需要保存中间结果、管理上下文，并在必要时向人确认。运行时间长，并不等于任务已经可靠完成。',
                'step' => '把任务拆成三个阶段，每阶段留下文件或记录；约定何时停下来确认，并检查中断后是否能继续。',
                'category' => 'explore', 'course' => 'build-your-own-agent', 'source' => 'harness',
            ],
            'multi-agent' => [
                'label' => '多个 Agent，一定更强吗？', 'tone' => 'highlight',
                'question' => '一个 Agent 做完，和多个 Agent 分工，哪种更适合我的任务？',
                'context' => '多 Agent 可以分工，但也要协调上下文与交接。先看任务能否独立拆分，再比较效果和成本。',
                'step' => '用同一份脱敏资料分别做单人式分析和分工分析，按准确性、时间与费用记录差异。',
                'category' => 'explore', 'course' => 'build-your-own-agent', 'source' => 'harness',
            ],
            'memory' => [
                'label' => '为什么它总忘记我的要求？', 'tone' => 'plain',
                'question' => '对话一长，Agent 为什么忘记目标，甚至反复做同一件事？',
                'context' => '上下文不是无限的。清楚的目标、精简的资料和可恢复的阶段记录，比把所有信息塞进一段对话更容易维护。',
                'step' => '把目标、限制和验收标准整理成一页任务说明，每个阶段补一段已完成记录，再检查下一步是否偏离。',
                'category' => 'solve', 'course' => 'build-a-knowledge-library', 'source' => 'context',
            ],
            'cost' => [
                'label' => '省了时间，为什么更费钱？', 'tone' => 'plain',
                'question' => '反复运行、长上下文、多 Agent，怎样才知道值不值得？',
                'context' => 'Agent 会多次读取信息、调用工具和检查输出。比较方案时，要把费用、完成时间与实际结果放在一起看。',
                'step' => '给一个小任务设定时间和费用上限，记录调用、返工与验收结果，再比较更简短的任务说明。',
                'category' => 'explore', 'course' => null, 'source' => 'context',
            ],
            'reliability' => [
                'label' => '它说“完成了”，可信吗？', 'tone' => 'success',
                'question' => 'Agent 说已经完成，怎样确认文件、数据或软件真的正确？',
                'context' => '执行记录和最终成果需要分开核对。明确的完成标准，才能把“看起来对”变成“能验证”。',
                'step' => '先写三条可检查的验收条件，再打开成品、核对原始数据，用一个异常样例试试。',
                'category' => 'explore', 'course' => null, 'source' => 'trust',
            ],
            'private-files' => [
                'label' => '私人文件，能放心交给 AI？', 'tone' => 'plain',
                'question' => '让 Agent 处理文件前，怎么确认资料范围和可执行的动作？',
                'context' => 'Agent 能接触什么资料、调用什么工具，取决于使用环境与权限。开始前需要看清范围与数据处理设置。',
                'step' => '先用脱敏副本和独立目录测试，只允许当前任务必需的操作；亲自确认服务的数据处理设置。',
                'category' => 'solve', 'course' => 'organize-your-files', 'source' => 'trust',
            ],
            'prompt-injection' => [
                'label' => '网页会把 Agent 带偏吗？', 'tone' => 'plain',
                'question' => 'Agent 读到网页或文档里的指令，会不会把它误当成我的要求？',
                'context' => '提示注入是 Agent 安全中持续讨论的问题。外部材料提供信息，但不能因此获得你的授权。',
                'step' => '用无敏感信息的样本文档做对照，检查它是否把材料中的命令当成任务，并记录哪些动作需要人工确认。',
                'category' => 'explore', 'course' => 'build-your-own-agent', 'source' => 'trust',
            ],
            'creator' => [
                'label' => '创作者怎么用 Agent 找选题？', 'tone' => 'highlight',
                'question' => 'Agent 能从作品、评论和播放数据里，找到有依据的下一条选题吗？',
                'context' => '资料分析是 Agent 的一个应用方向。对于创作者，重要的是把建议与自己的作品、受众反馈和数据联系起来。',
                'step' => '选三篇作品和同周期评论、播放数据，让 Agent 归纳差异；每条选题建议都要能指出依据。',
                'category' => 'solve', 'course' => 'plan-your-next-creation', 'source' => 'work',
            ],
            'human-role' => [
                'label' => 'Agent 越能干，人还做什么？', 'tone' => 'plain',
                'question' => '执行越来越自动化时，哪些选择、责任和验收应该留给人？',
                'context' => '真实使用中的自主程度与人的介入，是当前 Agent 研究的重要问题。把职责写清楚，有助于发现什么时候应该停下来。',
                'step' => '为一个任务列两张清单：Agent 可以执行什么，你必须决定什么。最后自己验收真实结果。',
                'category' => 'explore', 'course' => null, 'source' => 'autonomy',
            ],
        ];
    }
}
