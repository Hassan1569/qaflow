/**
 * QAFlow — Formatting Utilities
 *
 * Centralizes string, date, and number formatting to keep UI components clean.
 */

/**
 * Formats an ISO date string into a human-readable format.
 * @param {string} dateStr - e.g. "2026-09-01T09:00:00Z" or "2026-09-01"
 * @param {boolean} includeTime - Whether to append the time
 * @returns {string} - e.g. "Sep 1, 2026" or "Sep 1, 2026, 9:00 AM"
 */
export function formatDate(dateStr, includeTime = false) {
  if (!dateStr) return '—';
  
  try {
    const date = new Date(dateStr);
    
    // Check if the date is invalid
    if (isNaN(date.getTime())) return dateStr;

    const options = {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    };

    if (includeTime) {
      options.hour = 'numeric';
      options.minute = '2-digit';
    }

    return new Intl.DateTimeFormat('en-US', options).format(date);
  } catch (e) {
    return dateStr;
  }
}

/**
 * Formats a number into a percentage string.
 * @param {number} value - e.g., 85.5
 * @param {number} decimals - Number of decimal places (default 0)
 * @returns {string} - e.g., "86%"
 */
export function formatPercent(value, decimals = 0) {
  if (value === null || value === undefined) return '0%';
  return `${Number(value).toFixed(decimals)}%`;
}

/**
 * Truncates a string to a maximum length and appends an ellipsis.
 * @param {string} str
 * @param {number} maxLength
 * @returns {string}
 */
export function truncate(str, maxLength = 50) {
  if (!str) return '';
  if (str.length <= maxLength) return str;
  return `${str.substring(0, maxLength)}...`;
}

/**
 * Formats a duration in milliseconds to a readable string.
 * @param {number} ms
 * @returns {string}
 */
export function formatDuration(ms) {
  if (ms === null || ms === undefined) return '—';
  if (ms < 1000) return `${ms}ms`;
  
  const seconds = ms / 1000;
  if (seconds < 60) return `${seconds.toFixed(1)}s`;
  
  const minutes = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${minutes}m ${secs}s`;
}

/**
 * Generates initials from a name for avatars.
 * @param {string} name
 * @returns {string} - e.g., "Hassan Ali" -> "HA"
 */
export function getInitials(name) {
  if (!name) return '?';
  const parts = name.trim().split(' ');
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}