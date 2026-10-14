import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

(() => {
    'use strict';

    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    );

    const finePointer = window.matchMedia(
        '(hover: hover) and (pointer: fine)'
    );

    let cleanupMotion = null;

    function initializeMotion() {
        if (typeof cleanupMotion === 'function') {
            cleanupMotion();
            cleanupMotion = null;
        }

        document.querySelectorAll('.ss-cursor, .ss-spotlight')
            .forEach((element) => element.remove());

        document.body.classList.remove('ss-motion-ready');

        const cleanups = [];

        function listen(target, event, handler, options) {
            target.addEventListener(event, handler, options);
            cleanups.push(() => {
                target.removeEventListener(event, handler, options);
            });
        }

        /* Scroll reveal works without mouse tracking. */
        const revealElements = document.querySelectorAll('[data-reveal]');

        if (reducedMotion.matches || !('IntersectionObserver' in window)) {
            revealElements.forEach((element) => {
                element.classList.add('ss-revealed');
            });
        } else {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('ss-revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -35px 0px',
            });

            revealElements.forEach((element) => observer.observe(element));
            cleanups.push(() => observer.disconnect());
        }

        if (reducedMotion.matches || !finePointer.matches) {
            cleanupMotion = () => cleanups.forEach((cleanup) => cleanup());
            return;
        }

        const cursor = document.createElement('div');
        cursor.className = 'ss-cursor';
        cursor.setAttribute('aria-hidden', 'true');

        const spotlight = document.createElement('div');
        spotlight.className = 'ss-spotlight';
        spotlight.setAttribute('aria-hidden', 'true');

        document.body.append(cursor, spotlight);

        let pointerX = window.innerWidth / 2;
        let pointerY = window.innerHeight / 3;
        let frameRequested = false;

        function updatePointer() {
            document.body.style.setProperty('--ss-pointer-x', `${pointerX}px`);
            document.body.style.setProperty('--ss-pointer-y', `${pointerY}px`);

            cursor.style.transform =
                `translate3d(${pointerX}px, ${pointerY}px, 0) translate(-50%, -50%)`;

            frameRequested = false;
        }

        function onPointerMove(event) {
            pointerX = event.clientX;
            pointerY = event.clientY;

            cursor.classList.add('is-visible');
            document.body.classList.add('ss-motion-ready');

            if (!frameRequested) {
                frameRequested = true;
                window.requestAnimationFrame(updatePointer);
            }
        }

        function onPointerLeave() {
            cursor.classList.remove('is-visible');
        }

        function onPointerEnter() {
            cursor.classList.add('is-visible');
        }

        function onInteractiveEnter(event) {
            if (event.target.closest(
                'a, button, [role="button"], input[type="submit"], .ss-motion-card'
            )) {
                cursor.classList.add('is-hovering');
            }
        }

        function onInteractiveLeave(event) {
            if (event.target.closest(
                'a, button, [role="button"], input[type="submit"], .ss-motion-card'
            )) {
                cursor.classList.remove('is-hovering');
            }
        }

        listen(document, 'pointermove', onPointerMove, { passive: true });
        listen(document, 'pointerover', onInteractiveEnter, { passive: true });
        listen(document, 'pointerout', onInteractiveLeave, { passive: true });
        listen(document.documentElement, 'pointerleave', onPointerLeave);
        listen(document.documentElement, 'pointerenter', onPointerEnter);

        cleanupMotion = () => {
            cleanups.forEach((cleanup) => cleanup());
            cursor.remove();
            spotlight.remove();
            document.body.classList.remove('ss-motion-ready');
            document.body.style.removeProperty('--ss-pointer-x');
            document.body.style.removeProperty('--ss-pointer-y');
        };
    }

    initializeMotion();

    reducedMotion.addEventListener?.('change', initializeMotion);
    finePointer.addEventListener?.('change', initializeMotion);
})();

/* Product card 3D tilt and local spotlight */
(() => {
    'use strict';

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

    if (reducedMotion.matches || !finePointer.matches) {
        return;
    }

    const cards = document.querySelectorAll('.ss-tilt-card');
    const cleanups = [];

    cards.forEach((card) => {
        let frame = null;
        let latestX = 0;
        let latestY = 0;

        function onPointerMove(event) {
            const rect = card.getBoundingClientRect();

            if (!rect.width || !rect.height) {
                return;
            }

            latestX = event.clientX - rect.left;
            latestY = event.clientY - rect.top;

            if (frame !== null) {
                return;
            }

            frame = window.requestAnimationFrame(() => {
                const xRatio = (latestX / rect.width) * 2 - 1;
                const yRatio = (latestY / rect.height) * 2 - 1;

                const rotateY = xRatio * 4;
                const rotateX = yRatio * -4;

                card.style.setProperty('--ss-tilt-x', `${rotateX.toFixed(2)}deg`);
                card.style.setProperty('--ss-tilt-y', `${rotateY.toFixed(2)}deg`);
                card.style.setProperty('--ss-card-x', `${latestX}px`);
                card.style.setProperty('--ss-card-y', `${latestY}px`);

                frame = null;
            });
        }

        function onPointerLeave() {
            if (frame !== null) {
                window.cancelAnimationFrame(frame);
                frame = null;
            }

            card.style.setProperty('--ss-tilt-x', '0deg');
            card.style.setProperty('--ss-tilt-y', '0deg');
            card.style.setProperty('--ss-card-x', '50%');
            card.style.setProperty('--ss-card-y', '50%');
        }

        card.addEventListener('pointermove', onPointerMove, { passive: true });
        card.addEventListener('pointerleave', onPointerLeave);

        cleanups.push(() => {
            card.removeEventListener('pointermove', onPointerMove);
            card.removeEventListener('pointerleave', onPointerLeave);
            if (frame !== null) {
                window.cancelAnimationFrame(frame);
            }
        });
    });

    window.addEventListener('pagehide', () => {
        cleanups.forEach((cleanup) => cleanup());
    }, { once: true });
})();
