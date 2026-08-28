<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Button from '@/Components/Common/Button.vue';
import { Store, User } from 'lucide-vue-next';

defineOptions({ layout: AuthLayout });

const form = useForm({
    role: 'customer',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    shop_name: '',
    shop_description: '',
    shop_address: '',
});

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Create account" />

    <div class="mb-6 grid grid-cols-2 gap-2 rounded-lg bg-ink-50 p-1">
        <button
            type="button"
            class="flex items-center justify-center gap-2 rounded-md py-2 text-sm font-medium transition-colors"
            :class="form.role === 'customer' ? 'bg-white text-ink-900 shadow-card' : 'text-ink-500 hover:text-ink-700'"
            @click="form.role = 'customer'"
        >
            <User class="h-4 w-4" /> I'm shopping
        </button>
        <button
            type="button"
            class="flex items-center justify-center gap-2 rounded-md py-2 text-sm font-medium transition-colors"
            :class="form.role === 'vendor' ? 'bg-white text-ink-900 shadow-card' : 'text-ink-500 hover:text-ink-700'"
            @click="form.role = 'vendor'"
        >
            <Store class="h-4 w-4" /> I'm selling
        </button>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
        <div>
            <label for="name" class="block text-sm font-medium text-ink-700">Full name</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                required
                autofocus
                autocomplete="name"
                class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-ink-700">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="username"
                class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
            />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-ink-700">Phone <span class="text-ink-400">(optional)</span></label>
            <input
                id="phone"
                v-model="form.phone"
                type="text"
                autocomplete="tel"
                class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
            />
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div v-if="form.role === 'vendor'" class="space-y-4 rounded-lg border border-accent-200 bg-accent-50 p-4">
                <p class="text-xs font-medium text-accent-800">
                    Your shop starts in <strong>pending</strong> status. An admin reviews and approves it before you can publish products.
                </p>

                <div>
                    <label for="shop_name" class="block text-sm font-medium text-ink-700">Shop name</label>
                    <input
                        id="shop_name"
                        v-model="form.shop_name"
                        type="text"
                        :required="form.role === 'vendor'"
                        class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                    />
                    <p v-if="form.errors.shop_name" class="mt-1 text-xs text-red-600">{{ form.errors.shop_name }}</p>
                </div>

                <div>
                    <label for="shop_address" class="block text-sm font-medium text-ink-700">Shop address</label>
                    <input
                        id="shop_address"
                        v-model="form.shop_address"
                        type="text"
                        :required="form.role === 'vendor'"
                        class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                    />
                    <p v-if="form.errors.shop_address" class="mt-1 text-xs text-red-600">{{ form.errors.shop_address }}</p>
                </div>

                <div>
                    <label for="shop_description" class="block text-sm font-medium text-ink-700">Short description <span class="text-ink-400">(optional)</span></label>
                    <textarea
                        id="shop_description"
                        v-model="form.shop_description"
                        rows="2"
                        class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
                    />
                </div>
            </div>
        </Transition>

        <div>
            <label for="password" class="block text-sm font-medium text-ink-700">Password</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="new-password"
                class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-ink-700">Confirm password</label>
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
            />
        </div>

        <Button type="submit" variant="primary" size="lg" class="w-full" :loading="form.processing">
            {{ form.role === 'vendor' ? 'Create shop account' : 'Create account' }}
        </Button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        Already have an account?
        <Link href="/login" class="font-medium text-accent-600 hover:text-accent-700">Sign in</Link>
    </p>
</template>
