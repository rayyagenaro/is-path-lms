# IS-Path Design System

> Maintained with `frontend-god-mode` and `ui-ux-pro-max`.
> This is the source of truth for IS-Path typography, color, layout, components, and motion.
> Read this file before changing any interface. A mismatched implementation must be corrected to this document; do not rewrite the document to justify visual drift.

## Aesthetic direction

**Calm editorial career-tech** — a focused, credible learning workspace that feels human and considered: warm paper-like canvas, confident forest typography, restrained emerald actions, asymmetric editorial hierarchy, and only enough motion to clarify progress.

The interface should feel like a thoughtful career adviser, not a generic SaaS template. Information is dense enough to be useful, but grouped by decisions and next actions rather than enclosed in endless cards.

## Dials

- `DESIGN_VARIANCE: 6 / 10` — recognizable structure with deliberate asymmetry and varied spans.
- `MOTION_INTENSITY: 6 / 10` — visible but calm feedback; no spectacle in task flows.
- `VISUAL_DENSITY: 5 / 10` — balanced workspace density with compact metadata and comfortable reading rhythm.

## Implementation context

- Preserve the existing Laravel, Blade, CSS, and JavaScript architecture.
- Do not migrate the UI to React, Tailwind, or another framework solely for styling.
- Prefer shared tokens and reusable Blade/component classes over page-specific values.
- Desktop and mobile use the same semantic components, with layout reflow rather than separate visual systems.

## Typography

### Progress and evidence conventions

- Progress fills are visible on first paint; scroll observers must not hide recorded values.
- Use the same numeric source for the fill and its visible percentage. Proficiency level (0–5) is a separate band, not a percentage.
- Evidence actions open the relevant competency history, not an unrelated catalog.
- Saving a project reference does not award evidence or change scores before assessment.
- Product vocabulary: bidang, peran, kesiapan peran, kompetensi, sumber bukti. Keep assessment and post-assessment consistent with existing navigation; do not expose revision suffixes as topic names.

- Family: **Poppins** for all interface and content typography.
- Weights: `400` body, `500` supporting emphasis, `600` controls and section headings, `700` display headings only.
- Fallback: `"Poppins", sans-serif`.
- The ASCII layer is a non-semantic canvas/pixel-cell effect; it must not introduce a second visible content typeface.

```css
:root {
  --font-sans: "Poppins", sans-serif;

  --text-xs: 0.75rem;      /* 12px metadata floor */
  --text-sm: 0.875rem;     /* 14px supporting copy */
  --text-base: 1rem;       /* 16px body and controls */
  --text-lg: 1.125rem;     /* 18px card/section titles */
  --text-xl: 1.5rem;       /* 24px page section heading */
  --text-display: 2rem;    /* 32px desktop page display */

  --leading-tight: 1.18;
  --leading-heading: 1.28;
  --leading-body: 1.65;
}
```

- Body copy must remain at least `14px`; interactive labels should normally be `14–16px`.
- `12px` is reserved for metadata, eyebrows, tags, and table annotations. Never use text below `12px`.
- Eyebrows may use uppercase at weight `600`, but letter spacing must remain restrained (`0.08em–0.12em`).
- Prefer sentence case for headings, buttons, navigation, filters, and statuses.
- Keep reading copy at `55–68ch`.

## Color system

```css
:root {
  /* Primitive palette */
  --color-canvas: #F7F8F4;
  --color-surface: #FCFDFB;
  --color-forest: #073D2D;
  --color-emerald: #0B7A55;
  --color-sage: #E3F0E9;
  --color-ink: #14241E;
  --color-muted: #586861;
  --color-line: #D9E4DC;
  --color-amber: #C57A18;
  --color-coral: #B85445;

  /* Semantic aliases */
  --bg-page: var(--color-canvas);
  --bg-surface: var(--color-surface);
  --bg-subtle: var(--color-sage);
  --text-strong: var(--color-ink);
  --text-muted: var(--color-muted);
  --border-default: var(--color-line);
  --action-primary: var(--color-emerald);
  --action-primary-hover: var(--color-forest);
  --status-success: var(--color-emerald);
  --status-gap: var(--color-amber);
  --status-danger: var(--color-coral);
  --focus-ring: rgba(11, 122, 85, 0.32);
}
```

- Canvas is warm and quiet; surfaces are off-white rather than pure white.
- Forest anchors headings and high-value numbers. Ink is the default body color.
- Emerald is reserved for primary actions, active navigation, progress, and confirmed success.
- Sage is a supporting surface or selected state, never the default fill for every module.
- Amber identifies a skill gap or attention state. Coral is reserved for destructive actions and major gaps.
- Never rely on color alone for status; pair it with a label or icon.

## Spacing and layout

