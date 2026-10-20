# AdPilot — Visual Design System, Component Library & Frontend Design Tokens Specification

**Document ID:** ADP-DOC-012  
**Filename:** `12_ADPILOT_VISUAL_DESIGN_SYSTEM_COMPONENT_LIBRARY_FRONTEND_TOKENS.md`  
**Version:** 1.0.0 (implementation-design proposal)  
**Status:** Proposed — requires UX Lead, Visual Designer, Frontend/Gutenberg Leads, Accessibility Lead, Security/Privacy Lead, Product Owner and QA sign-off  
**Date:** 2026-10-12  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Document owner:** Design System Lead / Lead Product Designer  
**Primary implementers:** React/TypeScript, CSS/frontend, Gutenberg, WordPress/PHP, analytics visualization, QA/accessibility and localization engineers  
**Implementation target:** GPT-6 High with bounded, verifiable work packages.  
**Normative predecessors:** ADP-DOC-001 through ADP-DOC-011.  
**Next document:** ADP-DOC-013 — Administrator Console UI & Dashboard Implementation Specification (proposed sequencing).  

> **Status and limitation:** This is a proposed technical design contract—not an existing component package, production UI, tested accessibility claim, final brand approval, or statement that source code has already been implemented. Hexadecimal palette values and dimensions are initial design tokens subject to visual approval and measurable contrast checks. Existing PRD, role, API, commercial and security obligations take precedence over decorative choices. Any new feature or change to an R1–R4 release boundary requires upstream change control.

---

## 1. Purpose, source precedence, non-goals and release scope

### 1.1 Purpose

Establish one consistent and implementable visual system across six experiences: (1) publisher/admin console; (2) visual Layout Studio; (3) advertiser portal; (4) publicly accessible inventory marketplace; (5) Gutenberg editor/WordPress theme integrations; and (6) analytics explorer/heatmaps. The same component API must provide accessible, internationalized and stable states, while correctly respecting backend authorization and campaign-financial truth. High-density advertising inventory is a model/editor challenge—not justification for 500 DOM-heavy interactive preview components.

### 1.2 Document authority and inherited identifiers

| Upstream document | Requirements this document must preserve |
|---|---|
| ADP-DOC-001 | All 129 `FR-*`, 18 `BR-*`, 16 `NFR-*`, 15 `AC-*`, 10 `INV-*`; R1–R4 release definitions, business semantics and open decisions |
| ADP-DOC-002 | `CAP-01..CAP-18`, `AT-*`, `UI-01..UI-20`, `E2E-01..17` and feature ownership |
| ADP-DOC-003 | 16 personas, `JTBD-01..24`, `UJ-01..28`, `WF-01..18`, `UXA-01..28` and complete UX state handling |
| ADP-DOC-004 | `DP-*`, `SM-*`, `PERM-001..016`; paid-inventory and financial state invariants |
| ADP-DOC-005 | WordPress site capabilities, advertiser organization membership, object scope and permission-aware frontend behavior |
| ADP-DOC-006 | Modular-monolith boundaries, public app isolation, integration contracts `IC-001..024` and ADRs |
| ADP-DOC-007 | Typed domain, application command/query projections, events and no frontend policy authority |
| ADP-DOC-008 | Stable slot/layout IDs, money precision, analytics read models, lifecycle/retention rules |
| ADP-DOC-009 | Versioned REST routes, protected projections, error envelopes, retries, revisions, page/query semantics |
| ADP-DOC-010 | Authoritative async job state, outbox/events, export/report freshness, payment reconciliation |
| ADP-DOC-011 | All 20 `UI-*` screen contracts, 28 journeys, responsive/navigation structure, `UX11-*` acceptance and accessibility |

**Normative terms:** MUST/SHALL: mandatory within the applicable release; SHOULD: expected unless exception is reviewed; MAY: optional. `R1` core sale/delivery; `R2` aggregated heatmaps and certain layout extensions; `R3` metered commercial models and optimization; `R4` optional replay/network features. A disabled/not-yet-developed capability MUST NOT appear as a working function.

### 1.3 Non-goals and prohibited shortcuts

- Not a replacement for WordPress's block editor, Site Editor, user-management system or `theme.json` engine.
- Not permission to introduce a mandatory proprietary component system, telemetry SDK, font or icon service.
- Not permission to rewrite backend terms or change order/approval/delivery state machines to simplify UI.
- Not permission to infer visitor gaze from cursor/scroll data or to claim heatmap pixels prove attention.
- Not permission to hide uncertainty, data freshness or measurement qualification behind attractive charts.
- Not permission to inject global CSS into publisher websites or all wp-admin screens.
- Not permission to use unlabeled color alone for money status, sold inventory, errors, heatmaps or analytics.
- Not permission to count live impressions from Gutenberg previews, Layout Studio, screenshots or staging.

### 1.4 Critical sign-off gates

UX lead approves information hierarchy and interaction parity; visual lead approves tokens and components; frontend lead approves performance/isolated styling; accessibility lead validates WCAG 2.2 AA target through tests; security/privacy validates private and consent-dependent UI; finance/ad operations validates metrics and commercial statuses; QA approves executable scenarios. A screenshot cannot prove accessibility, authorization, responsiveness or data correctness by itself.

---

## 2. Product visual identity and interaction principles

**Working direction:** modern enterprise software, calm and information-dense, with high-contrast typography, restrained electric-blue accents, spacious white/light surfaces, and dark neutral surfaces as an optional interface theme. Layouts should convey reliability and precision rather than entertainment. The public marketplace may use richer editorial imagery than the admin console, but its forms, feedback and measurement semantics remain the same.

**Visual hierarchy:** (1) task and decision; (2) relevant authoritative state; (3) action with consequence; (4) explanation/freshness; (5) peripheral secondary navigation. Prominent counts must include qualifiers: `500 configured slots`, `35 viewable impressions`, `Payment pending verification`, not generic `500 ads delivered` or `Paid` without authority.

**Density presets:** `comfortable` (onboarding, buying, mobile), `standard` (default ops), `compact` (data grids and slot navigation). Density changes only spacing and eligible row heights; it MUST NOT shrink semantic minimum interaction targets or obscure validation. User choice may be stored per authenticated user (where permitted); no tenant data is leaked into cross-user UI preferences.

**Information principles:** progressive disclosure; progressive data loading; discriminable status in text and shape; preview separated from live production; reversible local edits; authenticated server acknowledgment for irreversible actions; freshness and denominator labeling; accessible numeric alternatives for spatial visualizations.

**Visual surfaces:**

| Surface | Primary visual character | Special rules |
|---|---|---|
| Publisher console | Clear data-first workspace, low decoration | Role-scoped KPIs and financially precise status |
| Layout Studio | Three-pane desktop canvas with navigation tree and inspector | Bounded preview; stable ID visible; contract blockers salient |
| Advertiser portal | Guided action, offer details, trustworthy progress | Price units and checkout states explicit; never fake purchase success |
| Public marketplace | Theme-respecting catalog and offers | Scoped CSS; no global typography reset; safe creative previews |
| Gutenberg | Native-editor visual patterns with scoped AdPilot inspector controls | Preview is synthetic/nonbillable; editors' WP UI conventions win |
| Analytics explorer | High-information report and heatmap navigation | Metric definitions, confidence/limitations, accessible tables |

---

## 3. Design token architecture and naming

### 3.1 Three levels and namespace

1. **Primitive tokens** express raw color/spacing/typography/shadow values (`--adp-ref-blue-700`, `--adp-space-3`). No application component should directly depend on primitives where a semantic token exists.
2. **Semantic tokens** express an interface purpose (`--adp-color-fg-primary`, `--adp-color-border-critical`, `--adp-color-action-primary`). These change with theme/contrast mode.
3. **Component tokens** permit documented local adjustment (`--adp-button-min-height`, `--adp-table-row-min-height`). Component tokens reference semantic tokens, never raw literal colors in JSX.

Use `adp-` for CSS custom properties and `adp-` classes. JS token keys may use `color.fg.primary`, `space.3`, `typography.body.md`. Do not shadow WordPress `--wp-*` variables or public theme tokens; internally map from approved WP Design System packages where viable. Do not depend on private Gutenberg CSS classes or `__experimental` internals for a required feature.

### 3.2 Semantic color policy

- Every foreground/background pair used in real UI MUST be tested: normal text >= 4.5:1, large text >= 3:1; active boundaries/icons and meaningful non-text visual affordances typically >= 3:1 where WCAG 1.4.11 applies. Disabled/inactive controls have defined exceptions but still need understandable accessible states.
- **Never rely on color alone.** Pair colors with labels, icons or shapes; include a persistent status legend when multiple statuses are shown.
- Focus indicator is a designed system component, not default browser outline suppression.
- Heatmap palettes need a monochrome/high-contrast alternative and tabular equivalence.
- Financial green/red should not encode gain/loss without a signed value and textual interpretation.

### 3.3 Token governance

Tokens are changed through semantic versioned review. Prohibit unreviewed component-local arbitrary color hex values except third-party creative content/sanitized preview data. Every token addition must document use, contrast contexts, light/dark pair, RTL behavior where relevant, and visual regression coverage. Use CSS logical properties to avoid LTR assumptions. Support OS high contrast/forced colors independent of the dark theme.

---

## 4. Primitive color palette (initial proposal)

The following values constitute a concrete baseline for engineering prototypes, not a final branding decision. Explicitly test all real rendered foreground/background combinations; derived opacity layers and chart overlays require separate checks. Do not claim AA based solely on the presence of these values in a table.

| Primitive token | Hex | Proposed role |
|---|---|---|

| `--adp-ref-white` | `#FFFFFF` | Neutral white |
| `--adp-ref-ink-950` | `#101828` | Deep canvas |
| `--adp-ref-ink-900` | `#1D2939` | Dark card |
| `--adp-ref-ink-800` | `#344054` | Secondary dark |
| `--adp-ref-slate-700` | `#344054` | Strong neutral |
| `--adp-ref-slate-600` | `#475467` | Text secondary |
| `--adp-ref-slate-400` | `#98A2B3` | Muted strokes |
| `--adp-ref-slate-200` | `#EAECF0` | Borders |
| `--adp-ref-slate-100` | `#F2F4F7` | Muted fills |
| `--adp-ref-slate-50` | `#F9FAFB` | Canvas |
| `--adp-ref-blue-900` | `#0A3D91` | Brand pressed |
| `--adp-ref-blue-800` | `#124BA2` | Brand hover |
| `--adp-ref-blue-700` | `#1456B8` | Brand primary |
| `--adp-ref-blue-100` | `#DCEBFF` | Brand subtle |
| `--adp-ref-green-800` | `#116149` | Positive text |
| `--adp-ref-green-100` | `#D9F7E9` | Positive surface |
| `--adp-ref-red-800` | `#A91D1D` | Critical text |
| `--adp-ref-red-100` | `#FEE4E2` | Critical surface |
| `--adp-ref-amber-900` | `#814D0B` | Warning text |
| `--adp-ref-amber-100` | `#FFF1CE` | Warning surface |
| `--adp-ref-purple-800` | `#6840A6` | Informational/secondary series |
| `--adp-ref-purple-100` | `#EDE7FB` | Subtle purple |

### 4.1 Semantic light/dark assignment

The dark values are not calculated by simply inverting light colors. Rendering teams SHALL use explicit theme-specific pairs and verify contrast in the actual states. Example use of `color-action-primary` as foreground versus as background may require separate semantic tokens (`action-primary-bg` versus `action-link-fg`) before implementation; do not use a single token indiscriminately for both.

| Semantic token | Light | Dark | Purpose |
|---|---|---|---|

| `--adp-color-bg-canvas` | `#F9FAFB` | `#101828` | Page background |
| `--adp-color-bg-surface` | `#FFFFFF` | `#1D2939` | Cards and forms |
| `--adp-color-bg-elevated` | `#FFFFFF` | `#344054` | Menus/dialogs |
| `--adp-color-bg-subtle` | `#F2F4F7` | `#344054` | Sections and neutral panels |
| `--adp-color-fg-primary` | `#1D2939` | `#F9FAFB` | Primary text |
| `--adp-color-fg-secondary` | `#475467` | `#D0D5DD` | Secondary text |
| `--adp-color-fg-muted` | `#475467` | `#D0D5DD` | Muted readable information |
| `--adp-color-border-default` | `#98A2B3` | `#667085` | Input and structural borders: verify context |
| `--adp-color-border-subtle` | `#EAECF0` | `#475467` | Low-emphasis dividers, not sole meaningful boundaries |
| `--adp-color-action-primary` | `#1456B8` | `#8AB4FF` | Primary action background or text, context dependent |
| `--adp-color-action-primary-hover` | `#124BA2` | `#A6C7FF` | Action hover |
| `--adp-color-action-on-primary` | `#FFFFFF` | `#101828` | Text on action surface |
| `--adp-color-state-positive-fg` | `#116149` | `#9BE6C5` | Positive state text |
| `--adp-color-state-positive-bg` | `#D9F7E9` | `#123D32` | Positive background |
| `--adp-color-state-warning-fg` | `#814D0B` | `#FFD79A` | Warning text |
| `--adp-color-state-warning-bg` | `#FFF1CE` | `#4C361C` | Warning background |
| `--adp-color-state-critical-fg` | `#A91D1D` | `#FFC8C3` | Critical text |
| `--adp-color-state-critical-bg` | `#FEE4E2` | `#552020` | Critical background |
| `--adp-color-focus-ring` | `#1456B8` | `#9ABEFF` | Focus ring |
| `--adp-color-overlay` | `rgba(16,24,40,.56)` | `rgba(0,0,0,.72)` | Modal scrim |
| `--adp-color-chart-series-1` | `#1456B8` | `#9ABEFF` | Series color; paired with label |
| `--adp-color-chart-series-2` | `#116149` | `#94E3B6` | Series color; paired with label |
| `--adp-color-chart-series-3` | `#814D0B` | `#FFD79A` | Series color; paired with label |
| `--adp-color-chart-series-4` | `#6840A6` | `#CCAFFF` | Series color; paired with label |

