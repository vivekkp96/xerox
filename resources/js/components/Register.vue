<template>
    <div class="page-wrapper">
        <Navbar />
        <div class="content-container">
            <div class="register-card">
                <div class="header">
                    <h2 class="title">Create Account</h2>
                    <p class="subtitle">Join us to manage your printing orders</p>
                </div>

                <form @submit.prevent="register" class="register-form">
                    <div class="form-group">
                        <label class="input-label" for="fullname">Full Name</label>
                        <input id="fullname" type="text" v-model="form.fullname" required class="form-control"
                            placeholder="John Doe" />
                    </div>

                    <div class="form-group">
                        <label class="input-label" for="email">Email Address</label>
                        <input id="email" type="email" v-model="form.email" required class="form-control"
                            placeholder="john@example.com" />
                    </div>

                    <div class="form-group">
                        <label class="input-label" for="phone">Phone Number <span
                                class="optional">(Optional)</span></label>
                        <input id="phone" type="tel" v-model="form.phonenumber" class="form-control"
                            placeholder="9847765110" />
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label class="input-label" for="password">Password</label>
                            <div class="password-input-wrapper">
                                <input id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password"
                                    required class="form-control" placeholder="••••••••" />
                                <button type="button" @click="showPassword = !showPassword" class="toggle-password-btn">
                                    {{ showPassword ? '😨' : '👁️' }}
                                </button>
                            </div>
                        </div>

                        <div class="form-group half">
                            <label class="input-label" for="password_confirmation">Confirm Password</label>
                            <div class="password-input-wrapper">
                                <input id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'"
                                    v-model="form.password_confirmation" required class="form-control"
                                    placeholder="••••••••" />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                    class="toggle-password-btn">
                                    {{ showConfirmPassword ? '😨' : '👁️' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading" class="btn-submit">
                        <svg v-if="loading" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        {{ loading ? 'Creating Account...' : 'Register' }}
                    </button>

                    <div class="login-prompt">
                        Already have an account? <router-link to="/login" class="login-link">Log in</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>


<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { showSuccess, showError } from '../utils/toast';
import { isValidMobileNumber } from '../utils/mobileValidation';
import Navbar from './Navbar.vue';

const form = reactive({
    fullname: '',
    email: '',
    phonenumber: '',
    password: '',
    password_confirmation: ''
});

const loading = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const router = useRouter();

const register = async () => {
    loading.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (form.password !== form.password_confirmation) {
            showError('Passwords do not match');
            loading.value = false;
            return;
        }
        // Mobile number validation (if not empty)
        if (form.phonenumber && !isValidMobileNumber(form.phonenumber)) {
            showError('Mobile number must be exactly 10 digits.');
            loading.value = false;
            return;
        }
        const response = await fetch('/api/v1/pre-users', {
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
            if(data.errors && Object.keys(data.errors).length > 0) {
                const firstError = (Object.values(data.errors)[0] as string[])[0];
                throw new Error(firstError);
            }
            throw new Error(data.message || 'Registration failed');
        }

        showSuccess('Registration successful! Redirecting to verification...');
        const userEmail = form.email;
        form.fullname = '';
        form.email = '';
        form.phonenumber = '';
        form.password = '';
        form.password_confirmation = '';

        // Redirect to verify page after short delay
        setTimeout(() => {
            router.push({ name: 'verify', query: { email: userEmail } });
        }, 1500);

    } catch (e: any) {
        showError(e.message || 'Registration failed');
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

.register-card {
    width: 100%;
    max-width: 32rem;
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

.optional {
    font-weight: 400;
    color: #94a3b8;
    font-size: 0.8rem;
}

.form-row {
    display: flex;
    gap: 1rem;
}

.form-group.half {
    flex: 1;
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

.login-prompt {
    margin-top: 1.5rem;
    text-align: center;
    font-size: 0.875rem;
    color: #64748b;
}

.login-link {
    color: #8b5cf6;
    font-weight: 600;
    text-decoration: none;
}

.login-link:hover {
    text-decoration: underline;
}

@media (max-width: 640px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}

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