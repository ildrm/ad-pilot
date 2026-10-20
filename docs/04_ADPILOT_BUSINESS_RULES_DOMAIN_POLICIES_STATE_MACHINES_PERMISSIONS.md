# AdPilot — Business Rules, Domain Policies, State Machines & Permission Scenarios

**Document ID:** ADP-DOC-004  
**Filename:** `04_ADPILOT_BUSINESS_RULES_DOMAIN_POLICIES_STATE_MACHINES_PERMISSIONS.md`  
**Version:** 1.0.0 — implementation specification proposal  
**Status:** Proposed; awaiting product, finance, privacy, and technical sign-off  
**Date:** 2026-10-11  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Primary implementation model:** WordPress-native PHP modular monolith; REST/Gutenberg/shortcode/theme adapters; optional WooCommerce checkout; native event analytics  
**Accountable owner:** Principal Software Architect + Product Owner  
**Required reviewers:** AdTech Lead, Business Analyst, Commerce/Finance Lead, Database Lead, Security Lead, Privacy Lead, UX Lead, Frontend/Gutenberg Lead, Analytics Lead, QA Lead  
**Intended implementer:** GPT-6 High with human architectural and product approval gates

---

## 1. Purpose, precedence and normative interpretation

This document translates business behavior from **ADP-DOC-001**, detailed feature tests from **ADP-DOC-002**, and persona/workflow definitions from **ADP-DOC-003** into executable domain policies and transition contracts. It must not silently broaden the release scope or modify canonical source requirements.

### 1.1 Normative source hierarchy

1. **ADP-DOC-001**: product scope, `FR-*` (129), `BR-001..018`, `NFR-001..016`, `AC-001..015`, `INV-01..10`, `DEC-001..012`, releases `R1..R4`.
2. **ADP-DOC-002**: detailed feature catalog; `AT-*` acceptance obligations; `E2E-01..17`; `UI-01..20`.
3. **ADP-DOC-003**: `PERS-01..16`, `JTBD-01..24`, `UJ-01..28`, `WF-01..18`, `UXA-01..28`; screen-state and recovery contracts.
4. **ADP-DOC-004 (this document)**: operational detail for those same requirements. IDs `DP-*`, `SM-*`, `PERM-*`, `SCN-*` and `AUD-*` introduced here are *supplementary*, never replacements.

If a detailed policy here conflicts with a baseline requirement, baseline wins until a reviewed architecture decision and revised canonical document explicitly supersede it. **No code or UI may substitute an inferred refund, retention, consent or legal policy for a decision not yet approved.**

### 1.2 Normative terms and release boundaries

- **SHALL / MUST**: required when the corresponding release/feature is enabled.
- **SHOULD**: preferred, with documented and approved exception.
- **MAY**: optional and not implied to exist in R1.
- **R1**: fixed/time-based sale, WooCommerce online checkout when enabled, manual/complimentary admin campaign, moderation, inventory, progressive rendering and baseline reporting.
- **R2**: aggregated heatmaps/viewability/exports, responsive geometry analysis; optional R2 creative extensions require separate gates.
- **R3**: verified metered CPM/CPC, share of voice, spending controls, conversion integrations, invalid-traffic investigations, experiments.
- **R4**: optionally self-hosted session replay, network workflows, additional commerce adapters, external workers, white-label extensions.

No R3 chargeable event is created just because the R1 tracking SDK reports a click. No replay collection is performed just because the R2 heatmap collector is installed. No-WooCommerce bootstrap remains supported at R1.

### 1.3 System-wide policy evaluation contract

All authoritative commands follow this sequence:

1. Authenticate actor if operation requires identity; classify anonymous endpoints separately.
2. Authorize capability **and** site/organization/resource ownership, including current membership and restriction state.
3. Validate input structure, permissible fields, state-version preconditions, temporal bounds and account policies.
4. Query fresh authoritative records; never trust frontend state, nonces, cached previews or a browser redirect to establish business facts.
5. Enforce invariants and eligibility conditions; use transactions and locks for conflicting financial or inventory writes.
6. Perform the smallest durable state mutation; append immutable audit/ledger facts as required.
7. Commit, then enqueue idempotent post-commit side effects through an outbox/queue strategy appropriate to the deployment.
8. Return confirmed state/version, typed errors and actionable next steps; invalidate caches and refresh UIs with bounded freshness.

A request must not partially update authoritative commercial state and then return an unqualified success. For multi-system operations, use an explicit saga/reconciliation process rather than pretending a MySQL transaction spans WooCommerce and an external payment provider.

### 1.4 Policy evaluation precedence

The effective precedence for ad eligibility is: **global emergency stop / legal-policy block → tenant or account suspension → current creative approval → valid contract/financial state → schedule and reservation/allocation → placement binding and capacity → user/privacy/density policy → contextual targeting → pacing/rotation/fallback**. Earlier denials cannot be overridden by lower-priority presentation settings or theme hooks. A `house` or complimentary campaign still follows creative and safety policy; only its commercial payment prerequisite differs by an explicitly authorized contract type.

### 1.5 Conceptual system-of-record ownership

| State or fact | Authoritative owner | Not authoritative |
|---|---|---|
| Advertiser membership and capabilities | WordPress identity + AdPilot organization membership store | Browser route visibility, cached role label |
| Zone, slot, binding and published layout | Inventory/Layout services + immutable version record | Gutenberg saved attrs alone; DOM structure |
| Exclusive capacity and contracted inventory | Reservation/Allocation service with transactional DB records | Availability calendar, quotation preview |
| Campaign and creative approval | Campaign/Moderation store | Payment receipt, creative filename, front-end badge |
| Online payment outcome | WooCommerce/provider verified status mapped to AdPilot order | Thank-you page, redirect query parameters |
| Financial balance, refund and adjustment | Append-only accounting ledger/reconciliation store | Mutable dashboard total, raw impression count |
| Current ad eligibility | Delivery policy using fresh authoritative gates | CDN fragment, JavaScript flags, stale cached creative |
| Qualified measurement | Validated server event pipeline | Unverified pixel/request/click event |
| Heatmap result | Versioned aggregate with consent/sampling metadata | Raw cursor coordinate without layout context |
| Consent authorization | Current applicable consent decision and regional policy | Merely loading a tracker script |

---

## 2. Domain vocabulary and invariants

### 2.1 Entities and essential identity rules

| Entity | Identity and relationships | Hard constraint |
|---|---|---|
| Site / tenant | WordPress site ID (multisite aware); all domain resources site-scoped | Never read/write across sites by unqualified ID |
| Organization | Publisher advertiser account with owners and members | Current account status gates commands |
| Zone | Stable named inventory region | Zone ID persists through cosmetic renaming |
| Container | Versioned layout grouping within zone | 200-slot container is not 200 Gutenberg blocks |
| Logical slot | Stable inventory address; may be generated from sequence | Contractual identity distinct from row/column presentation |
| Placement binding | Context mapping: block, shortcode, PHP, hook, template, injector | Repeated render requires unique render identity |
| Layout version | Immutable published configuration and geometry lineage | Historical metrics point to correct version |
| Quote | Snapshot of price, terms, scope, taxes, applicable availability and expiry | Nonbinding without successful hold |
| Hold | Temporarily claimed eligible capacity, with server-issued expiry | TTL is not automatically extended by frontend countdown |
| Reservation/allocation | Commercial commitment to eligible inventory and time | Exclusive overlaps cannot both be committed |
| Order / payment | Commercial intent and verified settlement mapping | Financial state distinct from approval/delivery |
| Campaign | Creative versions, schedule, advertiser, inventory, target rules | No direct payment=active shortcut |
| Creative version | Immutable reviewed media+destination+policy attributes | Material edits create new reviewable version |
| Render instance | Unique page-occurrence identifier of logical slot | One slot can have many render instances |
| Qualified event | Event passing the currently applicable qualification rules | Event never automatically means money is owed |
| Ledger entry | Irreversible/reversible-by-adjustment financial fact | No destructive editing of posted history |

### 2.2 Preserved canonical invariants (`INV-01..10`)

| ID | Preserved meaning | Enforced by |
|---|---|---|
| `INV-01` | Serving needs approval, finance/contract, schedule, allocation and targeting gates | `DP-DEL`, `SM-DEL`, delivery unit tests |
| `INV-02` | Exclusive reservation cannot oversell under concurrency | `DP-RES`, lock/transaction integration tests |
| `INV-03` | Stable logical slot ID across nonbreaking layout edits; impact review for breaking edits | `DP-LAY`, layout publish transaction |
| `INV-04` | Each occurrence of a slot has distinct render/DOM identity and correct attribution | `DP-DEL`, frontend integration tests |
| `INV-05` | Configured ≠ served ≠ rendered ≠ viewable ≠ billable | `DP-MET`, report contract |
| `INV-06` | Editor/staging/synthetic/filtered automation is never production billable | `DP-MET`, negative event tests |
| `INV-07` | Strict advertiser data and mutation isolation | `DP-AUTH`, permission tests |
| `INV-08` | A browser event is not itself a final charge | `DP-FIN`, R3 reconciliation |
| `INV-09` | Optional tracking refusal does not automatically disable safe baseline ads, subject to law | `DP-PRV`, ad renderer |
| `INV-10` | No unrestricted first-party execution of untrusted advertiser code | `DP-CRT`, creative sandbox and CSP tests |

### 2.3 Additional derived invariants (new, supporting rather than replacing baseline)

| ID | Domain invariant |
|---|---|
| `D04-INV-01` | A paid order may be awaiting moderation and must not imply delivery entitlement. |
| `D04-INV-02` | Expired hold + late payment requires explicit resolution; it must not capture other buyers' inventory. |
| `D04-INV-03` | A refund or financial reversal cannot erase delivered measurement or prior ledger events; create adjusting entries. |
| `D04-INV-04` | Campaign availability, quote amount and booking time window are evaluated in the server-selected contract timezone with stored UTC instants. |
| `D04-INV-05` | Failed moderation leaves a documented correction/remedy path and does not silently reallocate reserved capacity. |
| `D04-INV-06` | Suspended advertisers cannot create or change campaigns even if old member credentials remain valid. |
| `D04-INV-07` | Published layout versions and creative versions are immutable; changes create successors. |
| `D04-INV-08` | Successful job retry never duplicates a reservation, activation, ledger posting, invoice or billable event. |
| `D04-INV-09` | Advertiser exports disclose no other organization's or visitor-level sensitive data, including through stale jobs/URLs. |
| `D04-INV-10` | Audit actors, reasons, before/after identifiers and times remain trustworthy; sensitive payloads are redacted. |
| `D04-INV-11` | House/complimentary bookings must respect already-sold exclusive capacity and creative moderation. |
| `D04-INV-12` | An administrator role alone does not license unsafe first-party advertiser code or override legal/consent protections. |
| `D04-INV-13` | Public reports distinguish missing, zero, estimated, unmeasurable, stale and redacted values. |
| `D04-INV-14` | Unavailable optional dependencies disable only dependent capabilities, not plugin bootstrap. |

---

## 3. Exact baseline business-rule register and enforcement map

These 18 sentences are preserved verbatim from ADP-DOC-001. Domain policies expand them; no replacement numbering is introduced.

| Source rule | Exact canonical wording | Governing policy | Enforcement owner | Negative acceptance test |
|---|---|---|---|---|
| `BR-001` | Inventory sale is permitted only for an active, publishable placement with sufficient eligible capacity. | `DP-INV` | Inventory/AdOps | `AT-BR-001`: Suspended zone or insufficient capacity blocks quotation/hold. |
| `BR-002` | A temporary hold expires after its configured TTL; extension requires an explicit transaction. | `DP-RES` | Reservations/Database | `AT-BR-002`: Expired hold cannot be extended through a stale timer or replayed request. |
| `BR-003` | A successful online payment does not bypass creative moderation. | `DP-MOD` | Moderation/Delivery | `AT-BR-003`: Paid but unapproved campaign is never eligible to serve. |
| `BR-004` | If a paid campaign is rejected, revision/refund/cancellation options follow recorded policy; never silently keep funds without an enforceable fulfillment path. | `DP-REM` | Moderation/Finance | `AT-BR-004`: Rejected paid creative triggers visible approved remedy rather than silent retention. |
| `BR-005` | An exclusive reservation cannot be double sold over overlapping contract time. | `DP-RES` | Reservations/Database | `AT-BR-005`: Two simultaneous exclusive holds cannot both succeed for overlapping windows. |
| `BR-006` | Changes to a slot's commercial meaning require impact analysis and resolution of affected contracts. | `DP-LAY` | Layout/Finance | `AT-BR-006`: Sold-slot reassignment cannot publish without reconciled contract impact. |
| `BR-007` | A rejected or unapproved creative cannot serve; approved prior versions may serve only if contract and policy permit. | `DP-CRT` | Creative Security | `AT-BR-007`: Changed destination or assets cannot inherit approval without review. |
| `BR-008` | Delivery eligibility is recomputed against current authoritative campaign state, not based solely on a stale cached creative. | `DP-DEL` | Delivery/Cache | `AT-BR-008`: Paused/suspended campaign cannot keep serving from stale shared cache. |
| `BR-009` | Ad-preview, administrator-editing and synthetic load-test events are excluded from revenue-bearing analytics. | `DP-MET` | Measurement/Billing | `AT-BR-009`: Preview, editor and synthetic events produce no production billable records. |
| `BR-010` | Advertising density policies and ad-network restrictions are configurable; default warnings should discourage excessive clutter and inadvertent clicks. | `DP-DEN` | UX/AdTech | `AT-BR-010`: Extreme density or prohibited placement yields warning/suppression per policy. |
| `BR-011` | Advertiser reports show validated aggregate outcomes, not visitor-level session content. | `DP-REP` | Analytics/Security | `AT-BR-011`: Advertiser reports cannot expose raw session or another account's records. |
| `BR-012` | Raw events have finite retention and are not a permanent financial source of truth; reconciled financial entries are independently retained under applicable policy. | `DP-RET` | Privacy/Finance | `AT-BR-012`: Purged raw events do not erase reconciled financial transactions. |
| `BR-013` | Conversion and performance claims distinguish observed facts, attribution models, estimates and projections. | `DP-REP` | Analytics/Product | `AT-BR-013`: Forecast and attributed conversion are not mislabeled as observed sale. |
| `BR-014` | Currency arithmetic uses precise decimal/minor-unit representations, never floating point for ledger authority. | `DP-FIN` | Finance/Database | `AT-BR-014`: Ledger calculation uses decimals/minor units, not floats. |
| `BR-015` | Timestamps are stored in UTC; contract terms display user-selected time zone and daylight-saving behavior explicitly. | `DP-TIM` | Scheduling/Finance | `AT-BR-015`: DST ambiguous/nonexistent times cannot silently alter contract. |
| `BR-016` | Accessibility and mobile usability may constrain a particular layout even when there is ample logical inventory capacity. | `DP-UX` | UX/Frontend | `AT-BR-016`: Mobile and accessibility conflicts prevent unsafe layout publication. |
| `BR-017` | Any third-party tracking, behavioral replay, or remote conversion adapter requires disclosure, consent gating where appropriate, and documented data flows. | `DP-PRV` | Privacy/Security | `AT-BR-017`: Consent denied or revoked blocks prohibited collection/replay. |
| `BR-018` | Manual administrator overrides of paid placement allocation require reason, authorization and audit logging. | `DP-OVR` | AdOps/Security | `AT-BR-018`: Admin allocation override without authorization/reason/audit is rejected. |

**Implementation obligation:** Every `AT-BR-*` denial must be demonstrable through a server-side automated test as well as corresponding UX feedback, where applicable. A client-side disabled button is not sufficient enforcement.

---

## 4. Domain policy contracts (`DP-*`)

Each policy must be implemented as an application/domain decision callable independently of the presentation layer, with tested authorization, validated input, stable result codes and audit effects. Names are proposed architectural contracts, not pre-existing APIs. `allow`, `deny`, `defer`, and `requires_remedy` are different outcomes and must not be collapsed into a Boolean when the distinction affects money or customer action.

### DP-AUTH — Identity, ownership, tenant isolation and capabilities (R1)

**Inputs:** Actor WP user ID; site/network context; organization membership; target resource and owner; operation; current account status; capability grants; request source. **Authority:** WordPress auth plus current AdPilot membership and resource repository. **Decision:** `allow | deny_unauthenticated | deny_capability | deny_scope | deny_suspended | deny_stale_membership`.

- Use default-deny capabilities for each sensitive operation; require both `current_user_can(...)` or equivalent capability and object-level authorization on each entity. A site administrator is not automatically entitled to unrelated network-site resources.
- Advertiser organization owner may manage membership subject to the account's safeguards; campaign manager cannot silently assign themselves finance/admin roles; billing member cannot edit campaign targeting; analyst cannot access invoice payment credentials or raw visitor data.
- Membership revocation must take effect on subsequent API requests and signed export downloads; never trust a long-lived browser token or saved React state. Session invalidation should be defined for high-risk privilege changes.
- For anonymous catalog, reveal only published product/rate fields, sanitized availability and appropriately labeled historical estimates. Never reveal private creative drafts, internal moderation notes, billing contracts, personally identifying visitor information, or fraud rules.
- Site-admin override of allocation requires a **separate capability** and business justification; it does not override security, advertiser isolation, forbidden tracking, or legal policy.
- Nonces are CSRF defenses in applicable WordPress contexts, not authentication/authorization or guaranteed single-use tokens.

**Failure/UX:** Return `403 scope_denied` with no target existence leak when a foreign resource ID is supplied; `401 authentication_required` when no suitable identity is present; preserve unsaved form work without displaying protected records. **Tests:** `AT-MKT-004`, `AT-MKT-008`, `AT-OPS-001`, `AC-009`, `UJ-06`, `SCN-AUTH-*`.

### DP-INV — Sellable inventory and capacity policy (R1)

**Inputs:** Site, slot/sequence ID, placement binding, published layout version, eligible creative formats, contract window, capacity model, exclusions, blackout dates, active holds and allocations. **Decision:** `sellable | temporarily_unavailable | contract_blocked | unpublished | invalid_layout | unsupported_format`.

- Only published and active zone/binding/slot inventory is sellable. A page-preview placeholder is not evidence that inventory can be purchased.
- Capacity must be computed for a full half-open time interval `[start_utc, end_utc)`, with exclusive booking requiring no other overlapping committed/valid held inventory; shared/quantity capacity uses a defined allocation unit and count.
- A compact 200-slot footer definition must generate stable addresses; the system must not implicitly sell an entire zone when a buyer selected one slot.
- If layout metadata changes during quotation, pricing and slot eligibility must be re-evaluated before hold creation.
- Archived zones should not be newly sold. Retired slots with historic contracts remain addressable for auditing until retention rules permit archival.

**Failures:** Return an availability conflict including nonconfidential next actions (refresh, choose alternatives), not competing advertiser identity. **Tests:** `BR-001`, `FR-INV-*`, `FR-COM-002`, `UJ-04`, `UJ-08`, `SCN-INV-*`.

### DP-RES — Hold, booking, exclusivity and reservation policy (R1)

**Inputs:** Accepted unexpired quote, site and organization, canonical slot/capacity key(s), UTC window, duration/quantity, chosen inventory product, idempotency key. **Decision:** `created | already_exists | expired | conflict | invalid_quote | invalid_contract`.

