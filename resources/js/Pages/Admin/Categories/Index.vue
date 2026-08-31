<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Layers, ImagePlus } from 'lucide-vue-next';
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
const preview = ref(null);

const parentOptions = computed(() => props.categories.filter((c) => c.id !== editing.value?.id));

const form = useForm({
    name: '',
    parent_id: '',
    description: '',
    image: null,
    sort_order: 0,
    status: 'active',
});

function openCreate() {
    editing.value = null;
    preview.value = null;
    form.reset();
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(category) {
    editing.value = category;
    preview.value = category.image;
    form.name = category.name;
    form.parent_id = category.parent_id ?? '';
    form.description = category.description ?? '';
    form.image = null;
    form.sort_order = category.sort_order ?? 0;
    form.status = category.status;
    form.clearErrors();
    modalOpen.value = true;
}

function onImageChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    preview.value = URL.createObjectURL(file);
}

function submit() {
    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
            toast.success(editing.value ? 'Category updated.' : 'Category created.');
        },
    };

    if (editing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/admin/categories/${editing.value.id}`, options);
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
                    <th class="px-5 py-3 font-medium">Image</th>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Parent</th>
                    <th class="px-5 py-3 font-medium">Products</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                <tr v-for="category in categories" :key="category.id" class="hover:bg-ink-50">
                    <td class="px-5 py-3">
                        <div class="h-10 w-10 overflow-hidden rounded-full bg-ink-100">
                            <img v-if="category.image" :src="category.image" class="h-full w-full object-cover" alt="" />
                            <div v-else class="flex h-full w-full items-center justify-center text-xs font-semibold text-ink-400">
                                {{ category.name.charAt(0) }}
                            </div>
                        </div>
                    </td>
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
                <label class="block text-sm font-medium text-ink-700">Category image</label>
                <p class="text-xs text-ink-400">Shown as a circle on the home page — a square image works best.</p>
                <label class="mt-2 flex h-28 w-28 cursor-pointer items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-ink-200 text-ink-400 hover:border-accent-400 hover:text-accent-600">
                    <img v-if="preview" :src="preview" class="h-full w-full object-cover" alt="" />
                    <div v-else class="flex flex-col items-center gap-1">
                        <ImagePlus class="h-6 w-6" />
                        <span class="text-[10px]">Upload</span>
                    </div>
                    <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                </label>
                <p v-if="form.errors.image" class="mt-1 text-xs text-red-600">{{ form.errors.image }}</p>
            </div>

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