const schedule = document.querySelector('[data-live-schedule]');

if (schedule) {
    const sessions = [...schedule.querySelectorAll('[data-live-select]')];
    const detailDate = schedule.querySelector('[data-live-detail-date]');
    const detailTitle = schedule.querySelector('[data-live-detail-title]');
    const detailLink = schedule.querySelector('[data-live-detail-link]');
    const detailDisabled = schedule.querySelector('[data-live-detail-disabled]');

    sessions.forEach((session) => {
        session.addEventListener('click', () => {
            sessions.forEach((item) => item.setAttribute('aria-pressed', String(item === session)));
            detailDate.textContent = session.dataset.liveDate;
            detailTitle.textContent = session.dataset.liveTitle;
            const entryUrl = session.dataset.liveEntryUrl;
            detailLink.hidden = !entryUrl;
            detailDisabled.hidden = Boolean(entryUrl);
            if (entryUrl) {
                detailLink.href = entryUrl;
                detailLink.textContent = session.dataset.liveEntryLabel;
            } else {
                detailLink.removeAttribute('href');
                detailDisabled.textContent = session.dataset.liveEntryLabel;
            }
        });
    });
}
