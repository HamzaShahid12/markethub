<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, TicketPercent } from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Common/Modal.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Coupons' }, () => page) });

defineProps({
    coupons: { type: Array, default: () => [] },
});

const toast = useToast();
const modalOpen = ref(false);
const editing = ref(null);

const form = useForm({
    code: '',
    type: 'percentage',
    value: '',
    minimum_amount: '',
    maximum_discount: '',
    usage_limit: '',
    starts_at: '',
    expires_at: '',
    status: 'active',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(coupon) {
    editing.value = coupon;
    form.code = coupon.code;
    form.type = coupon.type;
    form.value = coupon.value;
    form.minimum_amount = coupon.minimum_amount ?? '';
    form.maximum_discount = coupon.maximum_discount ?? '';
    form.usage_limit = coupon.usage_limit ?? '';
    form.starts_at = coupon.starts_at?.slice(0, 10) ?? '';
    form.expires_at = coupon.expires_at?.slice(0, 10) ?? '';
    form.status = coupon.status;
    form.clearErrors();
    modalOpen.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
            toast.success(editing.value ? 'Coupon updated.' : 'Coupon created.');
        },
    };

    if (editing.value) {
        form.put(`/admin/coupons/${editing.value.id}`, options);
    } else {
        form.post('/admin/coupons', options);
    }
}

function remove(coupon) {
    if (!confirm(`Delete coupon "${coupon.code}"?`)) return;

    form.delete(`/admin/coupons/${coupon.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Coupon deleted.'),
    });
}
</script>

<template>
    <Head title="Coupons" />

    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-ink-500">{{ coupons.length }} coupons</p>
        <Button variant="primary" @click="openCreate"><Plus class="h-4 w-4" /> New coupon</Button>
    </div>

    <div v-if="coupons.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Discount</th>
                    <th class="px-5 py-3 font-medium">Min. order</th>
                    <th class="px-5 py-3 font-medium">Usage</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="coupon in coupons" :key="coupon.id" class="hover:bg-ink-50">
                    <td class="px-5 py-3 font-mono font-medium text-ink-900">{{ coupon.code }}</td>
                    <td class="px-5 py-3 text-ink-600">{{ coupon.type === 'percentage' ? `${coupon.value}%` : `$${Number(coupon.value).toFixed(2)}` }}</td>
                    <td class="px-5 py-3 text-ink-500">{{ coupon.minimum_amount ? `$${Number(coupon.minimum_amount).toFixed(2)}` : '—' }}</td>
                    <td class="px-5 py-3 text-ink-500">{{ coupon.used_count }}{{ coupon.usage_limit ? ` / ${coupon.usage_limit}` : '' }}</td>
                    <td class="px-5 py-3"><Badge :tone="coupon.status === 'active' ? 'success' : 'neutral'" class="capitalize">{{ coupon.status }}</Badge></td>
                    <td class="px-5 py-3">
                        <div class="flex gap-1.5">
                            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-ink-100 hover:text-ink-900" aria-label="Edit" @click="openEdit(coupon)">
                                <Pencil class="h-4 w-4" />
                            </button>
                            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-red-50 hover:text-red-600" aria-label="Delete" @click="remove(coupon)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <EmptyState v-else :icon="TicketPercent" title="No coupons yet" description="Create one to offer a discount." />

    <Modal :open="modalOpen" :title="editing ? 'Edit coupon' : 'New coupon'" max-width="lg" @close="modalOpen = false">
        <form class="space-y-4" @submit.prevent="submit">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Code</label>
                    <input v-model="form.code" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm uppercase focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Type</label>
                    <select v-model="form.type" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed amount</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Value</label>
                    <input v-model="form.value" type="number" step="0.01" min="0" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.value" class="mt-1 text-xs text-red-600">{{ form.errors.value }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Max discount <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.maximum_discount" type="number" step="0.01" min="0" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Minimum order <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.minimum_amount" type="number" step="0.01" min="0" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Usage limit <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.usage_limit" type="number" min="1" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Starts <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.starts_at" type="date" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Expires <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.expires_at" type="date" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.expires_at" class="mt-1 text-xs text-red-600">{{ form.errors.expires_at }}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700">Status</label>
                <select v-model="form.status" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button type="button" variant="ghost" @click="modalOpen = false">Cancel</Button>
                <Button type="submit" variant="primary" :loading="form.processing">{{ editing ? 'Save changes' : 'Create coupon' }}</Button>
            </div>
        </form>
    </Modal>
</template>
