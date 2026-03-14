<template>
    <div class="settings-card">
        <h2 class="card-title">Lamination Amount Setting</h2>
        <p class="card-description">Set the per-page cost for lamination. This will be added to the total order cost if lamination is selected.</p>
        
        <div v-if="loading" class="loading-state">Loading...</div>
        <div v-else class="form-container">
            <div class="input-group">
                <label for="lamination-amount" class="input-label">Lamination Amount (per page)</label>
                <input 
                    id="lamination-amount"
                    type="number" 
                    v-model="laminationAmount" 
                    placeholder="e.g., 5.00" 
                    class="input-field"
                    min="0"
                    step="0.01"
                />
            </div>
            <div class="button-group">
                <button @click="updateLaminationAmount" class="btn-primary" :disabled="isUpdating">
                    {{ isUpdating ? 'Saving...' : 'Save Amount' }}
                </button>
                <button @click="deleteLaminationAmount" class="btn-danger" :disabled="isDeleting || laminationAmount === null">
                    {{ isDeleting ? 'Deleting...' : 'Delete Amount' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { CONSTANTS } from '../../constants';

const emit = defineEmits(['update:success', 'update:error']);

const laminationAmount = ref(null);
const loading = ref(true);
const isUpdating = ref(false);
const isDeleting = ref(false);

const getAuthToken = () => {
    return document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
};

const fetchLaminationAmount = async () => {
    loading.value = true;
    try {
        const token = getAuthToken();
        const response = await axios.get('/api/v1/settings/lamination-amount', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        laminationAmount.value = response.data.value;
    } catch (error) {
        console.error('Error fetching lamination amount:', error);
        emit('update:error', 'Failed to fetch lamination amount.');
    } finally {
        loading.value = false;
    }
};

const updateLaminationAmount = async () => {
    if (laminationAmount.value === '' || laminationAmount.value === null || isNaN(parseFloat(laminationAmount.value))) {
        emit('update:error', 'Please enter a valid numeric amount for lamination.');
        return;
    }

    isUpdating.value = true;
    try {
        const token = getAuthToken();
        await axios.post('/api/v1/admin/settings/lamination-amount', 
            { value: laminationAmount.value },
            { headers: { 'Authorization': `Bearer ${token}` } }
        );
        emit('update:success', 'Lamination amount updated successfully.');
    } catch (error) {
        console.error('Error updating lamination amount:', error);
        const errorMessage = error.response?.data?.message || 'Failed to update lamination amount.';
        emit('update:error', errorMessage);
    } finally {
        isUpdating.value = false;
    }
};

const deleteLaminationAmount = async () => {
    isDeleting.value = true;
    try {
        const token = getAuthToken();
        await axios.delete('/api/v1/admin/settings/lamination-amount', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        laminationAmount.value = null;
        emit('update:success', 'Lamination amount deleted successfully.');
    } catch (error) {
        console.error('Error deleting lamination amount:', error);
        emit('update:error', 'Failed to delete lamination amount.');
    } finally {
        isDeleting.value = false;
    }
};

onMounted(fetchLaminationAmount);
</script>

<style scoped>
.settings-card {
    background-color: #ffffff;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-top: 2rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.card-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.form-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.input-group {
    display: flex;
    flex-direction: column;
}

.input-label {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.input-field {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    width: 100%;
    max-width: 300px;
}

.button-group {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
}

.btn-primary, .btn-danger {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.875rem;
    transition: background-color 0.2s;
}

.btn-primary {
    background-color: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background-color: #2563eb;
}

.btn-primary:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
}

.btn-danger {
    background-color: #ef4444;
    color: white;
}

.btn-danger:hover {
    background-color: #dc2626;
}

.btn-danger:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
}

.loading-state {
    color: #6b7280;
    font-style: italic;
}
</style>