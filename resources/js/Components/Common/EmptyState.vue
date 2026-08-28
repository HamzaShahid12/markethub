<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-ink-200 bg-white px-6 py-14 text-center">
        <span v-if="icon" class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-ink-50 text-ink-400">
            <component :is="icon" class="h-6 w-6" />
        </span>
        <p class="font-medium text-ink-800">{{ title }}</p>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-ink-500">{{ description }}</p>
        <slot />
    </div>
</template>
