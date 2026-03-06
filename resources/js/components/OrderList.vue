<template>
    <div class="order-list-container">
        <h2 class="section-title">Your Orders</h2>

        <div class="filters">
            <select v-model="statusFilter" class="form-control">
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
            <p>You haven't placed any orders yet.</p>
            <router-link to="/order" class="btn-primary">Create Your First Order</router-link>
        </div>

        <div v-if="!loading && orders.length > 0" class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
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
                        <td>{{ new Date(order.created_at).toLocaleDateString() }}</td>
                        <td>
                            <span :class="['status-badge', `status-${order.status}`]">{{ order.status }}</span>
                        </td>
                        <td>
                            <span :class="['status-badge', `payment-status-${order.payment_status}`]">{{ (order.payment_status || '').replace('_', ' ') }}</span>
                        </td>
                        <td>₹{{ parseFloat(order.total_price).toFixed(2) }}</td>
                        <td>
                            <router-link v-if="order.status === CONSTANTS.ORDER_STATUS_PENDING" :to="{ name: 'edit-order', params: { id: order.id } }" class="btn-view" style="margin-right: 0.5rem;">
                                Edit
                            </router-link>
                            <button v-if="order.status === CONSTANTS.ORDER_STATUS_PENDING" @click="deleteOrder(order.id)" class="btn-delete" style="margin-right: 0.5rem;">
                                Delete
                            </button>
                            <router-link :to="{ name: 'view-order', params: { id: order.id } }" class="btn-view" style="margin-right: 0.5rem;">
                                View
                            </router-link>
                            <router-link :to="{ name: 'user-order-payment', params: { orderId: order.id } }" class="btn-view">
                                Payment
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pagination.last_page > 1" class="pagination-controls">
            <button @click="fetchOrders(pagination.current_page - 1)" :disabled="!pagination.prev_page_url"
                class="btn-paginate">
                &laquo; Previous
            </button>
            <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <button @click="fetchOrders(pagination.current_page + 1)" :disabled="!pagination.next_page_url"
                class="btn-paginate">
                Next &raquo;
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { CONSTANTS } from '../constants';
import { checkApiStatus } from '../utils/apiUtils';
import { removeQueryParam } from '../utils/urlUtils';

const route = useRoute();
const orders = ref([]);
const pagination = ref({});
const loading = ref(true);
const error = ref('');
const statusFilter = ref(route.query.status || '');

const fetchOrders = async (page = 1) => {
    loading.value = true;
    error.value = '';
    try {
        const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
        if (!token) return;
        const config = {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            params: {
                page,
                status: statusFilter.value || undefined,
                per_page: 5
            }
        };
        const response = await axios.get(CONSTANTS.API.GET_ORDERS, config);
        
        
        if (response.data && Array.isArray(response.data.data)) {
            orders.value = response.data.data;
            pagination.value = response.data;
        } else {
            orders.value = [];
            error.value = 'Unexpected response from server.';
        }
    } catch (e) {
        if (e.response) {
            checkApiStatus(e.response.status);
        }
        error.value = 'Failed to fetch orders. Please try again later.';
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const deleteOrder = async (id) => {
    if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
        return;
    }

    try {
        const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
        const config = {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        };

        await axios.delete(`/api/v1/orders/${id}`, config);
        fetchOrders(pagination.value.current_page);
    } catch (e) {
        if (e.response) {
            checkApiStatus(e.response.status);
        }
        console.error(e);
        alert(e.response?.data?.message || 'Failed to delete order.');
    }
};


onMounted(() => {
    fetchOrders();
});

watch(() => route.query.status, (newStatus) => {
    statusFilter.value = newStatus || '';
    removeQueryParam('status', 5000)
});

watch(statusFilter, () => {
    fetchOrders(1); // Reset to first page when filter changes
});
</script>

<style scoped>
.order-list-container {
    background-color: white;
    border-radius: 1.5rem;
    padding: 2.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
    max-width: 200px;
}

.form-control {
    display: block;
    width: 100%;
    background-color: white;
    border: 1px solid #bfdbfe;
    color: #1e3a8a;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 1rem;
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

.empty-state p {
    margin-bottom: 1.5rem;
    font-size: 1.125rem;
}

.btn-primary {
    background-color: #2563eb;
    color: white;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
    text-decoration: none;
}

.btn-primary:hover {
    background-color: #1d4ed8;
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
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}

.orders-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    font-weight: 600;
}

.orders-table td {
    color: #374151;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-Pending {
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

.payment-status-Pending {
    background-color: #fef3c7;
    color: #92400e;
}

.payment-status-Paid {
    background-color: #dbeafe;
    color: #1e40af;
}

.payment-status-Payment_Verified {
    background-color: #d1fae5;
    color: #065f46;
}

.payment-status-Refunded {
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
}

.btn-view:hover {
    background-color: #dbeafe;
}

.btn-delete {
    background-color: #fee2e2;
    color: #dc2626;
    font-weight: 600;
    padding: 0.375rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid #fca5a5;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.btn-delete:hover {
    background-color: #fecaca;
}

.pagination-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
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
</style>
