<template>
    <div class="active-users-page">
        <div class="cards-stack">
            <div class="stat-card large-card">
                <div class="card-header">
                    <span class="card-title">REAL-TIME ACTIVE USERS</span>
                    <div class="status-indicator">
                        <span class="pulse"></span> Live
                    </div>
                </div>
                
                <div v-if="activeUserCount !== null" class="card-body centered-content">
                    <div class="main-value display-value">
                        {{ activeUserCount }}
                    </div>
                    <div class="sub-text">
                        Users online in the last {{ minutes }} minutes
                    </div>
                </div>
                <div v-else class="loading-state">Loading user data...</div>
            </div>

            <div class="stat-card large-card mt-4">
                <div class="card-header">
                    <span class="card-title">PEAK TRAFFIC HISTORY</span>
                </div>
                <div class="peak-list-container">
                    <div v-if="peakRecords.length > 0" class="peak-list">
                        <div v-for="(record, index) in peakRecords" :key="index" class="peak-item">
                            <span class="peak-time">{{ formatDateTime(record.timestamp) }}</span>
                            <span class="peak-count-badge">{{ record.count }} Users</span>
                        </div>
                    </div>
                    <div v-else class="sub-text text-center py-4">
                        No peak records found yet.
                    </div>

                    <div v-if="totalPages > 1" class="pagination-controls">
                        <button class="btn-page" :disabled="currentPage === 1" @click="changePage(currentPage - 1)">
                            Prev
                        </button>
                        <span class="page-info">{{ currentPage }} / {{ totalPages }}</span>
                        <button class="btn-page" :disabled="currentPage === totalPages" @click="changePage(currentPage + 1)">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { CONSTANTS } from '../../constants';
import { checkApiStatusForAdmin } from '../../utils/apiUtils';

const activeUserCount = ref<number | null>(null);
const peakRecords = ref<Array<{timestamp: string, count: number}>>([]);
const minutes = ref(CONSTANTS.USER_LAST_ACTIVITY_MINUTES || 15);
const currentPage = ref(1);
const totalPages = ref(1);
let intervalId: any = null;
let peakIntervalId: any = null;

const fetchActiveUserCount = async () => {
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) return;

        const response = await axios.get('/api/v1/admin/active-users/count', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        activeUserCount.value = response.data.count;
    } catch (error: any) {
        if (error.response) {
            checkApiStatusForAdmin(error.response?.status);
        }
        console.error('Error fetching active user count:', error);
    }
};

const fetchPeakRecords = async (page = 1) => {
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) return;

        const response = await axios.get(`/api/v1/admin/active-users/peak-records?page=${page}&per_page=5`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        // API returns a paginator, records are in data.data
        peakRecords.value = response.data.data || [];
        currentPage.value = response.data.current_page;
        totalPages.value = response.data.last_page;
    } catch (error: any) {
        console.error('Error fetching peak records:', error);
    }
};

const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        fetchPeakRecords(page);
    }
};

const formatDateTime = (dateStr: string) => {
    return new Date(dateStr).toLocaleString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

onMounted(async () => {
    await fetchActiveUserCount();
    await fetchPeakRecords();
    // Refresh every 30 seconds
    intervalId = setInterval(fetchActiveUserCount, 30000);
    // Refresh peak records every 100 minutes
    peakIntervalId = setInterval(() => fetchPeakRecords(1), 100 * 60 * 1000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
    if (peakIntervalId) clearInterval(peakIntervalId);
});
</script>

<style scoped>
.active-users-page {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    width: 100%;
}

.cards-stack {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 600px;
    gap: 2rem;
}

.large-card {
    background: white;
    border-radius: 12px;
    padding: 2.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    width: 100%;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}

.card-title {
    font-size: 1rem;
    font-weight: 700;
    color: #adb5bd;
    letter-spacing: 1px;
}

.centered-content {
    text-align: center;
    padding: 2rem 0;
}

.display-value {
    font-size: 6rem;
    font-weight: 800;
    color: #8b5cf6; /* Brand purple */
    line-height: 1;
    margin-bottom: 1rem;
}

.sub-text {
    font-size: 1.1rem;
    color: #6c757d;
}

.status-indicator {
    display: flex;
    align-items: center;
    color: #28a745;
    font-weight: 600;
    font-size: 0.9rem;
}

.pulse {
    width: 10px;
    height: 10px;
    background-color: #28a745;
    border-radius: 50%;
    margin-right: 8px;
    animation: pulse-animation 2s infinite;
}

@keyframes pulse-animation {
    0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}

.peak-list {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.peak-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #8b5cf6;
}

.peak-time {
    font-weight: 500;
    color: #495057;
}

.peak-count-badge {
    font-weight: 700;
    color: #8b5cf6;
}

.pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 1.5rem;
    gap: 1rem;
}

.btn-page {
    padding: 0.5rem 1rem;
    border: 1px solid #e9ecef;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    color: #495057;
    transition: all 0.2s;
}

.btn-page:hover:not(:disabled) {
    background: #f8f9fa;
    border-color: #dee2e6;
}

.btn-page:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-info {
    font-size: 0.9rem;
    color: #6c757d;
}
</style>