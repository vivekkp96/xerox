<template>
  <div class="page-layout">
    <AppHeader />
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
        <router-link to="/home" class="btn-back">Back to Home</router-link>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="order" class="payment-card">
      <div class="card-header">
        <div class="title-group">
          <h2 class="page-title">Payment Details</h2>
          <span class="order-badge">Order #{{ orderId }}</span>
        </div>
      </div>

      <div class="content-grid">
        <!-- Left Column: Payment History -->
        <div class="history-section">
          <h3 class="section-title">Payment History</h3>
          <div v-if="order.payments && Object.keys(order.payments).length > 0" class="payments-list">
            <div v-for="(payment, id) in order.payments" :key="id" class="payment-item">
              <div class="payment-header">
                <span class="payment-id">Payment #{{ Number(id) + 1 }}</span>
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
              </div>
            </div>
          </div>
          <div v-else class="empty-state">
            <p>No payments have been recorded for this order yet.</p>
          </div>
        </div>

        <!-- Right Column: Add Payment -->
        <div class="add-payment-section">
          <h3 class="section-title">Make a Payment</h3>
          
          <div class="qr-card">
            <div class="qr-wrapper">
              <img v-if="upiQrImage" :src="upiQrImage" alt="UPI QR Code" class="qr-code" />
              <div v-else class="qr-placeholder">QR Code Unavailable</div>
            </div>
            <div v-if="upiId" class="upi-id-container">
              <span class="upi-label">UPI ID</span>
              <div class="upi-value-wrapper">
                <span class="upi-value">{{ upiId }}</span>
                <button type="button" @click="copyUpiId" class="btn-copy" title="Copy UPI ID">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5" />
                  </svg>
                </button>
              </div>
            </div>
            <p class="qr-help">Scan with any UPI app to pay</p>
          </div>

          <form @submit.prevent="handleAddPayment" class="payment-form">
            <div class="form-group">
              <label class="form-label">Upload Screenshot <span class="max-size-info">(Max {{ 4 - existingImagePaymentsCount }} remaining, 2MB size, screenshots only)</span></label>
              <div v-if="canAddScreenshot">
                <div class="file-input-container">
                  <input type="file" id="payment_screenshot" @change="handleFileChange" accept="image/png, image/jpeg, image/jpg, image/webp" class="file-input">
                </div>
                <div v-if="fileError" class="form-error">{{ fileError }}</div>
              </div>
              <div v-else class="form-error">
                You have reached the maximum of 4 payment screenshots.
              </div>
            </div>

            <div class="separator">
              <span>OR</span>
            </div>

            <div class="form-group">
              <label for="reference_number" class="form-label">Reference Number (UTR)</label>
              <input type="text" id="reference_number" v-model="newPayment.reference_number" class="form-control" placeholder="e.g. 1234567890">
            </div>

            <div v-if="addPaymentError" class="form-error">
              {{ addPaymentError }}
            </div>

            <button type="submit" :disabled="submitting" class="btn-submit">
              <svg v-if="submitting" class="spinner-sm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
              </svg>
              {{ submitting ? 'Submitting...' : 'Submit Payment' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, reactive, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppHeader from './AppHeader.vue';
import axios from 'axios'; // Assuming axios is configured for API calls
import { CONSTANTS } from '../constants';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const route = useRoute();
const router = useRouter();
const orderId = computed(() => route.params.orderId);

const order = ref(null);
const upiQrImage = ref('');
const upiId = ref('');
const loading = ref(false);
const error = ref(null);
const submitting = ref(false);
const addPaymentError = ref(null);
const fileError = ref(null);
const screenshotUrls = reactive({});

const newPayment = ref({
  payment_screenshot: null,
  reference_number: '',
});

const existingImagePaymentsCount = computed(() => {
  if (!order.value || !order.value.payments) {
    return 0;
  }
  return Object.values(order.value.payments).filter(p => p.image).length;
});

const canAddScreenshot = computed(() => existingImagePaymentsCount.value < 4);

const getAuthToken = () => {
    return document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
};

const fetchPaymentDetails = async () => {
  if (!orderId.value) {
    error.value = "Order ID not found in URL.";
    return;
  }
  loading.value = true;
  error.value = null;
  try {
    const token = getAuthToken();
    const config = {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    };
    const response = await axios.get(`/api/v1/orders/${orderId.value}/payments`, config);
    order.value = response.data.order;
    upiQrImage.value = response.data.upi_qr_image;
    upiId.value = response.data.upi_id;
    loadScreenshots();
  } catch (err) {
    error.value = 'Failed to load payment details. You may not have permission to view this order.';
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
              const response = await axios.get(`/api/v1/orders/${orderId.value}/payments/${id}/screenshot`, config);
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

watch(orderId, (newOrderId) => {
  if (newOrderId !== undefined && newOrderId !== null) {
    fetchPaymentDetails();
  }
}, { immediate: true });

const handleFileChange = (event) => {
  fileError.value = null;
  const file = event.target.files[0];
  if (!file) {
    newPayment.value.payment_screenshot = null;
    return;
  }
  // Only allow PNG, JPG, JPEG, WEBP
  const allowedTypes = ["image/png", "image/jpeg", "image/jpg", "image/webp"];
  if (!allowedTypes.includes(file.type)) {
    fileError.value = "Only screenshot images (PNG, JPG, JPEG, WEBP) are allowed.";
    newPayment.value.payment_screenshot = null;
    event.target.value = '';
    return;
  }
  // Max size 2MB
  if (file.size > 2 * 1024 * 1024) {
    fileError.value = "Image size must be less than 2MB.";
    newPayment.value.payment_screenshot = null;
    event.target.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    newPayment.value.payment_screenshot = e.target.result;
  };
  reader.readAsDataURL(file);
};

const copyUpiId = () => {
  if (upiId.value) {
    navigator.clipboard.writeText(upiId.value)
      .then(() => toast.success('UPI ID copied!'))
      .catch(() => toast.error('Failed to copy'));
  }
};

const handleAddPayment = async () => {
  if (newPayment.value.payment_screenshot && !canAddScreenshot.value) {
    addPaymentError.value = 'You have reached the maximum of 4 payment screenshots.';
    return;
  }

  if (!newPayment.value.payment_screenshot && !newPayment.value.reference_number) {
    addPaymentError.value = 'Please provide either a screenshot or a reference number.';
    return;
  }

  if (orderId.value === undefined || orderId.value === null) {
    addPaymentError.value = 'Cannot add payment without an Order ID.';
    return;
  }

  submitting.value = true;
  addPaymentError.value = null;

  try {
    const token = getAuthToken();
    const config = {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    };
    await axios.post(`/api/v1/orders/${orderId.value}/payments`, {
      payment_screenshot: newPayment.value.payment_screenshot,
      reference_number: newPayment.value.reference_number,
    }, config);
    
    newPayment.value.payment_screenshot = null;
    newPayment.value.reference_number = '';
    const fileInput = document.getElementById('payment_screenshot');
    if (fileInput) fileInput.value = '';
    
    toast.success('Payment submitted successfully. Redirecting...');
    setTimeout(() => {
        router.push(CONSTANTS.ROUTE.CURRENT_ORDER);
    }, 3000);
  } catch (err) {
    addPaymentError.value = err.response?.data?.message || 'Failed to add payment.';
    console.error(err);
    submitting.value = false;
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

.page-layout {
    min-height: 100vh;
    background-color: #f8fafc;
    display: flex;
    flex-direction: column;
    font-family: 'Outfit', sans-serif;
    color: #334155;
}

.page-container {
    flex: 1;
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
    border: 1px solid #f1f5f9;
    width: 100%;
    max-width: 1000px;
}

.card-header {
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
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: -0.025em;
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

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
}

@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

.section-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.25rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 1.5rem;
}

/* History Section */
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
    color: #64748b;
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

/* Add Payment Section */
.qr-card {
    background-color: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 2rem;
}

.qr-wrapper {
    background: white;
    padding: 0.75rem;
    border-radius: 0.75rem;
    display: inline-block;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.qr-code {
    max-width: 180px;
    display: block;
}

.upi-id-container {
    margin-top: 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.upi-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.upi-value {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    color: #000000;
    font-size: 1.1rem;
    user-select: all;
}

.upi-value-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background-color: white;
    padding: 0.35rem 0.5rem 0.35rem 1rem;
    border-radius: 0.5rem;
    border: 1px dashed #bae6fd;
}

.btn-copy {
    background: none;
    border: none;
    cursor: pointer;
    color: #64748b;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-copy:hover {
    background-color: #f0f9ff;
    color: #0284c7;
}

.btn-copy svg {
    width: 1.25rem;
    height: 1.25rem;
}

.qr-help {
    margin-top: 1rem;
    color: #0369a1;
    font-weight: 500;
    font-size: 0.9rem;
}

.payment-form {
    background-color: #f9fafb;
    padding: 1.5rem;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    font-family: 'Outfit', sans-serif;
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.form-control {
    font-family: 'Outfit', sans-serif;
    width: 100%;
    padding: 0.625rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.file-input {
    font-family: 'Outfit', sans-serif;
    width: 100%;
    font-size: 0.875rem;
    color: #4b5563;
}

.file-input::file-selector-button {
    margin-right: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    background-color: white;
    cursor: pointer;
    font-weight: 500;
    color: #374151;
    transition: all 0.2s;
}

.file-input::file-selector-button:hover {
    background-color: #f3f4f6;
}

.separator {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 1.25rem 0;
    color: #9ca3af;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.separator::before,
.separator::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e5e7eb;
}

.separator span {
    padding: 0 1rem;
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
    gap: 0.5rem;
    margin-top: 1.5rem;
}

.btn-submit:hover:not(:disabled) {
    background-color: #7c3aed;
}

.btn-submit:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
}

.spinner-sm {
    animation: spin 1s linear infinite;
    height: 1.25rem;
    width: 1.25rem;
}

.form-error {
    color: #dc2626;
    font-size: 0.875rem;
    margin-top: 1rem;
    background-color: #fee2e2;
    padding: 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid #fca5a5;
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
}

.mt-4 {
    margin-top: 1rem;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>