<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Star, ShoppingBag, Heart, MessageCircle, Truck, RotateCcw, ShieldCheck } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/Product/ProductCard.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import { useCartStore } from '@/Stores/cart';
import { useWishlistStore } from '@/Stores/wishlist';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
    product: { type: Object, required: true },
    related: { type: Array, default: () => [] },
});

const page = usePage();
const cart = useCartStore();
const wishlist = useWishlistStore();
const toast = useToast();

const isWishlisted = computed(() => wishlist.productIds.has(props.product.id));

async function toggleWishlist() {
    if (!page.props.auth?.user) {
        window.location.href = '/login';
        return;
    }

    try {
        await wishlist.toggle(props.product.id);
    } catch {
        toast.error('Could not update your wishlist — please try again.');
    }
}

function messageVendor() {
    if (!page.props.auth?.user) {
        window.location.href = '/login';
        return;
    }

    router.post(`/customer/messages/start/${props.product.vendor.id}`);
}

const activeImage = ref(props.product.images[0] ?? null);
const quantity = ref(1);
const activeTab = ref('description');

// Group attribute values by attribute name, e.g. { Color: ['Black','Navy'], Size: ['M','L'] }
const attributeGroups = computed(() => {
    const groups = {};
    for (const variant of props.product.variants) {
        for (const av of variant.attribute_values) {
            groups[av.attribute] ??= new Set();
            groups[av.attribute].add(av.value);
        }
    }
    return Object.fromEntries(Object.entries(groups).map(([k, v]) => [k, [...v]]));
});

const selectedValues = ref({});

const selectedVariant = computed(() => {
    if (!props.product.variants.length) return null;

    return props.product.variants.find((variant) => {
        return variant.attribute_values.every((av) => selectedValues.value[av.attribute] === av.value)
            && variant.attribute_values.length === Object.keys(selectedValues.value).length;
    }) ?? null;
});

const displayPrice = computed(() => selectedVariant.value?.price ?? props.product.sale_price ?? props.product.price);
const displayStock = computed(() => selectedVariant.value ? selectedVariant.value.stock : props.product.stock);
const inStock = computed(() => displayStock.value > 0);

function selectValue(attribute, value) {
    selectedValues.value = { ...selectedValues.value, [attribute]: value };
}

async function addToCart() {
    if (props.product.variants.length && !selectedVariant.value) {
        toast.error('Please select ' + Object.keys(attributeGroups.value).join(' and ') + ' first.');
        return;
    }

    try {
        await cart.addItem(props.product.id, quantity.value, selectedVariant.value?.id ?? null);
        toast.success(`${props.product.name} added to your cart`);
    } catch {
        toast.error('Could not add that item — please try again.');
    }
}

function money(value) {
    return `$${Number(value).toFixed(2)}`;
}
</script>

