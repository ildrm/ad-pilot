# AdPilot — Administrator Console UI & Dashboard Implementation Specification

**Document ID:** ADP-DOC-013  
**Filename:** `13_ADPILOT_ADMINISTRATOR_CONSOLE_UI_DASHBOARD_IMPLEMENTATION.md`  
**Version:** 1.0.0 — proposed implementation contract  
**Status:** Proposed; Product Owner, UX Lead, Tech Lead, Finance, AdTech, Security/Privacy and QA approvals outstanding  
**Date:** 2026-10-12  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Owner:** Lead Product Designer and Senior React/WordPress Frontend Engineer  
**Implementation target:** GPT-6 High, with individually testable work packages and evidence  
**Normative input:** ADP-DOC-001 through ADP-DOC-012  
**Next document:** ADP-DOC-014 — Advertiser Portal, Marketplace & Campaign Purchasing UI Specification

> **Status notice:** All UI structures, routes, props, types, tests, metrics and examples below are proposed specifications. They are not claims that AdPilot has been built or tested. Existing source documents govern feature release scope, business behavior, visual tokens, API security, and module contracts.

---

## 1. Purpose, scope and precedence

This document defines the precise implementation of **publisher-facing WordPress administration experiences**: AdPilot executive overview, inventory, Layout Studio, slot navigation, placement binding inspection, operational campaign management, creative moderation, booking calendars, rates and orders, reconciliation, delivery diagnostics, aggregated reporting, R2 heatmaps, R2 exports, R3 dispute workflows, and settings/system health. It supplements—not replaces—the advertiser portal and public marketplace specifications.

**Authoritative source order:**

1. **ADP-DOC-001** controls FR, BR, NFR, INV, AC meanings and releases R1–R4.
2. **ADP-DOC-002** controls CAP-01..18, UI-01..20, AT-* and E2E-01..17 references.
3. **ADP-DOC-003** controls personas PERS-01..16, JTBD-01..24, user journeys UJ-01..28, workflows WF-01..18, UXA-01..28 and original screen behavior.
4. **ADP-DOC-004** controls DP-* policies, SM-* independent state machines, commercial invariants, elevated operations, and denied transitions.
5. **ADP-DOC-005** controls WordPress capability names, org-scoped authority, multisite scoping, resource authorization and audit rules.
6. **ADP-DOC-006/007** control the modular-monolith architecture, IC-001..024 inter-module contracts, aggregates, services, and canonical event model.
7. **ADP-DOC-008/009/010** control persistence ownership, existing route registry, field-level API projections, event handling, async jobs, privacy and idempotency.
8. **ADP-DOC-011/012** control UX navigation, screen IDs, design tokens, components DS12-C-001..049, interaction states, accessibility and frontend constraints.
9. **This document** adds admin-specific composition and test oracles only. It SHALL NOT rename a canonical UI/FR/API/capability ID, create a parallel payment or ad-serving backend, or silently settle an unresolved earlier architecture decision.

**Language:** MUST/SHALL = required for the relevant release; SHOULD = recommended with approved documented exception; MAY = optional. "R1" features must be implemented before an R1 release; "R2/R3/R4" features may appear only if a separately approved feature gate enables them. All timestamps are stored UTC, displayed with explicit locale and timezone context, and commercial intervals are half-open `[start,end)`.

### 1.1 In-scope versus linked interfaces

| Scope | Canonical UI | Commitment |
|---|---|---|
| Admin-owned | UI-01..05, UI-12..15, UI-17..20 | Full composition, interaction, access, API and QA specifications here |
| Shared publisher/advertiser projections | UI-11, UI-14 | Publisher-specific view must differ from organization-scoped portal view; shared components may be reused only with safe projections |
| Advertiser/public owned | UI-06..10, UI-16 | Navigation, referral, and authorized deep link rules here; complete UI implementation in ADP-DOC-014 |
| Gutenberg editor | Four existing `adpilot/*` blocks | Admin preview/binding diagnostics here; block-editor implementation in later document |
| Public ad renderer | Theme hooks, PHP functions, shortcodes | Admin diagnostics/configuration here; runtime contract remains ADP-DOC-006/009 |

### 1.2 Mandatory product truths

- Configured slot count **is not** served/rendered/measurable/viewable/billable impression count. A page may define 500+ logical slots but only expose a few during one visit.
- Payment settlement, creative approval, booking entitlement, campaign schedule and delivery are separate authoritative state dimensions. The UI never shows a generic "active" label as a substitute for all of them.
- Quote availability is advisory until an authoritative hold is acquired. A checkout timer never extends a server-side hold.
- A paid or exclusive layout slot cannot be repurposed by drag/drop without a contractual impact check and authorized remedy.
- Current authorization and resource ownership are checked by the server. Hiding a button is UX, not authorization.
- Unavailable analytics and disconnected financial data are shown as **unavailable or stale**, not zero.
- R1 has basic event reports; full-page native heatmaps belong to R2, metered-billing disputes to R3, and optional session replay/network federation to R4.
- No editor preview, diagnostic simulation or synthetic test can post a billable production measurement.
- Admin login, roles, privacy preferences and WordPress global screen behavior are inherited from WordPress; do not duplicate or weaken them.


### 1.3 Complete canonical UI index and this document's ownership

| UI ID | Original screen title | Ownership in ADP-DOC-013 |
|---|---|---|
| `UI-01` | Admin executive dashboard | Full admin specification |
| `UI-02` | Zone registry | Full admin specification |
| `UI-03` | Layout Studio | Full admin specification |
| `UI-04` | Slot navigator | Full admin specification |
| `UI-05` | Template binding inspector | Full admin specification |
| `UI-06` | Public catalog | Linked public marketplace; Document 14 owns detail |
| `UI-07` | Placement detail | Linked public marketplace; Document 14 owns detail |
| `UI-08` | Advertiser onboarding/team | Linked advertiser portal; Document 14 owns detail |
| `UI-09` | Campaign wizard | Linked advertiser portal; Document 14 owns detail |
| `UI-10` | Checkout handoff | Linked advertiser portal; Document 14 owns detail |
| `UI-11` | Campaign list/detail | Full publisher projection here; advertiser projection belongs to Document 14 |
| `UI-12` | Creative moderation | Full admin specification |
| `UI-13` | Booking calendar | Full admin specification |
| `UI-14` | Rates/orders and scoped billing | Full publisher finance projection; org projection in Document 14 |
| `UI-15` | Ad delivery diagnostics | Full admin specification |
| `UI-16` | Advertiser reporting | Advertiser portal owns; admin aggregate dashboards owned here |
| `UI-17` | Heatmap explorer | Full R2 admin specification |
| `UI-18` | Export jobs | Full R2 dedicated UI; R1 basic API remains distinct |
| `UI-19` | Billing dispute | R3 admin view; mutation API blocked until approved |
| `UI-20` | Settings/system health | Full R1 admin specification plus gated extensions |

## 2. User roles, ownership and release access

### 2.1 Audience and least privilege

| Publisher actor | Landing preference | May see | Cannot infer or bypass |
|---|---|---|---|
| Site Administrator | UI-01 overview | Site-scoped permitted operations, configurable scope | Cross-site/network private records, irreversible acts without policy approval |
| Ad Operations Manager | UI-11 operations or UI-02 inventory | Inventory, campaign readiness, booking, diagnostics by grant | Ledger mutation, payment approval, private replay, role escalation |
| Creative Moderator | UI-12 moderation | Versioned creative, policy checklist, sanitized destinations | Ledger, campaigns of unauthorized sites, advertiser private analytics |
| Finance Manager | UI-14 finance | Authorized orders, invoices, reconciliation and approved remedies | Creative approval, site security changes, raw heatmap/session data |
| Analytics Manager | UI-01 analytics variant | R1 validated aggregates; R2 aggregated heatmaps if authorized | Restricted visitor-level traces and replay absent separate grant |
| Support Agent | UI-15 diagnostics | Redacted incident and readiness statuses | Impersonation, sensitive exports, financial overrides |
| Layout Editor | UI-02/03 if specifically granted | Draft layout and approved bindings | Publishing a sold-impact change without required approval |
| WordPress Content Editor | Native editor + allowed block controls | Permitted published zone references | Privileged AdPilot Layout Studio merely from edit_posts |
| Network operator (R4) | Explicit network governance | Only independently authorized site-targeted tasks | Implicit site switching or cross-site access through network identity |

A user may have more than one role, but the policy engine evaluates each operation's current scope, resource, state, purpose, and required elevated approval. The frontend never combines unrelated grants into a super-role. Advertiser organization Owner/Manager roles do not automatically confer publisher permissions.

### 2.2 Nav visibility versus authorization

- `canDiscover`: UI may show a route entry based on a safe, server-derived capability projection and release gate.
- `canRead`: server evaluates route and target resource when requesting data.
- `canAct`: server evaluates current resource version, state, actor/site scope, finance/booking policy and potential two-person approval.
- `canExport`: permission at request time **and** again when downloading a finished file.
- No private financial or tenant IDs appear in unauthorized menu labels, toasts, error payloads, URLs, preloaded state or cache keys.
- If permission is revoked mid-session, abort relevant requests and display an accessible denial with a safe authorized return path.

### 2.3 Standard role-sensitive affordances

| Control | Read prerequisite | Mutation prerequisite | Secondary guard |
|---|---|---|---|
| Edit zone | `adpilot_view_inventory` | `adpilot_manage_inventory` | Active reservations/sold-impact validation |
| Publish layout | `adpilot_view_inventory` | `adpilot_publish_layout` | Version precondition + contract impacts resolved |
| Manage rate card | `adpilot_view_finance` or approved pricing view | `adpilot_manage_rates` | Draft/version valid; published/accepted versions immutable |
| Moderate creative | `adpilot_moderate_creatives` | Same plus exact review policy | Content digest/version, audited rationale |
| Emergency stop | campaign read within site | `adpilot_emergency_stop` | Current campaign, reason, nonfinancial audit |
| Approve refund/ledger remedy | restricted finance read | `adpilot_approve_financial_override` | Segregation of duties; no self-approval where configured |
| Edit privacy | permitted settings read | `adpilot_manage_privacy` | Consent/regulatory review; audit |
| View heatmap | `adpilot_view_aggregate_analytics` | None for aggregate read | R2 gate, privacy, site/layout/data limits |
| Download export | `adpilot_export_reports` | N/A | Current scope, expiry and protected storage |
| Retry background job | `adpilot_view_diagnostics` | `adpilot_retry_jobs` | Approved class, provenance and idempotency |

Use the **exact primitive grants and advanced restrictions** in ADP-DOC-005; the above is a UX summary, not an alternative capability registry.


## 3. Information architecture, routes and navigation

### 3.1 Publisher navigation tree

```text
AdPilot (WordPress administration menu)
├── Overview                                  UI-01
├── Inventory
│   ├── Zones & Containers                    UI-02
│   ├── Layout Studio                         UI-03
│   ├── Slot Navigator                        UI-04
│   ├── Theme / Template Bindings             UI-05
│   └── Booking Calendar                      UI-13
├── Campaign Operations
│   ├── Campaigns                              UI-11 (publisher projection)
│   ├── Creative Moderation                   UI-12
│   ├── Delivery Diagnostics                  UI-15
│   └── Advertiser Organizations              contextual admin directory
├── Commerce
│   ├── Rates & Orders                        UI-14
│   ├── Payment Reconciliation                UI-14 contextual
│   └── Billing Disputes                      UI-19 (R3)
├── Analytics
│   ├── Publisher Reports                     UI-01/report drill-down
│   ├── Heatmap Explorer                      UI-17 (R2)
│   └── Exports                               UI-18 (R2 enhanced)
└── Settings & System Health                  UI-20
```

Preserve the navigation canonical structure from `UX11-NAV-01`. Each entry has a stable internal route key, localized label, release flag, discover permission, breadcrumb parent and permitted default destination. A related advertiser screen does not become an administrator view merely because components are shared.

### 3.2 WordPress menu integration

- Register AdPilot as a top-level wp-admin plugin menu using the appropriate WordPress capability and plugin-owned page slugs.
- Child pages use WordPress menu APIs; use explicit `admin_enqueue_scripts` screen hooks, not global enqueue on every wp-admin page.
- App-level navigation may be client-side but MUST respect WordPress admin URL, browser Back/Forward, deep links, multisite site context and WordPress menu active status.
- A plugin screen can provide a React root and skip link, but SHALL NOT remove WordPress admin navigation, accessibility landmarks or core notices indiscriminately.
- Route display names use WordPress i18n and match the H1. Breadcrumbs are genuine navigation links.
- URL state may store date, pagination, filters, tab and selected non-sensitive entity IDs. Never store secrets, downloadable export tokens, payment provider credentials or replay session data in URL.
- Show a change-warning for unsaved drafts; distinguish cosmetic local edits from server-saved changes.

### 3.3 Proposed internal route-key contracts

These are **frontend router keys**, not new REST routes or WordPress-rewrite URL commitments; actual page URLs are bound through wp-admin menu registration.

| Key | Canonical UI | Purpose |
|---|---|---|
| `admin.overview` | UI-01 | Cross-workstream operational home |
| `admin.inventory.zones` | UI-02 | Zone registry |
| `admin.inventory.layout` | UI-03 | Layout editing and safe publication |
| `admin.inventory.slots` | UI-04 | Generated-slot navigator |
| `admin.inventory.bindings` | UI-05 | Theme/Gutenberg binding inspector |
| `admin.inventory.calendar` | UI-13 | Reservation calendar |
| `admin.campaigns.list` / `.detail` | UI-11 | Site-scoped campaign lifecycle |
| `admin.creatives.reviews` / `.detail` | UI-12 | Moderation queue and exact version review |
| `admin.advertisers` / `.detail` | Related operations surface | Site-scoped organization verification/suspension |
| `admin.commerce.orders` / `.rates` / `.reconciliation` | UI-14 | Commercial management |
| `admin.commerce.disputes` | UI-19 | R3 metered disputes |
| `admin.delivery.health` | UI-15 | Delivery health and incident triage |
| `admin.analytics.heatmaps` | UI-17 | R2 aggregate heatmaps |
| `admin.analytics.exports` | UI-18 | R2 export job lifecycle |
| `admin.settings.health` / `.privacy` / `.access` | UI-20 | Configuration and operations |

### 3.4 Deep-link and tab rules

