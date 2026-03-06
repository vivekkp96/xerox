<template>
    <div class="super-admin-layout">
        <nav class="navbar">
            <div class="navbar-brand">
                Super Admin Dashboard
            </div>
            <div class="navbar-menu">
                <button @click="handleLogout" class="btn-logout">Logout</button>
            </div>
        </nav>
        
        <main class="dashboard-content">
            <div class="header-section">
                <h1>System Monitor</h1>
                <p class="subtitle">Real-time system resource overview</p>
            </div>

            <div class="stats-grid">
                <!-- Memory Card -->
                <div class="stat-card">
                    <div class="card-header">
                        <span class="card-title">MEMORY USAGE</span>
                        <span class="card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20"></path><path d="M2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6"></path><path d="M12 12V2"></path><path d="M2 8h20"></path><path d="M2 4h20"></path></svg>
                        </span>
                    </div>
                    <div v-if="memoryStats" class="card-body">
                        <div class="main-value">
                            {{ memoryStats.percentage_used }}<span class="unit">%</span>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar" :style="{ width: memoryStats.percentage_used + '%', backgroundColor: getDiskBarColor(memoryStats.percentage_used) }"></div>
                        </div>
                        <div class="details-list">
                            <div class="detail-item"><span class="label">Used</span><span class="value" :class="{ 'value-change-animation': statsChanged }">{{ formatBytes(memoryStats.used_memory_bytes) }}</span></div>
                            <div class="detail-item"><span class="label">Total</span><span class="value">{{ formatBytes(memoryStats.total_memory_bytes) }}</span></div>
                            <div class="detail-item"><span class="label">Available</span><span class="value" :class="{ 'value-change-animation': statsChanged }">{{ formatBytes(memoryStats.available_memory_bytes) }}</span></div>
                        </div>
                    </div>
                    <div v-else class="loading-state">Loading...</div>
                </div>

                <!-- Disk Card -->
                <div class="stat-card">
                    <div class="card-header">
                        <span class="card-title">DISK SPACE</span>
                        <span class="card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                        </span>
                    </div>
                    <div v-if="diskStats" class="card-body">
                        <div class="main-value">
                            {{ diskStats.percentage_occupied }}<span class="unit">%</span>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar" :style="{ width: diskStats.percentage_occupied + '%', backgroundColor: getDiskBarColor(diskStats.percentage_occupied) }"></div>
                        </div>
                        <div class="details-list">
                            <div class="detail-item"><span class="label">Occupied</span><span class="value" :class="{ 'value-change-animation': diskStatsChanged }">{{ formatBytes(diskStats.occupied_space_bytes) }}</span></div>
                            <div class="detail-item"><span class="label">Total</span><span class="value">{{ formatBytes(diskStats.total_space_bytes) }}</span></div>
                            <div class="detail-item"><span class="label">Free</span><span class="value" :class="{ 'value-change-animation': diskStatsChanged }">{{ formatBytes(diskStats.free_space_bytes) }}</span></div>
                        </div>
                    </div>
                    <div v-else class="loading-state">Loading...</div>
                </div>

                <!-- CPU Card -->
                <div class="stat-card">
                    <div class="card-header">
                        <span class="card-title">CPU PERFORMANCE</span>
                        <span class="card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>
                        </span>
                    </div>
                    <div v-if="cpuLoadStats" class="card-body">
                        <div v-if="cpuLoadStats.os === 'windows'">
                            <div class="main-value">
                                {{ cpuLoadStats.cpu_usage_percent }}<span class="unit">%</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar" :style="{ width: cpuLoadStats.cpu_usage_percent + '%', backgroundColor: getDiskBarColor(cpuLoadStats.cpu_usage_percent) }"></div>
                            </div>
                            <div class="details-list">
                                <div class="detail-item"><span class="label">Current Load</span><span class="value" :class="{ 'value-change-animation': cpuStatsChanged }">{{ cpuLoadStats.cpu_usage_percent }}%</span></div>
                                <div class="detail-item"><span class="label">OS</span><span class="value">Windows</span></div>
                            </div>
                        </div>
                        <div v-if="cpuLoadStats.os === 'linux'">
                             <div class="main-value">
                                {{ cpuLoadStats.load_average[0].toFixed(2) }}<span class="unit" style="font-size: 1rem; color: #6c757d;">(1m avg)</span>
                            </div>
                            <div class="details-list" style="margin-top: 1.5rem;">
                                <div class="detail-item"><span class="label">1-min Load</span><span class="value" :class="{ 'value-change-animation': cpuStatsChanged }">{{ cpuLoadStats.load_average[0].toFixed(2) }}</span></div>
                                <div class="detail-item"><span class="label">5-min Load</span><span class="value" :class="{ 'value-change-animation': cpuStatsChanged }">{{ cpuLoadStats.load_average[1].toFixed(2) }}</span></div>
                                <div class="detail-item"><span class="label">15-min Load</span><span class="value" :class="{ 'value-change-animation': cpuStatsChanged }">{{ cpuLoadStats.load_average[2].toFixed(2) }}</span></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="loading-state">Loading...</div>
                </div>

                <!-- MySQL Card -->
                <div class="stat-card">
                    <div class="card-header">
                        <span class="card-title">MYSQL USAGE</span>
                        <span class="card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                        </span>
                    </div>
                    <div v-if="mysqlStats" class="card-body">
                        <div class="main-value">
                            {{ mysqlStats.cpu_usage_percent.toFixed(1) }}<span class="unit">%</span>
                            <span style="font-size: 0.8rem; color: #6c757d; display: block; margin-top: -5px;">CPU</span>
                        </div>
                        <div class="details-list" style="margin-top: 1.5rem;">
                             <div class="detail-item"><span class="label">CPU Usage</span><span class="value" :class="{ 'value-change-animation': mysqlStatsChanged }">{{ mysqlStats.cpu_usage_percent.toFixed(2) }}%</span></div>
                             <div class="detail-item"><span class="label">Memory Usage</span><span class="value" :class="{ 'value-change-animation': mysqlStatsChanged }">{{ formatBytes(mysqlStats.memory_usage_bytes) }}</span></div>
                        </div>
                    </div>
                    <div v-else class="loading-state">Loading...</div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { CONSTANTS } from '../../constants';

