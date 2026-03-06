<template>
  <div class="edit-order-container">
    <div class="header-row">
      <div class="header-left">
        <button @click="goBack" class="btn-back-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back
        </button>
        <h1 class="page-title">Edit Order #{{ orderId }}</h1>
      </div>
    </div>

    <div v-if="loading" class="loading-state">Loading order details...</div>
    <div v-else-if="error" class="error-message">{{ error }}</div>

    <div v-else>

      <div class="status-grid">
        <!-- Payment Status Row -->
        <div class="status-card">
          <h2 class="section-title">Payment Status</h2>
          <div class="form-group mb-0">
            <label class="form-label">Update Payment Status</label>
            <div class="payment-actions">
              <select v-model="newPaymentStatus" class="form-control">
                <option :value="CONSTANTS.ORDER_PAYMENT_STATUS_PENDING">{{ CONSTANTS.ORDER_PAYMENT_STATUS_PENDING }}</option>
                <option :value="CONSTANTS.ORDER_PAYMENT_STATUS_PAID">{{ CONSTANTS.ORDER_PAYMENT_STATUS_PAID }}</option>
                <option :value="CONSTANTS.ORDER_PAYMENT_STATUS_REFUNDED">{{ CONSTANTS.ORDER_PAYMENT_STATUS_REFUNDED }}</option>
                <option :value="CONSTANTS.ORDER_PAYMENT_STATUS_PAYMENT_VERIFIED">{{ CONSTANTS.ORDER_PAYMENT_STATUS_PAYMENT_VERIFIED }}</option>
              </select>
              <button type="button" @click="updatePaymentStatus" :disabled="updatingPaymentStatus || newPaymentStatus === initialPaymentStatus" class="btn-submit btn-payment">
                {{ updatingPaymentStatus ? 'Updating...' : 'Update Status' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Order Status Row -->
        <div class="status-card" v-if="initialStatus !== CONSTANTS.ORDER_STATUS_COMPLETED && initialStatus !== CONSTANTS.ORDER_STATUS_CANCELLED">
          <h2 class="section-title">Order Status</h2>
          <div class="form-group mb-0">
            <label class="form-label">Order Status</label>
            <div class="payment-actions">
              <select v-model="form.status" class="form-control">
                <option :value="CONSTANTS.ORDER_STATUS_PENDING">{{ CONSTANTS.ORDER_STATUS_PENDING }}</option>
                <option :value="CONSTANTS.ORDER_STATUS_PROCESSING">{{ CONSTANTS.ORDER_STATUS_PROCESSING }}</option>
                <option :value="CONSTANTS.ORDER_STATUS_COMPLETED">{{ CONSTANTS.ORDER_STATUS_COMPLETED }}</option>
                <option :value="CONSTANTS.ORDER_STATUS_CANCELLED">{{ CONSTANTS.ORDER_STATUS_CANCELLED }}</option>
              </select>
              <button type="button" @click="updateOrderStatus" :disabled="updatingStatus || form.status === initialStatus" class="btn-submit" style="white-space: nowrap;">
                {{ updatingStatus ? 'Updating...' : 'Update Status' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Additional Info Row -->
      <div class="status-card mb-8" v-if="initialStatus !== CONSTANTS.ORDER_STATUS_COMPLETED && initialStatus !== CONSTANTS.ORDER_STATUS_CANCELLED">
        <h2 class="section-title">Order Details</h2>
        <div class="form-group">
          <label class="form-label">Additional Charge</label>
          <input type="number" step="0.01" v-model.number="form.additional_charge" @input="recalculateTotalPrice" class="form-control" placeholder="0.00">
        </div>
        <div class="form-group">
          <label class="form-label">Remark</label>
          <textarea v-model="form.remark" class="form-control" rows="2" placeholder="Add any notes or remarks here..."></textarea>
        </div>
        <div style="text-align: right;">
          <button type="button" @click="updateAdditionalInfo" :disabled="updatingAdditionalInfo" class="btn-submit">
            {{ updatingAdditionalInfo ? 'Updating...' : 'Update Details' }}
          </button>
        </div>
      </div>

      <div v-if="initialStatus !== CONSTANTS.ORDER_STATUS_COMPLETED && initialStatus !== CONSTANTS.ORDER_STATUS_CANCELLED">
      <form @submit.prevent="updateOrder">
      <!-- Total Price -->
      <div class="form-group">
        <label class="form-label">Total Price</label>
        <input type="number" step="0.01" v-model.number="form.total_price" class="form-control" readonly>
      </div>

      <!-- User Comment -->
      <div class="form-group">
        <label class="form-label">User Comment</label>
        <textarea v-model="form.comment" class="form-control" rows="3" disabled></textarea>
      </div>
      <!-- Documents -->
      <div class="form-group">
        <h2 class="section-title">Documents</h2>

        <div v-for="filename in sortedDocumentKeys" :key="filename">
          <div class="document-card">
            <div class="serial-header">
              <span v-if="form.documents[filename].serial_number" class="serial-badge">Document #{{ form.documents[filename].serial_number }}</span>
            </div>
            <div class="document-header">
              <h3 class="document-title" :title="filename">{{ filename }}</h3>
              <div class="header-total-pages">
                <label class="header-label">Pages:</label>
                <input type="number" v-model.number="form.documents[filename].total_pages" @input="recalculateDocPrices(form.documents[filename])" class="header-input">
              </div>
              <button type="button" @click="markDocumentForDeletion(filename)" class="btn-remove">
                Remove Document
              </button>
            </div>

            <!-- Copies -->
            <div class="copies-section">
              <h4 class="subsection-title">Copies Configuration</h4>
              <div v-for="(copy, index) in form.documents[filename].copies" :key="index">
                <div v-if="copy !== null" class="copy-card">
                  <div class="copy-header">
                    <span class="copy-index">Copy #{{ index }}</span>
                    <button type="button" @click="markCopyForDeletion(filename, index)" class="btn-remove-sm">Remove
                      Copy</button>
                  </div>
                  <div class="copy-grid">
                    <div class="col-paper">
                      <label class="option-label">Size</label>
                      <select v-model="copy.paper_size_id" @change="recalculateCopyPrice(form.documents[filename], copy)" class="form-control">
                        <option v-for="size in paperSizes" :key="size.id" :value="size.id">
                          {{ size.value }}
                        </option>
                      </select>
                    </div>
                    <div class="col-mode">
                      <label class="option-label">Mode</label>
                      <select v-model="copy.mode_id" @change="recalculateCopyPrice(form.documents[filename], copy)" class="form-control">
                        <option v-for="mode in printModes" :key="mode.id" :value="mode.id">
                          {{ mode.name }}
                        </option>
                      </select>
                    </div>
                    <div class="col-orientation">
                      <label class="option-label">Orientation</label>
                      <div class="custom-dropdown" @click="copy.showOrientationDropdown = !copy.showOrientationDropdown">
                        <div class="selected-option">
                          <span v-if="getOrientationSvg(copy.orientation_id)" v-html="getOrientationSvg(copy.orientation_id)" class="icon-wrapper"></span>
                          <span>{{ getOrientationName(copy.orientation_id) }}</span>
                          <span class="dropdown-arrow">▼</span>
                        </div>
                        <ul v-if="copy.showOrientationDropdown" class="dropdown-list">
                          <li v-for="orientation in printOrientations" :key="orientation.id" @click.stop="selectOrientation(copy, orientation.id)" class="dropdown-item">
                            <span v-if="orientation.svg" v-html="orientation.svg" class="icon-wrapper"></span>
                            <span>{{ orientation.name }}</span>
                          </li>
                        </ul>
                      </div>
                    </div>
                    <div class="col-pages">
                      <label class="option-label">Pages</label>
                      <div class="pages-selection-row" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <label class="radio-label" style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer;">
                          <input type="radio" :name="'pages-' + filename + '-' + index" :checked="copy.pages === 'All'"
                            @change="setCopyPagesAll(form.documents[filename], copy)">
                          <span style="font-size: 0.75rem; white-space: nowrap;">All Pages</span>
                        </label>
                        <label class="radio-label" style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer;">
                          <input type="radio" :name="'pages-' + filename + '-' + index" :checked="copy.pages !== 'All'"
                            @change="setCopyPagesCustom(copy)">
                          <span style="font-size: 0.75rem; white-space: nowrap;">Custom</span>
                        </label>
                        <div style="flex: 1; min-width: 80px;">
                          <input type="text" :value="copy.pages === 'All' ? '' : copy.pages"
                            @input="copy.pages = $event.target.value" @change="recalculateCopyPrice(form.documents[filename], copy)"
                            class="form-control" placeholder="e.g. 1-5" :disabled="copy.pages === 'All'">
                        </div>
                      </div>
                      <span class="page-count-hint">Format: 1,2,3 or 1-2</span>
                    </div>
                    <div class="col-total-print-pages">
                      <label class="option-label">Total Pages to Print</label>
                      <div class="static-value">{{ calculateTotalPrintPages(copy.pages, form.documents[filename].total_pages, copy.number_of_copies) }}</div>
                    </div>
                    <div class="col-comment">
                      <label class="option-label">Comment</label>
                      <input type="text" v-model="copy.comment" @change="recalculateCopyPrice(form.documents[filename], copy)"
                        class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-copies">
                      <label class="option-label">Copies</label>
                      <input type="number" v-model.number="copy.number_of_copies" @change="recalculateCopyPrice(form.documents[filename], copy)"
                        class="form-control" min="1">
                    </div>
                    <div class="col-price">
                      <label class="option-label">Price</label>
                      <input type="number" step="0.01" v-model.number="copy.totalPrice" class="form-control">
                    </div>
                  </div>
                </div>
              </div>
              <button type="button" @click="addCopy(form.documents[filename])" class="btn-add-copy">
                + Add Copy
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" @click="$router.back()" class="btn-cancel">Cancel</button>
        <button type="submit" :disabled="saving" class="btn-submit">
          {{ saving ? 'Saving...' : 'Update Order' }}
        </button>
      </div>
    </form>
    </div>
    </div>
  </div>
</template>

<script setup>
function getOrientationSvg(id) {
  const orientation = printOrientations.value.find(o => o.id === id);
  return orientation ? orientation.svg : '';
}

function getOrientationName(id) {
  const orientation = printOrientations.value.find(o => o.id === id);
  return orientation ? orientation.name : 'Unknown';
}

function selectOrientation(copy, id) {
  copy.orientation_id = id;
  copy.showOrientationDropdown = false;
  recalculateCopyPrice && recalculateCopyPrice();
}
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { getPrintOrientations } from '../../utils/printOrientationUtils';
import { CONSTANTS } from '../../constants';
import { calculateNumberOfPages, getConfigurationPrice } from '../../utils/orderUtils';
import { checkApiStatusForAdmin } from '../../utils/apiUtils';
import { isValidPageFormat } from '../../utils/validation';
import { calculateTotalPrintPages } from '../../utils/printCalculationUtils';

const route = useRoute();
const router = useRouter();
const orderId = route.params.id;

const loading = ref(true);
const saving = ref(false);
const updatingPaymentStatus = ref(false);
const updatingStatus = ref(false);
const updatingAdditionalInfo = ref(false);
const initialStatus = ref('');
const initialPaymentStatus = ref(CONSTANTS.ORDER_PAYMENT_STATUS_PENDING);
const error = ref(null);
const printModes = ref([]);
const paperSizes = ref([]);
const printPrices = ref([]);
const printOrientations = ref([]);
const form = ref({
  status: '',
  total_price: 0,
  documents: {},
  additional_charge: 0,
  remark: '',
  comment: ''
});
const newPaymentStatus = ref(CONSTANTS.ORDER_PAYMENT_STATUS_PENDING);

const goBack = () => {
    router.back();
};

const sortedDocumentKeys = computed(() => {
  if (!form.value || !form.value.documents) return [];
  return Object.entries(form.value.documents)
    .filter(([, doc]) => doc !== null)
    .sort(([, docA], [, docB]) => (docA.serial_number || 0) - (docB.serial_number || 0))
    .map(([filename]) => filename);
});

const fetchOptions = async () => {
  try {
    const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
    const config = {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
    };
    const [modesRes, sizesRes, pricesRes] = await Promise.all([
      axios.get('/api/v1/print-modes', config),
      axios.get('/api/v1/paper-sizes', config),
      axios.get('/api/v1/print-prices', config)
    ]);
    printModes.value = modesRes.data;
    paperSizes.value = sizesRes.data;
    printPrices.value = pricesRes.data;
    // Use local utility for print orientations
    printOrientations.value = await getPrintOrientations();
  } catch (e) {
    if (e.response) {
      checkApiStatusForAdmin(e.response?.status);
    }
    console.error('Failed to fetch options', e);
  }
};

const fetchOrder = async () => {
  loading.value = true;
  error.value = null;
  try {
    const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
    if (!token) return;
    const config = {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
    };
    const { data } = await axios.get(`${CONSTANTS.API.GET_ADMIN_ORDERS}/${orderId}`, config);

    form.value.status = data.status;
    initialStatus.value = data.status;
    initialPaymentStatus.value = data.payment_status || CONSTANTS.ORDER_PAYMENT_STATUS_PENDING;
    form.value.total_price = data.total_price;
    form.value.additional_charge = data.additional_charge || 0;
    form.value.remark = data.remark || '';
    form.value.comment = data.comment || '';
    // Deep clone documents to ensure we have a mutable object structure
    form.value.documents = JSON.parse(JSON.stringify(data.documents || {}));
    newPaymentStatus.value = data.payment_status || CONSTANTS.ORDER_PAYMENT_STATUS_PENDING;
  } catch (err) {
    if (err.response) {
      checkApiStatusForAdmin(err.response?.status);
    }
    error.value = err.response?.data?.message || 'Failed to load order details.';
  } finally {
    loading.value = false;
  }
};

const recalculateTotalPrice = () => {
  let total = 0;
  for (const filename in form.value.documents) {
    const doc = form.value.documents[filename];
    if (!doc) continue;
    for (const key in doc.copies) {
      const copy = doc.copies[key];
      if (copy) {
        total += Number(copy.totalPrice || 0);
      }
    }
  }

  // Add additional charge
  total += Number(form.value.additional_charge || 0);

  form.value.total_price = parseFloat(total.toFixed(2));
};

const recalculateCopyPrice = (doc, copy) => {
  if (!copy || !doc) return;

  if (!isValidPageFormat(copy.pages)) {
    alert('Invalid page format. Allowed formats: 1,2,3 or 1-2 or All');
    return;
  }

  const copyConfig = { ...copy };
  
  const mode = printModes.value.find(m => m.id === copy.mode_id);
  const size = paperSizes.value.find(s => s.id === copy.paper_size_id);
  copyConfig.mode = mode?.value;
  copyConfig.size = size?.value || 'A4';

  const price = getConfigurationPrice(
    copyConfig,
    doc.total_pages || 0,
    printPrices.value,
    printModes.value,
    paperSizes.value
  ) * (copy.number_of_copies || 1);
  copy.totalPrice = parseFloat(price.toFixed(2));
  recalculateTotalPrice();
};

const recalculateDocPrices = (doc) => {
  if (!doc || !doc.copies) return;
  for (const key in doc.copies) {
    recalculateCopyPrice(doc, doc.copies[key]);
  }
};

const markDocumentForDeletion = (filename) => {
  if (confirm(`Are you sure you want to remove the document "${filename}"?`)) {
    form.value.documents[filename] = null;
    recalculateTotalPrice();
  }
};

const markCopyForDeletion = (filename, index) => {
  if (form.value.documents[filename] && form.value.documents[filename].copies) {
    form.value.documents[filename].copies[index] = null;
    recalculateTotalPrice();
  }
};

const addCopy = (doc) => {
  if (!doc.copies) {
    doc.copies = [];
  }

  const newCopy = {
    mode_id: printModes.value.length > 0 ? printModes.value[0].id : null,
    pages: `1-${doc.total_pages || 1}`,
    orientation_id: printOrientations.value.length > 0 ? printOrientations.value[0].id : null,
    paper_size_id: paperSizes.value.find(s => s.value === 'A4')?.id || (paperSizes.value.length > 0 ? paperSizes.value[0].id : null),
    number_of_copies: 1,
    comment: '',
    totalPrice: 0
  };

  if (Array.isArray(doc.copies)) {
    doc.copies.push(newCopy);
  } else {
    const keys = Object.keys(doc.copies).map(Number).filter(n => !isNaN(n));
    const nextKey = keys.length > 0 ? Math.max(...keys) + 1 : 0;
    doc.copies[nextKey] = newCopy;
  }
  recalculateCopyPrice(doc, newCopy);
};

const updateOrder = async () => {
  for (const filename in form.value.documents) {
    const doc = form.value.documents[filename];
    if (doc && doc.copies) {
      for (const key in doc.copies) {
        const copy = doc.copies[key];
        if (copy && !isValidPageFormat(copy.pages)) {
          alert(`Invalid page format in document "${filename}". Allowed formats: 1,2,3 or 1-2 or All`);
          return;
        }
      }
    }
  }
  saving.value = true;
  try {
    const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
    const config = {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
    };
    await axios.patch(`${CONSTANTS.API.PATCH_ADMIN_ORDERS}${orderId}`, form.value, config);
    alert('Order updated successfully.');
    if (form.value.status === CONSTANTS.ORDER_STATUS_COMPLETED || form.value.status === CONSTANTS.ORDER_STATUS_CANCELLED) {
      router.push('/admin');
    }
  } catch (err) {
    if (err.response) {
      checkApiStatusForAdmin(err.response?.status);
    }
    alert(err.response?.data?.message || 'Failed to update order.');
  } finally {
    saving.value = false;
  }
};

const updateOrderStatus = async () => {
  updatingStatus.value = true;
  try {
    const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
    const config = {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
    };
    await axios.patch(`/api/v1/admin/orders/${orderId}/status`, {
      status: form.value.status
    }, config);
    toast.success('Order status updated successfully.');
    initialStatus.value = form.value.status;
    if (form.value.status === CONSTANTS.ORDER_STATUS_COMPLETED || form.value.status === CONSTANTS.ORDER_STATUS_CANCELLED) {
      router.push('/admin');
    }
  } catch (err) {
    if (err.response) {
      checkApiStatusForAdmin(err.response?.status);
    }
    toast.error(err.response?.data?.message || 'Failed to update order status.');
  } finally {
    updatingStatus.value = false;
  }
};

const updateAdditionalInfo = async () => {
  updatingAdditionalInfo.value = true;
  try {
    const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
    const config = {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
    };
    await axios.patch(`/api/v1/admin/orders/${orderId}/additional-info`, {
      additional_charge: form.value.additional_charge,
      remark: form.value.remark,
      total_price: form.value.total_price
    }, config);
    toast.success('Order details updated successfully.');
  } catch (err) {
    if (err.response) {
      checkApiStatusForAdmin(err.response?.status);
    }
    toast.error(err.response?.data?.message || 'Failed to update order details.');
  } finally {
    updatingAdditionalInfo.value = false;
  }
};

const updatePaymentStatus = async () => {
  updatingPaymentStatus.value = true;
  try {
    const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
    const config = {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
    };
    await axios.patch(`/api/v1/admin/orders/${orderId}/payment-status`, {
      payment_status: newPaymentStatus.value
    }, config);
    toast.success('Payment status updated successfully.');
  } catch (err) {
    if (err.response) {
      checkApiStatusForAdmin(err.response?.status);
    }
    toast.error(err.response?.data?.message || 'Failed to update payment status.');
  } finally {
    updatingPaymentStatus.value = false;
  }
};

onMounted(() => {
  fetchOptions();
  if (orderId) {
    fetchOrder();
  } else {
    error.value = 'Invalid order ID.';
    loading.value = false;
  }
});
</script>

<style scoped>
/* Improved custom dropdown for orientation SVG */
.custom-dropdown {
  position: relative;
  border: 1px solid #bfdbfe;
  border-radius: 0.5rem;
  padding: 0;
  background: white;
  cursor: pointer;
  height: 42px;
  box-sizing: border-box;
  width: 100%;
}
.selected-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0 0.75rem;
  height: 100%;
  color: #1e3a8a;
}
.dropdown-list {
  position: absolute;
  left: 0;
  right: 0;
  background: #fff;
  border: 1.5px solid #2563eb;
  border-radius: 0.5em;
  z-index: 20;
  max-height: 220px;
  overflow-y: auto;
  margin: 0;
  padding: 0.25em 0;
  list-style: none;
  box-shadow: 0 4px 16px rgba(37,99,235,0.10);
}
.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  padding: 0.5rem;
  transition: background-color 0.15s;
}
.dropdown-item:hover {
  background-color: #e0e7ff;
}
.icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  flex-shrink: 0;
}
.icon-wrapper :deep(svg) {
  width: 100%;
  height: 100%;
  display: block;
}
.serial-header {
  margin-bottom: 0.5rem;
}

.serial-badge {
  display: inline-block;
  background-color: #dbeafe;
  color: #1e40af;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border: 1px solid #bfdbfe;
}
.dropdown-arrow {
  margin-left: auto;
  font-size: 0.75rem;
  color: #6b7280;
}
.edit-order-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem;
  background-color: white;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0;
  color: #1f2937;
}

