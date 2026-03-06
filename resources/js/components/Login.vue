<template>
    <div class="page-wrapper">
        <Navbar />
        <div class="content-container">
            <div class="login-card">
                <div class="header">
                    <h2 class="title">Welcome Back</h2>
                    <p class="subtitle">Sign in to your account to continue</p>
                </div>

                <form @submit.prevent="login" class="login-form">
                    <div class="form-group">
                        <label class="input-label">Email Address</label>
                        <input type="email" v-model="form.email" required class="form-control"
                            placeholder="name@example.com" />
                    </div>
                    <div class="password-input-wrapper">
                        <input :type="showPassword ? 'text' : 'password'" v-model="form.password" required
                            class="form-control" />
                        <button type="button" @click="showPassword = !showPassword" class="toggle-password-btn">
                            {{ showPassword ? '😨' : '👁️' }}
                        </button>
                    </div>


                    <div class="form-footer">
                        <router-link to="/forgot-password" class="forgot-link">Forgot Password?</router-link>
                    </div>

                    <button type="submit" :disabled="loading" class="btn-submit">
                        <svg v-if="loading" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        {{ loading ? 'Signing in...' : 'Sign In' }}
                    </button>

                    <div class="register-prompt">
                        Don't have an account? <router-link to="/register" class="register-link">Create
                            account</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { CONSTANTS } from '../constants';
import { showInfo, showSuccess, showError } from '../utils/toast';
import { removeQueryParam } from '../utils/urlUtils';
import Navbar from './Navbar.vue';

const form = reactive({
    email: '',
    password: ''
});

const loading = ref(false);
const showPassword = ref(false);
const router = useRouter();
const route = useRoute();

onMounted(() => {
    if (route.query.expired === 'true') {
        showInfo('Your session has expired. Please log in again.');
        removeQueryParam('expired', 5000);
    }
});

const login = async () => {
    loading.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch('/api/v1/login', {
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
            throw new Error(data.message || 'Login failed');
        }

        // Store token in cookie
        document.cookie = `${CONSTANTS.USER_TOKEN}=${data.token}; path=/; max-age=86400; SameSite=Lax`;

        showSuccess('Login successful!');

        // Redirect to home
        setTimeout(() => {
            router.push('/home');
        }, 1000);

    } catch (e: any) {
        showError(e.message || 'Login failed');
    } finally {
        loading.value = false;
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

.login-card {
    width: 100%;
    max-width: 28rem;
    background-color: white;
    border-radius: 1rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    padding: 2.5rem;
}

.header {
    text-align: center;
    margin-bottom: 2rem;
}

.title {
    font-size: 1.875rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.subtitle {
    color: #64748b;
    font-size: 0.875rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.input-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #334155;
    margin-bottom: 0.5rem;
}

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

.form-footer {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 1.5rem;
}

.forgot-link {
    color: #8b5cf6;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
}

.forgot-link:hover {
    text-decoration: underline;
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

.btn-submit:hover {
    background-color: #7c3aed;
}

.btn-submit:disabled {
    opacity: 0.7;
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
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

.register-prompt {
    margin-top: 1.5rem;
    text-align: center;
    font-size: 0.875rem;
    color: #6b7280;
}

.register-link {
    color: #8b5cf6;
    font-weight: 600;
    text-decoration: none;
}

.register-link:hover {
    text-decoration: underline;
}

.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.form-control {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
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
.forgot-password-link a:hover {
    text-decoration: underline;
}


.toggle-password-btn:hover {
    color: #1f2937;
}
</style>