const router = useRouter();

const memoryStats = ref<any>(null);
const diskStats = ref<any>(null);
const cpuLoadStats = ref<any>(null);
const mysqlStats = ref<any>(null);
const statsChanged = ref(false);
const diskStatsChanged = ref(false);
const cpuStatsChanged = ref(false);
const mysqlStatsChanged = ref(false);
let intervalId: any = null;
let intervalId1: any = null;
let intervalId2: any = null;
let intervalId3: any = null;

watch(memoryStats, (current, previous) => {
    if (previous && current.used_memory_bytes !== previous.used_memory_bytes) {
        statsChanged.value = true;
        setTimeout(() => {
            statsChanged.value = false;
        }, 500);
    }
});

watch(diskStats, (current, previous) => {
    if (previous && current.occupied_space_bytes !== previous.occupied_space_bytes) {
        diskStatsChanged.value = true;
        setTimeout(() => {
            diskStatsChanged.value = false;
        }, 500);
    }
});

watch(cpuLoadStats, (current, previous) => {
    if (previous) {
        const changed = current.os === 'windows'
            ? current.cpu_usage_percent !== previous.cpu_usage_percent
            : JSON.stringify(current.load_average) !== JSON.stringify(previous.load_average);

        if (changed) {
            cpuStatsChanged.value = true;
            setTimeout(() => {
                cpuStatsChanged.value = false;
            }, 500);
        }
    }
});

watch(mysqlStats, (current, previous) => {
    if (previous && (current.cpu_usage_percent !== previous.cpu_usage_percent || current.memory_usage_bytes !== previous.memory_usage_bytes)) {
        mysqlStatsChanged.value = true;
        setTimeout(() => {
            mysqlStatsChanged.value = false;
        }, 500);
    }
});

const formatBytes = (bytes: number, decimals = 2) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};


// Returns color based on percentage: green (<=60), orange (<=85), red (>85)
const getDiskBarColor = (percent: number) => {
    if (percent <= 60) return CONSTANTS.COLOR.GREEN; // green
    if (percent <= 85) return CONSTANTS.COLOR.ORANGE; // orange
    return CONSTANTS.COLOR.RED; // red
};