- Tab state is stable across refresh and browser navigation; if an inaccessible tab was bookmarked, open a safe denied state rather than redirect to a different tenant or leak the title.
- Detail views restore parent filter/sort/page on Back, without preserving stale transaction commands.
- Route entry must focus a meaningful heading, except when restoring expected focus to the initiating control.
- Failure to fetch navigation metadata must not show sensitive guessed menu items. Provide a permitted fallback screen and retry.
- R2–R4 links are omitted or explicitly marked unavailable, never navigable to a fake working UI in R1.

## 4. Application shell and layout behavior

### 4.1 Global layout anatomy

The shell consists of: WordPress menu (outside React root), application skip link, app navigation, breadcrumb, page header, context badge (active site only), date/time range where relevant, help entry, notification center, content region, toast/status announcer and shared modal manager. On desktop, content width follows the `ADP-DOC-012` layout tokens; dense data tables may use a larger safe width. Do not impose a rigid fixed width on the Layout Studio.

### 4.2 Responsive behavior

| Viewport/test context | Required adaptation |
|---|---|
| 320/360/390 CSS px | One-column essentials, tasks before charts, bottom-safe action bars that do not cover controls; tables expose bounded horizontal scrolling or card/list alternatives |
| ~768 px | Two-column cards where viable, folded filter drawer, calendar agenda/list alternative, Studio one active panel at a time |
| ~1024 px | Conditional two-pane editor, summary plus detail, compact navigation |
| 1280/1440+ px | Full three-pane Layout Studio, dashboard KPI grids, adjacent operations queues |
| 200%/400% zoom | Content remains usable and reflows; only genuine two-dimensional data tables may scroll horizontally in their own region |
| RTL | Logical properties, mirrored navigational placement where appropriate, preserved LTR syntax for IDs/URLs/code/money where context requires |
| Forced-colors / reduced-motion | Visible focus/boundaries, no color-only meaning, essential feedback survives disabled animation |

Testing widths are fixtures, **not hard-coded device categories or mandatory CSS breakpoints**. Use `--adp-*` semantic tokens and approved WP packages. Avoid global `h1`, `button`, `input`, `.components-*` or public theme selector overrides.

### 4.3 Screen states

Every data-driven screen must implement: `bootstrapping`, `initial_loading`, `ready`, `ready_empty`, `refreshing`, `stale_partial`, `validation_error`, `action_pending`, `action_accepted_processing`, `conflict`, `permission_denied`, `offline/network_error`, `server_error`, and `feature_unavailable`. When relevant, add `policy_blocked`, `approval_required`, `expired`, and `degraded_dependency`. Avoid a full-screen spinner after shell initialization; display targeted skeletons and retain safe existing data with a visible stale marker. Empty means authoritative zero records, not network failure.

| State | User-facing treatment | Accessibility + recovery |
|---|---|---|
| Initial loading | Stable skeleton, preserved header/filter context | Main region `aria-busy`; avoid focus theft |
| Empty | Specific explanation and capability-gated CTA | Heading plus next task, not decorative blank chart |
| Refreshing | Existing results remain with freshness cue | Avoid noisy announcements for polling |
| Stale/partial | Label source timestamp, affected fields and retry | Do not portray missing revenue as `$0` |
| Validation error | Inline field errors and summary | Focus error summary; connect `aria-describedby` |
| Concurrent conflict | Before/current version comparison, safe reload | No automatic overwrite of protected objects |
| Permission denied | Minimal non-sensitive message | Safe authorized destination; no leaked object title |
| Feature unavailable | Release/dependency explanation | Disable CTA; actionable docs/health link if authorized |
| Pending async | Server job receipt or state with polling | Resume after refresh; no synthetic success |
| Irreversible mutation | Impact view, explicit confirmation, reason | Keyboard-confirmable; audited backend action |

## 5. Data visualization, KPI semantics and dashboard source boundaries

### 5.1 Summary metric contract

Every KPI response must specify: machine metric key, display label, raw numeric value or explicit null, unit, aggregation interval, timezone, numerator/denominator where appropriate, metric definition version, freshness/watermark, source, qualification class, comparison period, and access scope. A financial projection is authoritative only if sourced from commerce ledger. Never calculate revenue by multiplying frontend click counters by a rate.

| KPI | Definition | Source | Notes |
|---|---|---|---|
| Recognized advertising revenue | Approved ledger-based revenue according to documented accounting definition | Commerce/Finance projection | Separate gross/net/refunds/credits, and don't assume tax treatment |
| Collected cash | Settled provider receipts, appropriately reconciled | Commerce | Different from recognized revenue |
| Outstanding receivables | Authorized invoiced, unpaid balance | Commerce | Do not mix with temporary quote totals |
| Active campaigns | Campaigns currently in authoritative delivery-active state | Campaigns/Delivery | Payment and creative approval displayed separately |
| Pending reviews | Exact immutable creative submissions needing moderator action | Moderation | Count by current site policy |
| Expiring reservations | Current holds/reservations expiring within selected window | Reservations | Never assume a reservation is sold if merely held |
| Available inventory | Eligible capacity after current holds/contracts | Inventory | Based on target dates and terms |
| Served impressions | Served qualified/ad-decision counters per measurement definition | Measurement | Not equivalent to rendered/viewed |
| Rendered/measurable/viewable | Separate measurement stages, R2 viewability enhancement | Measurement | Display supported metrics per release, with denominators |
| Qualified clicks and CTR | Validated qualifying clicks / defined denominator | Measurement | Traffic qualification required; not raw click events |
| Delivery incidents | Count of active authoritative delivery/queue issues | Platform | Expose incident severity and update time |
| Analytics ingestion lag | Last fully aggregated/qualified event watermark | Reporting/Async | Display delayed/unknown not zero |

### 5.2 Date/filter comparison rules

- Date range filter operates on explicit UTC request window plus selected presentation timezone; compare periods must be labeled and of comparable length.
- Default interval is a product decision; show actual selected dates, do not silently assume server timezone equals viewer timezone.
- Zero baseline makes percentage delta mathematically undefined: display `New activity`, `N/A`, or explicit qualitative classification, not `+∞%`.
- Currency comparisons must be same-currency or use an explicitly approved rate snapshot; do not sum unlike currencies as if equivalent.
- A data export/screenshot states metric definition and source coverage to prevent misinterpretation.
- Filter changes do not modify authoritative campaign or finance state; GET projections remain read-only.

## 6. Core reusable frontend composition

### 6.1 Existing design-system dependency

Use DS12-C-001..049 from Document 12, especially `AppShell`, `PageHeader`, `Breadcrumb`, `SideNavigation`, `MetricCard`, `DataTable`, `FilterBar`, `StatusPill`, `NoticeBanner`, `ModalDialog`, `ApprovalTimeline`, `ReservationCalendar`, `LayoutZoneTree`, `LayoutCanvas`, `SlotNavigator`, `ResponsiveInspector`, `PublishImpactDialog`, `SystemHealthCard`, `HeatmapViewer`, `HeatmapLegend` and `ExportJobRow`. The exact names of each component are frozen by the Document 12 catalog; new screen composites may wrap them but do not replace or rename canonical components.

### 6.2 New console-only composites (proposal)

| ID | Composite | Props / source contract | Critical behavior |
|---|---|---|---|
| `ADM13-C-01` | `ExecutiveOverview` | Typed KPI aggregate + authorized work queues | Independent financial and measurement freshness |
| `ADM13-C-02` | `PriorityActionQueue` | Audited queue preview, unread/blocked flags | Alert links to authorized owner action |
| `ADM13-C-03` | `InventoryUtilizationPanel` | Site/layout/version/date-scoped capacity | Available/held/sold/active distinct |
| `ADM13-C-04` | `ContractImpactSummary` | Immutable impact assessment | Block sale-affected publish; request approval |
| `ADM13-C-05` | `CampaignReadinessChecklist` | Server-derived reasons per campaign | Distinguish missing payment, creative, dates, capacity |
| `ADM13-C-06` | `CreativeReviewDesk` | Review id + immutable digest + preview | Approve only exact version; reason mandatory reject |
| `ADM13-C-07` | `ReservationConflictPanel` | Current availability + competing intervals | Suggest fresh quote/date; never oversell |
| `ADM13-C-08` | `LedgerReconciliationPanel` | Authorized exact money and provider evidence | Ledger vs provider mismatch with remediation path |
| `ADM13-C-09` | `DeliveryIncidentTimeline` | Redacted incidents and job health | Recheck state before retry/stop |
| `ADM13-C-10` | `SensitiveActionReview` | Actor, reason, impact, policy, approval status | Separation of duties; no self-elevation |
| `ADM13-C-11` | `AdminScreenStateBoundary` | Typed discriminated states + retry | No empty-as-error ambiguity |
| `ADM13-C-12` | `AuditContextDrawer` | Privileged redacted audit projection | Safe structured history; no raw visitor export |
| `ADM13-C-13` | `FinancialDimensionFilter` | Currency, tax view, account and interval | Unit-safe comparisons |
| `ADM13-C-14` | `DeliveryMeasurementLegend` | Metric definition, numerator/denominator | Zero vs unavailable + viewability version |
| `ADM13-C-15` | `AdminNotificationsDrawer` | Authorized alerts, outbox state, deep links | No private payload in toast or URLs |
| `ADM13-C-16` | `BulkOperationResultPanel` | Operation id, item results, success/failure | No misleading all-success toast on partial failure |
| `ADM13-C-17` | `LayoutRevisionDiffPanel` | Baseline & candidate semantic changes | Stable slot identity and commercial effect |
| `ADM13-C-18` | `ScopedReportBuilder` | Allowed metrics/dimensions + scope | Prevent arbitrary cross-tenant query expression |
| `ADM13-C-19` | `HealthCheckChecklist` | Redacted worker/db/queue/compat results | Block unsafe checkout/serving with diagnostic |
| `ADM13-C-20` | `SavedAdminView` | Non-sensitive filter/sort/columns | Reset when privileges or release gate change |

**Component architecture:** composites compose Document 12 primitives; they do not own domain mutation logic. Fetch hooks call typed REST clients; mutations use application command clients; policy-driven actionability derives from server response. Form state and data cache are separate. Story fixtures show all state variants and prevent production event emission.


## 7. Canonical administrator screen contracts

**How to implement each screen:** The sections below are normative screen contracts. Every screen additionally inherits `ADP-DOC-011`'s complete loading/empty/stale/denied/conflict/retry semantics, `ADP-DOC-012` design-system components, `ADP-DOC-009` exact REST field schemas, and `ADP-DOC-005` current capability/ownership checks. The API IDs listed are references—not permission bypasses or newly created endpoints. API routes may not be invented from UI convenience; an absent required operation is an upstream API change request.


### 7.1 `UI-01` — Executive Dashboard (`ADM13-S-01`)

**Release:** R1. **Personas:** PERS-01/02/09/10. **Server authorization:** adpilot_view_aggregate_analytics (individual finance cards require adpilot_view_finance). **API routes/contracts:** `REP-001, REP-002, REP-003, OPS-003`. **Journeys:** UJ-14, UJ-15, UJ-16, UJ-17.

**Information hierarchy and layout.** The top row is an **action summary**, not decorative vanity metrics: emergency-delivery failures, approvals overdue, contract violations, payment reconciliation warnings and expired holds appear in prioritized severity order. Second row shows independently sourced finance KPIs and measurement KPIs with separate watermarks. Third row shows campaign activity, inventory utilization and key trend visualizations. Last row contains recent audited operations and contextual documentation. Each alert links to its actual owner workflow; the general dashboard must not execute high-risk mutations directly.

**Actions and interaction states.** Time and site context, date range with timezone, metric definitions, saved filters, period comparison, accessible chart/table switch, card-to-detail navigation, refresh, mark operational alert read if authorized. Show finance cards only to appropriately privileged users; for analytics-only roles a restricted shell is legitimate, not an error.

**Failure, concurrency, security and financial truth.** When the commerce ledger is unavailable but measurement is healthy, display revenue as unavailable with independent analytics still usable. When an aggregate watermark lags, show its last complete time. On no campaigns, provide role-authorized setup flow. Never report zero spend or zero impressions solely because a datasource failed. Suppress R2 heatmap suggestions until the module exists.

**Frontend and accessibility.** Dashboard initial shell is visible without all slow cards. Widgets use parallel bounded queries, request cancelation, route-level Suspense boundaries where adopted, refresh controls and stable placeholders; no continuous 1-second polling. Screen-reader table alternatives accompany chart data.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-001`.


### 7.2 `UI-02` — Zones & Containers Registry (`ADM13-S-02`)

**Release:** R1. **Personas:** PERS-01/02. **Server authorization:** adpilot_view_inventory; edits require adpilot_manage_inventory. **API routes/contracts:** `INV-001..008, INV-013, LAY-001`. **Journeys:** UJ-09, UJ-10, UJ-22.

**Information hierarchy and layout.** Provide a searchable and filterable registry of zones with **immutable logical identifiers**, name, status, enabled page contexts, container type, configured slot count, sold/held capacity, active/published version, binding count, and updated timestamp. Zone details contain a compact tree of containers and links to slot navigator and Layout Studio. No "delete" control for commercially committed inventory; archive requires server policy validation.

**Actions and interaction states.** Create wizard (name, code/slug, template contexts, container defaults, visual preview), duplicate as independent draft with new identity, edit draft, archive, view conflicts, export permitted configuration if implemented. Batch selections show per-item eligibility and confirmation; publication remains in Layout Studio, not an implicit side effect of save.

**Failure, concurrency, security and financial truth.** Existing sold zone cannot be archived without validated remedy. Empty registry offers Create zone and prebuilt safe example. If filtered no results, clear filters without removing data. Concurrent edits display the stale revision and fresh current version, allowing deliberate reload or a safe merge only where policy permits.

**Frontend and accessibility.** Backend pagination and filter args are allowlisted; sort by stable keys. Large counts are aggregated server-side, not by fetching thousands of slots. A dense table includes a responsive list view, selection summary and keyboard actions.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-002`.


### 7.3 `UI-03` — Layout Studio (`ADM13-S-03`)

**Release:** R1. **Personas:** PERS-01/02. **Server authorization:** draft edits: adpilot_manage_inventory; publication: adpilot_publish_layout. **API routes/contracts:** `LAY-001..012, INV-009..014`. **Journeys:** UJ-09, UJ-10, UJ-22.

**Information hierarchy and layout.** Three-pane desktop workspace: (1) left **Zone Tree** with zone/container structure; (2) center **Canvas Preview**, viewport toolbar, overlay modes and bounds; (3) right **Property Inspector** with layout fields, responsive rules, allocation previews, density warnings and validation. Header contains Draft Version, dirty/saved timestamp, Compare, Validate, Preview and Publish. The footer or side panel contains commercial impact summary, available to authorized users; warnings remain visible even if there are no DOM elements for each generated slot.

