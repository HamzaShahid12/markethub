<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Star, MessageSquare } from 'lucide-vue-next';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';

defineOptions({ layout: (h, page) => h(CustomerLayout, { title: 'My reviews' }, () => page) });

defineProps({
    reviews: { type: Object, required: true },
});

const statusTone = { pending: 'warning', approved: 'success', rejected: 'danger' };
</script>

<template>
    <Head title="My reviews" />

    <div v-if="reviews.data.length" class="space-y-3">
        <div v-for="review in reviews.data" :key="review.id" class="rounded-xl border border-ink-100 bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <Link v-if="review.product_slug" :href="`/products/${review.product_slug}`" class="font-medium text-ink-900 hover:text-accent-600">
                        {{ review.product_name }}
                    </Link>
                    <span v-else class="font-medium text-ink-900">{{ review.product_name }}</span>

                    <div class="mt-1 flex items-center gap-0.5">
                        <Star v-for="n in 5" :key="n" class="h-3.5 w-3.5" :class="n <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-ink-200'" />
                    </div>
                </div>
                <Badge :tone="statusTone[review.status]" class="capitalize">{{ review.status }}</Badge>
            </div>

            <p v-if="review.comment" class="mt-3 text-sm text-ink-600">{{ review.comment }}</p>
            <p class="mt-2 text-xs text-ink-400">{{ review.created_at }}</p>
        </div>
    </div>

    <EmptyState v-else :icon="MessageSquare" title="No reviews yet" description="Reviews you write on delivered orders will show up here." />

    <div v-if="reviews.links.length > 3" class="mt-6 flex flex-wrap gap-1">
        <Link
            v-for="link in reviews.links"
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
</template>