# AdPilot — User Personas, Jobs-to-Be-Done, User Journeys & Workflow Specifications

**Document ID:** ADP-DOC-003  
**Filename:** `03_ADPILOT_USER_PERSONAS_JTBD_USER_JOURNEYS_WORKFLOWS.md`  
**Version:** 1.0.0 (implementation specification)  
**Status:** Proposed — requires Product Owner, UX Lead, Technical Lead, AdTech, Finance, Security/Privacy and QA sign-off  
**Date:** 2026-10-11  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Primary implementers:** GPT-6 High and human product, UX, frontend, WordPress, AdTech, finance, privacy, QA and operations teams  
**Document owner:** UX Lead (experience contracts); Product Owner (journey priorities and scope)  
**Source baselines:** [ADP-DOC-001 — PRD](01_ADPILOT_PRODUCT_REQUIREMENTS_DOCUMENT.md) and [ADP-DOC-002 — Feature Catalog & Traceability](02_ADPILOT_DETAILED_FEATURE_CATALOG_TRACEABILITY_ACCEPTANCE.md), both v1.0.0  
**Normative precedence:** Approved PRD and ADRs → ADP-DOC-002 feature/acceptance contracts → this journey/UX specification → future screen and implementation specifications. Where a conflict occurs, record a decision and update the impacted artifact; do not silently change the baseline.

---

## 1. Purpose, completeness standard and usage

This document defines *who uses AdPilot, why they use it, how they accomplish goals, which screens and system states they encounter, and precisely how the product must respond to routine, exceptional and hostile conditions*. It is a specification for behavior—not illustrative marketing copy—and is intended for direct decomposition into GPT-6 High implementation work packages, UX prototypes and Playwright/PHP integration tests.

**In scope:** publisher administration; ad inventory and responsive Layout Studio; 500/1,000+ logical-slot layouts; Gutenberg/block and classic-theme paths; anonymous catalog discovery; advertiser registration, organizations and onboarding; quote/reservation/checkout; creative moderation; campaign state; ad delivery; measured reporting; heatmaps and consent; finance operations; accessibility; system administration; and release-gated advanced workflows.

**Cross-cutting non-negotiables:**

1. A logical slot is not a rendered, measurable, viewable or billable impression. A 500-slot page must be usable without eagerly loading 500 creatives (`FR-INV-003..004`, `FR-DEL-004..006`, `BR-009`, `NFR-003`).
2. Payment confirmation is *authoritative provider/backend state*, never merely a successful browser redirect (`FR-COM-006..007`). Creative approval and delivery eligibility are distinct (`FR-CAM-001`, `BR-003`, `BR-007`).
3. Sold inventory cannot be structurally remapped or double sold by a visual editor or concurrent checkout (`FR-INV-009`, `BR-005..006`).
4. Each user action is checked server-side for site capability and applicable advertiser-organization/object ownership. UI controls do not grant permission (`FR-OPS-001`, `AC-009`).
5. R1 self-service **online checkout requires WooCommerce**; core inventory, moderation, delivery and approved manual/complimentary workflows remain operational without it (`FR-COM-013`, `AC-014`).
6. Heatmaps are native R2 aggregated proxies; no eye-tracking claim or mandatory third-party SaaS. Session replay is optional R4, off by default, separately governed (`FR-HEAT-001`, `FR-HEAT-010..011`).
7. All critical workflows require accessible nonvisual equivalents, responsive operation, recoverable error states and meaningful task-based usability validation (`NFR-009`, `NFR-016`, `AC-013`).

### 1.1 Identifier and status conventions

| Identifier | Meaning | Ownership |
|---|---|---|
| `PERS-xx` | Behavioral persona/profile; not a WordPress role | UX Lead |
| `JTBD-xx` | A job-to-be-done and outcome | Product Owner + UX Lead |
| `UJ-xx` | Full cross-interface user journey | UX Lead + domain owner |
| `WF-xx` | Atomic workflow/state contract | Module owner |
| `UI-01..UI-20` | Existing screen identifiers retained from ADP-DOC-002 | Frontend Lead |
| `UXA-xxx` | This document's UX acceptance scenario | QA + UX Lead |
| `FR-*` / `BR-*` / `NFR-*` / `AC-*` / `AT-*` / `E2E-*` | Immutable baseline requirements and tests | ADP-DOC-001 / 002 |
| `R1..R4` | Release commitment; never inferred from screen appearance | Product Owner |

**Source rule:** This document does not change the meaning of existing `FR-*` or `UI-*` identifiers. Every `UJ-*` references baseline IDs and receives an independent set of acceptance tests. UXA tests specified here are planned criteria, **not claims of executed or passed tests**.

### 1.2 Release gates and UX behavior

| Release | UX outcome | Required journeys |
|---|---|---|
| **R1** | Buy, review, schedule and serve fixed/time-based placements; manage very large layouts safely | All explicitly R1 `UJ-*` flows below; complete core accessible interface states |
| **R2** | Explore native full-page and ad-specific heatmaps with truthful metrics, consent and layout version awareness | R2 analytical journeys; viewability, heatmap/export reporting |
| **R3** | Manage contractually verified CPC/CPM/SoV, attribution and optimization | Advanced billing, disputes and experiment journeys |
| **R4** | Optional replay, provider adapters and multisite/network UX | Explicit opt-in extension journeys; independently gated |

Journey inclusion does not imply feature availability before its release. An R1 interface must not advertise an R3 billing model as immediately purchasable. Disabled future functionality must either be hidden or clearly marked not available, without a dead CTA.

---

## 2. Research framework, assumptions and validation protocol

These personas and jobs are **design hypotheses** constructed from the product requirements and domain analysis; they are not presented as actual user-interview findings. Before final UX acceptance, validate with representative publishers, nontechnical advertisers, agency campaign managers, moderators, finance staff, and site editors. Avoid anchoring the research on a single customer's custom theme.

### 2.1 Research questions

- Can a first-time advertiser understand pricing units, available inventory, time zone and moderation conditions before committing to payment?
- Can a publisher create four header slots, one 100-slot sidebar, 196 in-content slots and one 200-slot footer without authoring 500 blocks?
- Can an editor distinguish *logical slot* versus *rendered creative* and understand why a configured slot has no current advertisement?
- Can a finance reviewer reconstruct payment, reservation, fulfillment and refund state without editing campaign delivery arbitrarily?
- Can an analyst distinguish served/rendered/measurable/viewable impressions from clicks and estimated attention proxies?
- Can screen-reader/keyboard users buy placements, operate the calendar, edit layouts, review moderation and access tabular heatmap data?
- Can a developer embed the same zone from Gutenberg, shortcode, PHP and hook without conflicting policy outcomes?
- Can an administrator identify degraded queue/payment/analytics health and recover safely without falsifying success?

### 2.2 Validation methods and evidence

| Method | Participants/fixture | Decision output |
|---|---|---|
| Moderated task observation | First-time advertiser, experienced advertiser, publisher administrator | Discoverability, confusion points, recovery needs |
| Unmoderated prototype test | Mixed browser/device and language participants | Navigation/task completion without facilitator help |
| Screen-reader and keyboard inspection | Assistive technology users and accessibility engineer | Focus order, announcements, alternatives |
| Contextual workflow walkthrough | Moderator, AdOps and finance representative | Handoffs, exceptions, audit needs |
| Prototype comparison | Layout Studio and purchase wizard alternatives | Preferred flows with evidence, not aesthetic opinion alone |
| Security/privacy walkthrough | Privacy and security specialists | Collection boundaries and deny-path behavior |
| Performance-backed usability | 500/1,000+ slot fixtures on reference devices | UI responsiveness and perceived latency |

All studies must record version, environment, scenario, outcome, critical errors, participant feedback and disposition. Targets (task success, time and satisfaction) are approved in the study plan after baseline research; no fabricated benchmarks are asserted.

### 2.3 Research sampling boundaries

Test low-experience and expert operators; business users who do not know CPM/CTR jargon; mobile advertisers; keyboard-only users; RTL readers; experienced WordPress editors; cash/finance operators; and teams operating with WooCommerce disabled. Account for time-zone and currency comprehension, paid reservation anxiety, and confusing differences between *campaign status*, *creative status* and *payment status*.

---

## 3. Personas, permissions and behavioral needs

A **persona** represents a task, context and usability requirement; it must not automatically become a WordPress role. Permission enforcement derives from the Role/Capability model in ADP-DOC-001 and the Identity capability in ADP-DOC-002. One person can fulfill multiple personas, but their actions remain capability-scoped and audited.

| Persona | Priority/release | Goal and success | Primary surfaces | Key constraint |
|---|---|---|---|---|
| **PERS-01 — Independent publisher / site owner** | Primary, R1 | Sell inventory, approve purchases, understand revenue, keep site usable; **success:** Time to publish first valid inventory; first approved paid campaign; low critical support load | Admin dashboard, Layout Studio, rates, moderation, diagnostics | Cannot equate 500 slots with 500 impressions; must understand online checkout dependency |
| **PERS-02 — Advertising operations manager** | Primary, R1 | Create zones, handle exceptions, pace and troubleshoot delivery; **success:** 500-slot configuration without manual block duplication; no accidental inventory contract violation | Slot navigator, calendar, campaign queue, delivery diagnostics | Must not alter paid contracts without approved workflow |
| **PERS-03 — Small business advertiser (novice)** | Primary, R1 | Compare reach/format, upload creative, see full price, pay, know next step; **success:** Can independently complete supported purchase and distinguish payment from approval | Public catalog, placement detail, campaign wizard, checkout and status | Only own organization data; may not infer measured reach from estimates |
| **PERS-04 — Agency campaign manager** | Primary, R1/R2 | Create/edit campaigns, distribute assignments, compare performance; **success:** Efficient reuse; least-privilege access; predictable campaign status | Advertiser dashboard, creative library, campaign details | Only delegated organizations; no cross-client leakage |
| **PERS-05 — Advertiser organization owner** | Primary, R1 | Invite/remove members, approve spending and see organizational history; **success:** No unauthorized membership changes; transparent audit trail | Organization/profile/member screens, invoices | Cannot assign privileged WordPress/publisher roles |
| **PERS-06 — Advertiser analyst** | Secondary, R1/R2 | Filter verified metrics, export allowed aggregates, communicate results; **success:** Correctly interprets qualified versus unmeasurable metrics | Advertiser reports and campaign history | No visitor-level data or other organizations |
| **PERS-07 — Advertiser billing specialist** | Secondary, R1 | Review order totals, payment state, invoices and adjustments; **success:** Reconstructable order/payment history with authoritative status | Billing summary, invoice history and purchase details | No campaign editing or sensitive publisher revenue |
| **PERS-08 — Creative moderator** | Primary, R1 | Review safety, disclosures, URLs; request changes; emergency pause; **success:** No unapproved version serves; reason communicated clearly | Moderation queue, side-by-side creative diff, audit trail | Cannot override finance or edit payments |
| **PERS-09 — Publisher finance manager** | Primary, R1/R3 | Track payment state, refunds, discrepancies and later metered billing; **success:** Zero unexplained duplicate financial effects in tests | Order dashboard, reconciliation, dispute center | Financial permissions only, independently audited |
| **PERS-10 — Publisher analytics manager** | Primary, R1/R2 | Compare zones, inspect heatmaps and exposure, report opportunity; **success:** Can identify layout version and meaningful sample/limits | Reports, heatmap explorer, measurement coverage | Aggregates only; replay needs separate permission |
| **PERS-11 — WordPress content editor** | Primary, R1 | Insert blocks, preview responsiveness, avoid duplicate injection; **success:** One zone block references hundreds of slots compactly | Gutenberg inspectors, patterns, editor preview | Editor cannot override campaign/contract security |
| **PERS-12 — Theme/integration developer** | Secondary, R1 | Use PHP APIs, shortcodes and hooks; debug collisions; **success:** Integration equivalence across four routes | Developer docs, diagnostic console, template snippets | Trusted code only; never execute user-supplied PHP snippets |
| **PERS-13 — Site/network administrator** | Primary, R1/R4 | Configure system health, retention, access, integrations; **success:** Can isolate failures and perform safe upgrade/recovery | Health, settings, permissions and operations screens | Multisite network features R4, site isolation always |
| **PERS-14 — Anonymous prospective advertiser** | Secondary, R1 | Browse and compare published placements, know purchase prerequisites; **success:** Sees public truthful terms without private data | Catalog and public placement detail | No access to unpublished or advertiser-private records |
| **PERS-15 — Ordinary website visitor** | Primary, R1/R2 | Read content, optionally interact with ads, control consent; **success:** Content usable with JS failure/denied optional analytics | Public page, ad creatives, privacy preferences | No sensitive input or nonconsented behavior collection |
| **PERS-16 — Customer support / incident operator** | Secondary, R1 | Investigate status, capture diagnostics, route corrective action; **success:** Escalates with reproducible evidence and minimal data exposure | Diagnostics, campaign timeline, redacted support exports | Cannot impersonate or disclose sensitive advertiser records |

### 3.1 Persona specification details

Every persona has explicit motivation, friction, and design obligations. This section gives the UX team concrete design inputs and QA negative-path expectations rather than demographic stereotypes.

#### PERS-01 — Independent publisher / site owner

- **Context:** Needs direct ad revenue without ad-network complexity.
- **Top job:** Sell inventory, approve purchases, understand revenue, keep site usable.
- **Pain/friction:** Revenue versus UX clutter; uncertain placement capacity; operational overhead.
- **Where the person works:** Admin dashboard, Layout Studio, rates, moderation, diagnostics.
- **Outcome evidence:** Time to publish first valid inventory; first approved paid campaign; low critical support load.
- **Permission/trust boundary:** Cannot equate 500 slots with 500 impressions; must understand online checkout dependency.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-02 — Advertising operations manager

- **Context:** Manages dozens to thousands of slot assignments and campaigns.
- **Top job:** Create zones, handle exceptions, pace and troubleshoot delivery.
- **Pain/friction:** Stale placements; editor conflicts; too many manual slot operations.
- **Where the person works:** Slot navigator, calendar, campaign queue, delivery diagnostics.
- **Outcome evidence:** 500-slot configuration without manual block duplication; no accidental inventory contract violation.
- **Permission/trust boundary:** Must not alter paid contracts without approved workflow.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-03 — Small business advertiser (novice)

- **Context:** Needs a credible ad purchase with minimal jargon.
- **Top job:** Compare reach/format, upload creative, see full price, pay, know next step.
- **Pain/friction:** Technical ad terms; uncertainty around approval; lost draft or failed checkout.
- **Where the person works:** Public catalog, placement detail, campaign wizard, checkout and status.
- **Outcome evidence:** Can independently complete supported purchase and distinguish payment from approval.
- **Permission/trust boundary:** Only own organization data; may not infer measured reach from estimates.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-04 — Agency campaign manager

- **Context:** Manages multiple creatives and campaigns for authorized clients.
- **Top job:** Create/edit campaigns, distribute assignments, compare performance.
- **Pain/friction:** Repeated data entry; unclear member access; slow reports.
- **Where the person works:** Advertiser dashboard, creative library, campaign details.
- **Outcome evidence:** Efficient reuse; least-privilege access; predictable campaign status.
- **Permission/trust boundary:** Only delegated organizations; no cross-client leakage.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-05 — Advertiser organization owner

- **Context:** Owns company account and governs collaborator access.
- **Top job:** Invite/remove members, approve spending and see organizational history.
- **Pain/friction:** Overbroad member privileges; accidental exposure of billing.
- **Where the person works:** Organization/profile/member screens, invoices.
- **Outcome evidence:** No unauthorized membership changes; transparent audit trail.
- **Permission/trust boundary:** Cannot assign privileged WordPress/publisher roles.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-06 — Advertiser analyst

- **Context:** Reviews advertising outcomes without editing campaigns.
- **Top job:** Filter verified metrics, export allowed aggregates, communicate results.
- **Pain/friction:** Misleading CTR denominators; data freshness uncertainty.
- **Where the person works:** Advertiser reports and campaign history.
- **Outcome evidence:** Correctly interprets qualified versus unmeasurable metrics.
- **Permission/trust boundary:** No visitor-level data or other organizations.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-07 — Advertiser billing specialist

- **Context:** Needs financial clarity and defensible receipts.
- **Top job:** Review order totals, payment state, invoices and adjustments.
- **Pain/friction:** Failed payment confusion; mismatch of totals and currency.
- **Where the person works:** Billing summary, invoice history and purchase details.
- **Outcome evidence:** Reconstructable order/payment history with authoritative status.
- **Permission/trust boundary:** No campaign editing or sensitive publisher revenue.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-08 — Creative moderator

- **Context:** Protects visitors and publisher reputation.
- **Top job:** Review safety, disclosures, URLs; request changes; emergency pause.
- **Pain/friction:** Unsafe creatives; rushed approvals; unclear pending versions.
- **Where the person works:** Moderation queue, side-by-side creative diff, audit trail.
- **Outcome evidence:** No unapproved version serves; reason communicated clearly.
- **Permission/trust boundary:** Cannot override finance or edit payments.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-09 — Publisher finance manager

- **Context:** Ensures sales, reservations and refunds reconcile.
- **Top job:** Track payment state, refunds, discrepancies and later metered billing.
- **Pain/friction:** Double callbacks; late refunds; invoice mismatch.
- **Where the person works:** Order dashboard, reconciliation, dispute center.
- **Outcome evidence:** Zero unexplained duplicate financial effects in tests.
- **Permission/trust boundary:** Financial permissions only, independently audited.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-10 — Publisher analytics manager

- **Context:** Improves placement quality using trustworthy measurement.
- **Top job:** Compare zones, inspect heatmaps and exposure, report opportunity.
- **Pain/friction:** Long-page geometry drift; false gaze claims; missing denominators.
- **Where the person works:** Reports, heatmap explorer, measurement coverage.
- **Outcome evidence:** Can identify layout version and meaningful sample/limits.
- **Permission/trust boundary:** Aggregates only; replay needs separate permission.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-11 — WordPress content editor

- **Context:** Places approved inventory in articles/templates without writing code.
- **Top job:** Insert blocks, preview responsiveness, avoid duplicate injection.
- **Pain/friction:** Editor performance with 200-slot blocks; tracking in preview.
- **Where the person works:** Gutenberg inspectors, patterns, editor preview.
- **Outcome evidence:** One zone block references hundreds of slots compactly.
- **Permission/trust boundary:** Editor cannot override campaign/contract security.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-12 — Theme/integration developer

- **Context:** Embeds AdPilot in classic or block theme with stable contracts.
- **Top job:** Use PHP APIs, shortcodes and hooks; debug collisions.
- **Pain/friction:** Unstable APIs; CSS leakage; unclear cache behavior.
- **Where the person works:** Developer docs, diagnostic console, template snippets.
- **Outcome evidence:** Integration equivalence across four routes.
- **Permission/trust boundary:** Trusted code only; never execute user-supplied PHP snippets.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-13 — Site/network administrator

- **Context:** Owns runtime stability, upgrades, privacy settings and permissions.
- **Top job:** Configure system health, retention, access, integrations.
- **Pain/friction:** Plugin conflicts; multisite leakage; costly failures.
- **Where the person works:** Health, settings, permissions and operations screens.
- **Outcome evidence:** Can isolate failures and perform safe upgrade/recovery.
- **Permission/trust boundary:** Multisite network features R4, site isolation always.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-14 — Anonymous prospective advertiser

- **Context:** Wants to assess price/value before registration.
- **Top job:** Browse and compare published placements, know purchase prerequisites.
- **Pain/friction:** Forced registration too early; hidden availability or terms.
- **Where the person works:** Catalog and public placement detail.
- **Outcome evidence:** Sees public truthful terms without private data.
- **Permission/trust boundary:** No access to unpublished or advertiser-private records.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-15 — Ordinary website visitor

- **Context:** Wants site content accessible despite monetization.
- **Top job:** Read content, optionally interact with ads, control consent.
- **Pain/friction:** Excessive ad density, layout shifts, tracking discomfort.
- **Where the person works:** Public page, ad creatives, privacy preferences.
- **Outcome evidence:** Content usable with JS failure/denied optional analytics.
- **Permission/trust boundary:** No sensitive input or nonconsented behavior collection.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

#### PERS-16 — Customer support / incident operator