- A quotation is informative; only a successful transactionally committed hold reserves capacity. Holds are non-transferable between unrelated organizations.
- Temporary holds expire at authoritative server time; expiry can be processed lazily inside conflict evaluation and asynchronously by cleanup jobs. A delayed cleanup job cannot accidentally prolong exclusive inventory.
- A hold may transition to a durable `committed` reservation only via valid contract authorization (verified payment, explicitly approved manual/complimentary contract, or separately approved credit terms), while respecting currently owned hold and booking terms.
- Late success after hold expiry enters a **payment-success/inventory-unavailable remediation state**. It must not commit inventory already sold to another advertiser, and may need cancellation/refund, negotiated alternative, or explicit new booking.
- Atomic capacity allocation must use InnoDB-compatible transactions and a reliable per-inventory resource lock strategy; **a normal unique index does not by itself prevent arbitrary overlapping timestamp ranges**.
- Lock multiple slot/capacity keys in stable order. Re-read active holds and reservations inside the same transaction; enforce quantity and overlapping contractual time before insert.
- Extension is an explicit revalidation and new atomic mutation, not a client-side countdown reset.
- Record hold owner, quote version, expiry, status, locking/version information and audit reference.

**Failures:** `409 inventory_conflict`, `410 hold_expired`, `409 quote_stale`, `503 retryable_booking_error`; preserve campaign draft without representing an unsuccessful booking as confirmed. **Tests:** `BR-002`, `BR-005`, `FR-COM-003`, `AC-006..007`, `WF-04..06`, `SCN-RES-*`.

### DP-PRC — Rate card, quote integrity and commercial product (R1)

**Inputs:** Published versioned rate card, booked units, UTC time range, display time zone, organization eligibility, discounts, configured taxes, currency, policies, reservation terms. **Decision:** quote with immutable snapshot/hash, breakdown, expiry and explicit purchase terms.

- R1 offerings: fixed-price, time-based and exclusive sponsorship; no metered CPM/CPC quote should imply immediate R1 support.
- A quote must freeze the rate-card version, tax/discount inputs used, placement identifiers and accepted timezone interpretation. The expiry cannot retroactively alter an already committed contract.
- Currency amounts use decimal or integer minor units according to currency precision. Never rely on JavaScript floats or PHP floating-point arithmetic for posted ledger or tax authority.
- Rate card changes apply to subsequent quotes; modifications to an existing sold contract require amendment rather than mutable overwrite.
- Clearly distinguish forecast audience estimates, guaranteed delivery obligations (if any), and defined commercial units.

**Failures:** Changed rate/availability leads to refresh + compare; any pricing error prevents checkout initiation. **Tests:** `FR-COM-001`, `FR-COM-004`, `BR-014..015`, `AT-COM-001/004`, `SCN-PRICE-*`.

### DP-COM — Payment trust, order linkage and checkout authority (R1)

**Inputs:** Current AdPilot order, WooCommerce order and authorized provider state, hold, mapping keys, verified callback metadata, prior transitions. **Decision:** `awaiting_payment | confirmed | failed | reversed | pending_review | requires_reconciliation`.

- R1 online checkout uses WooCommerce only when installed/configured; commerce adapter failure disables online checkout but not layout, campaigns, display or manually authorized contracts.
- A browser redirect or `thank-you` route cannot mark a purchase as paid. Read authoritative WooCommerce state via supported APIs and verify trust for provider callbacks.
- Store stable order mappings and idempotency keys; handle duplicate, delayed and out-of-order notifications, and avoid double-posting or double-reserving.
- Do not infer financial settlement from gateway provider-specific states without verified mapping. Separate `authorized`, `captured`, `settled` if the provider supports these distinctions; mark ambiguous cases pending investigation.
- The system shall not retain card numbers or sensitive gateway credentials. Do not integrate a self-built card-data collection workflow.
- If WooCommerce is disabled after an order was created, preserve records, stop new online checkout and route pending reconciliation through diagnostics.

**Failure:** A payment considered confirmed when inventory is unavailable yields a visible pending-remedy state, not activation or hidden silent cancellation. **Tests:** `FR-COM-005..010`, `FR-COM-013`, `AC-005/007/014`, `WF-06..07`, `SCN-COM-*`.

### DP-MOD — Creative and campaign review policy (R1)

**Inputs:** Immutable creative version, destination, media metadata, advertiser state, policy version, moderation actor and current review version. **Decision:** `pending | approved | rejected | changes_requested | suspended` with reason.

- Approval targets a **specific creative version**, including relevant media digest, destination and significant disclosure/targeting attributes. Changed versions require fresh approval according to policy.
- Moderators require appropriate capability and must not approve their own unauthorized exceptions. Dual-control approval for high-risk policy exceptions can be introduced as a product setting with recorded ownership.
- Successful payment does not imply an approved ad. A moderator cannot alter the commercial contract by pressing Approve.
- Reject/change request must include safe explanation and a selected remedy path; review events are immutable and timestamped.
- Emergency creative/campaign stop must preempt cached ad delivery. Provide bounded invalidation/freshness and immediate server-side decision denial.
- Approved previous creative version may continue only when it remains contractually permitted, policy-compliant and explicitly selected; never fall back silently to an unapproved version.

**Failure:** Wrong-version approval returns conflict; unexpected dangerous content triggers stop and review. **Tests:** `BR-003/004/007`, `FR-CAM-001..010`, `AC-008`, `UJ-07`, `SCN-MOD-*`.

### DP-CRT — Creative format and first-party execution safety (R1, R2 extensions)

- R1 accepts validated raster images and controlled text/native templates with URL, dimensions, MIME, file size, alt text and sponsored disclosure rules.
- Arbitrary advertiser-controlled JavaScript, unsanitized SVG and unrestricted HTML must not be executed on first-party site origin. Any future interactive HTML/video creative is feature-gated and requires sandbox, CSP, browser security and performance/accessibility acceptance.
- Destinations require allowed protocols and server-side validation; any network fetch for destination verification must resist SSRF, redirects to restricted addresses, DNS rebinding where applicable, and excessive response sizes.
- Do not trust filename extension or browser-provided MIME alone; inspect content and normalize storage through WordPress media APIs or safe adapters.
- Image creative display must not expose administrative cookies, nonce values or site-private runtime state.

**Tests:** `FR-CAM-003/007..009`, `INV-10`, `AT-CAM-008`, `SCN-CRT-*`.

### DP-REM — Rejection, cancellation and remediation policy (R1)

- Paid creative rejection must open one or more approved remedies: correction and re-review within specified time, replacement creative, mutually accepted alternate inventory, partial/full refund or controlled contract cancellation.
- Refund eligibility, deadlines, fees and service consumption are **policy decisions (`DEC-007`)**; no universal percent, time period or automatic credit is invented here.
- Every remedy must bind affected order, reservation, campaign, approved original terms, actor and reason; notify parties of material outcomes.
- An unresolved paid rejection must not be marked `completed/fulfilled` solely because payment was captured; monitor unresolved commitments and expose administrator alerts.
- Any returned money is recorded by adjustment/credit note and provider status, not by deleting or overwriting original payment records.

**Tests:** `BR-004`, `FR-COM-008..010`, `UJ-16/21`, `SCN-REM-*`.

### DP-LAY — Layout version, sold-slot impact and publication (R1)

- `draft → previewed → validated → published` is a controlled process, not a direct mutable public DOM update. Publication creates immutable version and switches the active pointer atomically.
- Cosmetic changes that preserve slot commercial meaning and delivery constraints can proceed with record of review; dimension/position changes must be evaluated against contracted terms, targeting and guaranteed visibility/position.
- Sold/reserved slot deletion, semantic relocation, forced hiding, exclusivity alteration or breaking responsive changes require explicit impact resolution before publish: block, contract amendment, approved migration or advertiser-authorized substitution according to documented policy.
- Rollback is a new, validated publication action and cannot reinstate conflicts with contracts created since the earlier version.
- Heatmap and delivery aggregates retain old layout-version identity. Updating layout version must not relabel historical coordinates.
- High-density preview is bounded; editing 500 slots must not create 500 uncontrolled DOM heavy creative objects.

**Failures:** Show affected slot IDs, campaigns, impacted windows and remediation, without exposing restricted campaign finance data to unauthorized editors. **Tests:** `BR-006/016`, `FR-INV-005/008/009`, `UJ-09/10/22`, `AC-001..003`, `SCN-LAY-*`.

### DP-DEL — Delivery eligibility, selection, fallback and cache freshness (R1)

- Delivery requires fresh positive decisions for creative review, live contract, allocation, schedule, current account status, slot/binding state, targeting and site/global stop flags.
- Cache only safely shareable placement plans and candidate metadata; personalized or region-sensitive decisions require an appropriate privacy/cache partition or runtime evaluation.
- Every logical slot has an ID and each actual page occurrence has a different render-instance ID; measurement joins both without creating duplicate commercial entitlements.
- Supported policies include fixed, eligible rotation, contractual priority, fallback/house ads and scoped contextual matching. Advanced R3 share-of-voice and pacing are separately released.
- A paused/expired/cancelled campaign must not serve new decisions after defined, measured freshness boundaries. Stale creative HTML must not bypass server eligibility for dynamic decisions.
- Rendering failure must not break the page; no placeholder/house creative may fabricate purchased impressions. JavaScript-off limitations must be disclosed and accessible fallbacks maintained.
- Ads approaching viewport are loaded in bounded batches; 500 configured logical slots do not imply 500 immediately rendered/media-loaded advertisements.

**Tests:** `BR-008`, `FR-DEL-001..013`, `INV-01/04/05`, `AC-004/011`, `UJ-13/14`, `SCN-DEL-*`.

### DP-DEN — Advertising density and interaction safety (R1)

- Apply configurable rules by container, viewport, page context and ad network; warnings are not substitutes for hard prohibitions under applicable network/contract policy.
- A logical slot count may exceed the practical active rendering budget. Distinguish logical inventory supported from viewport density policy and rendering capacity.
- Prevent overlays/interactive advertising that create accidental clicks; enforce safe spacing, responsive creative constraints, accessible DOM ordering, and layout stability.
- Limits must not be bypassed by a shortcode override or theme function parameter. A licensed legal/safety prohibition takes precedence over administrator convenience.

**Tests:** `BR-010/016`, `FR-DEL-011`, `FR-UX-006..009`, `SCN-DEN-*`.

### DP-MET — Events, qualification and measurement integrity (R1/R2/R3)

- Preserve separate `requested`, `selected/served`, `rendered`, `measurable`, `viewable`, `observed_click`, `qualified_click`, `conversion_reported`, `billable` types with versioned definitions. R1 does **not** promise R2 viewability collection or R3 metered billing.
- Event ingestion is untrusted; validate max payload, schema version, site/slot/render IDs, timestamp skew, duplicate key, preview/staging status, applicable consent class, rate limits and session-independent validity as feasible.
- Ingestion acknowledgment means the event was accepted for processing, not certified billable. Use idempotent aggregation and explicit data freshness.
- Known bots, editor views, synthetic tests and preview interactions are excluded from production monetary counters. Filtering limits and uncertainty must be documented.
- Qualified click cannot be established solely by an advertiser-controlled redirect URL or a self-reported browser event.
- Raw retention is bounded, analytics aggregates are versioned, and financial ledger authority is separate.

**Tests:** `BR-009/012`, `FR-MET-001..011`, `AC-004/011`, `WF-12/16`, `SCN-MET-*`.

### DP-HEAT — Long-page heatmap aggregation and presentation (R2)

- Collector transmits allowed normalized coordinates, stable semantic slot anchors, page/layout version, viewport class and minimal timing metadata; never infer gaze from hover, cursor movement or visible time.
- Aggregate by page/template, immutable layout version, device breakpoint, zone/container/slot and compatible geometry. Incompatible versions are shown separately or with a documented alignment transform; otherwise exclude with a warning.
- Full-page scroll-based overlays must distinguish observed coverage from unvisited portions; sticky elements and dynamic DOM relocation require explicit coordinate mapping policies.
- Disclose sample size, captured/filtered/unknown coverage, consent sampling, retention limitations and what a heatmap actually estimates.
- Provide accessible numeric ranking/table alternatives and prohibit sensitive DOM/form/payment capture.

**Tests:** `FR-HEAT-001..009/011`, `UJ-19..20`, `SCN-HEAT-*`; no heatmap is labeled as eye tracking.

### DP-REP — Reporting truthfulness and export controls (R1/R2/R3)

- Advertiser-facing dashboards use authorized aggregated metrics only. Publisher roles get scoped inventory/commerce insight; visitor/session/raw restricted data require separate permission and release.
- Every metric must define unit, numerator, denominator, time window, invalid traffic treatment, aggregation lag, time zone, and whether observed/estimated/attributed/forecast.
- Freshness indicator and uncertainty/coverage notes accompany delayed and sampled statistics. Missing/unavailable data is not presented as zero.
- Report exports are server-side scoped, queued for large jobs, and reauthorized on download; expiry and revocation must invalidate previous URLs.
- Forecasts and conversion claims cannot be represented as confirmed sales; third-party offsite conversion events need an authorized integration and explicit attribution policy.

**Tests:** `BR-011/013`, `FR-REP-001..010`, `FR-HEAT-005`, `AT-REP-*`, `UJ-15/19/20/23`, `SCN-REP-*`.

### DP-FIN — Ledger, currencies, metered billing and disputes (R1/R3)

- Maintain a durable, append-only or append-only-equivalent immutable audit history for posted accounting facts. Amendments are adjusting entries with actor, reason, reference to original and timestamp.
- Monetary arithmetic is exact. Define each supported currency's precision/rounding and tax rules in an approved configuration, not in UI presentation logic.
- Reconcile AdPilot order, provider/WooCommerce payment state, inventory reservation, recognized fulfillment and refund/dispute status; report discrepancies without silent correction.
- R1 fixed/time contract total is determined from the accepted immutable quote/contract, subject to explicit amendments; raw impression counts do not change price.
- R3 CPC/CPM billing requires separately verified qualified event definitions, fraud exclusion, thresholds, caps, cutoffs, adjustment model, dispute period and reconciliation test gate. No mere internal QA run should be represented as external certification.
- Do not retain cardholder data or invent tax advice for unknown jurisdiction; provide provider/finance integration boundaries.

**Tests:** `BR-012/014`, `FR-COM-*`, `FR-MET-009..011`, `UJ-16/24`, `SCN-FIN-*`.

### DP-TIM — Time, recurrence, timezone and daylight saving (R1)

- Persist instants in UTC with explicit original selected IANA time zone, local wall-time display, timezone database conversion and contract interpretation.
- Use half-open periods `[start, end)` for overlap tests; an end instant equal to another start is not overlapping.
- On ambiguous DST fallback times, require unambiguous offset/choice; on nonexistent spring-forward wall times, reject or request a valid replacement rather than silently shifting a purchased contract.
- Quote, hold, campaign schedule, reservation and reporting windows must use consistent instants. Store original business time-zone context even when stored UTC values are used operationally.
- When the site's default timezone changes, do not silently rewrite existing contractual UTC instants.

**Tests:** `BR-015`, `FR-COM-014`, `UJ-04/08`, `SCN-TIM-*`.

### DP-PRV — Privacy, consent, data access and replay (R1/R2/R4)

- Separate the minimum processing required for ad delivery from optional behavior recording; evaluate legal basis and consent for each event type/region. The configuration must be documented and independently reviewed.
- Consent withheld or withdrawn blocks prohibited collection without hiding safe core content. Do not record raw form values, passwords, payment fields, sensitive DOM, private messages, or full URL tokens in tracking/replay.
- Honor purpose limitation, access scope, retention, eligible export/deletion and financial-record exceptions; track consent version where justified and minimize identifiers.
- Session replay is R4, opt-in, separately permissioned, sampled, masked at collection time, and independently deletable. A third-party analytics adapter is never mandatory to serve an advertisement.
- Background jobs must enforce retention and visibility restrictions even if legacy data already exists.

**Tests:** `BR-017`, `FR-OPS-004..005`, `FR-HEAT-008/010`, `AC-012`, `UJ-18/26`, `SCN-PRV-*`.

### DP-RET — Retention, purge and evidentiary preservation (R1+)

- Retention windows are typed by data category: operational telemetry, raw measurement events, aggregate metrics, restricted session data, financial audit, creative media and account data.
- The default durations and jurisdictional exceptions are deliberately left to `DEC-006` and the Privacy/Operations specification; do not infer a universally correct number of days.
- Expiry jobs must be idempotent, incremental and observable; purge of raw behavioral data cannot silently erase valid posted ledger entries.
- Erasure requests require authorization and conflict assessment with applicable financial retention duties; where preservation is required, minimize and isolate retained facts.
- Support legal hold only through explicit authorized policy with documented scope/expiry; do not provide an unrestricted hidden retention bypass.

**Tests:** `BR-012`, `FR-OPS-004..006`, `SCN-RET-*`.

### DP-OVR — Exceptional administrator override (R1)

- Define narrow override purposes (e.g., approved paid placement migration, authorized complimentary campaign, emergency stop, remedial reservation amendment), not a universal `force=true` flag.
- Require specific capability, current site context, target contract version, reason, impacted parties, conflict review and audit record; high-risk operations may require second approver once approved policy specifies it.
- An override cannot bypass creative safety, authorization across organizations, payment-provider truth, legal prohibitions, or consent protections.
- Notify affected advertiser or finance personnel when contract consequences require it; provide rollback/reconciliation plan where safe.

**Tests:** `BR-018`, `FR-INV-009`, `FR-OPS-003`, `UJ-10/16`, `SCN-OVR-*`.

### DP-UX — Business-critical experience contract (R1+)

- Whenever a UI proposes a mutation, show the actor exactly what will change, impacted inventory, cost/approval/delivery implications, and whether cancellation/recovery is available.
- Display the separate commercial, moderation and delivery statuses together when relevant; a single `Active` badge must never substitute for all three.
- Buttons represent intent, not confirmed backend state; show pending/error/conflict/retry states and announce results accessibly.
- High-density layouts use summary, pagination and preview, not hundreds of simultaneously mounted form fields; nonvisual editing alternatives must remain available.
- Localization, mobile and RTL must preserve information order and purchase clarity. No color-only indicators, mouse-only editing, or ambiguous currency/time presentations.

**Tests:** `BR-016`, `FR-UX-001..011`, `NFR-009/011/016`, `UXA-*`, `SCN-UX-*`.

---

## 5. State machine conventions and contracts

State machines represent distinct entities. A `Campaign` must **not** have one global status that simultaneously stands for creative approval, order payment, reservation validity and browser delivery. Every transition below is an application-service command, not an unchecked SQL update. Names may be mapped to WooCommerce provider statuses by an adapter, but the mapping itself must be explicit and verified.

### 5.1 Common command/transition envelope

```json
{
  "command_id": "uuid-or-supported-opaque-id",
  "site_id": "site-context",
  "actor_id": "authenticated-actor-or-trusted-system",
  "resource_type": "campaign",
  "resource_id": "opaque-resource-id",
  "expected_version": 7,
  "operation": "submit_for_review",
  "payload": {"creative_version_id": "opaque-version-id"},
  "reason": "optional-human-readable-justification",
  "correlation_id": "workflow-trace-id"
}
```

The JSON is a **proposed internal command shape**, not a public API contract or a claim that IDs can be arbitrary. Authenticated actor and site context must be resolved from trusted server authentication and authorization, not accepted from user-supplied fields. `expected_version` is an optimistic concurrency requirement where appropriate. Every transition checks present state, permissions, invariant guards and idempotency before durable effect.

