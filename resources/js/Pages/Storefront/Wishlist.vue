<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { Heart, ShoppingBag, X } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Button from '@/Components/Common/Button.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useCartStore } from '@/Stores/cart';
import { useWishlistStore } from '@/Stores/wishlist';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const wishlist = useWishlistStore();
const cart = useCartStore();
const toast = useToast();

onMounted(() => {
    wishlist.setItems(props.items);
});

async function removeItem(item) {
    try {
        await wishlist.remove(item.id);
        toast.success('Removed from wishlist.');
    } catch {
        toast.error('Could not remove that item.');
    }
}

async function addToCart(item) {
    try {
        await cart.addItem(item.product_id);
        toast.success(`${item.name} added to your cart`);
    } catch {
        toast.error('Could not add that item — please try again.');
    }
}

function money(value) {
    return `$${Number(value).toFixed(2)}`;
}
</script>

<template>
    <Head title="Your wishlist" />

    <div class="container-page py-10">
        <h1 class="font-display text-2xl font-bold text-ink-900">Your wishlist</h1>

        <div v-if="wishlist.items.length" class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <div
                v-for="item in wishlist.items"
                :key="item.id"
                class="group relative flex flex-col overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card"
            >
                <button
                    type="button"
                    class="absolute right-2 top-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-ink-500 shadow-card hover:text-red-500"
                    aria-label="Remove from wishlist"
                    @click="removeItem(item)"
                >
                    <X class="h-3.5 w-3.5" />
                </button>

                <Link :href="`/products/${item.slug}`" class="aspect-[4/5] overflow-hidden bg-ink-50">
                    <img v-if="item.image" :src="item.image" :alt="item.name" loading="lazy" class="h-full w-full object-cover" />
                </Link>

                <div class="flex flex-1 flex-col gap-2 p-4">
                    <Link :href="`/products/${item.slug}`" class="line-clamp-2 text-sm font-medium text-ink-900 hover:text-accent-600">
                        {{ item.name }}
                    </Link>
                    <div class="flex items-baseline gap-2">
                        <span class="font-display text-base font-bold text-ink-900">{{ money(item.sale_price ?? item.price) }}</span>
                        <span v-if="item.sale_price" class="text-sm text-ink-400 line-through">{{ money(item.price) }}</span>
                    </div>
                    <Button variant="secondary" size="sm" class="mt-auto" @click="addToCart(item)">
                        <ShoppingBag class="h-3.5 w-3.5" /> Add to cart
                    </Button>
                </div>
            </div>
        </div>

        <EmptyState
            v-else
            :icon="Heart"
            title="Your wishlist is empty"
            description="Tap the heart on any product to save it for later."
        >
            <Link href="/products" class="mt-4">
                <Button variant="primary">Browse products</Button>
            </Link>
        </EmptyState>
    </div>
</template>
