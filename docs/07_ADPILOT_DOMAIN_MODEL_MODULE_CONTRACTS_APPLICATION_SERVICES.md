# AdPilot — Domain Model, Module Contracts & Application Services

**Document ID:** ADP-DOC-007  
**Filename:** `07_ADPILOT_DOMAIN_MODEL_MODULE_CONTRACTS_APPLICATION_SERVICES.md`  
**Version:** 1.0.0 (implementation contract proposal)  
**Status:** Proposed for Product Owner, Architecture, AdTech, Finance, Security, UX and QA sign-off  
**Date:** 2026-10-11  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Document owner:** Principal Software Architect / Technical Lead  
**Primary implementers:** WordPress/PHP domain engineers, API engineers, database engineers, delivery and measurement engineers, frontend/Gutenberg engineers, commerce, security/privacy, QA, DevOps and technical writers  
**Implementation target:** GPT-6 High, bounded implementation packages with explicit evidence and stop conditions  
**Normative predecessors:** ADP-DOC-001 through ADP-DOC-006  
**Next proposed document:** ADP-DOC-008 — Database Schema, Migrations, Indexes & Data Lifecycle Specification

> **Implementation status and interpretation.** This document is a specification, not executable plugin code and not a claim of implemented behavior. PHP excerpts show intended contracts; they omit some framework wiring, serialization, imports, error handling, and integration details by design. They SHALL be completed and tested against the approved runtime and source documents before production use. Proposed class names and supplementary IDs are subject to architecture sign-off. This document MUST NOT silently change source requirements or business policies.

---

## 1. Authority, purpose and document conventions

### 1.1 Purpose

Translate the approved conceptual architecture into the actual *business objects, ownership boundaries, value semantics, application operations and module-to-module interfaces* needed to implement AdPilot. The objective is to eliminate guesswork when building campaign purchases, high-density layout inventory, WordPress/Gutenberg rendering, safe advertising delivery, verifiable measurement, and later heatmap/advanced monetization features.

The target is one self-hosted WordPress plugin: a **modular monolith**, with PHP domain/application services, WordPress infrastructure adapters, React/TypeScript for rich UIs, and a small public ad SDK. Do not introduce independently deployed microservices as an R1 prerequisite.

### 1.2 Governing document precedence

| Order | Document | Authority retained |
|---|---|---|
| 1 | ADP-DOC-001 PRD | 129 `FR-*` requirements, `BR-001..018`, `INV-01..10`, `NFR-001..016`, `AC-001..015`, release `R1..R4`, unresolved `DEC-*` decisions |
| 2 | ADP-DOC-002 Feature Catalog | `CAP-01..18`, `AT-*`, `E2E-01..17`, `UI-01..20`, functional ownership and acceptance obligations |
| 3 | ADP-DOC-003 Personas/Journeys | `PERS-01..16`, `JTBD-01..24`, `UJ-01..28`, `WF-01..18`, `UXA-01..28`, front-end state and recovery behavior |
| 4 | ADP-DOC-004 Domain Policies | `DP-*`, `SM-*`, `PERM-001..016`, negative cases; accepted state/legal/financial invariants |
| 5 | ADP-DOC-005 Authorization | WordPress site capabilities, advertiser organization memberships, multisite and access policies; supplementary `D05-*` tests |
| 6 | ADP-DOC-006 Architecture | 18 bounded modules, `IC-001..024` contract registry, `ARCH-BND-*`, ADRs, `AR-T-*` architecture acceptance |
| 7 | **ADP-DOC-007** | Adds `DM-*` entities, `VO-*` values, `UC-*` use cases, `EV-*` events, `D07-T-*` tests and implementation details, without renaming the above |

If a policy is unresolved (for example contract remedies, tax, geographic consent rules, guarantees, retention period), the implementation SHALL expose it as **BLOCKED/POLICY_REQUIRED** rather than invent a commercial/legal default. No implementation agent may redefine a canonical identifier's meaning. Release scope is determined by the PRD and Feature Catalog, not by which classes exist in source code.

### 1.3 Normative terminology

- **MUST/SHALL:** mandatory in the release where the capability is offered; **SHOULD:** expected unless explicitly justified; **MAY:** optional.
- **Aggregate:** transactional consistency boundary protecting a cluster of entities/value objects, not an automatic one-to-one table mapping.
- **Root:** object through which aggregate invariants and state transitions are enforced.
- **Domain event:** immutable *committed fact*, emitted through outbox when required, distinct from an untrusted browser event.
- **Application command:** authorization-aware request to change authoritative state; **query:** scoped request for a safe projection.
- **Policy:** server-enforced decision; **presentation rule:** UI affordance with no independent authorization authority.
- **Site scope:** WordPress site/blog; **advertiser tenant:** site-scoped organization. These two scopes must both be represented.
- **Logical slot:** commercial inventory identity; **render instance:** specific occurrence in a page or runtime context; **creative version:** immutable approved review target.
- **Configured/selected/served/rendered/measurable/viewable/qualified/billable:** distinguishable states and metrics; never interchange them.

### 1.4 Out of scope for this document

Concrete SQL `CREATE TABLE` definitions, API OpenAPI files, full `block.json`, complete React component code, exact tax rules, production legal advice, measured performance budgets and final license audit belong to later specifications. This document defines contracts that constrain those deliverables. R4 independent workers, replay, and network federation remain optional extensions; R3 CPC/CPM billing remains separate from R1 fixed/time sales.

---

## 2. Domain-driven design constraints and aggregate boundaries

### 2.1 Domain boundary principles

1. Each authoritative fact has **one owner module**; other modules receive typed projections, not permission to modify foreign tables.
2. Domain objects are independent of `$wpdb`, `WP_User`, `WC_Order`, `WP_REST_Request`, Gutenberg, HTTP, browser globals and mutable WordPress options.
3. Application services receive a trusted `ActorContext` or verified service principal and an explicit `SiteContext`; an arbitrary site/org ID from the client never confers authority.
4. Aggregate invariants are checked on state transition, not only at REST validation or checkout preview.
5. Complex cross-module operations use a transaction where one database controls all required writes; external gateways are handled through idempotent orchestration and compensating actions.
6. Published revisions, accepted quotes, creative approvals and ledger postings are immutable **facts**; corrections create new versions or entries.
7. IDs and times are stable, validated and explicit. All financial and contractual time boundaries are UTC, with the agreed IANA time zone saved for human display.
8. Read models may be eventually consistent if their freshness is labeled. Contract entitlement, capacity reservation and authorization checks may not.
9. No application service modifies a financial amount or commercial reservation just because an event counter or frontend preview changed.
10. Aggregate size stays bounded: **500 generated slots are not a 500-child in-memory aggregate**. Materialize individual identities only when commercially addressed or otherwise required.

### 2.2 Proposed aggregate-root catalog (`DM-*`)

| ID | Owning module | Aggregate root | Authoritative responsibility | Explicit consistency boundary |
|---|---|---|---|---|
| `DM-001` | Advertisers | `Organization` | Organization profile, status and branding | Profile/status mutations and audit; member grants evaluated separately |
| `DM-002` | Identity | `OrganizationMembership` | One user's current role/status inside an org/site | Grant/revoke with unique active site+org+user membership |
| `DM-003` | Advertisers | `MembershipInvitation` | Single-use invitation offer | Expiry, token hash, accepted/revoked transitions |
| `DM-004` | Inventory | `Zone` | Stable advertising region identity, purpose and status | Zone configuration and commercial class |
| `DM-005` | Inventory | `ContainerDefinition` | Typed layout, responsive rules and compact generator | Versioned configuration; no unbounded slot children |
| `DM-006` | Inventory | `SlotIdentity` | Stable logical, commercially addressable slot | No silent reassignment or destructive reuse |
| `DM-007` | Layout | `LayoutDraft` | Mutable candidate structure and its validation state | Optimistic revision edits, contract-impact markers |
| `DM-008` | Layout | `PublishedLayoutRevision` | Immutable configuration/snapshot and active publication pointer | Atomic active-pointer switch with impact review |
| `DM-009` | Campaigns | `Campaign` | Objective, organization ownership, schedule intent, approval/delivery references | Version-checked lifecycle and ownership |
| `DM-010` | Creatives | `Creative` | Logical creative and immutable media/destination versions | Changed version requires review per policy |
| `DM-011` | Creatives | `CreativeReview` | Approval or rejection of one exact version | Reviewer, version/digest and reason immutable |
| `DM-012` | Pricing | `RateCardVersion` | Published sellable rate conditions | Accepted revision never mutates |
| `DM-013` | Pricing | `Quote` | Immutable commercial offer snapshot, expiry and currency | Acceptance validates rate, availability, policy |
| `DM-014` | Reservations | `InventoryHold` | Temporary capacity claim and expiration | Lock and re-read inventory before mutation |
| `DM-015` | Reservations | `ContractReservation` | Purchased inventory, period, exclusivity and allocation | Prevent overlapping exclusive commitments |
| `DM-016` | Commerce | `AdvertisingOrder` | Commercial purchase and source contract snapshot | State changes from verified financial source |
| `DM-017` | Commerce | `PaymentAttempt` | Verified provider attempt/authorization/capture evidence | Idempotency and provider reference uniqueness |
| `DM-018` | Commerce | `FinancialLedgerEntry` | Immutable debit/credit/adjustment financial fact | Append-only, auditable money change |
| `DM-019` | Commerce | `CommercialRemedy` | Remedy obligations for failed/rejected purchase | Resolution, approvals, deadlines and notices |
| `DM-020` | Delivery | `AllocationPolicy` | Campaign-to-slot delivery selection/pacing instructions | Never substitutes for contractual reservation |
| `DM-021` | Measurement | `QualifiedMeasurementBatch` | Event validation and aggregation provenance | Bounded deduplication, final metric classification |
| `DM-022` | BehavioralAnalytics | `HeatmapAggregate` | Privacy-filtered counts for geometry/version and period | Aggregated, non-personal read permissions |
| `DM-023` | Reporting | `ReportExportJob` | Request, authorized report scope, artifact status | Permission recheck at retrieval |
| `DM-024` | Compliance | `ConsentRecord` | Captured permission/purpose status and change history | Never override applicable consent policy via UI |
| `DM-025` | Optimization | `Experiment` | Allocation/randomization definition and lifecycle | Controlled exposure and statistical provenance (R3) |
| `DM-026` | Notifications | `NotificationIntent` | Delivery intent and recipient scope | Current permissions and deduplicated dispatch |
| `DM-027` | Integrations | `WebhookSubscription` | Allowed topics, destination, signing key reference | Secret rotation, retry and authorization |
| `DM-028` | Platform | `OutboxMessage` | Persisted domain fact for reliable async consumers | Created with authoritative transaction |

Not every aggregate needs a separate table; do not force 28 top-level persistence schemas. Detailed relational DDL and uniqueness/index constraints belong to ADP-DOC-008. Conversely, do not disguise financial and reservation tables as post metadata.

### 2.3 Entity versus value object

Entities retain identity across revisions (`ZoneId`, `CampaignId`, `SlotId`). Value objects compare by validated content (`Money`, `UtcInterval`, `TargetingRules`, `ResponsiveGrid`, `QuoteBreakdown`). Domain services coordinate rules spanning several roots (`ExclusiveCapacityChecker`, `ContractImpactAnalyzer`, `DeliveryEligibilityEvaluator`). Repository persistence and event outbox are infrastructure concerns.

### 2.4 Aggregate lifecycle and mutation restrictions

| Aggregate | Allowed mutation style | Never permitted |
|---|---|---|
| `Campaign` | Transition methods with expected revision and actor policy | Arbitrary `setStatus('active')` bypassing delivery gates |
| `Creative` | Add new version; moderate exact version | Edit approved media/destination in place |
| `Quote` | Create, accept, expire as explicit transitions | Reprice an accepted quote after checkout |
| `InventoryHold` | Acquire, explicitly extend after revalidation, consume, expire | Reanimate expired hold by payment redirect |
| `ContractReservation` | Create under transaction, amend/remedy via controlled workflow | Change sold slot meaning by editing UI coordinates |
| `PublishedLayoutRevision` | Create immutable revision + activate pointer | Modify published snapshot row in place |
| `AdvertisingOrder` | Verified state transition + outbox | Mark paid from browser callback/query flag |
| `FinancialLedgerEntry` | Append fact or correcting entry | DELETE/UPDATE previously posted amount |
| `HeatmapAggregate` | Recompute from eligible source data with versioned definition | Mix geometry from different layout versions silently |

---

## 3. Core value objects, type semantics and identifiers

### 3.1 Required value-object catalog

| ID | Value object | Fields / validation | Use |
|---|---|---|---|
| `VO-001` | `SiteId` | Positive WordPress blog/site integer from trusted context | Isolation on all tenant-owned records |
| `VO-002` | `OrganizationId` | Opaque immutable ID; always paired with `SiteId` | Advertiser ownership |
| `VO-003` | `ActorContext` | Actor type, WP user ID or service principal, verified site, permission version, purpose | Authorization/audit |
| `VO-004` | `EntityId` family | Typed opaque IDs for zone, container, slot, campaign, creative, order, hold, revision | Prevent ID-type confusion |
| `VO-005` | `EntityVersion` | Monotonically increasing integer; expected version required for conflict-prone writes | Compare-and-swap |
| `VO-006` | `UtcInstant` | Timezone-aware UTC instant, no ambiguous local datetime | Scheduling and audit |
| `VO-007` | `UtcInterval` | Half-open `[start,end)`, start less than end | Conflict/overlap math |
| `VO-008` | `DisplayTimezone` | Valid IANA time zone and explicit DST ambiguity policy | Human-readable contracts |
| `VO-009` | `Money` | Currency ISO code + exact decimal/minor-unit amount; no binary floats | Pricing and ledger |
| `VO-010` | `PriceBreakdown` | Subtotal, discounts, taxes, grand total, rule snapshot | Quote display and reconciliation |
| `VO-011` | `IdempotencyKey` | Nonempty, bounded opaque token scoped by operation/site/actor | Retry suppression |
| `VO-012` | `CorrelationId` | Nonsecret trace identifier, no personal/financial payload | Diagnostics |
| `VO-013` | `SlotCapacity` | Nonnegative integer plus exclusivity/share policy | Booking |
| `VO-014` | `NormalizedPosition` | X/Y in `[0,1]` relative to verified anchor | Heatmap geometry |
| `VO-015` | `ResponsiveLayout` | Validated breakpoints, cols, gaps, widths, aspect ratios | Layout compilation |
| `VO-016` | `CreativeDestination` | Allowed scheme, validated URL and redirect policy | Safe clicks |
| `VO-017` | `CreativeFingerprint` | Digest over moderation-relevant immutable fields | Review exactness |
| `VO-018` | `ConsentPurpose` | Named purpose with region/configuration-specific policy | Data collection |
| `VO-019` | `TargetingPredicate` | Allowlisted normalized conditions, no raw executable code | Delivery |
| `VO-020` | `PageContext` | Verified site, post/template, locale, device context, preview flag | Placement resolution |
| `VO-021` | `MetricDefinitionVersion` | Semantic version + valid denominator/filters | Reporting and billing |
| `VO-022` | `EventIdentity` | Bounded event ID + decision/render IDs + site | Deduplication |
| `VO-023` | `ReportScope` | Authorized time, sites, orgs, campaign and visibility fields | Exports/reports |
| `VO-024` | `DecisionSnapshot` | Selected creative and authoritative eligibility version references | Evidence and safe response |
| `VO-025` | `DataRetentionPolicy` | Per-class retention, purge schedule and legal holds | Privacy operations |
| `VO-026` | `SignedProviderEvidence` | Verified provider identity + event key + mapped order/attempt | Payment trust |
| `VO-027` | `FeatureGate` | Release tier, prerequisites, policy readiness and required services | Server-side capability gating |
| `VO-028` | `PolicyVerdict` | `allow | deny | requires_action`; code, evidence and safe client hint | Consistent policy outcomes |

### 3.2 Money and arithmetic

`Money` SHALL carry currency and precision semantics explicitly; all posted amounts use integer minor units where supported by the agreed currency configuration or exact fixed-point decimals. Reject cross-currency addition without an explicit exchange-rate transaction; do not assume every currency has two minor units. Financial calculations MUST be checked for rounding/overflow and balanced by currency. The payment provider's settled currency may differ from displayed pricing; store conversion and fee facts rather than silently reinterpreting values.

**Example, illustrative (not a full production class):**

```php
<?php
namespace AdPilot\Shared\Domain\ValueObjects;

final readonly class Money
{
    public function __construct(
        public string $currencyCode,
        public string $minorUnits // Signed base-10 integer string; validated on construction.
    ) {
        // Implementation MUST validate currency precision and numeric bounds.
    }

    public function add(self $other): self
    {
        if ($this->currencyCode !== $other->currencyCode) {
            throw new \DomainException('currency_mismatch');
        }
        // Use a tested exact-arithmetic adapter. No float casting.
        return new self($this->currencyCode, ExactInteger::add($this->minorUnits, $other->minorUnits));
    }
}
```

`ExactInteger` is an **unimplemented example port**, not a built-in PHP class. Choose and license-review a tested exact-arithmetic strategy, then benchmark it and cover currency corner cases.

### 3.3 Time intervals, time zones and DST

- Persist contractual start/end as UTC instants and original IANA display time zone.
- Define intervals as half-open: overlap is `A.start < B.end && B.start < A.end`. Back-to-back reservations that share only a boundary do not overlap.
- Validate nonexistent/ambiguous local times around daylight-saving changes; require explicit handling rather than silently shifting purchases.
- Store clock time source, and inject a test clock; never use an HTTP-provided `now` as trusted business time.
- Use monotonic duration where required for browser viewability, not wall-clock arithmetic susceptible to clock jumps.

