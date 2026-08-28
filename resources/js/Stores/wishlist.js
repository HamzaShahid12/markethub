import { defineStore } from 'pinia';
import axios from 'axios';

export const useWishlistStore = defineStore('wishlist', {
    state: () => ({
        items: [],
        loading: false,
    }),
    getters: {
        productIds: (state) => new Set(state.items.map((i) => i.product_id)),
    },
    actions: {
        setItems(items) {
            this.items = items;
        },
        async fetch() {
            this.loading = true;
            try {
                const { data } = await axios.get('/api/wishlist');
                this.items = data.items ?? [];
            } finally {
                this.loading = false;
            }
        },
        async toggle(productId) {
            const { data } = await axios.post('/api/wishlist/toggle', { product_id: productId });
            this.items = data.items ?? this.items;
            return data.wishlisted;
        },
        async remove(itemId) {
            const { data } = await axios.delete(`/api/wishlist/items/${itemId}`);
            this.items = data.items ?? this.items;
        },
    },
});
