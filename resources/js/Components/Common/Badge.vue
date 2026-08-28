<script setup>
defineProps({
    tone: { type: String, default: 'neutral' }, // neutral | success | warning | danger | accent
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="{
            'bg-ink-100 text-ink-700': tone === 'neutral',
            'bg-accent-100 text-accent-800': tone === 'success' || tone === 'accent',
            'bg-amber-100 text-amber-800': tone === 'warning',
            'bg-red-100 text-red-700': tone === 'danger',
        }"
    >
        <slot />
    </span>
</template>
