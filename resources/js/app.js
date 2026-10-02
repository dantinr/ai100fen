import { createIcons, ArrowRight, ArrowUpRight, ArrowLeft, ArrowDown, ArrowDownToLine, Check, CircleCheck, ChevronRight, Clock3, Clapperboard, Copy, Download, Flag, Globe, Info, ListChecks, LockKeyhole, Menu, Monitor, NotebookPen, Play, Plus, Search, SearchX, Sprout, Timer, UserRound, Video, X, CalendarDays, LayoutTemplate, Table2, FlaskConical } from 'lucide';
import { updateUfoProgress } from './ufo';
import './paul';
import './free-lab';
import './question-topics';
import './question-lab';

const icons = { ArrowRight, ArrowUpRight, ArrowLeft, ArrowDown, ArrowDownToLine, Check, CircleCheck, ChevronRight, Clock3, Clapperboard, Copy, Download, Flag, Globe, Info, ListChecks, LockKeyhole, Menu, Monitor, NotebookPen, Play, Plus, Search, SearchX, Sprout, Timer, UserRound, Video, X, CalendarDays, LayoutTemplate, Table2, FlaskConical };
createIcons({ icons });

document.querySelectorAll('[data-contribution-grid]').forEach((grid) => {
    const cells = [...grid.querySelectorAll('[data-contribution-cell]')];
    const detail = document.querySelector('[data-contribution-detail]');
    const scroll = document.querySelector('[data-contribution-scroll]');
    const defaultLabel = detail.textContent;
    scroll.scrollLeft = scroll.scrollWidth;
    grid.addEventListener('keydown', (event) => {
        const index = cells.indexOf(event.target);
        if (index < 0) return;
        const directions = { ArrowUp: -1, ArrowDown: 1, ArrowLeft: -7, ArrowRight: 7 };
        let next;
        if (event.key in directions) next = index + directions[event.key];
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = cells.length - 1;
        else return;
        event.preventDefault();
        next = Math.max(0, Math.min(cells.length - 1, next));
        cells[next].focus();
    });
    cells.forEach((cell) => {
        cell.addEventListener('focus', () => {
            cells.forEach((item) => { item.tabIndex = item === cell ? 0 : -1; });
            detail.textContent = cell.dataset.label;
        });
        cell.addEventListener('pointerenter', () => { detail.textContent = cell.dataset.label; });
        cell.addEventListener('pointerleave', () => {
            detail.textContent = cells.includes(document.activeElement) ? document.activeElement.dataset.label : defaultLabel;
        });
    });
});

document.querySelectorAll('[data-submit-form]').forEach((form) => {
    const button = form.querySelector('button[type="submit"]');
    const label = button.innerHTML;
    form.addEventListener('submit', () => {
        button.disabled = true;
        button.textContent = '正在提交…';
    });
    window.addEventListener('pageshow', () => {
        button.disabled = false;
        button.innerHTML = label;
    });
});

let toastTimer;
function toast(message) {
    const target = document.querySelector('#toast');
    target.textContent = message;
    target.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { target.hidden = true; }, 3500);
}

const menu = document.querySelector('.menu-toggle');
menu?.addEventListener('click', () => {
    const expanded = menu.getAttribute('aria-expanded') !== 'true';
    menu.setAttribute('aria-expanded', String(expanded));
    menu.setAttribute('aria-label', expanded ? '收起导航' : '展开导航');
    document.querySelector('.main-nav').classList.toggle('open', expanded);
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menu) {
        menu.setAttribute('aria-expanded', 'false');
        menu.setAttribute('aria-label', '展开导航');
        document.querySelector('.main-nav').classList.remove('open');
    }
});

const dialog = document.querySelector('#availability-dialog');
document.querySelectorAll('[data-availability]').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelector('#dialog-title').textContent = button.dataset.availability === 'subscription' ? '订阅暂未开放' : '购买暂未开放';
        dialog.showModal();
    });
});
document.querySelectorAll('[data-close-dialog]').forEach((button) => button.addEventListener('click', () => dialog.close()));
dialog?.addEventListener('click', (event) => {
    const bounds = dialog.getBoundingClientRect();
    if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
});

document.querySelectorAll('[data-catalog]').forEach((catalog) => {
    let filter = 'all';
    const search = catalog.querySelector('[data-course-search]');
    const cards = [...catalog.querySelectorAll('[data-course-card]')];
    function applyFilter() {
        const query = (search?.value ?? '').trim().toLowerCase();
        cards.forEach((card) => { card.hidden = (filter !== 'all' && card.dataset.category !== filter) || !card.dataset.search.toLowerCase().includes(query); });
        catalog.querySelector('.empty-results').hidden = cards.some((card) => !card.hidden);
        catalog.querySelectorAll('[data-filter]').forEach((button) => {
            const active = button.dataset.filter === filter;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', String(active));
        });
    }
    catalog.querySelectorAll('[data-filter]').forEach((button) => button.addEventListener('click', () => { filter = button.dataset.filter; applyFilter(); }));
    search?.addEventListener('input', applyFilter);
    catalog.querySelector('[data-reset-filters]')?.addEventListener('click', () => { filter = 'all'; if (search) search.value = ''; applyFilter(); });
});

