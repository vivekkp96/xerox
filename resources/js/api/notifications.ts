import axios from 'axios';

export async function fetchUnreadNotificationCount(token: string): Promise<number> {
    const response = await axios.get('/api/v1/notifications/unread-count', {
        headers: {
            Authorization: `Bearer ${token}`
        }
    });
    return response.data.unread_count;
}