### 3.4 Identity and tenant-scoping

Every object-scoped command requires a trusted site. Advertiser-owned resources must also be checked against the *current* organization membership, organization state, actor, permission scope and protected object owner. `OrganizationId` on its own is not an authorization credential. IDs in public blocks/shortcodes are only references to safe public rendering, never privileges to see private records.

### 3.5 Layout identity and coordinates

`SlotId` is independent of column number, vertical Y position or block DOM ID. `RenderInstanceId` is unique per on-page occurrence; `LayoutRevisionId`, `GeometrySnapshotId`, device/viewport class and anchor ID are required to interpret historical heatmaps. A 200-slot footer generator should persist compact sequence rules plus stable commercially addressed exceptions, not 200 Gutenberg blocks.

---

## 4. Application contract conventions

### 4.1 Common command envelope

Every sensitive command SHALL carry or resolve the following information:

| Field | Required? | Purpose |
|---|---|---|
| `operation` | Yes | Stable named use case and audit action |
| `actor` | Yes | Trusted current WP actor or verified service principal |
| `siteId` | Yes | Explicit authorized WordPress site |
| `organizationId` | When advertiser-owned | Tenant scope; resolved against object and membership |
| `targetId` | When mutating existing object | Typed identity; not trusted as ownership proof |
| `expectedVersion` | Conflict-prone writes | Optimistic concurrency |
| `idempotencyKey` | Checkout, payments, hold, accounting, retries | At-most-one effect per operation key |
| `correlationId` | Yes | Cross-service evidence without personal data |
| `reason` | Elevated or destructive changes | Human-readable restricted audit justification |
| `requestedAt` | Optional evidence | Caller time not trusted for financial expiration |
| `payload` | Yes | Versioned, validated operation-specific DTO |

Authorize once at use-case admission **and** revalidate current business state/authority at commit or immediately before a sensitive external side effect where revocation matters. Do not place an untrusted full `ActorContext` JSON object on a public API and assume it is verified.

### 4.2 Command result types

Use an explicit result envelope with operation ID, success/domain error, safe response DTO, authoritative version, correlation ID, relevant warning(s), and whether the result was replayed from idempotency storage. Authorization failures may conceal object existence (`404` versus `403`) per the established threat policy. No raw exception trace is returned to frontend consumers.

### 4.3 Query contract rules

Queries require trusted site scope and purpose-limited projection. Each response must identify a freshness timestamp where eventual consistency applies. Tables use pagination and bounded filters; large slot sequences are queried by range/page. Advertiser projections must exclude raw behavioral sessions, other advertisers' spend, privileged commercial notes and high-risk audit data.

### 4.4 Interfaces and dependency injection

The following shows the intended architecture of adapters. Signature details are illustrative, and the final interfaces belong under owning-module application ports or published contracts:

```php
<?php
namespace AdPilot\Shared\Application\Contracts;

interface CommandBus
{
    public function dispatch(object $command, TrustedExecutionContext $context): CommandResult;
}

interface QueryBus
{
    public function ask(object $query, TrustedExecutionContext $context): QueryResult;
}

interface TransactionManager
{
    /** Invocation must own or explicitly join a known transactional boundary. */
    public function transactional(callable $operation): mixed;
}

interface Clock
{
    public function nowUtc(): \DateTimeImmutable;
}

interface DomainEventOutbox
{
    /** Persist a committed business fact in the same DB transaction. */
    public function append(DomainEvent $event): void;
}
```

`TrustedExecutionContext`, `CommandResult`, `QueryResult` and `DomainEvent` are proposed project types and need concrete definitions and tests. WordPress wrappers call application entrypoints; domain models never call `current_user_can()`, `get_current_user_id()`, or WooCommerce APIs directly.

### 4.5 Explicit error taxonomy (`ERR-*` proposal)

| Error code | Suggested HTTP mapping | Recovery | Information exposure policy |
|---|---|---|---|
| `unauthenticated` | 401 | Login / reauthenticate | No object details |
| `forbidden` | 403 or cloaked 404 | Request owner authorization | No foreign tenant identity |
| `feature_unavailable` | 409/501 | Explain release/configuration gate | No pretend feature success |
| `validation_failed` | 422 | Field-level correction | Sanitized error details |
| `version_conflict` | 409 | Refresh/review changes | No silent overwrite |
| `inventory_conflict` | 409 | Select alternative capacity | Minimal inventory facts |
| `hold_expired` | 410 | Get new quote/hold | No revival by browser action |
| `quote_stale` | 409 | Requote | Show authorized difference |
| `approval_required` | 409 | Moderator workflow | Safe reason only |
| `contract_remedy_required` | 409 | Finance/AdOps remediation | Auditable pending state |
| `provider_unverified` | 202/409 | Reconcile later | Do not assert paid |
| `idempotency_mismatch` | 409 | New approved key or investigate | Do not double post |
| `privacy_policy_denied` | 403/204 | Suppress optional collection | Do not disclose collection details |
| `transaction_unavailable` | 503 | Disable unsafe purchasing; diagnose | Never continue non-atomically |
| `retryable_dependency_failure` | 503 | Bounded retry/backoff | Correlation ID only |
| `internal_error` | 500 | Log and safe fallback | No stack/SQL/secret leakage |

For public ad decisions, an intentionally empty, policy-safe result may be preferable to a visible error. A settlement failure, conversely, must remain in an explicit recoverable finance state rather than being silently swallowed.

---
## 5. Module ownership, published contracts, and application operations

The following 18 bounded modules reproduce the owning boundaries of ADP-DOC-006. A `UC-*` name identifies a proposed implementation use case, not a new PRD requirement. Modules may be grouped into smaller packages when doing so reduces boilerplate without weakening module ownership. All commands/queries must use the common envelope, authorization, error, audit and idempotency rules of §4.

### 5.1 Identity / Authorization (`MOD-IDENTITY`, R1)

**Purpose:** Supply authoritative actor, site and current organization grants to every protected use case; enforce `DP-AUTH` and ADP-DOC-005. **Owns:** active memberships, invitation acceptance authorization decisions, capability policy evaluation and access decision audit. Organization profile facts come from Advertisers and must not be copied as authorization truth.

| Kind | Contract / use case | Behavior and failure conditions |
|---|---|---|
| Query `UC-IDN-Q01` | `ResolveExecutionContext` | Resolve authenticated WP session, CLI service principal, verified webhook principal or anonymous public purpose; NEVER accept client actor/site as authoritative. |
| Query `UC-IDN-Q02` | `AuthorizeOperation` | Check WordPress primitive capability OR site+organization membership, object ownership/state, purpose, high-risk approval and site version; deny unknown operation. |
| Query `UC-IDN-Q03` | `ListEffectivePermissions` | Return scoped UI capability *projection* with policy version and freshness; it never substitutes for action-time authorization. |
| Command `UC-IDN-C01` | `GrantMembership` | Authorized actor, unique active org/user grant, role allowlist and owner policy; event and audit. |
| Command `UC-IDN-C02` | `RevokeMembership` | Immediate authoritative revocation; reject removal of last active owner without governed transfer. |
| Command `UC-IDN-C03` | `ChangeMembershipRole` | Prevent self-escalation, audit old/new role, invalidate effective permission cache. |
| Query `UC-IDN-Q04` | `GetMembershipVersion` | Used by export download and queued sensitive action to detect stale authority. |

**Invariants:** No global advertiser-role grants through WordPress user role; cross-organization memberships never union into unrestricted access; no cross-site grant by `switch_to_blog`; lack of current authoritative membership fails closed. **Integration:** `IC-001 ActorScope`, `IC-002 OrganizationProjection`, `IC-024 FeatureAvailability`. **Error paths:** `forbidden`, `version_conflict`, `feature_unavailable`. **Tests:** two sites, two organizations, dual memberships, revoked token/export, super-admin cross-site without explicit grant, unsupported operation, stale permission projection.

### 5.2 Advertisers (`MOD-ADVERTISERS`, R1)

**Owns:** organization identity/profile, business verification state (if enabled), branding metadata and invitation *offer* lifecycle; membership grants are delegated to Identity through an atomic or controlled acceptance command.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-ADV-C01` | `RegisterOrganization` | Create site-owned organization in appropriate status; prevent duplicate idempotency effects. |
| Command `UC-ADV-C02` | `UpdateOrganizationProfile` | Validate legal/display name, branding, contact data; expected version; audit. |
| Command `UC-ADV-C03` | `ChangeOrganizationStatus` | Govern verification, suspension/reactivation; suspended org blocks checkout/new serving per policy. |
| Command `UC-ADV-C04` | `InviteMember` | Issue one-use bounded TTL token; record role offered, hashed token, site/org scope; do not leak user enumeration. |
| Command `UC-ADV-C05` | `AcceptInvitation` | Verify invite and user; use Identity membership command atomically or through idempotent completion; never duplicate owner or memberships. |
| Query `UC-ADV-Q01` | `GetOrganizationProfile` | Public safe metadata versus restricted owner/billing profile are different projections. |
| Query `UC-ADV-Q02` | `SearchOrganizations` | Publisher-only paginated, permission-filtered search. |

**Invariants:** Organization site immutable; private billing contact data never enters public storefront; suspension is enforceable on the next protected action. **Events:** `OrganizationRegistered`, `OrganizationStatusChanged`, `MembershipInvited`, `InvitationAccepted`. **Tests:** invite replay, invite tampering, suspended checkout, profile update conflict, cross-tenant enumeration.

### 5.3 Inventory (`MOD-INVENTORY`, R1)

**Owns:** stable `Zone`, `ContainerDefinition`, `SlotIdentity` and `SlotSequence` commercial identity definitions; not placements in a particular WordPress template (Layout owns bindings) and not sold reservation amounts (Reservations owns those).

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-INV-C01` | `CreateZone` | Unique slug under site; active/publishable status and commercial classification. |
| Command `UC-INV-C02` | `UpdateZoneMetadata` | Version-check commercial fields and invoke sold-impact policy for material change. |
| Command `UC-INV-C03` | `DefineContainer` | Validate layout kind (R1 single/row/stack/grid), breakpoints, dimension and density policy. |
| Command `UC-INV-C04` | `UpsertSlotSequence` | Compact rule for N generated slots and deterministic stable identity strategy. No fixed 500 ceiling. |
| Command `UC-INV-C05` | `MaterializeAddressableSlot` | Persist stable ID for reserved/sold/manual slot as needed; protect history from reuse. |
| Command `UC-INV-C06` | `ArchiveZoneOrSlot` | Block or require remediation where sold/reserved inventory is impacted. |
| Query `UC-INV-Q01` | `ListZonesAndContainers` | Paginated, site-scoped admin projection; anonymous published catalog is separate. |
| Query `UC-INV-Q02` | `GetSlotRange` | Resolve deterministic generated slots by page/range; preserve stable commercial IDs. |
| Query `UC-INV-Q03` | `GetInventoryDefinitions` | Immutable typed projection for compiler, availability and delivery. |

**Invariants:** A location in a layout is not its permanent commercial ID; configuration is not an impression; 1,000 logical slots need not create 1,000 eager creative objects. **Events:** `ZoneCreated`, `InventoryDefinitionChanged`, `SlotMaterialized`, `InventoryArchived`. **Error paths:** `inventory_conflict`, `contract_remedy_required`, `validation_failed`. **Tests:** 4/100/196/200 slots, 200 footer generator, stable IDs after responsive columns reorder, duplicate hook insertion, archive sold slot.

### 5.4 Layout / Layout Studio (`MOD-LAYOUT`, R1)

**Owns:** `PlacementBinding`, `LayoutDraft`, `PublishedLayoutRevision`, geometry and semantic anchors; UI canvas is a client of this module. The Layout Studio does not mutate paid reservation contracts on publish.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-LAY-C01` | `CreateLayoutDraft` | Copy approved/published configuration to a draft with source revision. |
| Command `UC-LAY-C02` | `ApplyLayoutPatch` | Structured patch, expected draft revision, validate container/slot referential integrity; no arbitrary executable selectors. |
| Query `UC-LAY-Q01` | `PreviewPlacementPlan` | Compile bounded preview, placeholders only; no paid event tracking. |
| Query `UC-LAY-Q02` | `AnalyzeContractImpact` | Identify sold/reserved slots, dates, device classes, placement promises, migration/remedy options. |
| Command `UC-LAY-C03` | `ValidateLayout` | Check schema, responsive geometry, duplicate injections, accessible layout constraints and policy violations. |
| Command `UC-LAY-C04` | `PublishLayoutRevision` | Revalidate impact/authority; immutable revision and active pointer switch transactionally. |
| Command `UC-LAY-C05` | `RollbackByNewRevision` | Revalidate *current* contract impact; never flip to conflicting old revision directly. |
| Query `UC-LAY-Q03` | `GetActivePlacementPlan` | Site/context-scoped published plan with version and bounded slot sequences. |
| Query `UC-LAY-Q04` | `GetGeometryAnchors` | Stable anchors and sanitized geometry version for R2 heatmap mapping. |

**Invariants:** Never relabel historical heatmaps; editor preview is nonbillable; sold changes require approved impact resolution, not a one-click warning dismissal; no unbounded editor DOM. **Integration:** `IC-003/004/016`, `DP-LAY`, `SM-LAYOUT`. **Tests:** published immutability, simultaneous editors, sold-slot deletion, old version rollback, mixed breakpoints, classic/block template invocation.

### 5.5 Campaigns (`MOD-CAMPAIGNS`, R1)

**Owns:** campaign metadata, schedule intent and three distinct relevant state dimensions where assigned by existing state specification; references external creative approvals and finance/booking facts via read-only contracts.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-CAM-C01` | `CreateCampaignDraft` | Current advertiser organization; immutable ownership; draft version and audit. |
| Command `UC-CAM-C02` | `UpdateCampaignDraft` | Typed editable fields only; expected version; already accepted commercial terms require amendment. |
| Command `UC-CAM-C03` | `SubmitCampaign` | Validate creative selection, targeting, inventory/quote references and publication rules. |
| Command `UC-CAM-C04` | `ChangeCampaignDeliveryIntent` | Pause/resume/cancel request; current finance, policy and authorization gates decide outcome. |
| Command `UC-CAM-C05` | `ScheduleCampaign` | UTC interval validated with contract reservation and approved creative references. |
| Query `UC-CAM-Q01` | `GetCampaignEligibilitySnapshot` | Authorized current approval, suspension, schedule and data version; fail closed on stale dependencies. |
| Query `UC-CAM-Q02` | `ListCampaignsForPrincipal` | Tenant-scoped, paginated; statuses remain independent in projection. |

**Invariants:** Successful payment does not approve creative; accepted contract is not overwritten by ordinary campaign editing; no cross-organization access; no universal `status` with conflated meanings. **Events:** `CampaignDrafted`, `CampaignSubmitted`, `CampaignPaused`, `CampaignScheduled`, `CampaignExpired`. **Tests:** paid but unapproved, approval but unpaid, creative revision, scheduled campaign with expired hold, emergency pause after cached decision.

### 5.6 Creatives / Moderation (`MOD-CREATIVES`, R1)

**Owns:** validated creative metadata, immutable `CreativeVersion`, its fingerprint and version-specific review; WordPress Media Library is storage adapter, not policy engine.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-CRT-C01` | `CreateCreative` | Validate owning organization, supported R1 formats, destination and content restrictions. |
| Command `UC-CRT-C02` | `AddCreativeVersion` | Create immutable media/destination copy and cryptographic fingerprint. |
| Command `UC-CRT-C03` | `SubmitCreativeForReview` | Queue exact version and moderation policy version. |
| Command `UC-CRT-C04` | `ReviewCreativeVersion` | Explicit approve/reject/change request with reviewer, reason, policy and version match. |
| Command `UC-CRT-C05` | `SuspendCreative` | Safety emergency stop and delivery invalidation. |
| Query `UC-CRT-Q01` | `GetApprovedCreativeProjection` | Contains exactly authorized approved media, destination, disclosure and version. |
| Query `UC-CRT-Q02` | `ListReviewQueue` | Moderator-scoped; excludes unrelated private advertiser data. |

**Invariants:** Do not run arbitrary advertiser PHP/JS on first-party origin; reject unapproved SVG and interactive HTML unless separate reviewed adapter/release; changing destination creates a new review target. **Tests:** file extension/MIME discrepancy, untrusted HTML, changed digest, moderation of wrong version, privileged moderation bypass, race with suspension.

### 5.7 Pricing (`MOD-PRICING`, R1 basic; R3 metered)

**Owns:** published `RateCardVersion`, commercial price rules, `Quote`, authorized discounts and immutable offer snapshots. Does not own invoice ledger or exclusive capacity locks.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-PRC-C01` | `PublishRateCardVersion` | Approve a new version without altering prior accepted quotes. |
| Query `UC-PRC-Q01` | `CalculateQuote` | Authoritative active rate/currency/tax inputs, interval, slot eligibility and availability read model; quote validity bounded. |
| Command `UC-PRC-C02` | `IssueQuote` | Persist terms digest, expiry, conditions and rate-card reference. |
| Command `UC-PRC-C03` | `AcceptQuote` | Recheck current policy and availability; immutable acceptance; hold created by Reservations. |
| Query `UC-PRC-Q02` | `GetQuoteSummary` | Exact authorized price lines, taxes and clear commercial guarantees/estimates. |