## 5. Sizing, spacing, radius, typography and elevation

### 5.1 Spacing scale

Use `rem`-based tokens with a 16 px baseline in screenshots and tests; respect users' font scaling. A pixel figure below is a reference equivalent, not an excuse to use hardcoded `px` everywhere.

| Token | Base equivalent | Intended use |
|---|---:|---|

| `--adp-space-0` | 0px / 0rem | None |
| `--adp-space-0-5` | 2px / 0.125rem | Tight inline grouping |
| `--adp-space-1` | 4px / 0.25rem | Tight inline grouping |
| `--adp-space-1-5` | 6px / 0.375rem | Tight inline grouping |
| `--adp-space-2` | 8px / 0.5rem | Tight inline grouping |
| `--adp-space-3` | 12px / 0.75rem | Field/card spacing |
| `--adp-space-4` | 16px / 1rem | Field/card spacing |
| `--adp-space-5` | 20px / 1.25rem | Field/card spacing |
| `--adp-space-6` | 24px / 1.5rem | Field/card spacing |
| `--adp-space-8` | 32px / 2rem | Page/section separation |
| `--adp-space-10` | 40px / 2.5rem | Page/section separation |
| `--adp-space-12` | 48px / 3rem | Page/section separation |
| `--adp-space-16` | 64px / 4rem | Page/section separation |
| `--adp-space-20` | 80px / 5rem | Page/section separation |

### 5.2 Type scale

| Semantic style | Font size | Line height | Weight | Use |
|---|---:|---:|---:|---|
| `label.xs` | 12px / .75rem | 16px | 500–600 | Nonessential compact labels; never financial fine-print with essential meanings |
| `body.sm` | 14px / .875rem | 20px | 400–500 | Dense admin tables and captions |
| `body.md` | 16px / 1rem | 24px | 400 | Default text and forms |
| `body.lg` | 18px / 1.125rem | 28px | 400–500 | Important support text |
| `heading.sm` | 18px / 1.125rem | 28px | 600 | Card title |
| `heading.md` | 22px / 1.375rem | 30px | 600–700 | Panel/page subsection |
| `heading.lg` | 28px / 1.75rem | 36px | 650–700 | Page title |
| `display.sm` | 36px / 2.25rem | 44px | 700 | Sparse hero/stat, not every KPI |

**Fonts:** default to platform/system UI stack (`system-ui`, `-apple-system`, `BlinkMacSystemFont`, `Segoe UI`, `sans-serif`) to avoid license and network dependencies; support locale-appropriate font fallbacks. No bundled font file without approved redistribution license and documented OFL/other compliance. Use font-feature settings for numeric alignment (`font-variant-numeric: tabular-nums`) in tables and metrics. Avoid ultra-light weights for important text. Wrap long product names and translated strings without truncating legal, price or status meaning.

**RTL:** typography direction follows locale; deliberate LTR isolates for URLs, code snippets, technical IDs and ambiguous ISO date/currency strings using `dir="ltr"`/`bdi` as appropriate. Never flip actual number order or Arabic-script glyphs manually.

### 5.3 Radius, shadows and layers

| Token | Value | Intended use |
|---|---|---|
| `radius.xs` | 4px | Inline chips, compact controls |
| `radius.sm` | 6px | Inputs, table select controls |
| `radius.md` | 10px | Cards, menus, buttons |
| `radius.lg` | 14px | Major panels, modals |
| `radius.xl` | 20px | Marketplace hero/exceptional large panel |
| `radius.round` | 9999px | Avatar, limited small badges |
| `shadow.1` | `0 1px 3px rgba(16,24,40,.08)` | Standard elevated card |
| `shadow.2` | `0 4px 16px rgba(16,24,40,.12)` | Popover/dropdown |
| `shadow.3` | `0 12px 36px rgba(16,24,40,.18)` | Modal/dialog |

Prefer border + surface grouping over extensive shadow. Elevation never substitutes for an accessible visible boundary. Never use enormous site-global `z-index` values; establish a locally scoped overlay portal/layer manager tested in Gutenberg and themes. Avoid drawing above WordPress admin modals unless explicitly intended. All surfaces must handle `forced-colors` and `prefers-contrast` where supported.

### 5.4 Control and layout sizing

- Standard single-line control: **44px design target** minimum height (subject to accessibility/localization validation); compact admin cells may contain visually smaller labels but actionable regions MUST still meet target sizing requirements.
- Default action button: 44px; compact table action: >=32px with spacing; destructive actions have text and confirmation context.
- Minimum useful readable column for forms: approx. 260–320px; actual responsiveness depends on localization and zoom.
- Typical content max width: ~1200–1440px for common dashboards; forms ~640–800px; Layout Studio uses available safe workspace width.
- Panel regions must use logical padding (`padding-inline`, `padding-block`) rather than left/right hardcoding.
- Data tables may overflow horizontally within labeled scroll regions; forms and prose must reflow without page-level horizontal overflow.

### 5.5 Responsive reference sizes and layout behavior

| Width under test | Page pattern | Studio pattern | Portal/marketplace pattern |
|---:|---|---|---|
| 320px | One column, native scroll | One task panel + tree/canvas/inspector switcher | Single offer card / stepper compacted |
| 360px | One column | Accessible list mode default | Full-width actions, visible price period |
| 390px | One column | Structured inspector navigation | Persistent next step without covering forms |
| 768px | Two-column when content allows | Docked tree + selected panel, no unreachable canvas | Catalog grid adjusts automatically |
| 1024px | Two-/three-column composition | Two-pane with collapsible inspector | Two-column offer detail where safe |
| 1280px | Expanded dashboard | Full three-pane workspace | Multi-column marketplace |
| 1440px | Dashboard with max readable widths | Three-pane, bounded canvas and filter bars | Wide offer catalog, not stretched prose |

These are **test widths** inherited from ADP-DOC-011, not exact mandatory media-query thresholds. Also test 200% and 400% zoom/reflow, portrait/landscape, touch keyboard, RTL and high contrast. Prefer container queries for component-specific composition and media queries for app-shell changes.

### 5.6 Motion

| Motion token | Baseline | Intended use |
|---|---:|---|
| `motion.instant` | 0ms | Essential state transitions and reduced-motion mode |
| `motion.fast` | 100ms | Hover/focus feedback |
| `motion.normal` | 160ms | Small surface changes |
| `motion.slow` | 240ms | Drawer/dialog transition if nonessential |
| `motion.easing` | `cubic-bezier(.2,.8,.2,1)` | Subtle, non-bouncy transitions |

Do not animate currency totals, ad metrics or tracking status in ways that imply fabricated live data. Skeletons should stop when data resolves; no blocking full-page spinners. All nonessential animation honors reduced-motion preferences. Loading placeholders match the eventual geometry to reduce layout shift.

---

## 6. Reference token implementation (illustrative CSS)

```css
/* Scoped application tokens. The build pipeline should generate this
   from a canonical typed token manifest, not maintain two divergent copies. */
.adp-app {
  --adp-color-bg-canvas: #f9fafb;
  --adp-color-bg-surface: #ffffff;
  --adp-color-fg-primary: #1d2939;
  --adp-color-fg-secondary: #475467;
  --adp-color-border-default: #98a2b3;
  --adp-color-border-subtle: #eaecf0;
  --adp-color-action-primary-bg: #1456b8;
  --adp-color-action-on-primary: #ffffff;
  --adp-color-focus-ring: #1456b8;
  --adp-space-1: .25rem;
  --adp-space-2: .5rem;
  --adp-space-3: .75rem;
  --adp-space-4: 1rem;
  --adp-space-6: 1.5rem;
  --adp-space-8: 2rem;
  --adp-radius-sm: .375rem;
  --adp-radius-md: .625rem;
  color: var(--adp-color-fg-primary);
  background: var(--adp-color-bg-canvas);
  font-family: system-ui, -apple-system, BlinkMacSystemFont,
    "Segoe UI", sans-serif;
  line-height: 1.5;
  color-scheme: light;
}
.adp-app[data-adp-theme="dark"] {
  --adp-color-bg-canvas: #101828;
  --adp-color-bg-surface: #1d2939;
  --adp-color-fg-primary: #f9fafb;
  --adp-color-fg-secondary: #d0d5dd;
  --adp-color-border-default: #667085;
  --adp-color-border-subtle: #475467;
  --adp-color-action-primary-bg: #9ab8ff; /* approve actual button pair */
  --adp-color-action-on-primary: #101828;
  --adp-color-focus-ring: #9abeff;
  color-scheme: dark;
}
.adp-app :where(button, a, input, select, textarea):focus-visible {
  outline: 3px solid var(--adp-color-focus-ring);
  outline-offset: 3px;
}
@media (prefers-reduced-motion: reduce) {
  .adp-app *, .adp-app *::before, .adp-app *::after {
    animation-duration: .01ms !important;
    transition-duration: .01ms !important;
    scroll-behavior: auto !important;
  }
}
@media (forced-colors: active) {
  .adp-app :where(button, input, select, textarea) {
    border: 1px solid ButtonText;
  }
  .adp-app :where(button, a):focus-visible { outline-color: Highlight; }
}
```

**Scoping distinction:** The `adp-app` scope above is for application shells, NOT a reset applied around every publicly displayed creative. Public ad wrappers use minimal `.adp-slot` / `.adp-zone` CSS with only safe box/layout geometry and typography inheritance from the theme. Block editor styles live in editor-specific assets, and preview styles are never conflated with live serving. Portaled popovers must inherit theme tokens through a documented provider/portal mechanism in the actual destination document.

### 6.1 Token source of truth and compiled artifacts

Suggested files: `frontend/shared/design-system/tokens/tokens.json`, schema validator `tokens.schema.json`, generated `tokens.css`, generated TypeScript `tokens.ts`, token documentation table, and contrast-test fixture. Build fails if manually edited generated code diverges. The published package contains only necessary compiled assets; no secret or proprietary font asset is introduced.

```json
{
  "$schemaVersion": 1,
  "color": {
    "bg": { "canvas": { "light": "#F9FAFB", "dark": "#101828" } },
    "fg": { "primary": { "light": "#1D2939", "dark": "#F9FAFB" } },
    "action": { "primaryBackground": { "light": "#1456B8", "dark": "#9AB8FF" } }
  },
  "space": { "1": "0.25rem", "2": "0.5rem", "3": "0.75rem", "4": "1rem" },
  "radius": { "sm": "0.375rem", "md": "0.625rem" }
}
```

The above manifest is a *minimal explanatory subset*, not a complete executable JSON schema; components must consume the full approved token registry and ensure generation is deterministic.

---

## 7. Component taxonomy, public contract and lifecycle

**Contract structure for every component:** stable name; semantic HTML; props/value types; states; keyboard/touch behavior; styling tokens; error/fallback; route/authorization context; loading/freshness behavior; release availability; localization/RTL; a11y test; visual-regression fixture. Components render already-authorized typed projections; they must never perform authorization locally as the sole guard.

**Naming:** React components `AdpButton` etc. Public component package exports should be versioned; internal private components are not promised as public extension APIs. Use native HTML first, stable `@wordpress/components` when matching editor/admin needs, and custom composition only when necessary.

### 7.1 Complete component contract catalog

#### DS12-C-001 — `AppShell` (Layout, R1)

- **Anatomy and content:** skip-link, primary nav, secondary nav, main landmark, page header.
- **Required states and failure behavior:** sidebar collapse, active route, permission changes. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Tab to skip link; landmarks navigable. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Responsive nav collapses with preserved page context. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-01..UI-05,UI-11..UI-20. **Required test:** `DS12-T-001` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-002 — `PageHeader` (Layout, R1)

- **Anatomy and content:** eyebrow/breadcrumb, title, description, status, primary action.
- **Required states and failure behavior:** title wrap, many actions, missing permissions. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Heading structure; controls follow reading order. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Actions wrap or overflow into labeled menu. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-002` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-003 — `Breadcrumb` (Navigation, R1)

- **Anatomy and content:** ordered navigation list, links, current page.
- **Required states and failure behavior:** deep path, renamed entity, inaccessible ancestor. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Native ordered list/links. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Collapse ancestors with expandable menu. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-003` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-004 — `SideNavigation` (Navigation, R1)

- **Anatomy and content:** group labels, route links, indicators.
- **Required states and failure behavior:** collapsed, focused, forbidden, feature-disabled. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Native link navigation, not clickable div. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Slide-out drawer with open/close button. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** admin. **Required test:** `DS12-T-004` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-005 — `TabBar` (Navigation, R1)

