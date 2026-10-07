const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

document.querySelectorAll('[data-typewriter]').forEach((container) => {
    if (reducedMotion.matches || document.hidden) return;

    const lines = [...container.querySelectorAll('[data-typewriter-text]')].map((element) => {
        const original = [...element.childNodes];
        const visual = document.createElement('span');
        visual.setAttribute('aria-hidden', 'true');
        original.forEach((node) => visual.append(node.cloneNode(true)));
        visual.querySelectorAll('.headline-mark').forEach((mark) => mark.classList.add('typewriter-decoration'));

        const walker = document.createTreeWalker(visual, NodeFilter.SHOW_TEXT);
        const textNodes = [];
        while (walker.nextNode()) textNodes.push(walker.currentNode);
        const characters = [];
        textNodes.forEach((node) => {
            const fragment = document.createDocumentFragment();
            Array.from(node.textContent).forEach((character) => {
                const span = document.createElement('span');
                span.className = 'typewriter-character';
                span.textContent = character;
                characters.push(span);
                fragment.append(span);
            });
            node.replaceWith(fragment);
        });

        // Keep the complete text accessible without announcing every new character.
        const readable = document.createElement('span');
        readable.className = 'typewriter-readable';
        readable.append(...original);
        element.replaceChildren(readable, visual);
        return { element, original, characters, speed: Number(element.dataset.typewriterSpeed) || 35 };
    });

    let timer;
    let cursor;
    let lineIndex = 0;
    let characterIndex = 0;

    const finish = () => {
        window.clearTimeout(timer);
        lines.forEach(({ element, original }) => element.replaceChildren(...original));
        reducedMotion.removeEventListener('change', onMotionChange);
        document.removeEventListener('visibilitychange', onVisibilityChange);
    };
    const onMotionChange = () => { if (reducedMotion.matches) finish(); };
    const onVisibilityChange = () => { if (document.hidden) finish(); };

    const type = () => {
        const line = lines[lineIndex];
        if (!line) return finish();
        cursor?.classList.remove('is-typing');
        cursor = line.characters[characterIndex++];
        if (cursor) {
            cursor.classList.add('is-visible', 'is-typing');
            cursor.closest('.typewriter-decoration')?.classList.add('is-visible');
            timer = window.setTimeout(type, line.speed);
        } else {
            lineIndex++;
            characterIndex = 0;
            timer = window.setTimeout(type, 180);
        }
    };

    reducedMotion.addEventListener('change', onMotionChange);
    document.addEventListener('visibilitychange', onVisibilityChange);
    type();
});
