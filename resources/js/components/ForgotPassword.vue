<template>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h2 class="title">Forgot Password</h2>
            <p class="subtitle" v-if="step === 1">Enter your email to receive an OTP</p>
            <p class="subtitle" v-else-if="step === 2">Enter the OTP sent to your email</p>
            <p class="subtitle" v-else>Enter your new password</p>

            <form v-if="step === 1" @submit.prevent="handleSendOtp" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" v-model="form.email" required class="form-control" placeholder="john@example.com" />
                </div>
                <button type="submit" :disabled="loading" class="btn-submit">
                    <span v-if="loading" class="spinner"></span>
                    {{ loading ? 'Sending...' : 'Send OTP' }}
                </button>
            </form>

            <form v-else-if="step === 2" @submit.prevent="handleVerifyOtp" class="auth-form">
                <div class="form-group">
                    <label for="otp">One-Time Password</label>
                    <input id="otp" type="text" v-model="form.otp" required class="form-control" placeholder="Enter 6-digit OTP" />
                </div>
                <button type="submit" :disabled="loading" class="btn-submit">
                    <span v-if="loading" class="spinner"></span>
                    {{ loading ? 'Verifying...' : 'Verify OTP' }}
                </button>
                <div class="text-center mt-2">
                    <button type="button" @click="step = 1" class="btn-link-text">Change Email</button>
                </div>
            </form>

            <form v-else @submit.prevent="handleResetPassword" class="auth-form">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input id="password" type="password" v-model="form.password" required class="form-control" placeholder="••••••••" />
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" type="password" v-model="form.password_confirmation" required class="form-control" placeholder="••••••••" />
                </div>
                <button type="submit" :disabled="loading" class="btn-submit">
                    <span v-if="loading" class="spinner"></span>
                    {{ loading ? 'Resetting...' : 'Reset Password' }}
                </button>
                <div class="text-center mt-2">
                    <button type="button" @click="step = 1" class="btn-link-text">Start Over</button>
                </div>
            </form>

            <div class="login-link">
                Remember your password? <router-link to="/login">Log in</router-link>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { showSuccess, showError } from '../utils/toast';

const router = useRouter();
const step = ref(1);
const loading = ref(false);
const form = reactive({
    email: '',
    otp: '',
    password: '',
    password_confirmation: ''
});

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const handleSendOtp = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/v1/forgot-password/send-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ email: form.email })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Failed to send OTP');
        
        showSuccess(data.message);
        step.value = 2;
    } catch (e: any) {
        showError(e.message || 'An error occurred');
    } finally {
        loading.value = false;
    }
};

const handleVerifyOtp = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/v1/forgot-password/verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ email: form.email, otp: form.otp })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Failed to verify OTP');

        showSuccess(data.message);
        step.value = 3;
    } catch (e: any) {
        showError(e.message || 'An error occurred');
    } finally {
        loading.value = false;
    }
};

const handleResetPassword = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/v1/forgot-password/reset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify(form)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Failed to reset password');

        showSuccess('Password reset successfully! Redirecting to login...');
        setTimeout(() => router.push('/login'), 1500);
        
    } catch (e: any) {
        showError(e.message || 'An error occurred');
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.auth-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; background-color: #f3f4f6; padding: 20px; }
.auth-card { background: white; width: 100%; max-width: 400px; padding: 2.5rem; border-radius: 1rem; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
.title { font-size: 1.5rem; font-weight: 800; color: #111827; text-align: center; margin: 0 0 0.5rem 0; }
.subtitle { text-align: center; color: #6b7280; margin-bottom: 2rem; font-size: 0.95rem; }
.form-group { margin-bottom: 1.25rem; }
label { display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; }
.form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; color: #1f2937; box-sizing: border-box; }
.form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
.btn-submit { width: 100%; padding: 0.875rem; background-color: #2563eb; color: white; border: none; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 0.5rem; }
.btn-submit:hover:not(:disabled) { background-color: #1d4ed8; }
.btn-submit:disabled { background-color: #93c5fd; cursor: not-allowed; }
.login-link { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #6b7280; }
.login-link a { color: #2563eb; text-decoration: none; font-weight: 600; }
.login-link a:hover { text-decoration: underline; }
.btn-link-text { background: none; border: none; color: #6b7280; text-decoration: underline; cursor: pointer; font-size: 0.875rem; }
.btn-link-text:hover { color: #374151; }
.text-center { text-align: center; }
.mt-2 { margin-top: 0.5rem; }
.spinner { width: 1rem; height: 1rem; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 50%; border-top-color: white; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>