```css
:root {
  --space-1: 0.25rem;
  --space-2: 0.5rem;
  --space-3: 0.75rem;
  --space-4: 1rem;
  --space-5: 1.25rem;
  --space-6: 1.5rem;
  --space-8: 2rem;
  --space-10: 2.5rem;
  --space-12: 3rem;
  --space-16: 4rem;

  --radius-control: 10px;
  --radius-card: 16px;
  --radius-feature: 24px;
}
```

- Main content uses the available workspace up to `1680px` on wide desktop screens, with `28–48px` fluid side padding; mobile keeps at least `16px` side padding.
- Prefer asymmetric `8 / 4` or `7 / 5` grids for a primary decision plus supporting context.
- Grid children use intrinsic height and `align-items: start`; never stretch sparse side panels to match tall content.
- One page section does not automatically mean one card. Use whitespace, rules, and typography before adding a container.
- On mobile, reflow to one column, keep the primary action near its related content, and use progressive disclosure for long collections.
- Career Discovery initially shows the best `5–6` matches on mobile; remaining results require an explicit “Lihat lainnya” action.

## Elevation and borders

```css
:root {
  --shadow-rest: 0 1px 2px rgba(7, 61, 45, 0.04);
  --shadow-lift: 0 14px 36px -24px rgba(7, 61, 45, 0.28);
}
```

- Default cards use a `1px` line border and `--shadow-rest`; do not combine heavy borders and heavy shadows.
- Interactive surfaces may use `--shadow-lift` on hover/focus while moving no more than `-1px` vertically.
- Nested cards should be exceptional. Prefer dividers or tinted subsections inside a parent surface.

## Components

### Buttons

- Minimum height: `44px`; mobile primary controls may use `48px`.
- Horizontal padding: `16–20px`; gap between icon and label: `8px`.
- Radius: `--radius-control` (`10px`).
- Primary: emerald fill, off-white label, forest hover.
- Secondary: surface fill, emerald border and label.
- Ghost: transparent, forest/emerald label, sage hover.
- Keep only one visually dominant primary action per section.
- Labels use specific verbs, for example “Lanjutkan pembelajaran”, “Lihat jalur belajar”, or “Hitung rekomendasi”.

### Icon buttons

- Size: `44px × 44px`, including search, notification, menu, and dismiss actions.
- Use one SVG icon family with consistent `1.75px–2px` stroke.
- Every icon-only control requires an accessible name and visible focus state.

### Inputs and filters

- Minimum height: `44px`, radius `10px`, surface background, line border.
- Focus uses a `2px` emerald ring with offset; error uses coral plus an explanatory message.
- Labels remain visible; placeholders are examples, never substitutes for labels.
- Filters animate state changes but never resize the page unexpectedly.

### Cards and data modules

- Standard card radius: `16px`; feature/hero radius: `24px`.
- Prefer one clear heading, one primary value or decision, and one related action per card.
- KPI values can share an open row; do not default to four identical bordered tiles.
- Course cards use category, outcome, level, duration, and state. Never use decorative numbered pseudo-thumbnails as fake imagery.
- Skill and readiness rows use a stable text column, progress track, numeric value, and explicit status label.

### Navigation

- Active state uses sage background, forest text, and a consistent SVG icon.
- Use one vocabulary throughout the product. Default Indonesian labels are preferred; do not mix “Career Discovery” and “Jelajah Karier” in the same navigation system.
- Brand spelling is always **IS-Path**, without a trailing period.

## Motion

```css
:root {
  --ease-natural: cubic-bezier(.16, 1, .3, 1);
  --duration-fast: 180ms;
  --duration-base: 320ms;
  --duration-slow: 640ms;
  --stagger-step: 40ms;
}
```

- Animate only `transform` and `opacity` for entrances, exits, and hover feedback.
- Page/section entrance: `280–420ms` with `--ease-natural`.
- Sequential content: `40ms` stagger, capped after the first `6–8` visible items.
- Progress bars and number changes: approximately `640ms`; render their final semantic value immediately for assistive technology.
- Hover/focus movement: maximum `translateY(-1px)` over `180ms`.
- Never animate width, height, top, left, or layout properties. For progress fills, use `transform: scaleX()` with `transform-origin: left`.
- No bounce, elastic easing, perpetual floating, scroll hijacking, or large parallax.

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    scroll-behavior: auto !important;
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

## ASCII career signal

- ASCII animation is allowed **only on the login experience**, inside the forest editorial panel.
- Purpose: a quiet, abstract signal/path motif that suggests exploration and direction—not a terminal, hacker, or retro theme.
- Render at `14fps` maximum with low visual prominence (`0.06–0.10` opacity) and no interaction dependency.
- Keep it behind content, clip it to the login panel, pause it when the document or panel is not visible, and avoid work on every animation frame beyond the capped interval.
- `prefers-reduced-motion` receives one static frame. Login, keyboard navigation, and authentication must work with the layer absent.
- Do not use ASCII animation in dashboards, navigation, forms, course cards, progress indicators, or career results.

