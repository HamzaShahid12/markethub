import { defineStore } from 'pinia';
import axios from 'axios';

/**
 * Cart state mirrors the server-backed cart (section 14: "Cart state
 * should synchronize with the server"). This store never invents
 * prices or totals client-side beyond simple display math — the
 * source of truth is always the /api/cart response.
 */
export const useCartStore = defineStore('cart', {
    state: () => ({
        items: [],
        loading: false,
    }),
    getters: {
        itemCount: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),
        subtotal: (state) =>
            state.items.reduce((sum, item) => sum + item.price * item.quantity, 0),
    },
    actions: {
        setItems(items) {
            this.items = items;
        },
        async fetch() {
            this.loading = true;
            try {
                const { data } = await axios.get('/api/cart');
                this.items = data.items ?? [];
            } finally {
                this.loading = false;
            }
        },
        async addItem(productId, quantity = 1, variantId = null) {
            try {
                const { data } = await axios.post('/api/cart/items', {
                    product_id: productId,
                    product_variant_id: variantId,
                    quantity,
                });
                this.items = data.items ?? this.items;
            } catch (error) {
                if (error.response?.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                throw error;
            }
        },
        async updateItem(itemId, quantity) {
            const { data } = await axios.put(`/api/cart/items/${itemId}`, { quantity });
            this.items = data.items ?? this.items;
        },
        async removeItem(itemId) {
            const { data } = await axios.delete(`/api/cart/items/${itemId}`);
            this.items = data.items ?? this.items;
        },
    },
});