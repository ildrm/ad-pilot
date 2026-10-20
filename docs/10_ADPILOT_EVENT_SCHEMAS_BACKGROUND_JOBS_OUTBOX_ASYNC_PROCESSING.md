# AdPilot — Event Schemas, Background Jobs, Outbox & Asynchronous Processing Specification

**Document ID:** ADP-DOC-010  
**Filename:** `10_ADPILOT_EVENT_SCHEMAS_BACKGROUND_JOBS_OUTBOX_ASYNC_PROCESSING.md`  
**Version:** 1.0.0 (proposed implementation contract)  
**Status:** Draft for architecture, database, finance, privacy, security, AdTech and QA approval; implementation not executed  
**Date:** 2026-10-12  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Implementation target:** GPT-6 High, bounded executable work packages  
**Accountable owner:** Principal Software Architect + Reliability/Distributed Processing Engineer  
**Required reviewers:** WordPress Lead, Database Lead, Commerce/Finance Lead, Measurement Lead, Privacy/Security Leads, Frontend/UX Lead, QA Lead, DevOps Lead  
**Normative predecessors:** `ADP-DOC-001` through `ADP-DOC-009`  
**Next document:** `ADP-DOC-011 — UX Architecture, Navigation & Interaction Design Specification`

> **Status notice.** This is a production-oriented design specification, not a claim of deployed workers, established throughput, executed tests, measured SLOs or formally audited dependencies. Field names and code fragments not already frozen in Documents 07–09 are explicit *proposals* requiring review and compatible migrations. Unknown operational thresholds must be benchmarked and approved; no fabricated performance measurements are stated.

---

## 1. Purpose, document hierarchy and binding conventions

### 1.1 Purpose

Specify a reliable asynchronous substrate for AdPilot's WordPress-native modular monolith: durable business events, transactionally captured outbox messages, idempotent consumers, Action Scheduler-driven work, operational reconciliation, notifications and optional signed webhooks, measurement ingestion and aggregation, secure exports, retention and recovery. It must sustain high-density pages with **500+ configured logical slots** without confusing configured slots, fetched creatives, render signals, qualified viewability events or billable events.

### 1.2 Source precedence

| Document | Controlling provisions that this specification SHALL preserve |
|---|---|
| `ADP-DOC-001` | Canonical `FR-*` (129), `BR-001..018`, `NFR-001..016`, `AC-001..015`, `INV-01..10`, releases R1–R4 |
| `ADP-DOC-002` | `AT-*` requirements, 18 capability groups `CAP-01..18`, 17 `E2E-*` tests |
| `ADP-DOC-003` | 28 `UJ-*` journeys, 18 `WF-*` workflows, 20 `UI-*` screens and user-facing recovery states |
| `ADP-DOC-004` | `DP-*` policy guards, independent `SM-*` state machines and paid-asset change control |
| `ADP-DOC-005` | Site-plane capabilities, organization-scoped membership, current-authorization requirements |
| `ADP-DOC-006` | Module boundaries, 24 `IC-*` published contracts, ADRs and eventual-consistency limits |
| `ADP-DOC-007` | 28 `DM-*` aggregates, 30 existing `EV-*` event IDs, outbox and application-service ports |
| `ADP-DOC-008` | Approved/proposed 50-table persistence catalog, migration/transaction protocols, retention rules |
| `ADP-DOC-009` | `API-*`/route identifiers, REST envelope, payment ingress, webhook signing and status semantics |
| **This document** | `ASY-*` supplementary queue/event/job implementation requirements, tests and operations |

Any inconsistency SHALL be logged as a cross-document change request; no downstream agent may silently rewrite `BR-*`, `IC-*`, `EV-*`, release scopes, schema columns or privilege semantics. In particular, D09 lists R1 outbound webhook operations whereas D08 schedules `webhook_subscriptions` as R4: this conflict is documented in §24 and **gated pending approved resolution**, rather than assuming the feature shipped in R1.

### 1.3 Normative words and time standards

`MUST`/`SHALL` are release obligations, `SHOULD` permits reviewed and documented deviation, and `MAY` is optional. Timestamps in persistent records are UTC with documented precision; input/output API time uses ISO 8601 UTC. Contract intervals use half-open `[start,end)`. Operational deadlines use a monotonic clock **within a running process** where possible but persisted due times are UTC; tolerate clock skew, delayed cron, daylight saving transitions and process restarts.

### 1.4 R1 versus advanced releases

- **R1:** Durable business outbox, idempotent commercial workflows, due campaign/hold processing, WooCommerce adapter events, qualified basic measurement, hourly/daily aggregate reporting, exports, notifications, audit and safe cleanup.
- **R2:** Native heatmap bins, sanitized geometry snapshots, privacy-gated engagement aggregation and related backfill processes.
- **R3:** Validated CPC/CPM billing evidence, meter reconciliations, experiment evaluation, fraud review and optional more elaborate pacing.
- **R4:** Optional replay retention/processing, network federation, independently deployed workers and additional integration adapters; webhook subscription runtime gated until D08/D09 release conflict is resolved.

R2–R4 jobs must not be scheduled, displayed as operative or billed in R1 merely because their message type exists in a registry.

### 1.5 Distinct concepts

- **Domain event:** Historical fact emitted by an authoritative service after a governed state transition. Not a user request and not untrusted browser telemetry.
- **Outbox entry:** Durable, transactionally committed dispatch intent representing that fact.
- **Job:** Runnable unit of background work, usually woken via Action Scheduler; not itself the sole store of financial truth.
- **Inbox consumption:** Consumer-scoped idempotency record for a committed event effect.
- **Measurement event:** Untrusted or qualified browser/server signal with separate provenance and retention.
- **Webhook delivery:** External HTTP attempt carrying a sanitized projection of a committed event, with its own retry identity.
- **Financial ledger fact:** Authoritative commerce posting from verified evidence; never derived merely from a client-side counter.

## 2. Reliability model and system topology

```text
[REST / Gutenberg / PHP / WooCommerce event adapters]
                   |
                   v
        [Authorized application command]
                   |
          BEGIN DB TRANSACTION
           |         |         |
      [Aggregate]  [Audit]  [Outbox row]
           |         |         |
          COMMIT (or atomic rollback)
                   |
         [Outbox wake-up / poller]
                   |
         [Claim with fenced lease]
                   |
       [Fan-out to allowed consumer(s)]
          /       |        |        \
  [Read model] [Notification] [Webhook*] [Cache invalidation]
         |         |          |
   [Inbox ack] [Delivery ack] [Per-attempt audit]
                   |
      [Retry / reconcile / quarantine]

*External webhooks are feature/release gated, not assumed R1.

Browser -> POST /measurement/events -> input validation -> bounded batch
       -> dedup/qualify -> aggregates/checkpoints -> reports
       -> R3 ONLY: separate billable validation -> commerce posting
```

### 2.1 Transactional guarantees

1. Commercial mutation, authoritative record(s), required audit and outbox event **MUST commit together** when within one supported database transaction.
2. Workers may execute a job more than once. Claiming and scheduling alone never guarantee exactly-once side effects.
3. Each financial/event consumer MUST be *effectively once* by unique deduplication and atomic effect+inbox insert, where local transaction capabilities allow; unavoidable nontransactional external side effects follow provider-specific reconciliation.
4. Publishers SHALL not serve a known suspended or expired campaign just because a queue, webhook or cache is delayed. Eligibility is read from authoritative states and revocation/stop version.
5. Worker progress may lag for reports, email, cache warmup and alerts; hold exclusivity, current permission, settlement and contract entitlement are not permitted to become unguarded eventually-consistent guesses.
6. An outage in reporting, analytics or outbound notification MUST NOT silently roll back or fabricate the outcome of an already committed payment or reservation.
7. No plugin routine should enqueue an event before its transaction commits, because the worker could observe an invalid or rolled-back business fact.

### 2.2 Guarantees by boundary

| Boundary | Delivery model | Authoritative proof | Acceptable lag | Forbidden behavior |
|---|---|---|---|---|
| Browser → event collector | Best effort with explicit loss reporting | Validation/dedup + accepted batch state | Configured reporting lag | Assume every browser send is billable |
| Command → domain store/outbox | Atomic DB transaction | Persisted aggregate, audit and outbox | None for commit | Business success without durable evidence |
| Outbox → internal consumer | At least once | Fenced claim, inbox + local effect | Monitored | Infinite retries with no alarm |
| Jobs → authoritative service | At least once | Re-evaluated current state | Business-policy deadline | Blind state transition from stale payload |
| Payment provider → Commerce | Provider-specific, possibly duplicated/out of order | Verified provider evidence + ledger reconciliation | Pending-verification UX | Treat thank-you URL as paid |
| Notification → recipient | Best effort with managed retry | Attempt/accepted relay status | Configured | Claim inbox delivery = email read |
| Outbound webhook → receiver | At least once when feature enabled | Signed delivery attempts + receiver ACK | Configured | Assume receiver executes business effect exactly once |
| Aggregation → reports | At least once + deterministic merge/checkpoint | Source-batch coverage and aggregate revision | Freshness badge | Duplicate counts after replay |

### 2.3 WordPress-native queue runtime

Use Action Scheduler when installed and initialized; document dependency loading strategy and avoid bundling a conflicting version. Use namespaced `adpilot_*` hooks and group labels. WooCommerce may provide Action Scheduler, but AdPilot's core MUST remain installable without WooCommerce; therefore it SHALL have an approved independently packaged/loaded scheduler integration or a safe documented fallback for noncritical jobs. For purchase safety, do not equate a WP-Cron fallback with guaranteed timely scheduling. Production installations SHOULD use reliable system cron or WP-CLI runner, health monitoring and explicit diagnostics. Queue runners and app services are not permitted to assume they own Action Scheduler's internal tables.

### 2.4 Side effect classification

**Class A — mandatory synchronous gates:** authorization, inventory conflict checks, reservation hold, financial state, creative approval, delivery stop, immutable ledger/audit/outbox posting. These require authoritative transaction/policy enforcement before success.

**Class B — essential deferred processes with governed fallback:** expiring hold sweeps, campaign due-state evaluation, payment reconciliation, stale reservation repair, status propagation, privacy purges. Monitor and reconcile overdue actions.

**Class C — eventually consistent read/communication:** aggregate reports, heatmaps, email, exports, third-party webhooks, search indexes, optional suggestions. Their failure must not compromise Class A correctness.

## 3. Event envelope and schema registry

### 3.1 Canonical committed domain event envelope — `ASY-EVT-001`

A committed event MUST have the following immutable fields (camelCase for domain/PHP contracts; snake_case for stored SQL where D08 already fixes columns):

| Field | Type | Rule |
|---|---|---|
| `eventId` | Opaque 26-character validated ID | Globally unique within site; maps to `outbox_messages.event_public_id` |
| `eventType` | Registry-defined event name | No arbitrary client-supplied topic |
| `schemaVersion` | Positive integer | Maps to D08 `event_schema_version`; exact breaking-change semantics |
| `occurredAtUtc` | UTC instant | Persisted transaction fact time, not queue processing time |
| `siteId` | Trusted integer | Derived from authoritative WordPress site context |
| `organizationId` | Optional scoped internal ID | Provided only for events whose owner allows it |
| `aggregateType` | Registry aggregate type | Maps to D08 `aggregate_type` |
| `aggregateId` | Internal aggregate key | Maps to D08 `aggregate_id`; do not expose via external webhook |
| `aggregateVersion` | Nonnegative committed revision | Consumer detects stale/out-of-order effects |
| `correlationId` | Bounded nonsecret identifier | Request-to-event tracing |
| `causationId` | Optional event/command ID | Prevent cycles and investigate orchestration |
| `privacyClassification` | Registry enum | `internal`, `restricted`, `aggregate`, never customer-controlled |
| `payload` | Versioned allowlisted DTO | No secrets, card data, raw session content or arbitrary serialized models |

**D08 compatibility:** outbox records explicitly store `topic`, `event_schema_version`, aggregate columns, `payload_json`, `occurred_at`, status and attempts. The additional context fields SHALL be encoded and validated within `payload_json` as a bounded versioned envelope or introduced via approved additive migration; do not silently assume nonexisting physical columns. Use stable canonical serialization and repository-level hashes where needed for replay safety.

### 3.2 Domain event example (illustrative, not a complete financial verification payload)

```json
{
  "eventId": "<validated-opaque-event-id>",
  "eventType": "ReservationCommitted",
  "schemaVersion": 1,
  "occurredAtUtc": "2026-10-12T00:00:00.000000Z",
  "siteId": 2,
  "organizationId": 45,
  "aggregateType": "ContractReservation",
  "aggregateId": 901,
  "aggregateVersion": 3,
  "correlationId": "6d7e62bb-f17b-49cd-8f22-9267df768df8",
  "causationId": "<prior-authoritative-command-id>",
  "privacyClassification": "restricted",
  "payload": {
    "reservationPublicId": "<validated-opaque-reservation-id>",
    "orderPublicId": "<validated-opaque-order-id>",
    "commercialIntervalUtc": {
      "start": "2026-10-13T12:00:00Z",
      "end": "2026-10-20T12:00:00Z"
    },
    "exclusivity": "exclusive"
  }
}
```

Illustrative internal IDs are NOT to be mirrored to public webhook subscribers. External payload projection rules come from D09 §11, and signing uses the **exact** D09 byte protocol.

### 3.3 Schema compatibility and evolution — `ASY-EVT-002`

- Additive optional fields may be introduced within an explicitly approved compatible schema version; consumers ignore unknown optional fields only if safe.
- Removing, renaming, changing meaning or narrowing any required field requires a new registered major schema revision and upcasters or time-limited dual readers.
- No in-place mutation of already committed outbox event payloads for normal version upgrades.
- Producer and consumer supported version ranges are recorded in the `EventSchemaRegistry` and included in upgrade tests.
- Unknown version, missing mandatory fields, invalid aggregate version or oversized payload => quarantine with structured redacted reason and operator alert; do not silently drop.
- Never let an event name imply permission to execute an action. Workers resolve authenticated internal provenance and current state/policy before any sensitive effect.
- Consumers handling sensitive state must not trust the event's *historical* permission as current authorization. They recheck current site/org entitlement and actor policy, or use explicitly authorized narrowly scoped internal principal for mandated system work.

### 3.4 Event ordering — `ASY-EVT-003`

- **Per aggregate:** track aggregate revision; a consumer may defer/reconcile out-of-order events or derive current projection from authoritative query instead of applying a stale delta.
- **Across aggregates:** no ordering guarantee; include causal IDs but never assume `PaymentSettled` always appears before `CreativeVersionApproved` or `ReservationCommitted` in the queue.
- **Cache:** use monotonic stop/eligibility version or short bounded TTL; old invalidation messages cannot roll eligibility backwards.
- **Fencing:** a stale worker may not commit after ownership/lease loss; compare current claim token and state when acknowledging.
- **Rebuild:** preserve stable dedup keys and source checkpoints; a replay must not re-post financial entries or emit duplicate business decisions.

### 3.5 Classification, allowed listeners and suppression

| Classification | Permitted consumer projections | Prohibited content |
|---|---|---|
| `internal` | Trusted site-scoped handlers, redacted operations logs | Credentials or raw exception stack |
| `restricted` | Authorized org/site scoped projection or audited worker | Full payment evidence, personal session content |
| `aggregate` | Role-scoped analytic projections after suppression | Cross-organization visitor-level data |
| `privacy_sensitive` | Explicitly authorized purpose-scoped handler only | Free-form DOM/form text or card/PAN data |

