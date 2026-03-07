<template>
    <div class="admin-settings-container">
        <AdminHeader />

        <div v-if="showLaminationSettings">
            <LaminationSettings
                @update:success="handleSuccess"
                @update:error="handleError"
            />
        </div>
        <div v-else>
            <PrintPrices 
                :key="printPricesKey"
                @update:success="handleSuccess"
                @update:error="handleError"
            />
    
            <PrintModes 
                @update:success="handleConfigUpdate"
                @update:error="handleError"
            />
    
            <PaperSizes 
                @update:success="handleConfigUpdate"
                @update:error="handleError"
            />
    
            <!-- UPI Payment Image Component -->
            <UPIPaymentImage 
                @update:success="handleSuccess"
                @update:error="handleError"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import PrintModes from './PrintModes.vue'
import PaperSizes from './PaperSizes.vue'
import PrintPrices from './PrintPrices.vue'
import UPIPaymentImage from './UPIPaymentImage.vue'
import LaminationSettings from './LaminationSettings.vue'
import { showSuccess, showError } from '../../utils/toast'
import AdminHeader from './AdminHeader.vue';

const route = useRoute()
const printPricesKey = ref(0)

const showLaminationSettings = computed(() => route.query.lamination === 'true')

const handleSuccess = (message) => {
    showSuccess(message)
}

const handleConfigUpdate = (message) => {
    showSuccess(message)
    printPricesKey.value++
}

const handleError = (message) => {
    showError(message)
}
</script>

<style scoped>
.admin-settings-container {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: bold;
    color: #1f2937;
    margin: 0;
}

.btn-back {
    padding: 0.5rem 1rem;
    background-color: #6b7280;
    color: white;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.875rem;
}

.btn-back:hover {
    background-color: #4b5563;
}
</style>
