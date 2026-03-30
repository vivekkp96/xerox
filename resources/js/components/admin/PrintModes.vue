<template>
    <div class="settings-section">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Manage Print Modes</h2>
            </div>
            <div class="card-body">
                <div class="add-form">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input v-model="newMode.name" placeholder="e.g., Color" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Value</label>
                        <input v-model="newMode.value" placeholder="e.g., Color" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select v-model="newMode.status" class="form-input">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button @click="addPrintMode" class="btn btn-primary">Add Mode</button>
                </div>

                <div v-if="loading" class="loading-state">Loading...</div>
                <div v-else class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Value</th>
                                <th>Status</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="mode in printModes" :key="mode.id">
                                <td>{{ mode.name }}</td>
                                <td>{{ mode.value }}</td>
                                <td>
                                    <span :class="['status-badge', mode.status]">{{ mode.status === 'active' ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td><button @click="openEditModal(mode)" class="btn btn-secondary w-full">Edit</button></td>
                            </tr>
                            <tr v-if="printModes.length === 0">
                                <td colspan="4" class="text-center py-4 text-gray-500">No print modes found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="modal-overlay" @click.self="closeEditModal">
            <div class="modal-content">
                <h3 class="modal-title">Edit Print Mode</h3>
                <div class="form-group mb-3">
                    <label class="form-label">Name</label>
                    <input v-model="editingMode.name" class="form-input">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Value</label>
                    <input v-model="editingMode.value" class="form-input">
                </div>
                <div class="form-group mb-4">
                    <label class="form-label">Status</label>
                    <select v-model="editingMode.status" class="form-input">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button @click="closeEditModal" class="btn btn-secondary">Cancel</button>
                    <button @click="savePrintMode" class="btn btn-primary">Save Changes</button>
                </div>
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

const showEditModal = ref(false);
const editingMode = ref(null);

const openEditModal = (mode) => {
    editingMode.value = { ...mode };
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingMode.value = null;
};

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

const savePrintMode = async () => {
    if (!editingMode.value) return;
    try {
        const token = getToken();
        if (!token) return;
        await axios.patch(`/api/v1/admin/print-modes/${editingMode.value.id}`, { name: editingMode.value.name, value: editingMode.value.value, status: editingMode.value.status }, {
            headers: { Authorization: `Bearer ${token}` }
        });
        fetchPrintModes();
        emit('update:success', 'Print mode updated successfully.');
        closeEditModal();
    } catch (error) {
        emit('update:error', error.response?.data?.message || 'Failed to update print mode.');
    }
};

onMounted(fetchPrintModes);
</script>

<style scoped>
.settings-section { margin-bottom: 2rem; }
.card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; overflow: hidden; }
.card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
.card-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #111827; }
.card-body { padding: 1.5rem; }

.add-form { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; background: #f3f4f6; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #e5e7eb; }
.form-group { display: flex; flex-direction: column; gap: 0.375rem; flex: 1; min-width: 150px; }
.form-label { font-size: 0.875rem; font-weight: 500; color: #374151; }
.form-input { width: 100%; padding: 0.625rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; transition: border-color 0.15s; background-color: #fff; box-sizing: border-box; }
.form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }

.table-responsive { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px; }
.data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.data-table th, .data-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.data-table th { background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
.data-table tbody tr:hover { background-color: #f9fafb; }
.data-table tbody tr:last-child td { border-bottom: none; }

.btn { padding: 0.625rem 1.25rem; border-radius: 6px; font-weight: 500; font-size: 0.875rem; cursor: pointer; transition: all 0.15s; border: none; display: inline-flex; align-items: center; justify-content: center; height: fit-content; }
.btn-primary { background: #2563eb; color: white; }
.btn-primary:hover { background: #1d4ed8; }
.btn-success { background: #10b981; color: white; }
.btn-success:hover { background: #059669; }
.btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
.btn-secondary:hover { background: #e5e7eb; }
.w-full { width: 100%; }
.text-center { text-align: center; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.text-gray-500 { color: #6b7280; }
.status-badge { padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; text-transform: capitalize; }
.status-badge.active { background: #d1fae5; color: #065f46; }
.status-badge.inactive { background: #fee2e2; color: #991b1b; }

.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 50; }
.modal-content { background: white; border-radius: 8px; padding: 1.5rem; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
.modal-title { margin-top: 0; margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 600; color: #111827; }
.mb-3 { margin-bottom: 0.75rem; }
.mb-4 { margin-bottom: 1.25rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; }
.loading-state { text-align: center; padding: 2rem; color: #6b7280; font-style: italic; }
</style>