| Field/behavior | Rule |
|---|---|
| Actor | Resolve authenticated user, trusted worker or provider adapter; never trust client-supplied actor identity |
| Resource version | Reject stale mutation with `409 state_conflict` and current safe state |
| Idempotency | Record `(site, actor/provider, operation, idempotency-key)` outcome for bounded retention and scope |
| Transition result | `applied`, `already_applied`, `denied`, `conflict`, `deferred`, or `needs_reconciliation` |
| Audit | Persist transition owner, UTC timestamp, old/new state, safe reason, related contract/version |
| Side effects | Post-commit outbox / idempotent jobs; never send an irreversible external action before successful DB commit where it can be avoided |
| Forbidden transition | Return a typed denial; never silently reinterpret as a different operation |

### 5.2 SM-QUOTE — Commercial quote (R1)

States: `building`, `issued`, `accepted`, `expired`, `superseded`, `cancelled`.

| From | Command → To | Actor | Guards | Durable effect |
|---|---|---|---|---|
| `building` | `issue` → `issued` | Pricing service | Sellable published inventory, supported price, valid window, approved quote inputs | Freeze quote lines, currency, rate version, terms, expiry |
| `issued` | `accept` → `accepted` | Authorized buyer | Before server expiry; all required terms accepted; availability rechecked at hold transaction | Link buyer, requested hold, acceptance timestamp |
| `issued` | `expire` → `expired` | Clock/worker | Current server time at/after expiry | Mark unusable for new hold |
| `issued` | `supersede` → `superseded` | Pricing service | Price, scope or availability changes requiring requote | Link successor and explanation |
| `issued` | `cancel` → `cancelled` | Owner/admin | No incompatible committed order | Record cancellation |

An accepted quote does **not** itself reserve capacity. If quote acceptance and hold creation are combined, they must be a single atomic application operation with documented result. No mutation of immutable issued quote amounts.

### 5.3 SM-HOLD — Temporary inventory hold (R1)

States: `active`, `committing`, `committed`, `expired`, `released`, `conflicted` (the last is a reconciliation/diagnostic state only; failed transactional attempts should ordinarily create no hold).

| From | Command → To | Guard / effect |
|---|---|---|
| none | `create` → `active` | Valid accepted quote, ownership, atomic interval capacity/exclusivity check, expiry; allocate stable hold ID |
| `active` | `begin_commit` → `committing` | Trusted valid contract/payment outcome, still owned and unexpired; current allocation remains conflict-free |
| `committing` | `commit` → `committed` | Durable reservation/allocation written transactionally; hold consumed once |
| `active` | `release` → `released` | Owner/admin cancel; no committed contract dependent on hold |
| `active` | `expire` → `expired` | Clock has reached `expires_at`; cleanup is idempotent |
| `active` | `renew` → `active` (new expiry/version) | Explicit authorized recheck of capacity, policy and bounded allowed extension |
| `committing` | `repair` → `committed` or `released` | Reconcile against authoritative reservation/order, no guessed success |

Late payment for `expired`/`released` inventory must not reopen this state machine. Handle through separate order reconciliation and remediation without taking capacity already allocated to someone else.

### 5.4 SM-RESERVATION — Contracted inventory (R1)

States: `planned`, `committed`, `active`, `fulfilled`, `cancel_pending`, `cancelled`, `amendment_pending`.

| From | To | Preconditions | Important consequences |
|---|---|---|---|
| `planned` | `committed` | Paid/authorized contract, valid hold/capacity, exact UTC range | Durable bookable unit and allocation record |
| `committed` | `active` | Start window reached; no cancellation; creative approval separately needed for serving | Inventory occupied, not proof ad displayed |
| `committed`/`active` | `amendment_pending` | Requested scope/position/date alteration | Freeze or constrain affected mutation pending review |
| `amendment_pending` | prior state or revised committed record | Approved change, same exclusivity transaction | New amendment version, preserved original terms |
| `committed`/`active` | `cancel_pending` | Valid cancellation/refund policy and authorized request | Delivery gating updated as specified; billing settlement may lag |
| `cancel_pending` | `cancelled` | Remediation completed or explicit governed cancellation | Release future allocation; preserve past delivery/audit |
| `active` | `fulfilled` | Contract period/delivery obligations complete | Reconcile obligations; never equate elapsed time with all obligations if guarantees exist |

Reservation and allocation are related but not identical: the reservation records the inventory commitment; allocation governs which campaign is eligible to consume it. Unpaid manual ads cannot take sold inventory.

### 5.5 SM-ORDER — AdPilot commercial order (R1)

States: `draft`, `quoted`, `pending_payment`, `payment_confirmed`, `payment_failed`, `payment_unknown`, `remediation_required`, `partially_refunded`, `refunded`, `cancelled`, `fulfilled`, `disputed`.

| From | Command → To | Preconditions / effect |
|---|---|---|
| `draft` | `attach_quote` → `quoted` | Authorized buyer, immutable valid quote ID |
| `quoted` | `initiate_checkout` → `pending_payment` | Current eligible hold, checkout provider ready, idempotent linked Woo order |
| `pending_payment` | `reconcile_paid` → `payment_confirmed` | Provider/WooCommerce authoritative confirmation; reconcile hold and contract separately |
| `pending_payment` | `reconcile_failed` → `payment_failed` | Authoritative failure; release/expire hold per policy |
| any active unresolved | `mark_uncertain` → `payment_unknown` | Delayed/out-of-order/conflicting provider evidence; no optimistic activation |
| `payment_confirmed` | `inventory_missing` → `remediation_required` | Hold expired or no legal allocation; initiate refund/alternative workflow |
| `payment_confirmed` | `mark_fulfilled` → `fulfilled` | Actual contract fulfillment criteria and ledger reconciliation satisfied |
| `payment_confirmed`/`fulfilled` | `refund_part` → `partially_refunded` | Approved refund terms, verified provider event and adjustment ledger |
| eligible paid states | `refund_all` → `refunded` | Verified total refund, ledger adjustments, delivery/contract implications resolved |
| eligible states | `open_dispute` → `disputed` | Authorized dispute or chargeback; preserve prior state/records |
| non-settled terminal candidates | `cancel` → `cancelled` | No active incompatible obligation; policy checked |

This is AdPilot's commercial abstraction; do not map every WooCommerce order status directly onto it without an approved adapter table. A dispute is an orthogonal case record if the implementation needs to preserve the financial-state lineage; avoid destroying history by overwriting the only status.

### 5.6 SM-PAYMENT — Payment attempt and provider evidence (R1)

States: `created`, `submitted`, `authorized`, `captured`, `settled`, `failed`, `cancelled`, `refunded`, `reversed`, `unknown`. Implement only distinctions that the chosen provider reliably exposes.

| Transition | Allowed evidence | Required action |
|---|---|---|
| `created → submitted` | Valid adapter checkout initiation | Persist attempt and provider reference |
| `submitted → authorized` | Verified auth event | Record authorization, not final settlement |
| `submitted/authorized → captured` | Verified capture/paid event | Reconcile commercial state, idempotency and inventory |
| `captured → settled` | Provider-supported settlement evidence | Mark settlement if supported; no fabricated assumption |
| pending → `failed/cancelled` | Verified rejection/cancel and no contradictory settled evidence | Update attempt, trigger hold remediation/release |
| paid → `refunded/reversed` | Verified provider refund/reversal | Adjust ledger and enforce campaign/contract policy |
| unresolved → `unknown` | Provider evidence missing or contradictory | Queue reconciliation; show unresolved state |

For an out-of-order `failed` arriving after `captured`, do not roll the attempt back to failed based on event arrival order. Compare provider transaction IDs, effective status, verified event version/timestamp and documented provider semantics; reconcile ambiguous cases.

### 5.7 SM-CREATIVE — Versioned moderation (R1)

States per **version**: `draft`, `submitted`, `under_review`, `changes_requested`, `approved`, `rejected`, `suspended`, `superseded`.

| From | Command → To | Preconditions / actions |
|---|---|---|
| `draft` | `submit` → `submitted` | Owner and safe media schema; freeze new version |
| `submitted` | `start_review` → `under_review` | Authorized moderator, version claim |
| `under_review` | `approve` → `approved` | Complete policy validation, version/hash match, review audit |
| `under_review` | `request_changes` → `changes_requested` | Explicit actionable reason; new version for material edits |
| `under_review` | `reject` → `rejected` | Recorded reason/policy, associated remedy case if paid |
| `approved` | `suspend` → `suspended` | Security/publisher emergency action, invalidate serving eligibility |
| `approved` | `supersede` → `superseded` | New approved revision selected by policy; old record retained |
| `changes_requested/rejected` | `revise` → new `draft` version | Original version remains immutable and nonserving |

A campaign can reference multiple creative versions, but each serving decision must resolve to an explicitly approved eligible version. Re-approval of one version must not approve a different destination automatically.

### 5.8 SM-CAMPAIGN — Campaign submission/review (R1)

States: `draft`, `submitted`, `under_review`, `changes_requested`, `approved`, `rejected`, `withdrawn`, `suspended`, `archived`.

| From | Command → To | Preconditions / effect |
|---|---|---|
| `draft` | `submit` → `submitted` | Owner, complete required config, valid creative references and inventory request |
| `submitted` | `start_review` → `under_review` | Authorized moderator and current version |
| `under_review` | `approve` → `approved` | Campaign policy and current creative version valid; no implied payment success |
| `under_review` | `request_changes` → `changes_requested` | Document revision requirement; notify actor |
| `under_review` | `reject` → `rejected` | Explain rejection; resolve any paid commercial case |
| `changes_requested` | `resubmit` → `submitted` | Updated immutable revision; validation |
| `draft/submitted` | `withdraw` → `withdrawn` | Owner within permitted contract state; independent booking remedy if needed |
| `approved` | `suspend` → `suspended` | Authorized intervention; delivery denial |
| terminal / obsolete | `archive` → `archived` | No unsettled obligations whose visibility would be hidden |

Approved campaign state does not imply all current/future creative versions are approved. Delivery also requires `SM-DELIVERY` gates.

### 5.9 SM-DELIVERY — Campaign serving (R1)

States: `not_ready`, `scheduled`, `active`, `paused`, `suspended`, `completed`, `expired`, `cancelled`.

| From | Command → To | Mandatory decision |
|---|---|---|
| `not_ready` | `schedule` → `scheduled` | Approved configuration, valid future window, eligible committed contract and allocation |
| `not_ready/scheduled` | `activate` → `active` | Server-authoritative gate checks all pass at current instant |
| `active` | `pause` → `paused` | Authorized owner/AdOps, reason and appropriate contract treatment |
| `paused` | `resume` → `active` | All eligibility gates rechecked; budget/time/approval not assumed unchanged |
| any nonterminal | `emergency_stop` → `suspended` | Privileged safety action with immediate deny intent and audit |
| `suspended` | `clear_suspension` → `not_ready` | Independent permission and full re-evaluation; no direct automatic activation |
| `active/paused/scheduled` | `complete` → `completed` | Approved fulfillment completion and reconciliation; no fictitious impressions |
| any active/scheduled | `expire` → `expired` | Authoritative contract/schedule window ended |
| eligible nonterminal | `cancel` → `cancelled` | Contract and remedy conditions satisfied |

`active` is a result of eligibility, not a substitute for checking eligibility at each decision. A queued activation job might arrive late; it must re-evaluate the present state and time and must not activate an expired or suspended campaign.

### 5.10 SM-LAYOUT — Layout revision and publication (R1)

States: `draft`, `previewed`, `validated`, `conflict_blocked`, `published`, `superseded`, `archived`.

| From | Command → To | Preconditions / effect |
|---|---|---|
| none / prior published | `create_draft` → `draft` | Author with appropriate layout permission; branch from immutable version |
| `draft` | `preview` → `previewed` | Validate structure for editor; no production impression events |
| `draft/previewed` | `validate` → `validated` | Inventory, active contracts, responsive constraints, bindings, accessibility and density checks |
| `draft/previewed` | `detect_breaking_conflict` → `conflict_blocked` | Persist or return impacted contracts and resolution requirements |
| `conflict_blocked` | `resolve_and_revalidate` → `validated` | Approved amendments/migrations and fresh impact comparison |
| `validated` | `publish` → `published` | Current active base version matches; atomic version swap plus audit |
| prior `published` | publish successor → `superseded` | Old version retained for historical analytics |
| obsolete safe version | `archive` → `archived` | No required historical references lost |

Publishing stale `validated` state after a new reservation was created must revalidate under transactional/consistency control. Rolling back creates a new publish candidate, not a mutable edit to an old historical version.

### 5.11 SM-ALLOCATION — Ad delivery share and reservation relationship (R1/R3)

States: `proposed`, `allocated`, `active`, `paused`, `released`, `expired`, `disputed`.

- `proposed → allocated`: authorized committed reservation, policy-compatible campaign, site/slot/window match.
- `allocated → active`: eligible active window; delivery engine still checks campaign+creative every decision.
- `active → paused`: service/account/contract pause; retain or relinquish capacity according to approved terms.
- `allocated/active/paused → released`: contract cancellation/amendment and reservation reconciliation.
- `allocated/active → expired`: effective end instant passed; prevent subsequent eligibility.
- `active → disputed`: disputed fulfillment/billing; apply explicitly approved temporary serving rules, not guessed blanket cessation.
- R3 weighted shares/guarantees require allocation capacity proofs and pacing rules absent from R1 fixed inventory.

### 5.12 SM-REMEDY — Paid rejection, late payment or inventory mismatch (R1)

States: `opened`, `awaiting_customer`, `awaiting_operator`, `revision_in_progress`, `alternative_proposed`, `refund_pending`, `resolved`, `cancelled`, `escalated`.

| Trigger | Permitted next action | Mandatory evidence |
|---|---|---|
| Paid ad rejected | Offer correction within policy; approved refund/cancel path | Original contract, specific rejected creative, notification |
| Payment after hold expiry | Requote/rebook alternate inventory or refund per terms | Provider-confirmed payment and inventory conflict proof |
| Partially delivered campaign suspended | Evaluate unfulfilled obligation and approved remedy | Measured delivery, contract time, remaining allocation |
| Wrong purchased placement published | Freeze harmful allocation change; propose compliant migration | Layout versions and affected slot/campaign contract |
| Provider refund delayed | Keep `refund_pending`, retry/reconcile rather than claim refunded | Verified provider state, adjustment reference |

Only authorized actors can close a remedy and closing requires a reason and evidence. A financial remedy does not magically change moderation status or erase prior delivery.

### 5.13 SM-DISPUTE — Financial or delivery dispute (R3 formal workflow; R1 basic handling)

States: `opened`, `evidence_collecting`, `under_review`, `adjustment_proposed`, `accepted`, `rejected`, `closed`, `reopened` (when governed by policy).

- Only authorized advertiser billing users, publisher finance users or support roles with explicit delegated access can open/review cases.
- Preserve contract, rate version, status history, billable-event aggregate and provider transaction references. Restricted raw visitor data must not be exported to advertisers.
- Adjustment creates a new ledger line, never edits/replaces previously posted authoritative entries.
- Review completion records reviewer, policy, decision, notified parties and dispute window.
- For R1 fixed/time sales, dispute may be administrative; R3 event-by-event reconciliation only becomes available with verified metered billing.

### 5.14 SM-EXPORT — Report export job (R2)

States: `requested`, `queued`, `running`, `ready`, `failed`, `expired`, `revoked`.

- `request → queued`: capability, ownership, permitted fields, bounded date/filter scope; snapshot safe query intent.
- `queued → running → ready`: worker aggregates eligible records, stores protected artifact and expiry metadata.
- `running → failed`: sanitized error; retry idempotently with bounded attempts.
- `ready → expired`: TTL elapses; underlying export artifact cleaned up.
- `ready → revoked`: membership revocation, changed access scope or administrator action.
- **Always re-check authorization on download**, regardless of the earlier report-request authorization. Do not expose direct public filesystem paths or transferable permanent download URLs.

### 5.15 SM-CONSENT/REPLAY — Purpose-specific tracking and optional replay (R2/R4)

A single global boolean cannot represent all lawful purpose/region combinations. Define purpose-scoped consent decisions: `unknown`, `granted`, `denied`, `withdrawn`, `not_required_under_approved_policy`; version the policy and effective time. The last status is allowed only after jurisdiction-specific assessment and must not be assumed universally.

- Heatmap browser instrumentation (R2) starts only when the chosen policy permits the specific event purpose.
- R4 replay service lifecycle: `disabled → configuration_draft → redaction_test_passed → enabled → disabled/emergency_stopped`; consent remains an **independent visitor-level gate** for each recording opportunity.
- Denial or withdrawal prevents prohibited further collection immediately upon detection, and retention/deletion rules address stored data.
- Recording cannot be enabled before synthetic sensitive-field tests confirm masking and exclusion at collection time; privacy validation is an explicit release gate, not one unchecked toggle.

### 5.16 SM-JOB — Background work and reconciliation (R1)

States: `queued`, `claimed`, `running`, `succeeded`, `retry_wait`, `dead_letter`, `cancelled`.

- Worker claim must be concurrency-safe and carry a bounded lease/heartbeat where supported.
- Every job has stable idempotency key, attempt limit, payload schema and safe redacted failure reason.
- On retriable failure: backoff and retry; on stale current-state precondition: harmless no-op or explicit reconciliation result.
- Dead-letter state requires admin diagnostics and authorized replay; never silently discard a financial discrepancy.
- A job's `succeeded` means its own postconditions are durable, not that an external gateway settlement has been fabricated.

### 5.17 Orthogonal state composition: authoritative readiness predicate

```text
canServe(site, renderContext, slot, campaign, creativeVersion, now) =
    site.active && !site.globalEmergencyStop
 && advertiser.active && !campaign.suspended
 && campaign.approval == approved
 && creativeVersion.approval == approved
 && contract.validFor(now) && contract.financiallyAuthorized
 && reservation.committedFor(slot, now)
 && allocation.permits(campaign, slot, now)
 && slotBinding.publishedAndEligible
 && targeting.permits(renderContext)
 && privacyAndAdDensityPolicy.permitsDelivery(renderContext)
 && supportedCreativeRenderer(creativeVersion)
 && campaignDeliveryLifecycle.permitsServingAt(now)
```

`canServe` is conceptual pseudocode. It must return a structured denial reason and current eligibility expiry/freshness constraints, not simply a truthy cached value. Manual/complimentary contracts satisfy `contract.financiallyAuthorized` through an explicit audited *nonmonetary authorization*, not a fake paid order. The browser may be unable to measure viewability; that does not itself prove or disprove whether the creative was eligible to serve.

---

## 6. Authorization architecture and permission scenarios

This section specifies **business authorization behavior**. The exact WordPress capability names and database representation will be finalized in the dedicated Permission Model document, but the following restrictions are binding now. Capabilities are never implied merely by a page being accessible.

### 6.1 Actors and scoping boundaries

| Actor / business role | Personas in ADP-DOC-003 | Legal scope and common needs |
|---|---|---|
| Site Administrator | `PERS-01`, `PERS-13` | Site policy, delegated permissions, advertising operations and diagnostic controls; financial overrides separately audited |
| Network Super Administrator (R4 network) | `PERS-13` | Network settings where implemented; no accidental bypass of site-specific privacy/contract policy |
| Ad Operations Manager | `PERS-02` | Inventory, booking, schedules, campaign delivery and safe operational changes |
| Creative Moderator | `PERS-08` | Creative review, policy flags, emergency suspension where granted; no invoice/finance editing |
| Publisher Finance Manager | `PERS-09` | Orders, settlements, credits, refunds, disputes, immutable adjustment workflow |
| Publisher Analytics Manager | `PERS-10` | Aggregated reports/heatmaps; raw data and R4 replay require distinct elevated grants |
| WordPress Content Editor | `PERS-11` | Insert approved blocks and bind eligible zones into permitted content; no ability to force paid allocation |
| Theme/Integration Developer | `PERS-12` | Documented PHP/theme interfaces in trusted code; no special access from shortcode parameters |
| Advertiser Organization Owner | `PERS-05` (and `PERS-03/04` where assigned) | Manage own organization and delegated campaign/billing users |
| Advertiser Campaign Manager | `PERS-04` | Own organization's campaign drafts, creative submissions and approved operational actions |
| Advertiser Analyst | `PERS-06` | Only authorized aggregate campaign metrics and exports |
| Advertiser Billing Specialist | `PERS-07` | Own organization's invoices, payment statuses and dispute requests; no campaign moderation |
| Public prospective buyer | `PERS-14` | Read public published catalog only |
| Site visitor | `PERS-15` | View eligible ad content and submit permitted minimal measurement signals |
| Support / Incident Operator | `PERS-16` | Redacted diagnostics and authorized delegated assistance; no implicit impersonation or raw replay |

