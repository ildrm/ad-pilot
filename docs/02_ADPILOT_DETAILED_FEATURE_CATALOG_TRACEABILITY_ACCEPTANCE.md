# AdPilot — Detailed Feature Catalog, Requirements Traceability & Acceptance Matrix

**Document ID:** ADP-DOC-002  
**Filename:** `02_ADPILOT_DETAILED_FEATURE_CATALOG_TRACEABILITY_ACCEPTANCE.md`  
**Version:** 1.0.0 (implementation specification)  
**Status:** Proposed — requires Product Owner, Technical Lead, UX Lead, Finance, Security/Privacy and QA sign-off  
**Date:** 2026-10-11  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Baseline:** [ADP-DOC-001 — Product Requirements Document](01_ADPILOT_PRODUCT_REQUIREMENTS_DOCUMENT.md), version 1.0.0  
**Authoring goal:** Make each requirement executable and independently verifiable by an implementation team or GPT-6 High without relaxing the PRD's business, UX, security, performance or privacy constraints.  
**Normative order:** PRD and approved ADRs > this catalog > module design specifications > work packages > implementation choices. Conflicts require an explicit revision, never silent reinterpretation.

---

## 1. Document purpose, coverage, and rules

This document provides a **delivery-ready requirement catalog**, feature dependency model, test obligations, end-to-end acceptance suites and bidirectional traceability. It includes **every functional requirement `FR-*`, every business rule `BR-*`, every nonfunctional requirement `NFR-*`, and every R1 product acceptance criterion `AC-*`** defined in ADP-DOC-001. This is one canonical document, not a replacement PRD. Future specifications may add detail and acceptance scenarios but must not silently alter the requirements or release commitments.

### 1.1 Feature completion model

A feature is *specified* when business behavior, inputs and outputs, data and authorization boundaries, UI states, failure behavior, relevant metrics and tests are defined; *implemented* when code exists behind the approved flag; *verified* when executable tests and UX/accessibility/performance evidence pass; *shipped* only after integration and release approval. An apparently functional screen is **not** evidence that the underlying financial or advertising contract is correct.

The product is **one standalone WordPress plugin**. Rich admin applications may use React/TypeScript; the public ad delivery SDK should be lightweight. WooCommerce is the initial optional online-checkout adapter; absence of WooCommerce must never break plugin bootstrap or publisher-managed complimentary/manual workflows. Native click/scroll/visibility heatmaps must function without Hotjar, Clarity or mandatory hosted analytics. Session replay is optional and disabled by default. Third-party code/assets must be GPL-compatible for distribution, with licenses documented and checked before bundling.

### 1.2 Traceability identifiers and severity

| Item | Format | Meaning |
|---|---|---|
| Source requirement | `FR-INV-001` | Immutable identifier imported from PRD |
| Business rule | `BR-001` | Normative invariant or policy |
| Quality target | `NFR-001` | Cross-cutting nonfunctional requirement |
| Product acceptance | `AC-001` | Release-level acceptance from PRD |
| Detailed test | `AT-INV-001` | Observable, one-to-one requirement test obligation in this document |
| End-to-end scenario | `E2E-01` | Complete cross-module user or operational journey |
| Capability | `CAP-01` | Workstream grouping for planning, ownership and dependency analysis |
| Release | `R1`–`R4` | Product delivery tranche; `R2/R3` means staged enhancements |
| Phase | `P0`–`P12` | Implementation sequencing from architecture plan |

Priority semantics: **P0 safety/integrity** (security, financial, contractual, privacy invariants); **P1 release-critical** (core workflow or usability correctness); **P2 advanced** (per release commitment); **P3 enhancement** (defer only via approval). Priority is not identical to release number. Functional traceability tables preserve release commitments in the PRD.

### 1.3 Ownership and evidence

Each detailed test references the exact `FR-*` and yields one or more of: PHPUnit or PHP integration tests; JavaScript unit/component tests; Playwright E2E; browser/device screenshots and manual UX evidence; accessibility audits; load benchmarks; schema/contract tests; concurrency replay; security/privacy tests; or signed financial reconciliation reports. The implementation must record actual commands, environments and results. Do not fabricate passing evidence.

**Authority roles:** Product Owner (scope), Principal Architect (module interfaces), UX Lead (interaction quality), WordPress Lead (platform compatibility), AdTech Lead (delivery), Finance Lead (pricing/billing), Measurement Lead (metrics), Security Lead (security), Privacy Lead (collection/retention), QA Lead (test obligations), DevOps Lead (reproducibility). For the complete 30-role responsibility roster, use the architecture and staffing baseline from the preceding planning discussion; this document assigns these accountable functions to the feature backlog.

---

## 2. Capability catalog and delivery dependencies

These capabilities organize the feature inventory; **they do not introduce a replacement numbering system for `FR-*`**. The FR-ID groups remain the authoritative line-item requirement list.

| Capability | Purpose / product outcome | Release | Primary FR families | Delivery predecessor |
|---|---|---|---|---|
| CAP-01 — Identity, access and advertiser isolation | Distinct permissions; organization boundaries; safe audit | R1 | MKT, OPS | Bootstrap, schema |
| CAP-02 — Zones and inventory registry | Advertisable zones, containers, slots, sequences | R1 | INV | Core + database |
| CAP-03 — Visual Layout Studio | Scalable editor, versioning, conflict-aware publication | R1 | INV, UX | CAP-02 |
| CAP-04 — Gutenberg and classic/block theme bridges | Four blocks, shortcodes, PHP API, hooks, auto-insertion | R1 | INT | CAP-02, CAP-05 rendering contract |
| CAP-05 — Ad delivery/runtime SDK | Eligibility, batch selection, safe placeholders, lazy fetch | R1 | DEL | CAP-02, CAP-07 |
| CAP-06 — Public advertising marketplace | Browse inventory, compare published offers | R1 | MKT, UX | CAP-02, CAP-08 |
| CAP-07 — Campaigns, creatives and moderation | Independent approval, finance and delivery state | R1 | CAM, MKT | CAP-01, CAP-02 |
| CAP-08 — Rate cards and quotations | Explainable pricing, availability, time/currency terms | R1 | COM | CAP-02 |
| CAP-09 — Reservations and online checkout | Atomic holds, WooCommerce HPOS, payment reconciliation | R1 | COM | CAP-07, CAP-08 |
| CAP-10 — First-party event measurement | Distinct request/render/click events, deduplication | R1 | MET | CAP-05 |
| CAP-11 — Administrator and advertiser dashboards | Status, attribution limits, scoped reporting | R1 | REP, UX | CAP-07, CAP-09, CAP-10 |
| CAP-12 — Heatmaps and scroll intelligence | Full-page and ad-slot visual engagement analysis | R2 | HEAT, MET, REP | CAP-10, CAP-13, layout versions |
| CAP-13 — Viewability and attention proxies | Measurable vs nonmeasurable exposure | R2 | MET, HEAT | CAP-10 |
| CAP-14 — Advanced CPM/CPC and share-of-voice | Metered contracts with validated financial accounting | R3 | COM, MET, DEL | CAP-09, CAP-13 |
| CAP-15 — Attribution, experiments and optimization | Provenance-aware conversions and recommendations | R3 | REP, MET, DEL | CAP-10, CAP-14 |
| CAP-16 — Session replay optional extension | Safely sample permitted visitor journeys | R4 | HEAT, OPS | CAP-12, consent gates |
| CAP-17 — Integrations and enterprise extensions | Optional providers, multisite/network adaptations | R4 | OPS, UX, COM | Stable APIs, isolation |
| CAP-18 — Security, privacy, operations and release governance | Safe distribution, monitoring, upgrades and diagnostics | R1–R4 | OPS, UX | Continuous |

### 2.1 Explicit feature exclusions and boundary decisions

- Supporting 500 configured slots does **not** imply fetching 500 creatives, creating 500 network calls, or counting 500 impressions on page load. An extra-long page is subject to density, accessibility and performance policies.
- An *attention heatmap* is a **measured in-viewport activity proxy**, not gaze, eye tracking, cognitive attention, or biometric inference.
- Self-service payment **requires a configured commerce provider**; in R1, WooCommerce is the integrated online checkout. Manual complimentary/admin-authorized delivery can proceed without it.
- CPC/CPM, credits, spend caps and share-of-voice billing are **R3**, not a shortcut layered onto R1 raw click counters.
- Optional external analytics may supplement, but never be a prerequisite for native R2 heatmaps.
- Uploaded advertiser JavaScript is not permitted unrestricted first-party execution. R1 creative formats are constrained to validated raster/text/native templates.
- Session replay, network federation, external workers and white labeling are not R1 launch blockers; any activated R4 feature has its own security and compatibility gates.

---

## 3. Detailed feature contracts — implementation behavior

The following contracts should be implemented as small vertical slices (domain rule → persistence → API → interface/rendering → tests → documentation). They are deliberately richer than marketing-level feature lists.

### 3.1 CAP-01 — Identity, organizations and capabilities

**Actors:** Anonymous browser, advertiser owner/manager/analyst/billing member, AdOps, finance, moderator, site administrator.  
**Objects:** WordPress user reference, advertiser organization, membership, scoped role assignment, invite, suspension and audit entry.  
**Inputs:** Authenticated actor, organization ID, requested action, subject ID, request nonce/token where relevant.  
**Outputs:** Capability decision; filtered list or mutation; durable audit result for sensitive changes.

**Contract:** WordPress remains the user-authentication authority. The plugin owns advertiser organizations and memberships, with server-side organization ownership checks in addition to WordPress capabilities. An organization member's access must be scoped to that organization's campaigns, drafts, invoices and reports, even if an ID is guessed. Invitations use short-lived, single-use secrets stored hashed where practical. Removal/suspension invalidates subsequent permission checks. Admin preview and support access must not silently impersonate an advertiser or reveal session/raw behavioral records.

**UI:** Onboarding, organization switcher where permitted, team table, invitation flow, access denied/expired invitation/partially completed profile states. **Security:** Object-level authorization and cross-tenant negative tests. **Related:** `FR-MKT-003..004`, `FR-MKT-008..009`, `FR-OPS-001..003`, `BR-011`, `AC-009`.

### 3.2 CAP-02 — Zone, container, slot and sequence model

**Actors:** Site Administrator, AdOps Manager, Layout Studio editor.  
**Objects:** Zone, container, slot, slot sequence, placement binding, immutable layout version, reserved position.  
**Inputs:** Site/page context, zone settings, container type, slot count, breakpoint rules, inventory policy.  
**Outputs:** Validated publishable layout plus stable logical slot identities and binding manifest.

**Contract:** A zone contains containers; a container materializes logical slots from compact configuration. Support single/row/stack/responsive grid in R1; advanced variants follow R2. Slots are **commercial identities**, never ephemeral zero-based DOM positions. A published layout is immutable, and its version must be included in tracking contexts. A breaking change to a sold slot requires contract impact review before publication. Creation of one 200-slot grid must not serialize 200 Gutenberg blocks. Availability and occupancy are not simple booleans: they depend on schedule and capacity. Duplicate logical zone occurrences need unique render-instance identities.

**Bulk control:** Paginate/virtualize the slot inspector; permit range selection, preview impacts, guarded apply, and audit. For 500 slots, the UI must not mount 500 full edit forms. **Related:** all `FR-INV-*`, `BR-001`, `BR-005..006`, `AC-001..002`.

### 3.3 CAP-03 — Layout Studio and responsive visual editor

