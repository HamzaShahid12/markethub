<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Star } from 'lucide-vue-next';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import StatusTimeline from '@/Components/Common/StatusTimeline.vue';
import Badge from '@/Components/Common/Badge.vue';
import Button from '@/Components/Common/Button.vue';
import Modal from '@/Components/Common/Modal.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(CustomerLayout, { title: 'Order details' }, () => page) });

const props = defineProps({
    order: { type: Object, required: true },
});

const toast = useToast();
const cancellable = ['pending', 'processing'].includes(props.order.status);

function cancelOrder() {
    if (!confirm('Cancel this order? This cannot be undone.')) return;

    router.post(`/customer/orders/${props.order.id}/cancel`, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Order cancelled.'),
        onError: () => toast.error('Could not cancel this order.'),
    });
}

const reviewModalOpen = ref(false);
const reviewingItem = ref(null);

const reviewForm = useForm({
    product_id: null,
    order_id: props.order.id,
    rating: 5,
    comment: '',
});

function openReview(item) {
    reviewingItem.value = item;
    reviewForm.reset();
    reviewForm.product_id = item.product_id;
    reviewForm.order_id = props.order.id;
    reviewForm.rating = 5;
    reviewModalOpen.value = true;
}

function submitReview() {
    reviewForm.post('/customer/reviews', {
        preserveScroll: true,
        onSuccess: () => {
            reviewModalOpen.value = false;
            toast.success('Thanks — your review is awaiting moderation.');
        },
    });
}

function money(value) {
    return `$${Number(value).toFixed(2)}`;
}
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-xs text-ink-400"><Link href="/customer/orders" class="hover:text-ink-700">My orders</Link> / {{ order.order_number }}</p>
            <h1 class="mt-1 font-display text-xl font-bold text-ink-900">Order {{ order.order_number }}</h1>
            <p class="text-sm text-ink-500">Placed {{ new Date(order.created_at).toLocaleString() }}</p>
        </div>
        <Button v-if="cancellable" variant="danger" size="sm" @click="cancelOrder">Cancel order</Button>
    </div>

    <div class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
        <StatusTimeline :status="order.status" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Item</th>
                        <th class="px-5 py-3 font-medium">Vendor</th>
                        <th class="px-5 py-3 font-medium">Qty</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Subtotal</th>
                        <th></th>
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
                        <td class="px-5 py-3 text-right">
                            <button v-if="item.can_review" type="button" class="text-sm font-medium text-accent-600 hover:text-accent-700" @click="openReview(item)">
                                Write a review
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <aside class="h-fit space-y-4 rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Summary</h2>
            <dl class="space-y-1.5 text-sm">
                <div class="flex justify-between text-ink-600"><dt>Subtotal</dt><dd>{{ money(order.subtotal) }}</dd></div>
                <div v-if="order.discount > 0" class="flex justify-between text-accent-700"><dt>Discount</dt><dd>−{{ money(order.discount) }}</dd></div>
                <div class="flex justify-between text-ink-600"><dt>Shipping</dt><dd>{{ order.shipping_fee > 0 ? money(order.shipping_fee) : 'Free' }}</dd></div>
                <div class="flex justify-between border-t border-ink-100 pt-1.5 font-semibold text-ink-900"><dt>Total</dt><dd>{{ money(order.total) }}</dd></div>
            </dl>

            <div class="border-t border-ink-100 pt-4 text-sm text-ink-500">
                <p class="font-medium text-ink-700">Shipping address</p>
                <p>{{ order.shipping_address.name }}</p>
                <p>{{ order.shipping_address.line1 }}</p>
                <p>{{ order.shipping_address.city }}, {{ order.shipping_address.state }} {{ order.shipping_address.postal_code }}</p>
                <p>{{ order.shipping_address.country }}</p>
            </div>
        </aside>
    </div>

    <Modal :open="reviewModalOpen" :title="`Review ${reviewingItem?.product_name ?? ''}`" @close="reviewModalOpen = false">
        <form class="space-y-4" @submit.prevent="submitReview">
            <div>
                <label class="block text-sm font-medium text-ink-700">Rating</label>
                <div class="mt-1 flex gap-1">
                    <button
                        v-for="n in 5"
                        :key="n"
                        type="button"
                        aria-label="Set rating"
                        @click="reviewForm.rating = n"
                    >
                        <Star class="h-6 w-6" :class="n <= reviewForm.rating ? 'fill-amber-400 text-amber-400' : 'text-ink-200'" />
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700">Comment <span class="text-ink-400">(optional)</span></label>
                <textarea v-model="reviewForm.comment" rows="4" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                <p v-if="reviewForm.errors.comment" class="mt-1 text-xs text-red-600">{{ reviewForm.errors.comment }}</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button type="button" variant="ghost" @click="reviewModalOpen = false">Cancel</Button>
                <Button type="submit" variant="primary" :loading="reviewForm.processing">Submit review</Button>
            </div>
        </form>
    </Modal>
</template>
