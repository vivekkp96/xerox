export const CONSTANTS = {
    USER_TOKEN: 'auth_token',
    ADMIN_TOKEN: 'ad_token',
    APP_NAME: 'YouPrints',
    API: {
        ADMIN_LOGIN: '/api/v1/admin/login',
        ADMIN_ME: '/api/v1/admin/me',
        GET_ORDERS: '/api/v1/orders',
        GET_ADMIN_ORDERS: '/api/v1/admin/orders',
        PATCH_ADMIN_ORDERS: '/api/v1/admin/orders/',
        USER_ME: '/api/v1/me',
        USER_CHANGE_PASSWORD: '/api/v1/change-password',
    },
    ROUTE: {
        HOME: '/home',
        ADMIN_HOME: '/admin/home',
        LOGIN: '/login',
        ADMIN_LOGIN: '/admin/login',
        CURRENT_ORDER: '/current-order',
        ORDER_HISTORY: '/order-history',
    },
    MAX_FILE_SIZE: 100 * 1024 * 1024, // 100MB
    MAX_ORDER_SIZE: 200 * 1024 * 1024, // 200 MB,
    MAX_FILE_NAME_CHARECTERS: 75,
    TOOL_TIP_SHOW_DELAY: 500,
    TOOL_TIP_HIDE_DELAY: 4000,
    FETCH_MEMORY_STATS_API_DELAY_25: 25000,
    FETCH_MEMORY_STATS_API_DELAY_30: 30000, //20 sec  
    FETCH_MEMORY_STATS_API_DELAY_40: 40000, //20 sec  
    FETCH_MEMORY_STATS_API_DELAY_50: 50000, //20 sec  
    FETCH_MEMORY_STATS_API_DELAY_70: 70000, //20 sec  
    PENDING_PROCESSING: 'Pending,Processing,history10',
    COMPLETED_CANCELLED: 'Completed,Cancelled',
    
    ORDER_PAYMENT_STATUS_PENDING: 'Pending',
    ORDER_PAYMENT_STATUS_PAID: 'Paid',
    ORDER_PAYMENT_STATUS_REFUNDED: 'Refunded',
    ORDER_PAYMENT_STATUS_PAYMENT_VERIFIED: 'Payment Verified',

    ORDER_STATUS_CANCELLED: 'Cancelled',
    ORDER_STATUS_COMPLETED: 'Completed',
    ORDER_STATUS_PENDING: 'Pending',
    ORDER_STATUS_PROCESSING: 'Processing',
    COLOR:{
        RED: "#dc3545",
        GREEN: "#28a745",
        ORANGE: "#fd7e14"
    }
}