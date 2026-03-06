export const calculateTotalPrintPages = (
    pagesString: string | null | undefined,
    documentTotalPages: number | string,
    numberOfCopies: number | string | undefined
): number => {
    const totalDocPages = Number(documentTotalPages) || 0;
    const copies = Number(numberOfCopies) || 1;
    
    if (!pagesString || pagesString === 'All') {
        return totalDocPages * copies;
    }

    let pagesCount = 0;
    // Remove spaces and split by comma
    const cleanString = pagesString.toString().replace(/\s/g, '');
    const parts = cleanString.split(',');

    for (const part of parts) {
        if (part.includes('-')) {
            const range = part.split('-');
            const start = parseInt(range[0]);
            const end = parseInt(range[1]);
            
            if (!isNaN(start) && !isNaN(end) && end >= start) {
                pagesCount += (end - start + 1);
            }
        } else if (!isNaN(parseInt(part))) {
            pagesCount += 1;
        }
    }

    return pagesCount * copies;
};
