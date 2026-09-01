<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { loadStripe } from '@stripe/stripe-js';
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
    guest_email: '',
    line1: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    phone: page.props.auth?.user?.phone ?? '',
    coupon_code: '',
    payment_method: 'cod',
    payment_intent_id: '',
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

const shippingFee = (subtotalValue) => (subtotalValue >= 100 ? 0 : 10);
const discount = () => couponState.value.applied?.discount ?? 0;
const total = () => Math.max(0, props.subtotal - discount() + shippingFee(props.subtotal));

// --- Stripe card element setup ---
const stripePromise = loadStripe(import.meta.env.VITE_STRIPE_KEY);
let stripe = null;
let elements = null;
let cardElement = null;
const cardMounted = ref(false);
const cardError = ref(null);
const stripeProcessing = ref(false);

async function mountCardElement() {
    stripe = await stripePromise;
    elements = stripe.elements();
    cardElement = elements.create('card', {
        style: {
            base: { fontSize: '14px', color: '#0a0e13', '::placeholder': { color: '#9dacba' } },
        },
    });
    cardElement.mount('#card-element');
    cardElement.on('change', (event) => {
        cardError.value = event.error ? event.error.message : null;
    });
    cardMounted.value = true;
}

onMounted(() => {
    if (form.payment_method === 'card') mountCardElement();
});

function onPaymentMethodChange() {
    if (form.payment_method === 'card' && !cardMounted.value) {
        setTimeout(mountCardElement, 50); // wait for #card-element to render
    }
}

async function submit() {
    if (form.payment_method !== 'card') {
        form.post('/checkout');
        return;
    }

    stripeProcessing.value = true;

    try {
        // 1. Create a PaymentIntent for the current cart total.
        const { data: intentData } = await axios.post('/api/payments/create-intent', {
            coupon_code: couponState.value.applied ? form.coupon_code : null,
        });

        // 2. Confirm the card payment with Stripe directly (card details
        //    never touch our server — Stripe.js handles them).
        const result = await stripe.confirmCardPayment(intentData.client_secret, {
            payment_method: {
                card: cardElement,
                billing_details: { name: form.name },
            },
        });

        if (result.error) {
            toast.error(result.error.message);
            stripeProcessing.value = false;
            return;
        }

        // 3. Payment succeeded — now actually place the order.
        form.payment_intent_id = result.paymentIntent.id;
        form.post('/checkout', {
            onFinish: () => { stripeProcessing.value = false; },
        });
    } catch (e) {
        toast.error('Payment could not be processed. Please try again.');
        stripeProcessing.value = false;
    }
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

                    <div v-if="!page.props.auth?.user" class="mt-4 rounded-lg bg-accent-50 p-3 text-xs text-accent-800">
                        Checking out as guest — no account needed.
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ink-700">Full name</label>
                            <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div v-if="!page.props.auth?.user" class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ink-700">Email (for order confirmation)</label>
                            <input v-model="form.guest_email" type="email" required class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                            <p v-if="form.errors.guest_email" class="mt-1 text-xs text-red-600">{{ form.errors.guest_email }}</p>
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
                        <label class="flex items-center gap-2 rounded-lg border border-ink-200 p-3 text-sm">
                            <input v-model="form.payment_method" type="radio" value="card" class="text-accent-600 focus:ring-accent-500" @change="onPaymentMethodChange" />
                            Credit / debit card
                        </label>
                        <label class="flex items-center gap-2 rounded-lg border border-ink-200 p-3 text-sm">
                            <input v-model="form.payment_method" type="radio" value="paypal" class="text-accent-600 focus:ring-accent-500" />
                            PayPal
                        </label>
                        <label class="flex items-center gap-2 rounded-lg border border-ink-200 p-3 text-sm">
                            <input v-model="form.payment_method" type="radio" value="cod" class="text-accent-600 focus:ring-accent-500" />
                            Cash on delivery
                        </label>
                    </div>

                    <!-- Stripe card element -->
                    <div v-if="form.payment_method === 'card'" class="mt-4">
                        <label class="block text-sm font-medium text-ink-700">Card details</label>
                        <div id="card-element" class="mt-1 rounded-lg border border-ink-200 p-3"></div>
                        <p v-if="cardError" class="mt-1 text-xs text-red-600">{{ cardError }}</p>
                        <p class="mt-2 text-xs text-ink-400">
                            Test card: 4242 4242 4242 4242 · any future date · any 3-digit CVC
                        </p>
                    </div>
                </section>

                <Button
                    type="submit"
                    variant="primary"
                    size="lg"
                    class="w-full lg:hidden"
                    :loading="form.processing || stripeProcessing"
                >
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

                <Button
                    type="button"
                    variant="primary"
                    size="lg"
                    class="hidden w-full lg:flex"
                    :loading="form.processing || stripeProcessing"
                    @click="submit"
                >
                    Place order
                </Button>
            </aside>
        </div>
    </div>
</template>