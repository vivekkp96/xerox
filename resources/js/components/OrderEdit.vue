<template>
    <div class="page-layout">
    <AppHeader />
    <div class="page-container">
        <div class="header-row">
            <h1 class="page-title">Edit Order</h1>
        </div>

        <div v-if="isUploading" class="uploading-overlay">
            <div class="spinner"></div>
            <div class="uploading-text">Uploading...</div>
        </div>

        <div v-if="loading" class="loading-state">Loading order details...</div>
        <div v-else-if="error" class="error-message">{{ error }}</div>

        <div v-else-if="order" class="order-layout">
            <div class="main-content">
                <div v-if="!canAddItems" class="error-message">
                    Cannot add new documents or copies as printing options are not configured.
                </div>

                <!-- Upload Documents -->
            <div class="form-group" ref="mainUploadSection">
                <label class="input-label">Upload New Documents</label>
                <div 
                    class="upload-drop-zone" 
                    :class="{ 'is-dragover': isDragOver, 'disabled': !canAddItems }"
                    @dragover.prevent="isDragOver = true"
                    @dragleave.prevent="isDragOver = false"
                    @drop.prevent="handleDrop"
                    @click="triggerFileInput"
                >
                    <input
                        ref="fileInput"
                        class="file-input-hidden"
                        id="documents" type="file" multiple
                        accept="image/*,.pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                        @change="handleFileUpload"
                        :disabled="!canAddItems">
                    
                    <div class="upload-content">
                        <svg class="upload-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="upload-text">Drag & drop files here, or</p>
                        <button type="button" class="btn-choose-files" :disabled="!canAddItems">Choose Files</button>
                    </div>
                </div>
                <div class="upload-footer">
                    <span class="hint">Supported formats: PDF, DOCX (Max 10 files)</span>
                </div>
            </div>

                <!-- Remark -->
                <div v-if="order.remark" class="section-card admin-info-card">
                    <div class="info-item">
                        <h4 class="info-label">Admin Remark</h4>
                        <p class="info-value remark-text">{{ order.remark }}</p>
                    </div>
                </div>

            <!-- Documents List -->
            <div v-if="sortedDocuments.length > 0" class="document-section">
                <h3 class="section-title">Selected Documents</h3>
                <div class="documents-scroll-container">
                <div v-for="doc in sortedDocuments" :key="doc.filename" class="document-item" :ref="el => setDocumentRef(el, doc.filename)">
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
                        <div class="file-details">
                            <span class="filename" :title="doc.filename">{{ truncateFileName(doc.filename) }}</span>
                        </div>
                    </div>
                    <div class="header-total-pages">
                        <label class="header-label" style="display: flex; align-items: center; gap: 0.25rem;" title="Total pages in the uploaded file.">
                            Total Pages
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px; color: #6b7280;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                        :</label>
                        <CustomTooltip text="Click outside to save" v-if="!isPdf(doc.filename)" @click.stop>
                            <input type="number" 
                                   v-model.number="doc.total_pages" 
                                   @change="updateDocumentPages(doc.filename, doc.total_pages)" 
                                   min="1" class="header-input"
                                   :class="{ 'input-green-highlight': !doc.total_pages || doc.total_pages === '' }">
                        </CustomTooltip>
                        <span v-else class="static-page-count">{{ doc.total_pages }}</span>
                    </div>
                    <button @click.stop="deleteDocument(doc.filename)" class="btn-remove" title="Remove Document" v-if="sortedDocuments.length > 1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

                <!-- Copies -->
                <div v-show="expandedDocs.includes(doc.filename)" class="document-body">
                    <div v-for="(copy, index) in doc.copies" :key="index" class="configuration-item" :class="{ 'has-remove-btn': Object.keys(doc.copies).length > 1 }">
                        <div class="config-actions">
                            <button v-if="Object.keys(doc.copies).length > 1" @click="deleteCopy(doc.filename, index)" class="btn-remove-config" title="Remove Copy">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                        <div class="document-options">
                            <div class="option-group group-paper">
                                <label class="option-label">Paper Size</label>
                                <select v-model="copy.paper_size_id" @change="updateCopy(doc.filename, index, copy)" class="form-control">
                                    <option v-for="size in activePaperSizes" :key="size.id" :value="size.id">
                                        {{ size.value }}
                                    </option>
                                </select>
                            </div>

                            <div class="option-group group-lamination" v-if="isLaminationAvailable(copy.paper_size_id)">
                                <label class="option-label">Lamination</label>
                                <label class="checkbox-container">
                                    <input type="checkbox" v-model="copy.lamination" @change="updateCopy(doc.filename, index, copy)">
                                    <span class="checkbox-text">Laminate (+₹{{ getLaminationAmount(copy.paper_size_id) }}/pg)</span>
                                </label>
                            </div>

                            <div class="option-group group-mode">
                                <label class="option-label">Mode</label>
                                <select v-model="copy.mode_id" @change="updateCopy(doc.filename, index, copy)" class="form-control">
                                    <option v-for="mode in activePrintModes" :key="mode.id" :value="mode.id">
                                        {{ mode.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="option-group group-orientation">
                                <label class="option-label">Orientation</label>
                                <div class="custom-dropdown" @click="copy.showOrientationDropdown = !copy.showOrientationDropdown">
                                    <div class="selected-option">
                                        <span v-if="getOrientationSvg(copy.orientation_id)" v-html="getOrientationSvg(copy.orientation_id)" class="icon-wrapper"></span>
                                        <span>{{ getOrientationName(copy.orientation_id) }}</span>
                                        <span class="dropdown-arrow">▼</span>
                                    </div>
                                    <ul v-if="copy.showOrientationDropdown" class="dropdown-list">
                                        <li v-for="orientation in printOrientations" :key="orientation.id" @click.stop="selectOrientation(doc.filename, index, copy, orientation.id)" class="dropdown-item">
                                            <span v-if="orientation.svg" v-html="orientation.svg" class="icon-wrapper"></span>
                                            <span>{{ orientation.name }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="option-group group-pages">
                                <label class="option-label">Pages</label>
                                <div class="pages-selection-row" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <label class="radio-label" style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer;">
                                        <input type="radio" 
                                               :name="'pages-' + doc.filename + '-' + index"
                                               :checked="copy.pages === 'All'"
                                               @change="setCopyPagesAll(doc.filename, index, copy)">
                                        <span style="font-size: 0.875rem; white-space: nowrap;">All Pages</span>
                                    </label>
                                    <label class="radio-label" style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer;">
                                        <input type="radio" 
                                               :name="'pages-' + doc.filename + '-' + index"
                                               :checked="copy.pages !== 'All'"
                                               @change="setCopyPagesCustom(copy)">
                                        <span style="font-size: 0.875rem; white-space: nowrap;">Custom</span>
                                    </label>
                                    <div style="flex: 1; min-width: 120px;">
                                        <CustomTooltip text="Click outside to save" class="w-100">
                                            <input type="text" 
                                                   :value="copy.pages === 'All' ? '' : copy.pages" 
                                                   @input="copy.pages = $event.target.value" 
                                                   @change="updateCopy(doc.filename, index, copy)" 
                                                   class="form-control" 
                                                   placeholder="e.g. 1-5, 8"
                                                   :disabled="copy.pages === 'All'">
                                        </CustomTooltip>
                                    </div>
                                </div>
                                <span class="page-count-hint">Format: 1,2,3 or 1-2</span>
                            </div>

                            <div class="option-group group-copies">
                                <label class="option-label">Number of Copies</label>
                                <div class="copies-control">
                                    <button type="button" class="btn-copy-control" @click="updateCopies(doc.filename, index, copy, -1)" :disabled="(copy.number_of_copies || 1) <= 1">-</button>
                                    <CustomTooltip text="Click outside to save" class="flex-grow-1 h-100" style="display: flex;">
                                        <input type="number" v-model.number="copy.number_of_copies" @change="updateCopy(doc.filename, index, copy)" class="copies-input" min="1">
                                    </CustomTooltip>
                                    <button type="button" class="btn-copy-control" @click="updateCopies(doc.filename, index, copy, 1)">+</button>
                                </div>
                            </div>

                            <div class="option-group group-comment">
                                <label class="option-label">Configuration Comment</label>
                                <CustomTooltip text="Click outside to save" class="w-100">
                                    <input type="text" v-model="copy.comment" @change="updateCopy(doc.filename, index, copy)" class="form-control" placeholder="Optional">
                                </CustomTooltip>
                            </div>

                            <div class="option-group group-total-print-pages">
                                <label class="option-label">Total Pages to Print</label>
                                <div class="static-value">{{ calculateTotalPrintPages(copy.pages, doc.total_pages, copy.number_of_copies) }}</div>
                            </div>

                            <div class="option-group group-price">
                                <label class="option-label">Price</label>
                                <div class="price-value">
                                    ₹{{ formatPrice(copy.totalPrice) }}
                                    <span class="per-page-price"> / ₹{{ calculatePerPagePrice(copy.totalPrice, copy.number_of_copies, doc.total_pages) }} Per Page</span>
                                </div>
                            </div>
                        </div>
                    </div>

                <div class="add-config-container">
                    <button @click="addCopy(doc.filename)" class="btn-add-config" :disabled="!canAddItems">
                        Add another copy
                    </button>
                </div>
                </div>
            </div>
            </div>
            </div>

            <!-- Order Comment -->
            <div class="section-card comment-card">
                <h3 class="section-title" style="font-size: 1rem; margin-bottom: 0.5rem;">Your Comment</h3>
                <textarea v-model="order.comment" class="form-control" rows="3" placeholder="Add any special instructions..."></textarea>
                <div style="text-align: right;">
                    <button @click="updateOrderComment" class="btn-save-comment" :disabled="updatingComment">
                        {{ updatingComment ? 'Saving...' : 'Save Comment' }}
                    </button>
                </div>
            </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar-content">
                <div class="sticky-sidebar">
                    <transition name="fade">
                        <div v-if="showSidebarUpload" class="section-card compact-upload-card">
                            <h3 class="section-title" style="font-size: 1rem; margin-bottom: 0.75rem;">Add More Files</h3>
                            <div 
                                class="compact-drop-zone" 
                                :class="{ 'is-dragover': isSidebarDragOver, 'disabled': !canAddItems }"
                                @dragover.prevent="isSidebarDragOver = true"
                                @dragleave.prevent="isSidebarDragOver = false"
                                @drop.prevent="handleSidebarDrop"
                                @click="triggerSidebarFileInput"
                            >
                                <input
                                    ref="sidebarFileInput"
                                    class="file-input-hidden"
                                    type="file" multiple
                                    accept="image/*,.pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    @change="handleFileUpload"
                                    :disabled="!canAddItems">
                                <span class="compact-upload-text">Click or Drop files here</span>
                                <svg class="compact-upload-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                        </div>
                    </transition>

                <div class="summary-box">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <div class="summary-row">
                        <span class="summary-label">Order Number</span>
                        <span class="summary-value">{{ formatOrderNumber(order.id) }}</span>
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
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { getPrintOrientations } from '../utils/printOrientationUtils';
import AppHeader from './AppHeader.vue';
import { CONSTANTS } from '../constants';
import { checkApiStatus } from '../utils/apiUtils';
import { calculateNumberOfPages, getConfigurationPrice } from '../utils/orderUtils';
import { isValidPageFormat, isPdf, validatePageCount } from '../utils/validation';
import { calculateTotalPrintPages } from '../utils/printCalculationUtils';
import { toast } from 'vue3-toastify';
import { validateFileSizes, isValidFileType, validateFileNameLength } from '../utils/fileValidation';
import { getPageCount, toBase64 } from '../utils/fileProcessing';
import { validateSafeFileName } from '../utils/fileNameSecurity';
import 'vue3-toastify/dist/index.css';
import CustomTooltip from './CustomTooltip.vue';
import { formatOrderNumber } from '../utils/formatters';
import { truncateFileName } from '../utils/stringUtils';

const route = useRoute();
const orderId = route.params.id;
const order = ref(null);
const loading = ref(true);
const error = ref('');
const paperSizes = ref([]);
const printModes = ref([]);
const printOrientations = ref([]);
const printPrices = ref([]);
const updatingComment = ref(false);
const isDragOver = ref(false);
const fileInput = ref(null);
const expandedDocs = ref([]);
const mainUploadSection = ref(null);
const showSidebarUpload = ref(false);
const sidebarFileInput = ref(null);
const isSidebarDragOver = ref(false);
const documentRefs = ref({});
const isUploading = ref(false);

const setDocumentRef = (el, filename) => {
    if (el) documentRefs.value[filename] = el;
};

const activePrintModes = computed(() => printModes.value.filter(m => m.status === 'active'));
const activePaperSizes = computed(() => paperSizes.value.filter(s => s.status === 'active'));

const canAddItems = computed(() => {
    // Don't block on initial load
    if (loading.value && printModes.value.length === 0 && paperSizes.value.length === 0 && printOrientations.value.length === 0) {
        return true;
    }
    return printOrientations.value.length > 0 && activePrintModes.value.length > 0 &&
        activePaperSizes.value.length > 0;
});

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

const getAuthConfig = () => {
    const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
    if (!token) {
        error.value = 'Authentication token not found. Please log in again.';
        throw new Error('Unauthenticated');
    }
    return {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
        }
    };
};

const formatPrice = (price) => {
    return Number(price).toFixed(2);
};

const getOrientationName = (id) => {
    const orientation = printOrientations.value.find(o => o.id === id);
    return orientation ? orientation.name : 'Unknown';
};

const getOrientationSvg = (id) => {
    const orientation = printOrientations.value.find(o => o.id === id);
    return orientation ? orientation.svg : null;
};

const isLaminationAvailable = (paperSizeId) => {
    const size = activePaperSizes.value.find(s => s.id == paperSizeId);
    return size && size.lamination_amount != null && size.lamination_amount !== '';
};

const getLaminationAmount = (paperSizeId) => {
    const size = activePaperSizes.value.find(s => s.id == paperSizeId);
    return size ? size.lamination_amount : 0;
};

const calculatePerPagePrice = (totalPrice, copies, pages) => {
    const c = copies || 1;
    const p = pages || 1;
    return (Number(totalPrice) / c / p).toFixed(2);
};

const selectOrientation = (filename, index, copy, orientationId) => {
    copy.orientation_id = orientationId;
    copy.showOrientationDropdown = false;
    updateCopy(filename, index, copy);
};

const fetchConstants = async () => {
    try {
        const config = getAuthConfig();
        const [sizesRes, modesRes, pricesRes, laminationRes] = await Promise.all([
            axios.get('/api/v1/paper-sizes', config),
            axios.get('/api/v1/print-modes', config),
            axios.get('/api/v1/print-prices', config),
            axios.get('/api/v1/settings/lamination-amount', config)
        ]);
        const laminationAmount = laminationRes.data.value;
        paperSizes.value = sizesRes.data.map(size => {
            return {
                ...size,
                lamination_amount: laminationAmount
            };
        });

        printModes.value = modesRes.data;
        printPrices.value = pricesRes.data;
        // Use local utility for print orientations
        printOrientations.value = await getPrintOrientations();
    } catch (e) {
        if (e.response) {
            checkApiStatus(e.response.status);
        }
        if (!error.value) {
            console.error('Failed to fetch constants', e);
            error.value = 'Failed to fetch page options.';
        }
    }
};

const fetchOrder = async () => {
    try {
        const config = getAuthConfig();
        const response = await axios.get(`/api/v1/orders/${orderId}`, config);
        order.value = response.data;

        // Ensure lamination is boolean for all copies
        if (order.value && order.value.documents) {
            Object.values(order.value.documents).forEach(doc => {
                if (doc.copies) {
                    Object.values(doc.copies).forEach(copy => {
                        copy.lamination = !!copy.lamination;
                    });
                }
            });
        }
    } catch (e) {
        if (e.response) {
            checkApiStatus(e.response.status);
        }
        if (!error.value) {
            error.value = e.response?.data?.message || 'Failed to load order';
        }
    } finally {
        loading.value = false;
    }
};

const updateOrderComment = async () => {
    updatingComment.value = true;
    try {
        const config = getAuthConfig();
        const response = await axios.put(`/api/v1/orders/${orderId}`, {
            comment: order.value.comment
        }, config);
        order.value = response.data.order;
        toast.success('Comment updated successfully');
    } catch (e) {
        checkApiStatus(e.response?.status);
        toast.error(e.response?.data?.message || 'Failed to update comment');
    } finally {
        updatingComment.value = false;
    }
};

const setCopyPagesAll = (filename, index, copy) => {
    copy.pages = 'All';
    updateCopy(filename, index, copy);
};

const setCopyPagesCustom = (copy) => {
    copy.pages = '';
};

const updateCopies = (filename, index, copy, change) => {
    const newValue = (copy.number_of_copies || 1) + change;
    if (newValue < 1) return;
    copy.number_of_copies = newValue;
    updateCopy(filename, index, copy);
};

const updateCopy = async (filename, copyIndex, copy) => {
    if (!isValidPageFormat(copy.pages)) {
        toast.error(`Invalid page format for document "${filename}". Allowed formats: 1,2,3 or 1-2 or All`);
        await fetchOrder(); // Revert changes
        return;
    }

    const docTotalPages = Number(order.value.documents[filename].total_pages) || 1;

    const pagesCount = calculateNumberOfPages(copy.pages, docTotalPages);
    if (!validatePageCount(docTotalPages, pagesCount)) {
        toast.error(`Document has ${docTotalPages} pages, but you are trying to print ${pagesCount} pages.`);
        await fetchOrder(); // Revert changes
        return;
    }

    const mode = printModes.value.find(m => m.id === copy.mode_id);
    const size = paperSizes.value.find(s => s.id == copy.paper_size_id);
    const configForPrice = { ...copy, mode: mode?.value, size: size?.value };

    let price = getConfigurationPrice(configForPrice, docTotalPages, printPrices.value, printModes.value, paperSizes.value) * (copy.number_of_copies || 1);

    // Optimistic update
    const oldPrice = copy.totalPrice;
    const priceDiff = price - oldPrice;
    copy.totalPrice = price;
    if (order.value) {
        order.value.total_price = Number(order.value.total_price) + priceDiff;
    }

    try {
        const config = getAuthConfig();
        const response = await axios.patch(`/api/v2/order/copy/${orderId}`, {
            filename,
            copy_index: copyIndex,
            total_pages: docTotalPages,
            copy: {
                ...copy,
                price: price
            }
        }, config);
        order.value = response.data.order;

        // Ensure lamination is boolean for all copies
        if (order.value && order.value.documents) {
            Object.values(order.value.documents).forEach(doc => {
                if (doc.copies) {
                    Object.values(doc.copies).forEach(c => {
                        c.lamination = !!c.lamination;
                    });
                }
            });
        }

        toast.success('Copy updated successfully');
    } catch (e) {
        // Revert optimistic update
        copy.totalPrice = oldPrice;
        if (order.value) {
            order.value.total_price = Number(order.value.total_price) - priceDiff;
        }

        if (e.response) {
            checkApiStatus(e.response.status);
        }
        toast.error(e.response?.data?.message || 'Failed to update copy');
        await fetchOrder(); // Revert changes on error
    }
};

const addCopy = async (filename) => {
    const defaultMode = printModes.value.find(m => m.status === 'active');
    const defaultSize = paperSizes.value.find(s => s.status === 'active' && s.code === 'A4') || paperSizes.value.find(s => s.status === 'active');
    const defaultOrientation = printOrientations.value[0];
    const totalPages = order.value.documents[filename].total_pages;

    const newCopyConfig = {
        mode_id: defaultMode?.id,
        orientation_id: defaultOrientation?.id,
        pages: `1-${totalPages}`,
        paper_size_id: defaultSize?.id,
        number_of_copies: 1,
        lamination: false,
        comment: '',
    };

    let price = getConfigurationPrice({ ...newCopyConfig, mode: defaultMode?.value, size: defaultSize?.value }, totalPages, printPrices.value, printModes.value, paperSizes.value) * 1;

    const newCopy = {
        ...newCopyConfig,
        price: price
    };

    try {
        const config = getAuthConfig();
        const response = await axios.post(`/api/v2/order/copy/${orderId}`, {
            filename,
            copy: newCopy
        }, config);
        order.value = response.data.order;
        toast.success('Copy added successfully');
    } catch (e) {
        if (e.response) {
            checkApiStatus(e.response.status);
        }
        toast.error(e.response?.data?.message || 'Failed to add copy');
    }
};

const updateDocumentPages = async (filename, newPages) => {
    if (newPages < 1) {
        toast.error("Pages must be at least 1");
        await fetchOrder();
        return;
    }

    // Validate that reducing pages doesn't invalidate existing copies
    const doc = order.value.documents[filename];
    for (const copy of Object.values(doc.copies)) {
        const pagesCount = calculateNumberOfPages(copy.pages, newPages);
        if (!validatePageCount(newPages, pagesCount)) {
            toast.error(`Cannot reduce pages to ${newPages} because a copy is set to print ${pagesCount} pages.`);
            await fetchOrder();
            return;
        }
    }

    try {
        const config = getAuthConfig();
        const response = await axios.patch(`/api/v2/order/document/${orderId}`, {
            filename,
            total_pages: newPages
        }, config);
        order.value = response.data.order;
        toast.success('Document pages updated successfully');
    } catch (e) {
        if (e.response) checkApiStatus(e.response.status);
        toast.error(e.response?.data?.message || 'Failed to update document pages');
        await fetchOrder();
    }
};

const deleteCopy = async (filename, copyIndex) => {
    if (!confirm('Are you sure you want to remove this copy?')) return;
    try {
        const config = getAuthConfig();
        const response = await axios.delete(`/api/v2/order/copy/${orderId}`, {
            ...config,
            data: { filename, copy_index: copyIndex }
        });
        order.value = response.data.order;
        toast.success('Copy removed successfully');
    } catch (e) {
        if (e.response) {
            checkApiStatus(e.response.status);
        }
        toast.error(e.response?.data?.message || 'Failed to delete copy');
    }
};

const deleteDocument = async (filename) => {
    if (!confirm('Are you sure you want to delete this document? This will remove all copies.')) return;
    try {
        const config = getAuthConfig();
        const response = await axios.delete(`/api/v2/order/document/${orderId}`, {
            ...config,
            data: { filename },
        });
        order.value = response.data.order;
        toast.success('Document deleted successfully');
    } catch (e) {
        if (e.response) {
            checkApiStatus(e.response.status);
        }
        toast.error(e.response?.data?.message || 'Failed to delete document');
    }
};

const triggerFileInput = () => {
    if (canAddItems.value && fileInput.value) {
        fileInput.value.click();
    }
};

const handleDrop = async (event) => {
    isDragOver.value = false;
    if (!canAddItems.value) return;
    await processFiles(event.dataTransfer.files);
};

const triggerSidebarFileInput = () => {
    if (canAddItems.value && sidebarFileInput.value) {
        sidebarFileInput.value.click();
    }
};

const handleSidebarDrop = async (event) => {
    isSidebarDragOver.value = false;
    if (!canAddItems.value) return;
    await processFiles(event.dataTransfer.files);
};

let uploadObserver = null;

watch(mainUploadSection, (el) => {
    if (uploadObserver) {
        uploadObserver.disconnect();
        uploadObserver = null;
    }
    
    if (el) {
        uploadObserver = new IntersectionObserver(([entry]) => {
            showSidebarUpload.value = !entry.isIntersecting && entry.boundingClientRect.top < 0;
        }, { threshold: 0 });
        uploadObserver.observe(el);
    }
});

onUnmounted(() => {
    if (uploadObserver) {
        uploadObserver.disconnect();
    }
});

const processFiles = async (fileList) => {
    const files = Array.from(fileList);
    if (files.length === 0) return;

    isUploading.value = true;
    
    try {
        for (const file of files) {

            if (!isValidFileType(file)) {
                toast.error('Invalid file type. Only PDF, Images, DOC, and DOCX are allowed.');
                continue;
            }

            const sizeValidation = validateFileSizes([file], 0);
            if (!sizeValidation.valid) {
                toast.error(sizeValidation.message);
                continue;
            }

            const nameValidation = validateFileNameLength(file);
            if (!nameValidation.valid) {
                toast.error(nameValidation.message);
                continue;
            }

            const safeNameValidation = validateSafeFileName(file.name);
            if (!safeNameValidation.isValid) {
                toast.error(safeNameValidation.error);
                continue;
            }

            const pageCount = await getPageCount(file);
            const base64 = await toBase64(file);
            const isManual = pageCount === 'All';
            const totalPages = isManual ? 1 : pageCount;
            
            const defaultMode = printModes.value.find(m => m.status === 'active');
            const defaultSize = paperSizes.value.find(s => s.status === 'active' && s.code === 'A4') || paperSizes.value.find(s => s.status === 'active');
            const defaultOrientation = printOrientations.value[0];

            const maxSerial = sortedDocuments.value.length > 0 
                ? Math.max(...sortedDocuments.value.map(d => d.serial_number || 0)) 
                : 0;

            const newConfig = {
                mode_id: defaultMode?.id,
                orientation_id: defaultOrientation?.id,
                pages: isManual ? 'All' : `1-${totalPages}`,
                paper_size_id: defaultSize?.id,
                number_of_copies: 1,
                lamination: false,
                comment: '',
            };

            let price = getConfigurationPrice({ ...newConfig, mode: defaultMode?.value, size: defaultSize?.value }, totalPages, printPrices.value, printModes.value, paperSizes.value) * 1;

            const payload = {
                file_base64: base64,
                filename: file.name,
                total_pages: totalPages,
                serial_number: maxSerial + 1,
                configurations: [{
                    ...newConfig,
                    price: price
                }]
            };

            try {
                const config = getAuthConfig();
                const response = await axios.post(`/api/v2/order/document/${orderId}`, payload, config);
                order.value = response.data.order;
                toast.success('Document uploaded successfully');
                expandedDocs.value.push(file.name);

                await nextTick();
                if (documentRefs.value[file.name]) {
                    documentRefs.value[file.name].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } catch (e) {
                if (e.response) {
                    checkApiStatus(e.response.status);
                }
                toast.error(e.response?.data?.message || 'Failed to upload document');
            }
        }
    } finally {
        isUploading.value = false;
    }
};

const handleFileUpload = async (event) => {
    await processFiles(event.target.files);
    event.target.value = '';
};

onMounted(async () => {
    try {
        await fetchConstants();
        await fetchOrder();
    } catch (e) {
        loading.value = false;
    }
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

.page-layout {
    min-height: 100vh;
    background-color: #f3f4f6;
    display: flex;
    flex-direction: column;
    font-family: 'Inter', sans-serif;
}
.page-container {
    max-width: 1300px;
    margin: 0 auto;
    padding: 2rem;
    width: 100%;
    box-sizing: border-box;
}
.header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}
.page-title {
    font-size: 1.75rem;
    font-weight: bold;
    color: #1f2937;
}
.status-text {
    color: #6b7280;
    margin-top: 0.25rem;
}
.total-price {
    font-size: 1.5rem;
    font-weight: bold;
    color: #059669;
}
.section-card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.loading-state, .error-message {
    text-align: center;
    padding: 2rem;
}
.error-message {
    color: #ef4444;
}

.admin-info-card {
    background-color: #fffbeb;
    border: 1px solid #fef3c7;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.info-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #92400e;
    margin-bottom: 0.25rem;
}
.info-value {
    color: #92400e;
    font-size: 1rem;
}
.remark-text {
    white-space: pre-wrap;
    line-height: 1;
}

.btn-save-comment {
    background-color: #2563eb;
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    margin-top: 0.5rem;
}
.btn-save-comment:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.custom-dropdown {
    position: relative;
    border: 1px solid #bfdbfe;
    border-radius: 0.5rem;
    padding: 0;
    background: white;
    cursor: pointer;
    height: 42px;
    box-sizing: border-box;
}

.selected-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0 0.75rem;
    height: 100%;
    color: #334155;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    cursor: pointer;
    transition: background-color 0.15s;
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

.dropdown-arrow {
    margin-left: auto;
    font-size: 0.75rem;
    color: #6b7280;
}

.dropdown-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    z-index: 10;
    list-style: none;
    padding: 0;
    margin: 0;
}

.dropdown-list li:hover {
    background-color: #f3f4f6;
}

/* New Styles from OrderPage.vue */
.form-group {
    margin-bottom: 2rem;
}

.input-label {
    display: block;
    color: #1d4ed8;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.upload-drop-zone {
    border: 2px dashed #d1d5db;
    border-radius: 0.5rem;
    padding: 2.5rem;
    text-align: center;
    background-color: #ffffff;
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.upload-drop-zone:hover:not(.disabled) {
    border-color: #2563eb;
    background-color: #f8fafc;
}

.upload-drop-zone.is-dragover {
    border-color: #2563eb;
    background-color: #eff6ff;
}

.upload-drop-zone.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #f3f4f6;
}

.file-input-hidden {
    display: none;
}

.upload-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.upload-icon {
    width: 3rem;
    height: 3rem;
    color: #60a5fa;
}

.upload-text {
    color: #4b5563;
    font-size: 1rem;
    margin: 0;
}

.btn-choose-files {
    background-color: white;
    border: 1px solid #d1d5db;
    color: #374151;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-choose-files:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.upload-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 0.5rem;
}

.hint {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: normal;
}

.document-section {
    margin-bottom: 2rem;
}

.document-section .section-title {
    margin-left: 0.5rem;
}

.documents-scroll-container {
    max-height: 800px;
    overflow-y: auto;
    padding-right: 0.5rem;
    padding-bottom: 1rem;
}

.documents-scroll-container::-webkit-scrollbar {
    width: 6px;
}

.documents-scroll-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.documents-scroll-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.document-item {
    background-color: #ffffff;
    border: 1px solid #bfdbfe;
    border-radius: 0.75rem;
    padding-top: 1rem;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.document-header {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    padding: 1rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    gap: 1rem;
    cursor: pointer;
}

.file-info-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    overflow: hidden;
    background-color: #eff6ff;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #bfdbfe;
    min-width: 0;
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

.serial-header {
    margin-bottom: 0.5rem;
    padding: 0 1rem;
    padding-top: 1rem;
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

.file-details {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.filename {
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 0.95rem;
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

.header-input {
    width: 60px;
    padding: 0.25rem 0.5rem;
    border: 1px solid #bfdbfe;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    text-align: center;
}

.static-page-count {
    font-weight: 600;
    color: #1e3a8a;
    background-color: #e0f2fe;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
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

.file-meta {
    font-size: 0.75rem;
    color: #64748b;
}

.btn-remove {
    color: #dc2626;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.5rem;
    border: 1px solid #fecaca;
    border-radius: 0.375rem;
    background: transparent;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 0;
}

.btn-remove svg {
    width: 1.25rem;
    height: 1.25rem;
}

.btn-remove:hover {
    background-color: #ef4444;
    color: white;
    border-color: #ef4444;
}

.checkbox-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    height: 42px;
    padding: 0 0.5rem;
    border: 1px solid #bfdbfe;
    border-radius: 0.5rem;
    background-color: white;
    cursor: pointer;
}
.checkbox-text {
    font-size: 0.9rem;
    color: #334155;
}

.document-options {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    padding: 1rem;
}

@media (min-width: 768px) {
    .document-options {
        grid-template-columns: repeat(6, 1fr);
    }
    .group-paper,
    .group-mode,
    .group-orientation,
    .group-lamination {
        grid-column: span 2;
    }
    .group-pages,
    .group-copies {
        grid-column: span 2;
    }
    .group-comment,
    .group-total-print-pages,
    .group-price {
        grid-column: span 2;
    }
}

.price-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1e3a8a;
    padding: 0.5rem 0.75rem;
    border: 1px solid #bfdbfe;
    border-radius: 0.5rem;
    background-color: #f8fafc;
    height: 42px;
    display: flex;
    align-items: center;
    box-sizing: border-box;
}

.per-page-price {
    font-size: 0.75rem;
    color: #6b7280;
    margin-left: 0.5rem;
    font-weight: 500;
}

.option-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #1d4ed8;
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

.form-control:disabled {
    background-color: #e5e7eb;
    color: #6b7280;
    cursor: not-allowed;
    border-color: #d1d5db;
}

.page-count-hint {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
    display: block;
}

.configuration-item {
    position: relative;
    padding-bottom: 0.5rem;
    border-top: 1px solid #f3f4f6;
}

.configuration-item.has-remove-btn {
    padding-top: 2.5rem;
}

.config-actions {
    position: absolute;
    top: 0.5rem;
    right: 0;
    padding: 0 1rem;
}

.btn-remove-config {
    color: #ef4444;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.4rem;
    border-radius: 0.375rem;
    background-color: #fee2e2;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-remove-config svg {
    width: 1rem;
    height: 1rem;
}

.btn-remove-config:hover {
    background-color: #fecaca;
    border-color: #fca5a5;
}

.add-config-container {
    margin-top: 1rem;
    text-align: center;
}

.btn-add-config {
    background-color: #eff6ff;
    color: #1d4ed8;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #bfdbfe;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-add-config:hover {
    background-color: #dbeafe;
}

.copies-control {
    display: flex;
    align-items: center;
    border: 1px solid #bfdbfe;
    border-radius: 0.5rem;
    overflow: hidden;
    height: 42px;
    background-color: white;
    box-sizing: border-box;
}
.btn-copy-control {
    background: #f8fafc;
    border: none;
    padding: 0 1rem;
    height: 100%;
    cursor: pointer;
    color: #1e3a8a;
    font-weight: bold;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
    border-right: 1px solid #bfdbfe;
}
.btn-copy-control:last-child {
    border-right: none;
    border-left: 1px solid #bfdbfe;
}
.btn-copy-control:hover:not(:disabled) {
    background-color: #e0f2fe;
}
.btn-copy-control:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    color: #9ca3af;
}
.copies-input {
    border: none;
    text-align: center;
    width: 3rem;
    flex-grow: 1;
    font-weight: 600;
    color: #1e3a8a;
    height: 100%;
    -moz-appearance: textfield;
    padding: 0;
}
.copies-input:focus {
    outline: none;
    background-color: #eff6ff;
}
.copies-input::-webkit-outer-spin-button,
.copies-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.input-green-highlight {
    border-color: #22c55e !important;
    background-color: #f0fdf4;
}

.w-100 {
    width: 100%;
}

.h-100 {
    height: 100%;
}

.flex-grow-1 {
    flex-grow: 1;
}

.order-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
}

@media (max-width: 900px) {
    .order-layout {
        grid-template-columns: 1fr;
    }
}

.sticky-sidebar {
    position: sticky;
    top: 2rem;
}

.summary-box {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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

.summary-value {
    font-weight: 600;
    color: #111827;
}

.divider {
    height: 1px;
    background-color: #e5e7eb;
    margin: 1rem 0;
}

.total-row {
    margin-bottom: 0;
    margin-top: 0.5rem;
}

.total-row .summary-label {
    font-size: 1.125rem;
    font-weight: 700;
    color: hsl(230, 70%, 50%);
}
.total-row .total-price {
    font-size: 1.5rem;
    font-weight: 800;
    color: hsl(168, 60%, 45%);
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

@media (max-width: 640px) {
    .page-container {
        padding: 1rem;
    }
}

.compact-upload-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.compact-drop-zone {
    border: 2px dashed #d1d5db;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background-color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.compact-drop-zone:hover:not(.disabled) {
    border-color: #2563eb;
    background-color: #f8fafc;
}

.compact-drop-zone.is-dragover {
    border-color: #2563eb;
    background-color: #eff6ff;
}

.compact-upload-text {
    font-size: 0.875rem;
    color: #4b5563;
    font-weight: 600;
    color: #2563eb;
}

.compact-upload-icon {
    width: 1.5rem;
    height: 1.5rem;
    color: #60a5fa;
}


/* Clean & Modern OrderEdit Styles */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

.page-layout, .page-container {
    font-family: 'Outfit', sans-serif;
    background-color: #f8fafc; /* Slate 50 */
    color: #334155; /* Slate 700 */
}
.page-title, .section-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
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
.input-label, .option-label, .header-label {
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
.form-control, .header-input, .copies-input {
    font-family: 'Outfit', sans-serif;
    border: 1px solid #cbd5e1;
    color: #334155;
    background: white;
    border-radius: 0.5rem;
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
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Theme Overrides */
.upload-drop-zone:hover:not(.disabled), .upload-drop-zone.is-dragover {
    border-color: #8b5cf6;
    background-color: #f5f3ff;
}
.upload-icon {
    color: #94a3b8;
}
.upload-drop-zone:hover .upload-icon {
    color: #8b5cf6;
}
.btn-choose-files {
    background: #8b5cf6;
    color: white;
    border: none;
}
.btn-choose-files:hover {
    background: #7c3aed;
}
.doc-icon {
    color: #8b5cf6;
}
.btn-save-comment {
    background: #334155;
}
.btn-save-comment:hover {
    background: #1e293b;
}
.btn-add-config:hover {
    border-color: #8b5cf6;
    color: #8b5cf6;
    background: #f5f3ff;
}
.compact-drop-zone:hover:not(.disabled), .compact-drop-zone.is-dragover {
    border-color: #8b5cf6;
    background-color: #f5f3ff;
}
.compact-upload-text {
    color: #8b5cf6;
}
.compact-upload-icon {
    color: #8b5cf6;
}

.uploading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.8);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(2px);
}
.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}
.uploading-text {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>