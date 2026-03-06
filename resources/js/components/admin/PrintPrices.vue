<template>
    <div class="settings-section">
        <h2 class="section-title">Print Prices Matrix</h2>
        <div v-if="loading" class="loading-state">Loading pricing data...</div>
        <div v-else class="table-responsive">
            <table class="pricing-table">
                <thead>
                    <tr>
                        <th>Paper Size</th>
                        <th v-for="mode in printModes" :key="mode.id">{{ mode.name }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="size in paperSizes" :key="size.id">
                        <td>{{ size.name }}</td>
                        <td v-for="mode in printModes" :key="mode.id">
                            <div class="price-input-group">
                                <span>₹</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-input price-input"
                                    :value="getPrice(size.id, mode.id)"
                                    @change="updatePrice(size.id, mode.id, $event.target.value)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { CONSTANTS } from '../../constants';

const emit = defineEmits(['update:success', 'update:error']);

const printModes = ref([]);
const paperSizes = ref([]);
const printPrices = ref([]);
const loading = ref(false);

const getToken = () => document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];

const fetchData = async () => {
    loading.value = true;
    try {
        const token = getToken();
        if (!token) return;
        const [modesRes, sizesRes, pricesRes] = await Promise.all([
            axios.get('/api/v1/print-modes'),
            axios.get('/api/v1/paper-sizes'),
            axios.get('/api/v1/admin/print-prices', {
                headers: { Authorization: `Bearer ${token}` }
            })
        ]);
        printModes.value = modesRes.data.filter(m => m.status === 'active');
        paperSizes.value = sizesRes.data.filter(s => s.status === 'active');
        printPrices.value = pricesRes.data;
    } catch (error) {
        emit('update:error', 'Failed to load pricing data.');
    } finally {
        loading.value = false;
    }
};

const getPrice = (paperSizeId, printModeId) => {
    const priceEntry = printPrices.value.find(
        p => p.paper_size_id === paperSizeId && p.print_mode_id === printModeId
    );
    return priceEntry ? priceEntry.price : 0;
};

const updatePrice = async (paperSizeId, printModeId, price) => {
    const newPrice = parseFloat(price);
    if (isNaN(newPrice) || newPrice < 0) {
        emit('update:error', 'Invalid price value.');
        return;
    }

    try {
        const token = getToken();
        if (!token) return;
        const { data } = await axios.post('/api/v1/admin/print-prices', {
            paper_size_id: paperSizeId,
            print_mode_id: printModeId,
            price: newPrice
        }, {
            headers: { Authorization: `Bearer ${token}` }
        });

        const priceIndex = printPrices.value.findIndex(p => p.id === data.data.id);

        if (priceIndex > -1) {
            printPrices.value[priceIndex] = data.data;
        } else {
            printPrices.value.push(data.data);
        }

        emit('update:success', 'Price updated successfully.');
    } catch (error) {
        emit('update:error', error.response?.data?.message || 'Failed to update price.');
        fetchData();
    }
};

onMounted(fetchData);
</script>

<style scoped>
.settings-section { margin-bottom: 3rem; }
.section-title { font-size: 1.5rem; font-weight: 600; color: #1f2937; margin-bottom: 1.5rem; }
.table-responsive { overflow-x: auto; }
.pricing-table { width: 100%; border-collapse: collapse; background-color: white; }
.pricing-table th, .pricing-table td { border: 1px solid #e5e7eb; padding: 0.75rem; text-align: left; }
.pricing-table th { background-color: #f9fafb; }
.price-input-group { display: flex; align-items: center; gap: 0.25rem; }
.price-input { max-width: 100px; text-align: right; }
.form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; box-sizing: border-box; }
.loading-state { padding: 2rem; text-align: center; color: #6b7280; }
</style>