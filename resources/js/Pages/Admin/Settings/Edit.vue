<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Common/Button.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Settings' }, () => page) });

const props = defineProps({
    settings: { type: Object, required: true },
});

const toast = useToast();

const form = useForm({
    category_display_style: props.settings.category_display_style,
});

const styles = [
    {
        value: 'circle',
        label: 'Circle',
        description: 'Round images with the name below — clean and compact.',
    },
    {
        value: 'card',
        label: 'Card',
        description: 'Horizontal cards with the image as a background and name overlaid.',
    },
    {
        value: 'square',
        label: 'Square tile',
        description: 'Square image tiles with the name below, grid layout.',
    },
];

function submit() {
    form.put('/admin/settings', {
        preserveScroll: true,
        onSuccess: () => toast.success('Home page updated.'),
    });
}
</script>

<template>
    <Head title="Settings" />

    <form @submit.prevent="submit" class="max-w-3xl">
        <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">"Shop by category" layout</h2>
            <p class="mt-1 text-sm text-ink-500">Choose how categories appear on the home page.</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <button
                    v-for="style in styles"
                    :key="style.value"
                    type="button"
                    class="relative rounded-xl border-2 p-4 text-left transition-colors"
                    :class="form.category_display_style === style.value
                        ? 'border-accent-500 bg-accent-50'
                        : 'border-ink-100 hover:border-ink-300'"
                    @click="form.category_display_style = style.value"
                >
                    <span
                        v-if="form.category_display_style === style.value"
                        class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full bg-accent-500 text-white"
                    >
                        <Check class="h-3 w-3" />
                    </span>

                    <!-- Mini preview -->
                    <div class="mb-3 flex h-16 items-center justify-center gap-2 rounded-lg bg-ink-50">
                        <template v-if="style.value === 'circle'">
                            <span v-for="n in 3" :key="n" class="h-8 w-8 rounded-full bg-ink-300" />
                        </template>
                        <template v-else-if="style.value === 'card'">
                            <span class="h-12 w-full rounded-md bg-ink-300 mx-3" />
                        </template>
                        <template v-else>
                            <span v-for="n in 3" :key="n" class="h-10 w-10 rounded-md bg-ink-300" />
                        </template>
                    </div>

                    <p class="text-sm font-semibold text-ink-900">{{ style.label }}</p>
                    <p class="mt-1 text-xs text-ink-500">{{ style.description }}</p>
                </button>
            </div>
        </section>

        <div class="mt-6 flex justify-end">
            <Button type="submit" variant="primary" :loading="form.processing">Save changes</Button>
        </div>
    </form>
</template>