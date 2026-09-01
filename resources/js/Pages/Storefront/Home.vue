<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Truck, ShieldCheck, RotateCcw, Clock } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/Product/ProductCard.vue';
import { useCartStore } from '@/Stores/cart';
import { useToast } from '@/Composables/useToast';
import HeroSlider from '@/Components/Storefront/HeroSlider.vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
    categories: { type: Array, default: () => [] },
    trending: { type: Array, default: () => [] },
    flashDeals: { type: Array, default: () => [] },
    flashDealsEndAt: { type: String, default: null },
    vendors: { type: Array, default: () => [] },
    banners: { type: Array, default: () => [] },
    categoryDisplayStyle: { type: String, default: 'circle' },
});

const page = usePage();
const cart = useCartStore();
const toast = useToast();

async function addToCart(product) {
    try {
        await cart.addItem(product.id);
        toast.success(`${product.name} added to your cart`);
    } catch {
        toast.error('Could not add that item — please try again.');
    }


    try {
        await cart.addItem(product.id);
        toast.success(`${product.name} added to your cart`);
    } catch {
        toast.error('Could not add that item — please try again.');
    }
}

// Flash-deal countdown (section 3.1 "flash deals").
const timeLeft = ref({ h: '00', m: '00', s: '00' });
let intervalId = null;

function tick() {
    if (!props.flashDealsEndAt) return;
    const diff = Math.max(0, new Date(props.flashDealsEndAt).getTime() - Date.now());
    const totalSeconds = Math.floor(diff / 1000);
    timeLeft.value = {
        h: String(Math.floor(totalSeconds / 3600)).padStart(2, '0'),
        m: String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0'),
        s: String(totalSeconds % 60).padStart(2, '0'),
    };
}

onMounted(() => {
    tick();
    intervalId = setInterval(tick, 1000);
});
onBeforeUnmount(() => clearInterval(intervalId));

const trustPoints = [
    { icon: Truck, label: 'Fast, tracked shipping' },
    { icon: ShieldCheck, label: 'Buyer protection on every order' },
    { icon: RotateCcw, label: 'Easy 30-day returns' },
];
</script>

<template>
    <Head title="Home" />

    <!-- Hero -->
   <HeroSlider v-if="banners.length" :slides="banners" />

    <!-- Categories -->
<section class="container-page py-14">
    <div class="mb-8 flex items-end justify-between">
        <h2 class="font-display text-2xl font-bold text-ink-900">Shop by category</h2>
        <Link href="/categories" class="text-sm font-medium text-accent-600 hover:text-accent-700">View all</Link>
    </div>

    <!-- Circle style -->
    <div v-if="categoryDisplayStyle === 'circle'" class="grid grid-cols-2 gap-6 sm:grid-cols-4 lg:grid-cols-8">
        <Link
            v-for="category in categories"
            :key="category.id"
            :href="`/categories/${category.slug}`"
            class="group flex flex-col items-center gap-3 text-center"
        >
            <span class="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-transparent bg-ink-50 shadow-card transition-all duration-300 group-hover:border-accent-400 group-hover:shadow-elevated sm:h-24 sm:w-24">
                <img v-if="category.image" :src="category.image" :alt="category.name" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110" />
                <span v-else class="text-2xl font-bold text-ink-400">{{ category.name.charAt(0) }}</span>
            </span>
            <span class="text-xs font-medium text-ink-700 transition-colors group-hover:text-accent-700 sm:text-sm">{{ category.name }}</span>
        </Link>
    </div>

    <!-- Card style (horizontal, image as background) -->
    <div v-else-if="categoryDisplayStyle === 'card'" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Link
            v-for="category in categories"
            :key="category.id"
            :href="`/categories/${category.slug}`"
            class="group relative h-32 overflow-hidden rounded-xl bg-ink-900 shadow-card transition-shadow duration-300 hover:shadow-elevated"
        >
            <img
                v-if="category.image"
                :src="category.image"
                :alt="category.name"
                class="absolute inset-0 h-full w-full object-cover opacity-70 transition-transform duration-500 group-hover:scale-110"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-ink-900/80 via-ink-900/20 to-transparent" />
            <span class="absolute bottom-4 left-4 font-display text-lg font-bold text-white">{{ category.name }}</span>
        </Link>
    </div>

    <!-- Square tile style -->
    <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
        <Link
            v-for="category in categories"
            :key="category.id"
            :href="`/categories/${category.slug}`"
            class="group flex flex-col gap-2"
        >
            <span class="relative block aspect-square overflow-hidden rounded-xl bg-ink-50 shadow-card transition-shadow duration-300 group-hover:shadow-elevated">
                <img v-if="category.image" :src="category.image" :alt="category.name" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110" />
                <span v-else class="flex h-full w-full items-center justify-center text-2xl font-bold text-ink-400">{{ category.name.charAt(0) }}</span>
            </span>
            <span class="text-center text-xs font-medium text-ink-700 transition-colors group-hover:text-accent-700 sm:text-sm">{{ category.name }}</span>
        </Link>
    </div>