**Primary UI:** Three-pane workspace (zone tree, visual page canvas, property inspector), plus availability/contract overlay, breakpoint selector, preview vs publish controls, slot navigator and version history.  
**Inputs:** Template or page selection, draft layout, campaign occupancy, breakpoint and geometry model.  
**Outputs:** Draft, validated publication attempt, new immutable version or precise blocking conflict explanation.

**Contract:** Layout Studio edits **configuration**, not publisher theme source. The visual canvas is representative and may show simulated creatives; no preview impression may count toward billing. Selecting a grid with 200 slots opens paginated inspectors and aggregate counts, not 200 draggable nodes. The editor must offer equivalent keyboard/nonvisual editing. Version compare should surface changed slot identity, placement, visibility, capacity and paid contract impacts. Publishing must revalidate against current reservations to prevent time-of-check/time-of-use conflicts.

**UX states:** Empty site → template picker → zone authoring; partial save; validation warnings; unsaved changes; unresolved binding; conflicting contract; live version changed since editing; publish success. **Related:** `FR-INV-007..010`, `FR-UX-003..004`, `BR-006`, `AC-001`, `AC-013`.

### 3.4 CAP-04 — Gutenberg blocks, shortcodes and theme APIs

**Entry points:** `adpilot/ad-slot`, `adpilot/ad-zone`, `adpilot/ad-repeater`, `adpilot/ad-showcase`, documented PHP functions, shortcodes, hooks, compatible widget areas and template parts.  
**Contract:** All paths resolve a common placement-plan and eligibility service. Gutenberg block serialization stores stable references and whitelisted overrides. Server-side block rendering validates attributes and never uses edited preview as paid delivery. Repeaters target supported paragraph/query-loop contexts through explicit adapters, detect duplicate insertion, and respect ad-density policies. Public PHP functions return documented safely rendered markup and never allow theme callers to bypass pricing/moderation rules.

**Theme behavior:** Scoped `.adpilot-*` styles; no global CSS resets or React admin bundles on normal pages. A missing or inactive zone renders a documented empty state instead of a fatal error. Untrusted shortcode parameters cannot select unpublished creatives. **Related:** `FR-INT-*`, `BR-007..010`, `AC-002..003`, `AC-010`.

### 3.5 CAP-05 — Viewport-aware public delivery

**Input:** Page/zone binding, active published layout version, site/locale/context, eligible creative/contract data.  
**Output:** Lightweight accessible placeholders, bounded batch decision responses, selected creative and distinct typed delivery events.  
**Contract:** Compile the page placement plan once per applicable request/cache context. Browser loader observes zones/slots with shared observers; only approaching inventory requests dynamic creatives. Reserve aspect-ratio/dimensions, avoid excessive content shift and support intentional collapse behavior. Enforce server-side creative approval, contract/payment readiness, schedule, allocation and targeting on each decision or through freshness-bounded invalidation. Handle slow networks, failed decisions and JS absence without breaking host content.

**Performance invariants:** One logical slot ≠ one query; one configured ad ≠ one page-load network call; no DOM duplicate IDs for intentional repeated zone rendering. Preserve graceful fallback under shared CDN/full-page caching. **Related:** `FR-DEL-*`, `FR-MET-001`, `NFR-003..005`, `AC-004`, `AC-011`.

### 3.6 CAP-06 — Advertising storefront and self-service purchase

**UI:** Searchable placement catalog, contextual preview, placement detail, availability selector, draft campaign wizard, quote breakdown and checkout handoff.  
**Contract:** Publish only approved inventory metadata and intentionally public prices; do not leak advertiser, visitor, internal yield or financial information. Explain the contract unit (fixed period, exclusivity, etc.), time zone, creative format restrictions, review terms and quote expiry. Forecast reach only if supported by real historical data and visibly label uncertainty. Checkout must revalidate availability and quote freshness.

**Recovery:** Expired quote, occupied slot, unavailable WooCommerce, failed checkout, declined creative, or changed inventory must produce recoverable actionable states. **Related:** `FR-MKT-001..008`, `FR-COM-004..005`, `FR-UX-005`, `AC-005`, `AC-014`.

### 3.7 CAP-07 — Campaign, creative and moderation lifecycle

**Objects:** Campaign, versioned creative, approval review, delivery state, suspension and audit records.  
**Contract:** Maintain three independent state machines: campaign approval, financial readiness and delivery. The system may mark an order paid without marking a creative approved. Approval applies to an identifiable creative version and destination URL. Changing approved assets or destinations forces re-review according to policy, with tightly controlled grandfathering of previously approved versions if allowed. Emergency suspension overrides normal scheduling immediately, with cache invalidation and audit evidence.

**Creative safety:** R1 raster + text/native templates; sanitize labels/URLs, validate uploads and media properties, prohibit unrestricted JavaScript/SVG/HTML. Include sponsored disclosure and appropriate link attributes. **Related:** `FR-CAM-*`, `BR-003..004`, `BR-007..009`, `AC-008`.

### 3.8 CAP-08/09 — Inventory contracts, pricing, reservations and commerce

**Objects:** Versioned rate card, quote, bounded hold, exclusive/capacity allocation, AdPilot order, WooCommerce order reference, callback idempotency key, financial ledger entry.  
**Contract:** Store money in exact decimal/minor units, not floating-point ledgers. Present explicit currency, tax, unit, date interval and timezone; store instants in UTC. Temporary holds expire by TTL; reservation creation uses DB transaction/concurrency discipline and a database-verifiable capacity invariant (especially for overlapping exclusive windows). Product pages and browser redirect success cannot serve as proof of settled payment. WooCommerce HPOS integration must use supported CRUD APIs and verify authoritative payment state. Duplicates/out-of-order callbacks are idempotently reconciled.

**Failure paths:** Double-booking races; expired quote during checkout; late payment after hold expiry; insufficient inventory after payment; refund/cancellation; rejected paid creative; partial fulfillment; reversals; timezone DST boundary; WooCommerce disabled. Policies must say when to restore capacity, keep hold, refund, or send for operator review. **Related:** `FR-COM-*`, `BR-001..005`, `BR-014..015`, `AC-005..007`, `AC-014`.

### 3.9 CAP-10 — Event measurement, validation and metric semantics

**Events:** request, served decision, browser rendered, measurable, viewable (R2), click observed, qualified click, attributed conversion (R3), billable event (R3).  
**Contract:** Validate ingestion schemas, identifiers, server-side context, payload bounds and abuse limits. Deduplicate across retries using bounded event IDs; process events at least once with idempotent aggregation. Exclude editor/staging/load-test data from billable streams. Raw behavioral samples have bounded retention; aggregate reports preserve defined numerator, denominator, time zone and event-quality notes. Unmeasurable ≠ nonviewable; missing data cannot be reported as zero without explanation.

**Financial isolation:** R1 analytics do not create CPC/CPM invoices. R3 billable events must pass contract-specific qualification, independent reconciliation and adjustment logging. **Related:** `FR-MET-*`, `FR-REP-004`, `BR-009`, `BR-012..013`, `AC-004`, `AC-011`.

### 3.10 CAP-11 — Admin, advertiser and financial reporting

**Admin UI:** Campaign status, approval queue, revenue and order state, inventory occupancy, event freshness, operational problems.  
**Advertiser UI:** Their own active contracts, creative/version status, verified impression/click metrics, invoices and notices.  
**Contract:** All reports are scoped by organization and role, with consistent filtering, explicit denominators/measurement coverage and a freshness timestamp. Publisher-only data, operational logs, raw events and session replay are not accessible through ordinary advertiser APIs. Financial dashboards must distinguish gross order amounts, collected cash, refunded amounts, recognized revenue, and remaining contracted delivery.

**Export:** R2 CSV/JSON jobs are queued, authenticated on download and expire; never expose unprotected server file URLs. **Related:** `FR-REP-*`, `BR-011..013`, `AC-009`, `NFR-012`.

### 3.11 CAP-12/13 — Native heatmaps, scroll and viewability intelligence

**Input:** Privacy-permitted event samples, page and layout version, semantic target, stable slot ID, unique render occurrence ID, device/viewport class.  
**Output:** Aggregated click/tap density; scroll-depth distributions; element/slot viewability and time-in-view; optional attention proxy; full-page and zone/slot overlays; numeric alternative.  
**Contract:** A full-page heatmap for a 500-slot page must map the footer and lower content accurately even if geometry changes during scrolling. Normalize coordinates to stable semantic elements where possible and partition by layout version/viewport class. If geometry cannot be mapped, flag/exclude it rather than silently mixing incompatible sessions. Always display sample size, consent/sampling limits, source, denominator and measurement freshness. Do not claim actual gaze tracking.

**Privacy:** Exclude passwords, messages, checkout and form data at collection time. Exclude recording on sensitive pages by default. R4 rrweb-based replay (if selected) is independently consented, sampled, permissioned and purged. **Related:** `FR-HEAT-*`, `FR-MET-007..008`, `FR-REP-005`, `BR-011..013`, R2 acceptance additions.

### 3.12 CAP-14/15 — Metered billing, optimization and attribution (R3)

**Contract:** Each CPM/CPC contract defines price unit, qualification rules, count windows, duplicates, invalid traffic, caps, refunds and reconciliation. A client event by itself can never authorize a financial charge. Attribution requires explicit authorized conversion data, documented window and model. Experiments have randomized assignment or stated alternative, sample size, declared success metric and uncertainty; avoid presenting observational correlation as causal uplift. Forecasts are clearly estimates.

**UI:** Contract editor, billable-event reconciliation queue, adjustment/dispute workflow, campaign performance comparisons, experiment panel, recommendation rationale. **Related:** `FR-COM-011`, `FR-MET-009..011`, `FR-REP-006..007`, `BR-012..014`, R3 acceptance additions.

### 3.13 CAP-16/17 — Optional replay, integrations and multisite extensions (R4)

**Contract:** Replay is off by default and must not record sensitive DOM/form values; event access has dedicated elevated capabilities. External providers use adapters that can fail independently; credentials remain protected. Multisite requires site-scoped inventory, organizations and keys unless an explicitly reviewed federation feature is enabled. No cross-site data leakage under a shared network administrator UI. White label changes visual identity only, not audit/sponsorship/security requirements.

**Related:** `FR-HEAT-010`, `FR-COM-012`, `FR-OPS-011`, `FR-UX-010`, `NFR-008..014`.

### 3.14 CAP-18 — Platform safety and release lifecycle

**Contract:** Registration and route access have explicit capabilities; all object retrieval checks organization/site scope. Inputs are validated, SQL is prepared, output escaped by context, uploads restricted, and remote requests protected against SSRF. Durable audit logs record financial changes, approval, site layout publication and membership changes. Privacy exporters/erasers, consent adapters, retention jobs, safe logging, diagnostics, reproducible builds, GPL-compatible license inventory and migration safety are release requirements—not later polish.

**Failure behavior:** Missing WooCommerce, failed scheduler, unavailable analytics, expired API keys and incompatible environments fail predictably and do not render WordPress admin unusable. Deactivation preserves stored campaigns. Explicit uninstall policy governs irreversible removal. **Related:** all `FR-OPS-*`, `NFR-*`, `AC-011..015`.

---

## 4. Screen catalog and UI state contracts

