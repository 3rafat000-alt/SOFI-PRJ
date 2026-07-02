/**
 * Unified auth validation layer — Arabic/English, matches backend rules.
 * All errors returned as i18n key strings (use with t()).
 */

import axios from 'axios';

export type ValidationErrors = Record<string, string>;

export interface FieldMeta {
  value: string;
  label: string; // i18n key for the field label (e.g. 'auth.validationEmail')
}

/** Check if email format is valid */
export function isValidEmail(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
}

/** Check if phone matches Syrian format (09XX XXX XXX / +963...) */
export function isValidPhone(phone: string): boolean {
  if (!phone.trim()) return true; // optional
  return /^(?:\+963|0)9\d{8}$/.test(phone.replace(/\s/g, ''));
}

/** Password strength: ≥8, uppercase, lowercase, digit, special */
export function getPasswordErrors(password: string, _tLabel: string): string[] {
  const errors: string[] = [];
  if (password.length < 8) {
    errors.push('auth.passwordMin');
  }
  if (!/[A-Z]/.test(password)) {
    errors.push('auth.passwordUppercase');
  }
  if (!/[a-z]/.test(password)) {
    errors.push('auth.passwordLowercase');
  }
  if (!/[0-9]/.test(password)) {
    errors.push('auth.passwordNumber');
  }
  if (!/[!@#$%^&*()_\-+=<>?\/{}\[\]~`|\\:;'",.<>]/.test(password)) {
    errors.push('auth.passwordSpecial');
  }
  return errors;
}

/** Validate login form. Returns { field: i18nKey } map */
export function validateLogin(data: { email: string; password: string }): ValidationErrors {
  const errs: ValidationErrors = {};
  if (!data.email.trim()) errs.email = 'auth.emailRequired';
  else if (!isValidEmail(data.email)) errs.email = 'auth.emailInvalid';
  if (!data.password) errs.password = 'auth.passwordRequired';
  return errs;
}

/** Validate register form */
export function validateRegister(data: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
}): ValidationErrors {
  const errs: ValidationErrors = {};
  if (!data.name.trim()) errs.name = 'auth.nameRequired';
  if (!data.email.trim()) errs.email = 'auth.emailRequired';
  else if (!isValidEmail(data.email)) errs.email = 'auth.emailInvalid';
  if (!data.password) errs.password = 'auth.passwordRequired';
  else {
    const pwErrs = getPasswordErrors(data.password, '');
    if (pwErrs.length > 0) errs.password = pwErrs[0]; // show first error
  }
  if (data.password !== data.password_confirmation) {
    errs.password_confirmation = 'auth.passwordMismatch';
  }
  if (data.phone && data.phone.trim() && !isValidPhone(data.phone)) {
    errs.phone = 'auth.phoneInvalid';
  }
  return errs;
}

/** Validate agency register form */
export function validateAgencyRegister(data: {
  owner_name: string;
  owner_email: string;
  owner_phone: string;
  password: string;
  password_confirmation: string;
  agency_name: string;
  license_no?: string;
  agency_email?: string;
  agency_phone?: string;
  whatsapp?: string;
  address?: string;
}): ValidationErrors {
  const errs: ValidationErrors = {};
  // Step 1: Owner
  if (!data.owner_name.trim()) errs.owner_name = 'auth.nameRequired';
  if (!data.owner_email.trim()) errs.owner_email = 'auth.emailRequired';
  else if (!isValidEmail(data.owner_email)) errs.owner_email = 'auth.emailInvalid';
  if (!data.owner_phone.trim()) errs.owner_phone = 'auth.phoneInvalid';
  else if (!isValidPhone(data.owner_phone)) errs.owner_phone = 'auth.phoneInvalid';
  if (!data.password) errs.password = 'auth.passwordRequired';
  else {
    const pwErrs = getPasswordErrors(data.password, '');
    if (pwErrs.length > 0) errs.password = pwErrs[0];
  }
  if (data.password !== data.password_confirmation) {
    errs.password_confirmation = 'auth.passwordMismatch';
  }
  // Step 2: Agency
  if (!data.agency_name.trim()) errs.agency_name = 'auth.nameRequired';
  // Optional fields — validate format if provided
  if (data.agency_email && data.agency_email.trim() && !isValidEmail(data.agency_email)) {
    errs.agency_email = 'auth.emailInvalid';
  }
  return errs;
}

