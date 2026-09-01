<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2 } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Button from '@/Components/Common/Button.vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
    order: { type: Object, required: true },
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

function claimAccount() {
    form.post(`/orders/${props.order.order_number}/claim-account`);
}

function money(value) {
    return `$${Number(value).toFixed(2)}`;
}
</script>

<template>
    <Head title="Order confirmed" />

    <div class="container-page flex justify-center py-16">
        <div class="w-full max-w-lg rounded-2xl border border-ink-100 bg-white p-8 text-center shadow-card">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-accent-100 text-accent-600">
                <CheckCircle2 class="h-7 w-7" />
            </span>
            <h1 class="mt-4 font-display text-xl font-bold text-ink-900">Order confirmed</h1>
            <p class="mt-1 text-sm text-ink-500">Thanks — your order <strong>{{ order.order_number }}</strong> has been placed.</p>

            <ul class="mt-6 space-y-2 border-y border-ink-100 py-4 text-left text-sm text-ink-600">
                <li v-for="(item, i) in order.items" :key="i" class="flex justify-between">
                    <span>{{ item.product_name }} × {{ item.quantity }}</span>
                    <span>${{ Number(item.subtotal).toFixed(2) }}</span>
                </li>
            </ul>

            <div class="mt-4 flex justify-between font-semibold text-ink-900">
                <span>Total</span>
                <span>{{ money(order.total) }}</span>
            </div>
            <p class="mt-1 text-xs text-ink-400">
                Paying by {{ order.payment_method === 'cod' ? 'cash on delivery' : order.payment_method }}
                — {{ order.payment_status }}
            </p>

            <!-- Guest: optional account creation, never forced -->
            <div v-if="order.is_guest" class="mt-8 rounded-xl border border-accent-200 bg-accent-50 p-5 text-left">
                <p class="text-sm font-semibold text-ink-900">Want to track this order later?</p>
                <p class="mt-1 text-xs text-ink-500">
                    Create a free account with <strong>{{ order.guest_email }}</strong> — completely optional.
                </p>
                <form class="mt-3 space-y-2" @submit.prevent="claimAccount">
                    <input v-model="form.password" type="password" placeholder="Choose a password" required class="w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <input v-model="form.password_confirmation" type="password" placeholder="Confirm password" required class="w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500" />
                    <p v-if="form.errors.password" class="text-xs text-red-600">{{ form.errors.password }}</p>
                    <Button type="submit" variant="secondary" size="sm" class="w-full" :loading="form.processing">Create account</Button>
                </form>
            </div>

            <Link href="/products" class="mt-8 block">
                <Button variant="primary" class="w-full">Continue shopping</Button>
            </Link>
        </div>
    </div>
</template>