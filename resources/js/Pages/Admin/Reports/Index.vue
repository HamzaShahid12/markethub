<script setup>
import { Head } from '@inertiajs/vue3';
import { DollarSign, Store, ShoppingBag } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import RevenueChart from '@/Components/Common/RevenueChart.vue';
import Badge from '@/Components/Common/Badge.vue';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Reports' }, () => page) });

defineProps({
    dailyGmv: { type: Array, default: () => [] },
    ordersByStatus: { type: Object, default: () => ({}) },
    topVendors: { type: Array, default: () => [] },
    topCategories: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
});

const statusTone = {
    pending: 'warning', processing: 'accent', shipped: 'accent',
    delivered: 'success', cancelled: 'danger', refunded: 'neutral',
};
</script>

<template>
    <Head title="Reports" />

    <div class="grid gap-4 sm:grid-cols-3">
        <StatCard label="Approved vendors" :value="summary.total_vendors" :icon="Store" />
        <StatCard label="Total GMV" :value="`$${summary.total_gmv.toFixed(2)}`" :icon="DollarSign" tone="accent" />
        <StatCard label="Average order value" :value="`$${summary.avg_order_value.toFixed(2)}`" :icon="ShoppingBag" />
    </div>

    <div class="mt-6 rounded-xl border border-ink-100 bg-white p-6 shadow-card">
        <h2 class="font-display text-base font-semibold text-ink-900">GMV — last 30 days</h2>
        <div class="mt-4">
            <RevenueChart :data="dailyGmv" />
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Orders by status</h2>
            <div class="mt-4 space-y-2">
                <div v-for="(count, status) in ordersByStatus" :key="status" class="flex items-center justify-between text-sm">
                    <Badge :tone="statusTone[status] ?? 'neutral'" class="capitalize">{{ status }}</Badge>
                    <span class="font-medium text-ink-900">{{ count }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Top vendors</h2>
            <div class="mt-4 space-y-2">
                <div v-for="v in topVendors" :key="v.name" class="flex items-center justify-between text-sm">
                    <span class="text-ink-700">{{ v.name }}</span>
                    <span class="font-medium text-ink-900">${{ v.revenue.toFixed(2) }}</span>
                </div>
                <p v-if="!topVendors.length" class="text-sm text-ink-400">No sales yet.</p>
            </div>
        </div>

        <div class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Top categories</h2>
            <div class="mt-4 space-y-2">
                <div v-for="c in topCategories" :key="c.category_name" class="flex items-center justify-between text-sm">
                    <span class="text-ink-700">{{ c.category_name }}</span>
                    <span class="font-medium text-ink-900">${{ Number(c.revenue).toFixed(2) }}</span>
                </div>
                <p v-if="!topCategories.length" class="text-sm text-ink-400">No sales yet.</p>
            </div>
        </div>
    </div>
</template>