**Actions and interaction states.** Generate a 500-slot layout from a compact sequence (e.g., header 4, sidebar 100, main 196, footer 200). Edit container type/grid count/gap/breakpoints; bulk edit generated slot ranges; inspect stable IDs; navigate page sections; compare responsive configurations; save optimistic revision; validate with server; preview strictly nonbillable; publish after contract-impact assessment. Reorder operations alter display order but SHALL NOT silently alter sold commercial identity. Undo/redo is a versioned **local draft operation**, not a bypass of publication review.

**Failure, concurrency, security and financial truth.** If edit alters a sold slot's commercial meaning, show a **blocking impact detail** listing affected reservations/campaigns, contractual effect, possible solutions, required approvers and immutable policy decision; publishing stays disabled until authoritative approved resolution. If someone else modifies the draft, display side-by-side diff and reload/reconcile. Failed preview cannot result in publication readiness. If schema/compiler mismatch, stop and show diagnostics; never mutate the live layout as fallback.

**Frontend and accessibility.** Preview maximum bounded elements (configurable safe batch), representing remaining 500+ slots as summarized ranges. No full page of 1,000 interactive nodes in inspector. Mobile/zoom: switch between tree, structural slot list, canvas and inspector, with keyboard equivalents for drag/drop. Layout canvas selection announced with semantic slot IDs; use fixed or normalized geometry only for display, not persisted commercial position identity.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-003`.


### 7.4 `UI-04` — Slot Navigator & Bulk Operations (`ADM13-S-04`)

**Release:** R1. **Personas:** PERS-02. **Server authorization:** adpilot_view_inventory; changes require adpilot_manage_inventory. **API routes/contracts:** `INV-009..014`. **Journeys:** UJ-09, UJ-10, UJ-22.

**Information hierarchy and layout.** Provide cursor-paginated slot table with stable slot ID, zone/container, sequence index, template binding, responsive eligibility, active reservations, availability status, creative format and relevant layout version. Toolbar supports filter by status, date window, campaign, zone, format and sold-contract impact. An explicit selection mode supports current page, all matching query (with signed server-side selection token if approved), and selected item list with counts.

**Actions and interaction states.** Bulk assign allowed policy flags, pause valid unused slots, update display rules, reassign permitted generated slots, export redacted slot inventory, inspect contracts and open selected slot in Studio. Every bulk job must return a job or per-row result summary (succeeded/rejected/conflicted), not claim atomic all-success if only some items worked.

**Failure, concurrency, security and financial truth.** Editing 100 sold slots must show blocked items with reasons; never skip silently. Cursor expired/stale filter requires refresh while preserving user intention. Duplicate IDs are data-integrity incidents, not editable names. Across page selection must clear or revalidate when filter or site context changes.

**Frontend and accessibility.** Avoid rendering all generated slots. Use server pagination or windowing only inside the admin table; role-controlled compact/comfortable density; keyboard row selection and screen reader summary. Batch preview shows first N changes and impact totals, not 1,000 panels.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-004`.


### 7.5 `UI-05` — Template & Theme Binding Inspector (`ADM13-S-05`)

**Release:** R1 with R2 diagnostics enhancement. **Personas:** PERS-11/12. **Server authorization:** adpilot_view_inventory; mutation requires adpilot_manage_inventory; editor contextual insertion rights separate. **API routes/contracts:** `LAY-013..016, BLOCK-001..004`. **Journeys:** UJ-11, UJ-12.

**Information hierarchy and layout.** A page/template binding table shows rendering source (dynamic block, PHP API, shortcode, hook, supported injection rule), WordPress context, zone ID, exact binding identifier, status, theme compatibility diagnostics and detected duplicate instances. Developer help drawer shows **copyable**, versioned PHP/shortcode examples from approved public APIs, without a PHP code editor. Code sample attribution and expected output are stated.

**Actions and interaction states.** Create a validated binding, disable a binding after sold-impact check, inspect a Gutenberg target, copy safe integration snippet, run diagnostic check and preview nonbillable placement. The inspector distinguishes multiple intentional occurrences from accidental duplicates and does not call cross-tenant admin APIs from public theme functions.

**Failure, concurrency, security and financial truth.** Unknown theme hook or removed template target yields a warning and fallback instructions, not a silent successful binding. Invalid selector/capability prevents save. When a zone is intentionally repeated, render occurrence IDs differ while slot identities and billing semantics follow explicit policies.

**Frontend and accessibility.** Code snippets are LTR isolated with copy and descriptive filename guidance. Accessible tree/list view is provided. Preview assets do not initialize production click/impression reporting.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-005`.


### 7.6 `UI-11` — Publisher Campaign Operations (`ADM13-S-06`)

**Release:** R1. **Personas:** PERS-01/02/04. **Server authorization:** adpilot_view_campaigns; actions require adpilot_manage_campaigns or adpilot_emergency_stop. **API routes/contracts:** `CAM-008..013, CRE-006..009`. **Journeys:** UJ-07, UJ-14, UJ-15.

**Information hierarchy and layout.** Campaign list columns are campaign, advertiser org, booking and creative versions, **payment status**, **approval status**, **delivery status**, schedule UTC/local, campaign readiness, configured allocation, qualified metrics, and next operational action. Campaign detail has separate tabs for Overview, Creatives, Inventory/Bookings, Delivery, Measurement, Commercial Summary (permissioned), and Audit. The timeline combines events while preserving which authoritative subsystem emitted each.

**Actions and interaction states.** Filter failed readiness, scheduled today, awaiting review, active, paused, overdue and advertiser; inspect validation blockers; issue schedule, pause, resume or emergency stop through command endpoints. No generic "Approve and activate" shortcut that collapses payment and moderation. Emergency stop requires explicit reason and renders immediate authoritative result, then asynchronous propagation health.

**Failure, concurrency, security and financial truth.** Paid but unapproved -> clearly shows **Paid / Review pending / Not serving**. Suspended but cached creatives -> emergency stop status with invalidation freshness. Out-of-order job changes -> conflict and authoritative current state. A rejected creative's previously approved version may be used only when server policy explicitly permits, never via a UI selector bypass.

**Frontend and accessibility.** Timeline entries have semantic status/time, expandable details and source identifiers; money only for authorized finance actors. Tables are paginated and derived counts independent of revenue; mobile cards prioritize three independent states.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-006`.


### 7.7 `UI-12` — Creative Moderation Desk (`ADM13-S-07`)

**Release:** R1. **Personas:** PERS-05/01. **Server authorization:** adpilot_moderate_creatives; emergency stop separately granted. **API routes/contracts:** `CRE-006..009, CAM-012`. **Journeys:** UJ-07, UJ-14.

**Information hierarchy and layout.** Two-column review layout: queue list with submitted time, risk/category, advertiser and immutable creative digest; main pane shows **sanitized image/video/text preview**, normalized destination URL, allowed dimensions, ad disclosure, policy checklist, related campaign and exact review version. Decision pane contains Approve, Request Changes, Reject, with obligatory rejection/changes reason and confirmation. Never execute unrestricted advertiser JavaScript in admin context.

**Actions and interaction states.** Open review, compare previous approved version, inspect safe destination, run automated validation results, enter policy rationale, submit decision with version CAS; send customer communication through Notifications. Moderator cannot modify the advertiser's financial contract during decision. Preview is nonbillable and clearly marked.

**Failure, concurrency, security and financial truth.** If creative digest changed since review began, invalidate decision and show current version. External destination verification failure shows Not verified and explicit manual policy path, never "Safe". If content is prohibited, report policy basis; if evidence insufficient, Request Changes or blocked decision. Rejected paid campaigns invoke separate finance-remedy workflow, not silent refund.

**Frontend and accessibility.** Preview must use hardened renderer/sandbox. Keyboard focus goes to decision outcome, risk warnings have text and shape, image alt is meaningful, video motion controlled, localized rejection reasons and screen-reader alternative exist.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-007`.


### 7.8 `UI-13` — Reservation Booking Calendar (`ADM13-S-08`)

**Release:** R1. **Personas:** PERS-02/04. **Server authorization:** adpilot_view_inventory; actual mutations through reservation authorization. **API routes/contracts:** `RES-004..006, INV-014, CAT-005 (public estimate only)`. **Journeys:** UJ-04, UJ-08, UJ-10.

**Information hierarchy and layout.** Calendar supports day/week/month/agenda and an **accessible chronological list**. Resource filters: zone, slot, campaign, advertiser, payment state, hold, exclusive contract, capacity. Show four distinctly labeled states: estimated availability, temporary hold, firm reservation, delivery-eligible allocation. Availability estimates are advisory and may change. Conflict details include time window, local timezone with DST caveat, exclusivity and contract class.

**Actions and interaction states.** Choose slot/time, inspect date range, view authorized hold/reservation details, navigate to contractual resolution, propose alternative inventory, compare capacity by day. Drag/reschedule is a proposal requiring server validation, never immediate mutation of a paid contract. Show separate overlay for scheduled delivery, not a claim about booking ownership.

**Failure, concurrency, security and financial truth.** When two users compete for exclusivity, one valid reservation wins; loser receives conflict and safe alternatives. Expired hold is not shown as paid inventory. DST ambiguous hour requires explicit timezone/disambiguation. Calendar fetch failure results in Unavailable status rather than free green time slots.

**Frontend and accessibility.** Keyboard date/grid navigation with list-equivalent; contrast and labels for status; long spans paginated/virtualized by interval; tooltips also exposed in focusable detail panels.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-008`.


### 7.9 `UI-14` — Rates, Orders & Financial Reconciliation (`ADM13-S-09`)

**Release:** R1. **Personas:** PERS-06/01. **Server authorization:** adpilot_view_finance, adpilot_view_orders; write gates adpilot_manage_rates / adpilot_adjust_finance / approval. **API routes/contracts:** `PRC-001..004, ORD-007..012`. **Journeys:** UJ-05, UJ-16, UJ-21.

**Information hierarchy and layout.** Three distinct workspaces: **Rate Cards** (draft and immutable accepted versions); **Orders & Invoices** (status, advertiser, currency, tax/total breakdown, provider evidence); **Reconciliation** (ledger receipt, provider settlement, unmatched callbacks, exception queue, immutable adjustments). Keep billing status separate from campaign delivery. Finance role sees payment details while analysts without finance grants do not receive hidden JSON fields.

**Actions and interaction states.** Create/edit/publish new rate version, view locked historical terms, filter orders by status/provider/currency/date, inspect refunds and credit notes, compare discrepancies, propose governed remedies, record financial approval from independent approver if required, request report export. Display minor units safely and avoid floating-point totals in browser arithmetic.

**Failure, concurrency, security and financial truth.** Payment provider event pending, late or repeated must not create success. A failed reconciliation does not become zero revenue. Refunded amount cannot exceed eligible amount per policy; duplicate callback remains one ledger effect. A paid rejected campaign must show documented remedy path. No generic edit amount on immutable ledger rows.

**Frontend and accessibility.** Use tabular numeric formatting, explicit ISO currency, separate base/tax/gross/net and tooltip definitions; sticky filter summary, role-sensitive action menus, readable audit record; screen reader receives precise signed figures and refund descriptions.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-009`.


### 7.10 `UI-15` — Delivery Diagnostics & Incident Center (`ADM13-S-10`)

**Release:** R1. **Personas:** PERS-02/13/01. **Server authorization:** adpilot_view_diagnostics; retry adpilot_retry_jobs; stop adpilot_emergency_stop. **API routes/contracts:** `MET-003, OPS-001..015, CAM-012`. **Journeys:** UJ-14, UJ-17, UJ-27.

**Information hierarchy and layout.** Show current ad decision endpoint health, queue backlog, overdue jobs, stale aggregate watermark, cache invalidation, theme/binding errors, expired campaigns still attempted, fallback percentages, missing required integrations and open incidents. Incidents are classified by site, system owner, severity, first/last seen, affected campaigns and next safe recovery action. Logs are redacted by default.

**Actions and interaction states.** Inspect an incident, filter by error class and severity, open related campaign/zone, run non-mutating diagnostic, retry approved idempotent job class, issue authorized emergency stop, export a secret-free support bundle. Display whether an incident has been acknowledged versus resolved.

**Failure, concurrency, security and financial truth.** If Action Scheduler is down, show delayed scheduled campaigns and actionable cron checks; do not auto-activate past campaigns without current eligibility check. If analytical collector fails, site rendering continues with reporting gap. A queue retry failure records attempt and reason; no infinite spinner or false recovery success.

**Frontend and accessibility.** Use data tables for incidents plus live-region only for escalations, bounded refresh, relative and absolute timestamps, raw trace IDs selectable but scrubbed. Detail panel cannot leak request bodies, API credentials or visitor content.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-010`.


### 7.11 `UI-17` — Aggregated Heatmap Explorer (`ADM13-S-11`)

**Release:** R2 (R4 replay optional separately). **Personas:** PERS-09/01. **Server authorization:** adpilot_view_restricted_analytics for HEAT-*; adpilot_view_replay separately in R4. **API routes/contracts:** `HEAT-001..005 (R2), REP-003; protected analytics entitlement`. **Journeys:** UJ-19, UJ-20.

**Information hierarchy and layout.** Full-page screenshot/representation viewer with layout-version selector, page/device/viewport filters, zone/slot selectors, scroll/click/tap/visibility/attention-proxy modes, opacity and legend, time period, sample count and definition version. A **tabular alternative** lists slot exposure, click rate and precision caveats. Heatmap overlays are grouped by compatible geometry and consent policy—not all visits merged into one absolute coordinate space.

**Actions and interaction states.** Select site page and published layout revision; compare two consistent versions side-by-side or present mismatch explanation; zoom to zone and stable slot; filter aggregated eligible samples; export authorized aggregate if enabled; toggle numerical analysis. Do not label pointer hover or viewport visibility as literal eye fixation.

**Failure, concurrency, security and financial truth.** Insufficient samples -> explicit provisional/unavailable analysis. Changed page geometry -> disallow invalid overlay rather than warp silently. Consent denied -> no optional behavioral collection; reporting completeness disclosed. Session replay option absent until R4, opt-in and extra authorization; no cross-advertiser raw sessions.

