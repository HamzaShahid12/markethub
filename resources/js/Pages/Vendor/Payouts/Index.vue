<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Wallet, AlertCircle } from 'lucide-vue-next';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import Badge from '@/Components/Common/Badge.vue';
import Button from '@/Components/Common/Button.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Payouts' }, () => page) });

const props = defineProps({
    payableBalance: { type: Number, required: true },
    minimumPayout: { type: Number, required: true },
    canRequest: { type: Boolean, required: true },
    payouts: { type: Object, required: true },
    hasPayoutDetails: { type: Boolean, required: true },
});

const toast = useToast();
const statusTone = { requested: 'warning', approved: 'accent', paid: 'success', rejected: 'danger' };

function requestPayout() {
    router.post('/vendor/payouts', {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Payout requested.'),
        onError: (errors) => toast.error(Object.values(errors)[0] ?? 'Could not request payout.'),
    });
}
</script>

<template>
    <Head title="Payouts" />

    <div v-if="!hasPayoutDetails" class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
        <p>
            Add your payout details before requesting a payout.
            <Link href="/vendor/store-profile" class="font-medium underline">Go to Store Profile</Link>
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <StatCard label="Available to withdraw" :value="`$${payableBalance.toFixed(2)}`" :icon="Wallet" tone="accent" />
        <div class="flex items-center justify-center rounded-xl border border-ink-100 bg-white p-5 shadow-card">
            <Button variant="primary" :disabled="!canRequest || !hasPayoutDetails" @click="requestPayout">
                Request Payout
            </Button>
        </div>
    </div>
    <p v-if="!canRequest" class="mt-2 text-xs text-ink-400">
        Minimum payout amount is ${{ minimumPayout.toFixed(2) }}.
    </p>

    <div class="mt-8 overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table v-if="payouts.data.length" class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Reference</th>
                    <th class="px-5 py-3 font-medium">Requested</th>
                    <th class="px-5 py-3 font-medium">Paid</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="p in payouts.data" :key="p.id">
                    <td class="px-5 py-3 font-medium text-ink-900">${{ Number(p.amount).toFixed(2) }}</td>
                    <td class="px-5 py-3"><Badge :tone="statusTone[p.status]" class="capitalize">{{ p.status }}</Badge></td>
                    <td class="px-5 py-3 text-ink-500">{{ p.reference_number ?? '—' }}</td>
                    <td class="px-5 py-3 text-ink-500">{{ p.requested_at }}</td>
                    <td class="px-5 py-3 text-ink-500">{{ p.processed_at ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
        <EmptyState v-else :icon="Wallet" title="No payouts yet" description="Request your first payout once you have enough balance." />
    </div>
</template>