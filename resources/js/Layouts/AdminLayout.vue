<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    LayoutDashboard, Users, Store, Package, Layers, ShoppingBag,
    TicketPercent, Star, Percent, BarChart3, Settings,
} from 'lucide-vue-next';
import DashboardShell from '@/Components/Layout/DashboardShell.vue';

const props = defineProps({ title: { type: String, default: 'Dashboard' } });
const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);

const navItems = computed(() => [
    { label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard, active: currentPath.value === '/admin/dashboard' },
    { label: 'Users', href: '/admin/users', icon: Users, active: currentPath.value === '/admin/users' },
    { label: 'Vendors', href: '/admin/vendors', icon: Store, active: currentPath.value.startsWith('/admin/vendors') },
    { label: 'Products', href: '/admin/products', icon: Package, active: currentPath.value === '/admin/products' },
    { label: 'Categories', href: '/admin/categories', icon: Layers, active: currentPath.value === '/admin/categories' },
    { label: 'Orders', href: '/admin/orders', icon: ShoppingBag, active: currentPath.value === '/admin/orders' },
    { label: 'Coupons', href: '/admin/coupons', icon: TicketPercent, active: currentPath.value === '/admin/coupons' },
    { label: 'Reviews', href: '/admin/reviews', icon: Star, active: currentPath.value === '/admin/reviews' },
    { label: 'Commissions', href: '/admin/commissions', icon: Percent, active: currentPath.value === '/admin/commissions' },
    { label: 'Reports', href: '/admin/reports', icon: BarChart3, active: currentPath.value === '/admin/reports' },
    { label: 'Settings', href: '/admin/settings', icon: Settings, active: currentPath.value === '/admin/settings' },
]);
</script>

<template>
    <DashboardShell :nav-items="navItems" role-label="Admin" :title="title">
        <slot />
    </DashboardShell>
</template>
