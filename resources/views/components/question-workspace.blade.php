<section class="question-lab" id="question-lab" data-question-lab data-state="idle"
         data-submit-url="{{ route('questions.store') }}" data-recommend-url="{{ route('questions.recommend') }}" aria-label="与 Z 定义问题">
    <section class="question-chat" aria-labelledby="question-chat-title">
        <header class="question-lab-heading"><x-paul-avatar size="small" /><div><h2 id="question-chat-title">和 Z，把问题想清楚。</h2><p>规则引导 · 未接入实时 AI</p></div></header>
        <p class="question-privacy">聊天仅在当前页面内存中，刷新或离开会清空。不保存聊天全文。</p>
        <div class="question-chat-log" data-chat-log role="log" aria-live="polite" aria-relevant="additions" tabindex="0" aria-label="本页规则对话">
            <p class="question-message"><strong>Z · 规则引导</strong><span>说吧，今天想搞定什么？我们先把问题想清楚。</span></p>
        </div>
        <form data-chat-form class="question-chat-form">
            <label for="question-message">告诉 Z，你遇到了什么问题</label>
            <textarea id="question-message" name="message" maxlength="1200" rows="3" placeholder="比如：我有几份订单表，想合成一张能核对的汇总表。" required></textarea>
            <div class="question-chat-tools"><span>Enter 发送 · Shift+Enter 换行</span><button class="button button-small button-dark" type="submit" disabled data-chat-send>发送</button></div>
        </form>
        <div class="question-recommendations"><button type="button" class="text-link" data-lab-recommend disabled>找一个相关免费任务<i data-lucide="arrow-right"></i></button><p class="question-privacy">点击后仅发送最多300字的目标和类别用于匹配，不保存该请求内容。</p><div data-lab-recommendations role="status" aria-live="polite"></div></div>
    </section>
    <section class="question-draft-panel" aria-labelledby="question-draft-title">
        <header class="question-draft-header"><div><span class="eyebrow">你来判断，Z 帮你整理</span><h2 id="question-draft-title">任务草稿</h2></div></header>
        <p class="question-privacy">草稿可直接修改。确认前不会上传任务卡，保存后仅你可见。</p>
        <form data-draft-form>
            @csrf
            <input type="hidden" name="submission_key" value="{{ (string) Illuminate\Support\Str::uuid() }}">
            <fieldset data-draft-fields class="question-draft-fields">
                <label>任务名称<input name="title" maxlength="160" required placeholder="一句话说清要做成什么"></label>
                <label>主要类别<select name="category" required><option value="">由你选择</option><option value="solve">Solve · 解决一个问题</option><option value="create">Create · 创作一个作品</option><option value="explore">Explore · 探索一个可能</option></select></label>
                <label>想达到的目标<textarea name="goal" rows="2" maxlength="1500" required></textarea></label>
                <label>范围与限制（可选）<textarea name="scope" rows="2" maxlength="1500" placeholder="时间、预算、已有资料，或不想做的部分"></textarea></label>
                <label>期望产出<textarea name="outcome" rows="2" maxlength="1500" required placeholder="最终拿到什么结果？"></textarea></label>
                <label>怎样验收<textarea name="completion_criteria" rows="3" maxlength="2400" required placeholder="每行一个可检查的标准，最多8条，每条300字"></textarea></label>
            </fieldset>
            @guest<p class="question-login-note">保存需要登录。<a class="text-link" href="{{ route('login') }}" target="_blank" rel="noopener noreferrer">在新窗口登录</a>后，可回来继续确认，不必离开草稿。</p>@endguest
            <button type="button" class="button button-primary" data-review-draft disabled>确认并提交问题<i data-lucide="arrow-right"></i></button>
            <section class="question-confirmation" data-draft-confirmation hidden aria-label="确认私人收集">
                <h3>只保存这张任务卡。</h3><p>保存名称、类别、目标、范围、期望产出和验收标准，不保存聊天记录，也不会公开发布。</p>
                <label><input type="checkbox" name="confirmed" value="1" required> 我已核对任务卡，同意保存为仅本人可见的私人问题。</label>
                <div class="question-confirm-actions"><button class="button button-dark" type="submit" data-submit-question>确认保存</button><button type="button" class="text-link" data-cancel-confirm>继续修改</button></div>
            </section>
        </form>
        <div class="question-receipt-stage" data-receipt-stage><p class="question-save-status" data-save-status role="status" aria-live="polite" tabindex="-1"></p><x-black-hole variant="submission" /></div>
        <div class="question-saved-actions" data-saved-actions hidden><a class="button button-dark" data-saved-link>查看已收集问题</a><a class="text-link" href="{{ route('questions') }}#question-lab">再定义一个问题</a></div>
        <a class="text-link question-mine-link" href="{{ route('questions.mine') }}">我的私人问题<i data-lucide="arrow-up-right"></i></a>
        <noscript><p>规则对话与任务卡提交需要 JavaScript；下方话题浏览仍可使用。</p></noscript>
    </section>
</section>
