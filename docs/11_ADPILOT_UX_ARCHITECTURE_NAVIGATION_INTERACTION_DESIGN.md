# AdPilot — UX Architecture, Navigation & Interaction Design Specification

**Document ID:** ADP-DOC-011  
**Filename:** `11_ADPILOT_UX_ARCHITECTURE_NAVIGATION_INTERACTION_DESIGN.md`  
**Version:** 1.0.0 (proposed implementation contract)  
**Status:** Draft for Product Owner, UX Lead, Accessibility Lead, Frontend Lead, WordPress/Gutenberg Lead, Security/Privacy Lead, Finance Lead, and QA approval  
**Date:** 2026-10-12  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Authoritative predecessors:** ADP-DOC-001 through ADP-DOC-010  
**Target executor:** GPT-6 High, with scoped work packages and evidence-based verification  
**Next document:** ADP-DOC-012 — Visual Design System, Component Library & Frontend Design Tokens Specification  
**Scope owner:** Lead Product Designer / UX Architect  
**Technical co-owners:** React/TypeScript Lead, Gutenberg Lead, Frontend Performance Lead, Accessibility Lead  

> **Implementation truthfulness:** This is a design and engineering specification. Any visual components, source examples, metrics, personas, timings, and acceptance cases are *proposed*. No prototype usability study, UI implementation, accessibility audit, or performance measurement is claimed to have occurred. Final brand colors and target benchmark values must be approved in subsequent design/performance documents. This file must not reinterpret canonical requirements or invent working external services.

---

## 1. Governance, document authority, and normative language

### 1.1 Purpose

Establish the **user experience architecture and interaction contract** for all AdPilot surfaces, from public discovery and advertising purchases through publisher control of 500+ logical advertising slots, Gutenberg placements, campaign moderation, reconciled reporting, and privacy-aware visual heatmaps. It specifies navigation, screen structure, interaction state machines, information hierarchy, copy guidance, accessibility, responsive behavior, frontend architecture implications, and measurable UX acceptance obligations. It intentionally precedes the detailed visual design system and screen-specific pixel designs.

### 1.2 Document precedence (no silent mutation)

1. **ADP-DOC-001** controls product scope, `FR-*` (129 total), `BR-001..018`, `NFR-001..016`, `AC-001..015`, `INV-01..10`, R1–R4 releases.
2. **ADP-DOC-002** controls capability IDs `CAP-01..18`, requirement-level `AT-*`, E2E-01..17, and screen inventory `UI-01..20`.
3. **ADP-DOC-003** controls `PERS-01..16`, `JTBD-01..24`, `UJ-01..28`, `WF-01..18`, screen journeys and `UXA-01..28`.
4. **ADP-DOC-004** controls domain policies `DP-*`, `SM-*` machines, permissions scenarios, and all 18 canonical business rules.
5. **ADP-DOC-005** controls actual WordPress capabilities, organization-scoped roles, multi-tenant authorization and access rules.
6. **ADP-DOC-006/007** control module boundaries, application commands/queries, aggregate invariants, public interfaces, architecture decision records and event names.
7. **ADP-DOC-008/009/010** control schema ownership, API payloads/endpoints, event contracts, queued operations, and asynchronous failure behavior.
8. **This document** defines UX presentation/interaction obligations. It may add `UX11-*` supplementary requirements and `UX11-T-*` tests; it SHALL NOT relabel or weaken upstream identifiers.

A conflict between any earlier source and this document is a **review-blocking specification defect**. Resolve upstream with change control and revision history, not by guessing. Preserve unresolved cross-document decisions (including R1/R4 outbound-webhook subscription storage identified in Document 10) as open and do not show unsupported features as enabled.

### 1.3 Normative language and release gates

- **MUST/SHALL:** Required for the release containing the feature; blocking if not met.
- **SHOULD:** Recommended, exceptions require documented owner approval.
- **MAY:** Optional expansion.
- **R1:** Core marketplace, fixed/time-based purchasing, Inventory/Layout Studio, Gutenberg/theme, moderation, WooCommerce optional checkout, basic delivery/measurement, operational dashboards.
- **R2:** Aggregated click/scroll/visibility/attention-proxy heatmaps, layout-aware visual analysis and advanced viewability.
- **R3:** Qualified CPM/CPC financial models, attribution, experimentation, advanced optimization, richer price/package flows.
- **R4:** Optional separately consented session replay, network-wide/multisite federation and enterprise extensions.

Permissions, accessibility, privacy, financial correctness, and safe error handling are mandatory *from R1* for features present in R1. R2–R4 features SHALL NOT be simulated as available in R1 through buttons, fake data, or unverified claims.

### 1.4 UX-specific identifiers

| Identifier | Meaning | Scope |
|---|---|---|
| `UX11-P-*` | New interaction principle/pattern | All applicable screens |
| `UX11-NAV-*` | Navigation contract | A specific surface |
| `UX11-S-*` | Screen elaboration | Canonical `UI-01..20`, same names |
| `UX11-F-*` | Critical user flow blueprint | Reuses `UJ-*` / `WF-*` |
| `UX11-STATE-*` | Cross-screen state contract | Remote and local interactions |
| `UX11-A11Y-*` | Accessibility design contract | All interfaces |
| `UX11-T-*` | New verification case | Supplements `AT-*`, `UXA-*`, `E2E-*` |

### 1.5 UX quality gates

A feature is not "UX-complete" unless it has: approved task outcome; permissions-aware IA; responsive and RTL models; data provenance labels; wireframe/high-fidelity behavior; keyboard and nonvisual equivalent; localized copy keys; complete state handling; prototype or appropriate task-based review; automated tests; and documented unresolved risk. Accessibility checker passing alone is insufficient; expert manual and assistive-technology testing is mandatory for novel interactions.

---

## 2. Experience strategy, research hypotheses, and measurable product goals

### 2.1 Product experience thesis

**A complex advertising business should feel operationally understandable without sacrificing financial/measurement integrity.** The interface shall progressively expose professional capabilities, yet remain comprehensible to a first-time sponsor purchasing a single placement. Avoid a settings maze, split-brain campaign states, misleading analytics totals, and visually impressive but inaccessible editors.

### 2.2 UX operating principles

| ID | Principle | Required design consequence | Antipattern to avoid |
|---|---|---|---|
| UX11-P-01 | Task-first information architecture | Group navigation by the user's action and decision, not database table | `SlotSequenceRepository` menu entries |
| UX11-P-02 | Truthful commerce | Display quote, hold, payment, approval and delivery independently | "Your ad is live" immediately after payment |
| UX11-P-03 | Explainability | Show why an ad cannot serve or a slot cannot be sold | Unexplained disabled Publish button |
| UX11-P-04 | Density without overload | Summaries, search, virtualized *admin* tables and batch controls | 500 separate editor widgets in DOM |
| UX11-P-05 | Progressive disclosure | Common options first; advanced rules in labeled sections | Exhaustive 80-field default form |
| UX11-P-06 | Safer irreversible actions | Review impact, explicit confirmation, audit outcome | Silent release of sold inventory |
| UX11-P-07 | Accessible by construction | Keyboard parity; semantic controls; alternatives to canvas-only behavior | Drag-only slot ordering |
| UX11-P-08 | Responsive, not merely compressed | Adaptive workflows and breakpoints; preserve main tasks | Desktop three-pane canvas squeezed onto phone |
| UX11-P-09 | Honest metrics | Show denominator, time range, data freshness, sampling and confidence | Reporting "attention" as eye gaze |
| UX11-P-10 | Privacy-aware interfaces | Consent/status boundaries clear; no visitor-sensitive previews | Exposing raw replay to advertiser |
| UX11-P-11 | Native WordPress affordances | Reuse familiar core UI patterns and block controls | Redefining Gutenberg toolbar semantics |
| UX11-P-12 | Recoverable operations | Persistence, retry and conflict resolution are explicit | Resetting 6-step purchase wizard on timeout |
| UX11-P-13 | Design-system consistency | Shared accessible primitives, tokens, content rules | Different error/dialog conventions across apps |
| UX11-P-14 | Performance is UX | Non-blocking loading; no network waterfall; explicit stale states | Blocking entire page on reports refresh |
| UX11-P-15 | Tenant isolation is visible but not trusted | Clear organization switcher, masked denied states | Mixed data from multiple organizations |
| UX11-P-16 | Localized directionality | True RTL reflow, locale-aware commerce/date formatting | Mirroring currency values or confusing UTC terms |
| UX11-P-17 | Purposeful visualization | Charts and maps must support decisions and nonvisual access | Decorative heatmap without underlying table |
| UX11-P-18 | Evidence-based improvements | Instrument task completion without collecting sensitive form content | Selecting UX based on unsupported analytics claims |

### 2.3 Personas and jobs inherited from Document 03

Preserve all `PERS-01..16`, `JTBD-01..24`, and 28 `UJ-*` journeys; this document does not replace their individual narratives. Group design needs into six user lenses: (a) site/publisher owner; (b) AdOps/layout manager; (c) moderator/finance/analyst specialists; (d) advertiser organization owner/manager; (e) advertiser analyst/billing member; (f) visitor/editor/developer/support. A WordPress user may occupy more than one lens, but must select a concrete organization/site context before any scoped action.

### 2.4 Proposed research tasks and evidence

| Research task | Participants | Research method | Required evidence |
|---|---|---|---|
| Discover/buy a placement | First-time advertisers and experienced agencies | Moderated task + observation | Completion, misinterpretation, purchase trust concerns |
| Create 200-slot footer | Publisher/AdOps | Prototype task | Time-to-complete, errors, perceived control |
| Bulk edit 500 slots | AdOps using keyboard and pointer | Contextual task | Time, confidence, correctness, keyboard reachability |
| Preserve sold inventory | Publisher + finance | Tabletop critical incident | Decision quality and understanding of affected contracts |
| Approve creative revision | Moderator | Task-based evaluation | Changed version identified, correct review decision |
| Interpret heatmap | Analyst/Publisher | Data interpretation exercise | Differentiates gaze from visibility/interaction proxies |
| Payment pending + hold expiry | Advertiser | Failure-injection usability test | Trust, recovery, no duplicate checkout |
| WooCommerce absent | Publisher | Guided setup | Noncommerce flow usable, limitations clearly explained |
| Site Editor ad insertion | WordPress editor | Hands-on editor prototype | Familiar WordPress workflow, zero fraudulent tracking |

Use actual participants where possible; simulated persona testing may help discover issues but is not proof of user research. Establish baseline task metrics before freezing numeric launch targets; retain raw research notes only with suitable consent/privacy controls.

### 2.5 Suggested outcome metrics, without fabricated baseline

Track task success, time-on-task, time to first published zone, checkout completion, quote-expiration recovery, moderation resolution time, undo usage, access-denied comprehension, form error recurrence, abandonment by step, administrator support load, WCAG conformance evidence, CLS/INP exposure impacts, and incidence of ambiguous financial copy. Analytics of UI tasks must not capture password/payment/form contents.

---

## 3. Information architecture and route/navigation contracts

### 3.1 Six surface architecture

| Surface | Primary shell | Context | Core capabilities |
|---|---|---|---|
| Publisher Console | WordPress admin top-level `AdPilot` | Active WordPress site | Sales, inventory, campaigns, approvals, reports, operations |
| Layout Studio | Full-content wp-admin application screen | Active site + selected layout version | Tree, responsive preview, slot navigator, validation, publish |
| Advertiser Portal | Theme-compatible authenticated frontend | Active site + explicit organization | Campaign creation, orders, billing, performance, team |
| Advertising Marketplace | Public theme-compatible frontend | Site, published inventory, anonymous/user | Catalog, placement detail, non-sensitive rates/availability |
| Gutenberg Extension | Block Editor / Site Editor native shell | Editor post/template + selected block | Four dynamic blocks and inspector controls |
| Analytics Explorer | Authorized wp-admin / scoped portal reports | Site, campaign, layout version; R2 heatmap authorization | Aggregated metrics, visualization, exports, R4 opt-in replay |

Rich apps can share React/TypeScript components and frontend API types; the public ad rendering SDK must not inherit the heavy admin/analytics bundle. No frontend shell is an authorization boundary by itself.

### 3.2 Publisher console navigation (`UX11-NAV-01`, R1)

```text
AdPilot
├── Overview                         UI-01
├── Inventory
│   ├── Zones & Containers           UI-02
│   ├── Layout Studio                UI-03
│   ├── Slot Navigator               UI-04
│   ├── Template & Theme Bindings    UI-05
│   └── Booking Calendar             UI-13
├── Campaign Operations
│   ├── Campaigns                     UI-11
│   ├── Creative Moderation          UI-12
│   ├── Delivery Diagnostics         UI-15
│   └── Advertisers / Organizations  contextual org view
├── Commerce
│   ├── Rates & Orders                UI-14
│   ├── Billing Disputes              UI-19
│   └── Reconciliation                UI-14 contextual
├── Analytics
│   ├── Publisher metrics             UI-01 report drill-down
│   ├── Heatmap Explorer (R2)         UI-17
│   └── Report Exports                UI-18
└── Settings & System Health         UI-20
```

Show nav entries only if relevant feature is enabled *and* actor may discover the surface; the backend independently enforces access. A user lacking access to any section sees a genuine permitted landing destination, not a broken nav link. Deep links to newly forbidden content show a safe, informative denial without revealing private identifiers/data.

### 3.3 Advertiser portal navigation (`UX11-NAV-02`, R1)

```text
Advertise With Us / Public Marketplace
├── Explore Placements               UI-06
└── Placement Details                UI-07

My Advertising / Authenticated Portal
├── Campaigns                         UI-11 (advertiser projection)
│   └── Create / Edit Campaign        UI-09
├── Orders & Checkout                 UI-10 and scoped UI-14
├── Performance                       UI-16
├── Reports & Exports                 UI-18 if permitted
└── Organization
    ├── Profile & Verification        UI-08
    ├── Members & Invitations         UI-08
    └── Billing Profile               UI-08 / UI-14
```

Use explicit current site and organization in authenticated portal header. Switching organization must cancel outstanding organization-scoped fetches, reset uncommitted editor context, invalidate cached sensitive queries, and refresh policy-bound actions. Avoid automatically changing organizations on a successful deep link without a clear confirmation.

### 3.4 Gutenberg and theme navigation (`UX11-NAV-03`)

- Gutenberg Inserter categories: `AdPilot` → **Single Ad Slot**, **Advertising Zone**, **Automatic Repeater**, **Campaign Showcase**. Search synonyms: banner, advertising, sponsorship, placement, ad zone.
- Block inspector controls in native WordPress settings panel; editing toolbar uses standard block semantics. Use WordPress supports rather than rebuilding common alignment/spacing/color controls.
- `UI-05` Template Binding Inspector links from zone details and Setup/Integrations, not a competing code editor.
- Theme developer guidance exposes shortcodes, PHP rendering functions, available hooks and integration diagnostics. Copy controls present exact snippets and warnings; no arbitrary PHP/JS editor in wp-admin.

### 3.5 Navigation metadata and active-state contract (`UX11-NAV-04`)

Each route/screen has `route_key`, title key, parent, breadcrumb, required read authorization, release flag, contextual entity (site/org/layout), primary CTA, canonical back destination, and analytics classification (no private values). Preserve route context on refresh; do not serialize credentials to URL. Canonical entity identifiers may appear in privileged URLs if authorized each request; do not embed signed payment/export secrets in general navigation history.

### 3.6 Breadcrumbs, deep links, browser history (`UX11-NAV-05`)

- Admin: `AdPilot / Inventory / Homepage / Layout v12 / Conflict review`.
- Advertiser: `My Advertising / Brand X / Campaigns / Autumn Promotion`.
- Every deep link must restore a valid view or provide an actionable fallback with safe permissions.
- Store non-sensitive filter/sort/page state in URL where useful; avoid persisting confidential quote tokens and session material there.
- Browser Back returns prior navigational state; destructive/pending mutations require guard when leaving.
- Focus management: new route focus moves to main heading, unless returning to an intentional anchored control; announce meaningful route changes to assistive technology.
- Clicking a card navigates via a genuine link and does not hijack nested buttons or selectable rows.

### 3.7 Global shell and hierarchy (`UX11-NAV-06`)

- Persistent page title, concise context, one primary action, and secondary actions grouped by purpose.
- Organization/site switcher visually distinct from per-record selectors.
- Global notifications include unread count and safe detail links; prohibit unredacted sensitive text in browser notifications.
- Search scope is shown. Search results are authorization filtered, server-side paginated, and resistant to cross-org suggestions.
- Quick filters, saved views and bulk actions provide explicit counts and filters currently applied.
- Help access is contextual; failed financial/admin actions link to relevant recovery guidance.

---

## 4. Global interaction state and asynchronous feedback contracts

### 4.1 Mandatory states (`UX11-STATE-01`)

Every `UI-01..UI-20` screen must deliberately handle: **initial**, **first-load skeleton**, **loaded**, **genuine empty**, **filtered empty**, **form validation error**, **request failure**, **partial/degraded**, **unauthorized**, **stale/concurrent change**, **success/completed**, and **not available in current release/permission**. Additional context states: offline, unsaved draft, expired quote/hold, background processing, pending approval, hard policy block, irreversible action confirmation, and data freshness warning.