| Screen ID | Surface / primary task | Required actions and states | QA evidence |
|---|---|---|---|
| UI-01 | Admin executive dashboard | Filterable revenue, booked/active counts, alerts, freshness, loading and degraded state | Layout, authorization, accessibility |
| UI-02 | Zone registry | Create/clone/archive/search, status, link to Layout Studio, empty and conflict states | CRUD/permission E2E |
| UI-03 | Layout Studio | Three-pane workspace, responsive preview, 200/500-slot summaries, impact review, publish/rollback | Layout contract, keyboard, performance |
| UI-04 | Slot navigator | Virtual/paginated records, bulk edit, occupancy, reserved contract lock indicator | 500/1,000-slot fixture |
| UI-05 | Template binding inspector | Gutenberg/hook/PHP/shortcode bindings, duplication warning, missing-target diagnostic | Theme matrix |
| UI-06 | Ad marketplace catalog | Filters, compare, availability, published pricing, empty/no-result states | Anonymous browse and privacy audit |
| UI-07 | Placement detail | Creative dimensions, responsive preview, rate terms, time zone, estimation notices | Content and WCAG review |
| UI-08 | Advertiser onboarding | Create organization, verify required fields, invitation and recovery | Invite/ownership tests |
| UI-09 | Campaign wizard | Autosave, step validation, placement selection, creative and quote breakdown | Happy/error/reload E2E |
| UI-10 | Checkout handoff | Reservation expiry, provider availability, failure/retry and clear payment state | Webhook idempotency E2E |
| UI-11 | Campaign list/detail | Independent approval, financial and delivery badges, timeline, allowed actions | State machine + access tests |
| UI-12 | Creative moderation | Media preview, destination, policy checklist, decision reason, emergency stop | Safe creative and audit tests |
| UI-13 | Booking calendar | Occupied/capacity intervals, alternative date, conflict details, keyboard calendar controls | DST/concurrency tests |
| UI-14 | Rate cards and orders | Versioned prices, financial status, invoices, reconciliation alerts | Decimal and order tests |
| UI-15 | Ad delivery diagnostics | Endpoint failure, fallback, event lag, campaign readiness, role-safe actions | Failure injection |
| UI-16 | Advertiser reporting | Authorized aggregate metrics, filters, denominators, nonmeasurable notes | Cross-tenant isolation |
| UI-17 | Heatmap explorer (R2) | Full-page version/device overlay, filters, samples, numeric view | Long-page/consent tests |
| UI-18 | Export jobs (R2) | Request, queued/running/done/error, expiring secure downloads | Job and auth tests |
| UI-19 | Billing dispute (R3) | Qualified event history, adjustments, review and resolution | Ledger audits |
| UI-20 | System settings/health | Dependency status, retention, privacy, jobs, licensing, version checks | Admin role and diagnostic tests |

**Every primary screen** must specify skeleton/empty/loading/error/success/permission-denied/incomplete-data states; offer keyboard access and a nonvisual equivalent for visual-only information. Never use a green check or success screen to assert that payment settled before an authoritative provider confirmation. Editor and frontend preview screens must be identifiable as nonbillable.

---

## 5. API, persistence, and event contracts by feature family

The following are **conceptual endpoints and domain objects**, not completed API specifications; downstream documents must formalize field schemas, error codes, pagination, idempotency and authentication.

| Family | API / PHP integration surface | Storage authority | Events / side effects |
|---|---|---|---|
| Inventory (INV) | `GET/POST /zones`, `/containers`, `/slots`, `/layouts/{id}/versions` | Versioned layout and inventory tables | `LayoutPublished`, `SlotArchived` |
| Integration (INT) | PHP `adpilot_render_*`; four block types and shortcode registration | WordPress content + placement bindings | `RenderPlanResolved`, diagnostic only on preview |
| Marketplace (MKT) | `/advertisers`, `/organizations`, `/catalog`, `/campaigns/drafts` | WP users + organization/membership records | `OrganizationCreated`, `DraftSaved` |
| Campaign (CAM) | `/campaigns`, `/creatives`, `/moderation` | Campaign, creative and audit stores | `CreativeSubmitted`, `CampaignApproved`, `CampaignSuspended` |
| Commerce (COM) | `/rates`, `/quotes`, `/reservations`, `/orders` | Quotes, reservations, order mapping, immutable financial entries | `HoldCreated`, `PaymentConfirmed`, `RefundReconciled` |
| Delivery (DEL) | `/delivery/decisions`; public PHP renderer | Selection/campaign snapshot caches, allocations | `DecisionServed`, lightweight fallback |
| Measurement (MET) | `/delivery/events` ingest; private aggregation APIs | Short-retention events + aggregates | `EventQualified`, `AggregateUpdated` |
| Heatmap (HEAT) | `/heatmaps`, `/layouts/snapshots`; visitor SDK | Aggregates, geometry snapshots, bounded samples | `HeatmapBatchAggregated` |
| Reporting (REP) | `/reports`, `/exports` | Aggregates, scoped export jobs | `ExportRequested`, `ExportCompleted` |
| Experience (UX) | Admin and portal React clients | Client-only UI state + safe persisted drafts | UX navigation, no financial authority |
| Operations (OPS) | `/diagnostics`, WP-CLI, hooks/adapters | Settings, audit, queue records | `JobFailed`, `MigrationCompleted` |

**Nonnegotiable trust rules:** REST nonces are not authorization; verify capability **and** object ownership. Financial/payment and inventory changes are authoritative on the server. Client viewport events are observations, not proof of billable value. All query, aggregate, export and replay endpoints require scoped access. Public dynamic decisions must not expose private advertiser/campaign or visitor data to shared caches.

---

## 6. Bidirectional functional requirement traceability matrix

This is the canonical **129-row** mapping from PRD requirements to implementation focus, release, accountable team, and unique acceptance obligation. The requirement text is preserved verbatim from ADP-DOC-001 (removing only Markdown line-break syntax). For each `AT-*`, the detailed test focus is an **additional obligation**, not a replacement for the full requirement. A test passes only when every clause in its referenced `FR-*` passes.

- `AT-*` tests are **planned**, not executed or passed.
- Accountable delivery functions and independent reviewers are stated at each group heading.
- Work phases are derived from the delivery plan. `P1+all` and `P2+all` mean continuous parallel work.
- If release tags span releases, the advanced subfeature is deferred until its assigned release-specific gate.

### 6.1 Inventory and high-density layouts — 14 requirements

**Accountable:** WP backend + Database. **Required review:** AdOps, UX, Security. **Default phase:** P3.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-INV-001` | R1 | Authorized administrators SHALL create, rename, archive, clone and search zones with stable unique IDs and descriptions. | **`AT-INV-001`:** Create, rename, archive, clone and search zones; ensure identity persists and unauthorized writes fail. |
| `FR-INV-002` | R1 | Zones SHALL contain multiple containers; containers SHALL support single, row, vertical stack and responsive grid configurations. | **`AT-INV-002`:** Render multiple single, row, stack and grid containers inside one zone with validated nesting. |
| `FR-INV-003` | R1 | Administrators SHALL configure slot count without a hard-coded 500-slot product maximum. Tested baseline: 500 logical slots on one page; stress test: 1,000+. Hosting-specific safe limits MAY be enforced with transparent diagnostics. | **`AT-INV-003`:** Persist and reopen 500 slots; stress 1,000+; enforce only explicit resource limits. |
| `FR-INV-004` | R1 | A single container SHALL generate 100 sidebar or 200 footer slots from a compact configuration, not 100/200 manually persisted Gutenberg blocks. | **`AT-INV-004`:** Generate 100 sidebar and 200 footer logical slots from one config each, without block duplication. |
| `FR-INV-005` | R1 | Responsive layouts SHALL support desktop/tablet/mobile column count, minimum width, aspect ratio, gaps and visibility rules without mutating contractual slot identity. | **`AT-INV-005`:** Change breakpoint columns and spacing; preserve paid slot identity and layout semantics. |
| `FR-INV-006` | R1 | The system SHALL resolve placement bindings for posts, pages, archives, search contexts, product pages where integrated, template parts and approved custom hooks. | **`AT-INV-006`:** Bind a zone separately to page, archive, product, template part and custom hook contexts. |
| `FR-INV-007` | R1 | Administrators SHALL preview zones and slots with availability, reservation occupancy, breakpoint and empty-state information. | **`AT-INV-007`:** Preview occupancy and empty states at three breakpoints without production tracking. |
| `FR-INV-008` | R1 | Layout edits SHALL support draft, preview, validate, publish, immutable version history and controlled rollback. | **`AT-INV-008`:** Publish versioned layout atomically, compare revisions and recover previous version safely. |
| `FR-INV-009` | R1 | Changes affecting reserved/sold inventory SHALL warn, block or initiate explicit migration/reapproval before publication. | **`AT-INV-009`:** Attempt to remove sold position; block unsafe publication or require audited contract migration. |
| `FR-INV-010` | R1 | Administrators SHALL bulk assign, unassign, edit, duplicate and filter generated slots using paginated/virtualized controls. | **`AT-INV-010`:** Bulk change 200 slots with paginated controls; verify scoped atomic results and undo policy. |
| `FR-INV-011` | R1 | Slots SHALL have explicit inactive, eligible, reserved, occupied and unavailable states where applicable. | **`AT-INV-011`:** Show accurate slot status while campaign and reservation states change. |
| `FR-INV-012` | R2 | Container types SHOULD extend to carousel, masonry, sticky and conditional/repeater presentations with accessibility and density constraints. | **`AT-INV-012`:** Exercise approved carousel/masonry/sticky variants after R2 enablement; maintain accessibility. |
| `FR-INV-013` | R1 | On empty eligible inventory, configurable house creative, collapsed placeholder or reserved blank layout space SHALL be possible without falsely counting impressions. | **`AT-INV-013`:** Apply house creative, collapse or placeholder for empty stock; count no false impressions. |
| `FR-INV-014` | R1 | The system SHALL detect duplicate unintended injection and distinguish it from permitted repeated rendering. | **`AT-INV-014`:** Detect accidental repeated hook injection; permit intentional duplicates with unique render IDs. |

### 6.2 Gutenberg and theme integration — 12 requirements

**Accountable:** Gutenberg + WordPress. **Required review:** Frontend, Accessibility, Security. **Default phase:** P4.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-INT-001` | R1 | Register dynamic blocks `adpilot/ad-slot`, `adpilot/ad-zone`, `adpilot/ad-repeater`, `adpilot/ad-showcase` using `block.json` and server-rendered output. | **`AT-INT-001`:** Register four dynamic blocks and verify server renders from saved attributes. |
| `FR-INT-002` | R1 | Each block SHALL expose relevant configuration, accessible editor controls, responsive preview and bounded representative rendering; editor previews SHALL not count as paid ad delivery. | **`AT-INT-002`:** Exercise inspector, keyboard controls, responsive editor preview and nonbillable editor mode. |
| `FR-INT-003` | R1 | `adpilot/ad-zone` SHALL store a compact stable zone reference and permitted overrides instead of one block per generated slot. | **`AT-INT-003`:** Inspect serialized post content for one compact zone reference instead of 200 blocks. |
| `FR-INT-004` | R1 | Repeater behavior SHALL support explicit interval policies for eligible content contexts; integrations into query loops, product grids and third-party layouts SHALL be adapter-controlled, not assumed universal. | **`AT-INT-004`:** Insert at eligible paragraph intervals; validate adapter behavior and unsupported query context. |
| `FR-INT-005` | R1 | Provide public PHP functions `adpilot_render_slot()`, `adpilot_render_zone()`, and `adpilot_render_container()` with stable, documented argument and return/error semantics. | **`AT-INT-005`:** Invoke all three public PHP functions with valid, absent and malformed arguments. |
| `FR-INT-006` | R1 | Provide corresponding shortcodes, e.g. `[adpilot_slot]`, `[adpilot_zone]`, `[adpilot_repeater]`, and `[adpilot_showcase]`. | **`AT-INT-006`:** Execute four shortcodes, including invalid attributes, with no unauthorized bypass. |
| `FR-INT-007` | R1 | Provide extensible action/filter hooks and a capability-gated REST API; all integration paths SHALL invoke the same authorization/eligibility/rendering core. | **`AT-INT-007`:** Prove Gutenberg, PHP, shortcode and hook paths reach one eligibility and authorization core. |
| `FR-INT-008` | R1 | Support classic themes, block themes, template parts and traditional widget areas through appropriately scoped adapters. | **`AT-INT-008`:** Render across classic theme, block theme, template part and classic widget area. |
| `FR-INT-009` | R1 | Avoid overriding theme global styles; use scoped styles and namespaced classes/CSS variables. | **`AT-INT-009`:** Compare theme global styles before and after loading plugin frontend CSS. |
| `FR-INT-010` | R1 | No shortcode, Gutenberg parameter or PHP template option SHALL bypass purchased inventory, creative approval, commercial restrictions, or security rules. | **`AT-INT-010`:** Inject disallowed campaign and targeting options; verify server rejects overrides. |
| `FR-INT-011` | R2 | Provide layout/theme diagnostics for unresolved hooks, unsupported selectors, duplicates and rendering conflicts. | **`AT-INT-011`:** Identify broken theme hook, conflicting selector and duplicate insertion with actionable diagnostics. |
| `FR-INT-012` | R1 | Documentation SHALL give minimal working examples for each supported theme integration path. | **`AT-INT-012`:** Run documented sample snippets in reference theme fixture without warnings. |