**Frontend and accessibility.** Canvas heatmap has numeric text/list fallback and keyboard zoom/slot navigation; safe colorblind/high-contrast palette; legend tied to normalized exposure denominator; lazy-load visualization code only on R2 screen.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-011`.


### 7.12 `UI-18` — Protected Report Exports (`ADM13-S-12`)

**Release:** R2 canonical screen; R1 basic export routes may exist per DOC-009. **Personas:** PERS-09/06/01. **Server authorization:** adpilot_export_reports and download-time authorization. **API routes/contracts:** `REP-005..007 (verify detailed route contract)`. **Journeys:** UJ-23.

**Information hierarchy and layout.** Export job table lists requested by, scope, report definition, filters, authorization policy, created timestamp, state (`queued`, `running`, `completed`, `failed`, `expired`), data watermark, and permitted download action. Sensitive report files are never served as public wp-uploads URLs. Existing basic R1 export API is not sufficient justification to claim the R2 full Export Jobs UI is an R1 requirement.

**Actions and interaction states.** Request report with allowed dimensions, confirm non-sensitive summary, track background processing, inspect error, retry permitted job, download after current permissions recheck, and view expiry. Filter task list without exposing other user/org jobs.

**Failure, concurrency, security and financial truth.** Revoked permission before download -> refuse even if file exists. Job completes after expiration -> needs new authorized request; never revive original signed URL. Background failure -> explain retry vs terminal error. Redacted file previews never reveal confidential rows.

**Frontend and accessibility.** Accessible progress/status changes with timestamps and no fake percentage; table works across mobile and long-running sessions; do not automatically download private exports on job completion.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-012`.


### 7.13 `UI-19` — Metered Billing Disputes (`ADM13-S-13`)

**Release:** R3. **Personas:** PERS-06/01. **Server authorization:** adpilot_view_finance, adpilot_adjust_finance, elevated approval per policy. **API routes/contracts:** `OPS-017 and established commerce dispute contracts`. **Journeys:** UJ-24.

**Information hierarchy and layout.** Dispute workbench contains line-item qualified CPM/CPC measurement, conversion to billable events, original contract terms, anti-fraud adjustments, immutable ledger postings, evidence sources, dispute message history, reviewer decision, remedy proposal and separately approved final execution. Distinguish *counter adjustments* from immutable ledger entries and contract changes.

**Actions and interaction states.** Open case, apply eligible filters, inspect event-qualification evidence, propose adjustment, request independent approval, execute reconciled remedy and send notice. Provide controlled case-state transitions rather than freeform "resolve" toggle.

**Failure, concurrency, security and financial truth.** Missing reliable billable evidence -> no forced resolution; hold case open with investigation. Raw visitor-level traces must not leak to advertisers through dispute UI. Duplicate reconciliation job -> one posting. R3 gate is strict: if metered billing not certified, dispute screen stays disabled.

**Frontend and accessibility.** Money table precision, labeled evidence, accessible compare table, source definitions, redacted event context and full audit timeline; high-risk CTA separate from ordinary navigation.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-013`.


### 7.14 `UI-20` — Settings, Privacy & System Health (`ADM13-S-14`)

**Release:** R1 foundation; R2/R4 settings gated. **Personas:** PERS-01/13. **Server authorization:** adpilot_manage_settings, adpilot_manage_privacy, adpilot_manage_integrations, adpilot_view_diagnostics (per panel). **API routes/contracts:** `OPS-001..018 (individual routes per release)`. **Journeys:** UJ-18, UJ-27.

**Information hierarchy and layout.** Use category sections for Site Configuration, Advertising Policies, User Access, Integrations, Privacy and Consent, Retention, Scheduling/Workers, Diagnostics, Versions/Migrations, and Developer References. Settings are grouped by outcome with danger zones clearly separated. Show configuration origin, last changed by and independent permission for sensitive settings. Secrets are never echoed back; UI uses "configured/not configured" and rotate workflow.

**Actions and interaction states.** Validate dependencies and feature gates, configure allowed defaults and retention through authorized endpoints, inspect current schema/API/layout compiler versions, verify cron/queue state, run diagnostic probes, change allowed policy settings with reason, manage supported integration credentials via masked write-only flow, export safe support bundle. Show schema migration readiness before actions and explain no-WooCommerce mode.

**Failure, concurrency, security and financial truth.** When WooCommerce is missing, AdPilot runs manual/complimentary workflows and integrated checkout is explicitly disabled. When schema migration fails, show read-only degraded status and block unsafe purchase/delivery pathways. Consent collection failures do not enable optional tracking. When setting changes conflict with an active contract, require impact assessment rather than autoapply.

**Frontend and accessibility.** Tabs and sections respect permissions at field and resource level; setting help text cites effect and risk. No unreviewed destructive "reset all" action; responsive forms, keyboard save/cancel, screen reader updates and safe secret input handling.

**Screen-specific completion checks:** (a) privileged and forbidden actor projections differ at the server response, not just the UI; (b) direct URL and stale-tab access enforce current state; (c) all mandatory error/loading/empty states can be reproduced with fixtures; (d) responsive, 400% zoom, RTL and keyboard coverage are evidenced; (e) backend state transitions and event/billing boundaries have integration/E2E proof; (f) current canonical API and design-system contracts are used exactly. **Implementation test:** `ADM13-T-014`.


## 8. Cross-screen operational surfaces (not new canonical UI IDs)

### 8.1 Advertiser organizations administration (UI-11 contextual / campaign operations)

Site-scoped directory for verifying/suspending organizations with `ORG-014..016`. Query only site-authorized organization details; show status, current contracts at risk, pending submissions, audit reasons and related campaigns. Verification requires policy-controlled evidence, explicit actor and reason. Suspension triggers current eligibility re-evaluation and notifies affected campaigns but **does not silently refund or cancel their financial contracts**. A user with advertiser membership in the same org must not cause a publisher role leak. Provide deterministic empty, denied, stale, conflict, and suspend-failure views.

### 8.2 Prioritized administrator inbox

Create a unified **projection of pending actions**, not a new authoritative workflow. Categories: creative approvals, expiring inventory/holds, campaign readiness, payment discrepancies, delivery outages, sold-layout impacts, restricted job failures, and important privacy notices. Every row carries owner role, severity, due time, affected object (within authorization), current status/version, and primary authorized deep link. Dedupe alerts by incident/action correlation ID. Do not present a dismissed notification as equivalent to resolving its underlying event.

### 8.3 Cross-context breadcrumb and context preservation

From `UI-01` → delivery alert → campaign detail → creative review → finance issue, preserve the source navigation context but recheck authorization at every target. Cross-site and cross-organization navigation must not silently switch protected context. Alerts and emails should route through safe site-scoped URLs; no actionable authority embedded solely in URL tokens.

### 8.4 Revenue and inventory relationship views

Provide a side-by-side table of inventory sold, fulfilled time-based allocation, measured exposure, and finance totals as separate columns. It must never imply a causal or mathematical equality between sold-slot count and impressions. A publisher may configure 500 slots and sell only 20; all reports must convey denominators and utilization semantics.


## 9. Detailed operator workflows and exact HTTP choreography

The sequences below are **frontend orchestration**, not permission authority. Route IDs refer to ADP-DOC-009 and request/response format MUST match its OpenAPI-equivalent specification. Every mutation includes appropriate nonce/session credentials for authenticated wp-admin, current authorized site scope, optimistic version (`If-Match` where specified), idempotency key where required, and server-side audit provenance.

### 9.1 Create and publish a 500-slot layout (R1)

```text
Open UI-02:      GET INV-001 → inspect zones, statuses, ETags
Start draft:     POST LAY-002 → draft_id, version
Build plan:      Edit container definitions (INV-007/008), generated sequences (INV-011)
Draft update:    PATCH LAY-004 If-Match = current draft ETag
Validate:        POST LAY-005 → severity, impact IDs, blockers, policy version
Preview:         POST LAY-006 → nonbillable projection, selected viewport
Impact:          GET LAY-011 → affected contractual rights
Resolve:         POST LAY-012 only if actor and independent approval permitted
Publish:         POST LAY-007 with approved impact/reference and If-Match
Observe result:  active published revision, config compiler version, audit
```

**UX:** Editing UI can show an estimated 500-slot blueprint (4 header + 100 sidebar + 196 content + 200 footer), but only authoritative validation may certify the configuration. The UI must not assemble a 500-record REST request for each mouse drag. If publication remains blocked, show a persistent conflict navigator with contract IDs available only to authorized operators. Offer Save Draft, Compare, or Request Remedy; never replace a reserved slot implicitly.

### 9.2 Moderator processes a creative revision (R1)

```text
Queue:           GET CRE-006 with pagination/filter
Open:            fetch exact review version and safe preview
Check:           compare digest, validated destination, category/policy
Decision:        POST CRE-007 OR CRE-008 OR CRE-009 (reason where required)
On 409/conflict: reload exact current revision; no blind retry
Side effect:     campaign readiness recalculated asynchronously/authoritatively
```

**UX:** Payment status is visible only if policy permits and never turned into an approval shortcut. A paid but rejected campaign gets a separate remedy status linked to Finance if authorized; moderator does not post refunds.

### 9.3 Urgent campaign pause or emergency stop (R1)

```text
Open campaign:   GET CAM-009
Inspect:         delivery health + cache freshness + contract status
Pause:           POST CAM-011, or elevated emergency stop CAM-012
Request:         immutable reason, current campaign version, idempotency key
Response:        authoritative stopped/suspended state + pending invalidation
Verify:          subsequent delivery decisions reject ineligible campaign
```

The UI SHALL avoid "paused" success language when the server only accepted an async request; it may show "Stop accepted; propagation check pending" with an eventual authoritative completion state.

### 9.4 Finance reconciles mismatch and proposes remedy (R1)

```text
Investigate:     GET ORD-012 (provider evidence), GET ORD-008 (ledger projection)
Inspect:         order via ORD-007 and audited payment status
Propose:         POST ORD-009 with reason, amounts in integer minor units, currency
Approve:         POST ORD-010 by authorized independent reviewer where policy requires
Execute:         POST ORD-011 by authorized actor with idempotency and evidence
Reopen:          poll authoritative remedy/ledger states; never create fake receipt
```

Quote, hold, order, settlement, moderation, and delivery are separate state projections. A partial or unauthorized remedy is a visible ongoing exception, not a quiet success banner.

### 9.5 AdOps resolves an exclusive booking conflict (R1)

Inspect inventory availability at current selected site, slot and time interval. Show advisory availability and authoritative booked contracts separately. If exclusive conflict exists, link to `RES-004/005`, show allowed alternatives and `RES-006` remedy proposal. Moving a sold slot in Studio requires `LAY-011/012` and an audited publication; dragging an item on the calendar alone SHALL NOT mutate a settled contract.

### 9.6 Delivery incident recovery (R1)

Fetch redacted `OPS-005` and `MET-003`, display classified incident, affected records and current queue lag. Retry approved job with `OPS-006` only after fresh `adpilot_retry_jobs` capability and job provenance check; show accepted retry receipt and subsequently actual result. If payment or capacity consistency is uncertain, flag **unsafe** and prohibit any UI retry path that might repeat commercial side effects.

### 9.7 Heatmap inspection (R2)

Fetch eligible pages via `HEAT-001`; resolve sanitized geometry `HEAT-002` for selected layout revision; fetch aggregated bins `HEAT-003`; optionally compare `HEAT-004` and keyboard-operable element summary `HEAT-005`. All are governed by **`adpilot_view_restricted_analytics`** per ADP-DOC-009, in addition to permitted purpose, privacy restrictions and version checks. Baseline `adpilot_view_aggregate_analytics` alone is **not sufficient for the protected heatmap routes**. No session replay requests exist before R4.

### 9.8 Export sensitive aggregated report (R1 basic API; R2 canonical screen)

Create report job `REP-005` only when permitted, poll status through the established protected export operation, and authorize again at actual download. The original endpoint `REP-007/008` is scoped to owning actor; publisher-specific access resolution must follow the API contract, not an invented admin shortcut. Explicitly show expiry and revoke control where supported, never public file path. UI-18's dedicated Export Jobs screen is an R2 surface; R1 basic export controls may live in permitted report areas.

## 10. Data-fetching, typed API client and state management

### 10.1 Separation of concerns

```text
React Screen / Route
  → Screen Hook (filters, navigation, data source orchestration)
    → Typed API Client (existing ADP-DOC-009 route contract)
      → WordPress REST, authentication and current site scope
        → REST controller → application use case → domain policy → repository

Mutations:
React Action → confirmation/validation → command client
  → current authorization + version checks → authoritative server mutation
    → result / 409 conflict / 403 denial / accepted async work
      → query invalidation + targeted feedback + preserved safe focus
```

Avoid business eligibility predicates embedded in React beyond presentation hints. Do not implement payment success from query parameters, retry count from UI alone, or callback trust based on absence of errors.

### 10.2 Suggested TypeScript contracts

```ts
export type LoadState<T> =
  | { state: 'initial_loading' }
  | { state: 'ready'; data: T; etag?: string; fetchedAt: string }
  | { state: 'ready_empty'; data: T }
  | { state: 'refreshing'; data: T; fetchedAt: string }
  | { state: 'stale_partial'; data: T; reason: string; watermark?: string }
  | { state: 'permission_denied'; safeMessage: string }
  | { state: 'feature_unavailable'; release: 'R2' | 'R3' | 'R4'; reason: string }
  | { state: 'conflict'; currentRevision?: string; safeSummary: string }
  | { state: 'error'; safeCode: string; retryable: boolean; requestId?: string };

export type MoneyDisplay = Readonly<{
  currency: string; // ISO 4217
  minorUnits: string; // decimal integer string; never float
  fractionDigits: number;
  display: string; // server-approved localized representation
}>;

export type MetricView = Readonly<{
  key: string;
  value: string | null;
  unit: string;
  definitionVersion: string;
  denominator?: string | null;
  qualification?: string;
  asOfUtc: string | null;
  unavailableReason?: string | null;
}>;

export type CommandOutcome<T> =
  | { kind: 'committed'; record: T; revision: string }
  | { kind: 'accepted'; jobId: string; state: 'queued' | 'processing' }
  | { kind: 'conflict'; currentRevision?: string; safeMessage: string }
  | { kind: 'forbidden'; safeMessage: string }
  | { kind: 'rejected'; code: string; fieldErrors?: Record<string,string> };