| State | Visual response | Semantic / recovery response | Forbidden behavior |
|---|---|---|---|
| Loading | Stable skeleton preserving layout | `aria-busy` for region; progress text if prolonged | Flicker and false empty state |
| Empty | Explain no records and show permitted first step | Actionable CTA | Dead-end illustration |
| Filtered empty | Show active criteria and Reset filters | State result count | Pretend no inventory exists |
| Validation | Field-level error and concise summary | Focus error summary/first invalid field | Toast-only error |
| Network failure | Preserve existing confirmed content as stale | Retry, safe state, correlation ID when useful | Replacing metrics with `0` |
| Partial | Show available data and missing dimension | Explicit limitation/freshness | Mixed metric denominators unlabeled |
| Unauthorized | Denial with route to allowed workspace | No protected data rendered | Leaking entity metadata in denial |
| Conflict | Side-by-side changes or current-state summary | Reload, reapply safely, re-authorize | Silent last-write-wins |
| Completed | Explain authoritative persisted result | Clear next step and receipt ID | Optimistic financial success |
| Future-release | Remove/disable with truthful context | No fake call-to-action | Present nonimplemented feature as working |

### 4.2 Save and optimistic-update policy (`UX11-STATE-02`)

- User-editable, reversible local form inputs may update optimistically.
- Campaign status, moderation, membership, price, inventory allocation, reservation, checkout, layout publication, settlement, invoice, and privacy consent operations **must wait for authoritative server confirmation** before showing committed success.
- Every editable resource carries a revision/version (or equivalent validator). Send with guarded update; stale writes receive explicit conflict recovery.
- Autosave state distinguishes `Editing locally`, `Saving`, `Saved to server`, `Offline/unsaved`, `Failed to save`, and `Conflict: another change`. Never claim "Saved" solely because a local draft exists.
- An idempotency key handles duplicate operation submissions where required; simply disabling the Submit button does not provide idempotency.

### 4.3 Feedback hierarchy (`UX11-STATE-03`)

- Inline validation near fields for fixable input errors.
- Inline contextual banners for long-lived state, action blockers, stale reporting, privacy configuration and payment pending.
- Accessible transient notice for completed low-risk action; no ephemeral toast as the sole evidence of payment/refund or destructive operations.
- Dialogs only for high-risk confirmation or tightly coupled secondary tasks; focus trap and return focus correctly.
- Real-time system alerts never auto-steal focus. Use appropriate polite/assertive live announcement.
- Copy user-facing reference codes plus human explanation; never show stack traces, payment provider secrets or SQL errors.

### 4.4 Forms and validation (`UX11-STATE-04`)

- Explicit labels; required/optional markers; examples and constraints before submit.
- Server is validation authority; client validates immediate format/empty fields without diverging business logic.
- Validate on blur or meaningful next-step attempts, not disruptive every-keystroke flashing.
- Preserve non-sensitive fields on server rejection; uploads/secret fields may need safe re-entry.
- Explain cross-field constraints (e.g., scheduling within sellable period, currency precision, format compatibility).
- Payment and tax amounts always come from authoritative quote/order response, not frontend recomputation.

### 4.5 Destructive and high-stakes actions (`UX11-STATE-05`)

- For delete/archive/override/refund/revoke/stop/publish-with-impact: show action, affected entities, known future effects, irreversible consequences, permission/approval gates, and explicit confirmation.
- A sold-slot move must display contract impact and proposed remedy. No one-click bypass of `BR-006` or `BR-018`.
- Financial changes show verified provider state, credited/debited amounts, currency, reversal semantics, audit notes and status; sensitive operations require additional approval where specified.
- If transaction fails or server response is unknown, show **unknown/pending**, not false success or false reversal; poll authorized status without duplicating command.

### 4.6 Time, date, currency, and precision (`UX11-STATE-06`)

- Store schedule and commercial boundaries in UTC per source docs; present explicit display zone (e.g., `Europe/Berlin`) and DST explanation for ambiguous/nonexistent local times.
- Use locale-aware date formatting while preserving exact ISO/UTC in inspectable technical details where authorized.
- Treat contract period as half-open `[start,end)`; clarify whether end timestamp is exclusive.
- Monetary display includes ISO currency and pricing unit. Treat estimated statistics separately from firm contractual values.
- Countdown timers inform user only; server-side hold TTL controls actual availability. Expired hold requires new quote/hold and may change price.
- Abbreviated numbers have exact values available via accessible labels/details (e.g., `1.2M` vs `1,240,519`).

### 4.7 Tables, filters, and large datasets (`UX11-STATE-07`)

- Server-side sorting/filtering with stable pagination and permission scope; show active filter chips and reset.
- Row actions are visible through an explicit button/menu, accessible without hover.
- Bulk selection across pages uses a named selection scope and count, with a review step and partial outcome reporting.
- Use virtualized admin tables where justified, preserving semantic row count, keyboard focus and screen-reader alternatives. This is distinct from public advertisement DOM virtualization.
- Never allow visual selection to imply records are authorized for update; server revalidates each operation.

### 4.8 Charts and data provenance (`UX11-STATE-08`)

Every chart/report presents: title, purpose, time range, time zone, source, relevant denominator, metric/version definition, freshness timestamp, sample completeness or confidence limitations, and accessible tabular equivalent. Distinguish `0`, `not measured`, `unknown`, `filtered`, `delayed`, and `permission denied`. Financial metrics derive from reconciled ledger projections; not browser counters. Clearly separate configured, served, rendered, measurable, viewable, qualified clicks and billable events.

### 4.9 Empty-state and onboarding contracts (`UX11-STATE-09`)

- Publisher first run: explain inventory → layout → offer → ad/approval → delivery → measured outcome. Offer a sample zone/creative, clearly nonproduction, and never auto-charge.
- Advertiser first run: discover placement → organization → creative → quote → checkout → moderation → delivery; offer progress checklist.
- No-WooCommerce: plugin remains operable for authorized manual/complimentary flows, with online checkout unavailable and explicit setup CTA only for authorized admins.
- Disabled analytics: show why collection is off and what reports still work, without inducing users to bypass consent.

### 4.10 Telemetry and instrumentation (`UX11-STATE-10`)

UX action telemetry is separate from billing measurement. Only collect permitted aggregate interaction metadata such as `task_kind`, screen key, completion, validation category, elapsed time band, and feature flag. Never log raw payment forms, names, secret headers, JWTs/nonces, personal input or behavioral session content in general UX event logs. Ensure previews, staging and QA events are nonbillable (`BR-009`).

---
## 5. Layout Studio — flagship UX blueprint (`UX11-F-01`)

### 5.1 Mental model and first-run experience

The Studio is a **versioned inventory layout editor**, not a visual editor for individual advertiser artwork and not a replacement for Gutenberg. Make these objects visibly distinct: Zone → Container → Stable Logical Slot → Placement Binding → Approved Campaign Allocation → Per-occurrence Render Instance. Use names and icons that convey the hierarchy, plus help text explaining why the number of *configured* slots differs from rendered/viewable impressions.

First-run wizard: select page/template scope; choose a preset (e.g., four-banner header, 100-item sidebar, 200-item footer grid); configure responsive layout; preview; validate; publish draft. Provide manual creation mode for experienced operators. Warn that an ad-heavy configuration is not a performance or advertising-policy endorsement; density policy may restrict effective rendering.

