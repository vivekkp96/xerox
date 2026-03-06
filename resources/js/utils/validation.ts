export const isValidPageFormat = (pages: string): boolean => {
    if (!pages) return false;
    // Regex allows "All" (case-insensitive) OR comma-separated numbers/ranges
    // Examples: "1", "1-5", "1,3,5", "1-5, 8, 11-13", "All", "all"
    const regex = /^((All)|(\d+(\s*-\s*\d+)?(\s*,\s*\d+(\s*-\s*\d+)?)*))$/i;
    return regex.test(pages.trim());
};

export const isPdf = (filename: string): boolean => {
    return !!filename && filename.toLowerCase().endsWith('.pdf');
};

export const isPdfOrImage = (input: string | { filename: string; mimeType?: string }): boolean => {
    if (typeof input !== 'string' && input.mimeType) {
        return input.mimeType === 'application/pdf' || input.mimeType.startsWith('image/');
    }

    const filename = typeof input === 'string' ? input : input.filename;
    if (!filename) return false;
    const name = filename.toLowerCase();
    return name.endsWith('.pdf') || /\.(jpg|jpeg|png|gif|bmp|webp)$/.test(name);
};

export const validatePageCount = (docTotalPages: number, printTotalPages: number): boolean => {
    return docTotalPages >= printTotalPages;
};