**Invariants:** No floating-point currency postings; R1 fixed/time/sponsorship, metered R3 behind feature gate; time zone ambiguity resolved before quote issue; quote expiry not retroactive to sold contract. **Tests:** currency precision, same display price different settlement currency, rate change during checkout, stale discount, DST interval, tax decision missing.

### 5.8 Reservations (`MOD-RESERVATIONS`, R1)

**Owns:** authoritative availability, transactional holds, `ContractReservation`, allocated time windows and quantity/exclusivity constraints. Does not trust storefront estimates as capacity evidence.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Query `UC-RES-Q01` | `EstimateAvailability` | Display-only snapshot with checked-at time and source; never guarantee until hold. |
| Command `UC-RES-C01` | `AcquireHold` | Lock capacity in deterministic slot order; re-read holds/reservations under transaction, enforce interval/exclusivity and TTL. |
| Command `UC-RES-C02` | `ExtendHold` | Explicit authorized revalidation, new version; never revive expired state implicitly. |
| Command `UC-RES-C03` | `ConsumeHoldToReservation` | Verify status/ownership/quote/financial prerequisites and convert atomically. |
| Command `UC-RES-C04` | `ExpireHold` | Idempotently expire only if current UTC now and state permit. |
| Command `UC-RES-C05` | `AmendReservation` | Contractual remedy approval and sold-slot impact analysis. |
| Query `UC-RES-Q02` | `GetActiveContractAllocation` | Current versioned read-only reservation facts for delivery. |

**Invariants:** No overlapping exclusive bookings; transactional guarantees required; deterministic lock order; valid half-open UTC interval; payment after hold expiry moves to explicit pending-remedy, not unauthorized delivery. **Events:** `HoldAcquired`, `HoldExpired`, `ReservationCommitted`, `ReservationAmended`. **Tests:** two buyers same interval, boundary adjacency, burst checkout, repeated hold consume, failed DB transaction, provider delay, 100 generated slots sharing availability lock ranges.

### 5.9 Commerce / Billing (`MOD-COMMERCE`, R1; R3 metered)

**Owns:** advertising orders, payment evidence, provider mapping, refunds, commercial remedies, immutable `FinancialLedgerEntry`. WooCommerce is an adapter; it is not domain authority for campaign approval or slot visibility.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-COM-C01` | `CreateAdvertisingOrder` | Link accepted quote, hold, accepted terms and site/org; idempotent creation. |
| Command `UC-COM-C02` | `StartCheckout` | Validate hold and active provider; create/retrieve mapped WooCommerce order using supported CRUD. |
| Command `UC-COM-C03` | `ApplyVerifiedPaymentEvidence` | Authenticate gateway/provider event; verify mapping, monotonic transition, idempotency and settlement interpretation. |
| Command `UC-COM-C04` | `PostFinancialEntry` | Append exact-currency balanced entries; no overwrite or delete. |
| Command `UC-COM-C05` | `OpenCommercialRemedy` | Trigger correction/re-review/reallocation/refund path when paid order cannot be fulfilled. |
| Command `UC-COM-C06` | `RequestRefundOrAdjustment` | Enforce policy, approval separation, provider state and auditable adjustment. |
| Query `UC-COM-Q01` | `GetAuthorizedOrder` | Org-scoped or finance-scoped, distinct safe fields. |
| Query `UC-COM-Q02` | `GetSettlementSnapshot` | Ledger-derived rather than frontend-derived financial truth. |

**Invariants:** No charge from raw browser event; browser return URL cannot settle payment; no held-card data; two separate provider callbacks cannot post twice; no forced fulfillment of rejected paid creative. **Events:** `AdvertisingOrderPlaced`, `PaymentEvidenceAccepted`, `PaymentSettled`, `RefundRequested`, `RefundRecorded`, `RemedyOpened`. **Tests:** duplicate callbacks, out-of-order refunded/paid callbacks, mismatch Woo order/site, provider outage, failed compensation and non-Woo operation.

### 5.10 Delivery / Targeting (`MOD-DELIVERY`, R1; advanced R3)

**Owns:** runtime selection, policy evaluation, safe creative decision response, rotation and approved candidate treatment. Never owns contracts, money, creative moderation or campaign approval.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Query `UC-DEL-Q01` | `BuildEligibleCandidateSet` | Verify current site, slot, binding, organization, creative version, contract, schedule and targeting. |
| Query `UC-DEL-Q02` | `DecideAdsBatch` | Evaluate bounded batch; apply rotation/density/exclusions; return opaque decision token/creative projection or safe empty result. |
| Query `UC-DEL-Q03` | `GetDeliveryPolicySnapshot` | Current eligibility and policy versions for invalidation/freshness. |
| Command `UC-DEL-C01` | `ApplyEmergencyStop` | Authorized global/organization/campaign/creative stop; make new decisions fail closed. |
| Query `UC-DEL-Q04` | `RenderCreativeMarkup` | Approved, sanitized presentation only; slot/render-instance identity stays distinct. |

**Invariants:** Selector must not call pricing mutation or write ledger; cache-stale approval/suspension fails closed after governed freshness bounds; no viewability claim from selection alone; untrusted creative code cannot run first-party. **Events:** `DeliveryPaused` for authoritative stop; browser telemetry is not this module's business event. **Tests:** 500 placeholders, batched decisions, 4 header/100 sidebar/200 footer, repeated zone with unique render IDs, page cache, offline measurement collector, policy/density suppression, stale selection.

### 5.11 Measurement (`MOD-MEASUREMENT`, R1 foundation; R2 deeper viewability; R3 billing)

**Owns:** event schema validation, de-duplication, qualifying rules, measurable/viewable definitions, and immutable aggregate provenance. Browser input remains untrusted; a qualified count is not automatically a billing charge.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-MET-C01` | `IngestEventBatch` | Bound payload size, decision validity, event type, consent/purpose, context, site and anti-abuse. |
| Command `UC-MET-C02` | `QualifyEventBatch` | Check duplicate keys, preview/staging/bot exclusions and measurement policy version. |
| Command `UC-MET-C03` | `AggregateQualifiedEvents` | Persist hourly/daily validated counts and provenance idempotently. |
| Query `UC-MET-Q01` | `GetQualifiedMetricSnapshot` | Metrics and denominator definitions, aggregation freshness and quality flags. |
| Query `UC-MET-Q02` | `ExplainMetric` | Definition version, exclusion flags, data lag, samples and auditability. |

**Invariants:** `configured ≠ served ≠ rendered ≠ measurable ≠ viewable ≠ billable`; browser click does not automatically create a financial entry. **Events:** `MeasurementBatchAccepted`, `MeasurementBatchQualified`, `MetricsAggregated` with non-personalized safe payloads. **Tests:** editor preview nonbillable, replayed events, fabricated decision token, offline send retry, very large event batch, viewport hidden, long-page footer unviewed.

### 5.12 Behavioral Analytics (`MOD-HEATMAP`, R2; R4 optional replay)

**Owns:** geometry-aware, normalized, privacy-filtered click/tap, scroll, element exposure and attention-proxy aggregates. It does **not** assert physiological eye gaze and cannot access a monetary ledger through event input.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Query `UC-HEAT-Q01` | `CanCollectBehavior` | Consent/purpose, page exclusions, privacy rule version, feature readiness. |
| Command `UC-HEAT-C01` | `IngestBehaviorSamples` | Validate allowed element/anchor, bounded sampling, normalized coords and no sensitive fields. |
| Command `UC-HEAT-C02` | `AggregateHeatmapByLayoutVersion` | Prevent mixed geometries and stale anchor association. |
| Query `UC-HEAT-Q02` | `GetHeatmap` | Admin-authorized aggregated bins with sample size, page/viewport/layout version and uncertainty cues. |
| Query `UC-HEAT-Q03` | `CompareLayoutVersions` | Compare disjoint layout versions with normalization and explicit comparability warnings. |
| Command `UC-HEAT-C03` | `CreateReplaySample` | R4-only optional collection; strong masking and policy gate before persistence. |

**Invariants:** R2 heatmaps need no session replay; advertiser receives only allowed aggregate reports, not visitor-level replay; consent withdrawal suppresses future unauthorized collection. **Tests:** coordinate normalization, 200 footer grid below fold, mobile vs desktop, layout changes, excluded checkout pages, opt-out, capture masking, insufficient sample warning.

### 5.13 Reporting (`MOD-REPORTING`, R1+)

**Owns:** report definitions and authorized projections from Measurement, Commerce and Inventory; export job lifecycle and controlled access to report artifacts. Does not invent counts or ledger balances.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Query `UC-REP-Q01` | `GetDashboardProjection` | Role-specific KPIs and status indicators with freshness. |
| Query `UC-REP-Q02` | `GetCampaignReport` | Scoped counts, defined denominators, authorized financial fields and interval. |
| Query `UC-REP-Q03` | `GetPlacementReport` | Slot/container/zone performance by explicit layout version. |
| Command `UC-REP-C01` | `RequestReportExport` | Validate report scope, authorize, queue bounded job, record current grant version. |
| Query `UC-REP-Q04` | `GetReportExportStatus` | Enforce ownership and provide sanitized status. |
| Query `UC-REP-Q05` | `DownloadReportArtifact` | Recheck current permission, site, org and artifact scope; protected storage/expiry. |

**Invariants:** Reporting data may lag but must declare lag and unit/definition; no cross-organization leakage; secure exports remain protected after generation. **Tests:** changed membership before download, revoked finance role, cache mixed tenant keys, zero denominator, unsynced ledger, CSV formula injection, large export queue.

### 5.14 Optimization (`MOD-OPTIMIZATION`, R3+)

**Owns:** controlled experiments, scoring/attribution definition, forecast snapshots and human-review recommendations. R1/R2 may retain interface stubs behind disabled feature gates, but must not advertise functioning R3 tools.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-OPT-C01` | `CreateExperiment` | Define randomization unit, variations, scope, guardrails and sample plan. |
| Command `UC-OPT-C02` | `StartOrStopExperiment` | Validate inventory, contracts, fairness, privacy and required authorizations. |
| Query `UC-OPT-Q01` | `GetExperimentResults` | Report confidence/uncertainty, exposure denominator, guardrail outcomes. |
| Query `UC-OPT-Q02` | `GetPlacementRecommendations` | Trace rationale and data provenance, avoid unverified causal claims. |
| Command `UC-OPT-C03` | `ApproveOptimizationAction` | Human-approved changes run through normal Inventory/Layout/Pricing owner use cases. |

**Invariants:** An optimizer cannot alter sold reservations directly; no default auto-repricing or campaign reassignment; forecasts labeled estimates. **Tests:** small sample, inconsistent creative approval, changed inventory contract, experiment collision, misleading statistical significance.

### 5.15 Compliance / Privacy (`MOD-COMPLIANCE`, R1+)

**Owns:** policy registry, category restrictions, consent-purpose rules, retention decisions, data access restrictions, high-risk operation policy. The exact regional legal interpretation must be approved by responsible specialists, not inferred from code defaults.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Query `UC-PRV-Q01` | `EvaluateAdPolicy` | Category/destination/disclosure/content/density verdict; safe explanation. |
| Query `UC-PRV-Q02` | `EvaluateConsentPurpose` | Determine whether a specific event class may be collected and used. |
| Command `UC-PRV-C01` | `RecordConsentChange` | Current policy, purpose, evidence and retention in approved form; avoid overcollection. |
| Command `UC-PRV-C02` | `ExecuteRetentionPolicy` | Delete/anonymize expired data unless documented lawful hold applies. |
| Query `UC-PRV-Q03` | `EvaluateElevatedOperation` | Require second approver/reason for governed financial or sold-slot override when policy dictates. |
| Query `UC-PRV-Q04` | `GetFeatureAvailability` | Enforce R1–R4 gates and dependency/policy readiness on server. |

**Invariants:** Extensions cannot bypass mandatory policy; no hidden optional behavioral capture; explicit unsupported decision states; product does not claim global legal compliance automatically. **Tests:** consent withdrawn, missing lawful basis, unsafe destination, disallowed category, cross-tenant replay, disabled R4 feature, override without reason.

### 5.16 Notifications (`MOD-NOTIFICATIONS`, R1+)

**Owns:** versioned notification templates and dispatch attempts; receives committed intents. External email delivery is an adapter. Must not leak tenant records because a previously authorized recipient lost access.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-NOT-C01` | `QueueNotificationIntent` | Store event ref, locale, recipients and visibility classification. |
| Command `UC-NOT-C02` | `DispatchNotification` | Re-resolve recipient eligibility and template, redact protected details; retries idempotently. |
| Query `UC-NOT-Q01` | `GetNotificationPreferences` | Respect active organization scope and publisher role. |
| Command `UC-NOT-C03` | `ChangeNotificationPreferences` | Actor authorization and version check. |

**Invariants:** Duplicate events must not send multiple contradictory messages; administrative notices never include private visitor replay; no outbound email with gateway secrets. **Tests:** recipient revoked before job, expired invitation reminder, failed email retry, locale fallback.

### 5.17 Integrations (`MOD-INTEGRATIONS`, R1 public adapters; R4 advanced)

**Owns:** registration and exposure of public PHP functions, shortcodes, action/filter hooks, REST adapters, outbound webhooks, payment provider adapters and optional external analytics. It *must not* contain duplicate business decisions.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Query `UC-INT-Q01` | `RenderZoneViaFacade` | Shared page-context + layout + eligibility pipeline for PHP/Gutenberg/shortcode/hook. |
| Command `UC-INT-C01` | `RegisterProviderAdapter` | Validate explicit version and interface; security/license approval and feature gate. |
| Command `UC-INT-C02` | `ManageWebhookSubscription` | Authorize destination and topics, sign key reference, safe DNS/SSRF policy. |
| Command `UC-INT-C03` | `DeliverIntegrationEvent` | From committed outbox, redact, sign, retry and record bounded attempts. |
| Query `UC-INT-Q02` | `ListDeveloperCapabilities` | Expose stable capability and API version documentation to authorized developers. |

**Invariants:** Theme functions do not convey administrator privileges; no ad-serving bypass through shortcode attributes; public PHP API returns safe HTML only, never direct access to financial domain roots. **Tests:** PHP/shortcode/block equivalence, unauthorized shortcode control, duplicate callbacks, webhook SSRF, provider outage, plugin deactivation without broken public templates.

### 5.18 Platform / Core (`MOD-PLATFORM`, R1+)

**Owns:** bootstrap, service composition, database migration orchestration, transaction/outbox primitives, cache namespaces, clock, job execution principal, feature flags, diagnostics and operational logs. Does not own arbitrary domain decisions.

| Kind | Contract / use case | Behavior |
|---|---|---|
| Command `UC-OPS-C01` | `InstallOrMigrate` | Versioned, idempotent, safe plugin-owned resources; maintenance gating if inconsistent. |
| Query `UC-OPS-Q01` | `CheckFeatureReadiness` | Runtime/dependency/license/config/policy prerequisites. |
| Command `UC-OPS-C02` | `DispatchOutbox` | Dequeue durable events; idempotent consumer processing and bounded retry. |
| Command `UC-OPS-C03` | `RunScheduledJob` | Verify site, operation, provenance, state and current feature gate. |
| Query `UC-OPS-Q02` | `GetRedactedDiagnostics` | Authorized health and migration snapshot without secrets or raw behavioral data. |
| Command `UC-OPS-C04` | `InvalidateScopedCache` | Target site/entity/policy revision; never rely solely on manual TTL for emergency stop. |

**Invariants:** Plugin boot without WooCommerce or proprietary SaaS; unsafe transactional sales disabled if database guarantees unavailable; retries do not duplicate authority or money; uninstall honors retention policy. **Tests:** clean activation, migration rerun, outbox duplicate, disabled provider, multisite context restore, site cache leak, database unavailable.

---

## 6. Published inter-module contract mapping (normative alignment with Document 06)

The `IC-001..024` identifiers and exact owner semantics are inherited from ADP-DOC-006. The following sections elaborate required payload shape and readiness, but are not a second registry. Treat all cross-module DTOs as immutable read projections; sensitive writes MUST route to owner commands.

### 6.1 Contract fields, consumer constraints and consistency

Every `IC-*` payload SHALL contain a declared schema version and relevant immutable references. The table below adds *minimum required content* and the sanctioned freshness class. Publication of further fields requires a versioned contract review; hidden ORM entities are not valid inter-module DTOs.

