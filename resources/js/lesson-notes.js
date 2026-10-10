document.querySelectorAll('[data-lesson-notes]').forEach((form) => {
    const editor = form.querySelector('[data-note-editor]');
    const button = form.querySelector('[data-note-save]');
    const status = form.querySelector('[data-note-status]');
    const counter = form.querySelector('[data-note-count]');
    const version = form.querySelector('[name="notes_version"]');
    let savedText = form.dataset.noteDirty === 'true' ? null : editor.value;
    let saving = false;
    const dirty = () => editor.value !== savedText;
    function update() {
        counter.textContent = `${Array.from(editor.value).length} / 10000`;
        button.disabled = saving || !dirty();
    }
    editor.addEventListener('input', () => {
        status.textContent = dirty() ? '有修改尚未保存。' : '笔记与已保存内容一致。';
        update();
    });
    editor.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
            event.preventDefault();
            if (!saving && dirty()) form.requestSubmit();
        }
    });
    window.addEventListener('beforeunload', (event) => {
        if (dirty()) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (saving || !dirty() || !form.reportValidity()) return;
        const text = editor.value;
        saving = true;
        update();
        status.textContent = '正在保存…';
        button.setAttribute('aria-busy', 'true');
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(form.action, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
                body: JSON.stringify({notes: text, notes_version: Number(version.value)}),
                signal: controller.signal,
            });
            if (!response.ok) {
                if (response.status === 401 || response.status === 419) throw new Error('登录已过期，当前文字已保留。请复制备份后重新登录。');
                if (response.status === 409) throw new Error('笔记已在其他页面更新。请复制当前文字，再刷新页面合并。');
                if (response.status === 429) throw new Error('保存太频繁，请稍后重试。当前文字已保留。');
                if (response.status === 422) throw new Error('笔记内容或版本无效，请检查字数，保留当前文字后重试。');
                if (response.status === 403 || response.status === 404) throw new Error('课程当前无法访问，未保存。请复制备份当前文字。');
                throw new Error('未能保存，当前文字已保留，请重试。');
            }
            const record = await response.json();
            if (!Number.isInteger(record.notes_version) || record.notes_version < Number(version.value)) throw new Error('保存结果未确认，请保留当前文字后重试。');
            version.value = String(record.notes_version);
            savedText = text;
            status.textContent = dirty() ? '刚才的内容已保存，还有新修改待保存。' : '已保存到账号。';
        } catch (error) {
            status.textContent = error.name === 'AbortError' ? '保存超时，结果未确认。当前文字已保留，可以重试。' : (error.message || '网络中断，请保留当前文字后重试。');
        } finally {
            clearTimeout(timeout);
            saving = false;
            button.removeAttribute('aria-busy');
            update();
        }
    });
    update();
});
