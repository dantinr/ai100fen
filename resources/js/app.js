import { createIcons, ArrowRight, ArrowUpRight, ArrowLeft, ArrowDown, ArrowDownToLine, Check, CircleCheck, ChevronRight, Clock3, Clapperboard, Copy, Download, Flag, Globe, Info, ListChecks, LockKeyhole, Menu, Monitor, NotebookPen, Play, Plus, Search, SearchX, Sprout, Timer, UserRound, Video, X, CalendarDays, LayoutTemplate, Table2, FlaskConical, Server, Terminal, PanelsTopLeft, Sparkles, BookOpen, Route, Command, FolderOpen, BadgeCheck, Share2 } from 'lucide';
import { updateUfoProgress } from './ufo';
import { copyText } from './clipboard';
import './paul';
import './free-lab';
import './course-share';
import './lesson-video';
import './lesson-notes';
import './question-topics';
import './question-lab';
import './live-schedule';
import './typewriter';

const icons = { ArrowRight, ArrowUpRight, ArrowLeft, ArrowDown, ArrowDownToLine, Check, CircleCheck, ChevronRight, Clock3, Clapperboard, Copy, Download, Flag, Globe, Info, ListChecks, LockKeyhole, Menu, Monitor, NotebookPen, Play, Plus, Search, SearchX, Sprout, Timer, UserRound, Video, X, CalendarDays, LayoutTemplate, Table2, FlaskConical, Server, Terminal, PanelsTopLeft, Sparkles, BookOpen, Route, Command, FolderOpen, BadgeCheck, Share2 };
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
document.querySelectorAll('[data-copy]').forEach((button) => {
    const label = button.querySelector('span');
    const originalLabel = label?.textContent;
    let restoreTimer;
    button.addEventListener('click', async () => {
        const target = document.getElementById(button.dataset.copy);
        if (!target) return;
        try {
            await copyText(target.textContent);
            if (label) {
                label.textContent = '已复制';
                clearTimeout(restoreTimer);
                restoreTimer = setTimeout(() => { label.textContent = originalLabel; }, 2000);
            }
            toast('已复制');
        } catch {
            clearTimeout(restoreTimer);
            if (label) label.textContent = originalLabel;
            const selection = window.getSelection();
            const range = document.createRange();
            range.selectNodeContents(target);
            selection.removeAllRanges(); selection.addRange(range);
            toast('暂时无法复制，已选中文字，请手动复制');
        }
    });
});

// Course progress comes exclusively from the authenticated server response.
function renderProgress() {
    document.querySelectorAll('[data-progress-for][data-server-score]').forEach((target) => {
        const percent = Number(target.dataset.serverScore);
        target.querySelector('[data-score]').textContent = percent;
        target.querySelectorAll('.progress-segments>span').forEach((segment, index) => segment.classList.toggle('filled', index < percent / 10));
        updateUfoProgress(target, percent);
    });
}
renderProgress();
window.addEventListener('pageshow', renderProgress);