.loading-state,
.error-message {
  text-align: center;
  padding: 1rem;
}

.error-message {
  color: #dc2626;
}

.form-group {
  margin-bottom: 1.5rem;
}

.status-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (min-width: 768px) {
  .status-grid {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  }
}

.status-card {
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background-color: #f9fafb;
}

.payment-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.btn-payment {
  white-space: nowrap;
  background-color: #059669;
}

.btn-payment:hover {
  background-color: #047857;
}

.mb-0 {
  margin-bottom: 0 !important;
}

.mb-8 {
  margin-bottom: 2rem;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
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
  height: 42px;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  box-shadow: 0 0 0 2px #93c5fd;
}

.form-control:read-only {
  background-color: #f3f4f6;
  cursor: default;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #374151;
}

.document-card {
  margin-bottom: 1.5rem;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  background-color: #f9fafb;
}

.document-header {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  margin-bottom: 1rem;
  gap: 1rem;
}

.document-title {
  font-weight: 500;
  color: #111827;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.header-total-pages {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #4b5563;
}

.header-input {
  width: 70px;
  padding: 0.25rem 0.5rem;
  border: 1px solid #bfdbfe;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  text-align: center;
}

.btn-remove {
  font-size: 0.875rem;
  color: #dc2626;
  background: none;
  border: none;
  cursor: pointer;
  margin-left: auto;
}

