document.querySelectorAll('[data-question-lab]').forEach((lab) => {
    const chat = lab.querySelector('[data-chat-form]');
    const entry = chat.elements.message;
    const log = lab.querySelector('[data-chat-log]');
    const draft = lab.querySelector('[data-draft-form]');
    const fields = lab.querySelector('[data-draft-fields]');
    const review = lab.querySelector('[data-review-draft]');
    const confirmation = lab.querySelector('[data-draft-confirmation]');
    const submit = lab.querySelector('[data-submit-question]');
    const status = lab.querySelector('[data-save-status]');
    const recommend = lab.querySelector('[data-lab-recommend]');
    const suggestions = lab.querySelector('[data-lab-recommendations]');
    const face = lab.querySelector('[data-paul-avatar]');
    let step = 0;
    let sending = false;
    let saved = false;
    let finding = false;
    let conversationStarted = false;
    const setState = (state, expression = 'idle') => {
        lab.dataset.state = state;
        face.dataset.state = expression;
    };
    const append = (text, user = false) => {
        const message = document.createElement('p');
        message.className = `question-message${user ? ' question-message-user' : ''}`;
        const name = document.createElement('strong');
        name.textContent = user ? '你' : 'Z · 规则引导';
        const body = document.createElement('span');
        body.textContent = text;
        message.append(name, body);
        log.append(message);
        log.scrollTop = log.scrollHeight;
    };
    const fillIfEmpty = (name, value) => {
        if (!draft.elements[name].value.trim()) draft.elements[name].value = value;
    };
    // Rule prompts only: local conversation never masquerades as a model response.
    chat.addEventListener('submit', (event) => {
        event.preventDefault();
        if (sending || saved || !chat.reportValidity()) return;
        const text = entry.value.trim();
        if (!text) return;
        if (log.children.length >= 25) {
            status.textContent = '这次对话已足够长，可直接编辑右侧任务卡，再核对保存。';
            return;
        }
        conversationStarted = true;
        append(text, true);
        entry.value = '';
        if (step === 0) {
            fillIfEmpty('title', text.slice(0, 60));
            fillIfEmpty('goal', text);
            const category = /验证|实验|对照|是否|可能/.test(text) ? 'explore' : /制作|创作|做一个|作品|搭建/.test(text) ? 'create' : 'solve';
            fillIfEmpty('category', category);
            append('先记下这个目标。我给了一个类别建议，你可以修改。做完以后，你希望拿到什么具体结果？');
        } else if (step === 1) {
            fillIfEmpty('outcome', text);
            append('还有哪些范围或限制？比如时间、预算、已有资料，或者这次明确不做什么。没有也可以说“暂时没有”。');
        } else if (step === 2) {
            fillIfEmpty('scope', text);
            append('最后，怎样检查才算做成？请写下至少一个可验证的标准，每行一条。');
        } else if (step === 3) {
            fillIfEmpty('completion_criteria', text);
            append('任务草稿已经整理好。请核对名称、类别和验收标准；你确认后，只保存这张任务卡，聊天不会上传。');
        } else {
            append('补充已留在本页对话里。请把需要保留的部分写进任务卡，核对后再保存。');
        }
        step += 1;
        setState(step >= 4 ? 'draft_ready' : 'chatting', step >= 4 ? 'idea' : 'got_it');
        entry.focus({ preventScroll: true });
    });
    entry.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            chat.requestSubmit();
        }
    });
    const closeConfirmation = () => {
        confirmation.hidden = true;
        draft.elements.confirmed.checked = false;
        review.hidden = false;
    };
    fields.addEventListener('input', () => {
        if (!sending && !saved) closeConfirmation();
    });
    review.addEventListener('click', () => {
        // Confirmation is required only in the next step, after checking the editable fields.
        draft.elements.confirmed.required = false;
        const valid = draft.reportValidity();
        draft.elements.confirmed.required = true;
        if (!valid || saved || sending) return;
        confirmation.hidden = false;
        review.hidden = true;
        draft.elements.confirmed.focus();
        setState('draft_ready', 'idea');
    });
    lab.querySelector('[data-cancel-confirm]').addEventListener('click', () => {
        closeConfirmation();
        review.focus();
    });
    const request = async (url, body) => {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 20000);
        try {
            const response = await fetch(url, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': draft.elements._token.value },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const messages = { 401: '请先在新窗口登录，再回来重试；任务草稿仍保留。', 419: '页面凭证已过期。请先复制任务卡内容，刷新页面后重试。', 429: '操作太频繁，请稍后重试；任务草稿仍保留。', 409: '该提交编号已有保存记录，请先查看“我的私人问题”。原草稿仍保留。' };
                throw new Error(messages[response.status] ?? (response.status === 422 ? Object.values(data.errors ?? {}).flat().join(' ') : '暂时无法完成请求，请稍后重试；任务草稿仍保留。'));
            }
            return data;
        } finally {
            clearTimeout(timeout);
        }
    };
    const receive = () => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const copy = document.createElement('div');
        copy.className = 'question-card-copy';
        copy.setAttribute('aria-hidden', 'true');
        const title = document.createElement('strong');
        title.textContent = draft.elements.title.value;
        const outcome = document.createElement('p');
        outcome.textContent = draft.elements.outcome.value;
        copy.append(title, outcome);
        lab.querySelector('[data-receipt-stage]').append(copy);
        copy.addEventListener('animationend', () => copy.remove(), { once: true });
        setTimeout(() => copy.remove(), 1100);
    };
    draft.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (sending || saved || confirmation.hidden || !draft.reportValidity()) return;
        const data = Object.fromEntries(new FormData(draft));
        data.completion_criteria = data.completion_criteria.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
        if (!data.completion_criteria.length || data.completion_criteria.length > 8 || data.completion_criteria.some((line) => line.length > 300)) {
            status.textContent = '请填写1–8条验收标准，每条最多300字。';
            return;
        }
        delete data._token;
        sending = true;
        fields.disabled = true;
        submit.disabled = true;
        lab.querySelector('[data-cancel-confirm]').disabled = true;
        chat.querySelector('[data-chat-send]').disabled = true;
        recommend.disabled = true;
        submit.textContent = '正在保存…';
        status.textContent = '正在保存私人任务卡，请稍候。';
        setState('submitting');
        try {
            const result = await request(lab.dataset.submitUrl, data);
            const url = new URL(result.url, location.href);
            if (!Number.isInteger(result.id) || result.id < 1 || url.origin !== location.origin || !/^\/questions\/\d+$/.test(url.pathname)) throw new Error('未收到有效保存回执，请重试或查看“我的私人问题”。');
            saved = true;
            setState('success', 'idea');
            status.textContent = '问题已收集。仅你可见，未保存聊天记录。';
            status.focus({ preventScroll: true });
            status.scrollIntoView({ block: 'nearest', behavior: 'instant' });
            lab.querySelector('[data-saved-link]').href = url.href;
            lab.querySelector('[data-saved-actions]').hidden = false;
            confirmation.hidden = true;
            review.hidden = true;
            entry.disabled = true;
            receive();
        } catch (error) {
            setState('error', 'awkward');
            status.textContent = error.name === 'AbortError' ? '请求超时，任务草稿仍保留。可以重试；同一提交编号不会重复保存。' : error.message || '网络连接失败，请重试；任务草稿仍保留。';
            status.focus({ preventScroll: true });
        } finally {
            sending = false;
            fields.disabled = saved;
            submit.disabled = saved;
            lab.querySelector('[data-cancel-confirm]').disabled = saved;
            chat.querySelector('[data-chat-send]').disabled = saved;
            recommend.disabled = saved;
            submit.textContent = '确认保存';
        }
    });
    recommend.addEventListener('click', async () => {
        if (finding || sending || saved) return;
        const q = draft.elements.goal.value.trim().slice(0, 300);
        if (!q) { suggestions.textContent = '先描述目标，或直接填写任务卡。'; return; }
        finding = true;
        recommend.disabled = true;
        suggestions.textContent = '正在匹配已发布免费任务…';
        try {
            const result = await request(lab.dataset.recommendUrl, { q, category: draft.elements.category.value || null });
            suggestions.replaceChildren();
            if (!result.recommendations?.length) suggestions.textContent = '暂时没有匹配的免费任务，可以继续把自己的问题定义清楚。';
            for (const match of result.recommendations ?? []) {
                const url = new URL(match.url, location.href);
                if (url.origin !== location.origin || !url.pathname.startsWith('/free/')) continue;
                const link = document.createElement('a');
                link.className = 'text-link'; link.href = url.href; link.target = '_blank'; link.rel = 'noopener noreferrer';
                link.textContent = `${match.title}（新窗口）`;
                const reason = document.createElement('p'); reason.textContent = match.reason;
                suggestions.append(link, reason);
            }
        } catch (error) {
            suggestions.textContent = error.name === 'AbortError' ? '匹配超时，可以再试一次。' : error.message;
        } finally { finding = false; recommend.disabled = sending || saved; }
    });
    lab.querySelector('[data-chat-send]').disabled = false;
    review.disabled = false;
    recommend.disabled = false;
    window.addEventListener('beforeunload', (event) => {
        const dirty = conversationStarted || [...fields.querySelectorAll('input,textarea,select')].some((field) => field.value.trim());
        if (!saved && dirty) { event.preventDefault(); event.returnValue = ''; }
    });
});
