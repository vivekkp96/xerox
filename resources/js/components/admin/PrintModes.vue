<template>
    <div class="settings-section">
        <h2 class="section-title">Manage Print Modes</h2>
        <div class="add-item-form">
            <input v-model="newMode.name" placeholder="Name (e.g., Color)" class="form-input">
            <input v-model="newMode.value" placeholder="Value (e.g., Color)" class="form-input">
            <select v-model="newMode.status" class="form-input">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button @click="addPrintMode" class="btn-add">Add Mode</button>
        </div>

        <div v-if="loading" class="loading-state">Loading...</div>
        <div v-else class="items-grid">
            <div v-for="mode in printModes" :key="mode.id" class="settings-card">
                <input v-model="mode.name" class="form-input mb-2" placeholder="Name" />
                <input v-model="mode.value" class="form-input mb-2" placeholder="Value" />
                <div class="form-group">
                    <label>Status</label>
                    <select v-model="mode.status" class="form-input">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button @click="updatePrintMode(mode)" class="btn-save">Save</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { CONSTANTS } from '../../constants';

const emit = defineEmits(['update:success', 'update:error']);

const printModes = ref([]);
const loading = ref(false);
const newMode = ref({ name: '', value: '', status: 'active' });

const getToken = () => document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];

const fetchPrintModes = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/print-modes');
        printModes.value = response.data;
    } catch (error) {
        emit('update:error', 'Failed to load print modes.');
    } finally {
        loading.value = false;
    }
};

const addPrintMode = async () => {
    try {
        const token = getToken();
        if (!token) return;
        await axios.post('/api/v1/admin/print-modes', newMode.value, {
            headers: { Authorization: `Bearer ${token}` }
        });
        newMode.value = { name: '', value: '', status: 'active' };
        fetchPrintModes();
        emit('update:success', 'Print mode added successfully.');
    } catch (error) {
        emit('update:error', error.response?.data?.message || 'Failed to add print mode.');
    }
};

const updatePrintMode = async (mode) => {
    try {
        const token = getToken();
        if (!token) return;
        await axios.patch(`/api/v1/admin/print-modes/${mode.id}`, { name: mode.name, value: mode.value, status: mode.status }, {
            headers: { Authorization: `Bearer ${token}` }
        });
        emit('update:success', 'Print mode updated successfully.');
    } catch (error) {
        emit('update:error', error.response?.data?.message || 'Failed to update print mode.');
    }
};

onMounted(fetchPrintModes);
</script>

<style scoped>
.settings-section { margin-bottom: 2rem; }
.section-title { font-size: 1.5rem; font-weight: 600; color: #1f2937; margin-bottom: 1rem; }
.add-item-form { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; background-color: white; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; }
.items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
.settings-card { border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; background-color: white; }
.form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; box-sizing: border-box; }
.mb-2 { margin-bottom: 0.5rem; }
.form-group { margin-bottom: 1rem; }
.btn-add, .btn-save { padding: 0.5rem 1rem; color: white; border: none; border-radius: 0.375rem; cursor: pointer; }
.btn-add { background-color: #3b82f6; height: fit-content; }
.btn-save { background-color: #10b981; width: 100%; margin-top: 1rem; }
</style>