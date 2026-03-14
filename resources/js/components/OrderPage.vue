<template>
    <div class="page-layout">
        <AppHeader />

    <div class="page-container">
        <div class="header-row">
                <h2 class="page-title">{{ isEditing ? 'Edit Order #' + route.params.id : 'Create New Order' }}</h2>
        </div>

        <div class="order-layout" :class="{ 'single-column': documents.length === 0 }">
            <div class="main-content">
                <div class="section-card">
            <div class="form-group" style="margin-bottom: 0;" ref="mainUploadSection">
                <label class="input-label">Upload Documents</label>
                <div 
                    class="upload-drop-zone" 
                    :class="{ 'is-dragover': isDragOver, 'disabled': !canAddItems && !loading }"
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

                    <div v-if="loading" class="loading-overlay">
                        <svg class="loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span class="loading-text">Loading, please wait...</span>
                    </div>
                </div>
                <div class="upload-footer">
                    <span class="hint">Supported formats: PDF, DOCX (Max 10 files)</span>
                </div>
            </div>
                </div>
            <div v-if="!canAddItems && !loading" class="message error">
                Cannot add new documents or copies as printing options are not configured.
            </div>

            <transition-group name="fade" tag="div" appear>
                <div v-if="documents.length > 0" key="docs-list" class="document-section">
                    <h3 class="section-title">Selected Documents</h3>
                    <div v-for="(doc, index) in documents" :key="index" class="document-item" :ref="el => setDocumentRef(el, index)">
                        <div class="document-header" @click="toggleDocument(doc)">
                            <div class="toggle-indicator" :class="{ 'is-open': doc.expanded }">
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
                            <div class="input-wrapper" :class="{ 'has-error': doc.errors?.totalPages }">
                                    <input v-if="!isPdf(doc.filename)" type="number" @click.stop
                                           v-model.number="doc.totalPages" 
                                           @input="doc.errors && (doc.errors.totalPages = null)"
                                           :ref="el => setTotalPagesRef(el, index)"
                                           min="1" class="header-input"
                                           :class="{ 'input-green-highlight': !doc.totalPages || doc.totalPages === '' }" title="Total Pages of the Document">
                                    <span v-else class="static-page-count">{{ doc.totalPages }}</span>
                                    <div v-if="doc.errors?.totalPages" class="error-tooltip">{{ doc.errors.totalPages }}</div>
                                </div>
                            </div>
                            <button @click.stop="removeDocument(index)"
                                class="btn-remove" title="Remove Document">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                        <div v-show="doc.expanded" class="document-body">
                        <div v-for="(config, configIndex) in doc.configurations" :key="configIndex" class="configuration-item" :class="{ 'has-remove-btn': doc.configurations.length > 1 }">
                            <div class="config-actions">
                                <button v-if="doc.configurations.length > 1" @click="removeConfiguration(index, configIndex)" class="btn-remove-config" title="Remove Copy">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <div class="document-options">
                                <div class="option-group group-paper">
                                    <label class="option-label">Paper Size</label>
                                    <select v-model="config.paper_size_id" class="form-control">
                                        <option v-for="size in activePaperSizes" :key="size.id" :value="size.id">
                                            {{ size.value }}
                                        </option>
                                    </select>
                                </div>
                                <div class="option-group group-lamination" v-if="isLaminationAvailable(config.paper_size_id)">
                                    <label class="option-label">Lamination</label>
                                    <label class="checkbox-container">
                                        <input type="checkbox" v-model="config.lamination">
                                        <span class="checkbox-text">Laminate (+₹{{ getLaminationAmount(config.paper_size_id) }}/pg)</span>
                                    </label>
                                </div>
                                <div class="option-group group-mode">
                                    <label class="option-label">Mode</label>
                                    <select v-model="config.mode_id" class="form-control">
                                        <option v-for="mode in activePrintModes" :key="mode.id" :value="mode.id">
                                            {{ mode.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="option-group group-orientation">
                                    <label class="option-label">Orientation</label>
                                    <div class="custom-dropdown" @click="config.showOrientationDropdown = !config.showOrientationDropdown">
                                        <div class="selected-option">
                                            <span v-if="getOrientationSvg(config.orientation_id)" v-html="getOrientationSvg(config.orientation_id)" class="icon-wrapper"></span>
                                            <span>{{ getOrientationName(config.orientation_id) }}</span>
                                            <span class="dropdown-arrow">▼</span>
                                        </div>
                                        <ul v-if="config.showOrientationDropdown" class="dropdown-list">
                                            <li v-for="orientation in printOrientations" :key="orientation.id" @click.stop="selectOrientation(config, orientation.id)" class="dropdown-item">
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
                                                   :name="'pages-' + index + '-' + configIndex"
                                                   :checked="config.pages === 'All'"
                                                   @change="setPagesAll(config)">
                                            <span style="font-size: 0.875rem; white-space: nowrap;">All Pages</span>
                                        </label>
                                        <label class="radio-label" style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer;">
                                            <input type="radio" 
                                                   :name="'pages-' + index + '-' + configIndex"
                                                   :checked="config.pages !== 'All'"
                                                   @change="setPagesCustom(config)">
                                            <span style="font-size: 0.875rem; white-space: nowrap;">Custom</span>
                                        </label>
                                        <div style="flex: 1; min-width: 120px;" class="input-wrapper" :class="{ 'has-error': config.errors?.pages }">
                                            <input :value="config.pages === 'All' ? '' : config.pages" 
                                                   @input="updateCustomPages(config, $event.target.value)" 
                                                   type="text" 
                                                   :ref="el => setCustomPagesRef(el, index, configIndex)"
                                                   class="form-control" 
                                                   placeholder="e.g. 1-5, 8"
                                                   :disabled="config.pages === 'All'">
                                            <div v-if="config.errors?.pages" class="error-tooltip">{{ config.errors.pages }}</div>
                                        </div>
                                    </div>
                                    <span class="page-count-hint" style="display: block;">Format: 1,2,3 or 1-2</span>
                                </div>
                                <div class="option-group group-copies">
                                    <label class="option-label">Number of Copies</label>
                                    <div class="copies-control">
                                        <button type="button" class="btn-copy-control" @click="updateCopies(config, -1)" :disabled="(config.number_of_copies || 1) <= 1">-</button>
                                        <input type="number" v-model.number="config.number_of_copies" class="copies-input" min="1">
                                        <button type="button" class="btn-copy-control" @click="updateCopies(config, 1)">+</button>
                                    </div>
                                </div>
                                <div class="option-group group-comment">
                                    <label class="option-label">Configuration Comment</label>
                                    <input type="text" v-model="config.comment" class="form-control" placeholder="Optional instruction for this copy">
                                </div>
                                <div class="option-group group-total-print-pages">
                                    <label class="option-label">Total Pages to Print</label>
                                    <div class="static-value">{{ calculateTotalPrintPages(config.pages, doc.totalPages, config.number_of_copies) }}</div>
                                </div>
                                <div class="option-group group-price">
                                    <label class="option-label">Price</label>
                                    <div class="price-value">
                                        ₹{{ getConfigurationPrice(config, doc).toFixed(2) }}
                                        <span class="per-page-price"> / ₹{{ getPerPagePrice(config, doc) }} Per Page</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="add-config-container">
                            <button @click="addConfiguration(index)" class="btn-add-config" :disabled="!canAddItems">Add another copy</button>
                        </div>
                        </div>
                    </div>
                </div>
            </transition-group>

            <div v-if="documents.length > 0" class="section-card">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="input-label">Order Comment (Optional)</label>
                <textarea v-model="comment" class="form-control" rows="3" placeholder="Any special instructions for this order..."></textarea>
            </div>
            </div>

            <div v-if="remark" class="section-card admin-info-card">
                <div class="info-group">
                    <label class="info-label">Admin Remark</label>
                    <div class="info-value remark-text">{{ remark }}</div>
                </div>
            </div>

            <div v-if="documents.length > 0" class="actions">
                <button
                    class="btn-submit"
                    type="button" @click="submitOrder" :disabled="loading">
                    <span v-if="loading" class="flex items-center">
                        <svg class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        Submitting...
                    </span>
                    <span v-else>{{ isEditing ? 'Update Order' : 'Submit Order' }}</span>
                </button>
            </div>
            <transition name="fade" appear>
                <p v-if="message" :class="['message', isError ? 'error' : 'success']">
                    {{ message }}
                </p>
            </transition>
            </div>

            <div class="sidebar-content" v-if="documents.length > 0">
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
                        <span class="summary-label">Total Documents</span>
                        <span class="summary-value">{{ documents.length }}</span>
                    </div>

                    <div v-if="additionalCharge > 0" class="summary-row">
                        <span class="summary-label">Additional Charge</span>
                        <span class="summary-value">₹{{ additionalCharge.toFixed(2) }}</span>
                    </div>

                    <div class="divider"></div>

                    <div class="summary-row total-row">
                        <span class="summary-label">Total Price</span>
                        <span class="summary-value total-price">₹{{ totalOrderPrice.toFixed(2) }}</span>
                    </div>
                </div>
            </div>
            </div>
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

function selectOrientation(config, id) {
    config.orientation_id = id;
    config.showOrientationDropdown = false;
}

function setPagesAll(config) {
    if (config.pages !== 'All') {
        config.customPageRange = config.pages;
    }
    config.pages = 'All';
    if (config.errors && config.errors.pages) {
        config.errors.pages = null;
    }
}

function setPagesCustom(config) {
    config.pages = config.customPageRange || '';
}

function updateCustomPages(config, value) {
    config.pages = value;
    config.customPageRange = value;
    if (config.errors && config.errors.pages) {
        config.errors.pages = null;
    }
}

const getPerPagePrice = (config, doc) => {
    const totalPrice = getConfigurationPrice(config, doc);
    const copies = config.number_of_copies || 1;
    const pages = doc.totalPages || 1;
    return (totalPrice / copies / pages).toFixed(2);
};

const updateCopies = (config, change) => {
    const newValue = (config.number_of_copies || 1) + change;
    if (newValue < 1) return;
    config.number_of_copies = newValue;
};

const toggleDocument = (doc) => {
    doc.expanded = !doc.expanded;
};

import { ref, onMounted, computed, watch, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { getPrintOrientations } from '../utils/printOrientationUtils';
import AppHeader from './AppHeader.vue';
import { CONSTANTS } from '../constants';
import { calculateNumberOfPages as calcPages, getConfigurationPrice as calculateConfigPrice } from '../utils/orderUtils';
import { isValidPageFormat, isPdf, validatePageCount } from '../utils/validation';
import { calculateTotalPrintPages } from '../utils/printCalculationUtils';
import { validateFileSizes, isValidFileType, validateFileNameLength } from '../utils/fileValidation';
import { getPageCount, toBase64 } from '../utils/fileProcessing';
import { toast } from 'vue3-toastify';
import { validateSafeFileName } from '../utils/fileNameSecurity';
import 'vue3-toastify/dist/index.css';
import { truncateFileName } from '../utils/stringUtils';

const documents = ref([]);
const loading = ref(false);
const message = ref('');
const isError = ref(false);
const paperSizes = ref([]);
const printModes = ref([]);
const printOrientations = ref([]);
const printPrices = ref([]);
const route = useRoute();
const router = useRouter();
const additionalCharge = ref(0);
const remark = ref('');
const comment = ref('');
const isDragOver = ref(false);
const fileInput = ref(null);
const mainUploadSection = ref(null);
const showSidebarUpload = ref(false);
const sidebarFileInput = ref(null);
const isSidebarDragOver = ref(false);

let errorTimeout = null;

const displayError = (msg) => {
    message.value = msg;
    isError.value = true;
    if (errorTimeout) clearTimeout(errorTimeout);
    errorTimeout = setTimeout(() => {
        message.value = '';
        isError.value = false;
    }, 10000);
};

const totalPagesRefs = ref({});
const customPagesRefs = ref({});
const documentRefs = ref({});

const setTotalPagesRef = (el, index) => {
    if (el) totalPagesRefs.value[index] = el;
};

const setCustomPagesRef = (el, docIndex, configIndex) => {
    if (el) {
        if (!customPagesRefs.value[docIndex]) customPagesRefs.value[docIndex] = {};
        customPagesRefs.value[docIndex][configIndex] = el;
    }
};

const setDocumentRef = (el, index) => {
    if (el) documentRefs.value[index] = el;
};

const isEditing = computed(() => route.name === 'edit-order');

const activePrintModes = computed(() => printModes.value.filter(m => m.status === 'active'));
const activePaperSizes = computed(() => paperSizes.value.filter(s => s.status === 'active'));

const canAddItems = computed(() => {
    return !loading.value && printOrientations.value.length > 0 && activePrintModes.value.length > 0 &&
        activePaperSizes.value.length > 0;
});

const calculateNumberOfPages = (pagesString, totalPages) => {
    return calcPages(pagesString, typeof totalPages === 'number' ? totalPages : 1);
};

const getConfigurationPrice = (config, doc) => {
    const mode = printModes.value.find(m => m.id === config.mode_id);
    const size = paperSizes.value.find(s => s.id == config.paper_size_id);
    const configForPrice = { ...config, mode: mode?.value, size: size?.value };

    return calculateConfigPrice(
        configForPrice,
        doc.totalPages,
        printPrices.value,
        printModes.value,
        paperSizes.value
    ) * (config.number_of_copies || 1);
};

const isLaminationAvailable = (paperSizeId) => {
    const size = activePaperSizes.value.find(s => s.id == paperSizeId);
    return size && size.lamination_amount != null && size.lamination_amount !== '';
};

const getLaminationAmount = (paperSizeId) => {
    const size = activePaperSizes.value.find(s => s.id == paperSizeId);
    return size ? size.lamination_amount : 0;
};

const totalOrderPrice = computed(() => {
    const docsTotal = documents.value.reduce((total, doc) => {
        const docTotal = doc.configurations.reduce((configTotal, config) => configTotal + getConfigurationPrice(config, doc), 0);
        return total + docTotal;
    }, 0);
    return docsTotal + additionalCharge.value;
});


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
    if (errorTimeout) {
        clearTimeout(errorTimeout);
    }
});

const processFiles = async (fileList) => {
    message.value = '';
    isError.value = false;
    const files = Array.from(fileList);

    const validFiles = files.filter(file => isValidFileType(file));

    if (validFiles.length < files.length) {
        toast.error('Some files were ignored. Only Images, PDF, DOC, and DOCX are allowed.');
        return;
        // displayError('Some files were ignored. Only Images, PDF, DOC, and DOCX are allowed.');
    }

    if (documents.value.length + validFiles.length > 10) {
        toast.error('You can upload a maximum of 10 documents.');
        return;
    }

    let currentTotalSize = documents.value.reduce((sum, doc) => sum + (doc.fileSize || 0), 0);

    const sizeValidation = validateFileSizes(validFiles, currentTotalSize);
    if (!sizeValidation.valid) {
        toast.error(sizeValidation.message);
        return;
    }

    const nameValidation = validateFileNameLength(validFiles);
    if (!nameValidation.valid) {
        toast.error(nameValidation.message);
        
        return;
    }

    if (validFiles.length > 0) {
        documents.value.forEach(doc => doc.expanded = false);
    }

    for (const file of validFiles) {
        const safeNameValidation = validateSafeFileName(file.name);
        if (!safeNameValidation.isValid) {
            toast.error(safeNameValidation.error);
            continue;
        }

        const pageCount = await getPageCount(file);
        const base64 = await toBase64(file);
        const isImage = file.type.startsWith('image/');
        const isManual = pageCount === 'All' || isImage;
        const defaultMode = activePrintModes.value[0];
        const defaultSize = activePaperSizes.value.find(s => s.value === 'A4' || s.code === 'A4') || activePaperSizes.value[0];
        const defaultOrientation = printOrientations.value[0];
        
        const maxSerial = documents.value.length > 0 
             ? Math.max(...documents.value.map(d => d.serial_number || 0)) 
             : 0;

        documents.value.push({
            filename: file.name,
            file_base64: base64,
            fileSize: file.size,
            serial_number: maxSerial + 1,
            mimeType: file.type,
            totalPages: isManual ? '' : pageCount,
            isManualPageCount: isManual,
            expanded: true,
            errors: {},
            configurations: [{
                orientation_id: defaultOrientation?.id,
                mode_id: defaultMode?.id,
                pages: 'All',
                paper_size_id: defaultSize?.id,
                number_of_copies: 1,
                lamination: false,
                comment: '',
                errors: {},
            }]
        });

        await nextTick();
        const newIndex = documents.value.length - 1;
        if (documentRefs.value[newIndex]) {
            documentRefs.value[newIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};

const handleFileUpload = async (event) => {
    await processFiles(event.target.files);
    event.target.value = ''; // Reset input
};

const addConfiguration = (docIndex) => {
    const defaultMode = activePrintModes.value[0];
    const defaultSize = activePaperSizes.value.find(s => s.value === 'A4' || s.code === 'A4') || activePaperSizes.value[0];
    const defaultOrientation = printOrientations.value[0];
    documents.value[docIndex].configurations.push({
        mode_id: defaultMode?.id,
        orientation_id: defaultOrientation?.id,
        pages: 'All',
        paper_size_id: defaultSize?.id,
        number_of_copies: 1,
        lamination: false,
        comment: '',
        errors: {},
    });
};

const removeConfiguration = (docIndex, configIndex) => {
    documents.value[docIndex].configurations.splice(configIndex, 1);
};

const removeDocument = (index) => {
    documents.value.splice(index, 1);
    documents.value.forEach((doc, i) => {
        doc.serial_number = i + 1;
    });
};

onMounted(async () => {
    loading.value = true;
    try {
        const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
        const config = {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        };

        const [paperSizeResponse, printModeResponse, printPriceResponse, laminationResponse] = await Promise.all([
            axios.get('/api/v1/paper-sizes', config),
            axios.get('/api/v1/print-modes', config),
            axios.get('/api/v1/print-prices', config),
            axios.get('/api/v1/settings/lamination-amount', config)
        ]);

        const laminationAmount = laminationResponse.data.value;
        paperSizes.value = paperSizeResponse.data.map(size => {
            return {
                ...size,
                lamination_amount: laminationAmount
            };
        });

        printModes.value = printModeResponse.data;
        printPrices.value = printPriceResponse.data;

        // Use local utility for print orientations
        printOrientations.value = await getPrintOrientations();
    } catch (error) {
        console.error('Failed to fetch page options:', error);
        displayError('Could not load page options. Please try again later.');
    }

    await loadOrderData();

    if (!isEditing.value) {
        loading.value = false;
    }
});

const loadOrderData = async () => {
    if (isEditing.value) {
        loading.value = true;
        try {
            const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
            const config = {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
            };
            const response = await axios.get(`/api/v1/orders/${route.params.id}`, config);
            const order = response.data;

            additionalCharge.value = parseFloat(order.additional_charge || 0);
            remark.value = order.remark || '';
            comment.value = order.comment || '';
            documents.value = [];
            // Map existing documents to component state
            for (const [filename, docData] of Object.entries(order.documents)) {
                documents.value.push({
                    filename: filename,
                    file_base64: null, // No base64 for existing files
                    storage_path: docData.storage_path,
                    serial_number: docData.serial_number,
                    expanded: false,
                    totalPages: (docData.total_pages && docData.total_pages !== 'All') ? Number(docData.total_pages) : 1,
                    isManualPageCount: !docData.total_pages || docData.total_pages === 'All',
                    errors: {},
                    configurations: Object.values(docData.copies).map(copy => ({
                        orientation_id: copy.orientation_id,
                        mode_id: copy.mode_id,
                        pages: copy.pages,
                        paper_size_id: copy.paper_size_id,
                        number_of_copies: copy.number_of_copies || 1,
                        lamination: !!copy.lamination,
                        comment: copy.comment || '',
                        price: parseFloat(copy.totalPrice),
                        errors: {}
                    }))
                });
            }
            documents.value.sort((a, b) => (a.serial_number || 0) - (b.serial_number || 0));
        } catch (error) {
            console.error('Failed to load order:', error);
            displayError('Failed to load order details.');
        } finally {
            loading.value = false;
        }
    }
};

watch(() => route.params.id, loadOrderData);

const submitOrder = async () => {
    loading.value = true;
    message.value = '';
    isError.value = false;
    let hasErrors = false;
    let firstErrorField = null;

    // Clear previous errors
    documents.value.forEach(doc => {
        if (!doc.errors) doc.errors = {};
        else doc.errors = {};
        
        doc.configurations.forEach(config => {
            if (!config.errors) config.errors = {};
            else config.errors = {};
        });
    });

    // Validate pages format for all configurations
    for (const [index, doc] of documents.value.entries()) {
        if (!isPdf(doc.filename) && (!doc.totalPages || doc.totalPages < 1)) {
            doc.errors.totalPages = 'Total pages of the document is required';
            hasErrors = true;
            doc.expanded = true;
            if (!firstErrorField) firstErrorField = totalPagesRefs.value[index];
        }

        for (const [configIndex, config] of doc.configurations.entries()) {
            if (!isValidPageFormat(config.pages)) {
                config.errors.pages = 'Invalid format';
                hasErrors = true;
                doc.expanded = true;
                if (!firstErrorField) firstErrorField = customPagesRefs.value[index]?.[configIndex];
            } else {
                const pagesCount = calcPages(config.pages, doc.totalPages || 1);
                if (!validatePageCount(doc.totalPages || 1, pagesCount)) {
                    config.errors.pages = `Exceeds total pages (${doc.totalPages})`;
                    hasErrors = true;
                    doc.expanded = true;
                    if (!firstErrorField) firstErrorField = customPagesRefs.value[index]?.[configIndex];
                }
            }
        }
    }

    if (hasErrors) {
        loading.value = false;
        displayError('Please correct the errors highlighted above.');
        if (firstErrorField) {
            nextTick(() => {
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }
        return;
    }
    
    try {
        const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`,
                'X-CSRF-TOKEN': csrfToken || ''
            }
        };

        const payloadDocuments = documents.value.reduce((acc, doc) => {
            const configurations = doc.configurations.map(cfg => ({
                ...cfg,
                price: getConfigurationPrice(cfg, doc)
            }));

            const docPrice = configurations.reduce((sum, cfg) => sum + cfg.price, 0);

            const docData = {
                ...doc,
                totalPages: doc.totalPages,
                price: docPrice,
                configurations
            };

            acc[doc.filename] = docData;
            return acc;
        }, {});

        const payload = { documents: payloadDocuments, total_price: totalOrderPrice.value, comment: comment.value };
        
        let createdOrderId = null;
        if (isEditing.value) {
            await axios.put(`/api/v1/orders/${route.params.id}`, payload, config);
            message.value = 'Order updated successfully!';
        } else {
            const response = await axios.post('/api/v1/orders', payload, config);
            message.value = 'Order created successfully!';
            createdOrderId = response.data.order ? response.data.order.id : response.data.id;
        }
        
        documents.value = [];
        if (isEditing.value) {
            setTimeout(() => router.push('/'), 1500);
        } else if (createdOrderId) {
            setTimeout(() => router.push({ name: 'user-order-payment', params: { orderId: createdOrderId } }), 1500);
        }
    } catch (error) {
        displayError(error.response?.data?.message || error.response?.data?.error || (isEditing.value ? 'Failed to update order.' : 'Failed to create order.'));
        console.error(error);
    } finally {
        loading.value = false;
    }
};
// ...existing code...
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
}
.selected-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0 0.75rem;
    height: 100%;
    color: #334155;
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
.dropdown-arrow {
    margin-left: auto;
    font-size: 0.75rem;
    color: #6b7280;
}
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.page-layout {
    min-height: 100vh;
    background-color: #eff6ff;
    display: flex;
    flex-direction: column;
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
    font-weight: 800;
    color: #1d4ed8;
}

.btn-home {
    position: relative;
    z-index: 10;
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

.form-group {
    margin-bottom: 2rem;
}

.input-label {
    display: block;
    color: #1d4ed8;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.hint {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: normal;
}

.document-section {
    margin-bottom: 2rem;
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
    gap: 0.5rem;
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
    width: 1.5rem;
    height: 1.5rem;
    color: #3b82f6;
}

.filename {
    font-size: 1.125rem;
    font-weight: 700;
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

.manual-page-count {
    padding: 0 1rem 1rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 0.5rem;
    max-width: 200px;
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

.option-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #1d4ed8;
}

.page-count-hint {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
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

.configuration-item.has-remove-btn {
    padding-top: 2.5rem;
}

.config-actions {
    position: absolute;
    top: 0.5rem;
    right: 0;
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

.price-breakdown {
    font-size: 0.75rem;
    color: #6b7280;
    margin-left: 0.5rem;
}

.admin-info-card {
    background-color: #fffbeb;
    border-color: #fcd34d;
    padding: 1rem;
}

.info-group {
    margin-bottom: 0.75rem;
}

.info-group:last-child {
    margin-bottom: 0;
}

.info-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
}

.info-value {
    color: #334155;
    font-size: 1rem;
}

.remark-text {
    white-space: pre-wrap;
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

.actions {
    display: flex;
    justify-content: center;
    margin-top: 1.5rem;
}

.btn-submit {
    width: 100%;
    background-color: #2563eb;
    color: white;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (min-width: 768px) {
    .btn-submit {
        width: auto;
    }
}

.btn-submit:hover {
    background-color: #1d4ed8;
}

.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.spinner {
    animation: spin 1s linear infinite;
    height: 1.25rem;
    width: 1.25rem;
    margin-right: 0.5rem;
    color: white;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.message {
    margin-top: 1.5rem;
    text-align: center;
    font-weight: 500;
}

.error {
    color: #dc2626;
}

.success {
    color: #16a34a;
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
    background-color: #f1f5f9;
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

/* Layout & Summary Styles */
.order-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
}

.order-layout.single-column {
    grid-template-columns: 1fr;
}

.section-card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e5e7eb;
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
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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

.total-row .total-price {
    font-size: 1.5rem;
    font-weight: 800;
    color: #059669;
}

@media (max-width: 900px) {
    .order-layout {
        grid-template-columns: 1fr;
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

/* Loading Overlay */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    backdrop-filter: blur(2px);
}
.loading-spinner {
    animation: spin 1s linear infinite;
    height: 3rem;
    width: 3rem;
    color: #8b5cf6;
    margin-bottom: 1rem;
}
.loading-text {
    font-family: 'Outfit', sans-serif;
    font-size: 1.125rem;
    font-weight: 500;
    color: #64748b;
}

/* Clean & Modern OrderPage Styles */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

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
    font-weight: 800;
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
.doc-icon {
    color: #8b5cf6;
}
.btn-submit {
    background: #8b5cf6;
}
.btn-submit:hover {
    background: #7c3aed;
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

.input-wrapper {
    position: relative;
}

.has-error input {
    border-color: #dc2626 !important;
    background-color: #fef2f2 !important;
}

.error-tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #dc2626;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 10;
    margin-bottom: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.error-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: #dc2626 transparent transparent transparent;
}

/* Theme Overrides to match OrderEdit */
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
</style>