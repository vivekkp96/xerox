// @ts-ignore
import { toast, type ToastOptions } from 'vue3-toastify';
// @ts-ignore
import 'vue3-toastify/dist/index.css';

export const showSuccess = (message: string, options?: ToastOptions) => {
    toast.success(message, { autoClose: 3000, ...options });
};

export const showError = (message: string, options?: ToastOptions) => {
    toast.error(message, { autoClose: 5000, ...options });
};

export const showInfo = (message: string, options?: ToastOptions) => {
    toast.info(message, { autoClose: 3000, ...options });
};

export const showWarning = (message: string, options?: ToastOptions) => {
    toast.warning(message, { autoClose: 4000, ...options });
};