### 5.2 Three-pane desktop shell

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│ Breadcrumb · Page/template · Layout v12 [DRAFT] · Device · Undo · Publish │
├─────────────────┬───────────────────────────────────┬───────────────────────┤
│ TREE / INVENTORY│ PAGE / ZONE PREVIEW               │ CONTEXTUAL INSPECTOR  │
│ Search & facets │ zoom · responsive presets         │ identity + attributes │
│ ▸ Header (4)    │ [4 banner placeholders]           │ pricing impact link   │
│ ▸ Main (196)    │ [content / bounded ad preview]    │ contract reservations │
│ ▸ Sidebar (100) │ [sidebar summary of 100]          │ breakpoint rules      │
│ ▸ Footer (200)  │ [footer grid sampling]            │ validation/errors     │
├─────────────────┴───────────────────────────────────┴───────────────────────┤
│ Validate · 500 logical slots · 24 sold · 3 warnings · 0 blockers · Publish │
└─────────────────────────────────────────────────────────────────────────────┘
```

Do not render 500 full advertisement creatives inside the editor. Use bounded preview samples and a slot navigator. The selected object must be synchronized across tree, canvas, property inspector and breadcrumb. Text/table representations are mandatory, not a "fallback" considered optional for screen readers.

### 5.3 Panel contracts

**Zone tree:** search/filter by name, ID, binding, occupied/available/sold/invalid; expand/collapse grouped nodes, report total counts. Provide explicit context-menu actions (`Edit`, `Duplicate`, `Move`, `Archive`, `View contracts`) with ability/permission-dependent availability. Avoid overlong 1000-node DOM via indexed summaries and incremental expansion. Keyboard tree model must follow appropriate ARIA pattern *only if fully implemented*; otherwise use semantic nested lists and ordinary buttons.

**Canvas:** accurately indicate selected region and approximate layout geometry without making a screenshot the source of truth. Canvas is a *presentation* of an authoritative server-side plan. Selection, focus, zoom, device preview, read-only paid contract overlay, and validation indicators all have non-pointer equivalents. Provide safety notice when preview geometry is approximate due to third-party theme CSS. Canvas is not an ad serving endpoint and must not count impressions.

**Inspector:** display stable ID prominently; grouped tabs `Layout`, `Responsive`, `Inventory`, `Commercial commitments`, `Delivery`, `Tracking`, `Advanced`. Put safe reversible settings upfront. Use in-context validation; show effective inherited configuration and override origin. For sold slots, the commercial impact panel must be visible before publishing an adjustment.

**Status/footer bar:** show dirty state, current version, last server save, number of selected slots, warnings/blockers, validation status and primary publish action. The Publish action is disabled (with accessible explanation) when unsatisfied contractual or permission constraints exist. The validation report is a separate keyboard-reachable page/panel.

### 5.4 Generate 500 slots without 500 manual controls

Reference fixture: header 4, sidebar 100, main/in-content 196, footer 200 = **500 configured logical slots**. Generate through declarative sequence settings: `slot_count`, stable allocation strategy, columns/breakpoints, gap, slot dimension constraints, ordering and empty behavior. Show "100 generated slots" or "40 rows × 5 columns" without mounting individual controls for each. Permit individual slot-specific overrides through the searchable navigator, with stable ID preserved across layout revisions.

**Bulk changes:** choose explicit scope (selected IDs, current filtered set, entire sequence), preview actual affected count and protected/sold items, validate all candidate changes, obtain server-authoritative partial/atomic result policy, and show a detailed result table. Never silently skip protected slots or change commercial meaning due to a grid reorder. Include `Select all filtered (N)` distinctly from `Select visible (n)`.

### 5.5 Layout publishing state model

`Published vN` → create `Draft vN+1` → edit/autosave → run validation → detect impacted contracts → if none and authorized, publish guarded revision → invalidate matching placement caches → display authoritative published revision and any lag status.

On conflicting active reservation: show impacted slot ID, advertiser/campaign redacted appropriately, contract type, affected dates, safe choices (keep existing, postpone change, propose approved migration), and audit requirements. Overrides follow Documents 04–05 and may require independent approver. Do not offer a "Force Publish" shortcut lacking domain approval. Concurrent publication returns a conflict with latest approved layout; user can compare versions and reapply permitted changes.

### 5.6 Responsive configuration and geometry

- Display distinct configurations for desktop/tablet/mobile with user-selectable exact viewport widths.
- Use adaptive grid/stack/carousel layout options; distinguish number of configured logical slots from viewport-specific visible ads.
- Explain `minmax`, aspect-ratio, columns, spacing, intentional overflow, height reservation, sticky eligibility, and breakpoint inheritance without requiring CSS knowledge.
- The same logical slot may appear in different physical positions; render instance IDs must be unique per occurrence and layout version.
- Show RTL and zoom previews; ensure inspector and label movement respect inline-start/end.
- Warn about extreme page height, ad-to-content density, sticky overlap, and slow creative sizes without blocking valid configurations unless a mandatory policy applies.

### 5.7 Layout Studio interaction decision table

| Condition | Admin sees | Allowed recovery | Forbidden action |
|---|---|---|---|
| Slot sold and user drags to a different commercial area | Contract impact overlay | Cancel, safe contract-preserving adjustment, governed migration | Silent relocation |
| Another admin published new version | Version conflict comparison | Reload, branch/edit new draft | Silent overwrite |
| 200-footer preview too large | Summarized grid + paginated navigator | Drill into row/slot | Huge DOM mount |
| No matching zone | Filtered-empty state | Clear filter, create zone if authorized | Empty canvas with no explanation |
| Theme selector/binding missing | Incompatible/unknown target status | Choose supported integration; diagnostic info | Silent injection to guessed selector |
| Backend validation unavailable | Safe unpublished draft with retry | Retry or export local unsaved layout for authorized backup, if provided | "Publish succeeded" |
| Read-only analyst opens layout | Accurate preview with no editing CTA | View aggregate data | Change through client-side API manipulation |
| Mobile viewport | Stacked inspector + tree navigation | Edit via responsive forms/list | Inoperable collapsed three-pane canvas |

### 5.8 Acceptance

A user can generate the 500-slot reference layout with a bounded number of deliberate actions, find a specific stable slot ID, bulk-edit an unsold subset, review protected reservations and publish an authorized revision. A keyboard-only user can perform equivalent tasks without dragging. All operations reflect actual validated persisted state; the preview is nonbillable. Task durations are to be measured through research, not invented here.

---

## 6. Advertiser marketplace, campaign wizard and purchasing experience (`UX11-F-02`)

### 6.1 Public discovery (`UI-06`, `UI-07`)

Show a focused hero with a single action to explore placements, followed by search/filterable offer cards and a clear explanation of terms. Each offer must disclose position, creative format, applicable responsive layouts, unit (week/day/fixed package), actual published price/currency or "Request quote" policy, scheduling constraints and published approval policy. Estimated historical reach must be marked *estimate* with period/source, never sold as guaranteed views.

**Catalog facets:** placement region, template/content topic, supported ad dimensions, price band, schedule availability, device applicability, fixed/sponsorship, and appropriate format. Filter changes show result count and URL-restorable state. Anonymous search returns only public data; do not expose advertiser invoices, private campaign metadata, visitor-level analytics, or nonpublic inventory.

**Placement detail:** sanitized responsive mockup showing location inside a representative page, dimensions, delivery semantics, inventory availability indicator, trust signals, moderation conditions and cancellation/refund terms. Provide technical details in an expandable area for agencies, not as default content for new advertisers. Include "Availability may change until you reserve" warning.

### 6.2 Registration and organization context (`UI-08`)

Ask for the minimum needed to create an advertiser organization, then progressively collect billing/legal fields as required for purchase. Validate invitations, role scope and organization switching. An owner may manage members; analyst and billing roles see only the permissions documented in ADP-DOC-005. A user with multiple organizations sees a clear selector with the active organization name in the shell and checkout summary. Invalid, revoked, or expired memberships trigger a safe interrupted-workflow state and no data mixing.

### 6.3 Campaign wizard (`UI-09`)

Proposed clear step sequence, adaptable to context:

1. **Inventory:** select published placement/package; show slot capacity and contract unit.
2. **Creative:** select approved existing asset or upload new version with format validation; show editorial approval requirement.
3. **Targeting & schedule:** configure only available allowed rules, ISO-localized date/time with time zone; explain exclusions.
4. **Review price & availability:** request server-side quote; show line items, taxes, currency, expiry, estimated vs contractual quantities.
5. **Reserve & pay:** obtain actual transactional hold; transition to authorized WooCommerce checkout or approved manual/complimentary path.
6. **Status & next steps:** show distinct *payment*, *creative approval*, and *delivery* statuses, with remediation/actions.

Steps can collapse when a valid existing approved creative is reused, but sequence state must remain understandable. Progress indicator communicates position in flow; Back preserves valid inputs. Autosave must not record payment secrets. Each step has validation-summary and field-level messages, explicit optional/required choices, and resilient draft rehydration.

### 6.4 Quote, hold and time-sensitive UX (`UI-09/10`)

`Available` is advisory until the server accepts a hold. A hold's expiry appears in the checkout summary with server-authoritative timestamp; a client countdown is informational only. On expiry, disable reuse and offer "Refresh price and availability" to obtain a **new** quote and reservation; show any changed terms before reauthorization. On payment result unknown: display pending verification and poll current order status via authorized API; never send a second paid order simply because browser navigation was interrupted.

### 6.5 Payment and moderation are independent

The post-payment status screen must show a three-column (or stacked mobile) status summary:

| Payment | Creative approval | Delivery | Correct message |
|---|---|---|---|
| Pending | Not submitted | Not eligible | "Payment confirmation pending; ad is not running." |
| Paid | Under review | Not eligible | "Payment received. Creative review still required." |
| Paid | Approved | Scheduled | "Approved. Scheduled to start at [localized time zone]." |
| Paid | Approved | Active | "Currently delivering; view verified reporting." |
| Paid | Rejected | Stopped | "Creative rejected. Review available revision or applicable remedy." |
| Refunded/Disputed | Previously approved | Paused/blocked per contract | "Contract status changed; review resolution." |

Do not compress these into a single ambiguous "Campaign Active" chip. For pending payment, keep a recovery path to payment provider confirmation or support; never assert payment success from redirect return parameters alone.

### 6.6 No-WooCommerce mode

The plugin can start and operate without WooCommerce. In R1, public commercial checkout is unavailable unless an approved commerce adapter is active; the publisher may use documented admin-approved manual/complimentary campaign operations. In storefront, provide an honest availability/help message; no fake payment control or accidental free sale. Authorized admins see explicit adapter setup/health instructions. Do not silently switch a paid campaign into complimentary delivery.

### 6.7 Funnel accessibility and frontend implementation

Use native input types where appropriate, generous labels, screen-reader step progress, inline errors, preserve history, avoid surprise automatic navigation. External payment handoff must describe provider transition and ensure returning focus to authoritative status. Mobile purchasing flows should prioritize primary call-to-action, quote breakdown and payment terms without sticky elements obscuring fields.

---

## 7. Publisher operations, moderation, reservations and financial UX (`UX11-F-03`)

### 7.1 Operations dashboard

Organize by **requires action**, **financial/inventory risk**, **campaign status**, and **performance**; avoid presenting aggregate vanity metrics as highest-priority if urgent rejected creatives or payment disputes are unresolved. Allow role-specific dashboard presets. Reports must distinguish stale from zero and show time range and source.

### 7.2 Moderation desk

Moderation is a compare-and-decide task. Show exact creative version under review, immutable submitted preview, destination URL domain and safety metadata, changed fields/diff, applicable policy, and previous decisions. Provide decisions: approve, request changes, reject, emergency stop (if permitted); each requiring appropriate reason codes and sometimes reviewer notes. Do not allow a new creative revision to inherit approval accidentally. If a reviewer loses permissions or the version changes during review, invalidate the decision UI and request a fresh review.

### 7.3 Inventory booking calendar

Provide day/week/month/list views as supported by the selected MIT-licensed calendar dependency; for dense allocations, a list/table is the authoritative accessible alternative. Calendar cells summarize occupied, available, exclusive, and at-risk capacity with a legend; tooltips alone must not contain critical information. Support date-range filter, zone/container, device, reservation status, advertiser policy-safe identifiers and R1 fixed/time-based booking. A visual calendar overlap indication is advisory; server transactional booking enforces `BR-005`.

### 7.4 Rate cards, orders and reconciliations

Amounts include currency/unit, contract snapshot and tax treatment. A quote/order view presents who purchased what, when and under which terms, then authorized payment evidence separately. Reconciliation issues show precise mismatch type, affected entries, available safe actions, and audit trail. Never allow staff to edit a reconciled ledger row in place. Refund controls include amount, remaining eligible amount, provider status, reason, external reference and approvals. Offer export only when current rights permit and with protected download handling.

### 7.5 Campaign diagnostics and emergency pause

Diagnostics surface desired vs observed delivery state, current creative/version, inventory eligibility, last checked time, error category, queue health and a link to authorized remediation. A paused campaign must show "Stopped by [role] at [time]" where permitted, and propagate to serving eligibility; UI acknowledges only authoritative outcome and documents cache freshness. Avoid showing unsafe ad content inside diagnostic reports.

### 7.6 Notifications and audit navigation

Define action-oriented notification classes: creative review required, quote/hold expiration, payment unknown, overdue activation, suspended campaign, inventory conflict, privacy-processing pause, export ready and queue incident. Notifications should include safe context, owner, next action, and link to filtered destination. Support frequency/digest policies. User-facing notifications are not raw event-log payloads.

---

## 8. Gutenberg blocks, Site Editor and theme integration UX (`UX11-F-04`)

### 8.1 Four canonical blocks

| Block | Intended editor mental model | Essential controls | Preview behavior |
|---|---|---|---|
| `adpilot/ad-slot` | Place one logical slot | Slot picker, format, alignment, controlled overrides | Lightweight label + sample/approved safe placeholder; no billable event |
| `adpilot/ad-zone` | Embed reusable zone | Zone selector, layout summary, responsive overrides, capacity status | Bounded sample and "N configured" badge |
| `adpilot/ad-repeater` | Apply rule-based repeated insertion | Eligible content context, interval, spacing, max count, exclusions | Preview insertion anchors; warn on unsupported block contexts |
| `adpilot/ad-showcase` | Present a curated sponsored collection | Showcase selection, grid/carousel, a11y labels, responsive settings | Sanitized sample cards without paid tracking |

### 8.2 Editor behaviors

- Use `block.json` registration and WordPress `InspectorControls`, `BlockControls`, `block supports` where appropriate; server-rendered output is authoritative on frontend.
- A block selection reveals its current zone/slot name, stable ID, capacity summary, and preview disclaimer; unavailable/archived zone gets a recoverable warning.
- Editors with `edit_posts` but without inventory-edit rights can use eligible published slots subject to policy; they cannot bypass advertisement billing/moderation by editing a block attribute.
- Provide Browse/select dialogs with keyboard navigation, search and server-authorized data; do not require writing an opaque zone ID.
- Handle conversion of block themes, Gutenberg templates and classic editor/shortcode interactions as documented; do not claim `block hooks` work in unsupported contexts.
- Avoid huge previews for 100/200-slot stacks/grids; use shape wireframes and bounded sampled creative placeholders.
- Duplicate insertion creates unique render-instance IDs at runtime. Accidental duplicate bindings should warn, while intentional repeated placement is allowed only under actual allocation policy.
- Block preview and editor interactions never emit billable ad requests/clicks/impressions or session recordings.

### 8.3 Developer integration (`UI-05`)

A dedicated theme integration help panel shows validated copyable examples using proposed `adpilot_render_slot()`, `adpilot_render_zone()`, shortcodes, and WordPress hooks. For each example show expected argument names, limits, escaping responsibility, example location (classic template, block theme, widget), version support, and whether it performs an actual ad decision. Mark APIs not available in current runtime. Provide a "Test placement" diagnostic in authenticated admin with no production billing events. Never provide an unrestricted executable PHP editor.

### 8.4 Accessibility and preview parity

An editor can insert, configure, reorder and remove a block using keyboard and built-in WordPress controls. Every inspector control has a unique label and help description. Prevent focus loss when the preview updates. Server-side errors show safe text with a retry action. Ensure translated/RTL block labels and control placement match WordPress editor conventions rather than imposing a separate UI shell.

---

## 9. Analytics Explorer and heatmap interpretation UX (`UX11-F-05`)

### 9.1 R1 baseline reporting

Publisher and advertiser dashboards must show **qualified/scoped** metrics only: configured versus served versus rendered/measured/qualified as available in current release; use accurate naming. Where viewability/attention becomes R2, hide unavailable metrics or label transparently as not yet measured. Revenue derives from ledger, never a raw event counter. Default reporting period must be explicitly visible with timezone and latest processing timestamp.

### 9.2 R2 full-page and slot-level explorer

Recommended desktop shell: page/layout-version selector and filters at top; optional page navigator/zone tree at left; page image/sanitized geometry overlay center; properties/metric detail at right; synchronized table below. A device selector must load the correct layout snapshots and not combine incompatible geometry. Distinguish page-level, zone-level and specific slot views with persistent breadcrumbs. Offer a direct "Go to slot statistics" action that does not require visual hit-testing on a heatmap.

### 9.3 Heatmap mode definitions and labels

- **Click/tap map:** sampled or aggregated interactions on elements, not every visitor's individual intent.
- **Scroll-depth map:** how far eligible visits reached; distinguish long pages and device breakpoints.
- **Visibility map:** estimated time area/slot intersects viewport using declared thresholds.
- **Attention proxy:** based on visible time and permitted engagement heuristics; explicitly **not eye gaze or mindshare**.
- **Pointer movement map:** sampled desktop mouse movement only; absence on touch does not mean lack of interest.
- **Conversion-associated map (R3 when attribution enabled):** association, not a causal claim.

Show legend, scale, sample size/completeness, event definition, consent filter, freshness and source. Support colorblind-safe alternative patterns and numerical tables; ensure gradient alone is not the only carrier of meaning.

### 9.4 Long-page geometry and layout versions

A heatmap is keyed to page context, layout version, viewport/device class, element anchor and coordinate normalization. Scrolling from header through 100-slot sidebar and 200-slot footer must not misplace aggregated events after DOM reflow. During historical comparison, show compatible layout versions or a clear incompatible-geometry warning; do not blend incompatible screens into a false image. If historic sanitized snapshot is unavailable, present statistical table/slot data instead of inventing screenshot geometry.

### 9.5 Comparison behavior

Provide side-by-side periods or layout versions with synchronized filters, matched metric denominators, and annotated changes. If versions differ structurally, list matched/unmatched stable slot IDs and explain comparison limits. An uplift claim must include adequate sample data and definitions; full experimental inference belongs to R3, with statistically appropriate caveats.

### 9.6 Permissions, consent and replay

Only authorized staff may see the R2 heatmap explorer; advertiser users receive authorized **aggregate campaign statistics**, not visitor-level movement/session content. When consent-required data is unavailable, explain the gap neutrally. R4 replay is opt-in, independently authorized, filtered/masked and guarded by short retention. Never use heatmap UI to access raw replay data implicitly. Screen captures and exports must exclude personal and sensitive content.

### 9.7 Alternative representations

Every heatmap must have a keyboard navigable numeric table: zone/slot ID, element label, reached count, measurable count, qualified events, percentage denominator, visibility/time bands and confidence/completeness information. Selecting a row highlights the corresponding heatmap area if available; accessible users can complete analysis entirely via table, search and filters.

---

## 10. Responsive, RTL, localization and inclusive interaction (`UX11-A11Y-*`)

### 10.1 WCAG 2.2 AA target (`UX11-A11Y-01`)

All custom administrator, portal and public UI shall target **WCAG 2.2 AA**. Specifically test keyboard operability, visible/not-obscured focus, reflow/zoom, target size (minimum 24×24 CSS pixels or applicable WCAG exception; larger design targets preferred), accessible names/error descriptions, color contrast, focus order, keyboard shortcuts, logical headings, and consistent help. WCAG 2.2 dragging interactions require a non-drag single-pointer alternative. Distinguish conformance targets from a completed external accessibility certification.

### 10.2 Keyboard patterns (`UX11-A11Y-02`)

- Dialog: label, initial focus, escape/cancel where safe, focus trap, restore prior focus.
- Tabs: roving keyboard behavior only if using full APG tab semantics; native buttons/links otherwise.
- Grid/tree: define and test correct aria-rowcount/aria-colcount where virtualized; offer simpler table/list where complex semantics are not warranted.
- Bulk controls: select/clear, move up/down, named list selection alternatives to drag.
- Calendar: list view, keyboard date selection and screen-reader time-zone description.
- Heatmap: fully equivalent table and keyboard slot selector.
- Autocomplete: correct combobox pattern with predictable selection and no inaccessible custom popover.

### 10.3 Breakpoint/viewport test matrix (`UX11-A11Y-03`)

Proposed testing includes **320, 360, 390, 768, 1024, 1280, 1440** CSS-pixel widths; 200% and 400% zoom/reflow where required; portrait/landscape; high-DPI; RTL. These are a testing matrix, not hardcoded product media queries. Layout Studio desktop three-pane becomes tabbed panels/stacked navigation on narrow viewports, with a working structural/list editor. Public ad containers inherit safe responsive rendering from the active theme and block config. Avoid horizontal scrolling for standard copy and forms; wide data tables may use documented horizontal table scrolling with clear accessible headers.

### 10.4 Internationalization (`UX11-A11Y-04`)

Use translatable string keys with context/pluralization; do not concatenate localized phrases. Do not embed display currency symbols into arithmetic. Adopt `start/end` directional CSS logical properties. Proper RTL order must preserve appropriate LTR content such as URLs, email addresses, monetary codes, numeric IDs, API samples, and version strings. Test WordPress language switching independently from creative language, campaign-targeting locale and payment locale. Dates/times include explicit time zone and ambiguity handling.

### 10.5 Reduced motion and cognitive accessibility (`UX11-A11Y-05`)

Respect `prefers-reduced-motion`; avoid essential animation. Use plain-language state labels and one main action per screen. Expose definitions for CPM, CPC, viewability, quote hold, pending settlement, and layout impact. Use summaries before details; distinguish critical warnings from decorative badges. Keep consent interfaces neutral and avoid dark patterns.

---

## 11. Frontend performance and integration implications for UX

### 11.1 Bundle boundaries

The UI suite may share React/TypeScript data contracts and design components; load only the specific wp-admin app needed for each page. The public ad SDK should be independently code-split or framework-light, with lazy import of optional analytics code. Session replay must not load when disabled or without applicable consent. Gutenberg editor assets load in editor contexts; no editor bundle required to render a basic public ad slot.

### 11.2 Perceived speed and data freshness

Use server-paginated entity lists, bounded admin previews and background report generation. First paint should show stable application shell; long-running tasks provide stage and current authoritative status rather than frozen full-screen spinners. Do not invent guaranteed latency goals until performance baseline/document sign-off. Display known delayed aggregation; avoid combining live and settled financial numbers without labels.

### 11.3 Public page performance

High-density pages represent **logical inventory**, not immediate creative bytes. Lazy fetch, batch selection, shared intersection observers, reserved aspect ratios and progressively loaded containers should minimize main-thread, network and layout impacts. Editor previews are intentionally summarized. Responsive virtualization must not break scroll position, anchor targets, keyboard accessibility or heatmap geometry. Where it does, prefer simple placeholders and incremental hydration.

### 11.4 CSS and WordPress isolation

Use scoped class namespace, accessible design tokens, CSS logical properties, and WordPress-provided component packages where sensible. Do not globally style site `button`, `input`, headings, or `wp-block-*` outside owned scopes. WordPress admin apps should not patch core wp-admin DOM structurally. Test common themes, block themes, font scaling, forced colors and RTL. A portal can inherit selected theme typography but must retain readable contrast and focus.

---
## 12. Detailed screen interaction contracts (`UI-01..UI-20`)

These screen IDs and primary scope are inherited from ADP-DOC-002/003. The details below add implementation-level hierarchy, controls, states, API boundaries and test oracles. All screens inherit Section 4 global states, Section 10 accessibility, and per-object permission checks. UI screen-level specification does **not** override its domain owner.

### UI-01 — Admin executive dashboard (`UX11-S-01`)

- **Release:** R1. **Actors:** Publisher / AdOps / finance / analytics (role scoped).
- **First-view information hierarchy:** Operational alerts > campaign and approval backlog > inventory utilization > qualified metrics > data freshness.
- **Primary actions and interaction flow:** Open alert, filter work queue, inspect campaign, navigate reconciliation.
- **Information design / components:** Summary KPI cards with status text; prioritized action list; compact time-series + accessible data tables; date selector.
- **Critical exceptional, stale and permission states:** Never blend finance and event counters; stale/unmeasured data labeled and not replaced with zero; denied widgets removed and server projection scoped.
- **Responsive and accessibility requirements:** Single-column urgent tasks on mobile; keyboard headings and live status updates.
- **API/domain boundary:** Dashboard read models; publisher analytics and scoped finance query services. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** From a sample overdue-campaign alert, reach its cause and authorized action; missing ledger snapshot shows unavailable, not $0.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-02 — Zone registry (`UX11-S-02`)

- **Release:** R1. **Actors:** Publisher / AdOps.
- **First-view information hierarchy:** Current active/draft zones > stable IDs > bindings > slot counts > sold exposure.
- **Primary actions and interaction flow:** Create zone, search, duplicate, archive, launch Studio, inspect bindings.
- **Information design / components:** Server-paginated data table with count summaries and exposed filter chips.
- **Critical exceptional, stale and permission states:** Archiving sold inventory must be blocked with contractual impact; duplicate zone name allowed only with distinguishable stable ID.
- **Responsive and accessibility requirements:** Responsive cards/list, explicit row action menu, accessible multi-selection.
- **API/domain boundary:** Inventory zones/containers list, create, state, governed archive. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Create header, sidebar and footer zones; sold zone archive results in governed impact dialog and no silent deletion.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-03 — Layout Studio (`UX11-S-03`)

- **Release:** R1. **Actors:** Publisher / AdOps.
- **First-view information hierarchy:** Draft/published version > three-pane tree/canvas/inspector > conflicts > effective publishability.
- **Primary actions and interaction flow:** Generate sequence, inspect slot, change breakpoint, validate, publish or compare versions.
- **Information design / components:** Responsive workspace, bounded canvas preview, hierarchical search tree, property inspector and conflict report.
- **Critical exceptional, stale and permission states:** Guard publish on stale revision, sold slot change or unresolved policy; no mass DOM mounts and no impression tracking.
- **Responsive and accessibility requirements:** Keyboard/table editor and move buttons; collapse inspector on tablet; stacked navigable panels on mobile.
- **API/domain boundary:** Layout revisions, validation, publication, impact query endpoints. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Build 500 logical slots, review sold-slot move, publish nonconflicting version; keyboard parity and editor remains responsive.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-04 — Slot navigator (`UX11-S-04`)

- **Release:** R1. **Actors:** AdOps.
- **First-view information hierarchy:** Slot stable ID > zone/container > reservation/capacity > filters and selection scope.
- **Primary actions and interaction flow:** Search exact ID, filter, bulk select, inspect slot, apply authorized batch update.
- **Information design / components:** Server-paginated table with slot status; bulk review sheet showing total scope and protected subset.
- **Critical exceptional, stale and permission states:** Filtered count vs current page count explicit; partial success with per-slot reasons; abort when request revision conflicts.
- **Responsive and accessibility requirements:** Accessible selection state and keyboard actions; mobile list with bulk toolbar.
- **API/domain boundary:** Slot/sequence queries, bulk validation and guarded mutation. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Find slot 317 in 500-slot zone without mounting all rows; protected slots never silently change.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-05 — Template binding inspector (`UX11-S-05`)

- **Release:** R1 with R2 diagnostics enhancement. **Actors:** Editors / theme integrators / admins.
- **First-view information hierarchy:** Page/template binding, environment, placement context, integration mode, duplicate warnings.
- **Primary actions and interaction flow:** Assign allowed binding, preview, copy PHP/shortcode/hook example, run safe diagnostic.
- **Information design / components:** Context summary + supported integration tabs + syntax-highlighted copyable example + status.
- **Critical exceptional, stale and permission states:** Unsupported selector, duplicate binding and wrong permissions show precise recoverable notice; snippets are not editable server-side execution.
- **Responsive and accessibility requirements:** Snippets selectable by keyboard, copied status announced; small screen stacked panels.
- **API/domain boundary:** Binding query/create; nonbillable preview and compatibility diagnostics. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Equivalent zone inserted by block and PHP uses one renderer; preview produces zero billable events.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-06 — Public catalog (`UX11-S-06`)

- **Release:** R1. **Actors:** Anonymous and logged-in advertisers.
- **First-view information hierarchy:** Published offers > format/pricing labels > filters > comparative details.
- **Primary actions and interaction flow:** Search/filter, compare eligible offers, open detail, begin purchase.
- **Information design / components:** Theme-compatible responsive cards with compact facets, summary/estimated reach disclosure.
- **Critical exceptional, stale and permission states:** No results and unavailable catalog explained; privacy-safe projection; no fake checkout if Woo missing.
- **Responsive and accessibility requirements:** Native links, collapsible filters on mobile, result count announced.
- **API/domain boundary:** Published inventory search endpoint, no private financial fields. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Anonymous buyer can filter placements without retrieving other advertisers or protected inventory.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-07 — Placement detail (`UX11-S-07`)

- **Release:** R1. **Actors:** Prospective advertiser.
- **First-view information hierarchy:** Page position and responsive preview > units/terms > pricing > availability > creative rules.
- **Primary actions and interaction flow:** Choose schedule, review full terms, start wizard.
- **Information design / components:** Safe mockup with desktop/mobile tabs, rates, specs, availability visualization.
- **Critical exceptional, stale and permission states:** Estimated traffic labeled non-guarantee; stale inventory requires new quote; disabled sold offer explains alternatives.
- **Responsive and accessibility requirements:** Alt preview equivalent, no auto-playing video; sticky CTA never obscures terms.
- **API/domain boundary:** Published placement detail, availability and quote preview. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** User accurately states what period and ad format is being purchased after inspecting detail.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-08 — Advertiser onboarding/team (`UX11-S-08`)

- **Release:** R1. **Actors:** Advertiser owner/manager/billing/analyst.
- **First-view information hierarchy:** Active organization > business identity > permissions > invitations > billing eligibility.
- **Primary actions and interaction flow:** Create profile, select org, invite/revoke authorized member, update verification metadata.
- **Information design / components:** Stepwise onboarding, accessible member table, invite status with expiry.
- **Critical exceptional, stale and permission states:** Expired invitation, unauthorized role grant, revocation and multi-org context require explicit feedback; no union across organizations.
- **Responsive and accessibility requirements:** Mobile cards for member list; labeled invite role; secure focus after modal.
- **API/domain boundary:** Identity/membership/organization routes, capability projections. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** A user in two organizations cannot accidentally see another org invoice after switch.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-09 — Campaign wizard (`UX11-S-09`)

- **Release:** R1. **Actors:** Advertiser owner/manager.
- **First-view information hierarchy:** Current step > draft persistence > input errors > placement/creative > price review.
- **Primary actions and interaction flow:** Choose placement, upload creative, set schedule, save draft, quote, reserve, submit.
- **Information design / components:** 6-step guided form with draft save indicator, review and clear final terms.
- **Critical exceptional, stale and permission states:** Stale quote, failed upload, concurrent edit, invalid creative or missing Woo provide save and recovery, not data loss.
- **Responsive and accessibility requirements:** Responsive stepper + current heading; focus first invalid; no sensitive autosave.
- **API/domain boundary:** Campaign draft, creative version, quote and reservation endpoints. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Complete purchase draft; disconnect during upload; reload without losing nonsecret fields; no accidental duplicate order.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-10 — Checkout handoff (`UX11-S-10`)

- **Release:** R1. **Actors:** Advertiser/billing user.
- **First-view information hierarchy:** Server-authoritative quote and hold > exact amount/currency/tax > gateway status > next step.
- **Primary actions and interaction flow:** Pay, safely retry verification, re-quote after expiry, view campaign state.
- **Information design / components:** Order receipt/pending panel; independent payment/approval/delivery statuses.
- **Critical exceptional, stale and permission states:** Gateway timeout or redirect return does not mark paid; expired hold cannot revive; Woo absent disables commercial handoff honestly.
- **Responsive and accessibility requirements:** Accessible pending state and exact amount reading; mobile checkout summary preserved.
- **API/domain boundary:** Checkout integration, order/payment status, fresh quote and hold. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Double return/callback never duplicates order or presents unsupported paid state.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-11 — Campaign list/detail (`UX11-S-11`)

- **Release:** R1. **Actors:** AdOps / advertiser in own organization.
- **First-view information hierarchy:** Three independent statuses > version / schedule > eligibility reason > history.
- **Primary actions and interaction flow:** Open campaign, request revision, authorized pause/resume, inspect creative/contract.
- **Information design / components:** Badges include text (not color only), timeline, scoped actions, list/table and status tabs.
- **Critical exceptional, stale and permission states:** Payment paid yet creative pending shows not running; state changed elsewhere produces conflict.
- **Responsive and accessibility requirements:** Mobile timeline/one-action menu, clear disabled reasons.
- **API/domain boundary:** Campaign read and state-command APIs with authorization. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** User can tell whether a campaign is approved, paid, serving, or blocked as distinct facts.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-12 — Creative moderation (`UX11-S-12`)

- **Release:** R1. **Actors:** Moderators and authorized AdOps.
- **First-view information hierarchy:** Exact version/digest > safe creative preview > destination URL/policy > submitted diff.
- **Primary actions and interaction flow:** Approve, reject, request changes, emergency stop (if authorized).
- **Information design / components:** Two-column review with version diff and policy checklist, protected preview.
- **Critical exceptional, stale and permission states:** Changed destination forces re-review; errors/unsafe creative do not render executable script.
- **Responsive and accessibility requirements:** Accessible image alt/spec summaries, keyboard diff navigation, full reason form.
- **API/domain boundary:** Creative review commands, current version and permission guards. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Review a modified redirect: previously approved status does not automatically authorize new creative.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-13 — Booking calendar (`UX11-S-13`)

- **Release:** R1. **Actors:** AdOps / advertiser (scoped).
- **First-view information hierarchy:** Timezone/date range > capacity/exclusivity > holds/bookings > alternative periods.
- **Primary actions and interaction flow:** Select dates, inspect conflict, request new authoritative quote, reschedule if authorized.
- **Information design / components:** Calendar plus accessible chronological list, clear status legend, filters.
- **Critical exceptional, stale and permission states:** DST nonexistent times explained; duplicate exclusive booking rejected after optimistic availability.
- **Responsive and accessibility requirements:** Keyboard date picker and full list parity; mobile list/agenda view.
- **API/domain boundary:** Availability/hold read, booking command, reservation endpoints. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Two simultaneous buyers: one wins lock, other receives clear conflict with alternatives.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-14 — Rates/orders and scoped billing (`UX11-S-14`)

- **Release:** R1. **Actors:** Finance admin / publisher / advertiser billing role.
- **First-view information hierarchy:** Rate version and contract snapshot > order/payment > reconciled ledger > remedy.
- **Primary actions and interaction flow:** View receipt, reconcile, review refund, download protected invoice.
- **Information design / components:** Audit-friendly table; exact money and currency; timeline linked to provider evidence.
- **Critical exceptional, stale and permission states:** Pending/unknown settlement, duplicate callbacks and refund disputes correctly distinguished.
- **Responsive and accessibility requirements:** Right-aligned numeric text with locale-safe codes, keyboard financial confirmation.
- **API/domain boundary:** Pricing/commerce/ledger queries; order/remedy mutations. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Refunded order has immutable original transaction and visible adjustment, never edited settled row.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-15 — Delivery diagnostics (`UX11-S-15`)

- **Release:** R1. **Actors:** AdOps / operations / technical support.
- **First-view information hierarchy:** Current eligibility > failed checks > queue age > cache status > last change.
- **Primary actions and interaction flow:** Inspect why ad not serving, pause, retry safe job, open affected campaign.
- **Information design / components:** Diagnostic checklist with pass/fail/unknown, copyable correlation, trace timeline.
- **Critical exceptional, stale and permission states:** Cache stale, queue failed or consent unavailable shows known cause and safe remediation; no misleading all-green status.
- **Responsive and accessibility requirements:** Text labels for all status icons; mobile scrollable results.
- **API/domain boundary:** Delivery diagnostics, job status, policy decisions. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Paused campaign excluded despite cached creative; stale state is detected and explained.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-16 — Advertiser reporting (`UX11-S-16`)

- **Release:** R1; advanced export R2. **Actors:** Advertiser owner/manager/analyst scoped.
- **First-view information hierarchy:** Verified aggregates > selected period/denominator > campaign comparison > freshness.
- **Primary actions and interaction flow:** Filter, compare, inspect trends, request permitted report.
- **Information design / components:** Metrics with units, data table alternative and chart, no raw sessions.
- **Critical exceptional, stale and permission states:** Sparse data, not measured, late aggregation, revocation all distinct; private audience details never exposed.
- **Responsive and accessibility requirements:** Accessible table for every chart, responsive summary, numeric precision.
- **API/domain boundary:** Authorized report queries; export availability per release. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Advertiser A cannot infer advertiser B or visitor-level activity from report/filter results.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-17 — Heatmap explorer (`UX11-S-17`)

- **Release:** R2; optional replay R4. **Actors:** Authorized analytics manager / publisher.
- **First-view information hierarchy:** Page/layout snapshot > selected device/version > metric legend/sample > overlay+numeric table.
- **Primary actions and interaction flow:** Filter, pan/zoom, select slot, compare layouts, export if permitted.
- **Information design / components:** Synchronized canvas and table with version-aware geometry and legend.
- **Critical exceptional, stale and permission states:** Geometry unavailable/changed, sampling bias, low consent or poor data quality show limits, no fabricated gaze.
- **Responsive and accessibility requirements:** Full table/keyboard alternative, contrast-safe palette; reduced motion.
- **API/domain boundary:** Heatmap aggregate and layout snapshots; separately gated replay. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Select footer slot 170 on long page and read data via table without using pointer.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-18 — Export jobs (`UX11-S-18`)

- **Release:** R2. **Actors:** Authorized advertiser analyst / finance / publisher.
- **First-view information hierarchy:** Requested report scope > job state > expiration > download rights.
- **Primary actions and interaction flow:** Request, monitor, cancel allowed job, download while authorized.
- **Information design / components:** Job-status list with progress stages, safe notifications and retry actions.
- **Critical exceptional, stale and permission states:** Revoked rights or expired protected link blocks delivery even if export generated previously.
- **Responsive and accessibility requirements:** Status updates announced without focus theft; mobile list.
- **API/domain boundary:** Protected export job creation, status and authorized download. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** User whose membership was revoked cannot download previously generated report.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-19 — Billing dispute (`UX11-S-19`)

- **Release:** R3. **Actors:** Finance and authorized advertiser representatives.
- **First-view information hierarchy:** Contract/rate snapshot > validated billable events > dispute evidence > remedy timeline.
- **Primary actions and interaction flow:** Open dispute, attach permitted evidence, review, resolve with approvals.
- **Information design / components:** Detailed reconcile comparison and audit thread, structured resolution form.
- **Critical exceptional, stale and permission states:** Unqualified events and late corrections distinguished; no self-approval if dual control required.
- **Responsive and accessibility requirements:** Accessible calculations/provenance, localized currency, mobile stacked evidence.
- **API/domain boundary:** Metered billing/dispute/ledger services. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Disputed billable clicks reconcile to qualified events, with auditable adjustments and no double posting.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### UI-20 — Settings/system health (`UX11-S-20`)

- **Release:** R1; R2/R4 feature sections as enabled. **Actors:** Site admin / operations.
- **First-view information hierarchy:** Site status and blockers > privacy/roles > dependency checks > queue and upgrade state.
- **Primary actions and interaction flow:** Review setup, configure authorized policy, run safe diagnostics, export redacted bundle.
- **Information design / components:** Category-based settings with save status, impact summaries and audit pointers.
- **Critical exceptional, stale and permission states:** Woo absent, failed migration, policy ambiguity or no consent show truthful failures; never bypass protected operation.
- **Responsive and accessibility requirements:** Headings/anchors, searchable settings, mobile stacked form.
- **API/domain boundary:** System health, config, capability, migrations, consent policies. Request/response semantics, capabilities, release flags and route names remain governed by ADP-DOC-009 and ADP-DOC-005.
- **Screen-specific test oracle:** Plugin runs without WooCommerce, protected diagnostics cannot leak secrets, revoked staff sees denial.
- **Required artifact set:** approved wireframe; mobile/RTL/high-zoom variants; design tokens; accessible labels/tab sequence; empty/loading/validation/network/conflict/denied state screenshots; front-end component tests; Playwright workflow evidence; actual manual keyboard and assistive-technology review.

### 12.1 Shared state-coverage matrix

| Screen | Release | Initial/loading | Empty/filter-empty | Failure/stale | Denied | Mobile/keyboard | Sensitive state oracle |
|---|---|---|---|---|---|---|---|
| `UI-01` | R1 | Required | Required | Required | Required | Required | Domain status |
| `UI-02` | R1 | Required | Required | Required | Required | Required | Sold-slot state |
| `UI-03` | R1 | Required | Required | Required | Required | Required | Sold-slot state |
| `UI-04` | R1 | Required | Required | Required | Required | Required | Sold-slot state |
| `UI-05` | R1 with R2 diagnostics enhancement | Required | Required | Required | Required | Required | Preview nonbillable |
| `UI-06` | R1 | Required | Required | Required | Required | Required | Domain status |
| `UI-07` | R1 | Required | Required | Required | Required | Required | Domain status |
| `UI-08` | R1 | Required | Required | Required | Required | Required | Tenant state |
| `UI-09` | R1 | Required | Required | Required | Required | Required | Domain status |
| `UI-10` | R1 | Required | Required | Required | Required | Required | Finance state |
| `UI-11` | R1 | Required | Required | Required | Required | Required | Tenant state |
| `UI-12` | R1 | Required | Required | Required | Required | Required | Domain status |
| `UI-13` | R1 | Required | Required | Required | Required | Required | Sold-slot state |
| `UI-14` | R1 | Required | Required | Required | Required | Required | Finance state |
| `UI-15` | R1 | Required | Required | Required | Required | Required | Domain status |
| `UI-16` | R1; advanced export R2 | Required | Required | Required | Required | Required | Tenant state |
| `UI-17` | R2; optional replay R4 | Required | Required | Required | Required | Required | Data privacy |
| `UI-18` | R2 | Required | Required | Required | Required | Required | Tenant state |
| `UI-19` | R3 | Required | Required | Required | Required | Required | Finance state |
| `UI-20` | R1; R2/R4 feature sections as enabled | Required | Required | Required | Required | Required | Domain status |

## 13. UX content strategy, microcopy and error recovery

### 13.1 Content voice and plain-language policy

Write concise, informative copy. State actual action, outcome, owner, and required next step. Prefer "Your payment is being verified" to "Processing..."; prefer "You cannot publish this layout until 3 paid reservations are resolved" to "Cannot publish". Use respectful neutral language; do not blame advertisers for policy rejection. Marketing copy may be persuasive, but financial facts, privacy controls and consent must remain neutral and complete.

### 13.2 Canonical glossary for visible labels

| UI term | Meaning shown to users | Avoid |
|---|---|---|
| Zone | Logical area of a site where ad slots are organized | Equating it to a particular campaign |
| Container | Layout arrangement within zone | Misleading "ad" if none assigned |
| Slot | Sellable logical position | Equating slot count to ad impressions |
| Placement binding | Integration point in Gutenberg/theme/template | User-facing "selector injection" without explanation |
| Draft layout | Unpublished revision | "Live changes" for drafts |
| Quote | Provisional priced offer | Calling it a completed order |
| Hold | Temporarily reserved capacity with expiration | "Guaranteed until checkout finishes" |
| Order paid | Financially confirmed contract state | "Campaign running" |
| Creative approved | Exact version passed moderation | Implying any future edit is approved |
| Ad served | Delivery decision/response | "Ad viewed" |
| Viewable | Meets configured viewability criterion | "Seen by a human" |
| Attention estimate | Visibility/engagement proxy | Eye tracking, gaze measurement |
| Qualified click | Interaction satisfying policy checks | Every click event |
| Billable event | Reconciled contract-eligible event | Raw browser event |

### 13.3 Sensitive notice examples (copy prototypes)

| Scenario | Suggested primary text | Action(s) |
|---|---|---|
| Quote expired | "This quote expired. Price and inventory may have changed." | Refresh price and availability |
| Hold expired | "The temporary reservation ended. The previous checkout cannot use that inventory." | Start new reservation |
| Paid but under review | "Payment received. Your creative is awaiting approval; delivery has not started." | View review details |
| Payment status unknown | "We have not verified the payment result. Do not submit a second payment until status is checked." | Check payment status / contact support |
| Sold slot conflict | "Changing this slot would affect an existing paid reservation." | Review impact / keep current |
| Unapproved creative revision | "This version has not been approved. The previous approved version may remain eligible under your contract." | Submit for review |
| Tracking not permitted | "Some engagement metrics are unavailable under the current consent settings." | View permitted aggregate reporting |
| No authorized organization | "You do not currently have access to this advertiser account." | Switch organization / request access |
| Woo missing | "Online checkout is not configured. Contact the publisher, or an administrator can enable an approved payment integration." | View contact or setup instructions |
| Queue delayed | "Reporting is delayed. Campaign status and financial totals may be updated at different times." | View status / retry only if authorized |
| Incompatible historical geometry | "These layouts cannot be overlaid accurately. Compare stable slots in the table instead." | Open slot comparison table |

### 13.4 Notification delivery and preference guidelines

Send only necessary event notifications to authorized actor scopes. Important changes (payment, approval, campaign suspension) should remain visible in product history regardless of email delivery. Respect role/organization, policy and data classifications when including links. Notifications contain minimal safe context, destination, status and next action. Do not email replay frames, visitor behavior trails, private payment tokens, or raw diagnostics.

### 13.5 Consistent error taxonomy

Map stable API problem `code` to localized content title, explanation, safe next action and logging correlation; never pattern-match raw server error prose in frontend. Suggested semantic classes (not replacements for upstream API error codes): validation, authentication, authorization, conflict/stale revision, inventory unavailable, quote/hold expired, payment unknown, provider unavailable, rate limit, creative unsafe, policy violation, analytics incomplete, background job delayed, migration blocked. Release/feature-disabled is not treated as network failure.

---

## 14. Interaction architecture and frontend implementation contracts

### 14.1 Feature/screen view-model boundary

React components receive scoped typed projections from an API client. Domain policy must remain server-side. Frontend view-model converts API states into presentation states without manufacturing authority. Components must not infer ownership from URL, read hidden organizations from browser storage, or assume that a capability in a cached route response remains valid after membership revocation.

```text
React/Gutenberg UI
  -> typed form / query model
  -> API client and request context (site + active org)
  -> REST permission/policy layer
  -> application command/query services
  -> authoritative domain state
  -> scoped DTO + result revision + freshness
  -> UX-state mapping + accessible feedback