| Contract | Minimum payload requirements | Source and permitted consistency |
|---|---|---|
| `IC-001 ActorScope` | Verified `siteId`, actor type/id, optional current `organizationId`, operation, permission version, purpose | Current authorization on every protected write/read |
| `IC-002 OrganizationProjection` | Site/org ID, status, display/brand-safe fields, ownership/status version | Current status for entitlements; cached redacted display metadata only |
| `IC-003 InventoryDefinition` | Zone/container/slot IDs, commercial class, generator spec/version, active status | Immutable published version; current state for sale/serve |
| `IC-004 PublishedLayout` | Layout revision, active pointer revision, placements, breakpoint behavior, geometry key | Immutable revision; publication change atomically visible |
| `IC-005 Availability` | Site/slot or capacity key, UTC interval, quantity, exclusivity, exclusions, checked-at | UI estimate can lag; acquisition rechecks transactionally |
| `IC-006 Quote` | Quote ID/version, rate version, currency, exact lines, conditions, expiry, terms digest | Immutable accepted offer; authorization current |
| `IC-007 Hold` | Hold ID, owner, inventory keys, UTC interval, expires-at, status, version | Authoritative with locked capacity |
| `IC-008 ContractAllocation` | Reservation/contract ID, slot and period, eligibility, allocation and version | Authoritative current entitlement for selection |
| `IC-009 ApprovedCreative` | Creative ID/version/fingerprint, validated URL, media ref, disclosure, review version | Exact approved version; changes never implicitly approved |
| `IC-010 CampaignEligibility` | Campaign ID, org/site, approval/delivery state, schedule, stop version | Fresh for serving, no stale blocked campaign |
| `IC-011 PaymentEvidence` | Verified gateway + event reference, mapped site/order, attempts, currency/amount, provider state | Trusted webhook/verified WC adapter only, idempotent |
| `IC-012 FinancialLedger` | Order/entry IDs, exact currency amount, entry type, posting time, source evidence | Append-only authoritative finance, not browser counts |
| `IC-013 DecisionBatch` | Page-context scope, render IDs, slot IDs, chosen approved creative or empty, opaque token, expiry | Bounded batch, per-decision eligibility, cache-safe |
| `IC-014 EventEnvelope` | Version, event ID/type, site/decision/render/slot refs, observed time, consent class, bounded metrics | **Untrusted** input until validation/qualification |
| `IC-015 QualifiedAggregates` | Metric definition, numerator/denominator, time bucket, campaign/slot/layout scope, exclusions, freshness | Eventual read projection; R3 billing requires reconciled qualified facts |
| `IC-016 GeometryAnchors` | Layout revision, anchor hashes, viewport class, normalized bounds and snapshot ID | Immutable geometry interpretation/version |
| `IC-017 HeatmapAggregate` | Layout/geometry version, target anchor, device, time interval, bins, sample count, suppression flags | R2 privacy-safe aggregates, never raw session export |
| `IC-018 ExportArtifact` | Export ID, report scope, owner/site/org, status, artifact expiry and checksum | Permission recheck at download; no public file URL |
| `IC-019 PolicyVerdict` | Decision `allow/deny/requires_action`, code, policy version, safe explanation, evidence ref | Mandatory guard; extensions may further restrict, not bypass |
| `IC-020 BackgroundJob` | Job type/version, site, resource ref, operation, scoped principal, idempotency, attempt count | Verified, idempotent worker; no arbitrary role impersonation |
| `IC-021 IntegrationEvent` | Committed event ID/type/version, site, safe published fields, signature metadata | Sanitized outbox fact; retryable external delivery |
| `IC-022 NotificationIntent` | Committed source event, recipient scope, template/locale, classification, dedup key | Resolve recipient's current rights at send time |
| `IC-023 DiagnosticsSnapshot` | Redacted counts, health states, queue latency, migration version, timestamp | Restricted operations projection; no secret/raw replay |
| `IC-024 FeatureAvailability` | Feature key, approved release, prerequisite state, policy/adapter readiness, safe reason | Every adapter and worker; deny unavailable capability server-side |

### 6.2 Concrete owner ports: inventory, layout, reservation and delivery

Illustrative PHP 8.2+ interface shapes (namespaced interfaces, implementations to be created under the owning module):

```php
<?php
namespace AdPilot\Modules\Inventory\Application\Contracts;

interface InventoryQuery
{
    public function getDefinitions(InventoryScope $scope): InventoryDefinitionProjection;
    public function getGeneratedSlotRange(SlotRangeQuery $query): SlotPage;
}

namespace AdPilot\Modules\Layout\Application\Contracts;

interface PublishedPlacementPlanQuery
{
    public function compile(PageContext $context, PlacementRequest $request): PlacementPlan;
}

namespace AdPilot\Modules\Reservations\Application\Contracts;

interface AvailabilityService
{
    public function estimate(AvailabilityRequest $request): AvailabilityEstimate;
    // A UI estimate is NOT a confirmed hold; no mutation here.
}

interface HoldCommandService
{
    public function acquire(AcquireHoldCommand $command, TrustedExecutionContext $context): HoldResult;
    public function consume(ConsumeHoldCommand $command, TrustedExecutionContext $context): ReservationResult;
}

namespace AdPilot\Modules\Delivery\Application\Contracts;

interface AdDecisionService
{
    public function decideBatch(AdBatchRequest $request, VerifiedPublicRequestContext $context): AdBatchResult;
}
```

`PageContext`, `SlotPage`, `HoldResult`, etc. are proposed typed DTOs; the cross-module code must import their final namespace and implement serialization/version tests. The frontend must not construct `VerifiedPublicRequestContext` merely by naming that type in JSON.

### 6.3 Contract call constraints

- **Synchronous:** authorization, current inventory availability for transactional hold, campaign eligibility, approved creative, current allocation, active layout and final payment evidence application.
- **Asynchronous after commit:** notifications, external webhooks, routine reporting refreshes, heatmap aggregation, retention cleanup and noncritical diagnostics.
- **Bounded synchronous at public request:** ad selection batch and safe creative projection. If the dependency fails, use explicit empty/house fallback without fraudulent measurement.
- **Never synchronous from public tracking to billing:** browser signal → validation/qualification → auditable reconciliation → R3 billable-event consideration.
- **No foreign owner table access:** consumers must not reach into another module's repository to bypass owner semantics. Performance-driven precomputed read models require approved provenance and invalidation ownership.

---

## 7. Domain events, outbox, jobs and projections

### 7.1 Domain event envelope (`EV-*`)

A domain event SHALL contain `eventId`, `eventType`, `schemaVersion`, `occurredAtUtc`, `siteId`, optional `organizationId`, aggregate type/id/version, causation/correlation IDs, typed **non-secret** payload, and `privacyClassification`. Events representing state change are emitted only after/with the durable transaction; never emit `PaymentSettled` merely because a payment endpoint returned HTTP 200 or a browser reached its thank-you page.

### 7.2 Canonical proposed event catalog

| Event ID | Event | Owner | Consumers and responsibilities | Initial release |
|---|---|---|---|---|
| `EV-001` | `OrganizationRegistered` | Advertisers | Welcome/onboarding notification, restricted admin read projection | R1 |
| `EV-002` | `MembershipGranted` | Identity | Invalidate permission projection, safe team notification | R1 |
| `EV-003` | `MembershipRevoked` | Identity | Invalidate all tenant-scoped read caches; reject future exports | R1 |
| `EV-004` | `OrganizationSuspended` | Advertisers | Campaign/Delivery current-state gates, alerts | R1 |
| `EV-005` | `ZoneCreated` | Inventory | Layout availability and audit updates | R1 |
| `EV-006` | `InventoryDefinitionChanged` | Inventory | Revalidate draft bindings, invalidate safe layout caches | R1 |
| `EV-007` | `LayoutRevisionPublished` | Layout | Invalidate placement plan; retain history/geometry | R1 |
| `EV-008` | `LayoutRemedyRequested` | Layout | AdOps/Finance notifications, governed change workflow | R1 |
| `EV-009` | `CampaignSubmitted` | Campaigns | Creative moderator queue and status projection | R1 |
| `EV-010` | `CreativeVersionApproved` | Creatives | Eligibility version invalidation; status projection | R1 |
| `EV-011` | `CreativeVersionRejected` | Creatives | Notify advertiser; open policy-required remedy if paid | R1 |
| `EV-012` | `CampaignPaused` | Campaigns | Delivery cache invalidation and status update | R1 |
| `EV-013` | `QuoteIssued` | Pricing | Safe draft/checkout notification | R1 |
| `EV-014` | `HoldAcquired` | Reservations | Reservation calendar and checkout state | R1 |
| `EV-015` | `HoldExpired` | Reservations | Release capacity; refresh storefront estimate | R1 |
| `EV-016` | `ReservationCommitted` | Reservations | Campaign entitlement and delivery eligibility | R1 |
| `EV-017` | `AdvertisingOrderCreated` | Commerce | WooCommerce adapter mapping and status projection | R1 |
| `EV-018` | `PaymentEvidenceAccepted` | Commerce | Reconciliation/hold conversion orchestration, audited receipt | R1 |
| `EV-019` | `PaymentSettled` | Commerce | Eligibility after authoritative reconciliation; notification | R1 |
| `EV-020` | `CommercialRemedyOpened` | Commerce | Finance/admin action and advertiser recovery UI | R1 |
| `EV-021` | `RefundRecorded` | Commerce | Ledger/read model and campaign entitlement review | R1 |
| `EV-022` | `DeliveryEmergencyStopped` | Delivery | Immediate revocation and operations alert | R1 |
| `EV-023` | `MeasurementBatchQualified` | Measurement | Authorized report read-model aggregation | R1 |
| `EV-024` | `ReportExportReady` | Reporting | Notification with restricted one-time navigation, not bare public URL | R1 |
| `EV-025` | `HeatmapAggregateUpdated` | BehavioralAnalytics | Restricted analytics read model refresh | R2 |
| `EV-026` | `BillableEventReconciled` | Measurement/Commerce orchestration | R3 controlled ledger posting, dispute proof | R3 |
| `EV-027` | `ExperimentCompleted` | Optimization | Review-only recommendations, no implicit paid-slot mutation | R3 |
| `EV-028` | `ReplayPolicyChanged` | Compliance | R4 consent/retention enforcement and audit | R4 |
| `EV-029` | `ExternalIntegrationConfigured` | Integrations | Adapter health/authorization refresh | R4, optional R1 hook features |
| `EV-030` | `DataRetentionCompleted` | Compliance | Privacy/compliance audit without raw deleted data | R1/R2 |

The names are proposed. The actual event registry must resolve the one potential overlap around `EV-026`: authoritative qualification belongs to Measurement, financial charge posting belongs to Commerce. Prefer two committed events (`MeteredUsageReconciled` and `LedgerChargePosted`) if that prevents ambiguity. Record the chosen split via an ADR before shipping R3.

### 7.3 Browser events are not domain events

Browser signals such as `ad.rendered`, `ad.viewable`, `ad.clicked`, `scroll.milestone`, `heatmap.click` are **untrusted telemetry**, even if they carry a signed opaque decision reference. They must pass schema limits, anti-abuse, privacy/purpose rules, decision provenance verification, preview/staging exclusions and deduplication before being promoted into qualified aggregate facts. Raw browser events must never write to the Commerce ledger.

### 7.4 Outbox implementation obligations

1. Insert domain mutation, audit and outbox row in a single transaction when supported by configured tables/engine.
2. The dispatch worker leases events with bounded visibility timeout and retries on failure.
3. Event consumers retain processed-event IDs or equivalent idempotent state.
4. Different event versions coexist during rolling upgrades; unsupported versions enter a quarantine with diagnostics.
5. Retries are bounded and observable; dead letters require authorized reconciliation, not silent discard.
6. A killed worker must not cause loss of a paid booking or untraceable ledger change.
7. Outbound webhook sends use signature verification/rotation policy and never include secret/raw visitor payloads.
8. Producer/consumer may not depend on ordering across unrelated aggregates; use aggregate version and causation checks when order matters.

### 7.5 Idempotent handler example (pseudocode)

```text
handle(PaymentEvidenceAccepted event):
    assert supported event schema and trusted outbox provenance
    acquire site+order reconciliation lock
    if event id already processed: acknowledge without side effects
    load current order/hold/reservation/provider status from owner ports
    if evidence is stale, out-of-order or contradictory: set investigation state
    else apply allowed state transition and append any new ledger fact atomically
    mark processed event in the same consistency scope
    publish committed follow-up event through outbox
```

This algorithm is deliberately not a direct `WC_Order` update. Provider verification and WooCommerce state transitions remain in their own adapter layer and must be tested with actual supported APIs.

---

## 8. Cross-module orchestration workflows and saga boundaries

### 8.1 Advertiser purchase → approved serving (R1)

```mermaid
sequenceDiagram
    participant UI as Advertiser Portal
    participant PR as Pricing
    participant RE as Reservations
    participant CO as Commerce/Woo Adapter
    participant CR as Creatives/Moderation
    participant CA as Campaigns
    participant DE as Delivery
    UI->>PR: Issue immutable quote
    PR-->>UI: Quote ID, expiry, exact terms
    UI->>RE: Acquire hold (authorized)
    RE-->>UI: Hold token + expiry
    UI->>CO: Create advertising order + checkout
    CO-->>UI: Provider checkout reference
    Note over UI,CO: Browser return is not settlement evidence
    CO->>CO: Verify provider evidence; reconcile ledger
    CO->>RE: Consume valid hold under governed transaction
    Note over CO,RE: Expired hold => pending remedy, never force conversion
    UI->>CR: Submit immutable creative version
    CR-->>CA: Approval/rejection fact
    CA->>DE: Eligibility evaluated on live decision request
    Note over DE: Financial entitlement AND approval AND schedule AND policy
```

**Key outcome states:** `payment_pending`, `under_review`, `scheduled`, `active` are projections of **separate** machines, not one overloaded `campaign.status`. UI must show pending and recovery paths. Late payment with expired hold opens the contract remedy path; do not fabricate a valid reservation. If creative is rejected after payment, policy `DP-REM` governs revision, substitution or refund; `DEC-007` must be approved before automating remedy specifics.

**Failure cut points:** reserve succeeded/order creation failed; order created/provider timeout; payment verified/hold expired; approved creative/suspended organization; refund provider timeout; stale frontend state. Each cut point MUST leave an observable recoverable state and preserve financial/audit evidence.

### 8.2 Layout publication while inventory is sold (R1)

```text
Draft layout patch
 -> validate syntax, references, breakpoints, density and accessibility
 -> compute semantic diff against currently published revision
 -> obtain active reservations/contracts for impacted slot IDs and periods
 -> classify change: cosmetic | compatible | commercially material
 -> if material: require explicit remedy/approval/amendment (never forced override)
 -> re-read contracts under protected publish transaction / lock
 -> create immutable PublishedLayoutRevision
 -> switch active version atomically + outbox event
 -> invalidate compatible cache keys and preserve prior geometry snapshot
 -> update Studio with confirmation and affected slot list
```

Rollback is a *new publish with impact analysis*; it cannot skip contracts formed after the version being restored. Old heatmap data retains old layout/geometry IDs. Preview cannot create paid impressions or commit new inventory allocations.

### 8.3 Gutenberg/theme invocation → browser decision (R1)

All four Gutenberg dynamic blocks, shortcode wrappers, theme PHP functions, configured injection rules and classic widgets must call **one shared presentation facade**. The facade obtains trusted `PageContext`, active layout and explicit slot/render-occurrence IDs; returns safe placeholders with bounded metadata. The browser SDK observes approaching slots, requests bounded batches from Delivery, renders approved content and sends permitted Measurement signals. The renderer must not eagerly initialize 500 creatives, attach 500 separate observer instances, or execute untrusted advertiser script. Cached HTML must not bypass current campaign stop/approval checks.

### 8.4 Measurement → report and R3 financial truth

```text
Browser signal (untrusted)
 -> validate site/decision/render and schema; apply consent and exclusion policy
 -> deduplicate, reject preview/test/bot-invalid events
 -> persist qualified measurement evidence (bounded retention)
 -> calculate versioned aggregate and metric denominator
 -> Reporting serves organization/site-scoped labeled projections
 -> R3 only: contract-specific reconciliation and dispute review
 -> Commerce posts authorized immutable billing entry (not raw counter)
```

No claim of MRC accreditation, perfect fraud detection, gaze tracking or off-site verified conversion may be made merely from implementing a browser observer. Report `configured`, `served`, `rendered`, `measurable`, `viewable`, `qualified-click`, `billable` with clearly distinguished definitions.

### 8.5 Consent withdrawal and heatmap handling (R2)

At the next policy decision, optional tracking collection must stop when required by applicable rules. Pending buffered data that is no longer eligible must be discarded or handled under the approved retention/deletion policy. Heatmaps remain scoped to layout version and device and display sample sizes; visitor-level replay is not part of native R2 heatmaps. Data export/deletion requests and legal holds are handled through Compliance policies, not by direct SQL from a user interface.

### 8.6 System outage and stale queue behavior

| Failure | Required domain outcome | UI/system behavior |
|---|---|---|
| Payment adapter unavailable | Existing facts preserved; no fabricated success | Disable checkout, offer retry/status and admin diagnostics |
| Transaction guarantees unavailable | No exclusive paid purchase | `transaction_unavailable`, payment route disabled |
| Event collector unavailable | No inference of missing viewed impressions | Public page/ad delivery continue safely; report data-gap warning |
| Queue backlog | Scheduling/notifications may be delayed; entitlement checked live | Surface pending/delay warnings; monitor freshness SLA |
| Cache stale after pause | New ad selections deny paused creative based on current policy | Invalidate; short maximum freshness; emergency stop check |
| Heatmap geometry missing | No misleading overlay | Show data unavailable, layout/version diagnostic |
| Multisite context mismatch | Operation denied | Restore original blog/site context and audit |
| Export permission revoked | Artifact inaccessible | Safe denial even if file already generated |
| Duplicate checkout callback | No duplicate reservation or payment posting | Idempotent response; show current authoritative status |
| Unauthorized integration provider | No privileged action | Disable adapter, alert administrator |

---
## 9. State machine composition and transition ownership

ADP-DOC-004 contains the 15 authoritative `SM-*` state specifications. This document does **not** replace their allowed transitions; it identifies the domain owner, mutation service and dependent checks. Each production implementation must translate its source transition table into an exhaustive test and reject undocumented transitions.

