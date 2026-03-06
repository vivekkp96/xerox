// Mobile number validation utility
export function isValidMobileNumber(mobile: string): boolean {
  // Only digits, exactly 10 characters
  return /^\d{10}$/.test(mobile);
}
