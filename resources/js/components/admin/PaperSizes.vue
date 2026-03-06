<template>
    <div class="settings-section">
        <h2 class="section-title">Manage Paper Sizes</h2>
        <div class="add-item-form">
            <input v-model="newSize.name" placeholder="Name (e.g., A4)" class="form-input">
            <input v-model="newSize.code" placeholder="Code (e.g., A4)" class="form-input">
            <select v-model="newSize.status" class="form-input">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button @click="addPaperSize" class="btn-add">Add Size</button>
        </div>

        <div v-if="loading" class="loading-state">Loading...</div>
        <div v-else class="items-grid">
            <div v-for="size in paperSizes" :key="size.id" class="settings-card">
                <input v-model="size.name" class="form-input mb-2" placeholder="Name" />
                <input v-model="size.code" class="form-input mb-2" placeholder="Code" />
                <div class="form-group">
                    <label>Status</label>
                    <select v-model="size.status" class="form-input">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button @click="updatePaperSize(size)" class="btn-save">Save</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { CONSTANTS } from '../../constants';

const emit = defineEmits(['update:success', 'update:error']);

const paperSizes = ref([]);
const loading = ref(false);
const newSize = ref({ name: '', code: '', status: 'active' });

const getToken = () => document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];

const fetchPaperSizes = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/paper-sizes');
        paperSizes.value = response.data;
    } catch (error) {
        emit('update:error', 'Failed to load paper sizes.');
    } finally {
        loading.value = false;
    }
};

const addPaperSize = async () => {
    try {
        const token = getToken();
        if (!token) return;
        await axios.post('/api/v1/admin/paper-sizes', newSize.value, {
            headers: { Authorization: `Bearer ${token}` }
        });
        newSize.value = { name: '', code: '', status: 'active' };
        fetchPaperSizes();
        emit('update:success', 'Paper size added successfully.');
    } catch (error) {
        emit('update:error', error.response?.data?.message || 'Failed to add paper size.');
    }
};

const updatePaperSize = async (size) => {
    try {
        const token = getToken();
        if (!token) return;
        await axios.patch(`/api/v1/admin/paper-sizes/${size.id}`, { name: size.name, code: size.code, status: size.status }, {
            headers: { Authorization: `Bearer ${token}` }
        });
        emit('update:success', 'Paper size updated successfully.');
    } catch (error) {
        emit('update:error', error.response?.data?.message || 'Failed to update paper size.');
    }
};

onMounted(fetchPaperSizes);
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