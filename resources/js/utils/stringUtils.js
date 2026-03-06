export const truncateFileName = (filename, maxLength = 40) => {
    if (!filename || filename.length <= maxLength) return filename;
    
    const separator = '...';
    const charsToShow = maxLength - separator.length;
    const frontChars = Math.ceil(charsToShow / 2);
    const backChars = Math.floor(charsToShow / 2);
    
    return filename.substring(0, frontChars) + separator + filename.substring(filename.length - backChars);
};