<script setup>
import { ref, computed } from 'vue';
import { Plus, Trash2, ImagePlus, X } from 'lucide-vue-next';
import Button from '@/Components/Common/Button.vue';

const props = defineProps({
    form: { type: Object, required: true }, // Inertia useForm instance
    categories: { type: Array, default: () => [] },
    attributes: { type: Array, default: () => [] }, // [{ id, name, values: [{id, value}] }]
    existingImages: { type: Array, default: () => [] }, // [{ id, url }]
    submitLabel: { type: String, default: 'Save product' },
});

const emit = defineEmits(['submit']);

const newImagePreviews = ref([]);
const keptExistingImages = ref([...props.existingImages]);

function onFilesSelected(e) {
    const files = Array.from(e.target.files ?? []);
    props.form.images = [...(props.form.images ?? []), ...files];
    newImagePreviews.value = [
        ...newImagePreviews.value,
        ...files.map((file) => ({ file, url: URL.createObjectURL(file) })),
    ];
    e.target.value = '';
}

function removeNewImage(index) {
    newImagePreviews.value.splice(index, 1);
    props.form.images.splice(index, 1);
}

function removeExistingImage(image) {
    keptExistingImages.value = keptExistingImages.value.filter((i) => i.id !== image.id);
    props.form.removed_image_ids = [...(props.form.removed_image_ids ?? []), image.id];
}

// --- Variants ---
props.form.variants ??= [];

function addVariantRow() {
    props.form.variants.push({
        sku: '',
        price: '',
        stock: 0,
        values: Object.fromEntries(props.attributes.map((a) => [a.name, ''])),
    });
}

function removeVariantRow(index) {
    props.form.variants.splice(index, 1);
}

function submit() {
    // Flatten each row's `values` map into attribute_value_ids before sending.
    props.form.transform((data) => ({
        ...data,
        variants: (data.variants ?? [])
            .filter((v) => v.sku)
            .map((v) => ({
                sku: v.sku,
                price: v.price || null,
                stock: v.stock,
                attribute_value_ids: Object.values(v.values ?? {}).filter(Boolean),
            })),
    }));

    emit('submit');
}

defineExpose({ submit });
</script>

<template>
    <form class="space-y-8" @submit.prevent="submit">
        <!-- Basics -->
        <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Basics</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-ink-700">Product name</label>
                    <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-700">Category</label>
                    <select v-model="form.category_id" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500">
                        <option value="" disabled>Select a category</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-700">SKU</label>
                    <input v-model="form.sku" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.sku" class="mt-1 text-xs text-red-600">{{ form.errors.sku }}</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-ink-700">Short description</label>
                    <input v-model="form.short_description" type="text" maxlength="500" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-ink-700">Full description</label>
                    <textarea v-model="form.description" rows="6" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
            </div>
        </section>

        <!-- Pricing & inventory -->
        <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Pricing & inventory</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Price</label>
                    <input v-model="form.price" type="number" step="0.01" min="0" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.price" class="mt-1 text-xs text-red-600">{{ form.errors.price }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Sale price <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.sale_price" type="number" step="0.01" min="0" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.sale_price" class="mt-1 text-xs text-red-600">{{ form.errors.sale_price }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Stock</label>
                    <input v-model="form.stock" type="number" min="0" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.stock" class="mt-1 text-xs text-red-600">{{ form.errors.stock }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Weight (kg) <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.weight" type="number" step="0.01" min="0" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
            </div>
        </section>

        <!-- Images -->
        <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Images</h2>
            <p class="mt-1 text-xs text-ink-500">Up to 8 images. First image is used as the main thumbnail.</p>

            <div class="mt-4 flex flex-wrap gap-3">
                <div v-for="image in keptExistingImages" :key="image.id" class="group relative h-24 w-24 overflow-hidden rounded-lg border border-ink-100">
                    <img :src="image.url" class="h-full w-full object-cover" alt="" />
                    <button type="button" class="absolute right-1 top-1 rounded-full bg-white/90 p-1 text-ink-600 opacity-0 transition-opacity group-hover:opacity-100" @click="removeExistingImage(image)">
                        <X class="h-3 w-3" />
                    </button>
                </div>

                <div v-for="(preview, i) in newImagePreviews" :key="preview.url" class="group relative h-24 w-24 overflow-hidden rounded-lg border border-ink-100">
                    <img :src="preview.url" class="h-full w-full object-cover" alt="" />
                    <button type="button" class="absolute right-1 top-1 rounded-full bg-white/90 p-1 text-ink-600 opacity-0 transition-opacity group-hover:opacity-100" @click="removeNewImage(i)">
                        <X class="h-3 w-3" />
                    </button>
                </div>

                <label class="flex h-24 w-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-ink-200 text-ink-400 hover:border-accent-400 hover:text-accent-600">
                    <ImagePlus class="h-5 w-5" />
                    <span class="text-xs">Add</span>
                    <input type="file" accept="image/*" multiple class="hidden" @change="onFilesSelected" />
                </label>
            </div>
            <p v-if="form.errors.images" class="mt-2 text-xs text-red-600">{{ form.errors.images }}</p>
        </section>

        <!-- Variants -->
        <section v-if="attributes.length" class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-display text-base font-semibold text-ink-900">Variants</h2>
                    <p class="mt-1 text-xs text-ink-500">Optional — add if this product comes in options like color or size.</p>
                </div>
                <Button type="button" variant="ghost" size="sm" @click="addVariantRow">
                    <Plus class="h-3.5 w-3.5" /> Add variant
                </Button>
            </div>

            <div v-if="form.variants.length" class="mt-4 space-y-3">
                <div v-for="(variant, i) in form.variants" :key="i" class="grid grid-cols-2 gap-3 rounded-lg border border-ink-100 p-4 sm:grid-cols-6">
                    <div v-for="attribute in attributes" :key="attribute.id">
                        <label class="block text-xs font-medium text-ink-500">{{ attribute.name }}</label>
                        <select v-model="variant.values[attribute.name]" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500">
                            <option value="">—</option>
                            <option v-for="val in attribute.values" :key="val.id" :value="val.id">{{ val.value }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-500">SKU</label>
                        <input v-model="variant.sku" type="text" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-500">Price override</label>
                        <input v-model="variant.price" type="number" step="0.01" min="0" placeholder="—" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-500">Stock</label>
                        <input v-model="variant.stock" type="number" min="0" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="rounded-lg p-2 text-ink-400 hover:bg-red-50 hover:text-red-600" aria-label="Remove variant" @click="removeVariantRow(i)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEO & status -->
        <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">SEO & visibility</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Meta title <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.meta_title" type="text" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Status</label>
                    <select v-model="form.status" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-ink-700">Meta description <span class="text-ink-400">(optional)</span></label>
                    <textarea v-model="form.meta_description" rows="2" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <Button type="submit" variant="primary" size="lg" :loading="form.processing">{{ submitLabel }}</Button>
        </div>
    </form>
</template>