### 6.3 Marketplace and advertiser organization — 10 requirements

**Accountable:** Product UX + Backend. **Required review:** Frontend, Security, Finance. **Default phase:** P5.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-MKT-001` | R1 | Advertisers SHALL browse a public, filterable catalog of published placements and packages, with dimensions, format eligibility, price, schedule and clear terms. | **`AT-MKT-001`:** Browse catalog anonymously; filter by format, price, dates, template and device without leakage. |
| `FR-MKT-002` | R1 | Published placement pages SHALL include sanitized preview, responsive context and measured historical estimates when available, labeled as estimates rather than guarantees. | **`AT-MKT-002`:** Present a safe creative mockup and label extrapolated audience data as estimates. |
| `FR-MKT-003` | R1 | Authorized advertisers SHALL register, manage business profiles, view their campaigns, invoices and notifications, and resume saved drafts. | **`AT-MKT-003`:** Create account, save business profile, draft campaigns and access own billing data. |
| `FR-MKT-004` | R1 | Advertiser organizations SHALL support owner/manager/analyst/billing membership with least-privilege permissions and secure invitations. | **`AT-MKT-004`:** Invite and revoke members; enforce owner, manager, analyst and billing scopes server-side. |
| `FR-MKT-005` | R1 | Campaign wizard SHALL guide placement selection, creative upload, targeting where allowed, scheduling, quote review, submission and checkout. | **`AT-MKT-005`:** Complete campaign wizard from placement choice through quote and approved checkout path. |
| `FR-MKT-006` | R1 | The wizard SHALL provide validation and clear recoverable errors, maintain drafts and preserve state during normal navigation. | **`AT-MKT-006`:** Interrupt every wizard step; reload draft; display inline recovery for invalid data. |
| `FR-MKT-007` | R1 | Advertisers SHALL see distinguishable approval, payment and delivery states. | **`AT-MKT-007`:** Show independent payment, approval and delivery status in portal and notifications. |
| `FR-MKT-008` | R1 | Public catalog SHALL expose no advertiser-private, financial-sensitive or visitor-personal records. | **`AT-MKT-008`:** Inspect anonymous catalog response for customer financial, private and visitor data. |
| `FR-MKT-009` | R1 | Administrators SHALL manage advertisers, verify organizations where configured, suspend accounts, and audit changes. | **`AT-MKT-009`:** Verify, suspend and audit advertiser organization; active policy applied on next request. |
| `FR-MKT-010` | R2 | Campaign renewal, duplication, packaged purchasing and organization reporting enhancements SHOULD be available. | **`AT-MKT-010`:** Use renewal, duplication, bundles and organization report only after R2 feature enablement. |

### 6.4 Campaigns, creatives and moderation — 10 requirements

**Accountable:** Campaign Backend + Moderator. **Required review:** AdTech, Security, UX. **Default phase:** P5.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-CAM-001` | R1 | Campaigns SHALL have separately managed approval, financial and delivery state machines. | **`AT-CAM-001`:** Confirm campaign approval, commerce and delivery state machines cannot be conflated. |
| `FR-CAM-002` | R1 | Campaigns SHALL support drafts, submission, review, changes requested, approval, rejection, schedule, activation, pause, completion and cancellation/suspension paths. | **`AT-CAM-002`:** Exercise allowed and disallowed transitions, retry and concurrent pause/resume. |
| `FR-CAM-003` | R1 | Creatives SHALL support safe raster images and text/native ad templates; file dimensions, format, size, destination URL and alt text SHALL be validated. | **`AT-CAM-003`:** Upload permitted raster/text creatives; reject wrong type, size, URL and missing required alt. |
| `FR-CAM-004` | R1 | Advertisers SHALL upload multiple creative versions and request edits; policy-relevant changes require reapproval before serving. | **`AT-CAM-004`:** Edit approved destination/creative and verify review state plus version isolation. |
| `FR-CAM-005` | R1 | Moderators SHALL approve/reject with reasons, maintain audit trails and support emergency stop. | **`AT-CAM-005`:** Approve/reject with notes; emergency stop removes eligibility and writes audit evidence. |
| `FR-CAM-006` | R1 | Paid placement SHALL not imply automatic creative approval; rejection SHALL invoke documented revision, cancellation and refund policies. | **`AT-CAM-006`:** Pay before approval then reject; verify correction/refund policy without implicit activation. |
| `FR-CAM-007` | R1 | Native/sponsored formats SHALL expose sponsorship disclosure and appropriate link attributes. | **`AT-CAM-007`:** Inspect sponsored disclosures and link rel policy across blocks, API and theme renders. |
| `FR-CAM-008` | R1 | Arbitrary advertiser-controlled JavaScript, unsanitized SVG and unrestricted HTML SHALL be prohibited. Safe interactive/sandboxed formats may be separately designed for later release. | **`AT-CAM-008`:** Attempt active JavaScript, unsanitized SVG and unrestricted HTML; verify safe rejection. |
| `FR-CAM-009` | R2 | Video and interactive advertising MAY be supported subject to explicit format, security, performance and accessibility acceptance tests. | **`AT-CAM-009`:** Run video/interactive fixture only once separate R2 policy and performance gates pass. |
| `FR-CAM-010` | R1 | Campaign audit trail SHALL record high-value actions including owner, time, previous/new state, and safe explanation. | **`AT-CAM-010`:** Validate immutable audit record content, actor, timestamp and redaction. |

### 6.5 Pricing, reservations and commerce — 14 requirements

**Accountable:** Commerce + Finance. **Required review:** Database, Security, QA. **Default phase:** P6.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-COM-001` | R1 | Support fixed-price, time-based and exclusive sponsorship price plans with clear units and rate-card version. | **`AT-COM-001`:** Quote fixed, daily/weekly and exclusive contracts with correct time and pricing units. |
| `FR-COM-002` | R1 | Availability SHALL be calculated from binding, capacity, exclusions, reservations and active allocations for the requested period. | **`AT-COM-002`:** Account for disabled bindings, blackout intervals and overlapping allocations in availability. |
| `FR-COM-003` | R1 | Reservation creation SHALL be concurrency-safe, have bounded expiry and be idempotent on retries. | **`AT-COM-003`:** Race concurrent exclusive holds; prove one winner, TTL expiry and duplicate retry identity. |
| `FR-COM-004` | R1 | Quote SHALL include currency, units, dates/time zone, inventory, discounts, taxes where configured, expiration and applicable approval/refund conditions. | **`AT-COM-004`:** Inspect money, dates, taxes, discount, terms and expiry shown in a frozen quote. |
| `FR-COM-005` | R1 | WooCommerce adapter SHALL map AdPilot commercial orders to WooCommerce orders using supported WooCommerce CRUD/HPOS-compatible APIs. | **`AT-COM-005`:** Complete WooCommerce HPOS checkout; verify stable two-way order reference mapping. |
| `FR-COM-006` | R1 | Payment status SHALL be obtained only from authoritative commerce/gateway callbacks, not browser redirect success alone. | **`AT-COM-006`:** Fake browser return success without verified settlement; campaign remains unpaid. |
| `FR-COM-007` | R1 | Duplicate, delayed and out-of-order callbacks SHALL not double-allocate inventory or duplicate invoices/charges. | **`AT-COM-007`:** Replay stale and duplicate callbacks; prove no duplicate reservation, invoice or charge. |
| `FR-COM-008` | R1 | Refunds, cancellation, payment reversals, expired quotes and declined creatives SHALL have explicit policy-governed workflows. | **`AT-COM-008`:** Exercise partial refund, reversal, expired quote and creative rejection policy. |
| `FR-COM-009` | R1 | Financial records SHALL maintain auditable adjustments and reconciliation against commercial orders. | **`AT-COM-009`:** Reconcile ledger adjustments with order state; validate decimal-safe monetary results. |
| `FR-COM-010` | R1 | Administrators SHALL view order, payment, reservation and fulfillment discrepancies. | **`AT-COM-010`:** Inject payment/reservation mismatch and display administrator actionable reconciliation. |
| `FR-COM-011` | R3 | Qualified, reconciled CPM/CPC, spending caps, credits and share-of-voice contracts SHALL be introduced only after measurement/billing certification tests. | **`AT-COM-011`:** Verify metered billing rejects unqualified events; test cap, credit and share-of-voice. |
| `FR-COM-012` | R4 | Additional payment/billing providers MAY be implemented without entangling the core inventory and campaign domains. | **`AT-COM-012`:** Add second provider against defined interfaces without changing core campaign logic. |
| `FR-COM-013` | R1 | When WooCommerce is absent, product SHALL still support administrator-approved complimentary/manual campaigns and all non-checkout functions; online self-service payment UI SHALL clearly state its prerequisite. | **`AT-COM-013`:** Disable WooCommerce; verify plugin works and online payment clearly unavailable. |
| `FR-COM-014` | R1 | Time ranges SHALL be stored in UTC, rendered in selected site/account timezone, and tested across daylight-saving transitions. | **`AT-COM-014`:** Book DST transition and cross-time-zone campaigns without changing contractual UTC interval. |

### 6.6 Ad delivery and browser rendering — 13 requirements

**Accountable:** Ad Delivery + Frontend Rendering. **Required review:** Performance, Security, Analytics. **Default phase:** P7.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-DEL-001` | R1 | Delivery decisions SHALL gate on approved creative, valid contract, active schedule, available allocation, policy eligibility and budget/hold rules. | **`AT-DEL-001`:** Prove each eligibility gate blocks delivery independently; invalidate stale approvals. |
| `FR-DEL-002` | R1 | Support fixed assignment, priority, weighted rotation and house-ad fallback with auditable selection rules. | **`AT-DEL-002`:** Run fixed, priority, weighted and house fallback policies with seeded reproducible inputs. |
| `FR-DEL-003` | R1 | Eligible targeting SHALL include page, post type, category/taxonomy, device/breakpoint and locale; advanced geo/audience policies require privacy/legal review. | **`AT-DEL-003`:** Match taxonomy, page context, device and locale; default deny unsupported sensitive targeting. |
| `FR-DEL-004` | R1 | Layout markup SHALL reserve predictable space or use controlled collapsing to minimize layout shifts. | **`AT-DEL-004`:** Use variable creative sizes and slow loads; verify placeholder stability and controlled collapse. |
| `FR-DEL-005` | R1 | Load creatives progressively as slots approach the viewport and batch backend decisions; do not fetch every heavy creative upfront on a 500-slot page. | **`AT-DEL-005`:** On 500-slot layout, load only near-viewport creative batches and verify slot counts. |
| `FR-DEL-006` | R1 | Limit in-flight fetches, observers and event handlers; reuse shared browser primitives where appropriate. | **`AT-DEL-006`:** Stress scroll bursts; verify bounded fetch concurrency, listeners and observer registrations. |
| `FR-DEL-007` | R1 | Different occurrences of the same slot/zone on a page SHALL have unique render identifiers and correct event attribution. | **`AT-DEL-007`:** Render zone twice in one page; verify DOM IDs and tracked occurrence IDs differ. |
| `FR-DEL-008` | R1 | Integration SHALL be resilient to full-page caching and common CDNs and shall not cache visitor-specific sensitive decisions in shared contexts. | **`AT-DEL-008`:** Simulate shared CDN cache; verify contextual decisions cannot leak across user contexts. |
| `FR-DEL-009` | R1 | Failure of delivery/analytics endpoints SHALL not break the containing WordPress page. | **`AT-DEL-009`:** Time out ad delivery and analytics; host page must remain readable and navigable. |
| `FR-DEL-010` | R1 | Admin previews, editor screens, tests and staging mode SHALL be flagged as nonbillable/nonproduction. | **`AT-DEL-010`:** Preview/staging traffic yields explicit test flags and zero billable events. |
| `FR-DEL-011` | R1 | Ad density, slot count and large creative configurations SHALL produce configurable UX/performance warnings; supported logical capacity does not equal permission to show all creatives simultaneously. | **`AT-DEL-011`:** Configure excessive ad density; show warnings and enforce selected policy. |
| `FR-DEL-012` | R2/R3 | Advanced frequency caps, pacing, viewability-aware delivery and spend-limited allocation SHALL be implemented with explicit data-collection constraints. | **`AT-DEL-012`:** Exercise caps, pacing and spend-limited rules after R2/R3 enablement, honoring consent. |
| `FR-DEL-013` | R1 | A page SHALL remain navigable and accessible with JavaScript disabled, with documented limitations to dynamic filling and metrics. | **`AT-DEL-013`:** Disable JavaScript; preserve semantic content and document dynamic-metric limitations. |

