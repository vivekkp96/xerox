import { CONSTANTS } from '../constants';

export const validateFileSizes = (files, currentTotalSize = 0) => {
    const fileList = Array.isArray(files) ? files : [files];
    let accumulatedSize = 0;

    for (const file of fileList) {
        if (file.size > CONSTANTS.MAX_FILE_SIZE) {
            return { valid: false, message: `File "${file.name}" exceeds the 100MB limit.` };
        }
        accumulatedSize += file.size;
        if (currentTotalSize + accumulatedSize > CONSTANTS.MAX_ORDER_SIZE) {
            return { valid: false, message: 'Total file size for this order exceeds the 200MB limit.' };
        }
    }
    return { valid: true };
};

export const validateFileNameLength = (files) => {
    const fileList = Array.isArray(files) ? files : [files];

    for (const file of fileList) {
        if (file.name.length > CONSTANTS.MAX_FILE_NAME_CHARECTERS) {
            return { valid: false, message: `File name "${file.name}" exceeds the ${CONSTANTS.MAX_FILE_NAME_CHARECTERS} character limit.` };
        }
    }
    return { valid: true };
};

export const isValidFileType = (file) => {
    const isImage = file.type.startsWith('image/');
    const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
    const isDoc = file.type === 'application/msword' || file.name.toLowerCase().endsWith('.doc');
    const isDocx = file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || file.name.toLowerCase().endsWith('.docx');
    return isImage || isPdf || isDoc || isDocx;
};