import { CONSTANTS } from "../constants";

export const checkApiStatus = (status: number, redirectPath: string = '/login?expired=true'): void => {
  if (status === 401) {
        document.cookie = `${CONSTANTS.USER_TOKEN}=; Max-Age=0; path=/`;
        window.location.href = redirectPath;
    }};

export const checkApiStatusForAdmin = (status: number, redirectPath: string = '/admin/login?expired=true'): void => {
  if (status === 401) {
        document.cookie = `${CONSTANTS.ADMIN_TOKEN}=; Max-Age=0; path=/`;
        window.location.href = redirectPath;
    }};
