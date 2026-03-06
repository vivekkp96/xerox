<template>
    <div class="page-container">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <svg class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span>Loading payment details...</span>
      </div>
  
      <!-- Error State -->
      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <div class="mt-4">
          <router-link :to="{ name: 'admin-home' }" class="btn-back">Back to Orders</router-link>
        </div>
      </div>
  
      <!-- Content -->
      <div v-else-if="order" class="payment-card">
        <div class="header">
          <div class="title-group">
            <h2 class="page-title">Payment Details</h2>
            <span class="order-badge">Order #{{ orderId }}</span>
          </div>
          <router-link :to="{ name: 'admin-home' }" class="btn-back">Back to Orders</router-link>
        </div>
  
        <div class="history-section">
          <h3 class="section-title">Payment History</h3>
          <div v-if="order.payments && Object.keys(order.payments).length > 0" class="payments-list">
            <div v-for="(payment, id) in order.payments" :key="id" class="payment-item">
              <div class="payment-header">
                <span class="payment-id">Payment #{{ id }}</span>
                <button @click="deletePayment(id)" class="btn-delete" :disabled="deleting[id]">
                  {{ deleting[id] ? 'Deleting...' : 'Delete' }}
                </button>
              </div>
              <div class="payment-details">
                <div v-if="payment.reference_number" class="detail-row">
                  <span class="label">Reference:</span>
                  <span class="value">{{ payment.reference_number }}</span>
                </div>
                <div v-if="payment.image" class="detail-row screenshot-row">
                  <span class="label">Screenshot:</span>
                  <span v-if="!screenshotUrls[id]" class="text-gray-500 text-sm">Loading image...</span>
                  <a v-else :href="screenshotUrls[id]" target="_blank" rel="noopener noreferrer" class="screenshot-link">
                    <img :src="screenshotUrls[id]" alt="Payment Screenshot" class="screenshot-thumb" />
                    <span class="view-text">View Full Size</span>
                  </a>
                </div>
                 <div v-if="!payment.reference_number && !payment.image" class="detail-row">
                    <span class="label">No details for this payment.</span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="empty-state">
            <p>No payments have been recorded for this order yet.</p>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, watch, computed, reactive, onUnmounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';
  import { CONSTANTS } from '../../constants';
  import { checkApiStatusForAdmin } from '../../utils/apiUtils';
  
  const route = useRoute();
  const router = useRouter();
  const orderId = computed(() => route.params.orderId);
  
  const order = ref(null);
  const loading = ref(false);
  const error = ref(null);
  const deleting = reactive({});
  const screenshotUrls = reactive({});
  
  const getAuthToken = () => {
      return document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
  };
  
  const fetchOrderDetails = async () => {
    if (!orderId.value) {
      error.value = "Order ID not found in URL.";
      return;
    }
    loading.value = true;
    error.value = null;
    try {
      const token = getAuthToken();
      if (!token) {
          router.push({ name: 'admin-login' });
          return;
      }
      const config = {
          headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json'
          }
      };
      const response = await axios.get(`/api/v1/admin/orders/${orderId.value}`, config);
      order.value = response.data;
      loadScreenshots();
    } catch (err) {
      if (err.response) {
          checkApiStatusForAdmin(err.response?.status);
      }
      error.value = 'Failed to load order details.';
      console.error(err);
    } finally {
      loading.value = false;
    }
  };

  const loadScreenshots = async () => {
    if (!order.value || !order.value.payments) return;
    
    const token = getAuthToken();
    const config = {
        headers: { 'Authorization': `Bearer ${token}` },
        responseType: 'blob'
    };

    for (const [id, payment] of Object.entries(order.value.payments)) {
        if (payment.image && !screenshotUrls[id]) {
            try {
                const response = await axios.get(`/api/v1/admin/orders/${orderId.value}/payments/${id}/screenshot`, config);
                screenshotUrls[id] = URL.createObjectURL(response.data);
            } catch (err) {
                console.error(`Failed to load screenshot for payment ${id}`, err);
            }
        }
    }
  };

  onUnmounted(() => {
      Object.values(screenshotUrls).forEach(url => URL.revokeObjectURL(url));
  });
  
  const deletePayment = async (paymentId) => {
      if (!confirm(`Are you sure you want to delete payment #${paymentId}?`)) return;
  
      deleting[paymentId] = true;
      try {
          const token = getAuthToken();
          if (!token) {
              router.push({ name: 'admin-login' });
              return;
          }
          const config = {
              headers: { 'Authorization': `Bearer ${token}` }
          };
          const response = await axios.delete(`/api/v1/admin/orders/${orderId.value}/payments/${paymentId}`, config);
          order.value = response.data.order;
      } catch (err) {
          if (err.response) {
              checkApiStatusForAdmin(err.response?.status);
          }
          alert('Failed to delete payment.');
          console.error(err);
      } finally {
          deleting[paymentId] = false;
      }
  };
  
  watch(orderId, (newOrderId) => {
    if (newOrderId !== undefined && newOrderId !== null) {
      fetchOrderDetails();
    }
  }, { immediate: true });
  
  </script>
  
  <style scoped>
  .page-container {
      min-height: 100vh;
      background-color: #eff6ff;
      padding: 2rem;
      display: flex;
      justify-content: center;
      align-items: flex-start;
  }
  
  .payment-card {
      background-color: white;
      border-radius: 1.5rem;
      padding: 2.5rem;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      border: 1px solid #e5e7eb;
      width: 100%;
      max-width: 800px;
  }
  
  .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2.5rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid #e5e7eb;
      flex-wrap: wrap;
      gap: 1rem;
  }
  
  .title-group {
      display: flex;
      align-items: center;
      gap: 1rem;
  }
  
  .page-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1d4ed8;
      margin: 0;
  }
  
  .order-badge {
      background-color: #dbeafe;
      color: #1e40af;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.875rem;
      font-weight: 600;
  }
  
  .btn-back {
      color: #4b5563;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.875rem;
      padding: 0.5rem 1rem;
      border: 1px solid #d1d5db;
      border-radius: 0.5rem;
      transition: all 0.2s;
      background-color: white;
      display: inline-flex;
      align-items: center;
  }
  
  .btn-back:hover {
      background-color: #f3f4f6;
      color: #1f2937;
  }
  
  .section-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1.5rem;
  }
  
  .payments-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
  }
  
  .payment-item {
      background-color: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 0.75rem;
      padding: 1.25rem;
      transition: box-shadow 0.2s;
  }
  
  .payment-item:hover {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  }
  
  .payment-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.75rem;
  }
  
  .payment-id {
      font-size: 0.75rem;
      text-transform: uppercase;
      color: #6b7280;
      font-weight: 700;
      letter-spacing: 0.05em;
  }
  
  .detail-row {
      margin-bottom: 0.5rem;
      font-size: 0.95rem;
  }
  
  .detail-row:last-child {
      margin-bottom: 0;
  }
  
  .label {
      font-weight: 600;
      color: #4b5563;
      margin-right: 0.5rem;
  }
  
  .value {
      color: #111827;
      font-family: monospace;
      background: #e5e7eb;
      padding: 0.1rem 0.4rem;
      border-radius: 0.25rem;
  }
  
  .screenshot-row {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      margin-top: 0.75rem;
  }
  
  .screenshot-link {
      position: relative;
      display: block;
      width: fit-content;
  }
  
  .screenshot-thumb {
      max-width: 100%;
      height: auto;
      max-height: 150px;
      border-radius: 0.5rem;
      border: 1px solid #d1d5db;
  }
  
  .view-text {
      display: block;
      font-size: 0.75rem;
      color: #2563eb;
      margin-top: 0.25rem;
      text-decoration: underline;
  }
  
  .empty-state {
      text-align: center;
      padding: 2rem;
      color: #6b7280;
      background-color: #f9fafb;
      border-radius: 0.75rem;
      border: 1px dashed #d1d5db;
  }
  
  .loading-state, .error-message {
      text-align: center;
      padding: 4rem 2rem;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
  }
  
  .spinner {
      animation: spin 1s linear infinite;
      height: 2.5rem;
      width: 2.5rem;
      margin-bottom: 1rem;
      color: #1d4ed8;
  }
  
  .error-message {
      color: #dc2626;
      background-color: #fee2e2;
      border: 1px solid #fca5a5;
      border-radius: 0.5rem;
      max-width: 500px;
      margin: 2rem auto;
      padding: 1rem;
  }
  
  .mt-4 {
      margin-top: 1rem;
  }
  
  @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
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
  
  .btn-delete:hover:not(:disabled) {
      background-color: #fecaca;
  }
  
  .btn-delete:disabled {
      opacity: 0.5;
      cursor: not-allowed;
  }
  </style>