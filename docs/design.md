# QAFlow — Design System

## 1. Design Principles

- Flat surfaces. No gradients. Ever.
- Information density over decoration. This is a QA tool, not a marketing site.
- Status is a first-class visual concept. PASS / FAIL / BLOCKED / NOT RUN must be instantly recognizable.
- Consistency over novelty. If a pattern exists, reuse it.
- Accessibility is not optional.

## 2. Layout

- App shell: fixed sidebar (240 px) + navbar (56 px) + content area.
- Content max width: none. Enterprise screens use full width.
- Content padding: 24 px desktop, 16 px tablet.
- Grid: 12 columns, 24 px gutter.

## 3. Spacing

Base unit: 4 px.

Tokens: `4, 8, 12, 16, 20, 24, 32, 40, 48, 64`.

Use tokens from `tailwind.config.js`. No arbitrary pixel values in components.

## 4. Typography

Font: `Inter`, fallback `system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`.

Sizes:

- `text-xs` 12 px / 16 — labels, meta.
- `text-sm` 14 px / 20 — body, table cells.
- `text-base` 16 px / 24 — default.
- `text-lg` 18 px / 28 — card titles.
- `text-xl` 20 px / 28 — section titles.
- `text-2xl` 24 px / 32 — page titles.

Weights: 400 body, 500 emphasis, 600 headings, 700 page titles.

No letter-spacing hacks. No all-caps except small labels.

## 5. Color Tokens

Semantic tokens in `tailwind.config.js`.

Light theme:

- `bg` `#f7f8fa`
- `surface` `#ffffff`
- `border` `#e5e7eb`
- `text` `#111827`
- `text-muted` `#6b7280`
- `primary` `#2563eb`
- `primary-hover` `#1d4ed8`

Dark theme (class-based):

- `bg` `#0b0f17`
- `surface` `#111827`
- `border` `#1f2937`
- `text` `#f3f4f6`
- `text-muted` `#9ca3af`
- `primary` `#3b82f6`
- `primary-hover` `#60a5fa`

Status colors (light / dark):

- pass: `#16a34a` / `#22c55e`
- fail: `#dc2626` / `#ef4444`
- blocked: `#d97706` / `#f59e0b`
- not-run: `#6b7280` / `#9ca3af`
- info: `#0ea5e9` / `#38bdf8`

Priority colors:

- critical: `#b91c1c` / `#ef4444`
- high: `#c2410c` / `#f97316`
- medium: `#a16207` / `#eab308`
- low: `#15803d` / `#22c55e`

## 6. Border Rules

- Default border: 1 px solid `border` token.
- Cards: 1 px border, `rounded-md`.
- Inputs: 1 px border, focus ring 2 px `primary` with 2 px offset.
- Tables: horizontal dividers only, 1 px.

No rounded pills except `StatusBadge` and `PriorityBadge` (`rounded-full`).
No border radius over 8 px except avatars.

## 7. Radius

- `rounded-sm` 2 px — reserved.
- `rounded-md` 6 px — cards, inputs, buttons.
- `rounded-lg` 8 px — modals, drawers.
- `rounded-full` — badges, avatars.

## 8. Shadows

Subtle only.

- `shadow-card` = `0 1px 2px rgba(15, 23, 42, 0.04)`.
- `shadow-modal` = `0 10px 30px rgba(15, 23, 42, 0.15)`.

No glow, no colored shadows, no inset shadows.

## 9. Tables

- Header: `text-xs uppercase text-muted font-medium`, background `bg`.
- Rows: `text-sm`, 44 px min height.
- Hover: row background `bg` at 50 % opacity.
- Numeric columns right-aligned.
- First column may be sticky on horizontal scroll.
- Pagination footer: page size selector, page numbers, total count.

## 10. Forms

