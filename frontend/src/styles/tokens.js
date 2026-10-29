/**
 * QAFlow — Design Tokens
 *
 * Mirrors the CSS variables defined in index.css. Use this file when a
 * component needs a literal color code (e.g. Recharts fills, inline styles).
 * For HTML elements, always use Tailwind utility classes.
 */

export const COLORS = {
  light: {
    bg: '#f7f8fa',
    surface: '#ffffff',
    border: '#e5e7eb',
    text: '#111827',
    textMuted: '#6b7280',
    primary: '#2563eb',
    primaryHover: '#1d4ed8',
    primaryLight: '#eff6ff',
    
    pass: '#16a34a',
    fail: '#dc2626',
    blocked: '#d97706',
    notRun: '#6b7280',
    info: '#0ea5e9',
    
    critical: '#b91c1c',
    high: '#c2410c',
    medium: '#a16207',
    low: '#15803d',
  },
  dark: {
    bg: '#0b0f17',
    surface: '#111827',
    border: '#1f2937',
    text: '#f3f4f6',
    textMuted: '#9ca3af',
    primary: '#3b82f6',
    primaryHover: '#60a5fa',
    primaryLight: '#1e3a8a',
    
    pass: '#22c55e',
    fail: '#ef4444',
    blocked: '#f59e0b',
    notRun: '#9ca3af',
    info: '#38bdf8',
    
    critical: '#ef4444',
    high: '#f97316',
    medium: '#eab308',
    low: '#22c55e',
  },
};

/**
 * Helper to get the correct palette based on the current theme.
 * Assumes the theme is controlled by the `dark` class on <html>.
 */
export const getThemeColors = () => {
  if (typeof document !== 'undefined' && document.documentElement.classList.contains('dark')) {
    return COLORS.dark;
  }
  return COLORS.light;
};