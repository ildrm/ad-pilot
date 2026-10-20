# AdPilot — Product Requirements Document (PRD)

**Document ID:** ADP-DOC-001  
**Filename:** `01_ADPILOT_PRODUCT_REQUIREMENTS_DOCUMENT.md`  
**Version:** 1.0.0 (baseline proposal)  
**Status:** Proposed for architecture and product sign-off  
**Date:** 2026-10-11  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Primary audience:** Product owner; UX/UI and frontend engineers; WordPress/PHP architects; AdTech, commerce, security, privacy, analytics, and QA specialists; GPT-6 High implementation agents  
**Document owner:** Product Owner  
**Required reviewers:** Technical Lead, UX Lead, AdTech Lead, Finance Lead, Security/Privacy Leads, QA Lead  
**Source of truth:** This PRD establishes product intent and acceptance boundaries. Lower-level specifications must elaborate, not contradict, it.  
**Name status:** *AdPilot* is a working title; trademark, domain, and WordPress.org slug clearance remain separate pre-release checks.

---

## 1. Purpose and decision framework

### 1.1 Problem statement

WordPress publishers often rely on disconnected solutions for ad inventory, manual sales, campaign approvals, creative rendering, payment collection, advertiser self-service, performance measurement, and visitor-engagement analytics. Simple banner plugins usually do not provide a dependable purchase-to-delivery workflow, while enterprise ad servers may be expensive, operationally complex, or unsuitable for self-hosted WordPress sites. Publishers also need to understand *where* ads are encountered on long, responsive pages, not just count image loads or link clicks.

AdPilot shall enable a publisher to define and sell advertising inventory, accept purchases, approve creatives, serve eligible advertising, reconcile commercial events, and measure performance using aggregate visual analytics. The product shall accommodate high-density layouts—e.g., **4 header + 100 sidebar + 196 in-content + 200 footer = 500 configured slots**—without assuming that all slots are loaded, viewed, or billable during one visit.

### 1.2 Product vision

A theme-independent, modular, self-hosted WordPress plugin that makes buying advertising straightforward for customers and makes complex inventory, layout, delivery, analytics, and business operations manageable for publishers. Core ad serving and reporting must remain functional without mandatory third-party SaaS subscriptions.

### 1.3 Product principles

- **Commercial integrity first:** No paid delivery without an eligible contract and approved creative; no billing from unverified counters.
- **Excellent UX, not a settings maze:** Visual placement management, actionable dashboards, guided purchase journeys, accessible controls, and clear error recovery.
- **Theme-independent core:** Gutenberg blocks, PHP APIs, shortcodes, and hooks consume one shared rendering service.
- **High-density by design:** A compact zone configuration may represent hundreds of logical slots; browser delivery is progressive and bounded.
- **Truthful metrics:** Configured, served, rendered, measurable, viewable, clicked, and billed are distinct states.
- **Privacy by design:** Behavioral analytics are consent-aware and data-minimizing; session replay is optional and off by default.
- **WordPress-native:** WordPress remains the runtime, identity store, integration host, and distribution target.
- **Open-source-compatible distribution:** Prefer GPL-compatible, maintainable dependencies and record their terms.
- **Incremental maturity:** Launch reliable fixed/time-based sales before introducing independently reconciled CPM/CPC billing or AI optimization.

### 1.4 Document conventions

- **SHALL / MUST:** Mandatory for the specified release.
- **SHOULD:** Strong preference, with recorded exception permitted.
- **MAY:** Optional or future functionality.
- **R1–R4:** Delivery releases defined in §4.
- **FR-xxx:** Functional requirement; **BR-xxx:** Business rule; **NFR-xxx:** Nonfunctional requirement; **AC-xxx:** Acceptance criterion.
- A *slot* is logical inventory; a *render instance* is one occurrence of that slot in a page view; a *creative* is advertiser material; an *ad decision* is a specific selection for a render opportunity.

## 2. Objectives and success measurements

### 2.1 Business objectives

1. Enable publishers to monetize first-party website inventory directly, including fixed placements, sponsorships, and packages.
2. Reduce operational effort for advertiser onboarding, reservations, creative review, campaign activation, billing, and renewal.
3. Give advertisers transparent placement descriptions, availability, price, approval status, and trustworthy performance reporting.
4. Provide measurable placement and layout intelligence, especially for long pages and responsive grids.
5. Establish a sustainable open-source-compatible architecture that can grow into an agency/network product without a forced SaaS dependency.

### 2.2 Product outcomes and initial evaluation metrics

Measure: storefront-to-checkout conversion; campaign setup abandonment; median administrator approval time; percentage of campaigns activated on schedule; discrepancy rate between orders, reservations and delivery status; successfully served and measurable impressions; event validation and deduplication rate; page-rendering overhead; number of support tickets per onboarding; task completion time for creating a 200-slot footer; usability test success; accessibility defect counts; and privacy violations (target: zero confirmed collection of protected field values).

**Baseline and target values are to be measured in Phase 0/1 and approved in `35_PERFORMANCE_BENCHMARKS_AND_SCALABILITY.md` and `34_TEST_STRATEGY_AND_ACCEPTANCE.md`; this PRD does not invent verified production baselines.**

### 2.3 Non-goals

AdPilot is not: (a) a replacement for major off-site ad exchanges in R1; (b) a full payment gateway or bank; (c) an eye-tracking system; (d) a guarantee against all click fraud; (e) a product that can independently verify conversions on an advertiser's external website; (f) a mandatory full-page session recorder; (g) a general unrestricted PHP/JavaScript snippet executor; or (h) a platform that promises acceptable user experience simply because it supports 500+ logical slots.

## 3. Actors, jobs-to-be-done, and boundaries

