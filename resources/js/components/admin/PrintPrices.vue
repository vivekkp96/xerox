<template>
    <div class="settings-section">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Print Prices Matrix</h2>
                <p class="card-subtitle">Set the cost per page for each combination of paper size and print mode.</p>
            </div>
            <div class="card-body">
                <div v-if="loading" class="loading-state">Loading pricing data...</div>
                <div v-else class="table-responsive">
                    <table class="pricing-table">
                        <thead>
                            <tr>
                                <th class="sticky-col">Paper Size \ Mode</th>
                                <th v-for="mode in printModes" :key="mode.id">{{ mode.name }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="size in paperSizes" :key="size.id">
                                <td class="sticky-col font-medium">{{ size.name }}</td>
                                <td v-for="mode in printModes" :key="mode.id">
                                    <div class="price-input-wrapper">
                                        <span class="currency-symbol">₹</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="price-input"
                                            :value="getPrice(size.id, mode.id)"
                                            @change="updatePrice(size.id, mode.id, $event.target.value)"
                                        />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="paperSizes.length === 0 || printModes.length === 0">
                                <td :colspan="printModes.length + 1" class="text-center py-4 text-gray-500">
                                    Please add active paper sizes and print modes to configure prices.
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
.settings-section { margin-bottom: 2rem; }
.card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; overflow: hidden; }
.card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
.card-title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #111827; }
.card-subtitle { margin: 0.5rem 0 0; font-size: 0.875rem; color: #6b7280; }
.card-body { padding: 1.5rem; }

.table-responsive { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px; }
.pricing-table { width: 100%; border-collapse: separate; border-spacing: 0; background-color: white; }
.pricing-table th, .pricing-table td { padding: 0.875rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; vertical-align: middle; }
.pricing-table th:last-child, .pricing-table td:last-child { border-right: none; }
.pricing-table th { background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 0.875rem; }
.pricing-table tbody tr:last-child td { border-bottom: none; }
.pricing-table tbody tr:hover { background-color: #f8fafc; }

.sticky-col { position: sticky; left: 0; background: #f9fafb; text-align: left !important; z-index: 10; font-weight: 600; color: #374151; border-right: 2px solid #e5e7eb !important; }
.font-medium { font-weight: 500; }

.price-input-wrapper { display: inline-flex; align-items: center; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden; transition: border-color 0.15s; }
.price-input-wrapper:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
.currency-symbol { padding: 0.5rem 0.75rem; background: #f3f4f6; color: #6b7280; font-weight: 500; border-right: 1px solid #d1d5db; }
.price-input { border: none; padding: 0.5rem 0.75rem; width: 80px; text-align: right; outline: none; -moz-appearance: textfield; font-weight: 500; color: #111827; }
.price-input::-webkit-outer-spin-button, .price-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.loading-state { text-align: center; padding: 3rem; color: #6b7280; font-style: italic; }
.text-center { text-align: center; }
.py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
.text-gray-500 { color: #6b7280; }
</style>