- **Context:** Resolves user-reported failures safely.
- **Top job:** Investigate status, capture diagnostics, route corrective action.
- **Pain/friction:** Insufficient evidence; risk of accidental privileged changes.
- **Where the person works:** Diagnostics, campaign timeline, redacted support exports.
- **Outcome evidence:** Escalates with reproducible evidence and minimal data exposure.
- **Permission/trust boundary:** Cannot impersonate or disclose sensitive advertiser records.
- **Interaction requirements:** Use intelligible task language, visible current/next action, errors with recovery, explicit data scope, keyboard-accessible control paths, and non-color status labels. Show publication, approval, finance and delivery independently where relevant.

### 3.2 Roles-to-personas-to-capabilities matrix

| WordPress/AdPilot authority | Typical persona(s) | Allowed high-value actions | Explicitly excluded |
|---|---|---|---|
| Site Administrator | PERS-01, PERS-13 | Site-scoped settings, role delegation, inventory, diagnostics, audited override | Unreviewed cross-site/network access |
| Network Super Administrator (R4 features) | PERS-13 | Approved network policy controls | Bypass of site-data isolation or advertiser consent |
| Ad Operations Manager | PERS-02 | Zones/layouts, scheduling, delivery, reviewed allocation actions | Unscoped financial ledger manipulation |
| Campaign Reviewer / Moderator | PERS-08 | Review creatives, reasoned decisions, emergency stop | Change payment settlement or business contracts |
| Financial Manager | PERS-09 | Orders, payments, refunds, reconciliation, adjustments | Unrestricted replay and visitor session data |
| Analytics Manager | PERS-10 | Publisher aggregate analytics, layout/slot analysis | Implicit replay permission; unrestricted personal-data export |
| Content Editor | PERS-11 | Insert configured blocks if granted editor/binding capabilities | Change paid slot meaning, targeting rules or campaign approval |
| Advertiser Owner | PERS-05 | Own organization, campaigns, membership and billing as granted | Publisher controls, other organizations |
| Advertiser Manager | PERS-04 | Authorized organization campaign creation/edits | Unassigned organizations, privileged membership/billing controls |
| Advertiser Analyst | PERS-06 | Own aggregated reports | Campaign mutation, visitor-level data |
| Advertiser Billing User | PERS-07 | Own billing and invoice data | Campaign mutation and publisher finances |
| Support Agent | PERS-16 | Restricted diagnostic access and escalation | Undocumented overrides, private replay by default |
| Anonymous browser | PERS-14, PERS-15 | Public published catalog and content/ad rendering | Private rates, campaigns, sessions or financial data |
| Developer with trusted deploy authority | PERS-12 | Integrate documented PHP/shortcodes/hooks in trusted code | Arbitrary code execution via advertiser/editor input |

**Authorization test standard:** hide disallowed controls for usability **and** return a consistent forbidden/not-found response for unauthorized direct API access, avoiding record-existence leaks. WordPress nonces are not authorization. Server decisions are authoritative even when client state or cached screens are stale.

---

## 4. Jobs-to-be-done (JTBD) and success outcomes

Use the format **When [situation], I want [progress], so I can [outcome]**. These are outcome statements, not specifications for individual buttons. Each links to an observable journey; quantitative targets await validated research baselines.

| ID / Actor | Situation and motivation | Desired outcome | Journey(s) | Release |
|---|---|---|---|---|
| `JTBD-01` — PERS-14 | When I am considering promoting a business, I want to compare real available placements and published terms before signing up | So I can make an informed decision | UJ-01,UJ-02 | R1 |
| `JTBD-02` — PERS-03 | When I buy my first placement, I want to complete a comprehensible guided campaign and payment | So I can launch without specialized AdTech expertise | UJ-03,UJ-04,UJ-05 | R1 |
| `JTBD-03` — PERS-03/PERS-04 | When checkout is interrupted or a quote expires, I want to recover without accidental duplicate payment or lost draft | So I can finish confidently or abandon safely | UJ-05,UJ-21 | R1 |
| `JTBD-04` — PERS-05 | When more coworkers manage our advertising, I want to delegate organization duties with least privilege | So I can collaborate without exposing billing or other clients | UJ-06 | R1 |
| `JTBD-05` — PERS-08 | When a new or changed creative needs review, I want to see what changed, apply policy, and explain the decision | So I can protect visitors and make review auditable | UJ-07 | R1 |
| `JTBD-06` — PERS-02 | When inventory demand overlaps or schedules change, I want to inspect availability and validate reservations | So I can avoid overselling and unfulfilled commitments | UJ-08,UJ-22 | R1 |
| `JTBD-07` — PERS-01/PERS-02 | When a site needs hundreds of ad positions, I want to build 4/100/196/200 responsive slots from compact containers | So I can sell flexible inventory without manual repetitive editing | UJ-09 | R1 |
| `JTBD-08` — PERS-01/PERS-02 | When a sold slot layout must be redesigned, I want to preview financial/contract impact and publish safely | So I can keep existing advertiser commitments | UJ-10 | R1 |
| `JTBD-09` — PERS-11 | When I edit a page in Gutenberg, I want to place a configured zone with one dynamic block | So I can embed ads without generating hundreds of blocks | UJ-11 | R1 |
| `JTBD-10` — PERS-12 | When a theme needs direct ad integration, I want to use stable PHP, shortcode or hook functions | So I can embed ads without custom forks or policy bypass | UJ-12 | R1 |
| `JTBD-11` — PERS-15 | When I scroll a content-heavy page, I want to read and interact with content despite many eligible ads | So I can avoid blocked content, jumpy layouts and intrusive behavior | UJ-13 | R1 |
| `JTBD-12` — PERS-02/PERS-01 | When a campaign is paid and approved, I want to reliably schedule/activate or safely pause it | So I can honor the sale with correct delivery | UJ-14 | R1 |
| `JTBD-13` — PERS-06/PERS-10 | When I assess campaign outcomes, I want to understand validated counts, denominators and freshness | So I can make informed decisions without false precision | UJ-15 | R1 |
| `JTBD-14` — PERS-09 | When payment or refund state looks inconsistent, I want to investigate authoritative events and reconciliation | So I can resolve accurately with an audit trail | UJ-16 | R1 |
| `JTBD-15` — PERS-13/PERS-16 | When services or scheduled jobs fail, I want to see truthful status, retry safely and escalate | So I can restore operation without inventing billable data | UJ-17 | R1 |
| `JTBD-16` — PERS-13 | When behavioral tracking might be prohibited, I want to set consent, masking, retention and exclusions | So I can protect visitors and meet configured privacy obligations | UJ-18 | R1/R2 |
| `JTBD-17` — PERS-10 | When I need to know where users interact on a long page, I want to inspect version-specific click/scroll/visibility heatmaps | So I can change layout based on defensible evidence | UJ-19 | R2 |
| `JTBD-18` — PERS-10 | When two layout versions differ, I want to compare compatible cohorts and see measurement gaps | So I can avoid invalid page-geometry conclusions | UJ-20 | R2 |
| `JTBD-19` — PERS-06/PERS-10 | When a report needs sharing, I want to request a scoped aggregated export | So I can communicate results without leaking private records | UJ-23 | R2 |
| `JTBD-20` — PERS-09/PERS-04 | When a contract bills for eligible impressions or clicks, I want to review qualification and reconciled billable records | So I can make fair contract-level charging and dispute decisions | UJ-24 | R3 |
| `JTBD-21` — PERS-10 | When I want to improve monetization, I want to test a layout or ad treatment against defined metrics | So I can avoid arbitrary optimization claims | UJ-25 | R3 |
| `JTBD-22` — PERS-13/PERS-10 | When replay might aid diagnostics, I want to turn on sampled replay under strict consent and access controls | So I can troubleshoot without exposing protected form inputs | UJ-26 | R4 |
| `JTBD-23` — PERS-13 | When I install or upgrade the plugin, I want to see prerequisites, safe migrations, and clear failures | So I can preserve my site and records | UJ-27 | R1/R4 |
| `JTBD-24` — PERS-01/PERS-02 | When WooCommerce is not installed, I want to operate campaigns manually while online payment stays disabled | So I can use core AdPilot features without forced external dependency | UJ-28 | R1 |

### 4.1 JTBD outcome instrumentation

Measure success through task completion, correct comprehension and permitted product events—not silent behavioral surveillance. Suggested product events include `catalog_filter_applied`, `placement_terms_viewed`, `wizard_draft_saved`, `quote_generated`, `reservation_hold_created`, `payment_status_confirmed`, `creative_review_decision_recorded`, `layout_conflict_resolved`, `layout_version_published`, `report_filters_applied`, `heatmap_version_selected`, and `export_completed`. Events are **proposed schema concepts**; they do not authorize personal tracking or recording of sensitive input values. Tie each to the relevant release and consent/privacy policy.

### 4.2 Functional, emotional and social dimensions

- **Functional:** reserve valid inventory, deploy safe creatives, visualize layout, understand measured performance.
- **Emotional:** confidence that money is not lost, creative approval is fair, changes will not silently break a paid placement, and metrics are credible.
- **Social/organizational:** share reports with stakeholders, demonstrate publisher accountability, allocate responsibilities without exposing irrelevant information, document moderator and finance decisions.

UX solutions must satisfy all three dimensions where they affect critical purchasing and administration journeys.

---

## 5. Information architecture and navigation

### 5.1 Administrator console structure

```text
AdPilot Admin
├── Overview / health alerts                         UI-01
├── Inventory
│   ├── Zone registry                               UI-02
│   ├── Layout Studio                               UI-03
│   ├── Slot Navigator                              UI-04
│   └── Template / Integration Bindings              UI-05
├── Campaign Operations
│   ├── Campaign list / detail                      UI-11
│   ├── Creative moderation                         UI-12
│   └── Reservations / booking calendar             UI-13
├── Revenue
│   └── Rate cards, orders and reconciliation       UI-14
├── Reporting & Intelligence
│   ├── Delivery diagnostics                        UI-15
│   ├── Publisher metrics (UI-01/report drilldown)
│   ├── Heatmap Explorer (R2)                       UI-17
│   ├── Aggregate exports (R2)                      UI-18
│   └── Metered billing disputes (R3)               UI-19
└── Settings / permissions / privacy / health        UI-20
```

### 5.2 Advertiser and public structure

```text
Public advertising catalog                           UI-06
└── Placement / package detail                       UI-07
    ├── Advertiser sign-in / onboarding              UI-08
    └── Guided campaign wizard                       UI-09
        ├── Placement & campaign objective
        ├── Creative + destination
        ├── Targeting + schedule
        ├── Quote & reservation details
        └── WooCommerce checkout handoff             UI-10

Advertiser Portal
├── Dashboard and campaign list/detail               UI-11
├── Organization and membership                       UI-08
├── Campaign analytics                               UI-16
└── Orders / invoices / payment status                UI-14 (scoped front-end view)
```

### 5.3 Navigation rules

1. Keep WordPress admin and public advertiser portal clearly distinct; advertisers do not need access to the WordPress administrator UI.
2. Breadcrumbs expose current object, organization/site context and last valid navigation path.
3. Deep links must restore permitted filters, selected tab, and time range without leaking private query data or transient tokens.
4. Editing workflows warn about unsaved changes; autosaved drafts show `Saved`, `Saving`, or `Save failed` only when verified by the server.
5. Render all state labels in text and use visual badges only as a supplement.
6. Privileged actions must appear only when the server reports permission and valid state, yet direct requests are independently authorized.
7. On mobile, replace dense navigation with accessible collapsed navigation; never remove important workflows solely because of viewport width.
8. The active advertiser organization must be always clear when a user belongs to more than one.

---

## 6. Common screen behavior contracts

### 6.1 Mandatory screen states

Every primary `UI-01..UI-20` screen needs explicit designs and automated/manual acceptance evidence for **initial**, **loading**, **loaded**, **empty**, **filtered-empty**, **validation-error**, **request-failure**, **partial/degraded**, **unauthorized**, **stale/concurrent-change**, **completed/success**, and **restricted-by-release** states as applicable.

| State | Required copy/action pattern | Forbidden behavior |
|---|---|---|
| Loading | Announce region status nonintrusively; stable skeleton dimensions | False paid/approved/complete badges |
| Empty | Explain why there is no data and give an authorized next action | Blank page with zero context |
| Filtered empty | Preserve filters; offer clear/reset while explaining no matches | Implicitly deleting filters or data |
| Invalid user input | Inline field guidance, summary, focused first error | Only toast error or lost entries |
| Server/network error | Correlation reference if safe; retry where idempotent | Silent failure or repeated payment submission |
| Partial/degraded | Show available information and freshness limitations | Treat missing metrics as zero/settled |
| Forbidden | No privileged values; concise accessible error | Hiding UI only while API leaks data |
| Conflict/stale state | Explain which record changed; reload/requote/review impact | Blind overwrite of another change |
| Success | Confirm only authoritative persisted state, with next action | Claiming paid/active from browser redirect alone |
| Consent restricted | State why optional analytics is unavailable | Collecting optional behavior anyway |

### 6.2 Shared interaction design rules

- Each form has visible labels, required markers, inline helper text and programmatically associated errors; never depend on placeholders alone.
- `Enter` and `Space` operate appropriate controls; focus order matches reading order and visible layout; dialogs trap and return focus correctly.
- Destructive changes require explicit consequences, affected counts, and confirmation; preferentially use reversible archiving instead of hard deletion.
- Toasts announce low-risk confirmations but durable state changes also remain visible in page status/timeline.
- Retry buttons must be disabled or guarded while a request is in flight and must carry appropriate idempotency tokens for financial/booking operations.
- Time zones, inclusive/exclusive boundaries and currency units must appear anywhere price and schedule can be misunderstood.
- Sensitive links and reports require reauthorization at retrieval time, not only when generated.
- Interactive charts and heatmaps require accompanying numerical/tabular representations and textual summary.
- Accessibility support is part of acceptance, not a separate premium/release-later feature.

### 6.3 Proposed design tokens and visual language constraints

Visual identity details will be finalized in the Design System document. UI components should employ semantic tokens for foreground/background, borders, focus, danger, warning, success, pending, disabled and analytics series; no color-only status or heatmap interpretation. Avoid fixed-width tables on narrow screens without discoverable horizontal navigation and accessible column labels. Use purpose-specific density presets for dashboards and slot navigation; never compromise target size, readable typography, focus visibility or data accuracy to fit more rows.

---

## 7. Detailed end-to-end user journeys

**Execution contract:** A journey is complete only when its happy path, alternatives, permission boundaries, recovery actions, state transitions, and specified UX acceptance checks pass on the declared release. User actions listed below are UX intentions; server actions are authoritative. API route names, DB schema and exact phrasing will be formalized in downstream contracts.

**Steps table conventions:** `A:` describes the actor's deliberate action; `S:` describes the system's observable response and required persistence/authorization. An interaction test must verify both sides. Every journey inherits §6's common error, loading, accessibility, mobile and privacy requirements.

### UJ-01 — Anonymous advertiser discovers an offer

**Release:** R1  
**Actors:** PERS-14, PERS-03  
**Screens/interfaces:** UI-06 → UI-07  
**Trigger:** Prospective advertiser wishes to compare opportunities.  
**Preconditions:** At least one published and sellable placement or package exists.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open public advertising page | Return only published inventory; show category, eligible format, dimensions, time/price units and stated terms. |
| 2 | Filter by position, format, price range and relevant audience/device | Apply filters together, preserve selected values, show result count and reset. |
| 3 | Inspect a placement card | Reveal availability headline, price basis and disclosure; no privileged competitor/visitor details. |
| 4 | Open a placement detail | Present desktop/mobile creative examples as simulations, contractual position identity and published restrictions. |
| 5 | Inspect reach/performance estimate | Label data source, period, sample and uncertainty; distinguish historical observations from forecasts. |
| 6 | Choose a published offer | Continue to authentication/onboarding or wizard while retaining selected offer context. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **No live offers** → explain empty catalog and avoid fake availability.
2. **Stale offer** → refresh quote/availability before holding inventory.
3. **Anonymous probe of private placement ID** → not found/forbidden without record leakage.
4. **Partial estimates** → show missing-data explanation; no invented reach.

**Postcondition:** A valid offer reference is selected without creating a reservation or payment.

**Baseline links:** FR-MKT-001..002, FR-MKT-008; BR-001, BR-013; AC-005  
**UX/accessibility oracle:** Search/filter are keyboard accessible; public terms readable at phone widths; estimates visually differentiated.  
**Candidate product telemetry (subject to policy):** `catalog_viewed`, `catalog_filter_applied`, `placement_detail_viewed`.

### UJ-02 — Advertiser registers and sets up organization

**Release:** R1  
**Actors:** PERS-03, PERS-05  
**Screens/interfaces:** UI-08  
**Trigger:** A buyer chooses to proceed from a published listing.  
**Preconditions:** Supported WordPress login/registration flow exists; invitation or self-signup policy configured.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Select sign-up or sign-in | Show safe WordPress-backed authentication, validation and privacy explanation. |
| 2 | Complete account verification where configured | Do not expose account existence or private details through public error text. |
| 3 | Create advertiser organization | Validate required business fields; persist organization plus owner membership atomically or recoverably. |
| 4 | Select organization context | Display active organization name before campaign actions. |
| 5 | Resume originally selected offer | Return to preserved public placement detail/wizard without sharing cross-user transient data. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Existing account** → offer sign-in and account recovery.
2. **Invitation expired** → allow safe request for a new invite.
3. **Duplicate organization business name** → provide policy-defined warning without claiming global uniqueness.
4. **Account suspended** → block mutations, show support path with scoped messages.

**Postcondition:** Authenticated account and authorized organization exist; session is organization-scoped.

**Baseline links:** FR-MKT-003..004, FR-MKT-009, FR-OPS-001..003; AC-009  
**UX/accessibility oracle:** Forms preserve nonsecret fields on ordinary validation errors; no inaccessible authentication loops.  
**Candidate product telemetry (subject to policy):** `registration_completed`, `organization_created`.

### UJ-03 — Advertiser drafts campaign and validates creative

**Release:** R1  
**Actors:** PERS-03, PERS-04  
**Screens/interfaces:** UI-09 → UI-11  
**Trigger:** An authorized advertiser begins a new campaign.  
**Preconditions:** Active organization, at least one eligible published placement, creator permissions.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Start campaign wizard | Create draft with organization/creator identity and durable draft key. |
| 2 | Choose placement and intended dates | Show availability estimate and rate unit, with site/account time zone. |
| 3 | Enter campaign name and objective | Validate lengths and display accessible inline errors. |
| 4 | Upload image/text/native creative | Check MIME, safe format, dimensions, file size, destination URL and alt text server-side. |
| 5 | Set supported contextual targeting | Show only permitted R1 targeting conditions, with conflict explanation. |
| 6 | Navigate backward and forward | Maintain draft and show verified save state and unsaved changes handling. |
| 7 | Review submission | Summarize offer, content, policy terms, costs, next approval/payment steps. |
| 8 | Save draft or submit to the next approved commercial step | Persist versioned draft; do not infer approval or payment. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Malformed/risky creative** → reject securely with specific validation reason.
2. **Stale price** → invalidate existing estimate and request a fresh quote.
3. **Browser closes while draft saving** → recover last acknowledged server version.
4. **Creative changed after earlier approval** → new version must go back to review.
5. **Unsupported targeting** → do not silently broaden reach.

**Postcondition:** Campaign draft is persisted with validated fields; no unapproved creative may serve.

**Baseline links:** FR-MKT-005..007, FR-CAM-003..004, FR-CAM-008, FR-UX-005; AC-008  
**UX/accessibility oracle:** Wizard step progress and required information conveyed without color; reload restores acknowledged draft.  
**Candidate product telemetry (subject to policy):** `campaign_draft_created`, `wizard_step_completed`, `creative_version_uploaded`, `campaign_draft_saved`.

### UJ-04 — Advertiser obtains quote and exclusive hold

**Release:** R1  
**Actors:** PERS-03, PERS-04  
**Screens/interfaces:** UI-09 → UI-13 → UI-10  
**Trigger:** Advertiser is ready to reserve a selected placement.  
**Preconditions:** Publishable slot/offer; supported fixed/time-based contract; authenticated organization.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Choose timezone-aware start and end | Display precise period, local offset/DST note and applicable unit. |
| 2 | Request availability | Evaluate reservations, capacity, exclusions and active allocations using authoritative source. |
| 3 | Request quote | Show slot contract, versioned rate, price precision, discounts, tax basis, currency and refund/approval conditions. |
| 4 | Review exact quote and hold lifetime | Explain non-guaranteed estimates separately and avoid hidden changes. |
| 5 | Confirm reservation hold | Create bounded, idempotent hold with server concurrency protection. |
| 6 | Proceed to next step | Display authoritative expiration and remaining allowed actions, not a client-only timer. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Hold collision** → deny second exclusive reservation and show alternatives.
2. **Hold expires before checkout** → explain expiration, recheck rates and availability.
3. **Invalid DST local time** → require explicit valid time selection.
4. **Two tabs submit hold** → idempotent or conflict result with no duplicate inventory allocation.
5. **Rate edited during flow** → require reacceptance of new quote.