| Actor | Main job | Scope of authority |
|---|---|---|
| Publisher / Site Administrator | Operate inventory, compliance, sales and configuration | Site-scoped, with privileged audited operations |
| Ad Operations Manager | Configure zones, placements, bookings and campaigns | Delegated operational access |
| Creative Moderator | Review advertising content and destinations | Review-only controls, not financial administration |
| Finance Manager | Reconcile purchases, invoices and refunds | Financial scope, with sensitive audit logging |
| Analytics Manager | Review aggregated delivery and heatmap insights | Aggregated analytics; separate permission for replay |
| Advertiser Organization Owner | Purchase placements and manage organization | Only own organizations, members and campaigns |
| Advertiser Campaign Manager | Draft, submit and inspect authorized campaigns | Campaign scope without unrestricted billing access |
| Advertiser Analyst / Billing User | Inspect permitted reports / invoices | Least-privilege reporting or billing |
| Anonymous prospective advertiser | Browse available advertising packages | Published inventory metadata only |
| Website visitor | Consume content and permitted ads | No admin or advertiser data access |
| Developer / Theme Integrator | Embed placement interfaces using code, Gutenberg or hooks | Trusted code and capabilities; no implicit financial override |
| Network Super Administrator | Manage multisite policies where applicable | Network-scoped permissions in supported deployments |

**Access control:** All sensitive functions require both capability checks and object-/organization-scope verification. UI hiding does not authorize operations.

## 4. Release scope and sequencing

| Release | Primary outcome | Mandatory capabilities |
|---|---|---|
| **R1 — Market-ready foundation** | Publishers sell fixed/time-based ads, approve them and serve them | Identity, placements/layouts, 500-slot page support, 4 Gutenberg blocks, theme APIs, storefront, advertiser portal, creative review, availability, reservations, WooCommerce checkout adapter, activation, safe delivery, basic impressions/clicks/reporting, security, privacy, i18n foundations |
| **R2 — Visual intelligence** | Administrators understand placement engagement | Measured viewability, click/tap and scroll heatmaps, layout versioning, zone/slot analysis, attention proxies, device comparisons, consent-aware collection, reporting exports |
| **R3 — Advanced monetization** | Publishers sell metered delivery and optimize inventory | Reconciled CPM/CPC, budgets/pacing, share-of-voice allocation, conversions through authorized integrations, invalid-traffic workflows, experiments, recommendations, advanced financial reporting |
| **R4 — Extended ecosystem** | Agencies and larger networks scale operations | Multisite/network workflows, advanced integrations, optional session replay, white labeling where feasible, additional billing providers, external worker/analytics adapters and advanced forecasting |

R1 requires WooCommerce only **for integrated online checkout**, not to run AdPilot inventory, rendering, moderation, or analytics; no-WooCommerce installations shall retain manual/complimentary campaign workflows. A native standalone online payment provider is a later adapter, not falsely advertised as available in R1. No R3 metered billing shall be enabled until validation and reconciliation gates pass.

## 5. Domain terminology and invariants

| Term | Definition |
|---|---|
| Zone | Logical publisher-defined region, e.g., header, sidebar, article body, footer |
| Container | Layout definition within a zone, e.g., grid, stack, row, carousel, repeater |
| Slot | Stable, addressable advertising position which can be reserved or allocated |
| Slot sequence | Compact definition generating many logical slots with stable, versioned identities |
| Placement binding | Rule connecting zone/container/slot to Gutenberg, theme API, hook, template or injection location |
| Layout version | Immutable published configuration snapshot used to interpret placements and historical analytics |
| Creative | Validated advertising media/text/template and its destination |
| Campaign | Advertiser-managed program with approved creative(s), schedule, contract, placement rules and delivery lifecycle |
| Reservation | Time-bounded hold or purchased allocation of inventory, with explicit expiry and uniqueness/capacity rules |
| Order | Commercial purchase transaction; distinct from campaign moderation and delivery states |
| Allocation | Contractual or operational share/position assigned to campaigns |
| Render instance | Unique occurrence of a logical slot in a particular response/browser context |
| Served / rendered / viewable | Distinct events: selected for delivery / displayed by browser / meeting measurement rules |
| Qualified click | Interaction passing declared validation/deduplication rules; not necessarily a conversion |
| Attention proxy | Estimated engagement from visibility and activity; **not human gaze** |
| Billable event | Event qualifying under a specific verified contract and reconciliation policy |

**Invariants**

- INV-01: A campaign cannot serve until required approval, financial, scheduling, allocation and targeting gates hold.
- INV-02: Inventory reservation operations cannot oversell exclusive capacity under concurrent transactions.
- INV-03: A logical slot keeps a stable identifier across noncontract-breaking layout changes; breaking changes need impact review.
- INV-04: Multiple render occurrences of a zone have distinct DOM/render identities and correct measurement attribution.
- INV-05: A placement configured in layout is not automatically a rendered, viewable, or billable impression.
- INV-06: Editorial previews, bots filtered by policy, and staging environments do not generate production-billable events.
- INV-07: An advertiser cannot inspect or change any other advertiser's confidential resources.
- INV-08: A browser-generated event by itself is insufficient to create a final charge.
- INV-09: Optional tracking refusal does not disable safe baseline ad rendering, subject to applicable regional policies.
- INV-10: No untrusted advertiser JavaScript receives unrestricted first-party execution privileges.

## 6. Functional requirements: inventory and high-density layouts

**FR-INV-001 (R1):** Authorized administrators SHALL create, rename, archive, clone and search zones with stable unique IDs and descriptions.  
**FR-INV-002 (R1):** Zones SHALL contain multiple containers; containers SHALL support single, row, vertical stack and responsive grid configurations.  
**FR-INV-003 (R1):** Administrators SHALL configure slot count without a hard-coded 500-slot product maximum. Tested baseline: 500 logical slots on one page; stress test: 1,000+. Hosting-specific safe limits MAY be enforced with transparent diagnostics.  
**FR-INV-004 (R1):** A single container SHALL generate 100 sidebar or 200 footer slots from a compact configuration, not 100/200 manually persisted Gutenberg blocks.  
**FR-INV-005 (R1):** Responsive layouts SHALL support desktop/tablet/mobile column count, minimum width, aspect ratio, gaps and visibility rules without mutating contractual slot identity.  
**FR-INV-006 (R1):** The system SHALL resolve placement bindings for posts, pages, archives, search contexts, product pages where integrated, template parts and approved custom hooks.  
**FR-INV-007 (R1):** Administrators SHALL preview zones and slots with availability, reservation occupancy, breakpoint and empty-state information.  
**FR-INV-008 (R1):** Layout edits SHALL support draft, preview, validate, publish, immutable version history and controlled rollback.  
**FR-INV-009 (R1):** Changes affecting reserved/sold inventory SHALL warn, block or initiate explicit migration/reapproval before publication.  
**FR-INV-010 (R1):** Administrators SHALL bulk assign, unassign, edit, duplicate and filter generated slots using paginated/virtualized controls.  
**FR-INV-011 (R1):** Slots SHALL have explicit inactive, eligible, reserved, occupied and unavailable states where applicable.  
**FR-INV-012 (R2):** Container types SHOULD extend to carousel, masonry, sticky and conditional/repeater presentations with accessibility and density constraints.  
**FR-INV-013 (R1):** On empty eligible inventory, configurable house creative, collapsed placeholder or reserved blank layout space SHALL be possible without falsely counting impressions.  
**FR-INV-014 (R1):** The system SHALL detect duplicate unintended injection and distinguish it from permitted repeated rendering.

