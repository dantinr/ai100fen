const form = document.querySelector('[data-free-progress]');
if (form) {
    const button = form.querySelector('[data-free-save]');
    const status = form.querySelector('[data-free-status]');
    if (button) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            button.disabled = true;
            status.textContent = '正在保存…';
            const checks = Array.from(form.querySelectorAll('input[type="checkbox"]'), (input) => input.checked);
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
                    body: JSON.stringify({checks}),
                });
                if (!response.ok) {
                    if (response.status === 401 || response.status === 419) throw new Error('登录已过期，请重新登录后保存。');
                    if (response.status === 429) throw new Error('保存太频繁，请稍后再试。');
                    throw new Error('未能保存，请保留当前页面并重试。');
                }
                const record = await response.json();
                document.querySelector('[data-free-percent]').textContent = `${record.progress_percent}%`;
                document.querySelector('[data-free-score]').textContent = record.series_score;
                status.textContent = record.completed ? '本步骤全部验收通过，已保存到账号。' : '已保存到账号，可以随时回来继续。';
            } catch (error) {
                status.textContent = error.message || '网络中断，未能保存，请重试。';
            } finally {
                button.disabled = false;
            }
        });
    }
}