/** Validate forgot password form */
export function validateForgotPassword(data: { email: string }): ValidationErrors {
  const errs: ValidationErrors = {};
  if (!data.email.trim()) errs.email = 'auth.emailRequired';
  else if (!isValidEmail(data.email)) errs.email = 'auth.emailInvalid';
  return errs;
}

/** Validate reset password form */
export function validateResetPassword(data: {
  email: string;
  password: string;
  password_confirmation: string;
}): ValidationErrors {
  const errs: ValidationErrors = {};
  if (!data.email.trim()) errs.email = 'auth.emailRequired';
  else if (!isValidEmail(data.email)) errs.email = 'auth.emailInvalid';
  if (!data.password) errs.password = 'auth.passwordRequired';
  else {
    const pwErrs = getPasswordErrors(data.password, '');
    if (pwErrs.length > 0) errs.password = pwErrs[0];
  }
  if (data.password !== data.password_confirmation) {
    errs.password_confirmation = 'auth.passwordMismatch';
  }
  return errs;
}

/** Validate change password form */
export function validateChangePassword(data: {
  current_password: string;
  password: string;
  password_confirmation: string;
}): ValidationErrors {
  const errs: ValidationErrors = {};
  if (!data.current_password) errs.current_password = 'auth.currentPasswordRequired';
  if (!data.password) errs.password = 'auth.passwordRequired';
  else {
    const pwErrs = getPasswordErrors(data.password, '');
    if (pwErrs.length > 0) errs.password = pwErrs[0];
  }
  if (data.password !== data.password_confirmation) {
    errs.password_confirmation = 'auth.passwordMismatch';
  }
  return errs;
}

/** Validate profile form */
export function validateProfile(data: {
  name: string;
  phone?: string;
}): ValidationErrors {
  const errs: ValidationErrors = {};
  if (!data.name.trim()) errs.name = 'auth.nameRequired';
  if (data.phone && data.phone.trim() && !isValidPhone(data.phone)) {
    errs.phone = 'auth.phoneInvalid';
  }
  return errs;
}

/**
 * Parse Laravel validation error response into { field: message } map.
 * Handles both string and array error formats.
 */
export function parseServerErrors(errorData: unknown): ValidationErrors {
  const errs: ValidationErrors = {};
  if (!errorData || typeof errorData !== 'object') return errs;

  const data = errorData as Record<string, unknown>;

  // Laravel validation errors structure: { message, errors: { field: [...] } }
  const fieldErrors = data.errors as Record<string, string[]> | undefined;
  if (fieldErrors) {
    for (const [field, messages] of Object.entries(fieldErrors)) {
      if (Array.isArray(messages) && messages.length > 0) {
        errs[field] = messages[0];
      }
    }
    return errs;
  }

  // Flat error: { field: message }
  if (data.email) errs.email = String(data.email);
  if (data.message) errs._general = String(data.message);

  return errs;
}

/**
 * Extract user-facing error message from an Axios error response.
 * Handles Laravel validation errors, generic messages, and connection failures.
 * Returns either an already-translated server message or an i18n key for fallback.
 * WARNING: caller should wrap with t() for safety — t() passes non-key strings through.
 */
export function extractServerError(err: unknown, fallbackKey = 'auth.connectionError'): string {
  if (axios.isAxiosError(err) && err.response?.data) {
    const data = err.response.data as Record<string, unknown>;
    // Laravel: { errors: { field: [message] } }
    const fieldErrors = data.errors as Record<string, string[]> | undefined;
    if (fieldErrors) {
      const firstKey = Object.keys(fieldErrors)[0];
      if (firstKey && fieldErrors[firstKey].length > 0) return fieldErrors[firstKey][0];
    }
    // Generic: { message: "..." }
    if (typeof data.message === 'string') return data.message;
  }
  return (err as any)?.message || fallbackKey;
}
