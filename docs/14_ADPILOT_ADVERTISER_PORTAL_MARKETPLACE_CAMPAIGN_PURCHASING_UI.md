# AdPilot — Advertiser Portal, Marketplace & Campaign Purchasing UI Specification

**Document ID:** ADP-DOC-014  
**Filename:** `14_ADPILOT_ADVERTISER_PORTAL_MARKETPLACE_CAMPAIGN_PURCHASING_UI.md`  
**Version:** 1.0.0 (proposed implementation contract)  
**Status:** Proposed — Product Owner, UX Lead, Technical Lead, AdTech, Finance, Security/Privacy, Accessibility, QA sign-off required  
**Date:** 2026-10-12  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Document owner:** Lead Product Designer / UX Architect  
**Responsible implementation roles:** Product Owner, Business Analyst, AdTech Specialist, React/TypeScript Lead, WordPress/PHP Lead, API Lead, WooCommerce Integrator, Visual Designer, Accessibility Lead, Security/Privacy Lead, Analytics Lead, QA/Automation Lead  
**Implementation target:** GPT-6 High using small, testable, evidence-backed work packages  
**Normative dependencies:** ADP-DOC-001 through ADP-DOC-013; this document adds no new privilege, state-machine transition, payable obligation, endpoint, or earlier release promise.  
**Next document:** ADP-DOC-015 — Layout Studio: Visual Advertising Inventory, High-Density Containers & Placement Design Specification (proposed sequence).

> **Implementation notice.** This is a detailed product/technical specification, not a claim that the UI, API, checkout, campaigns, or tests currently exist. API IDs refer to the proposed contracts in ADP-DOC-009; component names are implementation recommendations, not installed packages. Wireframes are structural prescriptions, not approved visual mockups. Real-money checkout and security-sensitive changes must use approved backend state and policy contracts.

---

## 1. Governance, precedence, scope and success conditions

### 1.1 Source of truth

| Source | Governing contract retained |
|---|---|
| ADP-DOC-001 | 129 `FR-*`; `BR-001..018`; `NFR-001..016`; `INV-01..10`; `AC-001..015`; release boundaries R1–R4 |
| ADP-DOC-002 | `CAP-01..18`, 20 canonical `UI-*` screens, matching `AT-*`, 17 `E2E-*` scenarios |
| ADP-DOC-003 | 16 personas, `JTBD-01..24`, `UJ-01..28`, `WF-01..18`, `UXA-01..28`, error/recovery workflows |
| ADP-DOC-004 | Authoritative `DP-*` policies, independent `SM-*` state machines, concurrency and override governance |
| ADP-DOC-005 | WordPress site capabilities, organization membership/ownership, cross-site isolation, role grants and revocation |
| ADP-DOC-006/007 | Bounded contexts, 24 `IC-*` contracts, typed query/command services and domain events |
| ADP-DOC-008 | Financial and reservation persistence, immutable versions, time/money precision and data retention |
| ADP-DOC-009 | **Exact proposed REST route IDs**, DTOs, error contracts, permissions and revision/idempotency rules |
| ADP-DOC-010 | Authoritative async state, outbox, scheduled activation, settlement synchronization and exports |
| ADP-DOC-011 | Navigation, complete asynchronous/error state grammar, responsive UX and 20 canonical screen contracts |
| ADP-DOC-012 | Semantic `--adp-*` tokens, 49-component system, WCAG 2.2 AA goal, RTL and CSS isolation |
| ADP-DOC-013 | Publisher/admin projections of shared screens; DO NOT copy administrator powers into advertiser UI |

If this document conflicts with a higher-authority contract, stop, log the discrepancy, propose an upstream change, and do not silently fix it in the frontend. `MUST`/`SHALL` denote required behavior within the enabled release, `SHOULD` denotes reviewed deviation, `MAY` is optional. R1 is a functioning fixed/time-based marketplace; R2 adds enhancement/exports and heatmaps; R3 adds metered contracts/disputes; R4 adds independently governed network/replay options.

### 1.2 Product outcome and safety targets

The public visitor can compare genuine published placement offers before registration; authorized advertiser staff can register an organization, construct a campaign, upload/review a creative, receive a server-calculated quote, obtain an atomic temporary inventory hold, initiate verified checkout, and track distinct financial, moderation and delivery statuses. They can review aggregate reports and authorized invoices without becoming WordPress administrators. A publisher can configure 500+ logical slots, but this does not imply 500 billable views or infinite purchasable exclusivity. Interface must be visually refined, exceptionally understandable, fast, accessible, localized, and recoverable from interruptions.

**Definition of success:** controlled usability studies demonstrate that a novice advertiser understands what is being sold, the quoted price/unit/currency, when a hold expires, which actions are reversible, and why payment alone does not activate a campaign. Quantitative task time and conversion targets shall be baselined by research; do not fabricate results. Zero known P0 cross-tenant leakage, false paid confirmation, prohibited creative execution, or unconsented sensitive-data collection is acceptable.

### 1.3 In scope and exclusions

**R1 in scope:** public catalog and detail, account/org onboarding, role-scoped membership controls, advertiser dashboard, draft campaign wizard, approved creative versions and safe preview, schedule and availability query, server quote, temporary hold, order creation, WooCommerce checkout when installed/configured, recovery, distinct statuses, scoped campaign metrics, billing and invoices. **R2:** renewal/duplication only where upstream enabled, rich report exports and export-job UI, publisher heatmaps *not* advertiser session access. **R3:** contractual CPM/CPC and dispute surfaces only after billable evidence and approved API/contracts. **R4:** optional replay/network features not part of advertiser self-service access.

Not in scope: full ad exchange, cryptocurrency custody, automatic verification of unreported external conversions, arbitrary PHP/JS editor, advertising-network impersonation, visitor-level/session replay access for advertisers, unsupported payment-provider flows, invented dispute mutation endpoints, and claiming that offer estimates are inventory guarantees.

---

## 2. Users, jobs, authority and success metrics

### 2.1 Relevant source personas

| Persona | Main task | Access and trust boundary | Canonical surfaces |
|---|---|---|---|
| `PERS-14` Anonymous prospective advertiser | Compare offers and published terms without registering | Only public published price/format/availability projections | `UI-06`, `UI-07` |
| `PERS-03` First-time advertiser | Complete a guided campaign purchase and understand approval | Authenticated org-scope on purchase | `UI-08`, `UI-09`, `UI-10`, `UI-11` |
| `PERS-04` Advertiser campaign manager | Manage delegated campaigns and assets | Current organization + current membership; no default finance/member-admin grants | `UI-09`, `UI-11`, `UI-16` |
| `PERS-05` Advertiser organization owner | Organize business details, membership and commercial work | Explicit organization ownership; never another organization | `UI-08`, `UI-11`, `UI-14` |
| `PERS-06` Advertiser analyst | Inspect appropriately filtered campaign metrics | Aggregates only, no raw visitor session data | `UI-16` |
| `PERS-07` Advertiser billing user | Inspect invoices/payments and permitted checkout tasks | Finance-scoped, no implied campaign mutations | `UI-10`, scoped `UI-14` |
| `PERS-08` Publisher creative moderator | Review the submitted exact creative version | Privileged *publisher UI*, not the advertiser portal | linked `UI-12`, never editable by advertiser |
| `PERS-09` Publisher finance manager | Reconcile funds and financial remedies | Privileged *publisher UI*, advertiser sees projections only | linked `UI-14` |
| `PERS-15` Public website visitor | Consume content safely and interact with legitimate ads | Never exposed to purchaser private account details | public renderer, distinct from storefront |

### 2.2 Observable JTBD alignment

`JTBD-01` = compare offers before signup; `JTBD-02` = first-time purchase; `JTBD-03` = recover interrupted checkout; `JTBD-04` = delegate membership; `JTBD-09`/other existing journey-level jobs concerning campaign visibility and reports remain as indexed in ADP-DOC-003. Do **not** reassign the source JTBD IDs; this document references them by identifier. Measure completion, comprehension of state/price, avoidable retries, successful resumptions, report correctness and accessibility, not surveillance of users' form content.

### 2.3 Functional authority vs UI affordances

- WordPress users authenticate through supported WordPress auth; advertiser roles exist as **site- and organization-scoped memberships** in AdPilot, NOT global capabilities granting publisher administration.
- Each authorization check uses current site, authenticated actor, active organization membership, target object ownership, operation, state and higher-level business policy. Read and mutation rights differ.
- UI routes and elements show permitted actions derived from the authenticated server projection; hiding a button is not security. Deep links and direct HTTP calls must reauthorize.
- Organization switches invalidate org-keyed caches and cancel inflight queries. A user in two organizations must not see a union of their records.
- Billing users may not assume campaign-edit permission; campaign managers may not assume billing rights; analyst permissions never imply raw behavioral recording access.
- Revocation must take effect at the next authoritative request, including protected export download.
- Publisher control of verification, creative review, schedule overrides, refunds and placement publishing is not delegated through advertiser interface components.

### 2.4 KPIs and instrumentation rules

| Metric | Exact meaning | Event/source policy |
|---|---|---|
| Public catalog engagement | Filter/apply/detail click counts, when permitted | No raw input values or personal segments in product telemetry |
| Campaign wizard completion | Permitted draft submissions / qualifying wizard starts | Exclude auto-navigation, bots and editor previews |
| Recovery success | Interrupted flows completed without duplicated commercial effects | Transaction IDs in protected backend only |
| Quote comprehension | User study success explaining estimated vs held inventory and monetary units | Task-based moderated research, not a guessed metric |
| Checkout completion | Verified provider-backed final outcome, not return-page visit | Trusted order/settlement projection |
| Time-to-first-submitted-campaign | Median of correctly defined comparable cohort | Segment by prior advertiser experience |
| Reporting trust | Accuracy of source, freshness, denominators and period comprehension | Research plus domain reconciliation |

Analytics data in the advertiser dashboard is *campaign performance*, not telemetry about identifiable shoppers. UX instrumentation must be purpose-limited and have its own lawful/consent basis as applicable.

---

## 3. Navigation, routes, architecture and integration ownership

### 3.1 Public and authenticated navigation tree

```text
Public Ad Marketplace (theme-integrated, no forced login)
  Catalog                       UI-06
    Placement detail            UI-07
      Compare / configure
      Sign in / create account  UI-08 when required

Authenticated Advertiser Portal (organization context shown)
  Campaigns
    Campaign list/detail        UI-11 (advertiser-scoped projection)
    New/Edit campaign           UI-09
    Creatives / review status   UI-09 / UI-11 contextual surfaces
  Performance                   UI-16
  Orders & Billing              UI-14 (scoped, distinct from publisher finance)
    Checkout and payment        UI-10
    Invoice details
  Organization                  UI-08
    Business profile
    Members & Invitations
    Account/role and verification status
  Export jobs                   UI-18 (R2 UI; R1 permitted APIs defined separately)
```

Use first-party, stable public URLs registered through WordPress routing and server-rendered shell fallbacks; exact human-facing slugs and page ownership are controlled by the installation/page-generation policy and localization. This doc does not invent another REST endpoint. Screen IDs are canonical; the location of an organization subview does not create `UI-21`.

### 3.2 Route metadata contract

Each route has `routeKey`, `canonicalUiId`, `featureRelease`, `organizationContextRequired`, `serverEntitlement`, `serverDataProjection`, `canonicalBackTarget`, `breadcrumbLabelKey`, `documentTitleKey`, `focusTarget`, `fallbackRoute`, and `telemetryCategory`. Screen protection is server-side; when an actor loses permission mid-session, invalidate cached projections and send them to a safe permissible route with a clear message. Do not leak private record existence through breadcrumbs or 404 explanations.

### 3.3 Public Marketplace and WordPress theme isolation

Public catalog/design components MUST inherit typography and most global flow dimensions from the active theme where possible, while retaining documented internal design tokens, validation/focus behavior and safe CSS scoping under `adp-`. They MUST NOT reset body styles, theme headings, buttons, or WordPress blocks. Supports both block themes and classic themes, content container widths, varied breakpoints, and RTL document direction. No mandatory remote fonts, CDNs, proprietary images/icons or tracking services.

### 3.4 Portal application shell

- Header: branded portal home, current organization/site scope, actionable notifications, account menu, access to public placement catalog.
- Desktop: left navigation or top route hierarchy, page heading, primary action, filter bar and content region; progressive disclosure rather than a wall of controls.
- Mobile: accessible condensed navigation; headings and primary action remain visible; transactional confirm and cancel are not placed dangerously adjacent.
- Breadcrumbs: meaningful plain-language page > object; no secret tokens, payment references, or invitation proof in URL/history.
- Route transitions: manage document title, announce new main heading to assistive tech, preserve safe filter navigation, restore focus predictably on Back.
- Data freshness: show explicit server update time for campaign and money states; no automatic optimistic “Paid” or “Approved.”
- Role scoping: show actual available routes (after server entitlement) and a non-empty legitimate landing page for analysts/billing-only users.

### 3.5 Organization switch protocol

1. On selector request, identify target organization from `/my-organizations` (ORG-002); never infer it from URL alone.
2. If unsaved draft exists, offer `Save draft`, `Discard local changes`, or `Cancel switch`; save requires server confirmation and current authorization.
3. Abort org-scoped requests, close protected dialogs, clear org-scoped queries, locally held actions, cached totals, invoice document URLs and stale form state.
4. Re-fetch `/organizations/{org_id}` and current membership capabilities; re-resolve selected route against actual entitlements.
5. Rehydrate only the target organization's authorized state; never preserve cross-org selection or old query `enabled` flag.
6. Return a safe view if route is not permitted; show a neutral not-found/access-unavailable result for foreign deep links.
7. No implicit transfer of a quote, hold, order or campaign from org A to org B.

### 3.6 Public vs signed-in persistence

Public browse filters may use URL parameters when safe. Drafts and purchase state belong to authenticated, site/org-scoped server objects; session/local storage may contain only non-sensitive, short-lived navigation hints and must never be the source of truth for quote prices, holds, settlement, or role grants. Sign-in return navigation must be allowlisted and protected from open redirects.

---

## 4. Screen framework and universal UI state contract

### 4.1 Canonical component contract

Each screen must define: purpose, actor, hierarchy, data loader and API IDs, permitted actions, loading/empty/error/stale/forbidden/offline behavior, mobile and RTL changes, focus/keyboard flow, telemetry privacy, and deterministic acceptance test. Reuse visual components from ADP-DOC-012 and async interaction grammar from ADP-DOC-011.

### 4.2 Universal states

| State | Required visible behavior | Forbidden shortcuts |
|---|---|---|
| Initial loading | Render meaningful skeletons with stable dimensions, heading, and busy announcement | Flash zero values as if authoritative |
| Loaded | Show active site/org scope, timestamp where needed, exact business status, and only allowed actions | Omit commercial units or denominator |
| True empty | Illustrate next step with a primary permitted CTA; show no fabricated campaign data | Treat permission-denied as empty |
| Filtered empty | Show applied filters and reset action without losing unrelated draft | Show “no campaigns exist” when only current filters yielded none |
| Unauthorized | Safe access message and a permitted navigation target | Reveal object title or balance from unauthorized response |
| Feature disabled | Explain unavailable release/capability and show an alternative if real | Render active buttons with broken handlers |
| Validation error | Inline accessible message per field plus form summary/focus to first error | Delete user-entered draft unexpectedly |
| Conflict / stale revision | Explain changed authoritative state and offer compare/reload/re-quote | Silently overwrite other actor's changes |
| Offline / timeout | Preserve allowed local edits, classify uncertain mutation and verify server state on reconnect | Blindly retry financial mutation with new IDs |
| Backend degraded | Show last-known safe data with freshness and limited controls | Invent completed payment, approved creative, available inventory |
| Mutation pending | Disable equivalent duplicate actions, provide progress/status, use backend idempotency | Assume button disable is sufficient duplicate protection |
| Mutation confirmed | Show server-issued receipt/reference, correct next step and associated statuses | Promise delivery before verified payment and approval |