**Postcondition:** Active hold references a valid quote and exact commercial inventory; nothing has been billed by itself.

**Baseline links:** FR-COM-001..004, FR-COM-014; BR-001..002, BR-005, BR-014..015; AC-006  
**UX/accessibility oracle:** Clear currency/unit/timezone, held-until status, and alternatives; screen readers receive reservation results.  
**Candidate product telemetry (subject to policy):** `quote_requested`, `quote_accepted`, `hold_created`, `hold_conflict`.

### UJ-05 — WooCommerce checkout, return, and recovery

**Release:** R1  
**Actors:** PERS-03, PERS-07  
**Screens/interfaces:** UI-10 → UI-11 / UI-14  
**Trigger:** Advertiser proceeds from a valid quote and reservation.  
**Preconditions:** WooCommerce installed/configured, owned active hold, supported provider and checkout.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open checkout | Map AdPilot order/hold to WooCommerce order through supported CRUD/HPOS path. |
| 2 | Review payment and contractual terms | Display immutable ordered line items and reservation expiry. |
| 3 | Submit payment through authorized provider | Show pending state and guard duplicate client submit. |
| 4 | Return from provider | Show verification in progress; do not claim success from redirect parameters. |
| 5 | Receive authoritative payment update | Verify signature/source and idempotency, reconcile financial state and order mapping. |
| 6 | Inspect purchase detail | Display payment, approval and delivery as three separate statuses and next steps. |
| 7 | Complete creative review workflow | Direct to moderation status rather than declaring ads active after payment. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Payment succeeds after hold expired** → quarantine for reconciliation and alternative allocation/refund path.
2. **Callback duplicated/delayed/out of order** → one commercial transition and auditable record.
3. **Gateway says declined** → permit safe retry with new/validated attempt.
4. **Browser closes after charge** → portal accurately shows backend-confirmed result.
5. **Partial refund or reversal** → policy-governed reconciliation and campaign hold/suspension.

**Postcondition:** Authoritative payment state is recorded; campaign still requires independent approval and delivery eligibility.

**Baseline links:** FR-COM-005..010, FR-CAM-001, FR-MKT-007; BR-003..004, BR-014; AC-005, AC-007  
**UX/accessibility oracle:** Pending and paid text is precise; no double-submit on slow network; invoice visible only to authorized billing users.  
**Candidate product telemetry (subject to policy):** `checkout_started`, `checkout_returned`, `payment_confirmed`, `payment_failed`.

### UJ-06 — Organization owner invites and restricts teammates

**Release:** R1  
**Actors:** PERS-05, PERS-04, PERS-06, PERS-07  
**Screens/interfaces:** UI-08  
**Trigger:** Business needs multiple employees with different responsibilities.  
**Preconditions:** Organization owner logged in; invitation policy configured.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open team screen | List only members of active organization and assigned role labels. |
| 2 | Enter invitee and select organization role | Describe manager, analyst and billing privileges in plain language. |
| 3 | Send invitation | Create expiring invitation securely with origin/organization context. |
| 4 | Invitee accepts | Confirm identity; bind authorized membership and audit initiator/acceptance. |
| 5 | Switch active organization | Refresh all cached API/report scopes and visible context. |
| 6 | Change or revoke membership | Recheck role, invalidate affected access and record decision. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Invited user already exists** → link verified existing account under invitation policy.
2. **Invite expires/revoked** → no access; safe resend option.
3. **Member attempts to promote self to owner** → permission denial.
4. **Two owners change roles concurrently** → detect version conflict.
5. **Previously opened report after revocation** → request reauthorization, deny protected data.

**Postcondition:** Only approved members see authorized organization resources; audit reflects changes.

**Baseline links:** FR-MKT-004, FR-OPS-001, FR-OPS-003; AC-009  
**UX/accessibility oracle:** Role descriptions are clear; active organization context never ambiguous; accessible invitations.  
**Candidate product telemetry (subject to policy):** `invitation_sent`, `member_joined`, `membership_changed`.

### UJ-07 — Moderator reviews new creative or changed version

**Release:** R1  
**Actors:** PERS-08, PERS-04  
**Screens/interfaces:** UI-12 → UI-11  
**Trigger:** Campaign content is submitted or a destination/media version changes.  
**Preconditions:** Authorized reviewer, submitted creative version, policy configuration.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open review queue | Show status, submitter, age, target slot/policy context and pending creative version. |
| 2 | Inspect asset and destination | Preview sanitized formats; check declared disclosures, alt text, link safety and file policy. |
| 3 | Compare revisions | Identify changes that require reapproval and distinguish approved prior version. |
| 4 | Approve, reject or request changes | Require decision reason as applicable and server-side validation. |
| 5 | Publish review outcome | Write auditable state transition; notify advertiser of actionable result. |
| 6 | If approval permits delivery | Recompute contract/schedule eligibility; do not directly override payment status. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Unsafe SVG/HTML/JS** → reject under R1 creative policy.
2. **Destination URL redirect becomes unsafe** → suspend/recheck according to policy.
3. **Reviewer lacks permission** → no decision possible.
4. **Creative edited during review** → stale version conflict; reload.
5. **Emergency stop** → immediately prevent future ad decisions and invalidate stale cache.

**Postcondition:** Approved version is explicitly recorded; rejected/unapproved version never becomes eligible.

**Baseline links:** FR-CAM-001..010; BR-003..004, BR-007..008; AC-008  
**UX/accessibility oracle:** Moderation decisions have keyboard path, explicit version, readable evidence and reason.  
**Candidate product telemetry (subject to policy):** `creative_review_started`, `creative_approved`, `changes_requested`, `emergency_stop`.

### UJ-08 — AdOps checks booking calendar and resolves overlap

**Release:** R1  
**Actors:** PERS-02, PERS-09  
**Screens/interfaces:** UI-13 → UI-14  
**Trigger:** A requested schedule conflicts with existing or proposed occupancy.  
**Preconditions:** Authorized operator, published inventory and rate cards.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open booking calendar | Display site time zone, reservations, sold/inactive inventory and capacity counts. |
| 2 | Filter zone, slot, date window | Show date/time in selected zone, with clear inclusive/exclusive interval semantics. |
| 3 | Inspect overlapping reservations | Present owned contract references and conflict reason within permission scope. |
| 4 | Select alternative dates or slots | Recalculate valid capacity and compatible commercial product. |
| 5 | Create authorized administrative hold or update | Use same transactional engine as storefront. |
| 6 | Review outcome | Audit changes and clearly label remaining capacity. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **DST ambiguous/nonexistent hour** → explicit offset handling.
2. **Out-of-date calendar** → refresh before mutation.
3. **Exclusive overlap** → block even for privileged visual editor unless auditable authorized resolution.
4. **Permission-limited operator** → redact billing specifics.
5. **Mass selection too broad** → confirm affected record count and provide paged review.

**Postcondition:** No impossible overlapping exclusive contracts; occupancy accurately reflects authoritative reservations.

**Baseline links:** FR-COM-002..003, FR-COM-014, FR-INV-011; BR-001, BR-005, BR-015; AC-006  
**UX/accessibility oracle:** Keyboard-operable alternative to drag calendar; visually and textually label occupancy.  
**Candidate product telemetry (subject to policy):** `availability_checked`, `booking_conflict_detected`, `booking_relocated`.

### UJ-09 — Publisher builds 500-slot responsive page

**Release:** R1  
**Actors:** PERS-01, PERS-02  
**Screens/interfaces:** UI-02 → UI-03 → UI-04  
**Trigger:** Publisher needs extreme but configurable advertising density on a long page.  
**Preconditions:** Authorized site editor/ad operations permission and compatible page/template.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Create distinct header, sidebar, content and footer zones | Use stable unique IDs and descriptive labels. |
| 2 | Choose zone containers | Configure header 4, sidebar 100, content 196 and footer 200 using compact sequences. |
| 3 | Adjust breakpoint rules | Set grid columns, minimum widths, aspect ratios, spacing, visibility and safe placeholders. |
| 4 | Preview logical structure | Show 500 count and representative slots; avoid rendering 500 creative assets. |
| 5 | Inspect generated slot navigator | Filter/paginate 100+ or 200+ slots; show eligible, reserved and occupied states. |
| 6 | Bind zones to desired contexts | Choose Gutenberg or supported template/hook binding with duplicate checks. |
| 7 | Run validation and conflict impact report | Check density/accessibility constraints and existing paid reservations. |
| 8 | Publish a new immutable version | Commit safe version atomically and show version identifier. |
| 9 | Open the public page | Use progressive viewport delivery and correct per-render slot identity. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Resource safety limit reached** → explain host-specific diagnostic without claiming fixed 500 max.
2. **200 footer slots on phone** → adaptive columns, no horizontal overflow.
3. **Unavailable targeted container** → explicit binding diagnostic.
4. **Duplicate hook and Gutenberg injection** → warn and distinguish intentional repeats.
5. **Partially loaded page** → preserve useful content and correct event accounting.

**Postcondition:** 500 logical slots persist correctly, Layout Studio remains usable and active layout is versioned.

**Baseline links:** FR-INV-001..011, FR-INV-013..014, FR-DEL-004..007; AC-001, AC-002, AC-004  
**UX/accessibility oracle:** 500 logical nodes never become 500 editor controls by default; keyboard slot lookup and responsive summary available.  
**Candidate product telemetry (subject to policy):** `layout_draft_created`, `slot_sequence_configured`, `layout_validated`, `layout_published`.

### UJ-10 — Publisher edits sold layout without breaking contracts

**Release:** R1  
**Actors:** PERS-02, PERS-01  
**Screens/interfaces:** UI-03 → UI-04 → UI-13  
**Trigger:** A sold footer or header must change columns, ordering or slot identities.  
**Preconditions:** Published layout exists with at least one active reservation.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Create layout draft from current published version | Keep public version stable until explicit commit. |
| 2 | Move/delete/resequence commercial position | Mark relevant logical slots and calculate impact. |
| 3 | Inspect dependency report | List affected contracts, reservations, dates, commercial slot meaning and alternatives. |
| 4 | Attempt invalid publish | Block unsafe change and explain affected contracts and resolution choices. |
| 5 | Choose compatible edit or authorized migration flow | Record migration decision, reason and necessary approval/reconsent. |
| 6 | Review desktop/tablet/mobile effects | Ensure responsive layout does not silently change sold slot identity. |
| 7 | Publish or cancel | Atomic publish on success; preserve old version and versioned measurement history. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Concurrent second publisher edit** → version conflict and merge/reload path.
2. **Paid slot deleted unintentionally** → publish blocked.
3. **Migration not approved** → preserve published layout.
4. **Rollback conflicts with new contracts** → run fresh impact review; not unconditional.
5. **Layout CSS changes location but not logical ID** → verify commercial meaning contract.

**Postcondition:** Contracts remain valid or have explicit authorized migration; historical layout versions remain inspectable.

**Baseline links:** FR-INV-005, FR-INV-008..009, FR-INV-014; BR-006, BR-018; AC-001  
**UX/accessibility oracle:** Diff clearly exposes *physical position versus contractual identity*; no one-click unsafe override.  
**Candidate product telemetry (subject to policy):** `layout_impact_viewed`, `layout_conflict_blocked`, `layout_migration_approved`.

### UJ-11 — Content editor inserts AdPilot Gutenberg blocks

**Release:** R1  
**Actors:** PERS-11  
**Screens/interfaces:** WordPress Gutenberg editor → UI-05  
**Trigger:** Editor needs an ad zone inside a page or template part.  
**Preconditions:** AdPilot block capability, valid zone, supported Gutenberg context.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open block inserter and search AdPilot | Expose four blocks: slot, zone, repeater, showcase. |
| 2 | Select `adpilot/ad-zone` | Insert compact block with editable zone reference and allowed overrides. |
| 3 | Choose zone with 200 footer slots | Show representative preview and count without 200 individual blocks. |
| 4 | Adjust responsive controls | Preview allowed breakpoints; validate unavailable overrides. |
| 5 | Save page/template | Persist block attributes safely; do not store 200 duplicated media blobs. |
| 6 | Preview editor output | Ensure preview event channel is marked nonproduction and nonbillable. |
| 7 | Open published page | Route through shared eligibility renderer with unique render occurrence IDs. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Deleted zone reference** → actionable broken-reference placeholder for editor.
2. **Missing permission to change commercial settings** → read-only info, API denial.
3. **Repeater unsupported in current query context** → explain adapter restriction.
4. **Duplicated dynamic block intentionally** → unique render IDs and explicit inventory policy.
5. **Block validation after upgrade** → migration/deprecation compatibility.

**Postcondition:** One compact block represents zone; editor preview does not create billable impressions.

**Baseline links:** FR-INT-001..004, FR-INT-007..010; AC-002, AC-003  
**UX/accessibility oracle:** Block inspector fully keyboard-usable and focus remains in editor after previews.  
**Candidate product telemetry (subject to policy):** `block_zone_selected`, `editor_preview_requested`, `binding_saved`.

### UJ-12 — Theme integrator embeds ad slots via public interfaces

**Release:** R1  
**Actors:** PERS-12, PERS-13  
**Screens/interfaces:** UI-05, developer docs, theme templates  
**Trigger:** Trusted developer needs AdPilot in header/sidebar/footer and a custom hook.  
**Preconditions:** Compatible theme and stable public APIs; no advertiser-provided code execution.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Read function/shortcode/hook documentation | Understand arguments, return/echo behavior, errors and cache notes. |
| 2 | Call `adpilot_render_zone()` in trusted theme PHP | Resolve zone through the same core campaign eligibility service. |
| 3 | Add shortcode or registered Gutenberg block | Compare equivalence of policy and safe markup. |
| 4 | Bind a documented WordPress/theme hook | Ensure diagnostic shows resolved or unresolved binding. |
| 5 | Test repeated invocation and cache | Identify intentional duplicate renders with unique occurrence identifiers. |
| 6 | Inspect CSS on classic and block themes | Ensure ad styles are scoped and do not override unrelated controls. |
| 7 | Simulate missing plugin/zone | Theme degrades gracefully with documented fallback. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Plugin deactivated** → guarded theme integration does not fatally error.
2. **Shortcode attempts bypass targeting** → override denied.
3. **Unknown hook silently never fires** → flagged in diagnostic path (R2 enhanced diagnostics).
4. **Shared cache reuses visitor-private decision** → architecture/security failure.
5. **Malicious shortcode attribute** → sanitized and rejected.

**Postcondition:** All supported integration paths invoke one policy-aware rendering service.

**Baseline links:** FR-INT-005..012, FR-DEL-007..009; AC-003, AC-010  
**UX/accessibility oracle:** Developer-facing diagnostics include reliable, sanitized context and copyable examples.  
**Candidate product telemetry (subject to policy):** `integration_binding_validated`, `duplicate_binding_detected`.

### UJ-13 — Visitor scrolls high-density page with consent controls

**Release:** R1  
**Actors:** PERS-15  
**Screens/interfaces:** Public page + ad runtime  
**Trigger:** A public visitor reads an article with many eligible advertising positions.  
**Preconditions:** Published 500-slot page with safe delivery policy; visitor may deny optional analytics.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Load public page | Render content and lightweight placement skeletons without blocking main content. |
| 2 | Observe initial viewport | Batch-fetch only creatives needed around visibility threshold; bound concurrency. |
| 3 | Continue reading and scrolling | Load subsequent batches as slots approach; maintain layout stability. |
| 4 | Observe fallback/empty positions | Reserve or collapse space according to configuration without fictitious rendered events. |
| 5 | Interact with eligible ad | Apply safe link/destination handling and qualified interaction pipeline. |
| 6 | Deny optional behavioral consent | Do not start nonessential heatmap/replay collection; essential policy-compliant delivery remains possible. |
| 7 | Disable JS or interrupt network | Keep article navigable; disclose dynamic serving and measurement limitations. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Slow connection** → throttled loading and fallback, not hundreds of synchronous requests.
2. **A campaign expires mid-page** → new decisions use authoritative eligibility.
3. **Creative image cannot load** → stable fallback and no fabricated rendered impression.
4. **Ad tracking endpoint down** → page still navigable.
5. **Very long mobile footer** → adapted grid and density warnings, no horizontal overflow.

**Postcondition:** Visitor experiences usable content; distinct configured/selected/rendered counters remain truthful.

**Baseline links:** FR-DEL-001..011, FR-MET-001..005, FR-OPS-005; BR-009..010, BR-016; AC-004, AC-011..012  
**UX/accessibility oracle:** Responsive reading order and focus remain meaningful; motion respects preferences; no ad traps.  
**Candidate product telemetry (subject to policy):** `ad_request_validated`, `ad_rendered_observed`, `qualified_click_observed`.

### UJ-14 — AdOps activates, pauses and expires a campaign

**Release:** R1  
**Actors:** PERS-02, PERS-08  
**Screens/interfaces:** UI-11 → UI-15  
**Trigger:** A campaign has been paid/authorized and approved, or requires emergency suspension.  
**Preconditions:** Valid campaign state, authorizations, scheduler and current inventory.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open campaign detail | Show approval, finance, delivery and creative version as distinct statuses. |
| 2 | Review eligibility checklist | Check contract, date, selected creative approval, allocation, holds and restrictions. |
| 3 | Schedule campaign | Persist UTC boundaries with explicit site/account time-zone display. |
| 4 | At scheduled start | Queue attempts idempotent activation, evaluating current authoritative state. |
| 5 | Inspect delivery diagnostics | Show active status plus fresh request/render counters and error signals. |
| 6 | Pause or emergency-stop | Write audited change and prevent subsequent ad decisions; refresh/invalidate caches. |
| 7 | Expire or complete | Move to terminal delivery state while preserving financial and performance history. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Missed cron** → self-healing job and health alert without falsely backdating paid impressions.
2. **Creative becomes unapproved** → cease new delivery.
3. **Payment reversal** → policy-based hold/suspension.
4. **Stale cached HTML** → dynamic decision rechecks eligibility.
5. **Concurrent pause and activation** → defined transition precedence and idempotence.

**Postcondition:** Only fully eligible creative serves; status history explains decisions.

**Baseline links:** FR-CAM-001..002, FR-DEL-001..003, FR-DEL-009..010, FR-OPS-006; BR-007..008; AC-007..008  
**UX/accessibility oracle:** Admin sees actionable why-not-serving checklist, not just a red generic error.  
**Candidate product telemetry (subject to policy):** `campaign_scheduled`, `campaign_activated`, `campaign_paused`, `campaign_completed`.

### UJ-15 — Advertiser or publisher reviews verified metrics

**Release:** R1  
**Actors:** PERS-06, PERS-10  
**Screens/interfaces:** UI-16 → UI-01  
**Trigger:** An analyst checks results for a campaign or zone.  
**Preconditions:** Authorized report scope with at least some aggregate data.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open scoped analytics dashboard | Load only records permitted by org/site and role. |
| 2 | Select campaign/zone/date/device | Update metric definitions and show report freshness. |
| 3 | Review ad requests, served, rendered and clicks | Show distinct labels and denominators; never call served impressions viewable in R1. |
| 4 | Inspect qualified click and CTR | Explain deduplication, event quality and denominator. |
| 5 | Compare another period | Keep units/currency/timezone consistent and disclose incomplete periods. |
| 6 | Open campaign timeline | Link anomalies to approved changes or service incidents where permitted. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **No qualifying data** → no fabricated zeros for unknown measures.
2. **Late aggregation** → stale-data badge and as-of timestamp.
3. **Unauthorized campaign ID** → access denied without cross-org values.
4. **Browser tracking blocked** → show measurement limitation, not guessed viewability.
5. **Financial figures vs delivery counters disagree** → separate scoped reconciliation investigation.

**Postcondition:** User can state meaning and freshness of each measured number without inferring unmeasured exposure.

**Baseline links:** FR-MET-001..006, FR-REP-001..004; BR-011..013; AC-009  
**UX/accessibility oracle:** Charts have data tables; comparison date/timezone visible; scope displayed.  
**Candidate product telemetry (subject to policy):** `report_opened`, `report_filter_changed`.

### UJ-16 — Finance manager investigates refund or mismatch

