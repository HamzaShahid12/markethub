<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Send } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { bootEcho } from '@/echo';
import Button from '@/Components/Common/Button.vue';

const props = defineProps({
    conversationId: { type: Number, required: true },
    initialMessages: { type: Array, required: true },
    currentUserId: { type: Number, required: true },
    sendUrl: { type: String, required: true },
});

const messages = ref([...props.initialMessages]);
const body = ref('');
const sending = ref(false);
const scrollRef = ref(null);

function scrollToBottom() {
    nextTick(() => {
        if (scrollRef.value) {
            scrollRef.value.scrollTop = scrollRef.value.scrollHeight;
        }
    });
}

function send() {
    if (!body.value.trim() || sending.value) return;

    const text = body.value;
    sending.value = true;

    // Optimistic append — the real row (with its DB id) replaces this
    // once the request resolves; the live broadcast is `.toOthers()`
    // only, so this tab never receives its own message back over the
    // socket and would otherwise show nothing until a reload.
    const optimisticId = `optimistic-${Date.now()}`;
    messages.value.push({
        id: optimisticId,
        sender_id: props.currentUserId,
        sender_name: 'You',
        body: text,
        created_at: new Date().toISOString(),
    });
    body.value = '';
    scrollToBottom();

    router.post(props.sendUrl, { body: text }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => { sending.value = false; },
    });
}

let channel = null;

onMounted(() => {
    scrollToBottom();

    const echo = bootEcho();
    channel = echo.private(`conversations.${props.conversationId}`);
    channel.listen('.message.sent', (event) => {
        if (event.sender_id === props.currentUserId) return; // avoid dupes with the optimistic append
        messages.value.push(event);
        scrollToBottom();
    });
});

onBeforeUnmount(() => {
    if (channel) {
        bootEcho().leave(`conversations.${props.conversationId}`);
    }
});
</script>

<template>
    <div class="flex h-[28rem] flex-col rounded-xl border border-ink-100 bg-white shadow-card">
        <div ref="scrollRef" class="flex-1 space-y-3 overflow-y-auto p-4">
            <div
                v-for="message in messages"
                :key="message.id"
                class="flex"
                :class="message.sender_id === currentUserId ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="max-w-[75%] rounded-2xl px-4 py-2 text-sm"
                    :class="message.sender_id === currentUserId
                        ? 'bg-ink-900 text-white'
                        : 'bg-ink-100 text-ink-800'"
                >
                    <p>{{ message.body }}</p>
                    <p class="mt-1 text-[10px] opacity-60">{{ new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</p>
                </div>
            </div>
        </div>

        <form class="flex items-center gap-2 border-t border-ink-100 p-3" @submit.prevent="send">
            <input
                v-model="body"
                type="text"
                placeholder="Type a message..."
                class="w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
            />
            <Button type="submit" variant="primary" size="sm" :disabled="!body.trim()">
                <Send class="h-4 w-4" />
            </Button>
        </form>
    </div>
</template>
