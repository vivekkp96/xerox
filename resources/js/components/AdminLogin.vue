<template>
    <div class="login-container">
        <h2>Admin Login</h2>
        <form @submit.prevent="login">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" v-model="form.email" required class="form-control" />
            </div>
            <div class="form-group">
                <label>Password:</label>
                <div class="password-input-wrapper">
                    <input :type="showPassword ? 'text' : 'password'" v-model="form.password" required class="form-control" />
                    <button type="button" @click="showPassword = !showPassword" class="toggle-password-btn">
                        {{ showPassword ? '😨' : '👁️' }}
                    </button>
                </div>
            </div>
            <button type="submit" :disabled="loading" class="btn-submit">
                {{ loading ? 'Logging in...' : 'Login' }}
            </button>
        </form>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { CONSTANTS } from '../constants';
import { showInfo, showSuccess, showError } from '../utils/toast';
import { removeQueryParam } from '../utils/urlUtils';

const router = useRouter();
const route = useRoute();
const form = reactive({
    email: '',
    password: ''
});

const loading = ref(false);
const showPassword = ref(false);

onMounted(() => {
    if (route.query.expired === 'true') {
        showInfo('Your session has expired. Please log in again.');
        removeQueryParam('expired', 5000);
    }
});

const login = async () => {
    loading.value = true;

    try {
        const response = await fetch(CONSTANTS.API.ADMIN_LOGIN, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(form)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Login failed');
        }

        document.cookie = `${CONSTANTS.ADMIN_TOKEN}=${data.token}; path=/; max-age=86400; SameSite=Lax`;
        showSuccess('Login successful!');
        
        setTimeout(() => {
            router.push({ name: 'admin-home' }); // Redirect to home or admin dashboard
        }, 1000);
    } catch (e: any) {
        showError(e.message || 'Login failed');
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.login-container { max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
.form-group { margin-bottom: 15px; }
label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-control { width: 100%; padding: 8px; box-sizing: border-box; }
.btn-submit { width: 100%; padding: 10px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; }
.btn-submit:disabled { background-color: #ccc; }
.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.toggle-password-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0;
    color: #6b7280;
    transition: color 0.2s;
}
.toggle-password-btn:hover {
    color: #1f2937;
}
</style>