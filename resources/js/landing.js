/**
 * ShopSync landing animations.
 *
 * Every scene on the landing page is drawn on a <canvas> from explicit
 * equations, evaluated per frame:
 *
 *   background  full-page vector field θ(x,y,t) = sin(yk₁+tω₁) + cos(xk₂−tω₂)
 *               with a local vortex injected around the visitor's cursor
 *   cursor      underdamped spring  a = −k(x − target) − c·v  chasing the
 *               pointer, leaving a sine-wobbled trail
 *   hero        shop nodes on Lissajous-style orbits, order packets along
 *               quadratic Bézier curves  B(u) = (1−u)²P₀ + 2(1−u)uP₁ + u²P₂
 *   rose        r = R·cos(5θ)  (odd k → one closed 5-petal curve over [0, π])
 *   waves       y = A·sin(k₁x − ω₁t) + ½A·sin(k₂x + ω₂t)  (superposition)
 *   growth      y = L / (1 + e^(−k(x−x₀)))  (logistic curve)
 *   signals     layered sines y = A·sin(kx + ωt + φ) + 0.35A·sin(2.7kx − 0.6ωt)
 *
 * All scenes share one requestAnimationFrame ticker, pause when scrolled
 * off-screen or the tab is hidden, and render a single static frame when
 * the user prefers reduced motion.
 */

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

/** Latest pointer position, shared by the background vortex and the cursor comet. */
const pointer = { x: -9999, y: -9999, active: false };

/** Deterministic PRNG (mulberry32) so layouts are stable between reloads. */
function seededRandom(seed) {
    let state = seed;

    return function random() {
        state |= 0;
        state = (state + 0x6d2b79f5) | 0;

        let mixed = Math.imul(state ^ (state >>> 15), 1 | state);

        mixed = (mixed + Math.imul(mixed ^ (mixed >>> 7), 61 | mixed)) ^ mixed;

        return ((mixed ^ (mixed >>> 14)) >>> 0) / 4294967296;
    };
}

/** smoothstep easing: p(t) = 3t² − 2t³. */
const smoothstep = (t) => t * t * (3 - 2 * t);

const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

/* =====================================================================
   Scene: background — full-page flow field with a cursor-driven vortex
   ===================================================================== */
