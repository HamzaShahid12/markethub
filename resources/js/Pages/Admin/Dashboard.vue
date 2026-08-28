<script setup>
import { Head } from '@inertiajs/vue3';
import { Users, Store, Clock3, ShoppingBag, DollarSign } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Dashboard' }, () => page) });

defineProps({
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head title="Admin dashboard" />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <StatCard label="Customers" :value="stats.customers_count" :icon="Users" />
        <StatCard label="Approved vendors" :value="stats.vendors_count" :icon="Store" />
        <StatCard label="Pending approvals" :value="stats.pending_vendors_count" :icon="Clock3" tone="accent" />
        <StatCard label="Orders" :value="stats.orders_count" :icon="ShoppingBag" />
        <StatCard label="GMV" :value="`$${Number(stats.gmv).toFixed(2)}`" :icon="DollarSign" />
    </div>

    <p v-if="stats.pending_vendors_count > 0" class="mt-6 text-sm text-ink-500">
        {{ stats.pending_vendors_count }} vendor{{ stats.pending_vendors_count === 1 ? '' : 's' }} waiting on approval —
        <a href="/admin/vendors?status=pending" class="font-medium text-accent-600 hover:text-accent-700">review them</a>.
    </p>
</template>
