<script setup>
import { Link } from '@inertiajs/vue3';
import { Store } from 'lucide-vue-next';

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-ink-50 px-4 py-12">
        <div class="w-full max-w-md">
            <Link href="/" class="mb-8 flex items-center justify-center gap-2 font-display text-xl font-bold text-ink-900">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink-900 text-white">
                    <Store class="h-4 w-4" />
                </span>
                MarketHub
            </Link>

            <div class="rounded-2xl bg-white p-8 shadow-card ring-1 ring-ink-100">
                <div v-if="title" class="mb-6 text-center">
                    <h1 class="font-display text-xl font-bold text-ink-900">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-1 text-sm text-ink-500">{{ subtitle }}</p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
