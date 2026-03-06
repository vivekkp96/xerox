<template>
    <div v-if="loading">Loading...</div>
    <SuperAdminDashboard v-else-if="isSuperAdmin" />
    <AdminHome :email="email" v-else />
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { CONSTANTS } from '../constants';
import AdminHome from './admin/AdminHome.vue';
import SuperAdminDashboard from './admin/SuperAdminDashboard.vue';
import { checkApiStatusForAdmin } from '../utils/apiUtils';

const router = useRouter();
const loading = ref(true);
const role = ref<string | null>(null);
const email = ref('');

const isSuperAdmin = computed(() => {
    // Check for role ID 2 or 'super_admin' string    
    return role.value === 'Super Admin';
});

onMounted(async () => {
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) return;

        // Fetch the authenticated admin's details
        const response = await axios.get(CONSTANTS.API.ADMIN_ME, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        role.value = response.data.role;
        email.value = response.data.email;
    } catch (error: any) {
        if (error.response) {
            checkApiStatusForAdmin(error.response?.status);
        }
        console.error(error);
    } finally {
        loading.value = false;
    }
});
</script>