### 6.7 Measurement and accounting — 11 requirements

**Accountable:** Measurement + Database. **Required review:** Finance, Privacy, QA. **Default phase:** P7.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-MET-001` | R1 | Separate ad requested, selected/served, browser rendered, click observed, click qualified and billable event types. | **`AT-MET-001`:** Emit distinct requested, served, rendered, observed, qualified and billable event types. |
| `FR-MET-002` | R1 | Distinguish counts and denominators in UI/API; metrics SHALL not silently conflate served with viewable. | **`AT-MET-002`:** Compare counters and denominator labels in API and dashboards; prevent false equivalence. |
| `FR-MET-003` | R1 | Validate events server-side for schema, origin/context where practicable, bounded payload and rate limits. | **`AT-MET-003`:** Post spoofed, oversized and malformed events; reject/rate-limit without endpoint collapse. |
| `FR-MET-004` | R1 | Provide event IDs and bounded deduplication/aggregation with at-least-once delivery assumptions. | **`AT-MET-004`:** Deliver duplicate/out-of-order IDs and reprocess queue; aggregates remain idempotent. |
| `FR-MET-005` | R1 | Exclude editor previews, testing traffic and known invalid automation under defined rules. | **`AT-MET-005`:** Generate editor, staging and known synthetic automation traffic; exclude from paid analytics. |
| `FR-MET-006` | R1 | Aggregate campaign, creative, page, zone and slot statistics by time bucket without requiring an unbounded raw-event history. | **`AT-MET-006`:** Aggregate by day/hour and campaign/creative/page/zone/slot, then purge expired raw events. |
| `FR-MET-007` | R2 | Measure viewability with explicit observable-browser thresholds and time-in-view; document unsupported/browser-limited measurement. | **`AT-MET-007`:** Observe visibility duration and thresholds; distinguish browser-supported observations. |
| `FR-MET-008` | R2 | Report measurable vs nonmeasurable impressions and reasons where known. | **`AT-MET-008`:** Report unmeasurable impressions and unknown reasons without fabricating measurements. |
| `FR-MET-009` | R3 | CPC/CPM billing SHALL be based on qualified, reconciled events and contract-specific rules, not client-reported counters alone. | **`AT-MET-009`:** Reconcile metered contract with qualified immutable billable events and adjustment ledger. |
| `FR-MET-010` | R3 | Maintain invalid-traffic investigation and adjustment workflow without claiming perfect fraud detection. | **`AT-MET-010`:** Create fraud investigation; record evidence and correct bills with auditable adjustment. |
| `FR-MET-011` | R3 | Off-site conversions SHALL require advertiser-installed or otherwise authorized integrations, with attribution model/windows explicitly labeled. | **`AT-MET-011`:** Submit off-site conversion without authenticated adapter; reject unverified attribution. |

### 6.8 Heatmaps and engagement — 11 requirements

**Accountable:** Behavioral Analytics + UX. **Required review:** Privacy, Accessibility, Frontend. **Default phase:** P8.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-HEAT-001` | R2 | Provide click/tap, scroll-depth and element/slot-visibility heatmaps, with an optional attention-time **proxy** view; never label proxies as eye tracking. | **`AT-HEAT-001`:** Render click/tap, scroll, slot visibility and explicitly labeled attention-proxy maps. |
| `FR-HEAT-002` | R2 | Admin SHALL inspect full-page, zone, container and slot-level overlays on vertically scrollable pages. | **`AT-HEAT-002`:** Navigate long page, zone, container and slot overlays including footer row 40. |
| `FR-HEAT-003` | R2 | Browser collector SHALL use normalized coordinates plus stable semantic element/slot references. | **`AT-HEAT-003`:** Collect normalized element/slot coordinates; exclude private DOM attributes and values. |
| `FR-HEAT-004` | R2 | Aggregates SHALL be segmented by page layout version and responsive device class; incompatible geometries shall not be combined silently. | **`AT-HEAT-004`:** Move slot in new layout version; refuse cross-version overlay without compatible mapping. |
| `FR-HEAT-005` | R2 | Editor SHALL show selected date range, event sample size, sampling status, denominator, measurement limitations and empty states. | **`AT-HEAT-005`:** Expose sample count, denominator, consent exclusions, sampling and empty state. |
| `FR-HEAT-006` | R2 | Administrators SHALL filter by eligible campaign, creative, zone, page/template, device and reporting period. | **`AT-HEAT-006`:** Filter by date, template, device, campaign and authorized creative consistently. |
| `FR-HEAT-007` | R2 | Support side-by-side comparison of compatible layout periods and numeric accessible alternatives to color-only heatmaps. | **`AT-HEAT-007`:** Compare compatible periods and export numeric accessible heatmap equivalents. |
| `FR-HEAT-008` | R2 | Tracking SHALL be consent-aware where required, exclude protected/sensitive content, and minimize visitor identifiers. | **`AT-HEAT-008`:** Deny behavioral consent; confirm zero prohibited collection and correct retention. |
| `FR-HEAT-009` | R2 | Changing page geometry, infinite scroll or AJAX insertion SHALL not silently assign historical events to the wrong placement. | **`AT-HEAT-009`:** Mutate AJAX and infinite-scroll DOM; preserve or quarantine unmappable interactions. |
| `FR-HEAT-010` | R4 | Optional sampled session replay SHALL be disabled by default, separately permissioned, sensitive-content-safe, retention-controlled, and never a prerequisite for regular ad serving. | **`AT-HEAT-010`:** Leave replay disabled by default; test masked recording, scoped playback and deletion. |
| `FR-HEAT-011` | R2 | Heatmap tracking and visualization SHALL operate without mandating Hotjar, Clarity or another proprietary hosted service. | **`AT-HEAT-011`:** Deploy click/scroll/visibility maps with no hosted analytics dependency. |

### 6.9 Dashboards, reporting and intelligence — 10 requirements

**Accountable:** Reporting + Analytics. **Required review:** UX, Finance, Privacy. **Default phase:** P7/P8.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-REP-001` | R1 | Administrator dashboard SHALL surface revenue/order state, active/pending campaigns, placement occupancy, basic delivery status and operational alerts. | **`AT-REP-001`:** Admin summary reconciles order status, active campaigns, occupancy and health. |
| `FR-REP-002` | R1 | Advertiser dashboard SHALL expose only their own campaigns, approval/payment status, qualified statistics and invoices where available. | **`AT-REP-002`:** Advertiser account sees only own summarized metrics and invoices. |
| `FR-REP-003` | R1 | Reports SHALL support period, campaign, creative, zone/slot and device filters where data is available. | **`AT-REP-003`:** Apply time, creative, campaign, zone and device filters to report aggregates. |
| `FR-REP-004` | R1 | Report values SHALL identify units, currencies, denominators, time zone and data freshness. | **`AT-REP-004`:** Inspect units, denominators, currency, time zone and freshness labels for every KPI. |
| `FR-REP-005` | R2 | Add viewability, scroll, placement exposure and heatmap summaries; export aggregate CSV/JSON as allowed. | **`AT-REP-005`:** Compare viewability and scroll summaries; export permitted CSV/JSON. |
| `FR-REP-006` | R3 | Add conversion attribution, finance reconciliations, experiments, anomaly analysis and evidence-based placement recommendations. | **`AT-REP-006`:** Analyze conversions, finance, experiment and anomaly outputs only with validated sources. |
| `FR-REP-007` | R3 | Forecasts and estimates SHALL be visually labeled and accompanied by assumptions and uncertainty. | **`AT-REP-007`:** Label estimated and forecast charts with assumptions, interval and model provenance. |
| `FR-REP-008` | R2 | Long-running report exports SHALL use queued background processing and scoped download authorization. | **`AT-REP-008`:** Queue large export and require reauthorized, expiring download. |
| `FR-REP-009` | R2 | Administrators SHALL review pending jobs, delivery/aggregation health and retention cleanup status. | **`AT-REP-009`:** Display failed jobs, aggregation lag, retention job outcomes and retry controls. |
| `FR-REP-010` | R1 | Operational metrics, raw behavioral data and replay recordings SHALL not be exposed through ordinary advertiser reporting. | **`AT-REP-010`:** Attempt to access raw events or replay through advertiser report and direct API; deny. |

### 6.10 User experience, accessibility and localization — 11 requirements

**Accountable:** UX Lead + React/TypeScript. **Required review:** Accessibility, Product, QA. **Default phase:** P1+all.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-UX-001` | R1 | Provide distinct admin console, advertiser portal, public storefront, Gutenberg block controls and Layout Studio, under one coherent design system. | **`AT-UX-001`:** Navigate admin, portal, storefront, blocks and studio with shared components. |
| `FR-UX-002` | R1 | Every primary workflow SHALL specify empty/loading/error/success/confirmation states and recovery paths. | **`AT-UX-002`:** Complete primary flows with empty, loading, error, success and retry behaviors. |
| `FR-UX-003` | R1 | Layout Studio SHALL use visual canvas + zone tree + inspector, while also supporting keyboard-accessible and nonvisual editing. | **`AT-UX-003`:** Edit 200-slot grid using zone tree/inspector and full keyboard alternative. |
| `FR-UX-004` | R1 | Screens containing hundreds of slots SHALL display summarized/paginated controls rather than hundreds of simultaneously mounted edit widgets. | **`AT-UX-004`:** Verify bounded, budgeted mounted editor controls while editing 500 configured slots. |
| `FR-UX-005` | R1 | Advertiser wizard SHALL autosave drafts, show a transparent cost/approval summary, and avoid silent loss of changes. | **`AT-UX-005`:** Reload wizard after autosave; preserve inputs and explain payment/approval conditions. |
| `FR-UX-006` | R1 | Primary interface SHALL meet WCAG 2.2 AA as a project target, with automated and manual verification. | **`AT-UX-006`:** Pass accessibility automation plus manual keyboard/screen-reader review on critical flows. |
| `FR-UX-007` | R1 | Use WordPress localization APIs; support RTL/LTR layouts, locale-aware dates, numbers and currency presentation. | **`AT-UX-007`:** Change language and RTL locale; verify dates, price and layout semantics. |
| `FR-UX-008` | R1 | Responsive controls and page previews SHALL work across phone, tablet and desktop representative widths. | **`AT-UX-008`:** Compare responsive previews and real rendering at agreed representative viewport sizes. |
| `FR-UX-009` | R1 | Avoid unnecessary global theme CSS/JS and unrelated plugin side effects. | **`AT-UX-009`:** Inject plugin in theme fixture; verify no global style/script collisions. |
| `FR-UX-010` | R2/R4 | Offer richer saved report views, white-label/site branding and advanced multisite workflows subject to individual release scopes. | **`AT-UX-010`:** Use R2 saved views and R4 white-label/multisite only when specified feature flag active. |
| `FR-UX-011` | R1 | AdPilot SHALL expose high-quality diagnostic feedback rather than generic fatal errors for configuration and integration failures. | **`AT-UX-011`:** Force configuration error; show actionable, safely redacted diagnostics. |

