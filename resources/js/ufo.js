const flightTimers = new WeakMap();
const celebrationTimers = new WeakMap();

function motionDuration(element, token) {
    const value = getComputedStyle(element).getPropertyValue(token).trim();
    return parseFloat(value) * (value.endsWith('ms') ? 1 : 1000);
}

// Presentation only: the caller supplies course progress; this never grants access.
export function updateUfoProgress(target, progress) {
    const path = target.querySelector('[data-ufo-path]');
    const widget = path?.querySelector('[data-ufo]');
    if (!widget) return;

    const numeric = Number(progress);
    const score = Number.isFinite(numeric) ? Math.max(0, Math.min(100, numeric)) : 0;
    const previous = path.dataset.ufoScore === undefined ? null : Number(path.dataset.ufoScore);
    path.dataset.ufoScore = String(score);
    path.style.setProperty('--ufo-progress', String(score / 100));
    path.setAttribute('aria-valuenow', String(score));
    path.setAttribute('aria-valuetext', `当前完成${score}%`);
    widget.dataset.progress = String(score);
    path.querySelectorAll('.ufo-path-nodes>span').forEach((node, index) => {
        node.classList.toggle('is-reached', index * 10 <= score);
    });
    const completion = target.querySelector('[data-ufo-completion]');
    if (completion) completion.hidden = score < 100;
    if (previous === score) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    clearTimeout(flightTimers.get(widget));
    clearTimeout(celebrationTimers.get(target));
    target.classList.remove('is-celebrating');
    widget.dataset.state = score === 100 ? 'completed' : 'idle';
    if (previous !== null && !reducedMotion) {
        if (score === 100 && previous < 100) {
            target.classList.add('is-celebrating');
            celebrationTimers.set(target, setTimeout(() => target.classList.remove('is-celebrating'), motionDuration(widget, '--motion-complete')));
        } else if (score < 100) {
            widget.dataset.state = 'flying';
            flightTimers.set(widget, setTimeout(() => { widget.dataset.state = 'idle'; }, motionDuration(widget, '--motion-progress')));
        }
    }
}
