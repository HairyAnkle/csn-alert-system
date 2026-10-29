export function normalizePhoneNumber(raw) {
  if (!raw) return '';
  let digits = raw.replace(/[^0-9+]/g, '');
  if (digits.startsWith('+63')) {
    digits = '0' + digits.slice(3);
  } else if (digits.startsWith('63') && digits.length === 12) {
    digits = '0' + digits.slice(2);
  } else if (digits.length === 10 && digits.startsWith('9')) {
    digits = '0' + digits;
  }
  return digits;
}

/** Formats digits for Philippine mobile numbers: e.g. "917 123 4567" */
export function formatPhilippineMobile(raw) {
  if (!raw) return '';
  let digits = raw.replace(/[^0-9]/g, '');
  if (digits.startsWith('09')) {
    digits = digits.slice(1);
  } else if (digits.startsWith('639')) {
    digits = digits.slice(2);
  }
  digits = digits.slice(0, 10);
  if (digits.length <= 3) return digits;
  if (digits.length <= 6) return `${digits.slice(0, 3)} ${digits.slice(3)}`;
  return `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6)}`;
}

/** Returns E.164 formatted string: e.g. "+639171234567" */
export function toE164Philippine(raw) {
  const norm = normalizePhoneNumber(raw);
  if (!norm) return '';
  if (norm.startsWith('09') && norm.length === 11) {
    return '+63' + norm.slice(1);
  }
  if (norm.length === 10 && norm.startsWith('9')) {
    return '+63' + norm;
  }
  return norm.startsWith('+') ? norm : '+' + norm;
}

/** Validates that raw is a valid 10-digit (without 0) or 11-digit (with 0) PH mobile number */
export function isValidPhilippineMobile(raw) {
  const norm = normalizePhoneNumber(raw);
  return norm.length === 11 && norm.startsWith('09');
}

