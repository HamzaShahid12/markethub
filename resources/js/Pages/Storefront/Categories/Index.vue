<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

defineOptions({ layout: StorefrontLayout });

defineProps({
    categories: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Categories" />

    <div class="container-page py-10">
        <h1 class="font-display text-2xl font-bold text-ink-900">Shop by category</h1>
        <p class="mt-1 text-sm text-ink-500">Browse everything on MarketHub, organized by department.</p>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="category in categories" :key="category.id" class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
                <Link :href="`/categories/${category.slug}`" class="font-display text-lg font-semibold text-ink-900 hover:text-accent-600">
                    {{ category.name }}
                </Link>
                <p class="mt-1 text-xs text-ink-400">{{ category.products_count }} products</p>
                <p v-if="category.description" class="mt-2 text-sm text-ink-500">{{ category.description }}</p>

                <ul v-if="category.children?.length" class="mt-4 space-y-1.5 border-t border-ink-100 pt-3">
                    <li v-for="child in category.children" :key="child.id">
                        <Link :href="`/categories/${child.slug}`" class="text-sm text-ink-600 hover:text-accent-600">
                            {{ child.name }}
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
