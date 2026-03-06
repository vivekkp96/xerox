<template>
    <div class="page-layout">
        <AppHeader />
        <div class="page-container">
            <div v-if="loading" class="loading-state">
                <svg class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span>Loading order details...</span>
            </div>

            <div v-else-if="error" class="error-message">
                {{ error }}
                <div class="mt-4">
                    <router-link to="/home" class="btn-back">Back to Orders</router-link>
                </div>
            </div>

            <div v-else-if="order" class="order-layout">
                <div class="main-content">
                    <div class="header-row">
                        <h2 class="page-title">Order {{ formatOrderNumber(order.id) }}</h2>
                    </div>

                    <div v-if="order.comment" class="section-card">
                        <h3 class="section-title">Your Comment</h3>
                        <p class="remark-text">{{ order.comment }}</p>
                    </div>

                    <div v-if="order.remark" class="section-card admin-info-card">
                        <h3 class="section-title">Admin Remark</h3>
                        <p class="remark-text">{{ order.remark }}</p>
                    </div>

                    <div class="document-section">
                        <h3 class="section-title">Documents</h3>
                    <div v-for="doc in sortedDocuments" :key="doc.filename" class="document-item">
                        <div class="document-header" @click="toggleDocument(doc.filename)">
                            <div class="toggle-indicator" :class="{ 'is-open': expandedDocs.includes(doc.filename) }">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 20px; height: 20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <div v-if="doc.serial_number" class="serial-number-wrapper">
                                <span class="simple-serial-number">{{ doc.serial_number }}</span>
                            </div>
                            <div class="file-info-wrapper">
                                <svg class="doc-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="filename" :title="doc.filename">{{ truncateFileName(doc.filename) }}</span>
                            </div>
                            <div class="header-total-pages">
                            <label class="header-label" style="display: flex; align-items: center; gap: 0.25rem;" title="Total pages in the uploaded file.">
                            Total Pages
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px; color: #6b7280;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            :</label>
                            <span class="static-page-count">{{ doc.total_pages || 1 }}</span>
                            </div>
                        </div>
                        
                        <div v-show="expandedDocs.includes(doc.filename)" class="document-body">
                        <div v-for="(copy, index) in doc.copies" :key="index" class="configuration-item">
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

                <div class="sidebar-content">
                    <div class="summary-box">
                        <h3 class="summary-title">Order Summary</h3>
                        
                        <div class="summary-row">
                            <span class="summary-label">Date Placed</span>
                            <span class="summary-value">{{ new Date(order.created_at).toLocaleDateString() }}</span>
                        </div>

                        <div class="summary-row">
                            <span class="summary-label">Order Status</span>
                            <span :class="['status-badge', `status-${order.status}`]">{{ order.status }}</span>
                        </div>

                        <div class="summary-row">
                            <span class="summary-label">Payment Status</span>
                            <span :class="['status-badge', `payment-status-${(order.payment_status || '').replace(/ /g, '_')}`]">
                                {{ (order.payment_status || '').replace(/_/g, ' ') }}
                            </span>
                        </div>

                        <div class="divider"></div>

                        <div class="summary-row">
                            <span class="summary-label">Total Documents</span>
                            <span class="summary-value">{{ sortedDocuments.length }}</span>
                        </div>

                        <div class="summary-row" v-if="order.additional_charge > 0">
                            <span class="summary-label" style="display: flex; align-items: center; gap: 0.25rem;">
                                Additional Charge
                                <CustomTooltip text="Additional charges applied by the administrator.">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px; color: #6b7280;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </CustomTooltip>
                            </span>
                            <span class="summary-value">₹{{ formatPrice(order.additional_charge) }}</span>
                        </div>

                        <div class="divider"></div>

                        <div class="summary-row total-row">
                            <span class="summary-label">Total Price</span>
                            <span class="summary-value total-price">₹{{ formatPrice(order.total_price) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import AppHeader from './AppHeader.vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { CONSTANTS } from '../constants';
import { getPrintOrientations } from '../utils/printOrientationUtils';
import { calculateTotalPrintPages } from '../utils/printCalculationUtils';
import CustomTooltip from './CustomTooltip.vue';
import { formatOrderNumber } from '../utils/formatters';
import { truncateFileName } from '../utils/stringUtils';
const route = useRoute();
const order = ref(null);
const loading = ref(true);
const error = ref('');
const paperSizes = ref([]);
const expandedDocs = ref([]);
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

const toggleDocument = (filename) => {
    if (expandedDocs.value.includes(filename)) {
        expandedDocs.value = expandedDocs.value.filter(f => f !== filename);
    } else {
        expandedDocs.value.push(filename);
    }
};

onMounted(async () => {
    const orderId = route.params.id;
    if (!orderId) {
        error.value = 'Invalid Order ID';
        loading.value = false;
        return;
    }

    try {
        const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
        if (!token) {
            error.value = 'Unauthenticated';
            loading.value = false;
            return;
        }

        const config = {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        };

        const [orderRes, sizesRes, modesRes] = await Promise.all([
            axios.get(`/api/v1/orders/${orderId}`, config),
            axios.get('/api/v1/paper-sizes', config),
            axios.get('/api/v1/print-modes', config)
        ]);
        order.value = orderRes.data;
        if (order.value.documents) {
            expandedDocs.value = Object.keys(order.value.documents);
        }
        paperSizes.value = sizesRes.data;
        printModes.value = modesRes.data;
        // Use local utility for print orientations
        printOrientations.value = await getPrintOrientations();
    } catch (e) {
        console.error(e);
        error.value = e.response?.data?.message || 'Failed to load order details.';
    } finally {
        loading.value = false;
    }
});

const formatPrice = (price) => {
    return Number(price).toFixed(2);
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
</script>

<style scoped>
.page-layout {
    min-height: 100vh;
    background-color: #eff6ff;
    display: flex;
    flex-direction: column;
}

.page-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 0.5rem;
}

.order-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
    width: 100%;
    max-width: 1200px;
}

.header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-title {
    font-size: 1.875rem;
    line-height: 2.25rem;
    font-weight: 800;
    color: #1d4ed8;
    margin-bottom: 0;
    letter-spacing: -0.025em;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}
.status-Pending { background-color: #fef3c7; color: #92400e; }
.status-Processing { background-color: #dbeafe; color: #1e40af; }
.status-Completed { background-color: #d1fae5; color: #065f46; }
.status-Cancelled { background-color: #fee2e2; color: #991b1b; }
.payment-status-Pending { background-color: #fef3c7; color: #92400e; }
.payment-status-Paid { background-color: #dbeafe; color: #1e40af; }
.payment-status-Payment_Verified { background-color: #d1fae5; color: #065f46; }
.payment-status-Refunded { background-color: #e5e7eb; color: #4b5563; }

.btn-back {
    color: #4b5563;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    transition: all 0.2s;
}
.btn-back:hover {
    background-color: #f3f4f6;
    color: #1f2937;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 1rem;
}

.section-card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e5e7eb;
}

.remark-text {
    margin-top: 0.5rem;
    color: #334155;
    white-space: pre-wrap;
    font-size: 1rem;
    line-height: 1.5;
}

.admin-info-card {
    background-color: #fffbeb;
    border-color: #fcd34d;
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
    justify-content: flex-start;
    align-items: center;
    margin-bottom: 0.75rem;
    gap: 1rem;
    cursor: pointer;
}

.file-info-wrapper {
   display: flex;
    align-items: center;
    gap: 0.5rem;
    background-color: #eff6ff;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #bfdbfe;
    min-width: 0;
    overflow: hidden;
}

.serial-number-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #eff6ff;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #bfdbfe;
}

.toggle-indicator {
    display: flex;
    align-items: center;
    color: #64748b;
    transition: transform 0.2s ease;
}

.toggle-indicator.is-open {
    transform: rotate(180deg);
}

.document-body {
    border-top: 1px solid #e5e7eb;
}

.simple-serial-number {
    font-weight: 700;
    color: #64748b;
    font-size: 1rem;
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

.header-total-pages {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
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

.loading-state, .error-message {
    text-align: center;
    padding: 3rem;
    width: 100%;
}

.spinner {
    animation: spin 1s linear infinite;
    height: 2rem;
    width: 2rem;
    margin: 0 auto 1rem;
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

/* Sidebar & Summary Styles */
.summary-box {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 2rem;
}

.summary-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
    color: #4b5563;
}

.summary-label {
    font-weight: 500;
}

.summary-value {
    font-weight: 600;
    color: #111827;
}

.divider {
    height: 1px;
    background-color: #e5e7eb;
    margin: 1rem 0;
}

.total-row .summary-label {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
}

@media (max-width: 900px) {
    .order-layout {
        grid-template-columns: 1fr;
    }
}


/* Clean & Modern OrderView Styles */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

.page-layout, .page-container {
    font-family: 'Outfit', sans-serif;
    background-color: #f8fafc; /* Slate 50 */
    color: #334155; /* Slate 700 */
}
.page-title, .section-title {
    font-family: 'Outfit', sans-serif;
}
.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b; /* Slate 800 */
    letter-spacing: -0.025em;
}
.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #334155;
}
.section-card, .summary-box, .compact-upload-card {
    background: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border: 1px solid #f1f5f9;
}
.input-label, .option-label, .header-label, .info-label {
    font-family: 'Outfit', sans-serif;
    color: #64748b; /* Slate 500 */
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    font-size: 0.75rem;
}
.hint, .page-count-hint {
    color: #94a3b8;
}
.filename {
    font-family: 'Outfit', sans-serif;
    color: #1e293b;
    font-weight: 600;
}
.serial-badge {
    background: #f1f5f9;
    color: #64748b;
    font-family: 'Outfit', sans-serif;
}
.static-page-count {
    color: #334155;
    font-weight: 700;
}
.price-value {
    color: #15803d;
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.per-page-price {
    color: #16a34a;
}
.message {
    font-family: 'Outfit', sans-serif;
}
.message.success {
    color: #15803d;
}
.message.error {
    color: #dc2626;
}
.message.info {
    color: #334155;
}
.form-control, .header-input, .copies-input, .read-only-field {
    font-family: 'Outfit', sans-serif;
    border: 1px solid #cbd5e1;
    color: #334155;
    background: white;
    border-radius: 0.5rem;
}
.read-only-field {
    background-color: #f8fafc;
    border-color: #e2e8f0;
}
.form-control:focus, .header-input:focus, .copies-input:focus {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    outline: none;
}
.summary-title {
    font-family: 'Outfit', sans-serif;
    color: #1e293b;
}
.summary-row {
    color: #64748b;
}
.summary-label {
    color: #64748b;
    font-weight: 500;
}
.summary-value {
    color: #334155;
}
.total-row .summary-label {
    color: #1e293b;
}
.total-row .total-price {
     font-size: 1.5rem;
    font-weight: 800;
    color: hsl(168, 60%, 45%);
}

/* Theme Overrides */
.doc-icon {
    color: #8b5cf6;
}
.btn-back {
    background: white;
    color: #64748b;
    border-color: #cbd5e1;
}
.btn-back:hover {
    background: #f1f5f9;
    color: #334155;
}
</style>