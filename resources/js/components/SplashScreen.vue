<template>
    <Transition name="splash-fade">
        <div v-if="isVisible"
            class="fixed inset-0 z-[100] flex items-center justify-center overflow-hidden bg-sage-50/70 px-4 py-4 sm:px-6 sm:py-6"
            aria-label="Splash screen">
            <!-- Ambient gradient wash -->
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(106,156,94,0.12),transparent_56%),radial-gradient(circle_at_80%_0%,rgba(106,156,94,0.08),transparent_45%)]">
            </div>

            <!-- Soft hill silhouette bleeding behind the card -->
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-40 sm:h-56" aria-hidden="true">
                <svg viewBox="0 0 1000 200" preserveAspectRatio="none" class="h-full w-full">
                    <path d="M0,140 C200,80 400,100 500,120 C650,150 800,80 1000,110 L1000,200 L0,200 Z"
                        class="text-sage-200" fill="currentColor" fill-opacity="0.35" />
                    <path d="M0,170 C220,120 420,140 520,155 C680,175 820,120 1000,150 L1000,200 L0,200 Z"
                        class="text-sage-300" fill="currentColor" fill-opacity="0.25" />
                </svg>
            </div>

            <!-- Card: scaled to fit whatever space is available, so it never needs to scroll -->
            <article ref="cardEl"
                class="relative z-10 w-full max-w-3xl rounded-2xl border border-sage-100 bg-white px-6 pb-8 pt-7 text-center shadow-[0_22px_55px_rgba(29,43,37,0.12)] transition-[transform,opacity] duration-300 ease-out motion-reduce:transition-none sm:px-10 sm:pb-10 sm:pt-9"
                :class="isExiting ? 'opacity-0' : 'opacity-100'" :style="{ transform: cardTransform }">
                <!-- Corner dot-grid ornaments -->
                <svg v-for="corner in corners" :key="corner.key"
                    class="pointer-events-none absolute h-8 w-8 text-sage-300/50" :class="corner.classes"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <circle v-for="(dot, i) in dotGrid" :key="i" :cx="dot.x" :cy="dot.y" r="1" fill="currentColor" />
                </svg>

                <!-- Logo -->
                <div
                    class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-md border border-sage-200/70 bg-sage-50 sm:mb-6 sm:h-28 sm:w-28">
                    <img v-if="resolvedLogo" :src="resolvedLogo" alt="Al-Hikmah logo"
                        class="h-full w-full rounded-md object-contain p-1">
                    <div v-else class="space-y-1 px-2">
                        <div class="h-[1px] w-12 bg-sage-300/60"></div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.26em] text-sage-500/90">Logo</p>
                        <div class="h-[1px] w-12 bg-sage-300/60"></div>
                    </div>
                </div>

                <!-- Title -->
                <h1
                    class="mx-auto max-w-2xl font-serif text-[1.55rem] font-bold uppercase tracking-[0.08em] text-slate-900 sm:text-[2rem]">
                    {{ mergedConfig.projectTitle }}
                </h1>
                <p
                    class="mx-auto mt-2 max-w-xl text-[0.68rem] font-semibold uppercase tracking-[0.19em] text-sage-700/90 sm:text-[0.78rem]">
                    {{ mergedConfig.subtitle }}
                </p>

                <!-- Divider -->
                <div class="mx-auto mt-5 flex w-40 items-center justify-center gap-3 sm:mt-6" aria-hidden="true">
                    <span class="h-px flex-1 bg-gradient-to-r from-transparent to-sage-300"></span>
                    <span class="h-1.5 w-1.5 rotate-45 bg-sage-400"></span>
                    <span class="h-px flex-1 bg-gradient-to-l from-transparent to-sage-300"></span>
                </div>

                <!-- Author -->
                <div class="mt-4 space-y-1 text-slate-800">
                    <p class="font-serif text-[1.14rem] text-slate-700">By</p>
                    <p
                        class="font-serif text-[1.2rem] font-bold uppercase tracking-[0.04em] text-sage-700 sm:text-[1.34rem]">
                        {{ mergedConfig.authorName }}</p>
                    <p class="text-sm font-medium tracking-[0.06em] text-slate-500">{{ mergedConfig.studentID }}</p>
                </div>

                <!-- Description -->
                <p
                    class="mx-auto mt-6 max-w-2xl text-[0.93rem] leading-relaxed text-slate-600 sm:mt-7 sm:text-[0.99rem]">
                    {{ mergedConfig.description }}
                </p>

                <!-- University block -->
                <div class="mt-6 space-y-1 font-serif leading-tight sm:mt-7">
                    <p class="text-[1.02rem] font-bold uppercase tracking-[0.02em] text-sage-700 sm:text-[1.2rem]">{{
                        mergedConfig.department }}</p>
                    <p class="text-[1.02rem] font-bold uppercase tracking-[0.02em] text-sage-700 sm:text-[1.2rem]">{{
                        mergedConfig.faculty }}</p>
                    <p class="text-[1.02rem] font-bold uppercase tracking-[0.02em] text-sage-700 sm:text-[1.2rem]">{{
                        mergedConfig.university }}</p>
                    <p class="text-[1.02rem] font-bold uppercase tracking-[0.02em] text-sage-700 sm:text-[1.2rem]">{{
                        mergedConfig.location }}</p>
                </div>

                <!-- Divider -->
                <div class="mx-auto mt-6 flex w-40 items-center justify-center gap-3 sm:mt-7" aria-hidden="true">
                    <span class="h-px flex-1 bg-gradient-to-r from-transparent to-sage-300"></span>
                    <span class="h-1.5 w-1.5 rotate-45 bg-sage-400"></span>
                    <span class="h-px flex-1 bg-gradient-to-l from-transparent to-sage-300"></span>
                </div>

                <!-- Date -->
                <p
                    class="mt-1 font-serif text-[1.3rem] font-bold uppercase tracking-[0.09em] text-slate-900 sm:text-[1.5rem]">
                    {{ mergedConfig.date }}
                </p>

                <!-- Campus illustration: hill base + monoline building + trees + walkway -->
                <div class="relative mt-6 h-16 sm:mt-7 sm:h-20" aria-hidden="true">
                    <svg class="h-full w-full" viewBox="0 0 960 200" preserveAspectRatio="xMidYMax meet"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,150 C160,120 320,130 480,140 C640,150 800,120 960,145 L960,200 L0,200 Z"
                            class="text-sage-100" fill="currentColor" fill-opacity="0.7" />
                        <g class="text-sage-600" fill="none" stroke="currentColor" stroke-width="1.6"
                            stroke-linecap="round" stroke-linejoin="round" opacity="0.24">
                            <path d="M60,178 H900" />
                            <path d="M300,178 V132 H430 V178" />
                            <rect x="316" y="146" width="14" height="14" />
                            <rect x="346" y="146" width="14" height="14" />
                            <rect x="376" y="146" width="14" height="14" />
                            <rect x="406" y="146" width="14" height="14" />
                            <path d="M530,178 V132 H660 V178" />
                            <rect x="546" y="146" width="14" height="14" />
                            <rect x="576" y="146" width="14" height="14" />
                            <rect x="606" y="146" width="14" height="14" />
                            <rect x="636" y="146" width="14" height="14" />
                            <path d="M430,178 V110 L480,80 L530,110 V178" />
                            <rect x="463" y="140" width="34" height="38" />
                            <path d="M463,158 H497" />
                            <path d="M455,178 L432,200" />
                            <path d="M505,178 L528,200" />
                            <path d="M470,185 H490" />
                            <path d="M235,178 V150" />
                            <circle cx="235" cy="136" r="17" />
                            <path d="M725,178 V150" />
                            <circle cx="725" cy="136" r="17" />
                        </g>
                    </svg>
                </div>

                <!-- Proceed + progress -->
                <div class="mx-auto mt-6 flex max-w-md flex-col items-center gap-4 sm:mt-7">
                    <button v-if="mergedConfig.showProceedButton" type="button"
                        class="group inline-flex items-center rounded-full border border-sage-300 bg-white py-1.5 pl-6 pr-1.5 text-sm font-semibold uppercase tracking-[0.14em] text-slate-800 shadow-sm transition-colors duration-300 hover:border-sage-400 hover:bg-sage-50 motion-reduce:transition-none"
                        @click.stop="skipIntro">
                        Proceed
                        <span
                            class="ml-4 flex h-9 w-9 items-center justify-center rounded-full border border-sage-300 bg-sage-50 text-sage-700 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transition-none">
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 10h12M11 5l5 5-5 5" />
                            </svg>
                        </span>
                    </button>

                    <div v-if="mergedConfig.showProgress" class="flex items-center justify-center gap-2"
                        aria-hidden="true">
                        <span v-for="n in 4" :key="n" class="h-2 w-2 rounded-full border transition-all duration-300"
                            :class="n <= activeDotCount ? 'scale-110 border-sage-600 bg-sage-600' : 'border-sage-300 bg-transparent'"></span>
                    </div>
                </div>
            </article>
        </div>
    </Transition>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    config: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['complete']);

