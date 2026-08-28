<script setup>
import { X } from 'lucide-vue-next';

defineProps({
    filters: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    vendors: { type: Array, default: () => [] },
    lockedCategory: { type: Object, default: null },
});

const emit = defineEmits(['update', 'reset']);

function set(key, value) {
    emit('update', { key, value });
}
</script>

<template>
    <aside class="space-y-6">
        <div v-if="!lockedCategory">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">Category</p>
            <select
                :value="filters.category_id ?? ''"
                class="w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                @change="set('category_id', $event.target.value || null)"
            >
                <option value="">All categories</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>

        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">Vendor</p>
            <select
                :value="filters.vendor_id ?? ''"
                class="w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                @change="set('vendor_id', $event.target.value || null)"
            >
                <option value="">All vendors</option>
                <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.shop_name }}</option>
            </select>
        </div>

        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">Price range</p>
            <div class="flex items-center gap-2">
                <input
                    type="number"
                    min="0"
                    placeholder="Min"
                    :value="filters.min_price ?? ''"
                    class="w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                    @change="set('min_price', $event.target.value || null)"
                />
                <span class="text-ink-300">—</span>
                <input
                    type="number"
                    min="0"
                    placeholder="Max"
                    :value="filters.max_price ?? ''"
                    class="w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                    @change="set('max_price', $event.target.value || null)"
                />
            </div>
        </div>

        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">Minimum rating</p>
            <select
                :value="filters.min_rating ?? ''"
                class="w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                @change="set('min_rating', $event.target.value || null)"
            >
                <option value="">Any rating</option>
                <option v-for="r in [4, 3, 2, 1]" :key="r" :value="r">{{ r }}★ &amp; up</option>
            </select>
        </div>

        <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm text-ink-600">
                <input
                    type="checkbox"
                    :checked="!!filters.in_stock_only"
                    class="rounded border-ink-300 text-accent-600 focus:ring-accent-500"
                    @change="set('in_stock_only', $event.target.checked || null)"
                />
                In stock only
            </label>
            <label class="flex items-center gap-2 text-sm text-ink-600">
                <input
                    type="checkbox"
                    :checked="!!filters.on_sale"
                    class="rounded border-ink-300 text-accent-600 focus:ring-accent-500"
                    @change="set('on_sale', $event.target.checked || null)"
                />
                On sale
            </label>
        </div>

        <button
            type="button"
            class="flex items-center gap-1.5 text-sm font-medium text-ink-500 hover:text-ink-800"
            @click="emit('reset')"
        >
            <X class="h-3.5 w-3.5" /> Clear filters
        </button>
    </aside>
</template>