## 7. Functional requirements: Gutenberg and theme independence

**FR-INT-001 (R1):** Register dynamic blocks `adpilot/ad-slot`, `adpilot/ad-zone`, `adpilot/ad-repeater`, `adpilot/ad-showcase` using `block.json` and server-rendered output.  
**FR-INT-002 (R1):** Each block SHALL expose relevant configuration, accessible editor controls, responsive preview and bounded representative rendering; editor previews SHALL not count as paid ad delivery.  
**FR-INT-003 (R1):** `adpilot/ad-zone` SHALL store a compact stable zone reference and permitted overrides instead of one block per generated slot.  
**FR-INT-004 (R1):** Repeater behavior SHALL support explicit interval policies for eligible content contexts; integrations into query loops, product grids and third-party layouts SHALL be adapter-controlled, not assumed universal.  
**FR-INT-005 (R1):** Provide public PHP functions `adpilot_render_slot()`, `adpilot_render_zone()`, and `adpilot_render_container()` with stable, documented argument and return/error semantics.  
**FR-INT-006 (R1):** Provide corresponding shortcodes, e.g. `[adpilot_slot]`, `[adpilot_zone]`, `[adpilot_repeater]`, and `[adpilot_showcase]`.  
**FR-INT-007 (R1):** Provide extensible action/filter hooks and a capability-gated REST API; all integration paths SHALL invoke the same authorization/eligibility/rendering core.  
**FR-INT-008 (R1):** Support classic themes, block themes, template parts and traditional widget areas through appropriately scoped adapters.  
**FR-INT-009 (R1):** Avoid overriding theme global styles; use scoped styles and namespaced classes/CSS variables.  
**FR-INT-010 (R1):** No shortcode, Gutenberg parameter or PHP template option SHALL bypass purchased inventory, creative approval, commercial restrictions, or security rules.  
**FR-INT-011 (R2):** Provide layout/theme diagnostics for unresolved hooks, unsupported selectors, duplicates and rendering conflicts.  
**FR-INT-012 (R1):** Documentation SHALL give minimal working examples for each supported theme integration path.

## 8. Functional requirements: marketplace and advertiser organization

**FR-MKT-001 (R1):** Advertisers SHALL browse a public, filterable catalog of published placements and packages, with dimensions, format eligibility, price, schedule and clear terms.  
**FR-MKT-002 (R1):** Published placement pages SHALL include sanitized preview, responsive context and measured historical estimates when available, labeled as estimates rather than guarantees.  
**FR-MKT-003 (R1):** Authorized advertisers SHALL register, manage business profiles, view their campaigns, invoices and notifications, and resume saved drafts.  
**FR-MKT-004 (R1):** Advertiser organizations SHALL support owner/manager/analyst/billing membership with least-privilege permissions and secure invitations.  
**FR-MKT-005 (R1):** Campaign wizard SHALL guide placement selection, creative upload, targeting where allowed, scheduling, quote review, submission and checkout.  
**FR-MKT-006 (R1):** The wizard SHALL provide validation and clear recoverable errors, maintain drafts and preserve state during normal navigation.  
**FR-MKT-007 (R1):** Advertisers SHALL see distinguishable approval, payment and delivery states.  
**FR-MKT-008 (R1):** Public catalog SHALL expose no advertiser-private, financial-sensitive or visitor-personal records.  
**FR-MKT-009 (R1):** Administrators SHALL manage advertisers, verify organizations where configured, suspend accounts, and audit changes.  
**FR-MKT-010 (R2):** Campaign renewal, duplication, packaged purchasing and organization reporting enhancements SHOULD be available.

## 9. Functional requirements: campaign, creative and moderation

**FR-CAM-001 (R1):** Campaigns SHALL have separately managed approval, financial and delivery state machines.  
**FR-CAM-002 (R1):** Campaigns SHALL support drafts, submission, review, changes requested, approval, rejection, schedule, activation, pause, completion and cancellation/suspension paths.  
**FR-CAM-003 (R1):** Creatives SHALL support safe raster images and text/native ad templates; file dimensions, format, size, destination URL and alt text SHALL be validated.  
**FR-CAM-004 (R1):** Advertisers SHALL upload multiple creative versions and request edits; policy-relevant changes require reapproval before serving.  
**FR-CAM-005 (R1):** Moderators SHALL approve/reject with reasons, maintain audit trails and support emergency stop.  
**FR-CAM-006 (R1):** Paid placement SHALL not imply automatic creative approval; rejection SHALL invoke documented revision, cancellation and refund policies.  
**FR-CAM-007 (R1):** Native/sponsored formats SHALL expose sponsorship disclosure and appropriate link attributes.  
**FR-CAM-008 (R1):** Arbitrary advertiser-controlled JavaScript, unsanitized SVG and unrestricted HTML SHALL be prohibited. Safe interactive/sandboxed formats may be separately designed for later release.  
**FR-CAM-009 (R2):** Video and interactive advertising MAY be supported subject to explicit format, security, performance and accessibility acceptance tests.  
**FR-CAM-010 (R1):** Campaign audit trail SHALL record high-value actions including owner, time, previous/new state, and safe explanation.

## 10. Functional requirements: rates, inventory reservation and commerce