Notifications and outbound webhooks MUST reproject allowed fields from current policy, rather than serializing raw event envelopes verbatim. An event about consent withdrawal triggers immediate collection suppression through synchronous policy, with queue work handling deletion/backfill only; a delayed worker must not continue newly forbidden collection.

## 4. Canonical event catalog and routing contracts (`EV-001..EV-030`)

Events below reproduce the identifiers and proposed names from `ADP-DOC-007 §7`; `ASY-EH-*` identifiers name consumer-handler contracts in this document, not new source events. Production event payloads SHALL be defined in typed DTOs with schema and privacy tests.

| Canonical ID | Event | Producer/owner | Release | Required response | Handler contract |
|---|---|---|---|---|---|
| `EV-001` | `OrganizationRegistered` | Advertisers | R1 | Welcome/onboarding notification, restricted admin read projection | `ASY-EH-001` |
| `EV-002` | `MembershipGranted` | Identity | R1 | Invalidate permission projection, safe team notification | `ASY-EH-002` |
| `EV-003` | `MembershipRevoked` | Identity | R1 | Invalidate all tenant-scoped read caches; reject future exports | `ASY-EH-003` |
| `EV-004` | `OrganizationSuspended` | Advertisers | R1 | Campaign/Delivery current-state gates, alerts | `ASY-EH-004` |
| `EV-005` | `ZoneCreated` | Inventory | R1 | Layout availability and audit updates | `ASY-EH-005` |
| `EV-006` | `InventoryDefinitionChanged` | Inventory | R1 | Revalidate draft bindings, invalidate safe layout caches | `ASY-EH-006` |
| `EV-007` | `LayoutRevisionPublished` | Layout | R1 | Invalidate placement plan; retain history/geometry | `ASY-EH-007` |
| `EV-008` | `LayoutRemedyRequested` | Layout | R1 | AdOps/Finance notifications, governed change workflow | `ASY-EH-008` |
| `EV-009` | `CampaignSubmitted` | Campaigns | R1 | Creative moderator queue and status projection | `ASY-EH-009` |
| `EV-010` | `CreativeVersionApproved` | Creatives | R1 | Eligibility version invalidation; status projection | `ASY-EH-010` |
| `EV-011` | `CreativeVersionRejected` | Creatives | R1 | Notify advertiser; open policy-required remedy if paid | `ASY-EH-011` |
| `EV-012` | `CampaignPaused` | Campaigns | R1 | Delivery cache invalidation and status update | `ASY-EH-012` |
| `EV-013` | `QuoteIssued` | Pricing | R1 | Safe draft/checkout notification | `ASY-EH-013` |
| `EV-014` | `HoldAcquired` | Reservations | R1 | Reservation calendar and checkout state | `ASY-EH-014` |
| `EV-015` | `HoldExpired` | Reservations | R1 | Release capacity; refresh storefront estimate | `ASY-EH-015` |
| `EV-016` | `ReservationCommitted` | Reservations | R1 | Campaign entitlement and delivery eligibility | `ASY-EH-016` |
| `EV-017` | `AdvertisingOrderCreated` | Commerce | R1 | WooCommerce adapter mapping and status projection | `ASY-EH-017` |
| `EV-018` | `PaymentEvidenceAccepted` | Commerce | R1 | Reconciliation/hold conversion orchestration, audited receipt | `ASY-EH-018` |
| `EV-019` | `PaymentSettled` | Commerce | R1 | Eligibility after authoritative reconciliation; notification | `ASY-EH-019` |
| `EV-020` | `CommercialRemedyOpened` | Commerce | R1 | Finance/admin action and advertiser recovery UI | `ASY-EH-020` |
| `EV-021` | `RefundRecorded` | Commerce | R1 | Ledger/read model and campaign entitlement review | `ASY-EH-021` |
| `EV-022` | `DeliveryEmergencyStopped` | Delivery | R1 | Immediate revocation and operations alert | `ASY-EH-022` |
| `EV-023` | `MeasurementBatchQualified` | Measurement | R1 | Authorized report read-model aggregation | `ASY-EH-023` |
| `EV-024` | `ReportExportReady` | Reporting | R1 | Notification with restricted one-time navigation, not bare public URL | `ASY-EH-024` |
| `EV-025` | `HeatmapAggregateUpdated` | BehavioralAnalytics | R2 | Restricted analytics read model refresh | `ASY-EH-025` |
| `EV-026` | `BillableEventReconciled` | Measurement/Commerce orchestration | R3 | R3 controlled ledger posting, dispute proof | `ASY-EH-026` |
| `EV-027` | `ExperimentCompleted` | Optimization | R3 | Review-only recommendations, no implicit paid-slot mutation | `ASY-EH-027` |
| `EV-028` | `ReplayPolicyChanged` | Compliance | R4 | R4 consent/retention enforcement and audit | `ASY-EH-028` |
| `EV-029` | `ExternalIntegrationConfigured` | Integrations | R4, optional R1 hook features | Adapter health/authorization refresh | `ASY-EH-029` |
| `EV-030` | `DataRetentionCompleted` | Compliance | R1/R2 | Privacy/compliance audit without raw deleted data | `ASY-EH-030` |

### 4.1 Mandatory event publisher rules

For each canonical event in the registry the owning application service SHALL define: producing command and exact state transition; aggregate and expected version; payload field allowlist; privacy classification; release gate; whether audit/outbox are required; subscribed internal consumers; replay behavior; failure and reconciliation paths; and a complete test vector. R1 events are mandatory only for activated capabilities—`EV-029` optional R1 hook functionality does not grant automatic R1 support for outbound subscriptions.

### 4.2 Handler validation for every `ASY-EH-*`

1. Validate current enabled feature, schema version and tenant/site scope.
2. Validate aggregate reference and provenance from the persisted committed outbox, not arbitrary client HTTP data.
3. Resolve current authoritative state when applying revocation, payment, booking, creative approval or privileged actions.
4. Check stable consumer key plus event ID in `inbox_consumptions` under a suitable transaction.
5. If not yet consumed, execute only the allowed local effect and commit the inbox acknowledgement atomically where supported.
6. If already consumed, acknowledge without repeating externally visible side effects; for nontransactional outbound calls use independent attempt idempotency and reconciliation.
7. Classify transient/permanent/security/privacy errors, redact logs, and only reschedule according to configured policy.
8. Never invoke another module's private repository; call the published `IC-*` boundary or application use case.

### 4.3 Strict source/derived-event separation

These registered `EV-*` events represent **committed domain facts**. `ad.rendered`, `ad.viewable`, `ad.clicked`, heatmap pointer samples and browser scroll events remain **measurement inputs**, which must be validated/deduplicated before `EV-023` may assert a qualified batch. Financial postings in R3 require a further governed reconciliation boundary, not a browser or generic analytics event callback.

### 4.4 `EV-026` decision boundary

D07 identifies a possible naming overlap in `BillableEventReconciled`: qualification belongs to Measurement while ledger posting belongs to Commerce. Preserve the canonical event ID as a provisional trigger but require an ADR deciding whether to split into `MeteredUsageReconciled` and `LedgerChargePosted`. R3 financial posting is blocked until the event ownership, dedup key and accounting treatment have been signed off; this document does not silently resolve the predecessor's open question.

## 5. Job envelope and queue registration

### 5.1 Job command envelope — `ASY-JOB-001`

```json
{
  "jobSchemaVersion": 1,
  "jobType": "adpilot.reservations.expire_holds",
  "jobId": "<validated-opaque-job-id>",
  "siteId": 2,
  "correlationId": "6d7e62bb-f17b-49cd-8f22-9267df768df8",
  "trigger": "scheduled",
  "targetRef": "<optional-opaque-resource-id>",
  "expectedVersion": 5,
  "scheduledForUtc": "2026-10-12T01:00:00Z",
  "attempt": 0,
  "idempotencyScope": "<internal-key-reference>"
}
```

Never serialize a PHP object graph, raw payment provider payload, password, unrestricted filter configuration, current user capability decision, cookie or full session recording into Action Scheduler arguments. Jobs carry IDs and revision hints, not privileged material. The worker reconstructs data under trusted site-scoped repositories and validates its *current* permissions and state. The argument limit and JSON schema are bounded; unrecognized job types fail closed.

### 5.2 Scheduler adapter contract — `ASY-JOB-002`

```php
<?php
namespace AdPilot\Infrastructure\Queue;

interface JobSchedulerPort
{
    public function scheduleOnce(JobDescriptor $job, \DateTimeImmutable $dueUtc): ScheduleResult;
    public function scheduleRecurring(JobDescriptor $job, RecurrencePolicy $policy): ScheduleResult;
    public function cancelByResource(JobCancellationScope $scope): CancellationResult;
    public function inspect(JobReference $reference): JobStatus;
}
```

Proposed types are project abstractions to implement and test; the Action Scheduler implementation maps to official `as_schedule_single_action()`, `as_schedule_recurring_action()`, `as_unschedule_action()` and available queue inspection APIs. Action Scheduler's `$unique` flag helps avoid duplicate *scheduled actions* with matching hook/args/group but is not equivalent to durable idempotency of financial side effects. A scheduling failure must be visible to diagnostics and recovered by bounded scanning of authoritative due records.

### 5.3 Names and groups

- Hook naming: `adpilot_job_<verb>_<subject>_v1` with registry keys versioned separately.
- Groups: `adpilot-commercial`, `adpilot-delivery`, `adpilot-measurement`, `adpilot-reports`, `adpilot-compliance`, `adpilot-integrations`, `adpilot-maintenance`.
- Queue settings are **per site**, feature-gated, bounded and adjustable according to verified hosting capacity.
- Separate high-impact commercial work from expensive export/heatmap computation to reduce starvation. Group labels alone do not guarantee dedicated workers or strict priority; runner strategy and monitoring must enforce priorities.
- No arbitrary hook names or dynamic callbacks accepted from REST request content.

### 5.4 Job lifecycle — `ASY-JOB-003`

`registered → scheduled → due → claimed → running → succeeded`;
`due|claimed|running → retry_wait → due` on classified transient failures;
`running → quarantined` after terminal validation, privacy/security denial or exhausted attempts;
`scheduled|due|retry_wait → cancelled` when feature/resource policy invalidates the job.

Action Scheduler has its own state model, so these are **AdPilot diagnostic/application states** maintained via adapter/repository projections, not invented native Action Scheduler state strings. A task marked done by the scheduler is not evidence the underlying payment, reservation or export completed successfully—domain state remains authoritative.

### 5.5 Idempotency key construction

Canonical scope should include trusted `siteId`, job type, target resource ID and version (when version governs the action), and semantic step/cycle. A recurring hourly aggregation run keyed only by `jobType` would incorrectly suppress all future runs. Conversely, retries of the *same source batch* must reuse the same stable consumption key. Use a versioned normalized hash and store collision-safe metadata; redact raw tenant identifiers in public logs.

## 6. Background-job catalog and execution contracts

`ASY-J-001..` are supplementary job definitions. Suffix `R4*` denotes pending outbound-webhook release reconciliation (§24); do **not** activate until approved. Job payloads must be bounded and carry **references**, not source objects. Every job has an explicit owner, observation, idempotency key and failure policy.

| Job ID | Registry key (`adpilot.*`) | Release | Queue group | Owner | Trigger | Main invariant |
|---|---|---|---|---|---|---|
| `ASY-J-001` | `adpilot.dispatch.outbox` | R1 | `commercial` | Platform | Poll every approved interval; also wake on commit | No row loss; overdue oldest-event alert |
| `ASY-J-002` | `adpilot.recover.outbox.leases` | R1 | `maintenance` | Platform | Periodic reconciliation | Never let old claimant acknowledge |
| `ASY-J-003` | `adpilot.expire.inventory.holds` | R1 | `commercial` | Reservations | Due hold expiry + periodic sweep | Expired cannot be resurrected |
| `ASY-J-004` | `adpilot.reconcile.hold.capacity` | R1 | `commercial` | Reservations | Periodic and after conflict alarms | No phantom free capacity |
| `ASY-J-005` | `adpilot.evaluate.due.campaigns` | R1 | `commercial` | Campaigns | Periodic due-state query | No approval bypass |
| `ASY-J-006` | `adpilot.reconcile.delivery.stop` | R1 | `delivery` | Delivery | On pause/suspension + periodic audit | Never re-enable paused state |
| `ASY-J-007` | `adpilot.reconcile.verified.payments` | R1 | `commercial` | Commerce | After provider event + periodic sweep | No paid mark from browser |
| `ASY-J-008` | `adpilot.recover.stale.payment.attempts` | R1 | `commercial` | Commerce | Bounded periodic scan | Never guess success on timeout |
| `ASY-J-009` | `adpilot.process.paid.rejection.remedies` | R1 | `commercial` | Commerce | Creative rejected + periodic SLA sweep | No retained funds without clear remedy |
| `ASY-J-010` | `adpilot.reconcile.financial.ledger` | R1 | `commercial` | Commerce | Periodic and manual governed run | No double postings or silent edits |
| `ASY-J-011` | `adpilot.reconcile.order.status` | R1 | `commercial` | Commerce | After Woo event + scheduled sweep | Do not auto-rebook expired slot |
| `ASY-J-012` | `adpilot.send.user.notification` | R1 | `reports` | Notifications | Committed notification intent | No cross-tenant/private leaks |
| `ASY-J-013` | `adpilot.send.admin.alert` | R1 | `maintenance` | Notifications | Threshold breach | Never include secrets in email |
| `ASY-J-014` | `adpilot.aggregate.hourly.stats` | R1 | `measurement` | Measurement | Periodic for closed/open windows | No duplicate count on replay |
| `ASY-J-015` | `adpilot.aggregate.daily.stats` | R1 | `measurement` | Reporting | UTC-day close + delayed correction | State freshness and corrections |
| `ASY-J-016` | `adpilot.qualify.measurement.batches` | R1 | `measurement` | Measurement | On accepted batch + sweeps | Reject preview/nonproduction claims |
| `ASY-J-017` | `adpilot.replay.measurement.deadletters` | R1 | `measurement` | Measurement | Approved manual trigger | No blind bulk unquarantine |
| `ASY-J-018` | `adpilot.generate.report.export` | R1 | `reports` | Reporting | On export request | No public URLs; no unbounded results |
| `ASY-J-019` | `adpilot.expire.protected.exports` | R1 | `compliance` | Reporting | Periodic | No stale downloadable artifacts |
| `ASY-J-020` | `adpilot.purge.expired.raw.events` | R1 | `compliance` | Compliance | Periodic | Never purge needed accounting evidence |
| `ASY-J-021` | `adpilot.purge.expired.dedup.keys` | R1 | `maintenance` | Measurement | Periodic | No premature reacceptance of replay |
| `ASY-J-022` | `adpilot.sweep.expired.invitations` | R1 | `maintenance` | Advertisers | Periodic | No resurrection after expiration |
| `ASY-J-023` | `adpilot.reconcile.missing.jobs` | R1 | `maintenance` | Platform | Periodic watchdog / operator run | Idempotent requeue without duplicate effect |
| `ASY-J-024` | `adpilot.cleanup.stale.idempotency` | R1 | `maintenance` | Platform | Periodic | Do not permit replay after tombstone loss |
| `ASY-J-025` | `adpilot.refresh.safe.cache` | R1 | `delivery` | Platform | Outbox event + periodic | No stale stop override |
| `ASY-J-026` | `adpilot.send.outbound.webhook` | R4* | `integrations` | Integrations | Committed event + enabled subscription | SSRF and entitlement recheck |
| `ASY-J-027` | `adpilot.retry.outbound.webhook` | R4* | `integrations` | Integrations | Retry schedule | No duplicate domain mutation |
| `ASY-J-028` | `adpilot.aggregate.heatmap.bins` | R2 | `measurement` | BehavioralAnalytics | On eligible sampled batches + periodic | Suppress small cohorts |
| `ASY-J-029` | `adpilot.refresh.geometry.snapshots` | R2 | `measurement` | BehavioralAnalytics | On layout published or observed geometry change | No private DOM capture |
| `ASY-J-030` | `adpilot.privacy.withdrawal.purge` | R2 | `compliance` | Compliance | Authoritative consent change/erasure request | Disable tracking immediately before queue |
| `ASY-J-031` | `adpilot.reconcile.metered.usage` | R3 | `commercial` | Measurement | Period close + manual reviewed backfill | No browser direct charge |
| `ASY-J-032` | `adpilot.post.metered.financial.entry` | R3 | `commercial` | Commerce | Committed reconciliation fact | Never twice; no floating money |
| `ASY-J-033` | `adpilot.evaluate.experiment` | R3 | `reports` | Optimization | Completed experiment window | No auto-mutation of sold contracts |
| `ASY-J-034` | `adpilot.detect.traffic.anomalies` | R3 | `measurement` | Measurement | Scheduled windows | Review suspicious not definitive |
| `ASY-J-035` | `adpilot.process.replay.retention` | R4 | `compliance` | BehavioralAnalytics | Periodic / consent change | Never retain raw sensitive DOM |
| `ASY-J-036` | `adpilot.federation.sync` | R4 | `integrations` | Integrations | Scheduled/network event | No silent cross-site blending |

