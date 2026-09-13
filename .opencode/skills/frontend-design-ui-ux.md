---
name: frontend-design-ui-ux
description: World-class UI/UX and visual design standards for this ERP's Livewire/Tailwind frontend - dashboards, forms, tables, POS, and the erp-* design system. Trigger keywords - UI, UX, design, frontend, component, dashboard, form, blade, Tailwind, accessibility, erp-, POS screen, layout, settings page.
---

# Frontend Design & UI/UX — Enterprise ERP Standard

Approach this as the design lead for an enterprise software vendor whose
customers are furniture retail/wholesale businesses in Bangladesh — not
a marketing site, not a consumer app. The audience is cashiers, warehouse
staff, accountants, and owners using this for 8+ hours a day. The job of
this UI is to get out of the way: fast, legible, low-error, and
consistent — never decorative for its own sake. A beautiful screen that
slows a cashier down or hides a validation error is a failed design.

## 0. Non-negotiable: use the existing system, don't reinvent it

This project already has an `erp-*` design system (6 base components)
and a working dark/light/system theme via Alpine `$store`. Before
creating any new visual pattern, check whether an `erp-*` component
already covers it. A new one-off button, card, or table style is a
defect, not a feature — it fragments the system and every future screen
then has to reconcile against two different patterns. If a genuine gap
exists, extend the design system with a properly named new `erp-*`
component in the same location as the existing six, rather than styling
inline in a Blade view.

## 1. Information density and hierarchy over decoration

Enterprise data screens are judged on how fast a trained user finds what
they need, not on first impression. Default to:
- Dense, well-aligned tables over card grids for lists of records
  (products, transactions, stock movements). Cards are for a handful of
  glanceable summary items (dashboard KPIs), not for 50-row lists.
- One clear primary action per screen, not three buttons of equal visual
  weight competing for attention.
- Numbers right-aligned in tables (currency, quantities); text
  left-aligned. Always render money as `৳ 12,450` — symbol plus
  thousands separator — never a bare number.
- Status is a color-coded label with text, never color alone: it must
  survive colorblind users and a black-and-white printed report.

## 2. A real semantic color system, not ad hoc classes

Define status meaning once, reuse everywhere: paid/unpaid,
in-stock/low-stock/out-of-stock, approved/pending/rejected,
active/suspended all map to the same 4-5 semantic tokens (`success`,
`warning`, `danger`, `neutral`, `info`), never a fresh color choice per
screen. This also removes the Tailwind 4 JIT purge risk already flagged
in the audit (`bg-{{ $color }}-100` gets silently dropped by the
compiler) — map status to a static class per token, never interpolate a
color name into a class string.

```php
// Bad - purge-unsafe, a new ad hoc mapping per screen
<span class="bg-{{ $status }}-100 text-{{ $status }}-700">{{ $status }}</span>

// Good - static map, purge-safe, consistent everywhere it's used
@php
  $statusClasses = [
    'paid' => 'erp-badge-success',
    'pending' => 'erp-badge-warning',
    'void' => 'erp-badge-danger',
  ];
@endphp
<span class="{{ $statusClasses[$status] ?? 'erp-badge-neutral' }}">
  {{ ucfirst($status) }}
</span>
```

## 3. Typography

One type family for the whole app (already loaded via Vite + Bunny
Fonts). Enterprise software does not need a separate display face for
headlines — that's a marketing-site instinct, and mixing faces here
reads as inconsistent rather than intentional. Express hierarchy with
weight and size, not font-switching. Use tabular figures
(`font-variant-numeric: tabular-nums`) on any column of numbers so
digits align vertically — this matters far more here than on a landing
page, since users scan financial columns for outliers.

## 4. Forms — this is where trust is won or lost

- Every invalid field gets `aria-invalid="true"` and an inline error
  directly under the field, in plain language stating what's wrong and
  how to fix it — never a bare "Invalid input."
- Validate on blur for expensive checks (e.g. SKU uniqueness), inline
  for cheap checks (required, format, range).
- Multi-step forms (PO creation, sale returns, employee onboarding)
  always show progress and let the user go back without losing already
  entered data.
- Destructive actions (void a sale, delete a product, remove a branch)
  require a confirmation step that names the specific record — "Void
  sale #4021 for ৳3,200?" — never a generic "Are you sure?"