- **Anatomy and content:** tablist, tabs, linked tabpanels.
- **Required states and failure behavior:** selected, disabled, overflow. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** APG keyboard interaction or ordinary links if routing. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Scrollable tab-strip with visible affordance. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-03,UI-09,UI-17. **Required test:** `DS12-T-005` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-006 — `StepProgress` (Navigation, R1)

- **Anatomy and content:** step labels, current, complete, error.
- **Required states and failure behavior:** blocked, editable completed, resume. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Readable ordered steps and current step. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Compact numbered steps with screen-readable full names. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-09,UI-10. **Required test:** `DS12-T-006` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-007 — `Button` (Action, R1)

- **Anatomy and content:** label, icon optional, spinner, target.
- **Required states and failure behavior:** primary, secondary, ghost, destructive, busy, disabled. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Native button; announced busy; never pointer-only. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Wrap label, min touch size. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-007` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-008 — `IconButton` (Action, R1)

- **Anatomy and content:** accessible text, icon, optional tooltip.
- **Required states and failure behavior:** focus, hover, disabled, active. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** aria-label/visually hidden label; tooltip not sole text. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** >=24px WCAG, design >=40px unless justified. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-008` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-009 — `SplitAction` (Action, R1)

- **Anatomy and content:** primary action, attached menu trigger.
- **Required states and failure behavior:** menu open, danger option, busy. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Separate named buttons; menu keyboard pattern. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Never hide main action on narrow screens. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** admin. **Required test:** `DS12-T-009` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-010 — `TextField` (Form, R1)

- **Anatomy and content:** label, help, input, error, char count.
- **Required states and failure behavior:** empty, active, invalid, readonly, server-rejected. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Linked label/help/error; autocomplete where appropriate. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Full width on narrow screens. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-010` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-011 — `TextArea` (Form, R1)

- **Anatomy and content:** label, hint, multiline input, error.
- **Required states and failure behavior:** autosave, max length, readonly. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Native textarea; resize preserves content. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Minimum readable rows. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-08,UI-09,UI-12. **Required test:** `DS12-T-011` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-012 — `SelectCombobox` (Form, R1)

- **Anatomy and content:** field label, search, listbox/options.
- **Required states and failure behavior:** large option set, no results, loading. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Use native select by default; APG combobox if custom. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Popup position within viewport and RTL. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-02,UI-03,UI-09. **Required test:** `DS12-T-012` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-013 — `CheckboxRadioSwitch` (Form, R1)

- **Anatomy and content:** control, label, description.
- **Required states and failure behavior:** checked, mixed, readonly, disabled. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Native controls; never color-only toggles. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Generous hit area with wrapped labels. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-013` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-014 — `CurrencyInput` (Financial, R1)

- **Anatomy and content:** numeric input, currency, precision, period.
- **Required states and failure behavior:** zero, negative, incompatible currency, invalid. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Describe units and validation; never silently round finance. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** No locale-dependent silent value mutation. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-09,UI-10,UI-14. **Required test:** `DS12-T-014` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-015 — `DateTimePicker` (Form, R1)

- **Anatomy and content:** date, time, zone, interval/offset detail.
- **Required states and failure behavior:** DST ambiguous, invalid, no availability. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Keyboard date entry + calendar alternative. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** One-column date/time on mobile. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-09,UI-13. **Required test:** `DS12-T-015` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-016 — `FormSection` (Form, R1)

- **Anatomy and content:** title, context help, fields, action area.
- **Required states and failure behavior:** pristine, dirty, saving, conflict. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Group fields with fieldset/legend when appropriate. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Single-column fields on mobile. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-016` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-017 — `FileUploader` (Creative, R1)

- **Anatomy and content:** file select, constraints, preview, progress.
- **Required states and failure behavior:** invalid type, unsafe, upload failed, resuming. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Keyboard-native file input; status live updates. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Progress/preview within viewport. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-09,UI-12. **Required test:** `DS12-T-017` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-018 — `NoticeBanner` (Feedback, R1)

- **Anatomy and content:** severity icon+text, title, action, dismiss optional.
- **Required states and failure behavior:** critical, advisory, stale, permission, completed. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Role appropriate; no important message exclusively transient. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Full-width stack with inline controls. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-018` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-019 — `Toast` (Feedback, R1)

- **Anatomy and content:** short message, optional retry, close.
- **Required states and failure behavior:** success, info, recoverable error. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Polite live region; not sole receipt of payment. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** No overlay covering mobile CTA. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-019` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-020 — `ModalDialog` (Overlay, R1)

- **Anatomy and content:** name, context, body, primary/secondary actions.
- **Required states and failure behavior:** confirm, unsaved, blocked, loading. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Focus containment, Escape where safe, focus restore. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Full screen sheet on narrow widths. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-03,UI-09,UI-12,UI-14. **Required test:** `DS12-T-020` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-021 — `PopoverTooltip` (Overlay, R1)

- **Anatomy and content:** trigger, floating content, close.
- **Required states and failure behavior:** keyboard hover, portal, clipping. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Tooltip is supplemental, real button names permanent. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Reposition/flips within viewport. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-021` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-022 — `DataTable` (Data, R1)

- **Anatomy and content:** caption, headings, rows, selection, toolbar, pagination.
- **Required states and failure behavior:** loading, empty, partial, stale, filtered, unauthorized. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Native table whenever possible; labeled sortable headings. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Horizontal scroll or card list; column label retained. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-02,UI-04,UI-11,UI-14,UI-16. **Required test:** `DS12-T-022` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-023 — `FilterBar` (Data, R1)

- **Anatomy and content:** query, facets, chips, reset, saved view.
- **Required states and failure behavior:** no matches, partial, server error. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Accessible labels, URL state where safe. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Filters collapse without hiding state. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-02,UI-06,UI-11,UI-16. **Required test:** `DS12-T-023` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-024 — `Pagination` (Data, R1)

- **Anatomy and content:** first/prev/next, page or cursor, result count.
- **Required states and failure behavior:** unknown total, out-of-range, fetch error. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Named navigation, accessible current page. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Simple prev/next on phones. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-02,UI-04,UI-11. **Required test:** `DS12-T-024` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-025 — `BulkActionBar` (Data, R1)

- **Anatomy and content:** selection summary, permitted actions, clear.
- **Required states and failure behavior:** mixed authorizations, partial failure, undo. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Status and selected count announced. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Sticky safe mobile toolbar. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-02,UI-04,UI-11. **Required test:** `DS12-T-025` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-026 — `MetricCard` (Analytics, R1)

- **Anatomy and content:** metric name, value, unit, period, definition, freshness.
- **Required states and failure behavior:** zero, no data, delayed, partial, unmeasurable. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Readable number and meaning, no color-only delta. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Stack/reflow values; avoid truncated units. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-01,UI-16. **Required test:** `DS12-T-026` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-027 — `TimeSeriesChart` (Analytics, R1)

- **Anatomy and content:** title, axes, series labels, legend, data table.
- **Required states and failure behavior:** missing bins, sampled, partial, stale. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Text summary + equivalent data table. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Touch tooltip nonessential; table always accessible. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-01,UI-16. **Required test:** `DS12-T-027` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-028 — `StatusPill` (Feedback, R1)

- **Anatomy and content:** status text, optional icon, semantic appearance.
- **Required states and failure behavior:** draft, pending, approved, paid, paused, failed. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** State name visible; distinguish parallel state planes. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Never shrink to unreadable single dot. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-028` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-029 — `ApprovalTimeline` (Campaign, R1)

- **Anatomy and content:** dated events, actor, version, reason.
- **Required states and failure behavior:** pending, reversed, async, partially complete. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Ordered semantic event list. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Vertical on mobile. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-11,UI-12. **Required test:** `DS12-T-029` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-030 — `CampaignWizard` (Campaign, R1)

- **Anatomy and content:** step navigation, progress, form, side quote summary.
- **Required states and failure behavior:** draft, field errors, stale quote, interrupted, conflict. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Accessible step sequence, preserved draft. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** One-pane sequential flow, visible price/hold expiry. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-09,UI-10. **Required test:** `DS12-T-030` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-031 — `InventoryOfferCard` (Marketplace, R1)

- **Anatomy and content:** placement name, visual context, format, price/unit, availability.
- **Required states and failure behavior:** sold out, stale, unavailable, estimated reach. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Semantic heading and clear offer price. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Single-column mobile offers. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-06,UI-07. **Required test:** `DS12-T-031` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-032 — `ReservationCalendar` (Commerce, R1)

- **Anatomy and content:** date grid or list, availability, zone, time zone.
- **Required states and failure behavior:** hold expired, occupied, sparse, multi-timezone. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Fully functional keyboard list alternative. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** List/timeline compact on phones. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-13,UI-09. **Required test:** `DS12-T-032` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-033 — `PaymentStatusPanel` (Financial, R1)

- **Anatomy and content:** authoritative state, amount, currency, invoice/ref, next step.
- **Required states and failure behavior:** pending webhook, failed, disputed, partially refunded. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Persistent confirmation evidence; live state not optimistic. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Clear single column and retry options. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-10,UI-14,UI-19. **Required test:** `DS12-T-033` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-034 — `LayoutZoneTree` (Layout Studio, R1)

- **Anatomy and content:** zone list, counts, selected ID, child containers.
- **Required states and failure behavior:** 1000 slots, lazy children, conflicts, no access. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Tree APG only if completely implemented; else nested list. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Panel selector and list mode on small screens. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-03,UI-04. **Required test:** `DS12-T-034` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-035 — `LayoutCanvas` (Layout Studio, R1)

- **Anatomy and content:** bounded visual zones, selected highlight, guidance.
- **Required states and failure behavior:** approximate geometry, loading, high slot density. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Keyboard selection and list-equivalent operations. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Can switch to structural list view. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-03. **Required test:** `DS12-T-035` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-036 — `SlotNavigator` (Layout Studio, R1)

- **Anatomy and content:** search, sortable slot list, occupancy and stable IDs.
- **Required states and failure behavior:** 500/1000+, selection, suspended, paged. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Virtualization keeps accessible counts/focus. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Card/list, not 1000 rendered tiles. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-04. **Required test:** `DS12-T-036` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-037 — `ResponsiveInspector` (Layout Studio, R1)

- **Anatomy and content:** device preset, columns, gap, slot count, inheritance.
- **Required states and failure behavior:** invalid layout, sold impact, branch edits. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Explicit labels and numeric values; keyboard controls. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Stacked inspector with full editable properties. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-03,UI-05. **Required test:** `DS12-T-037` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-038 — `LayoutDiffViewer` (Layout Studio, R1)

- **Anatomy and content:** before/after, impacted IDs, commitments, resolution.
- **Required states and failure behavior:** no changes, unresolved blockers, stale base. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Text/list change summary equivalent to visual diff. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** One diff at a time on phone. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-03,UI-05. **Required test:** `DS12-T-038` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-039 — `PublishImpactDialog` (Layout Studio, R1)

- **Anatomy and content:** changes, sold exposure, blocker reasons, available resolutions.
- **Required states and failure behavior:** pending validation, hard block, conflict. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** No confirm when blocked; retain focus and revalidation. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Structured scrollable summary. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-03. **Required test:** `DS12-T-039` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-040 — `CreativePreview` (Creative, R1)

- **Anatomy and content:** safe image/layout, dimensions, disclosure, click dest summary.
- **Required states and failure behavior:** unsafe asset, expired, placeholder, device variant. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** No execution of third-party scripts; alt information. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Responsive contained box. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-07,UI-09,UI-12. **Required test:** `DS12-T-040` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-041 — `DeliveryDiagnosticPanel` (AdTech, R1)

- **Anatomy and content:** slot, eligibility gate, result, timestamp, health.
- **Required states and failure behavior:** no campaign, blocked, stale, code error. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Named gate states and correlation IDs. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Expandable sections/stacked. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-15. **Required test:** `DS12-T-041` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-042 — `HeatmapViewer` (Analytics, R2)

- **Anatomy and content:** page/zone selector, viewport geometry, layered heatmap, numeric equivalent.
- **Required states and failure behavior:** layout version mismatch, sparse sample, no consent. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Complete table alternative, no gaze claim. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Zoom/pan plus list view on narrow screens. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-17. **Required test:** `DS12-T-042` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-043 — `HeatmapLegend` (Analytics, R2)

- **Anatomy and content:** scale units, bins, sample, limitations.
- **Required states and failure behavior:** unknown sample, misleading gradient. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Labeled numeric bins and pattern modes. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Visible adjacent to chart/data table. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-17. **Required test:** `DS12-T-043` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-044 — `ExportJobRow` (Operations, R1)

- **Anatomy and content:** job name, requester, state, expiration, download action.
- **Required states and failure behavior:** queued, processing, failed, revoked, expired. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Reauth on download; status/live announcements. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Responsive list with preserved timestamps. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-18. **Required test:** `DS12-T-044` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-045 — `SystemHealthCard` (Operations, R1)

- **Anatomy and content:** health check, state, last checked, guidance.
- **Required states and failure behavior:** unknown, unreachable, degraded. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Never present unknown as healthy. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Stacked diagnostics. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-20. **Required test:** `DS12-T-045` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-046 — `EmptyState` (Feedback, R1)

- **Anatomy and content:** title, reason, safe action.
- **Required states and failure behavior:** no data, filtered out, permission hidden, feature gated. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Explain truthful absence, not fabricated zero. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Constrain width; primary CTA contextual. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-046` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-047 — `Skeleton` (Feedback, R1)