### 6.1 Detailed per-job handler requirements

Every row below SHALL be implemented with: typed argument schema; due-time logic; trusted site resolver; authorized service principal; module query; state recheck; operation transaction or outbox ack; metric names; dead-letter policy; release feature gate; contract tests. Where financial finality or retention makes a generic retry unsafe, `quarantined` is preferable to silently retrying mutations.

| Job | Required handler behavior | Retry/dedup proof | Completion evidence |
|---|---|---|---|
| `ASY-J-001` | Poll committed outbox rows; lease batch and route to registered consumers. | at-least-once internal dispatch. | Source state and audit/projection verified; no row loss; overdue oldest-event alert. |
| `ASY-J-002` | Requeue expired claims after fencing/token check. | Claim token and lease time. | Source state and audit/projection verified; never let old claimant acknowledge. |
| `ASY-J-003` | Recheck hold TTL and release eligible capacity. | Transaction lock slot and hold. | Source state and audit/projection verified; expired cannot be resurrected. |
| `ASY-J-004` | Recalculate active holds and commitments on locked sellable slots. | Ledger/hold/reservation rows. | Source state and audit/projection verified; no phantom free capacity. |
| `ASY-J-005` | Transition scheduled/expired campaign eligibility using current UTC and status. | CAS state version. | Source state and audit/projection verified; no approval bypass. |
| `ASY-J-006` | Confirm emergency pause/stop stamp visible to selection and caches. | Read authoritative stop version. | Source state and audit/projection verified; never re-enable paused state. |
| `ASY-J-007` | Query WooCommerce authoritative order state via CRUD and align AdPilot finance projection. | Verified provider event idempotency. | Source state and audit/projection verified; no paid mark from browser. |
| `ASY-J-008` | Resolve pending provider attempts after timeout or eventual callback. | Provider-specific authoritative query. | Source state and audit/projection verified; never guess success on timeout. |
| `ASY-J-009` | Open/track policy-defined remedies for rejected paid campaigns. | Unique remedy by case. | Source state and audit/projection verified; no retained funds without clear remedy. |
| `ASY-J-010` | Compare authoritative provider/order evidence and immutable postings. | Posting idempotency. | Source state and audit/projection verified; no double postings or silent edits. |
| `ASY-J-011` | Recover order projection drift between WooCommerce and internal contract. | Per-order reconciliation revision. | Source state and audit/projection verified; do not auto-rebook expired slot. |
| `ASY-J-012` | Generate scoped translated notification from current authorized projection. | Recipient current scope and template key. | Source state and audit/projection verified; no cross-tenant/private leaks. |
| `ASY-J-013` | Raise redacted operational or campaign health alert. | Alert fingerprint and cooldown. | Source state and audit/projection verified; never include secrets in email. |
| `ASY-J-014` | Update deterministic metric buckets from qualified source batches. | Source checkpoint + keyset replay. | Source state and audit/projection verified; no duplicate count on replay. |
| `ASY-J-015` | Build UTC daily projections from qualified facts/hourly checkpoint. | Versioned aggregate revision. | Source state and audit/projection verified; state freshness and corrections. |
| `ASY-J-016` | Validate event provenance/dedup and write qualification evidence. | Event hash and batch identity. | Source state and audit/projection verified; reject preview/nonproduction claims. |
| `ASY-J-017` | Authorized replay of bounded quarantined batches for recoverable defects. | Revalidate schema and consent. | Source state and audit/projection verified; no blind bulk unquarantine. |
| `ASY-J-018` | Produce protected scoped CSV/JSON/PDF artifact from authorized aggregate query. | Permission snapshot + current check. | Source state and audit/projection verified; no public urls; no unbounded results. |
| `ASY-J-019` | Delete expired export artifacts and invalidate tokens. | Site scope and legal-hold conditions. | Source state and audit/projection verified; no stale downloadable artifacts. |
| `ASY-J-020` | Purge raw measurement data in bounded batches after retention and legal checks. | Retention policy and checkpoint. | Source state and audit/projection verified; never purge needed accounting evidence. |
| `ASY-J-021` | Remove expired event-key windows without affecting pending batch verification. | Dedup window and checkpoint. | Source state and audit/projection verified; no premature reacceptance of replay. |
| `ASY-J-022` | Expire invitation offers and invalidate usable invitation hashes. | Token expiration and membership version. | Source state and audit/projection verified; no resurrection after expiration. |
| `ASY-J-023` | Scan overdue authoritative due records and regenerate missing scheduling intent. | Authoritative state not queue list alone. | Source state and audit/projection verified; idempotent requeue without duplicate effect. |
| `ASY-J-024` | Purge expired request-level dedup records after preserving commercial evidence. | Legal/finance retention exclusions. | Source state and audit/projection verified; do not permit replay after tombstone loss. |
| `ASY-J-025` | Rebuild safe public placement plans or analytics projections after committed changes. | Version-checked cache swap. | Source state and audit/projection verified; no stale stop override. |
| `ASY-J-026` | Send signed sanitized event projection to verified subscription endpoint. | Attempt ID, event ID, key version. | Source state and audit/projection verified; ssrf and entitlement recheck. |
| `ASY-J-027` | Retry transient failed deliveries with independent attempt identity. | Stable source event + new attempt ID. | Source state and audit/projection verified; no duplicate domain mutation. |
| `ASY-J-028` | Process permitted normalized click/scroll/exposure bins per layout version. | Consent and geometry snapshot. | Source state and audit/projection verified; suppress small cohorts. |
| `ASY-J-029` | Validate/version sanitized page geometry snapshots used for heatmap mapping. | Layout snapshot digest. | Source state and audit/projection verified; no private dom capture. |
| `ASY-J-030` | Erase/expire prohibited trace data and rebuild allowed aggregates as policy requires. | Current policy + subject-scope proof. | Source state and audit/projection verified; disable tracking immediately before queue. |
| `ASY-J-031` | Reconcile qualified billable evidence with metered contract windows. | Meter/source identity + contract revision. | Source state and audit/projection verified; no browser direct charge. |
| `ASY-J-032` | Post exact-money charges from approved reconciled usage. | Ledger source idempotency. | Source state and audit/projection verified; never twice; no floating money. |
| `ASY-J-033` | Evaluate allocation experiments after prerequisite coverage and guardrails. | Assignment/provenance version. | Source state and audit/projection verified; no auto-mutation of sold contracts. |
| `ASY-J-034` | Assess qualified traffic for anomalies and fraud-review triggers. | No irreversible automated customer punishment. | Source state and audit/projection verified; review suspicious not definitive. |
| `ASY-J-035` | Purge expired replay artifacts and revoke access promptly. | Purpose/consent version. | Source state and audit/projection verified; never retain raw sensitive dom. |
| `ASY-J-036` | Transfer approved aggregate network facts via explicit site opt-in. | Site grant and export policy. | Source state and audit/projection verified; no silent cross-site blending. |

### 6.2 Job execution pseudocode — `ASY-JOB-004`

```php
<?php
// Illustrative orchestration only; types and error handling must be implemented.
function runAdPilotJob(JobDescriptor $job): void
{
    $site = $siteResolver->trustedSiteFromScheduledJob($job);
    $featureGate->requireEnabled($job->jobType(), $site);
    $principal = $servicePrincipalFactory->forRegisteredJob($job, $site);
    $schema->validateJob($job);

    $runner->runWithSiteContext($site, function () use ($job, $principal): void {
        $current = $applicationQueries->loadAuthoritativeTarget($job, $principal);
        $policy->requireCurrentPermissionAndState($principal, $current, $job);
        $jobHandlers->registered($job->jobType())->handle($job, $principal, $current);
    });
}
```

Never treat a REST nonce, persisted actor ID or a queue argument as a capability grant. A scheduled reminder might remain valid after a user is removed from an organization, but its sending behavior must be reauthorized and restricted at dispatch time.

### 6.3 Periodic jobs versus one-shot jobs

Prefer a combination of one-shot due jobs and periodic authoritative sweeps for holds/campaigns/payment verification. Periodic scans are necessary because a scheduled action can be lost, canceled, delayed or fail before being queued. Job recovery logic MUST use domain state + due indexes (`DB-Q-06`, `DB-Q-09`, payment states), not `as_next_scheduled_action()` as the only truth.

### 6.4 Priority and fairness

Reserve capacity for urgent commercial reconciliation and delivery stop remediation while avoiding starvation of reporting and privacy deletion work. Implement per-site and per-job concurrency caps, bounded batch sizes, scheduler time budgets, and fair scheduling across sites. At R1, Action Scheduler group labels alone do not provide guaranteed strict prioritization: enforce practical fairness via runner configuration plus watchdog and alert evidence.

### 6.5 Avoiding the one-job-per-impression anti-pattern

A page with 500 configured slots is **not** permitted to schedule 500 standalone PHP jobs on each visitor load. Browser telemetry should use bounded network batches and aggregate workers. Campaign due jobs should be keyed by changed campaign/time, not each viewport observation. All event processor workloads must have backpressure, limits and expired/replayed-batch handling.

## 7. Transactional outbox, claims and recovery

### 7.1 Write-path contract — `ASY-OUT-001`

For any mandatory event, write authoritative mutation + audit + outbox **inside the same InnoDB transaction**. If required transactional capabilities are unavailable, financial and exclusive inventory operations shall fail closed with a diagnostic; do not fall back to nontransactional read-then-write. Notify/wake a worker *after* commit (best effort); periodic poller guarantees wake-up recovery for committed pending messages.

```text
BEGIN
  SELECT/UPDATE authoritative aggregate with required lock/CAS
  enforce domain policy and current access scope
  write audit record
  INSERT outbox event_public_id UNIQUE per site
COMMIT
[best effort] schedule dispatch wake-up
```

### 7.2 Schema agreement and fenced claims — `ASY-OUT-002`

Document 08 specifies `outbox_messages` fields including `status`, `available_at`, `attempts`, `dispatched_at`, and an indexed `(site_id,status,available_at,id)` lookup. Those fields suffice for basic pending/due scans but **do not by themselves define a robust owner token for concurrent and crashed workers**. This document proposes an additive migration **`ASY-DB-001`** (pending D08 approval) adding `claim_token`, `lease_expires_at`, and optionally `last_error_code` and dispatch revision, or an equivalent separate lease table with fencing, demonstrated by concurrency tests. No code may simply assume these columns exist on D08's frozen schema. Until approved, use a conservative serialized per-site dispatcher with durable idempotent consumers and test crash/recovery, not unguarded concurrent claims.

Lease/fencing algorithm after approved migration:

1. Select bounded due IDs via index without wide table locking.
2. Atomically transition one row from eligible `pending`/`retry_wait` to `leased` with unique `claim_token`, `lease_expires_at`, version/attempt CAS and site scope.
3. Worker reads only rows whose token it owns, validates schema and feature gate, and performs registered fan-out.
4. Each consumer independently deduplicates by `(site_id,consumer_key,event_public_id)`; external side effects use independent stable idempotency keys.
5. Acknowledge only if `claim_token` and active lease ownership match; increment dispatch revision or set completed state atomically.
6. If worker crashes, a sweeper may re-lease after expiry; stale worker cannot acknowledge its obsolete token.
7. Poison, unauthorized or unsupported-version rows enter quarantine with redacted reason and operator action, never discarded.

### 7.3 Fan-out and multi-consumer completion — `ASY-OUT-003`

A single outbox event may have several internal handlers (e.g., `ReservationCommitted` → read-model refresh, notification, eligibility cache). **Do not** mark the event globally dispatched before its required consumers have each reached a persisted outcome. The `inbox_consumptions` unique key covers successful handler effects, but pending/failure status per consumer needs a work-item projection, deterministic consumer registry reconciliation, or approved supplementary dispatch table (**`ASY-DB-002`**, D08 change-control required). Re-enumerate registered consumers on retry and skip already acknowledged results. Preserve event type/version plus handler version to avoid old workers processing new payload semantics.

### 7.4 Idempotent consumer transaction — `ASY-OUT-004`

```php
<?php
// Non-production-complete example, within one site DB transaction.
$transaction->run(function () use ($event, $consumerKey): void {
    $existing = $inbox->findForUpdate($event->siteId, $consumerKey, $event->eventId);
    if ($existing !== null) {
        return; // Already committed handler effect.
    }
    $current = $ownedRepository->loadCurrentAuthorizedState($event);
    $handler->applyIdempotentLocalEffect($event, $current);
    $inbox->insertConsumed($event->siteId, $consumerKey, $event->eventId);
});
```

**Important:** A simple `SELECT` then `INSERT` without unique keys and valid transactional strategy is unsafe. Use unique constraint and handle duplicate-key races. For nontransactional email/HTTP sends, reserve an attempt identity before sending, record outcome, and reconcile ambiguous timeouts. Exactly-once external delivery is not promised.

### 7.5 Semantic status and retry causes — `ASY-OUT-005`

Proposed statuses: `pending`, `leased`, `retry_wait`, `delivered`, `quarantined`; **these are a proposed state enumeration**, not yet an approved D08 physical SQL constraint. `available_at` is the next retry due, not a record of payment entitlement. `attempts` is bounded and increments under claim. `dispatched_at` records full required internal dispatch completion, not external subscriber delivery.

### 7.6 Reconciliation requirements

Check: committed business fact with no outbox row; outbox row referencing missing aggregate; stuck lease; outbox backpressure; consumer ack lacking expected aggregate; repeated payment fact with unique provider event; overdue mandatory notifications; report checkpoint lag; active quote/hold with no scheduled expiry. Reconcile only by comparing authoritative committed records and audit data. Operator repair commands need current elevated permissions and explicit reason/correlation.

## 8. Inbox, ordering, versions and replay controls

### 8.1 Consumer identity

`consumer_key` SHALL be a stable identifier (`module.projection.vN`), not a runtime object name that unpredictably changes on deployments. If code is refactored, preserve the key or migrate inbox acknowledgement semantics intentionally to avoid mass reprocessing. Per-site unique consumption key is `(site_id,consumer_key,event_public_id)`, as in Document 08.