**FR-COM-001 (R1):** Support fixed-price, time-based and exclusive sponsorship price plans with clear units and rate-card version.  
**FR-COM-002 (R1):** Availability SHALL be calculated from binding, capacity, exclusions, reservations and active allocations for the requested period.  
**FR-COM-003 (R1):** Reservation creation SHALL be concurrency-safe, have bounded expiry and be idempotent on retries.  
**FR-COM-004 (R1):** Quote SHALL include currency, units, dates/time zone, inventory, discounts, taxes where configured, expiration and applicable approval/refund conditions.  
**FR-COM-005 (R1):** WooCommerce adapter SHALL map AdPilot commercial orders to WooCommerce orders using supported WooCommerce CRUD/HPOS-compatible APIs.  
**FR-COM-006 (R1):** Payment status SHALL be obtained only from authoritative commerce/gateway callbacks, not browser redirect success alone.  
**FR-COM-007 (R1):** Duplicate, delayed and out-of-order callbacks SHALL not double-allocate inventory or duplicate invoices/charges.  
**FR-COM-008 (R1):** Refunds, cancellation, payment reversals, expired quotes and declined creatives SHALL have explicit policy-governed workflows.  
**FR-COM-009 (R1):** Financial records SHALL maintain auditable adjustments and reconciliation against commercial orders.  
**FR-COM-010 (R1):** Administrators SHALL view order, payment, reservation and fulfillment discrepancies.  
**FR-COM-011 (R3):** Qualified, reconciled CPM/CPC, spending caps, credits and share-of-voice contracts SHALL be introduced only after measurement/billing certification tests.  
**FR-COM-012 (R4):** Additional payment/billing providers MAY be implemented without entangling the core inventory and campaign domains.  
**FR-COM-013 (R1):** When WooCommerce is absent, product SHALL still support administrator-approved complimentary/manual campaigns and all non-checkout functions; online self-service payment UI SHALL clearly state its prerequisite.  
**FR-COM-014 (R1):** Time ranges SHALL be stored in UTC, rendered in selected site/account timezone, and tested across daylight-saving transitions.

## 11. Functional requirements: ad delivery, targeting and browser rendering

**FR-DEL-001 (R1):** Delivery decisions SHALL gate on approved creative, valid contract, active schedule, available allocation, policy eligibility and budget/hold rules.  
**FR-DEL-002 (R1):** Support fixed assignment, priority, weighted rotation and house-ad fallback with auditable selection rules.  
**FR-DEL-003 (R1):** Eligible targeting SHALL include page, post type, category/taxonomy, device/breakpoint and locale; advanced geo/audience policies require privacy/legal review.  
**FR-DEL-004 (R1):** Layout markup SHALL reserve predictable space or use controlled collapsing to minimize layout shifts.  
**FR-DEL-005 (R1):** Load creatives progressively as slots approach the viewport and batch backend decisions; do not fetch every heavy creative upfront on a 500-slot page.  
**FR-DEL-006 (R1):** Limit in-flight fetches, observers and event handlers; reuse shared browser primitives where appropriate.  
**FR-DEL-007 (R1):** Different occurrences of the same slot/zone on a page SHALL have unique render identifiers and correct event attribution.  
**FR-DEL-008 (R1):** Integration SHALL be resilient to full-page caching and common CDNs and shall not cache visitor-specific sensitive decisions in shared contexts.  
**FR-DEL-009 (R1):** Failure of delivery/analytics endpoints SHALL not break the containing WordPress page.  
**FR-DEL-010 (R1):** Admin previews, editor screens, tests and staging mode SHALL be flagged as nonbillable/nonproduction.  
**FR-DEL-011 (R1):** Ad density, slot count and large creative configurations SHALL produce configurable UX/performance warnings; supported logical capacity does not equal permission to show all creatives simultaneously.  
**FR-DEL-012 (R2/R3):** Advanced frequency caps, pacing, viewability-aware delivery and spend-limited allocation SHALL be implemented with explicit data-collection constraints.  
**FR-DEL-013 (R1):** A page SHALL remain navigable and accessible with JavaScript disabled, with documented limitations to dynamic filling and metrics.

## 12. Functional requirements: event measurement and accounting

**FR-MET-001 (R1):** Separate ad requested, selected/served, browser rendered, click observed, click qualified and billable event types.  
**FR-MET-002 (R1):** Distinguish counts and denominators in UI/API; metrics SHALL not silently conflate served with viewable.  
**FR-MET-003 (R1):** Validate events server-side for schema, origin/context where practicable, bounded payload and rate limits.  
**FR-MET-004 (R1):** Provide event IDs and bounded deduplication/aggregation with at-least-once delivery assumptions.  
**FR-MET-005 (R1):** Exclude editor previews, testing traffic and known invalid automation under defined rules.  
**FR-MET-006 (R1):** Aggregate campaign, creative, page, zone and slot statistics by time bucket without requiring an unbounded raw-event history.  
**FR-MET-007 (R2):** Measure viewability with explicit observable-browser thresholds and time-in-view; document unsupported/browser-limited measurement.  
**FR-MET-008 (R2):** Report measurable vs nonmeasurable impressions and reasons where known.  
**FR-MET-009 (R3):** CPC/CPM billing SHALL be based on qualified, reconciled events and contract-specific rules, not client-reported counters alone.  
**FR-MET-010 (R3):** Maintain invalid-traffic investigation and adjustment workflow without claiming perfect fraud detection.  
**FR-MET-011 (R3):** Off-site conversions SHALL require advertiser-installed or otherwise authorized integrations, with attribution model/windows explicitly labeled.

## 13. Functional requirements: heatmaps and behavioral intelligence

**FR-HEAT-001 (R2):** Provide click/tap, scroll-depth and element/slot-visibility heatmaps, with an optional attention-time **proxy** view; never label proxies as eye tracking.  
**FR-HEAT-002 (R2):** Admin SHALL inspect full-page, zone, container and slot-level overlays on vertically scrollable pages.  
**FR-HEAT-003 (R2):** Browser collector SHALL use normalized coordinates plus stable semantic element/slot references.  
**FR-HEAT-004 (R2):** Aggregates SHALL be segmented by page layout version and responsive device class; incompatible geometries shall not be combined silently.  
**FR-HEAT-005 (R2):** Editor SHALL show selected date range, event sample size, sampling status, denominator, measurement limitations and empty states.  
**FR-HEAT-006 (R2):** Administrators SHALL filter by eligible campaign, creative, zone, page/template, device and reporting period.  
**FR-HEAT-007 (R2):** Support side-by-side comparison of compatible layout periods and numeric accessible alternatives to color-only heatmaps.  
**FR-HEAT-008 (R2):** Tracking SHALL be consent-aware where required, exclude protected/sensitive content, and minimize visitor identifiers.  
**FR-HEAT-009 (R2):** Changing page geometry, infinite scroll or AJAX insertion SHALL not silently assign historical events to the wrong placement.  
**FR-HEAT-010 (R4):** Optional sampled session replay SHALL be disabled by default, separately permissioned, sensitive-content-safe, retention-controlled, and never a prerequisite for regular ad serving.  
**FR-HEAT-011 (R2):** Heatmap tracking and visualization SHALL operate without mandating Hotjar, Clarity or another proprietary hosted service.