- **Anatomy and content:** placeholder matching final layout geometry.
- **Required states and failure behavior:** loading, long running, reduced motion. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Not aria-spam; name loading region. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Maintains layout, no flashing. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** all. **Required test:** `DS12-T-047` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-048 — `PrivacyConsentPanel` (Privacy, R2)

- **Anatomy and content:** categories, purpose, options, withdrawal.
- **Required states and failure behavior:** denied, granted, partial, stale. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Neutral choices, opt-out, no dark pattern. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Accessible stacked toggles. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-17,UI-20. **Required test:** `DS12-T-048` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

#### DS12-C-049 — `SessionReplayControls` (Analytics, R4)

- **Anatomy and content:** timeline, playback controls, masking state, retention.
- **Required states and failure behavior:** not consented, masked, expired, revoked. Every asynchronous variant must additionally support initial/loading, ready, empty, stale/partial, denied, error/retry and disabled-by-release where applicable; do not synthesize success or replace unavailable values with zero.
- **Keyboard and assistive technology:** Keyboard transport controls and accessible event list. Maintain a persistent discernible name, reading order and visible focus. Control target sizes and contrast follow Section 11.
- **Responsive/RTL:** Optional module, not loaded when off. Use logical properties and preserve technical LTR isolates; test long translations, 200%/400% zoom and touch.
- **Data, security, and completion contract:** Accept a typed projection; display server-authoritative status and revision/freshness where important. No direct SQL, no tenant-policy inference from client state, no privileged payload in public props. Separate live from preview and preserve error context. Document prop types, snapshots, stories, keyboard, RTL and interaction tests before shipping.
- **UI surface links:** UI-17. **Required test:** `DS12-T-049` (component behavior, accessibility, responsive state, RTL and scoped styling); additional domain scenarios still come from Documents 01–11.

### 7.2 Component implementation precedence

- Prefer native semantic HTML (`button`, `input`, `table`, `fieldset`, `dialog` where validated) when its behavior suffices.
- In Gutenberg and wp-admin, prefer documented bundled `@wordpress/components` and corresponding WordPress i18n/data/build packages for stable compatibility.
- The newer `@wordpress/ui` and `@wordpress/theme` packages may be evaluated in an ADR, but `@wordpress/ui` is documented as experimental as of this document. Do not use experimental behavior as an unguarded essential dependency. Version-match any opt-in package and pin compatibility tests.
- A component must not assume a React portal is inside its visual theme root. Portaled tooltip/dialog styles and focus are tested in the actual `ownerDocument` (including editor iframes where present).
- Avoid duplicate dialog, calendar, chart, icon and focus-management libraries when a vetted WordPress/native option already solves the problem. Every new asset must pass the licensing and SBOM policy from earlier documents.

---

## 8. Reference React/TypeScript component contracts

The following examples are **illustrative** public contracts. Final imports, actual routes, error types and release flags MUST be reconciled with ADP-DOC-009. Code is intentionally restricted to visual/presentation responsibilities; it does not simulate a payment, authorize a user, or record a real impression.

### 8.1 Typed component-state discriminants

```ts
export type DisplayState<T> =
  | { kind: 'initial' }
  | { kind: 'loading'; previous?: T }
  | { kind: 'ready'; data: T; revision?: string; asOf?: string }
  | { kind: 'empty'; reason: 'none' | 'filters' }
  | { kind: 'partial'; data: T; missing: string[]; asOf?: string }
  | { kind: 'stale'; previous: T; reason: string; asOf?: string }
  | { kind: 'forbidden' }
  | { kind: 'disabled'; release: 'R2' | 'R3' | 'R4' }
  | { kind: 'error'; code: string; retryable: boolean; correlationId?: string };

export type CurrencyCode = string & { readonly __currencyCode: unique symbol };
export type FormattedMoney = {
  majorFormatted: string; currency: CurrencyCode; periodLabel?: string;
  // Formatting is derived from an authoritative minor-unit amount and
  // approved currency precision; this is display-only, not arithmetic.
};
export interface AuthoritativeStatus {
  label: string;
  kind: 'pending' | 'approved' | 'rejected' | 'active' |
    'paused' | 'completed' | 'failed' | 'unknown';
  asOf?: string;
  entityRevision?: string;
}
```

Backend still owns identity, membership, price, availability, revision and status. A UI `disabled` flag is merely affordance; it is never a replacement for server checks. When a financial state is `unknown`, display `Awaiting confirmation` and a verified-operation lookup or support path instead of optimistic `Payment complete`.

### 8.2 Accessible status component

```tsx
import type { ReactNode } from 'react';

type StatusKind =
  | 'neutral' | 'pending' | 'success' | 'warning' | 'critical';
interface StatusPillProps {
  kind: StatusKind;
  children: ReactNode;
  detail?: string;
}

export function StatusPill({ kind, children, detail }: StatusPillProps) {
  return (
    <span className={`adp-status adp-status--${kind}`}>
      <span className="adp-status__mark" aria-hidden="true" />
      <span>{children}</span>
      {detail ? <span className="adp-visually-hidden">. {detail}</span> : null}
    </span>
  );
}
```

The mark is supplemental; e.g. `Approved`, `Payment pending verification` and `Scheduled` must remain independently readable text. Avoid content strings assembled from untranslated fragments; actual UI consumes localized complete status phrases.

### 8.3 Accessible field composition

```tsx
import { useId } from 'react';

export function TextField({
  label, value, onChange, help, error, required = false,
}: {
  label: string; value: string; onChange: (value: string) => void;
  help?: string; error?: string; required?: boolean;
}) {
  const id = useId();
  const helpId = `${id}-help`;
  const errId = `${id}-error`;
  const described = [help && helpId, error && errId].filter(Boolean).join(' ');
  return (
    <div className="adp-field">
      <label htmlFor={id}>{label}{required ? ' *' : ''}</label>
      {help ? <p id={helpId}>{help}</p> : null}
      <input id={id} value={value} required={required}
        aria-invalid={Boolean(error)}
        aria-describedby={described || undefined}
        onChange={(e) => onChange(e.currentTarget.value)} />
      {error ? <p id={errId} className="adp-field__error">{error}</p> : null}
    </div>
  );
}
```

**Production completion:** translate required-field prose and error text; optionally show a visible required legend; associate server validation codes with fields; add disabled/readonly/autocomplete/maxlength input properties; preserve focus/typed content on request failure. Avoid using the placeholder as label.

### 8.4 Domain-aware primary button

```tsx
interface ConfirmActionProps {
  label: string;
  canSubmit: boolean;
  busy: boolean;
  disabledExplanation?: string;
  onConfirm: () => void;
}

export function ConfirmAction(p: ConfirmActionProps) {
  return (
    <div className="adp-action">
      <button type="button" disabled={!p.canSubmit || p.busy}
        aria-busy={p.busy} onClick={p.onConfirm}>
        {p.busy ? 'Submitting…' : p.label}
      </button>
      {!p.canSubmit && p.disabledExplanation ?
        <p className="adp-action__hint">{p.disabledExplanation}</p> : null}
    </div>
  );
}
```

**Caution:** `canSubmit` is an authorized UI hint, not a server grant. The actual command uses current object policy, revision and idempotency rules. This example does not prove submission succeeded. Localize the pending label and do not disable accessible explanation.

---

## 9. Design specifications for flagship UI patterns

### 9.1 Layout Studio (`UI-03`, `UI-04`, `UI-05`)

**Desktop anatomy:** application header (page/template, version, status, validate/publish); left zone/slot tree with search; central selectable canvas/structural layout preview; right property inspector; bottom outcome bar with validation, inventory occupancy, sold commitments and publish blocker counts. The desktop panels must be resizable within min/max constraints; persist user-specific proportions safely. When 500 configured slots exist, show concise group summaries plus an indexed slot navigator; the canvas may preview a bounded sample, never load all commercial creatives and never emit production billable events.

**Selection:** selecting a slot in the tree synchronizes canvas highlight, inspector data, breadcrumbs and keyboard focus without changing its stable ID. Selecting canvas geometry selects the same entity. Multi-select operations must be possible by keyboard and through the slot navigator. Distinguish logical slot from render occurrence visually and in technical tooltips.

**Inspection:** group properties into Layout, Responsive, Inventory, Commercial Commitments, Delivery, Tracking, Advanced. Indicate inherited vs overridden values, measured vs estimated dimensions, and whether edits affect purchased rights. A changed breakpoint grid cannot reassign paid slot identities. For overridden visible creatives, explain how CSS aspect ratio and responsive variants are computed.

**Validation workflow:** save editable draft locally/remote with truthful state; validate current server-side configuration and active reservations; show findings categorized `blocker`, `warning`, `information`; link each finding to a precise slot/setting and owner; show contract impact; publish only after authoritative revalidation and permissions. A server conflict displays a compare/reload/reapply choice, not last-write-wins.

**Mobile structural mode:** replace three simultaneous panels with an explicit top-level switcher `Zones / Preview / Properties / Validation`, and always provide a searchable tabular/list editor. Panning/zooming canvas is optional, not sole method. At 400% zoom the publish/validation path must remain usable without trapped focus or hidden actions.

**Visual state taxonomy:** selected/hovered/focused, unsold/sold/reserved/unavailable, draft/published, valid/warning/blocked, sampled preview. Each state is distinguishable by text/icon/pattern plus color. Labels such as `200 configured`, `24 contracted`, `0 render events in preview` must be explicit.

### 9.2 Advertiser wizard (`UI-06..UI-11`)

**Step sequence:** inventory offer → creative/details → targeting/schedule → quote/hold → checkout → confirmation/status. Track step completion in a resumable draft; authoritative quote and inventory availability are rechecked before making a hold. Show a persistent human-readable summary with placement name, dates and time zone, price basis, currency, taxes where known, hold expiry and moderation status.

**Interaction priorities:** the dominant action advances only when inputs are valid; disabled controls explain their dependency; previous-step navigation does not discard work. Quotes have source and expiry, not merely a price. Availability is a snapshot until held. `Payment submitted`, `Payment pending confirmation`, `Creative awaiting approval`, and `Scheduled` must be separate, distinguishable states. Redirect back from WooCommerce requires server lookup before success messaging. Any expired hold offers recheck/requote, not a phantom existing booking.

**Creative upload:** validate file format, size/dimensions, unsafe content, alternate text and disclosure. Let advertisers preview responsively, but do not execute third-party scripts. Show approval status per immutable creative version; changed destination URL is an approval-relevant change. Use a step summary compatible with narrow screens and RTL.

**Organization switching:** display active organization identity and a deliberate account switcher if membership allows. Switching invalidates cached scoped views and prevents previous organization's invoice/creative data from remaining in memory or stale screen projections.

### 9.3 Moderation and financial workspaces (`UI-12..UI-14`, `UI-19`)

A creative review screen displays exact creative revision, advertiser, destination, policy flags, rendered-safe preview, past decision history and action reasons. Approve/reject actions require server confirmation and must expose stale-review conflict when another reviewer acted first. Dangerous URLs cannot be opened without safety safeguards. A financial action needs amount, currency, source order, refund path, current verified settlement state, consequence and audit reason. Avoid using only red/green for approval/refund; include actor, timestamps and receipt references.

**Separation of duty:** payment review roles and moderation roles receive distinct controls and projections. The design must not imply that a creative approved by a reviewer is automatically paid or that a paid order is automatically deliverable. Critical operations require confirmation with meaningful context, never generic `Are you sure?`.

### 9.4 Publisher and advertiser dashboards (`UI-01`, `UI-11`, `UI-16`)

Dashboards prioritize actionable exceptions over vanity numbers. Present `Billable impressions`, `Rendered impressions`, `Viewability rate`, `Qualified CTR`, `Recognized revenue` as separately defined metrics with consistent denominators/timezones. Report a freshness timestamp and data completeness when asynchronous aggregation lags. Unavailable finance data must display `Unavailable` or `Awaiting reconciliation` rather than `$0`.

Charts: always expose data table and readable text summary; label units and origin; charts are not required to convey unique essential data unavailable elsewhere. Use tabular numerals for amounts and values. Do not animate tickers or fractional price changes solely for visual effect. Avoid percentages without denominators and misleading y-axis truncation; absolute counts and ratios must be distinguishable.

### 9.5 Heatmap explorer (`UI-17`, R2)

**Anatomy:** source/page selector, layout-version selector, device/viewport filter, date range, method switch (`click`, `scroll`, `visibility`, `engagement proxy`), legend with units, sanitized page snapshot/canvas, slot details, numerical alternatives, privacy and freshness notice.

**Data fidelity:** only overlays events on matching layout version or applies a documented, confidence-labeled mapping; if geometry is incompatible display a visible mismatch state and prompt for a correct snapshot. Normalize coordinates with the documented geometry contract. Distinguish sampling, counts, exposure time, and inferred engagement. A cursor-movement heatmap is not eye tracking. Missing consent data is a true measurement limitation, not a zero-activity result.