### 8.2 Commutative and noncommutative effects

- Rebuilding a cache from current aggregate state is generally replay-safe.
- Incrementing a counter from a duplicate event is NOT replay-safe unless the batch/checkpoint dedup id protects the increment.
- Posting money is NOT replay-safe unless the source posting ID/ledger key is unique and checked transactionally.
- Changing a campaign state based on an old event may violate the current stop/version policy; re-query authoritative aggregate and use CAS.
- Dispatching a notification may be deduplicated by notification-intent ID; identical email acceptance is not verifiable as exactly once after an ambiguous SMTP timeout.

### 8.3 Backfill identifiers

Each backfill has an approved `run_id`, source range/window, tenant/site scope, schema version and dry-run report. Use a *new aggregate revision or rebuild staging table* instead of duplicating existing totals. Never reset financial idempotency keys to rerun billed events. Record reconciliation diff, affected ranges and final sign-off.

### 8.4 Schema rolling upgrades

Stop incompatible consumers during schema migrations; register upcasters; allow old and new payloads only when verified compatible. A message with a future unknown major version is quarantined, not processed speculatively. On rollback, ensure new outbox payload versions will not corrupt older plugin releases; version gate and explicit rollback strategy are release requirements.

## 9. Scheduled inventory, commercial time and campaign state

### 9.1 Hold expiry — `ASY-COM-001`

Triggering a timer does not make a hold expired by itself. Before release, load hold + slot capacity under the approved site-scoped transaction; compare `expires_at` with authoritative UTC; verify the hold remains in an expirable state and has not been legally consumed by a committed contract. Release only capacity belonging to the hold. Write the hold state, audit and `EV-015 HoldExpired` together. An expired hold cannot be revived by payment that arrives later. Late verified payment follows remedy and reconciliation policy (`BR-002`, `BR-004`, `BR-005`).

**Race test:** worker processing expiry and concurrent payment confirmation lock resources in a single documented global lock order. Exactly one valid transition wins; the loser sees the committed version and performs no conflicting mutation. Re-running either branch does not double release or double reserve.

### 9.2 Exclusive reservation and payment — `ASY-COM-002`

Checkout is synchronous where exclusivity is at stake. Inventory validation, sorted slot/capacity locks, idempotency record, hold/reservation transition, commercial fact, audit and outbox execute inside approved transaction boundaries. A queued job may reconcile the aftermath but SHALL NOT substitute for a missing transactional availability check. The binding order-to-WooCommerce references use approved Woo CRUD/HPOS adapter; never direct HPOS SQL. A successful API response or gateway callback is not accepted until provider-order evidence has been verified and matched to the correct site, order, amount/currency, state and idempotency reference.

### 9.3 Due campaign activation — `ASY-COM-003`

Periodic and one-shot scheduled actions query campaigns whose due range intersects current UTC. Each transition independently verifies creative exact-version approval, current contract/payment entitlement, slot reservation, site/org suspension, delivery stop version, budget, schedule and feature availability. A campaign with no valid creative or a failed payment remains not serving, even if its start timestamp passed hours earlier. Date/time recurrence uses stored IANA display timezone and explicit DST ambiguity resolution at contract creation; persisted transition due dates are UTC.

### 9.4 Emergency stop and paused campaigns — `ASY-COM-004`

`DeliveryEmergencyStopped`, campaign pause, creative revocation and organization suspension must apply synchronously to authoritative state and stop version. Cache invalidation and status projections may follow asynchronously, but ad decisions MUST consult an authoritative or bounded-validity stop stamp (per approved freshness policy) before serving. Never rely solely on a queued event to stop delivery. A retry of an old activation message cannot undo a newer stop version.

### 9.5 Layout revisions affecting sold inventory — `ASY-COM-005`

Publishing a layout with existing sold slots requires contract-impact review before committing the active revision pointer. A queued side effect may refresh placement cache or notify the advertiser, but cannot automatically approve relocation of paid inventory. Preserve logical slot identity and historical layout geometry. If a new layout is published while cache warmup fails, the renderer must use the immutable authoritative active pointer or a safe empty result, not a guessed stale position.

### 9.6 No guaranteed scheduled time from WP-Cron alone

A scheduled time is a business obligation, not an Action Scheduler guarantee. Production health pages shall show overdue activation/expiry, last scheduler run and drift. The app should use a real cron/CLI runner where available and maintain defensive runtime eligibility checks for start/end boundaries so a late timer cannot keep displaying an expired campaign. Provide an installation diagnostic explaining limitations of traffic-triggered WP-Cron.

## 10. WooCommerce ingress and financial reconciliation

### 10.1 Provider evidence pipeline — `ASY-PAY-001`

1. Receive a WooCommerce-native lifecycle notification or a permitted signed external provider callback through its **provider-specific** adapter.
2. Resolve trusted WordPress site and known Woo order using CRUD/HPOS-compatible APIs.
3. Validate external signature/provenance for HTTP callbacks, replay window, event ID uniqueness and known order mapping.
4. Persist a bounded `provider_events` record and verified `payment_attempts` evidence; no raw PAN/CVV, provider secrets or unneeded callback body.
5. Compare amount/currency/order/merchant state with accepted commercial quote and legal payment policy.
6. In a transaction, update authoritative order projection, post unique immutable ledger fact, audit and committed outbox. If provider state is uncertain, mark **pending verification**, enqueue reconciliation and communicate truthful status.
7. Only a separate guarded workflow commits or confirms inventory entitlement. If hold expired, open governed commercial remedy; never silently rebook.
8. Enqueue secondary notifications and reports after commit; failure to send email does not reverse the payment fact.

### 10.2 Duplicate, missing and out-of-order callbacks

Use provider-specific immutable event ID and payment-attempt/reference keys, protected by unique constraints and site scope. A `paid` callback can arrive before an `authorized` callback or after a refund event; processing order must follow verified authoritative state and version rather than raw receipt order. A transaction may be retried after deadlock, but must not double-post ledger. A provider API timeout is **unknown**, not success or failure. Quarantine amount mismatch, unknown order, wrong site, invalid signature or malformed references with redacted evidence and operator investigation.

### 10.3 Settlement/refund ambiguity

Document settlement versus authorization and capture semantics per payment provider. Do not conflate Woo `processing`, `completed`, `on-hold`, `refunded` and local `paid` without approved mapping. Full and partial refunds append new immutable ledger facts with explicit reason/source/reversal link, adjust entitlement under recorded contract policy, and emit notifications from reconciled state. If provider API is unavailable, display a pending, recoverable status to authorized users.

### 10.4 Accounting invariants

All stored monetary values are exact integer minor units with currency and precision, per D08 `DB-DES-05`; exchange-rate transformations must preserve source/provenance and be explicitly approved. The D08 ledger is append-only activity entries, not by itself a full double-entry accounting system. R3 credit/receivable or metered accrual requires an approved accounting model and exact-money rounding specification. Billing sources are reconciled qualifications/ledger, never raw engagement counters.

### 10.5 Payment recovery checklist

| Failure | Recovery | No-go behavior |
|---|---|---|
| Callback duplicated | Acknowledge duplicate after verified identity, reuse existing result | Second ledger posting |
| Callback before internal mapping | Quarantine and reconcile via known order references | Create unverified order by payload guess |
| Missing callback | Poll authoritative Woo/provider state with bounded backoff | Infer paid from checkout page |
| Provider API timeout | Pending-verification + scheduled reconciliation | Declare success or failure without evidence |
| Amount/currency mismatch | Stop fulfillment; finance incident | Accept partial mismatched evidence by default |
| Paid after hold expiry | Record payment, create remedy, no auto-rebook | Restore expired exclusive inventory |
| Rejection after settlement | Open documented revision/refund remedy | Retain funds with no fulfillment path |
| Worker crashes after posting | Unique posting key and inbox replay | Duplicate accounting effect |
| WooCommerce becomes unavailable | Safe disabled checkout; retained truthful facts | Change immutable ledger by UI inference |

## 11. Measurement collection, qualification and aggregation

### 11.1 Browser input to authoritative fact — `ASY-MET-001`

`POST /wp-json/adpilot/v1/measurement/events` (`MET-001` from D09) is a bounded **untrusted** input channel. The collector SHALL validate permitted event types, site decision references, opaque render-instance identity, timestamps, payload size, rate limits, permitted consent/purpose, environment, and replay window before accepting the batch to durable processing. A network `202` means received for processing, not "viewed", "billable" or "conversion verified". The public runtime uses bounded event buffers with a defined unload best-effort transport; loss is measured rather than concealed.

### 11.2 Event progression — `ASY-MET-002`

```text
untrusted browser signal
  -> transport-received batch
  -> schema/site/decision validation
  -> duplicate & bot/abuse screening
  -> measurement qualification / explicit rejection
  -> aggregation checkpoint
  -> hourly/daily read-model update
  -> [R2] privacy-gated heatmap bins
  -> [R3 ONLY] independent billable reconciliation
  -> [R3 ONLY] exact-money Commerce ledger posting
```

**Distinct counters:** slots configured, creative decisions, served, browser-rendered, measurable, viewable, qualified click, billable event, and ledger revenue. Do not increment 'viewed' from a configured slot or bill from a rendered placeholder. Editor preview, staging and synthetic load events are nonbillable (`BR-009`). Event qualification is a policy decision with versioned definition and evidence.

### 11.3 Accepted measurement envelope — `ASY-MET-003`

```json
{
  "schema_version": 1,
  "batch_id": "<opaque-batch-id>",
  "events": [
    {
      "event_id": "<opaque-event-id>",
      "event_type": "ad.viewability_sample",
      "decision_ref": "<server-issued-scoped-decision-ref>",
      "render_instance_ref": "<per-occurrence-render-ref>",
      "elapsed_visible_ms": 1250,
      "visible_ratio": 0.62,
      "observed_at": "2026-10-12T01:00:00Z"
    }
  ]
}
```

This is a **logical example** illustrating bounded signals; actual D09 endpoint schema and accepted event names are controlling. Do not assume this sample overrides existing wire contracts or makes a sample viewability signal MRC-certified. Duration/ration thresholds and dedup tolerance must be specified in the measurement document and tested across browser engines.

### 11.4 Deduplication scope — `ASY-MET-004`

`event_deduplication` has `(site_id,event_hash)` uniqueness with TTL. Derive a stable, keyed fingerprint from approved event type, server decision, render occurrence, event ID and known qualification window; never accept a client-chosen `event_hash` as financial truth. Insert the dedup identity with the accepted processing fact under an appropriate transaction. On repeated submission, return a safe duplicate outcome without incrementing read models. Retention must cover the effective transport retry window and any financial dispute evidence; do not delete protected accounting proof when raw events age out.

### 11.5 Deterministic aggregation — `ASY-MET-005`

Hourly and daily reporting grain is defined by D08 tables and index contracts: site, UTC bucket, campaign, creative/slot/layout revision and qualified metric dimension where present. Aggregator must enumerate bounded qualified source batches and maintain `aggregation_checkpoints` atomically with derived writes. Define whether each source batch is counted through **replace/rebuild** or **increment-once with checkpoint**; never mix strategies without preventing double count. When late events arrive after a bucket has been reported, update versioned aggregates, mark correction and expose freshness/coverage metadata to UIs.

**Recommended deterministic approach:** stage by `(site,bucket,source_batch_hash,metric_definition_version)` and update a bounded materialized projection. On recovery, reconstruct from still-retained qualified source, compare expected totals, then publish a new projection revision. Accounting evidence retention is independent.

### 11.6 Backpressure — `ASY-MET-006`

Bound: events per HTTP request, bytes per batch, concurrent processing workers, retry window, queue depth, allowed metric cardinality and raw data retention. Apply per-source/per-site quotas. Under pressure, preserve commercial truth and core WordPress availability before optional detailed pointer/heatmap samples. Do not silently synthesize missing measurement; expose partial coverage and reasons. Reject or sample nonessential high-frequency events according to approved privacy-aware policy. All thresholds must be measured on approved host/browser profiles and recorded in the performance specification.

### 11.7 R3 metered billing separation — `ASY-MET-007`

Metered usage must pass independent eligibility, fraud/invalid-traffic review, dedup, delivery-contract window validation, pricing snapshot, exact-money calculation and finance reconciliation. The consumer receives **settled usage facts**, not untrusted `ad.clicked` messages. Define the `EV-026` split before production and retain immutable posting keys even after raw browser events are purged. Metered billing cannot ship unless negative tests demonstrate no double charge across replay, backfill, and out-of-order corrections.

## 12. R2 heatmaps, privacy, optional R4 session replay

### 12.1 Aggregation by versioned geometry — `ASY-HEAT-001`

A full-page heatmap for hundreds of ad slots requires **layout version**, stable logical slot ID, per-occurrence render ID, device/viewport class, sanitized element anchor, normalized x/y and page geometry snapshot reference. Workers SHALL reject unmappable points, preserve geometry version and avoid silently superimposing data from incompatible layouts. Scroll reach and element exposure are independent from a pointer heatmap. The system does not infer actual eye gaze from cursor movement.

### 12.2 Consent and data minimization — `ASY-HEAT-002`

Nonessential behavioral samples must not be collected before the applicable consent/legal processing gate; prohibit capturing protected fields/DOM, PII-rich URLs and private page content. A denied/withdrawn purpose suppresses new collection synchronously; asynchronous deletion/rebuild follows in bounded privacy jobs. A historical consent record does not authorize future processing after policy changes. Aggregated heatmap reads require current privileged access and cohort suppression controls.

### 12.3 Privacy-preserving bins — `ASY-HEAT-003`

Use discretized/normalized bins and scoped counts rather than storing indefinite high-frequency pointer trails. Minimum cohort and low-volume suppression thresholds are governed privacy settings, not invented universal constants. Merge or compare only eligible compatible layout versions, time buckets and responsive classes. Add provenance and sample counts to render data. If insufficient or suppressed data, report "not enough eligible observations", not an invented heatmap.

### 12.4 Replay constraints — `ASY-HEAT-004`, R4

Session replay remains **off by default**, optionally implemented with verified GPL-compatible/MIT components (e.g., rrweb core after dependency audit). Recordings are strongly masked/excluded at collection time, sampled within purpose and retention constraints, stored in protected storage, and streamed only to users with `adpilot_view_replay` and approved purpose. Recheck entitlement for every replay request, never send a raw replay file to advertisers, and purge on expiry/withdrawal where applicable. Queue retention deletion cannot be postponed indefinitely by a broken worker; expose overdue deletion incidents.

### 12.5 Privacy-safe measurement repair

When policy changes invalidate historical samples, model explicit aggregate invalidation/rebuild with audit and versioned reporting. Do not use a lossy event purge to imply that an already issued financial invoice should be silently modified: finance adjustment, when legally required, follows the documented governed reversal/reconciliation workflow.

## 13. Notifications, exports and outbound webhooks

### 13.1 Notifications — `ASY-NOT-001`

Store an immutable notification intent referencing template key, locale, recipient scope, event or aggregate version and permitted projection. At send time, resolve **current** membership/site capabilities and privacy restrictions; do not include unauthorized invoice URLs or sensitive reviewer notes. Use approved WordPress mail adapter or configured provider with timeouts, bounded retries and accepted/delivered semantics separated. Notification failure must not change authoritative campaign or financial state. Rate-limit campaign reminders, maintain per-type dedup/cooldown and allow administrator diagnostics without exposing sensitive payloads.

