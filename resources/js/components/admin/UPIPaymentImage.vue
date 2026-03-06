<template>
    <div class="settings-section">
        <h2 class="section-title">UPI Configuration</h2>
        
        <div class="upi-card">
            <h3 class="subsection-title">UPI ID</h3>
            <div v-if="loadingUpiId" class="loading-text">Loading...</div>
            <div v-else class="input-group">
                <input type="text" v-model="upiId" class="form-control" placeholder="Enter UPI ID (e.g. username@bank)">
                <button @click="updateUpiId" :disabled="updatingUpiId" class="btn-primary">
                    {{ updatingUpiId ? 'Saving...' : 'Save' }}
                </button>
                <button v-if="upiId" @click="deleteUpiId" :disabled="deletingUpiId" class="btn-delete">
                    {{ deletingUpiId ? 'Deleting...' : 'Delete' }}
                </button>
            </div>
        </div>

        <div class="upi-card mt-4">
            <h3 class="subsection-title">QR Code Image</h3>
            <div v-if="loadingUPIImage" class="loading-text">Loading...</div>
            <div v-else>
                <div v-if="upiImagePath" class="upi-preview">
                    <img :src="upiImagePath" alt="UPI Admin Image" class="upi-image">
                    <button @click="deleteUPIImage" :disabled="deletingUPI" class="btn-delete-image">
                        {{ deletingUPI ? 'Deleting...' : 'Delete Image' }}
                    </button>
                </div>
                <div v-else class="no-image">
                    <p>No UPI image uploaded yet</p>
                </div>
                
                <div class="upload-section">
                    <label class="file-input-label">
                        <input 
                            type="file" 
                            @change="handleUPIImageUpload" 
                            accept="image/*"
                            :disabled="uploadingUPI"
                            class="file-input"
                        >
                        <span class="btn-upload">{{ uploadingUPI ? 'Uploading...' : 'Choose Image' }}</span>
                    </label>
                    <small class="file-hint">Max 5MB. Accepted formats: JPEG, PNG, JPG, GIF</small>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, defineEmits } from 'vue'
import axios from 'axios'
import { CONSTANTS } from '../../constants'
import { showSuccess, showError } from '../../utils/toast'

const upiImagePath = ref('')
const uploadingUPI = ref(false)
const deletingUPI = ref(false)
const upiId = ref('')
const loadingUpiId = ref(false)
const loadingUPIImage = ref(false)
const updatingUpiId = ref(false)
const deletingUpiId = ref(false)

const emit = defineEmits(['update:success', 'update:error'])

const fetchUpiId = async () => {
    loadingUpiId.value = true
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1]
        if (!token) return
        const response = await axios.get('/api/v1/admin/settings/upi-id', {
            headers: { Authorization: `Bearer ${token}` }
        })
        upiId.value = response.data.upi_id || ''
    } catch (error) {
        console.error('Error fetching UPI ID:', error)
    } finally {
        loadingUpiId.value = false
    }
}

const updateUpiId = async () => {
    if (!upiId.value) return showError('Please enter a UPI ID')
    
    updatingUpiId.value = true
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1]
        if (!token) return

        await axios.post('/api/v1/admin/settings/upi-id', { upi_id: upiId.value }, {
            headers: { Authorization: `Bearer ${token}` }
        })
        showSuccess('UPI ID updated successfully')
    } catch (error) {
        console.error('Error updating UPI ID:', error)
        showError(error.response?.data?.message || 'Failed to update UPI ID')
    } finally {
        updatingUpiId.value = false
    }
}

const deleteUpiId = async () => {
    if (!confirm('Are you sure you want to delete the UPI ID?')) return

    deletingUpiId.value = true
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1]
        if (!token) return

        await axios.delete('/api/v1/admin/settings/upi-id', {
            headers: { Authorization: `Bearer ${token}` }
        })
        upiId.value = ''
        showSuccess('UPI ID deleted successfully')
    } catch (error) {
        console.error('Error deleting UPI ID:', error)
        showError(error.response?.data?.message || 'Failed to delete UPI ID')
    } finally {
        deletingUpiId.value = false
    }
}

const fetchUPIImage = async () => {
    loadingUPIImage.value = true
    try {
        const response = await axios.get('/api/v1/admin/upi/image')
        upiImagePath.value = response.data.path
    } catch (error) {
        console.error('Error fetching UPI image:', error)
        upiImagePath.value = ''
    } finally {
        loadingUPIImage.value = false
    }
}

const handleUPIImageUpload = async (event) => {
    const file = event.target.files?.[0]
    if (!file) return

    uploadingUPI.value = true
    const formData = new FormData()
    formData.append('image', file)

    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1]
        if (!token) return

        const response = await axios.post('/api/v1/admin/upi/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                Authorization: `Bearer ${token}`
            }
        })
        upiImagePath.value = response.data.path
        showSuccess('UPI image uploaded successfully')
    } catch (error) {
        console.error('Error uploading UPI image:', error)
        const errorMsg = error.response?.data?.message || 'Failed to upload UPI image'
        showError(errorMsg)
    } finally {
        uploadingUPI.value = false
    }
}

const deleteUPIImage = async () => {
    if (!confirm('Are you sure you want to delete the UPI image?')) return

    deletingUPI.value = true
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1]
        if (!token) return

        await axios.delete('/api/v1/admin/upi/image', {
            headers: {
                Authorization: `Bearer ${token}`
            }
        })
        upiImagePath.value = ''
        showSuccess('UPI image deleted successfully')
    } catch (error) {
        console.error('Error deleting UPI image:', error)
        showError(error.response?.data?.message || 'Failed to delete UPI image')
    } finally {
        deletingUPI.value = false
    }
}

onMounted(() => {
    fetchUPIImage()
    fetchUpiId()
})
</script>

<style scoped>
.settings-section {
    margin-bottom: 3rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1.5rem;
}

.subsection-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 1rem;
}

.upi-card {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    background-color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.mt-4 {
    margin-top: 1rem;
}

.upi-preview {
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.upi-image {
    max-width: 300px;
    max-height: 300px;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
}

.no-image {
    padding: 2rem;
    text-align: center;
    color: #9ca3af;
    background-color: #f9fafb;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.upload-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.file-input-label {
    display: inline-block;
    cursor: pointer;
}

.file-input {
    display: none;
}

.btn-upload {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background-color: #3b82f6;
    color: white;
    border-radius: 0.375rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    text-align: center;
}

.btn-upload:hover {
    background-color: #2563eb;
}

.file-input:disabled + .btn-upload {
    background-color: #9ca3af;
    cursor: not-allowed;
}

.file-hint {
    font-size: 0.75rem;
    color: #6b7280;
}

.btn-delete-image {
    padding: 0.5rem 1rem;
    background-color: #ef4444;
    color: white;
    border: none;
    border-radius: 0.375rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-delete-image:hover:not(:disabled) {
    background-color: #dc2626;
}

.btn-delete-image:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
}

.input-group {
    display: flex;
    gap: 1rem;
    max-width: 500px;
}

.form-control {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
}

.btn-primary {
    padding: 0.5rem 1rem;
    background-color: #3b82f6;
    color: white;
    border: none;
    border-radius: 0.375rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-primary:hover:not(:disabled) {
    background-color: #2563eb;
}

.btn-delete {
    padding: 0.5rem 1rem;
    background-color: #ef4444;
    color: white;
    border: none;
    border-radius: 0.375rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-delete:hover:not(:disabled) {
    background-color: #dc2626;
}

.btn-delete:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
}

.loading-text {
    color: #6b7280;
    padding: 0.5rem 0;
}
</style>