```

This is a conceptual contract. Exact REST route names, error shapes and response schemas are fixed by ADP-DOC-009; do not replace with hand-invented endpoints.

### 14.2 Suggested TypeScript state unions (illustrative)

```ts
export type AsyncReadState<T> =
  | { kind: 'initial' }
  | { kind: 'loading'; previous?: T }
  | { kind: 'ready'; data: T; revision?: string; asOf?: string }
  | { kind: 'empty'; filtersApplied: boolean }
  | { kind: 'partial'; data: T; missing: string[]; asOf?: string }
  | { kind: 'stale'; previous: T; reason: string; asOf?: string }
  | { kind: 'forbidden' }
  | { kind: 'disabled'; release: 'R2' | 'R3' | 'R4' }
  | { kind: 'error'; code: string; retryable: boolean; correlationId?: string };

export type MutationState =
  | { kind: 'idle' }
  | { kind: 'validating' }
  | { kind: 'submitting'; idempotencyKey?: string }
  | { kind: 'committed'; serverRevision: string }
  | { kind: 'conflict'; expectedRevision: string; currentRevision: string }
  | { kind: 'unknown'; operationReference: string }
  | { kind: 'rejected'; code: string; fieldErrors?: Record<string, string> };
```

These are *UI discriminants*, not application-state replacements. Names/data types must be reconciled with generated API types. A `committed` UI mutation may be shown only after the responsible server confirms the authoritative business transition; for async operations show a separate `accepted/queued` state, not completed.

### 14.3 Task-boundary component plan

| Component / pattern | Responsibilities | Accessibility | Security/privacy |
|---|---|---|---|
| `AppShell` | Navigation, breadcrumbs, context | Main landmark, skip link, focus heading | No unscoped data |
| `EntityStatusGroup` | Show independent financial/approval/delivery state | Text and icon labels | Server authoritative projection |
| `SmartDataTable` | Filter/sort/page/selection | Header semantics, keyboard selection | Scoped queries/actions |
| `PermissionAction` | Explain unavailable operation | Convey reason without leaking resource | Backend still checks |
| `ImpactReview` | Show sold-layout consequences | Focused disclosure/dialog | Audited permission to execute |
| `CampaignWizard` | Persist safe draft, review steps | Step indicators/error summary | No secret persistence |
| `QuoteSummary` | Snapshot price and expiry | Exact money and timezone | Server-calculated amounts |
| `CreativeSafePreview` | Read-only approved/submitted creative | Alt fallback, no autoplay | Sandbox and sanitization |
| `BookingCalendar` | Range and occupancy exploration | Accessible agenda alternative | Transactional validation on hold |
| `LayoutTree` | Hierarchical zone navigator | APG semantics or native list | Scoped inventory |
| `SlotNavigator` | Search/manage 500+ slots | Accessible pagination/bulk selection | Guard protected slots |
| `ViewportPreview` | Device/layout wireframe | Structure alternative | Nonbillable rendering |
| `HeatmapExplorer` | Event overlay and comparisons | Numeric table/pan controls | R2 privacy and authorization |
| `DataProvenanceNotice` | Explain freshness/source/denominator | Screen-reader available | Prevent false claims |
| `ProtectedExportStatus` | Manage queued output | Accessible progress | Revalidate on download |
| `GlobalNotice` | Operation feedback | Appropriate live region | Redacted details |

### 14.4 Frontend interaction test strategy

- Unit tests: conversion from typed API result → explicit UI state; money/date formatting; permission-driven affordances; range and filter serialization; draft logic.
- Component tests: focus trap/restore; keyboard slot selection; card nested action behavior; optimistic-only local fields; error summaries; accessible tabs/comboboxes.
- Integration tests: concurrent-version conflict; cached membership revoked; server-authoritative checkout; upload revision changed; hold expiry and re-quote.
- Playwright browser: journeys UJ-01..28 as enabled per release; responsive viewports; RTL; zoom; navigation; critical notifications; success/failure paths.
- Manual accessibility: keyboard-only, high contrast, screen readers at minimum one desktop/browser combination per supported platform class, reduced-motion and touch inspection.
- Performance: test mounted nodes and fetch bursts in Studio with 500/1000 configured slots and rendering on long public pages; record actual baselines before accepting numeric targets.

---

## 15. UX work packages for GPT-6 High

Each work package must be bounded by screen/module ownership and explicit prerequisite. GPT-6 High implementation must *not* combine unrelated domain, financial and visual changes into an unverified giant commit. Every package includes targeted tests, screenshots or textual accessibly verifiable artifacts, and pass/fail evidence.

| Work package | Release | Input dependency | Expected deliverable | Mandatory sign-off |
|---|---|---|---|---|
| UX11-WP-01 — UX repository/contracts | R1 | Docs 01–10 | IA, definitions, dependency map, decision log | Product, UX, Architect |
| UX11-WP-02 — Navigation and shell | R1 | Access rules, typed API contracts | Publisher/portal shell, breadcrumbs, organization switcher | UX, Security, Frontend |
| UX11-WP-03 — Design-system foundations | R1 | UX11-WP-01 | Components, tokens and state specimens (details in Doc 12) | UX, UI, Accessibility |
| UX11-WP-04 — Admin dashboards | R1 | Reporting projections | UI-01, UI-15 core diagnostics | UX, QA, Operations |
| UX11-WP-05 — Inventory tables | R1 | Inventory API | UI-02 and UI-04 including bulk selection | UX, Inventory, Accessibility |
| UX11-WP-06 — Layout Studio | R1 | Layout contracts, stable slot IDs | UI-03 responsive workspace and impact review | UX, Inventory, QA |
| UX11-WP-07 — Binding inspector | R1 | Theme/block contracts | UI-05 snippets, safe previews, validation | WordPress, UX, Security |
| UX11-WP-08 — Marketplace | R1 | Public offer projections | UI-06 and UI-07 | Product, UX, QA |
| UX11-WP-09 — Advertiser access | R1 | Membership service | UI-08 and context switching | Security, UX, QA |
| UX11-WP-10 — Campaign wizard | R1 | Campaign/creative/quotes | UI-09 with draft/resume/review | Product, UX, AdTech |
| UX11-WP-11 — Checkout states | R1 | Booking/WooCommerce | UI-10 payment and recovery | Finance, Security, UX |
| UX11-WP-12 — Campaign operations | R1 | Policy and state machines | UI-11, UI-12, UI-13 | AdTech, UX, QA |
| UX11-WP-13 — Rates and billing | R1 | Commerce/ledger | UI-14 and allowed financial adjustments | Finance, UX, Security |
| UX11-WP-14 — Advertiser reporting | R1 | Scoped measurement | UI-16 + accessible tables | Measurement, UX, Privacy |
| UX11-WP-15 — Settings/onboarding | R1 | WordPress config/diagnostics | UI-20, safe setup and no-Woo mode | WordPress, UX, Operations |
| UX11-WP-16 — Heatmap experience | R2 | Analytics geometry/data/privacy | UI-17 visualization + table parity | Analytics, UX, Privacy |
| UX11-WP-17 — Protected exports | R2 | Async jobs/authorization | UI-18 scoped report jobs/download | Security, Reporting, UX |
| UX11-WP-18 — Advanced finance and experiments | R3 | Qualified billing/optimization | UI-19 + R3 analytics extensions | Finance, Data Science, UX |
| UX11-WP-19 — Replay/network UX | R4 | Consent/replay and federation | Gated UI-17/20 extensions and org/site contexts | Privacy, Security, UX |
| UX11-WP-20 — Cross-release audit | R1–R4 | All active features | Verified journey, a11y, RTL, latency, copy, role matrix | QA, UX, Accessibility |

### 15.1 Per-package execution protocol

1. List inherited `FR-*`, `BR-*`, `AT-*`, `UI-*`, `UJ-*`, `NFR-*` and active release gate.
2. Confirm current code, existing patterns, API names and dependencies; do not invent unavailable services.
3. Define user goals, responsive/RTL variants and all exceptional states.
4. Implement accessible screen components using shared design system; keep domain policy on server.
5. Implement state transitions and typed API error mapping; add tests for policy denial and stale changes.
6. Run lint/typecheck/component tests/Playwright/a11y tests that can actually run, record command outputs and unresolved defects.
7. Present actual screenshots or DOM/test evidence for both desktop and mobile, including form-error states; never label a mock as a tested implementation.
8. Update translation keys, documentation, and upstream change log if any contract changed.
9. Request required human technical and UX approvals for financial/sold inventory/privacy changes.
10. Mark shipped only when all applicable release gates pass.

### 15.2 UI Definition of Done

A screen is ready only when feature permissions and object-scope authorization are enforced; UI states cover initial, loading, empty, denied, invalid, stale, degraded and successful; content is accurate; keyboard/mobile/RTL interaction is verified; no secret or visitor-sensitive data leaks; frontend requests are bounded; browser history and focus are usable; design tokens are shared; translatable strings exist; actual test evidence is recorded; and the UX Lead signs off.

---
## 16. UX acceptance and adversarial verification catalog

The following **72 supplementary executable specifications** supplement—not replace—the source `AT-*`, `UXA-*`, `E2E-*`, `AR-T-*`, `API-T-*` and domain/business tests. A scenario is specified here, not recorded as passed. Test fixtures should deliberately include multi-org users, revoked access, 500/1000 slots, asynchronous failures, RTL, narrow screens and consent-dependent analytics.

### 16.1 Navigation and permissions

| ID | Release | Scenario | Given / When / Then acceptance oracle |
|---|---|---|---|
| `UX11-T-001` | R1 | **Publisher navigation** | **Given** User has AdOps rights for one site; **when** opens wp-admin navigation; **then** only permitted inventory/campaign sections are linked and keyboard focus reaches main heading. |
| `UX11-T-002` | R1 | **Deep link authorization** | **Given** User has lost rights to campaign A; **when** opens bookmarked campaign URL; **then** no private campaign fields render; denial offers safe permitted destination. |
| `UX11-T-003` | R1 | **Organization context switch** | **Given** User belongs to Org A and B; **when** switches active organization while report A is loading; **then** inflight A result cannot hydrate Org B screen or query cache. |
| `UX11-T-004` | R1 | **URL-restored filters** | **Given** User filters zone registry; **when** refreshes or shares permitted filtered URL; **then** filter state is restored without including private token values. |
| `UX11-T-005` | R1 | **Browser Back and focus** | **Given** User opens campaign from filtered list; **when** navigates Back; **then** filter/page position restored, and focus returned predictably. |
| `UX11-T-006` | R1 | **Navigation hiding is not auth** | **Given** User hides forbidden action in client; **when** calls denied REST endpoint directly; **then** server rejects request and no state or audit-sensitive data leaks. |
| `UX11-T-007` | R1 | **No-Woo commerce navigation** | **Given** Plugin starts without WooCommerce; **when** publisher opens storefront and settings; **then** storefront states online checkout unavailable while settings offers authorized setup guidance. |
| `UX11-T-008` | R4 | **Multisite active-context switch** | **Given** User has access to site A only; **when** switches to or crafts URL for site B; **then** safe authorization failure and no cross-site data cached. |

### 16.2 High-density Layout Studio

| ID | Release | Scenario | Given / When / Then acceptance oracle |
|---|---|---|---|
| `UX11-T-009` | R1 | **Five-hundred slot generation** | **Given** Admin has draft layout; **when** creates 4+100+196+200 slot groups; **then** exactly 500 logical stable slots reported without full creative render. |
| `UX11-T-010` | R1 | **Slot search at scale** | **Given** Layout has 1,000 slots; **when** searches stable slot identifier near end; **then** correct slot can be inspected without mounting 1,000 rich cards. |
| `UX11-T-011` | R1 | **Protected slot reorder** | **Given** Slot has overlapping paid exclusive reservation; **when** moves its commercial position; **then** publish blocked with impact, contract and governed remediation. |
| `UX11-T-012` | R1 | **Layout stale revision** | **Given** Two admins edit same draft; **when** second admin attempts guarded publish; **then** conflict presents compare/reload and never overwrites current revision. |
| `UX11-T-013` | R1 | **Bulk filtered selection** | **Given** Admin filters 500 slots to 120; **when** chooses select all filtered; **then** review explicitly states 120 scope, not current page size. |
| `UX11-T-014` | R1 | **Bulk partial error** | **Given** Bulk group includes protected slot; **when** admin applies allowed batch edit; **then** protected outcome explained per slot and no silent skipped mutation. |
| `UX11-T-015` | R1 | **Responsive preview** | **Given** Grid has 5 desktop, 3 tablet, 1 mobile columns; **when** switches breakpoint preview; **then** display reports same logical slot IDs with responsive structure. |
| `UX11-T-016` | R1 | **Keyboard layout editing** | **Given** Keyboard-only manager opens Layout Studio; **when** selects/reorders unsold slot via controls; **then** task works without drag and focus remains visible. |
| `UX11-T-017` | R1 | **Preview nonbillable** | **Given** Editor previews 200-slot footer; **when** inspects a sampled creative; **then** zero revenue-bearing events generated. |
| `UX11-T-018` | R1 | **Unknown theme binding** | **Given** Configured selector/hook disappears after theme update; **when** admin validates layout; **then** preview warns and publishing/injection obeys defined safe policy. |
| `UX11-T-019` | R1 | **Duplicate intentional binding** | **Given** Theme includes same zone via block and PHP; **when** runtime renders both intentionally; **then** unique render-instance identities; contract/measurement semantics visible. |
| `UX11-T-020` | R1 | **Density policy conflict** | **Given** Admin configures extreme ad density; **when** runs validation; **then** warning/block displays relevant documented rule, not invented legal claim. |

### 16.3 Marketplace and checkout

| ID | Release | Scenario | Given / When / Then acceptance oracle |
|---|---|---|---|
| `UX11-T-021` | R1 | **Anonymous browsing privacy** | **Given** Anonymous visitor loads catalog; **when** filters by price and placement; **then** only published offer metadata appears; no private advertiser or visitor details. |
| `UX11-T-022` | R1 | **Estimate disclosure** | **Given** Offer includes projected audience; **when** opens offer detail; **then** reach labeled estimated, period stated, cannot be confused with guarantee. |
| `UX11-T-023` | R1 | **Wizard draft recovery** | **Given** Campaign manager edits steps 1–3; **when** reloads before checkout; **then** nonsecret data resumes with accurate saved version. |
| `UX11-T-024` | R1 | **Upload partial failure** | **Given** Advertiser uploads invalid creative; **when** receives safe server rejection; **then** field/format error focuses recoverable control without losing unrelated form data. |
| `UX11-T-025` | R1 | **Quote vs hold** | **Given** Advertiser views available slot; **when** returns after another buyer reserves it; **then** new hold attempt rejected with honest updated availability. |
| `UX11-T-026` | R1 | **Expired quote** | **Given** Advertiser waits beyond authoritative expiry; **when** presses checkout; **then** old quote cannot purchase; new quote shows changed terms. |
| `UX11-T-027` | R1 | **Payment unknown** | **Given** Payment redirect returns before provider verification; **when** user lands on status; **then** pending state only; no paid claim; safe status polling. |
| `UX11-T-028` | R1 | **Duplicate payment callbacks** | **Given** Provider repeats payment notification; **when** user refreshes order page; **then** one financial effect and clear reconciled status. |
| `UX11-T-029` | R1 | **Paid unapproved creative** | **Given** Payment confirmed but moderator has not approved; **when** user views campaign status; **then** paid/under review/not serving shown distinctly. |
| `UX11-T-030` | R1 | **Rejection remedy** | **Given** Moderator rejects paid creative; **when** advertiser opens campaign; **then** revision/refund path shown according to approved contract policy. |
| `UX11-T-031` | R1 | **No WooCommerce** | **Given** Admin uses a documented complimentary/manual workflow; **when** creates campaign via authorized operation; **then** correct manual/complimentary status without fake paid checkout. |
| `UX11-T-032` | R1 | **RTL monetary display** | **Given** Locale is RTL with USD amount and timezone; **when** user reviews checkout summary; **then** currency, numbers, date and total are unambiguous. |

### 16.4 Moderation, inventory and finance

| ID | Release | Scenario | Given / When / Then acceptance oracle |
|---|---|---|---|
| `UX11-T-033` | R1 | **Moderator version safety** | **Given** Advertiser edits approved creative destination; **when** reviewer opens moderation; **then** new version requires independent approval; old approval not copied. |
| `UX11-T-034` | R1 | **Reviewer permission revoked** | **Given** Moderator has stale page open; **when** clicks Approve after revocation; **then** server denies; UI reports current rights and refresh action. |
| `UX11-T-035` | R1 | **Duplicate exclusive booking** | **Given** Two buyers reserve same interval; **when** both submit simultaneously; **then** at most one succeeds and loser sees alternative period. |
| `UX11-T-036` | R1 | **Calendar accessible alternative** | **Given** Keyboard user inspects bookings; **when** opens agenda/list view; **then** all status and date information available without color-only calendar. |
| `UX11-T-037` | R1 | **DST ambiguity** | **Given** User selects ambiguous local start during DST transition; **when** requests quote; **then** timezone ambiguity resolved or explicitly rejected before purchase. |
| `UX11-T-038` | R1 | **Refund unknown** | **Given** Provider acknowledgment is delayed; **when** finance user submits permitted remedy; **then** UI reports pending/unknown, does not claim settled refund. |
| `UX11-T-039` | R1 | **Ledger precision** | **Given** Order has tax and partial refund; **when** finance compares line items; **then** exact amounts/currency and original/adjustment evidence preserved. |
| `UX11-T-040` | R1 | **Emergency stop** | **Given** Authorized operator suspends campaign; **when** loads delivery diagnostics; **then** stopped reason/time shown and no active serving eligibility. |
| `UX11-T-041` | R1 | **Queue failure** | **Given** Outbox worker delay interrupts report; **when** ops opens dashboard; **then** diagnostic shows partial/delayed results with safe retry guidance. |
| `UX11-T-042` | R1 | **Missing financial source** | **Given** Ledger service temporarily unavailable; **when** dashboard loads KPI; **then** revenue metric unavailable, not zero. |

### 16.5 Measurement and heatmaps

| ID | Release | Scenario | Given / When / Then acceptance oracle |
|---|---|---|---|
| `UX11-T-043` | R1 | **Count differentiation** | **Given** Configured 500 ads but visitor scrolls to 35; **when** report computes observed metrics; **then** configured, rendered and billable numbers not conflated. |
| `UX11-T-044` | R1 | **Preview exclusion** | **Given** Admin opens block preview and synthetic load; **when** collector processes events; **then** no billable impressions or clicks. |
| `UX11-T-045` | R1 | **Advertiser reporting isolation** | **Given** Advertiser A queries unauthorized B campaign; **when** view report/filter; **then** no B data, totals or existence leaked. |
| `UX11-T-046` | R1 | **Reporting freshness** | **Given** Aggregator delayed by 20 minutes; **when** user loads chart; **then** timestamp and incompleteness visible; no fabricated current total. |
| `UX11-T-047` | R2 | **Full-page scroll map** | **Given** User scrolls long responsive page; **when** analyst opens same layout heatmap; **then** proper device/layout-version mapping and stated sample. |
| `UX11-T-048` | R2 | **Geometry drift** | **Given** Historical page layout changed; **when** analyst compares overlays; **then** incompatible geometry flagged; stable-slot table offered. |
| `UX11-T-049` | R2 | **Heatmap accessibility** | **Given** Keyboard-only analyst chooses footer slot 170; **when** opens numeric table and focuses slot; **then** same quantitative data obtainable without heatmap pixels. |
| `UX11-T-050` | R2 | **Attention wording** | **Given** Analyst toggles attention layer; **when** reads legend/help; **then** explicit proxy model and non-gaze limitation disclosed. |
| `UX11-T-051` | R2 | **Insufficient consent sample** | **Given** Analytics collection lacks eligible events; **when** heatmap opens; **then** no fake red hotspots; limited-data explanation and safe empty state. |
| `UX11-T-052` | R2 | **Export authorization revoked** | **Given** Export completed while analyst still allowed; **when** role revoked before download; **then** protected download rechecks authority and denies. |
| `UX11-T-053` | R3 | **Noncausal report labeling** | **Given** Optimization compares creative performance; **when** renders uplift summary; **then** associations distinguished from validated experiment inference. |
| `UX11-T-054` | R4 | **Replay privacy gating** | **Given** Session replay feature disabled/consent not given; **when** user opens replay extension; **then** no recording or viewer data exists; appropriate disabled state. |

### 16.6 Accessibility and localization

| ID | Release | Scenario | Given / When / Then acceptance oracle |
|---|---|---|---|
| `UX11-T-055` | R1 | **Focus not obscured** | **Given** Sticky admin header and bottom action bar are visible; **when** keyboard tabs through controls; **then** focused element remains visible at 200% and tested zoom. |
| `UX11-T-056` | R1 | **Non-drag controls** | **Given** User cannot drag items with pointer; **when** moves zone via dedicated Move buttons; **then** same order change achievable by single pointer without dragging. |
| `UX11-T-057` | R1 | **Target sizes** | **Given** Mobile toolbar has dense icons; **when** a11y audit checks pointer targets; **then** WCAG 2.2 AA target size/spacing satisfied. |
| `UX11-T-058` | R1 | **Error focus** | **Given** Form has three invalid fields; **when** user submits; **then** summary announces errors and focus moves to actionable correction. |
| `UX11-T-059` | R1 | **Modal focus lifecycle** | **Given** User opens confirmation; **when** closes using Escape where safe; **then** focus returns to launch control and background was inert. |
| `UX11-T-060` | R1 | **RTL flow** | **Given** WordPress admin locale Arabic/Persian; **when** user edits zone and buys placement; **then** panels reflow logically; IDs, code, money and timestamps retain readable order. |
| `UX11-T-061` | R1 | **Screen reader status** | **Given** Async quote changes; **when** screen reader user listens; **then** status and changed amount announced appropriately without focus hijack. |
| `UX11-T-062` | R1 | **Large text reflow** | **Given** User zooms and increases text; **when** uses 320 CSS-pixel viewport equivalent; **then** critical forms/workflows remain operable without clipped actions. |
| `UX11-T-063` | R1 | **Reduced motion** | **Given** Browser requests reduced motion; **when** opens campaign showcases and success state; **then** nonessential transitions removed and actions remain clear. |
| `UX11-T-064` | R2 | **Chart/heatmap equivalent** | **Given** Analyst cannot distinguish heatmap gradient; **when** uses table/data inspector; **then** equivalent quantitative and filtering functionality available. |

### 16.7 Privacy/security and release gating

| ID | Release | Scenario | Given / When / Then acceptance oracle |
|---|---|---|---|
| `UX11-T-065` | R1 | **Org switch stale request** | **Given** Report request A in flight; user switches to B; **when** A response resolves late; **then** A response discarded and never shown in B. |
| `UX11-T-066` | R1 | **Private deep link** | **Given** Anonymous user has private report URL; **when** navigates to URL; **then** denial without report metadata or identity. |
| `UX11-T-067` | R1 | **Data in errors** | **Given** Gateway returns diagnostic body with sensitive token; **when** UI maps error; **then** only safe category and correlation ID shown. |
| `UX11-T-068` | R1 | **Unsafe creative preview** | **Given** Uploaded creative includes script; **when** moderator previews asset; **then** script does not execute with site privileges. |
| `UX11-T-069` | R1 | **Privacy fields** | **Given** Consent SDK initializes on checkout/account forms; **when** user enters sensitive data; **then** no form values/secret fields enter general UX telemetry. |
| `UX11-T-070` | R1 | **Feature gate** | **Given** R4 replay code not installed; **when** R1 UI renders; **then** no replay control falsely claims available. |
| `UX11-T-071` | R1 | **Audit transparency** | **Given** High-impact layout override attempted; **when** authorized user reviews impacts; **then** required approver/justification and audit status shown. |
| `UX11-T-072` | R2 | **Visitor data visibility** | **Given** Advertiser opens reporting; **when** attempts UI route to heatmap raw sessions; **then** aggregates only; private visitor behavior unavailable. |

### 16.8 Test evidence and release decision rules

Each `UX11-T-*` requires a test ID in CI or manual test plan, supported release, fixture, actual execution environment, expected and observed result, screenshots/DOM/accessibility evidence where appropriate, defect link and reviewer disposition. Mark `not applicable (R2/R3/R4)` explicitly instead of reporting a test as passed in an R1 suite. A high-risk financial, tenant-authorization, privacy or sold-slot defect blocks release regardless of appearance polish.

## 17. Complete inherited journey/screen crosswalk

This table is taken from the canonical ADP-DOC-003 journey-to-screen index; it is repeated solely for implementation navigation. Refer to Document 03 for authoritative step-level preconditions/postconditions and all `UXA-*` tests.

| Canonical journey | Release | Actor(s) | UI surface(s) | Baseline anchors |
|---|---|---|---|---|
| UJ-01 — Anonymous advertiser discovers an offer | R1 | PERS-14, PERS-03 | UI-06 → UI-07 | FR-MKT-001..002, FR-MKT-008; BR-001, BR-013; AC-005 |
| UJ-02 — Advertiser registers and sets up organization | R1 | PERS-03, PERS-05 | UI-08 | FR-MKT-003..004, FR-MKT-009, FR-OPS-001..003; AC-009 |
| UJ-03 — Advertiser drafts campaign and validates creative | R1 | PERS-03, PERS-04 | UI-09 → UI-11 | FR-MKT-005..007, FR-CAM-003..004, FR-CAM-008, FR-UX-005; AC-008 |
| UJ-04 — Advertiser obtains quote and exclusive hold | R1 | PERS-03, PERS-04 | UI-09 → UI-13 → UI-10 | FR-COM-001..004, FR-COM-014; BR-001..002, BR-005, BR-014..015; AC-006 |
| UJ-05 — WooCommerce checkout, return, and recovery | R1 | PERS-03, PERS-07 | UI-10 → UI-11 / UI-14 | FR-COM-005..010, FR-CAM-001, FR-MKT-007; BR-003..004, BR-014; AC-005, AC-007 |
| UJ-06 — Organization owner invites and restricts teammates | R1 | PERS-05, PERS-04, PERS-06, PERS-07 | UI-08 | FR-MKT-004, FR-OPS-001, FR-OPS-003; AC-009 |
| UJ-07 — Moderator reviews new creative or changed version | R1 | PERS-08, PERS-04 | UI-12 → UI-11 | FR-CAM-001..010; BR-003..004, BR-007..008; AC-008 |
| UJ-08 — AdOps checks booking calendar and resolves overlap | R1 | PERS-02, PERS-09 | UI-13 → UI-14 | FR-COM-002..003, FR-COM-014, FR-INV-011; BR-001, BR-005, BR-015; AC-006 |
| UJ-09 — Publisher builds 500-slot responsive page | R1 | PERS-01, PERS-02 | UI-02 → UI-03 → UI-04 | FR-INV-001..011, FR-INV-013..014, FR-DEL-004..007; AC-001, AC-002, AC-004 |
| UJ-10 — Publisher edits sold layout without breaking contracts | R1 | PERS-02, PERS-01 | UI-03 → UI-04 → UI-13 | FR-INV-005, FR-INV-008..009, FR-INV-014; BR-006, BR-018; AC-001 |
| UJ-11 — Content editor inserts AdPilot Gutenberg blocks | R1 | PERS-11 | WordPress Gutenberg editor → UI-05 | FR-INT-001..004, FR-INT-007..010; AC-002, AC-003 |
| UJ-12 — Theme integrator embeds ad slots via public interfaces | R1 | PERS-12, PERS-13 | UI-05, developer docs, theme templates | FR-INT-005..012, FR-DEL-007..009; AC-003, AC-010 |
| UJ-13 — Visitor scrolls high-density page with consent controls | R1 | PERS-15 | Public page + ad runtime | FR-DEL-001..011, FR-MET-001..005, FR-OPS-005; BR-009..010, BR-016; AC-004, AC-011..012 |
| UJ-14 — AdOps activates, pauses and expires a campaign | R1 | PERS-02, PERS-08 | UI-11 → UI-15 | FR-CAM-001..002, FR-DEL-001..003, FR-DEL-009..010, FR-OPS-006; BR-007..008; AC-007..008 |
| UJ-15 — Advertiser or publisher reviews verified metrics | R1 | PERS-06, PERS-10 | UI-16 → UI-01 | FR-MET-001..006, FR-REP-001..004; BR-011..013; AC-009 |
| UJ-16 — Finance manager investigates refund or mismatch | R1 | PERS-09, PERS-07 | UI-14 → UI-11 | FR-COM-006..010; BR-003..005, BR-012, BR-014; AC-007 |
| UJ-17 — Administrator recovers from delivery/queue incident | R1 | PERS-13, PERS-16 | UI-15 → UI-20 | FR-DEL-009, FR-OPS-006..007, FR-OPS-010..012; NFR-005, NFR-012; AC-011 |
| UJ-18 — Site administrator configures privacy and consent exclusions | R1/R2 | PERS-13, PERS-15 | UI-20; consent controls | FR-OPS-004..005, FR-HEAT-008, BR-011..012, BR-017; AC-012 |
| UJ-19 — Publisher explores full-page heatmaps | R2 | PERS-10, PERS-02 | UI-17 | FR-HEAT-001..009, FR-MET-007..008, FR-REP-005; BR-011..013; R2 acceptance additions |
| UJ-20 — Analytics manager compares layouts across versions | R2 | PERS-10, PERS-01 | UI-17 | FR-HEAT-004..009, FR-REP-005, BR-006, BR-013; R2 acceptance additions |
| UJ-21 — Advertiser recovers abandoned or failed checkout | R1 | PERS-03, PERS-07 | UI-09 → UI-10 → UI-11 | FR-MKT-006, FR-COM-002..008; BR-002, BR-005; AC-007 |
| UJ-22 — AdOps bulk edits generated inventory and filters conflicts | R1 | PERS-02 | UI-04 → UI-03 | FR-INV-003..011, BR-006, BR-018; AC-001 |
| UJ-23 — Authorized user requests aggregate report export | R2 | PERS-06, PERS-10, PERS-09 | UI-16 → UI-18 | FR-REP-003..005, FR-OPS-001, BR-011..012, NFR-008; R2 acceptance additions |
| UJ-24 — Reconciled CPM/CPC billing and dispute | R3 | PERS-09, PERS-04, PERS-07 | UI-19 → UI-14 → UI-16 | FR-COM-011, FR-MET-009..010, FR-REP-006; BR-012..014; R3 acceptance additions |
| UJ-25 — Publisher runs placement experiment or optimization | R3 | PERS-10, PERS-01 | UI-01 → reporting/experiment extension | FR-REP-006..007, FR-INV-009, BR-006, BR-013; R3 scope |
| UJ-26 — Authorized administrator activates optional session replay | R4 | PERS-13, PERS-10 | UI-20 → UI-17 replay extension | FR-HEAT-010, FR-OPS-005, FR-OPS-011, BR-017; R4 scope |
| UJ-27 — Administrator installs, upgrades and diagnoses dependencies | R1/R4 | PERS-13, PERS-16 | UI-20 | FR-OPS-006..013, FR-COM-013, NFR-010, NFR-015; AC-014..015 |
| UJ-28 — Publisher runs complimentary/manual campaign without WooCommerce | R1 | PERS-01, PERS-02 | UI-11 → UI-14 → UI-15 | FR-COM-013, FR-OPS-012, FR-CAM-006, BR-003, BR-005; AC-014 |

### 17.1 Inherited atomic workflows

`WF-01..WF-18` retain the exact ordering and preconditions specified in ADP-DOC-003 §8. This UX specification imposes global state/recovery requirements for every one, without defining a replacement state machine: `WF-01`, `WF-02`, `WF-03`, `WF-04`, `WF-05`, `WF-06`, `WF-07`, `WF-08`, `WF-09`, `WF-10`, `WF-11`, `WF-12`, `WF-13`, `WF-14`, `WF-15`, `WF-16`, `WF-17`, `WF-18`.

## 18. Complete `FR-*` → journey, screen and acceptance crosswalk

**Normative meaning is inherited from the PRD.** These source rows from ADP-DOC-003 cover all 129 functional requirements. A technical `FR-*` linked to a UX surface still requires the full backend/security/financial acceptance from its owner; this table is not permission to implement the screen without its service. Multi-release values are retained exactly.

| FR ID | Release | Linked journey(s) | UI surface(s) | Accountable workstream | Existing acceptance |
|---|---|---|---|---|---|
| `FR-INV-001` | R1 | UJ-09, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-001` |
| `FR-INV-002` | R1 | UJ-09, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-002` |
| `FR-INV-003` | R1 | UJ-17, UJ-27, UJ-09, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-003` |
| `FR-INV-004` | R1 | UJ-09, UJ-11, UJ-12, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-004` |
| `FR-INV-005` | R1 | UJ-09, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-005` |
| `FR-INV-006` | R1 | UJ-09, UJ-11, UJ-12, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-006` |
| `FR-INV-007` | R1 | UJ-04, UJ-08, UJ-21, UJ-09, UJ-11 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-007` |
| `FR-INV-008` | R1 | UJ-09, UJ-11, UJ-12, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-008` |
| `FR-INV-009` | R1 | UJ-09, UJ-11, UJ-12, UJ-03, UJ-07 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-009` |
| `FR-INV-010` | R1 | UJ-09, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-010` |
| `FR-INV-011` | R1 | UJ-09, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-011` |
| `FR-INV-012` | R2 | UJ-19, UJ-09, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-012` |
| `FR-INV-013` | R1 | UJ-04, UJ-08, UJ-21, UJ-03, UJ-07 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-013` |
| `FR-INV-014` | R1 | UJ-09, UJ-10, UJ-22 | UI-02/03/04 | WP Inventory + UX/Layout | `AT-INV-014` |
| `FR-INT-001` | R1 | UJ-09, UJ-11, UJ-12, UJ-10, UJ-22 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-001` |
| `FR-INT-002` | R1 | UJ-09, UJ-11, UJ-12 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-002` |
| `FR-INT-003` | R1 | UJ-09, UJ-11, UJ-12, UJ-10, UJ-22 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-003` |
| `FR-INT-004` | R1 | UJ-09, UJ-10, UJ-22, UJ-11, UJ-12 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-004` |
| `FR-INT-005` | R1 | UJ-09, UJ-11, UJ-12, UJ-10, UJ-22 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-005` |
| `FR-INT-006` | R1 | UJ-09, UJ-11, UJ-12, UJ-10, UJ-22 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-006` |
| `FR-INT-007` | R1 | UJ-09, UJ-11, UJ-12 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-007` |
| `FR-INT-008` | R1 | UJ-09, UJ-11, UJ-12 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-008` |
| `FR-INT-009` | R1 | UJ-09, UJ-11, UJ-12 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-009` |
| `FR-INT-010` | R1 | UJ-09, UJ-11, UJ-12, UJ-03, UJ-07 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-010` |
| `FR-INT-011` | R2 | UJ-09, UJ-11, UJ-12, UJ-17, UJ-27 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-011` |
| `FR-INT-012` | R1 | UJ-09, UJ-11, UJ-12 | UI-05/Gutenberg | Gutenberg + Theme/Frontend | `AT-INT-012` |
| `FR-MKT-001` | R1 | UJ-01, UJ-02, UJ-03, UJ-06 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-001` |
| `FR-MKT-002` | R1 | UJ-09, UJ-11, UJ-12, UJ-01, UJ-02 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-002` |
| `FR-MKT-003` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-01 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-003` |
| `FR-MKT-004` | R1 | UJ-02, UJ-06, UJ-01, UJ-03 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-004` |
| `FR-MKT-005` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-04 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-005` |
| `FR-MKT-006` | R1 | UJ-01, UJ-02, UJ-03, UJ-06 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-006` |
| `FR-MKT-007` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-03 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-007` |
| `FR-MKT-008` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-18 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-008` |
| `FR-MKT-009` | R1 | UJ-02, UJ-06, UJ-24, UJ-01, UJ-03 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-009` |
| `FR-MKT-010` | R2 | UJ-02, UJ-06, UJ-15, UJ-16, UJ-23 | UI-06/07/08/09 | Product UX + Portal | `AT-MKT-010` |
| `FR-CAM-001` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-03 | UI-11/12 | Campaign/Moderation | `AT-CAM-001` |
| `FR-CAM-002` | R1 | UJ-03, UJ-07, UJ-14 | UI-11/12 | Campaign/Moderation | `AT-CAM-002` |
| `FR-CAM-003` | R1 | UJ-03, UJ-07, UJ-14 | UI-11/12 | Campaign/Moderation | `AT-CAM-003` |
| `FR-CAM-004` | R1 | UJ-03, UJ-07, UJ-14 | UI-11/12 | Campaign/Moderation | `AT-CAM-004` |
| `FR-CAM-005` | R1 | UJ-03, UJ-07, UJ-14 | UI-11/12 | Campaign/Moderation | `AT-CAM-005` |
| `FR-CAM-006` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-03 | UI-11/12 | Campaign/Moderation | `AT-CAM-006` |
| `FR-CAM-007` | R1 | UJ-03, UJ-07, UJ-14 | UI-11/12 | Campaign/Moderation | `AT-CAM-007` |
| `FR-CAM-008` | R1 | UJ-03, UJ-07, UJ-14 | UI-11/12 | Campaign/Moderation | `AT-CAM-008` |
| `FR-CAM-009` | R2 | UJ-03, UJ-07, UJ-14 | UI-11/12 | Campaign/Moderation | `AT-CAM-009` |
| `FR-CAM-010` | R1 | UJ-03, UJ-07, UJ-14 | UI-11/12 | Campaign/Moderation | `AT-CAM-010` |
| `FR-COM-001` | R1 | UJ-04, UJ-08, UJ-21, UJ-05, UJ-16 | UI-10/13/14 | Commerce/Finance | `AT-COM-001` |
| `FR-COM-002` | R1 | UJ-04, UJ-08, UJ-21, UJ-05, UJ-16 | UI-10/13/14 | Commerce/Finance | `AT-COM-002` |
| `FR-COM-003` | R1 | UJ-04, UJ-08, UJ-21, UJ-05, UJ-16 | UI-10/13/14 | Commerce/Finance | `AT-COM-003` |
| `FR-COM-004` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-04 | UI-10/13/14 | Commerce/Finance | `AT-COM-004` |
| `FR-COM-005` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-04 | UI-10/13/14 | Commerce/Finance | `AT-COM-005` |
| `FR-COM-006` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-04 | UI-10/13/14 | Commerce/Finance | `AT-COM-006` |
| `FR-COM-007` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-04 | UI-10/13/14 | Commerce/Finance | `AT-COM-007` |
| `FR-COM-008` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-04 | UI-10/13/14 | Commerce/Finance | `AT-COM-008` |
| `FR-COM-009` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-15 | UI-10/13/14 | Commerce/Finance | `AT-COM-009` |
| `FR-COM-010` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-04 | UI-10/13/14 | Commerce/Finance | `AT-COM-010` |
| `FR-COM-011` | R3 | UJ-24, UJ-25, UJ-04, UJ-05, UJ-08 | UI-10/13/14 | Commerce/Finance | `AT-COM-011` |
| `FR-COM-012` | R4 | UJ-28, UJ-26, UJ-27, UJ-04, UJ-05 | UI-10/13/14 | Commerce/Finance | `AT-COM-012` |
| `FR-COM-013` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-04 | UI-10/13/14 | Commerce/Finance | `AT-COM-013` |
| `FR-COM-014` | R1 | UJ-04, UJ-08, UJ-21, UJ-09, UJ-10 | UI-10/13/14 | Commerce/Finance | `AT-COM-014` |
| `FR-DEL-001` | R1 | UJ-04, UJ-08, UJ-21, UJ-03, UJ-07 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-001` |
| `FR-DEL-002` | R1 | UJ-12, UJ-13, UJ-14 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-002` |
| `FR-DEL-003` | R1 | UJ-18, UJ-19, UJ-26, UJ-12, UJ-13 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-003` |
| `FR-DEL-004` | R1 | UJ-09, UJ-10, UJ-22, UJ-12, UJ-13 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-004` |
| `FR-DEL-005` | R1 | UJ-19, UJ-20, UJ-03, UJ-07, UJ-14 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-005` |
| `FR-DEL-006` | R1 | UJ-12, UJ-13, UJ-14 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-006` |
| `FR-DEL-007` | R1 | UJ-09, UJ-10, UJ-22, UJ-12, UJ-13 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-007` |
| `FR-DEL-008` | R1 | UJ-18, UJ-19, UJ-26, UJ-12, UJ-13 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-008` |
| `FR-DEL-009` | R1 | UJ-12, UJ-13, UJ-14 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-009` |
| `FR-DEL-010` | R1 | UJ-09, UJ-11, UJ-12, UJ-13, UJ-14 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-010` |
| `FR-DEL-011` | R1 | UJ-03, UJ-07, UJ-14, UJ-09, UJ-10 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-011` |
| `FR-DEL-012` | R2/R3 | UJ-19, UJ-20, UJ-24, UJ-12, UJ-13 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-012` |
| `FR-DEL-013` | R1 | UJ-15, UJ-16, UJ-23, UJ-12, UJ-13 | public ad runtime/UI-15 | Ad Delivery/Frontend | `AT-DEL-013` |
| `FR-MET-001` | R1 | UJ-13, UJ-15 | UI-15/16 | Measurement/Analytics | `AT-MET-001` |
| `FR-MET-002` | R1 | UJ-15, UJ-16, UJ-23, UJ-13 | UI-15/16 | Measurement/Analytics | `AT-MET-002` |
| `FR-MET-003` | R1 | UJ-13, UJ-15 | UI-15/16 | Measurement/Analytics | `AT-MET-003` |
| `FR-MET-004` | R1 | UJ-13, UJ-15 | UI-15/16 | Measurement/Analytics | `AT-MET-004` |
| `FR-MET-005` | R1 | UJ-09, UJ-11, UJ-12, UJ-13, UJ-15 | UI-15/16 | Measurement/Analytics | `AT-MET-005` |
| `FR-MET-006` | R1 | UJ-03, UJ-07, UJ-14, UJ-09, UJ-10 | UI-15/16 | Measurement/Analytics | `AT-MET-006` |
| `FR-MET-007` | R2 | UJ-04, UJ-08, UJ-21, UJ-19, UJ-20 | UI-15/16 | Measurement/Analytics | `AT-MET-007` |
| `FR-MET-008` | R2 | UJ-15, UJ-16, UJ-23, UJ-19, UJ-20 | UI-15/16 | Measurement/Analytics | `AT-MET-008` |
| `FR-MET-009` | R3 | UJ-15, UJ-16, UJ-23, UJ-24, UJ-25 | UI-15/16 | Measurement/Analytics | `AT-MET-009` |
| `FR-MET-010` | R3 | UJ-24, UJ-25, UJ-13, UJ-15 | UI-15/16 | Measurement/Analytics | `AT-MET-010` |
| `FR-MET-011` | R3 | UJ-24, UJ-25, UJ-13, UJ-15 | UI-15/16 | Measurement/Analytics | `AT-MET-011` |
| `FR-HEAT-001` | R2 | UJ-19, UJ-20, UJ-09, UJ-10, UJ-22 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-001` |
| `FR-HEAT-002` | R2 | UJ-09, UJ-10, UJ-22, UJ-19, UJ-20 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-002` |
| `FR-HEAT-003` | R2 | UJ-09, UJ-10, UJ-22, UJ-19, UJ-20 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-003` |
| `FR-HEAT-004` | R2 | UJ-19, UJ-20, UJ-09, UJ-10, UJ-22 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-004` |
| `FR-HEAT-005` | R2 | UJ-19, UJ-20 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-005` |
| `FR-HEAT-006` | R2 | UJ-03, UJ-07, UJ-14, UJ-15, UJ-16 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-006` |
| `FR-HEAT-007` | R2 | UJ-19, UJ-20, UJ-09, UJ-10, UJ-22 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-007` |
| `FR-HEAT-008` | R2 | UJ-18, UJ-19, UJ-26, UJ-20 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-008` |
| `FR-HEAT-009` | R2 | UJ-19, UJ-20 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-009` |
| `FR-HEAT-010` | R4 | UJ-26, UJ-27, UJ-19, UJ-20 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-010` |
| `FR-HEAT-011` | R2 | UJ-19, UJ-20 | UI-17 | Behavioral Analytics/Privacy | `AT-HEAT-011` |
| `FR-REP-001` | R1 | UJ-15, UJ-16 | UI-01/16/17 | Reporting + UX | `AT-REP-001` |
| `FR-REP-002` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-03 | UI-01/16/17 | Reporting + UX | `AT-REP-002` |
| `FR-REP-003` | R1 | UJ-03, UJ-07, UJ-14, UJ-15, UJ-16 | UI-01/16/17 | Reporting + UX | `AT-REP-003` |
| `FR-REP-004` | R1 | UJ-04, UJ-08, UJ-21, UJ-15, UJ-16 | UI-01/16/17 | Reporting + UX | `AT-REP-004` |
| `FR-REP-005` | R2 | UJ-19, UJ-20, UJ-15, UJ-16, UJ-23 | UI-01/16/17 | Reporting + UX | `AT-REP-005` |
| `FR-REP-006` | R3 | UJ-15, UJ-16, UJ-23, UJ-25, UJ-24 | UI-01/16/17 | Reporting + UX | `AT-REP-006` |
| `FR-REP-007` | R3 | UJ-25, UJ-24, UJ-15, UJ-16 | UI-01/16/17 | Reporting + UX | `AT-REP-007` |
| `FR-REP-008` | R2 | UJ-15, UJ-16, UJ-23, UJ-17, UJ-27 | UI-01/16/17 | Reporting + UX | `AT-REP-008` |
| `FR-REP-009` | R2 | UJ-18, UJ-19, UJ-26, UJ-20, UJ-15 | UI-01/16/17 | Reporting + UX | `AT-REP-009` |
| `FR-REP-010` | R1 | UJ-15, UJ-16, UJ-23, UJ-18, UJ-19 | UI-01/16/17 | Reporting + UX | `AT-REP-010` |
| `FR-UX-001` | R1 | UJ-09, UJ-11, UJ-12, UJ-10, UJ-22 | UI-01..20 | UX/Design/Accessibility | `AT-UX-001` |
| `FR-UX-002` | R1 | UJ-03, UJ-09, UJ-11, UJ-19 | UI-01..20 | UX/Design/Accessibility | `AT-UX-002` |
| `FR-UX-003` | R1 | UJ-09, UJ-10, UJ-22, UJ-03, UJ-11 | UI-01..20 | UX/Design/Accessibility | `AT-UX-003` |
| `FR-UX-004` | R1 | UJ-09, UJ-10, UJ-22, UJ-03, UJ-11 | UI-01..20 | UX/Design/Accessibility | `AT-UX-004` |
| `FR-UX-005` | R1 | UJ-03, UJ-07, UJ-14, UJ-09, UJ-11 | UI-01..20 | UX/Design/Accessibility | `AT-UX-005` |
| `FR-UX-006` | R1 | UJ-03, UJ-09, UJ-11, UJ-19 | UI-01..20 | UX/Design/Accessibility | `AT-UX-006` |
| `FR-UX-007` | R1 | UJ-09, UJ-10, UJ-22, UJ-03, UJ-11 | UI-01..20 | UX/Design/Accessibility | `AT-UX-007` |
| `FR-UX-008` | R1 | UJ-09, UJ-11, UJ-12, UJ-03, UJ-19 | UI-01..20 | UX/Design/Accessibility | `AT-UX-008` |
| `FR-UX-009` | R1 | UJ-09, UJ-11, UJ-12, UJ-03, UJ-19 | UI-01..20 | UX/Design/Accessibility | `AT-UX-009` |
| `FR-UX-010` | R2/R4 | UJ-15, UJ-16, UJ-23, UJ-03, UJ-09 | UI-01..20 | UX/Design/Accessibility | `AT-UX-010` |
| `FR-UX-011` | R1 | UJ-17, UJ-27, UJ-03, UJ-09, UJ-11 | UI-01..20 | UX/Design/Accessibility | `AT-UX-011` |
| `FR-OPS-001` | R1 | UJ-18, UJ-19, UJ-26, UJ-06, UJ-17 | UI-20 | Platform/Security/DevOps | `AT-OPS-001` |
| `FR-OPS-002` | R1 | UJ-18, UJ-19, UJ-26, UJ-06, UJ-17 | UI-20 | Platform/Security/DevOps | `AT-OPS-002` |
| `FR-OPS-003` | R1 | UJ-02, UJ-06, UJ-05, UJ-16, UJ-21 | UI-20 | Platform/Security/DevOps | `AT-OPS-003` |
| `FR-OPS-004` | R1 | UJ-15, UJ-16, UJ-23, UJ-18, UJ-19 | UI-20 | Platform/Security/DevOps | `AT-OPS-004` |
| `FR-OPS-005` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-18 | UI-20 | Platform/Security/DevOps | `AT-OPS-005` |
| `FR-OPS-006` | R1 | UJ-15, UJ-16, UJ-23, UJ-17, UJ-27 | UI-20 | Platform/Security/DevOps | `AT-OPS-006` |
| `FR-OPS-007` | R1 | UJ-17, UJ-27, UJ-06, UJ-18 | UI-20 | Platform/Security/DevOps | `AT-OPS-007` |
| `FR-OPS-008` | R1 | UJ-06, UJ-17, UJ-18, UJ-27 | UI-20 | Platform/Security/DevOps | `AT-OPS-008` |
| `FR-OPS-009` | R1 | UJ-09, UJ-11, UJ-12, UJ-06, UJ-17 | UI-20 | Platform/Security/DevOps | `AT-OPS-009` |
| `FR-OPS-010` | R1 | UJ-15, UJ-16, UJ-23, UJ-17, UJ-27 | UI-20 | Platform/Security/DevOps | `AT-OPS-010` |
| `FR-OPS-011` | R4 | UJ-26, UJ-27, UJ-06, UJ-17, UJ-18 | UI-20 | Platform/Security/DevOps | `AT-OPS-011` |
| `FR-OPS-012` | R1 | UJ-05, UJ-16, UJ-21, UJ-28, UJ-06 | UI-20 | Platform/Security/DevOps | `AT-OPS-012` |
| `FR-OPS-013` | R1 | UJ-06, UJ-17, UJ-18, UJ-27 | UI-20 | Platform/Security/DevOps | `AT-OPS-013` |

