<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import MessageThread from '@/Components/Chat/MessageThread.vue';

defineOptions({ layout: (h, page) => h(CustomerLayout, { title: 'Messages' }, () => page) });

const props = defineProps({
    conversation: { type: Object, required: true },
    messages: { type: Array, default: () => [] },
    currentUserId: { type: Number, required: true },
});
</script>

<template>
    <Head :title="conversation.vendor_name" />

    <p class="mb-4 text-xs text-ink-400">
        <Link href="/customer/messages" class="hover:text-ink-700">Messages</Link> / {{ conversation.vendor_name }}
    </p>

    <MessageThread
        :conversation-id="conversation.id"
        :initial-messages="messages"
        :current-user-id="currentUserId"
        :send-url="`/customer/messages/${conversation.id}`"
    />
</template>