**Release:** R1  
**Actors:** PERS-09, PERS-07  
**Screens/interfaces:** UI-14 → UI-11  
**Trigger:** Advertiser reports failed payment, partial refund or inconsistent status.  
**Preconditions:** Authorized finance account, order and provider records.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open order detail | Display invoice, AdPilot mapping, provider state, hold and campaign lifecycle independently. |
| 2 | Inspect chronological events | Show timestamped authoritative callbacks, attempts and prior adjustments. |
| 3 | Request reconciliation | Fetch or process appropriate authoritative provider information through idempotent adapter. |
| 4 | Apply policy to unresolved paid/rejected case | Offer supported revision, cancellation or refund resolution path. |
| 5 | Record approved refund/adjustment | Write precise currency amounts and immutable audit/ledger entries. |
| 6 | Notify advertiser | Provide accurate outcome, next steps and safe reference for support. |
| 7 | Reinspect order | Verify totals and booking/fulfillment consistency. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Provider unavailable** → queued review, do not fabricate refund completion.
2. **Duplicate webhook** → single settled transition.
3. **Chargeback/reversal after active campaign** → policy-controlled pause and dispute.
4. **Currency precision anomaly** → block reconciliation and flag finance.
5. **Operator lacks rights** → read-only or forbidden result.

**Postcondition:** Reconciled order state and an explainable ledger without double adjustment.

**Baseline links:** FR-COM-006..010; BR-003..005, BR-012, BR-014; AC-007  
**UX/accessibility oracle:** Pending versus executed refund clearly separated; irreversible actions require confirm and reason.  
**Candidate product telemetry (subject to policy):** `reconciliation_requested`, `refund_adjustment_recorded`, `discrepancy_resolved`.

### UJ-17 — Administrator recovers from delivery/queue incident

**Release:** R1  
**Actors:** PERS-13, PERS-16  
**Screens/interfaces:** UI-15 → UI-20  
**Trigger:** Scheduler stalls or delivery/analytics endpoints time out.  
**Preconditions:** Admin/support privilege, health telemetry and redacted logs.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open health dashboard | See failed-job count, event lag, delivery status, last successful run and affected module. |
| 2 | Inspect incident detail | Show correlation IDs, bounded redacted diagnostic context and customer-visible effects. |
| 3 | Select recommended recovery | Offer documented rerun/reconcile steps according to job idempotency. |
| 4 | Execute allowed retry | Recheck permissions, deduplication key and current campaign/payment state. |
| 5 | Verify recovery | Compare job result, persisted state, campaign delivery and measurement freshness. |
| 6 | Export safe support bundle if needed | Exclude secrets, raw personal data and unrestricted replay. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Analytics down** → page content remains usable, unknown metrics are not fabricated.
2. **Scheduler inactive** → warn and provide WP-CLI/system cron guidance.
3. **Payment callback delayed** → preserve pending and avoid false completion.
4. **Retry itself fails** → controlled backoff/escalation rather than spin.
5. **Support actor insufficient permission** → limited diagnostics only.

**Postcondition:** Incident is recoverable or escalated with truthful status and no duplicate financial effects.

**Baseline links:** FR-DEL-009, FR-OPS-006..007, FR-OPS-010..012; NFR-005, NFR-012; AC-011  
**UX/accessibility oracle:** Each health alert gives impact, safe action and result; no overwhelming raw stack traces.  
**Candidate product telemetry (subject to policy):** `incident_opened`, `retry_requested`, `recovery_confirmed`.

### UJ-18 — Site administrator configures privacy and consent exclusions

**Release:** R1/R2  
**Actors:** PERS-13, PERS-15  
**Screens/interfaces:** UI-20; consent controls  
**Trigger:** Site owner activates tracking under applicable privacy obligations.  
**Preconditions:** Existing privacy policy and consent management approach reviewed by responsible specialist.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open privacy settings | List each tracking purpose, data type, default state, legal policy and provider dependencies. |
| 2 | Define exclusion contexts | Exclude checkout, password and sensitive-form pages/fields from optional behavioral capture. |
| 3 | Configure consent adapters | Map allowed categories to permitted collection and changing consent state. |
| 4 | Set retention and export/deletion | Select approved bounded policies and data-subject workflow. |
| 5 | Preview tracking state as denied | Confirm no prohibited heatmap/replay SDK starts and no sensitive DOM payload is transmitted. |
| 6 | Preview allowed state | Confirm only approved events and authorized data fields are sent. |
| 7 | Save settings and audit | Record policy version and affected tracking adapters. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Consent revoked mid-session** → stop future prohibited collection; process pending buffers according to approved policy.
2. **Consent provider missing** → conservative fallback, not silent opt-in.
3. **Privacy-sensitive content inserted dynamically** → excluded by collector design.
4. **Retention cleanup fails** → health alert and retriable purge.
5. **Admin tries to enable replay in R1** → unavailable until R4 gate.

**Postcondition:** Tracking behavior adheres to configured policy and release constraints.

**Baseline links:** FR-OPS-004..005, FR-HEAT-008, BR-011..012, BR-017; AC-012  
**UX/accessibility oracle:** Purpose/consent explanations readable and actionable; configuration preview does not leak real visitor data.  
**Candidate product telemetry (subject to policy):** `privacy_policy_updated`, `consent_adapter_validated`, `retention_job_status`.

### UJ-19 — Publisher explores full-page heatmaps

**Release:** R2  
**Actors:** PERS-10, PERS-02  
**Screens/interfaces:** UI-17  
**Trigger:** Publisher wants to know which advertising positions receive visible engagement.  
**Preconditions:** Permitted aggregated events, page layout snapshot/version and device classification.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Select site page and date window | Show available compatible layout versions, event coverage and consent/sampling caveats. |
| 2 | Choose device class and viewport | Filter mobile/tablet/desktop, avoiding mixed incompatible geometry. |
| 3 | Choose click, scroll, slot visibility or attention proxy | Explain metric, denominator and limitations; never label as gaze tracking. |
| 4 | Display sanitized full-page representation | Align overlays to semantic anchor/slot identity and versioned geometry. |
| 5 | Scroll deep into page | Inspect lower content and 200-slot footer without truncating visual map. |
| 6 | Select a zone/slot | Show associated qualified counts, sample size and relative distribution. |
| 7 | Switch to accessible table | Provide sortable textual data for same filtered aggregate results. |
| 8 | Record insight or export permitted aggregate | Preserve filter/version and snapshot provenance. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Page layout changed mid-sample** → segment events by version or exclude incompatible observations.
2. **Low sample/denied consent** → explicit insufficient-data state.
3. **Dynamic geometry cannot be resolved** → flag unmapped sample, never fake coordinate.
4. **Private/sensitive page** → no behavioral visualization from prohibited capture.
5. **Large page overlay performance issue** → tiled/progressive rendering.

**Postcondition:** Full-page, footer and slot-level heatmaps align with the correct version and disclose limitations.

**Baseline links:** FR-HEAT-001..009, FR-MET-007..008, FR-REP-005; BR-011..013; R2 acceptance additions  
**UX/accessibility oracle:** No color-only interpretation; table parity and keyboard zone selection; exposure is not portrayed as attention/gaze.  
**Candidate product telemetry (subject to policy):** `heatmap_opened`, `heatmap_metric_selected`, `heatmap_slot_selected`.

### UJ-20 — Analytics manager compares layouts across versions

**Release:** R2  
**Actors:** PERS-10, PERS-01  
**Screens/interfaces:** UI-17  
**Trigger:** Publisher changed sidebar or footer layout and wants to compare periods.  
**Preconditions:** Two historical layout snapshots and compatible aggregate observations.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Select first time window and layout version | Show version fingerprint, device class and data coverage. |
| 2 | Select comparison window | Warn if geometry, slot meaning or device context differs. |
| 3 | Match stable commercial slot identifiers | Separate actual equivalent positions from migrated/changed physical positions. |
| 4 | View side-by-side overlay/table | Align only compatible mapped samples; annotate changed positions. |
| 5 | Review sample sizes, denominators and freshness | Show uncertainty and missing observations. |
| 6 | Save or export defensible finding | Label observational comparison and prohibit causal uplift claim without experiment. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Layout versions incompatible** → disable direct overlaid difference and offer separated reports.
2. **Low sample sizes** → suppress unreliable ranking and label insufficient evidence.
3. **Ad slot reallocated between periods** → annotate confounding factor.
4. **Heatmap collector disabled in one period** → missing, not zero.
5. **Unchanged slot ID but different commercial location** → honor LayoutVersion mapping.

**Postcondition:** Comparison does not silently mix incompatible page geometry or attribute gains causally.

**Baseline links:** FR-HEAT-004..009, FR-REP-005, BR-006, BR-013; R2 acceptance additions  
**UX/accessibility oracle:** Comparison accessible in table form and describes what changed and why direct comparison may not be valid.  
**Candidate product telemetry (subject to policy):** `heatmap_comparison_opened`, `mapping_incompatibility_noted`.

### UJ-21 — Advertiser recovers abandoned or failed checkout

**Release:** R1  
**Actors:** PERS-03, PERS-07  
**Screens/interfaces:** UI-09 → UI-10 → UI-11  
**Trigger:** A buyer refreshes checkout, returns later, or retries after a failure.  
**Preconditions:** Persistent draft and authoritative order/hold state exist.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Reopen saved draft/order | Load last confirmed draft and authoritative quote/hold/payment state. |
| 2 | Inspect reservation status | Indicate expired or active hold, exact terms and whether a new quote is required. |
| 3 | Select safe retry action | Generate/resolve idempotent payment attempt; do not silently create duplicate obligation. |
| 4 | Complete provider flow or return | Keep state pending until authoritative confirmation. |
| 5 | Check activity timeline | Show captured/declined/pending/unknown state with next action and support path. |
| 6 | If expired and paid after expiry | Route to documented reconciliation, alternative inventory or refund. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Multiple browser tabs** → detect conflicts using version identifiers.
2. **Client lost response after server success** → reconcile existing order instead of repeating.
3. **Hold expired** → require reprice/reavailability, not local-clock trust.
4. **Checkout plugin disabled after draft** → preserve draft and show unavailable checkout diagnostic.
5. **Duplicate provider charge reported** → escalate finance with safe reference.

**Postcondition:** Customer can resume safely without a duplicate campaign, hold or charge.

**Baseline links:** FR-MKT-006, FR-COM-002..008; BR-002, BR-005; AC-007  
**UX/accessibility oracle:** Never use ambiguous “payment successful” when provider confirmation is missing.  
**Candidate product telemetry (subject to policy):** `draft_restored`, `checkout_attempt_reconciled`.

### UJ-22 — AdOps bulk edits generated inventory and filters conflicts

**Release:** R1  
**Actors:** PERS-02  
**Screens/interfaces:** UI-04 → UI-03  
**Trigger:** An operator must manage hundreds of slots efficiently.  
**Preconditions:** One published/generated sequence, sufficient inventory management capability.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open slot navigator | Query paged/cursor results with explicit total and active filters. |
| 2 | Filter by availability, zone and contract | Show stable slot IDs, occupancy and page position. |
| 3 | Select a range of slots | Display selected count across pages and persist selection intent safely. |
| 4 | Choose an allowed bulk operation | Preview all impacted contracts, warnings and skipped unauthorized records. |
| 5 | Confirm transaction | Apply allowed changes with documented atomicity/partial-success semantics. |
| 6 | Inspect results | Show updated count, conflicts, skipped records and audit reference. |
| 7 | Review changes in responsive preview | Ensure no visual/contractual identity drift. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Operation touches sold slots** → block or controlled migration.
2. **Filtered selection changes during operation** → snapshot/version confirmation.
3. **Large request times out** → idempotent async operation with progress/status.
4. **Unauthorized selected slot** → deny or explicitly report without leaking private data.
5. **Undo is unsafe after new bookings** → offer controlled reverse transaction, not instant revert.

**Postcondition:** Bulk operation is traceable; unapproved contract-impact changes are not published.

**Baseline links:** FR-INV-003..011, BR-006, BR-018; AC-001  
**UX/accessibility oracle:** Paginated selection is clear, keyboard accessible, and avoids hundreds of checkboxes in one DOM view.  
**Candidate product telemetry (subject to policy):** `bulk_slot_previewed`, `bulk_slot_updated`, `bulk_slot_rejected`.

### UJ-23 — Authorized user requests aggregate report export

**Release:** R2  
**Actors:** PERS-06, PERS-10, PERS-09  
**Screens/interfaces:** UI-16 → UI-18  
**Trigger:** Stakeholder needs a downloadable report for a permitted time range.  
**Preconditions:** Access to scoped aggregate report and active export service.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Choose report type, fields and filters | Show approximate scope, units and retention policy. |
| 2 | Request CSV/JSON export | Authorize selected org/site and queue signed bounded job. |
| 3 | View job progress | Display queued/running/complete/failed without false completion. |
| 4 | Open download link | Reauthorize current owner and scope; verify expiry and one-time/signed policy. |
| 5 | Inspect included metadata | Include data freshness, denominator, timezone, measurement and sampling notes. |
| 6 | Delete or let export expire | Enforce access revocation and retention cleanup. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Membership revoked after job creation** → download denied.
2. **Large export fails** → bounded retry and diagnostic reference.
3. **No rows** → valid empty export with explicit metadata.
4. **Wrong organization in deep link** → no data.
5. **Expired URL** → controlled regenerate path, not public bucket link.

**Postcondition:** Authorized aggregate report is downloadable and cannot be retrieved by other organizations.

**Baseline links:** FR-REP-003..005, FR-OPS-001, BR-011..012, NFR-008; R2 acceptance additions  
**UX/accessibility oracle:** Progress announced accessibly; export list retains accessible state/error history.  
**Candidate product telemetry (subject to policy):** `export_requested`, `export_ready`, `export_expired`.

### UJ-24 — Reconciled CPM/CPC billing and dispute

**Release:** R3  
**Actors:** PERS-09, PERS-04, PERS-07  
**Screens/interfaces:** UI-19 → UI-14 → UI-16  
**Trigger:** A metered contract disputes the number of chargeable events.  
**Preconditions:** R3 contract type approved and measurement/billing certification gates satisfied.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open signed contract summary | Show price unit, eligibility/qualification, valid windows, caps and currency precision. |
| 2 | Inspect measured and excluded counts | Separate raw, candidate, qualified, reconciled and billed quantities. |
| 3 | Open dispute case | Record allegation, timeframe, scope and authorized evidence request. |
| 4 | Run qualified-event reconciliation | Use immutable contract rules, deduplication and relevant invalid-traffic exclusions. |
| 5 | Propose adjustment | Require finance authorization and reason; compute precise decimal amount. |
| 6 | Post adjustment and outcome | Write immutable ledger entry and notify customer without changing raw history. |
| 7 | Verify statements | Report reconciled total, scope and limitations. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **No independent proof for event** → nonbillable or formally disputed per contract.
2. **Over-cap event** → excluded from charge.
3. **Late invalid traffic adjustment** → amendment record rather than rewriting ledger.
4. **Qualification rule version changed** → use contract-bound rule version.
5. **Unresolvable dispute** → escalate and preserve evidence under retention/legal policy.

**Postcondition:** Billable quantity matches approved qualification rules and immutable adjustments.

**Baseline links:** FR-COM-011, FR-MET-009..010, FR-REP-006; BR-012..014; R3 acceptance additions  
**UX/accessibility oracle:** Distinguish raw vs billable visually and textually; transparent formula and event definition.  
**Candidate product telemetry (subject to policy):** `billing_dispute_opened`, `adjustment_posted`, `dispute_closed`.

### UJ-25 — Publisher runs placement experiment or optimization

**Release:** R3  
**Actors:** PERS-10, PERS-01  
**Screens/interfaces:** UI-01 → reporting/experiment extension  
**Trigger:** Publisher tests whether changing ad position improves outcome.  
**Preconditions:** R3 experimentation released, instrumentation reliable, contract/layout impact reviewed.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Select hypothesis and metric | Declare primary metric, sample/power plan and attribution assumptions. |
| 2 | Choose eligible variants | Prevent changes that violate reserved commercial slot terms. |
| 3 | Review randomized assignment/experiment design | Define exposure and segmentation criteria before launch. |
| 4 | Start experiment | Record version and eligibility constraints; route traffic consistently. |
| 5 | Inspect progress | Show uncertainty, missing data, assignment imbalance and guardrail metrics. |
| 6 | End experiment | Freeze results and explain limitations; avoid unsupported causal claims. |
| 7 | Request operational rollout | Pass separate inventory and contract impact review before publishing winner. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Insufficient sample** → inconclusive result rather than forced winner.
2. **Experiment changes sold slot identity** → block until contract resolution.
3. **Different consent groups** → disclose measurement bias.
4. **Variant harms performance/accessibility** → pause per guardrails.
5. **Concurrent deployment changes layout** → segment/stop experiment.

**Postcondition:** Experiment record is reproducible; recommendation is evidence-based and independently approved.

**Baseline links:** FR-REP-006..007, FR-INV-009, BR-006, BR-013; R3 scope  
**UX/accessibility oracle:** Charts disclose uncertainty and counter metrics, with readable nonvisual comparison.  
**Candidate product telemetry (subject to policy):** `experiment_created`, `experiment_stopped`, `recommendation_reviewed`.

### UJ-26 — Authorized administrator activates optional session replay

**Release:** R4  
**Actors:** PERS-13, PERS-10  
**Screens/interfaces:** UI-20 → UI-17 replay extension  
**Trigger:** Site owner needs rare session-level diagnostics beyond aggregates.  
**Preconditions:** R4 separately deployed/approved replay capability, documented consent policy, separate permission.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Read risk and storage disclosure | Show purposes, excluded pages, masks, sampling and retention. |
| 2 | Configure replay consent gate | Prove replay never starts before allowed consent. |
| 3 | Choose safe sampling/exclusions | Mask/ignore protected inputs and sensitive DOM at collection time. |
| 4 | Run redaction test fixture | Use synthetic sensitive fields and verify payload contains none. |
| 5 | Enable for authorized contexts | Show clear on/off state, data scope and operational health. |
| 6 | Open session playback with elevated capability | Verify access at request and export boundaries. |
| 7 | Disable replay/delete expired sessions | Stop collection and apply retention policy. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Unmasked protected field found** → block enablement and trigger incident review.
2. **Consent revoked** → stop prohibited further collection.
3. **Replay endpoint down** → core ad delivery and heatmaps remain usable.
4. **User lacks replay privilege** → deny without session metadata leak.
5. **Retention purge failure** → alert and retriable cleanup.

**Postcondition:** Replay is opt-in, separately permissioned and privacy-safe; core analytics remains independent.

**Baseline links:** FR-HEAT-010, FR-OPS-005, FR-OPS-011, BR-017; R4 scope  
**UX/accessibility oracle:** UI never makes recording look mandatory, and does not conflate replay with heatmap measurements.  
**Candidate product telemetry (subject to policy):** `replay_config_tested`, `replay_enabled`, `replay_disabled`.

### UJ-27 — Administrator installs, upgrades and diagnoses dependencies

**Release:** R1/R4  
**Actors:** PERS-13, PERS-16  
**Screens/interfaces:** UI-20  
**Trigger:** Site is installing AdPilot or upgrading to new plugin version.  
**Preconditions:** Authorized WordPress administrator and supported runtime.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Activate plugin | Check version prerequisites and run idempotent bootstrap without assuming WooCommerce. |
| 2 | Initialize owned resources | Create documented AdPilot pages, tables, caps and example configurations only. |
| 3 | Review setup checklist | Show commerce adapter readiness, scheduler health, email, cache and privacy configuration. |
| 4 | Upgrade plugin version | Use versioned migrations and record completed schema state. |
| 5 | Encounter recoverable incompatibility | Show safe failure and documented remediation rather than fatal public frontend error. |
| 6 | Inspect health/logs and backup guidance | Explain deactivation versus uninstall data treatment. |
| 7 | Confirm ready features | Mark only verified dependencies ready; retain no-WooCommerce core capabilities. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Repeated activation** → no duplicate pages/roles/seed content.
2. **Migration interrupted** → safe recovery/retry with version check.
3. **WooCommerce missing** → online checkout unavailable, core still works.
4. **Third-party plugin conflict** → isolated diagnostic without changing unrelated settings.
5. **Multisite installation** → site scope, no automatic R4 network federation.

**Postcondition:** Core plugin remains installable/upgradeable with truthful readiness diagnostics.

**Baseline links:** FR-OPS-006..013, FR-COM-013, NFR-010, NFR-015; AC-014..015  
**UX/accessibility oracle:** Setup check list accessible; next action clear, no silent changes to unrelated WordPress settings.  
**Candidate product telemetry (subject to policy):** `installation_initialized`, `migration_completed`, `dependency_warning`.

