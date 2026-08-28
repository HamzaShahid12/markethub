<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutDashboard, Package, Heart, MessageCircle, MapPin, Star, User } from 'lucide-vue-next';
import DashboardShell from '@/Components/Layout/DashboardShell.vue';

const props = defineProps({ title: { type: String, default: 'Dashboard' } });
const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);

const navItems = computed(() => [
    { label: 'Dashboard', href: '/customer/dashboard', icon: LayoutDashboard, active: currentPath.value === '/customer/dashboard' },
    { label: 'Orders', href: '/customer/orders', icon: Package, active: currentPath.value.startsWith('/customer/orders') },
    { label: 'Wishlist', href: '/customer/wishlist', icon: Heart, active: currentPath.value === '/customer/wishlist' },
    { label: 'Messages', href: '/customer/messages', icon: MessageCircle, active: currentPath.value.startsWith('/customer/messages') },
    { label: 'Addresses', href: '/customer/addresses', icon: MapPin, active: currentPath.value === '/customer/addresses' },
    { label: 'Reviews', href: '/customer/reviews', icon: Star, active: currentPath.value === '/customer/reviews' },
    { label: 'Profile', href: '/customer/profile', icon: User, active: currentPath.value === '/customer/profile' },
]);
</script>

<template>
    <DashboardShell :nav-items="navItems" role-label="Customer" :title="title">
        <slot />
    </DashboardShell>
</template>