.btn-remove:hover {
  color: #991b1b;
  text-decoration: underline;
}

.grid-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

@media (min-width: 768px) {
  .grid-row {
    grid-template-columns: repeat(2, 1fr);
  }
}

.form-label-sm {
  display: block;
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.form-control-sm {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 0.25rem;
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.copies-section {
  margin-top: 1rem;
}

.subsection-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.copy-card {
  padding: 0.75rem;
  background-color: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.25rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  margin-bottom: 0.75rem;
}

.copy-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.copy-index {
  font-size: 0.75rem;
  font-weight: bold;
  color: #6b7280;
}

.btn-remove-sm {
  font-size: 0.75rem;
  color: #ef4444;
  background: none;
  border: none;
  cursor: pointer;
}

.btn-remove-sm:hover {
  color: #b91c1c;
  text-decoration: underline;
}

.btn-add-copy {
  margin-top: 0.5rem;
  padding: 0.375rem 0.75rem;
  background-color: #10b981;
  color: white;
  border-radius: 0.25rem;
  border: none;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 500;
}

.btn-add-copy:hover {
  background-color: #059669;
}

.copy-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
}

@media (min-width: 768px) {
  .copy-grid {
    grid-template-columns: repeat(6, 1fr);
  }
  .col-paper, .col-mode, .col-orientation, .col-comment, .col-copies, .col-price {
    grid-column: span 2;
  }
  .col-pages, .col-total-print-pages {
    grid-column: span 3;
  }
}

.option-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #1d4ed8;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 1.5rem;
}

.btn-cancel {
  margin-right: 0.75rem;
  padding: 0.5rem 1rem;
  background-color: #e5e7eb;
  color: #374151;
  border-radius: 0.375rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.btn-cancel:hover {
  background-color: #d1d5db;
}

.btn-submit {
  padding: 0.5rem 1rem;
  background-color: #4f46e5;
  color: white;
  border-radius: 0.375rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.btn-submit:hover {
  background-color: #4338ca;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-count-hint {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.btn-home {
  color: #4b5563;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.875rem;
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s;
  background-color: white;
}

.btn-home:hover {
  background-color: #f3f4f6;
  color: #1f2937;
}

.btn-back-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    border: 1px solid #e2e8f0;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    color: #64748b;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
}
.btn-back-header:hover {
    background: #f1f5f9;
    color: #334155;
    border-color: #cbd5e1;
}
.btn-back-header svg {
    width: 1.25rem;
    height: 1.25rem;
}

.static-value {
  display: flex;
  align-items: center;
  width: 100%;
  background-color: #e5e7eb;
  border: 1px solid #d1d5db;
  color: #6b7280;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 1rem;
  height: 42px;
  box-sizing: border-box;
  font-weight: 600;
  cursor: not-allowed;
}
</style>