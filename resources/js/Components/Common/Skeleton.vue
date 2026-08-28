<script setup>
defineProps({
    rounded: { type: String, default: 'rounded-lg' },
});
</script>

<template>
    <div class="animate-pulse bg-ink-100" :class="rounded" role="status" aria-label="Loading">
        <span class="sr-only">Loading…</span>
    </div>
</template>
