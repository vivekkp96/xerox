<template>
    <div class="profile-page-container">
        <app-header/>
        <main class="main-content">
            <div class="profile-card">
                <h2 class="page-title">Your Profile</h2>
                <p class="page-subtitle">Update your name and phone number.</p>

                <form @submit.prevent="updateProfile">
                    <div v-if="message" :class="['message', isError ? 'error' : 'success']">
                        {{ message }}
                    </div>

                    <div class="form-group">
                        <label for="fullname" class="input-label">Full name</label>
                        <input type="text" id="fullname" v-model="form.fullname" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="phone_number" class="input-label">Phone Number</label>
                        <input type="text" id="phone_number" v-model="form.phonenumber" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="email" class="input-label">Email</label>
                        <input type="email" id="email" v-model="form.email" class="form-control" disabled>
                        <p class="hint">Email address cannot be changed.</p>
                    </div>

                    <div class="actions">
                        <div class="action-group">
                            <router-link to="/home" class="btn-cancel">Cancel</router-link>
                        </div>
                        <button type="submit" class="btn-submit" :disabled="loading">
                            {{ loading ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>


<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { CONSTANTS } from '../constants';
import { checkApiStatus } from '../utils/apiUtils';
import { isValidMobileNumber } from '../utils/mobileValidation';
import AppHeader from './AppHeader.vue';
import { useUserStore } from '../store/user';

const userStore = useUserStore();
const form = ref({
    fullname: '',
    phonenumber: '',
    email: ''
});

const loading = ref(false);
const message = ref('');
const isError = ref(false);
const router = useRouter();

const getAuthConfig = () => {
    const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
    if (!token) {
        isError.value = true;
        message.value = 'Authentication token not found. Please log in again.';
        throw new Error('Unauthenticated');
    }
    return {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
        }
    };
};

const fillFormFromStore = () => {
    if (userStore.user) {
        form.value.fullname = userStore.user.fullname || '';
        form.value.phonenumber = userStore.user.phonenumber || '';
        form.value.email = userStore.user.email || '';
    }
};

const fetchUserProfile = async () => {
    loading.value = true;
    try {
        const config = getAuthConfig();
        const response = await axios.get('/api/v1/user', config);
        userStore.setUser(response.data);
        fillFormFromStore();
    } catch (e) {
        checkApiStatus(e.response?.status);
        isError.value = true;
        message.value = e.response?.data?.message || 'Failed to load user profile.';
    } finally {
        loading.value = false;
    }
};

const updateProfile = async () => {
    loading.value = true;
    message.value = '';
    isError.value = false;

    try {
        // Mobile number validation (if not empty)
        if (form.value.phonenumber && !isValidMobileNumber(form.value.phonenumber)) {
            isError.value = true;
            message.value = 'Mobile number must be exactly 10 digits.';
            loading.value = false;
            return;
        }
        const config = getAuthConfig();
        const payload = {
            fullname: form.value.fullname,
            phonenumber: form.value.phonenumber
        };
        const response = await axios.patch('/api/v1/user/profile', payload, config);
        message.value = response.data.message || 'Profile updated successfully!';
        // After update, fetch latest user data and update store
        await fetchUserProfile();
    } catch (e) {
        checkApiStatus(e.response?.status);
        isError.value = true;
        if (e.response && e.response.data && e.response.data.errors) {
            message.value = Object.values(e.response.data.errors).flat().join(' ');
        } else {
            message.value = e.response?.data?.message || 'Failed to update profile.';
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fillFormFromStore();
    if (!userStore.user) {
        fetchUserProfile();
    }
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

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
    max-width: 60rem; /* 1280px */
    background-color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border: 1px solid #f1f5f9;
    border-radius: 1rem;
    padding: 2.5rem;
    color: #334155;
}

.page-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.875rem; /* 30px */
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

.hint {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

.actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.action-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-cancel {
    color: #4b5563;
    text-decoration: none;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    transition: background-color 0.2s;
}

.btn-cancel:hover {
    background-color: #f1f5f9;
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