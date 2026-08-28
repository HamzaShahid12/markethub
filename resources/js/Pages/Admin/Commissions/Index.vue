<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Percent } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Commissions' }, () => page) });

const props = defineProps({
    commissions: { type: Object, required: true },
    vendors: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    totals: { type: Object, required: true },
});

const statusTone = { pending: 'warning', payable: 'accent', paid: 'success' };

function filterByVendor(e) {
    router.get('/admin/commissions', { ...props.filters, vendor_id: e.target.value || null }, { preserveState: true, preserveScroll: true });
}

function filterByStatus(e) {
    router.get('/admin/commissions', { ...props.filters, status: e.target.value }, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Commissions" />

    <div class="grid gap-4 sm:grid-cols-3">
        <StatCard label="Platform earned" :value="`$${totals.platform_earned.toFixed(2)}`" :icon="Percent" tone="accent" />
        <StatCard label="Vendors earned" :value="`$${totals.vendors_earned.toFixed(2)}`" :icon="Percent" />
        <StatCard label="Pending commission" :value="`$${totals.pending.toFixed(2)}`" :icon="Percent" />
    </div>

    <div class="my-6 flex flex-wrap gap-3">
        <select :value="filters.vendor_id ?? ''" class="rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" @change="filterByVendor">
            <option value="">All vendors</option>
            <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.shop_name }}</option>
        </select>
        <select :value="filters.status" class="rounded-lg border-ink-200 text-sm capitalize focus:border-accent-500 focus:ring-accent-500" @change="filterByStatus">
            <option value="all">All statuses</option>
            <option value="pending">Pending</option>
            <option value="payable">Payable</option>
            <option value="paid">Paid</option>
        </select>
    </div>

    <div v-if="commissions.data.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Vendor</th>
                    <th class="px-5 py-3 font-medium">Order</th>
                    <th class="px-5 py-3 font-medium">Order amount</th>
                    <th class="px-5 py-3 font-medium">Commission</th>
                    <th class="px-5 py-3 font-medium">Vendor gets</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="c in commissions.data" :key="c.id">
                    <td class="px-5 py-3 font-medium text-ink-900">{{ c.vendor_name }}</td>
                    <td class="px-5 py-3 text-ink-600">{{ c.order_number }}</td>
                    <td class="px-5 py-3 text-ink-600">${{ Number(c.order_amount).toFixed(2) }}</td>
                    <td class="px-5 py-3 text-ink-700">${{ Number(c.commission_amount).toFixed(2) }} ({{ c.commission_rate }}%)</td>
                    <td class="px-5 py-3 text-ink-600">${{ Number(c.vendor_amount).toFixed(2) }}</td>
                    <td class="px-5 py-3"><Badge :tone="statusTone[c.status]" class="capitalize">{{ c.status }}</Badge></td>
                    <td class="px-5 py-3 text-ink-500">{{ c.date }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <EmptyState v-else :icon="Percent" title="No commissions found" description="Nothing matches this filter yet." />

    <div v-if="commissions.links.length > 3" class="mt-6 flex flex-wrap gap-1">
        <Link
            v-for="link in commissions.links"
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
