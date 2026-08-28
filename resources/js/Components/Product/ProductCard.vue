<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Heart, ShoppingBag, Star } from 'lucide-vue-next';
import { computed } from 'vue';
import { useWishlistStore } from '@/Stores/wishlist';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    product: { type: Object, required: true },
});

const emit = defineEmits(['add-to-cart']);

const page = usePage();
const wishlist = useWishlistStore();
const toast = useToast();

const wishlisted = computed(() => wishlist.productIds.has(props.product.id));

async function toggleWishlist() {
    if (!page.props.auth?.user) {
        router_visit_login();
        return;
    }

    try {
        await wishlist.toggle(props.product.id);
    } catch {
        toast.error('Could not update your wishlist — please try again.');
    }
}

function router_visit_login() {
    window.location.href = '/login';
}

function money(value) {
    return `$${Number(value).toFixed(2)}`;
}
</script>

<template>
    <article
        class="group relative flex flex-col overflow-hidden rounded-xl bg-white shadow-card ring-1 ring-ink-100 transition-shadow duration-200 hover:shadow-elevated"
    >
        <Link :href="`/products/${product.slug}`" class="relative block aspect-[4/5] overflow-hidden bg-ink-50">
            <img
                :src="product.image ?? '/images/placeholder-product.svg'"
                :alt="product.name"
                loading="lazy"
                class="h-full w-full object-cover transition-opacity duration-300 group-hover:opacity-0"
            />
            <img
                v-if="product.secondary_image"
                :src="product.secondary_image"
                :alt="product.name"
                loading="lazy"
                class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100"
            />

            <span
                v-if="product.sale_price"
                class="absolute left-3 top-3 rounded-full bg-accent-500 px-2.5 py-1 text-xs font-semibold text-white"
            >
                Sale
            </span>

            <!-- Quick-add reveal (section 15) -->
            <button
                type="button"
                class="absolute inset-x-3 bottom-3 translate-y-3 rounded-lg bg-ink-900/90 px-3 py-2 text-xs font-medium text-white opacity-0 backdrop-blur transition-all duration-200 group-hover:translate-y-0 group-hover:opacity-100"
                @click.prevent="emit('add-to-cart', product)"
            >
                <span class="inline-flex items-center justify-center gap-1.5">
                    <ShoppingBag class="h-3.5 w-3.5" /> Quick add
                </span>
            </button>
        </Link>

        <button
            type="button"
            class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-ink-600 shadow-card transition-transform duration-150 hover:scale-110 hover:text-red-500"
            :aria-pressed="wishlisted"
            aria-label="Toggle wishlist"
            @click="toggleWishlist"
        >
            <Heart class="h-4 w-4 transition-colors" :class="wishlisted && 'fill-red-500 text-red-500'" />
        </button>

        <div class="flex flex-1 flex-col gap-1.5 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-400">{{ product.vendor_name }}</p>
            <Link :href="`/products/${product.slug}`" class="line-clamp-2 text-sm font-medium text-ink-900 hover:text-accent-600">
                {{ product.name }}
            </Link>

            <div class="mt-0.5 flex items-center gap-1 text-xs text-ink-500">
                <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                <span>{{ Number(product.rating_average ?? 0).toFixed(1) }}</span>
                <span class="text-ink-300">({{ product.rating_count ?? 0 }})</span>
            </div>

            <div class="mt-1 flex items-baseline gap-2">
                <span class="font-display text-base font-bold text-ink-900">
                    {{ money(product.sale_price ?? product.price) }}
                </span>
                <span v-if="product.sale_price" class="text-sm text-ink-400 line-through">
                    {{ money(product.price) }}
                </span>
            </div>
        </div>
    </article>
</template>
