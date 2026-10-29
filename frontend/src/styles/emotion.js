/**
 * QAFlow — Emotion theme
 *
 * A minimal theme object passed to Emotion's ThemeProvider (if used).
 * Since Tailwind handles 95% of the styling, this is only used for
 * complex dynamic components where Emotion's css prop is cleaner.
 */

export const emotionTheme = {
  colors: {
    light: {
      primary: '#2563eb',
      pass: '#16a34a',
      fail: '#dc2626',
      blocked: '#d97706',
      notRun: '#6b7280',
    },
    dark: {
      primary: '#3b82f6',
      pass: '#22c55e',
      fail: '#ef4444',
      blocked: '#f59e0b',
      notRun: '#9ca3af',
    },
  },
  fonts: {
    sans: 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
    mono: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
  },
  shadows: {
    card: '0 1px 2px rgba(15, 23, 42, 0.04)',
    modal: '0 10px 30px rgba(15, 23, 42, 0.15)',
  },
};