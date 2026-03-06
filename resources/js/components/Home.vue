<template>
    <div class="page-container">
            <app-header />
        <main class="main-content">
            <router-view :user="user" @require-phonenumber="showPhoneNumberPrompt = true" />
            <div v-if="showPhoneNumberPrompt" class="phonenumber-modal">
                <div class="modal-content">
                    <p>Your phone number is missing. Please update your profile to continue.</p>
                    <router-link to="/profile" class="btn-primary">Update Phone Number</router-link>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import { CONSTANTS } from '../constants';
import AppHeader from './AppHeader.vue';
import axios from 'axios';
import { useNotificationStore } from '../store/notification';
import { useUserStore } from '../store/user';

export default defineComponent({
    name: 'Home',
    components: {
        AppHeader,
    },
    setup() {
        const user = ref(null);
        const userStore = useUserStore();
        const showPhoneNumberPrompt = ref(false);
        const notificationStore = useNotificationStore();

        const fetchUser = async () => {
            if (userStore.user) {
                user.value = userStore.user;
                if (!user.value.phonenumber) {
                    showPhoneNumberPrompt.value = true;
                }
                return;
            }
            try {
                const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
                if (!token) return;
                const response = await axios.get('/api/v1/user', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                user.value = response.data;
                userStore.setUser(response.data);
                if(!user.value.phonenumber) {
                    showPhoneNumberPrompt.value = true;
                }
            } catch (e) {
                user.value = null;
                userStore.clearUser();
            }
        };


        onMounted(() => {
            fetchUser();
            notificationStore.fetchUnreadCount();
        });

        // This can be passed to NotificationList for refresh
        const updateUnreadCount = () => fetchUnreadCount();

        return { user, showPhoneNumberPrompt };
    }
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

.phonenumber-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-content {
    background: #fff;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 2px 16px rgba(0,0,0,0.2);
    text-align: center;
}
.page-container {
    min-height: 100vh;
    background-color: #eff6ff;
    font-family: 'Inter', sans-serif;
}

.main-content {
    padding: 2rem;
    max-width: 1280px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .main-content {
        padding: 1rem;
    }
}
</style>