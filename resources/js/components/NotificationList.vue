<template>
    <div class="page-layout">
    <AppHeader />
    <div class="page-container">
    <div class="notification-container">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button @click="refresh" class="btn-refresh" :disabled="loading">Refresh</button>
        </div>

        <div v-if="loading && notifications.length === 0" class="loading-state">Loading...</div>
        <div v-else-if="error" class="error-message">{{ error }}</div>
        
        <div v-else class="notification-list">
            <div v-if="notifications.length === 0" class="empty-state">
                No notifications found.
            </div>
            <div 
                v-for="notification in notifications" 
                :key="notification.id" 
                class="notification-item"
                :class="{ 'unread': !notification.user_read_flag }"
                @click="handleNotificationClick(notification)"
            >
                <div class="notification-content">
                    <p class="message">{{ notification.message }}</p>
                    <span class="date">{{ formatDate(notification.created_at) }}</span>
                </div>
                <div v-if="!notification.user_read_flag" class="unread-indicator" title="Mark as read"></div>
            </div>
        </div>

        <div v-if="pagination.last_page > 1" class="pagination">
            <button 
                :disabled="pagination.current_page === 1 || loading" 
                @click="changePage(pagination.current_page - 1)"
                class="btn-page"
            >
                Previous
            </button>
            <span class="page-info">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <button 
                :disabled="pagination.current_page === pagination.last_page || loading" 
                @click="changePage(pagination.current_page + 1)"
                class="btn-page"
            >
                Next
            </button>
        </div>
    </div>
    </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useNotifications } from '../utils/useNotifications';
import AppHeader from './AppHeader.vue';
import { useNotificationStore } from '../store/notification';

const notificationStore = useNotificationStore();
const { notifications, loading, error, pagination, fetchNotifications, markAsRead } = useNotifications();

const refresh = async () => {
    await fetchNotifications(1);
    await notificationStore.fetchUnreadCount();
};

const changePage = (page) => {
    fetchNotifications(page);
};

const handleNotificationClick = async (notification) => {
    if (!notification.user_read_flag) {
        await markAsRead(notification.id);
        await notificationStore.fetchUnreadCount();
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};

onMounted(() => {
    fetchNotifications();
    notificationStore.fetchUnreadCount();
});
</script>

<style scoped>
.page-layout {
    min-height: 100vh;
    background-color: #f3f4f6;
    display: flex;
    flex-direction: column;
}
.page-container {
    display: flex;
    justify-content: center;
    padding: 2rem;
}
.notification-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem 20% 1rem 20%;
    width: 80%;
    max-width: 80%;
}
.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    border-bottom: 1px solid #f3f4f6;
    padding-bottom: 0.5rem;
}
.notification-list {
    max-height: 400px;
    overflow-y: auto;
}
.notification-item {
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: background-color 0.2s;
}
.notification-item:hover {
    background-color: #f9fafb;
}
.notification-item.unread {
    background-color: #eff6ff;
}
.message {
    margin: 0 0 0.25rem 0;
    font-size: 0.875rem;
    color: #1f2937;
}
.date {
    font-size: 0.75rem;
    color: #9ca3af;
}
.unread-indicator {
    width: 8px;
    height: 8px;
    background-color: #2563eb;
    border-radius: 50%;
    margin-top: 0.25rem;
    flex-shrink: 0;
}
.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    font-size: 0.875rem;
}
.btn-refresh {
    background: none;
    border: none;
    color: #2563eb;
    cursor: pointer;
    font-size: 0.875rem;
}
.btn-refresh:disabled {
    color: #9ca3af;
}
.btn-page {
    padding: 0.25rem 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    background: white;
    cursor: pointer;
}
.btn-page:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.loading-state, .error-message, .empty-state {
    text-align: center;
    padding: 1rem;
    color: #6b7280;
}
.error-message {
    color: #ef4444;
}
</style>