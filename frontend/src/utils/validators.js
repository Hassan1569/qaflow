/**
 * QAFlow — Frontend Validators
 * 
 * Client-side validation for form UX. The backend always performs the
 * authoritative validation, but these checks prevent unnecessary network
 * requests and provide immediate feedback.
 */

export const isValidEmail = (email) => {
  if (!email) return false;
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(String(email).toLowerCase());
};

export const isRequired = (value) => {
  if (value === null || value === undefined) return false;
  if (typeof value === 'string') return value.trim().length > 0;
  if (Array.isArray(value)) return value.length > 0;
  return true;
};

export const hasMinLength = (value, min) => {
  if (!value) return false;
  return String(value).trim().length >= min;
};

export const hasMaxLength = (value, max) => {
  if (!value) return true;
  return String(value).trim().length <= max;
};

/**
 * Returns an error message string, or null if valid.
 */
export const validateField = (value, rules) => {
  if (rules.required && !isRequired(value)) {
    return 'This field is required.';
  }

  if (value && rules.email && !isValidEmail(value)) {
    return 'Please enter a valid email address.';
  }

  if (value && rules.minLength && !hasMinLength(value, rules.minLength)) {
    return `Must be at least ${rules.minLength} characters.`;
  }

  if (value && rules.maxLength && !hasMaxLength(value, rules.maxLength)) {
    return `Must be at most ${rules.maxLength} characters.`;
  }

  if (rules.custom && typeof rules.custom === 'function') {
    return rules.custom(value);
  }

  return null;
};