One real person may hold multiple roles. The effective permission is the union of authorized capabilities **bounded by every applicable site/organization/resource restriction**; a role combination does not remove a mandatory scope guard. A WordPress site editor role never becomes an AdPilot finance role just because they can edit a page.

### 6.2 Capability namespace (proposed, subject to dedicated permission document)

| Capability key | Protected operation | Scope |
|---|---|---|
| `adpilot_manage_settings` | Update site policies/integrations | Site |
| `adpilot_manage_roles` | Delegate site-specific roles | Site, nonescalating |
| `adpilot_manage_inventory` | Create/archive zones, containers and slots | Site |
| `adpilot_publish_layout` | Publish/rollback validated layout versions | Site + contract conflict gate |
| `adpilot_override_allocation` | Perform exceptional paid-slot change | Site + reason + contract policy |
| `adpilot_manage_rates` | Create/version rate cards | Site |
| `adpilot_view_campaigns` | View appropriately scoped campaigns | Site or organization |
| `adpilot_manage_campaigns` | Change own/authorized campaign config | Site or organization |
| `adpilot_moderate_creatives` | Approve/reject specific creative versions | Site |
| `adpilot_emergency_stop` | Suspend unsafe campaign/creative | Site + audit |
| `adpilot_view_finance` | See order and settlement data | Site or organization |
| `adpilot_adjust_finance` | Post refund/ledger adjustments | Site + dual control if policy requires |
| `adpilot_view_aggregate_analytics` | Query permitted aggregate metrics | Site or organization |
| `adpilot_export_reports` | Request and download scoped aggregates | Site or organization, recheck on download |
| `adpilot_view_restricted_analytics` | View restricted telemetry | Site + explicit data policy |
| `adpilot_view_replay` | View R4 session replay | Site + separately enabled permission |
| `adpilot_manage_privacy` | Change retention/consent integration | Site + audit |
| `adpilot_view_diagnostics` | View redacted operational health | Site |
| `adpilot_retry_jobs` | Requeue eligible safe jobs | Site + incident context |
| `adpilot_manage_advertiser_members` | Invite/revoke own members | Organization (or delegated site) |
| `adpilot_purchase_inventory` | Create quotes/holds/checkout for own org | Organization |
| `adpilot_insert_blocks` | Insert/configure approved inventory references | Editor + allowed content and zone scope |

Actual implementation may split capabilities further; it may **not** combine sensitive grants into an indiscriminate `adpilot_admin_all` permission for every operator. Multisite scopes must be explicit when R4 arrives.

### 6.3 Role-to-operation decision matrix

Legend: `✓` = may be granted by role subject to all resource checks; `OWN` = own organization only; `PUB` = public; `—` = no default grant; `ELEV` = separately elevated capability and release; `COND` = restricted explicit delegation. Matrix describes proposed defaults; final role names and mappings will be frozen in Document 05.

| Operation | Site Admin | AdOps | Moderator | Finance | Analytics | Content Editor | Adv Owner | Adv Manager | Adv Analyst | Adv Billing | Support | Public |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Browse published inventory | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | PUB | PUB | PUB | PUB | PUB | PUB |
| Create/modify zone | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — |
| Publish sold layout | ✓ | COND | — | — | — | — | — | — | — | — | — | — |
| Force contract-impact override | ELEV | ELEV | — | — | — | — | — | — | — | — | — | — |
| Change rate card | ✓ | COND | — | ✓ | — | — | — | — | — | — | — | — |
| Create campaign draft | ✓ | COND | — | — | — | — | OWN | OWN | — | — | — | — |
| Purchase/hold inventory | ✓ | COND | — | — | — | — | OWN | OWN | — | COND | — | — |
| Approve creative version | ✓ | — | ✓ | — | — | — | — | — | — | — | — | — |
| Emergency stop | ✓ | COND | COND | — | — | — | — | — | — | — | COND | — |
| View own organization reports | ✓ | COND | — | COND | ✓ | — | OWN | OWN | OWN | OWN | COND | — |
| View all publisher revenue | ✓ | COND | — | ✓ | COND | — | — | — | — | — | — | — |
| Approve refund/adjustment | ELEV | — | — | ✓ | — | — | — | — | — | — | — | — |
| Insert permitted Gutenberg zone | ✓ | ✓ | — | — | — | ✓ | — | — | — | — | — | — |
| View aggregate heatmaps (R2) | ✓ | COND | — | — | ✓ | — | — | — | — | — | — | — |
| View replay (R4) | ELEV | — | — | — | ELEV | — | — | — | — | — | — | — |
| Export restricted raw events | ELEV | — | — | — | ELEV | — | — | — | — | — | — | — |
| Manage tracking/privacy settings | ✓ | — | — | — | COND | — | — | — | — | — | — | — |
| View redacted system diagnostics | ✓ | COND | — | — | — | — | — | — | — | — | ✓ | — |
| Retry failed financial job | ELEV | — | — | COND | — | — | — | — | — | — | COND | — |
| Change advertiser team | ✓ | — | — | — | — | — | OWN | — | — | — | — | — |

### 6.4 Object-level authorization predicates

```text
authorize(command, actor, resource):
  identity = trustedWordPressIdentity(actor)
  if command.requiresLogin and identity is anonymous: DENY_UNAUTHENTICATED
  if !effectiveSiteCapability(identity, command.capability): DENY_CAPABILITY
  if resource.site_id != currentValidatedSite(): DENY_SCOPE
  if resource.organization_id exists:
      require activeOrganizationMembership(identity, organization_id)
      require membershipOperationGrant(identity, command.operation)
      require organization not suspended
  if resource is child object:
      require parent ownership and parent state are also valid
  if command touches protected data:
      require dataClassPermission, purpose, and allowed export/download context
  if command touches financial/sold inventory override:
      require reason, current contract version, and policy-specific approval
  return PERMITTED
```

This is a conceptual policy outline: site administrators may have site-scoped permissions to perform publisher operations even when a campaign is owned by an advertiser organization; implementations must use an explicit privileged scope rather than applying advertiser membership checks blindly to every site-admin operation. Generic CRUD controllers must use *the same* predicates.

### 6.5 High-risk permission scenarios (`PERM-*`)

| Scenario | Expected authorization decision and evidence |
|---|---|
| `PERM-001` Foreign campaign ID in advertiser REST route | Deny with nonenumerable error; neither data nor timing-sensitive details exposed |
| `PERM-002` Revoked team member using old open tab | Current membership check denies save/export; show nonleaking explanation |
| `PERM-003` Billing member attempts creative approval | Deny independent of organization ownership |
| `PERM-004` Advertiser manager attempts add owner role | Deny privilege escalation; owner notification/audit where appropriate |
| `PERM-005` Content editor shortcode supplies `force=true` | Ignore/reject prohibited override; regular eligibility applies |
| `PERM-006` Moderator changes order settlement | Deny finance mutation despite moderation capability |
| `PERM-007` Analytics manager opens replay without elevated R4 grant | Deny independently of ordinary heatmap access |
| `PERM-008` Site administrator accesses another network site by guessed record ID | Deny absent explicit valid network-scoped permission |
| `PERM-009` Export completed before user's revocation | Download still denied after revocation; prior URL invalidated |
| `PERM-010` Support operator requests raw session by ticket number | Deny unless separate tightly scoped elevated access granted by approved workflow |
| `PERM-011` API replay with valid nonce but insufficient capability | Deny; nonce cannot grant authority |
| `PERM-012` Anonymous catalog search includes internal finance query params | Whitelist permitted filters; no internal data returned |
| `PERM-013` Site administrator overrides sold slot without reason | Deny mutation and register failed privileged attempt safely |
| `PERM-014` Trusted worker retries state change after actor revocation | Check approved system authority and job provenance; do not allow deleted user grant to bypass policy |
| `PERM-015` Developer invokes PHP theme render method | Eligibility still checked; PHP code execution privileges are governed separately by WordPress hosting security |
| `PERM-016` An advertiser tries to infer another org via report filters | Scope filter enforced in query service and SQL, including aggregates |

---

## 7. Concurrency, idempotency and consistency specifications

### 7.1 Transaction boundary model

Operations that can conflict—exclusive reservation, contract allocation, quote-to-hold conversion, financially consequential state changes and layout publication affecting sold inventory—require explicit consistency boundaries.

**Proposed R1 database strategy (subject to migration/schema testing):** InnoDB transactions using a stable `inventory_capacity_lock`/resource row per site and inventory unit or an equivalent proven serializable scheme. Acquire row locks in canonical order, read effective unexpired holds and committed allocations for overlapping `[start,end)` intervals, validate capacity, then write hold/allocation and audit event before commit. Locking by slot alone is safe but may serialize broad purchase traffic; refine granularity after correct baseline behavior is proven. Do **not** rely on MySQL's ordinary unique indexes alone to enforce arbitrary interval-overlap exclusion.

| Operation | Consistency guarantee | Idempotency scope | Recovery |
|---|---|---|---|
| Generate quote | Valid immutable pricing snapshot | Quote request deduplication optional | Requote if stale |
| Create exclusive hold | Exactly one winning conflicting transaction | Site/org/operation/client request key | Failed loser returns fresh availability |
| Commit reservation | At most one conversion of hold into allocation | Hold ID + contract/payment reference | Repair stale `committing` hold |
| Create Woo checkout | No duplicate AdPilot→Woo order mapping | Commercial order + checkout attempt | Look up existing linked order |
| Handle provider callback | At-most-once effect, even if delivered at least once | Provider event ID + payment attempt | Replay/reconcile |
| Approve creative | Compare-and-swap exact version | Creative version + decision command | Stale reviewer gets conflict |
| Publish layout | Atomic active version swap after contract impact check | Layout draft revision/command ID | Revalidate against updated inventory |
| Activate campaign | State/eligibility rechecked at processing time | Campaign + activation window/version | Retry as no-op if already active/expired |
| Ingest measurement | Bounded deduplication, at-least-once safe aggregates | Event ID + contract/source scope | Duplicate acknowledged as not additionally counted |
| Post ledger adjustment | Immutable balanced effect and unique reference | Originating contract/adjustment operation | Reconciliation workflow |
| Export report | Scoped immutable query request; download reauthorized | Export request ID | Retry or revoke artifact |

### 7.2 Idempotency rules

- A client retry of the *same command* with the same idempotency key and identical canonical payload returns the stored result (or current safe processing status), not a second mutation.
- Reusing the key with a materially different payload produces `409 idempotency_key_reused`.
- Keys are scoped to site, authenticated principal/provider, operation, and bounded lifecycle; credentials are not derived solely from user-controlled IDs.
- Payment and ledger keys have retention appropriate to long-lived financial obligations, longer than short-lived browser event deduplication.
- A duplicate campaign activation does not count as a second activation or produce duplicate notification/invoice.

### 7.3 Clock, lock and cache correctness

- Use authoritative server/database UTC instants for expiry checks; do not trust JavaScript countdown accuracy.
- For multi-row locks, acquire in a stable canonical order to reduce deadlocks. On retryable deadlock, roll back and retry the **entire** transaction within a bounded policy, never retry only the final insert.
- Cache invalidation may be eventually consistent for noncommercial previews; high-risk serving decisions must have a documented bounded freshness period and emergency-deny mechanism.
- Concurrency tests must execute against the actual supported MySQL/MariaDB engines, not only an SQLite/in-memory mock.
- If database transactional capability is unavailable or unsafe, disable the affected paid booking path with explicit diagnostics; never silently use a nontransactional fallthrough for exclusive sales.

### 7.4 Cross-boundary process: successful purchase

```text
Buyer -> QuoteService: request quote
QuoteService -> InventoryPolicy: validate current sellability
QuoteService -> Buyer: issued quote + expiry + terms
Buyer -> ReservationService: hold accepted quote (idempotency key)
ReservationService -> DB transaction: lock, capacity proof, write active hold
ReservationService -> Buyer: confirmed hold + server expiry
Buyer -> WooCommerceAdapter: initiate linked checkout
WooCommerceAdapter -> AdPilotOrder: durable mapping, pending_payment
WooCommerce/provider -> Adapter: verified paid callback (may retry)
Adapter -> PaymentReconciler: idempotent provider evidence
PaymentReconciler -> ReservationService: commit valid owned hold, or open remedy
ReservationService -> Contract: authorized commercial commitment
Moderator -> CreativeVersion: independently approve or reject
Scheduler -> DeliveryService: re-evaluate all serve gates
DeliveryService -> Browser: eligible creative or safe fallback
```

If the payment callback arrives after hold expiry, inventory conversion **must fail closed** and `SM-REMEDY` starts; neither capture acknowledgement nor browser success may reserve someone else's slot retroactively.

### 7.5 Cross-boundary process: sold layout migration

```text
AdOps -> LayoutService: edit working draft based on published v12
LayoutService -> ContractImpactService: assess all active/sold intervals and semantic changes
ContractImpactService -> AdOps: compatible OR blocking contract list + permitted remedy options
AdOps/Finance -> AmendmentService: obtain explicit authorized remediation for each affected contract
LayoutService -> DB transaction: recheck current published version, reservations and amendments
LayoutService -> LayoutRepository: publish immutable v13 and atomically update active pointer
LayoutService -> DomainEvent/Outbox: cache invalidation + diagnostics
AnalyticsService -> Reports: retain v12 geometry links and v13 new geometry
```

A layout change started before a new paid booking cannot publish using an old conflict check; detect the changed reservation version and re-evaluate.

### 7.6 Cross-boundary process: creative change after approval

1. Approved version `V1` remains immutable.
2. Advertiser changes image or destination; create `V2` draft.
3. `V2` undergoes upload/policy verification and independent moderation.
4. Until approved, `V2` is ineligible. `V1` may remain active only if contractual and safety policy allows.
5. After `V2` approval, campaign selection changes through a versioned, audited command; pending browser rendering uses updated eligibility.
6. Rejecting/suspending `V2` never converts a prior rejected version to approved and never bypasses the contract remedy workflow.

### 7.7 Cross-boundary process: consent withdrawal

1. Client receives an applicable updated purpose-scoped consent decision.
2. Stop prohibited future event collection or replay immediately (to the extent browser state permits).
3. Clear untransmitted prohibited buffered events and avoid further restricted cookies/identifiers.
4. Report privacy-safe state to integration SDK where configured, without leaking personal consent details publicly.
5. Evaluate existing stored restricted data under retention/erasure policies; do not infer universal deletion duties independent of applicable law.
6. Keep safe baseline content/ad delivery functional where permissible; no fictional analytics zeros are generated.

---

## 8. Policy decision tables (must be reflected in automated tests)

### 8.1 Can this campaign serve now? (R1)

| Approved creative | Valid commercial authorization | Active time + allocation | Publisher/advertiser not suspended | Context policy passes | Decision |
|---|---|---|---|---|---|
| No | Any | Any | Any | Any | **DENY** unapproved creative |
| Yes | No | Any | Any | Any | **DENY** no valid contract |
| Yes | Yes | No | Any | Any | **DENY** out of schedule/unallocated |
| Yes | Yes | Yes | No | Any | **DENY** suspended |
| Yes | Yes | Yes | Yes | No | **DENY** targeting/density/consent-policy condition |
| Yes | Yes | Yes | Yes | Yes | **ELIGIBLE**, subject to current selection and safe renderer |

These conditions are **necessary** rather than universally sufficient: global stop, creative renderer safety, exclusive conflicts and other applicable policies also must pass. A `DENY` result may select a permitted fallback; no paid impression is manufactured for the denied creative.

### 8.2 Capacity and payment timing (R1)

| Hold state | Payment evidence | Competing commitment | Decision |
|---|---|---|---|
| Active, unexpired | Unpaid/pending | None | Keep bounded hold; do not activate |
| Active, unexpired | Verified paid | None | Commit once atomically; begin separate approval gate |
| Active, unexpired | Duplicate paid callback | None | Return already-processed result; no second contract |
| Expired | No payment | Any | Release capacity; require new quote/hold |
| Expired | Verified paid | None or other booking | Remediation/requote/refund; **no implicit revival** |
| Released/cancelled | Verified paid | Any | Remediation; preserve provider truth and ledger |
| Any | Browser redirect claims paid only | Any | Ignore claim; await authoritative provider status |

### 8.3 Creative moderation and financial outcome (R1)

| Commercial state | Creative review state | Permitted behavior |
|---|---|---|
| Pending payment | Approved | Show `approved, awaiting payment`, not serve |
| Confirmed payment | Pending review | Show `paid, awaiting review`, not serve |
| Confirmed payment | Rejected | Open governed remedy; no serving |
| Confirmed payment | Approved | Candidate for schedule/allocation; not automatically viewable |
| Complimentary/manual authorized | Approved | May serve when remaining gates pass |
| Refunded/cancelled contract | Approved | Do not serve under cancelled entitlement |
| Confirmed payment | New unapproved creative version | Continue prior approved version only if policy permits |

### 8.4 Layout impact classification (R1)

| Change | Normally safe? | Required control |
|---|---|---|
| Change display label without changing sold meaning | Potentially | Validate ID stability and historical references |
| Adjust gap with no dimensional/position violation | Potentially | Responsive preview + accessibility/density check |
| Reorder sold premium slot to lower-visibility location | No | Contract impact assessment and approved remedy |
| Delete sold slot or disable binding | No | Block until amendment/cancellation/migration |
| Increase grid slots without altering existing IDs | Often | Unique stable sequence ID proof + capacity and UI test |
| Change four columns to one on mobile | Context-dependent | Contract terms, responsive position, viewport and accessibility assessment |
| Roll back to old snapshot | Not automatically | Treat as fresh publication with all current reservations checked |
| Duplicate zone through theme and block | Not automatically | Unique render identity; explicit repeat contract semantics |

### 8.5 Event qualification, consent and billing (R1/R2/R3)

| Environment / event | Tracking result | Billing result |
|---|---|---|
| Editor preview / staging / synthetic fixture | Exclude from production totals | Always nonbillable |
| Real browser render, ordinary R1 measurements | Validate permitted event; aggregate if accepted | No R3 CPM/CPC posting |
| R2 heatmap signal without required consent | Suppress prohibited collection | No charge derived from suppressed event |
| R2 viewability measured but not qualifying for contract | Report under defined metrics | No paid viewability-derived charge unless R3 contract permits and qualifies |
| R3 qualified metered event with authenticated contract | Reconcile eligibility, thresholds, fraud and dedupe | Eligible for ledger posting only through billable event processor |
| Browser event forged/replayed | Reject/quarantine or deduplicate | Never directly posted as final billable amount |
| Event with unknown geometry version | Report missing/unmappable coverage | Cannot be invented as a verified heatmap exposure |

---

## 9. Error taxonomy, recovery and UX effects