### UJ-28 — Publisher runs complimentary/manual campaign without WooCommerce

**Release:** R1  
**Actors:** PERS-01, PERS-02  
**Screens/interfaces:** UI-11 → UI-14 → UI-15  
**Trigger:** Publisher wants internal or manually approved ad without payment integration.  
**Preconditions:** WooCommerce absent/disabled; authorized admin; inventory and creative policies.

| Step | Actor (A) | System (S) |
|---:|---|---|
| 1 | Open AdPilot with WooCommerce absent | Initialize all non-checkout modules normally. |
| 2 | Create manual/complimentary campaign | Specify reason, actor, contract/authorization policy and booked placement. |
| 3 | Review inventory and creative | Apply same reservation exclusivity, moderation and safe creative rules. |
| 4 | Save audit trail | Record administrative authorization and contract exception without creating fake paid order. |
| 5 | Schedule and deliver if eligible | Run standard ad-serving logic and report measured results. |
| 6 | Visit public storefront | Show truthful “online checkout unavailable” state and permitted contact/manual path. |

**Alternative paths, failure handling and security/privacy boundaries**

1. **Admin attempts paid online self-service** → clear WooCommerce prerequisite and disabled CTA.
2. **Manual free campaign overlaps exclusive sold contract** → block or resolve policy.
3. **Unapproved creative submitted manually** → still cannot serve.
4. **WooCommerce later installed** → no silent conversion of manual campaign into paid order.
5. **WooCommerce plugin deactivates mid-operation** → preserve records and suspend checkout only.

**Postcondition:** Core platform functions correctly without WooCommerce; no fictitious money movement.

**Baseline links:** FR-COM-013, FR-OPS-012, FR-CAM-006, BR-003, BR-005; AC-014  
**UX/accessibility oracle:** No dead links or misleading payment labels when checkout is disabled.  
**Candidate product telemetry (subject to policy):** `manual_campaign_created`, `commerce_adapter_unavailable`.

### 7.1 Journey composition rules

- `UJ-01..UJ-05` form a complete *browse → account → create → reserve → pay* sequence, but the order of creative submission and checkout may vary by approved workflow; in either arrangement, serving requires both commercial and moderation eligibility.
- `UJ-09..UJ-12` cover one coherent ad position from visual Layout Studio through Gutenberg/theme binding; integration paths cannot create independent pricing or approval rules.
- `UJ-13..UJ-15` cover visitor-facing delivery through measured reporting; all step-level counters retain distinct definitions.
- `UJ-19..UJ-20` are R2 analytical experiences only. Do not infer them as R1 deliverables because the page has basic click counters.
- `UJ-24..UJ-26` are explicitly R3/R4; opening routes or rendering placeholder UI does not make them production-ready.
- Failure variants must be implemented as first-class test cases, not exceptional customer-support instructions.

---

## 8. Atomic workflow/state contracts

`WF-*` denotes a transactional or cross-screen interaction contract. These are intentionally more precise than UI routes. Commands must be checked against authoritative server state; user interface transitions mirror confirmed results. Detailed state diagrams will be formalized in a later Business Rules / Domain State Machine document.

| Workflow | Valid initiation | Authoritative decision | Required UX result | Invalid/exception result |
|---|---|---|---|---|
| `WF-01` Draft autosave | Authorized org editor with mutable draft | Compare draft version, validate allowed fields, persist | `Saving` → `Saved at…` after acknowledgment | Conflict → preserve local edits and offer compare/reload; not false success |
| `WF-02` Creative submission | Valid creative metadata and owner | Validate file, destination and version, queue moderation | `Pending review`, immutable version reference | Rejected file/policy → specific actionable error; old approved version policy retained |
| `WF-03` Creative approval | Moderator authorized on pending version | Moderation rule plus version check and audited decision | `Approved` or `Changes requested` with reason | Version changed → re-review; cannot approve stale blob |
| `WF-04` Quote generation | Sellable inventory + valid period | Versioned rate, taxes, discounts, current capacity | Itemized terms with quote expiry | Unsupported product/rate → no quote; alternatives if eligible |
| `WF-05` Reservation hold | Active accepted quote | Atomic capacity/exclusivity check and idempotency | Reservation ID, expires at UTC and display zone | Collision/expiry → no hold, clear fresh-quote path |
| `WF-06` Checkout initiation | Owned valid hold and WooCommerce ready | Map to supported Woo order with idempotent order reference | Payment in progress, no claim of settlement | Missing Woo → disable online purchase and preserve campaign draft |
| `WF-07` Payment reconcile | Trusted payment provider event | Verify authority, replay/out-of-order protection, update ledger/order | Confirmed pending/settled/failed/refunded state | Ambiguous provider state → pending/manual reconciliation, never optimistic success |
| `WF-08` Delivery eligibility | Current schedule, contract, allocation, creative | Re-evaluate all policy gates | Ready/active/paused reasoned status | Stale cache/campaign disabled → no new served creative |
| `WF-09` Layout publish | Authorized new draft and version | Validate bindings, sold-slot impact, concurrent version, density | Published layout version with summary | Incompatible sold slot → block or audited migration workflow |
| `WF-10` Slot bulk mutation | Operator with inventory privilege | Snapshot affected records and contract compatibility | Applied/skipped/conflicted counts, audit reference | Partial failure → explicit consistent outcome and retry id |
| `WF-11` Public ad decision | Eligible slot render opportunity | Batched selection, contract policy, preview exclusion | Creative markup or safe empty/fallback | Delivery service failed → content intact and truthful missing counters |
| `WF-12` Event ingestion | Permitted browser observation | Validate schema, trusted context, dedupe, queue aggregate | No user-facing jank; safe transport response | Unknown/untrusted → rejected/excluded, never automatically billable |
| `WF-13` Report query | Authorized site/org/scope and timeframe | Aggregate data with stable metric definitions | Filters, as-of timestamp, denominator, coverage | Missing/stale → clearly labeled, no invented zero |
| `WF-14` Heatmap query (R2) | Allowed aggregate, layout version and device | Compatible geometry mapping with consent/sampling metadata | Overlay + table + sample/denominator | Unmappable geometry → exclude/flag; do not blend |
| `WF-15` Report export (R2) | Report scope and export permission | Queue filtered job; authorize each download | Progress and expiring protected link | Revoked membership → deny download, even if job completed |
| `WF-16` Metered reconciliation (R3) | Approved CPC/CPM contract and qualification data | Contract-specific billable ledger, adjustments | Explain unit, excluded events, amount and status | Dispute/mismatch → freeze or reconcile, not hidden counter rewrite |
| `WF-17` Replay opt-in (R4) | Elevated permission, enabled feature, consent policy | Safe masking, recording sampling and retention contract | Clear recording and deletion controls | Masking or consent failure → no collection |
| `WF-18` Operational retry | Authorized operator and retriable job | Current-state check with dedupe/attempt bounds | Accurate retry result and incident linkage | Terminal/unsafe job → explain and require human resolution |

### 8.1 Independent state machines that must not be collapsed

**Campaign approval:** `draft → submitted → under_review → approved | changes_requested | rejected`. Versioned resubmission returns the new creative version to an appropriate review state. A campaign-level approval must not implicitly validate every future creative revision.

**Financial order:** `draft/quoted → hold_active → pending_payment → provider_confirmed/settled | failed | expired | disputed | refunded`. States and transitions are conceptual; no fictional provider events. Reversals create auditable history, not destructive deletion.

**Delivery:** `not_ready → scheduled → active → paused/suspended → completed/expired/cancelled`, with permitted recovery and explicit precedence for emergency stop, contract expiry and payment reversal. Being `paid` alone never means `active`.

**Layout publication:** `draft → previewed → validated → published`, with blocking conflict state and controlled rollback/migration. Historical layout versions remain available for measurement interpretation even after a later version supersedes them.

**Export job:** `queued → processing → ready | failed | expired | revoked`. Authorization must be rechecked at download time.

### 8.2 Workflow concurrency and freshness contract

- Every mutable long-lived screen carries a server-issued version/ETag or equivalent optimistic concurrency token.
- Availability displayed on a calendar or quote is advisory until capacity is atomically reserved; visually distinguish `available now` from `held for you`.
- Buttons submit idempotent commands where duplicated requests are foreseeable; UI disabled state alone is insufficient protection.
- When a separate actor modifies a record, present a conflict notice with old/new state and safe merge/reload; do not silently overwrite moderation, inventory, financial or layout changes.
- Payment and reservation timestamps are server-authoritative. Visual timers are guidance only; network drift never extends expired inventory by itself.
- Invalidating a render cache must be testable after suspension, contract expiry and creative reapproval.

---

## 9. Screen-by-screen interaction contracts (`UI-01..UI-20`)

The 20 IDs below are **retained verbatim** from ADP-DOC-002. This mapping is more detailed about interaction states, content hierarchy, primary action, permission and accessibility behavior. Full visual specifications will follow in separate UI/Design System documents.

### UI-01 — Admin executive dashboard

- **Primary personas / release:** PERS-01/02/09/10 / R1.
- **First-view information hierarchy:** Publisher KPIs, campaign/approval queues, alerts, reporting freshness.
- **Primary authorized actions:** Review alerts; drill down to affected campaign, inventory or order.
- **Required exceptional/degraded behavior:** Missing analytics and financial records shown as distinct unavailable/stale, not zero.
- **Accessibility and mobile:** Metric definitions and table alternatives; dashboard keyboard order.
- **Referenced journeys:** UJ-14,UJ-15,UJ-16,UJ-17.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-02 — Zone registry

- **Primary personas / release:** PERS-01/02 / R1.
- **First-view information hierarchy:** Zone state, stable ID, container count, bindings, occupancy.
- **Primary authorized actions:** Create, clone, archive, search and open Studio.
- **Required exceptional/degraded behavior:** Archiving sold zone requires conflict resolution; no silent removal.
- **Accessibility and mobile:** Bulk selection/focus order and explicit empty-zone CTAs.
- **Referenced journeys:** UJ-09,UJ-10,UJ-22.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-03 — Layout Studio

- **Primary personas / release:** PERS-01/02 / R1.
- **First-view information hierarchy:** Tree + preview canvas + inspector + publish/impact summary.
- **Primary authorized actions:** Create/preview/breakpoint compare/validate/publish version.
- **Required exceptional/degraded behavior:** Sold slot collision blocks publication; stale version conflict.
- **Accessibility and mobile:** Keyboard slot navigation and structural alternative to drag/drop.
- **Referenced journeys:** UJ-09,UJ-10,UJ-22.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-04 — Slot navigator

- **Primary personas / release:** PERS-02 / R1.
- **First-view information hierarchy:** Stable slot ID, occupancy, status, filters, logical position.
- **Primary authorized actions:** Paginated search, multi-select, safe bulk edit.
- **Required exceptional/degraded behavior:** Selection across pages, race and partial success explicitly reported.
- **Accessibility and mobile:** Accessible row/selection count; no massive full DOM list.
- **Referenced journeys:** UJ-09,UJ-10,UJ-22.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-05 — Template binding inspector

- **Primary personas / release:** PERS-11/12 / R1/R2 diagnostic enhancement.
- **First-view information hierarchy:** Page/template/context binding, hook/code reference, duplicates.
- **Primary authorized actions:** Bind supported context and inspect render guidance.
- **Required exceptional/degraded behavior:** Unresolved target, accidental duplicate and incompatible theme warning.
- **Accessibility and mobile:** Copyable snippets, semantic statuses, keyboard inspector.
- **Referenced journeys:** UJ-11,UJ-12.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-06 — Public catalog

- **Primary personas / release:** PERS-14/03 / R1.
- **First-view information hierarchy:** Published placements/packages, terms, facets, public performance context.
- **Primary authorized actions:** Filter, compare, open published offer.
- **Required exceptional/degraded behavior:** No results, outdated prices, no-Woo checkout; no private data.
- **Accessibility and mobile:** Responsive facet controls, result counts, labeled cards.
- **Referenced journeys:** UJ-01,UJ-28.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-07 — Placement detail

- **Primary personas / release:** PERS-14/03 / R1.
- **First-view information hierarchy:** Position preview, sizes, device rules, pricing unit, approvals, availability.
- **Primary authorized actions:** Review details and start buying.
- **Required exceptional/degraded behavior:** Historical estimates flagged; unavailable slot offers alternatives.
- **Accessibility and mobile:** Alt/sponsored preview semantics and reduced-motion handling.
- **Referenced journeys:** UJ-01,UJ-04.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-08 — Advertiser onboarding/team

- **Primary personas / release:** PERS-03/05/04 / R1.
- **First-view information hierarchy:** Organization identity, verification, members, invites, active context.
- **Primary authorized actions:** Create organization, invite, change authorized role.
- **Required exceptional/degraded behavior:** Expired invite, organization switch, permission change.
- **Accessibility and mobile:** Field errors, active-org announcement, accessible invitation status.
- **Referenced journeys:** UJ-02,UJ-06.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-09 — Campaign wizard

- **Primary personas / release:** PERS-03/04 / R1.
- **First-view information hierarchy:** Step progress, persisted draft, creative version, schedule, quote.
- **Primary authorized actions:** Next/back/save/submit with validation.
- **Required exceptional/degraded behavior:** Partial upload failure, stale quote, autosave collision.
- **Accessibility and mobile:** Focus first error, nonsecret state retention, clear step heading.
- **Referenced journeys:** UJ-03,UJ-04,UJ-21.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-10 — Checkout handoff

- **Primary personas / release:** PERS-03/07 / R1.
- **First-view information hierarchy:** Booked item and expiry, payment attempt, authoritative status.
- **Primary authorized actions:** Pay, return to portal, safely retry.
- **Required exceptional/degraded behavior:** Hold expiry, duplicate payment event, missing WooCommerce.
- **Accessibility and mobile:** No optimistic paid confirmation; accessible pending state.
- **Referenced journeys:** UJ-04,UJ-05,UJ-21,UJ-28.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-11 — Campaign list/detail

- **Primary personas / release:** PERS-02/03/04/08 / R1.
- **First-view information hierarchy:** Distinct approval/payment/delivery chips, version, timeline, permitted actions.
- **Primary authorized actions:** Review, request change, pause/resume as authorized.
- **Required exceptional/degraded behavior:** Unapproved creative, expired contract, concurrent status.
- **Accessibility and mobile:** Textual state/timeline; explain why action disabled.
- **Referenced journeys:** UJ-03,UJ-05,UJ-07,UJ-14.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-12 — Creative moderation

- **Primary personas / release:** PERS-08 / R1.
- **First-view information hierarchy:** Sanitized creative preview, policy checklist, version diff, decision reason.
- **Primary authorized actions:** Approve/reject/request changes, emergency stop.
- **Required exceptional/degraded behavior:** Unsafe destination, changed version, missing reviewer rights.
- **Accessibility and mobile:** Keyboard media inspection, alt text view, readable evidence.
- **Referenced journeys:** UJ-07,UJ-14.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-13 — Booking calendar

- **Primary personas / release:** PERS-02/03 / R1.
- **First-view information hierarchy:** Available/held/occupied intervals, timezone, capacity.
- **Primary authorized actions:** Choose period, review conflicts and alternatives.
- **Required exceptional/degraded behavior:** DST ambiguity, stale availability, booking collision.
- **Accessibility and mobile:** Tabular/list calendar alternative; no color-only occupancy.
- **Referenced journeys:** UJ-04,UJ-08.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-14 — Rates/orders and scoped billing

- **Primary personas / release:** PERS-09/07/01 / R1.
- **First-view information hierarchy:** Rate versions, items, currency/tax, invoices, payment/refund ledger.
- **Primary authorized actions:** Review order, reconcile, issue allowed adjustment.
- **Required exceptional/degraded behavior:** Gateway unavailable, out-of-order callbacks, mismatch.
- **Accessibility and mobile:** Strong numeric alignment, localized money, confirmation on irreversible actions.
- **Referenced journeys:** UJ-05,UJ-16,UJ-24.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-15 — Delivery diagnostics

- **Primary personas / release:** PERS-02/13/16 / R1.
- **First-view information hierarchy:** Eligibility checks, last dispatch, queue delay, endpoint health.
- **Primary authorized actions:** Investigate, pause, invoke safe retry.
- **Required exceptional/degraded behavior:** Stale cache, failed queue, unavailable analytics.
- **Accessibility and mobile:** Text reasons, safe correlation identifier, screen-reader alerts.
- **Referenced journeys:** UJ-12,UJ-14,UJ-17.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-16 — Advertiser reporting

- **Primary personas / release:** PERS-06/03/04 / R1.
- **First-view information hierarchy:** Org-scoped aggregates, explicit denominator, freshness, time filter.
- **Primary authorized actions:** Compare campaigns; inspect allowed details.
- **Required exceptional/degraded behavior:** Unmeasurable data, sparse history, revoked membership.
- **Accessibility and mobile:** Sortable tables alongside charts and visible units.
- **Referenced journeys:** UJ-15,UJ-23.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-17 — Heatmap explorer

- **Primary personas / release:** PERS-10 / R2.
- **First-view information hierarchy:** Full-page sanitized layout, version, device, click/scroll/visibility overlays.
- **Primary authorized actions:** Choose metric/version, inspect slot, compare, switch table.
- **Required exceptional/degraded behavior:** Layout drift, insufficient consent/sample, low geometry quality.
- **Accessibility and mobile:** Noncolor numeric view, keyboard pan/zoom controls.
- **Referenced journeys:** UJ-19,UJ-20,UJ-26.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-18 — Export jobs

- **Primary personas / release:** PERS-06/09/10 / R2.
- **First-view information hierarchy:** Report scope, job state, expiry, destination authorization.
- **Primary authorized actions:** Request/download/retry permitted aggregate export.
- **Required exceptional/degraded behavior:** Expired link, revoked membership, failed async job.
- **Accessibility and mobile:** Status announcements and retry instructions.
- **Referenced journeys:** UJ-23.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-19 — Billing dispute

- **Primary personas / release:** PERS-09/04 / R3.
- **First-view information hierarchy:** Billable counts/qualifiers, contract terms, case and adjustments.
- **Primary authorized actions:** Open/review/resolve dispute.
- **Required exceptional/degraded behavior:** Unqualified events and missing provenance; late adjustments.
- **Accessibility and mobile:** Explanatory math and explicit irreversible-step confirmation.
- **Referenced journeys:** UJ-24.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### UI-20 — Settings/system health

- **Primary personas / release:** PERS-13/16 / R1.
- **First-view information hierarchy:** Dependency health, privacy/retention, queue, roles, upgrade diagnostics.
- **Primary authorized actions:** Configure, validate, recover, export safe diagnostics.
- **Required exceptional/degraded behavior:** Missing WooCommerce, failed migration, invalid consent config.
- **Accessibility and mobile:** Grouped sections, precise dependency messages, protected actions.
- **Referenced journeys:** UJ-17,UJ-18,UJ-26,UJ-27.
- **Screen evidence:** screenshot/DOM state for default, loading, no data, forbidden, validation error and failure as applicable; keyboard walkthrough; mobile at agreed test widths; API permission test; event/finance/layout contract test where relevant.

### 9.1 Screen ownership and UI release governance

- **UX Lead:** acceptance of information hierarchy, end-to-end task completion, microcopy and recovery patterns.
- **Visual UI/Design System Engineer:** component tokens and consistent visual design, density, iconography and responsive behavior.
- **Senior React/TypeScript Engineer:** typed client state, server reconciliation, deterministic forms, accessible asynchronous feedback.
- **Gutenberg Engineer:** editor-side block UX and preservation of WordPress-native editing patterns.
- **Frontend Rendering Engineer:** public ad markup performance, CSS isolation, progressive loading and fallback.
- **Accessibility Engineer:** keyboard/screen reader validation and parity of numerical alternatives.
- **QA Engineer:** actual test evidence for every screen/state; all scoped unauthorized API paths.

UX sign-off never substitutes for backend security, contract or finance sign-off.

---

## 10. Detailed experience design for flagship surfaces

### 10.1 Layout Studio: high-density operational design

**Workspace layout:** desktop three-pane (zone tree/slot search; central responsive canvas; contextual inspector) plus persistent validation/publication status. On smaller widths, panels collapse into accessible sequential views, never disable contractual review. A slot detail may open a drawer with stable ID, allocation, geometry, status, reservation references and layout version.

