<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Truck, ShieldCheck, RotateCcw, Clock } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/Product/ProductCard.vue';
import { useCartStore } from '@/Stores/cart';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
    categories: { type: Array, default: () => [] },
    trending: { type: Array, default: () => [] },
    flashDeals: { type: Array, default: () => [] },
    flashDealsEndAt: { type: String, default: null },
    vendors: { type: Array, default: () => [] },
});

const page = usePage();
const cart = useCartStore();
const toast = useToast();

async function addToCart(product) {
    if (!page.props.auth?.user) {
        window.location.href = '/login';
        return;
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
    <section class="border-b border-ink-100 bg-ink-900">
        <div class="container-page grid items-center gap-10 py-16 lg:grid-cols-2 lg:py-24">
            <div>
                <p class="text-sm font-medium uppercase tracking-widest text-accent-400">The marketplace, done right</p>
                <h1 class="mt-4 font-display text-4xl font-extrabold leading-tight text-white sm:text-5xl">
                    Independent vendors. <br class="hidden sm:block" />One trusted checkout.
                </h1>
                <p class="mt-5 max-w-lg text-ink-300">
                    Browse thousands of products from vetted vendors, track every order in real time, and get
                    help straight from the people who make what you buy.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        href="/products"
                        class="inline-flex items-center gap-2 rounded-lg bg-accent-500 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-600"
                    >
                        Start shopping <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        href="/vendor/register"
                        class="inline-flex items-center gap-2 rounded-lg border border-ink-700 px-6 py-3 text-sm font-semibold text-white hover:bg-ink-800"
                    >
                        Sell on MarketHub
                    </Link>
                </div>
                <dl class="mt-10 flex flex-wrap gap-x-8 gap-y-4">
                    <div v-for="point in trustPoints" :key="point.label" class="flex items-center gap-2 text-sm text-ink-300">
                        <component :is="point.icon" class="h-4 w-4 text-accent-400" />
                        {{ point.label }}
                    </div>
                </dl>
            </div>

            <div class="relative hidden lg:block">
                <div class="grid grid-cols-2 gap-4">
                    <div class="aspect-[3/4] translate-y-6 rounded-xl bg-ink-800" />
                    <div class="aspect-[3/4] rounded-xl bg-ink-700" />
                </div>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="container-page py-14">
        <div class="mb-6 flex items-end justify-between">
            <h2 class="font-display text-2xl font-bold text-ink-900">Shop by category</h2>
            <Link href="/categories" class="text-sm font-medium text-accent-600 hover:text-accent-700">View all</Link>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="`/categories/${category.slug}`"
                class="group flex flex-col items-center gap-2 rounded-xl border border-ink-100 bg-white p-4 text-center transition-colors hover:border-accent-300 hover:bg-accent-50"
            >
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-ink-50 text-lg font-semibold text-ink-500 group-hover:bg-accent-100 group-hover:text-accent-700">
                    {{ category.name.charAt(0) }}
                </span>
                <span class="text-xs font-medium text-ink-700">{{ category.name }}</span>
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