## 14. Functional requirements: dashboards, reporting and intelligence

**FR-REP-001 (R1):** Administrator dashboard SHALL surface revenue/order state, active/pending campaigns, placement occupancy, basic delivery status and operational alerts.  
**FR-REP-002 (R1):** Advertiser dashboard SHALL expose only their own campaigns, approval/payment status, qualified statistics and invoices where available.  
**FR-REP-003 (R1):** Reports SHALL support period, campaign, creative, zone/slot and device filters where data is available.  
**FR-REP-004 (R1):** Report values SHALL identify units, currencies, denominators, time zone and data freshness.  
**FR-REP-005 (R2):** Add viewability, scroll, placement exposure and heatmap summaries; export aggregate CSV/JSON as allowed.  
**FR-REP-006 (R3):** Add conversion attribution, finance reconciliations, experiments, anomaly analysis and evidence-based placement recommendations.  
**FR-REP-007 (R3):** Forecasts and estimates SHALL be visually labeled and accompanied by assumptions and uncertainty.  
**FR-REP-008 (R2):** Long-running report exports SHALL use queued background processing and scoped download authorization.  
**FR-REP-009 (R2):** Administrators SHALL review pending jobs, delivery/aggregation health and retention cleanup status.  
**FR-REP-010 (R1):** Operational metrics, raw behavioral data and replay recordings SHALL not be exposed through ordinary advertiser reporting.

## 15. Functional requirements: UX, accessibility, localization and settings

**FR-UX-001 (R1):** Provide distinct admin console, advertiser portal, public storefront, Gutenberg block controls and Layout Studio, under one coherent design system.  
**FR-UX-002 (R1):** Every primary workflow SHALL specify empty/loading/error/success/confirmation states and recovery paths.  
**FR-UX-003 (R1):** Layout Studio SHALL use visual canvas + zone tree + inspector, while also supporting keyboard-accessible and nonvisual editing.  
**FR-UX-004 (R1):** Screens containing hundreds of slots SHALL display summarized/paginated controls rather than hundreds of simultaneously mounted edit widgets.  
**FR-UX-005 (R1):** Advertiser wizard SHALL autosave drafts, show a transparent cost/approval summary, and avoid silent loss of changes.  
**FR-UX-006 (R1):** Primary interface SHALL meet WCAG 2.2 AA as a project target, with automated and manual verification.  
**FR-UX-007 (R1):** Use WordPress localization APIs; support RTL/LTR layouts, locale-aware dates, numbers and currency presentation.  
**FR-UX-008 (R1):** Responsive controls and page previews SHALL work across phone, tablet and desktop representative widths.  
**FR-UX-009 (R1):** Avoid unnecessary global theme CSS/JS and unrelated plugin side effects.  
**FR-UX-010 (R2/R4):** Offer richer saved report views, white-label/site branding and advanced multisite workflows subject to individual release scopes.  
**FR-UX-011 (R1):** AdPilot SHALL expose high-quality diagnostic feedback rather than generic fatal errors for configuration and integration failures.

## 16. Functional requirements: security, privacy, extensibility and operations

**FR-OPS-001 (R1):** Apply capability checks plus resource ownership to every sensitive REST/UI/CLI operation.  
**FR-OPS-002 (R1):** Validate and sanitize inputs, escape output by context, use prepared SQL and defend against CSRF, XSS, SSRF, upload abuse, replay and broken object authorization.  
**FR-OPS-003 (R1):** Audit financial changes, campaign approval, placement publication, account membership and emergency pause.  
**FR-OPS-004 (R1):** Enable privacy policy guidance, configurable retention, eligible data export/deletion and consent integration points.  
**FR-OPS-005 (R1):** Exclude sensitive DOM/form/payment values from any analytics collection; absence of user consent must prevent collection where required.  
**FR-OPS-006 (R1):** Provide diagnostics, explicit job retries, failed-job reporting, queue monitoring and supportable system cron/WP-CLI execution.  
**FR-OPS-007 (R1):** Implement schema-versioned installation/upgrades, data-preserving deactivation, intentional uninstall behavior and upgrade testing.  
**FR-OPS-008 (R1):** Distributable libraries, assets and source code SHALL have documented GPL-compatible licensing; generate dependency register/SBOM and required notices.  
**FR-OPS-009 (R1):** Use a versioned REST namespace and stable hooks/shortcodes/PHP APIs; changes SHALL follow documented deprecation policy.  
**FR-OPS-010 (R1):** Expose structured, redacted logs, health checks, and safe administrator diagnostic exports.  
**FR-OPS-011 (R4):** Support optional independently deployed workers/analytics integrations without a forced redesign of core WordPress services.  
**FR-OPS-012 (R1):** Plugin bootstrap SHALL not require WooCommerce or proprietary remote services merely to load.  
**FR-OPS-013 (R1):** Plugin activation SHALL create only documented, owned resources and SHALL avoid modifying unrelated existing pages/settings without authorization.

## 17. Business rules and policy constraints

