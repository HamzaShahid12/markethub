import axios from 'axios';

/**
 * Thin axios wrapper for the /api endpoints described in section 9.
 * Inertia pages should prefer Inertia visits for page navigation;
 * this is for in-page actions (cart, wishlist, chat) that shouldn't
 * trigger a full Inertia visit.
 */
export function useApi() {
    return axios.create({
        baseURL: '/api',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
}