function makeBackgroundScene() {
    const random = seededRandom(20261104);
    let particles = [];

    function build(w, h) {
        const count = Math.round(Math.min(170, Math.max(60, (w * h) / 11000)));

        particles = Array.from({ length: count }, () => ({
            x: random() * w,
            y: random() * h,
        }));
    }

    function step(ctx, w, h, t, dt) {
        // Fade previous frames instead of clearing: leaves comet trails.
        ctx.globalCompositeOperation = 'destination-out';
        ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        ctx.fillRect(0, 0, w, h);
        ctx.globalCompositeOperation = 'source-over';

        const speed = 32;
        const swirlRadius = 220;
        ctx.fillStyle = 'rgba(165, 180, 252, 0.45)';

        for (const particle of particles) {
            // Base direction field: θ(x, y, t) from three interfering waves.
            const theta =
                Math.sin(particle.y * 0.0019 + t * 0.28) * 1.4 +
                Math.cos(particle.x * 0.0015 - t * 0.22) * 1.4 +
                Math.sin((particle.x + particle.y) * 0.0009 + t * 0.1) * 0.6;

            let vx = Math.cos(theta);
            let vy = Math.sin(theta);

            // The cursor injects a local vortex: tangential swirl plus a
            // gentle pull toward the pointer, fading with distance.
            if (pointer.active) {
                const dx = pointer.x - particle.x;
                const dy = pointer.y - particle.y;
                const dist = Math.hypot(dx, dy);

                if (dist < swirlRadius && dist > 1) {
                    const falloff = (1 - dist / swirlRadius) ** 2;

                    vx += ((-dy / dist) * 2.4 + (dx / dist) * 0.7) * falloff;
                    vy += ((dx / dist) * 2.4 + (dy / dist) * 0.7) * falloff;
                }
            }

            const norm = Math.hypot(vx, vy) || 1;

            particle.x += (vx / norm) * speed * dt;
            particle.y += (vy / norm) * speed * dt;

            if (particle.x < -10) particle.x = w + 10;
            if (particle.x > w + 10) particle.x = -10;
            if (particle.y < -10) particle.y = h + 10;
            if (particle.y > h + 10) particle.y = -10;

            ctx.beginPath();
            ctx.arc(particle.x, particle.y, 1.2, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    return { build, step };
}

/* =====================================================================
   Scene: cursor — underdamped spring comet chasing the pointer
   ===================================================================== */
function makeCursorScene() {
    const spring = { x: -200, y: -200, vx: 0, vy: 0 };
    const trail = [];

    function step(ctx, w, h, t, dt) {
        ctx.clearRect(0, 0, w, h);

        if (!pointer.active) {
            return;
        }

        // a = −k(x − target) − c·v, integrated with semi-implicit Euler.
        // Slight underdamping gives the comet its organic overshoot.
        const stiffness = 140;
        const damping = 16;

        spring.vx += (stiffness * (pointer.x - spring.x) - damping * spring.vx) * dt;
        spring.vy += (stiffness * (pointer.y - spring.y) - damping * spring.vy) * dt;
        spring.x += spring.vx * dt;
        spring.y += spring.vy * dt;

        trail.push({ x: spring.x, y: spring.y });

        if (trail.length > 16) {
            trail.shift();
        }

        // Trail drawn as circles whose centres wobble along the path
        // normal with a travelling sine, so the tail ripples like a wave.
        const count = trail.length;

        for (let i = 1; i < count; i++) {
            const current = trail[i];
            const previous = trail[i - 1];
            const dx = current.x - previous.x;
            const dy = current.y - previous.y;
            const length = Math.hypot(dx, dy) || 1;
            const wobble = Math.sin(i * 0.9 + t * 8) * 3.5 * (i / count);

            ctx.beginPath();
            ctx.arc(
                current.x + (-dy / length) * wobble,
                current.y + (dx / length) * wobble,
                1 + (i / count) * 2.6,
                0,
                Math.PI * 2
            );
            ctx.fillStyle = `rgba(165, 180, 252, ${(i / count) * 0.4})`;
            ctx.fill();
        }

        const halo = ctx.createRadialGradient(
            spring.x,
            spring.y,
            0,
            spring.x,
            spring.y,
            26
        );

        halo.addColorStop(0, 'rgba(129, 140, 248, 0.35)');
        halo.addColorStop(1, 'rgba(129, 140, 248, 0)');

        ctx.fillStyle = halo;
        ctx.beginPath();
        ctx.arc(spring.x, spring.y, 26, 0, Math.PI * 2);
        ctx.fill();

        ctx.beginPath();
        ctx.arc(spring.x, spring.y, 3, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(224, 231, 255, 0.9)';
        ctx.fill();
    }

    return { step };
}

/* =====================================================================
   Scene: hero — shop network + Bézier order packets over the backdrop
   ===================================================================== */
function makeHeroScene() {
    const random = seededRandom(20261104);
    let nodes = [];
    let packets = [];

    function spawnPacket(nodeCount) {
        const from = Math.floor(random() * nodeCount);
        let to = Math.floor(random() * (nodeCount - 1));

        if (to >= from) {
            to += 1;
        }

        return {
            from,
            to,
            progress: random() * 0.3,
            duration: 2.2 + random() * 1.6,
            bulge: (random() - 0.5) * 0.55,
        };
    }

    function build(w, h) {
        const nodeCount = w < 640 ? 5 : 7;
        const packetCount = w < 640 ? 9 : 20;

        nodes = Array.from({ length: nodeCount }, (_, index) => ({
            phase: (index / nodeCount) * Math.PI * 2,
            fx: 0.9 + random() * 0.5,
            fy: 0.9 + random() * 0.5,
            x: 0,
            y: 0,
        }));

        packets = Array.from({ length: packetCount }, () => spawnPacket(nodeCount));
    }

    function step(ctx, w, h, t, dt) {
        // Fade previous frames instead of clearing: leaves comet trails.
        ctx.globalCompositeOperation = 'destination-out';
        ctx.fillStyle = 'rgba(0, 0, 0, 0.22)';
        ctx.fillRect(0, 0, w, h);
        ctx.globalCompositeOperation = 'source-over';

        // Shop nodes drifting on out-of-phase circular orbits (Lissajous-like).
        const cx = w / 2;
        const cy = h / 2;
        const rx = Math.min(w * 0.36, 430);
        const ry = Math.min(h * 0.3, 240);

        for (const node of nodes) {
            node.x = cx + rx * Math.sin(node.phase + t * 0.21 * node.fx);
            node.y = cy + ry * Math.cos(node.phase * 1.7 + t * 0.16 * node.fy);

            ctx.beginPath();
            ctx.arc(node.x, node.y, 14, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(99, 102, 241, 0.1)';
            ctx.fill();

            ctx.beginPath();
            ctx.arc(node.x, node.y, 7.5, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(129, 140, 248, 0.3)';
            ctx.fill();

            ctx.beginPath();
            ctx.arc(node.x, node.y, 3, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(224, 231, 255, 0.95)';
            ctx.fill();
        }

        // Order packets travel node→node along quadratic Bézier curves.
        for (const packet of packets) {
            packet.progress += dt / packet.duration;

            if (packet.progress >= 1) {
                // Continue from the node just reached; pick a fresh destination.
                const from = packet.to;
                let to = Math.floor(random() * (nodes.length - 1));

                if (to >= from) {
                    to += 1;
                }

                Object.assign(packet, spawnPacket(nodes.length), { from, to, progress: 0 });
            }

            const start = nodes[packet.from];
            const end = nodes[packet.to];

            if (!start || !end) {
                continue;
            }

            const u = smoothstep(Math.min(packet.progress, 1));
            const mx = (start.x + end.x) / 2;
            const my = (start.y + end.y) / 2;
            const dx = end.x - start.x;
            const dy = end.y - start.y;
            const length = Math.hypot(dx, dy) || 1;
            const bulge = packet.bulge * Math.min(w, h);

            const px = mx - (dy / length) * bulge;
            const py = my + (dx / length) * bulge;

            const x = (1 - u) * (1 - u) * start.x + 2 * (1 - u) * u * px + u * u * end.x;
            const y = (1 - u) * (1 - u) * start.y + 2 * (1 - u) * u * py + u * u * end.y;

            ctx.beginPath();
            ctx.arc(x, y, 2, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(196, 181, 253, 0.9)';
            ctx.fill();
        }
    }

    return { build, step };
}

/* =====================================================================
   Scene: rose — r = R·cos(5θ), traced progressively
   ===================================================================== */
function makeRoseScene() {
    const TRACE = 6.5;
    const HOLD = 1.4;

    function step(ctx, w, h, t) {
        ctx.clearRect(0, 0, w, h);

        const cx = w / 2;
        const cy = h / 2;
        const radius = Math.min(w, h) * 0.36;
        const cycle = TRACE + HOLD;
        const phase = t % cycle;
        const thetaMax = phase <= TRACE ? Math.PI * (phase / TRACE) : Math.PI;

        ctx.beginPath();
        ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(99, 102, 241, 0.14)';
        ctx.lineWidth = 1;
        ctx.stroke();

        // Odd petal count: θ over [0, π] covers the entire curve exactly once.
        const steps = 240;

        // Ghost of the finished curve so the panel never looks empty
        // while the trace is at an early angle.
        ctx.beginPath();

        for (let i = 0; i <= steps; i++) {
            const theta = (Math.PI * i) / steps;
            const r = radius * Math.cos(5 * theta);
            const x = cx + r * Math.cos(theta);
            const y = cy + r * Math.sin(theta);

            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }

        ctx.strokeStyle = 'rgba(129, 140, 248, 0.16)';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        const drawn = Math.max(2, Math.round(steps * (thetaMax / Math.PI)));

        ctx.beginPath();

        for (let i = 0; i <= drawn; i++) {
            const theta = (thetaMax * i) / drawn;
            const r = radius * Math.cos(5 * theta);
            const x = cx + r * Math.cos(theta);
            const y = cy + r * Math.sin(theta);

            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }

        ctx.strokeStyle = '#a5b4fc';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.stroke();

        // Pen dot sitting exactly on the curve's leading edge.
        const penTheta = thetaMax;
        const penR = radius * Math.cos(5 * penTheta);
        const penX = cx + penR * Math.cos(penTheta);
        const penY = cy + penR * Math.sin(penTheta);

        ctx.beginPath();
        ctx.arc(penX, penY, 9, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(199, 210, 254, 0.25)';
        ctx.fill();

        ctx.beginPath();
        ctx.arc(penX, penY, 3.5, 0, Math.PI * 2);
        ctx.fillStyle = '#e0e7ff';
        ctx.fill();
    }

    return { step };
}

/* =====================================================================
   Scene: waves — two traveling sines and their superposition
   ===================================================================== */
function makeWaveScene() {
    function step(ctx, w, h, t) {
        ctx.clearRect(0, 0, w, h);

        const midline = h * 0.52;
        const amplitude = h * 0.14;
        const lambda1 = w / 2.1;
        const lambda2 = w / 3.4;
        const omega1 = 1.5;
        const omega2 = 2.2;

        ctx.beginPath();
        ctx.moveTo(0, midline);
        ctx.lineTo(w, midline);
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.2)';
        ctx.lineWidth = 1;
        ctx.stroke();

        const sample = (fn) => {
            ctx.beginPath();

            for (let x = 0; x <= w; x += 3) {
                const y = midline + fn(x);

                if (x === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            }

            ctx.stroke();
        };

        ctx.lineWidth = 1.5;
        ctx.strokeStyle = 'rgba(45, 212, 191, 0.55)';
        sample((x) => amplitude * Math.sin((2 * Math.PI * x) / lambda1 - omega1 * t));

        ctx.strokeStyle = 'rgba(129, 140, 248, 0.55)';
        sample((x) => amplitude * 0.55 * Math.sin((2 * Math.PI * x) / lambda2 + omega2 * t));

        ctx.lineWidth = 2.5;
        ctx.strokeStyle = '#c4b5fd';
        sample((x) =>
            amplitude * Math.sin((2 * Math.PI * x) / lambda1 - omega1 * t) +
            amplitude * 0.55 * Math.sin((2 * Math.PI * x) / lambda2 + omega2 * t)
        );
    }

    return { step };
}

/* =====================================================================
   Scene: growth — logistic curve drawing itself, with data points
   ===================================================================== */
function makeGrowthScene() {
    const random = seededRandom(99);
    let dots = [];

    function reset(w) {
        dots = Array.from({ length: 12 }, () => ({
            x: w * (0.15 + random() * 0.72),
            born: -1,
        }));
    }

    function step(ctx, w, h, t, dt) {
        ctx.clearRect(0, 0, w, h);

        const baseline = h * 0.86;
        const top = h * 0.16;
        const midpointX = w * 0.42;
        const steepness = 9 / w;
        const logistic = (x) => 1 / (1 + Math.exp(-steepness * (x - midpointX)));

        const TRACE = 6;
        const phase = t % (TRACE + 1);
        const progress = phase <= TRACE ? smoothstep(phase / TRACE) : 1;
        const frontX = w * 0.06 + (w * 0.88 - w * 0.06) * progress;

        // Area under the drawn portion.
        ctx.beginPath();
        ctx.moveTo(w * 0.06, baseline);

        for (let x = w * 0.06; x <= frontX; x += 3) {
            ctx.lineTo(x, baseline - (baseline - top) * logistic(x));
        }

        ctx.lineTo(frontX, baseline);
        ctx.closePath();

        // Fade the fill out toward its leading edge so the moving
        // boundary never shows a hard vertical seam.
        const fill = ctx.createLinearGradient(w * 0.06, 0, frontX, 0);
        const fadeStart = Math.max(0, 1 - 40 / Math.max(frontX - w * 0.06, 1));

        fill.addColorStop(0, 'rgba(99, 102, 241, 0.12)');
        fill.addColorStop(fadeStart, 'rgba(99, 102, 241, 0.12)');
        fill.addColorStop(1, 'rgba(99, 102, 241, 0)');
        ctx.fillStyle = fill;
        ctx.fill();

        // The curve itself.
        ctx.beginPath();

        for (let x = w * 0.06; x <= frontX; x += 3) {
            const y = baseline - (baseline - top) * logistic(x);

            if (x === w * 0.06) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }

        ctx.strokeStyle = '#a5b4fc';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.stroke();

        // Data points pop in as the curve passes them.
        for (const dot of dots) {
            if (dot.born < 0 && dot.x <= frontX) {
                dot.born = t;
            }

            if (dot.born < 0) {
                continue;
            }

            const age = Math.min((t - dot.born) / 0.4, 1);
            const scale = easeOutCubic(age);
            const y = baseline - (baseline - top) * logistic(dot.x);

            ctx.beginPath();
            ctx.arc(dot.x, y, 3 * scale, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(240, 171, 252, 0.95)';
            ctx.fill();
        }
    }

    return { reset, step };
}

/* =====================================================================
   Scene: signals — layered sines across the CTA band
   ===================================================================== */
function makeSignalsScene() {
    function step(ctx, w, h, t) {
        ctx.clearRect(0, 0, w, h);

        const layers = [
            { alpha: 0.5, amp: 0.3, lambda: 1.9, omega: 0.9, phase: 0 },
            { alpha: 0.38, amp: 0.22, lambda: 2.6, omega: -1.3, phase: 2.1 },
            { alpha: 0.26, amp: 0.15, lambda: 3.3, omega: 1.7, phase: 4.2 },
        ];

        for (const layer of layers) {
            const k = (2 * Math.PI) / (w / layer.lambda);
            const amplitude = h * layer.amp * 0.6;

            ctx.beginPath();

            for (let x = 0; x <= w; x += 3) {
                const y =
                    h * 0.5 +
                    amplitude * Math.sin(k * x + layer.omega * t + layer.phase) +
                    amplitude * 0.35 * Math.sin(k * 2.7 * x - layer.omega * 0.6 * t);

                if (x === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            }

            ctx.strokeStyle = `rgba(129, 140, 248, ${layer.alpha})`;
            ctx.lineWidth = 1.5;
            ctx.stroke();
        }
    }

    return { step };
}

/* =====================================================================
   Stage + ticker plumbing
   ===================================================================== */
const stages = [];
let cursorStage = null;
let rafId = null;
let lastTime = 0;

function sizeStage(stage) {
    const rect = stage.canvas.getBoundingClientRect();

    if (rect.width === 0 || rect.height === 0) {
        return;
    }

    const dpr = Math.min(window.devicePixelRatio || 1, 2);

    stage.canvas.width = Math.round(rect.width * dpr);
    stage.canvas.height = Math.round(rect.height * dpr);
    stage.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    stage.w = rect.width;
    stage.h = rect.height;

    stage.scene.reset?.(stage.w, stage.h);
    stage.scene.build?.(stage.w, stage.h);
}

function drawStatic(stage) {
    stage.scene.reset?.(stage.w, stage.h);
    stage.scene.build?.(stage.w, stage.h);
    stage.scene.step(stage.ctx, stage.w, stage.h, 2.4, 1 / 60);
}

function tick(now) {
    rafId = null;

    const t = now / 1000;
    const dt = Math.min(Math.max((now - lastTime) / 1000, 0), 0.05) || 0.016;

    lastTime = now;

    for (const stage of stages) {
        if (stage.visible && !document.hidden) {
            // Isolate scenes: a failure in one panel must not stop the rest.
            try {
                stage.scene.step(stage.ctx, stage.w, stage.h, t, dt);
            } catch (error) {
                console.warn('landing scene failed', stage.canvas.id, error);
                stage.scene = { step: () => {} };
            }
        }
    }

    if (cursorStage && cursorStage.visible && !document.hidden) {
        try {
            cursorStage.scene.step(cursorStage.ctx, cursorStage.w, cursorStage.h, t, dt);
        } catch (error) {
            console.warn('landing cursor scene failed', error);
            cursorStage.visible = false;
        }
    }

    rafId = requestAnimationFrame(tick);
}

function startTicker() {
    if (rafId === null) {
        lastTime = performance.now();
        rafId = requestAnimationFrame(tick);
    }
}

function stopTicker() {
    if (rafId !== null) {
        cancelAnimationFrame(rafId);
        rafId = null;
    }
}

/** The cursor comet only runs for fine pointers without reduced motion. */
function updateCursorStageMode() {
    if (!cursorStage) {
        return;
    }

    cursorStage.visible = finePointer.matches && !reducedMotion.matches;

    if (!cursorStage.visible) {
        cursorStage.ctx.clearRect(0, 0, cursorStage.w, cursorStage.h);
    }
}

function applyMotionPreference() {
    if (reducedMotion.matches) {
        stopTicker();
        stages.forEach(drawStatic);
        updateCursorStageMode();
    } else {
        updateCursorStageMode();
        startTicker();
    }
}

function createStage(canvas, makeScene) {
    const stage = {
        canvas,
        ctx: canvas.getContext('2d'),
        scene: makeScene(),
        w: 0,
        h: 0,
        visible: false,
    };

    const resizeObserver = new ResizeObserver(() => {
        sizeStage(stage);

        if (reducedMotion.matches) {
            drawStatic(stage);
        }
    });

    resizeObserver.observe(canvas);
    sizeStage(stage);

    const visibilityObserver = new IntersectionObserver((entries) => {
        stage.visible = entries[0].isIntersecting;

        if (reducedMotion.matches && stage.visible) {
            drawStatic(stage);
        }
    });

    visibilityObserver.observe(canvas);
    stages.push(stage);
}

function revealContent() {
    const elements = document.querySelectorAll('[data-landing-reveal]');

    elements.forEach((element) => {
        const delay = element.dataset.revealDelay;

        if (delay) {
            element.style.setProperty('--reveal-delay', delay);
        }
    });

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -35px 0px' }
    );

    elements.forEach((element) => observer.observe(element));
}

function setupCursorStage() {
    const canvas = document.getElementById('landing-cursor-canvas');

    if (!canvas) {
        return;
    }

    cursorStage = {
        canvas,
        ctx: canvas.getContext('2d'),
        scene: makeCursorScene(),
        w: 0,
        h: 0,
        visible: false,
    };

    const resizeObserver = new ResizeObserver(() => sizeStage(cursorStage));

    resizeObserver.observe(canvas);
    sizeStage(cursorStage);

    window.addEventListener('pointermove', (event) => {
        pointer.x = event.clientX;
        pointer.y = event.clientY;
        pointer.active = true;
    }, { passive: true });

    document.documentElement.addEventListener('pointerleave', () => {
        pointer.active = false;
    });
}

function initializeLanding() {
    const scenes = [
        ['landing-bg-canvas', makeBackgroundScene],
        ['landing-hero-canvas', makeHeroScene],
        ['landing-rose-canvas', makeRoseScene],
        ['landing-wave-canvas', makeWaveScene],
        ['landing-growth-canvas', makeGrowthScene],
        ['landing-signals-canvas', makeSignalsScene],
    ];

    const mounted = scenes.filter(([id]) => document.getElementById(id));

    if (mounted.length === 0) {
        return;
    }

    mounted.forEach(([id, makeScene]) => createStage(document.getElementById(id), makeScene));

    setupCursorStage();
    revealContent();

    reducedMotion.addEventListener?.('change', applyMotionPreference);
    finePointer.addEventListener?.('change', updateCursorStageMode);
    applyMotionPreference();
}

initializeLanding();
