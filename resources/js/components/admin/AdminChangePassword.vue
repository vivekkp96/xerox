<template>
    <AdminHeader />
    <div class="change-password-container">
        <h2>Admin Change Password</h2>
        <form @submit.prevent="changePassword">
            <div class="form-group">
                <label>Current Password:</label>
                <input type="password" v-model="form.current_password" required class="form-control" />
            </div>
            <div class="form-group">
                <label>New Password:</label>
                <input type="password" v-model="form.new_password" required class="form-control" />
            </div>
            <div class="form-group">
                <label>Confirm New Password:</label>
                <input type="password" v-model="form.new_password_confirmation" required class="form-control" />
            </div>
            <button type="submit" :disabled="loading" class="btn-submit">
                {{ loading ? 'Changing...' : 'Change Password' }}
            </button>
        </form>
        <p v-if="message" :class="{'error': error, 'success': !error}" class="message">{{ message }}</p>
        <router-link :to="{ name: 'admin-home' }" class="back-link">Back to Dashboard</router-link>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { CONSTANTS } from '../../constants';
import { checkApiStatusForAdmin } from '../../utils/apiUtils';
import AdminHeader from './AdminHeader.vue';

const form = reactive({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
});

const loading = ref(false);
const message = ref('');
const error = ref(false);

const changePassword = async () => {
    if (form.new_password !== form.new_password_confirmation) {
        error.value = true;
        message.value = "New passwords do not match.";
        return;
    }

    loading.value = true;
    message.value = '';
    error.value = false;

    try {
        const tokenItem = document.cookie.split(';').find((item) => item.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`));
        const token = tokenItem ? tokenItem.split('=')[1] : null;

        if (!token) {
            throw new Error('Not authenticated');
        }

        const response = await fetch('/api/v1/admin/change-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(form)
        });

        const data = await response.json();

        if (!response.ok) {
            checkApiStatusForAdmin(response.status);
            throw new Error(data.message || 'Failed to change password');
        }

        message.value = 'Password changed successfully!';
        form.current_password = '';
        form.new_password = '';
        form.new_password_confirmation = '';
    } catch (e: any) {
        error.value = true;
        message.value = e.message;
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.change-password-container { 
    max-width: 400px;
     margin: 50px auto;
      padding: 20px; 
      border: 1px solid #ccc; 
      border-radius: 8px; 

}
.form-group { margin-bottom: 15px; }
label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-control { width: 100%; padding: 8px; box-sizing: border-box; }
.btn-submit { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
.btn-submit:disabled { background-color: #ccc; }
.message { margin-top: 15px; text-align: center; }
.error { color: red; }
.success { color: green; }
.back-link { display: block; margin-top: 15px; text-align: center; text-decoration: none; color: #007bff; }
</style>