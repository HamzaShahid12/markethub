<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Button from '@/Components/Common/Button.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
    items: { type: Array, default: () => [] },
    subtotal: { type: Number, required: true },
});

const page = usePage();
const toast = useToast();

const form = useForm({
    name: page.props.auth?.user?.name ?? '',
    line1: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    phone: page.props.auth?.user?.phone ?? '',
    coupon_code: '',
    payment_method: 'cod',
});

const couponState = ref({ applying: false, applied: null, error: null });

async function applyCoupon() {
    if (!form.coupon_code) return;

    couponState.value = { applying: true, applied: null, error: null };

    try {
        const { data } = await axios.post('/api/coupons/validate', {
            code: form.coupon_code,
            subtotal: props.subtotal,
        });
        couponState.value = { applying: false, applied: data, error: null };
        toast.success(`Coupon applied: -$${Number(data.discount).toFixed(2)}`);
    } catch (e) {
        couponState.value = { applying: false, applied: null, error: e.response?.data?.message ?? 'Invalid coupon.' };
    }
}

function money(value) {
    return `$${Number(value).toFixed(2)}`;
}

const shippingFee = subtotalValue => (subtotalValue >= 100 ? 0 : 10);
const discount = () => couponState.value.applied?.discount ?? 0;
const total = () => Math.max(0, props.subtotal - discount() + shippingFee(props.subtotal));

function submit() {
    form.post('/checkout');
}
</script>

<template>
    <Head title="Checkout" />

    <div class="container-page py-10">
        <h1 class="font-display text-2xl font-bold text-ink-900">Checkout</h1>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_360px]">
            <form class="space-y-6" @submit.prevent="submit">
                <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
                    <h2 class="font-display text-base font-semibold text-ink-900">Shipping address</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ink-700">Full name</label>
                            <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ink-700">Address</label>
                            <input v-model="form.line1" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                            <p v-if="form.errors.line1" class="mt-1 text-xs text-red-600">{{ form.errors.line1 }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-700">City</label>
                            <input v-model="form.city" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-700">State / Province</label>
                            <input v-model="form.state" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-700">Postal code</label>
                            <input v-model="form.postal_code" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-700">Country</label>
                            <input v-model="form.country" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ink-700">Phone</label>
                            <input v-model="form.phone" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-ink-100 bg-white p-6 shadow-card">
                    <h2 class="font-display text-base font-semibold text-ink-900">Payment method</h2>
                    <div class="mt-4 space-y-2">
                        <label v-for="method in [['card', 'Credit / debit card'], ['paypal', 'PayPal'], ['cod', 'Cash on delivery']]" :key="method[0]" class="flex items-center gap-2 rounded-lg border border-ink-200 p-3 text-sm">
                            <input v-model="form.payment_method" type="radio" :value="method[0]" class="text-accent-600 focus:ring-accent-500" />
                            {{ method[1] }}
                        </label>
                    </div>
                </section>

                <Button type="submit" variant="primary" size="lg" class="w-full lg:hidden" :loading="form.processing">
                    Place order — {{ money(total()) }}
                </Button>
            </form>

            <aside class="h-fit space-y-4 rounded-xl border border-ink-100 bg-white p-6 shadow-card">
                <h2 class="font-display text-base font-semibold text-ink-900">Order summary</h2>

                <ul class="space-y-2 border-b border-ink-100 pb-4 text-sm text-ink-600">
                    <li v-for="item in items" :key="item.id" class="flex justify-between">
                        <span>{{ item.name }} × {{ item.quantity }}</span>
                        <span>{{ money(item.price * item.quantity) }}</span>
                    </li>
                </ul>

                <div class="flex gap-2">
                    <input v-model="form.coupon_code" type="text" placeholder="Coupon code" class="w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <Button type="button" variant="ghost" size="sm" :loading="couponState.applying" @click="applyCoupon">Apply</Button>
                </div>
                <p v-if="couponState.error" class="text-xs text-red-600">{{ couponState.error }}</p>

                <dl class="space-y-1.5 border-t border-ink-100 pt-3 text-sm">
                    <div class="flex justify-between text-ink-600"><dt>Subtotal</dt><dd>{{ money(subtotal) }}</dd></div>
                    <div v-if="discount() > 0" class="flex justify-between text-accent-700"><dt>Discount</dt><dd>−{{ money(discount()) }}</dd></div>
                    <div class="flex justify-between text-ink-600"><dt>Shipping</dt><dd>{{ shippingFee(subtotal) === 0 ? 'Free' : money(shippingFee(subtotal)) }}</dd></div>
                    <div class="flex justify-between border-t border-ink-100 pt-1.5 font-semibold text-ink-900"><dt>Total</dt><dd>{{ money(total()) }}</dd></div>
                </dl>

                <Button type="button" variant="primary" size="lg" class="hidden w-full lg:flex" :loading="form.processing" @click="submit">
                    Place order
                </Button>
            </aside>
        </div>
    </div>
</template>
