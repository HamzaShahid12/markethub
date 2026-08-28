<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Layers } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Common/Modal.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Categories' }, () => page) });

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const toast = useToast();
const modalOpen = ref(false);
const editing = ref(null);

const parentOptions = computed(() => props.categories.filter((c) => c.id !== editing.value?.id));

const form = useForm({
    name: '',
    parent_id: '',
    description: '',
    sort_order: 0,
    status: 'active',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(category) {
    editing.value = category;
    form.name = category.name;
    form.parent_id = category.parent_id ?? '';
    form.description = category.description ?? '';
    form.sort_order = category.sort_order ?? 0;
    form.status = category.status;
    form.clearErrors();
    modalOpen.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
            toast.success(editing.value ? 'Category updated.' : 'Category created.');
        },
    };

    if (editing.value) {
        form.put(`/admin/categories/${editing.value.id}`, options);
    } else {
        form.post('/admin/categories', options);
    }
}

function remove(category) {
    if (!confirm(`Delete "${category.name}"? This can't be undone.`)) return;

    form.delete(`/admin/categories/${category.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Category deleted.'),
        onError: () => toast.error('Move or remove its products/subcategories first.'),
    });
}
</script>

<template>
    <Head title="Categories" />

    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-ink-500">{{ categories.length }} categories</p>
        <Button variant="primary" @click="openCreate">
            <Plus class="h-4 w-4" /> New category
        </Button>
    </div>

    <div v-if="categories.length" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 bg-ink-50 text-xs uppercase tracking-wide text-ink-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Parent</th>
                    <th class="px-5 py-3 font-medium">Products</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="category in categories" :key="category.id" class="hover:bg-ink-50">
                    <td class="px-5 py-3 font-medium text-ink-900">{{ category.name }}</td>
                    <td class="px-5 py-3 text-ink-500">{{ category.parent?.name ?? '—' }}</td>
                    <td class="px-5 py-3 text-ink-600">{{ category.products_count }}</td>
                    <td class="px-5 py-3">
                        <Badge :tone="category.status === 'active' ? 'success' : 'neutral'" class="capitalize">{{ category.status }}</Badge>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex gap-1.5">
                            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-ink-100 hover:text-ink-900" aria-label="Edit" @click="openEdit(category)">
                                <Pencil class="h-4 w-4" />
                            </button>
                            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-red-50 hover:text-red-600" aria-label="Delete" @click="remove(category)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <EmptyState v-else :icon="Layers" title="No categories yet" description="Create your first category to start organizing products." />

    <Modal :open="modalOpen" :title="editing ? 'Edit category' : 'New category'" @close="modalOpen = false">
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-ink-700">Name</label>
                <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700">Parent category <span class="text-ink-400">(optional)</span></label>
                <select v-model="form.parent_id" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500">
                    <option value="">None (top-level)</option>
                    <option v-for="c in parentOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700">Description <span class="text-ink-400">(optional)</span></label>
                <textarea v-model="form.description" rows="2" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Sort order</label>
                    <input v-model.number="form.sort_order" type="number" min="0" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Status</label>
                    <select v-model="form.status" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button type="button" variant="ghost" @click="modalOpen = false">Cancel</Button>
                <Button type="submit" variant="primary" :loading="form.processing">
                    {{ editing ? 'Save changes' : 'Create category' }}
                </Button>
            </div>
        </form>
    </Modal>
</template>
