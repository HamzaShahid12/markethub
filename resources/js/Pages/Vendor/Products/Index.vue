<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Package, Search } from 'lucide-vue-next';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Products' }, () => page) });

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const toast = useToast();

const statusTone = { draft: 'neutral', published: 'success', archived: 'danger' };
const tabs = [
    { key: 'all', label: 'All' },
    { key: 'published', label: 'Published' },
    { key: 'draft', label: 'Draft' },
    { key: 'archived', label: 'Archived' },
];

function filterByStatus(status) {
    router.get('/vendor/products', { ...props.filters, status }, { preserveState: true, preserveScroll: true });
}

function search(e) {
    router.get('/vendor/products', { ...props.filters, search: e.target.value }, { preserveState: true, preserveScroll: true, replace: true });
}

function remove(product) {
    if (!confirm(`Delete "${product.name}"? This can't be undone.`)) return;

    router.delete(`/vendor/products/${product.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Product removed.'),
    });
}
</script>

<template>
    <Head title="My products" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                :class="filters.status === tab.key ? 'bg-ink-900 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100'"
                @click="filterByStatus(tab.key)"
            >
                {{ tab.label }}
            </button>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
                <input
                    type="search"
                    placeholder="Search products..."
                    :value="filters.search"
                    class="rounded-lg border-ink-200 py-2 pl-9 pr-3 text-sm focus:border-accent-500 focus:ring-accent-500"
                    @change="search"
                />
            </div>
            <Link href="/vendor/products/create">
                <Button variant="primary"><Plus class="h-4 w-4" /> Add product</Button>
            </Link>
        </div>
    </div>

    <div v-if="products.data.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Product</th>
                    <th class="px-5 py-3 font-medium">Category</th>
                    <th class="px-5 py-3 font-medium">Price</th>
                    <th class="px-5 py-3 font-medium">Stock</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="product in products.data" :key="product.id" class="hover:bg-ink-50">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-ink-50">
                                <img v-if="product.images[0]" :src="`/storage/${product.images[0].image}`" loading="lazy" class="h-full w-full object-cover" alt="" />
                            </div>
                            <span class="font-medium text-ink-900">{{ product.name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-ink-500">{{ product.category?.name }}</td>
                    <td class="px-5 py-3 text-ink-700">${{ Number(product.sale_price ?? product.price).toFixed(2) }}</td>
                    <td class="px-5 py-3" :class="product.stock === 0 ? 'text-red-600' : 'text-ink-700'">{{ product.stock }}</td>
                    <td class="px-5 py-3"><Badge :tone="statusTone[product.status]" class="capitalize">{{ product.status }}</Badge></td>
                    <td class="px-5 py-3">
                        <div class="flex gap-1.5">
                            <Link :href="`/vendor/products/${product.id}/edit`" class="rounded-lg p-1.5 text-ink-500 hover:bg-ink-100 hover:text-ink-900" aria-label="Edit">
                                <Pencil class="h-4 w-4" />
                            </Link>
                            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-red-50 hover:text-red-600" aria-label="Delete" @click="remove(product)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <EmptyState v-else :icon="Package" title="No products yet" description="Add your first product to start selling.">
        <Link href="/vendor/products/create" class="mt-4">
            <Button variant="primary"><Plus class="h-4 w-4" /> Add product</Button>
        </Link>
    </EmptyState>

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
