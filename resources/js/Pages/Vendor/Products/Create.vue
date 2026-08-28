<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import ProductForm from '@/Components/Vendor/ProductForm.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Add product' }, () => page) });

const props = defineProps({
    categories: { type: Array, default: () => [] },
    attributes: { type: Array, default: () => [] },
});

const toast = useToast();

const form = useForm({
    category_id: '',
    name: '',
    sku: '',
    short_description: '',
    description: '',
    price: '',
    sale_price: '',
    stock: 0,
    weight: '',
    meta_title: '',
    meta_description: '',
    status: 'draft',
    images: [],
    removed_image_ids: [],
    variants: [],
});

function submit() {
    form.post('/vendor/products', {
        forceFormData: true,
        onSuccess: () => toast.success('Product created.'),
    });
}
</script>

<template>
    <Head title="Add product" />

    <ProductForm
        :form="form"
        :categories="categories"
        :attributes="attributes"
        submit-label="Create product"
        @submit="submit"
    />
</template>