**BR-001:** Inventory sale is permitted only for an active, publishable placement with sufficient eligible capacity.  
**BR-002:** A temporary hold expires after its configured TTL; extension requires an explicit transaction.  
**BR-003:** A successful online payment does not bypass creative moderation.  
**BR-004:** If a paid campaign is rejected, revision/refund/cancellation options follow recorded policy; never silently keep funds without an enforceable fulfillment path.  
**BR-005:** An exclusive reservation cannot be double sold over overlapping contract time.  
**BR-006:** Changes to a slot's commercial meaning require impact analysis and resolution of affected contracts.  
**BR-007:** A rejected or unapproved creative cannot serve; approved prior versions may serve only if contract and policy permit.  
**BR-008:** Delivery eligibility is recomputed against current authoritative campaign state, not based solely on a stale cached creative.  
**BR-009:** Ad-preview, administrator-editing and synthetic load-test events are excluded from revenue-bearing analytics.  
**BR-010:** Advertising density policies and ad-network restrictions are configurable; default warnings should discourage excessive clutter and inadvertent clicks.  
**BR-011:** Advertiser reports show validated aggregate outcomes, not visitor-level session content.  
**BR-012:** Raw events have finite retention and are not a permanent financial source of truth; reconciled financial entries are independently retained under applicable policy.  
**BR-013:** Conversion and performance claims distinguish observed facts, attribution models, estimates and projections.  
**BR-014:** Currency arithmetic uses precise decimal/minor-unit representations, never floating point for ledger authority.  
**BR-015:** Timestamps are stored in UTC; contract terms display user-selected time zone and daylight-saving behavior explicitly.  
**BR-016:** Accessibility and mobile usability may constrain a particular layout even when there is ample logical inventory capacity.  
**BR-017:** Any third-party tracking, behavioral replay, or remote conversion adapter requires disclosure, consent gating where appropriate, and documented data flows.  
**BR-018:** Manual administrator overrides of paid placement allocation require reason, authorization and audit logging.

## 18. Key user journeys and failure paths

### J-01: Advertiser purchase, review and delivery (R1)

1. Visitor opens public advertising catalog; sees current availability, rate units, eligible formats and policies.
2. Advertiser selects one or more placements and a valid schedule; quote is calculated with version/expiry.
3. Advertiser registers/logs in; campaign draft records creative and permitted targeting.
4. System atomically reserves requested inventory for the checkout window and creates linked commercial order.
5. WooCommerce checkout completes or fails; backend accepts only authoritative payment state.
6. Creative enters moderator workflow. Administrator approves, requests changes or rejects with explanation.
7. On approval plus fulfilled commercial conditions, campaign becomes scheduled/active; background tasks must recover from transient failure.
8. Eligible browser placement requests receive creative through unified renderer; metrics appear by freshness window.
9. Campaign concludes, expires or is suspended; financial and performance records remain auditable.

**Failure cases:** slot sold concurrently; quote expired; payment webhook duplicated/delayed; media upload rejected; advertiser changes URL after approval; activation job missed; cached stale state; campaign partially fulfilled; customer disputes billing.

### J-02: Publisher constructs 500-slot page (R1)

1. Administrator creates header 4, sidebar 100, in-content 196 and footer 200 logical slots through four or more containers.
2. Layout Studio shows aggregate counts, responsive rules and availability without rendering 500 full creatives in editor.
3. Administrator binds zones to Gutenberg, PHP template calls or page-context rules.
4. Preview verifies layout across breakpoints and reports conflicts with existing reservations.
5. Administrator publishes immutable layout version.
6. Public page emits lightweight correct structure; viewport-aware loader fetches near-view creatives in batches.
7. Tracking distinguishes configured capacity from eligible, rendered and viewable impressions.

**Failure cases:** theme hooks duplicate zone; shortcodes nested or repeated; 200-slot footer on narrow mobile screen; infinite-scroll content shifts geometry; shared CDN cache; JavaScript unavailable; CSS collision; campaign expires while page open.

### J-03: Heatmap placement review (R2)

1. Administrator selects page/layout version, date interval and device class.
2. UI renders compatible sanitized page representation with overlay aligned to stable slots/elements.
3. Administrator toggles click, scroll, viewability and attention-proxy views.
4. Sample size, missing observations, consent filtering, loading and measurement limitations are visible.
5. Administrator compares two periods or slots and exports permitted aggregate results.

**Failure cases:** responsive geometry differs; layout version changed; tracking disabled; low statistical volume; page contains private fields; overlay unavailable; third-party page builder changes DOM unexpectedly.

### J-04: Publisher changes a sold layout (R1)

1. Administrator modifies column count/position/removes slot.
2. System computes impact on active reservations and contracts.
3. Compatible nonbreaking changes can publish; breaking changes are blocked or routed into controlled migration/approval.
4. New version publishes atomically; old version remains usable for historical metric interpretation.

### J-05: Operational incident (R1)

1. Queue stalls, payment callback fails, or ad endpoint times out.
2. System records redacted incident signals and safe customer-facing states.
3. Site content remains usable; administrators can inspect health and retry idempotent operations.
4. Reconciliation job repairs recoverable inconsistency; manual intervention is audited where necessary.

## 19. Nonfunctional requirements

**NFR-001 — Architecture:** Modular-monolith design with explicit domain/application/infrastructure/presentation boundaries; PHP WordPress host; no mandatory external application server in R1.  
**NFR-002 — Maintainability:** WordPress Coding Standards, PHP static analysis, TypeScript strict mode, clear module APIs, source-level documentation and reviewed dependencies.  
**NFR-003 — Rendering:** A 500-logical-slot page must not eagerly fetch or paint 500 heavy creatives. Browser work and network concurrency must be bounded; no per-slot PHP query pattern.  
**NFR-004 — Performance:** Establish and validate environment-specific p75/p95 budgets for request overhead, Core Web Vitals, query count, JS bundle size, browser memory, batching and queue lag; declare benchmark hardware, creative sizes and cache configuration.  
**NFR-005 — Reliability:** Idempotent payment webhooks and jobs; resilient scheduler; safe fallback if ad delivery or analytics endpoint fails.  
**NFR-006 — Financial correctness:** Database-enforced uniqueness/capacity controls, concurrency testing, reconciliation, immutable adjustment trail and precise currency arithmetic.  
**NFR-007 — Security:** Threat model, dependency scans, permission tests, hardened creative handling, secure REST, server-side validation and emergency shutdown.  
**NFR-008 — Privacy:** Data minimization, regional consent policy, no sensitive input collection, finite behavioral-event retention and protected access.  
**NFR-009 — Accessibility:** WCAG 2.2 AA target for critical UIs, including nonvisual alternatives to heatmaps and drag/drop interactions.  
**NFR-010 — Compatibility:** Validated WordPress/PHP/MySQL/MariaDB matrix; support classic and block themes, modern browsers, WooCommerce HPOS when commerce adapter enabled.  
**NFR-011 — Internationalization:** Translation-ready strings, RTL/LTR support, reliable date/time-zone and currency displays.  
**NFR-012 — Observability:** Logs, queue metrics, failed-event reporting, health diagnostics, financial discrepancy alerts and safe support exports.  
**NFR-013 — Extensibility:** Versioned REST API, documented hooks, stable PHP and shortcode contracts, adapter interfaces and deprecation policy.  
**NFR-014 — Licensing:** GPL-compatible distribution, recorded license texts/notices, transitive dependency screening, no unclear copyrighted assets.  
**NFR-015 — Upgrade safety:** Idempotent migrations, version compatibility checks, reliable backup guidance, data-preserving deactivation and explicit uninstall.  
**NFR-016 — Usability:** Critical flows verified through task-based usability tests; major actions provide comprehensible states, feedback and undo/recovery when feasible.