| Governing machine | Owning module | Controlled by | Gate that must not be bypassed |
|---|---|---|---|
| `SM-QUOTE` | Pricing | `IssueQuote`, `AcceptQuote` | Valid rate snapshot, authority, expiry |
| `SM-HOLD` | Reservations | `AcquireHold`, `ExtendHold`, `ExpireHold`, `ConsumeHold` | TTL, exclusive capacity, authorized scope |
| `SM-RESERVATION` | Reservations | `ConsumeHoldToReservation`, `AmendReservation` | Transactional capacity, contractual amendment |
| `SM-ORDER` | Commerce | `CreateAdvertisingOrder`, reconciliation handlers | Independent, verified payment evidence |
| `SM-PAYMENT` | Commerce | `ApplyVerifiedPaymentEvidence` | Provider authenticity, idempotency, state monotonicity |
| `SM-CREATIVE` | Creatives | `AddCreativeVersion`, `ReviewCreativeVersion` | Exact approved digest/version |
| `SM-CAMPAIGN` | Campaigns | `SubmitCampaign`, scheduling and lifecycle handlers | Organization/current approval/contract policy |
| `SM-DELIVERY` | Delivery/Campaigns owned transition boundary | `ApplyEmergencyStop`, eligible serving projection | Current suspension, approved creative, contract, schedule |
| `SM-LAYOUT` | Layout | `PublishLayoutRevision`, `RollbackByNewRevision` | Impact analyzer, sold slot migration/remedy |
| `SM-ALLOCATION` | Reservations/Delivery separate commercial/runtime facts | Reserved capacity and runtime selection commands | Runtime rotation never amends sold contract |
| `SM-REMEDY` | Commerce | `OpenCommercialRemedy`, authorized resolution | Recorded responsible owner and resolution evidence |
| `SM-DISPUTE` | Commerce | Billing dispute service (R3) | No silent ledger deletion or self-approved invalid adjustment |
| `SM-EXPORT` | Reporting | `RequestReportExport`, worker and download policy | Current access at generation **and** download |
| `SM-CONSENT` | Compliance | `RecordConsentChange` | Lawful purpose, regional and privacy policy |
| `SM-JOB` | Platform | `RunScheduledJob`, outbox lease handlers | Verified origin, site scope, idempotent retry |

**Boundary review required:** ADP-DOC-006 treats `Delivery` as owner of runtime selection and `Campaigns` as owner of campaign delivery state intent. Implement one published `CampaignDeliveryStatus` projection and one definitive command owner, then link them using an explicit contract; never allow two modules to independently mutate an identical delivery-status field. A change to this decision requires an ADR, module contract update and migration impact review.

### 9.1 Forbidden transition patterns

- `draft → active` from an admin dropdown with no contract/creative checks.
- `pending_payment → paid` on browser redirect alone.
- `hold_expired → consumed` without a fresh authorized acquire/transaction.
- `approved_v1 → approved_v2` by editing the original approved destination/media in place.
- `published_revision → edited_revision` by direct SQL in the existing snapshot.
- `refund_requested → completed` because a provider timeout is interpreted as success.
- `session_replay_disabled → collecting` through a frontend flag without approved consent/policy.
- `export_ready → downloaded` after recipient permission has been revoked.

### 9.2 Source-to-domain invariant map

| PRD invariant | Domain enforcement contract | Primary owner |
|---|---|---|
| `INV-01` | `UC-DEL-Q01` checks approval + verified finance/contract + schedule + allocation + policy before serving | Delivery |
| `INV-02` | `UC-RES-C01/C03` enforce locked capacity and interval recheck | Reservations |
| `INV-03` | `UC-INV-C05` stable identity and `UC-LAY-C04` impact resolution | Inventory + Layout |
| `INV-04` | Page compiler includes unique `RenderInstanceId`; Measurement validates mapping | Layout + Delivery + Measurement |
| `INV-05` | Separate configuration/decision/render/viewable metric definitions | Measurement |
| `INV-06` | Test/staging/editor exclusion and nonbillable provenance | Measurement |
| `INV-07` | Current membership and object ownership check at every protected query/write | Identity + each owner |
| `INV-08` | Billing posts from reconciled evidence, not browser event | Commerce |
| `INV-09` | Safe baseline ad delivery independent of optional behavioral SDK | Delivery + Compliance |
| `INV-10` | Approved safe creative projection and no untrusted first-party code | Creatives + Delivery |

### 9.3 Baseline business rule enforcement map

| Rule | Enforcing module(s) | Required implementation gate |
|---|---|---|
| `BR-001` | Inventory, Pricing, Reservations | Only active/publishable/available inventory may be sold |
| `BR-002` | Reservations | Hold expires at TTL; explicit transaction to extend |
| `BR-003` | Commerce, Creatives, Delivery | Settled payment never bypasses moderation |
| `BR-004` | Commerce, Creatives, Notifications | Rejected paid campaign enters approved remedy, not silent fund retention |
| `BR-005` | Reservations | Competing exclusive purchases serialize; no overlapping sold interval |
| `BR-006` | Layout, Reservations | Sold-slot semantic change requires impact resolution and audit |
| `BR-007` | Creatives, Delivery | Serve only permitted approved creative version |
| `BR-008` | Campaigns, Delivery, Cache | Current eligibility/version checked beyond stale cached creative |
| `BR-009` | Measurement | Preview/editor/staging/synthetic activity never billable |
| `BR-010` | Compliance, Inventory, Delivery | Density/network policy enforced and surfaced to layout designers |
| `BR-011` | Reporting, Identity | Advertiser sees aggregate authorized metrics only |
| `BR-012` | Measurement, Compliance, Commerce | Bounded raw retention and independent ledger of financial truth |
| `BR-013` | Measurement, BehavioralAnalytics, Reporting | Metrics use defined provenance and labels, no gaze inference |
| `BR-014` | Pricing, Commerce | Exact money/currency arithmetic and rounding rules |
| `BR-015` | Pricing, Reservations, Campaigns | UTC schedules, DST ambiguity and display time zone consistency |
| `BR-016` | Layout, Frontend/UX | Responsive/accessibility protection without contract silent changes |
| `BR-017` | Compliance, Integrations, Measurement | Governance of optional third-party data flows |
| `BR-018` | Identity, Layout, Commerce | Allocation overrides require authorization, reasons and audit |

---

## 10. Authorization and least-privilege application rules

### 10.1 Action-level policy evaluation

All mutation entrypoints (REST, wp-admin, CLI, Action Scheduler, webhooks, WooCommerce integration) MUST call the use-case-level policy gate. Each gate evaluates `(verified site, principal, operation, resource owner, object state, purpose, feature gate, high-risk approvals)`. Object ownership is resolved from an authorized site-scoped repository, not from a request's claimed organization ID. Theme and shortcode rendering paths expose only published safe advertising output; rendering a public zone does not grant access to campaign reports or billing.

### 10.2 Publisher versus advertiser permission planes

- Publisher operations use actual WordPress capabilities plus contextual policy. A WP admin is not automatically authorized to enumerate a separate site's customer records in multisite.
- Advertiser operations use **organization membership** and resource ownership; one user may belong to two unrelated advertiser organizations without cross-tenant data union.
- Moderation is distinct from financial adjustment, layout sale remedy and replay access.
- Analysts may see aggregates within grant scope but not visitor session recordings by implication.
- Background jobs use verified internal principals with specific allowed operation/site and recheck current domain state; no arbitrary `user_id` in queued JSON creates rights.

### 10.3 Proposed authorizer interface

```php
<?php
namespace AdPilot\Modules\Identity\Application\Contracts;

interface AuthorizationService
{
    public function decide(
        TrustedExecutionContext $execution,
        OperationName $operation,
        ResourceReference $resource,
        AuthorizationPurpose $purpose
    ): PolicyVerdict;
}
```

**Execution order:** validate bounded input → resolve trusted site/actor → load site-scoped target → check ownership/capability → check state and applicable policy → apply command transaction/recheck → append audit. Every denial response must avoid leaking foreign tenant existence or private financial status.

### 10.4 Permission-aware frontend projection

The React admin, advertiser portal, Layout Studio and Gutenberg InspectorControls should receive a safe `AllowedActionsProjection` to hide, disable or explain unavailable actions. That projection is advisory: after permission revocation, the next request must fail even if a stale browser shows an enabled button. Render accessible denied and recovery states; a CSS-hidden button is not an access control.

### 10.5 Audited high-risk operations

Require actor, site, resource/version, previous/new value, reason, policy reference, correlation ID, timestamp, optional independent approver, and immutable audit entry for refunds, allocation overrides, sold-slot migrations, policy relaxation, replay enablement, gateway changes, membership owner transfers, and manual reconciliation. Respect policy-specific dual-control constraints and never allow an unapproved self-authorization loop.

---

## 11. Data ownership, persistence ports and transaction consistency

### 11.1 Proposed repository contracts

Repositories represent owned aggregate persistence only. Do not expose raw `$wpdb` handles to domain services; keep DB-specific SQL in infrastructure adapters.

```php
<?php
namespace AdPilot\Modules\Reservations\Domain\Contracts;

interface HoldRepository
{
    public function getForUpdate(SiteId $siteId, HoldId $holdId): ?InventoryHold;
    public function insert(InventoryHold $hold): void;
    public function updateIfVersion(InventoryHold $hold, EntityVersion $expected): bool;
}

interface CapacityLockPort
{
    /** Keys sorted deterministically by caller. Lock is transaction-scoped. */
    public function lockInventoryKeys(SiteId $siteId, array $capacityKeys): void;
}

namespace AdPilot\Modules\Commerce\Domain\Contracts;

interface LedgerRepository
{
    public function appendIfAbsent(FinancialLedgerEntry $entry, IdempotencyKey $key): AppendResult;
    public function totalForOrder(SiteId $siteId, OrderId $orderId): Money;
}
```

The final persistence design must confirm InnoDB transactional support (or equivalent supported guarantees), unique keys, and exact lock behavior. A generic unique index on `(slot_id, start_at, end_at)` does **not** prevent arbitrary overlapping date ranges; transactional range/capacity locking is mandatory. A database with inadequate guarantees must disable unsafe exclusive purchasing and show diagnostics.

### 11.2 Transaction categories

| Category | Atomic source of truth | Subsequent eventual effects |
|---|---|---|
| Acquire exclusive hold | Lock/recheck capacity + hold insert + audit + outbox | Storefront cache invalidation |
| Convert hold to reservation | Valid hold + current capacity + contract allocation + outbox | Campaign schedule/status read projection |
| Publish layout | Revalidate impact/contract + immutable revision + active pointer + audit + outbox | Page cache purge, Studio updates |
| Post verified payment | Verified evidence + legal allowed state + ledger entry + audit + outbox | Receipt email, report refresh |
| Review creative | Exact version decision + audit + outbox | Delivery candidate invalidation, notifications |
| Revoke membership | Membership status/version + audit + outbox | Cache purge; next command rechecks authority |
| Qualify telemetry | Dedup/validation + accepted fact batch | Aggregate/report refresh; **not** ledger mutation |

Cross-database/gateway operations cannot be covered by one WordPress MySQL transaction. Use explicit intermediate states and compensation (remedy/refund/retry), never a fake all-or-nothing promise.

### 11.3 Cache key and invalidation policy

Cache keys for protected data must include site, organization or role scope where required, resource/version, policy version and locale/device context when materially relevant. Cache only safe public layout plans and shareable metadata across visitors; do not share per-person targeted decisions. Emergency suspension and current financial entitlement cannot depend solely on stale page HTML. All active eligibility decisions must have an effective freshness bound and safe server-side fallback.

### 11.4 Read-model materialization

Reporting, catalogs, calendars and analytics may use materialized query tables. Every materializer records source version, aggregation time, schema version, metric definition and failure state. When materialization lags, display `as_of` and an explanation rather than representing data as strictly real time. Finance dashboards must distinguish authoritative ledger balances from delayed advertising event aggregates.

---

## 12. Frontend, Gutenberg and theme-facing application contracts

### 12.1 Six presentation clients

The administrator console, advertiser portal, public storefront, Layout Studio, Gutenberg editor and Analytics Explorer consume safe application DTOs. Public ad delivery uses a small independent browser SDK, not the entire React application. The single shared design system supplies typed states (loading, empty, permission denied, stale, conflict, retry, success) and accessible interaction primitives.

### 12.2 Shared rendering facade

Proposed stable API surface in `Presentation/Theme` / `PublicApi/Functions`:

```php
<?php
// Theme usage; proposed functions to be implemented in AdPilot.
if (function_exists('adpilot_render_zone')) {
    echo adpilot_render_zone('footer-grid', ['limit' => 200]);
}
```

All `adpilot_render_slot()`, `adpilot_render_zone()`, `adpilot_render_container()` and approved action/shortcode/block adapters must invoke the same layout/eligibility facade. The facade accepts stable IDs and bounded permitted options; it does not permit client overrides that force an unapproved ad or select unauthorized private campaigns. Return markup is safe according to the interface's output/escaping contract.

### 12.3 Gutenberg dynamic block contract

| Block | Expected application dependency | Saved data | Forbidden behavior |
|---|---|---|---|
| `adpilot/ad-slot` | `GetActivePlacementPlan` + safe renderer | Logical slot ID and permitted presentation overrides | Editor paid impression recording |
| `adpilot/ad-zone` | `GetActivePlacementPlan` + `GetSlotRange` | Compact zone reference, no 500 serialized child ads | Direct DB creative lookup |
| `adpilot/ad-repeater` | Context-aware injection adapter and plan compiler | Validated interval/placement rule | Recursive uncontrolled injection into all blocks |
| `adpilot/ad-showcase` | Approved creative showcase projection | Versioned template, allowed layout/display properties | Trust advertiser-provided executable HTML |

The editor preview must bound visible sample slots, show summary/count/occupancy and not execute paid tracking. The Site Editor, posts, template parts, classic widgets and PHP templates must not create inconsistent slot identities or separate delivery policies.

### 12.4 Public render identity

A rendered placeholder binds `(site, publishedLayoutRevision, logicalSlotId, renderInstanceId, pageContext, previewFlag)`. Two legitimate placements of one logical zone on the same page have different `renderInstanceId` values. DOM IDs must be unique; measurement and heatmap joins must distinguish render occurrence from commercial slot. Runtime selection validates current entitlements and policy; a serialized template alone cannot attest eligibility.

### 12.5 Large-layout UI performance

The Layout Studio SHALL operate on paginated logical slot ranges, summary aggregates, bounded viewport previews and selective inspectors rather than 500 interactive heavy creative frames. Bulk changes use validated server commands; every optimistic UI mutation shows version conflicts and recovery. Responsive columns and breakpoint previews do not mutate sold slot IDs. Maintain keyboard, screen-reader and non-drag alternatives.

### 12.6 Measurement and UX language

Advertiser/admin dashboards must expose explicit metric names and denominator/help text. Never label a heatmap `eye focus`; use `click density`, `scroll reached`, `visibility time` or `attention proxy` and document what was actually measured. Show zero vs unknown vs unavailable as distinct UI states. Heatmap comparisons must identify source layout/device versions and sampling limitations.

---

## 13. Security, privacy, consent and data minimization contracts

| Trust boundary | Domain/application enforcement | Negative evidence required |
|---|---|---|
| Anonymous storefront | Published allowlisted catalog projection only | No other advertiser financial/profile details |
| Authenticated advertiser | Current site+organization membership on all data operations | Foreign campaign/order ID denied without leakage |
| WordPress administrator | Site capability + object/policy authorization | Foreign multisite record denied without explicit grant |
| Gutenberg editing | Content edit permission + relevant AdPilot block/zone right | Crafted attributes cannot force ad to serve |
| Public decision | Rate limiting, validated slot/context, eligibility and current approval | Fabricated slot/decision returns no privileged output |
| Tracking ingestion | Bounded payload, event provenance, anti-abuse, privacy purpose | No card/form content, preview/test billing, duplicate count |
| Creative upload | Content checks, media allowlist, destination checks, immutable review | Malicious JS/SVG/HTML denied or sandboxed under future policy |
| Payment callback | Verified provider evidence, mapped site/order and idempotency | Forged/duplicated callback cannot post ledger |
| Export | Generate-time and download-time authorization | Revoked user cannot fetch old artifact |
| Worker | Verified service principal and current state | Queue JSON cannot impersonate administrator |
| External webhook | Allowlisted topic and destination, signed event, SSRF policy | Secret/raw sessions never leave by default |
| Optional replay | Separate R4 consent, masking, strict access and retention | Sensitive fields not collected; denial stops capture |

Every collected field must belong to a documented purpose and retention class. Logs and diagnostics must redact secrets, financial provider tokens, sensitive visitor data and raw session content. The implementation must not claim that all regions permit a given tracking behavior without approved policy and legal review.

---
## 14. Module source-code layout and dependency enforcement

### 14.1 Proposed PHP module structure

```text
src/
  Core/
    Application/{Contracts,Commands,Queries,Results}/
    Domain/{Events,ValueObjects,Errors}/
    Infrastructure/{Clock,Transactions,Outbox,Cache,Queue}/
  Modules/
    Inventory/
      Domain/{Entities,ValueObjects,Contracts,Services,Events}/
      Application/{Commands,Queries,Handlers,DTOs,Policies,Contracts}/
      Infrastructure/{Repositories,Mappers,ReadModels}/
      ModuleProvider.php
    Layout/ ...
    Advertisers/ ...
    Campaigns/ ...
    Creatives/ ...
    Pricing/ ...
    Reservations/ ...
    Commerce/ ...
    Delivery/ ...
    Measurement/ ...
    BehavioralAnalytics/ ...
    Reporting/ ...
    Optimization/ ...
    Compliance/ ...
    Notifications/ ...
    Integrations/ ...
    Identity/ ...
  Presentation/
    Rest/{Admin,Advertiser,Public}/
    Gutenberg/
    Theme/
    Shortcodes/
    Cli/
  Infrastructure/
    WordPress/
    WooCommerce/
    Database/
  PublicApi/{Functions,Contracts,Compatibility}/
frontend/
  shared/{design-system,api-client,types,i18n}/
  admin/ advertiser/ storefront/ layout-studio/ analytics/
  public-renderer/ tracking/ heatmaps/
blocks/{ad-slot,ad-zone,ad-repeater,ad-showcase}/
tests/{Unit,Integration,API,Gutenberg,E2E,Accessibility,Performance,Security,Contract}/
```