<template>
    <Head :title="product.name" />

    <div class="container-page py-10">
        <p class="mb-6 text-xs text-ink-400">
            <Link href="/" class="hover:text-ink-700">Home</Link> /
            <Link :href="`/categories/${product.category?.slug}`" class="hover:text-ink-700">{{ product.category?.name }}</Link> /
            <span class="text-ink-600">{{ product.name }}</span>
        </p>

        <div class="grid gap-10 lg:grid-cols-2">
            <!-- Gallery -->
            <div>
                <div class="aspect-square overflow-hidden rounded-xl bg-ink-50">
                    <img
                        v-if="activeImage"
                        :src="activeImage"
                        :alt="product.name"
                        class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                    />
                </div>
                <div v-if="product.images.length > 1" class="mt-3 flex gap-2">
                    <button
                        v-for="(image, i) in product.images"
                        :key="i"
                        type="button"
                        class="h-16 w-16 overflow-hidden rounded-lg ring-2 transition-colors"
                        :class="activeImage === image ? 'ring-accent-500' : 'ring-transparent hover:ring-ink-200'"
                        @click="activeImage = image"
                    >
                        <img :src="image" :alt="`${product.name} thumbnail ${i + 1}`" loading="lazy" class="h-full w-full object-cover" />
                    </button>
                </div>
            </div>

            <!-- Details -->
            <div>
                <div class="flex items-center justify-between gap-3">
                    <Link :href="`/vendors/${product.vendor?.slug}`" class="text-xs font-medium uppercase tracking-wide text-accent-600 hover:text-accent-700">
                        {{ product.vendor?.shop_name }}
                    </Link>
                    <button
                        v-if="!page.props.auth?.user || page.props.auth.user.role === 'customer'"
                        type="button"
                        class="flex items-center gap-1.5 text-xs font-medium text-ink-500 hover:text-ink-800"
                        @click="messageVendor"
                    >
                        <MessageCircle class="h-3.5 w-3.5" /> Message vendor
                    </button>
                </div>
                <h1 class="mt-1 font-display text-2xl font-bold text-ink-900 sm:text-3xl">{{ product.name }}</h1>

                <div class="mt-2 flex items-center gap-2 text-sm">
                    <div class="flex items-center gap-0.5">
                        <Star
                            v-for="n in 5"
                            :key="n"
                            class="h-4 w-4"
                            :class="n <= Math.round(product.rating_average) ? 'fill-amber-400 text-amber-400' : 'text-ink-200'"
                        />
                    </div>
                    <span class="text-ink-500">{{ product.rating_average }} ({{ product.rating_count }} reviews)</span>
                </div>

                <div class="mt-4 flex items-baseline gap-3">
                    <span class="font-display text-3xl font-bold text-ink-900">{{ money(displayPrice) }}</span>
                    <span v-if="!selectedVariant && product.sale_price" class="text-lg text-ink-400 line-through">
                        {{ money(product.price) }}
                    </span>
                </div>

                <p class="mt-4 text-sm leading-relaxed text-ink-600">{{ product.short_description }}</p>

                <!-- Variant selector -->
                <div v-for="(values, attribute) in attributeGroups" :key="attribute" class="mt-5">
                    <p class="mb-2 text-sm font-medium text-ink-700">{{ attribute }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="value in values"
                            :key="value"
                            type="button"
                            class="rounded-lg border px-3.5 py-1.5 text-sm font-medium transition-colors"
                            :class="selectedValues[attribute] === value
                                ? 'border-ink-900 bg-ink-900 text-white'
                                : 'border-ink-200 text-ink-700 hover:border-ink-400'"
                            @click="selectValue(attribute, value)"
                        >
                            {{ value }}
                        </button>
                    </div>
                </div>

                <p class="mt-5 text-sm" :class="inStock ? 'text-accent-700' : 'text-red-600'">
                    {{ inStock ? `${displayStock} in stock` : 'Out of stock' }}
                </p>

                <div class="mt-5 flex items-center gap-3">
                    <div class="flex items-center rounded-lg border border-ink-200">
                        <button type="button" class="px-3 py-2 text-ink-500 hover:text-ink-900" @click="quantity = Math.max(1, quantity - 1)">−</button>
                        <span class="w-8 text-center text-sm font-medium">{{ quantity }}</span>
                        <button type="button" class="px-3 py-2 text-ink-500 hover:text-ink-900" @click="quantity++">+</button>
                    </div>

                    <Button variant="primary" size="lg" class="flex-1" :disabled="!inStock" @click="addToCart">
                        <ShoppingBag class="h-4 w-4" /> Add to cart
                    </Button>

                    <button type="button" class="flex h-11 w-11 items-center justify-center rounded-lg border transition-colors" :class="isWishlisted ? 'border-red-300 bg-red-50 text-red-500' : 'border-ink-200 text-ink-500 hover:border-red-300 hover:text-red-500'" aria-label="Add to wishlist" @click="toggleWishlist">
                        <Heart class="h-4 w-4" :class="isWishlisted && 'fill-red-500'" />
                    </button>
                </div>

                <dl class="mt-8 space-y-2.5 border-t border-ink-100 pt-6 text-sm text-ink-500">
                    <div class="flex items-center gap-2"><Truck class="h-4 w-4 text-accent-600" /> Fast, tracked shipping</div>
                    <div class="flex items-center gap-2"><RotateCcw class="h-4 w-4 text-accent-600" /> 30-day returns</div>
                    <div class="flex items-center gap-2"><ShieldCheck class="h-4 w-4 text-accent-600" /> Buyer protection included</div>
                </dl>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mt-14 border-b border-ink-100">
            <nav class="flex gap-8">
                <button
                    type="button"
                    class="border-b-2 pb-3 text-sm font-medium transition-colors"
                    :class="activeTab === 'description' ? 'border-ink-900 text-ink-900' : 'border-transparent text-ink-400 hover:text-ink-700'"
                    @click="activeTab = 'description'"
                >
                    Description
                </button>
                <button
                    type="button"
                    class="border-b-2 pb-3 text-sm font-medium transition-colors"
                    :class="activeTab === 'reviews' ? 'border-ink-900 text-ink-900' : 'border-transparent text-ink-400 hover:text-ink-700'"
                    @click="activeTab = 'reviews'"
                >
                    Reviews ({{ product.reviews.length }})
                </button>
            </nav>
        </div>

        <div class="max-w-3xl py-8">
            <div v-if="activeTab === 'description'" class="prose prose-sm max-w-none text-ink-600">
                <p class="whitespace-pre-line">{{ product.description }}</p>
            </div>

            <div v-else class="space-y-6">
                <p v-if="!product.reviews.length" class="text-sm text-ink-500">No reviews yet.</p>
                <div v-for="review in product.reviews" :key="review.id" class="border-b border-ink-100 pb-6">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-0.5">
                            <Star v-for="n in 5" :key="n" class="h-3.5 w-3.5" :class="n <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-ink-200'" />
                        </div>
                        <span class="text-sm font-medium text-ink-900">{{ review.user_name }}</span>
                        <span class="text-xs text-ink-400">{{ review.created_at }}</span>
                    </div>
                    <p class="mt-2 text-sm text-ink-600">{{ review.comment }}</p>
                </div>
            </div>
        </div>

        <!-- Related products -->
        <div v-if="related.length" class="mt-10">
            <h2 class="mb-4 font-display text-xl font-bold text-ink-900">You might also like</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <ProductCard v-for="p in related" :key="p.id" :product="p" />
            </div>
        </div>
    </div>
</template>