### 13.2 Report exports — `ASY-EXP-001`

`REP-005/006` requests create permission-scoped export jobs containing: requesting trusted actor/site/org, query/schema/metric definition versions, filters, time interval, redaction policy, estimated row budget, expiration, state and correlation. Worker must check current entitlement **before generating**, bound resources, create private artifact with safe content headers/checksum and register `ReportExportReady` only on verified completion. `REP-008` download MUST reauthorize at request time; signed URLs alone are insufficient. Revoking access or expiry invalidates download. Purge job destroys expired artifact and records redacted audit proof. Avoid writable public `/uploads/` URLs for sensitive reports.

### 13.3 Outbound integration bridge — `ASY-WEB-001`

Follow D09 §11 exactly for approved destinations, signature bytes, `X-AdPilot-*` headers, key IDs, timestamp skew verification guidance, retries, SSRF DNS/IP/TLS protections and endpoint verification. An outbound webhook includes a **sanitized projection of committed fact**, not full domain event or analytics event stream. Stable event ID and unique delivery-attempt ID have different roles. An HTTP 2xx ACK means receiver accepted a request, not that receiver's business action completed. A timeout may have delivered successfully; retry with stable event identity so receiver can dedup.

### 13.4 Delivery states — `ASY-WEB-002`

`pending → attempting → accepted` or `pending|attempting → retry_wait → attempting`, finally `quarantined/disabled` under governance. Destination revocation, new permission policy, changed URL or stale secret makes queued attempts re-evaluate the subscription. Deactivation must not continue sending from old scheduled actions. Logs contain endpoint host, response class, redacted error and correlation only; no signed secret or recipient's full response body.

### 13.5 Webhook release conflict resolution

D09 `OPS-009..013` lists R1 public API routes for outgoing subscription administration; D08 marks `webhook_subscriptions` R4, and D07 `EV-029` R4 with optional R1 hooks. This specification **does not redefine R1**. The implementation MUST record one of: (A) promote a minimal compliant subscription schema and send-path to R1 through reviewed amendments to Documents 01/07/08, or (B) treat D09 routes as declared-but-gated with explicit `feature_unavailable` until R4. No production implementation may advertise a functional R1 webhook subscription without its necessary persisted schema, security validation and end-to-end tests. Internal WordPress actions/filters and WooCommerce-native hooks in R1 are independent of this decision.

## 14. Failure taxonomy, retry, compensation and quarantine

### 14.1 Error classes — `ASY-ERR-001`

| Class | Examples | Policy | Final state |
|---|---|---|---|
| `transient_transport` | HTTP timeout, temporary mail or storage failure | Exponential backoff + jitter, bounded retries | Retry then quarantine |
| `transient_database` | Deadlock, lock timeout, brief outage | Roll back, re-read, random backoff, bounded attempts | Retry then incident |
| `dependency_unavailable` | Woo/provider unavailable, cache/queue down | Preserve authoritative pending state; reconciliation | Pending or incident |
| `state_conflict` | Changed version, expired hold, paused campaign | No blind retry; fetch current state and deliberate replan | Safe no-op or manual remedy |
| `unauthorized` | Revoked org membership, wrong site | Fail closed; no older job-side grant | Cancel/quarantine |
| `privacy_blocked` | Consent revoked, prohibited raw sample | Stop processing, purge if required, audit | Suppressed/deleted |
| `invalid_schema` | Unknown event version, bad payload | Do not deserialize or execute | Quarantine |
| `invalid_provider_evidence` | Bad signature, wrong currency/order | Investigate, do not post paid | Quarantine |
| `capacity_conflict` | Overlap/exclusive lock failure | No oversell; return conflict/requote | Conflict/remedy |
| `permanent_configuration` | Removed template, rejected remote destination | Require explicit operator correction | Disabled/quarantine |
| `invariant_violation` | Missing ledger posting after verified settlement | High-severity alert, block unsafe continuation | Incident |

### 14.2 Retry policy contract — `ASY-ERR-002`

For retryable work use capped exponential backoff with *jitter*, classified deadlines, maximum attempt count, and distinct urgency per job. An illustrative schedule `base × 2^(attempt−1)` capped to `max_delay` describes the algorithm—not approved production defaults. A deterministic test clock and random-source injection are required. Explicit `Retry-After` may be honored within policy, without overriding contractual expiry. No job should continue attempting indefinitely without dead-letter visibility.

### 14.3 Queue poison message — `ASY-ERR-003`

Quarantined records retain minimum reason/status, payload schema/version, site, handler key, timestamps and correlation, with sensitive payload protected and retention-bounded. Operators can inspect redacted evidence and request approved, audited replay after fixing root cause. Allowlist which job types may be manually retried. Never expose "arbitrary background hook" execution or raw serialization/deserialization through a retry API.

### 14.4 Compensation and saga boundaries — `ASY-ERR-004`

- Money/payment facts are not rolled back by deleting ledger rows. Compensate with governed refund/adjustment entry.
- A paid but unfulfillable reservation opens a commercial remedy; do not resurrect expired capacity.
- A notification already sent cannot be unsent; follow-up correction notices are separate immutable intents.
- A customer-accepted sold-slot migration requires explicit recorded consent/approval and new layout binding, not an asynchronous inference.
- A failed optional heatmap job must not prevent normal ad rendering or purchase.
- An aborted export must delete partial private artifacts and report failure truthfully.

### 14.5 Cross-system consistency matrix — `ASY-ERR-005`

| Asynchronous input | Must re-query | Allowed effect | Must never do |
|---|---|---|---|
| `PaymentEvidenceAccepted` | Woo order, provider evidence, hold, internal order | Post verified ledger/remedy | Mark campaign approved |
| `CreativeVersionApproved` | Exact version/hash, current policy, entitlement | Invalidate eligibility cache | Auto-charge funds |
| `MembershipRevoked` | Current membership/site | Invalidate caches / revoke exports | Continue privileged job via old actor |
| `LayoutRevisionPublished` | Active pointer, paid contracts | Refresh placement plan | Rename sold slot identity silently |
| `CampaignPaused` | Stop version, state | Suppress creative and invalidate safe caches | Reactivate on old start job |
| `MeasurementBatchQualified` | Source checkpoint and definition version | Deterministic aggregate update | Direct financial debit |
| `HeatmapAggregateUpdated` | Current consent, ACL, geometry | Refresh privileged visualization | Expose visitor raw replay |
| `ReportExportReady` | Current export owner and artifact expiry | Send scoped notification | Publish protected file URL |
| `DataRetentionCompleted` | Deletion evidence/policy | Update compliance report | Reveal deleted content or suppress unresolved legal hold |

## 15. Scheduling time, operational quotas and performance budgets

### 15.1 Time model — `ASY-TIME-001`

- Store business timestamps as UTC `DATETIME(6)` (per D08), retain user-chosen IANA timezone for explanation/recurrence, and use `[start,end)` intervals.
- A daily recurrence is based on a time-zone/calendar rule, not naive `+86400` arithmetic across DST; normalize before persisting next UTC run.
- Compare due instants using a trusted database/application clock after bounded skew checks; record scheduled versus actual processing time.
- Aggregate reporting buckets use UTC boundaries; localized dashboards transform explicitly and preserve provenance.
- Expiration enforcement must also happen at synchronous purchase/delivery gates; relying solely on cron is unsafe.

### 15.2 Configurable quotas — `ASY-PERF-001`

| Quota | Why needed | Measurement/approval |
|---|---|---|
| `max_event_batch_bytes` | Prevent memory/HTTP abuse | Benchmark across hosting and browser baseline |
| `max_event_count_per_batch` | Bound ingest work | Validation error/partial acknowledgement model |
| `max_jobs_claimed_per_cycle` | Avoid PHP timeout | Queue throughput and worker time budget |
| `max_concurrent_jobs_per_site` | Avoid DB lock contention | Load and deadlock tests |
| `max_outbox_lease_seconds` | Crash recovery and long handler work | Fencing validation |
| `max_retry_attempts_by_class` | Prevent infinite poison retry | Fault-injection tests |
| `max_report_export_rows/bytes` | Prevent memory/storage exhaustion | Export performance benchmarks |
| `raw_event_retention_days` | Prevent unbounded storage | Privacy + accounting sign-off |
| `min_heatmap_cohort` | Suppress privacy-identifying sparse bins | Privacy threat assessment |
| `max_batch_processing_time` | Fit PHP runtime | Real host profiling |
| `max_queue_backlog_age` | Detect stalled operations | Business SLA approval |

These variable names are design proposals; approved values, hard safety ceilings, defaults, administrative edit rights and performance acceptance belong in performance/privacy configuration documents. Do not hard-code unvalidated global thresholds or promise arbitrary 500-ad creative loads will have constant cost.

### 15.3 Performance tests for high-density pages

Use fixtures of 4 header + 100 sidebar + 196 in-content + 200 footer **configured** slots. Compare measured queued events for 0, 35, 100, and 500 genuinely eligible viewport exposures; expected billable counters remain zero unless separately qualified under contract. Simulate rapid bottom scroll, mobile low-power, browser tab backgrounding, interrupted network, multi-tab views, consent denial and server overload. Measure decision batch count, transport batch size, accepted/rejected/dedup events, aggregate lag, SQL query count, queue age, browser memory and Core Web Vitals. Results must be reported with environment/hardware, percentiles and failure limits—not guessed in this specification.

## 16. Security, authorization and tenant boundaries

### 16.1 Execution principals — `ASY-SEC-001`

A background worker is a registered, narrowly scoped **service principal**, not an impersonated WordPress administrator. Each job resolves the trusted site, approved job type, caller provenance (where applicable), and current target resource. The worker enforces the operation's policy and audits sensitive actions. A previous actor's authority is not carried forward as an executable permission grant.

### 16.2 Multisite — `ASY-SEC-002`

- Every claim, inbox ack, event, export, dedup key, cache key and ledger posting is scoped to the correct site.
- `switch_to_blog()` does not grant access and must be paired with guaranteed restoration in `finally`.
- A network runner may iterate only approved target sites and instantiate fresh site-aware repositories after context change.
- A background job carrying a forged `siteId` is denied; verify scheduled provenance and table prefix via trusted resolver.
- An organizer operating in two advertiser organizations receives only the target organization's current grants, never a union of all memberships.
- R4 federation does not share advertiser customer or visitor data by default.

### 16.3 Payload confidentiality — `ASY-SEC-003`

Avoid raw provider bodies, credentials, personal data, replay chunks and DOM text in scheduler arguments, message headers, logs, monitoring metrics or webhook payloads. Apply fixed schemas, length limits, data classification, minimal allowlisted projections, at-rest protection for necessary secrets and sensitive artifacts, and redacted support exports. Correlation IDs must not contain emails or other personal identifiers.

### 16.4 Secure external webhooks — `ASY-SEC-004`

Follow D09 SSRF policy: verify HTTPS destination, DNS and effective resolved IP at connection time, reject private/special IP ranges and redirects unless revalidated, bounded timeouts, signed raw bytes, key rotation and permission recheck at send time. Requests signed with revoked subscription keys must not be sent on delayed retries. Provider inbound signatures follow provider documentation and cannot reuse AdPilot outbound HMAC as a substitute.

### 16.5 Eventual-consistency UI safety — `ASY-SEC-005`

AdPilot frontend must distinguish `paid`, `pending payment verification`, `under review`, `scheduled`, `active`, and `paused`. A "job accepted" response is not a successful settlement, approval or delivery. Dashboards need freshness timestamps; stale data warnings should provide usable refresh/retry actions. Revoke download permission immediately on membership removal, even if a prior job generated an export successfully.

## 17. Operational telemetry, monitoring and alerts

### 17.1 Metrics contract — `ASY-OBS-001`

| Metric (proposed) | Dimension/scope | Meaning |
|---|---|---|
| `adpilot_queue_due_total` | Site, group, priority | Actions eligible to run |
| `adpilot_queue_oldest_due_age_seconds` | Site, group | Time oldest job has been overdue |
| `adpilot_job_executions_total` | Type, outcome, site | Completion/failure events |
| `adpilot_job_runtime_seconds` | Type, site | Actual handler runtime histogram |
| `adpilot_outbox_pending_total` | Site, event family | Undispatched durable messages |
| `adpilot_outbox_oldest_pending_age_seconds` | Site | Commit-to-completion lag |
| `adpilot_outbox_claim_expired_total` | Site | Lease recovery frequency |
| `adpilot_inbox_duplicate_total` | Consumer, site | Safe repeat suppression |
| `adpilot_payment_pending_verification_total` | Site | Unresolved provider evidence |
| `adpilot_hold_expiration_drift_seconds` | Site | Scheduled versus observed expiry enforcement |
| `adpilot_campaign_activation_lag_seconds` | Site | Due-time versus actual approved transition |
| `adpilot_measurement_batch_rejected_total` | Site, reason | Invalid, nonconsented, duplicate signals |
| `adpilot_aggregate_freshness_seconds` | Site, metric family | Reporting lag |
| `adpilot_analytics_backpressure_total` | Site, reason | Samples rejected/dropped by approved policy |
| `adpilot_webhook_attempt_total` | Site, response class | Outbound attempt outcomes (feature-gated) |
| `adpilot_export_expired_unpurged_total` | Site | Private artifact purge backlog |
| `adpilot_privacy_purge_lag_seconds` | Site | Retention/erasure completion delay |
| `adpilot_deadletter_total` | Site, failure class | Records needing operator action |
| `adpilot_financial_reconciliation_discrepancy_total` | Site, reason | Potential correctness incident |

No unbounded dimensions for email addresses, visitors, exact URLs, high-cardinality event IDs or sensitive organization data. Logs use bounded correlation references; sensitive identifiers only in protected audit records.

### 17.2 Proposed alert classes — `ASY-OBS-002`

| Severity | Trigger | Response owner |
|---|---|---|
| P0 | Settled payment without unique ledger posting; exclusive reservation oversell | Finance + Database + Incident Lead |
| P0 | Stopped campaign serving beyond approved freshness guard | Ad Delivery + Security |
| P0 | Privacy forbidden raw collection or export access breach | Privacy + Security |
| P1 | Outbox backlog past approved commercial recovery deadline | Reliability/DevOps |
| P1 | Hold expiry or campaign activation overdue beyond contract SLA | AdOps + Reliability |
| P1 | Export artifacts persist beyond deletion policy | Privacy + DevOps |
| P2 | Elevated measurement rejection or dashboard lag | Analytics + Reliability |
| P2 | Outbound webhook repeated failures | Integrations + Support |
| P3 | Informational retry/dead letter requiring scheduled review | Owner module |

Actual alert thresholds and escalation windows require approved service objectives. Do not claim a zero-lag guarantee for ordinary email/reporting.

### 17.3 Admin operations dashboard — `ASY-OBS-003`

Expose queue group health, last executed cron/CLI run, scheduler adapter status, pending/failed/retried/quarantined counts, oldest due age, outbox lag, aggregate freshness, active worker ownership, redacted most recent error classes, WooCommerce reconciliation state, privacy purge health, and export storage. Drilldown requires `adpilot_view_diagnostics`; manual retry requires `adpilot_retry_jobs` and additional domain authorization when relevant. A retry button must not allow arbitrary class/method invocation.

### 17.4 Audit and trace — `ASY-OBS-004`

Carry correlation/causation ID through command, outbox, job, consumer, API, notification and webhook attempt. Record who requested high-risk retries, why, current policy/revision, source event, before/after status, independent approver if required and final outcome. Avoid duplicating sensitive source payloads in log and audit snapshots. Financial/audit retention outlives short-lived measurement where legally required.