### 4.3 Notification hierarchy

Critical information (reservation expiry, payment verification ambiguity, creative rejected with remedy) belongs inline at the decision point and in the activity timeline, not only in fleeting toast notifications. Toasters are supplementary. Badges must have text and icon/shape; color alone cannot identify Paid, Active, Held, or Rejected. User-facing language must distinguish `Estimated available`, `Temporarily held`, `Payment pending verification`, `Creative awaiting review`, and `Eligible to deliver`.

### 4.4 State and stale-read collision matrix

| Situation | UI action | Server authority |
|---|---|---|
| Another user edits same campaign | Present revision conflict and diff/reload; preserve eligible draft | `CAM-004` optimistic revision check |
| Hold expires during checkout | Mark hold expired, stop purchase, fetch current availability and new quote | `RES-002`, `RES-001` and domain reservation rules |
| Rate card changes after saved quote | Display quote version and expiry; re-quote when required | `PRC-005/006` pricing acceptance policy |
| Creative rejected after payment | Show exact review reason and governed revision/remedy options | `CRE-*`, `ORD-*` and `BR-004` |
| Role revoked with open invoice | Recheck authorization and block the protected view/download | `ORG-*`, `ORD-*`, export authorization |
| Browser returns from gateway before webhook | Show verifying/pending and poll/reload trusted order state | `ORD-003`, provider reconciliation |
| Campaign paused while report open | Refresh independent delivery state and timestamp; don't mutate historical metrics | `CAM-*`, `REP-004` |

---

## 5. Public marketplace — `UI-06` comprehensive specification (R1)

### 5.1 Purpose, page hierarchy and discovery behavior

**Actor:** anonymous prospect or advertiser in any organization. **Data:** `CAT-001/003/005/006/007/008`. **Goal:** compare truly published and sellable placements while seeing price units, formats and realistic constraints. **Primary CTA:** `View details`; optional `Start campaign` only on a published, currently eligible offer. Do not create a hold from simply browsing.

Recommended hierarchy: concise publisher advertising offer; search/filter toolbar; active result count and sort; accessible list/grid of offer cards; comparison selection; publisher policies/FAQ and support contact where configured. Each card presents zone/page context, supported dimensions, devices, available contract durations, price + currency + unit + taxes notice, publisher-defined approval disclaimer, and any historical exposure **explicitly identified as an estimate**. Sample creative thumbnails should be labeled demonstrative and never serve live paid inventory or trigger counted impressions.

### 5.2 Filters and sorting

Filters: published placement type/zone, format, desktop/mobile eligibility, page/template context, date window and duration, price range in known currency, categorical targeting eligibility, exclusive/shared inventory, and availability status. Apply with debounced request/cancel, persist non-sensitive filters in URL, show active filter chips, paginate/cursor with reliable scroll/focus, and provide Clear All. Sorting limited to values supported by `CAT-001`; unsupported relevance, guaranteed reach or random popularity claims are prohibited.

### 5.3 Catalog card interaction

Card itself is a standard navigable link; “Compare”, “View details”, and “Start campaign” are separate named controls with focus order. Do not nest buttons in links or make the whole card a click interceptor. Distinguish available-now estimate from temporary inventory hold. Use safe image previews that do not execute advertiser scripts. Card layout responds to the theme container; two-column on sufficient width, single column on narrow view, never rely solely on fixed pixel width.

### 5.4 States and edge cases

- No offers published: show truthful empty state, no generated fake inventory, contact channel only if configured.
- Filters produce zero matches: show filter summary and clear action.
- Availability lookup slow: preserve offer, mark current schedule as unknown instead of green “available.”
- Current offer withdrawn: show no live purchase CTA; route to catalog suggestions without naming unpublished placements.
- Unavailable supported format: show clear format requirements before campaign creation.
- Network request aborted by rapid filter changes: only latest query result may update UI.
- No WooCommerce: public browsing continues; online payment CTA is suppressed/disabled with an explicit statement; authorized manual process only if configured.

### 5.5 Visual and accessibility review

Typography, spacing, focus, loading and card appearance follow Document 12. Labels must remain meaningful without images. Responsive comparison table should switch to stacked labeled attribute comparisons, not inaccessible horizontal overflow with hidden values. Announce result count changes without overwhelming live regions. Respect reduced motion and WCAG 2.2 AA target.

---

## 6. Placement and package details — `UI-07` (R1)

### 6.1 Page contract

**Data:** `CAT-002/004/005/006/007`, with public published fields only. **Hierarchy:** name + placement region; theme-aware desktop/mobile simulated preview; dimensions and responsive constraints; permitted creative formats; page context; sellable contract options; published rate terms and currency; advisory availability; approval/delivery conditions; advertiser next-step CTA.

**Do not conflate:** (1) a logical slot in a potentially 500-slot page; (2) a sellable contract (duration/share/exclusivity); (3) a campaign creative; (4) an actual viewable browser impression. “This footer grid contains 200 advertising positions” does not imply that all 200 are immediately visible or have a guaranteed number of views.

### 6.2 Preview contract

The preview contains the actual eligible page region or a clearly labeled template simulation, with device toggle, aspect-ratio placeholder, highlighted selected slot and contextual neighboring slots. It must not load real targeting decisions or emit counted measurement. Theme-specific URL previews are treated as untrusted/sanitized; no dynamic arbitrary HTML from advertiser. The preview should remain performant even if the container generates 500 or 1,000 slots by rendering limited exemplars and a navigable/paginated slot summary.

### 6.3 Price and term comprehension

Show unit explicitly: `per day`, `per week`, `per month`, `fixed campaign`, `CPM` or `CPC` only when the corresponding release is active; indicate taxes, fees, billing currency, offer validity and estimated audience caveats. Store/publicize minor-unit amounts in server-derived DTOs, not client arithmetic. When user selects dates, request a new advisory availability estimate; only quote+hold establish any temporary entitlement.

### 6.4 Scheduling and slot granularity

Render time zone next to inputs; accept unambiguous local times and make daylight-saving overlaps explicit. Exclusive inventory must indicate if selected slot or sequence range is contractually reserved. If choosing a four-banner header versus 100-slot sidebar versus 200-slot footer, permit clear preview and selection without requiring an individual 200-card layout. Offer grouping must be commercially coherent rather than a client-invented package.

### 6.5 Error handling

Withdrawn placement, changed price, ineligible device, incompatible creative aspect ratio, unsupported interval and depleted capacity each have their own actionable state. The UI cannot fabricate an alternative reservation or offer; it must ask the server for available alternatives if an approved endpoint supports it.

---

## 7. Advertiser onboarding, organization and team — `UI-08` (R1)

### 7.1 Authentication and onboarding

Use supported WordPress authentication, with branded advertiser entry where allowed. Never store passwords in AdPilot-specific tables. Provide sign-in, registration if enabled by site policy, verified login return, optional organization creation, and a deterministic organization-selection view if multiple memberships exist. Respect WordPress registration and anti-abuse policy; do not assume self-registration is enabled. Show only minimal required business fields initially, then progressive optional tax/business details. Mark any administrator verification requirement explicitly and preserve drafts without implying verified status.

### 7.2 Organization details

Required fields are decided by product/locale policy and `ORG-003/005` validation schema. Candidate fields: legal name, display name, billing contact, country, address and optional tax identifier; these are **not** promises of a universal legally sufficient invoice schema. Show field-level privacy labels and server validation, never store bank card details or gateway secrets. Display organization status, permissions and responsible actions.

### 7.3 Members and invitations

Only authorized member administrators may view `ORG-006/008`, send `ORG-007`, change `ORG-011`, revoke `ORG-012`, or initiate controlled transfer `ORG-013`. UI must show `Owner`, `Manager`, `Analyst`, `Billing` as organization roles with scope explanation. An invitation is not active membership until accepted. Show pending invitations with expiry and minimal necessary recipient info. Never allow last-owner removal; ownership transfer uses explicit two-party or otherwise approved authoritative confirmation, not one destructive generic modal.

### 7.4 Contextual team UI

Accessible roster table supports search, status, role, last updated, per-row action menu, confirmation and audit metadata where permitted. A newly invited member sees a clear role summary and the target organization before acceptance. A revoked member's open tabs are reauthorized at next request; stale UI state must disappear. UI role selector must not expose publisher administrator privileges.

### 7.5 High-risk controls and safeguards

- Never accept `organization_id`, `role`, `owner`, or `verified` values from the browser as authority without enforcing current server policy.
- Never allow self-escalation or a direct POST to bypass membership ownership.
- Organization switch invalidates advertiser report, quote, hold, checkout and invoices caches as described in §3.5.
- Invitation links contain a high-entropy time-limited proof; do not log the proof, put it in analytics payloads, or expose it in screenshots/support exports.
- Sensitive business fields only appear to permitted roles and are never included in public placement JSON.
- Support recoverable account-verification and anti-abuse errors without disclosing whether a foreign business exists.

---
## 8. Campaign creation wizard — `UI-09` (R1)

### 8.1 Workflow ownership and design principles

The wizard is the primary conversion experience and must receive dedicated UX research, visual QA, and functional QA. It is a guided **transaction preparation interface**, not an alternative campaign business engine. Source `CAM-001..005`, `CRE-001..005/010`, `CAT-*`, `PRC-005/006`, `RES-001..003`, and `ORD-001` contracts remain authoritative. Separate draft-save, creative approval, quote, temporary hold and commercial order objects are required. A completed wizard means “submitted for configured review/commercial steps,” not automatically “active advertisement.”

Recommended progress labels: `Placement → Campaign details → Creative → Schedule/targeting → Review quote → Hold & checkout`. On narrow screens use current-step title with progress indicator and ordered progress list available to assistive technologies. Each step offers `Back`, `Save draft`, and context-appropriate `Continue`; never offer a no-op CTA. The current checkpoint must be reflected by an authoritative campaign revision, with autosave indicators `Saving…`, `Saved [time]`, or `Unable to save` based on actual response.

### 8.2 Step-by-step field and validation catalog

| Step | Required presentation and fields | Server validation and data source | Recovery and next action |
|---|---|---|---|
| W1: Select placement | Selected published offer, format, unit, device and sellable inventory context; clear change option | `CAT-001/002/005`, current offer version; do not read a stale rate card as final | Offer withdrawn → return to catalog with saved draft; no loss of other entered data |
| W2: Campaign information | Internal campaign name, advertiser/brand, objective as informational field where applicable; required policy notices | `CAM-002/004`, org membership, field length and name policy | Inline errors; conflict with another editor → compare/reload |
| W3: Creative material | Safe upload/selection, permitted type/dimensions/file limit, destination URL, alt text/disclosure; version preview | `CRE-001..005/010`; safe media ownership, URL allowlist, immutable creative digest | Rejected file → retain other data; show actionable format/dimension hints |
| W4: Dates and targeting | Date/time, time zone, duration, chosen inventory quantity, supported context/device target, placement density caveat | `CAT-005`, domain availability, UTC/DST resolution and targeting capabilities | Ambiguous local time → explicit offset choice; unavailable inventory → reselect/requote |
| W5: Commercial quote | Server quote ID, immutable contract version, subtotal, tax/fees if supported, total and currency, offer expiry, approval policy | `PRC-005/006`; server figures only; calculated minor units displayed via locale formatter | Changed source → invalidate quote and request updated version; no client-side total editing |
| W6: Temporary hold | Exact placement, time interval, exclusivity/share, absolute expiry, order prerequisites, final confirmation | `RES-001/002/003`; atomic server-side reservation with TTL | Hold conflict/expiry → explain and offer new quote/hold; no stale checkout |
| W7: Checkout handoff | Order summary, hold state, verified payment options, provider terms and return route | `ORD-001/003/004`, WooCommerce adapter, trusted reconciliation | Redirect return not final confirmation; show verifying, pending, failed or successful based on server |
| W8: Completion / next steps | Order reference, independent payment/creative approval/delivery states, future timeline, campaign link | Server projections from `CAM-003`, `ORD-003`, `CRE-004`, current contract | Rejected review → revision/remedy path; no promise of instant activation |

Step W8 is a **post-checkout experience**, not an additional prepayment step. Some organizations may require business verification before W6 or W7; the server policy determines timing. The UI must show precisely why verification is required, not invent a universal legal requirement.

### 8.3 Draft persistence and resumption

- Create a draft using `CAM-002`, then perform versioned updates through `CAM-004`. Enforce `If-Match`/expected revision according to ADP-DOC-009; no silent lost updates.
- Autosave waits for meaningful pauses and batches eligible draft fields; never autosave card details, raw upload bytes or security secrets. Save status follows backend acknowledgment.
- UI can keep an in-memory working copy while the server is unreachable. It must not claim the draft was saved when only the browser cache changed.
- Refresh/resume restores only authorized current-org campaigns; server-computed sections (prices, availability, holds, approval) are refreshed independently.
- Changing placement, dates, creative, rate package, or commercial terms invalidates the affected quote/hold under domain rules; UI highlights exactly which step needs recomputation.
- Navigating Back during payment or after hold acquisition must not silently create duplicate holds. On explicit cancellation offer release (`RES-003`) when policy permits; release success must be verified.
- If server draft changes during editing by another organization member, use a 409/412 conflict state and offer safe reload or reviewed merge of editable noncommercial fields. Never merge quote or payment values.

### 8.4 Creative workflow detailed behavior

The wizard should provide an explicit media dropzone **with an ordinary file-picker alternative**, client-side dimension/type/size hints, upload progress, cancel, safe preview and accessible error summary. Client checks are usability aids; authoritative media validation and sanitization are server-side. Define allowed file types from publisher policy and server schema. Script execution, unchecked SVG, executable HTML and third-party custom JavaScript are not default formats.

A creative version contains a reference to safely stored media, destination URL, alternate text/caption where applicable, allowed disclosure and immutable revision information. The advertiser sees moderation state (`draft`, `pending`, `approved`, `rejected`, `changes requested`) and exact version identifier. Editing an approved creative creates a new immutable version; it must not mutate the approved digest nor imply the new version may serve. An approved previous version may remain active only if explicit policy allows it. The preview is always nonbillable and cannot produce live paid impressions.

### 8.5 Targeting and delivery clarity

Explain which targeting is *actually supported for the selected placement and release*: page/context, approved device grouping, locale and schedule. Do not offer unimplemented geolocation precision, personal behavioral targeting or retargeting. The wizard should explain that a high-density zone contains logical inventory; not all slots are fetched or viewable for one visitor. If advertiser buys a share, show the commercial unit and qualification policy; do not call weighted share “guaranteed impressions.”

### 8.6 Validation and user education

Validation errors must identify the field, rule, example correction, and preservation of unaffected entered data. Do not ask for date time without showing time zone; do not silently resolve daylight-saving overlaps. Quote breakdown must show exact units and currency; a `10,000` count cannot appear without “estimated impressions” or “configured slots” qualifier. Progress bar completion percentage must not claim campaign activation.

### 8.7 CTA policy