Avoid allocating a folder per unused service class. Use PSR-4 autoloading, type-safe constructor injection and one composition root. Infrastructure adapters implement domain/application ports; modules share immutable DTOs or published application facades only.

### 14.2 Static architecture rules (`D07-BND-*`)

| Rule | Enforced condition | Automated check |
|---|---|---|
| `D07-BND-01` | `Modules/*/Domain` cannot import WordPress/WooCommerce/frontend classes | Dependency/static import audit |
| `D07-BND-02` | Controller/shortcode/block code cannot issue direct domain financial SQL | Repository ownership/static scan |
| `D07-BND-03` | Foreign modules cannot write another module's table or private repository | Module owner boundary test |
| `D07-BND-04` | Public render paths cannot load administrator React/chart bundles | Bundle and browser network audit |
| `D07-BND-05` | Domain state transitions cannot be replaced by DTO `status` assignment | Mutation/property access tests |
| `D07-BND-06` | All financially sensitive commands accept idempotency and current trusted scope | Constructor/contract tests |
| `D07-BND-07` | All publication and reservation mutations use required version/transaction guard | Handler contract tests |
| `D07-BND-08` | No raw browser analytics event is posted to Finance | Event ownership/consumer allowlist test |
| `D07-BND-09` | Every production outbox consumer is idempotent | Duplicate delivery integration test |
| `D07-BND-10` | API/boundary payloads are typed, versioned and scoped | Schema contract tests |
| `D07-BND-11` | R2/R3/R4 unavailable operations fail closed | Feature flag direct-call tests |
| `D07-BND-12` | Theme/Gutenberg/shortcode use the same delivery policy facade | Equivalent behavior snapshot tests |
| `D07-BND-13` | Subscriber/advertiser WP role is not sufficient for org access | Cross-tenant authorization tests |
| `D07-BND-14` | New dependency must have license and security approval | CI dependency manifest gate |
| `D07-BND-15` | All reports and exports use privacy-safe, authorized projections | Query and export read model tests |
| `D07-BND-16` | All sensitive state transitions create auditable facts | Audit coverage tests |

### 14.3 Extension contracts and backward compatibility

Interfaces marked public (`IC-*`, theme PHP functions, shortcode names, block names, REST routes, approved actions/filters) require versioning and deprecation policy. Internal private classes can evolve more readily, provided canonical business semantics and source acceptance tests remain stable. Published payloads reject unexpected privilege-bearing fields and handle additive fields according to a declared compatibility policy. Do not allow third-party filters to override a mandatory compliance denial or fabricate settled payments.

---

## 15. GPT-6 High execution plan and bounded implementation packages

### 15.1 Required development sequence

1. Approve business policies, site/org access model and the fundamental aggregate boundaries.
2. Implement shared value objects, time/money/id primitives, results/errors, trusted context, clock, transaction and outbox ports.
3. Implement identity/membership security; ensure unauthorized reads/writes fail before writing all other modules.
4. Implement inventory/slot identity and compact generator with deterministic tests.
5. Implement layout draft, compiler, active revision and sold-impact policy.
6. Integrate Gutenberg/theme/shortcodes via one renderer; test high-density and nonbillable preview behavior.
7. Implement advertisers, creative versioning and campaign draft/approval.
8. Implement pricing and quote, transactional holds and contractual reservation.
9. Implement WooCommerce commerce adapter, payment evidence, ledger and remedy states.
10. Implement eligibility, decision batching and public rendering.
11. Implement event validation, measurements, aggregate read models, scoped reporting and exports.
12. Add R2 heatmaps, geometry/anchor versions and consent-aware collection.
13. Add R3 metered billing, disputes, optimization/attribution only after reconciled event proof.
14. Add R4 optional session replay, network features and external workers only after security/license/privacy review.
15. Throughout: implement UX review, frontend accessibility, automated tests, audit and operations instrumentation.

### 15.2 Work package definition (owners, outputs and gates)

| WP | Work package | Lead / reviewers | Core outputs | Exit gate |
|---|---|---|---|---|
| `D07-WP-01` | Value objects, IDs, money and time | Backend / Finance, QA | `VO-*` types, validators and unit tests | No float ledger, DST ambiguity handled |
| `D07-WP-02` | Trusted actor and permissions | Security / WP, Backend | Site/org policies, membership store | Cross-org/site denial tests pass |
| `D07-WP-03` | Transaction, outbox, idempotency ports | Backend / DB, Reliability | Transaction manager, idempotent outbox | Duplicate retries create one effect |
| `D07-WP-04` | Inventory aggregate and 500-slot generator | WordPress/Backend / Frontend | `DM-004..006`, compact generator | 500/1000 stable-slot stress fixtures |
| `D07-WP-05` | Layout versioning and contract-impact | Layout/Backend / UX, Finance | Compiler, immutable revision, impact checker | Sold slot cannot change silently |
| `D07-WP-06` | WordPress/Gutenberg render facade | Gutenberg/Frontend / WP, QA | Four blocks + PHP, shortcode adapters | Integration path parity; previews nonbillable |
| `D07-WP-07` | Advertiser organization workflows | Backend/UX / Security | Profiles, invitations and team membership | Invite replay and cross-tenant tests |
| `D07-WP-08` | Campaign and creative moderation | Backend/AdTech / Security, UX | Independent states, exact creative approvals | Edited approved creative not served |
| `D07-WP-09` | Pricing and quote snapshots | Finance/Backend / Product | `Money`, rate card, quote services | Rate changes cannot rewrite accepted quote |
| `D07-WP-10` | Transactional reservations | Backend/DB / Finance, QA | Holds, locks, commit and TTL | Concurrent exclusive oversell = zero |
| `D07-WP-11` | Commerce and WooCommerce HPOS adapter | Commerce / Finance, Security | Orders, payment evidence, refunds, ledger | Duplicate/out-of-order callback safe |
| `D07-WP-12` | Ad eligibility and decision batch | AdTech/Backend / Performance | Selector, current stop checks, fallback | Paused or unapproved ads do not serve |
| `D07-WP-13` | Public SDK and renderer performance | Frontend / QA, Accessibility | Batched loading, observer, stable placeholders | 500 logical slots not 500 eager creatives |
| `D07-WP-14` | Qualified event pipeline | Measurement / Security, Privacy | Event ingest, dedup, aggregates | Preview/invalid events never billable |
| `D07-WP-15` | Reporting and protected exports | Analytics/Frontend / Security | Scoped read models, report/export lifecycle | Revoked access cannot download artifact |
| `D07-WP-16` | R2 layout-aware heatmaps | Behavioral/Frontend / Privacy, UX | normalized bins, viewport maps, explorer contracts | Mixed layout geometry rejected |
| `D07-WP-17` | R3 usage-based billing and experiments | Finance/Data Science / AdTech | reconciliation, experiments, disputes | No browser-to-ledger direct path |
| `D07-WP-18` | R4 optional integrations and replay | Integrations / Security, Legal | feature gated adapters, replay extension | No mandatory SaaS/replay to boot |
| `D07-WP-19` | QA/performance/security gates | QA/Performance / all leads | tests, threat and load reports | All applicable product release gates pass |
| `D07-WP-20` | Docs, operations, packaging | DevOps/Docs / Tech Lead | API docs, installation, migrations, diagnostics | Reproducible plugin artifact and rollback guidance |

Each WP references its precise `UC-*`, `DM-*`, `VO-*`, `IC-*`, `FR-*`, `BR-*` and `AT-*` IDs in the implementation ticket. Do not request GPT-6 High to implement 18 modules in one unaudited change: use small pull requests and report actual commands and test results at every gate.

### 15.3 Standard GPT-6 High implementation ticket template

```markdown
## Work package
- ID/title; normative docs and source line references
- Release: R1/R2/R3/R4, feature flags/prerequisites
- Owners/reviewers and affected modules
- Allowed files and interfaces; prohibited cross-module writes
- Domain entities/value objects; commands/queries/events
- Preconditions, postconditions, business invariants and transaction boundary
- Trusted site/org actor and authorization policy
- Data migration/index/read-model changes
- API/DTO and frontend states (loading/denied/conflict/retry/success)
- Privacy, security, licensing and accessibility requirements
- Acceptance IDs, positive/negative/concurrency tests, fixtures
- Commands to run, benchmark conditions, expected observations
- Failure recovery, logging/audit and rollback strategy
- Unresolved policy decisions that block execution
## Completion evidence
- Diff summary; exact tests run/results; missing tests/not-run reasons
- Compatibility and performance evidence; defects and risks
- Documentation and contract version updates
- Explicit ready/not-ready verdict for the package
```

### 15.4 No invented implementation evidence

A specification or plan is not an implementation. A successful release claim requires executed tests, reviewed code, database and browser measurements, audited dependencies and product-owner sign-off. GPT-6 High must never report a test as passed merely because it wrote the test definition.

---

## 16. Verification and acceptance scenario catalog

Each `D07-T-*` case is supplementary to source `AT-*`, `AT-BR-*`, `E2E-*`, `PERM-*` and `AR-T-*`. The test oracle must compare *authoritative persisted facts* and observable UI/API behavior rather than solely mocking application handlers. Suites shall include pure unit tests, integration tests with actual supported database engine, WordPress/WooCommerce tests, browser tests, and fault injection where appropriate.

| ID | Release | Module / test focus | Given / When | Required expected outcome |
|---|---|---|---|---|
| `D07-T-001` | R1 | Value Objects | Reject adding two different currency codes | Money operation throws currency_mismatch; no ledger mutation |
| `D07-T-002` | R1 | Value Objects | Apply exact minor-unit rounding across tax and refund breakdown | No binary float loss; original/adjustment entries sum exactly by currency |
| `D07-T-003` | R1 | Value Objects | Quote a local time that does not exist at DST spring transition | Explicit correction choice or validation error; no silently shifted purchase |
| `D07-T-004` | R1 | Value Objects | Quote an ambiguous repeated local time at DST fall transition | Disambiguation recorded with original IANA zone and UTC interval |
| `D07-T-005` | R1 | Authorization | Authenticated advertiser accesses campaign of another organization by guessed ID | Object denied without foreign tenant metadata |
| `D07-T-006` | R1 | Authorization | User belongs to two organizations and switches active workspace | Scope resolved per request; no cross-org filter/result union |
| `D07-T-007` | R1 | Authorization | Membership revoked after frontend permissions fetched | Next server query/action denied; stale UI cannot modify state |
| `D07-T-008` | R1 | Authorization | WordPress network super admin targets another site without approved grant | Denied or gated according to D05 site governance; no cross-site leak |
| `D07-T-009` | R1 | Authorization | CLI worker receives payload with arbitrary privileged user ID | Reject unverified job principal and actor impersonation |
| `D07-T-010` | R1 | Advertisers | Replay an already accepted invitation token | Second acceptance denied or idempotently returns existing membership |
| `D07-T-011` | R1 | Advertisers | Manager attempts to invite themselves as owner without authorization | Privilege escalation denied with audit |
| `D07-T-012` | R1 | Advertisers | Organization suspended while buyer is at checkout | Authoritative checkout/serve gates deny new actions; recovery guidance available |
| `D07-T-013` | R1 | Inventory | Create a 200-slot footer by one compact sequence | Stable logical slot range resolvable; no 200 Gutenberg child blocks |
| `D07-T-014` | R1 | Inventory | Define four header, 100 sidebar, 196 article, 200 footer slots | 500 logical positions compile deterministically with correct zone identities |
| `D07-T-015` | R1 | Inventory | Generate 1,000 logical slots on approved host fixture | Bounded generation/memory; no eager 1,000 creative assets |
| `D07-T-016` | R1 | Inventory | Archive a reserved logical slot | Commercial impact policy blocks or requires governed remedy |
| `D07-T-017` | R1 | Layout | Publish responsive revision changing only grid columns | Commercial slot IDs preserved; new immutable layout revision assigned |
| `D07-T-018` | R1 | Layout | Delete sold premium footer position in draft and publish | Contract-impact analysis blocks until authorized remedy resolved |
| `D07-T-019` | R1 | Layout | Rollback to old revision that now conflicts with newer reservations | Rollback denied or remediated; no pointer reset without validation |
| `D07-T-020` | R1 | Layout | Two editors modify one layout from the same starting version | Second write returns version conflict, no silent last-write overwrite |
| `D07-T-021` | R1 | Layout | Repeat one zone twice on same page via hook and block | Logical slot references coherent; render-instance identifiers distinct |
| `D07-T-022` | R1 | Gutenberg | View 500-slot zone in block editor preview | Representative bounded preview; zero production billable metrics |
| `D07-T-023` | R1 | Gutenberg | Call same public zone through PHP, shortcode, and Gutenberg | Same eligibility rules and safe output semantics across adapters |
| `D07-T-024` | R1 | Gutenberg | Forged shortcode asks to display rejected creative | Rejected and no creative served; shortcode has no privileged override |
| `D07-T-025` | R1 | Campaigns | Buyer pays but creative remains pending | Campaign does not serve; portal shows independent approval/payment state |
| `D07-T-026` | R1 | Campaigns | Approved creative media/destination is changed | New version requires review; prior reviewed facts remain immutable |
| `D07-T-027` | R1 | Campaigns | Pause approved campaign after decision metadata cache filled | New serving requests deny according to enforced freshness; no stale bypass |
| `D07-T-028` | R1 | Creatives | Upload SVG with active script or mismatched claimed MIME | Asset rejected by R1 creative policy |
| `D07-T-029` | R1 | Creatives | Moderator approves version A while version B has arrived | Approval binds A fingerprint only; B remains unapproved |
| `D07-T-030` | R1 | Pricing | Publish new rate card while buyer has valid accepted quote | Accepted quote remains immutable; later quotes use new rate version |
| `D07-T-031` | R1 | Pricing | Submit corrupted money string or inconsistent currency precision | Quote/ledger validation fails without posting |
| `D07-T-032` | R1 | Pricing | Attempt to purchase inventory with stale availability | Hold transaction rechecks and denies conflict even if quote screen appeared valid |
| `D07-T-033` | R1 | Reservations | Two simultaneous buyers purchase same exclusive slot and overlap | Exactly one reservation succeeds; no oversell |
| `D07-T-034` | R1 | Reservations | Two exclusive intervals meet at shared boundary only | Both may be valid under half-open UTC interval policy |
| `D07-T-035` | R1 | Reservations | Late payment arrives after hold TTL expiry | No expired hold revival; explicit pending-remedy path and financial evidence |
| `D07-T-036` | R1 | Reservations | Repeat the same AcquireHold command after lost HTTP response | Idempotent same outcome, no second capacity deduction |
| `D07-T-037` | R1 | Reservations | Transaction support is unavailable | Unsafe purchase disabled with actionable diagnostic; no false guarantee |
| `D07-T-038` | R1 | Commerce | Duplicate webhook events report same captured payment | One posted financial effect; duplicate logged as replay |
| `D07-T-039` | R1 | Commerce | Gateway sends refund event before delayed capture event | State machine handles out-of-order evidence without double payment |
| `D07-T-040` | R1 | Commerce | Browser opens WooCommerce thank-you URL without valid payment | Order remains unpaid/unverified; no delivery eligibility |
| `D07-T-041` | R1 | Commerce | Refund approved after partial campaign delivery | Ledger retains original entries, posts precise adjustments and remedy status |
| `D07-T-042` | R1 | Commerce | WooCommerce uninstalled/disabled after existing order created | Plugin continues core operation; new online checkout disabled; reconciliation visible |
| `D07-T-043` | R1 | Delivery | Public page contains 500 configured slots but user sees first 20 | No 500 rendered/viewable counters; progressive decisions bounded |
| `D07-T-044` | R1 | Delivery | Page cache contains old placeholder after campaign suspension | Current decision rejects suspended creative; fallback used |
| `D07-T-045` | R1 | Delivery | Ad decision endpoint asked for 10,000 slots in one batch | Request bounded/rate-limited; safe error or pagination |
| `D07-T-046` | R1 | Delivery | No eligible creative for a placement | Empty or house fallback; no fabricated sold ad impression |
| `D07-T-047` | R1 | Delivery | Advertiser injects executable HTML in supposedly safe creative | No first-party arbitrary JS; render denied/sandboxed only by later approved policy |
| `D07-T-048` | R1 | Measurement | Replay identical click event ID 20 times | One qualified event at most; duplication metrics available |
| `D07-T-049` | R1 | Measurement | Synthetic load test and editor preview send valid-looking events | Explicitly excluded from billable metrics |
| `D07-T-050` | R1 | Measurement | Browser reports viewable event for slot never rendered/selected | Rejected by event provenance/decision validation |
| `D07-T-051` | R1 | Measurement | Tracking collector unavailable while ad loads | Page remains usable; no invented impression; data gap diagnostic |
| `D07-T-052` | R1 | Reporting | Advertiser requests raw session or unrelated finance fields | Denied; only scoped aggregate safe projection provided |
| `D07-T-053` | R1 | Reporting | Export completes after requesting user membership is revoked | Download-time access denied; protected artifact not exposed |
| `D07-T-054` | R1 | Reporting | CSV report cell contains spreadsheet formula-like value | Neutralized/safely encoded on export |
| `D07-T-055` | R1 | Platform | Re-run activation/migration multiple times | Idempotent plugin-owned resources, no duplicate roles/pages/table records |
| `D07-T-056` | R1 | Platform | Worker crashes after DB commit before notification dispatch | Outbox retry delivers once effectively; authoritative state intact |
| `D07-T-057` | R1 | Platform | Site A cached report fetched by site B or org B | Scope keys prevent cross-site/cross-org leak |
| `D07-T-058` | R2 | Heatmaps | Normalize clicks within footer slot under desktop and mobile breakpoints | Device/layout anchored bins, no raw pixel mixing |
| `D07-T-059` | R2 | Heatmaps | Change active layout after collecting old heatmap samples | Historical data retains old geometry version; compare shows version warning |
| `D07-T-060` | R2 | Heatmaps | Withdraw optional behavior collection consent | Future disallowed telemetry suppressed and pending buffer handled by policy |
| `D07-T-061` | R2 | Heatmaps | Pointer sample touches protected form input | Sensitive content absent from payload and storage |
| `D07-T-062` | R2 | Heatmaps | Long page scrolls but user never reaches bottom 200 footer slots | Footer unseen; no derived visibility impression for unexposed slots |
| `D07-T-063` | R3 | Metered Billing | Send raw browser click signal and inspect ledger | No direct posting; only reconciled verified billable usage may charge |
| `D07-T-064` | R3 | Experiments | Run A/B with insufficient randomized units | No unqualified winner claim; report uncertainty and quality limits |
| `D07-T-065` | R3 | Optimization | Recommendation attempts to change sold slot without approval | Normal impacted Layout/Reservation command gate blocks bypass |
| `D07-T-066` | R4 | Replay | R4 replay feature flag disabled but collector request sent | Server refuses replay collection, no stealth recording |
| `D07-T-067` | R4 | Integration | External webhook destination resolves to internal network address | SSRF policy rejects endpoint; no secret connection |
| `D07-T-068` | R4 | Multisite | Network worker switches sites and throws exception | Original blog context restored; site-bound scope not leaked |

