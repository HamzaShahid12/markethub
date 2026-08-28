import { defineStore } from 'pinia';

/**
 * Small cross-cutting UI state: toasts and the mobile nav drawer.
 * Keep this store thin — page-local UI state should live in the
 * component itself, not here (see section 14: "no giant Pinia store").
 */
export const useUiStore = defineStore('ui', {
    state: () => ({
        toasts: [],
        mobileNavOpen: false,
        nextToastId: 1,
    }),
    actions: {
        toast(message, type = 'success') {
            const id = this.nextToastId++;
            this.toasts.push({ id, message, type });
            setTimeout(() => this.dismissToast(id), 4000);
        },
        dismissToast(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
    },
});
