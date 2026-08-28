<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBag } from 'lucide-vue-next';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Orders' }, () => page) });

defineProps({
    orders: { type: Object, required: true },
});

const statusTone = {
    pending: 'warning', processing: 'accent', shipped: 'accent',
    delivered: 'success', cancelled: 'danger', refunded: 'neutral',
};
</script>

<template>
    <Head title="Orders" />

    <div v-if="orders.data.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Order</th>
                    <th class="px-5 py-3 font-medium">Customer</th>
                    <th class="px-5 py-3 font-medium">Your items</th>
                    <th class="px-5 py-3 font-medium">Your subtotal</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Placed</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="order in orders.data" :key="order.id" class="hover:bg-ink-50">
                    <td class="px-5 py-3 font-medium text-ink-900">{{ order.order_number }}</td>
                    <td class="px-5 py-3 text-ink-600">{{ order.customer_name }}</td>
                    <td class="px-5 py-3 text-ink-600">{{ order.my_items_count }}</td>
                    <td class="px-5 py-3 text-ink-700">${{ Number(order.my_subtotal).toFixed(2) }}</td>
                    <td class="px-5 py-3"><Badge :tone="statusTone[order.status]" class="capitalize">{{ order.status }}</Badge></td>
                    <td class="px-5 py-3 text-ink-500">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                    <td class="px-5 py-3 text-right">
                        <Link :href="`/vendor/orders/${order.id}`" class="text-sm font-medium text-accent-600 hover:text-accent-700">View</Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <EmptyState v-else :icon="ShoppingBag" title="No orders yet" description="Orders containing your products will show up here." />

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
