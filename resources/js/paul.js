document.querySelectorAll('[data-paul-widget]').forEach((widget) => {
    const disclosure = widget.querySelector('details');
    const trigger = disclosure.querySelector('summary');
    const reply = widget.querySelector('[data-paul-reply]');
    const next = widget.querySelector('[data-paul-next]');
    const faces = widget.querySelectorAll('[data-paul-avatar]');
    const choices = widget.querySelectorAll('[data-paul-choice]');
    const welcome = reply.textContent;
    const defaultLinks = [...next.children].map((link) => link.cloneNode(true));
    const openers = document.querySelectorAll('[data-paul-open]');
    const preferenceKey = 'ai100fen:paul-hidden';
    const controls = document.querySelectorAll('main .button, main .all-series, main button, main input');
    let pendingFrame = 0;
    function avoidControls() {
        pendingFrame = 0;
        if (widget.hidden || disclosure.open) {
            widget.classList.remove('is-overlapping');
            return;
        }
        const avatar = trigger.getBoundingClientRect();
        const overlapping = [...controls].some((control) => {
            const bounds = control.getBoundingClientRect();
            return Math.min(avatar.right, bounds.right) - Math.max(avatar.left, bounds.left) > 12
                && Math.min(avatar.bottom, bounds.bottom) - Math.max(avatar.top, bounds.top) > 12;
        });
        widget.classList.toggle('is-overlapping', overlapping);
    }
    function schedulePositionCheck() {
        if (!pendingFrame) pendingFrame = requestAnimationFrame(avoidControls);
    }
    window.addEventListener('scroll', schedulePositionCheck, { passive: true });
    window.addEventListener('resize', schedulePositionCheck);
    window.addEventListener('pageshow', schedulePositionCheck);
    schedulePositionCheck();
    try { widget.hidden = sessionStorage.getItem(preferenceKey) === '1'; } catch {}
    function rememberHidden(hidden) {
        try {
            if (hidden) sessionStorage.setItem(preferenceKey, '1');
            else sessionStorage.removeItem(preferenceKey);
        } catch {}
    }
    function close(restoreFocus = false) {
        disclosure.open = false;
        if (restoreFocus) trigger.focus({ preventScroll: true });
    }
    disclosure.addEventListener('toggle', () => {
        schedulePositionCheck();
        if (!disclosure.open) return;
        faces.forEach((face) => { face.dataset.state = 'idle'; });
        choices.forEach((choice) => choice.setAttribute('aria-pressed', 'false'));
        reply.textContent = welcome;
        next.replaceChildren(...defaultLinks.map((link) => link.cloneNode(true)));
    });
    widget.querySelector('[data-paul-choices]').hidden = false;
    widget.querySelector('[data-paul-close]').hidden = false;
    widget.querySelector('[data-paul-hide]').hidden = false;
    widget.querySelector('[data-paul-close]').addEventListener('click', () => close(true));
    widget.querySelector('[data-paul-hide]').addEventListener('click', () => {
        close();
        widget.hidden = true;
        rememberHidden(true);
        openers[0]?.focus({ preventScroll: true });
    });
    openers.forEach((opener) => {
        opener.hidden = false;
        opener.addEventListener('click', () => {
            widget.hidden = false;
            widget.classList.remove('is-overlapping');
            rememberHidden(false);
            disclosure.open = true;
            trigger.focus({ preventScroll: true });
        });
    });
    choices.forEach((choice) => choice.addEventListener('click', () => {
        const response = widget.querySelector(`[data-paul-response="${choice.dataset.paulChoice}"]`);
        faces.forEach((face) => { face.dataset.state = response.dataset.state; });
        reply.textContent = response.content.querySelector('p').textContent;
        next.replaceChildren(...[...response.content.querySelectorAll('a')].map((link) => link.cloneNode(true)));
        choices.forEach((item) => item.setAttribute('aria-pressed', String(item === choice)));
    }));
    document.addEventListener('pointerdown', (event) => {
        if (disclosure.open && !widget.contains(event.target) && ![...openers].some((opener) => opener.contains(event.target))) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && disclosure.open) close(true);
    });
});
