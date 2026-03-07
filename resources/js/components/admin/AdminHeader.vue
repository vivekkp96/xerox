<template>
    <div class="admin-header-container">
        <header class="admin-header">
            <div class="admin-header-left">
                <h1 class="page-title">Admin Dashboard</h1>
            </div>
            <nav class="admin-header-nav">
                <router-link :to="{ name: 'admin-home' }" class="header-link">Home</router-link>
                <router-link :to="{ name: 'admin-settings' }" class="header-link">Price Settings</router-link>
                <router-link :to="{ name: 'admin-change-password' }" class="header-link">Change Password</router-link>
                <button @click="logout" class="header-link btn-logout">Logout</button>
            </nav>
        </header>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { CONSTANTS } from '../../constants';
const router = useRouter();
function logout() {
    document.cookie = `${CONSTANTS.ADMIN_TOKEN}=; Max-Age=0; path=/`;
    router.push({ name: 'admin-login' });
}
</script>

<style scoped>
/* Container for consistent max-width and padding */
.admin-header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}
/* Modern admin header styling */
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    background: linear-gradient(90deg, #f8fafc 60%, #e0e7ef 100%);
    border-radius: 1rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}
.admin-header-left {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}
.header-logo {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2563eb;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background: #e0e7ef;
    transition: background 0.2s;
}
.header-logo:hover {
    background: #c7d2fe;
}
.page-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1f2937;
    margin: 0;
}
.admin-header-nav {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.header-link {
    font-size: 1rem;
    font-weight: 500;
    color: #374151;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background: #f3f4f6;
    border: none;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.header-link:hover {
    background: #dbeafe;
    color: #2563eb;
}
.btn-logout {
    background: #fee2e2;
    color: #b91c1c;
    font-weight: 600;
}
.btn-logout:hover {
    background: #fca5a5;
    color: #7f1d1d;
}
</style>