**Color:** gradient alone is never sufficient; offer named bins, numeric distribution, outlines/patterns, and sortable accessible table for all meaningful comparisons. Contrast overlay depends on underlying screenshot: put labels and controls on solid surfaces. Do not make a red hotspot synonymous with danger or financial loss.

**Large page support:** full page and per-zone exploration, vertical navigation, breadcrumb to selected slot, bounded tiles/snapshots, table alternative. Mobile users may switch to the slot list rather than be forced to manipulate a tiny full-page raster. Screenshot/preview capture must mask sensitive content and use an authorized source.

**R4 replay:** optional, separately consent-gated, disabled by default, and independently loaded. Never imply R2 includes this. Playback UI includes scrubbing, speed, event list, masking indicator, deletion/expiry and access audit notices; do not provide advertiser access to visitor-level replay.

### 9.6 Public advertising surfaces (`adpilot/ad-*`)

Ad containers and creatives must remain compatible with arbitrary themes and preserve layout stability. A public ad should inherit the theme's baseline typography unless the approved creative explicitly owns bounded presentation styles. Ads have discernible sponsorship disclosure and safe click target sizing. Empty slots may collapse, reserve space or show a house ad according to policy, without fabricating impressions. Avoid global resets, fixed width overflowing mobile, disruptive automatic motion, unnecessary focus trapping, and intrusive sticky layouts. The parent theme retains control of its global design.

---

## 10. Advanced data visualization and content semantics

### 10.1 Numerical contract for every metric

Required adjacent or discoverable fields: display name; value; unit; report interval and timezone; denominator (where applicable); data origin; freshness; qualification definition/version; sampling/uncertainty if relevant; missing/partial state; intended comparison. A 3.1% CTR without definition of qualified clicks and eligible impressions is insufficient.

### 10.2 Chart types and alternatives

| Visualization | Allowed usage | Accessible equivalent | Critical misuse to prevent |
|---|---|---|---|
| Line/area | Time series over consistent intervals | Ordered data table and trend summary | Implying missing hours are zero |
| Bar (vertical/horizontal) | Compare grouped placements or campaigns | Sorted table | Truncated axis without disclosure |
| Stacked bar | Compare composition with consistent totals | Totals and category table | Combining incompatible denominators |
| Table + sparkline | Dense operational list | Tabular values remain primary | Hidden numeric summary only in sparkline |
| Calendar availability | Reservation intervals and capacity | Keyboard-accessible chronological list | Treating pre-hold availability as booked |
| Heatmap | Relative spatial interaction/exposure | Element/slot indexed numeric table | Calling scroll or pointer position eye gaze |
| Funnel | Defined stages with known counts | Stage table, definitions and coverage | Reporting unsupported causation |
| Density distribution | Approximate response time/performance | Percentile table and note | Hiding long-tail failures behind mean |

### 10.3 Heatmap color/accessibility model

Suggested sequential scheme may use 5 bins, `very low`, `low`, `medium`, `high`, `very high`, each labeled with quantiles or numeric thresholds, sample size and a monotonically ordered lightness/opacity representation. Define calculation separately for click density vs time exposure; never mix differently measured quantities into a single scale without visible explanation. Provide pattern/outline mode for grayscale and `forced-colors`. When overlays exceed background contrast, put controls in opaque labeled panels.

### 10.4 Financial semantics and microcopy

Use explicit language: `Price: USD 120 per week`, `Estimate based on the previous 30 days`, `Hold expires at 14:30 (UTC+04:00)`, `Payment received; creative awaiting review`, `Qualified clicks: 23`, `The latest report is delayed by 12 minutes`. Avoid vague `Successful` or `Complete` for an operation whose authoritative state is still pending. All monetary values must come from backend decimal/minor-unit projections, and all timers re-validate authoritative expiry rather than extending the contract client-side.

---

## 11. Accessibility and inclusive design conformance contract

**Target:** WCAG 2.2 AA for all custom user-facing interfaces; this is a release requirement, not an assertion of present compliance or third-party certification. Test with real browser/assistive-technology combinations, in addition to automated audits.

| Topic | Non-negotiable implementation behavior | Test evidence |
|---|---|---|
| Keyboard access | All actions operable without pointer; meaningful focus order | Full tasks using keyboard only |
| Drag alternatives | Layout reorder and bulk edit via menu/buttons/list controls | Keyboard 500-slot arrangement |
| Focus visibility | Visible focus on every operable target; not obscured by sticky UI | Focus screenshots in light/dark/forced colors |
| Reflow/zoom | At 320px and 400% zoom, primary tasks remain usable without page-wide scroll | Browser zoom/viewport matrix |
| Target size | WCAG 2.2 minimum pointer target 24x24px or allowed exceptions; design target 44px for primary controls | Geometry checker and manual touch test |
| Text contrast | Normal text >=4.5:1; large text >=3:1 in the intended pair | Automated token pair + rendered screen checks |
| Non-text contrast | Meaningful control edges/icon/state affordances >=3:1 where applicable | Component state audit |
| Status and errors | Never encoded solely by color; errors linked to fields | Screen reader + invalid server response |
| Dialogs/popovers | Proper name, focus containment/recovery, Escape when safe | Keyboard/APG interaction tests |
| Tables | Table headers/captions, accessible sort, selection count | VoiceOver/NVDA with paged table |
| Charts/heatmaps | Numeric/text alternatives, labeled legend, no eye-tracking claim | Navigate report with graphics disabled |
| Motion | Honor reduced-motion preference; no essential flashing/animation | OS preference + automated style check |
| RTL/i18n | Logical ordering and untranslated technical strings isolated | Arabic/Persian long-label tests |
| Auth/consent | Neutral options, accessible status, no forced attention to new data | Screen reader + legal/privacy review |

**Screen-reader matrix:** at least NVDA + Firefox/Chrome on Windows, VoiceOver + Safari on macOS/iOS where available, and TalkBack + Android browser for primary portal journeys. Record browser, version, locale, component revision and failures. Tests should not depend on a particular device brand to infer accessibility universally.

**ARIA rule:** native HTML first. Use APG semantics only when the full interaction pattern is implemented. Avoid `role="application"` on entire apps; use labeled landmarks and structural headings. Active tree/grid keyboard shortcuts must be documented and conflict-tested against the WordPress editor and assistive technologies. Disabled controls still require understandable explanation in adjacent content.

**Sensitive UX boundaries:** masking, consent, organization scope, exports, replay and billing records are server-side policies. UI may hide forbidden controls but MUST NOT render hidden private content into DOM or debug tooling. Errors include safe correlation identifiers, not raw SQL, payment payloads or personally identifying replay data.

---

## 12. Canonical 20-screen visual adoption matrix

The screen identifiers, names and release assignment are inherited from ADP-DOC-011. This matrix does not invent a second screen catalog. Each screen inherits the global app shell, state protocol, responsive/RTL conventions, accessibility gate and role-based server data projection. Below, **component focus** describes mandatory visual coverage rather than a complete list of controls. Implement individual screen flows according to ADP-DOC-003/011 and consume API contracts exactly as ADP-DOC-009 specifies.

| Screen | Canonical title | Release | Mandatory component focus | Primary visual acceptance |
|---|---|---|---|---|

| `UI-01` | Admin executive dashboard | R1 | MetricCard, NoticeBanner, TimeSeriesChart, DataTable | Unavailable finance is not rendered as zero; prioritize urgent work. |
| `UI-02` | Zone registry | R1 | FilterBar, DataTable, StatusPill, BulkActionBar | Stable zone IDs and sold-inventory impact clear. |
| `UI-03` | Layout Studio | R1 | LayoutZoneTree, LayoutCanvas, ResponsiveInspector, PublishImpactDialog | 500 slot summary with bounded preview; blocked publish explained. |
| `UI-04` | Slot navigator | R1 | SlotNavigator, DataTable, BulkActionBar | 1000+ slots paged without 1000 DOM control objects. |
| `UI-05` | Template binding inspector | R1 with R2 diagnostics enhancement | LayoutDiffViewer, ResponsiveInspector, NoticeBanner | Bindings and contractual movement visible before publish. |
| `UI-06` | Public catalog | R1 | InventoryOfferCard, FilterBar, EmptyState | Public offer availability and price period precise. |
| `UI-07` | Placement detail | R1 | InventoryOfferCard, CreativePreview, StatusPill | Preview simulated; reach estimates labeled, not promises. |
| `UI-08` | Advertiser onboarding/team | R1 | TextField, FormSection, SelectCombobox, NoticeBanner | Organization account scoped; invitation and role states clear. |
| `UI-09` | Campaign wizard | R1 | CampaignWizard, FileUploader, ReservationCalendar | Autosave truthful; quote/hold state and errors recoverable. |
| `UI-10` | Checkout handoff | R1 | PaymentStatusPanel, StepProgress, NoticeBanner | No success before verified payment and current hold. |
| `UI-11` | Campaign list/detail | R1 | DataTable, ApprovalTimeline, StatusPill | Payment, approval and delivery are separate labels. |
| `UI-12` | Creative moderation | R1 | CreativePreview, ApprovalTimeline, ModalDialog | Exact creative version and rejection rationale preserved. |
| `UI-13` | Booking calendar | R1 | ReservationCalendar, FilterBar, StatusPill | Availability vs held/reserved explicit; keyboard list option. |
| `UI-14` | Rates/orders and scoped billing | R1 | CurrencyInput, DataTable, PaymentStatusPanel | Ledger and invoice facts exact and role-scoped. |
| `UI-15` | Delivery diagnostics | R1 | DeliveryDiagnosticPanel, NoticeBanner, DataTable | Pending/invalid/stale diagnostic explanations actionable. |
| `UI-16` | Advertiser reporting | R1; advanced export R2 | MetricCard, TimeSeriesChart, DataTable | Qualified metric denominator and freshness shown. |
| `UI-17` | Heatmap explorer | R2; optional replay R4 | HeatmapViewer, HeatmapLegend, DataTable | Layout/version mismatch and numerical alternative required. |
| `UI-18` | Export jobs | R2 | ExportJobRow, StatusPill, NoticeBanner | Download expiry/revocation; recheck authorization. |
| `UI-19` | Billing dispute | R3 | PaymentStatusPanel, ApprovalTimeline, ModalDialog | Dispute state, monetary implications, next step explicit. |
| `UI-20` | Settings/system health | R1; R2/R4 feature sections as enabled | SystemHealthCard, FormSection, NoticeBanner | Warn on failed queue/scheduler; no secrets in diagnostics. |

## 13. Frontend package architecture and build boundaries

### 13.1 Proposed physical implementation

```text
frontend/
  shared/
    design-system/
      tokens/{tokens.json,tokens.schema.json,generate-tokens.ts}
      generated/{tokens.css,tokens.ts}
      primitives/{Button,Field,Status,Dialog,...}/
      composites/{DataTable,DateTimePicker,MetricCard,...}/
      domain/{CampaignWizard,SlotNavigator,HeatmapLegend,...}/
      hooks/{useFocusRestore,useDisclosure,useReducedMotion,...}/
      styles/{app-shell.css,utility.css,rtl.css}
      stories/
      tests/
    api-client/          # Versioned API contracts from ADP-DOC-009
    i18n/                # WordPress translation adapters
    types/               # Typed projections, not domain authority
  admin/                 # Publisher console only
  advertiser/            # Organization-scoped portal
  storefront/            # Public catalog and purchase entry point
  layout-studio/         # Editor/canvas/inspector bounded UI
  analytics/             # Chart and heatmap interfaces
  public-renderer/       # Independently bundled, minimal non-React ad runtime
  tracking/              # Consent-gated, optional collection bundles
blocks/{ad-slot,ad-zone,ad-repeater,ad-showcase}/
```

### 13.2 Build artifact separation

| Output | Loaded in | Must not include |
|---|---|---|
| Admin app JS/CSS | Authorized wp-admin screen(s) | Browser ad measurement SDK without need |
| Advertiser portal JS/CSS | Only scoped advertiser pages | Publisher-only financial/private datasets |
| Public storefront JS/CSS | Public catalog/offer pages | Admin shell, heavy Layout Studio dependencies |
| Layout Studio JS/CSS | Authorized Studio screen | Live paid impression reporting in preview |
| Analytics JS/CSS | Authorized analytics screens | Optional replay SDK unless consent/feature enabled |
| Gutenberg editor bundle | Editor/Site Editor block contexts | Full frontend ad-serving application |
| Public ad renderer | Only pages with eligible ad zones | React admin app, chart library, replay dependencies |

**No global style leakage:** use per-entrypoint CSS. Public style selectors are anchored under `.adp-zone`/`.adp-slot` or authorized ad creative container, with safe element selectors. Do not declare blanket `body`, `button`, `.wp-block`, `.components-button`, `h1` CSS on the public site. Any editor-only customization remains scoped to AdPilot blocks and must defer to WordPress editor UI conventions.

### 13.3 WordPress package selection

