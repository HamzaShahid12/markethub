<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Package, Search } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Products' }, () => page) });

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
});

const toast = useToast();
const statusTone = { draft: 'neutral', published: 'success', archived: 'danger' };
const tabs = ['all', 'published', 'draft', 'archived'];

function filterByStatus(status) {
    router.get('/admin/products', { ...props.filters, status }, { preserveState: true, preserveScroll: true });
}

function filterByCategory(e) {
    router.get('/admin/products', { ...props.filters, category_id: e.target.value || null }, { preserveState: true, preserveScroll: true });
}

function search(e) {
    router.get('/admin/products', { ...props.filters, search: e.target.value }, { preserveState: true, preserveScroll: true, replace: true });
}

function toggleArchive(product) {
    const next = product.status === 'archived' ? 'published' : 'archived';

    router.put(`/admin/products/${product.id}/status`, { status: next }, {
        preserveScroll: true,
        onSuccess: () => toast.success(`Product ${next}.`),
    });
}
</script>

<template>
    <Head title="Products" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <button
                v-for="tab in tabs"
                :key="tab"
                type="button"
                class="rounded-full px-4 py-1.5 text-sm font-medium capitalize transition-colors"
                :class="filters.status === tab ? 'bg-ink-900 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100'"
                @click="filterByStatus(tab)"
            >
                {{ tab }}
            </button>
        </div>
        <div class="flex gap-2">
            <select :value="filters.category_id ?? ''" class="rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" @change="filterByCategory">
                <option value="">All categories</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
                <input type="search" placeholder="Product name..." :value="filters.search" class="rounded-lg border-ink-200 py-2 pl-9 pr-3 text-sm focus:border-accent-500 focus:ring-accent-500" @change="search" />
            </div>
        </div>
    </div>

    <div v-if="products.data.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Product</th>
                    <th class="px-5 py-3 font-medium">Vendor</th>
                    <th class="px-5 py-3 font-medium">Category</th>
                    <th class="px-5 py-3 font-medium">Price</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="product in products.data" :key="product.id" class="hover:bg-ink-50">
                    <td class="px-5 py-3 font-medium text-ink-900">{{ product.name }}</td>
                    <td class="px-5 py-3 text-ink-500">{{ product.vendor?.shop_name }}</td>
                    <td class="px-5 py-3 text-ink-500">{{ product.category?.name }}</td>
                    <td class="px-5 py-3 text-ink-700">${{ Number(product.sale_price ?? product.price).toFixed(2) }}</td>
                    <td class="px-5 py-3"><Badge :tone="statusTone[product.status]" class="capitalize">{{ product.status }}</Badge></td>
                    <td class="px-5 py-3">
                        <button type="button" class="text-sm font-medium" :class="product.status === 'archived' ? 'text-accent-600 hover:text-accent-700' : 'text-red-600 hover:text-red-700'" @click="toggleArchive(product)">
                            {{ product.status === 'archived' ? 'Restore' : 'Archive' }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <EmptyState v-else :icon="Package" title="No products found" description="Nothing matches this filter yet." />

    <div v-if="products.links.length > 3" class="mt-6 flex flex-wrap gap-1">
        <Link
            v-for="link in products.links"
            :key="link.label"
            :href="link.url ?? '#'"
            v-html="link.label"
            preserve-scroll
            preserve-state
            class="rounded-lg px-3 py-1.5 text-sm"
            :class="[
                link.active ? 'bg-ink-900 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100',
                !link.url && 'pointer-events-none opacity-40',
            ]"
        />
    </div>
</template>
