import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Syncs a reactive filters object with the current URL's query string,
 * debounced, for product listing/search pages (section 3.1).
 */
export function useFilters(routeName, initial = {}, { debounceMs = 350 } = {}) {
    const filters = reactive({ ...initial });
    let timeout = null;

    watch(
        () => ({ ...filters }),
        (value) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                router.get(route(routeName), value, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }, debounceMs);
        },
        { deep: true },
    );

    return filters;
}
