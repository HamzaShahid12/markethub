<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { SlidersHorizontal, PackageSearch, X } from 'lucide-vue-next';
import { ref } from 'vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/Product/ProductCard.vue';
import ProductFilters from '@/Components/Product/ProductFilters.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useCartStore } from '@/Stores/cart';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    vendors: { type: Array, default: () => [] },
    lockedCategory: { type: Object, default: null },
});

const cart = useCartStore();
const toast = useToast();
const mobileFiltersOpen = ref(false);

const sortOptions = [
    { value: 'newest', label: 'Newest' },
    { value: 'featured', label: 'Featured' },
    { value: 'price_low', label: 'Price: Low to High' },
    { value: 'price_high', label: 'Price: High to Low' },
    { value: 'rating', label: 'Highest Rated' },
    { value: 'popularity', label: 'Most Popular' },
];

function baseUrl() {
    return props.lockedCategory ? `/categories/${props.lockedCategory.slug}` : '/products';
}

function applyFilters(next) {
    router.get(baseUrl(), { ...props.filters, ...next }, { preserveState: true, preserveScroll: true, replace: true });
}

function updateFilter({ key, value }) {
    applyFilters({ [key]: value });
}

function updateSort(e) {
    applyFilters({ sort: e.target.value });
}

function resetFilters() {
    router.get(baseUrl(), {}, { preserveScroll: true });
}

async function addToCart(product) {
    try {
        await cart.addItem(product.id);
        toast.success(`${product.name} added to your cart`);
    } catch {
        toast.error('Could not add that item — please try again.');
    }
}
</script>

<template>
    <Head :title="lockedCategory ? lockedCategory.name : 'Shop all products'" />

    <div class="container-page py-10">
        <div class="mb-6">
            <p v-if="lockedCategory" class="text-xs text-ink-400">
                <Link href="/categories" class="hover:text-ink-700">Categories</Link> / {{ lockedCategory.name }}
            </p>
            <h1 class="mt-1 font-display text-2xl font-bold text-ink-900">
                {{ lockedCategory ? lockedCategory.name : 'Shop all products' }}
            </h1>
            <p class="mt-1 text-sm text-ink-500">{{ products.total }} products</p>
        </div>

        <div class="grid gap-8 lg:grid-cols-[240px_1fr]">
            <!-- Desktop filters -->
            <div class="hidden lg:block">
                <ProductFilters
                    :filters="filters"
                    :categories="categories"
                    :vendors="vendors"
                    :locked-category="lockedCategory"
                    @update="updateFilter"
                    @reset="resetFilters"
                />
            </div>

            <div>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg border border-ink-200 px-3 py-2 text-sm font-medium text-ink-700 lg:hidden"
                        @click="mobileFiltersOpen = true"
                    >
                        <SlidersHorizontal class="h-4 w-4" /> Filters
                    </button>

                    <select
                        :value="filters.sort ?? 'newest'"
                        class="ml-auto rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                        @change="updateSort"
                    >
                        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>

                <div v-if="products.data.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    <ProductCard v-for="product in products.data" :key="product.id" :product="product" @add-to-cart="addToCart" />
                </div>

                <EmptyState
                    v-else
                    :icon="PackageSearch"
                    title="No products match your filters"
                    description="Try widening your search or clearing a filter."
                />

                <div v-if="products.links.length > 3" class="mt-8 flex flex-wrap gap-1">
                    <Link
                        v-for="link in products.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        preserve-scroll
                        preserve-state
                        class="rounded-lg px-3 py-1.5 text-sm"
                        :class="[
                            link.active ? 'bg-ink-900 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100',
                            !link.url && 'pointer-events-none opacity-40',
                        ]"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile filters drawer -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="mobileFiltersOpen" class="fixed inset-0 z-50 bg-ink-900/40 lg:hidden" @click="mobileFiltersOpen = false" />
    </Transition>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
    >
        <div v-if="mobileFiltersOpen" class="fixed inset-y-0 right-0 z-50 w-80 max-w-full overflow-y-auto bg-white p-6 shadow-elevated lg:hidden">
            <div class="mb-4 flex items-center justify-between">
                <p class="font-display text-lg font-semibold text-ink-900">Filters</p>
                <button type="button" class="rounded-lg p-1.5 hover:bg-ink-100" @click="mobileFiltersOpen = false" aria-label="Close filters">
                    <X class="h-4 w-4" />
                </button>
            </div>
            <ProductFilters
                :filters="filters"
                :categories="categories"
                :vendors="vendors"
                :locked-category="lockedCategory"
                @update="updateFilter"
                @reset="resetFilters"
            />
        </div>
    </Transition>
</template>
