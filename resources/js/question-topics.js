document.querySelectorAll('[data-question-topics]').forEach((board) => {
    const links = [...board.querySelectorAll('[data-topic-link]')];
    const panels = [...board.querySelectorAll('[data-topic-panel]')];
    const status = board.querySelector('[data-topic-status]');

    const select = (id, announce = true) => {
        const selected = links.find((link) => link.dataset.topicLink === id) ?? links[0];
        if (!selected) return;
        links.forEach((link) => {
            if (link === selected) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
        panels.forEach((panel) => { panel.hidden = panel.dataset.topicPanel !== selected.dataset.topicLink; });
        if (announce && status) status.textContent = `已选话题：${selected.textContent.trim()}`;
    };

    links.forEach((link) => link.addEventListener('click', (event) => {
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (link.hasAttribute('aria-current')) return;
        select(link.dataset.topicLink);
        window.history.pushState(null, '', link.href);
        if (window.matchMedia('(max-width: 760px)').matches) {
            board.querySelector('#question-detail')?.scrollIntoView({
                block: 'nearest',
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
            });
        }
    }));

    window.addEventListener('popstate', () => select(new URL(window.location.href).searchParams.get('topic'), false));
});
