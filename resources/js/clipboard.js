export async function copyText(text) {
    if (window.isSecureContext && navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch { /* Browser permissions may still allow copying from a selected field. */ }
    }

    const activeElement = document.activeElement;
    const selection = window.getSelection();
    const ranges = selection ? Array.from({length: selection.rangeCount}, (_, index) => selection.getRangeAt(index).cloneRange()) : [];
    const buffer = document.createElement('textarea');
    buffer.value = text;
    buffer.className = 'clipboard-copy-buffer';
    buffer.readOnly = true;
    buffer.tabIndex = -1;
    buffer.setAttribute('aria-hidden', 'true');
    document.body.append(buffer);

    try {
        buffer.focus({preventScroll: true});
        buffer.select();
        buffer.setSelectionRange(0, buffer.value.length);
        if (!document.execCommand('copy')) throw new Error('Clipboard copy was blocked.');
    } finally {
        buffer.remove();
        activeElement?.focus({preventScroll: true});
        if (selection) {
            selection.removeAllRanges();
            ranges.forEach(range => selection.addRange(range));
        }
    }
}