| Context | Primary CTA | Secondary action | Server effect |
|---|---|---|---|
| New campaign draft | Save & continue | Save draft, Cancel | Versioned campaign draft |
| Creative accepted for submission | Continue to schedule | Review preview | Versioned media/creative metadata |
| Quote review | Review inventory hold | Change selections | No entitlement yet |
| Hold acquired | Continue to checkout | Release hold | One authoritative active hold |
| Payment pending | Check payment status | Return to campaigns | Trusted order refresh only |
| Payment confirmed, review pending | View campaign status | Submit permitted review changes | No premature ad serving |
| Rejected creative | Revise creative | Review refund/revision terms | Governed version/review/remedy |

---

## 9. Availability, scheduling, quotation and reservation experience

### 9.1 Calendar semantics

The advertiser-facing availability widget in `UI-07/UI-09` is a scoped consumer of the same reservation authority used by publisher `UI-13`. Show month/week/list options only to the extent supported by `CAT-005` and the quote contract. Every cell/interval distinguishes `estimate available`, `limited`, `unavailable`, `selected`, and `temporarily held` with **text, not color alone**. The calendar must expose list-form keyboard navigation; no mouse-only drag required to select a time interval. Server UTC half-open periods `[start,end)` are the source of truth; display a clearly chosen local time zone including DST ambiguity handling.

### 9.2 Advisory availability vs contractual capacity

`CAT-005` is a nonbinding estimate and may change before the purchase. `PRC-005` creates a server quote against current terms; `RES-001` is the capacity-locking hold command. Offer cards cannot mark inventory “reserved” just because a visitor highlighted calendar cells. If two advertisers request the same exclusive slot, exactly one hold may succeed. The losing actor receives a polite, safe conflict state and alternative dates based on currently available public inventory. Do not imply a waitlist exists unless separately specified.

### 9.3 Hold countdown

Show server-returned hold expiry in local time and an accessible remaining-time indicator. Countdown uses synchronized server date or a safe skew estimate for display only; expiration authority is server time. Refresh state on tab reactivation, route return or uncertain failure. A hold showing `00:00` is no longer eligible for presumed payment progression; verify current hold status. Display accessibility-friendly time announcements at meaningful milestones, not every second. Hold extension is a **new explicit transaction** if permitted by the domain; do not restart a local timer to simulate extension.

### 9.4 Quote contract display

Recommended breakdown card: `placement/slot or package`, `scheduled window`, `contract basis`, `quantity`, `unit price`, `subtotal`, `discount` where supported, `tax/fee` where appropriate, `total + currency`, `offer expiry`, `terms version`, and `what happens after payment`. Pricing is a trusted backend projection in integer minor-unit storage; client formatting uses locale-aware presentation and never decides the charge amount. A quote that is stale or no longer matches capacity must be clearly invalidated.

### 9.5 Reservation changes

Changing dates, inventory quantity, creative eligibility or package after hold acquisition must trigger an impact warning and new authoritative quote/hold before purchase. The UI may release the old hold when the server confirms transition, but must not assume releasing the old hold guarantees the new hold succeeds. Show exact status if both cannot be managed atomically, and provide a safe manual-recovery option with current state.

### 9.6 Performance for dense inventory

A `footer: 200 slots` and `sidebar: 100 slots` offer requires an efficient **commercial inventory selector** with a compact summary, paginated slot navigator, occupancy indicators and optional filter by compatible size/date. It must not mount 200 interactive preview cards or request a separate availability endpoint for every slot. Results and images load in batches; offscreen previews are deferred. Preview of a 500-slot page must not trigger live delivery/measurement. Stable slot IDs are present in advanced detail views, while ordinary purchasers see human-readable placement labels and positions.

---

## 10. Checkout, verified payment and recovery — `UI-10` (R1)

### 10.1 Commercial truth model

The checkout experience is a presentation of **three independent authorities**: inventory hold/reservation, payment/order, and creative review/campaign eligibility. WooCommerce may handle checkout when installed and configured; AdPilot remains authoritative for advertising contracts and delivery gates. An order is not `paid` because a browser returned from a gateway, because a link contains `success=1`, or because a script observed checkout completion. The UI must query the trustworthy order projection via `ORD-003` and display server-verified settlement progress.

### 10.2 Checkout progression

1. After `RES-001` returns a current eligible hold, display scope, expiry and quotation.
2. User confirms commercial terms and initiates idempotent `ORD-001` from the current hold.
3. Backend binds the commercial order to the site/org and current capacity, with idempotent client operation key; UI displays returned order reference.
4. Authorized billing actor initiates `ORD-004` checkout via supported WooCommerce adapter. If user lacks billing right, offer a scoped “Request authorized billing user” workflow only if approved; never borrow another member's privilege.
5. Display gateway handoff screen with accessible status, clear provider transition, supported cancellation and recovery path.
6. On return or webhook lag, fetch `ORD-003` and associated hold/order states; display `Verifying payment` if unsettled, with appropriate retry/refresh. Do not optimistically settle.
7. If paid but creative approval pending, show `Payment confirmed — creative awaiting review` and a correct campaign link.
8. On rejection after payment, present precise version reason and the policy-governed revision/refund/cancellation remedy. Do not retain money silently without fulfillment remedy.
9. On failed/expired checkout, describe authoritative current order, hold and options to re-quote. Do not reuse an invalid expired reservation.

### 10.3 Checkout screen content hierarchy

- Heading: `Complete your advertising purchase` with organization context.
- Step/status strip: `Quote → Hold → Order → Payment verification → Review → Scheduled/Active` (visually indicate independent chains instead of one fake linear completion).
- Left/main: cart-like contract summary with dates, targeted placement, zone, price, accepted terms and human-readable approval rules.
- Right/secondary: payment handoff controls only when permitted; itemized totals and expiry persistent near CTA.
- Failure/recovery: inline panel with distinct statuses and a safe next action; avoid placing a success toast over an unconfirmed gateway result.
- Confirmation: server order reference, verified payment state, moderation state and estimated next administrative step. Never guarantee activation time unless established by schedule/approval policy.

### 10.4 Payment status vocabulary

| State in UI | Trigger/source | Allowed statement |
|---|---|---|
| Awaiting payment | Current payable order with valid checkout state | `Payment not yet verified` |
| Payment verification in progress | Unsettled provider callback or reconciliation | `We’re checking your payment status` |
| Payment confirmed | Trusted domain/order mapping settles | `Payment confirmed` without `Ad active` implication |
| Payment failed | Verified failed payment attempt and no settled state | `Payment unsuccessful — choose permitted retry` |
| Payment status unavailable | Network/outage prevents trusted status query | `Status temporarily unavailable — no additional charge initiated` |
| Reservation expired | Authoritative hold state expired or no valid replacement | `This placement is no longer held` |
| Creative under review | Submitted exact creative version awaiting moderator | `Awaiting creative review` |
| Eligible/scheduled | All delivery gates pass on current authoritative state | `Scheduled to begin [date/time]` |

### 10.5 Retry and duplicate protection

For every financial mutation use one stable operation idempotency key across a retry of the same intent. UI buttons disable duplicate submission while pending, but backend handles duplicate and out-of-order requests. A network timeout is **unknown outcome**, not a failure. First query current order/hold state; only retry same idempotent request when allowed. Browser Back, repeated gateway return and multiple tabs must not create multiple bookings or captured payments. Never store gateway secrets, raw card fields, unredacted payloads, or checkout tokens in analytics/local storage.

### 10.6 No-WooCommerce behavior

WordPress bootstrap, public catalog, draft campaigns, creative review and advertising operations continue with WooCommerce absent. Online checkout is **disabled** with an explicit dependency message; do not fabricate a fallback gateway, confirmation or invoice settlement. Document 03 `UJ-28` permits a policy-governed manual campaign process where configured and authorized, but the portal must not promise an unsupported direct-payment workflow. Features that rely on WooCommerce must gracefully explain prerequisites.

### 10.7 Refund/remedy UI

If a paid creative is rejected or delivery cannot fulfill contract, present a trustworthy remedy status and options according to `BR-004`, `ORD-009..011` and published refund policies. Advertiser may request assistance through an approved channel, but only publisher finance can approve/execute elevated remedy actions. Do not create a direct advertiser refund endpoint unless approved in ADP-DOC-009 and security reviewed. The interface must show applicable deadlines, amount/currency, execution status and supporting notes where authorized.

---

## 11. Advertiser campaign dashboard and detail — advertiser projection of `UI-11` (R1)

### 11.1 Landing dashboard

The organization dashboard prioritizes tasks: submitted campaigns needing edits, expiring holds, payment verification, rejected creatives requiring revision, approved scheduled campaigns, active campaigns and clearly attributed delivery metrics. Present aggregate KPI cards only when authorized and backed by server data. Where the actor has billing-only or analyst-only rights, land on permitted content rather than an inaccessible generic dashboard. No visitor-level data or publisher inventory secrets.

### 11.2 Campaign list

Columns: campaign name, placement/package summary, start/end schedule and time zone, **approval status**, **financial status**, **delivery status**, aggregate metric preview if available, last updated, allowed actions. These three statuses must be separate labeled columns or visibly separate tokens; never flatten into one `Active` badge. Controls: server-filtered search, status and date filters, sorting and cursor pagination. Tabular compact mode retains row accessible labels and obvious row link; mobile uses stacked cards with meaningful headings.

### 11.3 Campaign detail

Recommended tabs: `Overview`, `Creative versions`, `Schedule & placement`, `Performance`, `Activity`, and `Billing` only if permitted. Overview always shows legal contract snapshot, timeline, next action, last status update and expected review/delivery gates. Advertiser can request pause/resume under `CAM-006/007` only if permitted; server validates state and immediate delivery effect. No direct action to approve a creative, force activation, override slot capacity, or settle a payment.

### 11.4 Campaign versioning and moderation

Exact creative version reviewed must be shown in detail: uploaded version, preview, status, last review timestamp, any reason/requested correction, destination URL (sanitized) and instructions to create a new version. Submission for moderation uses `CRE-005`, and campaign submission uses `CAM-005`; these are separate backend operations. A new version requires reapproval and may change schedule/commercial readiness. Show previously approved version only if contract allows continued serving; do not assume.

### 11.5 Activity timeline

Timeline should display *authorized* events: draft saved, quote created, hold expired, order initiated, payment verified, creative submitted/reviewed, campaign scheduled/paused/resumed, delivery stopped and invoice available. Timestamps convert from UTC to selected user locale/zone with explicit label. Show who performed a user-visible action where authorized, but redact private support or financial details from nonfinance roles. Background queue events should be summarized as readable system activity, not leak internal stack traces.

### 11.6 Campaign operations matrix

| Actor | Draft | Submitted | Approved/scheduled | Active | Rejected/paused |
|---|---|---|---|---|---|
| Owner/manager with `org_campaign_write` | Edit permitted fields, submit | View; request changes where permitted | View; request policy-allowed pause | Request pause; no direct override | Revise creative/new version; request resume if eligible |
| Analyst with `org_campaign_read` | View only | View only | View only | View and aggregate performance | View reason where permitted |
| Billing-only member | Only permitted order/billing projection | Billing projection | Billing projection | Billing projection | Billing/remedy status if entitled |
| Publisher administrator | Separate privileged admin UI | Separate privileged admin UI | Separate privileged admin UI | Separate privileged admin UI | Separate privileged admin UI |

Policy always overrides illustrative matrix; the backend decides exact allowed action and fields.

---

## 12. Billing profile, orders and invoices — advertiser projection of `UI-14` (R1)

### 12.1 Organization-scoped finance navigation

Use `Orders`, `Invoices`, `Payment status`, `Billing profile`, and `Support/remedy status` as contextual routes under organization when actor has billing permissions. Data from `ORD-002/003/005/006` must be org-scoped server projections. Do not reuse publisher ledger API `ORD-008` or privileged refund actions in advertiser components. Invoice downloads must be protected server resources with current authorization, not public uploads URLs.

### 12.2 Orders table and detail

Orders list shows contract summary, order reference, created date, amount/currency, authoritative status, current payment state, related campaign(s) and permitted actions. Order detail shows immutable purchased inventory scope, quote/version reference, refund/adjustment history where authorized, invoice links, and a defensible distinction between paid amount, pending amount and refund outcome. Financial figures are formatted from server minor units/decimal strings; float calculation is presentation-only at most and must not change authoritative totals.

### 12.3 Invoices

Display invoice metadata only when authorized. PDF/invoice generation is an external/submodule contract, not a guarantee of jurisdiction-specific tax sufficiency. Tax labels and registration fields must follow approved locales and commerce configuration; show missing invoice as “Not available” rather than generate a fictional number. On revocation during download, reauthorize and safely deny without exposing a cached PDF URL.

### 12.4 Notifications

Provide actionable notices for payment awaiting verification, invoice available, refund/remedy progressed, hold expiring and renewal where release permits. Email templates must not expose private payment tokens, full invoices, or organization names to wrong recipients. In-app notification state must not be inferred from email delivery success.

---

## 13. Advertiser performance and reports — `UI-16` (R1)

### 13.1 Role and privacy boundary

Advertisers only see aggregated, validated results scoped to their authorized campaigns/organization through `REP-004`. Raw sessions, playback, precise individual visitor actions, site-wide unrelated revenue, publisher financial ledger, third-party advertiser results and administrator heatmap recordings are prohibited. Publisher `UI-17` heatmap access is not inherited by any advertiser persona.

### 13.2 Performance overview

Support date presets and custom range, campaign/creative filters, device grouping where supported, and comparison periods without violating privacy thresholds. Metrics should include actual delivered/rendered/measurable/viewable impressions if source supports them, qualified clicks, qualified CTR with named denominator, viewability rate with definition and denominator, authorized campaign expenditure if billing entitlement allows, and R3 conversion/billable details only when formally supported. A click count must not automatically imply “engaged visits.”

### 13.3 Metric precision and provenance

Each KPI displays the metric name, unit/denominator, date bucket, data freshness, source qualifier (`measured`, `estimated`, `adjusted`, `not available`) and applicable exclusions. A missing metric is never `0` unless the source explicitly attests zero measured events. Server aggregation lag and delayed correction must be visible. Mark reporting data as potentially updated after qualification/reconciliation; payment amounts derive from commerce ledger, not raw browser counters.

### 13.4 Charts and accessible equivalents

Time series charts must have axis labels, units, accessible legends, hover+keyboard tooltips where feasible, and an equivalent tabular representation. Granularity choices (day/week/month) require server-supported grouping, timezone information and consistent UTC source handling. Empty chart yields actual empty state rather than plotted dummy points. Use Chart.js or compatible approved dependency only when licensing and bundle budget review pass, and defer heavy chart code until `UI-16` is visited.

### 13.5 Report exports and release distinction

`REP-006..008` API contracts exist as R1 scoped export operations according to ADP-DOC-009, but the **dedicated `UI-18` export job management screen is an R2 enhancement** according to ADP-DOC-002/011. In R1, expose only the approved basic report export affordance and states; do not claim the entire R2 UI exists. For any export, reauthorize the request and the download separately, show queue progress/failure/expiry with current server state, and avoid placing sensitive files in a public uploads path.

### 13.6 Reporting failure states

Show `Data is delayed`, `Not measurable`, `No qualifying activity in period`, `Insufficient sample` or `You do not have access` as distinct states. Explain data source lag, qualification thresholds and nonbillable exclusions. No invasive “live user heatmap” should appear as advertiser reporting.

---

## 14. Adjacent surfaces and later-release feature gating

