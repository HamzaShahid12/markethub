<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Order details' }, () => page) });

const props = defineProps({
    order: { type: Object, required: true },
});

const toast = useToast();

const statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

function updateStatus(status) {
    if (status === props.order.status) return;

    router.put(`/admin/orders/${props.order.id}/status`, { status }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Order status updated.'),
    });
}

function money(value) {
    return `$${Number(value).toFixed(2)}`;
}
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs text-ink-400"><Link href="/admin/orders" class="hover:text-ink-700">Orders</Link> / {{ order.order_number }}</p>
            <h1 class="mt-1 font-display text-xl font-bold text-ink-900">Order {{ order.order_number }}</h1>
            <p class="text-sm text-ink-500">{{ order.customer.name }} ({{ order.customer.email }}) · placed {{ new Date(order.created_at).toLocaleString() }}</p>
        </div>
        <select :value="order.status" class="rounded-lg border-ink-200 text-sm capitalize focus:border-accent-500 focus:ring-accent-500" @change="updateStatus($event.target.value)">
            <option v-for="s in statuses" :key="s" :value="s" class="capitalize">{{ s }}</option>
        </select>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Item</th>
                        <th class="px-5 py-3 font-medium">Vendor</th>
                        <th class="px-5 py-3 font-medium">Qty</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="item in order.items" :key="item.id">
                        <td class="px-5 py-3 font-medium text-ink-900">
                            <Link v-if="item.product_slug" :href="`/products/${item.product_slug}`" class="hover:text-accent-600">{{ item.product_name }}</Link>
                            <span v-else>{{ item.product_name }}</span>
                        </td>
                        <td class="px-5 py-3 text-ink-500">{{ item.vendor_name }}</td>
                        <td class="px-5 py-3 text-ink-600">{{ item.quantity }}</td>
                        <td class="px-5 py-3"><Badge class="capitalize">{{ item.status }}</Badge></td>
                        <td class="px-5 py-3 text-ink-700">{{ money(item.subtotal) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <aside class="h-fit space-y-4 rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <div>
                <h2 class="font-display text-base font-semibold text-ink-900">Summary</h2>
                <dl class="mt-2 space-y-1.5 text-sm">
                    <div class="flex justify-between text-ink-600"><dt>Subtotal</dt><dd>{{ money(order.subtotal) }}</dd></div>
                    <div v-if="order.discount > 0" class="flex justify-between text-accent-700">
                        <dt>Discount{{ order.coupon_code ? ` (${order.coupon_code})` : '' }}</dt><dd>−{{ money(order.discount) }}</dd>
                    </div>
                    <div class="flex justify-between text-ink-600"><dt>Shipping</dt><dd>{{ order.shipping_fee > 0 ? money(order.shipping_fee) : 'Free' }}</dd></div>
                    <div class="flex justify-between border-t border-ink-100 pt-1.5 font-semibold text-ink-900"><dt>Total</dt><dd>{{ money(order.total) }}</dd></div>
                </dl>
                <p class="mt-2 text-xs text-ink-400 capitalize">{{ order.payment_method }} · {{ order.payment_status }}</p>
            </div>

            <div class="border-t border-ink-100 pt-4 text-sm text-ink-500">
                <p class="font-medium text-ink-700">Shipping address</p>
                <p>{{ order.shipping_address.name }}</p>
                <p>{{ order.shipping_address.line1 }}</p>
                <p>{{ order.shipping_address.city }}, {{ order.shipping_address.state }} {{ order.shipping_address.postal_code }}</p>
                <p>{{ order.shipping_address.country }}</p>
            </div>
        </aside>
    </div>
</template>