**Create from template:** choose `header-row`, `sidebar-stack`, `content-repeater`, `footer-grid`, or blank; define slot count; set allowed desktop/tablet/mobile rules; see immediate count and projected document flow. One configuration must produce 100/200 slots, *not* hundreds of nested Gutenberg blocks.

**Batch edit:** operators can select visible/page/range/all-filtered slots with a clearly stated scope; opening a bulk action shows a complete impact summary before commit. Where an operation runs asynchronously, provide job progress and audit references; no misleading immediate completion.

**Zoom and preview:** in normal mode use representative items or a low-cost wireframe; enable full technical preview only with clear warning, bounded rendering, and nonbillable event mode. Separate preview of ad positions from preview of sensitive advertiser content.

**Publish control:** `Draft` → `Validate` → `Review conflicts` → `Publish` → `Published version ID`. Layout versions are immutable. Rollback is another controlled publication action, not an edit to historical records.

**Responsive and structural meaning:** changing `columns: 5 → 1` is normally a responsive presentation change; deleting/reordering commercial slots can change paid placement meaning. The inspector must display these distinctly. Use explicit desktop/tablet/mobile constraints, CSS scoped to AdPilot, and no hidden animation that obscures slot commercial position.

**Acceptance traps:** loading 200 slot cards in the editor by default; slot IDs based solely on row/column; moving a paid slot without review; drag-only interactions; counting preview render as billable; treating 500 as a hard product limit. Any is a release defect.

### 10.2 Advertiser purchase funnel

**Information architecture:** offer discovery → placement detail → account/org context → campaign draft/creative → schedule/quote → accepted reservation hold → configured WooCommerce checkout → authoritative status → moderation → eligible delivery. The UI may reorder creative/checkout steps according to approved policy; the state machine remains independent.

**Friction reducers:** transparent published rate units, preview of dimensions and disclosure placement, progressive form fields, autosave with server acknowledgment, persisted offer selection through login, visible quote expiry, clear cancellation/refund terms, form error focus and documentable support path.

**Not permitted:** fake scarcity counters, fabricated reach predictions, forced opt-in tracking unrelated to purchase, hidden fees, disguised approval condition, or payment success derived from redirect parameters.

**Status summary card:** separately show **Payment: pending/confirmed/refunded**, **Creative: awaiting review/changes requested/approved**, **Delivery: not ready/scheduled/active/paused/completed**; include last update time and next authorized action. This card is consistent on checkout return, campaign detail and notifications.

**Conflict/recovery:** changed pricing demands new acknowledgment, expired holds demand recheck, failed payment must not create duplicate obligations, lost network responses must reconnect to authoritative order. A purchaser should never have to infer whether they have paid.

### 10.3 Moderation and advertiser feedback

Moderation list supports queues by age, format, site policy, last revision and associated sold inventory. Detail view shows preview, destination URL, disclosures/alt text, revision diff and required checks. Reject/request-change reasons should use actionable plain language; if a policy code is used, include human-readable explanation. The reviewer sees financial readiness only as required to make safe operational decisions, not unrestricted payment credentials. User notification names the exact version, decision and next step. The system keeps approved-old and unapproved-new version states separate under explicit serving policy.

### 10.4 Full-page analytics and heatmap viewer (R2)

**Header data:** selected page URL/template in sanitized form, layout version, date range, device and viewport class, collection policy, sampled event count, missing/unmapped percentage, as-of timestamp and measurement definition.

**Main viewer:** sanitized version-aligned page representation with overlay mode (`click/tap`, `scroll`, `slot visibility`, optional `attention proxy`). Use incremental/tiled drawing for 500+ slot pages and preserve scroll position while changing filters. Inspector links heatmap coordinates to stable semantic anchors and render/slot IDs; overlay transparency and legend are controls, not substitutes for numeric explanation.

**Numeric alternative:** sortable table with zone/container/slot, event count, exposure opportunity/denominator, coverage, percentage/rate where applicable, device and layout version. Exported data includes definitions and limitations.

**Version comparison:** restrict direct spatial comparison to sufficiently compatible geometry; if physical layout changed, place versions side by side with mapped equivalent slots or explicitly refuse a misleading heatmap difference. Do not call mouse hover or viewport time “eye tracking.”

**Privacy:** only authorized aggregated data visible to publisher analytics personnel. No raw session replay for ordinary advertiser analysts. Sensitive pages and fields must be excluded at collection. Empty/no-consent/low-sample states are distinct and explained.

### 10.5 Classic/block theme and Gutenberg integration UX

**Block inserter:** four discoverable blocks with distinct names, icons, keyboard-friendly controls and help links. Dynamic blocks store references, not whole creative sequences. Inspector describes zone eligibility and preview coverage; block-level overrides cannot bypass financial, security or delivery rules.

**Theme developer UX:** provide copyable snippets and arguments for guarded PHP calls, shortcodes and hook registrations, with escaping/output semantics and troubleshooting notes. Detect unresolved or unexpectedly duplicate bindings. Report preview/staging events separately from production.

**Semantic parity:** one logical zone and current campaign state must produce equivalent permissible creative decisions irrespective of PHP, shortcode, Gutenberg or hook rendering path; each render occurrence has a unique event context.

---

## 11. UX microcopy, notification and decision standards

### 11.1 Critical sample messages (normative intent, wording may be localized)

| Condition | Recommended meaning-bearing text | Next action |
|---|---|---|
| Quote available | “This quote is valid until [timestamp] in [timezone]. Inventory is not booked until reservation succeeds.” | Reserve now |
| Hold active | “This placement is reserved for your checkout until [timestamp].” | Continue to payment |
| Hold expired | “Your reservation expired. We need to check availability and pricing again.” | Recheck availability |
| Provider return pending | “We’re verifying the payment with the payment provider. Your campaign is not active yet.” | Refresh verified status |
| Paid, creative under review | “Payment confirmed. Your creative is awaiting approval; no advertisement is being served yet.” | View review status |
| Creative changes requested | “The reviewer requested changes to creative version [version]: [safe reason].” | Edit and resubmit |
| Layout blocked | “[count] paid or reserved positions would change. Review affected contracts before publishing.” | Review impact |
| High-density warning | “This layout defines [count] slots; visible ad density and loading are subject to your site's performance and policy limits.” | Preview and review |
| No WooCommerce | “Online checkout is unavailable until WooCommerce and a payment method are configured. Manual campaigns still work.” | Configure integration (authorized admin) |
| Analytics unavailable | “Metrics for this period are unavailable or delayed; this does not mean zero impressions.” | Check freshness |
| Heatmap low sample | “This view has insufficient eligible observations to support a reliable spatial comparison.” | Expand range or change cohort |
| Consent denied | “Optional engagement recording is disabled for this visitor.” | View consent policy |
| Member access removed | “You no longer have permission to open this report.” | Contact account owner |
| Job retry requested | “Retry requested. Completion will appear here after the server confirms it.” | View job status |

Messages must interpolate escaped/validated values only. Sensitive gateway responses, database traces, visitor identifiers and secrets must never appear in end-user copy.

### 11.2 Notification matrix

| Event | Recipient | Channel | Required context | Delivery semantics |
|---|---|---|---|---|
| Draft saved failure | Current editor | In-app | Last acknowledged version and retry | Immediate after failed save; no false success |
| Reservation confirmed/expired | Buyer | In-app; optional email | Placement, time zone, expiry, next step | Idempotent/de-duplicated |
| Payment confirmed/failed | Buyer and authorized billing staff | Portal/email where configured | Trusted provider status, order ref, next action | After authoritative confirmation only |
| Creative decision | Submitter and campaign manager | Portal/email | Version, decision, policy reason, resubmit path | One logical notification per audited decision |
| Campaign scheduled/active/paused | Authorized advertiser and AdOps | Dashboard; optional email | Three statuses, effective time and actor | Recompute, no premature active claims |
| Layout sold-slot conflict | AdOps/site admin | UI alert | Impacted count, contract links | Persistent until resolved |
| Queue/analytics incident | Site admin/support | Health/notification | Scope, severity, correlation ID | Rate-limited with alert deduplication |
| Export ready/failure | Report requester | Portal/email | Scoped report, expiry, protected access | No unprotected download URL |
| Replay privacy failure (R4) | Security/privacy/admin | Restricted incident channel | Synthetic or redacted diagnostics | Escalation, collection disabled |

---

## 12. Accessibility, responsive, localization and performance acceptance

### 12.1 Accessibility: target WCAG 2.2 AA

- All interactive flows keyboard operable with visible focus and logical focus management.
- Labels/errors tied to controls; explicit validation summary and focus to first invalid field.
- Dialogs, tabs, calendars, tables, breadcrumbs, live status and drag/drop alternatives use appropriate semantics and announcements.
- Heatmap color legends have numeric/table equivalents; link and status meaning never depend on color alone.
- Dynamic updates avoid intrusive live-region spam; major asynchronous completions are announced when meaningful.
- Media creatives follow their specified accessibility obligations (alt text, pause controls for motion/video when added, predictable links).
- Touch target sizes, contrast, reflow, orientation, reduced motion and RTL interactions are explicitly audited.

### 12.2 Responsive acceptance

| Context | Required behavior |
|---|---|
| Narrow mobile viewport | Single-column purchase wizard and readable price/quote content; no offscreen mandatory actions |
| Mobile advertiser dashboard | Cards or scrollable well-labeled tables preserving all material financial/approval data |
| Tablet Layout Studio | Collapsible inspector/tree, accessible sequential operation, retained version/slot context |
| Desktop Layout Studio | Dense three-pane workspace without unbounded controls in DOM |
| Public page with 200 footer slots | Responsive grid with appropriate space reservation/density limits, no unexpected sideways scroll |
| R2 full-page heatmap on mobile | Viewer and table remain accessible; visual panning does not trap page scrolling |
| RTL site/editor | Mirrored layout/navigation appropriate to locale; mathematical notation, money and IDs remain unambiguous |

Test representative widths rather than assuming that three named devices encompass all viewport sizes. Device targeting and responsive slots should not silently promise identical impression opportunities on all layouts.

### 12.3 Performance-oriented UX

- Avoid eager rendering of 200–1,000 interactive slot elements in editors or storefront filters; use pagination/virtualization when appropriate.
- In public pages, prefer progressive creative fetching and stable placeholders; benchmark document flow when considering virtualization (it can disrupt scroll depth and heatmap coordinates).
- Show progress for long-running bulk operations and exports without blocking unrelated administration.
- Preserve local input when nonfinancial updates fail, but only claim persistence after server acknowledgment.
- Charts and heatmaps should load on demand and not inflate the baseline public ad SDK.
- Establish p75/p95 interaction, rendering and memory budgets after representative hardware and workload tests; do not invent universal guaranteed numbers.

### 12.4 Internationalization and temporal semantics

- All user-visible messages use WordPress localization conventions and proper pluralization where applicable.
- Dates/times display user-selected/site zone, stored contract timestamps remain UTC, and DST ambiguous/nonexistent times are handled explicitly.
- Financial display includes ISO currency and applicable scale, localized grouping, and precise amount computation outside floating-point UI-only arithmetic.
- Translation accommodates longer strings; testing includes at least one RTL locale and longer-label layouts.

---

## 13. Security, privacy, data minimization and trust boundaries in UX

| Boundary | UX obligation | Server/data obligation | Journey proof |
|---|---|---|---|
| Advertiser A versus B | Do not show other organization's controls or options | Enforce object membership on every route/export/job | UJ-06, UJ-15, UJ-23 |
| Advertiser versus publisher | Only allowed campaign, billing and aggregate reports | Never expose internal margins, raw visitors, reconciliation internals without scope | UJ-01, UJ-15, UJ-16 |
| Payment return versus settled | Show pending until verified | Payment provider/backend is authoritative; idempotent mapping | UJ-05, UJ-21 |
| Admin override versus contract | Require affected contracts and reason | Audit and validate commercial conditions | UJ-08, UJ-10, UJ-22 |
| Editor preview versus public serving | Explicit preview/nonbillable label | Exclude preview from revenue-bearing events | UJ-09, UJ-11, UJ-13 |
| Behavioral analytics versus visitor privacy | Clear consent, opt-out and restricted displays | Exclude sensitive pages/fields, minimize data and retention | UJ-18..UJ-20, UJ-26 |
| Theme integration versus security | Code examples safe and minimal | Public API cannot bypass eligibility or execute arbitrary user PHP | UJ-12 |
| Report availability versus report accuracy | Freshness/sampling/denominator in UI | Aggregate data keyed by exact scope and version | UJ-15, UJ-19..UJ-20, UJ-23 |
| Health/recovery versus site stability | Recovery guidance with safe action labels | Backoff, idempotency, redacted diagnostics, nonblocking public page | UJ-17, UJ-27 |

### 13.1 Data handling and visual disclosure requirements

Store/report only fields required for legitimate use. A publisher heatmap may expose aggregate engagement by element and slot; it should not contain full visitor identities, sensitive typed inputs or raw payment data. An advertiser report must not provide session-level visitor activity. Request logs, error payloads and export files must be protected to the same standard as the underlying data. Automatic user invitations, support documents and provider callbacks must be tested for accidental leakage through URLs or email bodies.

### 13.2 Privacy-related interface failures

Consent-provider failure must follow the approved conservative policy. Disabling optional analytics must never disable legitimate content access. When tracking is stopped mid-session, the client must handle queued data according to the approved policy and document/report uncertainty in downstream aggregates. Any collector snapshot facility must be explicitly synthetic or privacy reviewed; debugging must not introduce an ungoverned parallel recording channel.

---

## 14. Negative-case and adversarial interaction catalog

The following cases must be covered in UX test plans *and* backend/security tests where relevant; success is not achieved by displaying a warning while allowing an unsafe action through an API.

| Case | Expected product behavior | Related journey(s) |
|---|---|---|
| N-01: anonymous deep link to unpublished offer | Deny private metadata without offer-existence disclosure | UJ-01 |
| N-02: create organization under an unverified invitation | Require approved verification and reject unauthorized membership | UJ-02, UJ-06 |
| N-03: submit JavaScript creative disguised as an image | Reject upload server-side; no active creative | UJ-03, UJ-07 |
| N-04: forged price/currency from browser | Recompute quote on server; no client-authoritative money | UJ-04 |
| N-05: two buyer sessions request same exclusive inventory | Only one successful hold/contract; other sees truthful alternatives | UJ-04, UJ-08 |
| N-06: duplicate provider redirect or callback | One authoritative financial transition, no duplicate charge mapping | UJ-05, UJ-21 |
| N-07: stale creative moderator view | Conflict, refresh/review current version | UJ-07 |
| N-08: publisher removes slot booked for next month | Block or audited authorized migration before publish | UJ-10 |
| N-09: 200-slot block inserted twice | Allow intentional repeated rendering with unique identity or diagnose accidental duplicate | UJ-11, UJ-12 |
| N-10: shortcode overrides hidden approval field | Ignore/reject override; server gating remains | UJ-12 |
| N-11: visitor has no JS | Page remains navigable; dynamic ad and measurement limits documented | UJ-13 |
| N-12: ad SDK attempts to load 500 creatives on first viewport | Performance violation; bounded batch/proximity required | UJ-13 |
| N-13: scheduler retries activation following pause | Current state wins; suspended creative never reactivates accidentally | UJ-14 |
| N-14: report aggregates delayed by event outage | Show stale/unavailable; not zero or falsely reconciled | UJ-15 |
| N-15: cross-organization analytics ID guessing | Scoped denial from API and export jobs | UJ-15, UJ-23 |
| N-16: refund webhook after delivery | Preserve delivery history and reconcile under contract | UJ-16 |
| N-17: queue worker reruns already applied financial job | Idempotent effect and audit, not second ledger entry | UJ-17 |
| N-18: consent denied and heatmap script initializes | Privacy failure; SDK must not collect prohibited events | UJ-18 |
| N-19: 500-slot page changes geometry after AJAX | Versioned semantic mapping or excluded unknown samples | UJ-19 |
| N-20: attention heatmap implies eye gaze | Terminology/measurement defect; label exposure proxy only | UJ-19 |
| N-21: combine incompatible viewport/layout snapshots | Block direct overlay merge; explain compatibility | UJ-20 |
| N-22: refund/checkout fails after hold expiry | Reconcile safely; do not double-sell or falsely mark paid | UJ-21 |
| N-23: bulk-select spans sold and unsold slots | Show affected groups; block unresolved contract violations | UJ-22 |
| N-24: report URL used after team membership revoked | Reauthorize on download; deny | UJ-23 |
| N-25: unqualified click included in CPC invoice | Billing qualification/reconciliation failure | UJ-24 |
| N-26: optimizer recommends moving paid slot | Block rollout pending contract impact assessment | UJ-25 |
| N-27: optional replay captures password-field value | Critical privacy failure; disable and investigate | UJ-26 |
| N-28: WooCommerce missing during activation | Core features initialize; checkout honest/unavailable | UJ-27, UJ-28 |
| N-29: RTL layout truncates paid total or expiry | Accessibility/business-defect; preserve exact terms | UJ-04, UJ-05 |
| N-30: admin diagnostic download includes secrets | Security failure; redact/protect before export | UJ-17, UJ-27 |

---

## 15. Journey-to-screen and capability traceability

The following matrix links every core journey to its release and the preserved ADP-DOC-002 `UI-*` identifiers. It does **not** make R2/R3/R4 features available in R1.

