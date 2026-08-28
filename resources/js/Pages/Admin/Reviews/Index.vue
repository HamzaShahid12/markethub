<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Star, MessageSquare } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import Button from '@/Components/Common/Button.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Reviews' }, () => page) });

const props = defineProps({
    reviews: { type: Object, required: true },
    filters: { type: Object, required: true },
    counts: { type: Object, required: true },
});

const toast = useToast();
const tabs = [
    { key: 'pending', label: 'Pending' },
    { key: 'approved', label: 'Approved' },
    { key: 'rejected', label: 'Rejected' },
    { key: 'all', label: 'All' },
];

function filterByStatus(status) {
    router.get('/admin/reviews', { status }, { preserveState: true, preserveScroll: true });
}

function moderate(review, status) {
    router.put(`/admin/reviews/${review.id}/status`, { status }, {
        preserveScroll: true,
        onSuccess: () => toast.success(`Review ${status}.`),
    });
}
</script>

<template>
    <Head title="Reviews" />

    <div class="mb-6 flex flex-wrap gap-2">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
            :class="filters.status === tab.key ? 'bg-ink-900 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100'"
            @click="filterByStatus(tab.key)"
        >
            {{ tab.label }} <span class="ml-1 opacity-70">{{ counts[tab.key] }}</span>
        </button>
    </div>

    <div v-if="reviews.data.length" class="space-y-3">
        <div v-for="review in reviews.data" :key="review.id" class="rounded-xl border border-ink-100 bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-0.5">
                            <Star v-for="n in 5" :key="n" class="h-3.5 w-3.5" :class="n <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-ink-200'" />
                        </div>
                        <span class="text-sm font-medium text-ink-900">{{ review.user.name }}</span>
                        <span class="text-xs text-ink-400">on</span>
                        <Link :href="`/products/${review.product.slug}`" class="text-sm text-accent-600 hover:text-accent-700">{{ review.product.name }}</Link>
                    </div>
                    <p class="mt-2 text-sm text-ink-600">{{ review.comment }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Badge :tone="review.status === 'approved' ? 'success' : review.status === 'rejected' ? 'danger' : 'warning'" class="capitalize">{{ review.status }}</Badge>
                </div>
            </div>
            <div v-if="review.status === 'pending'" class="mt-3 flex gap-2 border-t border-ink-100 pt-3">
                <Button size="sm" variant="secondary" @click="moderate(review, 'approved')">Approve</Button>
                <Button size="sm" variant="ghost" @click="moderate(review, 'rejected')">Reject</Button>
            </div>
        </div>
    </div>

    <EmptyState v-else :icon="MessageSquare" title="Nothing here" description="No reviews match this filter." />

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
