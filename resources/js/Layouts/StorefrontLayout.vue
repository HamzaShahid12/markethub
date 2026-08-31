<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watchEffect } from 'vue';
import { Search, Heart, ShoppingBag, User, Menu, X, Store, Bell } from 'lucide-vue-next';
import { useAuthStore } from '@/Stores/auth';
import { useCartStore } from '@/Stores/cart';
import { useWishlistStore } from '@/Stores/wishlist';
import { useNotificationsStore } from '@/Stores/notifications';
import Toast from '@/Components/Common/Toast.vue';

const page = usePage();
const auth = useAuthStore();
const cart = useCartStore();
const wishlist = useWishlistStore();
const notifications = useNotificationsStore();

watchEffect(() => {
    auth.setUser(page.props.auth?.user ?? null);
});

onMounted(() => {
    if (auth.isLoggedIn) {
        cart.fetch();
        wishlist.fetch();
        notifications.fetchUnreadCount();
        notifications.listen(auth.user.id);
    }
});

const mobileOpen = ref(false);
const searchQuery = ref('');

const accountHref = computed(() => {
    if (!auth.isLoggedIn) return '/login';
    if (auth.isAdmin) return '/admin/dashboard';
    if (auth.isVendor) return '/vendor/dashboard';
    return '/customer/dashboard';
});

const navLinks = [
    { label: 'Shop', href: '/products' },
    { label: 'Categories', href: '/categories' },
    { label: 'Deals', href: '/deals' },
    { label: 'Vendors', href: '/vendors' },
];
</script>

<template>
    <div class="flex min-h-screen flex-col bg-ink-50">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">
            Skip to content
        </a>

        <header class="sticky top-0 z-40 border-b border-ink-100 bg-white/95 backdrop-blur">
            <div class="container-page flex h-16 items-center gap-4">
                <button
                    type="button"
                    class="rounded-lg p-2 text-ink-600 hover:bg-ink-100 lg:hidden"
                    aria-label="Open menu"
                    @click="mobileOpen = true"
                >
                    <Menu class="h-5 w-5" />
                </button>

                <Link href="/" class="flex items-center gap-2 font-display text-xl font-bold text-ink-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink-900 text-white">
                        <Store class="h-4 w-4" />
                    </span>
                    MarketHub
                </Link>

                <nav class="hidden items-center gap-6 lg:flex">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="text-sm font-medium text-ink-600 transition-colors hover:text-ink-900"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <form action="/products" method="get" class="ml-auto hidden max-w-md flex-1 items-center md:flex">
                    <div class="relative w-full">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
                        <input
                            v-model="searchQuery"
                            name="search"
                            type="search"
                            placeholder="Search products, vendors..."
                            class="w-full rounded-lg border-ink-200 bg-ink-50 py-2 pl-9 pr-3 text-sm placeholder:text-ink-400 focus:border-accent-500 focus:bg-white focus:ring-accent-500"
                        />
                    </div>
                </form>

                <div class="ml-auto flex items-center gap-1 md:ml-0">
                    <Link v-if="auth.isLoggedIn" href="/notifications" class="relative rounded-lg p-2 text-ink-600 hover:bg-ink-100" aria-label="Notifications">
                        <Bell class="h-5 w-5" />
                        <span
                            v-if="notifications.unreadCount > 0"
                            class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-accent-500 text-[10px] font-bold text-white"
                        >
                            {{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}
                        </span>
                    </Link>
                    <Link href="/wishlist" class="relative rounded-lg p-2 text-ink-600 hover:bg-ink-100" aria-label="Wishlist">
                        <Heart class="h-5 w-5" />
                        <span
                            v-if="wishlist.items.length > 0"
                            class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                        >
                            {{ wishlist.items.length }}
                        </span>
                    </Link>
                    <Link href="/cart" class="relative rounded-lg p-2 text-ink-600 hover:bg-ink-100" aria-label="Cart">
                        <ShoppingBag class="h-5 w-5" />
                        <span
                            v-if="cart.itemCount > 0"
                            class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-accent-500 text-[10px] font-bold text-white"
                        >
                            {{ cart.itemCount }}
                        </span>
                    </Link>
                    <Link :href="accountHref" class="rounded-lg p-2 text-ink-600 hover:bg-ink-100" aria-label="Account">
                        <User class="h-5 w-5" />
                    </Link>
                </div>
            </div>
        </header>

        <!-- Mobile nav drawer -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileOpen" class="fixed inset-0 z-50 bg-ink-900/40 lg:hidden" @click="mobileOpen = false" />
        </Transition>
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <nav
                v-if="mobileOpen"
                class="fixed inset-y-0 left-0 z-50 w-72 bg-white p-6 shadow-elevated lg:hidden"
                aria-label="Mobile navigation"
            >
                <div class="mb-6 flex items-center justify-between">
                    <span class="font-display text-lg font-bold text-ink-900">MarketHub</span>
                    <button type="button" class="rounded-lg p-2 hover:bg-ink-100" aria-label="Close menu" @click="mobileOpen = false">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <ul class="flex flex-col gap-1">
                    <li v-for="link in navLinks" :key="link.href">
                        <Link :href="link.href" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-100">
                            {{ link.label }}
                        </Link>
                    </li>
                </ul>
            </nav>
        </Transition>

        <main id="main-content" class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-ink-100 bg-white">
            <div class="container-page grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="font-display text-lg font-bold text-ink-900">MarketHub</p>
                    <p class="mt-2 max-w-xs text-sm text-ink-500">
                        A marketplace where independent vendors sell directly to customers who trust what they buy.
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink-900">Shop</p>
                    <ul class="mt-3 space-y-2 text-sm text-ink-500">
                        <li><Link href="/products" class="hover:text-ink-900">All products</Link></li>
                        <li><Link href="/deals" class="hover:text-ink-900">Flash deals</Link></li>
                        <li><Link href="/vendors" class="hover:text-ink-900">All vendors</Link></li>
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink-900">Sell</p>
                    <ul class="mt-3 space-y-2 text-sm text-ink-500">
                        <li><Link href="/vendor/register" class="hover:text-ink-900">Become a vendor</Link></li>
                        <li><Link href="/vendor/dashboard" class="hover:text-ink-900">Vendor dashboard</Link></li>
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink-900">Stay in the loop</p>
                    <form class="mt-3 flex gap-2">
                        <input
                            type="email"
                            required
                            placeholder="you@example.com"
                            class="w-full rounded-lg border-ink-200 text-sm placeholder:text-ink-400 focus:border-accent-500 focus:ring-accent-500"
                        />
                        <button type="submit" class="shrink-0 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800">
                            Join
                        </button>
                    </form>
                </div>
            </div>
            <div class="border-t border-ink-100 py-6 text-center text-xs text-ink-400">
                © {{ new Date().getFullYear() }} MarketHub. Developed by NexMind Innovations.
            </div>
        </footer>

        <Toast />
    </div>
</template>
