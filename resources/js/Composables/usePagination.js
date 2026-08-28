import { router } from '@inertiajs/vue3';

/**
 * Helper for Laravel's paginator shape ({ data, links, meta }) as
 * returned by Inertia props. Navigates via Inertia so results stream
 * in without a full page reload.
 */
export function usePagination() {
    function goToPage(url, options = {}) {
        if (!url) return;

        router.visit(url, {
            preserveScroll: true,
            preserveState: true,
            ...options,
        });
    }

    return { goToPage };
}