| Surface | Release and allowed projection | UX behavior |
|---|---|---|
| `UI-18` Report export jobs | R2 dedicated management UI; R1 export API if enabled | Request, observe async progress, reauthorize download, purge expired links |
| `CAM-014` Duplicate campaign | R2 | Creates new draft; never duplicates approval/payment/reservation or live delivery |
| Renewal and bundle options | R2 if individually enabled | Clearly distinguish a new contract from extension of old contract; never reuse expired quote |
| R2 heatmaps (`UI-17`) | Publisher and authorized analyst purpose only | No advertiser visitor session playback or visitor-level heatmap entitlement |
| R3 CPM/CPC, dispute (`UI-19`) | Requires approved billing/qualification protocols | No UI mutations before corresponding explicit REST domain contract is approved |
| R4 replay and network advertising | Independent opt-in/site policy | No implied advertiser access to replay; no cross-site private data union |
| No-WooCommerce mode | R1 plugin bootstraps, online payment is unavailable | Explicit dependency message, safe alternate workflow only where domain supports |

A hidden feature must not be hinted as purchasable functionality through misleading prices, “Coming soon” billing buttons that appear actionable, or unimplemented checkout paths.

---
## 15. Visual composition, components and interaction contracts

### 15.1 Design system application

Use ADP-DOC-012 as the sole design-token source (`--adp-*`). A cohesive premium SaaS aesthetic is expected: deliberate information hierarchy, restrained branded accents, thoughtful whitespace, clear calls to action, excellent typography, and readable, trustworthy commercial data. The advertiser portal may be visually richer than wp-admin, while all transactional controls retain WordPress-compatible accessibility and state behavior. Support light/dark user/system modes only where the design-system package and contrast tests support them. Never rely on a paid font, remotely sourced icon, proprietary UI kit, or third-party design asset without license approval.

### 15.2 Component inventory (advertiser-specific compositions, no replacement for Document 12)

| Component / composition | Required props and responsibility | State and accessibility contract |
|---|---|---|
| `AdvertiserShell` | Site brand, authenticated actor, current org, nav permissions | Handles role-gated routes and focus restoration |
| `OrganizationSwitcher` | Authorized organizations + current selection + unsaved state | Keyboard combobox; cancels queries and clears private caches |
| `OfferCatalogFilterBar` | Server-supported filters, availability period, sorting, reset | Accessible search/filters with result-count announcements |
| `PlacementOfferCard` | Public title, format, zone, unit price, size, device, preview | Real links, text alternatives, no false inventory claim |
| `PlacementComparison` | Up to policy-approved selected offers and attributes | Responsive stacked fields and semantic table fallback |
| `PlacementDetailHero` | Title, description, preview and commercial conditions | Correct landmark/heading hierarchy |
| `CreativePlacementPreview` | Template simulation, viewport, stable selected zone/slot | Nonbillable; no untrusted creative scripts; not deceptive |
| `AvailabilityCalendar` | Date range, time zone, advisory intervals | Keyboard date selection; list alternative; DST clarity |
| `AdvertiserOnboardingForm` | Dynamic server fields, validation, privacy notes | Form summary/focus-to-error, autosave only as authorized |
| `OrgMemberRoster` | Permitted members/roles and invitable statuses | Row actions with explicit grant checks |
| `InviteMemberDialog` | Target email and allowable role | Sensitive token never shown after sending; safe error feedback |
| `OwnershipTransferDialog` | Affected org/member, acknowledgments, reason | Additional confirmation and server policy check |
| `CampaignWizard` | Current step, valid step transitions, persisted draft | Full keyboard flow; state available to screen readers |
| `WizardStepper` | Step names/status/current index | Text status, no “completed” when only visited |
| `DraftSaveIndicator` | Last server ack, local dirty flag, error state | Announce save failure; never false saved success |
| `CreativeUploader` | Server file policy, accepted types, upload state | Drop and browse; cancellation; errors preserved |
| `CreativeVersionPreview` | Safe creative URL, digest/status and device format | Sandbox/sanitized; preview never billable |
| `ScheduleTargetingPanel` | Supported rules, selected UTC interpretation | Clear local time/offset, optional controls disabled if unsupported |
| `QuoteSummary` | Trusted immutable quote fields and validity | Monetary display exact; expiry and breakdown visible |
| `HoldStatusPanel` | Current hold, authoritative expiry, contract scope | Accessible status intervals; revalidate on reactivation |
| `CheckoutHandoffPanel` | Trusted order, provider handoff link, state | No simulated provider or fake paid state |
| `PaymentVerificationPanel` | Current trusted order verification and recovery | Status/check refresh; no unbounded auto retries |
| `ThreeAxisCampaignStatus` | Payment, approval, delivery independent projections | Text and shape; explicit next-step explanation |
| `CampaignDataGrid` | Cursor rows, scoped state, actions, filters | Accessible table/grid, responsive cards |
| `CampaignTimeline` | Scoped authoritative audit projection | Visible and SR-readable event sequence |
| `InvoiceList` | Authorized invoices, dates, currency, protected links | Recheck before download; empty/forbidden distinct |
| `AdvertiserMetricCard` | Qualifier, metric definition, value, freshness | `unknown` never rendered as zero |
| `AdvertiserTimeSeries` | Qualified time buckets and series label | Chart with data table and keyboard/legend support |
| `ExportProgressItem` | Owned export lifecycle and expiry | Progress announcements and protected access |
| `InlineDomainNotice` | Recoverable domain state + safe action | Appropriate live-region priority; never dismiss critical blocker only |
| `ConflictResolutionPanel` | Server/current revision and safe compare/cancel | Supports keyboard, no blind overwrite |
| `OrganizationPermissionBoundary` | Server-entitled action and reason | Decorative gating only; server always rechecks |
| `NetworkStateBanner` | Offline/degraded/reconnection | No financial mutation replay without reconciliation |

Components must not directly import `$wpdb`, WordPress PHP controllers or shared admin privileged endpoints. They consume typed API projections, accepted state-machine capabilities and sanitized server models. All component stories cover enabled/disabled, loading, empty, invalid, stale, unauthorized, small viewport, RTL and keyboard behavior.

### 15.3 Wireframe-level layout contracts

**Marketplace desktop:** theme header → concise hero value proposition → sticky-but-nonobstructive filter area if permitted → results summary/sort → responsive offer grid → help/policy content; card CTA always visible and nearby exact unit price. **Marketplace mobile:** filter trigger opens accessible drawer, selected filter chips visible, single-column offer list, no horizontal clipped data.

**Campaign wizard desktop:** title + org/placement context → persistent stepper → 2-column main editor and bounded trusted-order summary side rail for quote steps → footer navigation with save status and primary CTA. **Wizard mobile:** linear sections, summary collapses accessibly and is explicitly reviewable before purchase; CTA not obscured by mobile navigation/virtual keyboard.

**Checkout desktop:** primary contract/terms summary and distinct status timeline on left; verified payment actions and full itemized pricing on right. **Mobile:** terms before action, repeat total near CTA without duplicating purchase submissions, readable error banner near payment control.

**Advertiser reports desktop:** context/breadcrumb + filters + KPI set + chart/table + glossary; **mobile:** filters use drawer, KPI rows stack, tabular alternative supports horizontal scroll with sticky meaningful row labels only when tested.

### 15.4 Form interaction standard

- Label fields directly; mark required vs optional; provide examples without using placeholder as label.
- Use complete accessible descriptions for currency and date/time units, accepted file dimensions, approval policy and privacy notices.
- Validation ordering: input constraints → server schema → policy/ownership → current state/version → database transaction; the UI can only mirror early checks.
- For async server errors, preserve entered data, focus first actionable error, link summary to field ID and allow retry if safe.
- Password/card details remain in supported auth/gateway UI; AdPilot does not copy them into its own React state or analytics.
- Confirm irreversible operations with explicit subject, consequence and required authorization; avoid generic “Are you sure?” copy.

### 15.5 Notifications and tone

Use precise, neutral language and actionable next steps. Examples:

| Situation | Recommended microcopy | Avoid |
|---|---|---|
| Capacity estimate | “Availability shown is an estimate until you reserve it.” | “Guaranteed available” |
| Hold acquired | “This placement is held until 16:42 (Europe/Berlin).” | “Your ad is booked forever” |
| Payment callback delayed | “We’re verifying the payment. No new charge has been started.” | “Payment successful” |
| Paid but pending review | “Payment confirmed. Your creative still requires approval.” | “Your ad is live” |
| Creative version rejected | “Creative version 3 needs changes: [approved reason].” | “Your account has failed” |
| Data missing | “No measurable impressions for this period.” | “0 impressions” with no evidence |
| Permission revoked | “Your access to this organization changed. Choose another authorized organization.” | Organization private details |
| Checkout provider unavailable | “Online checkout is unavailable. Your draft is saved; no payment was initiated.” | “Try any other provider” |

---

## 16. Frontend implementation architecture (React/TypeScript + WordPress)

### 16.1 Runtime separation

- **Public catalog:** server-rendered WordPress-safe shell plus a selectively hydrated catalog/filter React island where justified. It shares route/API contracts and design tokens but does not require loading a complete admin bundle.
- **Authenticated advertiser portal:** dedicated React/TypeScript app registered and mounted through WordPress-controlled routes, with tenant-safe loader and guarded client navigation; no publisher console route imports.
- **Checkout:** WooCommerce owns compatible gateway UI; AdPilot controls its pre-/post-checkout contract state and verified return status. Avoid iframe hacks that bypass gateway controls.
- **Gutenberg:** four block editors and server renderers are owned by the block integration specification, not imported into advertiser portal.
- **Public ad renderer:** a small independent client; never bundle portal React, charting or authentication views into ordinary content pages.
- **Analytics:** lazy-loaded report visualization; no public heatmap SDK bundled in advertiser portal by default.

### 16.2 Proposed filesystem organization

```text
frontend/
  shared/
    design-system/        # Document 12 shared components/tokens
    api-client/           # typed, versioned DTOs and error normalization
    auth/                 # WordPress identity and entitlements
    forms/                # validated, accessible forms
    i18n/                 # localization and direction helpers
    money-time/           # view formatting, not commerce truth
  storefront/
    catalog/              # UI-06 public listing and filters
    placement-detail/     # UI-07 offer and safe preview
    compare/              # bounded offer comparison
  advertiser/
    app-shell/            # safe organization context and navigation
    onboarding/           # UI-08 organization and membership
    campaign-wizard/      # UI-09 steps, autosave and revisions
    creatives/            # owned assets, versions, safe preview
    availability/         # advisory calendar/selection
    checkout/             # UI-10 quote/hold/order/verification
    campaigns/            # advertiser UI-11 list, detail, timeline
    billing/              # scoped UI-14 orders/invoices
    reporting/            # UI-16 aggregate reports; R2 export UI
    support/              # approved help/FAQ and actionable contact
  tests/
    components/
    contracts/
    e2e/
    accessibility/
    visual/
```

This is a proposed package layout; merge with the canonical Document 06 directories without duplicate copies of shared contracts. Use source-compatible build tooling already approved for the project. Production bundles must be reproducible and free of proprietary runtime dependency obligations.

### 16.3 State management plan

Separate five state classes:

1. **URL/navigation state:** public browse filters, safe selections, nonsecret route context.
2. **Server query cache:** explicitly keyed `(siteId, orgId, actor authorization revision, route, object id, filters, schema version)`, with invalidation on membership, organization or state changes.
3. **Local draft editing:** current input values, dirtiness and validation; server revision is independently stored.
4. **Transactional authority:** quote, hold, order, approval and delivery are **never** client-mutated projections; request then re-read.
5. **Ephemeral UI state:** dialog open, selected tab, filter drawer, notification focus and theme preference.

Use a typed query layer (WordPress `@wordpress/data` where appropriate or an approved query library) and a small app-local store for client-only state. Do not adopt multiple competing stores without an ADR and concrete use case. Server response snapshots include request IDs and timestamps where specified; stale results from prior orgs must be discarded even if they arrive after switch.

### 16.4 Source-level TypeScript illustrative contracts

The following types demonstrate frontend **usage** of the upstream API. They do not alter ADP-DOC-009 DTO schemas. Implement actual generated types from the approved OpenAPI/schema artifact and map fields explicitly rather than asserting these exact property names exist today.

```ts
// Illustrative view-model interfaces only. The canonical wire DTOs live in ADP-DOC-009.
type AsyncView<T> =
  | { kind: 'idle' | 'loading' }
  | { kind: 'ready'; value: T; fetchedAt: string; stale: boolean }
  | { kind: 'empty'; reason: 'none' | 'filtered' }
  | { kind: 'denied' | 'unavailable'; recoverable: boolean }
  | { kind: 'error'; code: string; requestId?: string; retryable: boolean };

type CampaignStatusAxes = {
  financial: string;
  moderation: string;
  delivery: string;
};

type QuoteSummaryView = {
  quoteId: string;
  currency: string;
  totalMinorUnits: string;
  priceBasis: string;
  expiresAtUtc: string;
  isAuthoritative: boolean; // true only for server projection
};

interface AdvertiserPortalRoute {
  screenId: 'UI-08' | 'UI-09' | 'UI-10' | 'UI-11' | 'UI-14' | 'UI-16';
  orgId?: string;
  readEntitlement: string;
  release: 'R1' | 'R2' | 'R3' | 'R4';
}
```

Never calculate final payment amount from `totalMinorUnits` in the view; format a trusted server value with currency-specific rules. TypeScript type safety does not replace runtime schema validation of untrusted network data.

### 16.5 API error normalization

Convert exact REST error envelope into a typed domain-facing UI `ApiError` with `httpStatus`, stable `code`, safe `message`, `fieldErrors`, `requestId`, `retryable`, `operationId` where present, and authoritative object revision when available. Recognize 401 (login), 403/permission 404 (denial), 409 (capacity or current-state conflict), 412 (stale expected revision), 422 (validation), 429 (rate limit), 5xx (server failure) without changing their server meanings. Never show sensitive raw error traces. Failure of client optimistic state must not invent new invoice/hold objects.

### 16.6 Idempotent frontend command executor

For a given user intent, generate a stable high-entropy operation key, attach it to each supported mutation exactly as specified by ADP-DOC-009, hold it through timeouts/retries, and avoid reusing across distinct intents. React event handler may mark `pending`, but command endpoint and persisted idempotency record are the source of truth. An uncertain payment response triggers an authoritative `GET` before another mutation.

### 16.7 API and UI performance rules

- Lazy-load reporting and rich preview packages; prioritize critical offer content, quote and action controls.
- Use request cancellation and last-query-wins tokens for filter changes; protect against stale writes across organization switches.
- Paginate campaigns/invoices, and never fetch entire catalogs or 500-slot inventories to paint a single page.
- Upload through supported bounded requests with progress and cancellation; avoid large base64 media in JSON.
- Limit expensive revalidation on every keystroke; debounce safe field checks and immediately validate on review.
- Render skeleton/placeholder dimensions to prevent layout shift; measure long task and interaction responsiveness on low-end devices.
- Do not render hidden publisher admin components, session replay modules or expensive full-page site previews in advertiser bundles.
- Publish budgets only after representative baseline benchmarks and Product/Perf approvals; do not claim arbitrary p95 performance from design alone.

---

## 17. API-to-screen integration registry (exact Document 09 identifiers)

**All API path examples below are relative to `/wp-json/adpilot/v1/`.** This register references real proposed IDs already defined by Document 09. If a desired operation has no upstream route (for example new ticket creation or R3 dispute mutation), log a change request; do not invent one in React and assume the backend will comply. Each scoped route derives org ID from the server-authorized context, not as sole authorization evidence.