### 6.11 Security, privacy, operations and extensibility — 13 requirements

**Accountable:** Security + Platform Engineering. **Required review:** Privacy, DevOps, License reviewer. **Default phase:** P2+all.

| PRD requirement | Release | Normative required behavior (PRD) | Detailed acceptance obligation |
|---|---|---|---|
| `FR-OPS-001` | R1 | Apply capability checks plus resource ownership to every sensitive REST/UI/CLI operation. | **`AT-OPS-001`:** Replay each sensitive API/CLI command as wrong role and wrong organization; deny. |
| `FR-OPS-002` | R1 | Validate and sanitize inputs, escape output by context, use prepared SQL and defend against CSRF, XSS, SSRF, upload abuse, replay and broken object authorization. | **`AT-OPS-002`:** Inject CSRF/XSS/SSRF/SQL/upload/replay payloads; validate all relevant trust boundaries. |
| `FR-OPS-003` | R1 | Audit financial changes, campaign approval, placement publication, account membership and emergency pause. | **`AT-OPS-003`:** Inspect audit trail after financial, approval, membership and layout changes. |
| `FR-OPS-004` | R1 | Enable privacy policy guidance, configurable retention, eligible data export/deletion and consent integration points. | **`AT-OPS-004`:** Delete/export eligible personal data and exercise retention cleanup under policy. |
| `FR-OPS-005` | R1 | Exclude sensitive DOM/form/payment values from any analytics collection; absence of user consent must prevent collection where required. | **`AT-OPS-005`:** Place secrets in forms/DOM and deny consent; capture no protected content. |
| `FR-OPS-006` | R1 | Provide diagnostics, explicit job retries, failed-job reporting, queue monitoring and supportable system cron/WP-CLI execution. | **`AT-OPS-006`:** Block scheduler and replay failed jobs safely via WP-CLI/system cron. |
| `FR-OPS-007` | R1 | Implement schema-versioned installation/upgrades, data-preserving deactivation, intentional uninstall behavior and upgrade testing. | **`AT-OPS-007`:** Activate, upgrade, deactivate and uninstall on seeded site with data safety. |
| `FR-OPS-008` | R1 | Distributable libraries, assets and source code SHALL have documented GPL-compatible licensing; generate dependency register/SBOM and required notices. | **`AT-OPS-008`:** Scan artifact including fonts/assets and transitive dependencies for valid licenses. |
| `FR-OPS-009` | R1 | Use a versioned REST namespace and stable hooks/shortcodes/PHP APIs; changes SHALL follow documented deprecation policy. | **`AT-OPS-009`:** Run API version compatibility and deprecation test for hooks/shortcodes/PHP functions. |
| `FR-OPS-010` | R1 | Expose structured, redacted logs, health checks, and safe administrator diagnostic exports. | **`AT-OPS-010`:** Export diagnostics without secrets or sensitive raw visitor records. |
| `FR-OPS-011` | R4 | Support optional independently deployed workers/analytics integrations without a forced redesign of core WordPress services. | **`AT-OPS-011`:** Detach optional analytics worker; core ad serving continues safely. |
| `FR-OPS-012` | R1 | Plugin bootstrap SHALL not require WooCommerce or proprietary remote services merely to load. | **`AT-OPS-012`:** Disable WooCommerce and external network; plugin bootstraps with supported core features. |
| `FR-OPS-013` | R1 | Plugin activation SHALL create only documented, owned resources and SHALL avoid modifying unrelated existing pages/settings without authorization. | **`AT-OPS-013`:** Activate on populated site; verify only plugin-owned pages/settings are created or changed. |

---

## 7. Business-rule enforcement matrix (all 18 baseline rules)

Business rules are enforced by domain services, transactional repositories and authoritative policy gates. Frontend warnings are supplementary; they cannot be the only enforcement. Traceability below adds one explicit negative/recovery test for each source rule.

| Rule | Exact PRD policy | Enforcement owner | Negative/recovery evidence |
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

---

## 8. Nonfunctional verification matrix (all 16 baseline requirements)

Numeric thresholds not specified by the PRD must be determined using representative measurements and approved **before** implementation is treated as release-ready. Do not invent production p95 latency or browser-memory guarantees. For each NFR, QA maintains a measured environment/evidence record and reproducer.

| Requirement | Baseline quality constraint | Verification type | Evidence obligation |
|---|---|---|---|
| `NFR-001` | Modular-monolith design with explicit domain/application/infrastructure/presentation boundaries; PHP WordPress host; no mandatory external application server in R1. | Architecture lint + dependency review | **`AT-NFR-001`:** Fail build on forbidden Infrastructure → Presentation/Domain cycles; inspect plugin bootstrap. |
| `NFR-002` | WordPress Coding Standards, PHP static analysis, TypeScript strict mode, clear module APIs, source-level documentation and reviewed dependencies. | Lint/static analysis and review | **`AT-NFR-002`:** WordPress PHPCS, PHPStan/Psalm where selected, TS strict, documented module API. |
| `NFR-003` | A 500-logical-slot page must not eagerly fetch or paint 500 heavy creatives. Browser work and network concurrency must be bounded; no per-slot PHP query pattern. | Browser and SQL profile | **`AT-NFR-003`:** 500-slot fixture: bounded creative fetch, batched DB queries, predictable DOM and memory. |
| `NFR-004` | Establish and validate environment-specific p75/p95 budgets for request overhead, Core Web Vitals, query count, JS bundle size, browser memory, batching and queue lag; declare benchmark hardware, creative sizes and cache configuration. | Benchmark report | **`AT-NFR-004`:** Record declared p75/p95 targets, hosting/browser profile, Web Vitals, JS, memory, queue lag. |
| `NFR-005` | Idempotent payment webhooks and jobs; resilient scheduler; safe fallback if ad delivery or analytics endpoint fails. | Fault injection and replay | **`AT-NFR-005`:** Duplicate webhooks; stall scheduler; time out analytics; page remains usable and jobs recover. |
| `NFR-006` | Database-enforced uniqueness/capacity controls, concurrency testing, reconciliation, immutable adjustment trail and precise currency arithmetic. | Concurrency and accounting proof | **`AT-NFR-006`:** Race reservations, adjust refunds and compare exact ledger to order/payment facts. |
| `NFR-007` | Threat model, dependency scans, permission tests, hardened creative handling, secure REST, server-side validation and emergency shutdown. | Threat model + security testing | **`AT-NFR-007`:** Cross-tenant API probes, XSS, SSRF, CSRF, upload, dependency, secret checks. |
| `NFR-008` | Data minimization, regional consent policy, no sensitive input collection, finite behavioral-event retention and protected access. | Consent/privacy data inspection | **`AT-NFR-008`:** No private field values leave browser; retention/export/erasure validated. |
| `NFR-009` | WCAG 2.2 AA target for critical UIs, including nonvisual alternatives to heatmaps and drag/drop interactions. | Automated and manual WCAG audit | **`AT-NFR-009`:** Keyboard, reader, zoom, contrast, mobile; heatmaps have nonvisual equivalents. |
| `NFR-010` | Validated WordPress/PHP/MySQL/MariaDB matrix; support classic and block themes, modern browsers, WooCommerce HPOS when commerce adapter enabled. | Version/browsers/theme matrix | **`AT-NFR-010`:** Reproduce classic/block theme, cache/CDN, WooCommerce HPOS and PHP/DB matrix. |
| `NFR-011` | Translation-ready strings, RTL/LTR support, reliable date/time-zone and currency displays. | Locale fixture | **`AT-NFR-011`:** Translation catalogs, RTL CSS, currencies and DST handling. |
| `NFR-012` | Logs, queue metrics, failed-event reporting, health diagnostics, financial discrepancy alerts and safe support exports. | Operational incident drill | **`AT-NFR-012`:** Diagnostics and alerts accurately show queue/aggregation/billing inconsistencies. |
| `NFR-013` | Versioned REST API, documented hooks, stable PHP and shortcode contracts, adapter interfaces and deprecation policy. | API contract compatibility | **`AT-NFR-013`:** Versioned endpoints, PHP hooks/shortcodes; backward compatibility and deprecation tests. |
| `NFR-014` | GPL-compatible distribution, recorded license texts/notices, transitive dependency screening, no unclear copyrighted assets. | SBOM and license audit | **`AT-NFR-014`:** Automated + manual review of source, assets, fonts, bundled and transitive licenses. |
| `NFR-015` | Idempotent migrations, version compatibility checks, reliable backup guidance, data-preserving deactivation and explicit uninstall. | Upgrade lifecycle suite | **`AT-NFR-015`:** Install, schema migrate, upgrade from previous release, deactivate, uninstall per policy. |
| `NFR-016` | Critical flows verified through task-based usability tests; major actions provide comprehensible states, feedback and undo/recovery when feasible. | Task-based usability study | **`AT-NFR-016`:** Test purchase, 200-slot build, moderation and heatmap decision against agreed benchmark. |

---

## 9. Product-level acceptance mapping (all R1 AC requirements)

