<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: [Object, Function], default: null },
    tone: { type: String, default: 'default' }, // default | accent
});
</script>

<template>
    <div class="rounded-xl border border-ink-100 bg-white p-5 shadow-card">
        <div class="flex items-center justify-between">
            <p class="text-sm text-ink-500">{{ label }}</p>
            <span
                v-if="icon"
                class="flex h-8 w-8 items-center justify-center rounded-lg"
                :class="tone === 'accent' ? 'bg-accent-100 text-accent-700' : 'bg-ink-100 text-ink-600'"
            >
                <component :is="icon" class="h-4 w-4" />
            </span>
        </div>
        <p class="mt-2 font-display text-2xl font-bold text-ink-900">{{ value }}</p>
    </div>
</template>
