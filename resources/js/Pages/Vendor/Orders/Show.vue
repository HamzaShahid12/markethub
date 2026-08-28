<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Order details' }, () => page) });

const props = defineProps({
    order: { type: Object, required: true },
});

const toast = useToast();

const statusTone = {
    pending: 'warning', processing: 'accent', shipped: 'accent',
    delivered: 'success', cancelled: 'danger',
};

const nextStatuses = {
    pending: ['processing', 'cancelled'],
    processing: ['shipped', 'cancelled'],
    shipped: ['delivered'],
    delivered: [],
    cancelled: [],
};

function updateStatus(item, status) {
    router.put(`/vendor/orders/${props.order.id}/items/${item.id}/status`, { status }, {
        preserveScroll: true,
        onSuccess: () => toast.success(`Marked as ${status}.`),
        onError: () => toast.error('Could not update that item.'),
    });
}

function money(value) {
    return `$${Number(value).toFixed(2)}`;
}
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <p class="text-xs text-ink-400"><Link href="/vendor/orders" class="hover:text-ink-700">Orders</Link> / {{ order.order_number }}</p>
    <h1 class="mt-1 font-display text-xl font-bold text-ink-900">Order {{ order.order_number }}</h1>
    <p class="text-sm text-ink-500">{{ order.customer_name }} · placed {{ new Date(order.created_at).toLocaleString() }}</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Item</th>
                        <th class="px-5 py-3 font-medium">Qty</th>
                        <th class="px-5 py-3 font-medium">Subtotal</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="item in order.items" :key="item.id">
                        <td class="px-5 py-3 font-medium text-ink-900">
                            <Link v-if="item.product_slug" :href="`/products/${item.product_slug}`" class="hover:text-accent-600">{{ item.product_name }}</Link>
                            <span v-else>{{ item.product_name }}</span>
                            <p class="text-xs text-ink-400">{{ item.sku }}</p>
                        </td>
                        <td class="px-5 py-3 text-ink-600">{{ item.quantity }}</td>
                        <td class="px-5 py-3 text-ink-700">{{ money(item.subtotal) }}</td>
                        <td class="px-5 py-3"><Badge :tone="statusTone[item.status]" class="capitalize">{{ item.status }}</Badge></td>
                        <td class="px-5 py-3">
                            <select
                                v-if="nextStatuses[item.status]?.length"
                                class="rounded-lg border-ink-200 text-xs focus:border-accent-500 focus:ring-accent-500"
                                @change="updateStatus(item, $event.target.value)"
                            >
                                <option value="" disabled selected>Set status…</option>
                                <option v-for="s in nextStatuses[item.status]" :key="s" :value="s">{{ s }}</option>
                            </select>
                            <span v-else class="text-xs text-ink-400">—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <aside class="h-fit space-y-2 rounded-xl border border-ink-100 bg-white p-6 shadow-card text-sm text-ink-500">
            <p class="font-medium text-ink-700">Shipping address</p>
            <p>{{ order.shipping_address.name }}</p>
            <p>{{ order.shipping_address.line1 }}</p>
            <p>{{ order.shipping_address.city }}, {{ order.shipping_address.state }} {{ order.shipping_address.postal_code }}</p>
            <p>{{ order.shipping_address.country }}</p>
        </aside>
    </div>
</template>