| Journey | Release | Actor(s) | Primary screens | Related baseline identifiers |
|---|---|---|---|---|
| **UJ-01 — Anonymous advertiser discovers an offer** | R1 | PERS-14, PERS-03 | UI-06 → UI-07 | FR-MKT-001..002, FR-MKT-008; BR-001, BR-013; AC-005 |
| **UJ-02 — Advertiser registers and sets up organization** | R1 | PERS-03, PERS-05 | UI-08 | FR-MKT-003..004, FR-MKT-009, FR-OPS-001..003; AC-009 |
| **UJ-03 — Advertiser drafts campaign and validates creative** | R1 | PERS-03, PERS-04 | UI-09 → UI-11 | FR-MKT-005..007, FR-CAM-003..004, FR-CAM-008, FR-UX-005; AC-008 |
| **UJ-04 — Advertiser obtains quote and exclusive hold** | R1 | PERS-03, PERS-04 | UI-09 → UI-13 → UI-10 | FR-COM-001..004, FR-COM-014; BR-001..002, BR-005, BR-014..015; AC-006 |
| **UJ-05 — WooCommerce checkout, return, and recovery** | R1 | PERS-03, PERS-07 | UI-10 → UI-11 / UI-14 | FR-COM-005..010, FR-CAM-001, FR-MKT-007; BR-003..004, BR-014; AC-005, AC-007 |
| **UJ-06 — Organization owner invites and restricts teammates** | R1 | PERS-05, PERS-04, PERS-06, PERS-07 | UI-08 | FR-MKT-004, FR-OPS-001, FR-OPS-003; AC-009 |
| **UJ-07 — Moderator reviews new creative or changed version** | R1 | PERS-08, PERS-04 | UI-12 → UI-11 | FR-CAM-001..010; BR-003..004, BR-007..008; AC-008 |
| **UJ-08 — AdOps checks booking calendar and resolves overlap** | R1 | PERS-02, PERS-09 | UI-13 → UI-14 | FR-COM-002..003, FR-COM-014, FR-INV-011; BR-001, BR-005, BR-015; AC-006 |
| **UJ-09 — Publisher builds 500-slot responsive page** | R1 | PERS-01, PERS-02 | UI-02 → UI-03 → UI-04 | FR-INV-001..011, FR-INV-013..014, FR-DEL-004..007; AC-001, AC-002, AC-004 |
| **UJ-10 — Publisher edits sold layout without breaking contracts** | R1 | PERS-02, PERS-01 | UI-03 → UI-04 → UI-13 | FR-INV-005, FR-INV-008..009, FR-INV-014; BR-006, BR-018; AC-001 |
| **UJ-11 — Content editor inserts AdPilot Gutenberg blocks** | R1 | PERS-11 | WordPress Gutenberg editor → UI-05 | FR-INT-001..004, FR-INT-007..010; AC-002, AC-003 |
| **UJ-12 — Theme integrator embeds ad slots via public interfaces** | R1 | PERS-12, PERS-13 | UI-05, developer docs, theme templates | FR-INT-005..012, FR-DEL-007..009; AC-003, AC-010 |
| **UJ-13 — Visitor scrolls high-density page with consent controls** | R1 | PERS-15 | Public page + ad runtime | FR-DEL-001..011, FR-MET-001..005, FR-OPS-005; BR-009..010, BR-016; AC-004, AC-011..012 |
| **UJ-14 — AdOps activates, pauses and expires a campaign** | R1 | PERS-02, PERS-08 | UI-11 → UI-15 | FR-CAM-001..002, FR-DEL-001..003, FR-DEL-009..010, FR-OPS-006; BR-007..008; AC-007..008 |
| **UJ-15 — Advertiser or publisher reviews verified metrics** | R1 | PERS-06, PERS-10 | UI-16 → UI-01 | FR-MET-001..006, FR-REP-001..004; BR-011..013; AC-009 |
| **UJ-16 — Finance manager investigates refund or mismatch** | R1 | PERS-09, PERS-07 | UI-14 → UI-11 | FR-COM-006..010; BR-003..005, BR-012, BR-014; AC-007 |
| **UJ-17 — Administrator recovers from delivery/queue incident** | R1 | PERS-13, PERS-16 | UI-15 → UI-20 | FR-DEL-009, FR-OPS-006..007, FR-OPS-010..012; NFR-005, NFR-012; AC-011 |
| **UJ-18 — Site administrator configures privacy and consent exclusions** | R1/R2 | PERS-13, PERS-15 | UI-20; consent controls | FR-OPS-004..005, FR-HEAT-008, BR-011..012, BR-017; AC-012 |
| **UJ-19 — Publisher explores full-page heatmaps** | R2 | PERS-10, PERS-02 | UI-17 | FR-HEAT-001..009, FR-MET-007..008, FR-REP-005; BR-011..013; R2 acceptance additions |
| **UJ-20 — Analytics manager compares layouts across versions** | R2 | PERS-10, PERS-01 | UI-17 | FR-HEAT-004..009, FR-REP-005, BR-006, BR-013; R2 acceptance additions |
| **UJ-21 — Advertiser recovers abandoned or failed checkout** | R1 | PERS-03, PERS-07 | UI-09 → UI-10 → UI-11 | FR-MKT-006, FR-COM-002..008; BR-002, BR-005; AC-007 |
| **UJ-22 — AdOps bulk edits generated inventory and filters conflicts** | R1 | PERS-02 | UI-04 → UI-03 | FR-INV-003..011, BR-006, BR-018; AC-001 |
| **UJ-23 — Authorized user requests aggregate report export** | R2 | PERS-06, PERS-10, PERS-09 | UI-16 → UI-18 | FR-REP-003..005, FR-OPS-001, BR-011..012, NFR-008; R2 acceptance additions |
| **UJ-24 — Reconciled CPM/CPC billing and dispute** | R3 | PERS-09, PERS-04, PERS-07 | UI-19 → UI-14 → UI-16 | FR-COM-011, FR-MET-009..010, FR-REP-006; BR-012..014; R3 acceptance additions |
| **UJ-25 — Publisher runs placement experiment or optimization** | R3 | PERS-10, PERS-01 | UI-01 → reporting/experiment extension | FR-REP-006..007, FR-INV-009, BR-006, BR-013; R3 scope |
| **UJ-26 — Authorized administrator activates optional session replay** | R4 | PERS-13, PERS-10 | UI-20 → UI-17 replay extension | FR-HEAT-010, FR-OPS-005, FR-OPS-011, BR-017; R4 scope |
| **UJ-27 — Administrator installs, upgrades and diagnoses dependencies** | R1/R4 | PERS-13, PERS-16 | UI-20 | FR-OPS-006..013, FR-COM-013, NFR-010, NFR-015; AC-014..015 |
| **UJ-28 — Publisher runs complimentary/manual campaign without WooCommerce** | R1 | PERS-01, PERS-02 | UI-11 → UI-14 → UI-15 | FR-COM-013, FR-OPS-012, FR-CAM-006, BR-003, BR-005; AC-014 |

### 15.1 Functional requirement mapping protocol

The next table is a **coverage index** for every one of the 129 `FR-*` requirements in ADP-DOC-001. It points to at least one user journey and a specific acceptance obligation in ADP-DOC-002. Some low-level operational requirements are not themselves UI features; their linked journeys demonstrate the user-visible consequence, while implementation remains governed by the complete source `FR-*` text. This mapping must not be used to omit technical clauses that have no UI representation.

`AT-{family}-{number}` refers to the pre-existing detailed acceptance test in ADP-DOC-002. A UX scenario is not a substitute for security, financial, API, concurrency or load testing.

| PRD source | Release | User journey evidence | Primary UI/surface | Implementation owner | Detailed baseline acceptance |
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

### 15.2 Business-rule traceability (`BR-001..BR-018`)

These rules are normative **invariants**, not merely UX considerations. Their corresponding user-visible or failure-recovery journeys are mapped below. Detailed enforcement remains specified in ADP-DOC-001 §17 and ADP-DOC-002 §7.

| Rule | Normative summary (read full PRD rule) | Journeys | Required proof |
|---|---|---|---|
| `BR-001` | Inventory sale is permitted only for an active, publishable placement with sufficient eligible capacity.   | UJ-01, UJ-04, UJ-08 | Server invariant + UX recovery + audit where applicable |
| `BR-002` | A temporary hold expires after its configured TTL; extension requires an explicit transaction.   | UJ-04, UJ-05, UJ-21 | Server invariant + UX recovery + audit where applicable |
| `BR-003` | A successful online payment does not bypass creative moderation.   | UJ-05, UJ-07, UJ-28 | Server invariant + UX recovery + audit where applicable |
| `BR-004` | If a paid campaign is rejected, revision/refund/cancellation options follow recorded policy; never silently keep funds without an enforceable fulfillment path.   | UJ-05, UJ-07, UJ-16 | Server invariant + UX recovery + audit where applicable |
| `BR-005` | An exclusive reservation cannot be double sold over overlapping contract time.   | UJ-04, UJ-08, UJ-10 | Server invariant + UX recovery + audit where applicable |
| `BR-006` | Changes to a slot's commercial meaning require impact analysis and resolution of affected contracts.   | UJ-09, UJ-10, UJ-22 | Server invariant + UX recovery + audit where applicable |
| `BR-007` | A rejected or unapproved creative cannot serve; approved prior versions may serve only if contract and policy permit.   | UJ-07, UJ-14 | Server invariant + UX recovery + audit where applicable |
| `BR-008` | Delivery eligibility is recomputed against current authoritative campaign state, not based solely on a stale cached creative.   | UJ-07, UJ-14 | Server invariant + UX recovery + audit where applicable |
| `BR-009` | Ad-preview, administrator-editing and synthetic load-test events are excluded from revenue-bearing analytics.   | UJ-11, UJ-13, UJ-15 | Server invariant + UX recovery + audit where applicable |
| `BR-010` | Advertising density policies and ad-network restrictions are configurable; default warnings should discourage excessive clutter and inadvertent clicks.   | UJ-09, UJ-13 | Server invariant + UX recovery + audit where applicable |
| `BR-011` | Advertiser reports show validated aggregate outcomes, not visitor-level session content.   | UJ-15, UJ-19, UJ-23 | Server invariant + UX recovery + audit where applicable |
| `BR-012` | Raw events have finite retention and are not a permanent financial source of truth; reconciled financial entries are independently retained under applicable policy.   | UJ-15, UJ-18, UJ-24 | Server invariant + UX recovery + audit where applicable |
| `BR-013` | Conversion and performance claims distinguish observed facts, attribution models, estimates and projections.   | UJ-01, UJ-15, UJ-20, UJ-25 | Server invariant + UX recovery + audit where applicable |
| `BR-014` | Currency arithmetic uses precise decimal/minor-unit representations, never floating point for ledger authority.   | UJ-04, UJ-05, UJ-16, UJ-24 | Server invariant + UX recovery + audit where applicable |
| `BR-015` | Timestamps are stored in UTC; contract terms display user-selected time zone and daylight-saving behavior explicitly.   | UJ-04, UJ-08, UJ-14 | Server invariant + UX recovery + audit where applicable |
| `BR-016` | Accessibility and mobile usability may constrain a particular layout even when there is ample logical inventory capacity.   | UJ-09, UJ-13 | Server invariant + UX recovery + audit where applicable |
| `BR-017` | Any third-party tracking, behavioral replay, or remote conversion adapter requires disclosure, consent gating where appropriate, and documented data flows.   | UJ-18, UJ-26 | Server invariant + UX recovery + audit where applicable |
| `BR-018` | Manual administrator overrides of paid placement allocation require reason, authorization and audit logging. | UJ-10, UJ-22 | Server invariant + UX recovery + audit where applicable |

### 15.3 Nonfunctional requirement coverage (`NFR-001..NFR-016`)

Each nonfunctional requirement applies across relevant modules and is tested at its technical boundary as well as through observable user experience. Exact benchmark thresholds and tested environments belong to the performance and quality specifications, not invented here.

| NFR | Quality requirement | Relevant journeys | Acceptance owner |
|---|---|---|---|
| `NFR-001` | **Architecture:** Modular-monolith design with explicit domain/application/infrastructure/presentation boundaries; PHP WordPress host; no mandatory external application server in R1.   | UJ-11, UJ-12, UJ-27 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-002` | **Maintainability:** WordPress Coding Standards, PHP static analysis, TypeScript strict mode, clear module APIs, source-level documentation and reviewed dependencies.   | All journeys via reviewed modules | UX+QA and appropriate architecture/security/performance owner |
| `NFR-003` | **Rendering:** A 500-logical-slot page must not eagerly fetch or paint 500 heavy creatives. Browser work and network concurrency must be bounded; no per-slot PHP query pattern.   | UJ-09, UJ-11, UJ-13 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-004` | **Performance:** Establish and validate environment-specific p75/p95 budgets for request overhead, Core Web Vitals, query count, JS bundle size, browser memory, batching and queue lag; declare benchmark hardware, creative sizes and cache configuration.   | UJ-09, UJ-13, UJ-19 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-005` | **Reliability:** Idempotent payment webhooks and jobs; resilient scheduler; safe fallback if ad delivery or analytics endpoint fails.   | UJ-05, UJ-14, UJ-17 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-006` | **Financial correctness:** Database-enforced uniqueness/capacity controls, concurrency testing, reconciliation, immutable adjustment trail and precise currency arithmetic.   | UJ-04, UJ-05, UJ-16, UJ-24 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-007` | **Security:** Threat model, dependency scans, permission tests, hardened creative handling, secure REST, server-side validation and emergency shutdown.   | UJ-03, UJ-06, UJ-07, UJ-12 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-008` | **Privacy:** Data minimization, regional consent policy, no sensitive input collection, finite behavioral-event retention and protected access.   | UJ-18, UJ-19, UJ-26 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-009` | **Accessibility:** WCAG 2.2 AA target for critical UIs, including nonvisual alternatives to heatmaps and drag/drop interactions.   | All UI-01..UI-20; UJ-03, UJ-09, UJ-19 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-010` | **Compatibility:** Validated WordPress/PHP/MySQL/MariaDB matrix; support classic and block themes, modern browsers, WooCommerce HPOS when commerce adapter enabled.   | UJ-11, UJ-12, UJ-27 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-011` | **Internationalization:** Translation-ready strings, RTL/LTR support, reliable date/time-zone and currency displays.   | UJ-04, UJ-08, UJ-27 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-012` | **Observability:** Logs, queue metrics, failed-event reporting, health diagnostics, financial discrepancy alerts and safe support exports.   | UJ-15, UJ-17, UJ-27 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-013` | **Extensibility:** Versioned REST API, documented hooks, stable PHP and shortcode contracts, adapter interfaces and deprecation policy.   | UJ-11, UJ-12 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-014` | **Licensing:** GPL-compatible distribution, recorded license texts/notices, transitive dependency screening, no unclear copyrighted assets.   | UJ-12, UJ-27 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-015` | **Upgrade safety:** Idempotent migrations, version compatibility checks, reliable backup guidance, data-preserving deactivation and explicit uninstall.   | UJ-17, UJ-27 | UX+QA and appropriate architecture/security/performance owner |
| `NFR-016` | **Usability:** Critical flows verified through task-based usability tests; major actions provide comprehensible states, feedback and undo/recovery when feasible. | All critical journeys; especially UJ-03, UJ-05, UJ-09, UJ-19 | UX+QA and appropriate architecture/security/performance owner |

### 15.4 R1 product acceptance coverage (`AC-001..AC-015`)

These are **product-release gates** imported from ADP-DOC-001; a UXA scenario never replaces the full AC. Every AC must have documented test evidence and traceability in ADP-DOC-002 §9.

| Acceptance | Normative PRD outcome | Journeys | Evidence |
|---|---|---|---|
| `AC-001` | Publisher creates and publishes a 500-slot page consisting of 4 header, 100 sidebar, 196 in-content and 200 footer logical slots. Layout Studio remains usable; publishing and responsive preview succeed.   | UJ-09, UJ-22 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-002` | One `adpilot/ad-zone` block can reference a 200-slot footer container without storing 200 Gutenberg blocks.   | UJ-09, UJ-11 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-003` | Four supported integration paths (Gutenberg, PHP function, shortcode, and supported hook binding) render through one policy-aware engine with correct per-occurrence identifiers.   | UJ-11, UJ-12 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-004` | Public page initially loads bounded creatives near viewport and does not miscount 500 configured slots as impressions.   | UJ-09, UJ-13 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-005` | Advertiser can discover an available slot, obtain a quote, reserve, pay through configured WooCommerce, upload creative, request review and observe activation when approved.   | UJ-01..UJ-05, UJ-07, UJ-14 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-006` | Two concurrent buyers cannot successfully reserve the same exclusive inventory over overlapping time windows.   | UJ-04, UJ-08 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-007` | Duplicate payment callbacks and campaign activation retries do not double-allocate, double-invoice or double-activate.   | UJ-05, UJ-14, UJ-16, UJ-17 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-008` | Creative changes requiring reapproval cannot bypass review and cannot serve in an unapproved state.   | UJ-07, UJ-14 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-009` | Advertiser A cannot access advertiser B's campaigns, invoices, raw behavioral data or privileged reports through UI or direct API calls.   | UJ-06, UJ-15, UJ-23 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-010` | Classic theme, block theme, common caching setup and supported WooCommerce HPOS mode pass the declared compatibility suite.   | UJ-11, UJ-12, UJ-27 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-011` | Broken analytics endpoint does not crash the public site, block reading content or silently produce fictitious billable events.   | UJ-13, UJ-17 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-012` | Privacy-sensitive pages/inputs are excluded from collection; denied consent prevents prohibited collection.   | UJ-13, UJ-18 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-013` | All primary application workflows pass agreed manual accessibility and usability gates.   | All R1 critical journeys, especially UJ-03, UJ-09, UJ-11 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-014` | Installer works without WooCommerce for nonpayment functions and provides an accurate diagnostic for unavailable online checkout.   | UJ-27, UJ-28 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |
| `AC-015` | Release contains reproducible build, automated tests, license notices, migrations and administrator/developer documentation. | UJ-12, UJ-17, UJ-27 | Corresponding ADP-DOC-002 E2E + automated/manual UX proof |

### 15.5 Alignment to existing `E2E-01..E2E-17` tests

| ADP-DOC-002 E2E | Primary user journeys | Release alignment |
|---|---|---|
| `E2E-01` — Self-service purchase, review, delivery | UJ-01..UJ-05, UJ-07, UJ-14 | R1 |
| `E2E-02` — 500 configured slots | UJ-09, UJ-13, UJ-22 | R1 |
| `E2E-03` — Integration path equivalence | UJ-11, UJ-12 | R1 |
| `E2E-04` — Lazy loading and truthful counters | UJ-13, UJ-15 | R1 |
| `E2E-05` — Concurrent exclusive buyers | UJ-04, UJ-08 | R1 |
| `E2E-06` — Duplicate/out-of-order callbacks | UJ-05, UJ-16, UJ-21 | R1 |
| `E2E-07` — Creative revision and emergency pause | UJ-07, UJ-14 | R1 |
| `E2E-08` — Cross-organization access isolation | UJ-06, UJ-15, UJ-23 | R1 (export extension R2) |
| `E2E-09` — Theme/cache/commerce compatibility | UJ-12, UJ-13, UJ-27 | R1 |
| `E2E-10` — Endpoint and analytics outage | UJ-13, UJ-17 | R1 |
| `E2E-11` — Privacy exclusion and denied consent | UJ-13, UJ-18 | R1/R2 |
| `E2E-12` — Accessibility and UX gate | All applicable UJ and UI-01..UI-20 | R1, continuous |
| `E2E-13` — No-WooCommerce operation | UJ-27, UJ-28 | R1 |
| `E2E-14` — Installation/migration/license/docs | UJ-12, UJ-27 | R1 |
| `E2E-15` — Full-page heatmap and layout changes | UJ-19, UJ-20 | R2 |
| `E2E-16` — Metered billing dispute | UJ-24 | R3 |
| `E2E-17` — Replay/multisite/adapters | UJ-26, UJ-27 (network validation additionally defined downstream) | R4 |

---

## 16. UX acceptance test catalog (Given / When / Then)

Each `UXA-*` is a **new** UX-specific acceptance test bound to a `UJ-*`. Existing `AT-*`, `E2E-*`, `AC-*` obligations remain authoritative. The test harness should include keyboard-only, browser zoom, mobile width, denied permissions and slow-network variants where relevant.

### UXA-01 — Anonymous advertiser discovers an offer

- **Given** at least one published and sellable placement or package exists.
- **When** the authorized actor completes **UJ-01** from its stated entry point, including recovery for at least one listed exception,
- **Then** a valid offer reference is selected without creating a reservation or payment.
- **And** search/filter are keyboard accessible; public terms readable at phone widths; estimates visually differentiated.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-06 → UI-07.

### UXA-02 — Advertiser registers and sets up organization

- **Given** supported WordPress login/registration flow exists; invitation or self-signup policy configured.
- **When** the authorized actor completes **UJ-02** from its stated entry point, including recovery for at least one listed exception,
- **Then** authenticated account and authorized organization exist; session is organization-scoped.
- **And** forms preserve nonsecret fields on ordinary validation errors; no inaccessible authentication loops.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-08.

### UXA-03 — Advertiser drafts campaign and validates creative

- **Given** active organization, at least one eligible published placement, creator permissions.
- **When** the authorized actor completes **UJ-03** from its stated entry point, including recovery for at least one listed exception,
- **Then** campaign draft is persisted with validated fields; no unapproved creative may serve.
- **And** wizard step progress and required information conveyed without color; reload restores acknowledged draft.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-09 → UI-11.

### UXA-04 — Advertiser obtains quote and exclusive hold

- **Given** publishable slot/offer; supported fixed/time-based contract; authenticated organization.
- **When** the authorized actor completes **UJ-04** from its stated entry point, including recovery for at least one listed exception,
- **Then** active hold references a valid quote and exact commercial inventory; nothing has been billed by itself.
- **And** clear currency/unit/timezone, held-until status, and alternatives; screen readers receive reservation results.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-09 → UI-13 → UI-10.

### UXA-05 — WooCommerce checkout, return, and recovery

- **Given** wooCommerce installed/configured, owned active hold, supported provider and checkout.
- **When** the authorized actor completes **UJ-05** from its stated entry point, including recovery for at least one listed exception,
- **Then** authoritative payment state is recorded; campaign still requires independent approval and delivery eligibility.
- **And** pending and paid text is precise; no double-submit on slow network; invoice visible only to authorized billing users.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-10 → UI-11 / UI-14.

### UXA-06 — Organization owner invites and restricts teammates

- **Given** organization owner logged in; invitation policy configured.
- **When** the authorized actor completes **UJ-06** from its stated entry point, including recovery for at least one listed exception,
- **Then** only approved members see authorized organization resources; audit reflects changes.
- **And** role descriptions are clear; active organization context never ambiguous; accessible invitations.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-08.

### UXA-07 — Moderator reviews new creative or changed version

- **Given** authorized reviewer, submitted creative version, policy configuration.
- **When** the authorized actor completes **UJ-07** from its stated entry point, including recovery for at least one listed exception,
- **Then** approved version is explicitly recorded; rejected/unapproved version never becomes eligible.
- **And** moderation decisions have keyboard path, explicit version, readable evidence and reason.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-12 → UI-11.

### UXA-08 — AdOps checks booking calendar and resolves overlap

- **Given** authorized operator, published inventory and rate cards.
- **When** the authorized actor completes **UJ-08** from its stated entry point, including recovery for at least one listed exception,
- **Then** no impossible overlapping exclusive contracts; occupancy accurately reflects authoritative reservations.
- **And** keyboard-operable alternative to drag calendar; visually and textually label occupancy.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-13 → UI-14.

### UXA-09 — Publisher builds 500-slot responsive page

