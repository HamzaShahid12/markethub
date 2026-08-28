<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { MessageCircle } from 'lucide-vue-next';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import Badge from '@/Components/Common/Badge.vue';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Messages' }, () => page) });

defineProps({
    conversations: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Messages" />

    <div v-if="conversations.length" class="space-y-2">
        <Link
            v-for="c in conversations"
            :key="c.id"
            :href="`/vendor/messages/${c.id}`"
            class="flex items-center justify-between rounded-xl border border-ink-100 bg-white p-4 shadow-card hover:border-accent-200"
        >
            <div>
                <p class="font-medium text-ink-900">{{ c.customer_name }}</p>
                <p class="text-xs text-ink-400">{{ c.last_message_at ? new Date(c.last_message_at).toLocaleString() : 'No messages yet' }}</p>
            </div>
            <Badge v-if="c.unread_count > 0" tone="accent">{{ c.unread_count }} new</Badge>
        </Link>
    </div>

    <EmptyState v-else :icon="MessageCircle" title="No conversations yet" description="Customer messages about your products will show up here." />
</template>
