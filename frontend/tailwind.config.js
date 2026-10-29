/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Base surfaces
        bg: 'var(--color-bg)',
        surface: 'var(--color-surface)',
        border: 'var(--color-border)',
        
        // Text
        text: 'var(--color-text)',
        'text-muted': 'var(--color-text-muted)',
        
        // Primary brand
        primary: 'var(--color-primary)',
        'primary-hover': 'var(--color-primary-hover)',
        'primary-light': 'var(--color-primary-light)',
        
        // Semantic statuses
        pass: 'var(--color-pass)',
        fail: 'var(--color-fail)',
        blocked: 'var(--color-blocked)',
        'not-run': 'var(--color-not-run)',
        info: 'var(--color-info)',
        
        // Priorities
        critical: 'var(--color-critical)',
        high: 'var(--color-high)',
        medium: 'var(--color-medium)',
        low: 'var(--color-low)',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'sans-serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace'],
      },
      boxShadow: {
        card: '0 1px 2px rgba(15, 23, 42, 0.04)',
        modal: '0 10px 30px rgba(15, 23, 42, 0.15)',
      },
      screens: {
        sm: '640px',
        md: '768px',
        lg: '1024px',
        xl: '1280px',
        '2xl': '1536px',
      },
    },
  },
  plugins: [],
}