| Screen/surface | Read routes | Mutation routes | Authority / important caveat |
|---|---|---|---|
| `UI-06` public catalog | `CAT-001, CAT-003, CAT-005..008` | None | Public published fields; availability advisory |
| `UI-07` placement detail | `CAT-002, CAT-004..007` | None | Safe simulated preview; no billing |
| `UI-08` authentication/org | `ORG-001, ORG-002, ORG-004, ORG-006, ORG-008` | `ORG-003, ORG-005, ORG-007, ORG-009..013` | Current membership and ownership; no global admin capability |
| `UI-09` campaign wizard | `CAT-002, CAT-005..007, CAM-003, CRE-001/004/010, PRC-006, RES-002` | `CAM-002, CAM-004/005, CRE-002/003/005, PRC-005, RES-001/003` | Versioned draft, approved-format checks, quote+hold authority |
| `UI-10` checkout | `PRC-006, RES-002, ORD-003` | `ORD-001, ORD-004`, `RES-003` if voluntary release | Verified provider order state; no simulated payment success |
| `UI-11` advertiser campaigns | `CAM-001/003, CRE-004/010, REP-004` | `CAM-004/005/006/007`, `CRE-003/005` | Three separate lifecycle axes; org-scoped |
| `UI-14` scoped billing | `ORD-002/003/005/006` | `ORD-004` only when permitted | Protected invoices; no publisher ledger or refund mutations |
| `UI-16` advertiser reporting | `REP-004` | `REP-006` only if permitted | Aggregate projection; no heatmap/replay privilege |
| `UI-18` export UX (R2) | `REP-007/008` | `REP-006` | Dedicated UI R2; R1 approved export APIs remain distinct |
| Contextual `CAM-014` (R2) | `CAM-003` | `CAM-014` | Duplicate as new draft, not approved/paid campaign |

### 17.1 HTTP request composition examples

**Example A: public placement listing**

```http
GET /wp-json/adpilot/v1/catalog/placements?format=image&device=desktop&limit=20
Accept: application/json
```

Field names and supported filter vocabulary must be taken from ADP-DOC-009’s validated route schema; query above is illustrative. The server returns only explicitly published fields. A frontend must not assume `page=1` offset pagination where the API uses cursors.

**Example B: generate a quote**

```http
POST /wp-json/adpilot/v1/organizations/{org_id}/quotes
Content-Type: application/json
X-WP-Nonce: {current_auth_nonce}
Idempotency-Key: {stable_intent_key_if_route_requires}
```

The body must use ADP-DOC-009 fields including current offered inventory, selected time interval and related commercial conditions. The server calculates totals. The WordPress nonce supports cookie-auth request forgery protection but is **not authorization**; membership, policy, quote and inventory checks happen independently.

**Example C: checkout status query**

```http
GET /wp-json/adpilot/v1/organizations/{org_id}/orders/{order_id}
Accept: application/json
X-WP-Nonce: {current_auth_nonce}
```

The browser does not set payment status; it reads the trusted projection. Avoid logging nonce, body secrets or financial tokens.

### 17.2 Missing route/change-request register

| Desired behavior | Current authority | Required action |
|---|---|---|
| Generic advertiser support-ticket creation | No established `support` REST endpoint in Document 09 | Use configured safe support contact or raise reviewed endpoint change request |
| Extra order download binary API if not included in existing projection | `ORD-006` invoice metadata only where defined | Reuse approved protected download contract; otherwise design/API review |
| Advertiser refund request mutation | Publisher `ORD-009..011` is privileged | Do not expose; governed contact/request workflow or upstream contract change |
| Instant payment success after provider redirect | Commerce and WooCommerce trusted callback | Explicitly forbidden; query `ORD-003` |
| Advertiser access to full heatmaps/session replay | Privileged R2/R4 purpose-scoped analytics only | Forbidden by default, separate security/product change needed |
| R3 billing dispute submit/resolve | Scope unresolved in Documents 09/13 | Do not build active buttons until approved API/policy exists |
| Push/email notification preference mutation | No guaranteed dedicated endpoint from route registry | Show settings only if actual contract exists; document change request |

---

## 18. Security, privacy, quality and internationalization contracts

### 18.1 Security boundaries

Public offers/preview media are sanitized; never execute advertiser-submitted JS as first-party privileged code. All privileged reads and writes reauthorize per object/site/org. Reject unsafe redirects, disallowed URL schemes, cross-origin credential leakage, forged nonces, replayed invitations, CSRF on authenticated state changes, SSRF-prone creative previews, and unauthorized export retrieval. No route may use a client-provided role/grant/verification boolean as authority. For any unrecognized session or organization route, send a safe not-found/denial state without leaking record existence.

### 18.2 Commerce and data classification

- Never collect or save full card data, secrets, passwords, authorization proofs, payment-gateway callback tokens, or sensitive form contents in product analytics.
- Financial reporting and invoice fields follow current billing grants; a campaign manager does not gain finance rights merely by owning a campaign.
- Order amounts are integer minor units/explicit fixed-decimal strings with ISO currency and controlled tax rules; never charge from a JS-computed total.
- Public availability and reach estimates do not expose unpublished campaigns, advertiser spend, traffic segmentation, private IP or individual visitor records.
- Allow deletion/export of appropriate personal data through the platform’s approved privacy and retention services; do not promise immediate deletion of legally retained financial records.

### 18.3 Accessibility acceptance

WCAG 2.2 AA target on all essential routes, manually audited in addition to automated checks. Every component requires visible keyboard focus, semantic heading sequence, labels and error associations, screen-reader announcement for asynchronous status changes, noncolor meaning, sufficient contrast and target sizing. Date selection and upload need non-drag alternatives. Screen-reader and keyboard users must be able to complete signup → draft → upload → availability → quote → hold → checkout initiation → trusted order status → report without a mouse. Dialog focus trap, Escape behavior, save/cancel order and route focus restoration must be tested.

### 18.4 RTL and localization

Full interface strings must use WordPress localization facilities and translation catalogs; RTL via logical CSS properties, `dir`-aware icons where semantics require, and tested chart/stepper direction. Numeric order IDs, currencies and URLs must retain correct bidirectional isolates in RTL text. Format money, dates, decimals and units by locale and explicit time zone. Do not infer date from browser clock or treat a display-formatted date as authoritative UTC input. Test English LTR and one actual RTL locale at minimum; test long translated strings and small screens. Locale switching cannot change purchased contract currency or silently modify backend schedule timestamps.

### 18.5 Mobile, zoom and reduced motion

At narrow widths (320/360/390 CSS px examples) and 200–400% zoom, interfaces remain operable with reflow and without loss of material purchasing content. Side rails become reviewable collapsed or stacked summaries; CTA is not hidden under keyboard or sticky footer. Reduce or disable decorative animation for `prefers-reduced-motion`; motion does not communicate sole state. Forced-colors mode preserves focus and state labels. Charts, comparisons and org roster provide readable mobile alternatives.

### 18.6 Privacy-aware UX measurement

Event names such as `advertiser_catalog_filter_changed`, `advertiser_wizard_step_completed`, `advertiser_quote_requested`, `advertiser_hold_expired`, `advertiser_payment_status_checked` may be proposed for instrumentation but require separate approved schema, purpose and consent controls. Do not send creative destination URLs, form body, invoice details, invite tokens or financial identifiers to a third-party analytics SDK by default. Never import Hotjar or proprietary session replay by default.

### 18.7 Dependency and asset licensing

Use WordPress-compatible packages and maintained GPL-compatible open-source dependencies. Preserve license/notice records, avoid unlicensed fonts/mock images, and only embed a third-party gateway SDK according to its actual approved distribution terms. Source-available does not imply open source. Test WordPress.org guidelines before shipping. All network-dependent optional integrations must fail without breaking core portal routing, campaign drafts and display of verified stored status.

---

## 19. UI-to-domain workflow orchestration and error handling

| Workflow | Entry / precondition | Authoritative sequence | Success UI | Failure / compensation |
|---|---|---|---|---|
| Prospect to advertiser | Published offer browsed | `CAT-002` → account/`ORG-003` if permitted → actor/org validation | Return to offer; no lost nonsecret selection | Registration unavailable → retain public offer and contact path |
| Create campaign | Active membership, compatible offer | `CAM-002` → versioned `CAM-004` → server draft | Draft saved state | Revision conflict, forbidden membership, draft errors |
| Upload and review creative | Valid owned draft | `CRE-002/003` → safe preview `CRE-010` → `CRE-005` | Current version pending review | Invalid URL/file, policy rejection, upload interrupted |
| Schedule and quote | Offer still published, dates valid | `CAT-005` → `PRC-005/006` | Exact price/expiry visible | Unavailable slot → reselect interval; quote stale → re-quote |
| Acquire hold | Quote current, member allowed | `RES-001` with atomic checks → `RES-002` | Held until authoritative deadline | 409 overlapping exclusive booking → no purchase |
| Create order | Hold current, actor authorized | `ORD-001` → authoritative order | Order ref and pending payment | Duplicate request reuses idempotent result; expired hold triggers recovery |
| Checkout | Provider configured, billing right | `ORD-004` WooCommerce handoff → trusted reconciliation → `ORD-003` | Verified paid/pending/failed independently | Gateway return uncertain → safe refresh and no double charge |
| Moderate campaign | Publisher separate interface | `CAM-005` / `CRE-005`, privileged review `CRE-007/008/009` | Advertiser sees exact review state | Paid rejection → remedy pathways without false delivery |
| Manage own campaign | Active member with relevant grant | `CAM-001/003`, `CAM-006/007` if state permits | Updated server lifecycle axes | Permission revoked or stale state → block and reload |
| View own reports | Campaign/org read grant | `REP-004` authorized aggregates | Qualified stats, definitions and freshness | Lag/unavailable/unmeasurable distinct, no raw sessions |
| Export report | Entitled and enabled | `REP-006` → `REP-007` → `REP-008` with fresh auth | Download valid protected artifact | Expired/revoked/failed job → no stale public URL |
| Switch organization | Multiple active memberships | cancel/invalidate scoped work → `ORG-004` → new loaders | New scope visible everywhere | Stale data dropped, no cross-org leakage |

### 19.1 Cross-tab concurrency scenarios

- Same user opens two campaign editor tabs: stale CAS update rejected with safe reconciliation; the last blind tab cannot overwrite approved fields.
- Same user and organization acquire two competing exclusive holds: backend rejects second if capacity conflict; frontend shows individual authoritative hold statuses.
- Two organization owners concurrently change a member role: server expected revision prevents stale elevation; UI refreshes roster.
- A billing actor starts checkout after campaign manager changed schedule: order must be checked against current quote/hold contract and reject stale changes.
- A page remains open while site admin suspends organization: next protected request fails and old cached dashboards clear; a gateway return cannot bypass suspension.

### 19.2 Offline, timeouts and browser navigation

- Browsing pages can degrade to cached public nonsecret content with clear stale badge; availability/price should be revalidated before action.
- Draft editing may preserve in-memory values; offline autosave must show `Not saved to server`.
- Payment and hold commands use stable idempotency and trusted state lookup; do not auto-repeat unknown financial operation.
- Navigation guard applies to dirty drafts; avoid blocking browser Back after successful checkout; never intercept external gateway security controls.
- Background return while hold timer looks valid must recheck `RES-002` and order before claiming entitlement.

---
## 20. Acceptance test strategy, fixtures and evidence standards

### 20.1 Test implementation requirements

For each `AD14-T-*` scenario below, QA SHALL implement a reproducible Given/When/Then browser or API-integrated test as appropriate. Record fixture, actor/site/org, environment, endpoint calls, actual status/body, component snapshots where justified, keyboard/assistive-tech observations and result evidence. Mere inclusion of the test in this document does **not** mean it passed. Test with two or more advertiser organizations, mixed membership roles, one admin moderator, one finance user, an anonymous visitor, and a realistic classic/block theme pair. Test without WooCommerce and with WooCommerce HPOS. Use synthetic creatives and payments only in automated tests; NEVER point load tests at a live payment gateway.

### 20.2 Core fixture catalog

| Fixture ID | Fixture setup | Must verify |
|---|---|---|
| `AD14-FX-01` | Public site, 4 header / 100 sidebar / 196 in-content / 200 footer logical slots | Large inventory previews bounded; offer metadata truthful |
| `AD14-FX-02` | 1,000+ generated slots and several occupied/held positions | Pagination and mobile/keyboard browse performance |
| `AD14-FX-03` | Advertiser A/B, overlapping user memberships but different roles | No cross-org cache or disclosure |
| `AD14-FX-04` | Owner, manager, analyst, billing-only, revoked member | Scoped navigation and denied mutations |
| `AD14-FX-05` | Creative v1 approved, v2 pending, v3 rejected | Exact creative version status and policy |
| `AD14-FX-06` | Quote valid, expired, replaced, rate changed | UI quote invalidation, clear new total |
| `AD14-FX-07` | Overlapping exclusive capacity for two buyers | Exactly one successful hold and safe loser recovery |
| `AD14-FX-08` | Hold expires immediately before order creation | No payment from expired entitlement |
| `AD14-FX-09` | Payment callback delayed, duplicated, reversed, failed | Verified statuses, no duplicate charge |
| `AD14-FX-10` | Paid but creative under review and later rejected | Separate axes and governed remedy |
| `AD14-FX-11` | No WooCommerce / unavailable gateway | Graceful disabled checkout, drafts intact |
| `AD14-FX-12` | Campaign paused, scheduled, suspended, completed, timezone DST overlap | Accurate timeline and action permissions |
| `AD14-FX-13` | Aggregates zero, unknown, stale, delayed, adjustment | Proper metric qualifiers and timestamps |
| `AD14-FX-14` | Authorized and revoked export files | Fresh authorization and expiring downloads |
| `AD14-FX-15` | English, Arabic/Persian RTL, long translations and narrow viewport | Full localization, BIDI correctness and reflow |
| `AD14-FX-16` | Offline, 429/500, 401/403, 409/412, form 422, cancellation | Distinct retry and recovery controls |
| `AD14-FX-17` | Invitation expired, wrong recipient, last-owner transfer | Secure role boundaries and notifications |
| `AD14-FX-18` | Public preview with dangerous external creative HTML/URL | Sanitization and no executing first-party JS |
| `AD14-FX-19` | Report actor with only analyst or only billing permission | Valid landing page and field projection |
| `AD14-FX-20` | Two browser tabs editing same campaign and organization switching midfetch | Revision conflicts, query cancellation, no leakage |

### 20.3 Baseline test environments

Minimum declared browser matrix: current supported Chromium, Firefox and Safari; mobile Safari/Chromium and at least one low-end mobile performance profile. WordPress classic theme and block theme; cookie-auth with REST nonces, translated locale and RTL; both WooCommerce/HPOS configured and no-WooCommerce. Add server fault injection for delayed queues/settlement. Version numbers are to be frozen in the supported compatibility matrix; no fabricated claims of testing.

### 20.4 Regression gates

A failed R1 financial/tenant safety test blocks release, even if other UI screenshots appear polished. Ensure preview/staging don't generate paid events, gateway return does not prove settlement, expired hold does not become order, unauthorized invoice bytes never leak, and a sold slot cannot be misrepresented by a frontend preview. All critical user journeys need keyboard-only E2E acceptance and at least one manual screen-reader pass at final gate.

---

### 20.5 Detailed acceptance and adversarial scenario catalog (`AD14-T-*`)

Every scenario requires a fixture, executed result and evidence. The following are **proposed tests**, not tests that have passed. `When` is an action/trigger; the `Then` clause must be supported by backend state and (where relevant) observed UI. For tests covering several UI IDs, scope runs separately.

