<template>
    <header class="header">
        <div class="header-content">
            <router-link to="/home" class="logo-container">
                <span class="logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path fill-rule="evenodd"
                            d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
                <h1 class="welcome-title">YouPrints</h1>
            </router-link>
            <div class="header-actions">
                <router-link  to="/order" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd" />
                        </svg>
                        <span>New Order</span>
                    </router-link>
                    <router-link :to="{ path: CONSTANTS.ROUTE.CURRENT_ORDER, query: {  } }" class="nav-link">Current Orders</router-link>
                    <router-link :to="{ path: CONSTANTS.ROUTE.ORDER_HISTORY, query: {  } }" class="nav-link">Order History</router-link>
                <router-link  to="/notifications" class="btn btn-secondary notification-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span v-if="unreadCount > 0" class="notification-badge">{{ unreadCount }}</span>
                </router-link>
                <router-link to="/home" class="btn btn-secondary" title="Home">
                    <svg xmlns="http://www.w3.org/2000/svg" class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10" />
                    </svg>
                </router-link>
                <button class="btn btn-secondary account-btn" @click="toggleAccountCard" title="Account">
                    <svg xmlns="http://www.w3.org/2000/svg" class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 14c2.21 0 4 1.79 4 4v2H4v-2c0-2.21 1.79-4 4-4h8z" />
                        <circle cx="12" cy="8" r="4" />
                    </svg>
                </button>
                                <AccountCard
                                    ref="accountCardRef"
                                    :visible="showAccountCard"
                                    :fullname="userFullname"
                                    :email="userEmail"
                                />
            </div>
        </div>
    </header>
</template>

<script lang="ts">
import { defineComponent, computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { CONSTANTS } from '../constants';
import { useNotificationStore } from '../store/notification';
import AccountCard from './AccountCard.vue';
import axios from 'axios';
import { useUserStore } from '../store/user';

export default defineComponent({
    name: 'AppHeader',
    components: { AccountCard },
    setup(_, { emit }) {
        const notificationStore = useNotificationStore();
        const userStore = useUserStore();
        const unreadCount = computed(() => notificationStore.unreadCount);
        const showAccountCard = ref(false);
        const accountCardRef = ref<any>(null);
        const userFullname = computed(() => {
            const user = userStore.user;
            if (!user) return '';
            if (user.fullname) return user.fullname;
            if (user.first_name && user.last_name) return user.first_name + ' ' + user.last_name;
            if (user.name) return user.name;
            return user.email || '';
        });
        const userEmail = computed(() => userStore.user?.email || '');

        const toggleAccountCard = async () => {
            showAccountCard.value = !showAccountCard.value;
            if (showAccountCard.value && !userStore.user) {
                // Fetch user only if not already in store
                const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
                if (token) {
                    try {
                        const response = await axios.get('/api/v1/user', {
                            headers: { 'Authorization': `Bearer ${token}` }
                        });
                        userStore.setUser(response.data);
                    } catch {
                        userStore.clearUser();
                    }
                }
            }
        };

        const handleClickOutside = (event: MouseEvent) => {
            if (!showAccountCard.value) return;
            let cardEl = null;
            if (accountCardRef.value) {
                // If it's a Vue component instance, use $el; otherwise, use the element itself
                cardEl = accountCardRef.value.$el ? accountCardRef.value.$el : accountCardRef.value;
            }
            if (cardEl && !cardEl.contains(event.target)) {
                showAccountCard.value = false;
            }
        };

        onMounted(() => {
            document.addEventListener('mousedown', handleClickOutside);
        });
        onBeforeUnmount(() => {
            document.removeEventListener('mousedown', handleClickOutside);
        });

        return { unreadCount, showAccountCard, toggleAccountCard, userFullname, userEmail, accountCardRef, CONSTANTS };
    }
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

.header {
    font-family: 'Outfit', sans-serif;
    background-color: white;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #f1f5f9;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

.header-content {
    max-width: 1280px;
    margin: 0 auto;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.logo-icon {
    width: 2.5rem;
    height: 2.5rem;
    color: #8b5cf6;
}

.welcome-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.875rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.025em;
}

.nav-link {
    text-decoration: none;
    color: #4b5563;
    font-weight: 600;
    font-size: 0.95rem;
    transition: color 0.2s;
}

.nav-link:hover, .nav-link.router-link-exact-active {
    color: #8b5cf6;
}

.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    padding: 0.625rem 1.25rem;
    border-radius: 0.5rem;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 0.875rem;
}

.btn-primary {
    background-color: #8b5cf6;
    color: white;
}

.btn-primary:hover {
    background-color: #7c3aed;
}

.btn-secondary {
    background-color: #f9fafb;
    color: #374151;
    border-color: #d1d5db;
}

.btn-secondary:hover {
    background-color: #f3f4f6;
}

.btn-icon {
    width: 1.25rem;
    height: 1.25rem;
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    .header {
        padding: 1rem 0.5rem;
    }
    .logo-container {
        gap: 0.5rem;
    }
    .welcome-title {
        font-size: 1.25rem;
    }
    .header-actions {
        gap: 0.5rem;
        width: 100%;
        flex-direction: column;
        align-items: stretch;
    }
    .btn {
        width: 100%;
        justify-content: flex-start;
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }
    .nav-link {
        padding: 0.5rem;
        text-align: center;
    }
    .account-btn {
        width: 100%;
    }
    .notification-btn {
        width: 100%;
    }
}
.notification-btn {
    position: relative;
}
.notification-badge {
    position: absolute;
    top: -0.5rem;
    right: 0.5rem;
    background: #ef4444;
    color: #fff;
    border-radius: 9999px;
    padding: 0.15em 0.6em;
    font-size: 0.75rem;
    font-weight: bold;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    z-index: 2;
}
</style>