```

These illustrate **frontend-owned state types**, not new REST payload guarantees. `ADP-DOC-009` governs serialized field names and wire format. The adapters shall translate exact API values into typed `LoadState` using strict runtime validation at network boundaries. Do not silently coerce missing money/metrics to zero.

### 10.3 Cache key and invalidation rules

- Cache keys include current trusted site, actor permission scope, feature gate, resource ID and projection version; finance and restricted telemetry never share a public cache partition.
- Access revocation invalidates sensitive query caches at the next authoritative request and proactively clears local UI caches when client detects updated grants.
- Layout publish invalidates zone/list/preview/contract-impact caches; does not mutate historical layout version data.
- Campaign pause invalidates readiness detail and operating lists; public ad selection freshness is independently enforced by the Delivery domain, not React cache rules.
- Payment callback updates invalidate order and finance queries after confirmed provider evidence; optimistic UI may say "processing", not "settled".
- Return from tab suspension / reconnect revalidates high-stakes statuses rather than relying on stale local snapshot.

### 10.4 Mutations and concurrency

`If-Match` / entity versions are sent on draft, layout, rate and relevant campaign modifications where Document 09 requires them. Retry idempotent requests only when the API declares the operation retryable; mutation retries preserve the same idempotency key when repeating the *same* intent. For a new intent or modified amounts, mint a new key. A 409/412 is an opportunity to compare/review, not overwrite by default. Never put credentials, tax IDs, raw customer data or webhook secrets into localStorage or client telemetry.

### 10.5 Data loading architecture

- Central API adapter may use WordPress `@wordpress/api-fetch` for authenticated wp-admin requests when supported, preserving exact API errors and request cancellation.
- Choose either a compatible WordPress data store or an approved query cache abstraction; do not mix competing global stores without an ADR.
- Use route-level code splitting for Layout Studio, Analytics Explorer and other large screens; core dashboard should not eagerly load heatmap or diagram bundles.
- Pagination and search are server driven; debounce typing without delaying keyboard actions; abort superseded fetches.
- Do not make progress UI dependent on optimistic server truth: operation stages are fetched from authoritative job state.

## 11. Frontend package architecture and integration with WordPress

```text
frontend/admin/
  app/
    AdminApp.tsx
    AdminRouter.tsx
    AdminShell.tsx
    route-registry.ts
    capability-aware-nav.ts
  data/
    api-client.ts
    query-keys.ts
    state-adapters.ts
    error-mapper.ts
  features/
    overview/
    inventory/zones/
    inventory/layout-studio/
    inventory/slots/
    inventory/bindings/
    operations/campaigns/
    operations/moderation/
    operations/calendar/
    operations/advertisers/
    commerce/rates/
    commerce/orders/
    commerce/reconciliation/
    delivery/diagnostics/
    analytics/heatmaps/           # R2, lazy loaded
    analytics/exports/            # R2 dedicated screen
    commerce/disputes/            # R3
    settings/privacy/
    settings/health/
    settings/integrations/
  components/
    admin-composites/
  __tests__/

frontend/shared/
  design-system/                  # exactly ADP-DOC-012 tokens/components
  api-contracts/                  # derived from ADP-DOC-009 schemas
  i18n/
  accessibility/

src/Presentation/Admin/
  MenuRegistrar.php
  AssetRegistrar.php
  PageShellController.php
  Rest/                          # controllers delegated to domain services

build/admin/
  overview.js
  inventory.js
  studio.js
  campaigns.js
  commerce.js
  diagnostics.js
  analytics.js
  settings.js
  *.asset.php                     # generated dependency manifests
```

**Production build contract:** Use `@wordpress/scripts` or an approved compatible build pipeline with repeatable dependency manifests and license checks. WordPress admin assets are enqueued only for registered plugin page hooks. PHP must use generated `*.asset.php` dependencies when present and avoid shipping a duplicate React instance. Feature bundles (Studio, heatmaps, large charts) lazy-load only on appropriate authorized screens; source maps are not disclosed with secrets. `@wordpress/ui` is experimental in the current reference and must not become a required unpinned dependency without explicit approval; favor stable WordPress components or first-party accessible composites.

### 11.1 Illustrative wp-admin registration (NOT production-complete)

```php
<?php
add_action( 'admin_menu', function (): void {
    add_menu_page(
        __( 'AdPilot', 'adpilot' ),
        __( 'AdPilot', 'adpilot' ),
        'adpilot_view_aggregate_analytics', // Final menu capability needs multi-role landing review.
        'adpilot-overview',
        'adpilot_render_admin_root',
        'dashicons-chart-area'
    );
} );

