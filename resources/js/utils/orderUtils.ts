export const calculateNumberOfPages = (pagesString: string, totalPages: number): number => {
    const s = String(pagesString).trim().toLowerCase();
    if (s === 'all' || s === '') {
        return totalPages;
    }

    let count = 0;
    const parts = s.replace(/\s/g, '').split(',');

    for (const part of parts) {
        if (part.includes('-')) {
            const [start, end] = part.split('-').map(Number);
            if (!isNaN(start) && !isNaN(end) && end >= start) {
                count += (end - start + 1);
            }
        } else {
            if (!isNaN(Number(part))) {
                count += 1;
            }
        }
    }

    return count > 0 ? count : 1;
};

export const getConfigurationPrice = (
    config: { mode: string, pages: string, size?: string },
    totalPages: number,
    printPrices: { paper_size_id: number, print_mode_id: number, price: number }[],
    printModes: { id: number, value: string }[],
    paperSizes: { id: number, value: string }[]
): number => {
    const numberOfPages = calculateNumberOfPages(config.pages, totalPages);

    const mode = printModes.find(m => m.value === config.mode);
    const size = paperSizes.find(s => s.value === (config.size));
    console.log('mode', mode);
    console.log('size', size);
    console.log('numberOfPages', numberOfPages);


    if (!mode || !size) return 0;

    const priceEntry = printPrices.find(p => p.print_mode_id === mode.id && p.paper_size_id === size.id);
    const unitPrice = priceEntry ? Number(priceEntry.price) : 0;

    return unitPrice * numberOfPages;
};