<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ImagePlus } from 'lucide-vue-next';
import VendorLayout from '@/Layouts/VendorLayout.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: (h, page) => h(VendorLayout, { title: 'Store profile' }, () => page) });

const props = defineProps({
    vendor: { type: Object, required: true },
});

const toast = useToast();

const form = useForm({
    shop_name: props.vendor.shop_name,
    description: props.vendor.description ?? '',
    phone: props.vendor.phone ?? '',
    address: props.vendor.address ?? '',
    logo: null,
    banner: null,
    payout_method: props.vendor.payout_method ?? '',
    bank_name: props.vendor.bank_name ?? '',
    account_title: props.vendor.account_title ?? '',
    account_number: props.vendor.account_number ?? '',
    iban: props.vendor.iban ?? '',
    payout_phone: props.vendor.payout_phone ?? '',
});
const logoPreview = ref(props.vendor.logo);
const bannerPreview = ref(props.vendor.banner);

function onLogoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.logo = file;
    logoPreview.value = URL.createObjectURL(file);
}

function onBannerChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.banner = file;
    bannerPreview.value = URL.createObjectURL(file);
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post('/vendor/store-profile', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Store profile updated.'),
    });
}

const statusTone = { pending: 'warning', approved: 'success', suspended: 'danger', rejected: 'neutral' };
</script>

<template>
    <Head title="Store profile" />

    <div class="mb-6 flex items-center gap-3">
        <Badge :tone="statusTone[vendor.status]" class="capitalize">{{ vendor.status }}</Badge>
        <span class="text-sm text-ink-500">Commission rate: {{ vendor.commission_rate }}% · Rating: {{ Number(vendor.rating_average).toFixed(1) }}★</span>
    </div>

    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Branding</h2>
            <div class="mt-4 flex flex-wrap gap-6">
                <div>
                    <p class="mb-2 text-sm font-medium text-ink-700">Logo</p>
                    <label class="flex h-24 w-24 cursor-pointer items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-ink-200 text-ink-400 hover:border-accent-400 hover:text-accent-600">
                        <img v-if="logoPreview" :src="logoPreview" class="h-full w-full object-cover" alt="" />
                        <ImagePlus v-else class="h-6 w-6" />
                        <input type="file" accept="image/*" class="hidden" @change="onLogoChange" />
                    </label>
                </div>
                <div class="flex-1">
                    <p class="mb-2 text-sm font-medium text-ink-700">Banner</p>
                    <label class="flex h-24 w-full max-w-sm cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-ink-200 text-ink-400 hover:border-accent-400 hover:text-accent-600">
                        <img v-if="bannerPreview" :src="bannerPreview" class="h-full w-full object-cover" alt="" />
                        <ImagePlus v-else class="h-6 w-6" />
                        <input type="file" accept="image/*" class="hidden" @change="onBannerChange" />
                    </label>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-semibold text-ink-900">Shop details</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Shop name</label>
                    <input v-model="form.shop_name" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.shop_name" class="mt-1 text-xs text-red-600">{{ form.errors.shop_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Description</label>
                    <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-ink-700">Phone</label>
                        <input v-model="form.phone" type="text" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-700">Address</label>
                        <input v-model="form.address" type="text" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
    <h2 class="font-display text-base font-semibold text-ink-900">Payout details</h2>
    <p class="mt-1 text-xs text-ink-500">Where should we send your earnings?</p>

    <div class="mt-4 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink-700">Payout method</label>
            <select v-model="form.payout_method" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500">
                <option value="">Select a method</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="jazzcash">JazzCash</option>
                <option value="easypaisa">Easypaisa</option>
                <option value="paypal">PayPal</option>
            </select>
        </div>

        <template v-if="form.payout_method === 'bank_transfer'">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Bank name</label>
                    <input v-model="form.bank_name" type="text" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Account title</label>
                    <input v-model="form.account_title" type="text" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Account number</label>
                    <input v-model="form.account_number" type="text" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">IBAN <span class="text-ink-400">(optional)</span></label>
                    <input v-model="form.iban" type="text" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                </div>
            </div>
        </template>

        <template v-else-if="form.payout_method === 'jazzcash' || form.payout_method === 'easypaisa'">
            <div>
                <label class="block text-sm font-medium text-ink-700">{{ form.payout_method === 'jazzcash' ? 'JazzCash' : 'Easypaisa' }} phone number</label>
                <input v-model="form.payout_phone" type="text" placeholder="03XX-XXXXXXX" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
            </div>
        </template>

        <template v-else-if="form.payout_method === 'paypal'">
            <div>
                <label class="block text-sm font-medium text-ink-700">PayPal email</label>
                <input v-model="form.account_title" type="email" class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
            </div>
        </template>
    </div>
</section>

        <div class="flex justify-end">
            <Button type="submit" variant="primary" size="lg" :loading="form.processing">Save changes</Button>
        </div>
    </form>
</template>