- Use stable public APIs provided by `@wordpress/components`, `@wordpress/block-editor`, `@wordpress/i18n`, and other WordPress packages for contexts that need them; let WordPress's enqueuing system manage dependencies in supported versions. `@wordpress/components` requires its stylesheet and related dependencies loaded according to documentation.
- `@wordpress/ui` is experimental at the time of this specification. Consider its newer component/theming model behind a documented version/compatibility ADR, not as a hard prerequisite for R1.
- `@wordpress/theme` design tokens/ThemeProvider can be evaluated for compatible admin surfaces; reconcile collision with the approved `--adp-*` palette and avoid replacing `theme.json` or site themes.
- Use documented block metadata `style`, `editorStyle`, `viewStyle` and render entry points to isolate assets. Test actual behavior in selected supported WordPress versions.
- Do not import Gutenberg/private source files, undocumented component internals or unlicensed icons. Icons should come from approved GPL-compatible/MIT/OFL/etc. sources according to exact licensing and trademark review.

### 13.4 Typed CSS class and polymorphism rules

- Use an internal `cx()` helper or approved class concatenation; no dynamic generation of uncontrolled CSS values from advertiser input.
- Public component API may expose documented `className`, `style` only for validated design-safe props; avoid exposing arbitrary script/HTML injection surface.
- Avoid uncontrolled HTML `dangerouslySetInnerHTML` in UI; approved creative renderer is separate and governed by security controls.
- Component imports must remain acyclic; app-specific modules may depend on `shared/design-system`, but design-system code must not import application stores, API endpoints or tenant data.
- Use WordPress i18n and translation catalogs; avoid string concatenation across locales and preserve grammatical number/plural behavior.

### 13.5 Portal overlays and focus management

Modal and popup roots may render outside the `.adp-app` root. The application must supply them with the correct tokens and localization direction using documented portal/theme APIs; test in wp-admin, Gutenberg iframes and the public advertiser portal. Focus return should target the opener or a documented safe fallback after deletion. Prevent stacking order conflicts with native WordPress dialogs and admin notices.

### 13.6 Performance and bundle governance

Baseline targets MUST be derived and approved through the later performance specification rather than fabricated in this document. Each component story shall capture initial JS/CSS bundle contribution, on-demand chunks, render count, layout shift and memory under realistic data. High-density inventory administration uses pagination and summary rendering; public pages use framework-light ad delivery and shared browser observers. Analytics libraries should be lazy-loaded only in analytical screens. Never add a real-time polling loop because a dashboard visually looks live; follow documented report freshness and scheduled processing.

---

## 14. Responsive and theme isolation implementation contracts

### 14.1 Application shell vs theme consumer

The admin app may own its content-area shell under `.adp-app` and permit light/dark preference; it must coexist with WordPress chrome and must not hide update notices. The advertiser portal may own its content region but must respect active theme width, heading semantics and navigation. The public ad renderer must not style surrounding theme content; only geometry needed for safe ad delivery, scoped ad disclosure and creatives.

### 14.2 RTL and mixed scripts

Use `margin-inline`, `padding-inline`, `inset-inline-start`, `border-inline-*`, `text-align: start`. Do not blindly flip navigation meaning, graphs' chronological left-to-right axis, or string literals in code/URL fields. UI navigational arrows may mirror where semantically appropriate. Technical code/IDs/URLs should render LTR in isolated containers and have copy buttons or selectable text. Numbers and currency should be locale-aware while accounting values remain unchanged. Confirm Arabic/Persian translations visually and with screen readers.

### 14.3 Long labels and localization expansion

Every label, button and breadcrumb must be testable with 1.5–2x length pseudo-locale; wrapping is preferable to truncating state, price or law-related notices. Use `min-width: 0` in flex/grid children and controlled overflow for long identifiers. Never truncate accessible name while showing the longer name in a tooltip alone.

### 14.4 Public ad container behavior

`grid-template-columns: repeat(var(--adp-columns, 1), minmax(0, 1fr))` is a conceptual render option and needs runtime validation for narrow themes. Permit breakpoint-dependent columns, stable aspect boxes and configurable gap, while preserving slot identities. A hidden/responsive slot should not silently create a billable viewable impression. The ad renderer should avoid layout shift by reserving space or using a documented collapse/fallback policy. Do not demand 500 ad creatives to be visibly mounted simply because 500 configured slots exist.

### 14.5 Visual regression matrix

Capture representative story fixtures for 320/360/390/768/1024/1280/1440 px; light/dark; LTR/RTL; normal/200%/400% zoom; hover/focus/error/disabled; reduced motion; high contrast; Gutenberg and classic themes. Include long page with 4 header, 100 sidebar, 196 in-content and 200 footer *logical slots*, but verify bounded visual preview rather than generate a 500-creative screenshot as the sole benchmark.

---

## 15. Visual review, story fixtures, accessibility and testing tooling

Each component must have at least: default, loading, empty, invalid, forbidden/disabled, overflow/long locale, dark, RTL, keyboard focus, and high-contrast stories as relevant. Story fixtures are synthetic only and must not contain copied real advertiser creatives or customer personal data. Use a locally runnable component showcase (Storybook or a compatible open-source equivalent) with reviewed dependency licenses; do not ship the development tool in the frontend runtime.

Automated checks: token schema and contrast combinations; TypeScript strict; ESLint; component unit tests; visual snapshots; Playwright interaction tests; axe-core accessibility checks; RTL and responsive screenshot matrix; reduced-motion checks; CSS selector leakage inspection; bundle size/regression comparison; dependency/license audit. Supplement automation with manual keyboard/screen-reader tests and product UX research; automated checks do not establish complete WCAG conformance.

**Visual acceptance criteria:** content readable in both themes and relevant language directions; consistent panel spacing and labels; accessible focus/interaction; stable layout, truthful state messaging, useful empty/error handling, and no unauthorized data appearing in browser markup. UX sign-off records actual evidence artifacts, not only positive claims.

---

## 16. Acceptance and adversarial test catalog (new Document 12 cases)

`DS12-T-001..` component-level oracles are defined at each component entry in Section 7; the `DS12-A-*` suite below provides cross-screen high-value scenarios. All are test specifications, **not executed results**. Every case must be backed by browser evidence and the relevant backend/authorization tests from the controlling document.

| Test ID | Area | Scenario | Given/When/Then oracle |
|---|---|---|---|

| `DS12-A-001` | NAV | Owner sees navigation for authorized site only | Given publisher role and site A, when opening console, then show only allowed site-scoped routes and no B data. |
| `DS12-A-002` | NAV | Advertiser org switch flushes scoped projections | Given memberships in org A and B, when switching B, then invoices and campaign lists from A are removed and query cache keys scoped. |
| `DS12-A-003` | NAV | Forbidden route direct URL | Given no finance capability, when loading finance deep link, then show safe forbidden state with no private DOM payload. |
| `DS12-A-004` | NAV | Future feature flag disabled | Given R1 only, when navigating analytics module, then no R2 heatmap UI falsely functions. |
| `DS12-A-005` | NAV | Critical navigation does not lose draft | Given dirty campaign wizard, when navigating away, then present save/recovery behavior without false saved state. |
| `DS12-A-006` | NAV | Skip link/heading landmarks | Given keyboard user, when pressing Tab from document start, then provide skip link/main landmark and coherent headings. |
| `DS12-A-007` | TOK | Color contrast of normal body copy | Given approved light/dark tokens, when measuring rendered text, then normal text is >=4.5:1 except defined WCAG exemptions. |
| `DS12-A-008` | TOK | Meaningful borders and icons | Given input/focus/active UI, when measuring non-text contrast, then meaningful boundaries meet applicable 3:1. |
| `DS12-A-009` | TOK | Status distinguishable without color | Given grayscale/high contrast, when reviewing statuses, then name/icons or patterns distinguish each. |
| `DS12-A-010` | TOK | Dark theme independent of WordPress theme | Given dark AdPilot preference, when opening public placement in light site theme, then plugin app theme does not globally recolor site. |
| `DS12-A-011` | TOK | High contrast forced colors | Given forced-colors preference, when using forms, then borders/focus/action remain visible. |
| `DS12-A-012` | TOK | Token generation deterministic | Given committed manifest, when generating CSS/TS twice, then output hashes match and no unreviewed literals remain. |
| `DS12-A-013` | A11Y | Keyboard-only campaign purchase | Given keyboard visitor, when selecting offer through quote, then every step is operable with accessible errors. |
| `DS12-A-014` | A11Y | No-drag Layout Studio operations | Given keyboard user, when moving slots and selecting 200 entries, then equal functionality exists without drag. |
| `DS12-A-015` | A11Y | Modal focus and return | Given confirmation modal, when opened/closed, then focus is contained and restored appropriately. |
| `DS12-A-016` | A11Y | Field validation association | Given server validation errors, when screen reader accesses field, then control announces label and associated error. |
| `DS12-A-017` | A11Y | Long page reflow at 400% zoom | Given narrow zoomed viewport, when editing/clicking primary controls, then no task loses access. |
| `DS12-A-018` | A11Y | Accessible table sorting | Given publisher data table, when sorting, then sort state, headings and item count announced. |
| `DS12-A-019` | A11Y | Charts without graphics | Given screen reader, when hiding SVG/chart, then all critical metrics are reachable in labeled table. |
| `DS12-A-020` | A11Y | Heatmap without spatial input | Given R2 heatmap, when using only keyboard/list view, then slot-level exposure is inspectable. |
| `DS12-A-021` | A11Y | Touch target sizes | Given mobile 320px, when inspecting action targets, then WCAG target size/spacing requirements hold. |
| `DS12-A-022` | A11Y | Motion reduction | Given reduced motion, when opening overlays and loading views, then nonessential animation is suppressed. |
| `DS12-A-023` | FORM | Large translated error string | Given pseudo-localized long errors, when validating, then message wraps and no button covered. |
| `DS12-A-024` | FORM | Input preservation after network failure | Given campaign draft and failed save, when retrying, then nonsensitive fields retained and unsaved state clearly indicated. |
| `DS12-A-025` | FORM | Payment state unknown | Given redirect with unverified callback, when displaying checkout result, then no receipt or paid indicator until server confirmation. |
| `DS12-A-026` | FORM | Quote expired between steps | Given expired quote/hold, when submitting checkout, then UI offers re-quote, not hidden extension. |
| `DS12-A-027` | FORM | Conflict during concurrent moderation | Given creative updated by reviewer B, when A approves stale revision, then show conflict and re-fetch. |
| `DS12-A-028` | FORM | Creative upload blocked | Given invalid MIME/unsafe asset, when uploading, then show actionable result and no unsafe preview execution. |
| `DS12-A-029` | FORM | Invalid DST schedule | Given ambiguous local time, when selecting booking dates, then UI requires explicit zone/clarification. |
| `DS12-A-030` | FORM | Business action disable reason | Given paid slot blocked for archive, when operator tries archiving, then explain impacted contract IDs and resolution. |
| `DS12-A-031` | LAY | Layout Studio 500 slot editor performance | Given 500 configured logical slots, when opening Studio, then bounded previews render and slot navigator remains usable. |
| `DS12-A-032` | LAY | Layout Studio 1000 slot stress | Given 1000+ slots, when filtering/editing, then virtual/paged UI has accessible counts and stable focus. |
| `DS12-A-033` | LAY | Responsive slot identity | Given 200 footer slots, when changing columns, then stable IDs and reservations do not change. |
| `DS12-A-034` | LAY | Sold slot structural edit blocker | Given booked footer slot, when applying destructive layout, then publish blocked with contract impact detail. |
| `DS12-A-035` | LAY | Layout version compare | Given v4 and v5, when opening diff, then affected IDs, geometry and campaign impact shown. |
| `DS12-A-036` | LAY | Canvas/list selection parity | Given slot selected on canvas, when switching structural list, then same selected stable ID retained. |
| `DS12-A-037` | LAY | Admin preview nonbillable | Given Studio or Gutenberg preview, when loading creatives, then no production impressions or payable clicks recorded. |
| `DS12-A-038` | LAY | Dark theme preview isolation | Given editor canvas with site preview, when changing AdPilot dark mode, then site preview keeps intended theme appearance. |
| `DS12-A-039` | LAY | Invalid unsold slot state | Given no eligible creative, when selecting slot, then show honest empty/fallback presentation, not phantom campaign. |
| `DS12-A-040` | GUT | Dynamic block context styles | Given editor and frontend, when rendering ad-zone, then only appropriate CSS/JS assets load. |
| `DS12-A-041` | GUT | Shortcode and PHP equivalent | Given same zone via block/shortcode/PHP, then output uses shared eligibility and render-instance separation. |
| `DS12-A-042` | GUT | Theme CSS leakage | Given unrelated theme headings/buttons, when ad CSS enqueued, then computed styles outside ad wrappers unchanged. |
| `DS12-A-043` | GUT | Unsupported hook diagnostic | Given missing custom hook, when previewing binding, then show actionable diagnostic (when released) not silent success. |
| `DS12-A-044` | GUT | Block editor portal focus | Given Gutenberg property dialog inside editor/iframe, when opening, then overlay focus and tokens work in destination DOM. |
| `DS12-A-045` | ANA | Metric unknown vs zero | Given missing ledger or event aggregation, when rendering KPI, then show unavailable/partial not zero. |
| `DS12-A-046` | ANA | Chart data and table parity | Given time series including gaps, when switching chart/table, then all known/missing bins equivalent. |
| `DS12-A-047` | ANA | Ad status planes | Given paid pending approval campaign, when opening detail, then payment/approval/delivery shown independently. |
| `DS12-A-048` | ANA | Viewable count vs configured slots | Given 500 slots and 35 viewable events, when reporting, then distinct 500 configured and 35 viewable. |
| `DS12-A-049` | ANA | Heatmap version mismatch | Given R2 events from old geometry, when loading new snapshot, then show mismatch or correctly versioned mapping. |
| `DS12-A-050` | ANA | Heatmap palette color-blind mode | Given grayscale mode, when reading heatmap, then numbered bins and table remain sufficient. |
| `DS12-A-051` | ANA | Attention proxy label | Given pointer/scroll-based engagement measure, when showing result, then no gaze/eye-tracking language. |
| `DS12-A-052` | ANA | Privacy withdrawal | Given user revokes consent, when switching state, then optional collection ceases under applicable policy without blocking basic page. |
| `DS12-A-053` | ANA | Session replay release gating | Given R2 deployment, when visiting heatmap explorer, then R4 replay controls not displayed as active. |
| `DS12-A-054` | OPS | Export status revoked | Given export created then role revoked, when downloading, then recheck denies and prior data not sent. |
| `DS12-A-055` | OPS | Degraded queue health | Given scheduled jobs failing, when reading health page, then show delayed/warning rather than healthy success. |
| `DS12-A-056` | OPS | RTL mixed numeric amounts | Given Persian locale and USD price, when showing invoice/hold, then amount and currency isolate remain readable and arithmetically identical. |
| `DS12-A-057` | OPS | Cross-browser icon/font availability | Given no external network, when opening app, then system fonts, bundled approved icons and necessary controls render. |
| `DS12-A-058` | OPS | Unauthorized render-cache leakage | Given organization A and B, when navigating rapidly, then scoped data never appears in wrong UI. |
| `DS12-A-059` | OPS | Portaled tooltip theme | Given dialog/popover moved under body, when toggling dark/RTL, then popup has correct colors and direction. |
| `DS12-A-060` | OPS | Visual regression no silent token drift | Given updated component, when snapshot diff occurs, then review records intentional visual change. |
| `DS12-A-061` | OPS | Concurrent hold busy action | Given double-click checkout hold, when pending, then UI remains clear and backend idempotency prevents duplicate effect. |
| `DS12-A-062` | OPS | Financial values in locale | Given 2 currencies with distinct decimal scales, when formatting, then representation matches authoritative precision. |
| `DS12-A-063` | OPS | Unsafe dependency rejected | Given proprietary icon/font dependency, when assembling release, then license and SBOM gate prevents unapproved asset. |
| `DS12-A-064` | OPS | Responsive ad density warning | Given 500-slot layout, when requesting full eager rendering, then UI exposes density/performance constraints. |
| `DS12-A-065` | OPS | Theme content untouched by public ad SDK | Given plugin deactivated, when loading site page, then no global reset or structural modification persists. |