#### CAT: Public offers and marketplace

| Test ID | Release / UI | Given | When | Then |
|---|---|---|---|---|
| `AD14-T-001` — anonymous public listing | R1; `UI-06` | A published and an unpublished zone exist | An anonymous prospect opens and filters the catalog | Only published safe offers are shown and no private organization facts appear. |
| `AD14-T-002` — no published offers | R1; `UI-06` | All public offers are inactive | The catalog is requested | A truthful empty state and permitted next action appear. |
| `AD14-T-003` — filtered empty listing | R1; `UI-06` | Published offers exist but a filter excludes them | The prospect filters by unsupported size | Applied filters and a clear option show; data is not fabricated. |
| `AD14-T-004` — rapid filter races | R1; `UI-06` | Two catalog requests have different filters and out-of-order responses | The second request completes first | Only the latest chosen filter updates the visible results. |
| `AD14-T-005` — public pricing units | R1; `UI-06` | Published offers have weekly and one-time rates | The cards render | Every price has unit and currency, without invented tax inclusion. |
| `AD14-T-006` — advisory availability | R1; `UI-06` | A date appears available in a cached catalog | Another buyer acquires the exclusive hold | The page does not continue to claim the first buyer holds inventory. |
| `AD14-T-007` — offer withdrawal | R1; `UI-07` | The viewer opens a published placement | The admin withdraws it before detail refresh | Detail shows unavailable without private withdrawn campaign info. |
| `AD14-T-008` — multi-device preview | R1; `UI-07` | The placement has desktop and mobile configurations | The user changes the preview device | Simulated layout changes without triggering live ad decisions or impressions. |
| `AD14-T-009` — 200-slot preview bound | R1; `UI-07` | The footer zone contains 200 logical slots | The preview opens | UI renders a bounded exemplar with truthful slot count. |
| `AD14-T-010` — theme style isolation | R1; `UI-06` | A classic theme applies global buttons and heading css | The marketplace loads | AdPilot preserves theme content layout and keeps controls accessible. |
| `AD14-T-011` — public endpoint data-minimization | R1; `UI-06` | Unpublished and restricted price records exist | Anonymous actor inspects response and interface | No private data appears in DOM, JSON, or error strings. |
| `AD14-T-012` — no-commerce discoverability | R1; `UI-07` | Woocommerce is disabled | The prospect browses the catalog | Browse remains functional; no live checkout promise appears. |

#### ORG: Organizations, onboarding and membership

| Test ID | Release / UI | Given | When | Then |
|---|---|---|---|---|
| `AD14-T-013` — registration disabled | R1; `UI-08` | Wordpress site disables public signup | A prospect initiates advertiser onboarding | UI explains prerequisites without claiming an account was created. |
| `AD14-T-014` — organization creation | R1; `UI-08` | A logged-in permitted user has no organization | They submit valid organization information | Authorized organization is created and context selected from response. |
| `AD14-T-015` — invalid organization details | R1; `UI-08` | An owner submits missing/invalid required fields | The server returns schema validation errors | Fields and focus show precise feedback and valid input is preserved. |
| `AD14-T-016` — membership role separation | R1; `UI-08` | An analyst user can read reports | The user opens team management by direct url | Membership mutation is unavailable and server denies direct action. |
| `AD14-T-017` — invite teammate | R1; `UI-08` | An org owner has invitation permission | They invite a billing user | Invitation is pending, not active membership, with correct scope. |
| `AD14-T-018` — invitation wrong recipient | R1; `UI-08` | An invitation belongs to another wordpress user | The current user attempts acceptance | Server denies and UI does not expose invitation details. |
| `AD14-T-019` — expired invitation | R1; `UI-08` | An invitation expiry is in the past | The intended recipient follows it | UI shows expired safe recovery, without activating membership. |
| `AD14-T-020` — last owner safeguard | R1; `UI-08` | An organization has exactly one owner | The owner attempts to revoke self | Operation is blocked with a clear transfer recommendation. |
| `AD14-T-021` — ownership transfer | R1; `UI-08` | A current owner starts an approved transfer | Only one of the required parties approves | UI keeps transfer pending and no early privilege changes occur. |
| `AD14-T-022` — cross-org read | R1; `UI-08` | A user belongs to two organizations with different entitlements | They switch from a to b | A-specific cached campaigns, finance and drafts do not appear in B. |
| `AD14-T-023` — stale org API response | R1; `UI-08` | A slow request for org a is in flight | The user switches to b before it resolves | The response from A is discarded and cannot repopulate B. |
| `AD14-T-024` — revoked membership | R1; `UI-08` | A member loses access in another browser | They invoke a protected operation | Server denies and portal redirects to a permitted route. |
| `AD14-T-025` — billing-only landing | R1; `UI-08` | A billing member lacks campaign-read grant | They open the portal home | The landing page uses their valid billing route, not a blank denied dashboard. |
| `AD14-T-026` — multi-tab role update | R1; `UI-08` | Two authorized owners concurrently edit the same membership revision | A stale tab submits the old role version | Conflict is displayed; no stale privilege grant applies. |

#### WIZ: Campaign wizard and creative versions

| Test ID | Release / UI | Given | When | Then |
|---|---|---|---|---|
| `AD14-T-027` — new draft creation | R1; `UI-09` | A manager has a current org and a valid offer | They start and save a campaign | Server returns owned versioned draft and accurate save status. |
| `AD14-T-028` — autosave offline | R1; `UI-09` | A draft editor loses connectivity | User changes a field and waits | UI says changes are not saved to server and preserves local values. |
| `AD14-T-029` — draft reload | R1; `UI-09` | A draft was previously saved | The manager refreshes the browser | The server draft is restored with valid step and no fake quote. |
| `AD14-T-030` — stale campaign editor | R1; `UI-09` | Two tabs edit the same campaign | The second submits an outdated revision | A conflict/reload choice appears without overwriting the first. |
| `AD14-T-031` — unsafe creative upload | R1; `UI-09` | A user attempts to upload an executable svg or js file | Server rejects the asset | UI shows safe error and other draft fields remain. |
| `AD14-T-032` — bad destination URL | R1; `UI-09` | Creative destination uses a dangerous url scheme | User tries to submit creative | Server validates and denies; no open redirect is exposed. |
| `AD14-T-033` — preview nonbillable | R1; `UI-09` | Campaign preview is rendered inside the wizard | Browser tracking is inspected | Production billable impressions and clicks remain unchanged. |
| `AD14-T-034` — creative version reapproval | R1; `UI-09` | Creative v1 was approved and v2 is edited | Manager submits v2 | V2 is pending exact-version review, not auto-approved. |
| `AD14-T-035` — invalid schedule | R1; `UI-09` | User selects an end before start | The schedule is validated | Inline errors explain the problem and cannot advance to quote. |
| `AD14-T-036` — daylight-saving overlap | R1; `UI-09` | Selected local time is ambiguous at dst fall back | Manager requests a quote | UI requires unambiguous offset/time selection before use. |
| `AD14-T-037` — targeting feature unavailable | R1; `UI-09` | A particular targeting method is disabled in r1 | User views targeting step | Control is absent or clearly disabled and cannot produce invalid API params. |
| `AD14-T-038` — wrong format creative | R1; `UI-09` | Offer requires a compatible aspect ratio | User selects incompatible creative | Clear validation and alternatives appear; server denies submission. |
| `AD14-T-039` — permission downgrade midwizard | R1; `UI-09` | Campaign manager is revoked during editing | User attempts submit | Submission is denied, private state removed, and no data leak occurs. |
| `AD14-T-040` — dirty org switch | R1; `UI-09` | Unsaved draft exists in org a | User chooses org b | Switch offers save/discard/cancel, without moving draft to B. |
| `AD14-T-041` — creative rejection recovery | R1; `UI-09` | Moderator requests changes on v2 | Advertiser opens campaign wizard | Precise reason shown; correction creates a new governed version. |

#### QTE: Pricing, availability and holds

| Test ID | Release / UI | Given | When | Then |
|---|---|---|---|---|
| `AD14-T-042` — advisory only | R1; `UI-09` | Public availability predicts slot free | The user highlights the date | There is no contract/held badge before RES-001 success. |
| `AD14-T-043` — server quote totals | R1; `UI-09` | Pricing server computes multi-line total | User opens review step | UI displays exact totals without modifying calculated units. |
| `AD14-T-044` — quote expired | R1; `UI-09` | Quote expiry passes before hold | User clicks reserve | System refuses old quote and guides fresh quote. |
| `AD14-T-045` — rate changed | R1; `UI-09` | Site updates a published rate after quote | User attempts purchase with stale contract | Server policy is evaluated and user sees re-quote when required. |
| `AD14-T-046` — competing exclusive holds | R1; `UI-09` | Two buyers race for the same overlapping interval | Both request hold | Only one succeeds; losing user gets truthful conflict and options. |
| `AD14-T-047` — hold timer skew | R1; `UI-10` | Client clock is inaccurate by several minutes | Hold panel is shown and browser tab wakes | UI refreshes authoritative expiry and never extends it locally. |
| `AD14-T-048` — hold expires during checkout | R1; `UI-10` | Hold expires while user views checkout | User attempts to continue | Order creation cannot proceed from expired hold. |
| `AD14-T-049` — hold release | R1; `UI-10` | User explicitly cancels before ordering | They release authorized hold | Status is confirmed by server, without local-only release claim. |
| `AD14-T-050` — commercial changed after hold | R1; `UI-09` | User changes zone/dates after obtaining hold | They revisit commercial summary | Old hold is not silently reused and new quote/hold is required. |
| `AD14-T-051` — one thousand slots listing | R1; `UI-07` | Publisher offers generated high-density slot sequence | Advertiser inspects available positions | List is bounded/paginated and keyboard usable. |
| `AD14-T-052` — quote ownership | R1; `UI-09` | Quote belongs to org a | Billing actor in org b guesses its id | Server denies and UI exposes no price or contract data. |
| `AD14-T-053` — manual no-commerce quote | R1; `UI-09` | Site runs without woocommerce | User saves draft and sees pricing | Online purchase route is unavailable and manual eligibility is explained. |

#### PAY: Checkout, verified payments and invoices

| Test ID | Release / UI | Given | When | Then |
|---|---|---|---|---|
| `AD14-T-054` — order idempotency | R1; `UI-10` | Buyer repeats order create after a timeout with same intent key | Both calls complete | One authoritative order/commercial effect exists. |
| `AD14-T-055` — gateway redirect spoof | R1; `UI-10` | Browser return contains success=true without trusted capture | Ui reloads order | Payment remains pending or unverified. |
| `AD14-T-056` — delayed webhook | R1; `UI-10` | Payment provider settlement callback is delayed | Browser returns to adpilot | UI shows verifying and a safe status refresh, not paid. |
| `AD14-T-057` — duplicate webhook | R1; `UI-10` | Gateway sends identical callback multiple times | Status is checked | Only one settlement effect and correct UI state appear. |
| `AD14-T-058` — out-of-order callback | R1; `UI-10` | Failure notification arrives after verified settlement | Viewer refreshes | Authoritative state is preserved and not incorrectly overwritten. |
| `AD14-T-059` — payment failed | R1; `UI-10` | Provider confirms a failed attempt | Customer opens order | UI shows failure and governed recovery without implying charge. |
| `AD14-T-060` — payment timeout | R1; `UI-10` | Start-checkout returns network timeout | User presses retry | UI reconciles current state and reuses same intent key if safe. |
| `AD14-T-061` — paid review pending | R1; `UI-10` | Provider confirms order but creative remains unapproved | Customer views completion | Payment confirmed and review pending show independently; not active. |
| `AD14-T-062` — paid creative rejected | R1; `UI-10` | Moderator rejects creative after payment | Customer views status | Remedy/revision policy is visible; no false fulfilled claim. |
| `AD14-T-063` — disabled WooCommerce | R1; `UI-10` | Payment adapter disabled | Buyer reaches quote stage | Checkout is unavailable and no fake provider/fallback is rendered. |
| `AD14-T-064` — invoice cross-tenant | R1; `UI-14` | Billing user knows foreign invoice id | They request invoice details/download | Authorization denies without disclosing file bytes. |
| `AD14-T-065` — financial precision | R1; `UI-14` | Store backend handles currency minor-unit rules | Invoice total is formatted | Exact currency/unit semantics display, not JS float-derived charge. |
| `AD14-T-066` — suspension midpayment | R1; `UI-10` | Site suspends org after checkout started | Browser returns from gateway | Status is verified and invalid further actions blocked. |
| `AD14-T-067` — unknown provider state | R1; `UI-10` | Payment status api is temporarily unavailable | User views checkout status | UI labels status unavailable and does not start another charge. |

#### CAMP: Campaign lifecycle and moderation projections

| Test ID | Release / UI | Given | When | Then |
|---|---|---|---|---|
| `AD14-T-068` — three independent badges | R1; `UI-11` | Campaign has paid, approved, scheduled states | Advertiser views campaign detail | Three distinct labels display accurately. |
| `AD14-T-069` — payment but not approved | R1; `UI-11` | Campaign is paid but creative is pending | User attempts to view as active | UI states pending approval; no active delivery claim. |
| `AD14-T-070` — limited campaign manager | R1; `UI-11` | Manager may edit campaign but not billing | They open detail | Billing fields are omitted and backend permission enforced. |
| `AD14-T-071` — analyst read only | R1; `UI-11` | Analyst views campaign with aggregate read right | They attempt pause via forged ui call | Server denies and no status change occurs. |
| `AD14-T-072` — publisher emergency stop | R1; `UI-11` | Administrator emergency-stops active ad | Advertiser views campaign status | Delivery axis updates from current authoritative state. |
| `AD14-T-073` — request pause | R1; `UI-11` | Owner requests pause in allowed state | Server accepts request | UI displays current resulting state and next step. |
| `AD14-T-074` — resume not eligible | R1; `UI-11` | Creative was revoked while paused | Manager requests resume | UI shows why resume is denied based on server readiness. |
| `AD14-T-075` — campaign timeline | R1; `UI-11` | Review/financial events occurred at different times | User opens activity | Event types and time zones are correctly labeled and privacy-safe. |
| `AD14-T-076` — duplicate campaign release gate | R1; `UI-11` | R2 duplicate is disabled in r1 | User opens campaign actions | No working duplicate button appears and approval is never copied. |
| `AD14-T-077` — filter selection persistence | R1; `UI-11` | Campaign list has filters and cursor | User opens a campaign then returns | Safe filters/scroll context restore without stale org leakage. |

#### REP: Reporting and controlled exports

