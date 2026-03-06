import { defineStore } from 'pinia';
import { fetchUnreadNotificationCount } from '../api/notifications';

export const useNotificationStore = defineStore('notification', {
    state: () => ({
        unreadCount: 0,
    }),
    actions: {
        async fetchUnreadCount() {
            const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith('auth_token='))?.split('=')[1];
            if (!token) {
                this.unreadCount = 0;
                return;
            }
            try {
                this.unreadCount = await fetchUnreadNotificationCount(token);
            } catch {
                this.unreadCount = 0;
            }
        },
        setUnreadCount(count:any) {
            this.unreadCount = count;
        }
    }
});