const fetchMemoryStats = async () => {
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) return;

        const response = await axios.get('/api/v1/admin/system-memory', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        memoryStats.value = response.data;
    } catch (error) {
        console.error('Error fetching memory stats:', error);
    }
};

const fetchDiskStats = async () => {
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) return;

        const response = await axios.get('/api/v1/admin/disk-space', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        diskStats.value = response.data;
    } catch (error) {
        console.error('Error fetching disk stats:', error);
    }
};

const fetchCpuLoadStats = async () => {
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) return;

        const response = await axios.get('/api/v1/admin/cpu-load', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        cpuLoadStats.value = response.data;
    } catch (error) {
        console.error('Error fetching cpu load stats:', error);
    }
};

const fetchMysqlStats = async () => {
    try {
        const token = document.cookie.split(';').find(c => c.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`))?.split('=')[1];
        if (!token) return;

        const response = await axios.get('/api/v1/admin/mysql-usage', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        mysqlStats.value = response.data;
    } catch (error) {
        console.error('Error fetching mysql stats:', error);
    }
};


onMounted(async () => {
    await fetchMemoryStats();
    await fetchDiskStats();
    await fetchCpuLoadStats();
    await fetchMysqlStats();
    intervalId = setInterval(() => {
        fetchMemoryStats();
    }, CONSTANTS.FETCH_MEMORY_STATS_API_DELAY_30);
    intervalId1 = setInterval(() => {
        fetchDiskStats();
    }, CONSTANTS.FETCH_MEMORY_STATS_API_DELAY_70);
    intervalId2 = setInterval(() => {
        fetchCpuLoadStats();
    }, CONSTANTS.FETCH_MEMORY_STATS_API_DELAY_40);
    intervalId3 = setInterval(() => {
        fetchMysqlStats();
    }, CONSTANTS.FETCH_MEMORY_STATS_API_DELAY_70);
});


onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
    if (intervalId1) clearInterval(intervalId1);
    if (intervalId2) clearInterval(intervalId2);
    if (intervalId3) clearInterval(intervalId3);
});

const handleLogout = () => {
    // Clear the auth cookie
    document.cookie = `${CONSTANTS.ADMIN_TOKEN}=; path=/; max-age=0; SameSite=Lax`;
    router.push({ name: 'admin-login' });
};
</script>

<style scoped>
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
    background-color: #2c3e50;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.navbar-brand {
    font-size: 1.5rem;
    font-weight: bold;
}

.btn-logout {
    padding: 0.5rem 1rem;
    background-color: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

.btn-logout:hover {
    background-color: #c82333;
}

.dashboard-content {
    padding: 2rem 3rem;
    background-color: #f8f9fa;
    min-height: calc(100vh - 64px);
}

.header-section {
    margin-bottom: 2rem;
}

.header-section h1 {
    font-size: 1.75rem;
    color: #2c3e50;
    margin: 0;
    font-weight: 700;
}

.subtitle {
    color: #6c757d;
    margin-top: 0.5rem;
    font-size: 0.95rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.card-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: #adb5bd;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.card-icon {
    color: #e9ecef;
    background-color: #f8f9fa;
    padding: 8px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-icon svg {
    color: #28a745;
}

.progress-container {
    width: 100%;
    height: 8px;
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin: 1rem 0;
}

.progress-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease;
}

.main-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.unit {
    font-size: 1.25rem;
    color: #6c757d;
    margin-left: 4px;
    font-weight: 500;
}

.details-list {
    margin-top: 1.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.detail-item .label {
    color: #6c757d;
}

.detail-item .value {
    font-weight: 600;
    color: #2c3e50;
}

.value-change-animation {
        animation: valueChange 2.5s cubic-bezier(0.4, 0, 0.2, 1);
        background-color: #ffe082;
        color: #222;
        border-radius: 4px;
        box-shadow: 0 0 8px 2px #ffe08255;
}

@keyframes highlight-fade {
        0% {
            background-color: #ffe082;
            color: #222;
            box-shadow: 0 0 8px 2px #ffe08255;
        }
        70% {
            background-color: #ffe082;
            color: #222;
            box-shadow: 0 0 8px 2px #ffe08255;
        }
        100% {
            background-color: transparent;
            color: inherit;
            box-shadow: none;
        }
}

.loading-state {
    color: #adb5bd;
    font-style: italic;
}
</style>
