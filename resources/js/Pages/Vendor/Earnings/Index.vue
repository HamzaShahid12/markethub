<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Clock, CheckCircle2, Wallet } from 'lucide-vue-next';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Earnings' }, () => page) });

defineProps({
    commissions: { type: Object, required: true },
    totals: { type: Object, required: true },
    commissionRate: { type: Number, required: true },
});

const statusTone = { pending: 'warning', payable: 'accent', paid: 'success' };
</script>

<template>
    <Head title="Earnings" />

    <div class="grid gap-4 sm:grid-cols-5">
        <StatCard label="Pending" :value="`$${totals.pending.toFixed(2)}`" :icon="Clock" />
        <StatCard label="In payout" :value="`$${totals.in_payout.toFixed(2)}`" :icon="Clock" />
        <StatCard label="Available" :value="`$${totals.payable.toFixed(2)}`" :icon="Wallet" tone="accent" />
        <StatCard label="Lifetime earnings" :value="`$${totals.lifetime.toFixed(2)}`" :icon="Wallet" />
        <StatCard label="Paid out" :value="`$${totals.paid.toFixed(2)}`" :icon="CheckCircle2" />
    </div>

    <p class="mt-4 text-xs text-ink-500">
        Your current commission rate is <strong>{{ commissionRate }}%</strong> — MarketHub keeps this share of each sale, the rest is yours.
    </p>

    <div class="mt-6 overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table v-if="commissions.data.length" class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Order</th>
                    <th class="px-5 py-3 font-medium">Order amount</th>
                    <th class="px-5 py-3 font-medium">Commission</th>
                    <th class="px-5 py-3 font-medium">You earn</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="c in commissions.data" :key="c.id">
                    <td class="px-5 py-3 font-medium text-ink-900">{{ c.order_number }}</td>
                    <td class="px-5 py-3 text-ink-600">${{ Number(c.order_amount).toFixed(2) }}</td>
                    <td class="px-5 py-3 text-ink-600">${{ Number(c.commission_amount).toFixed(2) }} ({{ c.commission_rate }}%)</td>
                    <td class="px-5 py-3 font-medium text-ink-900">${{ Number(c.vendor_amount).toFixed(2) }}</td>
                    <td class="px-5 py-3"><Badge :tone="statusTone[c.status]" class="capitalize">{{ c.status }}</Badge></td>
                    <td class="px-5 py-3 text-ink-500">{{ c.date }}</td>
                </tr>
            </tbody>
        </table>
        <EmptyState v-else :icon="Wallet" title="No earnings yet" description="Commissions appear here once orders come in." />
    </div>

    <div v-if="commissions.links?.length > 3" class="mt-6 flex flex-wrap gap-1">
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