| Test ID | Release / UI | Given | When | Then |
|---|---|---|---|---|
| `AD14-T-078` — metric denominator | R1; `UI-16` | Qualified clicks and measurable impressions differ | Advertiser views ctr | Display includes correct denominator/metric definition. |
| `AD14-T-079` — missing vs zero | R1; `UI-16` | Server provides no measurable data | Metric cards render | Unknown is not misrepresented as numeric zero. |
| `AD14-T-080` — analytics lag | R1; `UI-16` | Aggregation job is behind real time | Report is opened | Freshness/delay is displayed; no real-time claim. |
| `AD14-T-081` — cross-org report | R1; `UI-16` | User has report permission in org a only | They request org b report by id | Server denies and report/chart DOM never receives B data. |
| `AD14-T-082` — billing vs analyst data | R1; `UI-16` | Analyst has reporting but not billing grants | Campaign report loads | Restricted money fields are absent; aggregate performance remains. |
| `AD14-T-083` — accessible charts | R1; `UI-16` | Time series has data values and axis units | Keyboard/screen reader navigates | Equivalent data table and labels expose important values. |
| `AD14-T-084` — empty reporting period | R1; `UI-16` | No validated events exist in selected interval | User selects date range | UI shows true empty/qualification explanation. |
| `AD14-T-085` — export reauthorization | R2; `UI-18` | Owned export becomes ready and member is later revoked | Member downloads using old link | Server denies at download time and no bytes leak. |
| `AD14-T-086` — export expiration | R2; `UI-18` | Export artifact expires before download | User activates old link | Clear expired state and safe new-request action if permitted. |
| `AD14-T-087` — export retry failures | R2; `UI-18` | Worker fails during an export | User views export status | UI reports failed/retryable accurately without public file link. |
| `AD14-T-088` — R3 metric hidden | R1; `UI-16` | Metered billing r3 is not enabled | User views advertising report | CPC/CPM billable sums are not presented as operational. |
| `AD14-T-089` — publisher heatmap not advertiser | R2; `UI-16` | R2 heatmap available to publisher only | Advertiser analyst opens reporting | No visitor/session heatmap data or privileged route is exposed. |

#### A11Y: Accessibility, responsiveness and trust

| Test ID | Release / UI | Given | When | Then |
|---|---|---|---|---|
| `AD14-T-090` — keyboard-only purchase | R1; `UI-06..10` | Keyboard user begins public catalog flow | They complete campaign and checkout initiation | All required controls and dialogs work without pointer. |
| `AD14-T-091` — screen-reader purchase | R1; `UI-09` | Screen-reader user completes wizard with errors | Error and step change occur | Proper labels, summaries and meaningful announcements are heard. |
| `AD14-T-092` — mobile 320px | R1; `UI-10` | Narrow viewport renders checkout with virtual keyboard | User inspects terms and submits | No content/action overlap or inaccessible total. |
| `AD14-T-093` — two-hundred percent zoom | R1; `UI-08/14` | Browser uses 200% zoom | User edits organization and checks invoices | Important content reflows and controls remain operable. |
| `AD14-T-094` — RTL bidirectional price | R1; `UI-09` | Rtl locale and latin currency reference are displayed | User reads the quote | Amount, code, dates and reference remain correctly ordered. |
| `AD14-T-095` — forced colors | R1; `UI-11` | Os high contrast mode is enabled | User inspects statuses and focus | All statuses remain textually distinguishable and focus visible. |
| `AD14-T-096` — reduced motion | R1; `UI-09` | User prefers reduced motion | Wizard and notifications update | Nonessential motion is disabled without hiding feedback. |
| `AD14-T-097` — payment multi-tab | R1; `UI-10` | Checkout open in two tabs and payment settles | Both tabs update independently | Neither submits duplicate charges nor invents status. |
| `AD14-T-098` — screen route denied | R1; `UI-11` | Org member loses read access while dashboard open | They refresh/deep link | Protected information cleared with safe fallback. |
| `AD14-T-099` — creative no-script | R1; `UI-07/09` | Untrusted creative contains scripted html | Public preview attempts rendering | No first-party privileged code executes. |
| `AD14-T-100` — long localized string | R1; `UI-09` | Button and field text expand in translated locale | Wizard renders on mobile | Label remains readable and CTA interaction remains usable. |
| `AD14-T-101` — public theme compatibility | R1; `UI-06` | Classic and block theme differ in css normalization | Catalog is embedded on both | Scope prevents global overrides and essential UI remains usable. |

## 21. GPT-6 High bounded implementation plan and handoff

Each work package must have at most one primary owner and a focused vertical slice. Before editing code, GPT-6 High SHALL read the affected canonical sections, extract exact requirement IDs, identify current source files, inspect actual route schemas, and make an explicit change list. After implementing, it SHALL run the specified tests, report actual commands/results, update docs, and list unresolved issues. It must not invent APIs, assert checkout success from a mock gateway, or treat planned acceptance scenarios as executed. Work packages with unresolved upstream contract gaps must stop at the dependency boundary and raise a change request rather than ship phantom controls.

### 21.1 Cross-team review gates

- **UX Lead:** information hierarchy, copy, workflow coherence and navigation.
- **Visual/UI Designer:** exact tokens/components, mobile polish and visual consistency.
- **Frontend Lead:** React boundaries, rendering, bundles and testable state.
- **WordPress/API Lead:** validated DTOs, route correctness, theme isolation.
- **AdTech/Finance:** accurate pricing, hold vs estimate, campaign statuses, metric qualifiers.
- **Security/Privacy:** authorization, creative safety, financial secrecy, consent and exports.
- **Accessibility Lead:** keyboard/screen-reader, focus, contrast, RTL and reflow.
- **QA Lead:** acceptance evidence, regression, concurrency and fault injection.

---

### 21.2 Detailed implementation work-package registry (`AD14-WP-*`)

#### AD14-WP-01 — Foundation and existing-contract audit

- **Owner / primary reviewers:** Frontend Lead + API Lead; reviewer roles per §21.1.
- **Entry dependency:** ADP-DOC-009 / ADP-DOC-012; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Build routing shell, API typegen, source ID audit, conditional component registry, CI; prevent new REST routes.
- **Required evidence:** Route/type tests; public portal vs publisher privilege partition. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-02 — Public catalog information architecture

- **Owner / primary reviewers:** UX Lead + Product Researcher; reviewer roles per §21.1.
- **Entry dependency:** CAT-001..008; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Implement filtering, sorting, public offer cards, locale-aware terms, no-result/loading states.
- **Required evidence:** Usability test + CAT cases + theme snapshot. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-03 — Placement detail and simulated preview

- **Owner / primary reviewers:** Frontend Lead + AdTech; reviewer roles per §21.1.
- **Entry dependency:** CAT-002/004/005; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Build safe responsive placement preview, 500-slot summary, terms and exact scope.
- **Required evidence:** Preview no-billing, format and policy tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-04 — Sign-in/registration and organization entry

- **Owner / primary reviewers:** WordPress + Frontend; reviewer roles per §21.1.
- **Entry dependency:** ORG-001..005; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Handle WordPress auth handoff, org creation, multi-org choice and routing recovery.
- **Required evidence:** Authentication and current-org reload tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-05 — Membership management and context isolation

- **Owner / primary reviewers:** Security + Frontend; reviewer roles per §21.1.
- **Entry dependency:** ORG-006..013; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Implement least-privilege member roster, invite, ownership transfer and org-switch cache flush.
- **Required evidence:** Cross-org, last-owner and revocation tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-06 — Design system advertiser compositions

- **Owner / primary reviewers:** Visual Designer + Frontend; reviewer roles per §21.1.
- **Entry dependency:** ADP-DOC-012; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Build portal cards, stepper, calendar, price table, status triaxis, errors, tables.
- **Required evidence:** Accessible component stories in LTR/RTL/mobile. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-07 — Campaign wizard skeleton and draft

- **Owner / primary reviewers:** Frontend + Domain; reviewer roles per §21.1.
- **Entry dependency:** CAM-002/003/004; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Implement stepper, route guards, autosave state, revision conflict resolution.
- **Required evidence:** Draft recovery, reload, two-tab conflict tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-08 — Creative material and previews

- **Owner / primary reviewers:** Creative/Security + Frontend; reviewer roles per §21.1.
- **Entry dependency:** CRE-001..005/010; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Implement accessible upload, server-policy validation, immutable versions and preview.
- **Required evidence:** Unsafe media/URL, version reapproval and no-event tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-09 — Schedule and targeting wizard step

- **Owner / primary reviewers:** AdTech + UX; reviewer roles per §21.1.
- **Entry dependency:** CAT-005/CAM-004; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Expose supported audience/time rules, DST handling and accessible date selection.
- **Required evidence:** Ambiguous times and unsupported targeting tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-10 — Price quotes and review

- **Owner / primary reviewers:** Commerce + Frontend; reviewer roles per §21.1.
- **Entry dependency:** PRC-005/006; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Render server-calculated exact-price quote, expiry and invalidation state.
- **Required evidence:** Minor units, price change, expired quote tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-11 — Atomic hold and inventory recovery

- **Owner / primary reviewers:** Reservations + Frontend; reviewer roles per §21.1.
- **Entry dependency:** RES-001..003; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Use current hold state, countdown display, conflicts and explicit release.
- **Required evidence:** Buyer race, TTL expiry, skew and cancellation tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-12 — Order creation and checkout handoff

- **Owner / primary reviewers:** Commerce + WooCommerce; reviewer roles per §21.1.
- **Entry dependency:** ORD-001/004; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Implement idempotent order creation, gateway handoff, no-provider fallback.
- **Required evidence:** Duplicate create, failed/reversed/timeout tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-13 — Trusted payment verification and remediation

- **Owner / primary reviewers:** Finance + Frontend; reviewer roles per §21.1.
- **Entry dependency:** ORD-003, CRE-004; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Render pending/verified/failed states; paid-awaiting-approval and governed remedies.
- **Required evidence:** Late callback and paid-rejection tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-14 — Advertiser campaign listing and detail

- **Owner / primary reviewers:** Frontend + Campaign Lead; reviewer roles per §21.1.
- **Entry dependency:** CAM-001/003/006/007; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Separate three statuses, audit timeline, permission-scoped actions.
- **Required evidence:** Manager vs analyst, pause/resume and stop tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-15 — Orders/invoices scoped projection

- **Owner / primary reviewers:** Finance + Frontend; reviewer roles per §21.1.
- **Entry dependency:** ORD-002/003/005/006; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Build billing profile, protected invoice access, financial formatting.
- **Required evidence:** Revocation/download and currency exactness tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-16 — Aggregate advertiser analytics

- **Owner / primary reviewers:** Analytics + Frontend; reviewer roles per §21.1.
- **Entry dependency:** REP-004; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Implement qualified KPI cards, source freshness, charts/data table, permitted filters.
- **Required evidence:** Unknown vs zero, late aggregates and isolation tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-17 — R2 export job UX

- **Owner / primary reviewers:** Reporting + Frontend; reviewer roles per §21.1.
- **Entry dependency:** REP-006..008; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Add asynchronous export progress, current rights on download, expiry.
- **Required evidence:** R2 gate, revoked/expired export tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-18 — Notification and error system integration

- **Owner / primary reviewers:** UX + QA; reviewer roles per §21.1.
- **Entry dependency:** Existing domain error contract; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Provide actionable inline notices, result summary, safe retries and degraded states.
- **Required evidence:** 429/5xx, forbidden, validation, conflict and pending tests. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-19 — Internationalization and accessibility review

- **Owner / primary reviewers:** A11y + Frontend; reviewer roles per §21.1.
- **Entry dependency:** NFR-009/NFR-011; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** WCAG 2.2 AA manual audit, RTL, zoom, keyboard and screen-reader complete purchase.
- **Required evidence:** Run A11Y suite and block release on critical findings. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

#### AD14-WP-20 — High-density, compatibility and release verification

- **Owner / primary reviewers:** Performance + QA + Security; reviewer roles per §21.1.
- **Entry dependency:** All R1 + UJ-01..07; verify actual available endpoints, DTO fields, component tokens and release gate before code changes.
- **Execution scope:** Verify public preview with 500+ slots, cache isolation, WC HPOS, no-WC, frontend bundle.
- **Required evidence:** Cross-browser E2E, performance logs, license audit, reviewer sign-off. Attach concrete test commands/output, UI screenshots with actual data fixtures, role checks, a11y observations and source references.
- **Stop condition:** If a necessary authoritative API/permission/commercial policy is absent or contradictory, record a source-controlled issue and do not fake the server behavior in a React component.

### 21.3 Canonical source requirement crosswalk (`FR-*` 129/129)

The following identifiers and releases are read from **ADP-DOC-001**, not assigned by this document. “Direct” means advertiser UX implementation; “Shared” means the portal depends on the capability via backend/other surface, without claiming publisher functions belong in the advertiser UI. Consult ADP-DOC-002 for the exact matching `AT-*` acceptance ID and ADP-DOC-009 for route contracts.