## 17. GPT-6 High implementation work packages

Each package SHALL be executed within a bounded context with entry criteria, file ownership, acceptance links, actual verification command/output and a review gate. Suggested files are subordinate to ADP-DOC-006 physical architecture. Packages may run in parallel only after stable token and API contracts are approved. Do not treat an unrun test as passed.

| Package | Task | Deliverables | Accountable reviewers | Exit gate |
|---|---|---|---|---|

| `DS12-WP-01` | Token architecture & brand direction | Token manifest/schema, light/dark palette, contrast fixtures | UX/Visual + Frontend | Review semantic color and governance before building component CSS. |
| `DS12-WP-02` | Typography, spacing, responsive scales | Type/space/radius/motion tokens, RTL-safe utility classes | Visual + Accessibility | Verify readability at 320 px and zoom. |
| `DS12-WP-03` | UI package boundary and builds | Scoped CSS/TS artifacts, WordPress dependency mapping | Frontend + WordPress | Validate wp-admin/editor/site isolation and bundle budgets. |
| `DS12-WP-04` | Foundational navigation components | AppShell, Breadcrumb, SideNavigation, TabBar, StepProgress | Frontend + UX | Keyboard and role-scoped navigation acceptance. |
| `DS12-WP-05` | Forms and validations | TextField, CurrencyInput, DateTimePicker, SelectCombobox, FileUploader | Frontend + Accessibility | Native controls, error association, DST and locale tests. |
| `DS12-WP-06` | Actions, overlays and focus | Buttons, Notices, Dialogs, Popovers, Toasts | Frontend + Accessibility | Portal DOM/theme, modal focus, confirmation states. |
| `DS12-WP-07` | Tables, filtering and bulk actions | DataTable, FilterBar, Pagination, BulkActionBar | Frontend + Backend | Server paging, org scoping, large set accessibility. |
| `DS12-WP-08` | Status and financial semantics | StatusPill, PaymentStatusPanel, ApprovalTimeline | UX + Finance + Frontend | Separate payment/approval/delivery states; pending server truth. |
| `DS12-WP-09` | Marketplace cards and campaign wizard | InventoryOfferCard, CreativePreview, CampaignWizard | UX + Frontend + AdTech | Complete keyboard purchase and quote recovery. |
| `DS12-WP-10` | Layout Studio visual primitives | LayoutZoneTree, LayoutCanvas, SlotNavigator | Frontend + Layout + UX | 500-slot bounded preview and keyboard selection parity. |
| `DS12-WP-11` | Layout inspector, diff and publish | ResponsiveInspector, LayoutDiffViewer, PublishImpactDialog | Frontend + Domain + UX | Sold inventory blocker and version consistency. |
| `DS12-WP-12` | Gutenberg editor component wiring | Inspector controls, editor preview, block stylesheet boundaries | Gutenberg + WordPress | No production billable preview, no private bypass. |
| `DS12-WP-13` | Theme and public renderer isolation | Minimal ad wrappers, responsive grid CSS, disclosure | Public Frontend + WordPress | Classic/block themes unaffected outside ad wrapper. |
| `DS12-WP-14` | Charts and dashboard cards | MetricCard, TimeSeriesChart, definitions, data tables | Analytics + Frontend | Numerical and freshness parity; no fake finance. |
| `DS12-WP-15` | Heatmap viewer/legend (R2) | Semantic overlays, normalized geometry, accessible table | Behavioral + Frontend + Privacy | Version mapping and chart/table alternative. |
| `DS12-WP-16` | Optional replay controls (R4) | Scoped timeline UI, masking and playback gating | Behavioral + Security | Absent in R1/R2; consent and access reviewed. |
| `DS12-WP-17` | Dark, RTL, forced-colors and localization | Theme switches, directions, pseudo-locales, zoom | Accessibility + Localization | No clipped controls or ambiguous money. |
| `DS12-WP-18` | Stories, visual tests and license audit | Story fixtures, tests, SBOM and supported package decisions | QA + DevOps + License | Auditable snapshots; no unauthorized assets. |
| `DS12-WP-19` | Cross-screen E2E and performance | All UI-01..20 snapshots; critical workflow automation | QA + Performance + UX | Release-aware screen and theme matrix pass. |
| `DS12-WP-20` | Production packaging and design sign-off | Compiled assets, documented API, decision log, changelog | Tech Lead + Product + UX | No unapproved visual/security/financial issue in release. |

### 17.1 Mandatory per-package GPT-6 High task envelope

```text
WORK PACKAGE: DS12-WP-XX
SOURCE: ADP-DOC-001..012 (read controlling text, not only summaries)
SCOPE: one component family / UI surface / implementation boundary
REQUIRED IDS: FR-*, BR-*, NFR-*, UI-*, UJ-*, AT-*, DS12-T-*, DS12-A-*
PRECONDITIONS: exact runtime versions, approved tokens, API schemas, permissions
FILES TO TOUCH: explicit allowlist (record any justified deviations)
IMPLEMENT: typed state, semantic markup, tokens, RTL, accessible interaction,
           responsive variants, loading/empty/stale/error/denied states,
           per-surface CSS, feature/release gating
PROHIBITIONS: no hardcoded finance success, no client authorization authority,
              no global theme resets, no production metrics from previews,
              no unreviewed proprietary UI assets
VALIDATE: static analysis, unit/component, browser E2E, accessibility,
          visual regression, responsive/RTL, license, bundle/performance
DELIVER: changed files, actual commands and output, screenshot/test evidence,
         remaining conflicts, migration/deprecation notes, docs updates
STOP: if policy/architecture/API collision is found, raise explicit decision;
      do not invent behavior or declare success without verified execution
```

### 17.2 Definition of Done for every component

A component is done only when: (a) approved semantic tokens and variants are used; (b) data source and release scope are documented; (c) all specified states have usable UI; (d) keyboard and screen-reader tasks are verified; (e) light/dark/RTL/zoom/reduced-motion/forced-colors behavior has evidence; (f) source tests and dependency licensing pass; (g) public/private styling isolation is proven; (h) server authorization is enforced outside UI; and (i) visual/UX/QA leads record sign-off. A pixel-perfect image without working interaction is insufficient.

---

## 18. Cross-document traceability

### 18.1 Complete functional requirements index (129 canonical IDs)

The source `FR-*` ID, release, and normative behavior belong to ADP-DOC-001 and ADP-DOC-002. The entries below reference them, associate a design-surface obligation, and DO NOT attempt to rewrite or override those requirements. New visual tests supplement—not replace—the original `AT-*` tests. Features without a direct visible UI still require truthful administrator diagnostics, safe access restrictions or no frontend code as appropriate.

| Canonical requirement | Release (from ADP-DOC-002) | Design owner/surfaces | Design-system obligation | Primary original acceptance |
|---|---|---|---|---|

| `FR-CAM-001` | R1 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-001` |
| `FR-CAM-002` | R1 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-002` |
| `FR-CAM-003` | R1 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-003` |
| `FR-CAM-004` | R1 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-004` |
| `FR-CAM-005` | R1 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-005` |
| `FR-CAM-006` | R1 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-006` |
| `FR-CAM-007` | R1 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-007` |
| `FR-CAM-008` | R1 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-008` |
| `FR-CAM-009` | R2 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-009` |
| `FR-CAM-010` | R1 | Campaign / Moderation | Distinct campaign and creative states, approval version/reasons, safe creative presentation. | `AT-CAM-010` |
| `FR-COM-001` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-001` |
| `FR-COM-002` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-002` |
| `FR-COM-003` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-003` |
| `FR-COM-004` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-004` |
| `FR-COM-005` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-005` |
| `FR-COM-006` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-006` |
| `FR-COM-007` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-007` |
| `FR-COM-008` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-008` |
| `FR-COM-009` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-009` |
| `FR-COM-010` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-010` |
| `FR-COM-011` | R3 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-011` |
| `FR-COM-012` | R4 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-012` |
| `FR-COM-013` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-013` |
| `FR-COM-014` | R1 | Checkout / Billing / Calendar | Price/period transparency, UTC/local time distinction, hold expiry and authoritative payment response. | `AT-COM-014` |
| `FR-DEL-001` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-001` |
| `FR-DEL-002` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-002` |
| `FR-DEL-003` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-003` |
| `FR-DEL-004` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-004` |
| `FR-DEL-005` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-005` |
| `FR-DEL-006` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-006` |
| `FR-DEL-007` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-007` |
| `FR-DEL-008` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-008` |
| `FR-DEL-009` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-009` |
| `FR-DEL-010` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-010` |
| `FR-DEL-011` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-011` |
| `FR-DEL-012` | R2/R3 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-012` |
| `FR-DEL-013` | R1 | Public ad renderer / Delivery diagnostics | No global site CSS; responsive bounded ad presentation and correct measured/live distinction. | `AT-DEL-013` |
| `FR-HEAT-001` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-001` |
| `FR-HEAT-002` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-002` |
| `FR-HEAT-003` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-003` |
| `FR-HEAT-004` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-004` |
| `FR-HEAT-005` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-005` |
| `FR-HEAT-006` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-006` |
| `FR-HEAT-007` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-007` |
| `FR-HEAT-008` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-008` |
| `FR-HEAT-009` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-009` |
| `FR-HEAT-010` | R4 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-010` |
| `FR-HEAT-011` | R2 | Heatmap explorer | Geometry/version maps, privacy, labeled attention proxy, numerical accessible alternatives. | `AT-HEAT-011` |
| `FR-INT-001` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-001` |
| `FR-INT-002` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-002` |
| `FR-INT-003` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-003` |
| `FR-INT-004` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-004` |
| `FR-INT-005` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-005` |
| `FR-INT-006` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-006` |
| `FR-INT-007` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-007` |
| `FR-INT-008` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-008` |
| `FR-INT-009` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-009` |
| `FR-INT-010` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-010` |
| `FR-INT-011` | R2 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-011` |
| `FR-INT-012` | R1 | Gutenberg / Theme / Public renderer | Scoped integration styles, consistent previews, safe fallback and no billable editor renders. | `AT-INT-012` |
| `FR-INV-001` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-001` |
| `FR-INV-002` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-002` |
| `FR-INV-003` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-003` |
| `FR-INV-004` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-004` |
| `FR-INV-005` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-005` |
| `FR-INV-006` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-006` |
| `FR-INV-007` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-007` |
| `FR-INV-008` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-008` |
| `FR-INV-009` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-009` |
| `FR-INV-010` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-010` |
| `FR-INV-011` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-011` |
| `FR-INV-012` | R2 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-012` |
| `FR-INV-013` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-013` |
| `FR-INV-014` | R1 | Layout Studio / Inventory | Stable identities, responsive preview, slot density/occupancy, safe publish and bulk-edit states. | `AT-INV-014` |
| `FR-MET-001` | R1 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-001` |
| `FR-MET-002` | R1 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-002` |
| `FR-MET-003` | R1 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-003` |
| `FR-MET-004` | R1 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-004` |
| `FR-MET-005` | R1 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-005` |
| `FR-MET-006` | R1 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-006` |
| `FR-MET-007` | R2 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-007` |
| `FR-MET-008` | R2 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-008` |
| `FR-MET-009` | R3 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-009` |
| `FR-MET-010` | R3 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-010` |
| `FR-MET-011` | R3 | Dashboard / Measurement | Defined metrics, observed vs billable qualification, freshness, error and table equivalents. | `AT-MET-011` |
| `FR-MKT-001` | R1 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-001` |
| `FR-MKT-002` | R1 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-002` |
| `FR-MKT-003` | R1 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-003` |
| `FR-MKT-004` | R1 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-004` |
| `FR-MKT-005` | R1 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-005` |
| `FR-MKT-006` | R1 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-006` |
| `FR-MKT-007` | R1 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-007` |
| `FR-MKT-008` | R1 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-008` |
| `FR-MKT-009` | R1 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-009` |
| `FR-MKT-010` | R2 | Marketplace / Advertiser portal | Filterable offers, accessible forms, checkout, role-scope and reliable account guidance. | `AT-MKT-010` |
| `FR-OPS-001` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-001` |
| `FR-OPS-002` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-002` |
| `FR-OPS-003` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-003` |
| `FR-OPS-004` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-004` |
| `FR-OPS-005` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-005` |
| `FR-OPS-006` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-006` |
| `FR-OPS-007` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-007` |
| `FR-OPS-008` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-008` |
| `FR-OPS-009` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-009` |
| `FR-OPS-010` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-010` |
| `FR-OPS-011` | R4 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-011` |
| `FR-OPS-012` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-012` |
| `FR-OPS-013` | R1 | Admin health / Integrations / Developer UX | Diagnostics, background state, extension/compatibility and system readiness feedback. | `AT-OPS-013` |
| `FR-REP-001` | R1 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-001` |
| `FR-REP-002` | R1 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-002` |
| `FR-REP-003` | R1 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-003` |
| `FR-REP-004` | R1 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-004` |
| `FR-REP-005` | R2 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-005` |
| `FR-REP-006` | R3 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-006` |
| `FR-REP-007` | R3 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-007` |
| `FR-REP-008` | R2 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-008` |
| `FR-REP-009` | R2 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-009` |
| `FR-REP-010` | R1 | Reporting / Analytics | KPI formatting, chart table parity, safe export states and scoped financial presentation. | `AT-REP-010` |
| `FR-UX-001` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-001` |
| `FR-UX-002` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-002` |
| `FR-UX-003` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-003` |
| `FR-UX-004` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-004` |
| `FR-UX-005` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-005` |
| `FR-UX-006` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-006` |
| `FR-UX-007` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-007` |
| `FR-UX-008` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-008` |
| `FR-UX-009` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-009` |
| `FR-UX-010` | R2/R4 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-010` |
| `FR-UX-011` | R1 | Shared Design System / All UIs | WCAG AA, responsive/RTL/motion/component states, use tested semantic tokens. | `AT-UX-011` |