## 20. First-release acceptance suite (product-level)

**AC-001:** Publisher creates and publishes a 500-slot page consisting of 4 header, 100 sidebar, 196 in-content and 200 footer logical slots. Layout Studio remains usable; publishing and responsive preview succeed.  
**AC-002:** One `adpilot/ad-zone` block can reference a 200-slot footer container without storing 200 Gutenberg blocks.  
**AC-003:** Four supported integration paths (Gutenberg, PHP function, shortcode, and supported hook binding) render through one policy-aware engine with correct per-occurrence identifiers.  
**AC-004:** Public page initially loads bounded creatives near viewport and does not miscount 500 configured slots as impressions.  
**AC-005:** Advertiser can discover an available slot, obtain a quote, reserve, pay through configured WooCommerce, upload creative, request review and observe activation when approved.  
**AC-006:** Two concurrent buyers cannot successfully reserve the same exclusive inventory over overlapping time windows.  
**AC-007:** Duplicate payment callbacks and campaign activation retries do not double-allocate, double-invoice or double-activate.  
**AC-008:** Creative changes requiring reapproval cannot bypass review and cannot serve in an unapproved state.  
**AC-009:** Advertiser A cannot access advertiser B's campaigns, invoices, raw behavioral data or privileged reports through UI or direct API calls.  
**AC-010:** Classic theme, block theme, common caching setup and supported WooCommerce HPOS mode pass the declared compatibility suite.  
**AC-011:** Broken analytics endpoint does not crash the public site, block reading content or silently produce fictitious billable events.  
**AC-012:** Privacy-sensitive pages/inputs are excluded from collection; denied consent prevents prohibited collection.  
**AC-013:** All primary application workflows pass agreed manual accessibility and usability gates.  
**AC-014:** Installer works without WooCommerce for nonpayment functions and provides an accurate diagnostic for unavailable online checkout.  
**AC-015:** Release contains reproducible build, automated tests, license notices, migrations and administrator/developer documentation.

**R2 acceptance additions:** a 500-slot long-page heatmap aligns with active layout version and device class; click/scroll/viewability denominators are disclosed; optional replay is absent unless intentionally enabled, authorized and appropriately consented.  
**R3 acceptance additions:** metered CPM/CPC invoices reconcile from validated billable events; advertiser contract disputes can be investigated without depending solely on mutable frontend counters.

## 21. Nonfunctional validation fixtures

Minimum fixtures shall include: empty WordPress site; block theme and classic theme; WooCommerce disabled/enabled HPOS; slow shared hosting fixture; Redis object cache/CDN fixture where available; anonymous and authenticated traffic; anonymous browser with tracking denied; RTL locale; device widths representative of mobile/tablet/desktop; 4/100/196/200 zone composition; 1,000-slot stress layout; 100 competing advertiser campaigns; duplicate/late webhook payloads; delayed background worker; malicious creative attempts; accessibility keyboard navigation; altered responsive layout versions; and full-page scrolling with sticky components.

Fixtures must be versioned and reproducible. Reports shall record tested environment, dataset and exact outcome rather than asserting blanket cross-platform success.

## 22. Data classification and retention requirements

- **Public:** Published placement catalog, published rate terms and advertising disclosures.
- **Advertiser confidential:** Draft creatives, contracts, organization contacts, invoices, private campaign reports.
- **Publisher confidential:** Revenue breakdowns, internal allocation decisions, moderation audits, investigation details.
- **Restricted behavioral:** Tracking events, pseudonymous/session linkages, playback, layout snapshots containing visitor-derived data.
- **Financial audit:** Ledger entries, refunds, payment mappings and tax/audit records subject to configured jurisdictional obligations.

Default retention schedules must be proposed and approved in the privacy/operations specifications, not guessed as universal legal requirements. Heatmap event collection must be disabled when prohibited by applicable consent state, and raw snapshots must exclude protected contents. Deletion must respect legitimate financial record preservation duties without retaining unrelated visitor behavioral data.

## 23. Product analytics and reporting formula governance

Each metric used for commercial decisions needs a defined numerator, denominator, time window, attribution policy, invalid-event handling and confidence/measurement coverage note. At minimum:

- **Qualified CTR** = qualified ad clicks / applicable qualified impression denominator; UI must state which impression class is used.
- **Viewability rate** = viewable impressions / measurable impressions, with unmeasurable share displayed separately.
- **Fill rate** = filled eligible ad opportunities / eligible ad opportunities, not total configured slots.
- **Inventory utilization** = contractually allocated capacity / sellable capacity over a specified time window, accounting for exclusivity and overlapping products.
- **Recognized revenue** = reconciled financial values under configured accounting policy; distinct from collected cash or order total.
- **Attention time** = measured viewport/engagement proxy; never represented as actual gaze or cognitive attention.

The measurement specification shall formalize these, including edge cases and data freshness. No dashboard can invent a numerator/denominator independently.

## 24. Dependencies, assumptions and unresolved decisions

### Confirmed product direction

- WordPress-native PHP plugin, React/TypeScript for rich UI, lightweight public ad renderer.
- Configurable high-density zone/container/slot model.
- Four Gutenberg blocks plus PHP, shortcode and hook-based theme integration.
- Self-service advertising marketplace and administrator-controlled moderation.
- Native aggregated analytics and optional session replay; no mandatory proprietary analytics SaaS.
- GPL-compatible distribution as a product goal.

### Decisions that must be settled before affected work begins