## Copy and voice

- Tone: direct, encouraging, concrete, and professionally warm.
- Explain why a recommendation appears and identify the next useful action.
- Prefer “Berdasarkan kekuatanmu…” over vague promotional claims.
- Avoid generic filler such as “elevate”, “seamless”, “unleash”, “revolutionary”, and “next-level”.
- Avoid excessive exclamation marks and praise. Let evidence and progress communicate momentum.

## Accessibility floor

- Meet WCAG 2.2 AA contrast: `4.5:1` for body text and `3:1` for large text and essential UI boundaries.
- All interactive elements have a visible `:focus-visible` state.
- Keyboard order follows visual reading order; no hover-only actions.
- Touch/click targets are at least `44px × 44px`, with at least `8px` between adjacent mobile targets.
- Use correct heading order, real form labels, accessible names, and status text announced appropriately.
- Never communicate readiness, success, warning, or failure using color alone.
- Every animated experience respects reduced motion.

## Project-specific bans

- No card soup, unnecessary nested cards, or grids of generic equal-sized feature tiles.
- No purple-to-blue gradients, glassmorphism, neon glows, oversized blur blobs, or decorative noise over content.
- No pure `#000000` or `#FFFFFF` as dominant page colors.
- No metadata below `12px`, low-contrast placeholder-like copy, or excessive uppercase tracking.
- No inconsistent button heights, icon sizes, corner radii, or icon families.
- No fake image placeholders, decorative course numbers presented as thumbnails, emoji UI icons, or unexplained decorative badges.
- No `100vh`/`h-screen` content traps; use natural document flow or `min-height: 100dvh` only where appropriate.
- No hidden content beneath sticky actions. Sticky elements must reserve their own layout space and remain dismissible or naturally bounded.
- No rendering all career matches as an unbroken mobile page; use ranking and progressive disclosure.
- No animation added solely to make a static element look “alive”. Motion must explain hierarchy, state, progress, or feedback.

## Pre-delivery check

- Check at `375px`, `390px`, `768px`, `1024px`, and `1440px`.
- Confirm page hierarchy remains obvious without borders and color.
- Confirm primary actions, inputs, and icon buttons meet the `44px` minimum.
- Confirm sparse panels do not stretch and sticky UI does not cover content.
- Confirm long lists use progressive disclosure and all empty/loading/error/success states are intentional.
- Confirm motion remains smooth and the reduced-motion experience is complete.
- Confirm all visible language and IS-Path branding are consistent.

## Last updated

- 2026-09-20 - Consolidated global control and radius tokens, moved the tablet shell breakpoint to 920px, restored explicit keyboard focus on form fields, and replaced the oversized composite logo sheet in navigation with the established text monogram. The original source image remains untouched for future official asset export.

- 2026-09-09 - Interview audit: explicit unavailable-material states, knowledge-versus-interest wording, state-aware career actions, session recovery with tab-local assessment drafts, persistent career comparison, and dynamic coverage counts. Drafts are not server backups; assessment results are learning indicators, not professional certification.

- 2026-09-09 - Fixed card-link styles overriding primary buttons; aligned assessment headers with the workspace, moved question padding to an outer container, made trackers fit their panel, and standardized readable action labels and 44px compact controls. Forest/emerald and Poppins remain the shared identity.

- 2026-09-09 - Added gated first-session onboarding for students without pre-assessment, plus a five-question assessment workspace with direct question tracking, meaningful page transitions, and a cross-page 12px metadata floor. ASCII remains login-only.

- 2026-09-06 - Replaced Career Discovery progressive load-more with native pagination. Career listing keeps stable 8-item pages, preserves active filters in page links, and uses IS-Path control sizing for accessible previous, next, and page states.

- 2026-09-06 — Added stage-aware exploration and module learning. Career scores, readiness, target selection, and personalized paths stay locked until post-assessment; pre-gate pages emphasize interest-led courses and ordered module progress.

- 2026-09-06 — Separated profile-based initial match from evidence-based readiness. Before post-assessment, readiness and competency gaps use an explicit unmeasured state and guide the student to the next valid action.

- 2026-09-06 — Added the assessment workspace pattern: open editorial list rows, a focused question surface, native accessible radio controls, live completion progress, an explicit submission state, and explainable competency results. ASCII remains restricted to authentication.

- 2026-09-06 — Refined the authenticated workspace into a wider editorial layout, aligned the top bar with page content, flattened KPI cards into an information band, standardized row actions and control sizing, and introduced a responsive dashboard hierarchy with separate priority and portfolio context.

- 2026-09-06 — Simplified the authentication experience, removed promotional labels and numbered explainer blocks from login, added a database-backed student registration flow, and established a restrained read-only guest preview.
- 2026-09-05 — Established the calm editorial career-tech direction, production tokens, component rules, natural motion system, anti-slop constraints, and login-only ASCII career signal.
