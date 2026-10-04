@props(['prompt', 'id' => 'lesson-prompt'])
<div class="agent-prompt" aria-label="Agent 任务指令">
    <div class="agent-prompt-header">
        <div class="agent-prompt-identity"><x-paul-avatar size="small" :portrait="true" /><div><strong>Agent 对话</strong><span>把目标说清楚，让 Agent 开始行动</span></div></div>
        <span class="agent-prompt-tag">本课 Prompt</span>
    </div>
    <div class="agent-prompt-conversation">
        <div class="agent-prompt-message agent-prompt-message-human">
            <div class="agent-prompt-speaker"><span>你 → Agent</span><span class="agent-prompt-human-avatar" aria-hidden="true"><i data-lucide="user-round"></i></span></div>
            <pre id="{{ $id }}" class="agent-prompt-bubble">{{ $prompt }}</pre>
        </div>
        <div class="agent-prompt-message agent-prompt-message-guide">
            <x-paul-avatar size="small" :portrait="true" />
            <div class="agent-prompt-guide-bubble"><strong>Z · 使用提示</strong><p>复制这段指令，发给你正在使用的 Agent。做完后回来，按本课清单亲自验收。</p></div>
        </div>
    </div>
    <div class="agent-prompt-footer"><span>你的下一步，从这段话开始。</span><button class="button button-dark" type="button" data-copy="{{ $id }}"><i data-lucide="copy" aria-hidden="true"></i><span>复制 Prompt</span></button></div>
</div>