## 19. Business rules, NFR and product acceptance UX alignment

### 19.1 Business-rule UI effects (all canonical `BR-001..018`)

| BR ID | Source rule (canonical summary/wording) | Domain enforcement owner | Required negative/recovery proof |
|---|---|---|---|
| `BR-001` | Inventory sale is permitted only for an active, publishable placement with sufficient eligible capacity. | Inventory availability/quotation | **`AT-BR-001`:** Try to sell paused or overbooked zone; quote and hold must fail. |
| `BR-002` | A temporary hold expires after its configured TTL; extension requires an explicit transaction. | Reservation hold state and TTL | **`AT-BR-002`:** Advance clock beyond TTL, then attempt extension without new transaction; deny. |
| `BR-003` | A successful online payment does not bypass creative moderation. | Financial and creative approval gates | **`AT-BR-003`:** Confirm payment before approval; no serving occurs. |
| `BR-004` | If a paid campaign is rejected, revision/refund/cancellation options follow recorded policy; never silently keep funds without an enforceable fulfillment path. | Moderation policy + remedy orchestration | **`AT-BR-004`:** Reject paid creative; expose revision/refund path and ledger state. |
| `BR-005` | An exclusive reservation cannot be double sold over overlapping contract time. | Reservation concurrency transaction | **`AT-BR-005`:** Race buyers for same exclusive slot/time; exactly one succeeds. |
| `BR-006` | Changes to a slot's commercial meaning require impact analysis and resolution of affected contracts. | Layout publication contract-impact service | **`AT-BR-006`:** Move or delete sold slot; block until explicitly resolved and audited. |
| `BR-007` | A rejected or unapproved creative cannot serve; approved prior versions may serve only if contract and policy permit. | Creative version approval store | **`AT-BR-007`:** Change destination after approval; edited version cannot serve unapproved. |
| `BR-008` | Delivery eligibility is recomputed against current authoritative campaign state, not based solely on a stale cached creative. | Delivery eligibility invalidation | **`AT-BR-008`:** Pause campaign after cache fill; stale selection never continues beyond documented freshness. |
| `BR-009` | Ad-preview, administrator-editing and synthetic load-test events are excluded from revenue-bearing analytics. | Production environment and collector filters | **`AT-BR-009`:** Render editor/staging fixture; billable metrics remain zero. |
| `BR-010` | Advertising density policies and ad-network restrictions are configurable; default warnings should discourage excessive clutter and inadvertent clicks. | Density enforcement and admin UX warning | **`AT-BR-010`:** Exceed configured visible density; enforce or warn consistently without invalid click layout. |
| `BR-011` | Advertiser reports show validated aggregate outcomes, not visitor-level session content. | Report authorization and aggregation layer | **`AT-BR-011`:** Try to fetch visitor playback via advertiser report; deny. |
| `BR-012` | Raw events have finite retention and are not a permanent financial source of truth; reconciled financial entries are independently retained under applicable policy. | Retention jobs + independent financial ledger | **`AT-BR-012`:** Purge raw events; reconciled financial record remains auditable. |
| `BR-013` | Conversion and performance claims distinguish observed facts, attribution models, estimates and projections. | Reporting schema metadata | **`AT-BR-013`:** Mix forecast and observed data; UI/API labels distinguish origins. |
| `BR-014` | Currency arithmetic uses precise decimal/minor-unit representations, never floating point for ledger authority. | Decimal/money value objects | **`AT-BR-014`:** Run high-quantity fractional currency computations; exact totals reconcile. |
| `BR-015` | Timestamps are stored in UTC; contract terms display user-selected time zone and daylight-saving behavior explicitly. | UTC time handling with local display | **`AT-BR-015`:** Book DST boundary; stored instants and shown terms remain consistent. |
| `BR-016` | Accessibility and mobile usability may constrain a particular layout even when there is ample logical inventory capacity. | Layout accessibility and UX policy | **`AT-BR-016`:** Configure impossible mobile grid; warn/block according to documented policy. |
| `BR-017` | Any third-party tracking, behavioral replay, or remote conversion adapter requires disclosure, consent gating where appropriate, and documented data flows. | Consent adapter + integration registration | **`AT-BR-017`:** Disable consent; optional remote tracking/replay makes no prohibited call. |
| `BR-018` | Manual administrator overrides of paid placement allocation require reason, authorization and audit logging. | Override command authorization and audit | **`AT-BR-018`:** Attempt unauthorized reallocation; reject; authorized override requires reason. |

