<script setup>
import { Head } from '@inertiajs/vue3';
import { Package, ShoppingBag, Wallet, Clock, Clock3 } from 'lucide-vue-next';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import StatCard from '@/Components/Common/StatCard.vue';
import Badge from '@/Components/Common/Badge.vue';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Dashboard' }, () => page) });

defineProps({
    vendor: { type: Object, required: true },
    stats: { type: Object, default: null },
});
</script>

<template>
    <Head title="Vendor dashboard" />

    <!-- Pending / rejected / suspended states -->
    <div
        v-if="vendor.status !== 'approved'"
        class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-ink-200 bg-white px-6 py-16 text-center"
    >
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-500">
            <Clock3 class="h-6 w-6" />
        </span>

        <template v-if="vendor.status === 'pending'">
            <p class="font-display text-lg font-semibold text-ink-900">{{ vendor.shop_name }} is awaiting approval</p>
            <p class="max-w-sm text-sm text-ink-500">
                An admin needs to review your shop before you can add products or receive orders.
                This usually doesn't take long — check back soon.
            </p>
        </template>
        <template v-else-if="vendor.status === 'rejected'">
            <p class="font-display text-lg font-semibold text-ink-900">{{ vendor.shop_name }} was not approved</p>
            <p class="max-w-sm text-sm text-ink-500">Contact support if you'd like to understand why or reapply.</p>
        </template>
        <template v-else-if="vendor.status === 'suspended'">
            <p class="font-display text-lg font-semibold text-ink-900">{{ vendor.shop_name }} is suspended</p>
            <p class="max-w-sm text-sm text-ink-500">Your shop is temporarily unavailable to customers. Contact support for details.</p>
        </template>

        <Badge :tone="vendor.status === 'pending' ? 'warning' : 'danger'" class="mt-1 capitalize">
            {{ vendor.status }}
        </Badge>
    </div>

    <!-- Approved: real stats -->
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="Products" :value="stats.products_count" :icon="Package" />
        <StatCard label="Orders" :value="stats.orders_count" :icon="ShoppingBag" />
        <StatCard label="Total earnings" :value="`$${Number(stats.total_earnings).toFixed(2)}`" :icon="Wallet" tone="accent" />
        <StatCard label="Pending payout" :value="`$${Number(stats.pending_commissions).toFixed(2)}`" :icon="Clock" />
    </div>
</template>