| Source ID | Canonical release | Direct/shared UI responsibility | Implementer / reference |
|---|---|---|---|
| `FR-INV-001` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-001` |
| `FR-INV-002` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-002` |
| `FR-INV-003` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-003` |
| `FR-INV-004` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-004` |
| `FR-INV-005` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-005` |
| `FR-INV-006` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-006` |
| `FR-INV-007` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-007` |
| `FR-INV-008` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-008` |
| `FR-INV-009` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-009` |
| `FR-INV-010` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-010` |
| `FR-INV-011` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-011` |
| `FR-INV-012` | `R2` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-012` |
| `FR-INV-013` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-013` |
| `FR-INV-014` | `R1` | Shared: `UI-06/07/09` published inventory, not publisher editing | CAT-001/002/005, Inventory; `AT-INV-014` |
| `FR-INT-001` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-001` |
| `FR-INT-002` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-002` |
| `FR-INT-003` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-003` |
| `FR-INT-004` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-004` |
| `FR-INT-005` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-005` |
| `FR-INT-006` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-006` |
| `FR-INT-007` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-007` |
| `FR-INT-008` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-008` |
| `FR-INT-009` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-009` |
| `FR-INT-010` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-010` |
| `FR-INT-011` | `R2` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-011` |
| `FR-INT-012` | `R1` | Shared: `UI-07` theme preview and safe integration; editor functionality separate | Gutenberg/Theme modules; `AT-INT-012` |
| `FR-MKT-001` | `R1` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-001` |
| `FR-MKT-002` | `R1` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-002` |
| `FR-MKT-003` | `R1` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-003` |
| `FR-MKT-004` | `R1` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-004` |
| `FR-MKT-005` | `R1` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-005` |
| `FR-MKT-006` | `R1` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-006` |
| `FR-MKT-007` | `R1` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-007` |
| `FR-MKT-008` | `R1` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-008` |
| `FR-MKT-009` | `R1` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-009` |
| `FR-MKT-010` | `R2` | Direct: `UI-06/07/08/09/10/11` discovery, org and guided purchase | CAT-*, ORG-*, CAM-*; `AT-MKT-010` |
| `FR-CAM-001` | `R1` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-001` |
| `FR-CAM-002` | `R1` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-002` |
| `FR-CAM-003` | `R1` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-003` |
| `FR-CAM-004` | `R1` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-004` |
| `FR-CAM-005` | `R1` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-005` |
| `FR-CAM-006` | `R1` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-006` |
| `FR-CAM-007` | `R1` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-007` |
| `FR-CAM-008` | `R1` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-008` |
| `FR-CAM-009` | `R2` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-009` |
| `FR-CAM-010` | `R1` | Direct: `UI-09/11` campaign + creative version/read approval | CAM-*, CRE-*; `AT-CAM-010` |
| `FR-COM-001` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-001` |
| `FR-COM-002` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-002` |
| `FR-COM-003` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-003` |
| `FR-COM-004` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-004` |
| `FR-COM-005` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-005` |
| `FR-COM-006` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-006` |
| `FR-COM-007` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-007` |
| `FR-COM-008` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-008` |
| `FR-COM-009` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-009` |
| `FR-COM-010` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-010` |
| `FR-COM-011` | `R3` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-011` |
| `FR-COM-012` | `R4` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-012` |
| `FR-COM-013` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-013` |
| `FR-COM-014` | `R1` | Direct: `UI-09/10/14` quotes, holds, orders and billing | PRC-*, RES-*, ORD-*; `AT-COM-014` |
| `FR-DEL-001` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-001` |
| `FR-DEL-002` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-002` |
| `FR-DEL-003` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-003` |
| `FR-DEL-004` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-004` |
| `FR-DEL-005` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-005` |
| `FR-DEL-006` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-006` |
| `FR-DEL-007` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-007` |
| `FR-DEL-008` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-008` |
| `FR-DEL-009` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-009` |
| `FR-DEL-010` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-010` |
| `FR-DEL-011` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-011` |
| `FR-DEL-012` | `R2/R3` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-012` |
| `FR-DEL-013` | `R1` | Shared: `UI-11/16` delivery status and qualified reporting, not public renderer | Delivery and REP-004; `AT-DEL-013` |
| `FR-MET-001` | `R1` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-001` |
| `FR-MET-002` | `R1` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-002` |
| `FR-MET-003` | `R1` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-003` |
| `FR-MET-004` | `R1` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-004` |
| `FR-MET-005` | `R1` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-005` |
| `FR-MET-006` | `R1` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-006` |
| `FR-MET-007` | `R2` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-007` |
| `FR-MET-008` | `R2` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-008` |
| `FR-MET-009` | `R3` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-009` |
| `FR-MET-010` | `R3` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-010` |
| `FR-MET-011` | `R3` | Shared: `UI-16` validated measurement metrics, no client billable authority | REP-004, Measurement; `AT-MET-011` |
| `FR-HEAT-001` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-001` |
| `FR-HEAT-002` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-002` |
| `FR-HEAT-003` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-003` |
| `FR-HEAT-004` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-004` |
| `FR-HEAT-005` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-005` |
| `FR-HEAT-006` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-006` |
| `FR-HEAT-007` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-007` |
| `FR-HEAT-008` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-008` |
| `FR-HEAT-009` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-009` |
| `FR-HEAT-010` | `R4` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-010` |
| `FR-HEAT-011` | `R2` | Shared: R2 publisher heatmaps; no advertiser raw session entitlement | UI-17 privileged; `AT-HEAT-011` |
| `FR-REP-001` | `R1` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-001` |
| `FR-REP-002` | `R1` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-002` |
| `FR-REP-003` | `R1` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-003` |
| `FR-REP-004` | `R1` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-004` |
| `FR-REP-005` | `R2` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-005` |
| `FR-REP-006` | `R3` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-006` |
| `FR-REP-007` | `R3` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-007` |
| `FR-REP-008` | `R2` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-008` |
| `FR-REP-009` | `R2` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-009` |
| `FR-REP-010` | `R1` | Direct: `UI-16` advertiser aggregate reporting; R2 export and R3 reports gated | REP-004/006..008; `AT-REP-010` |
| `FR-UX-001` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-001` |
| `FR-UX-002` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-002` |
| `FR-UX-003` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-003` |
| `FR-UX-004` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-004` |
| `FR-UX-005` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-005` |
| `FR-UX-006` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-006` |
| `FR-UX-007` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-007` |
| `FR-UX-008` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-008` |
| `FR-UX-009` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-009` |
| `FR-UX-010` | `R2/R4` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-010` |
| `FR-UX-011` | `R1` | Direct: All advertiser/public surfaces; accessible responsive i18n | ADP-DOC-011/012/014; `AT-UX-011` |
| `FR-OPS-001` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-001` |
| `FR-OPS-002` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-002` |
| `FR-OPS-003` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-003` |
| `FR-OPS-004` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-004` |
| `FR-OPS-005` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-005` |
| `FR-OPS-006` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-006` |
| `FR-OPS-007` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-007` |
| `FR-OPS-008` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-008` |
| `FR-OPS-009` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-009` |
| `FR-OPS-010` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-010` |
| `FR-OPS-011` | `R4` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-011` |
| `FR-OPS-012` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-012` |
| `FR-OPS-013` | `R1` | Shared: Site/tenant security, privacy, core bootstrapping and integrations | ADP-DOC-005/006/010; `AT-OPS-013` |

### 21.4 Business-rule and invariant UI enforcement crosswalk

| Rule | Required advertiser interface enforcement | Surface | Authority |
|---|---|---|---|
| `BR-001` | Advisory catalog only; quote/hold allowed only when capacity current | `UI-07/09` | CAT-005/PRC-005/RES-001 |
| `BR-002` | Hold expiry only server; extensions require new transaction | `UI-09/10` | RES-001..003 |
| `BR-003` | Paid does not imply creative approved or delivery active | `UI-10/11` | ORD-003/CRE-004 |
| `BR-004` | Reject paid creative with governed revision/refund path | `UI-10/11/14` | CRE-004/ORD-003 |
| `BR-005` | Concurrent exclusive slot purchase cannot double sell | `UI-09` | RES-001 |
| `BR-006` | Commercial meaning of sold slot never silently changes | `UI-07/09` | CAT-002 + Inventory contract |
| `BR-007` | Only approved exact creative version eligible to serve | `UI-09/11` | CRE-003..005 |
| `BR-008` | Authoritative eligibility overrides cached stale content | `UI-11` | CAM-003/006/007 |
| `BR-009` | Preview/admin/test contexts nonbillable | `UI-07/09` | CRE-010 |
| `BR-010` | Advertising-density policy and network restrictions | `UI-07` | CAT-007 |
| `BR-011` | Advertiser receives validated aggregate report only | `UI-16` | REP-004 |
| `BR-012` | Raw events limited retention; ledger independent | `UI-16/14` | REP-004/ORD-003 |
| `BR-013` | Measured and estimated metrics labeled with definitions | `UI-06/07/16` | CAT-001/REP-004 |
| `BR-014` | Exact currency arithmetic and billing presentation | `UI-09/10/14` | PRC-005/ORD-003 |
| `BR-015` | UTC contract intervals and timezone handling | `UI-07/09` | CAT-005/PRC-005 |
| `BR-016` | Accessible responsive interface still constrains high-density offering | `UI-07/09` | CAT-002 |
| `BR-017` | Tracking/remote adapter opt-in per policy and disclosure | `UI-06/11/16` | Privacy/Feature status |
| `BR-018` | Advertiser has no unilateral paid inventory override | `UI-09/11` | Authoritative server policy |

**`INV-01..INV-10` remain independently mandatory:** no serving without gates; exclusive capacity; stable slot identity; distinct render instances; configured ≠ viewed; previews nonbillable; no cross-org disclosure; untrusted event ≠ charge; optional tracking refusal does not destroy safe baseline serving; untrusted creative JS is not privileged. The UI reflects these facts but does not replace server enforcement.

### 21.5 Nonfunctional and acceptance ID preservation

| Source | Applicable advertiser-frontend verification |
|---|---|
| `NFR-001` | Modular architecture; execute original `AT-NFR-001` with portal fixtures where applicable. |
| `NFR-002` | Code maintainability; execute original `AT-NFR-002` with portal fixtures where applicable. |
| `NFR-003` | Bounded public rendering; execute original `AT-NFR-003` with portal fixtures where applicable. |
| `NFR-004` | Measured performance budgets; execute original `AT-NFR-004` with portal fixtures where applicable. |
| `NFR-005` | Retries and scheduler reliability; execute original `AT-NFR-005` with portal fixtures where applicable. |
| `NFR-006` | Financial reconciliation; execute original `AT-NFR-006` with portal fixtures where applicable. |
| `NFR-007` | Security; execute original `AT-NFR-007` with portal fixtures where applicable. |
| `NFR-008` | Privacy/data minimization; execute original `AT-NFR-008` with portal fixtures where applicable. |
| `NFR-009` | Accessibility; execute original `AT-NFR-009` with portal fixtures where applicable. |
| `NFR-010` | Compatibility; execute original `AT-NFR-010` with portal fixtures where applicable. |
| `NFR-011` | i18n/RTL; execute original `AT-NFR-011` with portal fixtures where applicable. |
| `NFR-012` | Diagnostics; execute original `AT-NFR-012` with portal fixtures where applicable. |
| `NFR-013` | Versioned integrations; execute original `AT-NFR-013` with portal fixtures where applicable. |
| `NFR-014` | GPL-compatible licensing; execute original `AT-NFR-014` with portal fixtures where applicable. |
| `NFR-015` | Observability/audit; execute original `AT-NFR-015` with portal fixtures where applicable. |
| `NFR-016` | User experience and usability; execute original `AT-NFR-016` with portal fixtures where applicable. |
| `AC-001` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-002` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-003` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-004` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-005` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-006` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-007` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-008` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-009` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-010` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-011` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-012` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-013` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-014` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |
| `AC-015` | Preserve source PRD R1 exit criterion; provide portal/browser evidence if applicable, else reference responsible module test without weakening the gate. |

### 21.6 Canonical UI and journey index

| Existing screen | Ownership in Document 14 | Release |
|---|---|---|
| `UI-06` — Public catalog | Full | R1 |
| `UI-07` — Placement detail | Full | R1 |
| `UI-08` — Advertiser onboarding/organization | Full | R1 |
| `UI-09` — Campaign wizard | Full | R1 |
| `UI-10` — Checkout handoff | Full | R1 |
| `UI-11` — Campaign list/detail | Advertiser projection | R1 |
| `UI-14` — Rates/orders/finance | Advertiser billing projection | R1 |
| `UI-16` — Advertiser reporting | Full | R1 |
| `UI-18` — Export jobs | R2 canonical UI, R1 API distinct | R2 |
| `UI-17` — Heatmap explorer | Publisher-only; no advertiser rights | R2 |
| `UI-19` — Billing dispute | Publisher R3 and contract unresolved | R3 |

All `UJ-01..UJ-28`, `WF-01..WF-18`, `UXA-01..UXA-28` and `E2E-01..17` remain canonical in Documents 02/03. R1 marketplace, campaign, payment-recovery, reporting, and no-WooCommerce journeys are implemented here where advertiser-facing; `UJ-23` is the R2 export journey and remains release-gated. Publisher Layout Studio, moderation, and heatmap journeys retain ownership in their dedicated specifications. No source journey was renamed by this document.

## 22. Decision register and upstream contract reconciliation

| Decision ID | Issue | Owner | Interim rule / release impact |
|---|---|---|---|
| `AD14-DEC-01` | Final public page slugs and auto-created WordPress pages | WP/UX/Product | Use approved WordPress page provisioning; no invented hardcoded URL ownership |
| `AD14-DEC-02` | Sign-up and organization verification required fields by jurisdiction | Product/Legal/Privacy | Minimum viable policy; no country-wide compliance claims |
| `AD14-DEC-03` | Checkout/provider handoff return and fallback URL | Commerce/WooCommerce/Security | Use supported gateway contract; never trust redirect query |
| `AD14-DEC-04` | Manual/complimentary R1 workflow in no-WooCommerce mode | Product/Finance | Online purchase not available; guarded nonpayment contracts only |
| `AD14-DEC-05` | Quote/hold TTL, refresh and extension rights | AdTech/Finance | Server-authoritative and policy-controlled; no client timer extensions |
| `AD14-DEC-06` | Exact public card estimate formulas and suppression thresholds | Analytics/AdTech | Label estimates; omit unsupported precision |
| `AD14-DEC-07` | Supported pricing/tax locale combinations | Finance/Legal | Server calculates, client formats; unsupported locale blocks paid checkout |
| `AD14-DEC-08` | Whether billing user can initiate checkout for another campaign manager | Product/Security/Finance | Explicit role plus object grant; no inherited campaign-edit right |
| `AD14-DEC-09` | Creative supported MIME, size, URL/redirect restrictions | Security/Moderation | Server policy decides; no arbitrary user JS |
| `AD14-DEC-10` | Public 500-slot commercial selection UI and ability to buy multiple slots | Product/Inventory/UX | Only backend-defined bundles/slots; avoid client-invented packages |
| `AD14-DEC-11` | Export API R1 (`REP-006..008`) vs dedicated export screen R2 (`UI-18`) | Product/API/UX | Preserve both boundaries; avoid declaring the entire R2 UI active in R1 |
| `AD14-DEC-12` | R3 dispute mutation endpoints remain incomplete in preceding API spec | Finance/API/Security | UI read-only or gated until approved contract/ADR |
| `AD14-DEC-13` | Third-party checkout failure/missing WooCommerce messaging | UX/Commerce/Support | Preserve drafts, show truthful unavailable status, no invented provider |
| `AD14-DEC-14` | Authorization-aware portal landing for analyst and billing-only roles | Security/UX | Route to actually permitted screen, never block all organization members |
| `AD14-DEC-15` | Consent basis for advertiser portal product analytics | Privacy/Product | Instrument only approved limited events, no form fields/identifiers sent |
| `AD14-DEC-16` | Actual usability/performance targets and visual approval | UX/Performance/Product | Benchmark on representative user cohort and hardware before committing numbers |
| `AD14-DEC-17` | Advertiser support tickets/notifications endpoints not established | Product/API/Support | Use safe configured contact; propose API through change control |
| `AD14-DEC-18` | Paid rejection remedy presentation and timelines | Finance/Legal/Moderation | Follow BR-004 and configured policy; no one-click advertiser refund |

Every decision must include rationale, status, approver, target release, impacted `FR/BR/AT/API`, migration impacts if any, and link to the amendment that resolves it. Open decisions are not permission to leave mandatory R1 workflows partly simulated.

---

## 23. Definition of Done and external references

### 23.1 Per-feature acceptance

A user-facing feature is complete only when its UI, server contract, authorization, accessible interaction, responsive/RTL states, failure handling, analytics truth, automated tests, docs and security review are implemented. Each work package includes one success and at least two negative/edge cases; financial or cross-tenant work requires concurrency and authorization tests. Screens must be tested in the actual WordPress host, not only standalone component stories.

### 23.2 Documentation-specific completion audit

1. Preserve canonical `UI-06/07/08/09/10/11/14/16` and optional `UI-18` without new duplicate screen IDs.
2. Define all public catalog, auth, organization, creative, wizard, schedule, quote, hold, checkout, reporting, billing and recovery flows.
3. Maintain source ID release meanings R1–R4 and existing `CAT/ORG/CAM/CRE/PRC/RES/ORD/REP` routes.
4. Distinguish availability estimate, quote, hold, order, payment verification, approval and delivery as independent facts.
5. Protect tenant access, exact financial values and vendor creative trust boundaries.
6. Avoid unsupported promise of all 500 configured slots being viewed, sold or rendered.
7. Every screen has loading, empty, conflict, permission, degraded, responsive, RTL and keyboard contracts.
8. All required acceptance IDs are unique; no tests are represented as executed.
9. Trace canonical 129 functional requirements without redefining meanings or promoting later-release features.
10. Complete open decisions and cross-document discrepancies with owner and safe default.

### 23.3 Primary source references for implementation validation

These are platform documentation links, not substitutes for AdPilot contractual requirements:

- WordPress REST API: <https://developer.wordpress.org/rest-api/>
- WordPress REST authentication: <https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/>
- WordPress JavaScript packages: <https://developer.wordpress.org/block-editor/reference-guides/packages/>
- WordPress components: <https://developer.wordpress.org/block-editor/reference-guides/components/>
- WordPress internationalization: <https://developer.wordpress.org/plugins/internationalization/>
- WordPress shortcode and theme development: <https://developer.wordpress.org/plugins/shortcodes/>
- WordPress plugin developer security: <https://developer.wordpress.org/plugins/security/>
- WooCommerce HPOS: <https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/>
- WCAG 2.2: <https://www.w3.org/TR/WCAG22/>
- WordPress.org distribution guidelines: <https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/>

**End-of-document status:** Proposed implementation specification. No user testing, browser pass, payments, legal verification or source-code implementation is asserted. **Next:** ADP-DOC-015 — Visual Layout Studio, High-Density Inventory & Placement Designer Specification.
