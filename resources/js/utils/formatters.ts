export const formatOrderNumber = (id: any) => {
    return 'P' + String(id).padStart(5, '0');
};