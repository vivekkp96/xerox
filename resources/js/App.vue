<template>
    <div class="app-container">
        
        <router-view></router-view>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
// const showNav = computed(() => route.name !== 'home' && route.name !== 'change-password' && route.name !== 'admin-login');
const showNav = computed(() => route.name == 'register' || route.name == 'verify' || route.name == 'login');
</script>

<style>
.nav-buttons { text-align: center; margin-bottom: 20px; }
.nav-btn { margin: 0 10px; padding: 8px 16px; cursor: pointer; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; text-decoration: none; color: black; display: inline-block; }
.nav-btn.active { background: #007bff; color: white; border-color: #0056b3; }
.nav-btn:hover { text-decoration: none; background-color: #e2e6ea; }
</style>