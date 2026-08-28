<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Button from '@/Components/Common/Button.vue';

defineOptions({ layout: AuthLayout });

defineProps({
    status: { type: String, default: null },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Sign in" />

    <p v-if="status" class="mb-4 rounded-lg bg-accent-50 p-3 text-sm text-accent-700">{{ status }}</p>

    <form class="space-y-4" @submit.prevent="submit">
        <div>
            <label for="email" class="block text-sm font-medium text-ink-700">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autofocus
                autocomplete="username"
                class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
            />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-ink-700">Password</label>
                <Link href="/forgot-password" class="text-xs font-medium text-accent-600 hover:text-accent-700">
                    Forgot password?
                </Link>
            </div>
            <input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="current-password"
                class="mt-1 w-full rounded-lg border-ink-200 text-sm focus:border-accent-500 focus:ring-accent-500"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input v-model="form.remember" type="checkbox" class="rounded border-ink-300 text-accent-600 focus:ring-accent-500" />
            Remember me
        </label>

        <Button type="submit" variant="primary" size="lg" class="w-full" :loading="form.processing">
            Sign in
        </Button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        New to MarketHub?
        <Link href="/register" class="font-medium text-accent-600 hover:text-accent-700">Create an account</Link>
    </p>

    <div class="mt-6 rounded-lg bg-ink-50 p-3 text-xs text-ink-500">
        <p class="font-medium text-ink-600">Demo accounts (password: "password")</p>
        <p>admin@markethub.test · vendor@markethub.test · customer@markethub.test</p>
    </div>
</template>
