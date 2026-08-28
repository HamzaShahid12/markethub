<script setup>
import { TransitionGroup } from 'vue';
import { useUiStore } from '@/Stores/ui';
import { CheckCircle2, XCircle, Info, X } from 'lucide-vue-next';

const ui = useUiStore();
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4 sm:items-end sm:right-4 sm:left-auto">
        <TransitionGroup
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-for="toast in ui.toasts"
                :key="toast.id"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-white p-4 shadow-elevated ring-1 ring-ink-100"
            >
                <CheckCircle2 v-if="toast.type === 'success'" class="h-5 w-5 shrink-0 text-accent-600" />
                <XCircle v-else-if="toast.type === 'error'" class="h-5 w-5 shrink-0 text-red-600" />
                <Info v-else class="h-5 w-5 shrink-0 text-ink-500" />
                <p class="flex-1 text-sm text-ink-800">{{ toast.message }}</p>
                <button
                    type="button"
                    class="text-ink-400 hover:text-ink-700"
                    @click="ui.dismissToast(toast.id)"
                    aria-label="Dismiss notification"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