A stable error-code contract is more important than a particular HTTP transport response in an internal PHP service. REST adapters should map the codes to suitable HTTP statuses and redact confidential details. Suggested REST resource routes will be finalized in the API specification.

| Error code | Typical HTTP | Explanation | UX handling and recovery |
|---|---:|---|---|
| `authentication_required` | 401 | No valid principal | Preserve draft, request login, then revalidate |
| `scope_denied` | 403/404 | Resource not within granted scope | Do not reveal foreign resource; show authorization failure |
| `capability_denied` | 403 | Insufficient operation grant | Disable prohibited action and provide role-based guidance |
| `account_suspended` | 403 | Advertiser or organization disabled | No mutation; contact publisher for review |
| `validation_failed` | 422 | Invalid attributes or unacceptable creative format | Inline field errors and corrections |
| `state_conflict` | 409 | Stale object version or incompatible state | Compare server version, safe reload/merge |
| `idempotency_key_reused` | 409 | Same key with different request payload | Generate a new legitimate command, investigate duplicates |
| `quote_stale` | 409/410 | Changed inventory/pricing or quote expired | Requote and obtain explicit new acceptance |
| `hold_expired` | 410 | Authoritative expiry reached | Release display, retry availability without promising same slot |
| `inventory_conflict` | 409 | Overlap or insufficient quantity | Alternatives or different dates; no buyer information leak |
| `layout_contract_conflict` | 409 | Sold inventory harmed by draft publication | Show affected contract IDs to authorized actors and remedy workflow |
| `creative_not_approved` | 409 | Candidate version lacks approval | Pending-review status; no delivery |
| `payment_not_verified` | 409/202 | Provider settlement evidence incomplete | Show pending, queue reconciliation, no false success |
| `payment_inventory_mismatch` | 409 | Late paid order cannot acquire inventory | Open financial remedy and escalate |
| `commerce_provider_unavailable` | 503 | WooCommerce not ready | Disable online checkout only, maintain nonpayment operations |
| `delivery_unavailable` | 503 | Ad decision dependency failure | Safe fallback, public content remains available |
| `tracking_prohibited` | 403/204 | Consent/purpose policy forbids collection | Silent compliant no-collection; do not expose sensitive details |
| `event_rejected` | 400/422 | Invalid/duplicate/untrusted measurement | No billable increment; diagnostics for authorized operator |
| `geometry_mismatch` | 409/422 | Heatmap data cannot map to active layout | Offer correct layout version or summary instead of false overlay |
| `export_revoked` | 403/410 | Permission or TTL invalidated | Request new authorized export |
| `job_retry_exhausted` | 409 | Bounded retry limit reached | Incident queue and supervised recovery |
| `policy_decision_required` | 409 | Financial/privacy/legal value unresolved | Explicit blocking setup/admin review, no guessed behavior |

Never return raw SQL messages, payment credentials, signed provider payloads, private moderation notes or visitor identifiers in a public error. Errors should have correlation IDs where safe, translated user-facing descriptions, and keyboard/screen-reader announcements for critical operations.

---

## 10. Audit, ledger and domain-event requirements

### 10.1 Auditable operation classes (`AUD-*`)

| ID | Operation class | Required audit details | Required visibility |
|---|---|---|---|
| `AUD-001` | Organization membership changed | Actor, role change, organization, old/new grant, invitation/revocation | Site admin and own organization owner as permitted |
| `AUD-002` | Rate/quote contract accepted | Rate version, currency, exact money/units, terms version, quote expiry | Finance and affected organization |
| `AUD-003` | Hold/booking committed or released | Site, slot set, interval, contract, transaction result, idempotency reference | AdOps/Finance and own org status |
| `AUD-004` | Payment attempt/provider evidence | Trusted provider reference, mapped state, dedup result, transaction time | Finance; redacted advertiser status |
| `AUD-005` | Refund/adjustment/credit issued | Origin ledger ID, adjustment amount, currency, reason, provider state | Finance and authorized billing role |
| `AUD-006` | Creative version reviewed | Version digest, reviewer, policy result, reason, previous/new state | Moderator and own advertiser status |
| `AUD-007` | Campaign activated/paused/stopped | Trigger, authority, campaign version, eligibility reason | AdOps and own advertiser |
| `AUD-008` | Layout version published or rolled back | Base/new version, slot impacts, resolved contracts, actor | AdOps and relevant affected parties |
| `AUD-009` | Privileged allocation override | Separate permission, actor, reason, affected contracts, before/after | Site admin, finance and appropriate advertiser notice |
| `AUD-010` | Consent/replay policy changed | Scope, purpose, policy version, operator, synthetic masking check status | Privacy administrator |
| `AUD-011` | Restricted data/export accessed | Actor, scope, purpose, artifact, permitted result, expiry | Privacy/security audit team |
| `AUD-012` | Job retried/failed/reconciled | Job key, attempts, correlation, resulting safe state, operator | Operations and relevant finance |

### 10.2 Audit record envelope (proposed)

```json
{
  "event_id": "stable-opaque-id",
  "event_type": "adpilot.layout.published.v1",
  "site_id": "trusted-site-id",
  "actor_kind": "user|worker|payment_adapter",
  "actor_id": "trusted-internal-actor-ref",
  "resource_type": "layout",
  "resource_id": "opaque-layout-id",
  "old_version": 12,
  "new_version": 13,
  "occurred_at_utc": "2026-10-11T18:00:00Z",
  "reason_code": "contract_impact_resolved",
  "correlation_id": "workflow-trace-id",
  "causation_id": "command-or-job-id",
  "data_classification": "publisher_confidential",
  "redacted_details": {"affected_contract_count": 3}
}
```

**Example payload only.** Real events must validate actor provenance, separate immutable audit metadata from restricted details, and use exact schema types. The audit database should be append-only at the application boundary with restricted mutation paths and retention based on policy; it is not automatically tamper-proof merely because code avoids `UPDATE`.

### 10.3 Business event catalog

| Event (proposed) | Produced by | Consumers (examples) |
|---|---|---|
| `organization.member_revoked.v1` | Identity | Session/access invalidation, audit, notification |
| `inventory.layout_published.v1` | Layout | Cache invalidate, analytics geometry registry |
| `inventory.hold_created.v1` | Reservations | Expiry scheduler, buyer status |
| `inventory.reservation_committed.v1` | Reservations | Campaign readiness, finance reconciliation |
| `commerce.payment_reconciled.v1` | Commerce | Reservation commit or remedy |
| `commerce.remedy_opened.v1` | Remedy service | Finance queue, advertiser notification |
| `creative.review_completed.v1` | Moderation | Campaign eligibility, moderation notification |
| `campaign.emergency_stopped.v1` | Campaigns | Delivery deny cache, audit, alerts |
| `delivery.event_accepted.v1` | Measurement | Aggregate processor, quality diagnostics |
| `analytics.heatmap_aggregate_ready.v1` | Analytics (R2) | Heatmap viewer cache/report freshness |
| `report.export_ready.v1` | Reporting (R2) | Scoped notification and expiring download |
| `privacy.consent_policy_changed.v1` | Privacy | Tracker config invalidation and audit |

A domain event is not proof an external side effect happened. Consumers must recheck authoritative current state before mutations and be idempotent. Events crossing plugin/integration boundaries need schema-versioning, authentication, retry, rejection and dead-letter behavior.

### 10.4 Financial and event reconciliation

- Reconciliation reports should compare AdPilot order status, WooCommerce/provider verified state, hold/reservation state, allocated campaign period, refunded amounts and posted ledger totals.
- Never sum inconsistent currencies without explicit conversion methodology and conversion time. Rounding differences must appear as explicit adjustments where material.
- Separate billing evidence from exploratory analytics. A `qualified_click` in R1 is reportable but not R3 financial authority.
- Use immutable charge/credit lineage with links to contract version and commercial events. Financial records are preserved per approved retention schedule even when short-lived raw events are purged.
- Document reconciliation frequency, allowed delay, alert threshold and designated human escalation as configurable operational decisions.

---

## 11. Business-rule implementation contracts and policy service interfaces

The following interfaces are **suggested PHP abstractions** that express testable domain boundaries; actual namespaces and DTO fields will be established by architecture/schema documents. Domain services must not depend on React, block editor state or PHP template globals.

```php
<?php
// Conceptual API contract, not a complete runnable plugin implementation.

interface AuthorizeOperation {
    public function decide(ActorContext $actor, Operation $operation, ResourceContext $resource): AuthorizationDecision;
}
interface QuoteInventory {
    public function issue(QuoteRequest $request, ActorContext $actor): QuoteResult;
}
interface ReserveInventory {
    public function hold(HoldRequest $request, ActorContext $actor, IdempotencyKey $key): HoldResult;
    public function commit(CommitReservation $command): ReservationResult;
}
interface PublishLayout {
    public function assess(LayoutDraft $draft, PublishedLayoutVersion $base): LayoutImpact;
    public function publish(PublishLayoutCommand $command): PublishedLayoutResult;
}
interface ReviewCreativeVersion {
    public function decide(ReviewCommand $command, ActorContext $moderator): ReviewResult;
}
interface DetermineDelivery {
    public function decide(AdOpportunity $opportunity): DeliveryDecision;
}
interface ReconcilePayment {
    public function process(VerifiedPaymentEvidence $evidence): ReconciliationResult;
}
interface QualifyMeasurement {
    public function evaluate(MeasurementEvent $event): MeasurementDecision;
}
```

**Implementation rules:** Value objects validate constructor invariants; services return typed results, not mixed booleans/exceptions; infrastructure adapters handle `$wpdb`, WooCommerce and WordPress capabilities; events and logs exclude sensitive raw payloads; state changes use repository version checks and appropriate transactions. This sample should not be copy-pasted as a complete production interface before the concrete DTOs are defined.

### 11.1 Architecture-to-role decision gates

| Decision | Accountable | Required concurrence | Main tests |
|---|---|---|---|
| Quote units and final currency rounding | Finance Lead | Product Owner, QA | `SCN-PRICE-*`, `SCN-FIN-*` |
| Reservation locking model | Database Lead | Backend/Commerce, QA | `SCN-RES-*` concurrency suite |
| Creative moderation and exceptions | Security/Moderation Lead | Product, UX | `SCN-CRT-*`, `SCN-MOD-*` |
| Layout commercial impact semantics | Inventory/AdOps Lead | Finance, UX, Technical Lead | `SCN-LAY-*` |
| Current delivery eligibility | AdTech Lead | Backend, Security | `SCN-DEL-*` |
| Heatmap collection/meaning | Measurement Lead | Privacy, UX | `SCN-HEAT-*` |
| Replay opt-in and masking | Privacy Lead | Security, Analytics | `SCN-PRV-*` |
| Release gate and unsupported features | Product Owner | QA, Technical Lead | R1/R2/R3/R4 acceptance |

---

## 12. Traceability protocol and change control

All baseline IDs listed below were extracted from ADP-DOC-001/002/003. Exact functional descriptions are retained in the authoritative PRD, while this document identifies the primary policy evaluator and references to the existing `AT-*` detailed acceptance tests. The mappings **do not** imply the implementation/tests have been executed or external compliance verified.

An implementation work item should carry: `FR-ID`, `BR-ID`, primary `DP-ID`, release, state-machine transition, actor/scope, API entry point, database effect, user-facing state, audit event and test identifiers. A change to a rule requires impact analysis on quote/contract terms, rendering, historical metrics, UI copy, data migrations and backward-compatible public integration APIs.

### 12.1 Functional requirements crosswalk — all 129 baseline requirements

#### INV — 14 requirements; owner: Inventory & Layout

**Default policy:** `DP-INV / DP-LAY / DP-RES`. **States:** SM-LAYOUT / SM-RESERVATION. **Core journey links:** UJ-09/10/22.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-INV-001` | R1 | Authorized administrators SHALL create, rename, archive, clone and search zones with stable unique IDs and descriptions. | `AT-INV-001` |
| `FR-INV-002` | R1 | Zones SHALL contain multiple containers; containers SHALL support single, row, vertical stack and responsive grid configurations. | `AT-INV-002` |
| `FR-INV-003` | R1 | Administrators SHALL configure slot count without a hard-coded 500-slot product maximum. Tested baseline: 500 logical slots on one page; stress test: 1,000+. Hosting-specific safe limits MAY be enforced with transparent diagnostics. | `AT-INV-003` |
| `FR-INV-004` | R1 | A single container SHALL generate 100 sidebar or 200 footer slots from a compact configuration, not 100/200 manually persisted Gutenberg blocks. | `AT-INV-004` |
| `FR-INV-005` | R1 | Responsive layouts SHALL support desktop/tablet/mobile column count, minimum width, aspect ratio, gaps and visibility rules without mutating contractual slot identity. | `AT-INV-005` |
| `FR-INV-006` | R1 | The system SHALL resolve placement bindings for posts, pages, archives, search contexts, product pages where integrated, template parts and approved custom hooks. | `AT-INV-006` |
| `FR-INV-007` | R1 | Administrators SHALL preview zones and slots with availability, reservation occupancy, breakpoint and empty-state information. | `AT-INV-007` |
| `FR-INV-008` | R1 | Layout edits SHALL support draft, preview, validate, publish, immutable version history and controlled rollback. | `AT-INV-008` |
| `FR-INV-009` | R1 | Changes affecting reserved/sold inventory SHALL warn, block or initiate explicit migration/reapproval before publication. | `AT-INV-009` |
| `FR-INV-010` | R1 | Administrators SHALL bulk assign, unassign, edit, duplicate and filter generated slots using paginated/virtualized controls. | `AT-INV-010` |
| `FR-INV-011` | R1 | Slots SHALL have explicit inactive, eligible, reserved, occupied and unavailable states where applicable. | `AT-INV-011` |
| `FR-INV-012` | R2 | Container types SHOULD extend to carousel, masonry, sticky and conditional/repeater presentations with accessibility and density constraints. | `AT-INV-012` |
| `FR-INV-013` | R1 | On empty eligible inventory, configurable house creative, collapsed placeholder or reserved blank layout space SHALL be possible without falsely counting impressions. | `AT-INV-013` |
| `FR-INV-014` | R1 | The system SHALL detect duplicate unintended injection and distinguish it from permitted repeated rendering. | `AT-INV-014` |

#### INT — 12 requirements; owner: WordPress Integration

**Default policy:** `DP-LAY / DP-DEL / DP-AUTH`. **States:** SM-LAYOUT / SM-DELIVERY. **Core journey links:** UJ-11/12.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-INT-001` | R1 | Register dynamic blocks `adpilot/ad-slot`, `adpilot/ad-zone`, `adpilot/ad-repeater`, `adpilot/ad-showcase` using `block.json` and server-rendered output. | `AT-INT-001` |
| `FR-INT-002` | R1 | Each block SHALL expose relevant configuration, accessible editor controls, responsive preview and bounded representative rendering; editor previews SHALL not count as paid ad delivery. | `AT-INT-002` |
| `FR-INT-003` | R1 | `adpilot/ad-zone` SHALL store a compact stable zone reference and permitted overrides instead of one block per generated slot. | `AT-INT-003` |
| `FR-INT-004` | R1 | Repeater behavior SHALL support explicit interval policies for eligible content contexts; integrations into query loops, product grids and third-party layouts SHALL be adapter-controlled, not assumed universal. | `AT-INT-004` |
| `FR-INT-005` | R1 | Provide public PHP functions `adpilot_render_slot()`, `adpilot_render_zone()`, and `adpilot_render_container()` with stable, documented argument and return/error semantics. | `AT-INT-005` |
| `FR-INT-006` | R1 | Provide corresponding shortcodes, e.g. `[adpilot_slot]`, `[adpilot_zone]`, `[adpilot_repeater]`, and `[adpilot_showcase]`. | `AT-INT-006` |
| `FR-INT-007` | R1 | Provide extensible action/filter hooks and a capability-gated REST API; all integration paths SHALL invoke the same authorization/eligibility/rendering core. | `AT-INT-007` |
| `FR-INT-008` | R1 | Support classic themes, block themes, template parts and traditional widget areas through appropriately scoped adapters. | `AT-INT-008` |
| `FR-INT-009` | R1 | Avoid overriding theme global styles; use scoped styles and namespaced classes/CSS variables. | `AT-INT-009` |
| `FR-INT-010` | R1 | No shortcode, Gutenberg parameter or PHP template option SHALL bypass purchased inventory, creative approval, commercial restrictions, or security rules. | `AT-INT-010` |
| `FR-INT-011` | R2 | Provide layout/theme diagnostics for unresolved hooks, unsupported selectors, duplicates and rendering conflicts. | `AT-INT-011` |
| `FR-INT-012` | R1 | Documentation SHALL give minimal working examples for each supported theme integration path. | `AT-INT-012` |

#### MKT — 10 requirements; owner: Marketplace / Identity

**Default policy:** `DP-AUTH / DP-PRC`. **States:** SM-QUOTE / SM-CAMPAIGN. **Core journey links:** UJ-01/02/03/06.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-MKT-001` | R1 | Advertisers SHALL browse a public, filterable catalog of published placements and packages, with dimensions, format eligibility, price, schedule and clear terms. | `AT-MKT-001` |
| `FR-MKT-002` | R1 | Published placement pages SHALL include sanitized preview, responsive context and measured historical estimates when available, labeled as estimates rather than guarantees. | `AT-MKT-002` |
| `FR-MKT-003` | R1 | Authorized advertisers SHALL register, manage business profiles, view their campaigns, invoices and notifications, and resume saved drafts. | `AT-MKT-003` |
| `FR-MKT-004` | R1 | Advertiser organizations SHALL support owner/manager/analyst/billing membership with least-privilege permissions and secure invitations. | `AT-MKT-004` |
| `FR-MKT-005` | R1 | Campaign wizard SHALL guide placement selection, creative upload, targeting where allowed, scheduling, quote review, submission and checkout. | `AT-MKT-005` |
| `FR-MKT-006` | R1 | The wizard SHALL provide validation and clear recoverable errors, maintain drafts and preserve state during normal navigation. | `AT-MKT-006` |
| `FR-MKT-007` | R1 | Advertisers SHALL see distinguishable approval, payment and delivery states. | `AT-MKT-007` |
| `FR-MKT-008` | R1 | Public catalog SHALL expose no advertiser-private, financial-sensitive or visitor-personal records. | `AT-MKT-008` |
| `FR-MKT-009` | R1 | Administrators SHALL manage advertisers, verify organizations where configured, suspend accounts, and audit changes. | `AT-MKT-009` |
| `FR-MKT-010` | R2 | Campaign renewal, duplication, packaged purchasing and organization reporting enhancements SHOULD be available. | `AT-MKT-010` |

#### CAM — 10 requirements; owner: Campaigns / Moderation

**Default policy:** `DP-MOD / DP-CRT / DP-DEL`. **States:** SM-CREATIVE / SM-CAMPAIGN / SM-DELIVERY. **Core journey links:** UJ-03/07/14.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-CAM-001` | R1 | Campaigns SHALL have separately managed approval, financial and delivery state machines. | `AT-CAM-001` |
| `FR-CAM-002` | R1 | Campaigns SHALL support drafts, submission, review, changes requested, approval, rejection, schedule, activation, pause, completion and cancellation/suspension paths. | `AT-CAM-002` |
| `FR-CAM-003` | R1 | Creatives SHALL support safe raster images and text/native ad templates; file dimensions, format, size, destination URL and alt text SHALL be validated. | `AT-CAM-003` |
| `FR-CAM-004` | R1 | Advertisers SHALL upload multiple creative versions and request edits; policy-relevant changes require reapproval before serving. | `AT-CAM-004` |
| `FR-CAM-005` | R1 | Moderators SHALL approve/reject with reasons, maintain audit trails and support emergency stop. | `AT-CAM-005` |
| `FR-CAM-006` | R1 | Paid placement SHALL not imply automatic creative approval; rejection SHALL invoke documented revision, cancellation and refund policies. | `AT-CAM-006` |
| `FR-CAM-007` | R1 | Native/sponsored formats SHALL expose sponsorship disclosure and appropriate link attributes. | `AT-CAM-007` |
| `FR-CAM-008` | R1 | Arbitrary advertiser-controlled JavaScript, unsanitized SVG and unrestricted HTML SHALL be prohibited. Safe interactive/sandboxed formats may be separately designed for later release. | `AT-CAM-008` |
| `FR-CAM-009` | R2 | Video and interactive advertising MAY be supported subject to explicit format, security, performance and accessibility acceptance tests. | `AT-CAM-009` |
| `FR-CAM-010` | R1 | Campaign audit trail SHALL record high-value actions including owner, time, previous/new state, and safe explanation. | `AT-CAM-010` |

