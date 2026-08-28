<script setup>
import { Check, X } from 'lucide-vue-next';

const props = defineProps({
    status: { type: String, required: true },
    steps: {
        type: Array,
        default: () => ['pending', 'processing', 'shipped', 'delivered'],
    },
});

function stepState(step) {
    if (props.status === 'cancelled' || props.status === 'refunded') return 'skipped';
    const stepIndex = props.steps.indexOf(step);
    const currentIndex = props.steps.indexOf(props.status);
    if (stepIndex < currentIndex) return 'done';
    if (stepIndex === currentIndex) return 'current';
    return 'upcoming';
}
</script>

<template>
    <div>
        <div v-if="status === 'cancelled' || status === 'refunded'" class="flex items-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <X class="h-4 w-4" /> This order was {{ status }}.
        </div>

        <ol v-else class="flex items-center">
            <li v-for="(step, i) in steps" :key="step" class="flex flex-1 items-center last:flex-none">
                <div class="flex flex-col items-center gap-1.5">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold"
                        :class="{
                            'bg-accent-500 text-white': stepState(step) === 'done',
                            'bg-ink-900 text-white ring-4 ring-ink-100': stepState(step) === 'current',
                            'bg-ink-100 text-ink-400': stepState(step) === 'upcoming',
                        }"
                    >
                        <Check v-if="stepState(step) === 'done'" class="h-4 w-4" />
                        <span v-else>{{ i + 1 }}</span>
                    </span>
                    <span class="text-xs font-medium capitalize text-ink-600">{{ step }}</span>
                </div>
                <div v-if="i < steps.length - 1" class="mx-2 h-0.5 flex-1" :class="stepState(step) === 'done' ? 'bg-accent-500' : 'bg-ink-100'" />
            </li>
        </ol>
    </div>
</template>
