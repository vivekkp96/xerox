/**
 * Utility to validate filenames against server-harmful text and patterns.
 * Ensures filenames are safe for storage and processing.
 */

export interface ValidationResult {
    isValid: boolean;
    error?: string;
}

/**
 * Validates that a filename does not contain server-harmful text or characters.
 * 
 * @param filename - The name of the file to validate
 * @returns Object with isValid boolean and optional error message
 */
export const validateSafeFileName = (filename: string): ValidationResult => {
    if (!filename || filename.trim().length === 0) {
        return { isValid: false, error: 'Filename cannot be empty.' };
    }

    // 1. Block Path Traversal
    // Prevents accessing files outside the intended directory (e.g., ../../etc/passwd)
    if (filename.includes('..')) {
        return { isValid: false, error: 'Filename cannot contain path traversal patterns ("..").' };
    }

    // 2. Block Directory Separators
    // Filenames should not contain slashes or backslashes
    if (filename.includes('/') || filename.includes('\\')) {
        return { isValid: false, error: 'Filename cannot contain directory separators.' };
    }

    // 3. Block Shell Injection Characters & Control Characters
    // These characters can be used to execute commands on the server if not properly handled.
    // Blocked: < > : " | ? * (Windows reserved)
    // Blocked: ; & $ ` (Shell command injection)
    // Blocked: Control characters (ASCII 0-31 and 127)
    // Note: We allow parentheses () as they are common in filenames, but block $ to prevent variable expansion.
    // eslint-disable-next-line no-control-regex
    const harmfulCharsRegex = /[<>:"|?*;`$]|[\x00-\x1F\x7F]/;
    
    if (harmfulCharsRegex.test(filename)) {
        return { isValid: false, error: 'Filename contains invalid or harmful characters.' };
    }

    // 4. Block Reserved System Names (Windows/DOS)
    // These names are reserved by the system and can cause IO errors on Windows servers.
    const reservedNamesRegex = /^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(\..*)?$/i;
    if (reservedNamesRegex.test(filename)) {
        return { isValid: false, error: 'Filename is a reserved system name.' };
    }

    return { isValid: true };
};