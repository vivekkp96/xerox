<template>
    <div class="profile-page-container">
        <AppHeader />
        <main class="main-content">
            <div class="profile-card">
                <h2 class="page-title">Change Password</h2>
                <p class="page-subtitle">Update your account password.</p>
                <form @submit.prevent="changePassword">
                    <div v-if="message" :class="['message', error ? 'error' : 'success']">
                        {{ message }}
                    </div>
                    <div class="form-group">
                        <label class="input-label">Current Password</label>
                        <input type="password" v-model="form.current_password" required class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="input-label">New Password</label>
                        <input type="password" v-model="form.new_password" required class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="input-label">Confirm New Password</label>
                        <input type="password" v-model="form.new_password_confirmation" required class="form-control" />
                    </div>
                    <div class="actions">
                        <button type="submit" :disabled="loading" class="btn-submit">
                            {{ loading ? 'Changing...' : 'Change Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import AppHeader from './AppHeader.vue';
import { CONSTANTS } from '../constants';
import { checkApiStatus } from '../utils/apiUtils';

const form = reactive({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
});

const loading = ref(false);
const message = ref('');
const error = ref(false);

const changePassword = async () => {
    loading.value = true;
    message.value = '';
    error.value = false;

    try {
        const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch(CONSTANTS.API.USER_CHANGE_PASSWORD, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`,
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify(form)
        });

        const data = await response.json();

        if (!response.ok) {
            checkApiStatus(response.status);
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
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

/* --- Copied and adapted from UserProfile.vue for design consistency --- */
.profile-page-container {
    min-height: 100vh;
    background-color: #f8fafc;
    font-family: 'Outfit', sans-serif;
}

.main-content {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 0.5rem;
}

.profile-card {
    width: 60vw;
    max-width: 60rem;
    background-color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border: 1px solid #f1f5f9;
    border-radius: 1rem;
    padding: 2.5rem;
    color: #334155;
}

.page-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.875rem;
    font-weight: 800;
    color: #1e293b;
    text-align: center;
    margin-bottom: 0.5rem;
    letter-spacing: -0.025em;
}

.page-subtitle {
    text-align: center;
    color: #6b7280;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.input-label {
    display: block;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-family: 'Outfit', sans-serif;
}

.form-control {
    display: block;
    width: 100%;
    background-color: white;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 0.75rem 0.35rem;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: box-shadow 0.15s ease-in-out;
    font-family: 'Outfit', sans-serif;
}

.form-control:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.form-control:disabled {
    background-color: #e5e7eb;
    cursor: not-allowed;
    color: #6b7280;
}

.actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-top: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.btn-submit {
    background-color: #8b5cf6;
    color: white;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-submit:hover {
    background-color: #7c3aed;
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.message {
    margin-bottom: 1.5rem;
    padding: 1rem;
    border-radius: 0.5rem;
    text-align: center;
    font-weight: 500;
    font-family: 'Outfit', sans-serif;
}

.error {
    color: #991b1b;
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
}

.success {
    color: #065f46;
    background-color: #d1fae5;
    border: 1px solid #6ee7b7;
}
</style>