### 18.2 Invariants, business rules and release policy crosswalk

| Canonical baseline | Design enforcement and review |
|---|---|
| `BR-001..002` | Available, quote, hold and expired UI states differ; never announce reservation before atomic server confirmation. |
| `BR-003..004` | Paid/rejected creative paths have separate status lanes and remedy explanation. |
| `BR-005..006` | Sold inventory and exclusive conflicts are prominent in Layout Studio publish-impact UX. |
| `BR-007..008` | Approved creative version is explicit; stale eligibility signals prevent optimistic serving claim. |
| `BR-009..010` | Preview is nonbillable; excessive density warned and configurable policy displayed. |
| `BR-011..012` | Advertisers receive aggregates only; analytics retention and ledger separation are not obscured. |
| `BR-013..014` | Metrics labeled observed/estimated and money uses authoritative precision, unit and currency. |
| `BR-015..016` | UTC/time-zone clarity; accessible responsive controls and interaction alternatives. |
| `BR-017..018` | Third-party data destinations, privileged overrides and audited decisions have explicit warnings and confirmation. |
| `INV-01..10` | Apply exact PRD meanings to all visual states; no local redefinition. |
| `R1` | All essential catalogue/merchant, Layout Studio, block/theme, purchase and base analytics UI available as specified. |
| `R2` | Native aggregated heatmaps and specified advanced layouts become available only when implemented and enabled. |
| `R3` | CPM/CPC billing, attribution/advanced optimization UI is truthful and gated by validated financial system. |
| `R4` | Optional replay and network extras; never loaded or claimed in R1/R2. |

### 18.3 Individually indexed baseline NFRs and ACs

The following explicit identifiers are navigation links for implementation gating. Their meanings and release coverage remain in the PRD; design checks never substitute for full backend/security acceptance.

| Baseline | Frontend/design verification emphasis |
|---|---|

| `NFR-001` | Architecture: composable dependencies; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-002` | Maintainability and source conventions; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-003` | Rendering large logical inventories; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-004` | Performance and UX responsiveness; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-005` | Reliability and degraded states; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-006` | Financial truth and exact values; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-007` | Security and safe untrusted creatives; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-008` | Privacy and consent gates; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-009` | Accessibility WCAG 2.2 AA target; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-010` | Compatibility with themes/editor; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-011` | Localization and RTL; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-012` | Observability and diagnostic feedback; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-013` | Extension compatibility and public API; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-014` | Open-source licenses and asset provenance; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-015` | Safe upgrades and release migrations; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `NFR-016` | Verified task-based usability; preserve exact ADP-DOC-001 text, verify design-dependent behavior and test evidence. |
| `AC-001` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-002` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-003` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-004` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-005` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-006` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-007` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-008` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-009` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-010` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-011` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-012` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-013` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-014` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |
| `AC-015` | Apply the exact R1 acceptance from ADP-DOC-001 and corresponding feature tests; validate visual/a11y task completion where UI is involved. |

### 18.4 Complete user journey and screen navigation inventory

All 28 `UJ-01..28`, 18 `WF-01..18` and 20 `UI-01..20` retain their earlier numbering and contracts. Where a journey spans multiple screens, the appropriate component design in Section 7 and the cross-screen test in Section 16 apply. Do not use visual states to change business state machines or tenant permissions.

| Identifier family | Count | Controlling definition | Design acceptance source |
|---|---:|---|---|
| `UI-01..UI-20` | 20 | ADP-DOC-002/003/011 | Section 12 + component stories |
| `UJ-01..UJ-28` | 28 | ADP-DOC-003 | UXA-* plus DS12-A-* |
| `WF-01..WF-18` | 18 | ADP-DOC-003/004 | State-specific UI screens and original tests |
| `CAP-01..CAP-18` | 18 | ADP-DOC-002 | Component/surface families and work packages |
| `IC-001..IC-024` | 24 | ADP-DOC-006/007 | UI consumes typed/scoped API projections; no new contracts fabricated |

---

## 19. Open design decisions, assumptions and change control

| ID | Decision still requiring approval | Owner | Default until approved |
|---|---|---|---|
| `DS12-DEC-01` | Working product logo and final brand palette | Product + Visual Lead | Use proposed palette and text mark, no unlicensed artwork |
| `DS12-DEC-02` | Exact supported WordPress versions and package component set | WordPress + Frontend Leads | Stable bundled `@wordpress/components` where applicable |
| `DS12-DEC-03` | Whether to opt into experimental `@wordpress/ui` / `@wordpress/theme` | Architecture + UX | Not an R1 hard dependency |
| `DS12-DEC-04` | Dark mode in Gutenberg vs admin vs public marketplace | UX + WordPress | Scoped app preference; do not recolor active theme |
| `DS12-DEC-05` | Optional local font under an approved open-source license | Visual + License Reviewer | System fonts, no remote font requirement |
| `DS12-DEC-06` | Icon set and trademarks | Visual + License + Security | Approved bundled WordPress icons/native alternatives |
| `DS12-DEC-07` | Table virtualization library, if needed | Frontend + Accessibility | Server paging / bounded lists first |
| `DS12-DEC-08` | Heatmap color bins and sampling thresholds | Analytics + Accessibility | Numerical table plus clearly marked illustrative visual layer |
| `DS12-DEC-09` | Approved contrast pairs and refined dark palette | Visual + Accessibility | Proposed values, no unsupported AA claim |
| `DS12-DEC-10` | Autosave warnings and unsaved navigation behavior | UX + Backend | Strict authoritative save state, conflict recovery |
| `DS12-DEC-11` | Responsive Layout Studio breakpoint behavior | UX + Frontend | Section 9.1 structural list alternative |
| `DS12-DEC-12` | Performance budgets for bundles, render and previews | Performance + Product | Benchmark and freeze in downstream performance spec |
| `DS12-DEC-13` | Hosted component/story preview tooling | DevOps + License | Local open-source tooling; never mandatory runtime SaaS |
| `DS12-DEC-14` | Unresolved R1 vs R4 outbound webhook storage mismatch from ADP-DOC-010 | Architecture + Product | Do not imply an affected feature is implemented; resolve in authoritative documents |
| `DS12-DEC-15` | Public marketplace visual ownership against site theme | WordPress + UX | Inherit theme, use isolated AdPilot components |
| `DS12-DEC-16` | Administrator visual density preference persistence | UX + Privacy | Standard preset, safe per-user option if implemented |

Document decisions as ADRs/issue links with rationale, source impact, owner, planned release and changed tests. Do not silently weaken accessibility, consent, financial correctness or paid inventory invariants. If a conflict is discovered, stop dependent implementation and request upstream resolution.

---

## 20. Official references and compatibility verification

These references guide design-system implementation and must be verified against the chosen supported WordPress releases during development and release packaging:

1. WordPress `@wordpress/components`: <https://developer.wordpress.org/block-editor/reference-guides/packages/packages-components/> — public components, CSS dependencies, portal and RTL setup.
2. WordPress `@wordpress/theme`: <https://developer.wordpress.org/block-editor/reference-guides/packages/packages-theme/> — design tokens and ThemeProvider, not site `theme.json`.
3. WordPress `@wordpress/ui`: <https://developer.wordpress.org/block-editor/reference-guides/packages/packages-ui/> — experimental as documented in October 2026; version-gate any use.
4. WordPress Design System package guidance: <https://developer.wordpress.org/block-editor/contributors/design/design-system-packages/> — public API boundaries, portals and package transition.
5. WordPress Block metadata: <https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/> — `editorStyle`, `style`, `viewStyle`, PHP render file.
6. WordPress Block Editor accessibility: <https://developer.wordpress.org/block-editor/how-to-guides/accessibility/> — landmark and editor accessibility guidance.
7. W3C WCAG 2.2: <https://www.w3.org/TR/WCAG22/> and <https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/> — AA success criteria, focus/target and dragging requirements.
8. WAI-ARIA Authoring Practices: <https://www.w3.org/WAI/ARIA/apg/> — modal, combobox, tab, grid and tree behavior; implement full patterns where used.
9. WordPress Plugin guidelines: <https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/> — open-source-compatible distribution and assets.

**Final dependency/legal review:** Open source does not mean copyright-free; licenses and required notices apply. Fonts, icons, creative screenshots and example images need independent provenance and distribution permission. This design spec does not grant rights to redistribute visual assets.

---

## 21. Final specification acceptance and next document

- [ ] Source precedence respected; no `FR-*`, `BR-*`, `NFR-*`, `AC-*`, `INV-*`, `UJ-*` or `UI-*` is redefined.
- [ ] Semantic token registry has light/dark/forced-color behavior and verified real contrast pairs.
- [ ] All Section 7 components have typed contracts, accessible states, stories and required tests.
- [ ] UI-01..20 maps to correct release, role-scoped data, navigation and component families.
- [ ] Layout Studio maintains high-density bounded preview, stable slot identity and nonvisual alternative.
- [ ] Checkout/moderation/billing present independent authoritative states and safe recovery.
- [ ] Heatmap R2 vs replay R4 boundaries, privacy and quantitative alternatives are explicit.
- [ ] WordPress/Gutenberg/public site styling is isolated and compatible with RTL and classic/block themes.
- [ ] Test plan covers 320..1440 widths, zoom, keyboard, screen readers, forced colors, reduced motion and pseudo-locale.
- [ ] Dependency/license and visual provenance register is maintained.
- [ ] All issues requiring product/security/legal approval remain open, not quietly guessed.
- [ ] Document 12 is marked approved only after the required review, not merely after generation.

**Next: ADP-DOC-013 — Administrator Console UI & Dashboard Implementation Specification.** Define page-level layout, navigation, widget composition, priority information hierarchy, permissions, action choreography, typed UI state, React implementation structure and acceptance across all publisher-management screens. It shall use Document 12 tokens/components without altering source business rules.

**End-of-document status:** Proposed detailed design/implementation specification; no software implementation, WCAG conformance test, browser benchmark or user research is claimed completed.