These `AC-*` requirements are independent release gates. They must pass as integrated scenarios, **not only** as their individual `AT-*` line-item tests. Each `AC-*` is mapped to a canonical cross-module test fixture and an objective proof artifact.

| Baseline AC | PRD release outcome | Canonical E2E | Evidence needed |
|---|---|---|---|
| `AC-001` | Publisher creates and publishes a 500-slot page consisting of 4 header, 100 sidebar, 196 in-content and 200 footer logical slots. Layout Studio remains usable; publishing and responsive preview succeed. | `E2E-02` | Screenshot/serialized layout fixture + publish audit, breakpoint previews |
| `AC-002` | One `adpilot/ad-zone` block can reference a 200-slot footer container without storing 200 Gutenberg blocks. | `E2E-02` | Stored Gutenberg post content showing one compact zone block |
| `AC-003` | Four supported integration paths (Gutenberg, PHP function, shortcode, and supported hook binding) render through one policy-aware engine with correct per-occurrence identifiers. | `E2E-03` | HTML snapshot, policy decision trace and unique render instance IDs |
| `AC-004` | Public page initially loads bounded creatives near viewport and does not miscount 500 configured slots as impressions. | `E2E-04` | Waterfall/network capture and qualified event count comparison |
| `AC-005` | Advertiser can discover an available slot, obtain a quote, reserve, pay through configured WooCommerce, upload creative, request review and observe activation when approved. | `E2E-01` | End-to-end recording of catalog → checkout → moderation → delivery |
| `AC-006` | Two concurrent buyers cannot successfully reserve the same exclusive inventory over overlapping time windows. | `E2E-05` | Concurrent transaction logs, inventory invariant and reconciled winner |
| `AC-007` | Duplicate payment callbacks and campaign activation retries do not double-allocate, double-invoice or double-activate. | `E2E-06` | Replay callback trace, unique ledger references and absence of extra charge |
| `AC-008` | Creative changes requiring reapproval cannot bypass review and cannot serve in an unapproved state. | `E2E-07` | Creative version transition and negative delivery decision after change |
| `AC-009` | Advertiser A cannot access advertiser B's campaigns, invoices, raw behavioral data or privileged reports through UI or direct API calls. | `E2E-08` | Cross-organization UI/API authorization matrix |
| `AC-010` | Classic theme, block theme, common caching setup and supported WooCommerce HPOS mode pass the declared compatibility suite. | `E2E-09` | Theme/WooCommerce HPOS/cache matrix results |
| `AC-011` | Broken analytics endpoint does not crash the public site, block reading content or silently produce fictitious billable events. | `E2E-10` | Fault-injection trace and content accessibility verification |
| `AC-012` | Privacy-sensitive pages/inputs are excluded from collection; denied consent prevents prohibited collection. | `E2E-11` | Browser network/privacy payload inspection under denied consent |
| `AC-013` | All primary application workflows pass agreed manual accessibility and usability gates. | `E2E-12` | Manual screen-reader/keyboard and usability research evidence |
| `AC-014` | Installer works without WooCommerce for nonpayment functions and provides an accurate diagnostic for unavailable online checkout. | `E2E-13` | No-WooCommerce activation and clearly unavailable online-checkout UI |
| `AC-015` | Release contains reproducible build, automated tests, license notices, migrations and administrator/developer documentation. | `E2E-14` | Signed release checklist, SBOM, tests, migration, docs, plugin artifact |

---

## 10. End-to-end acceptance scenarios (Given / When / Then)

Use these scenarios as Playwright/browser, PHPUnit/integration, concurrency-harness, security or operations tests, depending on their source of truth. Each is a **planned** executable scenario with a seed fixture and traceable assertions, not a claim of a completed test.

### E2E-01 — Self-service advertiser purchase, review and delivery (R1)

**Given** published bookable inventory, an authorized advertiser organization, a valid raster creative and WooCommerce checkout enabled; **when** the advertiser selects an available interval, receives a server-authenticated versioned quote, completes a temporary hold, pays, submits the creative, the moderator approves, and schedule becomes active; **then** the correct order, reservation, campaign and approval states are reconciled, and the eligible ad serves only after all gates pass. **Negative branches:** quote expires, declined payment, rejected creative, subscription/provider unavailable, late callback, changed inventory. **Trace:** `FR-MKT-*`, `FR-CAM-*`, `FR-COM-001..010`, `FR-DEL-001`, `AC-005`.

### E2E-02 — 500 configured slots in a vertically scrolling WordPress page (R1)

**Given** header 4, sidebar 100, article body 196 and footer 200 (total 500); **when** AdOps constructs a responsive layout, publishes a version and saves one Gutenberg zone block for the footer; **then** all logical slots are addressable, layout remains usable without 500 full editor controls, and serialized content does not contain 200 independent footer blocks. **Negative:** moving/removing a sold position blocks publish or follows approved migration; invalid mobile columns require correction. **Trace:** `FR-INV-*`, `FR-UX-003..004`, `AC-001..002`.

### E2E-03 — Integration-path equivalence (R1)

**Given** one slot/zone and an active eligible campaign; **when** it is inserted via Gutenberg, PHP API, shortcode and supported theme hook, including repeated occurrence on the same page; **then** rendering shares policy logic, produces unique per-occurrence identities, uses scoped CSS and never bypasses moderation. **Negative:** invalid shortcode attribute, missing zone, duplicate automatic insertion. **Trace:** `FR-INT-*`, `FR-DEL-007`, `AC-003`.

### E2E-04 — Lazy loading, visibility and truthful R1 counters (R1)

**Given** the 500-slot page with viewport initially showing only a small subset; **when** the visitor opens the page, scrolls slowly and then rapidly to the bottom; **then** only near-viewport batches load, requests remain bounded, no slot is counted viewed merely because it is configured, and R1 metrics distinguish requested/served/rendered/qualified clicks. **R2 extension:** measurable/viewable events use explicit thresholds. **Trace:** `FR-DEL-004..006`, `FR-MET-001..008`, `AC-004`.

### E2E-05 — Two buyers competing for exclusive placement (R1)

**Given** one exclusive slot and matching time interval; **when** two concurrent transactions create holds and proceed to payment; **then** at most one overlapping successful entitlement is persisted, the other receives a clear alternative, and orphaned holds are cleaned without overselling. Repeat with duplicate requests and expired holds. **Trace:** `FR-COM-002..003`, `BR-002`, `BR-005`, `AC-006`.

### E2E-06 — Duplicate, out-of-order and delayed provider events (R1)

**Given** a checkout order awaiting authoritative provider callbacks; **when** success, failure, refund or duplicate messages arrive in varying orders, including after a hold expires; **then** settlement follows provider authority and policy; allocation, ledger and invoices remain idempotent, any irreconcilable case enters an operator queue. **Trace:** `FR-COM-005..010`, `BR-003..004`, `AC-007`.

### E2E-07 — Creative edit after approval and emergency pause (R1)

**Given** an approved campaign serving a reviewed creative; **when** advertiser changes destination URL or asset, then admin pauses the campaign; **then** the unapproved variant never serves, prior approved version serves only if policy explicitly allows it, pause invalidates eligibility, and audit records exist. **Trace:** `FR-CAM-001..010`, `BR-007..008`, `AC-008`.

### E2E-08 — Cross-organization access isolation (R1)

**Given** organization A and B have private campaigns, invoices, reports and asset drafts; **when** A's user guesses B's IDs via portal or REST and tries different member roles; **then** requests are denied without existence leakage, exports cannot be fetched without scoped authorization and logs do not expose protected content. **Trace:** `FR-MKT-004`, `FR-OPS-001..002`, `FR-REP-002,010`, `AC-009`.

### E2E-09 — WordPress theme + caching + commerce compatibility (R1)

**Given** one classic theme, one block theme, WooCommerce HPOS and a representative full-page cache; **when** the same zone is inserted using documented mechanisms and visitors arrive under distinct allowed contexts; **then** content renders consistently, cached HTML holds only safe public information, and dynamic ad decisions do not leak a prior visitor's private context. **Trace:** `FR-INT-008..009`, `FR-DEL-008`, `FR-COM-005`, `AC-010`.

### E2E-10 — Ad delivery/analytics outage (R1)

**Given** a functioning public WordPress page; **when** ad-decision API times out or analytics ingest fails and background worker stalls; **then** the page remains navigable, reserved space/fallback behaves consistently, events are not fabricated as billable and admin diagnostics show recoverable failure. **Trace:** `FR-DEL-009`, `FR-OPS-006,010`, `NFR-005`, `AC-011`.

### E2E-11 — Privacy exclusions and denied consent (R1/R2)

**Given** a visitor denies optional behavioral tracking and sensitive pages contain form/checkout/password fields; **when** the page renders and offers ads; **then** no prohibited behavioral samples or protected values are transmitted, consent policy and exclusions are observable and necessary baseline serving follows allowed rules. **Trace:** `FR-OPS-004..005`, `FR-HEAT-008`, `AC-012`.

### E2E-12 — UX and accessibility completion gate (R1)

**Given** critical publisher and advertiser personas and representative mobile/desktop devices; **when** test participants purchase an ad, approve a creative, build a 200-slot footer and interpret metrics using keyboard and assistive technology; **then** each task meets preapproved usability criteria, color is not sole signal and all meaningful states recover. **Trace:** `FR-UX-*`, `NFR-009,016`, `AC-013`.

### E2E-13 — No-WooCommerce operation (R1)

**Given** fresh WordPress install without WooCommerce; **when** AdPilot activates and admin configures inventory and complimentary/manual campaign; **then** plugin and eligible ad display work, online paid checkout is accurately disabled, and no PHP fatal or unsupported order is created. **Trace:** `FR-COM-013`, `FR-OPS-012`, `AC-014`.

### E2E-14 — Installation, migration, artifact, licensing and docs (R1)

**Given** clean and upgrade-from-prior-version fixtures; **when** build, activate, upgrade, deactivate and inspect final release ZIP; **then** artifacts are reproducible, plugin-owned records are preserved, dependencies and assets are licensed, and administrator/developer documentation matches shipped APIs. **Trace:** `FR-OPS-007..009,013`, `NFR-014..015`, `AC-015`.

### E2E-15 — Full-page heatmap with layout changes (R2)

**Given** a consent-eligible 500-slot long page, known click and scroll samples, a responsive layout with different desktop/mobile geometry and two published layout versions; **when** admin opens full-page and footer-zone overlays, filters by device and compares time periods; **then** samples align with the correct slot/version, sample size and unknown coverage appear, mismatched geometry is rejected or clearly mapped, and numeric accessibility view is available. **Trace:** `FR-HEAT-001..009,011`, `FR-MET-007..008`, `FR-REP-005`.

### E2E-16 — Metered billing accuracy and dispute workflow (R3)

**Given** CPC/CPM campaigns with distinct terms and synthetic valid/duplicate/bot clicks/impressions; **when** ingestion, qualification, aggregation, contract evaluation and reconciliation run, including retry and refund; **then** only eligible events generate immutable billable records, invoices match ledger totals, disputed charges receive audited adjustments and no raw browser counter is treated as accounting authority. **Trace:** `FR-COM-011`, `FR-MET-009..011`, `FR-REP-006`, `BR-012..014`.

### E2E-17 — R4 session replay, multisite and adapter isolation (R4)

