<template>
    <div class="admin-home-container">
        <AdminHeader />

        <div class="orders-section">
            <h2 class="section-title">All Orders</h2>

            <div class="filters">
                <input v-model="emailFilter" type="text" placeholder="Search by email" class="form-control">
                <select v-model="statusFilter" class="form-control" @change="fetchOrders(1)">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div v-if="loading" class="loading-state">
                <svg class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span>Loading orders...</span>
            </div>

            <div v-if="error" class="error-message">
                {{ error }}
            </div>

            <div v-if="!loading && orders.length === 0" class="empty-state">
                <p>No orders found.</p>
            </div>

            <div v-if="!loading && orders.length > 0" class="orders-table-wrapper">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Total Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders" :key="order.id">
                            <td>#{{ order.id }}</td>
                            <td>
                                <div v-if="order.user">
                                    <div class="font-bold">{{ order.user.name }}</div>
                                    <div class="text-sm text-gray-500">{{ order.user.email }}</div>
                                </div>
                                <span v-else class="text-gray-400">Unknown User</span>
                            </td>
                            <td>{{ new Date(order.created_at).toLocaleDateString() }}</td>
                            <td>
                                <span :class="['status-badge', `status-${order.status}`]">{{ order.status }}</span>
                            </td>
                            <td>
                                <span v-if="order.payment_status" :class="['status-badge', `status-payment-${order.payment_status.replace(' ', '_')}`]">
                                    {{ order.payment_status.replace('_', ' ') }}
                                </span>
                                <span v-else class="status-badge status-payment-pending">N/A</span>
                            </td>
                            <td>₹{{ parseFloat(order.total_price).toFixed(2) }}</td>
                            <td>
                                <router-link :to="{ name: 'admin-view-order', params: { id: order.id } }"
                                    class="btn-view">
                                    View
                                </router-link>
                                <router-link :to="{ name: 'admin-order-payments', params: { orderId: order.id } }"
                                    class="btn-payment" style="margin-left: 0.5rem;">
                                    Payments
                                </router-link>
                                <router-link :to="{ name: 'admin-edit-order', params: { id: order.id } }"
                                    class="btn-edit" style="margin-left: 0.5rem;">
                                    Edit
                                </router-link>
                                <button @click="deleteOrder(order.id)" class="btn-delete" style="margin-left: 0.5rem;">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pagination.last_page > 1" class="pagination-controls">
                <button @click="fetchOrders(pagination.current_page - 1)"
                    :disabled="!pagination.prev_page_url || pagination.current_page === 1" class="btn-paginate">
                    &laquo; Previous
                </button>
                <span>Page {{ pagination.current_page }} of {{ pagination.last_page || 1 }}</span>
                <button @click="fetchOrders(pagination.current_page + 1)"
                    :disabled="!pagination.next_page_url || pagination.current_page === pagination.last_page"
                    class="btn-paginate">
                    Next &raquo;
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { CONSTANTS } from '../../constants';
import AdminHeader from './AdminHeader.vue';
import axios from 'axios';
import { checkApiStatusForAdmin } from '../../utils/apiUtils';

defineProps({
    email: {
        type: String,
        required: true
    }
});

const router = useRouter();
const route = useRoute();
const orders = ref([]);
const pagination = ref({});
const loading = ref(true);
const error = ref('');
const statusFilter = ref('');
const emailFilter = ref('');