## 18. Implementation file structure and PHP interfaces

### 18.1 Proposed physical organization (fits Document 06)

```text
src/
  Core/Contracts/{Clock,TransactionManager,OutboxPublisher}.php
  Infrastructure/
    Queue/{ActionSchedulerAdapter,JobRegistry,JobDispatcher,WorkerContext,QueueHealth}.php
    Persistence/{OutboxRepository,InboxRepository,IdempotencyRepository,LeaseRepository}.php
    Telemetry/{OperationalMetrics,RedactedLogger,CorrelationContext}.php
    WordPress/{SiteContextResolver,CronHealthAdapter}.php
  Modules/
    Reservations/Application/Jobs/{ExpireHolds,ReconcileCapacity}.php
    Campaigns/Application/Jobs/EvaluateDueCampaigns.php
    Commerce/Application/Jobs/{ReconcilePayments,RecoverProviderEvents,OpenRemedies}.php
    Measurement/Application/Jobs/{QualifyBatches,AggregateHourly,AggregateDaily}.php
    BehavioralAnalytics/Application/Jobs/{AggregateHeatmaps,EraseData}.php
    Reporting/Application/Jobs/{GenerateExport,ExpireExports}.php
    Integrations/Application/Jobs/{DispatchWebhook,RetryWebhook}.php
    Notifications/Application/Jobs/DispatchNotification.php
    Compliance/Application/Jobs/PurgeRetention.php
  PublicApi/
    Contracts/{CommittedEvent,JobDescriptor,JobResult,ConsumerHandler}.php
config/
  event-registry.php
  job-registry.php
  queue-policy.php
  feature-gates.php
  retention-policy.php
  service-principals.php

tests/
  Unit/{EventSchema,JobPolicy,RetryPolicy,Idempotency}/
  Integration/{Outbox,Inbox,Transactions,Payment,Multisite}/
  E2E/{CampaignScheduling,ExportPermissions,Heatmap,Delivery}/
  Performance/{EventBatching,QueueThroughput,LargePage}/
  Security/{QueueProvenance,WebhookSSRF,PrivacyDeletion}/
```

All concrete class/interface naming above is proposed; do not create empty placeholders to satisfy directory aesthetics. Published `IC-*`, repository ownership, authorization and state-machine contracts in D05–D09 remain controlling.

### 18.2 Publisher and handler interfaces — `ASY-INT-001`

```php
<?php
namespace AdPilot\PublicApi\Contracts;

interface CommittedEvent
{
    public function eventId(): string;
    public function eventType(): string;
    public function schemaVersion(): int;
    public function siteId(): int;
    public function aggregateVersion(): int;
    /** @return array<string, mixed> Strict allowlisted payload only. */
    public function sanitizedPayload(): array;
}

interface EventConsumer
{
    public function consumerKey(): string;
    public function supports(CommittedEvent $event): bool;
    public function handle(CommittedEvent $event, TrustedJobContext $context): void;
}

interface OutboxPublisher
{
    /** Must be invoked inside a required transaction boundary. */
    public function append(CommittedEvent $event): void;
}
```

`TrustedJobContext`, event registry and other dependent types are proposed types requiring concrete definitions and test fixtures. `OutboxPublisher.append()` should reject invocations outside an owned transaction for events whose business facts require atomic outbox capture.

### 18.3 Transport/scheduler/clock injection — `ASY-INT-002`

Inject `Clock`, deterministic `RandomSource` (for retry jitter), `JobSchedulerPort`, `TransactionManager`, `EventSchemaRegistry`, `SiteContextResolver`, `IdempotencyRepository`, `OutboxClaimRepository`, `AuthorizationPort`, `RedactedLogger` and `MetricsPort`. Production adapters may use WordPress APIs and Action Scheduler; domain handlers depend on ports, not global `wp_*` functions. `RequestFactory` for outbound HTTP must enforce SSRF/scheme/IP rules.

### 18.4 Anti-patterns forbidden by architecture — `ASY-INT-003`

- Triggering payment ledger posting from `wp_mail()` callback, WP-Cron hook alone or browser analytics receipt.
- Serializing unrestricted PHP objects and restoring them from a job payload.
- Passing `$_POST` directly to scheduler argument serialization.
- Doing 500 `as_schedule_single_action()` calls per page view for 500 ad slots.
- Treating scheduler status `complete` as authoritative campaign/order status.
- Catching and swallowing all payment exceptions with silent success.
- Storing session replay DOM data or keys in outbox/Action Scheduler logs.
- Sharing one queue configuration across sites without explicit site scoping.
- Letting a stale retry revive an expired hold or an unapproved creative.
- Treating a failed event handler as an excuse to rewrite committed business facts.

## 19. Operator runbooks and recovery instructions

### 19.1 Stalled queues — `ASY-RUN-001`

1. Confirm WordPress/Action Scheduler dependency health, site context, last cron/CLI run, recent PHP fatal/timeouts and queue oldest age.
2. Check database readiness, transaction support, schema migrations, outbox pending/leased/retry counts and storage capacity.
3. Determine whether a commercial transition is overdue; **do not** manually force a campaign to active without all eligibility gates.
4. Restart a safe registered worker/runner and trigger authoritative sweeps for due campaigns/holds/payments as documented.
5. Inspect quarantine reasons, event schema versions, scheduler args, and lease ownership. Reconcile from source truth before replay.
6. Record incident evidence, before/after lag, and release/hosting versions. Escalate any payment/exclusive inventory inconsistency.

### 19.2 Worker died after external send — `ASY-RUN-002`

For an HTTP webhook or email with ambiguous ACK, the system must assume receiver could have seen it; retry using stable event/notification identity with a **new attempt** and bounded policy, or query provider evidence if available. Do not roll back underlying business event. Annotate delivery state `uncertain` if confirmation is impossible.

### 19.3 Payment verification incident — `ASY-RUN-003`

Freeze unsafe automatic fulfillment, preserve provider and ledger references, check signatures/order mapping, reconcile Woo via CRUD and provider records, append governed adjustment/remedy if required, and record who approved remediation. Never manually edit ledger rows or create a fake successful callback to "fix" state.

### 19.4 Aggregate drift — `ASY-RUN-004`

Select source interval, confirm qualified raw evidence still exists, perform dry-run recomputation into isolated projection, compare by site/campaign/slot/layout version, approve diff, atomically switch read-model revision, and invalidate reporting caches. Keep an audit of omitted samples and metric-definition version. Financial postings remain unaffected until finance separately reconciles.

### 19.5 Data protection incident — `ASY-RUN-005`

Immediately stop forbidden collection/access at synchronous policy gates, revoke tokens/access, preserve appropriate forensic metadata without persisting raw sensitive payload, process purpose-limited deletion and aggregate corrections, verify privacy-policy completion, and follow legal incident/reporting procedures. Queue delay does not justify continued prohibited collection.

### 19.6 Deployment and rollback — `ASY-RUN-006`

Before upgrade: capture schema/event compatibility, queue backlog and consumer versions; apply migrations in documented order; pause only handlers incompatible with new schema; deploy code; run smoke checks and resume workers. If rollback is necessary, reject unsupported new schema-version messages rather than misparse them, restore from approved compatible backup or bridge reader, and never drop already committed financial facts. Do not assume database DDL transaction rollback.

## 20. Detailed acceptance and adversarial test catalog

The following tests supplement `AT-*`, `E2E-01..17`, `DB-AT-*`, `API` acceptance cases, and 68 D07 domain tests. **These are planned, not executed**. Each test uses deterministic clocks, controlled scheduler, two-site/two-organization fixtures where applicable, and source-of-truth database assertions. A passing HTTP result alone is insufficient for a financial or privacy proof.

