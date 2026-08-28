<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Array, required: true }, // [{ date, revenue }]
});

const max = computed(() => Math.max(1, ...props.data.map((d) => Number(d.revenue))));

function heightPct(value) {
    return Math.max(4, Math.round((Number(value) / max.value) * 100));
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}
</script>

<template>
    <div v-if="data.length" class="flex h-40 items-end gap-1">
        <div
            v-for="point in data"
            :key="point.date"
            class="group relative flex-1"
        >
            <div
                class="w-full rounded-t bg-accent-400 transition-colors group-hover:bg-accent-600"
                :style="{ height: `${heightPct(point.revenue)}%` }"
            />
            <div class="pointer-events-none absolute -top-9 left-1/2 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-ink-900 px-2 py-1 text-xs text-white group-hover:block">
                {{ formatDate(point.date) }} · ${{ Number(point.revenue).toFixed(2) }}
            </div>
        </div>
    </div>
    <p v-else class="text-sm text-ink-400">No sales in this period yet.</p>
</template>
