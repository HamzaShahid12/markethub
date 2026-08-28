<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, required: true },
    title: { type: String, default: '' },
    maxWidth: { type: String, default: 'md' }, // sm | md | lg
});

const emit = defineEmits(['close']);

function onKeydown(e) {
    if (e.key === 'Escape' && props.open) emit('close');
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));

watch(
    () => props.open,
    (isOpen) => {
        document.body.style.overflow = isOpen ? 'hidden' : '';
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/40 p-4" @click.self="emit('close')">
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="open"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="title"
                        class="w-full rounded-2xl bg-white p-6 shadow-elevated"
                        :class="{ 'max-w-sm': maxWidth === 'sm', 'max-w-md': maxWidth === 'md', 'max-w-2xl': maxWidth === 'lg' }"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="font-display text-lg font-semibold text-ink-900">{{ title }}</h2>
                            <button type="button" class="rounded-lg p-1.5 text-ink-400 hover:bg-ink-100 hover:text-ink-700" aria-label="Close" @click="emit('close')">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <slot />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
