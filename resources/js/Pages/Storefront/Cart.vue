<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { Trash2, ShoppingBag } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Button from '@/Components/Common/Button.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useCartStore } from '@/Stores/cart';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const cart = useCartStore();
const toast = useToast();

onMounted(() => {
    // Hydrate from server-rendered props immediately, then keep the
    // store as the source of truth for any further mutations.
    cart.setItems(props.items);
});

async function updateQuantity(item, quantity) {
    if (quantity < 1) return;
    try {
        await cart.updateItem(item.id, quantity);
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Could not update quantity.');
    }
}

async function removeItem(item) {
    try {
        await cart.removeItem(item.id);
        toast.success('Item removed.');
    } catch {
        toast.error('Could not remove that item.');
    }
}

function money(value) {
    return `$${Number(value).toFixed(2)}`;
}
</script>

<template>
    <Head title="Your cart" />

    <div class="container-page py-10">
        <h1 class="font-display text-2xl font-bold text-ink-900">Your cart</h1>

        <div v-if="cart.items.length" class="mt-8 grid gap-8 lg:grid-cols-[1fr_320px]">
            <div class="space-y-4">
                <div
                    v-for="item in cart.items"
                    :key="item.id"
                    class="flex gap-4 rounded-xl border border-ink-100 bg-white p-4 shadow-card"
                >
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-ink-50">
                        <img v-if="item.image" :src="item.image" :alt="item.name" loading="lazy" class="h-full w-full object-cover" />
                    </div>

                    <div class="flex flex-1 flex-col">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <Link :href="`/products/${item.slug}`" class="text-sm font-medium text-ink-900 hover:text-accent-600">
                                    {{ item.name }}
                                </Link>
                                <p v-if="item.variant_label" class="text-xs text-ink-400">{{ item.variant_label }}</p>
                            </div>
                            <button type="button" class="text-ink-400 hover:text-red-500" aria-label="Remove item" @click="removeItem(item)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="mt-auto flex items-center justify-between">
                            <div class="flex items-center rounded-lg border border-ink-200">
                                <button type="button" class="px-2.5 py-1 text-ink-500 hover:text-ink-900" @click="updateQuantity(item, item.quantity - 1)">−</button>
                                <span class="w-8 text-center text-sm font-medium">{{ item.quantity }}</span>
                                <button type="button" class="px-2.5 py-1 text-ink-500 hover:text-ink-900" @click="updateQuantity(item, item.quantity + 1)">+</button>
                            </div>
                            <span class="font-medium text-ink-900">{{ money(item.price * item.quantity) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="h-fit rounded-xl border border-ink-100 bg-white p-6 shadow-card">
                <h2 class="font-display text-base font-semibold text-ink-900">Order summary</h2>
                <div class="mt-4 flex justify-between text-sm text-ink-600">
                    <span>Subtotal</span>
                    <span>{{ money(cart.subtotal) }}</span>
                </div>
                <p class="mt-1 text-xs text-ink-400">Shipping and any coupon are calculated at checkout.</p>
                <Link href="/checkout" class="mt-5 block">
                    <Button variant="primary" size="lg" class="w-full">Proceed to checkout</Button>
                </Link>
            </aside>
        </div>

        <EmptyState
            v-else
            :icon="ShoppingBag"
            title="Your cart is empty"
            description="Browse the catalog and add something you like."
        >
            <Link href="/products" class="mt-4">
                <Button variant="primary">Start shopping</Button>
            </Link>
        </EmptyState>
    </div>
</template>
