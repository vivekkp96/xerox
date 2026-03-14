export const convertToDate = (dateString: string): Date => {
    return new Date(dateString);
};

export const convertDateToFormattedLocalString = (value: string): string => {
    return convertToDate(value).toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
};