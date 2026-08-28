import { defineStore } from 'pinia';

/**
 * Thin wrapper around the Inertia-shared `auth.user` prop so components
 * can read the current user from Pinia without prop-drilling. The page
 * layout is responsible for calling `setUser` from `usePage().props.auth`.
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
    }),
    getters: {
        isLoggedIn: (state) => state.user !== null,
        isAdmin: (state) => state.user?.role === 'admin',
        isVendor: (state) => state.user?.role === 'vendor',
        isCustomer: (state) => state.user?.role === 'customer',
    },
    actions: {
        setUser(user) {
            this.user = user;
        },
    },
});