add_action( 'admin_enqueue_scripts', function ( string $hook ): void {
    // Real implementation SHALL check a registered AdPilot hook-suffix allowlist,
    // current site capability, release feature flag, and the correct asset manifest.
    // Do NOT enqueue AdPilot dashboard bundles on all wp-admin pages.
} );
```

**Important correction to illustration:** A menu registered with only `adpilot_view_aggregate_analytics` may hide AdPilot from legitimate moderators, finance users or AdOps users. The final implementation MUST use a reviewed parent-menu discovery strategy plus capability-specific destinations, without creating a universal privileged capability that inadvertently grants protected resources. The example is a skeleton to explain hooks, not a recommended final access policy.

### 11.2 Accessible and theme-safe mounting

- Render a single approved React root inside the AdPilot WordPress admin page with a heading and skip target. Preserve wp-admin landmark/navigation semantics.
- Support a non-JS diagnostic fallback for missing build assets so the entire WordPress admin does not white-screen.
- Portal and marketplace code must be packaged separately and not pulled into publisher wp-admin by default.
- Public ad placement CSS must remain scoped and independent from admin styles; admin CSS must not target unrelated core/plugin screens.

## 12. Forms, tables, charts, calendars, dialogs and content interaction patterns

### 12.1 Search/filter grids

Server-side pagination with visible result count, active filter summary and reset control. Sorting is allowlisted and stable. Table rows have explicit click targets and separate action menus; entire row is not a hijacked keyboard-only click surface. Bulk selection shows current page versus all matching, with membership/scope revalidation. Column resizing/hiding cannot remove required state or risk information (e.g. payment, approval, delivery in UI-11).

### 12.2 Forms

Field-level help includes business consequences, validation limits and default reasons. Each save command maps precisely to domain fields, respects `If-Match` and server errors, and produces a localized actionable summary. Dates include time zone; prices include currency and unit. Avoid turning all state changes into one large form: layout publication and financial approvals are distinct reviewed actions.

### 12.3 Time-series, charts and metric definitions

Charts always provide numeric table or download where authorized, original measurement definitions, completeness/watermark, numerator/denominator and meaningful comparison periods. Invalid baselines are labeled. Bars/lines do not use color alone; interactions are keyboard accessible. Legends and axis labels remain readable on small screens. A chart is never a financial ledger source.

### 12.4 Dialogs and destructive actions

Require clear action title, affected entities, consequence, undo availability, authorization, version and specific confirmation language. Focus moves into modal, is trapped appropriately, returns to meaningful origin on close, and is restored after successful mutation. Never close dialog automatically on 409/422. Emergency stop and financial approvals have distinct confirmation styles and separate backend policies.

### 12.5 Calendar

Provide accessible agenda alternative, explicit time zone, overlap explanations, synchronized scroll/keyboard focus, and unambiguous contract status. Drag-and-drop merely creates a proposed change needing backend validation.

### 12.6 Notification semantics

Use inline field notices, task/operation banners and global toasts proportionately. A toast is not a substitute for a durable job status or audit entry. Notifications contain no confidential ledger detail in an unauthorized user's browser. Prevent repeated poll notifications from flooding assistive live regions.

### 12.7 Motion, imagery and loading

Subtle 100–200 ms motion may improve affordance only where acceptable under Document 12 tokens; `prefers-reduced-motion` fully removes decorative motion. No page-wide skeleton bounce. Rich creative previews use safe preprocessed media and explicit content warnings; do not insert unrestricted remote scripts in admin frames. Reserve layout space and avoid shifting actions after data arrives.


## 13. Admin screen-to-API route registry

This section is a **subset and mapping** of existing ADP-DOC-009 routes, not a second API spec. All listed endpoints are namespaced under `/wp-json/adpilot/v1/`. Implementers MUST re-read the endpoint's full request/response, authorization, release and idempotency contract in ADP-DOC-009 before coding. Site-scoped data must never be fetched through advertiser-owned routes merely because response shapes look similar.

| Admin UI | Required routes (canonical ADP-DOC-009 IDs) | Client responsibility |
|---|---|---|
| UI-01 | `REP-001..003`, `OPS-005`, `MET-003` | Merge authorized projections with independent freshness, never merge source authority |
| UI-02 | `INV-001..008`, `INV-013`, `LAY-001` | Search, create draft, edit/lock inventory, occupancy |
| UI-03 | `LAY-001..012`, `INV-007..014` | Draft CAS, nonbillable preview, contract impact, approved publish |
| UI-04 | `INV-009..014` | Cursor page, stable slots, bounded bulk operations |
| UI-05 | `LAY-013..016`, `BLOCK-001..004` | Binding diagnostics and source-aware editor preview |
| UI-11 publisher | `CAM-008..013`, `CRE-006..009` | Site-scoped campaign lists, readiness and elevated stop |
| UI-12 | `CRE-006..009` | Review and moderate exact immutable creative version |
| UI-13 | `RES-004..006`, `INV-013..014` | Contract calendar and safe remedy proposals |
| UI-14 | `PRC-001..004`, `ORD-007..012` | Rate versioning, orders, ledger and reconciliation |
| UI-15 | `MET-003`, `OPS-005..007`, `CAM-012` | Diagnostics, restricted job retry and emergency pause |
| UI-17 | `HEAT-001..005` (R2) | Privileged purpose-checked aggregated visual intelligence |
| UI-18 | `REP-005..008` (R1 APIs, R2 canonical screen) | Protected export lifecycle and reauthorized download |
| UI-19 | `OPS-017` plus approved disputes API contract (R3) | Metered reconciliation; **no invented mutation route** |
| UI-20 | `OPS-001..016` (R1 per route), `OPS-018` (R4) | Feature, settings, roles, privacy, integrations and health, by exact grants |
| Advertiser admin context | `ORG-014..016` | Verification/suspension and scoped organization directory |

### 13.1 Known cross-document API/release issues requiring resolution

1. **Outbound webhook subscriptions:** ADP-DOC-009 proposes R1 `OPS-009..013`; ADP-DOC-008 schedules corresponding subscription storage for R4 and ADP-DOC-010 flags the discrepancy. Do not expose functioning subscription creation until release/storage scope is approved and reconciled in the source documents. Core site health must continue without this optional surface.
2. **UI-18 canonical release:** ADP-DOC-002/012 put dedicated UI-18 in R2, while ADP-DOC-009 has some basic export routes in R1. Permit R1 report exports where source contracts say so; do not mislabel the complete dedicated UI-18 as R1 unless an approved change occurs.
3. **R2 heatmap privilege:** API `HEAT-001..005` requires `adpilot_view_restricted_analytics`; baseline aggregate analytical access is insufficient. Reconcile naming in client policy projection; avoid weakening Document 09.
4. **R3 dispute mutation endpoints:** `OPS-017` is a read endpoint, not a complete case-management CRUD contract. A specific approved API extension is required before implementing R3 dispute mutations. Document 13 must not invent a path.
5. **Parent WordPress menu:** An overly restrictive primary-menu capability could hide valid subordinate tools from moderators/finance users. Architect and test an access-scoped menu discovery + landing solution that cannot confer broad access.

Each issue has an owner (Tech Lead + relevant domain owner), logged ADR/change request, blocker flag, decision deadline before affected work, and acceptance proof.

## 14. Security, trust boundaries and privacy-specific UI requirements

### 14.1 Server authority in every entrypoint

- Current WordPress authenticated identity is resolved on server. Nonces prevent some CSRF classes but are not authorization.
- Query uses `site_id` from trusted runtime/site context. Client-supplied organization, campaign or layout IDs require server ownership resolution.
- Direct URL, exported CSV, preloaded React state, tooltip and browser cache are subject to the same field-level authorization as visible components.
- `switch_to_blog()` never conveys privileges. Support multisite isolated records in R1; network federation is R4.
- No "Admin" checks based on displayed role label or user email address; use real capabilities plus object/state policy.

### 14.2 Financial data protection

- Use exact money minor units or approved decimal string representations. Client formatting is display-only, never billing authority.
- Never auto-execute refund or ledger mutation after a modal close, stale optimistic update, retry or browser Back.
- Escalation may require a separate approver and reason; server must reject self-approval as specified.
- Mask/omit sensitive provider identifiers and tokens in summary screens; use permission-scoped detail retrieval for necessary metadata.
- Audit reason, actor, site, entity, old/new revision, correlation id and reconciliation evidence required for high-impact commands.

### 14.3 Behavioral data and heatmap security

- R1 analytics: qualified aggregates; R2 heatmaps: permission/purpose/consent-aware aggregated view; R4 replay: independently privileged and off by default.
- No raw form values, passwords, payment fields, personal email bodies or unmasked session data in heatmap canvas, exporter, logs, sentry-like integrations, preview fixtures or screenshots.
- Aggregate page/slot analysis must suppress small or unsafe cohorts according to privacy policy; advertiser accounts receive only authorized aggregates and never publisher-restricted sessions.
- Do not claim gaze/eye-tracking measurement from mouse/scroll/viewport proxies.

### 14.4 UI injection and creative safety

- Creative previews must render only sanitized and vetted media. Remote URLs are never trusted for script injection.
- Inner HTML from advertiser copy is not inserted through `dangerouslySetInnerHTML` without explicit approved sanitizer/output policy.
- React JSX escaping is not a substitute for validating URL protocols, media metadata or server-stored HTML.
- Console notifications, alert messages and audit comments are treated as untrusted text.

## 15. Accessibility, internationalization and inclusive review

The **WCAG 2.2 AA target** applies to critical publisher interfaces. Automated tooling is necessary but not sufficient. At minimum: visible focus and meaningful tab order; skip navigation; correctly labeled controls; semantic tables and headings; keyboard actions for slot bulk edits, calendar and layout management; no color-only status; accessible progress for background jobs; predictable error summary/focus; contrast for all semantic token states; reduced motion; high contrast/forced colors; 200%/400% zoom; RTL navigation; and locale-aware numbers/dates.

### 15.1 Keyboard-specific behavioral protocols

| Pattern | Required keyboard contract |
|---|---|
| Navigation tree | Arrow controls if tree ARIA pattern adopted; otherwise native nested links/buttons with Tab |
| Bulk selection table | Space toggles current row checkbox; Shift range selection only if discoverable and tested; selection summary announced |
| Layout canvas | Focusable selected region/slot; keyboard move/reorder commands and structural list alternative |
| Confirmation modal | Focus initial meaningful control, Escape handling when safe, focus return; irreversible actions need explicit reason |
| Calendar | Arrow/date navigation, PageUp/Down if matching documented widget pattern; agenda list parity |
| Charts | Descriptive title, data table, focusable details; no color-only series meaning |
| Heatmap | Keyboard slot list, zoom controls, numerical exposure table and legend alternatives |
| Toast/live updates | Avoid frequent disruptive announcements; critical errors use assertive output sparingly |

### 15.2 RTL / i18n

- Translate all screen labels using WordPress internationalization pipeline; no hard-coded English in production components.
- CSS uses logical properties (`margin-inline-start`, `inset-inline-end`). Do not manually reverse actual date order, financial account codes, email addresses, slugs or URLs.
- International money display uses proper locale/currency formatting but retains exact backend minor units.
- The meaning of `scheduled`, `approved`, `paid`, `held` and `viewable` must remain distinguishable in all translations.
- Form labels expand safely by >30%; role/action menus wrap without covering content.

## 16. UX analytics, instrumentation and privacy-conscious product telemetry

Track **the console's own workflow usability**, separate from public ad performance events. Suggested event types: filter_used, chart_table_switch, dashboard_alert_opened, layout_draft_saved, layout_validation_started/completed, publish_blocked_contract, bulk_operation_summary_opened, moderation_decision_submitted, reconciliation_review_started, incident_retry_requested, heatmap_table_fallback_used, and help_opened. These are product analytics candidates; implementation requires consent/purpose review and must exclude raw free-text reasons, payment info, visitor identifiers and secret values.

Each event uses safe route key, feature flag, coarse device class and duration bucket. Do not collect arbitrary DOM clickstream from administrator screens as a hidden analytics dependency. Consent policies, telemetry disablement and retention must be first-class configuration controls.

## 17. Required test datasets and reproducible fixtures

| Fixture ID | Dataset | Expected stress/failure target |
|---|---|---|
| `ADM13-FX-01` | Clean new site, no WooCommerce | Settings and manual campaign remain operable; online checkout disabled |
| `ADM13-FX-02` | 4 header / 100 sidebar / 196 content / 200 footer | Studio represents 500 configured slots without 500 heavy editable nodes |
| `ADM13-FX-03` | 1,200 logical slots, 40 containers | Cursor pagination, safe scroll, aggregate counts, search and keyboard selection |
| `ADM13-FX-04` | Exclusive overlapping reservations by two purchasers | Only one valid commitment, visible conflict and audit |
| `ADM13-FX-05` | Sold-slot layout move and changed breakpoint | Blocking impact dialog, remedy and revision history |
| `ADM13-FX-06` | Five creative revisions, one approved digest, newer pending | Moderator cannot approve or serve mismatched version |
| `ADM13-FX-07` | Order paid, creative pending, delivery not ready | Three distinct state labels, no false activation |
| `ADM13-FX-08` | Payment callback duplicate and refund mismatch | Ledger singularity and visible review process |
| `ADM13-FX-09` | WordPress role matrix with separate site/finance/moderation actors | Correct routes, server projections, forbidden actions |
| `ADM13-FX-10` | Two multisite sites with colliding opaque resource IDs | No cross-site data in lists, cache, exports, diagnostics |
| `ADM13-FX-11` | Queue stalled, delivery collector down, database read degraded | Partial-data labels and safe incident recovery |
| `ADM13-FX-12` | R2 page geometry v1/v2 and varied device classes | Accurate heatmap grouping and incompatibility warning |
| `ADM13-FX-13` | Consent denied, optional replay off | No session capture or behavioral event leakage |
| `ADM13-FX-14` | 320px RTL, 400% zoom, forced colors, keyboard only | Critical workflows operable |
| `ADM13-FX-15` | Revoked analyst permission during export generation | File download blocked even if generated |
| `ADM13-FX-16` | R1 with disabled R2/R3/R4 gates | No pretend heatmap/dispute/replay controls |

Fixtures must have synthetic media, fake companies and test-only payment provider doubles; never contain copied real customer data. Test events from fixtures are excluded from live billing metrics.


## 18. Automated acceptance and adversarial scenario catalog

**Test identifier namespace:** `ADM13-T-001..` is supplementary to `AT-*`, `UXA-*`, `AR-T-*`, `D05-T-*`, `DS12-T-*` and `E2E-*`. These tests provide administrator-specific frontend evidence and **do not replace** domain tests, external security assessment or financial reconciliation. Use deterministic fake services/clock as appropriate. For any test marked R2–R4, verify the feature gate before running its positive path. Screen-unique completion checks `ADM13-T-001..014` are supplemented by the detailed scenarios below starting at `ADM13-T-015`.

| ID | Area | Given / When | Then / required assertion |
|---|---|---|


| `ADM13-T-015` | NAV — Finance user sees finance navigation and valid landing without aggregate permission | Given Finance-only WP actor; when Load AdPilot top-level menu | Allowed commerce route visible; protected analytics omitted; no elevated grants |


| `ADM13-T-016` | NAV — Moderator lands on review desk rather than denied dashboard | Given Moderator-only actor; when Open AdPilot from WordPress menu | UI-12 accessible with no finance leakage |


| `ADM13-T-017` | NAV — Revoked role closes protected deep link | Given Logged-in AdOps then grant removed; when Refresh direct UI-03 URL | No draft payload returned; safe denied landing |


| `ADM13-T-018` | NAV — Disabled release feature omitted | Given R1-only deployment; when Navigate directly to R2 heatmap | Feature unavailable, no live heatmap bundle/API fetch |


| `ADM13-T-019` | NAV — Browser history restores prior filter | Given Campaign list paged, filter applied; when Open detail and Back | Filter/sort/site context restored safely |


| `ADM13-T-020` | NAV — Two-site multisite context isolation | Given Same opaque ID in two sites; when Follow site-specific deep link | Only target site-authorized record returned |


| `ADM13-T-021` | DASH — Dashboard finance datasource outage | Given Finance adapter throws error; when Load executive summary | Revenue unavailable with ledger freshness; measurement remains usable |


| `ADM13-T-022` | DASH — No-activity is different from network failure | Given Zero campaigns with healthy data; when Load dashboard | Empty-state CTA for authorized actor, metrics show genuine zeros |


| `ADM13-T-023` | DASH — Undefined percentage delta | Given Comparison baseline is zero; when Change compare date range | New activity/N/A not infinity or misleading percent |


| `ADM13-T-024` | DASH — Mixed currencies not summed | Given USD and EUR ledgers; when Apply global total filter | Separate currency totals or approved FX treatment |


| `ADM13-T-025` | DASH — Click KPI expands to definition | Given R1 metric with denomination; when Open tooltip and accessible table | Numerator, denominator, source, watermark shown |


| `ADM13-T-026` | DASH — Alert deep link authorization | Given Analyst lacks campaign manager grant; when Click campaign incident alert | No unauthorized campaign details; safe guidance |


| `ADM13-T-027` | INV — Zone creation validates stable unique identity | Given Two identical proposed IDs; when Save second draft | Conflict with resolution; never overwrite existing zone |


| `ADM13-T-028` | INV — Archive sold zone prohibited | Given Active paid reservation; when Click Archive zone | Contract remediation required; no deletion |


| `ADM13-T-029` | INV — Registry supports stable sorting | Given Thousand zones; when Sort/update cursor pages | No omitted or duplicated records |


| `ADM13-T-030` | INV — Bulk update partial failure visible | Given Some sold and some free slots; when Bulk change rules | Per-slot accepted/rejected count and reasons |


| `ADM13-T-031` | INV — Selection reset on filter change | Given Selected across multiple pages; when Change site/filter | Prompt and cleared/revalidated selection |


| `ADM13-T-032` | INV — 1,200 slot table bounded | Given Large sequence; when Navigate slot page | Bounded DOM and cursor requests, no freeze |


| `ADM13-T-033` | LAY — Create canonical 500-slot mix | Given 4/100/196/200 config; when Generate and preview layout | 500 logical slots; bounded visual preview |


| `ADM13-T-034` | LAY — Layout previews nonbillable | Given Admin editing active zone; when Preview | No production billable or measured impression |


| `ADM13-T-035` | LAY — Sold-slot move blocks publish | Given Active exclusive booking; when Move slot and Publish | Impact summary and blocked authoritative publish |


| `ADM13-T-036` | LAY — Approved remedy still revalidates | Given Approved impact old version; when Change contract state then Publish | Stale remedy rejected, no silent overwrite |


| `ADM13-T-037` | LAY — Concurrent draft conflict | Given Two admin editing sessions; when Publish stale revision | 409/412; diff and reload |


| `ADM13-T-038` | LAY — Keyboard reorder parity | Given Mouse inaccessible; when Use keyboard-only structural edit | Same layout changes and validation possible |


| `ADM13-T-039` | LAY — Responsive layout maintains identity | Given Grid columns change per viewport; when Compare 320/1440 widths | Stable logical slot IDs and distinct render occurrences |


| `ADM13-T-040` | LAY — Publish rollback as new revision | Given Published version changes; when Restore historical layout | New audited version; sold impacts checked |


| `ADM13-T-041` | BIND — Duplicate theme and block binding | Given Same zone mounted twice; when Run diagnostics | Intentional vs accidental repeated placement explained |


| `ADM13-T-042` | BIND — Unknown hook does not claim working integration | Given Theme lacks custom hook; when Validate binding | Actionable warning, no successful live claim |


| `ADM13-T-043` | BIND — Unsafe shortcode argument rejected | Given Malicious/script URL input; when Preview | Escaped/validated safely, no execution |


| `ADM13-T-044` | BIND — Editor actor cannot modify inventory | Given Editor inserts block; when Attempt admin zone PATCH directly | 403 object/capability enforced |


| `ADM13-T-045` | CAM — Separate payment and approval labels | Given Paid order, unapproved creative; when Open campaign | Paid/Under Review/Not serving shown |


| `ADM13-T-046` | CAM — Expired paused campaign cannot resume | Given Contract ended; when Request resume | Current eligibility denial and reason |


| `ADM13-T-047` | CAM — Emergency pause propagation | Given Campaign cached by renderer; when Execute stop | Next decisions reject, UI tracks health |


| `ADM13-T-048` | CAM — Campaign detail cross-org protection | Given Advertiser org membership only; when Request admin campaign detail | No site publisher grant; denied |


| `ADM13-T-049` | CAM — Rejection with paid contract | Given Moderation rejects paid creative; when Open case | Separate commercial remedy visible, no silent refund |


| `ADM13-T-050` | MOD — Approval exact creative digest | Given Creative changed during review; when Submit old approval | Stale digest rejected |


| `ADM13-T-051` | MOD — Reject requires reason | Given Moderator clicks Reject; when Submit blank reason | Validation and focus summary |


| `ADM13-T-052` | MOD — Creative preview blocks executable script | Given Ad creative contains JS; when Load moderation viewer | No first-party arbitrary script execution |


| `ADM13-T-053` | MOD — Destination verification fails visibly | Given Unsafe/timeout URL; when Inspect review | Not verified; no false Safe badge |


| `ADM13-T-054` | MOD — Moderation audit | Given Decision submitted; when Inspect timeline | Actor/digest/reason/time immutable |


| `ADM13-T-055` | CAL — Exclusive double booking contention | Given Two held buyers same interval; when Try simultaneous contract | One valid booking only; UI conflict to loser |


| `ADM13-T-056` | CAL — Expired hold not sold | Given Hold TTL passes; when Refresh calendar | Released/expired status, not paid |


| `ADM13-T-057` | CAL — DST ambiguity explicit | Given Clocks fall back in configured timezone; when Select ambiguous time | Timezone/offset disambiguation and accurate interval |


| `ADM13-T-058` | CAL — Calendar accessibility | Given Keyboard only actor; when Navigate and inspect interval | List equivalent, all status labels accessible |


| `ADM13-T-059` | FIN — Late webhook does not settle expired hold | Given Hold expired before callback; when Inspect order | Unresolved exception/rebook policy, not auto active |


| `ADM13-T-060` | FIN — Duplicate provider callback singular | Given Same signed callback twice; when Review ledger | Exactly one effect, idempotent replay logged |


| `ADM13-T-061` | FIN — Refund and net revenue separation | Given Partial refund posted; when Open report | Gross, refunded and net separately computed |


| `ADM13-T-062` | FIN — Publish accepted rate immutable | Given Quote references prior rate version; when Edit price | New draft version, historical unchanged |


| `ADM13-T-063` | FIN — Segregation of duties | Given Refund proposer is approver; when Try approve own change | Blocked according to policy and audited |


| `ADM13-T-064` | FIN — Financial role cannot approve creative | Given Finance-only actor; when Invoke CRE-007 | 403 and no creative mutation |


| `ADM13-T-065` | DIAG — Queue failure is actionable | Given Cron disabled; when Open health screen | Backlog and safe resolution, no false job success |


| `ADM13-T-066` | DIAG — Unauthorized job retry blocked | Given Support-only actor; when Invoke OPS-006 | 403; queue unchanged |


| `ADM13-T-067` | DIAG — Retry idempotency | Given Job accepted then retry repeated; when Repeat same retry intent | No duplicate money or booking effect |


| `ADM13-T-068` | DIAG — Restricted logs redacted | Given Provider secrets present in internal trace; when Download diagnostics | No secrets/PII in output |


| `ADM13-T-069` | HEAT — R1 has no R2 heatmap view | Given Feature disabled; when Deep link UI-17 | No heatmap permission leakage or script loaded |


| `ADM13-T-070` | HEAT — Aggregate role insufficient for protected heatmaps | Given Only aggregate analytics grant; when Call HEAT-003 | Denied; requires restricted privilege/purpose |


| `ADM13-T-071` | HEAT — Geometry version mismatch | Given Old and new content layouts; when Compare overlays | Mismatched layout warning or separate comparable views |


| `ADM13-T-072` | HEAT — Keyboard and data alternative | Given Canvas not visible; when Operate heatmap via keyboard | Equivalent table and slot selection available |


| `ADM13-T-073` | HEAT — No eye-tracking claim | Given Attention proxy data; when Inspect legend | Visible/engaged time labeled proxy |


| `ADM13-T-074` | HEAT — Consent denied excluded from samples | Given Visitor denied optional collection; when Load aggregate report | No prohibited session data; completeness caveat |


| `ADM13-T-075` | EXP — Export download reauth | Given User revoked after export queued; when Download finished artifact | Access denied, private file not disclosed |


| `ADM13-T-076` | EXP — Expired export invalid | Given Download URL expired; when Retry download | Denied; fresh request required |


| `ADM13-T-077` | EXP — Export job pending not ready | Given Worker delayed; when View export table | Queued/running with watermark, not fake completed |


| `ADM13-T-078` | SET — No WooCommerce boot | Given WooCommerce deactivated; when Open health and campaigns | Plugin works, checkout unavailable |


| `ADM13-T-079` | SET — Privacy denied policy update | Given Support-only actor; when Invoke OPS-016 | 403 and no policy change |


| `ADM13-T-080` | SET — Failed migration blocks unsafe writes | Given Database version incomplete; when Try checkout/publish | Guarded error and recovery, no unsafe commit |


| `ADM13-T-081` | SET — Secret write-only UI | Given Integration credential already stored; when Load settings | Configured/rotate, never plaintext value |


| `ADM13-T-082` | A11Y — RTL and mixed technical values | Given RTL locale; when Inspect money/IDs and dialogs | Logical layout; identifiers and amounts legible |


| `ADM13-T-083` | A11Y — 400 percent zoom critical controls | Given Small viewport + zoom; when Perform campaign stop | No lost button or covered confirmation |


| `ADM13-T-084` | A11Y — Forced colors distinctions | Given High contrast enabled; when Inspect campaign three statuses | Text labels and focus remain discernible |


| `ADM13-T-085` | A11Y — Reduced motion | Given User prefers reduced motion; when Open Studio and dialogs | No essential animation, no lost feedback |


| `ADM13-T-086` | A11Y — Async announcement bounded | Given Fast polling updates; when Listen with screen reader | No excessive aria-live spam |


| `ADM13-T-087` | PERF — Screen specific scripts loaded | Given User opens simple dashboard; when Inspect network | No heatmap/replay/studio mega-bundle |


| `ADM13-T-088` | PERF — Table memory bounded at scale | Given 1,200 slot fixture; when Scroll and select across pages | Bounded DOM, correct selection |


| `ADM13-T-089` | PERF — Privacy-safe telemetry | Given Admin enters refund reason; when Observe UI product analytics | No reason/payment/secret in event payload |


| `ADM13-T-090` | PERF — Restore after offline | Given Mutation posted, network drops; when Reconnect and refresh | Server truth and idempotency, not duplicated mutation |


| `ADM13-T-091` | INTEGR — API required absence flagged | Given Dispute mutation not defined; when Attempt building UI-19 | Blocks implementation pending approved API extension |


| `ADM13-T-092` | INTEGR — Webhook release discrepancy flagged | Given R1 UI vs R4 storage unresolved; when Open integration settings | No unimplemented webhook creation advertised |


### 18.1 Verification modes

- **Unit:** component rendering with tagged data states, formatters, navigation guards, metrics and operation-state reducers.
- **Contract:** typed REST client against ADP-DOC-009 schemas, exact grants, handling of 401/403/404/409/412/422/429/5xx and idempotency.
- **Integration:** real WordPress plugin context with capability changes, multisite current-site isolation, WooCommerce optionality and database fixtures.
- **E2E:** Playwright administrator scenarios with fixtures, scoped roles, force-time, payment double and web accessibility assertions.
- **Visual:** snapshot/screenshot across baseline widths, color themes, RTL, zoom, forced colors; do not use snapshot approval instead of interactive QA.
- **Performance:** profiled high-density admin table and Studio, screen-specific bundle sizes, input latency, memory and network request counts.
- **Security:** privileged API and direct URL access with malicious IDs, CSRF attempts, untrusted creatives, export reauthorization, information disclosure.
- **Manual:** screen-reader and actual keyboard usability on critical workflows, including financial, Studio and heatmap numeric fallback.

### 18.2 Execution proof template

For each test record actual environment, plugin/core versions, site/blog ID, feature gates, role and organization memberships, fixture IDs, test command, result, screenshots/HAR/a11y artifacts (with secrets redacted), known limitations, failing request IDs and linked defect. **Do not mark passed if only a specification or screenshot mock exists.**


## 19. Implementation work packages for GPT-6 High

Do not instruct GPT-6 High to implement all administrator screens in one unbounded change. Each package below should be scheduled only after its canonical dependencies are available. It must declare files to touch, tests to add, UI states to verify, migration implications (if any), and exact result evidence. Packages are sequential by major dependency but independent work may proceed in parallel if API contracts are signed.

| Package | Release | Owner roles | Dependency | Implementation deliverable | Completion evidence |
|---|---|---|---|---|---|


| `ADM13-WP-01` — Admin shell, WP menu, route registry | R1 | WordPress Engineer, React Lead, UX | ADP-DOC-005/009/011/012 | Register conditional parent menu, safe landing, release flags, breadcrumbs, skip link, deep-link and Back support | Permissioned navigation with finance-only and moderator-only fixture, keyboard and RTL |


| `ADM13-WP-02` — Stable design system consumption | R1 | UI Designer, React Engineer, Accessibility | DS12-C-001..049 | Theme-safe CSS tokens, reusable primitives/composites, status semantics, visual stories | Visual regression all screen widths; no global WordPress collisions |


| `ADM13-WP-03` — Typed API and state adapters | R1 | Frontend Lead, API Engineer | ADP-DOC-009 exact envelopes, capabilities | Request client, auth/site scope, runtime validation, 409/412 handling, bounded retries, query cache partitioning | Contract tests with revoked permissions, errors, partial response |


| `ADM13-WP-04` — Dashboard operational homepage | R1 | UX Lead, Analytics, Finance, React | REP-001..003, OPS-005 | KPIs, alerts, freshness and route links, role-safe financial projections | Unavailable ledger not zero, dashboard permission fixtures |


| `ADM13-WP-05` — Zones/containers registry | R1 | React, Inventory Backend, UX | INV-001..008 | CRUD drafts, pagination, occupancy, sold/archive impact | Stable IDs, validation, sold archived blocked |


| `ADM13-WP-06` — Layout Studio tree/canvas/inspector | R1 | Layout Engineer, React, UX, Accessibility | LAY-001..006, ADP-DOC-012 | Desktop 3-pane, compact 500+ slot model, breakpoints, structural keyboard editor | 500/1200 slots bounded DOM, keyboard parity and responsive |


| `ADM13-WP-07` — Contract-aware layout publishing | R1 | Layout Backend, AdTech, Finance, Security | LAY-007..012, BR-006 | Version comparison, blocker dialog, remedy gates, approval/rollback | Sold-slot change blocked until authoritative safe resolution |


| `ADM13-WP-08` — Slot navigator and bulk operations | R1 | React, Inventory Backend, QA | INV-009..014 | Cursor table, across-page selection, per-item result panel | Large datasets, partial failures, stale selection |


| `ADM13-WP-09` — Binding inspector and Gutenberg handoff | R1 | WP Block Engineer, React, UX | LAY-013..016, BLOCK-001..004 | Theme snippets, binding diagnostics, nonbillable previews | Classic/block theme fixtures, no double counting |


| `ADM13-WP-10` — Admin campaign operations | R1 | Campaign Backend, React, AdTech | CAM-008..013 | Separate readiness dimensions, action timeline, emergency stop UX | Paid/unapproved/paused permutations and cache stop |


| `ADM13-WP-11` — Creative moderation desk | R1 | Creative/Moderation Engineer, Security, React | CRE-006..009 | Versioned preview, policy checklist, review decisions, reasons | Stale digest, untrusted media, audit data |


| `ADM13-WP-12` — Booking calendar | R1 | Reservations Engineer, UX, React | RES-004..006, INV-014 | Calendar/agenda, capacity overlays, timezone/DST, conflict remedy | Exclusive race, expired hold, keyboard list |


| `ADM13-WP-13` — Pricing and orders workspaces | R1 | Commerce Engineer, Finance, React | PRC-001..004, ORD-007..012 | Rate versions, order tables, reconciliation, privilege gates | Exact money, duplicate callback, refund truth |


| `ADM13-WP-14` — Organization operations context | R1 | Identity/Advertiser Backend, React, Security | ORG-014..016 | Verification and suspension with authorization and audit | Tenant isolation, no silent financial cancellation |


| `ADM13-WP-15` — Delivery health and diagnostics | R1 | Platform/Queue Engineer, React, Support | OPS-005..007, MET-003 | Incident lists, redacted diagnostics, retry with current provenance | Stalled job, collector down, no unsafe retry |


| `ADM13-WP-16` — Settings and system health | R1 | WP Engineer, Security, Privacy, React | OPS-001..016 as release-approved | Config, privacy, feature gates, cron, secrets rotation, no-Woo path | Capability-based sections, secret masking, migration failure |


| `ADM13-WP-17` — Heatmap Explorer and numerical alternative | R2 | Behavioral Analytics, UX, React, Privacy | HEAT-001..005 | Full-page view, layout geometry, versions, restricted permissions, accessible table | Scroll-long page, version mismatch, denied consent |


| `ADM13-WP-18` — Protected export jobs UI | R2 dedicated screen; R1 baseline APIs | Reporting Engineer, React, Security | REP-005..008 | Status/progress, expiry, reauthorized private download | Revocation between generation and download |


| `ADM13-WP-19` — Metered billing dispute UI | R3 after API approval | Finance/Measurement, React, Security | OPS-017 + approved case mutations | Qualified event evidence, dispute approval, immutable ledger reconciliation | No invented mutation API, no repeat posting |


| `ADM13-WP-20` — Final cross-screen verification and handoff | R1 then per release | QA, UX, Security, DevOps, Tech Lead | All earlier work packages | Automation, accessibility, performance, i18n, licensing and docs | Executed test report; only actually verified claims marked passed |


### 19.1 Standard GPT-6 High task envelope

Every implementation request to GPT-6 High SHALL contain:

1. **Canonical documents:** ADP-DOC-001..013, with exact versions/hashes available to execution environment.
2. **One package:** `ADM13-WP-xx`, clear scope, dependencies and relevant API contracts; no unrelated opportunistic refactoring.
3. **Entry conditions:** installed WordPress/PHP/npm versions, current codebase status, approved decisions, feature flags and fixtures.
4. **Implementation targets:** existing filenames/modules, authorized new files, component names from ADP-DOC-012, server route IDs from ADP-DOC-009.
5. **Invariants:** financial/tenant/privacy/delivery state conditions applicable to the package, with explicit denied paths.
6. **User-visible states:** loading, empty, stale, forbidden, conflict, validation, network and pending-async behavior.
7. **Verification:** exact unit/contract/E2E/keyboard/visual tests expected, including failure injection.
8. **Output evidence:** changed files, test commands actually executed, pass/fail, screenshots/trace artifacts, unresolved decisions, risk and follow-up dependencies.

**Agent operating rules:** inspect code and relevant docs before editing; do not invent a missing REST operation; no fake "production ready" claim; tests may only be marked passed when executed; preserve schemas, roles and API names; escalate upstream spec conflicts through change control.

### 19.2 Package Definition of Done

A package is done only if UI review approved, accessible and responsive states implemented, server data properly scoped, current capability honored, no business logic duplicated in React, API errors and concurrency handled, test evidence recorded, security/privacy review complete, docs and translations updated, and dependency/license checks passed. No outstanding P0/P1 financial, authorization, safety or critical accessibility issue may be waived by UI sign-off.

## 20. Requirements, navigation and test traceability

### 20.1 Screen-to-journey-to-acceptance map

| Canonical UI | Admin owner package | Acceptance anchor | User journey |
|---|---|---|---|


| `UI-01` — Executive Dashboard | `ADM13-WP-04` | `ADM13-T-001` + detailed scenarios | UJ-14, UJ-15, UJ-16, UJ-17 |


| `UI-02` — Zones & Containers Registry | `ADM13-WP-05` | `ADM13-T-002` + detailed scenarios | UJ-09, UJ-10, UJ-22 |


| `UI-03` — Layout Studio | `ADM13-WP-06/WP-07` | `ADM13-T-003` + detailed scenarios | UJ-09, UJ-10, UJ-22 |


| `UI-04` — Slot Navigator & Bulk Operations | `ADM13-WP-08` | `ADM13-T-004` + detailed scenarios | UJ-09, UJ-10, UJ-22 |


| `UI-05` — Template & Theme Binding Inspector | `ADM13-WP-09` | `ADM13-T-005` + detailed scenarios | UJ-11, UJ-12 |


| `UI-11` — Publisher Campaign Operations | `ADM13-WP-10` | `ADM13-T-006` + detailed scenarios | UJ-07, UJ-14, UJ-15 |


| `UI-12` — Creative Moderation Desk | `ADM13-WP-11` | `ADM13-T-007` + detailed scenarios | UJ-07, UJ-14 |


| `UI-13` — Reservation Booking Calendar | `ADM13-WP-12` | `ADM13-T-008` + detailed scenarios | UJ-04, UJ-08, UJ-10 |


| `UI-14` — Rates, Orders & Financial Reconciliation | `ADM13-WP-13` | `ADM13-T-009` + detailed scenarios | UJ-05, UJ-16, UJ-21 |


| `UI-15` — Delivery Diagnostics & Incident Center | `ADM13-WP-15` | `ADM13-T-010` + detailed scenarios | UJ-14, UJ-17, UJ-27 |


| `UI-17` — Aggregated Heatmap Explorer | `ADM13-WP-17` | `ADM13-T-011` + detailed scenarios | UJ-19, UJ-20 |


| `UI-18` — Protected Report Exports | `ADM13-WP-18` | `ADM13-T-012` + detailed scenarios | UJ-23 |


| `UI-19` — Metered Billing Disputes | `ADM13-WP-19` | `ADM13-T-013` + detailed scenarios | UJ-24 |


| `UI-20` — Settings, Privacy & System Health | `ADM13-WP-16` | `ADM13-T-014` + detailed scenarios | UJ-18, UJ-27 |


### 20.2 Exact functional-requirement index (not a reinterpretation)

The 129 `FR-*` identifiers below are extracted from ADP-DOC-001. "Console touchpoint" identifies the owning administration screen or a deliberate **linked/not-owned** interface. Each canonical `AT-*` requirement test remains in ADP-DOC-002 and is **not replaced by these UX tests**. Release labels are copied exactly from the original PRD, including composite labels, without promotion to R1.

| PRD requirement | Release | Console touchpoint / implementation responsibility | Baseline acceptance |
|---|---|---|---|


| `FR-INV-001` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-001` |