- Label above input, `text-xs font-medium text-muted mb-1`.
- Input height 36 px, `text-sm`, `rounded-md`, 1 px border.
- Focus: 2 px ring `primary`, offset 2 px.
- Error text below field, `text-xs` in `fail`.
- Required fields marked with `*` in `text-muted`.
- Help text below field, `text-xs text-muted`.

## 11. Buttons

Variants:

- `primary` — solid `primary` background, white text.
- `secondary` — surface background, 1 px border, text color.
- `ghost` — no background, text color, hover surface.
- `danger` — solid `fail` background, white text.

Sizes: `sm` (28 px), `md` (36 px), `lg` (44 px).

Always `rounded-md`, `text-sm font-medium`, no gradients.

## 12. Cards

- Surface background, 1 px border, `rounded-md`, `shadow-card`.
- Padding: 20 px.
- Title: `text-base font-semibold`.
- Meta: `text-xs text-muted`.

## 13. Modals

- Centered, max width 560 px (default), `rounded-lg`, `shadow-modal`.
- Header: title + close button.
- Body: scrollable if long.
- Footer: right-aligned actions.
- Backdrop: `rgba(15, 23, 42, 0.5)`.

## 14. Drawers

- Right-side, 480 px wide default, full height.
- Same header/body/footer structure as modals.
- Used for entity detail previews and quick edits.

## 15. Navigation

- Sidebar: dark surface (`#0f172a`) or light surface depending on theme.
- Active item: `primary` text, `primary` 10 % background.
- Group headings: `text-xs uppercase text-muted`.
- Icons: 18 px, aligned with text.

## 16. Sidebar

Sections:

- Dashboard
- Projects
- Requirements
- Test Cases
- Test Suites
- Test Plans
- Test Runs
- Defects
- Traceability
- Reports
- Environments
- Team
- Settings

Below `lg`, the sidebar collapses to an overlay drawer.

## 17. Dashboard

- Row of `StatCard`s at the top (responsive grid, min 160 px).
- Chart row below, `ChartCard` components.
- Recent activity panel on the right at `xl`.

## 18. Charts

- Use `recharts`.
- Colors from the token set. No gradients inside bars or areas.
- Tooltips flat, surface background, 1 px border.
- Gridlines light `border`, dashed.
- Legend bottom, `text-xs`.

## 19. Empty States

- Centered within the container.
- Icon (24 px, muted), title, one-line description, primary action.
- No illustrations. No decorative art.

## 20. Loading States

- Inline: skeleton rows matching table structure.
- Page: centered spinner plus "Loading…" text.
- Buttons: disabled with spinner replacing the label.

## 21. Error States

- Panel with `fail` left border 3 px.
- Title, message, and retry button.
- Never a raw stack trace.

## 22. Accessibility

- Contrast ratio ≥ 4.5:1 for body text, ≥ 3:1 for large text.
- Focus ring visible on all interactive elements.
- Keyboard navigation for tables, modals, dropdowns.
- ARIA roles only where semantics are insufficient.

## 23. Responsive Behavior

- Desktop first.
- Breakpoints: `sm` 640, `md` 768, `lg` 1024, `xl` 1280, `2xl` 1536.
- Sidebar collapses below `lg`.
- Tables gain horizontal scroll below `md`.

## 24. Dark Mode

- Class-based toggle on `<html>`.
- Semantic tokens swap via `.dark` selector.
- Chart colors swap with tokens.
- No inverted images; provide dark-safe assets if needed.

## 25. Animation Rules

- Use `gsap` only for:
  - Page-level fade/slide (150 ms).
  - Drawer and modal entrance (200 ms).
  - Chart entrance (300 ms, once).
- No bouncing. No floating. No animated gradients.
- Respect `prefers-reduced-motion`: disable non-essential transitions.

## 26. NO GRADIENTS

The design uses solid colors only. Do not use `linear-gradient`, `radial-gradient`, or `conic-gradient` anywhere in the codebase. This rule is enforced by review and by lint rules on the `styles/` directory.