#### COM — 14 requirements; owner: Commerce / Finance

**Default policy:** `DP-RES / DP-COM / DP-FIN / DP-TIM`. **States:** SM-HOLD / SM-ORDER / SM-PAYMENT. **Core journey links:** UJ-04/05/16/21/28.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-COM-001` | R1 | Support fixed-price, time-based and exclusive sponsorship price plans with clear units and rate-card version. | `AT-COM-001` |
| `FR-COM-002` | R1 | Availability SHALL be calculated from binding, capacity, exclusions, reservations and active allocations for the requested period. | `AT-COM-002` |
| `FR-COM-003` | R1 | Reservation creation SHALL be concurrency-safe, have bounded expiry and be idempotent on retries. | `AT-COM-003` |
| `FR-COM-004` | R1 | Quote SHALL include currency, units, dates/time zone, inventory, discounts, taxes where configured, expiration and applicable approval/refund conditions. | `AT-COM-004` |
| `FR-COM-005` | R1 | WooCommerce adapter SHALL map AdPilot commercial orders to WooCommerce orders using supported WooCommerce CRUD/HPOS-compatible APIs. | `AT-COM-005` |
| `FR-COM-006` | R1 | Payment status SHALL be obtained only from authoritative commerce/gateway callbacks, not browser redirect success alone. | `AT-COM-006` |
| `FR-COM-007` | R1 | Duplicate, delayed and out-of-order callbacks SHALL not double-allocate inventory or duplicate invoices/charges. | `AT-COM-007` |
| `FR-COM-008` | R1 | Refunds, cancellation, payment reversals, expired quotes and declined creatives SHALL have explicit policy-governed workflows. | `AT-COM-008` |
| `FR-COM-009` | R1 | Financial records SHALL maintain auditable adjustments and reconciliation against commercial orders. | `AT-COM-009` |
| `FR-COM-010` | R1 | Administrators SHALL view order, payment, reservation and fulfillment discrepancies. | `AT-COM-010` |
| `FR-COM-011` | R3 | Qualified, reconciled CPM/CPC, spending caps, credits and share-of-voice contracts SHALL be introduced only after measurement/billing certification tests. | `AT-COM-011` |
| `FR-COM-012` | R4 | Additional payment/billing providers MAY be implemented without entangling the core inventory and campaign domains. | `AT-COM-012` |
| `FR-COM-013` | R1 | When WooCommerce is absent, product SHALL still support administrator-approved complimentary/manual campaigns and all non-checkout functions; online self-service payment UI SHALL clearly state its prerequisite. | `AT-COM-013` |
| `FR-COM-014` | R1 | Time ranges SHALL be stored in UTC, rendered in selected site/account timezone, and tested across daylight-saving transitions. | `AT-COM-014` |

#### DEL — 13 requirements; owner: Delivery / Frontend

**Default policy:** `DP-DEL / DP-DEN / DP-PRV`. **States:** SM-DELIVERY / SM-ALLOCATION. **Core journey links:** UJ-13/14.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-DEL-001` | R1 | Delivery decisions SHALL gate on approved creative, valid contract, active schedule, available allocation, policy eligibility and budget/hold rules. | `AT-DEL-001` |
| `FR-DEL-002` | R1 | Support fixed assignment, priority, weighted rotation and house-ad fallback with auditable selection rules. | `AT-DEL-002` |
| `FR-DEL-003` | R1 | Eligible targeting SHALL include page, post type, category/taxonomy, device/breakpoint and locale; advanced geo/audience policies require privacy/legal review. | `AT-DEL-003` |
| `FR-DEL-004` | R1 | Layout markup SHALL reserve predictable space or use controlled collapsing to minimize layout shifts. | `AT-DEL-004` |
| `FR-DEL-005` | R1 | Load creatives progressively as slots approach the viewport and batch backend decisions; do not fetch every heavy creative upfront on a 500-slot page. | `AT-DEL-005` |
| `FR-DEL-006` | R1 | Limit in-flight fetches, observers and event handlers; reuse shared browser primitives where appropriate. | `AT-DEL-006` |
| `FR-DEL-007` | R1 | Different occurrences of the same slot/zone on a page SHALL have unique render identifiers and correct event attribution. | `AT-DEL-007` |
| `FR-DEL-008` | R1 | Integration SHALL be resilient to full-page caching and common CDNs and shall not cache visitor-specific sensitive decisions in shared contexts. | `AT-DEL-008` |
| `FR-DEL-009` | R1 | Failure of delivery/analytics endpoints SHALL not break the containing WordPress page. | `AT-DEL-009` |
| `FR-DEL-010` | R1 | Admin previews, editor screens, tests and staging mode SHALL be flagged as nonbillable/nonproduction. | `AT-DEL-010` |
| `FR-DEL-011` | R1 | Ad density, slot count and large creative configurations SHALL produce configurable UX/performance warnings; supported logical capacity does not equal permission to show all creatives simultaneously. | `AT-DEL-011` |
| `FR-DEL-012` | R2/R3 | Advanced frequency caps, pacing, viewability-aware delivery and spend-limited allocation SHALL be implemented with explicit data-collection constraints. | `AT-DEL-012` |
| `FR-DEL-013` | R1 | A page SHALL remain navigable and accessible with JavaScript disabled, with documented limitations to dynamic filling and metrics. | `AT-DEL-013` |

#### MET — 11 requirements; owner: Measurement

**Default policy:** `DP-MET / DP-FIN / DP-PRV`. **States:** SM-JOB / SM-DISPUTE. **Core journey links:** UJ-13/15/24.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-MET-001` | R1 | Separate ad requested, selected/served, browser rendered, click observed, click qualified and billable event types. | `AT-MET-001` |
| `FR-MET-002` | R1 | Distinguish counts and denominators in UI/API; metrics SHALL not silently conflate served with viewable. | `AT-MET-002` |
| `FR-MET-003` | R1 | Validate events server-side for schema, origin/context where practicable, bounded payload and rate limits. | `AT-MET-003` |
| `FR-MET-004` | R1 | Provide event IDs and bounded deduplication/aggregation with at-least-once delivery assumptions. | `AT-MET-004` |
| `FR-MET-005` | R1 | Exclude editor previews, testing traffic and known invalid automation under defined rules. | `AT-MET-005` |
| `FR-MET-006` | R1 | Aggregate campaign, creative, page, zone and slot statistics by time bucket without requiring an unbounded raw-event history. | `AT-MET-006` |
| `FR-MET-007` | R2 | Measure viewability with explicit observable-browser thresholds and time-in-view; document unsupported/browser-limited measurement. | `AT-MET-007` |
| `FR-MET-008` | R2 | Report measurable vs nonmeasurable impressions and reasons where known. | `AT-MET-008` |
| `FR-MET-009` | R3 | CPC/CPM billing SHALL be based on qualified, reconciled events and contract-specific rules, not client-reported counters alone. | `AT-MET-009` |
| `FR-MET-010` | R3 | Maintain invalid-traffic investigation and adjustment workflow without claiming perfect fraud detection. | `AT-MET-010` |
| `FR-MET-011` | R3 | Off-site conversions SHALL require advertiser-installed or otherwise authorized integrations, with attribution model/windows explicitly labeled. | `AT-MET-011` |

#### HEAT — 11 requirements; owner: Behavioral Analytics

**Default policy:** `DP-HEAT / DP-PRV / DP-REP`. **States:** SM-CONSENT/REPLAY. **Core journey links:** UJ-18/19/20/26.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-HEAT-001` | R2 | Provide click/tap, scroll-depth and element/slot-visibility heatmaps, with an optional attention-time **proxy** view; never label proxies as eye tracking. | `AT-HEAT-001` |
| `FR-HEAT-002` | R2 | Admin SHALL inspect full-page, zone, container and slot-level overlays on vertically scrollable pages. | `AT-HEAT-002` |
| `FR-HEAT-003` | R2 | Browser collector SHALL use normalized coordinates plus stable semantic element/slot references. | `AT-HEAT-003` |
| `FR-HEAT-004` | R2 | Aggregates SHALL be segmented by page layout version and responsive device class; incompatible geometries shall not be combined silently. | `AT-HEAT-004` |
| `FR-HEAT-005` | R2 | Editor SHALL show selected date range, event sample size, sampling status, denominator, measurement limitations and empty states. | `AT-HEAT-005` |
| `FR-HEAT-006` | R2 | Administrators SHALL filter by eligible campaign, creative, zone, page/template, device and reporting period. | `AT-HEAT-006` |
| `FR-HEAT-007` | R2 | Support side-by-side comparison of compatible layout periods and numeric accessible alternatives to color-only heatmaps. | `AT-HEAT-007` |
| `FR-HEAT-008` | R2 | Tracking SHALL be consent-aware where required, exclude protected/sensitive content, and minimize visitor identifiers. | `AT-HEAT-008` |
| `FR-HEAT-009` | R2 | Changing page geometry, infinite scroll or AJAX insertion SHALL not silently assign historical events to the wrong placement. | `AT-HEAT-009` |
| `FR-HEAT-010` | R4 | Optional sampled session replay SHALL be disabled by default, separately permissioned, sensitive-content-safe, retention-controlled, and never a prerequisite for regular ad serving. | `AT-HEAT-010` |
| `FR-HEAT-011` | R2 | Heatmap tracking and visualization SHALL operate without mandating Hotjar, Clarity or another proprietary hosted service. | `AT-HEAT-011` |

#### REP — 10 requirements; owner: Reporting

**Default policy:** `DP-REP / DP-AUTH`. **States:** SM-EXPORT. **Core journey links:** UJ-15/19/20/23/25.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-REP-001` | R1 | Administrator dashboard SHALL surface revenue/order state, active/pending campaigns, placement occupancy, basic delivery status and operational alerts. | `AT-REP-001` |
| `FR-REP-002` | R1 | Advertiser dashboard SHALL expose only their own campaigns, approval/payment status, qualified statistics and invoices where available. | `AT-REP-002` |
| `FR-REP-003` | R1 | Reports SHALL support period, campaign, creative, zone/slot and device filters where data is available. | `AT-REP-003` |
| `FR-REP-004` | R1 | Report values SHALL identify units, currencies, denominators, time zone and data freshness. | `AT-REP-004` |
| `FR-REP-005` | R2 | Add viewability, scroll, placement exposure and heatmap summaries; export aggregate CSV/JSON as allowed. | `AT-REP-005` |
| `FR-REP-006` | R3 | Add conversion attribution, finance reconciliations, experiments, anomaly analysis and evidence-based placement recommendations. | `AT-REP-006` |
| `FR-REP-007` | R3 | Forecasts and estimates SHALL be visually labeled and accompanied by assumptions and uncertainty. | `AT-REP-007` |
| `FR-REP-008` | R2 | Long-running report exports SHALL use queued background processing and scoped download authorization. | `AT-REP-008` |
| `FR-REP-009` | R2 | Administrators SHALL review pending jobs, delivery/aggregation health and retention cleanup status. | `AT-REP-009` |
| `FR-REP-010` | R1 | Operational metrics, raw behavioral data and replay recordings SHALL not be exposed through ordinary advertiser reporting. | `AT-REP-010` |

#### UX — 11 requirements; owner: UX / Frontend

**Default policy:** `DP-UX / DP-AUTH`. **States:** Cross-cutting screen contracts. **Core journey links:** UJ-01..28.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-UX-001` | R1 | Provide distinct admin console, advertiser portal, public storefront, Gutenberg block controls and Layout Studio, under one coherent design system. | `AT-UX-001` |
| `FR-UX-002` | R1 | Every primary workflow SHALL specify empty/loading/error/success/confirmation states and recovery paths. | `AT-UX-002` |
| `FR-UX-003` | R1 | Layout Studio SHALL use visual canvas + zone tree + inspector, while also supporting keyboard-accessible and nonvisual editing. | `AT-UX-003` |
| `FR-UX-004` | R1 | Screens containing hundreds of slots SHALL display summarized/paginated controls rather than hundreds of simultaneously mounted edit widgets. | `AT-UX-004` |
| `FR-UX-005` | R1 | Advertiser wizard SHALL autosave drafts, show a transparent cost/approval summary, and avoid silent loss of changes. | `AT-UX-005` |
| `FR-UX-006` | R1 | Primary interface SHALL meet WCAG 2.2 AA as a project target, with automated and manual verification. | `AT-UX-006` |
| `FR-UX-007` | R1 | Use WordPress localization APIs; support RTL/LTR layouts, locale-aware dates, numbers and currency presentation. | `AT-UX-007` |
| `FR-UX-008` | R1 | Responsive controls and page previews SHALL work across phone, tablet and desktop representative widths. | `AT-UX-008` |
| `FR-UX-009` | R1 | Avoid unnecessary global theme CSS/JS and unrelated plugin side effects. | `AT-UX-009` |
| `FR-UX-010` | R2/R4 | Offer richer saved report views, white-label/site branding and advanced multisite workflows subject to individual release scopes. | `AT-UX-010` |
| `FR-UX-011` | R1 | AdPilot SHALL expose high-quality diagnostic feedback rather than generic fatal errors for configuration and integration failures. | `AT-UX-011` |

#### OPS — 13 requirements; owner: Security / Operations

**Default policy:** `DP-AUTH / DP-PRV / DP-RET / DP-OVR`. **States:** SM-JOB / SM-CONSENT/REPLAY. **Core journey links:** UJ-17/18/27.

| Requirement | Release | Canonical functional obligation | Detailed acceptance |
|---|---|---|---|
| `FR-OPS-001` | R1 | Apply capability checks plus resource ownership to every sensitive REST/UI/CLI operation. | `AT-OPS-001` |
| `FR-OPS-002` | R1 | Validate and sanitize inputs, escape output by context, use prepared SQL and defend against CSRF, XSS, SSRF, upload abuse, replay and broken object authorization. | `AT-OPS-002` |
| `FR-OPS-003` | R1 | Audit financial changes, campaign approval, placement publication, account membership and emergency pause. | `AT-OPS-003` |
| `FR-OPS-004` | R1 | Enable privacy policy guidance, configurable retention, eligible data export/deletion and consent integration points. | `AT-OPS-004` |
| `FR-OPS-005` | R1 | Exclude sensitive DOM/form/payment values from any analytics collection; absence of user consent must prevent collection where required. | `AT-OPS-005` |
| `FR-OPS-006` | R1 | Provide diagnostics, explicit job retries, failed-job reporting, queue monitoring and supportable system cron/WP-CLI execution. | `AT-OPS-006` |
| `FR-OPS-007` | R1 | Implement schema-versioned installation/upgrades, data-preserving deactivation, intentional uninstall behavior and upgrade testing. | `AT-OPS-007` |
| `FR-OPS-008` | R1 | Distributable libraries, assets and source code SHALL have documented GPL-compatible licensing; generate dependency register/SBOM and required notices. | `AT-OPS-008` |
| `FR-OPS-009` | R1 | Use a versioned REST namespace and stable hooks/shortcodes/PHP APIs; changes SHALL follow documented deprecation policy. | `AT-OPS-009` |
| `FR-OPS-010` | R1 | Expose structured, redacted logs, health checks, and safe administrator diagnostic exports. | `AT-OPS-010` |
| `FR-OPS-011` | R4 | Support optional independently deployed workers/analytics integrations without a forced redesign of core WordPress services. | `AT-OPS-011` |
| `FR-OPS-012` | R1 | Plugin bootstrap SHALL not require WooCommerce or proprietary remote services merely to load. | `AT-OPS-012` |
| `FR-OPS-013` | R1 | Plugin activation SHALL create only documented, owned resources and SHALL avoid modifying unrelated existing pages/settings without authorization. | `AT-OPS-013` |

### 12.2 Baseline invariants, business rules and acceptance anchors

| Source class | Canonical IDs | Preservation / execution contract |
|---|---|---|
| Business rules | `BR-001..018` | Exact wording in §3; server enforcement through `DP-*`; negative tests `AT-BR-001..018` plus `SCN-*`. |
| Invariants | `INV-01..10` | Explicit gate preservation in §2; new `D04-INV-*` supplementary only. |
| Functional requirements | 129 `FR-*` | One-to-one crosswalk above to each existing `AT-*` in ADP-DOC-002. |
| R1 product criteria | `AC-001..015` | Acceptance gates below; no claim of test execution. |
| NFRs | `NFR-001..016` | Cross-cutting quality/operational behavior retained; map below. |
| Journeys | `UJ-01..28` / `UXA-01..28` | Preserve existing user flows and acceptance tests; state and policy links below. |
| Atomic workflows | `WF-01..18` | Preserve domain workflow semantics from ADP-DOC-003; named machine references below. |
| Core E2E | `E2E-01..17` | Executable test definitions remain in ADP-DOC-002. |

### 12.3 Nonfunctional requirement policy mapping

| ID | Canonical topic | Policy application / test domain |
|---|---|---|
| `NFR-001` | Architecture | `DP-INV/DEL`; dedicated NFR benchmark/assurance tests |
| `NFR-002` | Maintainability | `DP-AUTH`; dedicated NFR benchmark/assurance tests |
| `NFR-003` | Rendering | `DP-DEL`; dedicated NFR benchmark/assurance tests |
| `NFR-004` | Performance | `DP-UX/DEL`; dedicated NFR benchmark/assurance tests |
| `NFR-005` | Reliability | `DP-COM/RES`; dedicated NFR benchmark/assurance tests |
| `NFR-006` | Financial correctness | `DP-FIN/RES`; dedicated NFR benchmark/assurance tests |
| `NFR-007` | Security | `DP-AUTH/CRT`; dedicated NFR benchmark/assurance tests |
| `NFR-008` | Privacy | `DP-PRV/RET`; dedicated NFR benchmark/assurance tests |
| `NFR-009` | Accessibility | `DP-UX`; dedicated NFR benchmark/assurance tests |
| `NFR-010` | Compatibility | `DP-LAY/DEL`; dedicated NFR benchmark/assurance tests |
| `NFR-011` | Internationalization | `DP-TIM/UX`; dedicated NFR benchmark/assurance tests |
| `NFR-012` | Observability | `DP-REP`; dedicated NFR benchmark/assurance tests |
| `NFR-013` | Extensibility | `DP-AUTH`; dedicated NFR benchmark/assurance tests |
| `NFR-014` | Licensing | `DP-CRT/PRV`; dedicated NFR benchmark/assurance tests |
| `NFR-015` | Upgrade safety | `DP-RET`; dedicated NFR benchmark/assurance tests |
| `NFR-016` | Usability | `DP-UX`; dedicated NFR benchmark/assurance tests |

### 12.4 R1 product-level acceptance mapping