| `FR-INV-002` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-002` |


| `FR-INV-003` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-003` |


| `FR-INV-004` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-004` |


| `FR-INV-005` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-005` |


| `FR-INV-006` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-006` |


| `FR-INV-007` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-007` |


| `FR-INV-008` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-008` |


| `FR-INV-009` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-009` |


| `FR-INV-010` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-010` |


| `FR-INV-011` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-011` |


| `FR-INV-012` | `R2` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-012` |


| `FR-INV-013` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-013` |


| `FR-INV-014` | `R1` | UI-02/03/04/05/13 (Inventory and Studio) | `AT-INV-014` |


| `FR-INT-001` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-001` |


| `FR-INT-002` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-002` |


| `FR-INT-003` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-003` |


| `FR-INT-004` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-004` |


| `FR-INT-005` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-005` |


| `FR-INT-006` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-006` |


| `FR-INT-007` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-007` |


| `FR-INT-008` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-008` |


| `FR-INT-009` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-009` |


| `FR-INT-010` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-010` |


| `FR-INT-011` | `R2` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-011` |


| `FR-INT-012` | `R1` | UI-05 (theme/binding diagnostics); native Gutenberg specification separate | `AT-INT-012` |


| `FR-MKT-001` | `R1` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-001` |


| `FR-MKT-002` | `R1` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-002` |


| `FR-MKT-003` | `R1` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-003` |


