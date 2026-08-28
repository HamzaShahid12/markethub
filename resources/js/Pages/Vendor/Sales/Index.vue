<script setup>
import { Head } from '@inertiajs/vue3';
import { DollarSign, Package, TrendingUp } from 'lucide-vue-next';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import RevenueChart from '@/Components/Common/RevenueChart.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Sales' }, () => page) });

defineProps({
    dailyRevenue: { type: Array, default: () => [] },
    topProducts: { type: Array, default: () => [] },
    recentSales: { type: Array, default: () => [] },
    totals: { type: Object, required: true },
});
</script>

<template>
    <Head title="Sales" />

    <div class="grid gap-4 sm:grid-cols-3">
        <StatCard label="Revenue (30 days)" :value="`$${totals.revenue_30d.toFixed(2)}`" :icon="DollarSign" tone="accent" />
        <StatCard label="Units sold (30 days)" :value="totals.units_30d" :icon="Package" />
        <StatCard label="All-time revenue" :value="`$${totals.all_time_revenue.toFixed(2)}`" :icon="TrendingUp" />
    </div>

    <div class="mt-6 rounded-xl border border-ink-100 bg-white p-6 shadow-card">
        <h2 class="font-display text-base font-semibold text-ink-900">Revenue — last 30 days</h2>
        <div class="mt-4">
            <RevenueChart :data="dailyRevenue" />
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Top products</h2>
            <div v-if="topProducts.length" class="mt-4 space-y-3">
                <div v-for="p in topProducts" :key="p.product_id" class="flex items-center justify-between text-sm">
                    <span class="text-ink-700">{{ p.product_name }}</span>
                    <div class="text-right">
                        <p class="font-medium text-ink-900">${{ Number(p.revenue).toFixed(2) }}</p>
                        <p class="text-xs text-ink-400">{{ p.units_sold }} sold</p>
                    </div>
                </div>
            </div>
            <EmptyState v-else :icon="Package" title="No sales yet" />
        </div>

        <div class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Recent sales</h2>
            <div v-if="recentSales.length" class="mt-4 space-y-3">
                <div v-for="sale in recentSales" :key="sale.id" class="flex items-center justify-between text-sm">
                    <div>
                        <p class="text-ink-700">{{ sale.product_name }}</p>
                        <p class="text-xs text-ink-400">{{ sale.order_number }} · {{ sale.date }}</p>
                    </div>
                    <p class="font-medium text-ink-900">${{ Number(sale.subtotal).toFixed(2) }}</p>
                </div>
            </div>
            <EmptyState v-else :icon="Package" title="No sales yet" />
        </div>
    </div>
</template>
