<template>
    <div class="order-list-container">
        <h2 class="section-title">Order History</h2>

        <div class="filters">
            <select v-model="statusFilter" class="form-control">
                <option :value="CONSTANTS.COMPLETED_CANCELLED">All Statuses</option>
                <option :value="CONSTANTS.ORDER_STATUS_COMPLETED">{{ CONSTANTS.ORDER_STATUS_COMPLETED }}</option>
                <option :value="CONSTANTS.ORDER_STATUS_CANCELLED">{{ CONSTANTS.ORDER_STATUS_CANCELLED }}</option>
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
            <p>{{ emptyStateMessage }}</p>
        </div>

        <div v-if="!loading && orders.length > 0" class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Payment Status</th>
                        <th>Total Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders" :key="order.id">
                        <td>{{ formatOrderNumber(order.id) }}</td>
                        <td>{{ new Date(order.created_at).toLocaleString() }}</td>
                        <td>
                            <span :class="['status-badge', `status-${order.status}`]">{{ order.status }}</span>
                        </td>
                        <td>
                            <span :class="['status-badge', `payment-status-${order.payment_status.replace(' ', '_')}`]">{{ (order.payment_status || '').replace('_', ' ') }}</span>
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
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { CONSTANTS } from '../constants';
import { checkApiStatus } from '../utils/apiUtils';
import { removeQueryParam } from '../utils/urlUtils';
import { formatOrderNumber } from '../utils/formatters';

const route = useRoute();
const router = useRouter();
const orders = ref([]);
const pagination = ref({});
const loading = ref(true);
const error = ref('');
const statusFilter = ref(route.query.status || CONSTANTS.COMPLETED_CANCELLED);

const emptyStateMessage = computed(() => {
    if (statusFilter.value === CONSTANTS.ORDER_STATUS_CANCELLED) {
        return 'No Cancelled orders found.';
    } else if (statusFilter.value === CONSTANTS.ORDER_STATUS_COMPLETED) {
        return 'No Completed orders found.';
    }
    return 'No orders found.';
});

const fetchOrders = async (page = null) => {
    let pageNumber = page;
    if (!pageNumber) {
        pageNumber = route.query.page ? parseInt(route.query.page) : 1;
    }
    router.replace({ query: { ...route.query, page: pageNumber } }).catch(() => { });

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
                page: pageNumber,
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
    statusFilter.value = newStatus || CONSTANTS.COMPLETED_CANCELLED;
    removeQueryParam('status', 5000)
});

watch(statusFilter, () => {
    fetchOrders(1); // Reset to first page when filter changes
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

.order-list-container {
    font-family: 'Outfit', sans-serif;
    background-color: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border: 1px solid #f1f5f9;
    color: #334155;
}

.section-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 1.5rem;
    letter-spacing: -0.025em;
}

.filters {
    margin-bottom: 1.5rem;
    max-width: 200px;
}

.form-control {
    font-family: 'Outfit', sans-serif;
    display: block;
    width: 100%;
    background-color: white;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 1rem;
}

.form-control:focus {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    outline: none;
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
    color: #8b5cf6;
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
    font-family: 'Outfit', sans-serif;
    color: #dc2626;
    background-color: #fef2f2;
    border: 1px solid #fecaca;
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
    font-family: 'Outfit', sans-serif;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    font-weight: 600;
}

.orders-table td {
    color: #334155;
    font-family: 'Outfit', sans-serif;
}

.status-badge {
    font-family: 'Outfit', sans-serif;
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
    background-color: #f5f3ff;
    color: #7c3aed;
    font-weight: 600;
    padding: 0.375rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid #ddd6fe;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 0.875rem;
}

.btn-view:hover {
    background-color: #ede9fe;
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
    justify-content: center;
    gap: 1rem;
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
