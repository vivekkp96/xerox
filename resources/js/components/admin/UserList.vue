<template>
    <div class="user-list-container">
        <div class="controls">
            <input type="text" v-model="searchQuery" @keyup.enter="fetchUsers(1)"
                placeholder="Search by name, email, or phone..." class="search-input" />
            <button @click="fetchUsers(1)" class="btn-search">Search</button>
        </div>

        <div class="table-responsive">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id">
                        <td>{{ user.id }}</td>
                        <td>{{ user.fullname }}</td>
                        <td>{{ user.email }}</td>
                        <td>{{ user.phonenumber }}</td>
                        <td>{{ convertDateToFormattedLocalString(user.created_at) }}</td>
                    </tr>
                    <tr v-if="users.length === 0 && !loading">
                        <td colspan="5" class="text-center">No users found</td>
                    </tr>
                    <tr v-if="loading">
                        <td colspan="5" class="text-center">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" v-if="totalPages > 1">
            <button :disabled="currentPage === 1" @click="fetchUsers(currentPage - 1)" class="page-btn">
                Previous
            </button>
            <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
            <button :disabled="currentPage === totalPages" @click="fetchUsers(currentPage + 1)" class="page-btn">
                Next
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { CONSTANTS } from '../../constants';
import { convertDateToFormattedLocalString } from '../../utils/dateUtility';

interface User {
    id: number;
    fullname: string;
    email: string;
    phonenumber: string;
    created_at: string;
}

const users = ref<User[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const searchQuery = ref('');

const getCookie = (name: string) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        const popped = parts.pop();
        return popped ? popped.split(';').shift() : undefined;
    }
};

const fetchUsers = async (page = 1) => {
    loading.value = true;
    try {
        const token = getCookie(CONSTANTS.ADMIN_TOKEN);
        let url = `/api/v1/admin/users?page=${page}`;
        if (searchQuery.value) {
            url += `&search=${encodeURIComponent(searchQuery.value)}`;
        }

        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            const data = await response.json();
            users.value = data.data;
            currentPage.value = data.current_page;
            totalPages.value = data.last_page;
        }
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchUsers();
});
</script>

<style scoped>
.user-list-container {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.controls {
    margin-bottom: 1.5rem;
    display: flex;
    gap: 10px;
}

.search-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 300px;
    font-size: 14px;
}

.btn-search {
    padding: 8px 16px;
    background-color: #2c3e50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.user-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.user-table th,
.user-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.user-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.text-center {
    text-align: center;
}

.pagination {
    margin-top: 1rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
}

.page-btn {
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>