| Decision ID | Decision | Default proposal | Must be decided by |
|---|---|---|---|
| DEC-001 | Minimum supported WordPress/PHP/DB versions | Freeze current tested matrix, PHP 8.2+ preferred | Phase 0/2 |
| DEC-002 | First-release payment provider | WooCommerce checkout adapter | Phase 0/6 |
| DEC-003 | Manual campaign financial path | Complimentary or administratively approved, no implied online charge | Phase 0/6 |
| DEC-004 | R1 creative formats | Safe image + text/native templates | Phase 0/5 |
| DEC-005 | Default tracking consent policies | Nonessential behavioral collection consent-gated per applicable region | Phase 0/8 |
| DEC-006 | Default event/raw data retention | Conservative bounded values after privacy review | Phase 0/7 |
| DEC-007 | Booking hold TTL and refund policy | Configurable with documented defaults | Phase 0/6 |
| DEC-008 | Initial theme compatibility matrix | Official block/classic themes plus target builders | Phase 0/4 |
| DEC-009 | Supported multisite scope | Site isolation first; network features R4 | Phase 0/10 |
| DEC-010 | Advanced payment billing methods | Metered CPM/CPC gated until R3 certification | Phase 0/9 |
| DEC-011 | Product/trademark/public slug | AdPilot working name, perform clearance | Before public launch |
| DEC-012 | First-release language set | English reference; fully localization-ready and RTL infrastructure | Phase 0/1 |

Unresolved decisions shall be tracked with an owner, deadline, impact and chosen disposition. Implementers shall not invent financial, privacy or legal policy silently.

## 25. Risks and mitigations

| Risk | Mitigation | Owner |
|---|---|---|
| Ad overload degrades visitor experience | Density guidelines, progressive loading, real device benchmarks | Frontend/UX |
| Exclusive slots oversold | DB constraints, transactions, concurrency tests | Database/Commerce |
| Creative injects malicious content | Safe formats, sanitization, sandbox policy and moderation | Security |
| Ad measurements drive incorrect bills | Explicit metrics, event quality, reconciliation and disputes | Measurement/Finance |
| Cache serves stale or cross-user ad data | Separate cache-safe slot markup from dynamic decisions | Delivery |
| Layout changes break contracts or heatmaps | Stable slot IDs, immutable layout versions and migration review | Inventory/Analytics |
| Behavioral data contains sensitive information | Collection-time masking/exclusions, consent, privacy testing | Privacy |
| Dependency conflicts or license violations | SBOM, automated scanning and manual compatibility review | License/DevOps |
| WordPress update breaks integration | Supported version matrix and continuous compatibility suite | WordPress/QA |
| Overly broad R1 scope delays launch | Release gates and explicit R2–R4 deferrals | Product Owner |

## 26. Architecture constraints (normative for downstream specs)

- Deployable core is **one WordPress plugin**, using a modular-monolith internal architecture.
- WordPress bootstrap and adapters live outside pure application/domain rules where possible.
- High-volume data uses appropriate indexed custom tables; ordinary WordPress users/media/settings are reused when fit for purpose.
- REST API under an explicitly versioned namespace; capability + ownership authorization.
- Background tasks and event ingestion are retriable and idempotent.
- Gutenberg blocks are dynamic and consume core services; PHP/shortcode/hook integrations cannot bypass security or commercial rules.
- Admin/advertiser rich applications may use React; the public renderer should be lean and largely independent of React.
- Database and browser instrumentation shall preserve layout version and logical/render identity.
- Optional integrations/adapters must be isolated; WooCommerce absence does not cause bootstrap failure.

## 27. GPT-6 High implementation handoff rules

The implementation agent shall:

1. **Read this PRD and the relevant canonical specifications before coding**; enumerate dependencies and detect contradictions explicitly.
2. Create a requirement-to-module and requirement-to-test traceability ledger referencing `FR-*`, `BR-*`, `NFR-*` and `AC-*` IDs.
3. Work vertically by approved release slice: backend policy → persistence/API → frontend/block/theme integration → tests → docs.
4. Implement business invariants as enforced code, not only as UI restrictions or prose.
5. Never report a feature as complete without executable tests and a validated user scenario.
6. Never claim code/build/test success without actually running the relevant command; record blocked verification honestly.
7. Keep design quality and frontend performance as release gates, not optional polish.
8. Avoid speculative dependencies, fabricated external API contracts, pretend payment confirmations and unsupported browser tracking claims.
9. Preserve migration compatibility, auditable financial events, stable slot IDs and product privacy rules throughout refactors.
10. Update traceability, architecture decisions and documentation when implementation decisions change.
11. Use self-contained work packages sized for GPT-6 High reasoning context; keep clear entry criteria, files/modules touched, expected outputs, verification commands and acceptance tests.
12. Stop and flag a policy conflict when a proposed implementation contradicts security, consent, financial correctness or a paid inventory contract; do not silently resolve it in favor of convenience.

### Recommended task completion template

For each completed implementation package, record: requirement IDs; affected modules; business invariants; data/API changes; UX states; security/privacy considerations; unit/integration/E2E tests; commands actually executed and their outcomes; migration/rollback needs; known limitations; documentation updates; and next dependencies.

## 28. Documentation interface and ownership

Follow-on specifications, in order, shall cover: feature acceptance; personas/journeys; business rules; permissions; architecture; module boundaries; state machines; schema; APIs; event/jobs; UX; design system; administrator experience; advertiser experience; Layout Studio; blocks; theme API; inventory/reservations; delivery; frontend performance; creatives; commerce; measurement; heatmaps; replay; reporting; optimization; external integrations; security; privacy; licenses; compatibility/i18n; QA; performance; CI/deployment; operations; lifecycle management; implementation backlog; and user/developer guides.

No future specification may redefine a `FR-*`, `BR-*`, or `NFR-*` meaning without a documented revision to this PRD and impact analysis.

## 29. References and verification sources

These are primary sources for platform/legal-compatible *engineering assumptions*, not substitutes for product requirements:

- WordPress Block API and dynamic blocks: <https://developer.wordpress.org/block-editor/reference-guides/block-api/>
- `block.json` metadata and server registration: <https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/>
- WordPress REST API: <https://developer.wordpress.org/rest-api/>
- WordPress plugin repository guidelines and GPL compatibility: <https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/>
- WordPress licensing: <https://wordpress.org/about/license/>

Dependency versions, WordPress compatibility and legal obligations must be rechecked at release time. License compatibility is a requirement to verify, not something inferred from public source availability.

---

**Document-end checkpoint:** This PRD is the product-scope baseline. The next document is **ADP-DOC-002 — Detailed Feature Catalog, Requirement Traceability & Acceptance Matrix**. No implementation is represented as already completed by this specification.
