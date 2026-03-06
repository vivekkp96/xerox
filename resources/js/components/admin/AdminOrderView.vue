<template>
    <div class="page-container">
        <div v-if="loading" class="loading-state">Loading...</div>
        <div v-if="error" class="error-message">{{ error }}</div>
        <div v-if="order" class="order-card">
            <div class="header">
                <div class="header-left">
                    <button @click="goBack" class="btn-back-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </button>
                    <h2 class="page-title">Order Details #{{ order.id }}</h2>
                </div>
            </div>

            <div class="order-summary">
                <div class="summary-item">
                    <span class="summary-label">Status:</span>
                    <span :class="['status-badge', `status-${order.status}`]">{{ order.status }}</span>
                </div>
                <div class="summary-item" v-if="order.payment_status">
                    <span class="summary-label">Payment Status:</span>
                    <span :class="['status-badge', `status-payment-${order.payment_status.replace(' ', '_')}`]">{{ order.payment_status.replace('_', ' ') }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Price:</span>
                    <span class="summary-value">₹{{ parseFloat(order.total_price).toFixed(2) }}</span>
                </div>
                <div class="summary-item" v-if="order.user">
                    <span class="summary-label">User:</span>
                    <span class="summary-value">{{ order.user.name }} ({{ order.user.email }})</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Order Date:</span>
                    <span class="summary-value">{{ new Date(order.created_at).toLocaleString() }}</span>
                </div>
                <div class="summary-item" v-if="order.comment">
                    <span class="summary-label">User Comment:</span>
                    <span class="summary-value">{{ order.comment }}</span>
                </div>
            </div>

            <div class="document-section">
                <h3 class="section-title">Documents</h3>
                <div v-for="doc in sortedDocuments" :key="doc.filename" class="document-item">
                    <div class="serial-header">
                        <span v-if="doc.serial_number" class="serial-badge">Document #{{ doc.serial_number }}</span>
                    </div>
                    <div class="document-header">
                        <div class="file-info-container">
                            <div class="file-info-wrapper">
                                <svg class="doc-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="filename">{{ doc.filename }}</span>
                            </div>
                            <div class="header-total-pages">
                                <label class="header-label">Total Pages:</label>
                                <span class="static-page-count">{{ doc.total_pages || 1 }}</span>
                            </div>
                        </div>
                        <div class="header-actions">
                            <button @click="downloadDocument(doc.filename)" class="btn-download">Download</button>
                        </div>
                    </div>
                    
                    <div v-for="(copy, index) in doc.copies" :key="index" class="configuration-item">
                        <div class="copy-label" v-if="doc.copies.length > 1 || true">Copy #{{ index }}</div>
                        <div class="document-options">
                            <div class="option-group group-paper">
                                <label class="option-label">Paper Size</label>
                                <div class="read-only-field">{{ getSizeName(copy.paper_size_id) }}</div>
                            </div>
                            <div class="option-group group-mode">
                                <label class="option-label">Mode</label>
                                <div class="read-only-field">{{ getModeName(copy.mode_id) }}</div>
                            </div>
                            <div class="option-group group-orientation">
                                <label class="option-label">Orientation</label>
                                <div class="read-only-field orientation-field">
                                    <span v-if="getOrientationSvg(copy.orientation_id)" v-html="getOrientationSvg(copy.orientation_id)" class="icon-wrapper"></span>
                                    <span>{{ getOrientationName(copy.orientation_id) }}</span>
                                </div>
                            </div>
                            <div class="option-group group-pages">
                                <label class="option-label">Pages</label>
                                <div class="read-only-field">{{ copy.pages }}</div>
                            </div>
                            <div class="option-group group-copies">
                                <label class="option-label">Number of Copies</label>
                                <div class="read-only-field">{{ copy.number_of_copies || 1 }}</div>
                            </div>
                            <div class="option-group group-comment" v-if="copy.comment">
                                <label class="option-label">Comment</label>
                                <div class="read-only-field">{{ copy.comment }}</div>
                            </div>
                            <div class="option-group group-total-print-pages">
                                <label class="option-label">Total Pages to Print</label>
                                <div class="read-only-field">{{ calculateTotalPrintPages(copy.pages, doc.total_pages, copy.number_of_copies) }}</div>
                            </div>
                            <div class="option-group group-price">
                                <label class="option-label">Price</label>
                                <div class="price-value">₹{{ parseFloat(copy.totalPrice).toFixed(2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { getPrintOrientations } from '../../utils/printOrientationUtils';
import { CONSTANTS } from '../../constants';
import { checkApiStatusForAdmin } from '../../utils/apiUtils';
import { calculateTotalPrintPages } from '../../utils/printCalculationUtils';

const route = useRoute();
const router = useRouter();
const order = ref(null);
const loading = ref(true);
const error = ref('');
const paperSizes = ref([]);
const printModes = ref([]);
const printOrientations = ref([]);

const sortedDocuments = computed(() => {
    if (!order.value || !order.value.documents) return [];
    return Object.entries(order.value.documents)
        .map(([filename, doc]) => ({
            filename,
            ...doc
        }))
        .sort((a, b) => (a.serial_number || 0) - (b.serial_number || 0));
});

const goBack = () => {
    router.back();
};

const fetchOrder = async () => {
    loading.value = true;
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) {
            error.value = 'Authentication error.';
            return;
        }
        const config = {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        };
        const [orderRes, sizesRes, modesRes] = await Promise.all([
            axios.get(`/api/v1/admin/orders/${route.params.id}`, config),
            axios.get('/api/v1/paper-sizes', config),
            axios.get('/api/v1/print-modes', config)
        ]);
        order.value = orderRes.data;
        paperSizes.value = sizesRes.data;
        printModes.value = modesRes.data;
        // Use local utility for print orientations
        printOrientations.value = await getPrintOrientations();
    } catch (e) {
        if (e.response) {
            checkApiStatusForAdmin(e.response?.status);
        }
        console.error(e);
        error.value = e.response?.data?.message || 'Failed to load order details.';
    } finally {
        loading.value = false;
    }
};

const getModeName = (id) => {
    const mode = printModes.value.find(m => m.id === id);
    return mode ? mode.name : 'Unknown';
};

const getSizeName = (id) => {
    const size = paperSizes.value.find(s => s.id === id);
    return size ? size.value : 'Default';
};

const getOrientationName = (id) => {
    const orientation = printOrientations.value.find(o => o.id === id);
    return orientation ? orientation.name : 'Unknown';
};

const getOrientationSvg = (id) => {
    const orientation = printOrientations.value.find(o => o.id === id);
    return orientation ? orientation.svg : null;
};

const downloadDocument = async (filename) => {
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) {
            alert('Authentication error');
            return;
        }

        const response = await axios.get(`/api/v1/admin/orders/${route.params.id}/documents/${filename}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
            },
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        console.error(e);
        alert('Failed to download document');
    }
};

onMounted(fetchOrder);
</script>

<style scoped>
/* Using styles from OrderPage and OrderList for consistency */
.page-container {
    min-height: 100vh;
    background-color: #f9fafb;
    padding: 2rem;
}

.order-card {
    max-width: 56rem;
    margin: auto;
    background-color: white;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border-radius: 1rem;
    padding: 2.5rem;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title {
    font-size: 1.875rem;
    font-weight: 800;
    color: #1f2937;
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

.loading-state,
.error-message {
    text-align: center;
    padding: 2rem;
    font-size: 1.125rem;
    color: #6b7280;
}

.error-message {
    color: #dc2626;
    background-color: #fee2e2;
    border-radius: 0.5rem;
}

.order-summary {
    background-color: #f9fafb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.summary-item {
    display: flex;
    flex-direction: column;
}

.summary-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.summary-value {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
}

.document-section {
    margin-top: 2rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #1d4ed8;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 0.5rem;
}

.document-item {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.document-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    gap: 1rem;
}

.file-info-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}

.file-info-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
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

.doc-icon {
    width: 1.25rem;
    height: 1.25rem;
    color: #3b82f6;
}

.filename {
    font-weight: 500;
    color: #1e3a8a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-total-pages {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.header-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #4b5563;
}

.static-page-count {
    font-weight: 600;
    color: #1e3a8a;
    background-color: #e0f2fe;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.btn-download {
    background-color: #eff6ff;
    color: #1d4ed8;
    font-weight: 600;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
    border: 1px solid #bfdbfe;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    white-space: nowrap;
}

.btn-download:hover {
    background-color: #dbeafe;
}

.configuration-item {
    position: relative;
    padding-top: 1rem;
    margin-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.configuration-item:first-of-type {
    border-top: none;
    margin-top: 0;
    padding-top: 0;
}

.copy-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}

.document-options {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    padding: 0.5rem;
}

@media (min-width: 768px) {
    .document-options {
        grid-template-columns: repeat(6, 1fr);
    }
    .group-paper,
    .group-mode,
    .group-orientation {
        grid-column: span 2;
    }
    .group-pages,
    .group-copies {
        grid-column: span 3;
    }
    .group-comment,
    .group-total-print-pages,
    .group-price {
        grid-column: span 2;
    }
}

.option-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #1d4ed8;
}

.read-only-field {
    display: block;
    width: 100%;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #1e3a8a;
    padding: 0 0.75rem;
    border-radius: 0.5rem;
    font-size: 1rem;
    height: 42px;
    display: flex;
    align-items: center;
    box-sizing: border-box;
}

.orientation-field {
    gap: 0.75rem;
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

.price-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1d4ed8;
    padding: 0.5rem 0.75rem;
    border: 1px solid #bfdbfe;
    border-radius: 0.5rem;
    background-color: #f8fafc;
    height: 42px;
    display: flex;
    align-items: center;
    box-sizing: border-box;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: capitalize;
    display: inline-block;
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

.text-sm {
    font-size: 0.875rem;
}

.text-gray-500 {
    color: #6b7280;
}

.mb-2 {
    margin-bottom: 0.5rem;
}

.font-semibold {
    font-weight: 600;
}

.text-gray-700 {
    color: #374151;
}
</style>