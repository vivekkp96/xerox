<template>
    <div class="super-admin-layout">
        <nav class="navbar">
            <div class="navbar-brand">
                Super Admin Dashboard
            </div>
            <div class="navbar-menu">
                <button @click="handleLogout" class="btn-logout">Logout</button>
            </div>
        </nav>
        
        <div class="layout-body">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li :class="{ active: currentView === 'dashboard' }" @click="currentView = 'dashboard'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        Dashboard
                    </li>
                    <li :class="{ active: currentView === 'active-users' }" @click="currentView = 'active-users'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        Active Users
                    </li>
                    <li :class="{ active: currentView === 'users' }" @click="currentView = 'users'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                        User Details
                    </li>
                </ul>
            </aside>

            <main class="dashboard-content">
                <div class="header-section">
                    <h1 v-if="currentView === 'dashboard'">System Monitor</h1>
                    <h1 v-else-if="currentView === 'active-users'">User Activity</h1>
                    <h1 v-else-if="currentView === 'users'">Registered Users</h1>
                    <p class="subtitle">Overview and statistics</p>
                </div>

                <!-- Dashboard Monitor View -->
                <SystemMonitor v-if="currentView === 'dashboard'" />

                <!-- Active Users Page View -->
                <ActiveUsers v-if="currentView === 'active-users'" />

                <!-- User List View -->
                <UserList v-if="currentView === 'users'" />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import UserList from './UserList.vue';
import ActiveUsers from './ActiveUsers.vue';
import SystemMonitor from './SystemMonitor.vue';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { CONSTANTS } from '../../constants';

const currentView = ref('dashboard');
const router = useRouter();

const handleLogout = () => {
    // Clear the auth cookie
    document.cookie = `${CONSTANTS.ADMIN_TOKEN}=; path=/; max-age=0; SameSite=Lax`;
    router.push({ name: 'admin-login' });
};
</script>

<style scoped>
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
    background-color: #2c3e50;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar-brand {
    font-size: 1.5rem;
    font-weight: bold;
}

.btn-logout {
    padding: 0.5rem 1rem;
    background-color: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

.layout-body {
    display: flex;
    min-height: calc(100vh - 64px);
}

.sidebar {
    width: 260px;
    background-color: white;
    border-right: 1px solid #e9ecef;
    padding: 1.5rem 0;
    flex-shrink: 0;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    padding: 12px 24px;
    color: #495057;
    cursor: pointer;
    display: flex;
    align-items: center;
    font-weight: 500;
    transition: all 0.2s ease;
}

.sidebar-menu li:hover {
    background-color: #f8f9fa;
    color: #2c3e50;
}

.sidebar-menu li.active {
    background-color: #e3f2fd;
    color: #0d6efd;
    border-right: 3px solid #0d6efd;
}

.sidebar-menu li svg {
    margin-right: 12px;
    color: inherit;
}

.btn-logout:hover {
    background-color: #c82333;
}

.dashboard-content {
    flex-grow: 1;
    padding: 2rem 3rem;
    background-color: #f8f9fa;
}

.header-section {
    margin-bottom: 2rem;
}

.header-section h1 {
    font-size: 1.75rem;
    color: #2c3e50;
    margin: 0;
    font-weight: 700;
}

.subtitle {
    color: #6c757d;
    margin-top: 0.5rem;
    font-size: 0.95rem;
}

</style>
