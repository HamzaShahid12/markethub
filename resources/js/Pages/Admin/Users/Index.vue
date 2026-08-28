<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Users, Search } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import Button from '@/Components/Common/Button.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Users' }, () => page) });

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const toast = useToast();
const roles = ['all', 'customer', 'vendor', 'admin'];

function filterByRole(role) {
    router.get('/admin/users', { ...props.filters, role }, { preserveState: true, preserveScroll: true });
}

function search(e) {
    router.get('/admin/users', { ...props.filters, search: e.target.value }, { preserveState: true, preserveScroll: true, replace: true });
}

function toggleStatus(user) {
    router.post(`/admin/users/${user.id}/toggle-status`, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success(user.status === 'active' ? 'User suspended.' : 'User reactivated.'),
        onError: () => toast.error('Could not update that user.'),
    });
}
</script>

<template>
    <Head title="Users" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <button
                v-for="role in roles"
                :key="role"
                type="button"
                class="rounded-full px-4 py-1.5 text-sm font-medium capitalize transition-colors"
                :class="filters.role === role ? 'bg-ink-900 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100'"
                @click="filterByRole(role)"
            >
                {{ role }}
            </button>
        </div>
        <div class="relative">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
            <input type="search" placeholder="Name or email..." :value="filters.search" class="rounded-lg border-ink-200 py-2 pl-9 pr-3 text-sm focus:border-accent-500 focus:ring-accent-500" @change="search" />
        </div>
    </div>

    <div v-if="users.data.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Role</th>
                    <th class="px-5 py-3 font-medium">Orders</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="user in users.data" :key="user.id" class="hover:bg-ink-50">
                    <td class="px-5 py-3 font-medium text-ink-900">{{ user.name }}</td>
                    <td class="px-5 py-3 text-ink-500">{{ user.email }}</td>
                    <td class="px-5 py-3"><Badge class="capitalize">{{ user.role }}</Badge></td>
                    <td class="px-5 py-3 text-ink-600">{{ user.orders_count }}</td>
                    <td class="px-5 py-3">
                        <Badge :tone="user.status === 'active' ? 'success' : 'danger'" class="capitalize">{{ user.status }}</Badge>
                    </td>
                    <td class="px-5 py-3">
                        <Button v-if="user.role !== 'admin'" size="sm" :variant="user.status === 'active' ? 'danger' : 'secondary'" @click="toggleStatus(user)">
                            {{ user.status === 'active' ? 'Suspend' : 'Reactivate' }}
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <EmptyState v-else :icon="Users" title="No users found" description="Nothing matches this filter yet." />

    <div v-if="users.links.length > 3" class="mt-6 flex flex-wrap gap-1">
        <Link
            v-for="link in users.links"
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