</section>

    <!-- Flash deals -->
    <section v-if="flashDeals.length" class="bg-ink-900/[0.02] py-14">
        <div class="container-page">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="font-display text-2xl font-bold text-ink-900">Flash deals</h2>
                    <p class="mt-1 text-sm text-ink-500">Limited-time prices from our vendors. Once it's gone, it's gone.</p>
                </div>
                <div v-if="flashDealsEndAt" class="flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-white">
                    <Clock class="h-4 w-4 text-accent-400" />
                    <span class="font-mono text-sm tabular-nums">{{ timeLeft.h }}:{{ timeLeft.m }}:{{ timeLeft.s }}</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <ProductCard
                    v-for="product in flashDeals"
                    :key="product.id"
                    :product="product"
                    @add-to-cart="addToCart"
                />
            </div>
        </div>
    </section>

    <!-- Trending products -->
    <section class="container-page py-14">
        <div class="mb-6 flex items-end justify-between">
            <h2 class="font-display text-2xl font-bold text-ink-900">Trending now</h2>
            <Link href="/products?sort=popularity" class="text-sm font-medium text-accent-600 hover:text-accent-700">View all</Link>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <ProductCard
                v-for="product in trending"
                :key="product.id"
                :product="product"
                @add-to-cart="addToCart"
            />
        </div>
    </section>

    <!-- Vendors -->
    <section class="bg-ink-900/[0.02] py-14">
        <div class="container-page">
            <div class="mb-6 flex items-end justify-between">
                <h2 class="font-display text-2xl font-bold text-ink-900">Featured vendors</h2>
                <Link href="/vendors" class="text-sm font-medium text-accent-600 hover:text-accent-700">View all</Link>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <Link
                    v-for="vendor in vendors"
                    :key="vendor.id"
                    :href="`/vendors/${vendor.slug}`"
                    class="flex flex-col items-center gap-3 rounded-xl border border-ink-100 bg-white p-6 text-center transition-shadow hover:shadow-elevated"
                >
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-ink-900 text-lg font-bold text-white">
                        {{ vendor.shop_name.charAt(0) }}
                    </span>
                    <span class="text-sm font-semibold text-ink-900">{{ vendor.shop_name }}</span>
                    <span class="text-xs text-ink-500">{{ vendor.product_count ?? 0 }} products</span>
                </Link>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="container-page py-16">
        <div class="rounded-2xl bg-ink-900 px-8 py-12 text-center">
            <h2 class="font-display text-2xl font-bold text-white">Get the best deals first</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-ink-300">
                New arrivals, flash deals and vendor spotlights — straight to your inbox, once a week.
            </p>
            <form class="mx-auto mt-6 flex max-w-md gap-2">
                <input
                    type="email"
                    required
                    placeholder="you@example.com"
                    class="w-full rounded-lg border-0 text-sm placeholder:text-ink-400 focus:ring-accent-500"
                />
                <button type="submit" class="shrink-0 rounded-lg bg-accent-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-accent-600">
                    Subscribe
                </button>
            </form>
        </div>
    </section>
</template>