**Given** a configured replay extension and independent sites within a WordPress network; **when** consented sessions record, denied sessions navigate, and an optional worker fails; **then** only allowed, redacted, sampled sessions are stored, replay access is restricted, data remains site-isolated, and core delivery continues independently. **Trace:** `FR-HEAT-010`, `FR-OPS-011`, `FR-UX-010`, `NFR-008,013`.

---

## 11. Edge-case and fault-injection catalog

| Case | Trigger | Expected system contract | Lead test owner |
|---|---|---|---|
| EDGE-01 | 500 configured slots, visitor sees only 10 | Count configured ≠ rendered ≠ viewable; bounded work | Ad Delivery / Measurement |
| EDGE-02 | 200-slot footer at 360px viewport | Responsive columns and stable accessible flow; no horizontal overflow | Frontend / Accessibility |
| EDGE-03 | Ad content causes layout shift | Reserved geometry or controlled collapse; emit diagnostics | Frontend / Performance |
| EDGE-04 | Same logical zone inserted twice intentionally | Unique render identities; governed inventory semantics | Gutenberg / Delivery |
| EDGE-05 | Unknown shortcode attribute | Ignore/reject safely; no privilege or eligibility bypass | WordPress / Security |
| EDGE-06 | Layout edit while slot is sold | Contract-impact block or audited migration | Inventory / Commerce |
| EDGE-07 | Two checkout reservations in one millisecond | Concurrency safety without double entitlement | Database / Finance |
| EDGE-08 | Late gateway callback after TTL expiry | Policy-driven reconciliation, not silent campaign activation | Finance / Commerce |
| EDGE-09 | Creative swapped after approval | New version requires approval | Moderation / Security |
| EDGE-10 | Admin pauses campaign during CDN cache TTL | Authoritative eligibility prevents stale unsafe serve | Delivery / Platform |
| EDGE-11 | Browser JavaScript disabled | No fatal; user-facing content remains usable | Frontend / QA |
| EDGE-12 | Request to analytics API blocked | Content and ad fallback remain functional | Platform / QA |
| EDGE-13 | Visitor denies collection consent | No prohibited tracking data transmitted | Privacy / QA |
| EDGE-14 | Dynamic AJAX changes page coordinates | Stable target mapping or sample exclusion | Heatmap / Measurement |
| EDGE-15 | Mobile layout differs from desktop | Separate heatmap segment or validated mapping | Heatmap / UX |
| EDGE-16 | Replay records password/checkout page | Mask/exclude at source; privacy incident test | Privacy / Security |
| EDGE-17 | Advertiser guesses another campaign ID | Deny at backend; no private existence leakage | Security / API |
| EDGE-18 | Queue replays same event twice | Idempotent aggregate and billing ledger | Reliability / Finance |
| EDGE-19 | Network mode and two WordPress sites | No site-crossing inventory/data access | WordPress / Security |
| EDGE-20 | R1 admin sets CPC price despite feature off | Rejected or presented as unavailable, never billed from R1 clicks | Product / Finance |
| EDGE-21 | Refund after partially served term | Explicit pro-ration/policy and audited adjustment | Finance / Product |
| EDGE-22 | DST change inside purchased schedule | Preserve UTC instants and disclose local clock semantics | Backend / QA |
| EDGE-23 | Uploaded SVG/script/payload | Reject or sandbox under an approved later format | Security / Creative |
| EDGE-24 | Export worker produces file then member loses access | Recheck authorization on download, not only at request | Reporting / Security |

---

## 12. Dependencies, sequencing and quality gates

| Gate | Must exist before gate | Blocking evidence | Release |
|---|---|---|---|
| G-01 Foundation | Architecture decisions, schema/migration conventions, auth | Clean bootstrap, module boundaries, permission tests | R1 |
| G-02 UX architecture | Approved screens, tokens, responsive/keyboard flows | Figma/structured prototypes and reviewed state models | R1 |
| G-03 Inventory | Slots, binding/version contract, draft/publish conflict logic | `AT-INV-*`, E2E-02 | R1 |
| G-04 Integrations | Shared rendering API + dynamic Gutenberg blocks | `AT-INT-*`, E2E-03, classic/block theme matrix | R1 |
| G-05 Marketplace | Advertiser scope, placement catalog, campaign wizard | `AT-MKT-*`, role/UX evidence | R1 |
| G-06 Commercial sales | Quotes, atomic holds, payment mapping, ledger | `AT-COM-001..010,013..014`, E2E-05/06 | R1 |
| G-07 Delivery and first-party analytics | Eligibility, progressive frontend, event collector | `AT-DEL-*` R1 + `AT-MET-*` R1 + E2E-04/10 | R1 |
| G-08 Release 1 | AC-001..015 and all R1 security/privacy/accessibility | Signed R1 release evidence and artifact | R1 |
| G-09 Behavioral analytics | Stable geometry, consent and aggregate measurement | `AT-HEAT-*` R2, E2E-15 | R2 |
| G-10 Metered sales | Validated measured events and billing reconciliation | `AT-COM-011`, E2E-16 | R3 |
| G-11 Extended network | Site isolation, optional replay and failure-isolated adapters | E2E-17, privacy/multisite evidence | R4 |

**Avoid premature completion claims:** A screenshot is not proof of database integrity; a generated invoice is not proof of payment; a JS IntersectionObserver event is not automatically an MRC-accredited measurement; a library's public repository is not sufficient copyright clearance; a prototype is not an implemented application.

---

## 13. Implementation work-package template for GPT-6 High

Break delivery into vertical work packages with no more than one major domain invariant per change set where feasible. Every package must include:

```yaml
package_id: ADP-WP-<sequential>
release: R1|R2|R3|R4
phase: P0..P12
capabilities: [CAP-xx]
requirements: [FR-..., BR-..., NFR-..., AC-...]
owner: <accountable engineering role>
reviewers: [UX Lead, Security Lead, QA Lead, ...]
inputs:
  - canonical_documents_and_versions
  - existing_code_interfaces
  - schema_and_migration_versions
implementation:
  - domain_rules_and_transactions
  - persistence_and_api_changes
  - UI_states_and_accessibility
  - performance_and_security_constraints
  - integrations_and_backward_compatibility
acceptance_tests: [AT-..., E2E-...]
commands_executed: []
observed_results: []
failures_or_blockers: []
review_evidence: []
migration_and_rollback_notes: []
status: not_started|design_ready|in_progress|implemented|verified|shipped
```

### Handoff instructions for GPT-6 High

1. Read ADP-DOC-001 and this document before editing code. Keep their IDs and normative language unchanged.
2. Choose a package whose predecessor gates are completed; enumerate concrete existing files/APIs and any policy decisions still unresolved.
3. Implement a vertical slice: domain invariant → persistence/transaction → REST/PHP surface → UI/rendering → tests → user/developer documentation.
4. For frontend packages, deliver keyboard, mobile, empty/loading/error/success and theme-collision states; review visual quality against the approved design system.
5. For commerce packages, write concurrency, late-callback and ledger-reconciliation tests **before** declaring success.
6. For ad delivery, maintain configured/served/rendered/measurable/viewable/billable distinctions, including preview and consent behavior.
7. For heatmaps, include layout-version and viewport-class provenance; refuse unsupported eye-tracking claims.
8. Never add a network dependency, premium licensed library or third-party creative execution path without a license/security/availability review.
9. Run and report actual tests, static analysis, build and deployment checks. If not run, mark **unverified**.
10. Update this document's traceability status and the relevant ADR whenever implementation changes a previously approved contract.

### Proof of completion per package

A feature is ready for review only if its `FR-*` requirements are implemented, `AT-*` tests are linked to real executable test files, representative E2E scenarios are exercised, affected `BR-*` invariants hold, nonfunctional budgets are measured, accessibility/security/privacy evidence exists, and product/engineering owners explicitly approve the result. Tests that are skipped or unavailable must be listed as such.

---

## 14. Requirements coverage accounting and audit protocol

The baseline requirement totals were programmatically extracted from the actual ADP-DOC-001 Markdown file. Any future PRD edit must re-run coverage checks and fail if an ID appears twice or is missing.

| Requirement family | Imported count | Mapped acceptance obligations |
|---|---:|---:|
| `FR-INV-*` | 14 | 14 |
| `FR-INT-*` | 12 | 12 |
| `FR-MKT-*` | 10 | 10 |
| `FR-CAM-*` | 10 | 10 |
| `FR-COM-*` | 14 | 14 |
| `FR-DEL-*` | 13 | 13 |
| `FR-MET-*` | 11 | 11 |
| `FR-HEAT-*` | 11 | 11 |
| `FR-REP-*` | 10 | 10 |
| `FR-UX-*` | 11 | 11 |
| `FR-OPS-*` | 13 | 13 |
| **Total functional** | **129** | **129** |
| Business rules `BR-*` | 18 | 18 |
| Nonfunctional `NFR-*` | 16 | 16 |
| Product acceptance `AC-*` | 15 | 15 E2E mappings |

**Bidirectional verification:** For any `FR-*`, locate its `AT-*` in §6, the related capability contract in §3, implementation surface in §5, and integrated E2E in §10 where applicable. For any `AT-*`, remove the `AT-` prefix and restore `FR-` to recover its PRD source; BR/NFR follow the same convention with `AT-BR-*` / `AT-NFR-*`. For any shipped work package, enumerate source requirement IDs and link test evidence by exact identifier. QA must reject unlinked tests and incomplete FR coverage.

**Status ledger (initial document release):** Every entry in this document is `specified / implementation not independently verified`. These statements are requirements and planned tests, not evidence that AdPilot is implemented. Implementation and verification status must be maintained in the project tracker or canonical backlog with exact commit/build/test references; do not mark rows passed in the specification just because they are written here.

**Open decisions inherited from PRD:** `DEC-001` runtime compatibility, `DEC-002` first checkout adapter, `DEC-003` manual campaigns, `DEC-004` R1 creatives, `DEC-005` consent policy, `DEC-006` retention, `DEC-007` hold TTL/refund, `DEC-008` theme matrix, `DEC-009` multisite scope, `DEC-010` metered billing validation, `DEC-011` naming clearance, and `DEC-012` localization baseline. Each must have an owner and formal sign-off before affected work starts.

---

## 15. External platform assumptions and authoritative references

- WordPress Block API: <https://developer.wordpress.org/block-editor/reference-guides/block-api/>.
- Dynamic blocks and metadata: <https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/>.
- WordPress REST API authentication and permission callbacks: <https://developer.wordpress.org/rest-api/>.
- WordPress plugin directory license guidelines: <https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/>.
- WooCommerce HPOS guidance: <https://developer.woocommerce.com/docs/features/high-performance-order-storage/>.
- WordPress privacy and personal data integration: <https://developer.wordpress.org/plugins/privacy/>.
- WordPress security and sanitization: <https://developer.wordpress.org/plugins/security/>.

**Verification policy:** Confirm these references, supported versions and third-party licenses again during release hardening. Compliance and measurement certification cannot be inferred from this document alone.

---

## 16. Document end checkpoint / next document

**Completed document:** `ADP-DOC-002` — Detailed Feature Catalog, Requirements Traceability & Acceptance Matrix.  
**Next:** `ADP-DOC-003` — User Personas, Jobs-to-Be-Done, User Journeys & Workflow Specifications.  
**Next-document responsibility:** Precisely define actors, front-end and backend user journeys, task/state transitions, edge cases, permissions and screen-level acceptance for every critical business scenario; preserve the identifiers used here.

**No implementation, tests, benchmarks or legal review are claimed to have been performed.**
