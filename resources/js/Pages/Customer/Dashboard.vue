<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Package, Heart, Star, ArrowRight } from 'lucide-vue-next';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import Badge from '@/Components/Common/Badge.vue';

defineOptions({ layout: (h, page) => h(CustomerLayout, { title: 'Dashboard' }, () => page) });

defineProps({
    stats: { type: Object, required: true },
    recentOrders: { type: Array, default: () => [] },
});

const statusTone = {
    pending: 'warning',
    processing: 'accent',
    shipped: 'accent',
    delivered: 'success',
    cancelled: 'danger',
    refunded: 'neutral',
};
</script>

<template>
    <Head title="My dashboard" />

    <div class="grid gap-4 sm:grid-cols-3">
        <StatCard label="Orders" :value="stats.orders_count" :icon="Package" />
        <StatCard label="Wishlist items" :value="stats.wishlist_count" :icon="Heart" />
        <StatCard label="Reviews written" :value="stats.reviews_count" :icon="Star" />
    </div>

    <div class="mt-8">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-display text-lg font-semibold text-ink-900">Recent orders</h2>
            <Link href="/customer/orders" class="flex items-center gap-1 text-sm font-medium text-accent-600 hover:text-accent-700">
                View all <ArrowRight class="h-3.5 w-3.5" />
            </Link>
        </div>

        <div v-if="recentOrders.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Order</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Total</th>
                        <th class="px-5 py-3 font-medium">Placed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-ink-50">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ order.order_number }}</td>
                        <td class="px-5 py-3"><Badge :tone="statusTone[order.status] ?? 'neutral'">{{ order.status }}</Badge></td>
                        <td class="px-5 py-3 text-ink-700">${{ Number(order.total).toFixed(2) }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState
            v-else
            :icon="Package"
            title="No orders yet"
            description="Once you place an order, you'll be able to track it here."
        />
    </div>
</template>
