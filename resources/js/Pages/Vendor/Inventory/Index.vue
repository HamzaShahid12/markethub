<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { AlertTriangle, Boxes } from 'lucide-vue-next';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Inventory' }, () => page) });

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
    threshold: { type: Number, required: true },
});

const toast = useToast();
const editing = ref({}); // { [key]: newValue }

function toggleLowStockOnly() {
    router.get('/vendor/inventory', { low_stock_only: props.filters.low_stock_only ? null : 1 }, { preserveState: true, preserveScroll: true });
}

function saveProductStock(product) {
    const key = `product-${product.id}`;
    const value = editing.value[key];
    if (value === undefined || value === '') return;

    router.put(`/vendor/inventory/products/${product.id}`, { stock: value }, {
        preserveScroll: true,
        onSuccess: () => { toast.success('Stock updated.'); delete editing.value[key]; },
    });
}

function saveVariantStock(variant) {
    const key = `variant-${variant.id}`;
    const value = editing.value[key];
    if (value === undefined || value === '') return;

    router.put(`/vendor/inventory/variants/${variant.id}`, { stock: value }, {
        preserveScroll: true,
        onSuccess: () => { toast.success('Stock updated.'); delete editing.value[key]; },
    });
}
</script>

<template>
    <Head title="Inventory" />

    <div class="mb-6 flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" :checked="filters.low_stock_only" class="rounded border-ink-300 text-accent-600 focus:ring-accent-500" @change="toggleLowStockOnly" />
            Show low stock only (≤ {{ threshold }} units)
        </label>
    </div>

    <div v-if="products.data.length" class="space-y-4">
        <div v-for="product in products.data" :key="product.id" class="rounded-xl border border-ink-100 bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-ink-900">{{ product.name }}</p>
                    <p class="text-xs text-ink-400">{{ product.sku }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge v-if="product.low_stock" tone="danger"><AlertTriangle class="mr-1 inline h-3 w-3" /> Low stock</Badge>
                    <input
                        type="number"
                        min="0"
                        :value="editing[`product-${product.id}`] ?? product.stock"
                        class="w-24 rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                        @input="editing[`product-${product.id}`] = $event.target.value"
                        @keyup.enter="saveProductStock(product)"
                        @blur="saveProductStock(product)"
                    />
                </div>
            </div>

            <div v-if="product.variants.length" class="mt-3 space-y-2 border-t border-ink-100 pt-3">
                <div v-for="variant in product.variants" :key="variant.id" class="flex items-center justify-between gap-3 pl-4 text-sm">
                    <span class="text-ink-600">{{ variant.label || variant.sku }}</span>
                    <div class="flex items-center gap-2">
                        <Badge v-if="variant.low_stock" tone="danger">Low</Badge>
                        <input
                            type="number"
                            min="0"
                            :value="editing[`variant-${variant.id}`] ?? variant.stock"
                            class="w-20 rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                            @input="editing[`variant-${variant.id}`] = $event.target.value"
                            @keyup.enter="saveVariantStock(variant)"
                            @blur="saveVariantStock(variant)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <EmptyState v-else :icon="Boxes" title="Nothing to show" description="No products match this filter." />
</template>