### 16.1 Testing oracle and evidence storage

For every scenario, record fixtures, authoritative persisted state before/after, API response, audit/outbox entries, frontend state where applicable, relevant log/trace with secrets redacted, and actual test execution result. For financial/concurrency cases use a real compatible transactional database, parallel processes and failure injection; pure mocked tests are not sufficient. Use Playwright/device tests for long-page scrolling, Gutenberg and accessibility scenarios. Do not mark tests passed until executed against the intended supported environment.

### 16.2 Acceptance relationship to original scenarios

The `D07-T-*` cases supplement, not replace, `AT-BR-001..018`, `E2E-01..17`, `UXA-01..28`, `PERM-001..016`, `D05-T-*`, and `AR-T-*`. QA shall maintain a test coverage matrix keyed by requirement, release, owning module and level (unit/integration/E2E/security/load). Business invariants must have more than nominal happy-path coverage.

---

## 17. Bidirectional traceability — original functional requirements to domain owners

The rows below are generated directly from the 129 canonical `FR-*` identifiers in ADP-DOC-001; text snippets are navigational summaries, not replacement normative wording. **Read the full PRD statement and matching `AT-*` in ADP-DOC-002 before coding.** A module shown as primary does not remove cross-module review obligations. Release labels are copied as authored, including multi-release labels.

| Canonical ID | Release | Domain owner / published use cases | Existing detailed test | Requirement summary (PRD excerpt) |
|---|---|---|---|---|
| `FR-INV-001` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-001` | Authorized administrators SHALL create, rename, archive, clone and search zones with stable unique IDs and descriptions |
| `FR-INV-002` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-002` | Zones SHALL contain multiple containers; containers SHALL support single, row, vertical stack and responsive grid configurations |
| `FR-INV-003` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-003` | Administrators SHALL configure slot count without a hard-coded 500-slot product maximum. Tested baseline: 500 logical slots on one page; stress test: 1,000+. Hosting-specific safe limits… |
| `FR-INV-004` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-004` | A single container SHALL generate 100 sidebar or 200 footer slots from a compact configuration, not 100/200 manually persisted Gutenberg blocks |
| `FR-INV-005` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-005` | Responsive layouts SHALL support desktop/tablet/mobile column count, minimum width, aspect ratio, gaps and visibility rules without mutating contractual slot identity |
| `FR-INV-006` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-006` | The system SHALL resolve placement bindings for posts, pages, archives, search contexts, product pages where integrated, template parts and approved custom hooks |
| `FR-INV-007` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-007` | Administrators SHALL preview zones and slots with availability, reservation occupancy, breakpoint and empty-state information |
| `FR-INV-008` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-008` | Layout edits SHALL support draft, preview, validate, publish, immutable version history and controlled rollback |
| `FR-INV-009` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-009` | Changes affecting reserved/sold inventory SHALL warn, block or initiate explicit migration/reapproval before publication |
| `FR-INV-010` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-010` | Administrators SHALL bulk assign, unassign, edit, duplicate and filter generated slots using paginated/virtualized controls |
| `FR-INV-011` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-011` | Slots SHALL have explicit inactive, eligible, reserved, occupied and unavailable states where applicable |
| `FR-INV-012` | R2 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-012` | Container types SHOULD extend to carousel, masonry, sticky and conditional/repeater presentations with accessibility and density constraints |
| `FR-INV-013` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-013` | On empty eligible inventory, configurable house creative, collapsed placeholder or reserved blank layout space SHALL be possible without falsely counting impressions |
| `FR-INV-014` | R1 | Inventory + Layout; `UC-INV-*/UC-LAY-*` | `AT-INV-014` | The system SHALL detect duplicate unintended injection and distinguish it from permitted repeated rendering |
| `FR-INT-001` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-001` | Register dynamic blocks adpilot/ad-slot, adpilot/ad-zone, adpilot/ad-repeater, adpilot/ad-showcase using block.json and server-rendered output |
| `FR-INT-002` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-002` | Each block SHALL expose relevant configuration, accessible editor controls, responsive preview and bounded representative rendering; editor previews SHALL not count as paid ad delivery |
| `FR-INT-003` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-003` | adpilot/ad-zone SHALL store a compact stable zone reference and permitted overrides instead of one block per generated slot |
| `FR-INT-004` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-004` | Repeater behavior SHALL support explicit interval policies for eligible content contexts; integrations into query loops, product grids and third-party layouts SHALL be… |
| `FR-INT-005` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-005` | Provide public PHP functions adpilot_render_slot(), adpilot_render_zone(), and adpilot_render_container() with stable, documented argument and return/error semantics |
| `FR-INT-006` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-006` | Provide corresponding shortcodes, e.g. [adpilot_slot], [adpilot_zone], [adpilot_repeater], and [adpilot_showcase] |
| `FR-INT-007` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-007` | Provide extensible action/filter hooks and a capability-gated REST API; all integration paths SHALL invoke the same authorization/eligibility/rendering core |
| `FR-INT-008` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-008` | Support classic themes, block themes, template parts and traditional widget areas through appropriately scoped adapters |
| `FR-INT-009` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-009` | Avoid overriding theme global styles; use scoped styles and namespaced classes/CSS variables |
| `FR-INT-010` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-010` | No shortcode, Gutenberg parameter or PHP template option SHALL bypass purchased inventory, creative approval, commercial restrictions, or security rules |
| `FR-INT-011` | R2 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-011` | Provide layout/theme diagnostics for unresolved hooks, unsupported selectors, duplicates and rendering conflicts |
| `FR-INT-012` | R1 | Integrations + Gutenberg/Layout; `UC-INT-*/UC-LAY-Q03` | `AT-INT-012` | Documentation SHALL give minimal working examples for each supported theme integration path |
| `FR-MKT-001` | R1 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-001` | Advertisers SHALL browse a public, filterable catalog of published placements and packages, with dimensions, format eligibility, price, schedule and clear terms |
| `FR-MKT-002` | R1 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-002` | Published placement pages SHALL include sanitized preview, responsive context and measured historical estimates when available, labeled as estimates rather than guarantees |
| `FR-MKT-003` | R1 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-003` | Authorized advertisers SHALL register, manage business profiles, view their campaigns, invoices and notifications, and resume saved drafts |
| `FR-MKT-004` | R1 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-004` | Advertiser organizations SHALL support owner/manager/analyst/billing membership with least-privilege permissions and secure invitations |
| `FR-MKT-005` | R1 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-005` | Campaign wizard SHALL guide placement selection, creative upload, targeting where allowed, scheduling, quote review, submission and checkout |
| `FR-MKT-006` | R1 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-006` | The wizard SHALL provide validation and clear recoverable errors, maintain drafts and preserve state during normal navigation |
| `FR-MKT-007` | R1 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-007` | Advertisers SHALL see distinguishable approval, payment and delivery states |
| `FR-MKT-008` | R1 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-008` | Public catalog SHALL expose no advertiser-private, financial-sensitive or visitor-personal records |
| `FR-MKT-009` | R1 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-009` | Administrators SHALL manage advertisers, verify organizations where configured, suspend accounts, and audit changes |
| `FR-MKT-010` | R2 | Advertisers + Identity; `UC-ADV-*/UC-IDN-*` | `AT-MKT-010` | Campaign renewal, duplication, packaged purchasing and organization reporting enhancements SHOULD be available |
| `FR-CAM-001` | R1 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-001` | Campaigns SHALL have separately managed approval, financial and delivery state machines |
| `FR-CAM-002` | R1 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-002` | Campaigns SHALL support drafts, submission, review, changes requested, approval, rejection, schedule, activation, pause, completion and cancellation/suspension paths |
| `FR-CAM-003` | R1 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-003` | Creatives SHALL support safe raster images and text/native ad templates; file dimensions, format, size, destination URL and alt text SHALL be validated |
| `FR-CAM-004` | R1 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-004` | Advertisers SHALL upload multiple creative versions and request edits; policy-relevant changes require reapproval before serving |
| `FR-CAM-005` | R1 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-005` | Moderators SHALL approve/reject with reasons, maintain audit trails and support emergency stop |
| `FR-CAM-006` | R1 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-006` | Paid placement SHALL not imply automatic creative approval; rejection SHALL invoke documented revision, cancellation and refund policies |
| `FR-CAM-007` | R1 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-007` | Native/sponsored formats SHALL expose sponsorship disclosure and appropriate link attributes |
| `FR-CAM-008` | R1 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-008` | Arbitrary advertiser-controlled JavaScript, unsanitized SVG and unrestricted HTML SHALL be prohibited. Safe interactive/sandboxed formats may be separately designed for later release |
| `FR-CAM-009` | R2 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-009` | Video and interactive advertising MAY be supported subject to explicit format, security, performance and accessibility acceptance tests |
| `FR-CAM-010` | R1 | Campaigns + Creatives; `UC-CAM-*/UC-CRT-*` | `AT-CAM-010` | Campaign audit trail SHALL record high-value actions including owner, time, previous/new state, and safe explanation |
| `FR-COM-001` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-001` | Support fixed-price, time-based and exclusive sponsorship price plans with clear units and rate-card version |
| `FR-COM-002` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-002` | Availability SHALL be calculated from binding, capacity, exclusions, reservations and active allocations for the requested period |
| `FR-COM-003` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-003` | Reservation creation SHALL be concurrency-safe, have bounded expiry and be idempotent on retries |
| `FR-COM-004` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-004` | Quote SHALL include currency, units, dates/time zone, inventory, discounts, taxes where configured, expiration and applicable approval/refund conditions |
| `FR-COM-005` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-005` | WooCommerce adapter SHALL map AdPilot commercial orders to WooCommerce orders using supported WooCommerce CRUD/HPOS-compatible APIs |
| `FR-COM-006` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-006` | Payment status SHALL be obtained only from authoritative commerce/gateway callbacks, not browser redirect success alone |
| `FR-COM-007` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-007` | Duplicate, delayed and out-of-order callbacks SHALL not double-allocate inventory or duplicate invoices/charges |
| `FR-COM-008` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-008` | Refunds, cancellation, payment reversals, expired quotes and declined creatives SHALL have explicit policy-governed workflows |
| `FR-COM-009` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-009` | Financial records SHALL maintain auditable adjustments and reconciliation against commercial orders |
| `FR-COM-010` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-010` | Administrators SHALL view order, payment, reservation and fulfillment discrepancies |
| `FR-COM-011` | R3 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-011` | Qualified, reconciled CPM/CPC, spending caps, credits and share-of-voice contracts SHALL be introduced only after measurement/billing certification tests |
| `FR-COM-012` | R4 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-012` | Additional payment/billing providers MAY be implemented without entangling the core inventory and campaign domains |
| `FR-COM-013` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-013` | When WooCommerce is absent, product SHALL still support administrator-approved complimentary/manual campaigns and all non-checkout functions; online self-service payment UI SHALL clearly… |
| `FR-COM-014` | R1 | Pricing + Reservations + Commerce; `UC-PRC-*/UC-RES-*/UC-COM-*` | `AT-COM-014` | Time ranges SHALL be stored in UTC, rendered in selected site/account timezone, and tested across daylight-saving transitions |
| `FR-DEL-001` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-001` | Delivery decisions SHALL gate on approved creative, valid contract, active schedule, available allocation, policy eligibility and budget/hold rules |
| `FR-DEL-002` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-002` | Support fixed assignment, priority, weighted rotation and house-ad fallback with auditable selection rules |
| `FR-DEL-003` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-003` | Eligible targeting SHALL include page, post type, category/taxonomy, device/breakpoint and locale; advanced geo/audience policies require privacy/legal review |
| `FR-DEL-004` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-004` | Layout markup SHALL reserve predictable space or use controlled collapsing to minimize layout shifts |
| `FR-DEL-005` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-005` | Load creatives progressively as slots approach the viewport and batch backend decisions; do not fetch every heavy creative upfront on a 500-slot page |
| `FR-DEL-006` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-006` | Limit in-flight fetches, observers and event handlers; reuse shared browser primitives where appropriate |
| `FR-DEL-007` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-007` | Different occurrences of the same slot/zone on a page SHALL have unique render identifiers and correct event attribution |
| `FR-DEL-008` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-008` | Integration SHALL be resilient to full-page caching and common CDNs and shall not cache visitor-specific sensitive decisions in shared contexts |
| `FR-DEL-009` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-009` | Failure of delivery/analytics endpoints SHALL not break the containing WordPress page |
| `FR-DEL-010` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-010` | Admin previews, editor screens, tests and staging mode SHALL be flagged as nonbillable/nonproduction |
| `FR-DEL-011` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-011` | Ad density, slot count and large creative configurations SHALL produce configurable UX/performance warnings; supported logical capacity does not equal permission to show all creatives… |
| `FR-DEL-012` | R2/R3 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-012` | Advanced frequency caps, pacing, viewability-aware delivery and spend-limited allocation SHALL be implemented with explicit data-collection constraints |
| `FR-DEL-013` | R1 | Delivery + Compliance; `UC-DEL-*/UC-PRV-Q01` | `AT-DEL-013` | A page SHALL remain navigable and accessible with JavaScript disabled, with documented limitations to dynamic filling and metrics |
| `FR-MET-001` | R1 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-001` | Separate ad requested, selected/served, browser rendered, click observed, click qualified and billable event types |
| `FR-MET-002` | R1 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-002` | Distinguish counts and denominators in UI/API; metrics SHALL not silently conflate served with viewable |
| `FR-MET-003` | R1 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-003` | Validate events server-side for schema, origin/context where practicable, bounded payload and rate limits |
| `FR-MET-004` | R1 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-004` | Provide event IDs and bounded deduplication/aggregation with at-least-once delivery assumptions |
| `FR-MET-005` | R1 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-005` | Exclude editor previews, testing traffic and known invalid automation under defined rules |
| `FR-MET-006` | R1 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-006` | Aggregate campaign, creative, page, zone and slot statistics by time bucket without requiring an unbounded raw-event history |
| `FR-MET-007` | R2 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-007` | Measure viewability with explicit observable-browser thresholds and time-in-view; document unsupported/browser-limited measurement |
| `FR-MET-008` | R2 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-008` | Report measurable vs nonmeasurable impressions and reasons where known |
| `FR-MET-009` | R3 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-009` | CPC/CPM billing SHALL be based on qualified, reconciled events and contract-specific rules, not client-reported counters alone |
| `FR-MET-010` | R3 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-010` | Maintain invalid-traffic investigation and adjustment workflow without claiming perfect fraud detection |
| `FR-MET-011` | R3 | Measurement + Commerce (R3); `UC-MET-*/UC-COM-C04` | `AT-MET-011` | Off-site conversions SHALL require advertiser-installed or otherwise authorized integrations, with attribution model/windows explicitly labeled |
| `FR-HEAT-001` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-001` | Provide click/tap, scroll-depth and element/slot-visibility heatmaps, with an optional attention-time **proxy** view; never label proxies as eye tracking |
| `FR-HEAT-002` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-002` | Admin SHALL inspect full-page, zone, container and slot-level overlays on vertically scrollable pages |
| `FR-HEAT-003` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-003` | Browser collector SHALL use normalized coordinates plus stable semantic element/slot references |
| `FR-HEAT-004` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-004` | Aggregates SHALL be segmented by page layout version and responsive device class; incompatible geometries shall not be combined silently |
| `FR-HEAT-005` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-005` | Editor SHALL show selected date range, event sample size, sampling status, denominator, measurement limitations and empty states |
| `FR-HEAT-006` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-006` | Administrators SHALL filter by eligible campaign, creative, zone, page/template, device and reporting period |
| `FR-HEAT-007` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-007` | Support side-by-side comparison of compatible layout periods and numeric accessible alternatives to color-only heatmaps |
| `FR-HEAT-008` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-008` | Tracking SHALL be consent-aware where required, exclude protected/sensitive content, and minimize visitor identifiers |
| `FR-HEAT-009` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-009` | Changing page geometry, infinite scroll or AJAX insertion SHALL not silently assign historical events to the wrong placement |
| `FR-HEAT-010` | R4 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-010` | Optional sampled session replay SHALL be disabled by default, separately permissioned, sensitive-content-safe, retention-controlled, and never a prerequisite for regular ad serving |
| `FR-HEAT-011` | R2 | BehavioralAnalytics + Compliance; `UC-HEAT-*/UC-PRV-Q02` | `AT-HEAT-011` | Heatmap tracking and visualization SHALL operate without mandating Hotjar, Clarity or another proprietary hosted service |
| `FR-REP-001` | R1 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-001` | Administrator dashboard SHALL surface revenue/order state, active/pending campaigns, placement occupancy, basic delivery status and operational alerts |
| `FR-REP-002` | R1 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-002` | Advertiser dashboard SHALL expose only their own campaigns, approval/payment status, qualified statistics and invoices where available |
| `FR-REP-003` | R1 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-003` | Reports SHALL support period, campaign, creative, zone/slot and device filters where data is available |
| `FR-REP-004` | R1 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-004` | Report values SHALL identify units, currencies, denominators, time zone and data freshness |
| `FR-REP-005` | R2 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-005` | Add viewability, scroll, placement exposure and heatmap summaries; export aggregate CSV/JSON as allowed |
| `FR-REP-006` | R3 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-006` | Add conversion attribution, finance reconciliations, experiments, anomaly analysis and evidence-based placement recommendations |
| `FR-REP-007` | R3 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-007` | Forecasts and estimates SHALL be visually labeled and accompanied by assumptions and uncertainty |
| `FR-REP-008` | R2 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-008` | Long-running report exports SHALL use queued background processing and scoped download authorization |
| `FR-REP-009` | R2 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-009` | Administrators SHALL review pending jobs, delivery/aggregation health and retention cleanup status |
| `FR-REP-010` | R1 | Reporting + Optimization; `UC-REP-*/UC-OPT-*` | `AT-REP-010` | Operational metrics, raw behavioral data and replay recordings SHALL not be exposed through ordinary advertiser reporting |
| `FR-UX-001` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-001` | Provide distinct admin console, advertiser portal, public storefront, Gutenberg block controls and Layout Studio, under one coherent design system |
| `FR-UX-002` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-002` | Every primary workflow SHALL specify empty/loading/error/success/confirmation states and recovery paths |
| `FR-UX-003` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-003` | Layout Studio SHALL use visual canvas + zone tree + inspector, while also supporting keyboard-accessible and nonvisual editing |
| `FR-UX-004` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-004` | Screens containing hundreds of slots SHALL display summarized/paginated controls rather than hundreds of simultaneously mounted edit widgets |
| `FR-UX-005` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-005` | Advertiser wizard SHALL autosave drafts, show a transparent cost/approval summary, and avoid silent loss of changes |
| `FR-UX-006` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-006` | Primary interface SHALL meet WCAG 2.2 AA as a project target, with automated and manual verification |
| `FR-UX-007` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-007` | Use WordPress localization APIs; support RTL/LTR layouts, locale-aware dates, numbers and currency presentation |
| `FR-UX-008` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-008` | Responsive controls and page previews SHALL work across phone, tablet and desktop representative widths |
| `FR-UX-009` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-009` | Avoid unnecessary global theme CSS/JS and unrelated plugin side effects |
| `FR-UX-010` | R2/R4 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-010` | Offer richer saved report views, white-label/site branding and advanced multisite workflows subject to individual release scopes |
| `FR-UX-011` | R1 | Presentation + Identity; `UC-IDN-Q03/UC-INT-Q01` | `AT-UX-011` | AdPilot SHALL expose high-quality diagnostic feedback rather than generic fatal errors for configuration and integration failures |
| `FR-OPS-001` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-001` | Apply capability checks plus resource ownership to every sensitive REST/UI/CLI operation |
| `FR-OPS-002` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-002` | Validate and sanitize inputs, escape output by context, use prepared SQL and defend against CSRF, XSS, SSRF, upload abuse, replay and broken object authorization |
| `FR-OPS-003` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-003` | Audit financial changes, campaign approval, placement publication, account membership and emergency pause |
| `FR-OPS-004` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-004` | Enable privacy policy guidance, configurable retention, eligible data export/deletion and consent integration points |
| `FR-OPS-005` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-005` | Exclude sensitive DOM/form/payment values from any analytics collection; absence of user consent must prevent collection where required |
| `FR-OPS-006` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-006` | Provide diagnostics, explicit job retries, failed-job reporting, queue monitoring and supportable system cron/WP-CLI execution |
| `FR-OPS-007` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-007` | Implement schema-versioned installation/upgrades, data-preserving deactivation, intentional uninstall behavior and upgrade testing |
| `FR-OPS-008` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-008` | Distributable libraries, assets and source code SHALL have documented GPL-compatible licensing; generate dependency register/SBOM and required notices |
| `FR-OPS-009` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-009` | Use a versioned REST namespace and stable hooks/shortcodes/PHP APIs; changes SHALL follow documented deprecation policy |
| `FR-OPS-010` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-010` | Expose structured, redacted logs, health checks, and safe administrator diagnostic exports |
| `FR-OPS-011` | R4 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-011` | Support optional independently deployed workers/analytics integrations without a forced redesign of core WordPress services |
| `FR-OPS-012` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-012` | Plugin bootstrap SHALL not require WooCommerce or proprietary remote services merely to load |
| `FR-OPS-013` | R1 | Platform + Compliance + Integrations; `UC-OPS-*/UC-PRV-*/UC-INT-*` | `AT-OPS-013` | Plugin activation SHALL create only documented, owned resources and SHALL avoid modifying unrelated existing pages/settings without authorization |

