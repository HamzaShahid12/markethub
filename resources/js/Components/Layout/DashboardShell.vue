<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { Bell, LogOut, Menu, Store, X } from 'lucide-vue-next';
import Toast from '@/Components/Common/Toast.vue';
import { useNotificationsStore } from '@/Stores/notifications';

const props = defineProps({
    navItems: { type: Array, required: true }, // [{ label, href, icon, active }]
    roleLabel: { type: String, required: true },
    title: { type: String, default: '' },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const mobileOpen = ref(false);
const notifications = useNotificationsStore();

onMounted(() => {
    notifications.fetchUnreadCount();
    if (user.value) {
        notifications.listen(user.value.id);
    }
});
</script>

<template>
    <div class="flex min-h-screen bg-ink-50">
        <!-- Mobile overlay -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-ink-900/40 lg:hidden" @click="mobileOpen = false" />
        </Transition>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-ink-100 bg-white transition-transform duration-200 lg:static lg:translate-x-0"
            :class="mobileOpen && 'translate-x-0'"
        >
            <div class="flex h-16 items-center justify-between border-b border-ink-100 px-5">
                <Link href="/" class="flex items-center gap-2 font-display text-lg font-bold text-ink-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink-900 text-white">
                        <Store class="h-4 w-4" />
                    </span>
                    MarketHub
                </Link>
                <button type="button" class="rounded-lg p-1.5 hover:bg-ink-100 lg:hidden" @click="mobileOpen = false" aria-label="Close menu">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <Link href="/notifications" class="mx-3 mt-3 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink-600 hover:bg-ink-100 hover:text-ink-900">
                <span class="relative">
                    <Bell class="h-4 w-4" />
                    <span v-if="notifications.unreadCount > 0" class="absolute -right-1 -top-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-accent-500 text-[9px] font-bold text-white">
                        {{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}
                    </span>
                </span>
                Notifications
            </Link>

            <p class="px-5 pt-4 text-xs font-semibold uppercase tracking-wide text-ink-400">{{ roleLabel }}</p>

            <nav class="flex-1 space-y-1 px-3 py-3">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="item.active
                        ? 'bg-ink-900 text-white'
                        : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900'"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="border-t border-ink-100 p-4">
                <div class="mb-3 flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ink-100 text-sm font-semibold text-ink-700">
                        {{ user?.name?.charAt(0) ?? '?' }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-ink-900">{{ user?.name }}</p>
                        <p class="truncate text-xs text-ink-400">{{ user?.email }}</p>
                    </div>
                </div>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-ink-500 hover:bg-ink-100 hover:text-ink-900"
                >
                    <LogOut class="h-4 w-4" /> Sign out
                </Link>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 lg:pl-0">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-ink-100 bg-white/95 px-5 backdrop-blur lg:px-8">
                <button type="button" class="rounded-lg p-2 text-ink-600 hover:bg-ink-100 lg:hidden" aria-label="Open menu" @click="mobileOpen = true">
                    <Menu class="h-5 w-5" />
                </button>
                <h1 class="font-display text-lg font-semibold text-ink-900">{{ title }}</h1>
            </header>

            <main class="p-5 lg:p-8">
                <slot />
            </main>
        </div>

        <Toast />
    </div>
</template>