- If a submit button is disabled, say why right next to it ("Add at
  least one line item") — a silently disabled button is a dead end for
  the user.

## 5. Tables (the ERP's most common surface)

- `scope="col"` on every header cell (flagged missing in the audit — fix
  as you touch each table).
- Sticky header on scroll for any table likely to exceed one screen.
- Sortable columns visually show current sort direction, not only on
  hover.
- Empty state is a directive, not a blank grid: "No products in this
  category yet — [Add product]," not an empty table with no explanation.
- Pagination over infinite scroll for anything a user needs to reference
  later — "page 3 of stock movements" is a real, repeatable user need
  that infinite scroll destroys.

## 6. POS-specific rules (already the most polished module — protect it)

- Speed beats aesthetics here. Every added visual flourish costs a
  cashier milliseconds per transaction, multiplied across hundreds of
  sales a day. No animation on the product grid or cart beyond an
  instant state change.
- Preserve and extend the existing F2–F12 keyboard shortcuts — any new
  POS feature needs a keyboard path, not only a mouse/touch one.
- Minimum touch target 44×44px — this may run on a touchscreen terminal,
  not only a desktop with a mouse.
- Held sales, barcode scan feedback, and payment confirmation each need
  an unmistakable, distinct visual and sound cue, so a busy cashier
  doesn't have to stop and read text to know what just happened.

## 7. Motion — near-zero, and only functional

No entrance animations, no hover-lift-and-shadow on cards, no page
transition effects. The only motion that belongs here answers a user's
own action: a row collapsing after deletion, a toast sliding in, a save
button showing a brief spinner then a checkmark. Respect
`prefers-reduced-motion` everywhere motion exists.

## 8. Accessibility floor (non-negotiable, not aspirational)

- Visible keyboard focus ring on every interactive element — never
  `outline: none` without a replacement focus style.
- Text contrast meets WCAG AA at minimum, including text inside status
  badges (a light badge background with pale text is a common failure).
- `aria-live="polite"` on the toast/notification region (flagged missing
  in the audit).
- Icon-only buttons always carry an `aria-label` — never rely on a
  tooltip alone; tooltips don't reliably reach screen readers or touch
  devices.

## 9. Dark / light / system theme discipline

Every new component must be checked in both themes before being called
done — not built in light mode and "fixed for dark later." Use the
existing theme CSS variables; never hardcode a hex color in a new
component's Blade markup or scoped CSS.

## 10. Avoid these enterprise-SaaS tells

Generic AI-generated dashboards cluster around specific defaults — avoid
them unless a real requirement calls for it:
- Purple-to-blue gradient sidebars or headers with no connection to the
  furniture-retail domain or the existing brand.
- Cards nested inside cards inside cards for what is really just one
  list of records.
- A KPI row of four identical rounded cards, each with an icon, a big
  number, and a tiny trend arrow — fine occasionally, but if every
  dashboard screen in the app defaults to this, it's a habit, not a
  decision made for that screen.
- Ambiguous icon-only action buttons in a table row (a pencil, a trash
  can, three dots) with no label and no confirmation step on the
  destructive one.
- A settings page as a flat list of toggles with no grouping and no
  statement of consequence — group by what the setting affects, and say
  what changes when it's toggled.

## 11. Writing in the interface

- Buttons state the action's result: "Post to ledger," "Void sale," not
  "Submit" or "Confirm."
- Errors state what happened and what to do, in the system's voice, with
  no apology: "Stock unavailable — only 3 units left in Branch 2," not
  "Oops, something went wrong!"
- Use plain language a shop owner actually uses, not internal system
  terms: "Branch," not "Location Entity"; "Needs approval," not
  "Pending workflow state."
- If a surface is bilingual (Bengali/English), keep numeral and currency
  formatting consistent with local convention (৳ symbol, comma-grouped)
  regardless of which language the labels are shown in.

## 12. Before calling any UI task done

- [ ] Reused an existing `erp-*` component, or added one properly
      instead of styling inline
- [ ] Checked in both light and dark theme
- [ ] Keyboard-only pass: can the flow be completed with no mouse?
- [ ] Screen reader labels present on every icon-only control
- [ ] No dynamic Tailwind class interpolation
- [ ] Status shown as color + label, never color alone
- [ ] Empty / loading / error states designed, not left as a blank
      screen