### 19.2 NFR coverage (all 16 IDs)

| Canonical ID | Exact normative PRD topic and requirement | UX acceptance responsibility |
|---|---|---|
| `NFR-001` | **Architecture::** Modular-monolith design with explicit domain/application/infrastructure/presentation boundaries; PHP WordPress host; no mandatory external application server in R1. | Ensure consistent domain-to-presentation ownership |
| `NFR-002` | **Maintainability::** WordPress Coding Standards, PHP static analysis, TypeScript strict mode, clear module APIs, source-level documentation and reviewed dependencies. | Typed, reusable, maintainable screen contracts |
| `NFR-003` | **Rendering::** A 500-logical-slot page must not eagerly fetch or paint 500 heavy creatives. Browser work and network concurrency must be bounded; no per-slot PHP query pattern. | Bounded Layout Studio previews and lightweight public creative rendering |
| `NFR-004` | **Performance::** Establish and validate environment-specific p75/p95 budgets for request overhead, Core Web Vitals, query count, JS bundle size, browser memory, batching and queue lag; declare benchmark hardware, creative sizes and cache configuration. | Actual browser task and interaction benchmarks with documented environment |
| `NFR-005` | **Reliability::** Idempotent payment webhooks and jobs; resilient scheduler; safe fallback if ad delivery or analytics endpoint fails. | Truthful degraded/retry/unknown states |
| `NFR-006` | **Financial correctness::** Database-enforced uniqueness/capacity controls, concurrency testing, reconciliation, immutable adjustment trail and precise currency arithmetic. | Exact authoritative money/contract presentation; no false completion |
| `NFR-007` | **Security::** Threat model, dependency scans, permission tests, hardened creative handling, secure REST, server-side validation and emergency shutdown. | Server permission enforcement, safe preview/error surfaces |
| `NFR-008` | **Privacy::** Data minimization, regional consent policy, no sensitive input collection, finite behavioral-event retention and protected access. | No restricted data in telemetry/replay; consent-aware controls |
| `NFR-009` | **Accessibility::** WCAG 2.2 AA target for critical UIs, including nonvisual alternatives to heatmaps and drag/drop interactions. | Keyboard/drag alternative/table parity and expert review |
| `NFR-010` | **Compatibility::** Validated WordPress/PHP/MySQL/MariaDB matrix; support classic and block themes, modern browsers, WooCommerce HPOS when commerce adapter enabled. | Classic/block theme + Gutenberg/HPOS and browsers |
| `NFR-011` | **Internationalization::** Translation-ready strings, RTL/LTR support, reliable date/time-zone and currency displays. | Localized RTL/timezone/currency semantics |
| `NFR-012` | **Observability::** Logs, queue metrics, failed-event reporting, health diagnostics, financial discrepancy alerts and safe support exports. | Accessible queue/health diagnostics and redacted support export |
| `NFR-013` | **Extensibility::** Versioned REST API, documented hooks, stable PHP and shortcode contracts, adapter interfaces and deprecation policy. | Stable developer portal/help and valid theme snippets |
| `NFR-014` | **Licensing::** GPL-compatible distribution, recorded license texts/notices, transitive dependency screening, no unclear copyrighted assets. | Reuse and audit GPL-compatible UI dependencies, icons, fonts/assets |
| `NFR-015` | **Upgrade safety::** Idempotent migrations, version compatibility checks, reliable backup guidance, data-preserving deactivation and explicit uninstall. | Safe upgrade/failed migration states; no premature success |
| `NFR-016` | **Usability::** Critical flows verified through task-based usability tests; major actions provide comprehensible states, feedback and undo/recovery when feasible. | End-to-end task success/understanding and recovery evidence |