| Test ID | Release | Required scenario / invariant | Test class | Evidence to collect |
|---|---|---|---|---|
| `ASY-AT-OUT-001` | R1 | Business fact and outbox insert in one transaction; rollback leaves neither | Unit+DB TX | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-002` | R1 | DB commit succeeds but schedule wake-up fails; periodic poller still dispatches | Fault injection | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-003` | R1 | Two workers attempt same pending message; at most one owns fenced lease | DB concurrency | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-004` | R1 | Worker crashes after lease; expired lease safely reclaimed | Crash recovery | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-005` | R1 | Stale worker tries to acknowledge after replacement claim; compare fails | DB concurrency | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-006` | R1 | Consumer handler errors after partial transaction; effect and inbox both rollback | DB TX | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-007` | R1 | Duplicate event delivery cannot apply twice to same consumer | Idempotency | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-008` | R1 | Two separate registered consumers each complete despite the other failing | Integration | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-009` | R1 | Unsupported future event schema quarantined with redacted evidence | Versioning | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-010` | R1 | Old event version is read through approved upcaster without changing committed payload | Compatibility | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-011` | R1 | Out-of-order aggregate events cannot reverse newer published state | Ordering | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OUT-012` | R1 | Missing required outbox event detected by reconciliation scan | Reconciliation | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-001` | R1 | Action Scheduler absent/not initialized; plugin boot remains safe with diagnostic | Boot | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-002` | R1 | WooCommerce disabled; non-commerce queue features stay available | Plugin compatibility | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-003` | R1 | Disabled feature prevents scheduling/executing its advanced job | Feature gate | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-004` | R1 | Forged job type/hook/arguments rejected before handler dispatch | Security | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-005` | R1 | Job from wrong WordPress site denied and site context restored | Multisite | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-006` | R1 | Job due in past after missed cron is recovered by authoritative sweep | Scheduler outage | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-007` | R1 | Job marked complete but domain transition unsuccessful; health alert raised | State health | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-008` | R1 | Recurring job run-key separates different windows and dedups retries | Idempotency | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-009` | R1 | Long-running job respects budget/limits and resumes with checkpoint | Performance | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-010` | R1 | Crash during job does not leave incomplete privileged state untracked | Recovery | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-011` | R1 | Stale queued actor permissions denied after membership revocation | Authorization | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-JOB-012` | R1 | Manual retry disallows arbitrary hook execution and audits operator | Security | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-001` | R1 | Two concurrent exclusive slot holds: exactly one gets capacity | Concurrent booking | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-002` | R1 | Hold expiry race with valid checkout is resolved under lock ordering | Concurrent booking | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-003` | R1 | Late payment cannot revive expired hold or double-sell slot | Finance | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-004` | R1 | Duplicate Woo event yields one payment posting and one source event | Finance | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-005` | R1 | Wrong site/order/currency callback quarantined without posting | Security+finance | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-006` | R1 | Provider timeout leaves pending-verification rather than paid guess | Recovery UX | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-007` | R1 | Paid campaign awaiting creative approval never serves | Eligibility | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-008` | R1 | Rejected paid creative opens documented remedy and notification | Finance+moderation | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-009` | R1 | Older due activation job cannot undo newer emergency pause | Delivery safety | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-010` | R1 | Approved creative version changes destination; pending version cannot serve | Moderation | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-011` | R1 | Published sold-slot layout change requires audited resolution | Inventory | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-012` | R1 | Partial refund posts new auditable entry and reevaluates entitlement | Ledger | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-013` | R1 | Recurring activation calculates UTC due with DST ambiguity policy | Time | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-COM-014` | R1 | Read-only hosting/damaged InnoDB disables unsafe exclusive checkout | Environment | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-001` | R1 | 500 configured slots but 35 viewport exposures are not counted as 500 views | Metric semantics | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-002` | R1 | Identical event sent twice increments qualified aggregate once | Dedup | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-003` | R1 | Malformed oversized browser batch rejected within bounded resources | Security | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-004` | R1 | Synthetic/editor/staging impressions remain nonbillable | Business rule | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-005` | R1 | Event from expired/foreign opaque decision token rejected | Trust boundary | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-006` | R1 | Unknown client event schema not treated as a valid billable event | Schema | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-007` | R1 | Out-of-order batch delivery reconciles without duplicate hourly counts | Aggregation | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-008` | R1 | Late verified event updates aggregate revision and freshness label | Aggregation | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-009` | R1 | Backfill replay does not double-increment bucket | Rebuild | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-010` | R1 | Raw event purge preserves qualified finance evidence when retention requires it | Retention | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-011` | R1 | Overloaded collector reports partial coverage, not fabricated impressions | Backpressure | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-012` | R3 | R3 invalid-click correction posts governed adjustment, not direct browser charge | Metered finance | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-MET-013` | R3 | R3 billable source replay cannot create second exact-money entry | Metered finance | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-HEAT-001` | R1 | R2 heatmap job not scheduled when feature disabled in R1 | Feature gate | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-HEAT-002` | R2 | R2 click sample maps to exact slot/render/layout version and normalized position | Geometry | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-HEAT-003` | R2 | R2 changed layout renders distinct heatmap data instead of silent overlay | Geometry | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-HEAT-004` | R2 | R2 low-cohort heatmap aggregate hidden from unauthorized/small cohort reads | Privacy | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-HEAT-005` | R2 | R2 consent denied suppresses sample collection and processing | Privacy | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-HEAT-006` | R2 | R2 withdrawal disables current collection before deletion job runs | Privacy | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-HEAT-007` | R2 | R2 interrupted privacy purge resumes and produces auditable completion | Retention | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-HEAT-008` | R4 | R4 replay job suppressed until enabled and approved purpose | Replay | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-HEAT-009` | R4 | R4 replay retention purges protected artifact without public exposure | Privacy | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-001` | R1 | Membership revoked before email dispatch; recipient scope rechecked | Tenant safety | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-002` | R1 | Email timeout marked unknown/retry, not proof user received notification | Delivery truth | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-003` | R1 | Report requested by permitted user, access revoked before generation; export blocked | Authorization | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-004` | R1 | Download time ACL revoked after export ready; private stream denied | Authorization | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-005` | R1 | Failed export cleans up partial artifact and shows actionable error | Recovery UX | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-006` | R1 | Expired export removed from protected storage with audit | Privacy | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-007` | R4* | Webhook private-IP literal and DNS rebinding rejected | SSRF | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-008` | R4* | Webhook payload modified after signing fails receiver verification | Signing | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-009` | R4* | Webhook timeout retried with new attempt ID and same event ID | Idempotency | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-NOT-010` | R4* | Revoked subscription key prevents queued retry | Security | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-001` | R1 | Older plugin version quarantines new event major version on rollback | Upgrade | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-002` | R1 | Multisite worker never processes another site collection under same ID | Isolation | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-003` | R1 | Outbox logs omit card credentials, DOM content and personal identifiers | Privacy | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-004` | R1 | Dead-letter replay requires operator grant and records reason | Authorization | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-005` | R1 | Application metrics have bounded labels and no visitor identity | Telemetry | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-006` | R1 | Stale cache does not override current delivery stop version | Safety | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-007` | R1 | System cron outage reported with actual queue and reconciliation lag | Observability | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-008` | R1 | DB migration and worker deployment order validated with pending messages | Deployment | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-009` | R2 | Backpressure preserves checkout and suppresses optional pointer samples first | Load | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-010` | R2 | Privacy purge honored across site scopes and legal-hold boundaries | Compliance | State diffs, expected vs actual rows, logs/trace, assertion result |
| `ASY-AT-OPS-011` | R4* | Commercial state not corrupted when optional webhook provider offline | Fault isolation | State diffs, expected vs actual rows, logs/trace, assertion result |

### 20.1 Test harness and tooling

- PHPUnit integration fixtures on supported InnoDB MySQL/MariaDB versions and WordPress versions; transaction concurrency requires **separate DB connections/processes**.
- Action Scheduler integration tests with injected clock/runner, process interruption and resumable jobs; preserve real behavior of scheduled job storage.
- Playwright for UI status/freshness/retry states and full-page 500-slot event batching (do not pretend Playwright simulates 500 real visitors).
- Load testing with bounded event fixtures, synthetic flag, consent variations, low-resource mobiles and real supported hosting profiles.
- Security fixture matrix: unauthorized org/site, wrong signature, forged service identity, private DNS host, stale membership, replayed callback.
- Finance oracle: immutable order, provider event, payment attempt, ledger entry, remedy and outbox cross-check.
- Privacy oracle: data inventory, consent gate, deletion evidence, protected export access and no raw sensitive fields.

### 20.2 Required per-test evidence format

`test_id`, requirement/rule references, fixture hash, supported runtime versions, database engine/settings, queue adapter version, reproducible command, start state, injected fault, expected state, observed state, ledger/outbox/inbox counts, duration, logs/artifact paths, pass/fail and owner. Never mark a test passed because the generator successfully wrote a Markdown scenario.

### 20.3 Definition of Done for async features

An async feature is complete only if it has: typed event/job registry entry; versioned payload; producer invariant and outbox capture; registered consumer; idempotency; current authorization/purpose check; documented concurrency order; bounded retry/quarantine; user-visible recovery; retention; monitored metrics; error messages; positive+negative automated tests; migration if needed; release feature gate; and documentation/user guide update. For financial jobs, Finance Lead sign-off is mandatory. For heatmap/replay jobs, Privacy Lead sign-off is mandatory.

## 21. GPT-6 High implementation plan and work-package handoff

Implement small cohesive vertical slices in dependency order. GPT-6 High should first load the latest approved source documents and list conflicting assumptions; it must not silently infer missing DB fields, gateway callbacks, queue guarantees, retention periods or running test evidence. The following packages define accountability and objective acceptance.

| Work package | Owner roles | Output artifact | Acceptance gate |
|---|---|---|---|
| `ASY-WP-01` — Define schemas & registry | Platform + Architecture | Event registry, schema validators, codegen DTOs and version compatibility | Every EV 001..030 typed and feature-gated |
| `ASY-WP-02` — Implement scheduler adapter | WordPress + Reliability | Action Scheduler integration, bootstrap without Woo, group config and health | Plugin boots without Woo; missed runner diagnostics |
| `ASY-WP-03` — Implement atomic outbox port | Database + Backend | Outbox repository and transactional command boundary | Transaction rollback/commit fault tests |
| `ASY-WP-04` — Approve fenced claim extension | Database + Architecture | D08 change proposal ASY-DB-001/002 and production lease implementation | Two workers + crash fencing verified |
| `ASY-WP-05` — Build consumer inbox & dispatcher | Backend + Reliability | Consumer registry, per-handler inbox ack, quarantine | Duplicate/out-of-order handler tests |
| `ASY-WP-06` — Build job security context | Security + Platform | Scoped service principals, site resolver, permission recheck | Cross-org/site negative tests |
| `ASY-WP-07` — Commercial hold sweep | Reservations + Database | Expiry, capacity reconciliation, scheduled/sweep recovery | Checkout-expiry race assertions |
| `ASY-WP-08` — Campaign due scheduling | Campaign + Delivery | Due-state queries and immutable stop version enforcement | Paused and unapproved cannot serve |
| `ASY-WP-09` — Woo reconciliation workers | Commerce + Woo | Provider event projection, Woo CRUD polling, remedy handling | No doubled ledger, timeout recovery |
| `ASY-WP-10` — Financial diagnostics | Finance + Reporting | Reconciliation discrepancy alerts and audited operator repair | Source-to-ledger balancing evidence |
| `ASY-WP-11` — Browser measurement ingress | Measurement + Frontend | Batch schema, bounded buffers, dedup, abuse controls | 500-slot fixtures correct metrics |
| `ASY-WP-12` — Hourly/daily aggregate pipeline | Measurement + Database | Checkpoints, late event correction, rebuild flow | Replay no double count |
| `ASY-WP-13` — Notification delivery | Notifications + UX | Template keys, recipient scope recheck, retries | Revoked org gets no mail leak |
| `ASY-WP-14` — Protected export pipeline | Reporting + Security | Scoped generation, private storage, expiry and download gate | Revoked access denied |
| `ASY-WP-15` — Retention and purge jobs | Privacy + Database | Bounded deletion, legal holds, audit completion | Retention and recovery test |
| `ASY-WP-16` — R2 heatmap pipeline | Behavioral + Privacy | Geometry mapping, bins, suppression, consent gating | Layout/viewport checks |
| `ASY-WP-17` — R3 usage billing pipeline | Measurement + Finance | Billable evidence ledger separation, EV-026 decision | Double-charge replay tests |
| `ASY-WP-18` — R4 external webhook bridge | Integrations + Security | Approved release scheme, signing, SSRF, retries | Subscription entitlement and DNS cases |
| `ASY-WP-19` — Observability/admin health | DevOps + UX | Queue health, dashboards, alerts, redacted tracing | Runbook incident drill |
| `ASY-WP-20` — Performance and rollout | QA + DevOps | 500-slot queue tests, migrations, chaos/rollback CI | Documented benchmarks and release sign-off |

### 21.1 Task execution template for each package

```text
PACKAGE: ASY-WP-XX
SOURCE DOCUMENTS: ADP-DOC-001..010 (approved revisions)
REQUIREMENT IDS: FR-... / BR-... / NFR-... / EV-... / IC-... / ASY-AT-...
RELEASE GATE: R1|R2|R3|R4 with feature flags
OWNED MODULE(S): ...
PRECONDITIONS: migrations/version/capabilities/adapter availability
FILES TO MODIFY: explicit bounded set; no speculative unrelated refactor
PUBLIC CONTRACT CHANGE: yes/no; migration/ADR impact
INVARIANTS: financial, tenant, privacy, schedule, delivery, idempotency
UI STATES: pending, retrying, stale, degraded, blocked, corrected
TEST PLAN: unit + DB + integration + E2E + fault injection as applicable
COMMANDS EXECUTED: list real commands and results only
EVIDENCE: assertions and sanitized logs, benchmark environment, diffs
SECURITY REVIEW: token/site/tenant checks and secrets not exposed
OPEN DECISIONS: do not bypass release blockers
OUTPUT: commit-ready code, tests, migration, docs, status and limitations
```

### 21.2 Dependency order

1. Event DTOs and schema registry, trustworthy Clock/TransactionManager, then per-site outbox and audit invariants.
2. Action Scheduler bootstrap and diagnostic recovery, then consumer inbox and fenced claim/dispatched state.
3. Identity/site-scoped worker context, then critical reservation/campaign/payment jobs.
4. Public decision/measurement ingress and deterministic aggregates, then reporting/exports.
5. Privacy/retention and operators' incident tooling integrated from the first eligible release.
6. R2 heatmaps, R3 metered billing, R4 external subscription/replay/network features only after prerequisite release gates and ADR decisions.

### 21.3 Rule against fabricated implementation claims

Generated PHP or tests are not proof of production correctness. Require actual command output, real DB concurrency tests and documented environment; if a particular source document cannot be accessed, declare the gap instead of inventing its schema. A task may be marked **specified**, **implemented**, **tested**, **reviewed** or **released** only with evidence for that status.

## 22. Business-rule and invariant traceability

`BR-*` rules are authoritative policies **enforced by synchronous domain services**, not delegated wholesale to background jobs. The job links below identify the asynchronous follow-up or reconciliation path. Source acceptance IDs retain their exact meaning from D02.

| Baseline rule | Exact governing business statement | Async follow-up (if any) | Existing test |
|---|---|---|---|
| `BR-001` | Inventory sale is permitted only for an active, publishable placement with sufficient eligible capacity. | `ASY-J-003/004` | `AT-BR-001` |
| `BR-002` | A temporary hold expires after its configured TTL; extension requires an explicit transaction. | `ASY-J-003` | `AT-BR-002` |
| `BR-003` | A successful online payment does not bypass creative moderation. | `ASY-J-005/007` | `AT-BR-003` |
| `BR-004` | If a paid campaign is rejected, revision/refund/cancellation options follow recorded policy; never silently keep funds without an enforceable fulfillment path. | `ASY-J-009/012` | `AT-BR-004` |
| `BR-005` | An exclusive reservation cannot be double sold over overlapping contract time. | `ASY-J-003/004` | `AT-BR-005` |
| `BR-006` | Changes to a slot's commercial meaning require impact analysis and resolution of affected contracts. | `ASY-J-025` | `AT-BR-006` |
| `BR-007` | A rejected or unapproved creative cannot serve; approved prior versions may serve only if contract and policy permit. | `ASY-J-005/006` | `AT-BR-007` |
| `BR-008` | Delivery eligibility is recomputed against current authoritative campaign state, not based solely on a stale cached creative. | `ASY-J-006/025` | `AT-BR-008` |
| `BR-009` | Ad-preview, administrator-editing and synthetic load-test events are excluded from revenue-bearing analytics. | `ASY-J-016/014` | `AT-BR-009` |
| `BR-010` | Advertising density policies and ad-network restrictions are configurable; default warnings should discourage excessive clutter and inadvertent clicks. | `ASY-J-025` | `AT-BR-010` |
| `BR-011` | Advertiser reports show validated aggregate outcomes, not visitor-level session content. | `ASY-J-014/015` | `AT-BR-011` |
| `BR-012` | Raw events have finite retention and are not a permanent financial source of truth; reconciled financial entries are independently retained under applicable policy. | `ASY-J-020/021` | `AT-BR-012` |
| `BR-013` | Conversion and performance claims distinguish observed facts, attribution models, estimates and projections. | `ASY-J-014/032` | `AT-BR-013` |
| `BR-014` | Currency arithmetic uses precise decimal/minor-unit representations, never floating point for ledger authority. | `ASY-J-010/032` | `AT-BR-014` |
| `BR-015` | Timestamps are stored in UTC; contract terms display user-selected time zone and daylight-saving behavior explicitly. | `ASY-J-005` | `AT-BR-015` |
| `BR-016` | Accessibility and mobile usability may constrain a particular layout even when there is ample logical inventory capacity. | `ASY-J-005/025` | `AT-BR-016` |
| `BR-017` | Any third-party tracking, behavioral replay, or remote conversion adapter requires disclosure, consent gating where appropriate, and documented data flows. | `ASY-J-030/026` | `AT-BR-017` |
| `BR-018` | Manual administrator overrides of paid placement allocation require reason, authorization and audit logging. | `ASY-J-004/025` | `AT-BR-018` |

### 22.1 Synchronous gates that may not be delegated to queued actions

- **`INV-01..10`** product invariants from D01 persist regardless of scheduler health.
- Full mandatory capability and object-policy checks on every protected mutation/read are synchronous.
- Exclusive reservations require transactional capacity locking; a future expiry task does not replace a current availability test.
- Settled payment and exact ledger facts require provider evidence and durable unique postings before a financial success projection.
- Current campaign/creative/delivery stop gates are enforced during ad decision, independent of stale queued invalidations.
- Consent gate prevents new prohibited data capture before any async erase task finishes.
- Layout publication affecting sold contracts requires synchronous conflict review and audited resolution.

### 22.2 State-machine and workflow links

For each `WF-01..18`, orchestrating commands publish only its valid `EV-*` transition events. Independent state machines (quote/hold/order/payment/creative/campaign/delivery/layout/remedy/export/consent/jobs) continue to be owned by D04; this document does not create new legal state transitions by declaring queue tasks. `UJ-01..28` UI journeys display asynchronous status, freshness, deadline and recovery using documented D03 `UI-*` contracts.

## 23. Complete functional requirement traceability (`129/129`)

The matrix below includes **every** functional requirement ID/release and its pre-existing `AT-*` test. Async linkage is intentionally **module-level**, because some FRs are synchronous business/UI features that require only async notification or operational follow-up; a job mapping does not transfer ownership of their core acceptance. Release labels are copied without flattening mixed `R2/R3` or `R2/R4` boundaries.

| Requirement | Source release | Owner group | Related async jobs | Required async boundary | Source test |
|---|---|---|---|---|---|
| `FR-INV-001` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-001` |
| `FR-INV-002` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-002` |
| `FR-INV-003` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-003` |
| `FR-INV-004` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-004` |
| `FR-INV-005` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-005` |
| `FR-INV-006` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-006` |
| `FR-INV-007` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-007` |
| `FR-INV-008` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-008` |
| `FR-INV-009` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-009` |
| `FR-INV-010` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-010` |
| `FR-INV-011` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-011` |
| `FR-INV-012` | `R2` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-012` |
| `FR-INV-013` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-013` |
| `FR-INV-014` | `R1` | Inventory/Layout | `ASY-J-004/025` | `ASY-COM-005` | `AT-INV-014` |
| `FR-INT-001` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-001` |
| `FR-INT-002` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-002` |
| `FR-INT-003` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-003` |
| `FR-INT-004` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-004` |
| `FR-INT-005` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-005` |
| `FR-INT-006` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-006` |
| `FR-INT-007` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-007` |
| `FR-INT-008` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-008` |
| `FR-INT-009` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-009` |
| `FR-INT-010` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-010` |
| `FR-INT-011` | `R2` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-011` |
| `FR-INT-012` | `R1` | Integration/Gutenberg/Theme | `ASY-J-025/026/027` | `ASY-SEC-002; ASY-WEB-001` | `AT-INT-012` |
| `FR-MKT-001` | `R1` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-001` |
| `FR-MKT-002` | `R1` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-002` |
| `FR-MKT-003` | `R1` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-003` |
| `FR-MKT-004` | `R1` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-004` |
| `FR-MKT-005` | `R1` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-005` |
| `FR-MKT-006` | `R1` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-006` |
| `FR-MKT-007` | `R1` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-007` |
| `FR-MKT-008` | `R1` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-008` |
| `FR-MKT-009` | `R1` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-009` |
| `FR-MKT-010` | `R2` | Advertisers/Storefront | `ASY-J-012/022` | `ASY-NOT-001` | `AT-MKT-010` |
| `FR-CAM-001` | `R1` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-001` |
| `FR-CAM-002` | `R1` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-002` |
| `FR-CAM-003` | `R1` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-003` |
| `FR-CAM-004` | `R1` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-004` |
| `FR-CAM-005` | `R1` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-005` |
| `FR-CAM-006` | `R1` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-006` |
| `FR-CAM-007` | `R1` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-007` |
| `FR-CAM-008` | `R1` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-008` |
| `FR-CAM-009` | `R2` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-009` |
| `FR-CAM-010` | `R1` | Campaigns/Creatives | `ASY-J-005/006/009` | `ASY-COM-003/004` | `AT-CAM-010` |
| `FR-COM-001` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-001` |
| `FR-COM-002` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-002` |
| `FR-COM-003` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-003` |
| `FR-COM-004` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-004` |
| `FR-COM-005` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-005` |
| `FR-COM-006` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-006` |
| `FR-COM-007` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-007` |
| `FR-COM-008` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-008` |
| `FR-COM-009` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-009` |
| `FR-COM-010` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-010` |
| `FR-COM-011` | `R3` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-011` |
| `FR-COM-012` | `R4` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-012` |
| `FR-COM-013` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-013` |
| `FR-COM-014` | `R1` | Commerce/Reservations | `ASY-J-003/007/008/009/010/011` | `ASY-COM-001/002; ASY-PAY-001` | `AT-COM-014` |
| `FR-DEL-001` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-001` |
| `FR-DEL-002` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-002` |
| `FR-DEL-003` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-003` |
| `FR-DEL-004` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-004` |
| `FR-DEL-005` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-005` |
| `FR-DEL-006` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-006` |
| `FR-DEL-007` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-007` |
| `FR-DEL-008` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-008` |
| `FR-DEL-009` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-009` |
| `FR-DEL-010` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-010` |
| `FR-DEL-011` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-011` |
| `FR-DEL-012` | `R2/R3` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-012` |
| `FR-DEL-013` | `R1` | Delivery | `ASY-J-006/025` | `ASY-COM-004; ASY-MET-001` | `AT-DEL-013` |
| `FR-MET-001` | `R1` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-001` |
| `FR-MET-002` | `R1` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-002` |
| `FR-MET-003` | `R1` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-003` |
| `FR-MET-004` | `R1` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-004` |
| `FR-MET-005` | `R1` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-005` |
| `FR-MET-006` | `R1` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-006` |
| `FR-MET-007` | `R2` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-007` |
| `FR-MET-008` | `R2` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-008` |
| `FR-MET-009` | `R3` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-009` |
| `FR-MET-010` | `R3` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-010` |
| `FR-MET-011` | `R3` | Measurement | `ASY-J-014/015/016/017` | `ASY-MET-001..007` | `AT-MET-011` |
| `FR-HEAT-001` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-001` |
| `FR-HEAT-002` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-002` |
| `FR-HEAT-003` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-003` |
| `FR-HEAT-004` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-004` |
| `FR-HEAT-005` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-005` |
| `FR-HEAT-006` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-006` |
| `FR-HEAT-007` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-007` |
| `FR-HEAT-008` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-008` |
| `FR-HEAT-009` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-009` |
| `FR-HEAT-010` | `R4` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-010` |
| `FR-HEAT-011` | `R2` | BehavioralAnalytics | `ASY-J-028/029/030/035` | `ASY-HEAT-001..004` | `AT-HEAT-011` |
| `FR-REP-001` | `R1` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-001` |
| `FR-REP-002` | `R1` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-002` |
| `FR-REP-003` | `R1` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-003` |
| `FR-REP-004` | `R1` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-004` |
| `FR-REP-005` | `R2` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-005` |
| `FR-REP-006` | `R3` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-006` |
| `FR-REP-007` | `R3` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-007` |
| `FR-REP-008` | `R2` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-008` |
| `FR-REP-009` | `R2` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-009` |
| `FR-REP-010` | `R1` | Reporting | `ASY-J-014/015/018/019` | `ASY-EXP-001; ASY-OBS-001` | `AT-REP-010` |
| `FR-UX-001` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-001` |
| `FR-UX-002` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-002` |
| `FR-UX-003` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-003` |
| `FR-UX-004` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-004` |
| `FR-UX-005` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-005` |
| `FR-UX-006` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-006` |
| `FR-UX-007` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-007` |
| `FR-UX-008` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-008` |
| `FR-UX-009` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-009` |
| `FR-UX-010` | `R2/R4` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-010` |
| `FR-UX-011` | `R1` | UX/Frontend | `ASY-J-012/018/023` | `ASY-SEC-005; ASY-OBS-003` | `AT-UX-011` |
| `FR-OPS-001` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-001` |
| `FR-OPS-002` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-002` |
| `FR-OPS-003` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-003` |
| `FR-OPS-004` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-004` |
| `FR-OPS-005` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-005` |
| `FR-OPS-006` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-006` |
| `FR-OPS-007` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-007` |
| `FR-OPS-008` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-008` |
| `FR-OPS-009` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-009` |
| `FR-OPS-010` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-010` |
| `FR-OPS-011` | `R4` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-011` |
| `FR-OPS-012` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-012` |
| `FR-OPS-013` | `R1` | Platform/Compliance | `ASY-J-001/002/020/021/023/024` | `ASY-OUT-001; ASY-SEC-001` | `AT-OPS-013` |

### 23.1 Nonfunctional requirements — individual checks

| ID | Subject | Async-specific evidence |
|---|---|---|
| `NFR-001` | Architecture | Confirm worker/queue adapters do not leak into domain dependencies. |
| `NFR-002` | Maintainability | Require typed event schemas, reviewed dependencies and testable ports. |
| `NFR-003` | Rendering | A 500-slot view must not spawn per-slot background PHP jobs. |
| `NFR-004` | Performance | Benchmark queue/aggregation lag and batching on declared environments. |
| `NFR-005` | Reliability | Crash/retry tests must prove safe scheduler, payments and delivery fallback. |
| `NFR-006` | Financial correctness | Reconcile provider/ledger/capacity with unique facts and precise money. |
| `NFR-007` | Security | Harden job principals, retries, third-party callbacks and creative/tracking data. |
| `NFR-008` | Privacy | Consent-gated collector, retention sweeps and private export/replay storage. |
| `NFR-009` | Accessibility | Provide accessible pending/failure/retry statuses and heatmap data alternative. |
| `NFR-010` | Compatibility | Test jobs across classic/block themes, Woo HPOS and supported DB engines. |
| `NFR-011` | Internationalization | Localize user notifications, UTC due states and time-zone details. |
| `NFR-012` | Observability | Expose redacted queue/outbox dashboards, alerts and finance discrepancies. |
| `NFR-013` | Extensibility | Version interfaces, events, hooks and stable consumer-key contracts. |
| `NFR-014` | Licensing | Audit queue and analytics dependencies with license notices/SBOM. |
| `NFR-015` | Upgrade safety | Test queued-message compatibility on migration, rollback and deactivation. |
| `NFR-016` | Usability | Validate understandable state, progress, error recovery and accessible notices. |

### 23.2 First release acceptance criteria — individual gates

R1 `AC-*` criteria are defined in the PRD; these entries identify required **async evidence** in addition to the source acceptance tests. They do not replace product-level approval.

| Source ID | Canonical PRD acceptance requirement | Required async evidence |
|---|---|---|
| `AC-001` | Publisher creates and publishes a 500-slot page consisting of 4 header, 100 sidebar, 196 in-content and 200 footer logical slots. Layout Studio remains usable; publishing and responsive preview succeed. | No per-slot queue fan-out; layout publication outbox correct. |
| `AC-002` | One `adpilot/ad-zone` block can reference a 200-slot footer container without storing 200 Gutenberg blocks. | Dynamic zone contains compact references with no per-slot jobs. |
| `AC-003` | Four supported integration paths (Gutenberg, PHP function, shortcode, and supported hook binding) render through one policy-aware engine with correct per-occurrence identifiers. | Shared render engine suppresses duplicate event accounting. |
| `AC-004` | Public page initially loads bounded creatives near viewport and does not miscount 500 configured slots as impressions. | Batch collection counts viewport-exposed events, not all configured slots. |
| `AC-005` | Advertiser can discover an available slot, obtain a quote, reserve, pay through configured WooCommerce, upload creative, request review and observe activation when approved. | Checkout, hold, provider verification, moderation and activation recover safely. |
| `AC-006` | Two concurrent buyers cannot successfully reserve the same exclusive inventory over overlapping time windows. | Concurrent lock/expiry jobs never oversell exclusive inventory. |
| `AC-007` | Duplicate payment callbacks and campaign activation retries do not double-allocate, double-invoice or double-activate. | Payment/activation job replay is idempotent and audited. |
| `AC-008` | Creative changes requiring reapproval cannot bypass review and cannot serve in an unapproved state. | Creative reapproval and delivery stop checks defeat stale queued actions. |
| `AC-009` | Advertiser A cannot access advertiser B's campaigns, invoices, raw behavioral data or privileged reports through UI or direct API calls. | All queue/export operations enforce fresh tenant access. |
| `AC-010` | Classic theme, block theme, common caching setup and supported WooCommerce HPOS mode pass the declared compatibility suite. | Test queue integration alongside supported theme, cache and Woo HPOS modes. |
| `AC-011` | Broken analytics endpoint does not crash the public site, block reading content or silently produce fictitious billable events. | Measurement failure yields truthful partial coverage and no fake billing. |
| `AC-012` | Privacy-sensitive pages/inputs are excluded from collection; denied consent prevents prohibited collection. | Collection gate applies before any asynchronous privacy purge. |
| `AC-013` | All primary application workflows pass agreed manual accessibility and usability gates. | Task feedback, stale status, errors and retries are accessible. |
| `AC-014` | Installer works without WooCommerce for nonpayment functions and provides an accurate diagnostic for unavailable online checkout. | Core scheduler and noncommerce functions boot without Woo. |
| `AC-015` | Release contains reproducible build, automated tests, license notices, migrations and administrator/developer documentation. | Release validates jobs/migrations, license notices, tests and runbooks. |

### 23.3 Auxiliary registry coverage

- **Domain aggregates:** `DM-001..DM-028` retain their authoritative repositories; background processing never owns their mutable truth merely by running a job.
- **Existing events:** `EV-001..EV-030` mapped in §4 without renaming.
- **Inter-module contracts:** `IC-001..IC-024` remain in D06 and are respected by worker consumers; key ones include `IC-014` measurement, `IC-015` reporting, `IC-018` export, `IC-020` job scheduling, `IC-021` external integration, `IC-023` diagnostic and `IC-024` feature availability (verify exact names in D06 before coding).
- **API routes:** `MET-001`, `MET-002`, `DEL-001..003`, `REP-005..008`, `HEAT-001..007`, `OPS-005..006`, `OPS-009..013` retain their IDs and release policies. No new public HTTP route is implicitly approved by this document.
- **Database:** `outbox_messages`, `inbox_consumptions`, `idempotency_records`, `event_batches`, `event_deduplication`, `delivery_events`, `measurement_qualifications`, `aggregation_checkpoints`, `stats_hourly`, `stats_daily`, `provider_events`, `payment_attempts`, `financial_ledger_entries`, `notifications`, `report_exports`, `data_lifecycle_jobs` and optional tables retain D08 ownership and schema.
- **UX:** `UI-01..UI-20`, `UJ-01..UJ-28` and `WF-01..WF-18` need truthful pending/failed/degraded states, not unannounced busy spinners or silent background failures.

An identifier appearing in a crosswalk is documentation coverage, **not a claim that the event/job satisfies the whole original requirement by itself**.

## 24. Open decisions, design conflicts, formal sign-off

| Decision ID | Required decision | Why it blocks a feature | Required approvers |
|---|---|---|---|
| `ASY-DEC-001` | D09 R1 webhook routes vs D08 R4 `webhook_subscriptions` | Cannot enable external subscriptions until release/storage/permission model agrees | Product, Architect, Integrations, Security |
| `ASY-DEC-002` | Outbox owner-token/lease schema extension `ASY-DB-001` | Concurrent claim and fencing cannot be asserted on unextended D08 columns | Database, Reliability, Architect |
| `ASY-DEC-003` | Multi-consumer attempt tracking `ASY-DB-002` versus replay-only registry | Exact per-consumer completion/poison status needed for operations | Database, Reliability, QA |
| `ASY-DEC-004` | Action Scheduler packaging/loading and alternative runner | Must bootstrap without WooCommerce yet satisfy R1 reliability | WordPress, DevOps, License Reviewer |
| `ASY-DEC-005` | Actual worker intervals, timeouts and queue concurrency | Contract-critical jobs need measured scheduling drift | Product, DevOps, Performance |
| `ASY-DEC-006` | Metered `EV-026` ownership and possible split | Prevent Measurement from directly posting ledger by ambiguous event | AdTech, Finance, Architect |
| `ASY-DEC-007` | Approved source of Woo/provider settlement verification | Callback semantics differ by gateway and Woo state | Woo, Finance, Security |
| `ASY-DEC-008` | Money rounding/dispute evidence retention for R3 | Prevent irreversible wrong charges | Finance, Legal, Database |
| `ASY-DEC-009` | Raw event, dedup, aggregate and audit retention | Storage/PII and accounting obligations differ by jurisdiction | Privacy, Legal, Finance |
| `ASY-DEC-010` | Heatmap minimum cohort and sampling thresholds | R2 privacy and accuracy | Privacy, Measurement, UX |
| `ASY-DEC-011` | Replay hosting/storage and processor data protection terms | Optional R4 high-risk feature | Security, Privacy, License Reviewer |
| `ASY-DEC-012` | Open-source dependency redistribution and transitive licensing | WordPress distribution and legal compatibility | License Reviewer, Release Lead |
| `ASY-DEC-013` | DB engine/version matrix and DDL migration behavior | Atomic booking/lease promises rely on tested InnoDB | Database, DevOps, QA |
| `ASY-DEC-014` | R1 non-WooCommerce noncritical queue fallback and diagnostics | Plugin cannot crash because Woo unavailable | WordPress, Product, QA |
| `ASY-DEC-015` | Event payload max size, retry windows, service objectives | Avoid invented limits and unbounded load | Performance, Reliability, Security |
| `ASY-DEC-016` | Backfill projection algorithm and corrections accounting | Avoid duplicate totals across late events | Measurement, Reporting, Finance |
| `ASY-DEC-017` | Export artifact encryption/protected storage and rotation | Privacy and access revocation | Security, Privacy, DevOps |
| `ASY-DEC-018` | Audit, outbox and inbox archival/legal hold | Data deletion must not destroy required proof | Finance, Legal, Privacy |

### 24.1 Conflicts are blocking, not convenience defaults

Where release or schema documents disagree, annotate the issue and open a controlled revision to the owning predecessor. GPT-6 High must not generate an apparently working but unsafe optimistic implementation that bypasses required schema, authorization, legal consent or provider evidence. A not-yet-approved feature should be explicitly disabled with actionable diagnostics.

### 24.2 Formal approval gates

**Architecture/Database:** event registry, worker leases, inbox semantics, recovery and migration reviewed.  
**Finance/AdTech:** payment evidence, scheduling truth, R3 billable events and ledger handling reviewed.  
**Security/Privacy:** job provenance, tenant isolation, SSRF, replay/heatmap privacy, export protection reviewed.  
**Frontend/UX:** status, errors, retry, warnings, progress and accessibility reviewed.  
**QA/DevOps:** automated chaos/concurrency/load tests, alert/runbook drills and rollout compatibility reviewed.

**Status:** All approvals remain pending until explicitly recorded; this document provides the review material rather than claiming the reviews happened.

## 25. External technical references and implementation safeguards

The following are implementation verification sources, not authority to redefine AdPilot's business contracts. Check actual installed versions and documented APIs during implementation:

- [Action Scheduler official API](https://actionscheduler.org/api/) — one-off/recurring scheduling, arguments, unique scheduling behavior and hooks.
- [Action Scheduler documentation](https://actionscheduler.org/) — WordPress queue runner and operational context.
- [WordPress Cron handbook](https://developer.wordpress.org/plugins/cron/) — WP-Cron semantics and scheduling limitations.
- [WordPress REST API handbook](https://developer.wordpress.org/rest-api/) — endpoint validation, permissions, namespace and error conventions.
- [WooCommerce HPOS documentation](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/) — supported CRUD abstraction and authoritative order storage.
- [WordPress plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) — GPL compatibility and distribution requirements.
- [WordPress personal data guidelines](https://developer.wordpress.org/plugins/privacy/) — export/erasure and privacy considerations.

**Document end checkpoint:** `ADP-DOC-010` defines the asynchronous reliability specification. The next document is **ADP-DOC-011 — UX Architecture, Navigation & Interaction Design Specification**, focusing on administrator and advertiser journeys, Layout Studio, responsive design, accessible states, dashboards and heatmap explorer interactions. Do not treat acceptance scenarios as executed code tests.

