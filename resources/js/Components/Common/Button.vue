<script setup>
defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | ghost | danger
    size: { type: String, default: 'md' }, // sm | md | lg
    loading: { type: Boolean, default: false },
    as: { type: String, default: 'button' },
});
</script>

<template>
    <component
        :is="as"
        class="inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-all duration-150 disabled:cursor-not-allowed disabled:opacity-60"
        :class="[
            size === 'sm' && 'px-3 py-1.5 text-sm',
            size === 'md' && 'px-4 py-2.5 text-sm',
            size === 'lg' && 'px-6 py-3 text-base',
            variant === 'primary' && 'bg-ink-900 text-white hover:bg-ink-800 active:scale-[0.98]',
            variant === 'secondary' && 'bg-accent-500 text-white hover:bg-accent-600 active:scale-[0.98]',
            variant === 'ghost' && 'bg-transparent text-ink-700 hover:bg-ink-100',
            variant === 'danger' && 'bg-red-600 text-white hover:bg-red-700 active:scale-[0.98]',
        ]"
        :disabled="loading"
    >
        <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg>
        <slot />
    </component>
</template>
