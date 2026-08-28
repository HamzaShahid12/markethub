<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Package } from 'lucide-vue-next';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';

defineOptions({ layout: (h, page) => h(CustomerLayout, { title: 'Orders' }, () => page) });

defineProps({
    orders: { type: Object, required: true },
});

const statusTone = {
    pending: 'warning', processing: 'accent', shipped: 'accent',
    delivered: 'success', cancelled: 'danger', refunded: 'neutral',
};
</script>

<template>
    <Head title="My orders" />

    <div v-if="orders.data.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Order</th>
                    <th class="px-5 py-3 font-medium">Items</th>
                    <th class="px-5 py-3 font-medium">Total</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Placed</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="order in orders.data" :key="order.id" class="hover:bg-ink-50">
                    <td class="px-5 py-3 font-medium text-ink-900">{{ order.order_number }}</td>
                    <td class="px-5 py-3 text-ink-600">{{ order.items_count }}</td>
                    <td class="px-5 py-3 text-ink-700">${{ Number(order.total).toFixed(2) }}</td>
                    <td class="px-5 py-3"><Badge :tone="statusTone[order.status]" class="capitalize">{{ order.status }}</Badge></td>
                    <td class="px-5 py-3 text-ink-500">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                    <td class="px-5 py-3 text-right">
                        <Link :href="`/customer/orders/${order.id}`" class="text-sm font-medium text-accent-600 hover:text-accent-700">View</Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <EmptyState v-else :icon="Package" title="No orders yet" description="Once you place an order, you'll be able to track it here." />

    <div v-if="orders.links.length > 3" class="mt-6 flex flex-wrap gap-1">
        <Link
            v-for="link in orders.links"
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