const tabs = [...document.querySelectorAll('[data-tab]')];
function activateTab(name, focus = false) {
    tabs.forEach((tab) => {
        const selected = tab.dataset.tab === name;
        tab.setAttribute('aria-selected', String(selected));
        tab.tabIndex = selected ? 0 : -1;
        if (selected && focus) tab.focus();
    });
    document.querySelectorAll('[data-tab-panel]').forEach((panel) => { panel.hidden = panel.dataset.tabPanel !== name; });
}
tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateTab(tab.dataset.tab));
    tab.addEventListener('keydown', (event) => {
        let target;
        if (event.key === 'ArrowRight') target = (index + 1) % tabs.length;
        if (event.key === 'ArrowLeft') target = (index + tabs.length - 1) % tabs.length;
        if (event.key === 'Home') target = 0;
        if (event.key === 'End') target = tabs.length - 1;
        if (target !== undefined) { event.preventDefault(); activateTab(tabs[target].dataset.tab, true); }
    });
});
document.querySelectorAll('[data-open-tab]').forEach((button) => button.addEventListener('click', () => { activateTab(button.dataset.openTab, true); document.querySelector('#lesson-tabs').scrollIntoView({ block: 'start' }); }));
document.querySelectorAll('[data-copy]').forEach((button) => button.addEventListener('click', async () => {
    try {
        await navigator.clipboard.writeText(document.getElementById(button.dataset.copy).textContent);
        const label = button.querySelector('span');
        label.textContent = '已复制';
        toast('已复制');
        setTimeout(() => { label.textContent = '复制'; }, 2000);
    } catch {
        const selection = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(document.getElementById(button.dataset.copy));
        selection.removeAllRanges(); selection.addRange(range);
        toast('暂时无法复制，已选中文字');
    }
}));

// Browser progress is a preview record only and never grants access to a lesson.
const storageKey = 'ai100fen.frontend-progress.v1';
let progress = { started: false, completed: false, checks: [false, false, false] };
function loadProgress() {
    try {
        const saved = JSON.parse(localStorage.getItem(storageKey));
        if (saved && typeof saved === 'object') progress = {
            started: saved.started === true,
            completed: saved.completed === true,
            checks: [0, 1, 2].map((index) => saved.checks?.[index] === true),
        };
    } catch { /* A blocked or unavailable store keeps the page usable. */ }
}
loadProgress();
function saveProgress() {
    try { localStorage.setItem(storageKey, JSON.stringify(progress)); }
    catch { toast('浏览器未允许保存，学习记录仅在当前页面有效'); }
}
function renderProgress() {
    const score = progress.completed ? 10 : 0;
    document.querySelectorAll('[data-progress-for]').forEach((target) => {
        const currentScore = target.dataset.progressFor === 'build-a-website' ? score : 0;
        target.querySelector('[data-score]').textContent = currentScore;
        const segments = target.querySelector('.progress-segments');
        [...segments.children].forEach((segment, index) => segment.classList.toggle('filled', index < currentScore / 10));
        updateUfoProgress(target, currentScore);
    });
    if (progress.completed) document.querySelectorAll('[data-lesson-row="server-and-ip"]').forEach((row) => {
        row.classList.add('is-completed');
        const state = row.querySelector('.outline-state');
        if (state) state.textContent = '已完成';
    });
    const myPage = document.querySelector('[data-my-progress]');
    if (myPage) {
        myPage.querySelector('[data-total-score]').textContent = score;
        myPage.querySelector('[data-started-count]').textContent = progress.started ? 1 : 0;
        myPage.querySelector('[data-my-empty]').hidden = progress.started;
        myPage.querySelector('[data-my-course-list]').hidden = !progress.started;
        const website = myPage.querySelector('[data-my-course="build-a-website"]');
        website.hidden = !progress.started;
        if (progress.completed) {
            website.querySelector('[data-current-lesson]').textContent = '已完成服务器与 IP · 下一步：域名（待发布）';
            website.querySelector('[data-continue-link]').href = '/series/build-a-website/lessons/domain';
        }
    }
}
const learningPage = document.querySelector('[data-learning-lesson]');
let syncAcceptance;
if (learningPage) {
    progress.started = true;
    saveProgress();
    const checks = [...learningPage.querySelectorAll('[data-acceptance]')];
    const complete = learningPage.querySelector('[data-complete-lesson]');
    function renderAcceptance() {
        checks.forEach((input, index) => { input.checked = progress.completed || progress.checks[index]; input.disabled = progress.completed; });
        complete.disabled = progress.completed || !progress.checks.every(Boolean);
        if (progress.completed) {
            complete.innerHTML = '<i data-lucide="circle-check"></i>已完成 · 10分';
            learningPage.querySelector('[data-completion-hint]').textContent = '第一个10分，已记录。继续把下一步做成。';
            createIcons({ icons });
        }
    }
    syncAcceptance = renderAcceptance;
    checks.forEach((input, index) => input.addEventListener('change', () => { progress.checks[index] = input.checked; saveProgress(); renderAcceptance(); }));
    complete.addEventListener('click', () => {
        if (progress.completed || !progress.checks.every(Boolean)) return;
        progress.completed = true;
        saveProgress(); renderAcceptance(); renderProgress();
        toast('第一个10分，完成！');
    });
    renderAcceptance();
}
renderProgress();
window.addEventListener('pageshow', (event) => {
    if (event.persisted) { loadProgress(); syncAcceptance?.(); }
    renderProgress();
});
window.addEventListener('storage', (event) => {
    if (event.key === storageKey) { loadProgress(); syncAcceptance?.(); renderProgress(); }
});