These are exact source requirement statements; their implications in the third column are supplementary UX verification obligations.

### 19.3 Release-1 product acceptance criteria (all 15 IDs)

| Canonical ID | Exact PRD acceptance criterion | Required UX evidence |
|---|---|---|
| `AC-001` | Publisher creates and publishes a 500-slot page consisting of 4 header, 100 sidebar, 196 in-content and 200 footer logical slots. Layout Studio remains usable; publishing and responsive preview succeed. | UI-02/03/04 500-slot fixture |
| `AC-002` | One `adpilot/ad-zone` block can reference a 200-slot footer container without storing 200 Gutenberg blocks. | Gutenberg `adpilot/ad-zone` preview/editor behavior |
| `AC-003` | Four supported integration paths (Gutenberg, PHP function, shortcode, and supported hook binding) render through one policy-aware engine with correct per-occurrence identifiers. | UI-05 and front-end paths equivalent |
| `AC-004` | Public page initially loads bounded creatives near viewport and does not miscount 500 configured slots as impressions. | Public runtime perception/measure semantics |
| `AC-005` | Advertiser can discover an available slot, obtain a quote, reserve, pay through configured WooCommerce, upload creative, request review and observe activation when approved. | UI-06/07/08/09/10/11/12 complete buying flow |
| `AC-006` | Two concurrent buyers cannot successfully reserve the same exclusive inventory over overlapping time windows. | UI-13 safe loss/alternative state |
| `AC-007` | Duplicate payment callbacks and campaign activation retries do not double-allocate, double-invoice or double-activate. | UI-10/14/15/20 retry and reconciliation state |
| `AC-008` | Creative changes requiring reapproval cannot bypass review and cannot serve in an unapproved state. | UI-12 exact-version review/approval |
| `AC-009` | Advertiser A cannot access advertiser B's campaigns, invoices, raw behavioral data or privileged reports through UI or direct API calls. | UI-08/11/14/16 tenant isolation |
| `AC-010` | Classic theme, block theme, common caching setup and supported WooCommerce HPOS mode pass the declared compatibility suite. | UI-05/09/10 theme/HPOS diagnostics |
| `AC-011` | Broken analytics endpoint does not crash the public site, block reading content or silently produce fictitious billable events. | UI-15/20 graceful failure plus public page stability |
| `AC-012` | Privacy-sensitive pages/inputs are excluded from collection; denied consent prevents prohibited collection. | UI-20 consent control and protected page exclusion |
| `AC-013` | All primary application workflows pass agreed manual accessibility and usability gates. | All active screens; manual accessibility/usability evidence |
| `AC-014` | Installer works without WooCommerce for nonpayment functions and provides an accurate diagnostic for unavailable online checkout. | UI-20 setup no Woo plus manual flows |
| `AC-015` | Release contains reproducible build, automated tests, license notices, migrations and administrator/developer documentation. | Installation guidance and true health/release states |