### 17.1 Traceability accounting

| Canonical family | Count | Primary implementation boundary |
|---|---:|---|
| `FR-CAM-*` | 10 | Campaigns + Creatives |
| `FR-COM-*` | 14 | Pricing + Reservations + Commerce |
| `FR-DEL-*` | 13 | Delivery + Compliance |
| `FR-HEAT-*` | 11 | BehavioralAnalytics + Compliance |
| `FR-INT-*` | 12 | Integrations + Gutenberg/Layout |
| `FR-INV-*` | 14 | Inventory + Layout |
| `FR-MET-*` | 11 | Measurement + Commerce (R3) |
| `FR-MKT-*` | 10 | Advertisers + Identity |
| `FR-OPS-*` | 13 | Platform + Compliance + Integrations |
| `FR-REP-*` | 10 | Reporting + Optimization |
| `FR-UX-*` | 11 | Presentation + Identity |
| **Total** | **129** | One-to-one canonical IDs and `AT-*` coverage |

### 17.2 Other governing requirements and artifacts

| Source ID family | Required enforcement artifact | Proof requirement |
|---|---|---|
| `BR-001..018` | §9.3 business-rule gates; ADP-DOC-004 exact rule text | Matching `AT-BR-*` plus negative cases |
| `INV-01..10` | §9.2 invariant map; authoritative domain ports | Integration/transaction/authorization evidence |
| `NFR-001..016` | Architecture, reliability, accessibility, privacy, performance and license governance | Actual benchmark, audit and compatibility results |
| `AC-001..015` | R1 release acceptance gates | QA/Product sign-off; no assumed pass |
| `CAP-01..18` | Module boundaries in §5, ADP-DOC-006 §6 | Feature owner acceptance |
| `IC-001..024` | §6 published contracts | Schema and consumer contract tests |
| `SM-*` (15) | §9 transition ownership; ADP-DOC-004 allowed transitions | Exhaustive transition and forbidden-path tests |
| `PERS/JTBD/UJ/WF/UXA` | ADP-DOC-003 workflows and UI states | Usability/E2E/accessibility evidence |
| `ARCH-BND-*` and `AR-T-*` | ADP-DOC-006 architecture gates | Static boundaries and runtime integration evidence |

---

## 17.3 Individual nonfunctional requirements and release acceptance gates

The individual IDs below are included so implementations can trace every baseline nonfunctional and R1 acceptance obligation without relying on a range shorthand. Exact text remains in ADP-DOC-001; these are verbatim one-line source statements with assigned verification ownership.

### 17.3.1 Nonfunctional requirements (`NFR-001..016`)

| Canonical ID | Domain/quality focus | Normative statement from PRD | Primary reviewer |
|---|---|---|---|
| `NFR-001` | Architecture | Modular-monolith design with explicit domain/application/infrastructure/presentation boundaries; PHP WordPress host; no mandatory external application server in R1. | Architect/Backend |
| `NFR-002` | Maintainability | WordPress Coding Standards, PHP static analysis, TypeScript strict mode, clear module APIs, source-level documentation and reviewed dependencies. | Architect/QA |
| `NFR-003` | Rendering | A 500-logical-slot page must not eagerly fetch or paint 500 heavy creatives. Browser work and network concurrency must be bounded; no per-slot PHP query pattern. | Frontend/Delivery |
| `NFR-004` | Performance | Establish and validate environment-specific p75/p95 budgets for request overhead, Core Web Vitals, query count, JS bundle size, browser memory, batching and queue lag; declare benchmark hardware, creative sizes and cache configuration. | Performance/Frontend |
| `NFR-005` | Reliability | Idempotent payment webhooks and jobs; resilient scheduler; safe fallback if ad delivery or analytics endpoint fails. | Reliability/DevOps |
| `NFR-006` | Financial correctness | Database-enforced uniqueness/capacity controls, concurrency testing, reconciliation, immutable adjustment trail and precise currency arithmetic. | Finance/Reservations |
| `NFR-007` | Security | Threat model, dependency scans, permission tests, hardened creative handling, secure REST, server-side validation and emergency shutdown. | Security |
| `NFR-008` | Privacy | Data minimization, regional consent policy, no sensitive input collection, finite behavioral-event retention and protected access. | Privacy/Compliance |
| `NFR-009` | Accessibility | WCAG 2.2 AA target for critical UIs, including nonvisual alternatives to heatmaps and drag/drop interactions. | Accessibility/UX |
| `NFR-010` | Compatibility | Validated WordPress/PHP/MySQL/MariaDB matrix; support classic and block themes, modern browsers, WooCommerce HPOS when commerce adapter enabled. | WP/Integration |
| `NFR-011` | Internationalization | Translation-ready strings, RTL/LTR support, reliable date/time-zone and currency displays. | i18n/Frontend |
| `NFR-012` | Observability | Logs, queue metrics, failed-event reporting, health diagnostics, financial discrepancy alerts and safe support exports. | DevOps/QA |
| `NFR-013` | Extensibility | Versioned REST API, documented hooks, stable PHP and shortcode contracts, adapter interfaces and deprecation policy. | API/Architect |
| `NFR-014` | Licensing | GPL-compatible distribution, recorded license texts/notices, transitive dependency screening, no unclear copyrighted assets. | Legal/Release |
| `NFR-015` | Upgrade safety | Idempotent migrations, version compatibility checks, reliable backup guidance, data-preserving deactivation and explicit uninstall. | Database/DevOps |
| `NFR-016` | Usability | Critical flows verified through task-based usability tests; major actions provide comprehensible states, feedback and undo/recovery when feasible. | UX/Research |

### 17.3.2 R1 product acceptance gates (`AC-001..015`)

| Canonical ID | Authoritative requirement | Related application contract |
|---|---|---|
| `AC-001` | Publisher creates and publishes a 500-slot page consisting of 4 header, 100 sidebar, 196 in-content and 200 footer logical slots. Layout Studio remains usable; publishing and responsive preview succeed. | `UC-INV-* / UC-LAY-*` |
| `AC-002` | One `adpilot/ad-zone` block can reference a 200-slot footer container without storing 200 Gutenberg blocks. | `UC-LAY-Q03 / UC-INT-Q01` |
| `AC-003` | Four supported integration paths (Gutenberg, PHP function, shortcode, and supported hook binding) render through one policy-aware engine with correct per-occurrence identifiers. | `UC-INT-Q01 / UC-DEL-Q02` |
| `AC-004` | Public page initially loads bounded creatives near viewport and does not miscount 500 configured slots as impressions. | `UC-DEL-Q02 / UC-MET-C02` |
| `AC-005` | Advertiser can discover an available slot, obtain a quote, reserve, pay through configured WooCommerce, upload creative, request review and observe activation when approved. | `UC-PRC-C02 / UC-RES-C01 / UC-COM-C03 / UC-CRT-C04` |
| `AC-006` | Two concurrent buyers cannot successfully reserve the same exclusive inventory over overlapping time windows. | `UC-RES-C01` |
| `AC-007` | Duplicate payment callbacks and campaign activation retries do not double-allocate, double-invoice or double-activate. | `UC-COM-C03 / UC-OPS-C03` |
| `AC-008` | Creative changes requiring reapproval cannot bypass review and cannot serve in an unapproved state. | `UC-CRT-C04 / UC-DEL-Q01` |
| `AC-009` | Advertiser A cannot access advertiser B's campaigns, invoices, raw behavioral data or privileged reports through UI or direct API calls. | `UC-IDN-Q02 / UC-REP-Q02` |
| `AC-010` | Classic theme, block theme, common caching setup and supported WooCommerce HPOS mode pass the declared compatibility suite. | `UC-INT-Q01 / UC-COM-C02` |
| `AC-011` | Broken analytics endpoint does not crash the public site, block reading content or silently produce fictitious billable events. | `UC-MET-C01 / UC-DEL-Q02` |
| `AC-012` | Privacy-sensitive pages/inputs are excluded from collection; denied consent prevents prohibited collection. | `UC-PRV-Q02 / UC-HEAT-C01` |
| `AC-013` | All primary application workflows pass agreed manual accessibility and usability gates. | `UC-IDN-Q03 / UX validation` |
| `AC-014` | Installer works without WooCommerce for nonpayment functions and provides an accurate diagnostic for unavailable online checkout. | `UC-OPS-Q01 / UC-COM-C02` |
| `AC-015` | Release contains reproducible build, automated tests, license notices, migrations and administrator/developer documentation. | `UC-OPS-C01 / CI/Release` |

Each R1 gate is mandatory for the R1 release; any dependencies not ready must be resolved or the release explicitly held, rather than relabeling an acceptance obligation as a future feature. Evidence must include actual execution of related integration/E2E/security and performance tests.

---

## 18. Open decisions, sign-off and handoff

### 18.1 Decisions that block specific implementation work

| Decision / owner | Implementation consequence | Required approval before coding a definitive rule |
|---|---|---|
| Refund and rejected-paid remedy (`DEC-007`, Product/Finance/Legal) | Financial percentages, deadlines and contractual rights cannot be guessed | Approved policy, acceptance tests and updated PRD if meaning changes |
| Jurisdictional consent/retention (Privacy/Legal) | Cannot indiscriminately record behavior/replay or assume legal basis | Approved region/purpose matrix and retention classes |
| Tax, settlement currency, financial precision (Finance) | Cannot assert tax/rounding defaults universally | Approved currency/tax configuration and exact-value tests |
| Site scope and network federation (Security/Architecture) | No implicit network super-admin data union | Release-gated scoped federation design |
| Campaign delivery state owner (Architecture/AdTech) | Avoid duplicate mutable state field between Campaigns and Delivery | Single command owner and published status projection |
| Exclusive-capacity granularity (AdTech/Database) | Booking lock keys and share policy depend on commercial slot semantics | Confirm normalized reservation-capacity contract |
| Creative HTML/video formats (Security/Product) | Cannot enable first-party advertiser script by default | Sandboxing, CSP, accessibility, performance and policy sign-off |
| Release support matrix (WordPress/DevOps) | PHP/WP/Woo/database minimums require actual compatibility tests | Frozen supported versions, signed-off CI matrix |
| Measurement and metered contract definitions (AdTech/Finance) | R3 billable metrics cannot inherit raw frontend counters | Reconciled usage definitions and dispute workflow |
| Open-source dependency audit (Legal/Release) | Third-party package distribution and notices must be verified | SBOM, transitive license and vulnerability report |

### 18.2 Review and acceptance signature gates

**Required reviewers:** Product Owner (scope and user outcomes), Principal Architect (module/contract boundaries), WordPress Engineer (platform APIs), UX/Frontend Lead (design, high-density interactions, accessibility), AdTech/Measurement Lead (delivery/metric semantics), Finance Lead (reservations and financial reconciliation), Security/Privacy Leads (site/org access and behavioral data), Database/DevOps Lead (transaction/outbox/migrations), and QA Lead (testability and release gates). Unresolved policy decisions remain visibly blocked; proposed architecture does not itself count as signed off.

### 18.3 Documentation follow-on / next specification

**ADP-DOC-008 — Database Schema, Migrations, Indexes & Data Lifecycle Specification** shall turn the approved `DM-*` aggregates and repository contracts into explicit tables, keys, unique/index constraints, transaction boundaries, migration strategies, read models, retention and purge behavior, multisite storage mapping, and comprehensive database acceptance tests. Document 08 shall not reinterpret billing truth, permissions, booking overlap or published layout immutability.

### 18.4 Verification status

This is a documentation artifact. Identifier and Markdown audits may be performed on the generated file; no claim is made of executed PHP code, browser testing, penetration testing, financial settlement validation or production readiness.

---

**Document-end checkpoint:** ADP-DOC-007 establishes proposed domain and application contracts and preserves the canonical source requirements. Implementation may proceed in bounded work packages only after the corresponding policy and architecture gates are accepted.
