<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import ProductForm from '@/Components/Vendor/ProductForm.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Edit product' }, () => page) });

const props = defineProps({
    product: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    attributes: { type: Array, default: () => [] },
});

const toast = useToast();

const existingImages = computed(() =>
    props.product.images.map((img) => ({ id: img.id, url: `/storage/${img.image}` })),
);

const initialVariants = computed(() =>
    props.product.variants.map((variant) => ({
        sku: variant.sku,
        price: variant.price ?? '',
        stock: variant.stock,
        values: Object.fromEntries(
            props.attributes.map((attr) => {
                const match = variant.attribute_values.find((av) => av.attribute.name === attr.name);
                return [attr.name, match?.id ?? ''];
            }),
        ),
    })),
);

const form = useForm({
    category_id: props.product.category_id,
    name: props.product.name,
    sku: props.product.sku,
    short_description: props.product.short_description ?? '',
    description: props.product.description ?? '',
    price: props.product.price,
    sale_price: props.product.sale_price ?? '',
    stock: props.product.stock,
    weight: props.product.weight ?? '',
    meta_title: props.product.meta_title ?? '',
    meta_description: props.product.meta_description ?? '',
    status: props.product.status,
    images: [],
    removed_image_ids: [],
    variants: initialVariants.value,
});

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(`/vendor/products/${props.product.id}`, {
        forceFormData: true,
        onSuccess: () => toast.success('Product updated.'),
    });
}
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <ProductForm
        :form="form"
        :categories="categories"
        :attributes="attributes"
        :existing-images="existingImages"
        submit-label="Save changes"
        @submit="submit"
    />
</template>
