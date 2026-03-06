<template>
    <nav class="navbar">
        <div class="nav-container">
            <router-link to="/" class="brand">
                <span class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span class="brand-text">{{ CONSTANTS.APP_NAME }}</span>
            </router-link>
            
            <div class="nav-links">
                <router-link to="/home" class="nav-item" active-class="active">Home</router-link>
                <router-link to="/verify" class="nav-item" active-class="active">Verify</router-link>
                
                <div v-if="!isLoggedIn" class="auth-group">
                    <router-link to="/login" class="nav-item" active-class="active">Login</router-link>
                    <router-link to="/register" class="btn-register">Get Started</router-link>
                </div>
                
                <a v-else @click="logout" class="nav-item logout-btn">Logout</a>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { CONSTANTS } from '../constants';

const isLoggedIn = ref(false);
const router = useRouter();

onMounted(() => {
    checkLoginStatus();
});

const checkLoginStatus = () => {
    isLoggedIn.value = document.cookie.split(';').some((item) => item.trim().startsWith(`${CONSTANTS.USER_TOKEN}=`));
};

const logout = () => {
    document.cookie = `${CONSTANTS.USER_TOKEN}=; path=/; max-age=0`;
    isLoggedIn.value = false;
    router.push('/login');
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

.navbar {
    font-family: 'Outfit', sans-serif;
    background-color: white;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 50;
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
    height: 4rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.brand {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.brand-icon {
    width: 24px;
    height: 24px;
    color: #8b5cf6;
}

.brand-text {
    font-size: 1.5rem;
    font-weight: 800;
    color: #8b5cf6;
    letter-spacing: -0.025em;
}

.nav-links { display: flex; align-items: center; gap: 2rem; }
.auth-group { display: flex; align-items: center; gap: 1.5rem; }

.nav-item {
    color: #64748b;
    font-weight: 500;
    text-decoration: none;
    transition: color 0.2s;
    cursor: pointer;
}
.nav-item:hover, .nav-item.active { color: #8b5cf6; }

.btn-register { background-color: #8b5cf6; color: white; padding: 0.5rem 1.25rem; border-radius: 0.5rem; font-weight: 600; text-decoration: none; transition: background-color 0.2s; }
.btn-register:hover { background-color: #7c3aed; }
</style>