| ID | Source acceptance objective | Primary policies / state verification |
|---|---|---|
| `AC-001` | Publisher creates and publishes a 500-slot page consisting of 4 header, 100 sidebar, 196 in-content and 200 footer logical slots. Layout Studio remains usable; publishing and responsive preview succeed. | `DP-INV/LAY/UX; SM-LAYOUT` |
| `AC-002` | One `adpilot/ad-zone` block can reference a 200-slot footer container without storing 200 Gutenberg blocks. | `DP-INV/LAY; Gutenberg` |
| `AC-003` | Four supported integration paths (Gutenberg, PHP function, shortcode, and supported hook binding) render through one policy-aware engine with correct per-occurrence identifiers. | `DP-DEL/AUTH; shared renderer` |
| `AC-004` | Public page initially loads bounded creatives near viewport and does not miscount 500 configured slots as impressions. | `DP-DEL/MET; performance` |
| `AC-005` | Advertiser can discover an available slot, obtain a quote, reserve, pay through configured WooCommerce, upload creative, request review and observe activation when approved. | `DP-PRC/RES/COM/MOD/DEL` |
| `AC-006` | Two concurrent buyers cannot successfully reserve the same exclusive inventory over overlapping time windows. | `DP-RES; SM-HOLD` |
| `AC-007` | Duplicate payment callbacks and campaign activation retries do not double-allocate, double-invoice or double-activate. | `DP-COM/FIN/DEL; SM-PAYMENT` |
| `AC-008` | Creative changes requiring reapproval cannot bypass review and cannot serve in an unapproved state. | `DP-MOD/CRT; SM-CREATIVE` |
| `AC-009` | Advertiser A cannot access advertiser B's campaigns, invoices, raw behavioral data or privileged reports through UI or direct API calls. | `DP-AUTH; PERM-001/016` |
| `AC-010` | Classic theme, block theme, common caching setup and supported WooCommerce HPOS mode pass the declared compatibility suite. | `DP-UX/DEL/COM; compatibility` |
| `AC-011` | Broken analytics endpoint does not crash the public site, block reading content or silently produce fictitious billable events. | `DP-DEL/MET; fallback` |
| `AC-012` | Privacy-sensitive pages/inputs are excluded from collection; denied consent prevents prohibited collection. | `DP-PRV; consent` |
| `AC-013` | All primary application workflows pass agreed manual accessibility and usability gates. | `DP-UX; accessibility` |
| `AC-014` | Installer works without WooCommerce for nonpayment functions and provides an accurate diagnostic for unavailable online checkout. | `DP-COM; manual path` |
| `AC-015` | Release contains reproducible build, automated tests, license notices, migrations and administrator/developer documentation. | `DP-RET/OVR; release` |

### 12.5 Existing workflow-to-state-machine mapping

| Workflow ID | Preserved workflow | Governing detailed contract |
|---|---|---|
| `WF-01` | Draft autosave | SM-CAMPAIGN / DP-AUTH |
| `WF-02` | Creative submission | SM-CREATIVE / DP-CRT |
| `WF-03` | Creative approval | SM-CREATIVE / DP-MOD |
| `WF-04` | Quote generation | SM-QUOTE / DP-PRC |
| `WF-05` | Reservation hold | SM-HOLD / DP-RES |
| `WF-06` | Checkout initiation | SM-ORDER / DP-COM |
| `WF-07` | Payment reconcile | SM-PAYMENT / DP-COM |
| `WF-08` | Delivery eligibility | SM-DELIVERY / DP-DEL |
| `WF-09` | Layout publish | SM-LAYOUT / DP-LAY |
| `WF-10` | Slot bulk mutation | SM-LAYOUT / DP-INV |
| `WF-11` | Public ad decision | SM-DELIVERY / DP-DEL |
| `WF-12` | Event ingestion | SM-JOB / DP-MET |
| `WF-13` | Report query | SM-EXPORT / DP-REP |
| `WF-14` | Heatmap query (R2) | SM-CONSENT/REPLAY / DP-HEAT |
| `WF-15` | Report export (R2) | SM-EXPORT / DP-REP |
| `WF-16` | Metered reconciliation (R3) | SM-DISPUTE / DP-FIN |
| `WF-17` | Replay opt-in (R4) | SM-CONSENT/REPLAY / DP-PRV |
| `WF-18` | Operational retry | SM-JOB / DP-RET |

### 12.6 Existing journeys and corresponding UX tests

| Journey | Existing journey name | Policy and acceptance reference |
|---|---|---|
| `UJ-01` | Anonymous advertiser discovers an offer | `DP-PRC/REP`; `UXA-01` |
| `UJ-02` | Advertiser registers and sets up organization | `DP-AUTH`; `UXA-02` |
| `UJ-03` | Advertiser drafts campaign and validates creative | `DP-MOD/CRT`; `UXA-03` |
| `UJ-04` | Advertiser obtains quote and exclusive hold | `DP-PRC/RES`; `UXA-04` |
| `UJ-05` | WooCommerce checkout, return, and recovery | `DP-COM/RES`; `UXA-05` |
| `UJ-06` | Organization owner invites and restricts teammates | `DP-AUTH`; `UXA-06` |
| `UJ-07` | Moderator reviews new creative or changed version | `DP-MOD/REM`; `UXA-07` |
| `UJ-08` | AdOps checks booking calendar and resolves overlap | `DP-INV/RES`; `UXA-08` |
| `UJ-09` | Publisher builds 500-slot responsive page | `DP-LAY/UX`; `UXA-09` |
| `UJ-10` | Publisher edits sold layout without breaking contracts | `DP-LAY/OVR`; `UXA-10` |
| `UJ-11` | Content editor inserts AdPilot Gutenberg blocks | `DP-LAY/DEL`; `UXA-11` |
| `UJ-12` | Theme integrator embeds ad slots via public interfaces | `DP-DEL/AUTH`; `UXA-12` |
| `UJ-13` | Visitor scrolls high-density page with consent controls | `DP-DEL/MET/PRV`; `UXA-13` |
| `UJ-14` | AdOps activates, pauses and expires a campaign | `DP-DEL`; `UXA-14` |
| `UJ-15` | Advertiser or publisher reviews verified metrics | `DP-REP/MET`; `UXA-15` |
| `UJ-16` | Finance manager investigates refund or mismatch | `DP-FIN/REM`; `UXA-16` |
| `UJ-17` | Administrator recovers from delivery/queue incident | `DP-RET/COM`; `UXA-17` |
| `UJ-18` | Site administrator configures privacy and consent exclusions | `DP-PRV`; `UXA-18` |
| `UJ-19` | Publisher explores full-page heatmaps | `DP-HEAT/REP`; `UXA-19` |
| `UJ-20` | Analytics manager compares layouts across versions | `DP-HEAT/LAY`; `UXA-20` |
| `UJ-21` | Advertiser recovers abandoned or failed checkout | `DP-COM/REM`; `UXA-21` |
| `UJ-22` | AdOps bulk edits generated inventory and filters conflicts | `DP-INV/LAY`; `UXA-22` |
| `UJ-23` | Authorized user requests aggregate report export | `DP-REP/AUTH`; `UXA-23` |
| `UJ-24` | Reconciled CPM/CPC billing and dispute | `DP-FIN/MET`; `UXA-24` |
| `UJ-25` | Publisher runs placement experiment or optimization | `DP-REP/LAY`; `UXA-25` |
| `UJ-26` | Authorized administrator activates optional session replay | `DP-PRV`; `UXA-26` |
| `UJ-27` | Administrator installs, upgrades and diagnoses dependencies | `DP-RET/AUTH`; `UXA-27` |
| `UJ-28` | Publisher runs complimentary/manual campaign without WooCommerce | `DP-COM/MOD`; `UXA-28` |

**Change control:** If any source ID is renamed, deprecated, added or split, amend canonical documents, change the mapping and regenerate the audit. Never silently assign source IDs to invented behaviors or mark a future-release workflow done in R1.

---

## 13. Business-rule acceptance tests — executable Given/When/Then contracts

Tests below extend, rather than replace, `AT-BR-001..018` in ADP-DOC-002. Each test must be executable through a domain/integration harness and should have a UI/E2E variant where the behavior is user-visible. Test names use `SCN-BR-*` to distinguish them from canonical business-rule IDs.

### SCN-BR-001 — Only published capacity is sellable (`BR-001`, R1)

- **Given** an unpublished zone, a paused binding, an active valid binding, and active conflicting allocations for the same site and time interval;
- **When** an advertiser requests a quote/hold for each;
- **Then** the unpublished and paused inventories are denied, capacity is recalculated transactionally for a requested hold, and only eligible remaining units may be held. No competing advertiser details are exposed.

### SCN-BR-002 — Hold expiry is server-authoritative (`BR-002`, R1)

- **Given** a confirmed hold with an exact UTC expiry and a browser countdown lagging behind server time;
- **When** the expired hold is accepted again or an extension is replayed without new authorization;
- **Then** it is rejected, capacity is eligible for other buyers subject to locks, and the frontend requests a fresh quote. Delayed cleanup must not alter this conclusion.

### SCN-BR-003 — Paid does not equal approved (`BR-003`, R1)

- **Given** a provider-verified paid order for a campaign whose creative remains `under_review`;
- **When** an ad decision is requested and a scheduled activation job runs;
- **Then** no paid creative serves, payment remains truthfully confirmed, and both UI and audit show the separate approval status.

### SCN-BR-004 — Paid rejection has a governed remedy (`BR-004`, R1)

- **Given** a paid booked campaign and a moderator rejecting its creative;
- **When** the rejection is recorded;
- **Then** an explicit governed correction/refund/cancellation route is opened, pending obligations remain visible, and no refund is falsely represented as settled before provider confirmation.

### SCN-BR-005 — Concurrent exclusive holds cannot double-sell (`BR-005`, R1)

- **Given** two authenticated organizations requesting overlapping exclusive time windows for one slot;
- **When** both submit concurrent hold transactions on supported MySQL/MariaDB;
- **Then** no more than one conflicting hold commits, the loser receives a safe conflict, and retrying either key cannot create a second committed allocation.

### SCN-BR-006 — Sold layout cannot silently change (`BR-006`, R1)

- **Given** a slot with an active premium contract and a draft that moves it to a materially different placement;
- **When** an editor attempts publication;
- **Then** a fresh impact analysis blocks publish until all affected commitments are remedied and audited, and historical layout version remains intact.

### SCN-BR-007 — Creative approval is version-specific (`BR-007`, R1)

- **Given** approved creative `V1` and a new `V2` changing destination URL;
- **When** `V2` is uploaded and immediately selected;
- **Then** it is not eligible until approved as its own version, and whether `V1` continues depends on the recorded policy/contract, not on a hidden default.

### SCN-BR-008 — Paused campaign defeats stale cache (`BR-008`, R1)

- **Given** a warm shared cache and campaign initially eligible;
- **When** an authorized emergency stop or pause commits;
- **Then** new delivery decisions deny the stopped campaign within the declared freshness guarantee, rendering remains safe, and no stale cached creative is authorized merely because it exists in markup.

### SCN-BR-009 — Preview is nonbillable (`BR-009`, R1)

- **Given** editor preview, administrator preview, staging load testing and an eligible real visitor;
- **When** identical creative elements are rendered and clicked;
- **Then** preview/staging/synthetic events do not contribute to production billable counters and qualifying production events remain distinguishable.

### SCN-BR-010 — Density restrictions remain effective (`BR-010`, R1)

- **Given** a footer grid with 200 logical slots and a mobile density/network policy;
- **When** a renderer evaluates the narrow viewport;
- **Then** only policy-eligible creatives render, permitted placeholders/fallbacks are used, density warnings identify constraints, and no shortcode flag can bypass a mandatory restriction.

### SCN-BR-011 — Advertiser reports are aggregate only (`BR-011`, R1+)

- **Given** advertiser A, advertiser B, visitor-level restricted telemetry and aggregate campaign results;
- **When** advertiser A requests reports or crafts direct REST filters for B;
- **Then** only A's authorized aggregates are returned; raw session/replay/visitor data are denied even through CSV export.

### SCN-BR-012 — Retention is not financial erasure (`BR-012`, R1+)

- **Given** expired raw tracking events whose aggregates were processed and a valid posted order/ledger record;
- **When** retention cleanup runs twice;
- **Then** raw data is purged within the approved policy, ledger and permitted financial audit evidence are preserved, and cleanup is idempotent with diagnostics.

### SCN-BR-013 — Reporting claims are classified (`BR-013`, R1/R2/R3)

- **Given** observed clicks, model-attributed conversions and placement revenue forecasts;
- **When** reports are displayed or exported;
- **Then** each value is explicitly labeled observed, attributed, estimated or forecast with its definition and limitations, and missing evidence never becomes a verified conversion.

### SCN-BR-014 — Precise currency arithmetic (`BR-014`, R1)

- **Given** repeating fractional prices, currency-specific decimals, discounts, taxes and a partial refund;
- **When** quotes, payments and adjustments are calculated;
- **Then** all authoritative amounts use exact decimal/minor-unit semantics with documented rounding; total ledger postings reconcile without float drift.

### SCN-BR-015 — Timezone and DST consistency (`BR-015`, R1)

- **Given** an ambiguous fall-back local time, a nonexistent spring-forward local time and an ordinary UTC interval;
- **When** the buyer schedules and books each;
- **Then** the UI requires explicit valid interpretation or rejects the invalid wall-time, persists UTC interval correctly and displays the chosen IANA zone/offset unambiguously.

### SCN-BR-016 — Usability constrains inventory (`BR-016`, R1)

- **Given** a 500-slot page configuration that causes mobile overlap, inaccessible keyboard focus or dangerous CLS;
- **When** layout validation/publish occurs;
- **Then** the appropriate policy blocks publication or reports required remediation; merely having capacity does not override accessibility and performance gates.

### SCN-BR-017 — Third-party tracking requires governed data flow (`BR-017`, R2/R4)

- **Given** optional R2 heatmap tracking or R4 replay and a visitor whose applicable consent is denied;
- **When** the visitor loads eligible public advertising;
- **Then** prohibited analytics/replay collection does not start, baseline compliant ad delivery remains available, and no unapproved third-party destination receives behavioral data.

### SCN-BR-018 — Audited override with explicit authorization (`BR-018`, R1)

- **Given** a paid placement requiring migration and an operator lacking the override capability or documented reason;
- **When** the operator calls UI, REST or CLI override operations;
- **Then** denial is consistent everywhere. A duly authorized override captures justification, contract impact, and before/after values; no safety/consent gate can be overridden.

---

## 14. Extended edge and failure scenario catalog

The catalog supplements `E2E-01..17`, `UXA-01..28` and `SCN-BR-001..018`. Each row should be linked to a test artifact, fixture, execution environment, expected denial/recovery and actual observed result before it is considered verified.