const defaultConfig = {};

const mergedConfig = computed(() => ({ ...defaultConfig, ...props.config }));
const resolvedLogo = computed(() => mergedConfig.value.logoSrc || mergedConfig.value.logo || '');

// Corner dot-grid ornaments — one 3x3 grid, reused in all four corners
const dotGrid = [2, 8, 14].flatMap((y) => [2, 8, 14].map((x) => ({ x, y })));
const corners = [
    { key: 'tl', classes: 'left-5 top-5' },
    { key: 'tr', classes: 'right-5 top-5' },
    { key: 'bl', classes: 'bottom-5 left-5' },
    { key: 'br', classes: 'bottom-5 right-5' }
];

const isVisible = ref(false);
const isExiting = ref(false);
const progress = ref(0);
const cardEl = ref(null);
const prefersReducedMotion = ref(false);

// --- Scale-to-fit: shrink the whole card so it always fits the viewport, no scrolling ---
const EDGE_MARGIN = 24;   // px of breathing room kept around the card on every side
const MIN_SCALE = 0.85;   // never shrink smaller than this, even on very short screens
const fitScale = ref(1);

const computeFitScale = () => {
    if (!cardEl.value) {
        return;
    }
    // offsetWidth/offsetHeight reflect the card's natural (untransformed) layout size,
    // so this stays accurate regardless of the scale already applied.
    const naturalWidth = cardEl.value.offsetWidth;
    const naturalHeight = cardEl.value.offsetHeight;
    if (!naturalWidth || !naturalHeight) {
        fitScale.value = 1;
        return;
    }

    const availableWidth = window.innerWidth - EDGE_MARGIN * 2;
    const availableHeight = window.innerHeight - EDGE_MARGIN * 2;

    const nextScale = Math.min(1, availableWidth / naturalWidth, availableHeight / naturalHeight);
    fitScale.value = Math.max(MIN_SCALE, nextScale);
};

