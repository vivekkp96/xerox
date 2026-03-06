<template>
    <div class="page-wrapper">
        <Navbar />
        <div class="content-container">
            <div class="verify-card">
                <div class="header">
                    <h2 class="title">Verify Email</h2>
                    <p class="subtitle">Enter the OTP sent to your email</p>
                </div>
                <form @submit.prevent="verify" class="verify-form">
                    <div class="form-group">
                        <label class="input-label">Email Address</label>
                        <input type="email" v-model="form.email" required class="form-control" placeholder="name@example.com" />
                    </div>
                    <div class="form-group">
                        <label class="input-label">One-Time Password (OTP)</label>
                        <input type="text" v-model="form.otp" required class="form-control" placeholder="Enter 6-digit OTP" />
                    </div>
                    <button type="submit" :disabled="loading" class="btn-submit">
                        <svg v-if="loading" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        {{ loading ? 'Verifying...' : 'Verify Email' }}
                    </button>
                    <button type="button" @click="resendOtp" :disabled="resendLoading" class="btn-resend">
                        {{ resendLoading ? 'Sending...' : 'Resend OTP' }}
                    </button>
                </form>
                <p v-if="message" :class="['message', error ? 'error' : 'success']">{{ message }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { showSuccess, showError } from '../utils/toast';
import Navbar from './Navbar.vue';

const route = useRoute();

const form = reactive({
    email: '',
    otp: ''
});

const loading = ref(false);
const resendLoading = ref(false);
const message = ref('');
const error = ref(false);
const router = useRouter();

onMounted(() => {
    // Pre-fill email from route query parameter if available
    if (route.query.email) {
        form.email = route.query.email as string;
    }
});

const verify = async () => {
    loading.value = true;
    message.value = '';
    error.value = false;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/api/v1/verify-users', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify(form)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || data.message || 'Verification failed');
        }

        message.value = 'Verification successful! Account created.';
          setTimeout(() => {
            router.push({ name: 'login', query: {  } });
        }, 1500);
        // You can redirect here if needed
    } catch (e: any) {
        error.value = true;
        message.value = e.message;
    } finally {
        loading.value = false;
    }
};

const resendOtp = async () => {
    if (!form.email) {
        showError('Please enter your email address');
        return;
    }

    resendLoading.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/api/v1/resend-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({ email: form.email })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || data.message || 'Failed to resend OTP');
        }

        showSuccess('OTP sent successfully! Check your email.');
        message.value = '';
    } catch (e: any) {
        showError(e.message);
    } finally {
        resendLoading.value = false;
    }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

.page-wrapper {
    font-family: 'Outfit', sans-serif;
    min-height: 100vh;
    background-color: #f3f4f6;
    display: flex;
    flex-direction: column;
}

.content-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.verify-card {
    width: 100%;
    max-width: 28rem;
    background-color: white;
    border-radius: 1rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    padding: 2.5rem;
}

.header { text-align: center; margin-bottom: 2rem; }
.title { font-size: 1.875rem; font-weight: 800; color: #1e293b; margin-bottom: 0.5rem; }
.subtitle { color: #64748b; font-size: 0.875rem; }

.form-group { margin-bottom: 1.25rem; }
.input-label { display: block; font-size: 0.875rem; font-weight: 500; color: #334155; margin-bottom: 0.5rem; }

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.btn-submit {
    width: 100%;
    background-color: #8b5cf6;
    color: white;
    font-weight: 600;
    padding: 0.75rem;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    justify-content: center;
    align-items: center;
}

.btn-submit:hover { background-color: #7c3aed; }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }

.btn-resend {
    width: 100%;
    background-color: #f3f4f6;
    color: #334155;
    font-weight: 600;
    padding: 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 0.75rem;
    font-size: 0.875rem;
}

.btn-resend:hover:not(:disabled) {
    background-color: #e5e7eb;
    border-color: #9ca3af;
}

.btn-resend:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.spinner {
    animation: spin 1s linear infinite;
    height: 1.25rem;
    width: 1.25rem;
    margin-right: 0.5rem;
    color: white;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.message { margin-top: 1rem; text-align: center; font-size: 0.875rem; font-weight: 500; }
.error { color: #dc2626; }
.success { color: #16a34a; }
</style>