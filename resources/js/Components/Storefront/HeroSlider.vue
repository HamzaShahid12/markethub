<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    slides: { type: Array, required: true },
    autoplayMs: { type: Number, default: 5000 },
});

const active = ref(0);
let timer = null;

function goTo(index) {
    active.value = (index + props.slides.length) % props.slides.length;
}

function next() {
    goTo(active.value + 1);
}

function prev() {
    goTo(active.value - 1);
}

function startAutoplay() {
    stopAutoplay();
    timer = setInterval(next, props.autoplayMs);
}

function stopAutoplay() {
    if (timer) clearInterval(timer);
}

onMounted(startAutoplay);
onBeforeUnmount(stopAutoplay);
</script>

<template>
    <section
        class="relative overflow-hidden border-b border-ink-100"
        @mouseenter="stopAutoplay"
        @mouseleave="startAutoplay"
    >
        <div class="relative h-[420px] sm:h-[480px] lg:h-[560px]">
            <TransitionGroup name="slide">
                <div
                    v-for="(slide, i) in slides"
                    v-show="i === active"
                    :key="slide.id ?? i"
                    class="absolute inset-0 flex items-center bg-ink-900 bg-cover bg-center"
                    :style="slide.image ? { backgroundImage: `linear-gradient(90deg, rgba(10,14,19,0.85), rgba(10,14,19,0.35)), url(${slide.image})` } : {}"
                >
                    <div class="container-page">
                        <div class="max-w-xl">
                            <p v-if="slide.eyebrow" class="text-sm font-medium uppercase tracking-widest text-accent-400">
                                {{ slide.eyebrow }}
                            </p>
                            <h1 class="mt-4 font-display text-3xl font-extrabold leading-tight text-white sm:text-4xl lg:text-5xl">
                                {{ slide.title }}
                            </h1>
                            <p v-if="slide.subtitle" class="mt-5 text-ink-200">
                                {{ slide.subtitle }}
                            </p>
                            <Link
                                v-if="slide.ctaLabel"
                                :href="slide.ctaHref ?? '/products'"
                                class="mt-8 inline-flex items-center gap-2 rounded-lg bg-accent-500 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-600"
                            >
                                {{ slide.ctaLabel }}
                            </Link>
                        </div>
                    </div>
                </div>
            </TransitionGroup>
        </div>

        <!-- Prev / Next arrows -->
        <button
            type="button"
            class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition-colors hover:bg-white/25"
            aria-label="Previous slide"
            @click="prev"
        >
            <ChevronLeft class="h-5 w-5" />
        </button>
        <button
            type="button"
            class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition-colors hover:bg-white/25"
            aria-label="Next slide"
            @click="next"
        >
            <ChevronRight class="h-5 w-5" />
        </button>

        <!-- Dots -->
        <div class="absolute bottom-5 left-1/2 flex -translate-x-1/2 gap-2">
            <button
                v-for="(slide, i) in slides"
                :key="`dot-${i}`"
                type="button"
                class="h-2 rounded-full transition-all"
                :class="i === active ? 'w-6 bg-white' : 'w-2 bg-white/40 hover:bg-white/60'"
                :aria-label="`Go to slide ${i + 1}`"
                @click="goTo(i)"
            />
        </div>
    </section>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: opacity 0.6s ease;
}
.slide-enter-from,
.slide-leave-to {
    opacity: 0;
}
</style>