The criteria above retain their normative wording from ADP-DOC-001. Additional R2/R3 acceptance comes from the PRD and is not relabeled as R1.

### 19.4 Existing E2E suite

All 17 source cross-system tests remain mandatory for their active releases: `E2E-01`, `E2E-02`, `E2E-03`, `E2E-04`, `E2E-05`, `E2E-06`, `E2E-07`, `E2E-08`, `E2E-09`, `E2E-10`, `E2E-11`, `E2E-12`, `E2E-13`, `E2E-14`, `E2E-15`, `E2E-16`, `E2E-17`. Detailed test steps and correct gate are in ADP-DOC-002. UX11 tests provide additional browser-state and accessibility oracles; they are not replacements.

---

## 20. Open UX decisions and controlled assumptions

| Decision | Proposed default / uncertainty | Accountable reviewers | Blocking point |
|---|---|---|---|
| UX11-D-01 — Brand and color system | Neutral WordPress-compatible admin foundation; final tokens follow Document 12 | UX, UI, Accessibility | Before polished implementation |
| UX11-D-02 — Responsive breakpoint tokens | Use observed content/container needs; viewport matrix is testing only | UI, Frontend, QA | Before Layout Studio launch |
| UX11-D-03 — Campaign wizard form length | Six conceptual steps; research may collapse steps with equivalent auditability | Product, UX, Finance | Before implementation freeze |
| UX11-D-04 — Editor portal host theme | Scope styling and use theme-compatible routes; custom themes require validation | WordPress, Frontend | Before first portal release |
| UX11-D-05 — Heatmap source geometry | Choose sanitized snapshot and element-anchor implementation in analytics detail spec | Analytics, Privacy, UX | R2 |
| UX11-D-06 — Heatmap sampling/low-count privacy | Thresholds and retention require Privacy/Measurement approval | Privacy, Analytics | R2 |
| UX11-D-07 — Ads with rich media | Only approved safe creative types; no arbitrary advertiser script | Security, AdTech, UX | Before rich creative previews |
| UX11-D-08 — WooCommerce unavailable | No paid checkout unless configured, manual complimentary publisher path remains | Finance, Product | R1 |
| UX11-D-09 — Advertiser report export | Preserve source R2 entitlement; R1 reports still visible | Reporting, Security | R2 |
| UX11-D-10 — Webhook subscription release conflict | Document 10 reports R1 routes vs R4 storage scope; do not expose unsupported UI | Architect, Product, Security | Prior to webhook settings implementation |
| UX11-D-11 — License approval | Validate icons/fonts/chart/calendar licenses and WordPress directory compatibility | Legal, Security, UI | Before redistribution |
| UX11-D-12 — Experience performance targets | Set actual numeric budgets only after reproducible baseline | Performance, UX, QA | Before launch gate |
| UX11-D-13 — Accessibility testing environment | Freeze supported browser/AT/OS combinations and research sample | Accessibility, QA | Before acceptance test plan |
| UX11-D-14 — Mobile Layout Studio interactions | Structural editor with full task parity; evaluate advanced canvas on touch via research | UX, Frontend, AdOps | Before R1 launch |

Unresolved items are **decisions**, not permission to fabricate defaults. Any resolution that changes business, security, finance, privacy or release scope requires amendment of the authoritative upstream source and downstream traceability.

## 21. References and implementation anchors

Primary upstream project documents (local file names):

- `01_ADPILOT_PRODUCT_REQUIREMENTS_DOCUMENT.md` — normative scope and 129 FR / 18 BR / 16 NFR / 15 AC.
- `02_ADPILOT_DETAILED_FEATURE_CATALOG_TRACEABILITY_ACCEPTANCE.md` — 18 capabilities, 20 screen IDs, FR acceptance, E2E.
- `03_ADPILOT_USER_PERSONAS_JTBD_USER_JOURNEYS_WORKFLOWS.md` — 16 personas, 24 JTBD, 28 journeys, 18 workflows, UXA cases.
- `04_ADPILOT_BUSINESS_RULES_DOMAIN_POLICIES_STATE_MACHINES_PERMISSIONS.md` — policy enforcement, versioned state and guarded transitions.
- `05_ADPILOT_ROLES_CAPABILITIES_PERMISSIONS_MULTI_TENANT_ACCESS_CONTROL.md` — site and advertiser organization permission planes.
- `06_ADPILOT_SYSTEM_ARCHITECTURE_ADRS_MODULE_BOUNDARIES.md` — bounded module interfaces and deployment topology.
- `07_ADPILOT_DOMAIN_MODEL_MODULE_CONTRACTS_APPLICATION_SERVICES.md` — domain contracts and event ownership.
- `08_ADPILOT_DATABASE_SCHEMA_MIGRATIONS_INDEXES_DATA_LIFECYCLE.md` — immutable layout, finance, and analytic data.
- `09_ADPILOT_REST_API_WEBHOOKS_INTEGRATION_CONTRACTS.md` — canonical routes, request/response, errors and authorizations.
- `10_ADPILOT_EVENT_SCHEMAS_BACKGROUND_JOBS_OUTBOX_ASYNC_PROCESSING.md` — job state, async consistency and release discrepancy register.

Public standards and technical guidance consulted (not substitutes for the project specifications):

- [WordPress block metadata (`block.json`)](https://developer.wordpress.org/block-editor/getting-started/fundamentals/block-json/)
- [WordPress block editor controls and components](https://developer.wordpress.org/block-editor/getting-started/fundamentals/block-in-the-editor/)
- [WordPress Block API Reference](https://developer.wordpress.org/block-editor/reference-guides/block-api/)
- [W3C WCAG 2.2](https://www.w3.org/TR/WCAG22/)
- [W3C What's New in WCAG 2.2](https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/)
- [WAI-ARIA Authoring Practices Guide](https://www.w3.org/WAI/ARIA/apg/)
- [ARIA Treegrid Pattern](https://www.w3.org/WAI/ARIA/apg/patterns/treegrid/)

External component API versions, WordPress/browser compatibility and dependency licenses must be reverified when software is actually implemented and released.

## 22. Specification audit and handoff to Document 12

**Structural audit obligations:** exactly 20 canonical screen IDs (`UI-01..20`), 28 journey IDs (`UJ-01..28`), 18 workflows (`WF-01..18`), 129 exact functional requirements with unchanged release labels and existing `AT-*` links, 18 business-rule IDs, 16 NFR IDs, 15 AC IDs, unique new UX test IDs, balanced Markdown fences, and no unapproved R1 promotion of R2 heatmap/R3 metered billing/R4 replay. Mechanical checks verify identifier presence and table structure, not semantic correctness or test execution.

**Required implementation handoff for GPT-6 High:** choose one `UX11-WP-*` at a time; load authoritative source contracts; inspect actual repository; produce typed components and localized UI states; preserve domain server gates; test keyboard/mobile/RTL and failure scenarios; capture real evidence and unresolved questions; do not present mock visualizations as a shipped screen or fabricated analytics as actual data.

**Next canonical document:** **ADP-DOC-012 — Visual Design System, Component Library & Frontend Design Tokens Specification.** It will specify approved token scales, component anatomy/variants, navigation visual patterns, tables/forms, typography, charts, UX motion, RTL and accessibility design guidelines, while preserving all interaction obligations in this document.

---

**Document-end checkpoint:** UX architecture and interaction contracts are specified; implementation, actual usability research and test runs remain outstanding until separately evidenced.
