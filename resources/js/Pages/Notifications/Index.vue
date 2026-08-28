<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck } from 'lucide-vue-next';
import { onMounted } from 'vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import Button from '@/Components/Common/Button.vue';
import { useNotificationsStore } from '@/Stores/notifications';

defineOptions({
    layout: (h, page) => {
        const role = page.props.auth?.user?.role;
        const Layout = role === 'admin' ? AdminLayout : role === 'vendor' ? VendorLayout : CustomerLayout;
        return h(Layout, { title: 'Notifications' }, () => page);
    },
});

const props = defineProps({
    notifications: { type: Object, required: true },
    unreadCount: { type: Number, required: true },
});

const notificationsStore = useNotificationsStore();

onMounted(() => {
    notificationsStore.setUnreadCount(props.unreadCount);
});

function open(notification) {
    if (!notification.read) {
        router.post(`/notifications/${notification.id}/read`, {}, { preserveScroll: true, preserveState: true });
    }
    if (notification.url) {
        router.visit(notification.url);
    }
}

function markAllRead() {
    router.post('/notifications/read-all', {}, {
        preserveScroll: true,
        onSuccess: () => notificationsStore.setUnreadCount(0),
    });
}

function timeAgo(dateStr) {
    return new Date(dateStr).toLocaleString();
}
</script>

<template>
    <Head title="Notifications" />

    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-ink-500">{{ unreadCount }} unread</p>
        <Button v-if="unreadCount > 0" variant="ghost" size="sm" @click="markAllRead">
            <CheckCheck class="h-3.5 w-3.5" /> Mark all read
        </Button>
    </div>

    <div v-if="notifications.data.length" class="space-y-2">
        <button
            v-for="n in notifications.data"
            :key="n.id"
            type="button"
            class="flex w-full items-start gap-3 rounded-xl border p-4 text-left transition-colors"
            :class="n.read ? 'border-ink-100 bg-white' : 'border-accent-200 bg-accent-50'"
            @click="open(n)"
        >
            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full" :class="n.read ? 'bg-transparent' : 'bg-accent-500'" />
            <div class="flex-1">
                <p class="text-sm text-ink-800">{{ n.message }}</p>
                <p class="mt-1 text-xs text-ink-400">{{ timeAgo(n.created_at) }}</p>
            </div>
        </button>
    </div>

    <EmptyState v-else :icon="Bell" title="No notifications yet" description="Order updates and alerts will show up here." />

    <div v-if="notifications.links.length > 3" class="mt-6 flex flex-wrap gap-1">
        <Link
            v-for="link in notifications.links"
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