| Scenario ID | Priority / release | Fixture or stimulus | Expected observable outcome |
|---|---|---|---|
| `SCN-INV-01` | P0/R1 | Inactive slot still shown in cached public catalog | Revalidate hold; purchase denied; refreshed catalog eventually reconciles |
| `SCN-INV-02` | P0/R1 | 1,000 generated slots; editor opens pagination | Stable IDs, bounded editor mount/memory and nonblocking slot navigation |
| `SCN-INV-03` | P1/R1 | Slot sequence regenerated after column count change | Logical IDs stable unless explicit migration and impact resolution |
| `SCN-INV-04` | P1/R1 | Two placement bindings intentionally render same zone | Unique render instances; no duplicated commercial entitlement |
| `SCN-RES-01` | P0/R1 | 50 concurrent holds for one exclusive weekly slot | Exactly one conflicting allocation wins; no oversell |
| `SCN-RES-02` | P0/R1 | Concurrent overlapping `[10:00,12:00)` and `[11:59,14:00)` | Second conflict detected under DB lock |
| `SCN-RES-03` | P0/R1 | Back-to-back `[10:00,12:00)` and `[12:00,14:00)` | No overlap according to half-open semantics |
| `SCN-RES-04` | P0/R1 | Booking transaction deadlock injected | Entire transaction rolled back and bounded retried safely |
| `SCN-RES-05` | P0/R1 | Hold cleanup worker delayed beyond TTL | Expired hold is invalid immediately on transactional availability check |
| `SCN-RES-06` | P0/R1 | Existing hold accidentally submitted for another organization | Owner mismatch denied without information leak |
| `SCN-PRICE-01` | P0/R1 | Price changes between catalog browse and quote issue | New quote reflects rate version; stale values not charged |
| `SCN-PRICE-02` | P0/R1 | Price changes after accepted held quote | Active quote terms respected until contractual expiry per policy |
| `SCN-PRICE-03` | P0/R1 | Discount + tax + multiple line items | Exact money reconciliation and transparent totals |
| `SCN-COM-01` | P0/R1 | Successful browser redirect without verified provider confirmation | Order still pending; campaign not paid/activated |
| `SCN-COM-02` | P0/R1 | Identical WooCommerce paid notification delivered 10 times | One payment mapping, one reservation commit, one ledger effect |
| `SCN-COM-03` | P0/R1 | Payment captured after hold expiry and competing buyer succeeds | Open remedy; do not seize competitor's inventory |
| `SCN-COM-04` | P0/R1 | Refund initiated but provider times out | `refund_pending`, no false refunded state |
| `SCN-COM-05` | P0/R1 | WooCommerce disabled immediately after installation | Plugin boots, shows online checkout unavailable, manual safe path intact |
| `SCN-COM-06` | P0/R1 | Out-of-order failed event after verified captured event | Reconcile provider state without destructive downgrade |
| `SCN-COM-07` | P0/R1 | WooCommerce HPOS enabled | CRUD adapter maps orders correctly; no postmeta-only assumption |
| `SCN-MOD-01` | P0/R1 | Moderator approves stale version while advertiser uploads new version | `409` version conflict; new version stays pending |
| `SCN-MOD-02` | P0/R1 | Approved campaign paid then creative rejected | No serving; paid remedy offered and audited |
| `SCN-MOD-03` | P0/R1 | Emergency stop during decision requests | New requests denied within policy freshness bound |
| `SCN-CRT-01` | P0/R1 | Advertiser uploads disguised JavaScript/SVG as image | Reject unsafe content, do not execute payload |
| `SCN-CRT-02` | P0/R1 | URL uses javascript/data/file scheme | Reject dangerous destination |
| `SCN-CRT-03` | P0/R1 | Remote URL check redirects to internal network address | Block SSRF and avoid private request |
| `SCN-REM-01` | P0/R1 | Paid ad rejected with unclear policy | Surface unresolved `policy_decision_required`, not arbitrary refund math |
| `SCN-REM-02` | P1/R1 | Partial campaign delivery then cancellation | Retain past delivery, compute remedy per approved contract and adjustment |
| `SCN-LAY-01` | P0/R1 | Publisher deletes sold premium slot | Block publish, identify contract impact |
| `SCN-LAY-02` | P0/R1 | New reservation created while layout draft is validated | Publish must revalidate; stale validation cannot override sale |
| `SCN-LAY-03` | P0/R1 | Roll back a published layout containing old positions | Fresh impact validation; preserve history, no blind rollback |
| `SCN-LAY-04` | P1/R1 | Responsive breakpoint hides sold mobile placement | Reject or obtain approved contractual resolution |
| `SCN-LAY-05` | P1/R2 | Layout v14 heatmap requested while v15 active | Overlay tied to v14; incompatible v15 geometry not silently used |
| `SCN-DEL-01` | P0/R1 | 500 slots with only 20 near viewport | Bounded batch loading; no assumption of 500 impressions |
| `SCN-DEL-02` | P0/R1 | Public delivery endpoint times out | Page remains usable and ads fail safely without fabricated counts |
| `SCN-DEL-03` | P0/R1 | Repeat slot in PHP and Gutenberg on one page | Distinct render IDs; correct logical slot relationship |
| `SCN-DEL-04` | P0/R1 | HTML full-page cache includes zone placeholders | Safe runtime eligibility; no wrong-user data leaked |
| `SCN-DEL-05` | P1/R1 | User scrolls rapidly to bottom of 200-ad footer | Near-view batches prioritize current viewport with stable layout |
| `SCN-DEL-06` | P1/R1 | JS disabled on a page with zone blocks | Accessible content/placeholder, documented static fallback behavior |
| `SCN-DEN-01` | P1/R1 | Four header creatives crowd narrow viewport | Responsive policy and spacing; no overlapping click targets |
| `SCN-DEN-02` | P1/R1 | Shortcode tries to bypass density limit | Limit remains server-enforced |
| `SCN-MET-01` | P0/R1 | Browser sends 100 duplicate click event IDs | At most one qualified event per dedupe policy, no double counting |
| `SCN-MET-02` | P0/R1 | Editor preview and synthetic load test | No production billable impression/click record |
| `SCN-MET-03` | P0/R1 | Forged event refers to invalid slot/render pair | Reject/quarantine; no qualifying metric/ledger post |
| `SCN-MET-04` | P1/R1 | Events delivered out of order | Aggregation remains correct or flags data quality with reconciliation |
| `SCN-MET-05` | P0/R3 | Browser-reported click attempts direct financial posting | Denied; only verified R3 billable processor posts amounts |
| `SCN-HEAT-01` | P0/R2 | 500-slot long page with different scroll depths | Correct normalized slot attribution and sample denominators |
| `SCN-HEAT-02` | P0/R2 | Same page desktop and mobile layouts differ | Separate geometry cohorts; incompatible overlays not combined |
| `SCN-HEAT-03` | P0/R2 | Consent denied in protected region | No prohibited heatmap payload emitted |
| `SCN-HEAT-04` | P1/R2 | Sticky sidebar causes varying absolute Y | Semantic anchor/slot map remains valid or events flagged unmappable |
| `SCN-HEAT-05` | P1/R2 | AJAX inserts new footer content | Version/geometry refresh or explicit excluded sample coverage |
| `SCN-HEAT-06` | P1/R2 | Sparse heatmap sample | Visible low-sample warning and numeric accessible table |
| `SCN-REP-01` | P0/R1 | Advertiser filters by another org ID | Server-enforced own-scope data only |
| `SCN-REP-02` | P1/R1 | Aggregation delayed 45 minutes | Show `as of` timestamp, not misleading current total |
| `SCN-REP-03` | P0/R2 | Export permission revoked after job ready | Download denied and link invalidated |
| `SCN-REP-04` | P1/R3 | Forecast presented near observed conversion chart | Distinct model labeling, assumptions and uncertainty |
| `SCN-FIN-01` | P0/R1 | Currency with 0 or 3 decimal minor units | Exact configured rounding; no floating point ledger drift |
| `SCN-FIN-02` | P0/R1 | Financial reversal after fulfillment | Separate adjustment, preserve original revenue/delivery history |
| `SCN-FIN-03` | P0/R3 | Fraudulent clicks included in raw observations | Excluded from qualified billable ledger by verified contract policy |
| `SCN-TIM-01` | P0/R1 | DST fall-back ambiguous 01:30 | Explicit disambiguation required |
| `SCN-TIM-02` | P0/R1 | Spring-forward nonexistent 02:30 | Reject/request valid time, do not silently normalize contract |
| `SCN-PRV-01` | P0/R2 | User denies/withdraws behavioral tracking | No prohibited new collection and buffered forbidden events discarded |
| `SCN-PRV-02` | P0/R4 | Replay probe with password, payment field, private message | No protected value ever present in collection payload |
| `SCN-PRV-03` | P0/R4 | Replay feature unavailable or disabled | Core ads and ordinary aggregates still function |
| `SCN-RET-01` | P1/R1 | Cleanup rerun after partial network error | Idempotent bounded purge and error diagnostics |
| `SCN-RET-02` | P0/R1 | Erasure request conflicts with legitimate billing retention | Separate retained legal facts; remove eligible behavioral data |
| `SCN-OVR-01` | P0/R1 | AdOps attempts override without special capability | Denied and safely audited |
| `SCN-OVR-02` | P0/R1 | Privileged override provides no business reason | Denied; no mutable backdoor |
| `SCN-AUTH-01` | P0/R1 | Advertiser B's ID passed to A's REST report endpoint | Deny/leak-free, same across list/detail/export |
| `SCN-AUTH-02` | P0/R1 | Member revoked between export request and download | Recheck current permission, deny file access |
| `SCN-AUTH-03` | P0/R1 | Site admin tries to access other multisite tenant ID | Default deny without network-specific validated scope |
| `SCN-UX-01` | P0/R1 | Campaign wizard interrupted at each step | Saved state restored or explicit unsaved-state recovery |
| `SCN-UX-02` | P0/R1 | Keyboard-only publisher edits a 200-slot grid | Complete functional alternative to drag-and-drop |
| `SCN-UX-03` | P1/R1 | RTL language and mobile viewport | Readable correctly ordered purchase terms, dates, money and errors |

**Priority interpretation:** `P0` scenarios block the stated release if failing; `P1` scenarios must be planned and resolved to approved budgets/quality gates before production release when they apply. Test priority never overrides mandatory `FR`, `BR`, `NFR` or release requirements.

---

## 15. Unresolved product decisions and mandatory decision register

The following are carried forward **unchanged** from ADP-DOC-001. They are not implementation defaults that may silently become contractual promises. The accountable owner must approve each before work that depends on it is released.

| Decision | Existing source question | Proposed path / gating effect | Sign-off deadline |
|---|---|---|---|
| `DEC-001` | Supported WordPress/PHP/database matrix | Freeze tested minimums, prefer PHP 8.2+ as proposed; no universal claim | Phase 0/2 |
| `DEC-002` | First online checkout provider | R1 WooCommerce adapter; no mandatory dependency for bootstrap | Phase 0/6 |
| `DEC-003` | Complimentary/manual advertising authorization | Site-admin governed commercial exception, **not** an invented paid transaction | Phase 0/6 |
| `DEC-004` | R1 creative types | Raster and sanitized text/native templates; interactive requires distinct review | Phase 0/5 |
| `DEC-005` | Tracking consent policy defaults | Purpose/region matrix, nonessential behavioral collection gate | Phase 0/8 |
| `DEC-006` | Raw event and replay retention | Approve bounded defaults and applicable legal exceptions before data collection | Phase 0/7 |
| `DEC-007` | Hold TTL, creative correction and refunds | Product/finance-approved terms with transparent customer disclosure | Phase 0/6 |
| `DEC-008` | Theme/browser integration matrix | Test supported classic/block themes and stated builders; no arbitrary universal promise | Phase 0/4 |
| `DEC-009` | Multisite support | Site isolation first; R4 centralized federation | Phase 0/10 |
| `DEC-010` | Metered billing models | R3 qualification/reconciliation gates required before chargeable CPC/CPM | Phase 0/9 |
| `DEC-011` | Product name/slug clearance | AdPilot provisional pending trademark/WordPress.org validation | Before launch |
| `DEC-012` | Initial language set | English reference, translation/RTL-ready R1; freeze supported locale matrix | Phase 0/1 |

### 15.1 Additional decisions introduced by this specification (must not silently override above)

| Proposed decision | Owner | Decision needed | Recommended interim treatment |
|---|---|---|---|
| `D04-DEC-01` | Finance/Product | When a paid hold expires before authoritative capture, what alternative/refund deadlines apply? | Enter `remediation_required`; no automatic allocation |
| `D04-DEC-02` | AdTech/Finance | Contractual definition of a premium slot's position/size and acceptable responsive substitutions | Block materially harmful layout migration until resolved |
| `D04-DEC-03` | Security/Product | Which emergency-stop roles and whether dual control are required? | Restrict to designated privileged operators and audit |
| `D04-DEC-04` | AdTech/Performance | Maximum eligible ad density per device/network and enforcement vs warning | Fail closed for actual prohibitions; warn for nonbinding UX budgets |
| `D04-DEC-05` | Ad Delivery/Operations | Bounded cache freshness on ordinary pause and emergency stop | Use fresh deny gate; benchmark and approve measurable bound |
| `D04-DEC-06` | Finance/Tax | Exact tax handling, supported currencies, rounding, invoice legal fields | Disable unsupported tax scenarios rather than invent compliance |
| `D04-DEC-07` | Analytics/Privacy | R2 event aggregation granularity, sample thresholds, suppression of tiny cohorts | Do not expose reidentifiable aggregates; label sparse results |
| `D04-DEC-08` | Product/Finance | Policy for advertiser-initiated pause on time-based purchased reservation | Keep commercial entitlement and billing distinct until approved rule |
| `D04-DEC-09` | Product/Finance | Manual/complimentary contract authority and tax/invoice representation | Limit to explicit authorized administrative contract type |
| `D04-DEC-10` | Security/Privacy | R4 replay enablement, sensitive-field fixture, access and retention controls | Disabled entirely until approved and tested |

Each decision record must state: ID, owner, context, selected alternative, rationale, affected requirements, date, security/privacy/financial impacts, compatibility, tests, and backward-migration strategy. When still open, mark it **OPEN** and gate dependent functionality; never write a fictional answer into code or UI.

---

## 16. Test harness, fixtures and evidence protocol

### 16.1 Required deterministic test fixtures

- **Inventory:** one site with 4 header + 100 sidebar + 196 in-content + 200 footer logical slots; a separate 1,000+ slot stress page; duplicate zone renderers; active and unpublished bindings.
- **Commercial:** fixed-priced daily/weekly and sponsorship contracts, exclusive overlaps, adjacent nonoverlapping windows, active holds, expired holds, refunds and payment callbacks.
- **Commerce:** WooCommerce on/off, verified order mappings, HPOS, delayed/duplicate/out-of-order payment evidence, abandoned checkout and late capture.
- **Creative:** approved V1, changed/unapproved V2, missing alt, malicious markup, unsafe redirects, prohibited advertiser and emergency stop.
- **Layout:** v12 sold premium slot, v13 materially changed position, newer concurrent reservation, old geometry snapshots and RTL/mobile breakpoint variants.
- **Analytics:** real/synthetic/staging render contexts, event duplicates, untrusted client signatures, viewable/nonmeasurable distinctions, delayed aggregates, consent denied, long-page scroll and sticky sidebar coordinates.
- **Authorization:** two organizations, owner/manager/analyst/billing users, revoked membership, content editor, moderator, finance, AdOps, site admin, multisite foreign-site IDs.
- **Operations:** crashed/suspended worker, lost external callback, database deadlock, full-page CDN cache, unavailable analytics, missing WooCommerce, failed retention cleanup.

### 16.2 Test layers

| Layer | What it proves | Implementation approach |
|---|---|---|
| Pure unit | Invariants, money/time value objects, decision tables, state transition validity | PHPUnit isolated domain services |
| WordPress integration | Capabilities, REST permissions, hooks, Gutenberg server render, activation/migrations | WordPress test runner + database |
| Commercial transaction | Holds/commits, competing sessions, WooCommerce mapping, adjustments | Real supported MySQL/MariaDB + integration fixture |
| Browser E2E | Wizard, Layout Studio, slot preview, denied access, fallback and screen states | Playwright supported browser matrix |
| API security | Cross-tenant retrieval, CSRF, object ownership, rate limits, replay/SSRF attempts | Automated negative API suites |
| UX/accessibility | Keyboard navigation, focus, screen-reader names, visual clarity, mobile/RTL | axe automation plus manual review |
| High-density performance | 500+ slots, batching, memory, Core Web Vitals, queue lag, caching | Reproducible benchmark with machine/env evidence |
| Privacy & behavioral | No sensitive fields, consent switching, geometry fidelity, retention | Synthetic DOM and consent-policy tests |
| Financial reconciliation | Correct totals, duplicates, dispute/correction lineage, no float drift | Deterministic fixtures + ledger differential checks |

### 16.3 Evidence format for each completed test

```text
Test ID:
Source requirement IDs:
Release and feature flag:
Test owner/reviewer:
Environment: WordPress/PHP/DB/WooCommerce/browser/version
Fixture identifiers and seed:
Preconditions and actor permissions:
Command sequence or user steps:
Expected invariant and UI outcome:
Actual observed result:
Logs / screenshots / traces (redacted):
Pass / Fail / Blocked / Not run:
Defect IDs and follow-up:
Date and revision tested:
```

Do not mark a test `PASS` because an AI model produced code for it; only actual execution or explicitly documented human verification counts as evidence. If a test is blocked, retain its release-blocking status when it protects safety, money, privacy or access control.

### 16.4 Critical release gates

**R1:** `BR-001..018` applicable R1 portions, `AC-001..015`, `AT-*` for R1, own-organization authorization, transaction-safe booking, idempotent checkout/mapping, approved creative delivery, 500-slot rendering, Gutenberg/theme integration, nonbillable preview, robust fallback, accessible commerce flows and GPL-compatible deliverable package.

**R2:** Add viewability measurement qualification, native privacy-aware aggregated heatmaps with correct layout version/device mapping, protected exports, consent enforcement and accessible numerical alternatives.

**R3:** Add certified-by-internal-acceptance (not externally accredited) metered CPM/CPC policy and reconciliation, billable event definitions, adjustments/disputes, targeting/pacing and statistically validated experiments.

**R4:** Add restricted opt-in replay with tested masking/retention, optional provider/network features and clear failure isolation.

---

## 17. GPT-6 High implementation work-package template

The execution model must work in small **vertical slices**, not by generating every service, DB table and UI independently. Each slice covers authorization/policy → schema/repository → application command → REST adapter → frontend interaction → tests → docs.

### Required task header

```yaml
work_package: ADP-WP-XXX
release: R1
business_goal: "one verifiable user-visible outcome"
canonical_requirements: ["FR-COM-003", "BR-005", "NFR-006"]
journeys_and_acceptance: ["UJ-04", "WF-05", "AT-COM-003", "SCN-BR-005"]
policy_contracts: ["DP-RES", "SM-HOLD"]
actors_permissions: ["adpilot_purchase_inventory", "organization_owned"]
source_documents: ["ADP-DOC-001", "ADP-DOC-002", "ADP-DOC-003", "ADP-DOC-004"]
expected_files_and_modules: ["Reservations", "Inventory", "Tests"]
forbidden_shortcuts:
  - "frontend-only capacity checks"
  - "nontransactional overlapping exclusive bookings"
  - "fake test success"
required_proof:
  - "concurrent exclusive booking test against supported DB"
  - "correct conflict/status UI"
  - "idempotent retry behavior"
reviewers: ["Database Lead", "Finance Lead", "QA Lead"]
```

This YAML is a **template**, not a declaration of completed work. For each work package:

1. Read the canonical requirements and previous modules; record ambiguity without inventing a business decision.
2. Define DTOs/contracts, state transitions and invariants before writing controllers or components.
3. Implement server-enforced policy with database transaction and authorization checks where necessary.
4. Implement corresponding user-facing workflow, including loading, conflicts, denial, stale state, screen-reader feedback, mobile/RTL.
5. Write positive, negative, concurrent/retry and recovery tests proportional to risk.
6. Run the actual configured commands; record results and any blockers.
7. Update test/requirement maps, API and migration docs; document changed event schemas.
8. Request independent approval for financial, privacy, creative security or sold-inventory exceptions.

### 17.1 Recommended first vertical slices

| Slice | Outcome | References |
|---|---|---|
| `WP-01` | Tenant-aware identity/capability and organization isolation | DP-AUTH, PERM-001/002/016 |
| `WP-02` | Sellable zone and stable slot identity | DP-INV, INV-03, FR-INV-001..005 |
| `WP-03` | Quote + exclusive hold transaction | DP-PRC/RES, SM-QUOTE/HOLD, SCN-RES-01..06 |
| `WP-04` | Basic creative review with safe media versions | DP-CRT/MOD, SM-CREATIVE, SCN-MOD/CRT |
| `WP-05` | Shared policy-gated ad render in Gutenberg and PHP | DP-DEL, SM-DELIVERY, AC-002..004 |
| `WP-06` | WooCommerce linked order and verified payment | DP-COM/FIN, SM-ORDER/PAYMENT, SCN-COM-01..07 |
| `WP-07` | Layout version publish with sold-slot impact | DP-LAY/OVR, SM-LAYOUT, SCN-LAY-01..05 |
| `WP-08` | Qualified R1 measurement and basic reports | DP-MET/REP, SCN-MET, SCN-REP |
| `WP-09` | R2 heatmap visualization with consent and layout mapping | DP-HEAT/PRV, SCN-HEAT/PRV |

The ordering is dependency-oriented, not a license to skip full product milestones, frontend design or formal QA.

---

## 18. Document completeness audit and next handoff

Before publishing a revision, verify:

1. Every baseline `BR-001..018` appears **once** in the exact rule register, with enforcement owner, policy and negative acceptance link.
2. Every baseline `INV-01..10` is preserved with an implementation enforcement owner.
3. Every one of the 129 `FR-*` IDs retains its source release scope and has a matching `AT-*` reference.
4. `NFR-001..016`, `AC-001..015`, `WF-01..18`, `UJ-01..28` and `UXA-01..28` appear in the appropriate crosswalk.
5. Payment, creative approval, booking, campaign state and delivery remain independently authoritative.
6. An expired hold can never be revived by a late payment without a fresh governed booking.
7. Overlapping exclusive inventory is protected by actual transactional consistency, not advisory checks.
8. Sold layout changes cannot silently change commercial rights or historical analytics geometry.
9. All repeated zones use stable logical slot identity and distinct per-occurrence render identity.
10. Heatmap and R4 replay are privacy-gated, with no false eye-tracking claims.
11. Every high-risk action has an authorization predicate, audit effect, error response and executable negative test.
12. Unresolved refund, consent, tax, retention and legal decisions remain visible and block dependent implementation.
13. Future-release behavior is not accidentally presented as implemented in R1.
14. Test results in this document remain specifications, **not assertions of execution**.

### Next document — ADP-DOC-005

**Roles, Capabilities, Permission Matrix, Multi-Tenant Authorization & Access-Control Specification.** It will freeze WordPress capability registration, organization membership data model, site/network scoping, per-route/per-object authorization rules, permission administration UX, credential/session lifecycle and exhaustive access-control tests. It must refine `DP-AUTH`, `PERM-*` and the matrix above without weakening their conditions.

### Source document index

- **ADP-DOC-001:** `01_ADPILOT_PRODUCT_REQUIREMENTS_DOCUMENT.md` — canonical product scope and business rules.
- **ADP-DOC-002:** `02_ADPILOT_DETAILED_FEATURE_CATALOG_TRACEABILITY_ACCEPTANCE.md` — detailed feature obligations and acceptance tests.
- **ADP-DOC-003:** `03_ADPILOT_USER_PERSONAS_JTBD_USER_JOURNEYS_WORKFLOWS.md` — personas, user journeys, UX states and workflow contracts.
- **ADP-DOC-004 (this document):** policy enforcement, separate state machines, authorization scenarios, consistency boundaries, error/audit contracts, traceability and negative tests.

---

**Document-end checkpoint:** Business-rule and traceability structure authored; implementation and test execution are **not** represented as complete. Any changes to contractual interpretation, refund, privacy/legal defaults or release obligations require reviewed decisions and coordinated updates across all canonical documents.