| `FR-MKT-004` | `R1` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-004` |


| `FR-MKT-005` | `R1` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-005` |


| `FR-MKT-006` | `R1` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-006` |


| `FR-MKT-007` | `R1` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-007` |


| `FR-MKT-008` | `R1` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-008` |


| `FR-MKT-009` | `R1` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-009` |


| `FR-MKT-010` | `R2` | UI-11 contextual; UI-06..10 advertiser/public specification separate | `AT-MKT-010` |


| `FR-CAM-001` | `R1` | UI-11/12 (operations and moderation) | `AT-CAM-001` |


| `FR-CAM-002` | `R1` | UI-11/12 (operations and moderation) | `AT-CAM-002` |


| `FR-CAM-003` | `R1` | UI-11/12 (operations and moderation) | `AT-CAM-003` |


| `FR-CAM-004` | `R1` | UI-11/12 (operations and moderation) | `AT-CAM-004` |


| `FR-CAM-005` | `R1` | UI-11/12 (operations and moderation) | `AT-CAM-005` |


| `FR-CAM-006` | `R1` | UI-11/12 (operations and moderation) | `AT-CAM-006` |


| `FR-CAM-007` | `R1` | UI-11/12 (operations and moderation) | `AT-CAM-007` |


| `FR-CAM-008` | `R1` | UI-11/12 (operations and moderation) | `AT-CAM-008` |


| `FR-CAM-009` | `R2` | UI-11/12 (operations and moderation) | `AT-CAM-009` |


| `FR-CAM-010` | `R1` | UI-11/12 (operations and moderation) | `AT-CAM-010` |


| `FR-COM-001` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-001` |


| `FR-COM-002` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-002` |


| `FR-COM-003` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-003` |


| `FR-COM-004` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-004` |


| `FR-COM-005` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-005` |


| `FR-COM-006` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-006` |


| `FR-COM-007` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-007` |


| `FR-COM-008` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-008` |


| `FR-COM-009` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-009` |


| `FR-COM-010` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-010` |


| `FR-COM-011` | `R3` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-011` |


| `FR-COM-012` | `R4` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-012` |


| `FR-COM-013` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-013` |


| `FR-COM-014` | `R1` | UI-13/14; UI-19 R3 (commerce operations) | `AT-COM-014` |


| `FR-DEL-001` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-001` |


| `FR-DEL-002` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-002` |


| `FR-DEL-003` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-003` |


| `FR-DEL-004` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-004` |


| `FR-DEL-005` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-005` |


| `FR-DEL-006` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-006` |


| `FR-DEL-007` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-007` |


| `FR-DEL-008` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-008` |


| `FR-DEL-009` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-009` |


| `FR-DEL-010` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-010` |


| `FR-DEL-011` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-011` |


| `FR-DEL-012` | `R2/R3` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-012` |


| `FR-DEL-013` | `R1` | UI-11/15 (delivery status/incident); public renderer separate | `AT-DEL-013` |


| `FR-MET-001` | `R1` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-001` |


| `FR-MET-002` | `R1` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-002` |


| `FR-MET-003` | `R1` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-003` |


| `FR-MET-004` | `R1` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-004` |


| `FR-MET-005` | `R1` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-005` |


| `FR-MET-006` | `R1` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-006` |


| `FR-MET-007` | `R2` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-007` |


| `FR-MET-008` | `R2` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-008` |


| `FR-MET-009` | `R3` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-009` |


| `FR-MET-010` | `R3` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-010` |


| `FR-MET-011` | `R3` | UI-01/15/17 R2 (qualified stats and health) | `AT-MET-011` |


| `FR-HEAT-001` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-001` |


| `FR-HEAT-002` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-002` |


| `FR-HEAT-003` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-003` |


| `FR-HEAT-004` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-004` |


| `FR-HEAT-005` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-005` |


| `FR-HEAT-006` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-006` |


| `FR-HEAT-007` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-007` |


| `FR-HEAT-008` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-008` |


| `FR-HEAT-009` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-009` |


| `FR-HEAT-010` | `R4` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-010` |


| `FR-HEAT-011` | `R2` | UI-17 R2; optional R4 replay separately governed | `AT-HEAT-011` |


| `FR-REP-001` | `R1` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-001` |


| `FR-REP-002` | `R1` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-002` |


| `FR-REP-003` | `R1` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-003` |


| `FR-REP-004` | `R1` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-004` |


| `FR-REP-005` | `R2` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-005` |


| `FR-REP-006` | `R3` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-006` |


| `FR-REP-007` | `R3` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-007` |


| `FR-REP-008` | `R2` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-008` |


| `FR-REP-009` | `R2` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-009` |


| `FR-REP-010` | `R1` | UI-01/14/17 R2/18 R2/19 R3 (reports and finance) | `AT-REP-010` |


| `FR-UX-001` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-001` |


| `FR-UX-002` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-002` |


| `FR-UX-003` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-003` |


| `FR-UX-004` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-004` |


| `FR-UX-005` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-005` |


| `FR-UX-006` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-006` |


| `FR-UX-007` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-007` |


| `FR-UX-008` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-008` |


| `FR-UX-009` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-009` |


| `FR-UX-010` | `R2/R4` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-010` |


| `FR-UX-011` | `R1` | All admin UI screens + ADP-DOC-012 design system | `AT-UX-011` |


| `FR-OPS-001` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-001` |


| `FR-OPS-002` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-002` |


| `FR-OPS-003` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-003` |


| `FR-OPS-004` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-004` |


| `FR-OPS-005` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-005` |


| `FR-OPS-006` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-006` |


| `FR-OPS-007` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-007` |


| `FR-OPS-008` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-008` |


| `FR-OPS-009` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-009` |


| `FR-OPS-010` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-010` |


| `FR-OPS-011` | `R4` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-011` |


| `FR-OPS-012` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-012` |


| `FR-OPS-013` | `R1` | UI-20/15 (security/privacy/configuration); background/server contracts separate | `AT-OPS-013` |


### 20.3 Business and nonfunctional coverage

| Canonical source | Admin console implementation interpretation | Responsible proof |
|---|---|---|
| `BR-001..005` | Accurate sellability, expiring holds, payment/review separation, paid rejection remedy, exclusive contracts | UI-11/13/14 contract and concurrency tests |
| `BR-006..010` | Sold layout guard, immutable creative approvals, cache freshness, nonbillable preview, density safeguards | UI-03/05/11/12/15 tests |
| `BR-011..014` | Aggregate advertiser boundaries, retention, metric provenance, exact money | UI-01/14/17/18 + finance/analytics validation |
| `BR-015..018` | UTC/DST correctness, accessibility, external data governance, allocation overrides | UI-03/13/20 + role/elevation tests |
| `INV-01..10` | All fundamental architectural invariants enforced by server and surfaced truthfully | Relevant UI + backend test anchor from DOC-001/004 |
| `NFR-001..016` | Modularity, security/privacy, performance, accessible and localized UI, reliable delivery | Work package quality gates + DOC-002 NFR verification |
| `AC-001..015` | First-release product exit criteria remain unchanged and outside isolated UI ownership | R1 cross-system E2E evidence |
| `E2E-01..17` | Critical release workflows across buying, layout, reporting, security and upgrades | Full-system E2E suite DOC-002 |
| `UJ-01..28`, `WF-01..18`, `UXA-01..28` | Existing user journeys and atomic workflows remain normative | DOC-003 interactions + DOC-011 screen tests |
| `IC-001..024`, `AR-T-001..040` | Domain module boundaries and architecture tests remain binding | DOC-006/007 reviews + contract tests |
| `DS12-C-001..049`, `DS12-T-001..049` | Reuse component design system and visual/a11y verification | DOC-012 component stories/tests |

### 20.4 Architecture contract for changing an existing requirement

If UI implementation exposes a missing route, absent permission, ambiguous payment state or conflict in later-release dependencies: (1) open a change request tied to canonical IDs; (2) identify business, security, data, API, UX and test impacts; (3) obtain required owner approval; (4) revise upstream document first; (5) version downstream UI contract; (6) implement and execute tests. **Do not silently create new routes or use client-only workarounds.**

## 21. Performance and visual quality acceptance

The following are **candidate performance acceptance budgets and benchmarks**, not measured results. Final numeric p75/p95 targets must be established by the dedicated performance document using named hardware, WordPress/PHP versions, browser, host and test data. Meanwhile enforce qualitative hard constraints: no per-slot network fetch in Studio, bounded DOM for 1,200-slot fixture, no heatmap/replay code in R1 dashboard, no leaking finance widgets to unprivileged users, no layout shift covering high-impact actions, protected report queries with cursor pagination and observable data freshness.

| Scenario | Profiling requirement | Blocking symptom |
|---|---|---|
| UI-01 dashboard | Network waterfall, route JS, async widget latency, stale source handling | Entire page blocks on one report, missing data shown as zero |
| UI-03 500-slot Studio | DOM node counts, input responsiveness, memory over repeated edit cycles | 500+ heavyweight slot components mounted at once; keyboard fails |
| UI-04 1,200-slot table | Cursor performance, DOM/windowing, focus and selection continuity | Browser freeze or selection loss |
| UI-13 month calendar | Bounded fetch per interval/resource, keyboard list alternative | Fetches all slots/years or falsely displays vacancy |
| UI-14 finance | Stable numeric precision, role-scoped payload size | Client float arithmetic as final money truth |
| UI-17 R2 heatmap | Separate lazy bundle, geometry and rendering cost, table alternate | R2 code downloaded on every admin page or misleading aggregation |
| UI-20 system health | Sensitive log payload/asset audit | Credentials or PII in diagnostics/client JS |

## 22. Quality gates, reviewer sign-off and exit criteria

| Gate | Minimum evidence | Decision owner |
|---|---|---|
| Product correctness | Screen workflows trace to approved PRD and exact state machines | Product Owner + Business Analyst |
| UX and visual quality | Responsive high-fidelity designs, real copy, all state fixtures, usable keyboard/zoom/RTL | UX Lead + Visual Designer |
| Frontend architecture | Typed client, scoped bundles, component compliance, no duplicated domain logic | React Lead + WordPress Lead |
| Financial integrity | Payment/ledger/review/booking independent, exact money, remedies and retries safe | Finance Lead + Backend Lead |
| Ad operations | Slot identities stable, impact gate, valid delivery and incident behavior | AdTech Lead + Layout Engineer |
| Security/privacy | Tenant-safe APIs, secure previews, export rechecks, consent and role tests | Security + Privacy Leads |
| QA and performance | Actually executed automated + manual results, test fixtures, p75/p95 performance data where specified | QA Lead + Performance Engineer |
| Distribution | Open-source license checks, WP compatibility, no unnecessary external dependencies | DevOps + License Reviewer |

**Release sign-off is conditional:** No severe unresolved authorization, monetary, creative-execution, contractual layout or privacy defect. R2/R3/R4 features are not required for R1 acceptance. No feature is marked fully implemented because this document was authored.

## 23. Open decisions and reconciliation register

| ID | Decision required | Blocking scope | Responsible owner |
|---|---|---|---|
| `ADM13-DEC-01` | Parent menu discovery for distinct publisher roles | WP menu/nav release | WordPress + Security Leads |
| `ADM13-DEC-02` | Exact initial dashboard date-window and widget preferences | UI-01 defaults | Product + UX |
| `ADM13-DEC-03` | Approved per-route client state/query library and version support | All React screens | React + Architecture |
| `ADM13-DEC-04` | Performance budgets under real representative devices | UI-03/04/17 exit | Performance + QA |
| `ADM13-DEC-05` | Published design palette and validated contrast pairs | All screens | UX + Accessibility |
| `ADM13-DEC-06` | Outbound webhook subscription release mismatch (Docs 08/09/10) | UI-20 integrations | Architecture + Platform |
| `ADM13-DEC-07` | Full R3 dispute mutation route contract | UI-19 | Commerce + API + Security |
| `ADM13-DEC-08` | R1 basic export control vs R2 dedicated UI-18 screen | Reporting UX | Product + Reporting |
| `ADM13-DEC-09` | Approved safe creative preview renderer/embed policies | UI-12 | Security + Creative |
| `ADM13-DEC-10` | Verified date/time zone and currency display treatment for billing | UI-13/14 | Finance + UX |
| `ADM13-DEC-11` | Small-cohort heatmap suppression/retention controls | UI-17 R2 | Privacy + Analytics |
| `ADM13-DEC-12` | Multisite admin context landing without federated authority | Site-admin R1 | Security + WordPress |

Until an unresolved decision is approved, implement only unaffected behavior, document blocked routes/controls, and do not invent a default that compromises safety or financial correctness.

## 24. References and future document interface

**Local canonical inputs:** ADP-DOC-001 through ADP-DOC-012 in the same documentation bundle.

**Authoritative WordPress references (verify version-specific behavior during implementation):**

- `@wordpress/components`: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-components/
- WordPress REST endpoint permission callbacks: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
- Script enqueue and dependency manifests: https://developer.wordpress.org/reference/functions/wp_enqueue_script/
- Admin script enqueue hook: https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
- Experimental `@wordpress/ui` (explicit compatibility gate): https://developer.wordpress.org/block-editor/reference-guides/packages/packages-ui/
- WP coding conventions: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/
- WordPress plugin security: https://developer.wordpress.org/plugins/security/

**Next:** ADP-DOC-014 — Advertiser Portal, Marketplace & Campaign Purchasing UI Specification. The next document SHALL reuse this design system and the original UI-06..10 and UI-16 contracts while avoiding an unapproved parallel backend, alternate price calculation or tenant security implementation.

---

**End of ADP-DOC-013.** This is a complete specification candidate, not implementation evidence. Review unresolved decisions and run all actual tests before representing the AdPilot Administrator Console as production ready.