- **Given** authorized site editor/ad operations permission and compatible page/template.
- **When** the authorized actor completes **UJ-09** from its stated entry point, including recovery for at least one listed exception,
- **Then** 500 logical slots persist correctly, Layout Studio remains usable and active layout is versioned.
- **And** 500 logical nodes never become 500 editor controls by default; keyboard slot lookup and responsive summary available.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-02 → UI-03 → UI-04.

### UXA-10 — Publisher edits sold layout without breaking contracts

- **Given** published layout exists with at least one active reservation.
- **When** the authorized actor completes **UJ-10** from its stated entry point, including recovery for at least one listed exception,
- **Then** contracts remain valid or have explicit authorized migration; historical layout versions remain inspectable.
- **And** diff clearly exposes *physical position versus contractual identity*; no one-click unsafe override.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-03 → UI-04 → UI-13.

### UXA-11 — Content editor inserts AdPilot Gutenberg blocks

- **Given** adPilot block capability, valid zone, supported Gutenberg context.
- **When** the authorized actor completes **UJ-11** from its stated entry point, including recovery for at least one listed exception,
- **Then** one compact block represents zone; editor preview does not create billable impressions.
- **And** block inspector fully keyboard-usable and focus remains in editor after previews.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** WordPress Gutenberg editor → UI-05.

### UXA-12 — Theme integrator embeds ad slots via public interfaces

- **Given** compatible theme and stable public APIs; no advertiser-provided code execution.
- **When** the authorized actor completes **UJ-12** from its stated entry point, including recovery for at least one listed exception,
- **Then** all supported integration paths invoke one policy-aware rendering service.
- **And** developer-facing diagnostics include reliable, sanitized context and copyable examples.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-05, developer docs, theme templates.

### UXA-13 — Visitor scrolls high-density page with consent controls

- **Given** published 500-slot page with safe delivery policy; visitor may deny optional analytics.
- **When** the authorized actor completes **UJ-13** from its stated entry point, including recovery for at least one listed exception,
- **Then** visitor experiences usable content; distinct configured/selected/rendered counters remain truthful.
- **And** responsive reading order and focus remain meaningful; motion respects preferences; no ad traps.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** Public page + ad runtime.

### UXA-14 — AdOps activates, pauses and expires a campaign

- **Given** valid campaign state, authorizations, scheduler and current inventory.
- **When** the authorized actor completes **UJ-14** from its stated entry point, including recovery for at least one listed exception,
- **Then** only fully eligible creative serves; status history explains decisions.
- **And** admin sees actionable why-not-serving checklist, not just a red generic error.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-11 → UI-15.

### UXA-15 — Advertiser or publisher reviews verified metrics

- **Given** authorized report scope with at least some aggregate data.
- **When** the authorized actor completes **UJ-15** from its stated entry point, including recovery for at least one listed exception,
- **Then** user can state meaning and freshness of each measured number without inferring unmeasured exposure.
- **And** charts have data tables; comparison date/timezone visible; scope displayed.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-16 → UI-01.

### UXA-16 — Finance manager investigates refund or mismatch

- **Given** authorized finance account, order and provider records.
- **When** the authorized actor completes **UJ-16** from its stated entry point, including recovery for at least one listed exception,
- **Then** reconciled order state and an explainable ledger without double adjustment.
- **And** pending versus executed refund clearly separated; irreversible actions require confirm and reason.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-14 → UI-11.

### UXA-17 — Administrator recovers from delivery/queue incident

- **Given** admin/support privilege, health telemetry and redacted logs.
- **When** the authorized actor completes **UJ-17** from its stated entry point, including recovery for at least one listed exception,
- **Then** incident is recoverable or escalated with truthful status and no duplicate financial effects.
- **And** each health alert gives impact, safe action and result; no overwhelming raw stack traces.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-15 → UI-20.

### UXA-18 — Site administrator configures privacy and consent exclusions

- **Given** existing privacy policy and consent management approach reviewed by responsible specialist.
- **When** the authorized actor completes **UJ-18** from its stated entry point, including recovery for at least one listed exception,
- **Then** tracking behavior adheres to configured policy and release constraints.
- **And** purpose/consent explanations readable and actionable; configuration preview does not leak real visitor data.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1/R2. **Referenced screens:** UI-20; consent controls.

### UXA-19 — Publisher explores full-page heatmaps

- **Given** permitted aggregated events, page layout snapshot/version and device classification.
- **When** the authorized actor completes **UJ-19** from its stated entry point, including recovery for at least one listed exception,
- **Then** full-page, footer and slot-level heatmaps align with the correct version and disclose limitations.
- **And** no color-only interpretation; table parity and keyboard zone selection; exposure is not portrayed as attention/gaze.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R2. **Referenced screens:** UI-17.

### UXA-20 — Analytics manager compares layouts across versions

- **Given** two historical layout snapshots and compatible aggregate observations.
- **When** the authorized actor completes **UJ-20** from its stated entry point, including recovery for at least one listed exception,
- **Then** comparison does not silently mix incompatible page geometry or attribute gains causally.
- **And** comparison accessible in table form and describes what changed and why direct comparison may not be valid.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R2. **Referenced screens:** UI-17.

### UXA-21 — Advertiser recovers abandoned or failed checkout

- **Given** persistent draft and authoritative order/hold state exist.
- **When** the authorized actor completes **UJ-21** from its stated entry point, including recovery for at least one listed exception,
- **Then** customer can resume safely without a duplicate campaign, hold or charge.
- **And** never use ambiguous “payment successful” when provider confirmation is missing.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-09 → UI-10 → UI-11.

### UXA-22 — AdOps bulk edits generated inventory and filters conflicts

- **Given** one published/generated sequence, sufficient inventory management capability.
- **When** the authorized actor completes **UJ-22** from its stated entry point, including recovery for at least one listed exception,
- **Then** bulk operation is traceable; unapproved contract-impact changes are not published.
- **And** paginated selection is clear, keyboard accessible, and avoids hundreds of checkboxes in one DOM view.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-04 → UI-03.

### UXA-23 — Authorized user requests aggregate report export

- **Given** access to scoped aggregate report and active export service.
- **When** the authorized actor completes **UJ-23** from its stated entry point, including recovery for at least one listed exception,
- **Then** authorized aggregate report is downloadable and cannot be retrieved by other organizations.
- **And** progress announced accessibly; export list retains accessible state/error history.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R2. **Referenced screens:** UI-16 → UI-18.

### UXA-24 — Reconciled CPM/CPC billing and dispute

- **Given** r3 contract type approved and measurement/billing certification gates satisfied.
- **When** the authorized actor completes **UJ-24** from its stated entry point, including recovery for at least one listed exception,
- **Then** billable quantity matches approved qualification rules and immutable adjustments.
- **And** distinguish raw vs billable visually and textually; transparent formula and event definition.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R3. **Referenced screens:** UI-19 → UI-14 → UI-16.

### UXA-25 — Publisher runs placement experiment or optimization

- **Given** r3 experimentation released, instrumentation reliable, contract/layout impact reviewed.
- **When** the authorized actor completes **UJ-25** from its stated entry point, including recovery for at least one listed exception,
- **Then** experiment record is reproducible; recommendation is evidence-based and independently approved.
- **And** charts disclose uncertainty and counter metrics, with readable nonvisual comparison.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R3. **Referenced screens:** UI-01 → reporting/experiment extension.

### UXA-26 — Authorized administrator activates optional session replay

- **Given** r4 separately deployed/approved replay capability, documented consent policy, separate permission.
- **When** the authorized actor completes **UJ-26** from its stated entry point, including recovery for at least one listed exception,
- **Then** replay is opt-in, separately permissioned and privacy-safe; core analytics remains independent.
- **And** uI never makes recording look mandatory, and does not conflate replay with heatmap measurements.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R4. **Referenced screens:** UI-20 → UI-17 replay extension.

### UXA-27 — Administrator installs, upgrades and diagnoses dependencies

- **Given** authorized WordPress administrator and supported runtime.
- **When** the authorized actor completes **UJ-27** from its stated entry point, including recovery for at least one listed exception,
- **Then** core plugin remains installable/upgradeable with truthful readiness diagnostics.
- **And** setup check list accessible; next action clear, no silent changes to unrelated WordPress settings.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1/R4. **Referenced screens:** UI-20.

### UXA-28 — Publisher runs complimentary/manual campaign without WooCommerce

- **Given** wooCommerce absent/disabled; authorized admin; inventory and creative policies.
- **When** the authorized actor completes **UJ-28** from its stated entry point, including recovery for at least one listed exception,
- **Then** core platform functions correctly without WooCommerce; no fictitious money movement.
- **And** no dead links or misleading payment labels when checkout is disabled.
- **And** no step bypasses server authorization, the independently modeled payment/moderation/delivery states, or the release-specific privacy/measurement boundary.
- **Evidence:** Playwright workflow, API authorization assertion, relevant PHP/domain test, annotated responsive/assistive-technology review, and recorded before/after authoritative state.
- **Release:** R1. **Referenced screens:** UI-11 → UI-14 → UI-15.

### 16.1 Cross-journey usability studies

| Study ID | Task sequence | Participant scope | Mandatory observations |
|---|---|---|---|
| `UXS-01` | Find published placement → buy → view approval/payment state | Novice and agency advertisers | Comprehension of units, quotes, expiration, approval and next action |
| `UXS-02` | Generate 500 logical slots → responsive preview → publish | Publisher and AdOps | Number of unnecessary edits, conflict understanding, accessibility, editor responsiveness |
| `UXS-03` | Insert 200-slot zone via Gutenberg → verify published output | WordPress content editors | Block discoverability, inspector labels, editor preview semantics |
| `UXS-04` | Use PHP/shortcode/hook and troubleshoot absent plugin | WordPress/theme developers | API documentation accuracy, failure diagnostics, parity |
| `UXS-05` | Find underperforming position → inspect long-page heatmap → table | Publisher analytics managers (R2) | Denominator/sample comprehension, page version awareness, no eye-tracking assumption |
| `UXS-06` | Approve revision → suspend campaign → verify no serve | Moderator and AdOps | Version comprehension, ability to find emergency stop, audit trace |
| `UXS-07` | Delayed payment callback → reconcile/communicate | Finance and advertiser billing roles | No false payment success; dispute/recovery discoverability |
| `UXS-08` | Run without WooCommerce → set up manual campaign | Administrators | Accurate dependency communication and viable core workflow |

A study report must provide attempts, task completion criteria, critical errors, user quotes with consent, assistive technology/environment details where applicable, and changes accepted/rejected. No results may be invented by the implementation agent.

---

## 17. Implementation work-package decomposition for GPT-6 High

Break work into independently reviewable vertical slices. A work package is complete only when its UX, backend, security and regression evidence all pass. Suggested order follows P0–P12 but dependencies, not arbitrary calendar dates, control sequencing.

| Work package | Target journeys/screens | Core deliverables | Mandatory specialist review |
|---|---|---|---|
| `WP-UX-01` | Personas/JTBD/IA, all UI-01..20 | Prototypes, state specs, accessible design tokens | Product Owner, UX Lead, Accessibility |
| `WP-UX-02` | UJ-01/02/03/06 | Catalog, advertiser registration and campaign draft states | UX, Backend, Security |
| `WP-UX-03` | UJ-04/05/21 | Availability calendar, quote, hold and checkout UX | Finance, Commerce, QA |
| `WP-UX-04` | UJ-07/14 | Moderation/version review and campaign status UI | AdTech, Security, UX |
| `WP-UX-05` | UJ-09/10/22 | Layout Studio tree, inspector, bulk navigator, publish conflict gate | WordPress, Database, UX, Performance |
| `WP-UX-06` | UJ-11/12 | Four Gutenberg blocks, theme bindings and integration diagnostics | Gutenberg, Frontend, Security |
| `WP-UX-07` | UJ-13 | Accessible progressive public advertising runtime | Delivery, Performance, Accessibility |
| `WP-UX-08` | UJ-15/16 | Admin/advertiser report contracts and financial issue views | Measurement, Finance, QA |
| `WP-UX-09` | UJ-17/18/27/28 | Diagnostics, privacy and no-commerce operational states | Platform, Privacy, DevOps |
| `WP-UX-10` | UJ-19/20/23 (R2) | Full-page heatmap, numeric alternative and aggregate export | Analytics, Privacy, UX |
| `WP-UX-11` | UJ-24/25 (R3) | Metered billing dispute and experiment views | Finance, Data Science, QA |
| `WP-UX-12` | UJ-26 (R4) | Optional consented replay settings/viewer | Privacy, Security, Analytics |

### 17.1 GPT-6 High execution instructions

1. Read ADP-DOC-001, ADP-DOC-002 and this file; summarize the exact work-package scope without replacing baseline IDs.
2. Identify the `UJ`, `UI`, `WF`, `FR`, `BR`, `NFR`, `AT`, `E2E`, `AC` and `UXA` identifiers governing the slice.
3. Produce state and interface contracts before coding. Record unresolved decisions; do not improvise financial, consent, schema or sold-inventory rules.
4. Implement domain/persistence and permission contracts first; connect frontend only after stable API behavior is tested.
5. Build all mandatory UX states and responsive/accessibility variants, not just happy-path components.
6. For risky operations, test concurrent, duplicate, delayed, unauthorized and failure cases before declaring completion.
7. Execute applicable automated tests and real browser/keyboard checks. Preserve exact commands, environment and outcomes; never claim tests ran without evidence.
8. Verify no R2/R3/R4 feature is accidentally described or enabled as R1.
9. Update source references, documentation, changelog and regression coverage; report residual risks and next dependencies.
10. Request role-owner approval for any change that affects design principles, contracts, safety, privacy, measurement or paid inventory.

### 17.2 Work-package completion report template

```markdown
## Work package: [ID / title]
- Implemented scope: [exact requirements and journeys]
- UI states completed: [default/loading/empty/error/permission/stale/etc.]
- Backend/domain changes: [commands, repositories, events]
- Migration/API compatibility: [what changed and why]
- Authorization/privacy review: [evidence]
- Financial/inventory invariants: [evidence or not applicable]
- Accessibility: [methods, issues, outcome]
- Performance: [fixture, measurements, limitations]
- Tests run: [actual commands + pass/fail]
- Deferred items: [release ID + approved decision]
- Unresolved risks/blockers: [details]
- Reviewer approvals: [actual sign-off, not assumed]
```

---

## 18. Open UX/product decisions and change control

These are *not* implicit implementation permissions. Resolve them through Product/UX/Architecture decisions and update source specifications when they alter contract meaning.

| Decision | Question requiring approved answer | Proposed default / controlling rule | Dependency |
|---|---|---|---|
| `UXD-001` | Exact first-release checkout sequence (creative before/after payment)? | UX prototype may vary; payment, moderation and delivery states remain independent | UJ-03..05, BR-003 |
| `UXD-002` | Whether self-service signup is open or owner-approved? | Configurable admin policy with safe defaults and identity checks | UJ-02 |
| `UXD-003` | First-release price/rate unit and quote display default? | Fixed/time-based only; transparent unit and currency | UJ-01, UJ-04 |
| `UXD-004` | Hold duration and abandonment/recovery window? | Configurable, server-authoritative; exact value in commerce rules | UJ-04/05/21 |
| `UXD-005` | Refund policy when moderation rejects a paid creative? | Require explicit documented revision/cancellation/refund choices | UJ-05/07/16 |
| `UXD-006` | Exact target theme/browser/mobile matrix? | Official block/classic themes + chosen popular integrations with test fixtures | UJ-11..13 |
| `UXD-007` | Slot sequencing when a 200-slot footer rearranges responsively? | Preserve logical commercial identity; visualize physical change | UJ-09/10 |
| `UXD-008` | Bulk mutation transaction/undo behavior? | Preview + audited atomicity/partial results; contract conflict blocks unsafe change | UJ-22 |
| `UXD-009` | Default nonessential analytics consent map and retention? | Conservative until privacy/legal specialist signs off | UJ-18/19 |
| `UXD-010` | Heatmap mapping quality threshold and minimum sample? | Define by measurement engineer with explainable low-coverage state | UJ-19/20 |
| `UXD-011` | Session replay adoption and storage provider? | R4 optional; disabled by default with approved masking/consent | UJ-26 |
| `UXD-012` | First-language and RTL QA matrix? | English reference plus translation-ready/RTL infrastructure from R1 | All primary flows |
| `UXD-013` | Baseline usability, responsiveness and performance targets? | Measure on test fixture; approve thresholds before sign-off | All critical UX studies |
| `UXD-014` | Default ad-density warnings and publisher override policy? | Conservative UX defaults subject to ad-network policies | UJ-09/13 |
| `UXD-015` | Product name, public domain and WordPress slug clearance? | AdPilot is working name until verified | Publishing/release |
| `UXD-016` | Multi-organization agency switching and billing visibility? | Permission-scoped organization context, explicit active org | UJ-06/15 |

**Change protocol:** Proposed change → identify impacted `FR/BR/NFR/AC`, `UJ/UI/WF/UXA`, module/API and existing tests → owner reviews → ADR/PRD revision if normative → updated mocks and tests → release note. Never “fix” a UX inconsistency by bypassing a billing, inventory, security or privacy requirement.

---

## 19. Definition of Done and document acceptance

A journey is **implemented** only when:

- The screen/state design is approved by UX and validated against accessibility requirements.
- All named actor permissions and organization/site boundaries are enforced in backend/API tests.
- Success, errors, abandonment, loss of connectivity, stale state and recovery are covered as specified.
- Campaign, creative, booking and payment states remain independent and authoritative.
- Responsive and RTL layout behaviors work within the supported compatibility matrix.
- Event tracking is purpose-limited, consent-aware where applicable, and excludes preview/nonbillable contexts.
- Financial calculations, statuses and user-facing terms are precise and auditable.
- Measured statistics have defined units, denominators, data freshness and limitations.
- Load/performance testing covers representative high-density pages where involved.
- Existing ADP-DOC-002 acceptance tests and new `UXA-*` evidence are recorded as **passed** only after actual execution.
- No unresolved P0 safety/financial/privacy/accessibility issue remains for the release.
- User guides, API integration examples, translations, notices and related docs are updated.

### 19.1 Specific document-completeness audit

1. All 16 personas have job, context, pain, surfaces, success and access boundary.
2. All 24 `JTBD-*` statements link to one or more of 28 distinct `UJ-*` definitions.
3. All 28 `UJ-*` definitions contain preconditions, ordered actions and system responses, negative cases, postconditions, baseline references, accessibility oracle and event candidates.
4. All 20 pre-existing `UI-01..UI-20` screens have first-view hierarchy, primary actions, degraded behavior and accessibility acceptance.
5. All 129 `FR-*` requirements have a traceability row pointing to existing detailed `AT-*` IDs.
6. All 18 `BR-*`, 16 `NFR-*`, 15 `AC-*` and 17 `E2E-*` source identifiers are covered.
7. Every optional advanced journey retains its release boundary (R2, R3, R4).
8. The file remains an implementable UX/user journey contract, not a claim that user research or tests were already executed.

### 19.2 Next document

**ADP-DOC-004 — Business Rules, Domain Policies, State Machines & Permission Scenarios.** It should formalize the conditions, transitions, audit events, invariants and concurrency policies referenced by this document without changing the baseline source requirements. Follow with dedicated data/API, Design System, Layout Studio, Gutenberg/theme, commerce, ad delivery, measurement/heatmap and quality specifications.

---

## 20. Source and handoff index

- **ADP-DOC-001:** `01_ADPILOT_PRODUCT_REQUIREMENTS_DOCUMENT.md` — product vision, scope, 129 `FR-*`, 18 `BR-*`, 16 `NFR-*`, 15 `AC-*`, release gates, decision register.
- **ADP-DOC-002:** `02_ADPILOT_DETAILED_FEATURE_CATALOG_TRACEABILITY_ACCEPTANCE.md` — 18 capabilities, 20 `UI-*` screens, 129 `AT-*` detailed requirement tests, `E2E-01..17`, implementation boundaries.
- **ADP-DOC-003 (this file):** personas, 24 JTBDs, 28 journeys, 18 atomic workflows, 20 screen contracts, negative cases, test and traceability indexes.
- **Architecture baseline:** modular monolith, WordPress host and shared renderer; React/TypeScript for rich applications and lightweight public ad SDK; WooCommerce online checkout adapter; native R2 aggregated heatmaps; opt-in R4 replay.

**Status of validation:** Requirements/identifier audit in this generated specification is automated; no implementation, browser test, security audit or participant usability study is represented as completed merely by being defined here.

