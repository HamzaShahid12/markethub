import { defineStore } from 'pinia';
import axios from 'axios';
import { bootEcho } from '@/echo';

export const useNotificationsStore = defineStore('notifications', {
    state: () => ({
        unreadCount: 0,
        listening: false,
    }),
    actions: {
        async fetchUnreadCount() {
            try {
                const { data } = await axios.get('/api/notifications/unread-count');
                this.unreadCount = data.count ?? 0;
            } catch {
                // Silent — the bell badge just stays at its last known count.
            }
        },
        setUnreadCount(count) {
            this.unreadCount = count;
        },
        /**
         * Subscribes to the user's private notification channel so the
         * bell badge increments live instead of only refreshing on
         * navigation. Laravel's broadcast notification channel name is
         * always `App.Models.User.{id}` and is auto-authorized by the
         * framework — no entry needed in routes/channels.php for it.
         */
        listen(userId) {
            if (this.listening || !userId) return;

            const echo = bootEcho();
            echo.private(`App.Models.User.${userId}`).notification(() => {
                this.unreadCount += 1;
            });

            this.listening = true;
        },
    },
});
