<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Store, Check, X, Ban } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Common/Badge.vue';
import Button from '@/Components/Common/Button.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Vendors' }, () => page) });

const props = defineProps({
    vendors: { type: Object, required: true }, // paginator
    filters: { type: Object, required: true },
    counts: { type: Object, required: true },
});

const toast = useToast();

const tabs = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Pending' },
    { key: 'approved', label: 'Approved' },
    { key: 'suspended', label: 'Suspended' },
    { key: 'rejected', label: 'Rejected' },
];

const statusTone = {
    pending: 'warning',
    approved: 'success',
    suspended: 'danger',
    rejected: 'neutral',
};

function filterBy(status) {
    router.get('/admin/vendors', { status }, { preserveState: true, preserveScroll: true });
}

function act(vendor, action) {
    const verbs = { approve: 'approved', reject: 'rejected', suspend: 'suspended' };

    router.post(`/admin/vendors/${vendor.id}/${action}`, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success(`${vendor.shop_name} was ${verbs[action]}.`),
        onError: () => toast.error('Something went wrong — please try again.'),
    });
}
</script>

<template>
    <Head title="Vendors" />

    <div class="mb-6 flex flex-wrap gap-2">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
            :class="filters.status === tab.key ? 'bg-ink-900 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100'"
            @click="filterBy(tab.key)"
        >
            {{ tab.label }}
            <span class="ml-1 opacity-70">{{ counts[tab.key] }}</span>
        </button>
    </div>

    <div v-if="vendors.data.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Shop</th>
                    <th class="px-5 py-3 font-medium">Owner</th>
                    <th class="px-5 py-3 font-medium">Products</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="vendor in vendors.data" :key="vendor.id" class="hover:bg-ink-50">
                    <td class="px-5 py-3 font-medium text-ink-900">{{ vendor.shop_name }}</td>
                    <td class="px-5 py-3 text-ink-600">
                        <p>{{ vendor.user.name }}</p>
                        <p class="text-xs text-ink-400">{{ vendor.user.email }}</p>
                    </td>
                    <td class="px-5 py-3 text-ink-600">{{ vendor.products_count }}</td>
                    <td class="px-5 py-3"><Badge :tone="statusTone[vendor.status]" class="capitalize">{{ vendor.status }}</Badge></td>
                    <td class="px-5 py-3">
                        <div class="flex gap-2">
                            <Button v-if="vendor.status === 'pending'" size="sm" variant="secondary" @click="act(vendor, 'approve')">
                                <Check class="h-3.5 w-3.5" /> Approve
                            </Button>
                            <Button v-if="vendor.status === 'pending'" size="sm" variant="ghost" @click="act(vendor, 'reject')">
                                <X class="h-3.5 w-3.5" /> Reject
                            </Button>
                            <Button v-if="vendor.status === 'approved'" size="sm" variant="danger" @click="act(vendor, 'suspend')">
                                <Ban class="h-3.5 w-3.5" /> Suspend
                            </Button>
                            <Button v-if="vendor.status === 'suspended'" size="sm" variant="secondary" @click="act(vendor, 'approve')">
                                <Check class="h-3.5 w-3.5" /> Reinstate
                            </Button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <EmptyState v-else :icon="Store" title="No vendors here" description="Nothing matches this filter yet." />

    <div v-if="vendors.links.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="link in vendors.links"
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