const fetchOrders = async (page = null) => {
    let pageNumber = page;
    if (!pageNumber) {
        pageNumber = route.query.page ? parseInt(route.query.page) : 1;
    }
    router.replace({ query: { ...route.query, page: pageNumber } }).catch(() => { });

    loading.value = true;
    error.value = '';
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) return;

        const config = {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            },
            params: {
                page: pageNumber,
                status: statusFilter.value || undefined,
                email: emailFilter.value || undefined,
                per_page: 10
            }
        };

        const response = await axios.get(CONSTANTS.API.GET_ADMIN_ORDERS, config);
        orders.value = response.data.data;
        pagination.value = response.data;
    } catch (err) {
        if (err.response) {
            checkApiStatusForAdmin(err.response?.status);
        }
        error.value = 'Failed to fetch orders.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const deleteOrder = async (id) => {
    if (!confirm('Are you sure you want to delete this order?')) return;

    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        await axios.delete(`/api/v1/admin/orders/${id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        fetchOrders(pagination.value.current_page || 1);
    } catch (err) {
        if (err.response) {
            checkApiStatusForAdmin(err.response?.status);
        }
        alert('Failed to delete order');
        console.error(err);
    }
};

const logout = () => {
    document.cookie = `${CONSTANTS.ADMIN_TOKEN}=; Max-Age=0; path=/`;
    router.push({ name: 'admin-login' });
};

let debounceTimer;
watch(emailFilter, (newVal) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        if (newVal.length > 3 || newVal.length === 0) {
            fetchOrders(1);
        }
    }, 500);
});

onMounted(() => {
    fetchOrders();
});
</script>

<style scoped>
.admin-home-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    background-color: white;
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.page-title {
    font-size: 1.875rem;
    font-weight: 800;
    color: #1f2937;
    margin: 0;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.orders-section {
    background-color: white;
    border-radius: 1.5rem;
    padding: 2.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1d4ed8;
    margin-bottom: 1.5rem;
}

.filters {
    margin-bottom: 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.filters .form-control {
    max-width: 250px;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
}

.loading-state,
.empty-state {
    text-align: center;
    padding: 3rem 0;
    color: #6b7280;
}

.spinner {
    animation: spin 1s linear infinite;
    height: 2rem;
    width: 2rem;
    margin: 0 auto 1rem;
    color: #1d4ed8;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

.error-message {
    color: #dc2626;
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
}

.orders-table-wrapper {
    overflow-x: auto;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.orders-table th,
.orders-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}

.orders-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6b7280;
    font-weight: 600;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
    display: inline-block;
}

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-Processing {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-Completed {
    background-color: #d1fae5;
    color: #065f46;
}

.status-Cancelled {
    background-color: #fee2e2;
    color: #991b1b;
}

.status-payment-Pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-payment-Paid {
    background-color: #d1fae5;
    color: #065f46;
}

.status-payment-Payment_Verified {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-payment-Refunded {
    background-color: #e5e7eb;
    color: #4b5563;
}

.btn-view {
    background-color: #eff6ff;
    color: #1d4ed8;
    font-weight: 600;
    padding: 0.375rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid #bfdbfe;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 0.875rem;
    display: inline-block;
    vertical-align: middle;
}

.btn-view:hover {
    background-color: #dbeafe;
}

.btn-payment {
    background-color: #f0fdf4;
    color: #15803d;
    font-weight: 600;
    padding: 0.375rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid #bbf7d0;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 0.875rem;
    display: inline-block;
    vertical-align: middle;
}

.btn-payment:hover {
    background-color: #dcfce7;
}

.btn-edit {
    background-color: #f3e8ff;
    color: #7e22ce;
    font-weight: 600;
    padding: 0.375rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid #e9d5ff;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 0.875rem;
    display: inline-block;
    vertical-align: middle;
}

.btn-edit:hover {
    background-color: #e9d5ff;
}

.btn-delete {
    background-color: #fee2e2;
    color: #dc2626;
    padding: 0.375rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid #fca5a5;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-delete:hover {
    background-color: #fecaca;
}

.btn-secondary {
    background-color: #f3f4f6;
    color: #374151;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background-color: #e5e7eb;
}

.btn-logout {
    background-color: #ef4444;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-logout:hover {
    background-color: #dc2626;
}

.pagination-controls {
    display: flex;
    justify-content: space-between;
    margin-top: 1.5rem;
    align-items: center;
    color: #6b7280;
    font-size: 0.875rem;
}

.btn-paginate {
    background-color: white;
    border: 1px solid #d1d5db;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-paginate:hover:not(:disabled) {
    background-color: #f9fafb;
}

.btn-paginate:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.font-bold {
    font-weight: 700;
}

.text-sm {
    font-size: 0.875rem;
}

.text-gray-500 {
    color: #6b7280;
}

.text-gray-400 {
    color: #9ca3af;
}
</style>