<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutDashboard, Package, Boxes, ShoppingBag, MessageCircle, TrendingUp, Wallet, Star, Store } from 'lucide-vue-next';
import DashboardShell from '@/Components/Layout/DashboardShell.vue';

const props = defineProps({ title: { type: String, default: 'Dashboard' } });
const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);

const navItems = computed(() => [
    { label: 'Dashboard', href: '/vendor/dashboard', icon: LayoutDashboard, active: currentPath.value === '/vendor/dashboard' },
    { label: 'Products', href: '/vendor/products', icon: Package, active: currentPath.value.startsWith('/vendor/products') },
    { label: 'Inventory', href: '/vendor/inventory', icon: Boxes, active: currentPath.value === '/vendor/inventory' },
    { label: 'Orders', href: '/vendor/orders', icon: ShoppingBag, active: currentPath.value.startsWith('/vendor/orders') },
    { label: 'Messages', href: '/vendor/messages', icon: MessageCircle, active: currentPath.value.startsWith('/vendor/messages') },
    { label: 'Sales', href: '/vendor/sales', icon: TrendingUp, active: currentPath.value === '/vendor/sales' },
    { label: 'Earnings', href: '/vendor/earnings', icon: Wallet, active: currentPath.value === '/vendor/earnings' },
    { label: 'Payouts', href: '/vendor/payouts', icon: Wallet, active: currentPath.value === '/vendor/payouts' },
    { label: 'Reviews', href: '/vendor/reviews', icon: Star, active: currentPath.value === '/vendor/reviews' },
    { label: 'Store Profile', href: '/vendor/store-profile', icon: Store, active: currentPath.value === '/vendor/store-profile' },
]);
</script>

<template>
    <DashboardShell :nav-items="navItems" role-label="Vendor" :title="title">
        <slot />
    </DashboardShell>
</template>
