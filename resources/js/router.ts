import { createRouter, createWebHistory } from 'vue-router';
import Register from './components/Register.vue';
import VerifyEmail from './components/VerifyEmail.vue';
import Login from './components/Login.vue';
import Home from './components/Home.vue';
import ChangePassword from './components/ChangePassword.vue';
import AdminLogin from './components/AdminLogin.vue';
import { CONSTANTS } from './constants';
import AdminHome from './components/admin/AdminHome.vue';
import AdminDashboard from './components/AdminDashboard.vue';
import AdminChangePassword from './components/admin/AdminChangePassword.vue';
import OrderPage from './components/OrderPage.vue';
import OrderView from './components/OrderView.vue';
import AdminOrderView from './components/admin/AdminOrderView.vue';
import AdminOrderEdit from './components/admin/AdminOrderEdit.vue';
import OrderEdit from './components/OrderEdit.vue';
import ForgotPassword from './components/ForgotPassword.vue';
import AdminOrderPayment from './components/admin/AdminOrderPayment.vue';
import UserOrderPayment from './components/UserOrderPayment.vue';
import LandingPage from './components/LandingPage.vue';
import AdminSettings from './components/admin/AdminSettings.vue';
import UserProfile from './components/UserProfile.vue';
import NotificationList from './components/NotificationList.vue';
import OrderHistory from './components/OrderHistory.vue';
import CurrentOrder from './components/CurrentOrder.vue';

const routes = [
    {
        path: '/',
        name: 'landing',
        component: LandingPage,
        meta: { title: 'YouPrints - Home' }
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: { title: `${CONSTANTS.APP_NAME} - Register` }
    },
    {
        path: '/verify',
        name: 'verify',
        component: VerifyEmail,
        meta: { title: `${CONSTANTS.APP_NAME} - Verify Email` }
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { title: `${CONSTANTS.APP_NAME} - Login` }
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPassword,
        meta: { title: `${CONSTANTS.APP_NAME} - Forgot Password` }
    },
    {
        path: CONSTANTS.ROUTE.HOME,
        component: Home,
        children: [
            {
                path: '',
                name: 'home',
                component: CurrentOrder,
                meta: { title: `${CONSTANTS.APP_NAME} - Home` }
            },
            {
                path: CONSTANTS.ROUTE.CURRENT_ORDER,
                name: 'current-order',
                component: CurrentOrder,
                meta: { title: `${CONSTANTS.APP_NAME} - Current Order` }
            },
            {
                path: CONSTANTS.ROUTE.ORDER_HISTORY,
                name: 'order-history',
                component: OrderHistory,
                meta: { title: `${CONSTANTS.APP_NAME} - Order History` }
            }
        ]
    },
    {
        path: '/change-password',
        name: 'change-password',
        component: ChangePassword,
        meta: { title: `${CONSTANTS.APP_NAME} - Change Password` }
    },
    {
        path: '/profile',
        name: 'profile',
        component: UserProfile,
        meta: { title: `${CONSTANTS.APP_NAME} - Profile` }
    },
    {
        path: '/notifications',
        name: 'notifications',
        component: NotificationList,
        meta: { title: `${CONSTANTS.APP_NAME} - Notifications` }
    },
    {
        path: '/order',
        name: 'order',
        component: OrderPage,
        meta: { title: `${CONSTANTS.APP_NAME} - Create Order` }
    },
    {
        path: '/orders/:id',
        name: 'view-order',
        component: OrderView,
        meta: { title: `${CONSTANTS.APP_NAME} - View Order` }
    },
    {
        path: '/orders/:orderId/payments',
        name: 'user-order-payment',
        component: UserOrderPayment,
        meta: { title: `${CONSTANTS.APP_NAME} - Order Payment` }
    },
    {
        path: '/order/:id/edit',
        name: 'edit-order',
        component: OrderEdit,
        meta: { title: `${CONSTANTS.APP_NAME} - Edit Order` }
    },
    {
        path: '/admin/login',
        name: 'admin-login',
        component: AdminLogin,
        meta: { title: `${CONSTANTS.APP_NAME} - Admin Login` }
    },
    {
        path: '/admin/',
        name: 'admin-home',
        component: AdminDashboard,
        meta: { title: `${CONSTANTS.APP_NAME} - Admin Dashboard` }
    },
    {
        path: '/admin/change-password',
        name: 'admin-change-password',
        component: AdminChangePassword,
        meta: { title: `${CONSTANTS.APP_NAME} - Admin Change Password` }
    },
    {
        path: '/admin/orders/:id',
        name: 'admin-view-order',
        component: AdminOrderView,
        meta: { title: `${CONSTANTS.APP_NAME} - View Order` }
    },
    {
        path: '/admin/orders/:orderId/payments',
        name: 'admin-order-payments',
        component: AdminOrderPayment,
        meta: { title: `${CONSTANTS.APP_NAME} - Admin Order Payment` }
    },
    {
        path: '/admin/orders/:id/edit',
        name: 'admin-edit-order',
        component: AdminOrderEdit,
        meta: { title: `${CONSTANTS.APP_NAME} - Edit Order` }
    },
    {
        path: '/admin/settings',
        name: 'admin-settings',
        component: AdminSettings,
        meta: { title: `${CONSTANTS.APP_NAME} - Admin Settings` }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    document.title = (to.meta.title as string) || `${CONSTANTS.APP_NAME}`;

    const isUserAuthenticated = document.cookie.split(';').some((item) => item.trim().startsWith(`${CONSTANTS.USER_TOKEN}=`));
    const isAdminAuthenticated = document.cookie.split(';').some((item) => item.trim().startsWith(`${CONSTANTS.ADMIN_TOKEN}=`));
    const isAuthenticated = isUserAuthenticated || isAdminAuthenticated;

    if (to.path.startsWith('/admin')) {
        if (isAdminAuthenticated) {
            if (to.name == 'admin-login') {
                return next({ name: 'admin-home' });
            }
        } else {
            if (to.name !== 'admin-login') {
                return isAuthenticated ? next({ name: 'home' }) : next({ name: 'admin-login' });
            }
        }
        next();
        return;
    } else {
        if (!isUserAuthenticated && to.name !== 'login' && to.name !== 'register' && to.name !== 'verify' && to.name !== 'forgot-password' && to.name !== 'landing') {
            next({ name: 'login' });
        } else if (isUserAuthenticated && (to.name === 'login' || to.name === 'register' || to.name === 'verify')) {
            next({ name: 'home' });
        } else {
            next();
        }
    }

});

export default router;