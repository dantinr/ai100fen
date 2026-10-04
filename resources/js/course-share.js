document.querySelectorAll('[data-course-share]').forEach((share) => {
    const trigger = share.querySelector('[data-share-trigger]');
    const fallback = share.querySelector('[data-share-fallback]');
    const link = share.querySelector('[data-share-link]');
    const status = share.querySelector('[data-share-status]');
    const copy = async () => {
        try {
            await navigator.clipboard.writeText(link.value);
            status.textContent = '课程链接已复制，可以发给好友。';
        } catch {
            fallback.hidden = false;
            link.focus();
            link.select();
            status.textContent = '请选择并复制课程链接。';
        }
    };
    trigger.addEventListener('click', async () => {
        status.textContent = '';
        if (navigator.share) {
            trigger.disabled = true;
            try {
                await navigator.share({title: share.dataset.shareTitle, text: share.dataset.shareText, url: share.dataset.shareUrl});
                return;
            } catch (error) {
                if (error.name === 'AbortError') return;
            } finally {
                trigger.disabled = false;
            }
        }
        fallback.hidden = false;
        await copy();
    });
    share.querySelector('[data-share-copy]').addEventListener('click', copy);
});
