<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Image as ImageIcon, ImagePlus } from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Common/Modal.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Banners' }, () => page) });

const props = defineProps({
    banners: { type: Array, default: () => [] },
});

const toast = useToast();
const modalOpen = ref(false);
const editing = ref(null);
const preview = ref(null);

const form = useForm({
    eyebrow: '',
    title: '',
    subtitle: '',
    image: null,
    cta_label: '',
    cta_href: '',
    sort_order: 0,
    is_active: true,
    starts_at: '',
    ends_at: '',
});

function openCreate() {
    editing.value = null;
    preview.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(banner) {
    editing.value = banner;
    preview.value = banner.image;
    form.eyebrow = banner.eyebrow ?? '';
    form.title = banner.title;
    form.subtitle = banner.subtitle ?? '';
    form.image = null;
    form.cta_label = banner.cta_label ?? '';
    form.cta_href = banner.cta_href ?? '';
    form.sort_order = banner.sort_order;
    form.is_active = banner.is_active;
    form.starts_at = banner.starts_at ?? '';
    form.ends_at = banner.ends_at ?? '';
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
            toast.success(editing.value ? 'Banner updated.' : 'Banner created.');
        },
    };

    if (editing.value) {
        // Laravel can't parse multipart PUT bodies — spoof the method on a POST.
       form.transform((data) => ({ ...data, _method: 'put' })).post(`/admin/banners/${editing.value.id}`, options);
    } else {
        form.post('/admin/banners', options);
    }
}

function toggle(banner) {
    form.post(`/admin/banners/${banner.id}/toggle`, {
        preserveScroll: true,
        onSuccess: () => toast.success(banner.is_active ? 'Banner deactivated.' : 'Banner activated.'),
    });
}

function remove(banner) {
    if (!confirm(`Delete banner "${banner.title}"?`)) return;

    form.delete(`/admin/banners/${banner.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Banner deleted.'),
    });
}
</script>

<template>
    <Head title="Banners" />

    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-sm text-ink-500">{{ banners.length }} banners — shown on the home page slideshow</p>
            <p class="text-xs text-ink-400">Only active banners within their date range appear live.</p>
        </div>
        <Button variant="primary" @click="openCreate"><Plus class="h-4 w-4" /> New banner</Button>
    </div>

    <div v-if="banners.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="banner in banners" :key="banner.id" class="overflow-hidden rounded-xl border border-ink-100 bg-white shadow-card">
            <div class="relative aspect-[16/9] bg-ink-900">
                <img :src="banner.image" class="h-full w-full object-cover opacity-80" alt="" />
                <div class="absolute inset-0 flex flex-col justify-end p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-accent-300">{{ banner.eyebrow }}</p>
                    <p class="font-display text-lg font-bold text-white">{{ banner.title }}</p>
                </div>
                <Badge :tone="banner.is_active ? 'success' : 'neutral'" class="absolute right-3 top-3">
                    {{ banner.is_active ? 'Active' : 'Inactive' }}
                </Badge>
            </div>
            <div class="flex items-center justify-between p-3">
                <span class="text-xs text-ink-400">Order: {{ banner.sort_order }}</span>
                <div class="flex gap-1.5">
                    <button type="button" class="rounded-lg px-2 py-1 text-xs font-medium text-ink-600 hover:bg-ink-100" @click="toggle(banner)">
                        {{ banner.is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-ink-100 hover:text-ink-900" @click="openEdit(banner)">
                        <Pencil class="h-4 w-4" />
                    </button>
                    <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-red-50 hover:text-red-600" @click="remove(banner)">
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <EmptyState v-else :icon="ImageIcon" title="No banners yet" description="Add your first banner to show it on the home page." />

    <Modal :open="modalOpen" :title="editing ? 'Edit banner' : 'New banner'" max-width="lg" @close="modalOpen = false">
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-ink-700">Banner image</label>
                <label class="mt-1 flex h-40 cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-ink-200 text-ink-400 hover:border-accent-400 hover:text-accent-600">
                    <img v-if="preview" :src="preview" class="h-full w-full object-cover" alt="" />
                    <div v-else class="flex flex-col items-center gap-1">
                        <ImagePlus class="h-6 w-6" />
                        <span class="text-xs">Click to upload (recommended 1600×700px)</span>
                    </div>
                    <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                </label>
                <p v-if="form.errors.image" class="mt-1 text-xs text-red-600">{{ form.errors.image }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Small label <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.eyebrow" type="text" placeholder="e.g. Limited time" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Sort order</label>
                    <input v-model.number="form.sort_order" type="number" min="0" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700">Title</label>
                <input v-model="form.title" type="text" required placeholder="e.g. Flash Sale — Up to 50% Off" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700">Subtitle <span class="text-ink-400">(optional)</span></label>
                <input v-model="form.subtitle" type="text" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Button text <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.cta_label" type="text" placeholder="e.g. Shop Now" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Button link <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.cta_href" type="text" placeholder="/deals" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Show from <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.starts_at" type="date" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Show until <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.ends_at" type="date" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-ink-600">
                <input v-model="form.is_active" type="checkbox" class="rounded border-ink-300 text-accent-600 focus:ring-accent-500" />
                Active (visible on the home page)
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <Button type="button" variant="ghost" @click="modalOpen = false">Cancel</Button>
                <Button type="submit" variant="primary" :loading="form.processing">
                    {{ editing ? 'Save changes' : 'Create banner' }}
                </Button>
            </div>
        </form>
    </Modal>
</template>