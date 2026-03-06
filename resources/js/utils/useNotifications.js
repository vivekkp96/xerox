import { ref } from 'vue';
import axios from 'axios';
import { CONSTANTS } from '../constants';

export function useNotifications() {
    const notifications = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        total: 0,
        per_page: 20
    });

    const getAuthConfig = () => {
        const token = document.cookie.split(';').map(c => c.trim()).find(c => c.startsWith(`${CONSTANTS.USER_TOKEN}=`))?.split('=')[1];
        if (!token) {
             // Ideally redirect to login or handle unauthenticated state
             return {};
        }
        return {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        };
    };

    const fetchNotifications = async (page = 1, filters = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const config = getAuthConfig();
            const params = { page, ...filters };
            const response = await axios.get('/api/v1/notifications', { ...config, params });
            
            notifications.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                total: response.data.total,
                per_page: response.data.per_page
            };
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to fetch notifications';
            console.error(e);
        } finally {
            loading.value = false;
        }
    };

    const markAsRead = async (id) => {
        try {
            const config = getAuthConfig();
            await axios.patch(`/api/v1/notifications/${id}`, { user_read_flag: true }, config);
            
            const index = notifications.value.findIndex(n => n.id === id);
            if (index !== -1) {
                notifications.value[index].user_read_flag = true;
            }
        } catch (e) {
            console.error('Failed to mark notification as read', e);
        }
    };

    return {
        notifications,
        loading,
        error,
        pagination,
        fetchNotifications,
        markAsRead
    };
}