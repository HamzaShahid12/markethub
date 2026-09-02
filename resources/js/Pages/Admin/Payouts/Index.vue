<script setup>
import { Head, router } from '@inertiajs/vue3';
import { Check, X, DollarSign } from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Common/Modal.vue';
import Badge from '@/Components/Common/Badge.vue';
import Button from '@/Components/Common/Button.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { Wallet } from 'lucide-vue-next';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Payouts' }, () => page) });

const props = defineProps({
    payouts: { type: Object, required: true },
    filters: { type: Object, required: true },
    counts: { type: Object, required: true },
});

const toast = useToast();
const statusTone = { requested: 'warning', approved: 'accent', paid: 'success', rejected: 'danger' };
const tabs = ['requested', 'approved', 'paid', 'rejected', 'all'];

const markPaidModal = ref(false);
const activePayout = ref(null);
const referenceNumber = ref('');

function filterByStatus(status) {
    router.get('/admin/payouts', { status }, { preserveState: true, preserveScroll: true });
}

function approve(payout) {
    router.post(`/admin/payouts/${payout.id}/approve`, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Payout approved.'),
    });
}

function reject(payout) {
    if (!confirm('Reject this payout? Commissions will return to the vendor\'s balance.')) return;

    router.post(`/admin/payouts/${payout.id}/reject`, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Payout rejected.'),
    });
}

function openMarkPaid(payout) {
    activePayout.value = payout;
    referenceNumber.value = '';
    markPaidModal.value = true;
}

function submitMarkPaid() {
    router.post(`/admin/payouts/${activePayout.value.id}/mark-paid`, {
        reference_number: referenceNumber.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            markPaidModal.value = false;
            toast.success('Payout marked as paid.');
        },
    });
}

function payoutDetailsFor(vendor) {
    if (vendor.payout_method === 'bank_transfer') {
        return `${vendor.bank_name} — ${vendor.account_title} — ${vendor.account_number}`;
    }
    if (vendor.payout_method === 'jazzcash' || vendor.payout_method === 'easypaisa') {
        return `${vendor.payout_method} — ${vendor.payout_phone}`;
    }
    if (vendor.payout_method === 'paypal') {
        return `PayPal — ${vendor.account_title}`;
    }
    return 'No payout details';
}
</script>

<template>
    <Head title="Payouts" />

    <div class="mb-6 flex flex-wrap gap-2">
        <button
            v-for="tab in tabs"
            :key="tab"
            type="button"
            class="rounded-full px-4 py-1.5 text-sm font-medium capitalize transition-colors"
            :class="filters.status === tab ? 'bg-ink-900 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100'"
            @click="filterByStatus(tab)"
        >
            {{ tab }} <span class="ml-1 opacity-70">{{ counts[tab] }}</span>
        </button>
    </div>

    <div v-if="payouts.data.length" class="space-y-3">
        <div v-for="payout in payouts.data" :key="payout.id" class="rounded-xl border border-ink-100 bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-medium text-ink-900">{{ payout.vendor.shop_name }}</p>
                    <p class="text-xs text-ink-400">{{ payoutDetailsFor(payout.vendor) }}</p>
                    <p class="mt-1 font-display text-lg font-bold text-ink-900">${{ Number(payout.amount).toFixed(2) }}</p>
                </div>
                <Badge :tone="statusTone[payout.status]" class="capitalize">{{ payout.status }}</Badge>
            </div>

            <div v-if="payout.status === 'requested'" class="mt-3 flex gap-2 border-t border-ink-100 pt-3">
                <Button size="sm" variant="secondary" @click="approve(payout)"><Check class="h-3.5 w-3.5" /> Approve</Button>
                <Button size="sm" variant="danger" @click="reject(payout)"><X class="h-3.5 w-3.5" /> Reject</Button>
            </div>
            <div v-else-if="payout.status === 'approved'" class="mt-3 border-t border-ink-100 pt-3">
                <Button size="sm" variant="primary" @click="openMarkPaid(payout)"><DollarSign class="h-3.5 w-3.5" /> Mark as Paid</Button>
            </div>
            <p v-else-if="payout.status === 'paid'" class="mt-3 border-t border-ink-100 pt-3 text-xs text-ink-500">
                Reference: {{ payout.reference_number }} · Paid on {{ new Date(payout.processed_at).toLocaleDateString() }}
            </p>
        </div>
    </div>

    <EmptyState v-else :icon="Wallet" title="No payouts found" description="Nothing matches this filter yet." />

    <Modal :open="markPaidModal" title="Mark payout as paid" @close="markPaidModal = false">
        <form class="space-y-4" @submit.prevent="submitMarkPaid">
            <p class="text-sm text-ink-600">
                Confirm you've sent <strong>${{ Number(activePayout?.amount).toFixed(2) }}</strong> to {{ activePayout?.vendor.shop_name }}, then enter your transfer reference number.
            </p>
            <div>
                <label class="block text-sm font-medium text-ink-700">Reference / transaction number</label>
                <input v-model="referenceNumber" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <Button type="button" variant="ghost" @click="markPaidModal = false">Cancel</Button>
                <Button type="submit" variant="primary">Confirm Paid</Button>
            </div>
        </form>
    </Modal>
</template>