let resizeRaf;
const scheduleComputeFitScale = () => {
    if (resizeRaf) {
        cancelAnimationFrame(resizeRaf);
    }
    resizeRaf = requestAnimationFrame(computeFitScale);
};

let resizeObserver;

// Combine the entrance/exit micro-pop with the fit-to-screen scale
const cardTransform = computed(() => `scale(${(isExiting.value ? 0.985 : 1) * fitScale.value})`);

// At least one dot lit from the start so the indicator always reads as "in progress"
const activeDotCount = computed(() => Math.min(4, Math.max(1, Math.ceil(progress.value / 25))));

const fadeDurationMs = computed(() => (prefersReducedMotion.value ? 0 : 420));

let timeoutId;
let rafId;
let startTime = 0;
let hasCompleted = false;

const durationMs = computed(() => {
    const raw = Number(mergedConfig.value.timerDuration);
    return Number.isFinite(raw) && raw > 0 ? raw : defaultConfig.timerDuration;
});

const cleanupTimer = () => {
    if (timeoutId) {
        clearTimeout(timeoutId);
        timeoutId = undefined;
    }
    if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = undefined;
    }
};

const completeIntro = () => {
    if (hasCompleted) {
        return;
    }
    hasCompleted = true;

    cleanupTimer();
    progress.value = 100;
    isExiting.value = true;

    window.setTimeout(() => {
        isVisible.value = false;
        emit('complete');
    }, fadeDurationMs.value);
};

const updateProgress = (timestamp) => {
    const elapsed = timestamp - startTime;
    const ratio = Math.min(elapsed / durationMs.value, 1);
    progress.value = ratio * 100;

    if (ratio < 1 && !hasCompleted) {
        rafId = requestAnimationFrame(updateProgress);
    }
};

const startTimer = () => {
    cleanupTimer();
    progress.value = 0;
    startTime = performance.now();
    rafId = requestAnimationFrame(updateProgress);
    timeoutId = window.setTimeout(() => {
        completeIntro();
    }, durationMs.value);
};

const skipIntro = () => {
    completeIntro();
};

onMounted(async () => {
    prefersReducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    isVisible.value = true;
    // Backup for iOS where nextTick can be delayed by address-bar animation
    setTimeout(() => { isVisible.value = true; }, 50);
    await nextTick();

    // Measure once layout is ready, then keep re-measuring if the viewport
    // or the card's own natural content size changes (e.g. logo image loads).
    computeFitScale();
    if (typeof ResizeObserver !== 'undefined' && cardEl.value) {
        resizeObserver = new ResizeObserver(() => scheduleComputeFitScale());
        resizeObserver.observe(cardEl.value);
    }
    window.addEventListener('resize', scheduleComputeFitScale);
    window.addEventListener('orientationchange', scheduleComputeFitScale);

    startTimer();
});

onBeforeUnmount(() => {
    cleanupTimer();
    if (resizeRaf) {
        cancelAnimationFrame(resizeRaf);
    }
    resizeObserver?.disconnect();
    window.removeEventListener('resize', scheduleComputeFitScale);
    window.removeEventListener('orientationchange', scheduleComputeFitScale);
});
</script>

<style scoped>
.splash-fade-enter-active,
.splash-fade-leave-active {
    transition: opacity 420ms ease;
}

.splash-fade-enter-from,
.splash-fade-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {

    .splash-fade-enter-active,
    .splash-fade-leave-active {
        transition: none;
    }
}
</style>
