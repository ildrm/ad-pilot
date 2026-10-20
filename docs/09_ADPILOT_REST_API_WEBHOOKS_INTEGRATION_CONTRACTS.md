# AdPilot — REST API, Webhooks & Integration Contracts Specification

**Document ID:** ADP-DOC-009  
**Filename:** `09_ADPILOT_REST_API_WEBHOOKS_INTEGRATION_CONTRACTS.md`  
**Version:** 1.0.0 (proposed API implementation contract)  
**Status:** Proposed; architecture, security, product, finance, privacy and UX sign-off required  
**Date:** 2026-10-12  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Owner:** API & Integration Engineering Lead; Technical Lead accountable  
**Implementers:** WordPress/PHP, React/TypeScript, Gutenberg, ad-delivery, analytics, commerce, security/privacy, database, QA and DevOps engineers  
**Implementation target:** GPT-6 High; bounded verifiable work packages, no unsupported inference  
**Normative sources:** ADP-DOC-001 through ADP-DOC-008  
**Next document:** ADP-DOC-010 — Event Schemas, Background Jobs, Outbox & Asynchronous Processing Specification

> **Status disclaimer:** This document specifies APIs to implement; no endpoints, webhooks, benchmarks, or security tests are claimed as deployed or executed. Route names and auxiliary identifiers proposed here require sign-off before public stability commitments.

---

## 1. Scope, precedence, and non-negotiable invariants

### 1.1 Purpose

Specify AdPilot's REST boundary in sufficient precision for independently implementable PHP controllers, React query clients, Gutenberg block editors, marketplace pages, public advertising delivery SDK, analytics explorer, payment adapters, outbound webhooks, and WordPress theme integrations. An API route does not establish authority over an object merely because a caller can name its ID.

### 1.2 Normative precedence

1. **ADP-DOC-001:** canonical `FR-*` (129), `BR-001..018`, `NFR-001..016`, `INV-01..10`, `AC-001..015`, release boundaries `R1..R4`.
2. **ADP-DOC-002:** `AT-*` tests, `CAP-01..18` capability categories, `UI-01..20`, `E2E-01..17`.
3. **ADP-DOC-003:** `PERS-*`, `JTBD-*`, `UJ-01..28`, `WF-01..18`, UX error/recovery contracts.
4. **ADP-DOC-004:** `DP-*` policies, `SM-*` independent state machines, `PERM-001..016`, privileged-command rules.
5. **ADP-DOC-005:** WordPress site-plane capability names, per-organization membership policies, object authorization, site/network isolation.
6. **ADP-DOC-006:** 18 modules, 24 `IC-001..024` inter-module contracts, ADR boundaries, service and frontend isolation.
7. **ADP-DOC-007:** aggregate roots, domain service interfaces, command/query handlers, domain events and orchestration.
8. **ADP-DOC-008:** physical table ownership, exact-money storage, outbox, transaction/locking, retention, schema migration and multisite persistence.
9. **This specification:** HTTP presentation, API schemas, request authorization behavior, signed delivery protocols, client integration and tests. It SHALL NOT redefine older contracts. Any conflict is a recorded change request, not an implementation shortcut.

### 1.3 Mandatory global invariants

- A public ad request never grants financial, site-admin or cross-organization access.
- All protected reads, writes, exports, downloads and queue-induced mutations verify current trusted site/organization/object scope.
- A WordPress REST nonce is a CSRF measure and is **not** authorization, a one-use token, nor replay protection.
- Checkout, creative approval and delivery state machines remain independent; a confirmed payment does not approve creative.
- A quote, hold, payable order, reservation and exclusive slot allocation each have distinct authoritative state/version checks.
- No published layout changes a sold slot's commercial meaning without documented remediation and approval.
- Logical slots and per-page render occurrences have distinct identifiers; 500 configured slots do not imply 500 rendered, viewable or billable events.
- Browser events are untrusted signals, not billable ledger entries. Preview, editor, crawler and synthetic contexts never create billable impressions.
- Privacy-sensitive heatmap collection is R2 and optional replay is R4; consent/policy gates are separate from bare API availability.
- Public renderer must survive transient API/analytics failure without leaking private data or breaking page HTML.
- All routes have explicit release/feature gates and must fail closed if unavailable. R1 must function without WooCommerce bootstrapping or proprietary SaaS.

### 1.4 Normative wording

`MUST/SHALL` mandatory for an enabled release; `SHOULD` recommended with signed-off exception; `MAY` optional. `R1` core inventory/marketplace/fixed-time commerce; `R2` aggregate heatmaps; `R3` metered campaigns, conversions and advanced optimization; `R4` replay/network federation. Example HTTP payloads are proposed wire contracts and must pass OpenAPI and implementation tests before shipping.

---

## 2. API surface strategy and runtime topology

- **Base namespace:** `/wp-json/adpilot/v1` through WordPress REST API (`register_rest_route` during `rest_api_init`). The REST prefix may vary on unusual installations; frontend obtains its base URL from `rest_url('adpilot/v1/')`, not hard-coded `/wp-json`.
- **No mandatory external application server.** WordPress PHP controllers delegate to application commands/queries. In-process PHP callers may bypass HTTP transport but NEVER policy services.
- **API planes:** (P) public storefront; (A) authenticated advertiser organization; (S) site publisher/admin; (D) public delivery and event ingestion; (W) external callback/webhook; (I) signed outbound integration; (O) operations/diagnostics. A route can be assigned exactly one primary plane; multi-actor views use explicit scoped policy.
- **Dynamic block renders:** server-side `render_callback` invoking application rendering/query contracts, not a self-fetching admin REST request. React editor obtains sanitized draft/preview metadata through authenticated routes.
- **Theme functions/shortcodes:** invoke the same trusted application services directly, with public render projections; they must not expose privileged query DTOs or call a private endpoint with an administrator nonce.
- **Frontend:** WordPress site cookie authentication + `X-WP-Nonce` for same-origin authenticated operations; server performs capability and object checks. Browser delivery API is anonymous/low-trust with separately validated tokens and budgets.
- **Adapters:** WooCommerce uses supported CRUD and lifecycle events; direct provider callbacks are separately verified where applicable. Outbound third-party webhooks derive from committed domain outbox records.
- **No API key embedded in page HTML.** Public opaque decisions are ephemeral bounded attestations for individual render opportunities, not credentials.

### 2.1 Execution sequence / trust boundaries

```text
React admin / advertiser / editor --> WordPress REST route (authenticated)
  -> WP authentication + REST CSRF check -> trusted ActorScope (IC-001)
  -> scoped resource resolver + authorization policy -> schema validation
  -> application command / query -> domain invariants -> persistence transaction
  -> audit + outbox -> redacted response projection

Visitor/browser public SDK --> Delivery batch route (untrusted)
  -> origin/site binding + rate/size checks + layout existence
  -> server-authored context + feature/privacy policy -> fresh eligibility
  -> DecisionBatch (IC-013): empty or approved creative + expiring opaque reference

Browser measurement --> Event ingestion (untrusted)
  -> size/consent/decision association + anti-abuse/dedup
  -> quarantine/validate/qualify asynchronously -> aggregate (IC-015)

Committed outbox event --> webhook dispatcher (service principal)
  -> allowlisted subscription + minimized payload + signed HTTPS attempt
  -> retries + redacted logs + bounded retention
```

### 2.2 Naming, compatibility, and ownership

- Resource IDs in public JSON are stable opaque strings (e.g. 26-character application public IDs), never database auto-increment IDs. No exposure of site table prefixes.
- Public REST paths use plural nouns for resources. Side-effect commands use explicit `POST /resource/{id}/actions/{verb}` paths and document idempotency and expected state.
- Server uses `site_id` internally; all client `site_id`, `organization_id`, `member_user_id`, `approved`, `is_paid`, `billable`, `role`, `capability` fields are untrusted hints or disallowed, never authority.
- Version `v1` is the major URL contract, not a declaration that all v1 endpoints are enabled in R1. Feature availability is `IC-024`.
- Changes to existing v1 property meaning, field type, enum meaning, authorization behavior, money precision or time semantics require a new major version or a backward-compatible adapter plus documented transition.
- Do not advertise an OpenAPI `operationId` as stable until corresponding route, authorization, schema and tests are implemented.

---

## 3. HTTP conventions and universal schemas

### 3.1 Transport and content

- TLS in production. `Content-Type: application/json; charset=utf-8` for JSON mutations; `Accept: application/json` preferred.
- UTF-8 strings; output escaped by rendering consumers. Incoming HTML creatives use specialized sanitization, not generic text input rules.
- ISO 8601/RFC 3339 UTC timestamps with `Z` on the API; use UTC microseconds where storage supports them. Half-open intervals `[starts_at, ends_at)`; reject ambiguous local clock times without explicit timezone/offset resolution.
- Money: `{currency: "USD", amount_minor: "15000", scale: 2}`; decimal integer **string** for minor units to avoid JS floating-point corruption. `currency` and `scale` are server validated against approved policy; never derive tax from client.
- Fractions and rates: named numerator/denominator or a documented decimal string/precision. Do not silently mix CTR, viewability, billable clicks and raw click rates.
- Client-controlled arbitrary SQL order, field names, selection expressions or PHP callbacks are prohibited. Query filter and sort tokens are strict allowlists.
- Scope and release information are explicit in metadata rather than inferred from a UI toggle.
- Raw uploads: use WordPress media upload mechanisms under appropriate auth/permissions, with creative validation; no unauthenticated media upload endpoint.

### 3.2 Success envelope

Canonical new AdPilot endpoints return native `WP_REST_Response` with a defined application JSON envelope (WordPress REST index/standard core endpoints remain untouched):

```json
{
  "data": {"id": "01JEXAMPLE0000000000000000", "status": "draft"},
  "meta": {
    "request_id": "req_opaque_example",
    "api_version": "v1",
    "resource_version": 3,
    "server_time_utc": "2026-10-12T00:00:00Z"
  }
}
```

Do not echo untrusted request trace headers verbatim. Server generates log correlation IDs. All response DTOs are explicit allowlisted projections. Return `201 Created` + `Location` after creation when safe; `202 Accepted` for queued operations with job status, `204 No Content` for successful no-body actions, `304` for applicable conditional GET, `206` only with a defined compatible range contract.

### 3.3 Failure envelope

Use `WP_Error` in controllers with a stable plugin code and HTTP status. This produces the native WordPress REST error shape; don't inconsistently wrap it in the success envelope:

```json
{
  "code": "adpilot_revision_conflict",
  "message": "This layout has changed. Refresh the draft and review conflicts.",
  "data": {
    "status": 409,
    "request_id": "req_opaque_example",
    "expected_revision": 12,
    "actual_revision": 13,
    "retryable": false
  }
}
```

Only safe, allowlisted error details are returned; no stack traces, SQL messages, signing tokens, payment secrets, tenant existence, customer PII or internal hostnames. Authenticated private object not found across organizations returns indistinguishable `404` where disclosure of existence would violate policy. The UI must differentiate missing credentials (401), denied permission (403), unavailable target (404), stale state (409/412), validation (400/422), rate limit (429), and temporary failure (503) without claiming money has moved.

### 3.4 Canonical request and response headers

| Header | Side | Semantics |
|---|---|---|
| `X-WP-Nonce` | Client → authenticated WordPress REST | WordPress cookie-session CSRF protection; not object authorization |
| `Idempotency-Key` | Client → eligible mutating routes | Bounded opaque key bound to trusted site/org/actor/operation/request fingerprint |
| `If-Match` | Client → versioned update | Quoted strong entity revision/ETag; return `412` on mismatch |
| `ETag` | Server → cacheable versioned resource | Representation revision; not authority; private responses no shared caching |
| `Cache-Control` | Server → client/CDN | `private, no-store` for sensitive data and commands; carefully scoped public catalog caching |
| `Retry-After` | Server → client | Advisory retry for 429/503, server enforce idempotency |
| `X-AdPilot-Request-ID` | Server → client | Generated safe correlation reference (do not leak internal identifiers) |
| `X-AdPilot-Api-Version` | Server → client | API major contract information |
| `X-AdPilot-Webhook-*` | Outbound webhook | Signed delivery metadata described in §11 |

Clients cannot supply authorization context using any of the above headers alone. Treat intermediary/header stripping and proxy deployments as part of deployment tests.

### 3.5 Pagination, sorting, filters and representations

- Collections use `page_size` bounded by a documented per-route maximum and opaque signed `cursor` for potentially large lists (slots, campaigns, events, reports). Cursor binds site/org actor scope, filter hash, sort key, snapshot/version or expiry; reject tampering and cross-tenant replay.
- `next_cursor` is nullable; no fabricated exact `total` on expensive collections. Explicit `has_more` may be given when determined.
- Small stable reference collections may use page/per-page if documented; do not implement inconsistent hidden pagination.
- Default sorting is deterministic with public ID tie-breaker; no unstable offset pagination for mutable high-volume collections.
- Filter ranges: `from`, `to` in UTC, with explicit min/max duration; field allowlists (`status`, `zone_id`, `campaign_id`, `device_class`, etc.) and specific sort enum.
- Public catalog responses use a published safe projection. Never include customer membership, PII, account balances, private creative moderation, hidden rate details or raw analytics.
- ETag invalidation must respect live suspension, roles, published layout versions and offer expiry; no CDN cache of privileged data.

### 3.6 Time, money and identifier wire examples

```json
{
  "interval": {
    "starts_at": "2026-11-01T12:00:00Z",
    "ends_at": "2026-11-08T12:00:00Z",
    "display_timezone": "Europe/Berlin"
  },
  "price": {"currency": "EUR", "amount_minor": "12500", "scale": 2},
  "slot_id": "01JDEMO000000000000000001",
  "expected_version": 7
}
```

Identifiers above are illustrative. A local-time input is resolved to a UTC instant using explicit IANA zone and DST ambiguity policy before a quote or contractual reservation is accepted. Display time zones are not authorization scopes.

### 3.7 API errors and HTTP semantics catalog

| Code | HTTP | Trigger | Client recovery |
|---|---:|---|---|
| `adpilot_auth_required` | 401 | No authenticated session where required | Reauthenticate and recreate CSRF nonce |
| `adpilot_permission_denied` | 403 | Current actor lacks operation capability | Show denied state; no privileged retry |
| `adpilot_not_found` | 404 | Missing/inaccessible resource | Use safe unavailable message |
| `adpilot_validation_failed` | 422 | Field or semantic validation failure | Preserve draft, show per-field details |
| `adpilot_bad_request` | 400 | Malformed JSON/parameters/unsupported enum | Correct request |
| `adpilot_revision_conflict` | 409 | Business version/state conflict | Reload authoritative resource and re-evaluate |
| `adpilot_precondition_failed` | 412 | `If-Match` stale | Refresh and present differences |
| `adpilot_precondition_required` | 428 | Required revision header absent | Retrieve revision and retry consciously |
| `adpilot_inventory_unavailable` | 409 | Requested capacity not currently sellable | Offer fresh availability alternatives |
| `adpilot_hold_expired` | 409 | Hold TTL elapsed | Create a new quote/hold; never reactivate silently |
| `adpilot_unapproved_creative` | 409 | Creative version not approved | Submit for review; no serving |
| `adpilot_payment_unverified` | 409 | Payment lacks trusted evidence | Recheck payment state; no false paid success |
| `adpilot_feature_unavailable` | 404/409 | Release feature disabled or prerequisites absent | Display feature unavailable without sensitive details |
| `adpilot_policy_blocked` | 403/409 | Non-overridable policy denies action | Explain safe remediation if available |
| `adpilot_idempotency_conflict` | 409 | Same key with different request fingerprint | Use corrected key after conflict review |
| `adpilot_rate_limited` | 429 | Abuse/request budget exceeded | Respect `Retry-After` |
| `adpilot_service_unavailable` | 503 | Transient dependency outage | Retry safely, show degraded mode |
| `adpilot_export_expired` | 410 | Protected artifact expired/purged | Recreate export subject to permission |
| `adpilot_callback_invalid` | 401/403 | Untrusted webhook signature/provider source | Acknowledge only valid authenticated callbacks |
| `adpilot_cursor_invalid` | 400 | Cursor altered, expired or wrong scope | Restart paginated query |

HTTP status per code must be fixed in the OpenAPI artifact, with any policy-sensitive 404 mapped explicitly; the combined status notation above marks a decision to resolve by route rather than an invitation to vary randomly. Avoid `200` with `{success:false}` for failed mutations.

## 4. Canonical endpoint inventory

Every route below is a **proposed v1 contract**, not proof of implementation. `P/A/S/D` denote the execution plane from §2. `Policy` is an operation-level authorization predicate or a canonical WordPress capability; `org_*` policies are evaluated by Document 05's organization authorization service, never by granting a site-wide WP capability. `{id}` is a public scoped ID. `POST /actions/*` routes remain mutations and are validated against state-machine preconditions. All object-scoped `GET` methods reauthorize the resource even if a list request was already authorized. Public catalog, delivery and event endpoints have no private user projections.

### 4.0 Route authorization legend

| Entry | Meaning |
|---|---|
| `public_published` | No login; only explicitly published, safe fields |
| `public_estimate` | No sale, no hold; throttle/filter and label estimate |
| `public_decision_budget` | Untrusted browser, validated current layout+bounded slot batch; no elevated right |
| `public_event_budget` | Untrusted signal, decision provenance and anti-abuse checks |
| `org_*` | Active membership, current org/site, object ownership, applicable operation/state/finance restriction |
| `adpilot_*` | Document 05 WordPress capability plus object site, state, finance and policy checks |
| `export_owner_current` | Request-time owner/scope and fresh rights, never signature-only download |
| `invited_user` | Authenticated designated invitation recipient plus expiring high-entropy invitation proof |

### 4.1.1 Public catalog and discovery

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `CAT-001` | P | `R1` | `GET` | `/catalog/placements` | `public_published` | `IC-003/004` | Inventory | Published filters, bounded pagination |
| `CAT-002` | P | `R1` | `GET` | `/catalog/placements/{placement_id}` | `public_published` | `IC-003/004` | Inventory | Safe listing, preview and commercial terms |
| `CAT-003` | P | `R1` | `GET` | `/catalog/packages` | `public_published` | `IC-003/006` | Pricing | Published offers only |
| `CAT-004` | P | `R1` | `GET` | `/catalog/packages/{package_id}` | `public_published` | `IC-006` | Pricing | Public package version |
| `CAT-005` | P | `R1` | `GET` | `/catalog/placement-availability` | `public_estimate` | `IC-005` | Reservations | Advisory estimate, not held capacity |
| `CAT-006` | P | `R1` | `GET` | `/catalog/formats` | `public_published` | `IC-003` | Inventory | Accepted formats and safe specifications |
| `CAT-007` | P | `R1` | `GET` | `/catalog/policies` | `public_published` | `IC-019` | Compliance | Ad policy/disclosure without internal controls |
| `CAT-008` | P | `R1` | `GET` | `/public/feature-capabilities` | `public_minimized` | `IC-024` | Platform | Public availability flags without secrets |

### 4.1.2 Advertiser identity and organization

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `ORG-001` | A | `R1` | `GET` | `/me` | `authenticated_self` | `IC-001` | Identity | Scoped actor profile and granted affordances |
| `ORG-002` | A | `R1` | `GET` | `/my-organizations` | `membership_current` | `IC-002` | Advertisers | Only authorized org projections |
| `ORG-003` | A | `R1` | `POST` | `/organizations` | `create_org_policy` | `IC-002` | Advertisers | Self-service organization creation |
| `ORG-004` | A | `R1` | `GET` | `/organizations/{org_id}` | `org_read` | `IC-002` | Advertisers | Current member-only projection |
| `ORG-005` | A | `R1` | `PATCH` | `/organizations/{org_id}` | `org_owner_or_delegate` | `IC-002` | Advertisers | Profile edit CAS |
| `ORG-006` | A | `R1` | `GET` | `/organizations/{org_id}/members` | `org_member_admin` | `IC-001/002` | Identity | Member roster scoped to org |
| `ORG-007` | A | `R1` | `POST` | `/organizations/{org_id}/invitations` | `org_member_admin` | `IC-001/002` | Advertisers | Invite with constrained role |
| `ORG-008` | A | `R1` | `GET` | `/organizations/{org_id}/invitations` | `org_member_admin` | `IC-002` | Advertisers | Protected invitation list |
| `ORG-009` | A | `R1` | `POST` | `/invitations/{invitation_id}/accept` | `invited_user` | `IC-001/002` | Identity | Token verified out of band |
| `ORG-010` | A | `R1` | `POST` | `/invitations/{invitation_id}/decline` | `invited_user` | `IC-001/002` | Identity | Idempotent decline |
| `ORG-011` | A | `R1` | `POST` | `/organizations/{org_id}/members/{member_id}/actions/change-role` | `org_owner_non_escalate` | `IC-001` | Identity | Versioned role update |
| `ORG-012` | A | `R1` | `POST` | `/organizations/{org_id}/members/{member_id}/actions/revoke` | `org_owner_non_escalate` | `IC-001` | Identity | Enforce last-owner transfer rule |
| `ORG-013` | A | `R1` | `POST` | `/organizations/{org_id}/actions/transfer-ownership` | `org_owner_elevated` | `IC-001/002` | Identity | Dual-acknowledged high impact |
| `ORG-014` | S | `R1` | `GET` | `/admin/organizations` | `site_org_management` | `IC-002` | Advertisers | Admin directory scoped to current site |
| `ORG-015` | S | `R1` | `POST` | `/admin/organizations/{org_id}/actions/verify` | `site_org_management` | `IC-002` | Advertisers | Audited verification |
| `ORG-016` | S | `R1` | `POST` | `/admin/organizations/{org_id}/actions/suspend` | `site_org_management` | `IC-002` | Advertisers | Stop entitlement, notice and audit |

### 4.1.3 Inventory and slots

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `INV-001` | S | `R1` | `GET` | `/admin/zones` | `adpilot_view_inventory` | `IC-003` | Inventory | Filtered current-site catalog |
| `INV-002` | S | `R1` | `POST` | `/admin/zones` | `adpilot_manage_inventory` | `IC-003` | Inventory | Create draft zone |
| `INV-003` | S | `R1` | `GET` | `/admin/zones/{zone_id}` | `adpilot_view_inventory` | `IC-003` | Inventory | Full authorized zone with version |
| `INV-004` | S | `R1` | `PATCH` | `/admin/zones/{zone_id}` | `adpilot_manage_inventory` | `IC-003` | Inventory | CAS update only |
| `INV-005` | S | `R1` | `POST` | `/admin/zones/{zone_id}/actions/archive` | `adpilot_manage_inventory` | `IC-003` | Inventory | Respect sold commitments |
| `INV-006` | S | `R1` | `GET` | `/admin/containers` | `adpilot_view_inventory` | `IC-003` | Inventory | List container definitions |
| `INV-007` | S | `R1` | `POST` | `/admin/containers` | `adpilot_manage_inventory` | `IC-003` | Inventory | Create compact container |
| `INV-008` | S | `R1` | `PATCH` | `/admin/containers/{container_id}` | `adpilot_manage_inventory` | `IC-003` | Inventory | CAS update, validate responsiveness |
| `INV-009` | S | `R1` | `GET` | `/admin/slots` | `adpilot_view_inventory` | `IC-003` | Inventory | Cursor page, never unbounded 5000 |
| `INV-010` | S | `R1` | `GET` | `/admin/slots/{slot_id}` | `adpilot_view_inventory` | `IC-003` | Inventory | Stable commercial identity |
| `INV-011` | S | `R1` | `POST` | `/admin/slots/bulk-generate` | `adpilot_manage_inventory` | `IC-003` | Inventory | Bounded job with generator version |
| `INV-012` | S | `R1` | `POST` | `/admin/slots/bulk-update` | `adpilot_manage_inventory` | `IC-003` | Inventory | Per-item validation and audit |
| `INV-013` | S | `R1` | `GET` | `/admin/inventory/occupancy` | `adpilot_view_inventory` | `IC-003/008` | Inventory | Contract-aware occupancy aggregate |
| `INV-014` | S | `R1` | `GET` | `/admin/inventory/slot-reservations` | `adpilot_view_inventory` | `IC-008` | Reservations | Auth scoped contract impact |

### 4.1.4 Layout Studio

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `LAY-001` | S | `R1` | `GET` | `/admin/layouts` | `adpilot_view_inventory` | `IC-004` | Layout | List drafts and published pointers |
| `LAY-002` | S | `R1` | `POST` | `/admin/layouts/drafts` | `adpilot_manage_inventory` | `IC-004` | Layout | Create versioned draft |
| `LAY-003` | S | `R1` | `GET` | `/admin/layouts/drafts/{draft_id}` | `adpilot_view_inventory` | `IC-004` | Layout | Fetch editor state and ETag |
| `LAY-004` | S | `R1` | `PATCH` | `/admin/layouts/drafts/{draft_id}` | `adpilot_manage_inventory` | `IC-004` | Layout | CAS edit, slots remain stable |
| `LAY-005` | S | `R1` | `POST` | `/admin/layouts/drafts/{draft_id}/actions/validate` | `adpilot_manage_inventory` | `IC-003/004/019` | Layout | No side effect, contract-safe warnings |
| `LAY-006` | S | `R1` | `POST` | `/admin/layouts/drafts/{draft_id}/actions/preview` | `adpilot_view_inventory` | `IC-004` | Layout | Nonbillable authenticated preview |
| `LAY-007` | S | `R1` | `POST` | `/admin/layouts/drafts/{draft_id}/actions/publish` | `adpilot_publish_layout` | `IC-004/008/019` | Layout | Atomic pointer/impact resolution |
| `LAY-008` | S | `R1` | `GET` | `/admin/layouts/{layout_id}/revisions` | `adpilot_view_inventory` | `IC-004` | Layout | Immutable layout history |
| `LAY-009` | S | `R1` | `GET` | `/admin/layouts/{layout_id}/revisions/{version_id}` | `adpilot_view_inventory` | `IC-004` | Layout | Authorized snapshot |
| `LAY-010` | S | `R1` | `POST` | `/admin/layouts/{layout_id}/actions/restore` | `adpilot_publish_layout` | `IC-004/008` | Layout | Restore as new revision, impact check |
| `LAY-011` | S | `R1` | `GET` | `/admin/layouts/{layout_id}/contract-impacts` | `adpilot_view_inventory` | `IC-008/004` | Layout | List sold-slot impact assessments |
| `LAY-012` | S | `R1` | `POST` | `/admin/layouts/{layout_id}/contract-impacts/{impact_id}/actions/resolve` | `adpilot_approve_layout_remedy` | `IC-004/008` | Layout | Governed remedy approval |
| `LAY-013` | S | `R1` | `GET` | `/admin/placement-bindings` | `adpilot_view_inventory` | `IC-004` | Layout | Gutenberg/template binding inventory |
| `LAY-014` | S | `R1` | `POST` | `/admin/placement-bindings` | `adpilot_manage_inventory` | `IC-004` | Layout | Validated binding create |
| `LAY-015` | S | `R1` | `PATCH` | `/admin/placement-bindings/{binding_id}` | `adpilot_manage_inventory` | `IC-004` | Layout | CAS update, duplication policy |
| `LAY-016` | S | `R1` | `POST` | `/admin/placement-bindings/{binding_id}/actions/disable` | `adpilot_manage_inventory` | `IC-004` | Layout | Versioned deactivate, sold impact |

### 4.1.5 Gutenberg editor

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `BLOCK-001` | S | `R1` | `GET` | `/editor/zones` | `adpilot_insert_blocks` | `IC-003/004` | Layout | Post-context-safe selector |
| `BLOCK-002` | S | `R1` | `GET` | `/editor/zones/{zone_id}/preview` | `adpilot_insert_blocks` | `IC-004` | Layout | No billable events, limited preview |
| `BLOCK-003` | S | `R1` | `POST` | `/editor/placement-config/validate` | `adpilot_insert_blocks` | `IC-004/019` | Layout | Server validation of block attributes |
| `BLOCK-004` | S | `R1` | `GET` | `/editor/placement-presets` | `adpilot_insert_blocks` | `IC-003` | Layout | Approved presets, role scoped |

### 4.1.6 Campaign lifecycle

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `CAM-001` | A | `R1` | `GET` | `/organizations/{org_id}/campaigns` | `org_campaign_read` | `IC-010` | Campaigns | Org-scoped cursor list |
| `CAM-002` | A | `R1` | `POST` | `/organizations/{org_id}/campaigns` | `org_campaign_write` | `IC-010` | Campaigns | Create own draft |
| `CAM-003` | A | `R1` | `GET` | `/organizations/{org_id}/campaigns/{campaign_id}` | `org_campaign_read` | `IC-010` | Campaigns | Ownership and projection |
| `CAM-004` | A | `R1` | `PATCH` | `/organizations/{org_id}/campaigns/{campaign_id}` | `org_campaign_write` | `IC-010` | Campaigns | Only editable states/fields with CAS |
| `CAM-005` | A | `R1` | `POST` | `/organizations/{org_id}/campaigns/{campaign_id}/actions/submit` | `org_campaign_write` | `IC-009/010` | Campaigns | Submit linked creative and terms |
| `CAM-006` | A | `R1` | `POST` | `/organizations/{org_id}/campaigns/{campaign_id}/actions/request-pause` | `org_campaign_write` | `IC-010` | Campaigns | Policy-governed action |
| `CAM-007` | A | `R1` | `POST` | `/organizations/{org_id}/campaigns/{campaign_id}/actions/request-resume` | `org_campaign_write` | `IC-010` | Campaigns | Recheck eligibility |
| `CAM-008` | S | `R1` | `GET` | `/admin/campaigns` | `adpilot_view_campaigns` | `IC-010` | Campaigns | Site-scoped operational campaign list |
| `CAM-009` | S | `R1` | `GET` | `/admin/campaigns/{campaign_id}` | `adpilot_view_campaigns` | `IC-010` | Campaigns | Moderation and delivery projection |
| `CAM-010` | S | `R1` | `POST` | `/admin/campaigns/{campaign_id}/actions/schedule` | `adpilot_manage_campaigns` | `IC-010/008` | Campaigns | Authorized scheduling |
| `CAM-011` | S | `R1` | `POST` | `/admin/campaigns/{campaign_id}/actions/pause` | `adpilot_manage_campaigns` | `IC-010` | Campaigns | Bump stop version |
| `CAM-012` | S | `R1` | `POST` | `/admin/campaigns/{campaign_id}/actions/emergency-stop` | `adpilot_emergency_stop` | `IC-010/019` | Campaigns | Immediate authoritative stop and audit |
| `CAM-013` | S | `R1` | `POST` | `/admin/campaigns/{campaign_id}/actions/resume` | `adpilot_manage_campaigns` | `IC-010` | Campaigns | Fresh eligibility required |
| `CAM-014` | A | `R2` | `POST` | `/organizations/{org_id}/campaigns/{campaign_id}/actions/duplicate` | `org_campaign_write` | `IC-010` | Campaigns | New draft, never cloned approval |

### 4.1.7 Creatives and moderation

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `CRE-001` | A | `R1` | `GET` | `/organizations/{org_id}/creatives` | `org_creative_read` | `IC-009` | Creatives | Org-scoped creative list |
| `CRE-002` | A | `R1` | `POST` | `/organizations/{org_id}/creatives` | `org_creative_write` | `IC-009` | Creatives | Create metadata with approved media reference |
| `CRE-003` | A | `R1` | `POST` | `/organizations/{org_id}/creatives/{creative_id}/versions` | `org_creative_write` | `IC-009` | Creatives | New immutable version submission |
| `CRE-004` | A | `R1` | `GET` | `/organizations/{org_id}/creatives/{creative_id}` | `org_creative_read` | `IC-009` | Creatives | Versioned projection, safe media |
| `CRE-005` | A | `R1` | `POST` | `/organizations/{org_id}/creatives/{creative_id}/actions/submit-review` | `org_creative_write` | `IC-009` | Moderation | Review exact version digest |
| `CRE-006` | S | `R1` | `GET` | `/admin/creative-reviews` | `adpilot_moderate_creatives` | `IC-009` | Moderation | Approval queue |
| `CRE-007` | S | `R1` | `POST` | `/admin/creative-reviews/{review_id}/actions/approve` | `adpilot_moderate_creatives` | `IC-009/019` | Moderation | Approve immutable digest only |
| `CRE-008` | S | `R1` | `POST` | `/admin/creative-reviews/{review_id}/actions/reject` | `adpilot_moderate_creatives` | `IC-009/019` | Moderation | Reason required |
| `CRE-009` | S | `R1` | `POST` | `/admin/creative-reviews/{review_id}/actions/request-changes` | `adpilot_moderate_creatives` | `IC-009` | Moderation | Version follow-up |
| `CRE-010` | A | `R1` | `GET` | `/organizations/{org_id}/creatives/{creative_id}/preview` | `org_creative_read` | `IC-009` | Creatives | Safe nonbillable preview |

### 4.1.8 Pricing and quotes

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `PRC-001` | S | `R1` | `GET` | `/admin/rate-cards` | `adpilot_manage_rates` | `IC-006` | Pricing | Draft and published rate cards |
| `PRC-002` | S | `R1` | `POST` | `/admin/rate-cards` | `adpilot_manage_rates` | `IC-006` | Pricing | Create draft version |
| `PRC-003` | S | `R1` | `PATCH` | `/admin/rate-cards/{rate_id}` | `adpilot_manage_rates` | `IC-006` | Pricing | Versioned draft update |
| `PRC-004` | S | `R1` | `POST` | `/admin/rate-cards/{rate_id}/actions/publish` | `adpilot_manage_rates` | `IC-006` | Pricing | Freeze accepted version |
| `PRC-005` | A | `R1` | `POST` | `/organizations/{org_id}/quotes` | `adpilot_purchase_inventory` | `IC-005/006` | Pricing | Authoritative offer, server totals |
| `PRC-006` | A | `R1` | `GET` | `/organizations/{org_id}/quotes/{quote_id}` | `org_quote_read` | `IC-006` | Pricing | Own immutable quote |

### 4.1.9 Reservations and holds

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `RES-001` | A | `R1` | `POST` | `/organizations/{org_id}/holds` | `adpilot_purchase_inventory` | `IC-005/007` | Reservations | Atomic capacity acquire |
| `RES-002` | A | `R1` | `GET` | `/organizations/{org_id}/holds/{hold_id}` | `org_hold_read` | `IC-007` | Reservations | Current expiry and state |
| `RES-003` | A | `R1` | `POST` | `/organizations/{org_id}/holds/{hold_id}/actions/release` | `org_hold_write` | `IC-007` | Reservations | Idempotent early release |
| `RES-004` | S | `R1` | `GET` | `/admin/reservations` | `adpilot_view_inventory` | `IC-008` | Reservations | Site current contracts |
| `RES-005` | S | `R1` | `GET` | `/admin/reservations/{reservation_id}` | `adpilot_view_inventory` | `IC-008` | Reservations | Contract detail, constrained finance |
| `RES-006` | S | `R1` | `POST` | `/admin/reservations/{reservation_id}/actions/propose-remedy` | `adpilot_approve_layout_remedy` | `IC-008` | Reservations | Policy review and audit |

### 4.1.10 Orders, billing and finance

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `ORD-001` | A | `R1` | `POST` | `/organizations/{org_id}/orders` | `adpilot_purchase_inventory` | `IC-006/007/011` | Commerce | Create from current hold, idempotent |
| `ORD-002` | A | `R1` | `GET` | `/organizations/{org_id}/orders` | `org_finance_read` | `IC-011/012` | Commerce | Org billing projection |
| `ORD-003` | A | `R1` | `GET` | `/organizations/{org_id}/orders/{order_id}` | `org_finance_read` | `IC-011/012` | Commerce | Own order, safe status |
| `ORD-004` | A | `R1` | `POST` | `/organizations/{org_id}/orders/{order_id}/actions/start-checkout` | `org_finance_write` | `IC-011` | Commerce | WC adapter trusted checkout handoff |
| `ORD-005` | A | `R1` | `GET` | `/organizations/{org_id}/invoices` | `org_finance_read` | `IC-012` | Commerce | Invoices own org |
| `ORD-006` | A | `R1` | `GET` | `/organizations/{org_id}/invoices/{invoice_id}` | `org_finance_read` | `IC-012` | Commerce | Protected invoice metadata |
| `ORD-007` | S | `R1` | `GET` | `/admin/orders` | `adpilot_view_orders` | `IC-011/012` | Commerce | Site finance lookup |
| `ORD-008` | S | `R1` | `GET` | `/admin/ledger` | `adpilot_view_finance` | `IC-012` | Commerce | Restricted ledger projection |
| `ORD-009` | S | `R1` | `POST` | `/admin/orders/{order_id}/actions/propose-remedy` | `adpilot_adjust_finance` | `IC-012` | Commerce | Refund/cancellation governed policy |
| `ORD-010` | S | `R1` | `POST` | `/admin/remedies/{remedy_id}/actions/approve` | `adpilot_approve_financial_override` | `IC-012` | Commerce | Independent approval policy |
| `ORD-011` | S | `R1` | `POST` | `/admin/remedies/{remedy_id}/actions/execute` | `adpilot_adjust_finance` | `IC-011/012` | Commerce | Verified gateway result/ledger append |
| `ORD-012` | S | `R1` | `GET` | `/admin/payment-reconciliation` | `adpilot_view_finance` | `IC-011/012` | Commerce | Trusted provider evidence only |

### 4.1.11 Public ad decision and click

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `DEL-001` | D | `R1` | `POST` | `/delivery/decisions` | `public_decision_budget` | `IC-004/009/010/013/019` | Delivery | Bounded batch slots and opaque decisions |
| `DEL-002` | D | `R1` | `POST` | `/delivery/click` | `public_click_validation` | `IC-013/014` | Delivery | Server validated redirect or denied click |
| `DEL-003` | D | `R1` | `GET` | `/delivery/creative/{decision_ref}` | `public_decision_token` | `IC-009/013` | Delivery | Safe public creative only, short TTL |

### 4.1.12 Measurement ingestion

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `MET-001` | D | `R1` | `POST` | `/measurement/events` | `public_event_budget` | `IC-014` | Measurement | Untrusted batch, nonbillable until qualified |
| `MET-002` | D | `R1` | `GET` | `/measurement/client-config` | `public_minimized` | `IC-019/024` | Measurement | Safe supported event types and limits |
| `MET-003` | S | `R1` | `GET` | `/admin/measurement/health` | `adpilot_view_diagnostics` | `IC-014/015/023` | Measurement | Redacted ingestion stats |

### 4.1.13 Reporting and exports

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `REP-001` | S | `R1` | `GET` | `/admin/reports/overview` | `adpilot_view_aggregate_analytics` | `IC-015/012` | Reporting | Revenue from ledger only |
| `REP-002` | S | `R1` | `GET` | `/admin/reports/campaigns` | `adpilot_view_aggregate_analytics` | `IC-015` | Reporting | Filtered aggregated metrics |
| `REP-003` | S | `R1` | `GET` | `/admin/reports/placements` | `adpilot_view_aggregate_analytics` | `IC-015` | Reporting | Stable slot/layout version |
| `REP-004` | A | `R1` | `GET` | `/organizations/{org_id}/reports/campaigns` | `org_analytics_read` | `IC-015` | Reporting | Org aggregated only |
| `REP-005` | S | `R1` | `POST` | `/admin/exports` | `adpilot_export_reports` | `IC-018` | Reporting | Protected async report task |
| `REP-006` | A | `R1` | `POST` | `/organizations/{org_id}/exports` | `org_export_reports` | `IC-018` | Reporting | Org-scoped export task |
| `REP-007` | A | `R1` | `GET` | `/exports/{export_id}` | `export_owner_current` | `IC-018` | Reporting | Job status, private metadata |
| `REP-008` | A | `R1` | `GET` | `/exports/{export_id}/download` | `export_owner_current` | `IC-018` | Reporting | Fresh auth, protected stream |

### 4.1.14 Heatmaps and optional replay

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `HEAT-001` | S | `R2` | `GET` | `/admin/heatmaps/pages` | `adpilot_view_restricted_analytics` | `IC-016/017` | BehavioralAnalytics | Eligible pages, aggregate only |
| `HEAT-002` | S | `R2` | `GET` | `/admin/heatmaps/geometry/{layout_version}` | `adpilot_view_restricted_analytics` | `IC-016` | BehavioralAnalytics | Safe geometry/anchors |
| `HEAT-003` | S | `R2` | `GET` | `/admin/heatmaps/aggregates` | `adpilot_view_restricted_analytics` | `IC-017` | BehavioralAnalytics | Normalized bins and suppression notices |
| `HEAT-004` | S | `R2` | `GET` | `/admin/heatmaps/compare` | `adpilot_view_restricted_analytics` | `IC-016/017` | BehavioralAnalytics | No cross-version silent remap |
| `HEAT-005` | S | `R2` | `GET` | `/admin/heatmaps/element-summary` | `adpilot_view_restricted_analytics` | `IC-017` | BehavioralAnalytics | Accessible tabular equivalent |
| `HEAT-006` | S | `R4` | `GET` | `/admin/replay/sessions` | `adpilot_view_replay` | `IC-024` | BehavioralAnalytics | Consent-gated session index only |
| `HEAT-007` | S | `R4` | `GET` | `/admin/replay/sessions/{session_id}` | `adpilot_view_replay` | `IC-024` | BehavioralAnalytics | Purpose + access check every request |

### 4.1.15 Site operations and integration management

| ID | Plane | Release | Method | Path | Policy | Contract(s) | Owner | Behavior |
|---|---|---|---|---|---|---|---|---|
| `OPS-001` | S | `R1` | `GET` | `/admin/settings` | `adpilot_manage_settings` | `IC-024` | Platform | Redacted settings, never secret values |
| `OPS-002` | S | `R1` | `PATCH` | `/admin/settings` | `adpilot_manage_settings` | `IC-024` | Platform | Versioned allowed fields, no implicit elevated grants |
| `OPS-003` | S | `R1` | `GET` | `/admin/roles-capabilities` | `adpilot_manage_roles` | `IC-001` | Identity | Scope-aware role projection |
| `OPS-004` | S | `R1` | `POST` | `/admin/roles-capabilities/actions/update` | `adpilot_manage_roles` | `IC-001` | Identity | No self-escalation, audited |
| `OPS-005` | S | `R1` | `GET` | `/admin/diagnostics` | `adpilot_view_diagnostics` | `IC-023` | Platform | Redacted health, queues and migrations |
| `OPS-006` | S | `R1` | `POST` | `/admin/jobs/{job_id}/actions/retry` | `adpilot_retry_jobs` | `IC-020` | Platform | Approved job types, re-auth current state |
| `OPS-007` | S | `R1` | `GET` | `/admin/audit-log` | `adpilot_view_audit_log` | `IC-001` | Platform | Redacted audited events |
| `OPS-008` | S | `R1` | `GET` | `/admin/integrations` | `adpilot_manage_integrations` | `IC-021/024` | Integrations | Configured endpoints without secrets |
| `OPS-009` | S | `R1` | `POST` | `/admin/integrations/webhook-subscriptions` | `adpilot_manage_integrations` | `IC-021` | Integrations | Verified endpoint/allowlist |
| `OPS-010` | S | `R1` | `PATCH` | `/admin/integrations/webhook-subscriptions/{subscription_id}` | `adpilot_manage_integrations` | `IC-021` | Integrations | Versioned modifications |
| `OPS-011` | S | `R1` | `POST` | `/admin/integrations/webhook-subscriptions/{subscription_id}/actions/rotate-secret` | `adpilot_manage_integrations` | `IC-021` | Integrations | Issue new secret, short overlap, encrypted vault |
| `OPS-012` | S | `R1` | `GET` | `/admin/integrations/webhook-deliveries` | `adpilot_manage_integrations` | `IC-021` | Integrations | Redacted attempts and status |
| `OPS-013` | S | `R1` | `POST` | `/admin/integrations/webhook-deliveries/{delivery_id}/actions/retry` | `adpilot_manage_integrations` | `IC-020/021` | Integrations | Audited manual retry |
| `OPS-014` | S | `R1` | `GET` | `/admin/feature-availability` | `adpilot_manage_settings` | `IC-024` | Platform | Approved server feature state |
| `OPS-015` | S | `R1` | `GET` | `/admin/privacy` | `adpilot_manage_privacy` | `IC-019` | Compliance | Scoped policy and retention status |
| `OPS-016` | S | `R1` | `PATCH` | `/admin/privacy` | `adpilot_manage_privacy` | `IC-019` | Compliance | Elevated, CAS and audited |
| `OPS-017` | S | `R3` | `GET` | `/admin/metered-reconciliation` | `adpilot_view_finance` | `IC-012/015` | Commerce | R3 qualified billing only |
| `OPS-018` | S | `R4` | `GET` | `/network/sites` | `adpilot_network_governance` | `IC-001/024` | Integrations | Explicit network governance scope |

### 4.2 Operations deliberately excluded from R1

- R3 metered billing endpoints may only be introduced after reconciled qualified-event evidence, disputes and ledger acceptance. Browser-submitted clicks never invoke a charge endpoint.
- R2 heatmap analytics endpoints must not start accepting data merely because `GET /admin/heatmaps/*` exists; consent, configuration, collection permissions and aggregation must be enabled and reviewed.
- R4 replay and network federation endpoints remain disabled and are never discoverable as executable in R1.
- Arbitrary advertiser JavaScript uploads, SQL querying, PHP snippet execution, direct ledger overwrite, unauthenticated campaign creation, unrestricted analytics data exports, cross-site impersonation and public creative approval APIs are prohibited.

### 4.3 Route-to-owner enforcement contract

A route's **owner** is the only module allowed to write its aggregate. API controllers never modify `wp_adpilot_*` tables directly. Read-model aggregation is eventually consistent only when permitted (charts, reports, exports); inventory capacity, authorization, financial evidence, campaign kill-switch and creative approval are authoritative. The path's policy token is registered with the Document 05 policy registry and backed by table-driven tests. Routes may be co-located in one REST controller class by cohesive resource ownership, but must not bypass module contracts to call another module's repositories.

## 5. Resource DTO and field-level schema catalog

### 5.1 Common reusable components

| Schema | Required fields | Constraints / policy |
|---|---|---|
| `PublicId` | string | Application opaque ID; validate shape and site-scoped lookup, not authorization |
| `UtcInstant` | string | RFC3339 UTC with `Z`; exact instant; no fabricated local conversion |
| `TimeWindow` | `starts_at`, `ends_at` | Start strictly before end; half-open range; contract DST handling |
| `Money` | `currency`, `amount_minor`, `scale` | Exact integer minor units as signed decimal string; server-produced money math |
| `ResourceVersion` | `resource_version` | Monotonic positive integer; `If-Match` for mutations |
| `ActorProjection` | `user_id`, `site_scope`, `affordances` | Server determined; no raw membership union across orgs |
| `PageCursor` | `items`, `next_cursor`, `has_more` | Cursor binds immutable scope/filter or expires |
| `IdempotencyReference` | `idempotency_key` | Request header, never echoed as a credential; recoverable status if network timeout |
| `Warning` | `code`, `message`, `severity` | Sanitized actionable warning; cannot override binding legal constraint |
| `FeatureGate` | `feature`, `enabled`, `reason_code` | Safe UI projection only, server enforces independently |
| `ProblemDetail` | WP REST `code`, `message`, `data.status` | Machine-stable code and restricted details |

### 5.2 Ad inventory model

`ZoneRead`: `id`, `name`, `status`, `commercial_class`, `current_layout_version`, `resource_version`, `containers_summary`, `published_at`, optional authorized `internal_notes`. `ContainerRead`: `id`, `zone_id`, `layout_type` (`single|row|stack|grid|carousel|repeater|conditional` where implemented), `responsive_rules`, `slot_count`, `generator_version`, `resource_version`. `SlotRead`: `id`, `container_id`, `slot_key`, `ordinal`, `status`, `commercial_class`, `allocation_mode`, `capacity_units`, `resource_version`, `active_reservation_indicator`. Server validates supported format capabilities; UI's preview never assigns an unapproved creative.

`SlotRangeQuery`: `zone_id` or `container_id` + approved `cursor`/`page_size`; for 500/1000+ slots return a compact indexed range. Do not send one giant mutable `slots[]` payload inside a Gutenberg block. A container's `slot_count` is configuration, while its *sellable slots* have stable materialized identifiers before booking.

### 5.3 Layout draft, binding and publication

`LayoutDraftRead`: `id`, `scope`, `draft_revision`, `active_published_version`, `container_tree`, `breakpoint_rules`, `bindings`, `preview_manifest`, `resource_version`. `PublishLayoutRequest`: `expected_draft_revision` (int), `expected_active_layout_version` (string|null), `validated_impact_id` (string|null), `approved_remedy_ref` (string|null), `reason` (required for sensitive impact), and `Idempotency-Key` header. The server computes geometry/material bindings, not the client.

`LayoutPublishResult`: `published_layout_version_id`, `active_pointer_revision`, `slot_identity_map_hash`, `binding_summary`, `contract_impact_summary`, `warnings`, `audit_ref`; emit `LayoutPublished` from transactional outbox. `PreviewResult` is nonbillable and contains bounded safe creative placeholders, not real campaign metrics or decision tokens.

`BindingRead`: `id`, `site_scope`, `context_type` (e.g. `page|post|archive|block_template|theme_hook|shortcode|php_api`), scoped context reference, `zone_id`, `render_policy`, `resource_version`. Client-supplied selector, theme hook or template name is checked against allowed formats and meaningful compatibility policies. Boundaries for exact builder integrations remain explicit extension decisions.

### 5.4 Advertiser and membership projection

`OrganizationRead`: `id`, `display_name`, `status`, `verification_state`, `own_member_role`, `resource_version`, safe brand fields. `MembershipRead`: `id`, `user_public_ref`, `organization_id`, `membership_role` (`owner|manager|analyst|billing`), `status`, `resource_version`, `granted_affordances` (server derived). Owner transfer cannot be completed by UI manipulation of `owner_user_id`; require dedicated command, policy approval and audit. Site-publisher capabilities do not imply organization memberships.

An advertiser who belongs to organizations A and B must select a current organization when operating on a campaign; `GET /organizations/{org_id}/campaigns` re-evaluates that user's *current* membership and scoped campaign ownership for every request. Organization selection in browser state cannot change the trusted site context.

### 5.5 Campaign and creative projection

`CampaignRead`: `id`, `organization_id`, `name`, `schedule`, `approval_state`, `financial_state`, `delivery_state`, `linked_reservation`, `selected_creative_version`, `resource_version`, `next_allowed_actions`, measured reporting freshness when authorized. Do **not** flatten states to a single `status`. Separate allowed actions apply to manager vs publisher.

`CreativeVersionRead`: `creative_id`, `version_id`, `digest`, `media_type`, `safe_asset_ref`, `safe_destination_url`, `approval_state`, `disclosure`, `submitted_at`, `reviewed_at` (if allowed), `resource_version`. Uploaded file metadata alone is not a safe rendered creative. Any destination/content change creates a new version and an approval requirement.

`CreativeReviewAction`: `expected_review_version`, `creative_version_id`, `review_reason` (required for reject/request changes; optional for approve with policy), `policy_version`, `Idempotency-Key`. Review endpoints return updated review and campaign summary, not a grant that bypasses payment or scheduling.

### 5.6 Commercial objects

`QuoteRead`: `id`, `organization_id`, `rate_card_version`, `line_items` (all amounts exact), `tax_rule_ref`, `currency`, `total_minor`, `terms_digest`, `expires_at`, `status`, `resource_version`. `HoldRead`: `id`, `quote_id`, `slot_lines`, `starts_at`, `ends_at`, `expires_at`, `status`, `resource_version`. `OrderRead`: `id`, `organization_id`, `quote_snapshot_ref`, `hold_ref`, `financial_status`, `checkout_provider`, `verified_amount_minor`, `currency`, `resource_version`, safe `next_actions`.

`PaymentEvidenceRead` is never exposed as a gateway secret or raw provider webhook; use sanitized status/provenance for finance-only views. `LedgerEntryRead` is append-only, includes source fact, audited adjustment reference, currency and exact amount; the normal advertiser only sees authorized invoices/payment summaries.

`ReservationRead`: `id`, `contract_ref`, `slot_ids`, `time_window`, `allocation_mode`, `status`, `commercial_rights_version`, `resource_version`. A brief public availability estimate cannot be treated as a reservation or guaranteed impression volume.

### 5.7 Delivery decisions and event envelope

`DecisionBatchRequest`: `published_layout_ref` (hint only; server must validate active), `page_context_ref` (bounded and validated), `requested_slots` as `{slot_id, render_instance_id}` pairs, `device_class` when appropriate, bounded `batch_ref`. `render_instance_id` identifies an occurrence and does not grant tenant access. Never accept `paid`, `approved`, `site_id`, `campaign_owner`, `targeting_verified`, or `viewed` from the browser as business facts.

`DecisionBatchResponse`: `batch_ref`, `server_time_utc`, `expires_at`, `decisions[]` entries: `render_instance_id`, `slot_id`, `result` (`creative|empty`), `decision_ref` if creative, safe asset and disclosure metadata, optional layout dimensions and delivery hints. No private contract price, advertiser identity data, site authentication or personal targeting details. Temporary failures respond with bounded retry/fallback metadata, no unsafe stale creative serving.

`BrowserEvent`: `schema_version`, `event_id`, `decision_ref`, `render_instance_id`, `event_type`, `occurred_at`, permitted metrics, optional layout/geometry version, `consent_category` as signal not authority. Event types in R1: `rendered`, `visibility_milestone`, `click_observed`, with documented validation; R2 adds purpose-limited `scroll_milestone`, `element_visibility`, `pointer_sample` where enabled. R3 billable events are derived server-side and reconciled, not submitted as `event_type=billable`.

### 5.8 Reports, heatmaps, export and diagnostics

`MetricRow`: metric name, definition version, numerator/denominator or exact value, aggregation bucket UTC, sampling/exclusion flags, freshness timestamp, dimensions, qualification level. `ReportResponse`: authorized scope, metric rows, `last_aggregated_at`, `incomplete_through`, `data_quality_warnings`, pagination. Revenue comes only from commerce financial projections.

`HeatmapResponse` (R2): `page_ref`, `layout_version`, `geometry_version`, `device_class`, normalized bins, suppression and sample-count metadata, consent/purpose source, `last_aggregated_at`. The API cannot truthfully label movement maps as gaze tracking. Raw session playback and visitor-level raw trajectories are excluded from advertiser reports.

`ExportRead`: `id`, `owner_scope`, `report_definition`, `job_status` (`queued|running|ready|failed|expired|purged`), `created_at`, `expires_at`, sanitized error; **no permanent public `file_url`**. Download requires current permission+ownership+purpose and protected streaming; a prior job acceptance cannot grant perpetual access.

`DiagnosticsRead`: component health, queue delay, migration status, export/collector failures and redacted correlation references. No PHP errors, private asset paths, gateway secrets or raw behavioral payload.

### 5.9 Field projection matrix

| Data class | Public storefront | Advertiser member | Publisher AdOps | Finance | Restricted analytics |
|---|---|---|---|---|---|
| Published placement description and public rate | Yes | Yes | Yes | Yes | Yes, if operational |
| Unpublished layout and sold-contract details | No | No | Scoped | Only necessary financial scope | Limited |
| Advertiser's campaign and approved creative | No private data | Own org only | Current site | Contract references | Aggregate only |
| Organization membership roster | No | Org owner/delegate only | Narrow verified management | No by default | No |
| Invoice/payment summary | No | Own org billing rights | No by default | Current site, restricted | No |
| Internal financial ledger | No | No | No | Authorized finance only | No |
| Aggregated ad impressions and qualified CTR | Published safe examples only | Own org | Site | Contract-linked when necessary | Site authorized |
| Page heatmap aggregate | No | No by default | Restricted R2 privilege | No by default | R2 explicit permission |
| Session replay | No | No | No by default | No | R4 separately approved only |
| Secrets/webhook signing keys | Never | Never | Never | Never | Never |

---

## 6. Normative request/response examples

### 6.1 Public placement catalog

```http
GET /wp-json/adpilot/v1/catalog/placements?format=image&device_class=desktop&page_size=20 HTTP/1.1
Accept: application/json
```

```json
{
  "data": {
    "items": [
      {
        "id": "01JPLACEMENTEXAMPLE00000001",
        "title": "Homepage premium banner",
        "formats": ["image"],
        "responsive_preview": {"desktop": "wide", "mobile": "stack"},
        "pricing": {"type": "time_based", "currency": "USD", "amount_minor": "12000", "scale": 2, "period": "week"},
        "availability": "estimate_only"
      }
    ],
    "next_cursor": null,
    "has_more": false
  },
  "meta": {"request_id": "req_demo", "api_version": "v1", "server_time_utc": "2026-10-12T00:00:00Z"}
}
```

Public previews use sanitized placeholders, not arbitrary advertiser HTML. Public rate values reflect a published rate-card version and never constitute a confirmed quote.

### 6.2 Quote and hold with independent authoritative capacity checks

```http
POST /wp-json/adpilot/v1/organizations/01JORGDEMO0000000000000001/quotes HTTP/1.1
X-WP-Nonce: <wp-rest-nonce>
Idempotency-Key: quote-attempt-client-unique
Content-Type: application/json
```

```json
{
  "placement_ids": ["01JSLOTDEMO000000000000001"],
  "time_window": {"starts_at":"2026-11-01T12:00:00Z","ends_at":"2026-11-08T12:00:00Z"},
  "campaign_draft_id": "01JCAMDEMO0000000000000001"
}
```

Server returns immutable quote price, policies and expiry. The frontend must display a warning that availability can change before a hold is acquired. It must not send final `total_minor` for authority.

```http
POST /wp-json/adpilot/v1/organizations/01JORGDEMO0000000000000001/holds HTTP/1.1
X-WP-Nonce: <wp-rest-nonce>
Idempotency-Key: hold-attempt-client-unique
Content-Type: application/json
```

```json
{
  "quote_id": "01JQUOTEDEMO00000000000001",
  "expected_quote_version": 1
}
```

A success result includes the hold's expiration, status and quote reference. On inventory conflict return `409 adpilot_inventory_unavailable`. An expired hold cannot be restored by a delayed payment; create a new, transactionally verified hold and determine refund/remedy for the late payment.

### 6.3 Layout save and safe publication

```http
PATCH /wp-json/adpilot/v1/admin/layouts/drafts/01JLAYOUTDRAFT00000000001 HTTP/1.1
X-WP-Nonce: <wp-rest-nonce>
If-Match: "draft-v12"
Content-Type: application/json
```

```json
{
  "container_tree": [
    {"container_id": "01JCONTAINERDEMO00000001", "layout_type": "grid", "configured_slot_count": 200,
     "breakpoints": [{"min_width_px": 0, "columns": 1},{"min_width_px": 640,"columns": 3},{"min_width_px": 1024,"columns": 5}]}
  ],
  "reason": "Improve footer responsive layout"
}
```

The server validates slot identity mapping, responsiveness, sold inventory, and policy limits before saving; successful save creates a draft revision but does not publish. `POST .../actions/publish` requires expected active pointer version, explicit contract impact references where applicable, idempotency and policy approval. A sold slot move without remedy is a hard `409`, not a warning the frontend may dismiss.

### 6.4 Campaign review and separate readiness states

```json
{
  "data": {
    "id": "01JCAMDEMO0000000000000001",
    "approval_state": "approved",
    "financial_state": "paid_verified",
    "delivery_state": "scheduled",
    "approved_creative_version": "01JCREATIVEVERSION00000001",
    "next_allowed_actions": ["request_pause"]
  },
  "meta": {"request_id":"req_demo","api_version":"v1","resource_version":8}
}
```

A payment confirmation cannot populate `approved_creative_version`. A submitted revised creative remains unapproved until exact-version moderation; the serving engine uses the approved immutable fingerprint, not the editable asset.

### 6.5 Public ad decision batch

```http
POST /wp-json/adpilot/v1/delivery/decisions HTTP/1.1
Content-Type: application/json
```

```json
{
  "published_layout_ref": "layout_current_hint",
  "page_context_ref": "public_page_context_reference",
  "requested_slots": [
    {"slot_id": "01JSLOTDEMO000000000000001", "render_instance_id": "ri_page_01_slot_001"},
    {"slot_id": "01JSLOTDEMO000000000000002", "render_instance_id": "ri_page_01_slot_002"}
  ]
}
```

```json
{
  "data": {
    "batch_ref": "db_opaque",
    "expires_at": "2026-10-12T00:01:00Z",
    "decisions": [
      {"slot_id":"01JSLOTDEMO000000000000001","render_instance_id":"ri_page_01_slot_001","result":"creative","decision_ref":"dr_opaque_signed","creative":{"format":"image","asset_url":"https://example.test/wp-content/uploads/safe-banner.webp","alt":"Sponsor banner","disclosure":"Advertisement"}},
      {"slot_id":"01JSLOTDEMO000000000000002","render_instance_id":"ri_page_01_slot_002","result":"empty"}
    ]
  },
  "meta": {"request_id":"req_demo","api_version":"v1","server_time_utc":"2026-10-12T00:00:00Z"}
}
```

**Example semantics:** `decision_ref` is a short-lived per-decision validation reference, not a proof of viewability, approval authority, billable event, payment, or audience identity. `asset_url` is a validated same-site example, not a license to fetch arbitrary remote scripts. The delivered creative must be revalidated against current kill-switch state according to the documented freshness envelope. One batch contains a bounded number of requested slots and no giant 500-slot response by default.

### 6.6 Browser measurement batch

```json
{
  "schema_version": "1.0",
  "events": [
    {
      "event_id": "evt_opaque1",
      "event_type": "rendered",
      "decision_ref": "dr_opaque_signed",
      "render_instance_id": "ri_page_01_slot_001",
      "occurred_at": "2026-10-12T00:00:05Z"
    },
    {
      "event_id": "evt_opaque2",
      "event_type": "visibility_milestone",
      "decision_ref": "dr_opaque_signed",
      "render_instance_id": "ri_page_01_slot_001",
      "occurred_at": "2026-10-12T00:00:07Z",
      "measures": {"visible_ratio": "0.60", "continuous_visible_ms": 1200}
    }
  ]
}
```

Respond `202 Accepted` when syntactically accepted for verification; do **not** report `billable: true` or invent final validated view counts in the ingestion response. Rejected or invalid events should be classified internally with privacy-preserving diagnostics, not leak per-decision verification logic useful to attackers.

### 6.7 Protected heatmap query (R2)

```http
GET /wp-json/adpilot/v1/admin/heatmaps/aggregates?page_ref=page_opaque&layout_version=lv_opaque&device_class=desktop&from=2026-10-01T00:00:00Z&to=2026-10-08T00:00:00Z HTTP/1.1
X-WP-Nonce: <wp-rest-nonce>
```

```json
{
  "data": {
    "layout_version":"lv_opaque",
    "device_class":"desktop",
    "metric":"click_density",
    "bins":[{"x_normalized":"0.250","y_normalized":"0.700","count":34}],
    "sample_count":1200,
    "suppression_applied":true,
    "last_aggregated_at":"2026-10-08T00:10:00Z"
  },
  "meta":{"request_id":"req_demo","api_version":"v1"}
}
```

Actual privacy thresholds, minimum cohort sizes, retained geometry and consent-gating policy require approved privacy specifications. Normal advertiser reporting must not expose this admin-only endpoint.

### 6.8 Export lifecycle and fresh download permissions

`POST /admin/exports` or org export submits a verified report definition and returns `202` with `{export_id, status:"queued"}`. `GET /exports/{id}` returns status for currently authorized owner/site/org. `GET /exports/{id}/download` reauthenticates and reauthorizes *at download time*, uses protected storage and secure content headers, and returns `410` after expiry. Do not return stable public uploads URLs or accept client-chosen server filesystem paths.

---

## 7. Mutation concurrency, idempotency and transactional semantics

### 7.1 Concurrency control classes

| Class | Applies to | Mandatory concurrency control |
|---|---|---|
| Editable configuration | Zone, layout draft, rate draft, org profile | `If-Match`/expected version, reject stale updates (`412`) |
| Inventory sale/hold | Quote accept, hold acquisition, consume into contract | DB transaction + deterministic locks + overlap/capacity recheck; idempotency |
| Money | Order creation, settlement, refunds and adjustments | Verified source evidence + ledger append + provider dedup + idempotent effects |
| Creative moderation | Exact creative version | Revision/digest and current-review state lock |
| Campaign state | Approval/schedule/pause/resume | Expected revision and current eligibility/stop version |
| Layout publish | Active pointer + sold-impact resolution | Locked current pointer, CAS and authoritative impact check |
| Export | Job request and protected retrieval | Idempotent job creation; fresh read/download authorization |
| Telemetry | Browser event ingestion | Bounded duplicate suppression; no synchronous financial posting |

### 7.2 Idempotency key lifecycle

1. Client creates an opaque collision-resistant key per *intended command*; reuses it after transport timeout with the exact same payload and actor/org/site context.
2. Server computes canonical request fingerprint and binds key to trusted site, actor, organization, route/operation, payload fingerprint and time window in durable idempotency storage (Document 08).
3. For a replay with the same bound fingerprint, return the original committed result or a stable processing reference. Never execute the side effect again.
4. Same key with different payload, tenant, actor or operation returns `409 adpilot_idempotency_conflict` without revealing a foreign operation's existence.
5. Concurrent retries synchronize on a persistent unique key/lock. Relying only on an in-memory cache is insufficient for financial commands.
6. Choose TTL based on the contractual action and incident/retry requirements, not a universal one-hour setting. Financial callback dedup evidence may require much longer retention than browser event dedup.
7. Outbox and job consumers also maintain independent idempotent processing cursors and provenance checks.

### 7.3 `If-Match` versus domain state

Passing `If-Match` does not mean that action is permitted. After checking entity revision, command handler still rechecks rights, contract period, financial state, active creative approval, inventory and policy. `If-Match` prevents lost updates; it does not enforce slot exclusivity across different reservations.

### 7.4 Hold acquisition and checkout correctness

- Hold creation performs site/organization checks, resolves authoritative quote, acquires sorted capacity locks, reads overlapping half-open intervals, checks resource policy and TTL, and commits hold plus outbox atomically when database transactions are supported.
- Checkout creation consumes a valid hold according to Document 04 state machine. Success of WooCommerce's checkout frontend is never independently treated as provider settlement; verified WooCommerce/provider state is the source.
- A webhook arriving after TTL or after a cancellation cannot resurrect a hold by inserting a reservation. Trigger the defined remedy/refund/manual review workflow.
- A payment retry must not charge twice or move money from one org/site order to another. Inconsistent evidence is quarantined and surfaced to finance with a correlation reference.
- If InnoDB transactions/locking are unavailable, disable unsafe paid exclusive inventory sale with an administrator diagnostic; no faux transaction guarantee via a normal WordPress transient.

### 7.5 Eventual consistency and response truthfulness

- `202` means an operation was accepted for later processing, **not** completed; include job status endpoint and next steps.
- Charts, aggregates, exports and delivery summaries may lag; expose `last_aggregated_at` or equivalent data-freshness indicator.
- Payment status in the advertiser portal is derived from verified source evidence, not user navigation to a thank-you URL.
- A currently suspended creative cannot be served merely because an API cache has a previous decision. Document and test the maximum eligibility freshness bound.
- Authorization revocation must be effective on the next authoritative request; cache invalidation may not extend access rights.

## 8. Authentication, authorization and tenant security

### 8.1 Browser authentication and CSRF

Authenticated WordPress admin, Gutenberg and same-site advertiser portal requests use standard WordPress cookie authentication plus REST nonce (`X-WP-Nonce` for cookie-backed REST calls). A nonce is not a single-use token, API key, OAuth proof or access-control substitute. Requests without the required session/nonce fail cleanly. For external integrations, use approved server-to-server credentials and scoped application-password/auth adapter policy over HTTPS; do not enable a global unauthenticated admin API. Provider callbacks are authenticated by their own protocol, not site cookies.

### 8.2 Server authorization sequence

```text
1. Resolve trusted current WordPress site/network context.
2. Resolve real actor or verified service principal; never trust client role/site hints.
3. Match route to registered operation policy; unknown operation => deny.
4. Check feature/release gate (IC-024) and required consent/purpose policy.
5. Look up resource through site-constrained repository, with org scope where applicable.
6. Evaluate current WordPress capability OR current active organization membership,
   and then object ownership, workflow state, separation-of-duties and elevated controls.
7. Validate DTO shape/fields and domain transition; recheck authoritative states in TX.
8. Perform application use case, append audit/outbox, serialize allowlisted projection.
9. Mark sensitive data `private, no-store` and prevent intermediary caching.
```

Document 05 capability catalog is canonical: e.g. `adpilot_manage_inventory`, `adpilot_publish_layout`, `adpilot_moderate_creatives`, `adpilot_view_finance`, `adpilot_adjust_finance`, `adpilot_manage_privacy`, `adpilot_view_restricted_analytics`, `adpilot_view_replay`, `adpilot_manage_integrations`. Organization operations such as `adpilot_purchase_inventory` must evaluate membership and organization ownership, never be granted as unlimited site-wide permissions.

### 8.3 Explicit protected resource ownership checks

| Resource | Authoritative ownership chain | Negative assertion |
|---|---|---|
| Organization | trusted site → active membership/authorized site admin | Foreign org ID returns denial/indistinguishable 404 |
| Campaign | site → organization → campaign | Campaign ID cannot bypass organization membership |
| Creative and review | site → organization → creative/version; moderator review site policy | Advertiser cannot approve own creative |
| Quote/hold/order | site → organization → quote/hold/order | Foreign org invoice or payment not visible |
| Layout and zone | current site → published/draft ownership → capability | Non-editor shortcode cannot mutate drafts |
| Financial remedy | current site → order → approved workflow → independent policy | Operator cannot self-approve protected refund |
| Heatmap | site + privacy purpose + restricted role + geometry version | Publisher general analyst cannot see raw replay |
| Export | site → creator/scope → fresh permission and retention state | Revoked permission makes old download inaccessible |
| Webhook subscription | site → integration administrator → validated endpoint | Org advertiser cannot register arbitrary outgoing webhook |
| Job | verified service principal → site-scoped provenance → target current state | Job ID cannot cause cross-site action |

### 8.4 Multisite rule

Use current site-specific `$wpdb->prefix`/site-bound repository and explicit `site_id` predicates where the schema requires them. Never trust `blog_id` from a browser query as a site-switching grant. If internal code switches sites, it requires an authorized target site, `try/finally` context restoration, fresh site-specific dependencies, fresh authorization and cache namespace. A WordPress super admin does not automatically receive cross-site AdPilot personal-data access or business contract exceptions. R4 network administration requires separate contracts and approval.

### 8.5 API authentication matrix

| Caller | Authentication | Effective privilege | Constraints |
|---|---|---|---|
| Anonymous marketplace visitor | none | published catalog only | throttling + no private data |
| Visitor public ad SDK | decision-bound opaque token + abuse controls | bounded event/creative usage, no business mutations | no inference of valid purchase |
| Advertiser portal | WordPress cookie/nonce and current org membership | current org approved operations | object and finance scope |
| Publisher admin | WP session/nonce and site capability | site authorized policy actions | state and elevated approval |
| Gutenberg editor | WP session/nonce, content editing + AdPilot block policy | safe preview/selection | no impression billing |
| WP-CLI | trusted administrator/service execution context | explicitly authorized site action | audited, no actor spoofing |
| Queue worker | approved internal service principal and job provenance | narrow idempotent job | recheck current target state |
| External payment webhook | authenticated provider signature/verification | only verified payment ingestion | mapped site/order + dedup |
| Outbound webhook subscriber | receiver verifies AdPilot signature | consumption of sanitized event | receiver learns no admin powers |

### 8.6 Authorization caching and response leakage prevention

- Permission-sensitive queries avoid shared caches. Keys include trusted site and authorized scope, plus revision when appropriate.
- Cached org membership data must have revocation invalidation/current check; stale menu affordances in React never authorize endpoint requests.
- ETags, error messages, cursor tokens and timing details must not reveal foreign org or hidden resource existence.
- REST preloading and Gutenberg editor data must not accidentally serialize private visitor telemetry, credentials, financial information or other-org campaigns.
- The export response MUST be recalculated against current membership/capability on download, not only when the job was queued.

---

## 9. REST controller and PHP integration contracts

### 9.1 Required controller layering

```text
RestController (adapters/WordPress)
  -> RequestSchema + AuthenticatedRequestContextResolver
  -> AuthorizationPolicy (Document 05)
  -> Application Command/Query (Document 07)
  -> owner repository + transaction (Document 08)
  -> ResponseDTO Serializer + WP_REST_Response / WP_Error
```

Controllers must not write cross-module tables, interpolate untrusted SQL, call private methods on another module, or perform money arithmetic using PHP floats.

### 9.2 Example WordPress route registration (illustrative, not production complete)

```php
<?php
namespace AdPilot\Presentation\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

final class ZoneController
{
    public function __construct(
        private readonly ZoneQuery $zones,
        private readonly AuthorizationPolicy $authorization,
        private readonly RequestContextResolver $contexts
    ) {}

    public function register(): void
    {
        register_rest_route('adpilot/v1', '/admin/zones/(?P<id>[A-Z0-9]{26})', [
            'methods'             => 'GET',
            'callback'            => [$this, 'show'],
            'permission_callback' => [$this, 'canShow'],
            'args'                => [
                'id' => [
                    'required' => true,
                    'type'     => 'string',
                    'pattern'  => '^[A-Z0-9]{26}$',
                ],
            ],
        ]);
    }

    public function canShow(WP_REST_Request $request): bool|WP_Error
    {
        // Implementation SHALL bind current site + actor, resolve the scoped
        // resource and run Document 05 object policy; a matching ID is not enough.
        return $this->authorization->checkZoneRead(
            $this->contexts->forRequest($request),
            (string) $request['id']
        );
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        // Only an authorized, allowlisted DTO is serialized here.
        return $this->zones->restProjection(
            $this->contexts->forRequest($request),
            (string) $request['id']
        );
    }
}
```

`ZoneQuery`, `AuthorizationPolicy`, and `RequestContextResolver` here are proposed interfaces to be imported/implemented; the example is intentionally incomplete and MUST NOT be pasted as-is as a production route. All custom routes register on `rest_api_init` with a permission callback. Public callbacks explicitly use an allowlisted public handler and bounded input validation rather than omitting authorization.

### 9.3 Schema and OpenAPI source of truth

Maintain `docs/api/openapi/adpilot-v1.yaml`, schema components and shared TypeScript API types. The contract compiler must map WordPress REST args to the approved subset of JSON Schema, with server-side semantic validators for domain constraints that JSON Schema cannot express (interval overlap, monetary proof, valid state transition, tenant access, contractual layout impact).

**Build gate:** OpenAPI must not claim unsupported server validation (e.g. full draft 2020-12 JSON Schema is not automatically provided by native WP REST). Explicitly document differences and add validator contract tests. API schemas generate TypeScript clients only after their compatibility and security contracts pass review.

### 9.4 Input, output and sanitization responsibilities

- **Input adapter:** validate types, lengths, enumerations, media references, dates and unknown field policies; reject client-provided authority fields.
- **Application handler:** enforce business invariants, state and authorization; sanitize URLs and controlled HTML creatives using type-specific policies.
- **Output serializer:** select allowed fields by viewer role and purpose; format exact amounts and UTC timestamps; never leak internal objects.
- **HTML/theme layer:** escape all dynamic strings for the final output context even after REST validation; REST JSON serialization is not HTML sanitization.
- **Data access:** use prepared SQL and repository contracts. Server-side allowlist values for sort and field projection selectors.

### 9.5 Gutenberg integration contracts

`BLOCK-001..004` return editor-safe projections for four blocks: `adpilot/ad-slot`, `adpilot/ad-zone`, `adpilot/ad-repeater`, `adpilot/ad-showcase`. Preview rendering is never a paid impression. Block attributes reference stable zone/slot IDs, not an injected campaign's approval/payment state, and cannot invoke privileged query endpoints merely because the editor is logged in.

`BlockPlacementValidationRequest` should contain editor post/template context and candidate block attributes. The server derives edit rights and permitted target contexts using WordPress capability checks and current AdPilot zone policies. Unknown attributes and unsupported combinations produce actionable field errors. If 500 slots are configured, the editor receives a bounded preview with summary (`configured_slot_count`, `visible_preview_slot_count`, `validation_warnings`) rather than rendering 500 reactive controls.

### 9.6 Theme PHP functions, shortcodes and hooks

Public PHP functions (`adpilot_render_slot`, `adpilot_render_zone`, etc.) and `[adpilot_slot]`, `[adpilot_zone]`, `[adpilot_repeater]`, `[adpilot_showcase]` consume the same published layout/eligibility/read model and output safe markup. They do not use admin REST credentials and do not provide private finance/heatmap data. WordPress shortcode filters and hook callbacks receive sanitized, typed inputs; extension hooks may further restrict policy but cannot force an unapproved creative or override contractual allocation.

### 9.7 Frontend fetch/retry behavior

- Typed API client injects REST nonce only on same-origin authenticated calls and refreshes after session expiry.
- Client distinguishes `400/422` validation, `401` session expiry, `403` denied, `404` inaccessible/missing, `409/412` stale business/resource version, `429` bounded retry, `503` service degradation.
- Optimistic UI updates may be used for purely cosmetic drafts, not for paid booking, approval, refunds, financial ledger or layout publication.
- After ambiguous network failure, retry a mutation using the **same** idempotency key and payload; avoid generating a new purchase command unless authoritative state confirms absence.
- Fetch cancellations, navigation, and stale response races must not overwrite newer editor state or cause duplicate commands.
- UI must present measured vs estimated values and explicit last-updated time in finance/analytics screens.

---

## 10. External integrations and payment provider ingress

### 10.1 WooCommerce as the R1 commerce adapter

AdPilot remains loadable without WooCommerce. If WooCommerce is installed and approved, AdPilot maps its own advertising order/hold identifiers to WooCommerce orders using official WooCommerce CRUD/HPOS-safe APIs and supported lifecycle hooks, not raw postmeta assumptions. Provider/commerce evidence enters a dedicated trusted adapter, which verifies the WordPress site/order/currency/amount/mapped state before any AdPilot finance or reservation transition. The customer-facing checkout return URL is never payment evidence.

### 10.2 Preferred in-process integration versus generic webhook endpoint

For WooCommerce installed on the **same** WordPress site, prefer in-process supported WooCommerce order/transaction events plus authoritative WooCommerce CRUD state re-read. Do not expose an unnecessary external WooCommerce webhook receiver merely because outbound WooCommerce webhooks exist. If a remote gateway or external WooCommerce site is explicitly supported, expose a provider-specific callback route with signature verification and site/order mapping, feature gated and tested.

### 10.3 Provider-specific callback profiles

Each provider profile SHALL document:

- Exact callback path, methods, content type, byte limit and authentication mechanism.
- HMAC/signature scheme, canonical signed bytes, timestamp tolerance, key rotation and constant-time comparison.
- Provider transaction identifiers, event dedup semantics, idempotency retention and replay tests.
- How authoritative settlement is verified (including provider server API lookup where needed).
- Site/tenant/order mapping and exact currency/amount requirements.
- Handling of asynchronous authorization, capture, settlement, chargeback, partial refund and reversal events.
- Safe failure codes and retry behavior, with no acknowledgement before durable receipt when required.
- Secret storage and strict redaction.

Never invent provider signatures, callback names, event schemas or replay windows. An external integration becomes supported only after its official API/profile is verified and stored in its adapter contract.

### 10.4 Inbound callback workflow

```text
Receive bounded raw bytes
  -> verify provider-specific signature/provenance BEFORE trusting JSON
  -> parse and validate documented event schema
  -> resolve trusted site + mapped provider account + order
  -> idempotent store of external event ID/fingerprint
  -> verify authoritative transaction/currency/amount/state
  -> enqueue governed commerce application command
  -> append finance/hold/reservation effects once, audit and outbox
  -> send provider-specific acknowledgement after durable receipt
```

Callbacks never accept `paid=true` supplied in the URL/body without verification. A remote callback must not be able to re-enable suspended advertising, approve a creative, or resurrect an expired hold.

### 10.5 WooCommerce integration failure matrix

| Condition | Domain response | Advertiser UI |
|---|---|---|
| WooCommerce disabled at runtime | Disable payment initiation through WC; remain bootable | Show unavailable checkout and safe alternatives |
| Verified payment, creative not approved | Finance updated, delivery remains blocked | “Payment received; awaiting review” |
| Payment reported from wrong site/order | Quarantine and investigate | Do not mark paid |
| Duplicate webhook/event | Return safely; do not append duplicate ledger | Show unchanged confirmed result |
| Payment after expired hold | No inventory resurrection; start remedy | Explain review/refund route |
| Gateway timeout after checkout | Inspect authoritative order/provider state | Pending verification, not paid/failed guess |
| Partial refund/dispute | Append governed finance facts and update eligibility per contract | Clear financial and delivery statuses |
| Order deleted externally | Quarantine/reconciliation incident; no silent records deletion | Safe support message |

---

## 11. Outbound webhooks: subscriptions, signing, delivery and recovery

### 11.1 Purpose and data classes

Outbound webhooks push **committed, sanitized business facts**, not raw click streams, raw heatmap trajectories, personally identifying visitor sessions, credentials, server internals or mutable domain entity dumps. Delivery consumes `IC-021 IntegrationEvent` from the transactionally durable outbox in Document 08. Webhook delivery does not block committing purchases or layouts.

### 11.2 Supported event topic candidates (feature/release gated)

| Topic | Release | Typical payload | Sensitive fields excluded |
|---|---|---|---|
| `organization.verified` | R1 | Org public ref, status, occurred_at | Verification documents, contact secrets |
| `organization.suspended` | R1 | Org public ref, suspension reason code | Private investigation details |
| `campaign.submitted` | R1 | Campaign/org refs, stage, timestamp | Creative untrusted HTML, billing account data |
| `campaign.approved` | R1 | Campaign ref, reviewed creative version ref | Private reviewer notes |
| `campaign.paused` | R1 | Campaign ref, stop version, reason code | Visitor-level signal |
| `creative.reviewed` | R1 | Creative version ref, safe decision, timestamp | File content and review confidential fields |
| `layout.published` | R1 | Published layout version, affected zone IDs | Raw page HTML, private contracts |
| `inventory.hold.created` | R1 | Opaque hold ref, expiry, safe status | Protected slot/customer details unless approved |
| `inventory.reservation.confirmed` | R1 | Contract ref, timeframe, permitted line summary | Raw payment provider evidence |
| `order.paid_verified` | R1 | Order ref, money summary only if entitled | Payment card info, provider secrets |
| `order.refunded` | R1 | Order ref, refund summary as permitted | Personal payment data |
| `export.ready` | R1 | Export job ref and status | Public downloadable file URL |
| `metric.aggregate.ready` | R2+ | Scope, metric definition, freshness | Raw event data, visitor identifiers |
| `experiment.completed` | R3 | Experiment ref, safe evaluation status | Individual visitor assignments |

Subscription creation chooses a topic allowlist appropriate to the authenticated site's integration policy, and may include limited, approved fields/filters. Arbitrary fields, wildcard raw tables, server metadata or general SSRF/testing URL mechanisms are prohibited.

### 11.3 Outbound signature protocol (AdPilot proposal, not a third-party standard)

A webhook request SHALL include:

```http
POST /receiver HTTP/1.1
Content-Type: application/json
X-AdPilot-Webhook-Id: wh_del_opaque
X-AdPilot-Webhook-Event: campaign.approved
X-AdPilot-Webhook-Timestamp: 1791763200
X-AdPilot-Webhook-Key-Id: key_2026_10
X-AdPilot-Webhook-Signature: v1=<lowercase-hex-hmac-sha256>
```

Define the signed message as UTF-8 bytes:

```text
v1
{unix_timestamp_seconds}
{webhook_delivery_id}
{raw_request_body_bytes}
```

The sender computes `HMAC-SHA256(secret_for_key_id, signed_message)` and encodes as lowercase hex. The receiver verifies key ID, supported version, bounded timestamp skew, immutable delivery ID dedup and signature using constant-time comparison. Reuse the identical event payload but produce a new delivery attempt ID and signature for each attempt, while preserving the stable committed event ID in the JSON body. Timestamp skew and dedup retention limits are configurable policy values and MUST be finalized and tested; don't invent specific guarantees.

Do not sign JSON after reserialization at the receiver; verify the raw HTTP body bytes. Provider inbound webhooks use **their provider's** documented signature scheme, not this proprietary outbound format.

### 11.4 Proposed event payload

```json
{
  "event_id": "evt_business_opaque",
  "event_type": "campaign.approved",
  "event_schema_version": "1.0",
  "occurred_at": "2026-10-12T00:00:00Z",
  "site_public_ref": "site_opaque",
  "data": {
    "campaign_id": "01JCAMDEMO0000000000000001",
    "creative_version_id": "01JCREATIVEVERSION00000001",
    "approval_state": "approved"
  }
}
```

All payload fields come from allowlisted committed projections and current subscription entitlements. If the integration loses permission before send, the delivery is blocked or minimized according to the revised scope policy.

### 11.5 SSRF prevention and destination policy

- HTTPS required in production; no arbitrary `http://`, local files, IP literal, localhost, private/link-local/loopback/multicast/metadata ranges or unix sockets.
- Resolve DNS and reject private/special destinations, including IPv4/IPv6 and mixed encodings; defend DNS rebinding by validating effective connected IP and TLS hostname with the chosen HTTP client facilities.
- Redirect following is disabled for outbound webhook delivery (or restricted with equivalent revalidation at **every** hop); reject credentials in URL and disallowed ports/schemes.
- Apply strict timeout, response size, concurrency and per-subscription rate limits; no response body echoed to normal advertisers.
- Verify endpoint ownership through an explicit challenge/confirmation before enabling high-trust outbound delivery, where policy requires it.
- Secret values are one-time-display administrative configuration, encrypted/protected at rest according to approved hosting policy; never returned by ordinary `GET /admin/integrations`.

### 11.6 Delivery attempts and retry semantics

1. Transaction commits domain outbox event; a queue job resolves active subscriptions and builds a minimal event projection.
2. Dispatcher generates a unique delivery attempt and signed request; writes attempt reference and redacted metadata.
3. Treat documented success status (normally a 2xx HTTP result) as accepted delivery; distinguish temporary (timeouts, 429, 5xx) and permanent errors (for example, invalid configured endpoint or persistent 4xx) under approved policy.
4. Retry with exponential backoff and jitter, bounded attempts, and a dead-letter/quarantine state. Preserve stable event ID to enable receiver dedup.
5. Delivery logs show timestamp, status, duration, limited redacted failure details and next attempt. No request secrets, full credentials, PII-rich payload or private response bodies in normal logs.
6. Admin may retry a **failed authorized delivery** through `OPS-013`; retries must not regenerate the underlying domain business fact.
7. If a subscriber is revoked/suspended or tenant deleted, pending jobs recheck entitlement before sending.
8. Provide receiver documentation with canonical-message test vectors generated and reviewed during implementation.

### 11.7 Webhook security acceptance

- Payload tampering, timestamp replay, wrong key ID, wrong site, incorrect body canonicalization, altered HTTP method, reused delivery IDs and revoked subscriptions are all denied/handled predictably.
- DNS/redirect tests cover private IPv4/IPv6, internal hostnames, metadata-service hosts and DNS rebinding fixtures.
- Secret rotation exercises overlap, key revocation and delayed retries; expired keys fail.
- Delivery acknowledgments and idempotency do not imply downstream recipient action succeeded; receiver is responsible for its own transaction consistency.

---

## 12. Public ad delivery and measurement API abuse controls

### 12.1 Decision request is not authenticated business intent

A visitor may request eligible ads from a public page. The server verifies currently published site/page layout and slot association, enforces request batch cap and rate budget, computes permitted context, selects approved creatives with current contractual entitlement and returns a short-lived decision. A public request's page context, referrer, device flags, frequency storage and privacy signal are individually untrusted and only considered under defined server verification/consent policies. Do not accept client-provided `ad_count=500` as entitlement to receive 500 live ads in one response.

### 12.2 Opaque decision reference

A decision reference binds, as warranted by privacy and fraud policy: trusted site ID, approved creative/version, logical slot, per-render instance, issued/expiry time, event schema version and a server-managed nonce/bounded redemption state. Cryptographically protected tokens or stored opaque handles must not expose private campaign IDs, order details, organization membership or audience identity. A valid decision reference is **not** proof the creative was rendered or seen.

### 12.3 Public click routing

The click handler validates the decision, active creative/destination policy, allowed HTTPS URL, anti-abuse and creative-specific redirect restrictions. A validated click event may be recorded without delaying a safe redirect. Unapproved destinations, dangerous schemes and unrestricted URL parameters are rejected. Preserve user privacy by stripping sensitive tokens and excluding unintended tracking data from redirects. A plain client-side `click=true` claim does not establish a qualified click.

### 12.4 Measurement ingestion

- Limit JSON byte size, batch event count, nesting depth, event frequency and per-decision allowance; choose tested, documented defaults in later implementation configuration.
- Bound time skew and token expiry, reject unknown event types/schemas and impossible geometry values.
- Validate event association with previously issued decisions and render instances, while recognizing client measurements cannot prove intent or human identity alone.
- Apply bot/abuse filtering, duplication controls, consent policy and privacy sampling.
- Process in async queue where viable and return ingestion acceptance, not final billing outcome.
- Under backpressure drop or sample optional behavioral data before undermining core ad delivery; record measurement incompleteness and do not silently bill unobserved events.
- Do not log raw passwords, payment card data, form field values, or unrestricted URLs.

### 12.5 Cache behavior and long pages

Public HTML may contain many lightweight placeholders. The frontend loads decisions as slots approach the viewport; use bounded batched requests, shared observers and adaptive retry. A CDN can cache static creative assets and published safe catalog fragments, but not privileged responses or improperly personalized decisions. Invalidation is tied to campaign stop version, published layout revision, creative approval and contract validity. Distinguish actual rendered, measurable, viewable and billable impressions in reports.

### 12.6 Explicit failure/empty policies

Return empty decision when no eligible campaign exists. For transient delivery outages prefer a safe noninteractive placeholder or approved house-ad fallback rather than an unreviewed cached paid creative. JavaScript disabled: theme-rendered safe placeholders or SSR approved policy may apply, but do not fabricate browser viewability. Analytics disabled: rendering still works, with absent/incomplete metrics clearly labeled.

## 13. Frontend contract: screens, workflow states and human factors

### 13.1 UI-to-API mapping

| UI surface | Minimum API calls | Pending/failed UI contract |
|---|---|---|
| Public marketplace | `CAT-001..007` | No hidden placements; estimate disclaimers; filter empty state |
| Advertiser onboarding | `ORG-001..013` | Invitation verification, role failure, org switch without data leak |
| Campaign builder | `CAM-001..007`, `CRE-001..005`, `PRC-005..006` | Autosaved draft; field errors; no optimistic purchase truth |
| Checkout/availability | `CAT-005`, `PRC-005..006`, `RES-001..003`, `ORD-001..004` | Hold countdown from server; expiry/price change/retry recovery |
| Advertiser campaign dashboard | `CAM-001/003`, `ORG-002`, `REP-004` | Separate financial, moderation, delivery states; data freshness |
| Advertiser billing | `ORD-002..006`, `REP-006..008` | Protected invoices, retry export, 403 on revocation |
| Admin campaign moderation | `CAM-008..013`, `CRE-006..009` | Exact creative version, no self-approval bypass, audit references |
| Layout Studio | `INV-001..014`, `LAY-001..016` | 500-slot summary and paged inspector, contract-impact review |
| Gutenberg block editors | `BLOCK-001..004` | Accessible preview with no payable impression emission |
| Site performance/analytics | `REP-001..003`, `MET-003`, `HEAT-001..005` (R2) | Sample count, denominators, geometry and freshness |
| Site finance | `ORD-007..012` | Independently verified payment, refund and ledger state |
| Site integrations | `OPS-008..013` | Masked secrets, endpoint verification, safe retry |
| Site diagnostics | `OPS-005..007` | Redacted logs, no sensitive client-facing payload |
| Public page ad runtime | `DEL-001..003`, `MET-001..002` | Non-blocking progressive display, graceful empty/failure |

### 13.2 UX state contract for every asynchronous operation

Every affected screen SHALL implement, as appropriate, `initial`, `loading`, `empty`, `ready`, `validating`, `saving`, `success`, `validation_error`, `permission_denied`, `conflict`, `network_error`, `rate_limited`, `partial_data`, `stale_data`, `offline`, and `feature_disabled` states. State text describes a verifiable action, e.g., “Payment status is being verified” rather than “Payment successful” when only checkout redirect occurred. Retain draft input on validation errors and nonfatal network failures. Respect reduced motion, keyboard navigation and screen-reader announcements for status updates.

### 13.3 Conflict UX patterns

- **Layout CAS conflict:** show last saved/published revision, list impacted zones and sold slots, let user reload/reapply changes; never blind overwrite.
- **Booking conflict:** show new availability and quote options; do not retry the exact unavailable slot in a loop.
- **Payment ambiguity:** show pending verification and safe status refresh; reuse original idempotency intent.
- **Creative review denied:** show immutable version, rejection reason and revision workflow; don't imply payment refund occurred.
- **Membership revoked:** clear org-specific client caches, deny protected download, show reauthentication/permission state.
- **Heatmap geometry mismatch:** prohibit silent overlay; prompt matching layout version/filter.
- **Async export:** show queued/running/ready/failed/expired states and current authorization on download.

### 13.4 Typed API client responsibilities

Use a single TypeScript client layer with runtime shape checks at untrusted boundaries, request cancellation, versioned DTOs, test fixtures, contextual error mapping, exact-money helpers (never JS Number for amounts beyond precision), and stable cursor utilities. Hooks/selectors use scoped query-cache keys containing trusted org/site and feature version; clear caches when switching organization, logging out or losing access. Sensitive route responses use `no-store` browser policy and do not persist to localStorage by default.

### 13.5 Frontend performance and accessibility

The Layout Studio loads a container summary then pages slot details as needed. It must not assume the backend can serialize all 1,000 slots in one synchronous request. Virtualized administrative tables have accessible alternatives; public scroll flow and heatmap coordinates cannot be blindly virtualized. REST requests should coalesce duplicate reads, cancel stale requests, batch related decisions, avoid thundering herds on scrolling, and keep the public bundle independent of React dashboard chunks.

---

## 14. API observability and operations

### 14.1 Required measurements

Track aggregate endpoint status/latency by plane and route template, request size rejections, authorization denials by class (not private object name), rate limiting, schema failures, idempotency replays/conflicts, booking collision rate, stale ETag rate, decision batch utilization, collector event rejection category, queue lag, webhook delivery retries, and export failures. Correlate with generated request ID and audited domain command ID. Do not log raw visitor sessions, exact passwords, private URLs, payment secrets or full webhook content.

### 14.2 Reliability and service objectives

Set numeric availability/latency budgets only after representative WordPress hosting benchmarks and Product/DevOps sign-off. Distinguish low-latency public decision APIs from slow authenticated reporting queries. Response `503` must convey safe retry metadata without revealing dependencies. A degraded measurement pipeline should not stop core public ad rendering; a degraded reservation/payments pipeline must stop unsafe purchases rather than guess entitlement.

### 14.3 Deployment and compatibility matrix

Test registered routes with site and pretty-permalink configurations; WordPress REST base URL from `rest_url()`. Include WordPress multisite site switching, object cache/CDN, REST cookie nonce lifecycle, PHP exceptions, WooCommerce HPOS enabled/disabled/missing, different timezones, RTL/admin localization, varying MariaDB/MySQL versions and hardened hosting where headers/body limits differ. Preserve signed raw webhook body bytes through reverse proxies; proxies stripping signatures or modifying body must trigger a supported diagnostic, not bypass authentication.

### 14.4 API version and deprecation lifecycle

- Public `v1` additive fields may be introduced with stable defaults and schema evolution tests; do not remove or reinterpret existing fields without a major version migration.
- New enum values are a compatibility risk for strict frontend switch statements; define tolerant display behavior with safe fail-closed semantics for privileged actions.
- Use explicit `Sunset`/deprecation communication when old routes must be retired, plus upgrade tooling for existing client integrations.
- Webhook topic payload schema versions evolve separately from REST major versions. Old integration consumers receive compatibility metadata and test vectors.
- Internal `IC-*` contract DTO versioning remains independent of HTTP representation; controller maps contracts and does not leak domain entity serialization.

### 14.5 Documentation and SDK artifacts

Release with OpenAPI YAML/JSON, route inventory, PHP integration reference, webhook verification guide and signature test vectors, generated TypeScript interfaces, API changelog, Postman/Bruno-style open-source-compatible examples, CLI smoke tests, redacted error catalog, and controlled example payload fixtures. Only mark an endpoint “implemented” when its routes, policy, DTO, tests and documentation match the verified build.

---

## 15. API security threat model and denial-first controls

| Threat | Entry point | Mandatory defense and verification |
|---|---|---|
| IDOR / cross-organization read | Campaign, creative, invoice by guessed ID | Scoped repository and membership recheck, indistinguishable object 404 |
| Cross-site access | Multisite paths or queue site hints | Trusted site resolver, per-site table/cache namespace, target approval |
| Privilege escalation | Membership, capabilities, admin settings | Operation policy, non-escalation and independent approvals |
| CSRF | Cookie-backed REST mutations | WordPress REST nonce + authenticated session + capability checks |
| Authentication replay | App credentials, provider signature | Provider/credential replay policy, nonce not mistaken for one-use proof |
| Mass assignment | PATCH bodies | Strict writable-property allowlist; reject `is_paid`, `is_approved`, tenant and role fields |
| SSRF | Outbound webhook URL | HTTPS/allowlist policies, DNS/IP/redirect revalidation, timeouts |
| Open redirect | Ad click | Immutable approved destination and safe scheme policy |
| Creative XSS | Uploaded HTML/SVG/link | Restrict formats, sanitize, sandbox when supported, CSP-compatible rendering |
| Payment spoofing | Checkout redirect / webhook | Verified WooCommerce/provider evidence, order/site/amount/currency mapping |
| Hold double spend | Concurrent requests | InnoDB row locks, interval overlap, idempotent durable commands |
| Replay of event tokens | Browser measurement | Expiry, decision association, event dedup, fraud qualification |
| Viewability inflation | Public client | Qualify measurements, no direct billing trust, report limitations |
| Cache scope leak | Reports, org switching | Scoped private caches and revocation checking |
| Export leak | Download URL, expired link | Protected stream, fresh rights and retention check |
| Timing/resource enumeration | Unauthorized lookup | Safe 404, bounded response metadata, rate limits |
| Abuse/DoS | Public decision and collector | Body/slot limits, queue budgets, throttling, circuit breaker |
| Request smuggling / proxy mismatch | Webhook verification | Raw bytes, method/header checks, infrastructure tests |
| Stale layout serving | Published pointer changes | Signed/versioned reference and current entitlement check |
| Data reidentification | Heatmap small cohorts | Aggregation suppression, privacy authorization and retention |

**Review rule:** A security/privacy block cannot be overridden by a frontend switch, plugin filter, site administrator shortcut or commercial rate rule. Any nonstandard exception requires upstream policy amendments and approved ADR with executable negative tests.

---

## 16. Automated API verification catalog

All tests below are **specified**, not executed. The `API-AT-###` namespace is supplementary and must not replace `AT-*`, `AR-T-*`, `D05-T-*`, `DM-AT-*` or `DB-AT-*` of Documents 02–08. “Given/When/Then” oracles must be implemented with realistic fixtures (two advertisers, two sites, overlapping holds, competing revisions, different consent states, mixed devices).

### 16.1 Contract and HTTP

| ID | Given | When | Then / invariant |
|---|---|---|---|
| `API-AT-001` **Public catalog safe projection** | anonymous visitor requests published placements | inspect response and query filters | only public published fields, no org private details or raw visitor data |
| `API-AT-002` **Valid authenticated API access** | signed-in permitted publisher with valid REST nonce | read inventory list | 200 scoped items with stable request metadata |
| `API-AT-003` **Missing cookie/nonce** | cookie-backed mutation without valid nonce | submit a new zone | request denied, no side effect |
| `API-AT-004` **Foreign organization resource** | manager of org A guesses org B campaign ID | GET org B campaign | no object information disclosed |
| `API-AT-005` **Unknown enum property** | caller sends unsupported sort or format | GET collection | 400/422 schema-safe error without SQL interaction |
| `API-AT-006` **Mass assignment blocked** | advertiser includes is_paid and approved fields | PATCH campaign | protected fields rejected/ignored under fixed policy, no status change |
| `API-AT-007` **Cursor scope tamper** | user changes cursor tenant/filter bytes | load next page | cursor rejected without foreign items |
| `API-AT-008` **Deterministic pagination** | mutable list has same timestamp records | paginate entire dataset | no duplicate or omitted stable items under documented cursor consistency |
| `API-AT-009` **Sensitive cache policy** | authorized finance query served behind CDN | inspect headers | private no-store and no cross-user reuse |
| `API-AT-010` **WordPress JSON errors** | invalid resource mutation | inspect error | native stable WP REST code/message/data.status shape |
| `API-AT-011` **OpenAPI route alignment** | registered route inventory extracted | compare schema and source | exact method/path/schema/security/release agreement |
| `API-AT-012` **Multiple locale output** | same data requested across locale switches | inspect amounts/time/errors | exact values and UTC instants preserved; user text localized where enabled |

### 16.2 Inventory/layout/Gutenberg/theme

| ID | Given | When | Then / invariant |
|---|---|---|---|
| `API-AT-013` **Bulk inventory server limit** | 500 stable slots generated | page GET slots | bounded cursor pages with stable slot keys |
| `API-AT-014` **Duplicate slot generation** | same generator job retried | materialize same sequence | no new duplicate stable logical IDs |
| `API-AT-015` **Wrong layout version** | editor uses stale If-Match | PATCH draft | 412 without overwriting other changes |
| `API-AT-016` **Commercial layout protection** | paid sold footer slot moved | publish layout without approved remedy | 409 and unchanged active pointer |
| `API-AT-017` **Publish transaction race** | two publisher requests race to publish | submit both with same expected pointer | at most one succeeds; pointer/outbox atomic |
| `API-AT-018` **Preview no billing** | editor renders 200-slot footer preview | inspect event counters | zero billable/production viewability |
| `API-AT-019` **Block attribute tampering** | editor supplies disabled zone or arbitrary PHP | validate/preview block | deny unsafe binding, safe editor error |
| `API-AT-020` **PHP/shortcode parity** | same zone rendered through 3 interfaces | compare eligible slot set | same business eligibility and distinct render occurrences |
| `API-AT-021` **Theme duplicate binding** | template invokes zone twice intentionally | render page | distinct render instance IDs with explicit duplication policy |
| `API-AT-022` **Responsive 4/100/196/200 layout** | page has 500 configured slots | scroll desktop and mobile | bounded decisions, no eager 500 requests, correct responsive placement |
| `API-AT-023` **No theme override** | theme requests ad with private finance parameter | invoke shortcode/PHP | only safe render projection |

### 16.3 Campaign/commerce/concurrency

| ID | Given | When | Then / invariant |
|---|---|---|---|
| `API-AT-024` **Separate paid/approved/delivery** | payment succeeds before creative review | inspect campaign and decisions | paid state, unapproved creative not served |
| `API-AT-025` **Immutable creative moderation** | creative link changed after approval | attempt delivery new version | not served until exact version approved |
| `API-AT-026` **Exclusive slot race** | two orgs acquire same slot/time simultaneously | POST holds concurrently | one allocation succeeds, other conflicts |
| `API-AT-027` **Half-open slot intervals** | first reservation ends when second starts | book adjacent intervals | valid adjacent booking under policy |
| `API-AT-028` **Expired hold late payment** | signed payment after hold TTL | process provider fact | no revival/overselling; remedy workflow |
| `API-AT-029` **Idempotent order timeout** | checkout API times out after commit | retry same key/payload | one order and stable result |
| `API-AT-030` **Idempotency misuse** | same key new amount or tenant | repeat command | 409 no mutation and no foreign information |
| `API-AT-031` **Duplicate provider notification** | same verified provider event twice | ingest callback twice | one durable transition and ledger effect |
| `API-AT-032` **Wrong amount/currency** | provider event mismatches order | ingest callback | quarantine and no paid entitlement |
| `API-AT-033` **Gateway return spoofing** | attacker calls thank-you URL | inspect financial state | no paid promotion |
| `API-AT-034` **Rejected paid creative** | moderator rejects fully paid campaign | inspect UI/orders | revision or remedy path; no hidden fund retention |
| `API-AT-035` **Unapproved refund adjustment** | AdOps tries finance adjustment | POST remedy execute | denied; unchanged ledger |
| `API-AT-036` **Campaign emergency stop** | authorized emergency stop after cache fill | request decisions | no serving beyond reviewed maximum freshness |
| `API-AT-037` **Double command replay** | two concurrent identical state commands | submit idempotency key | no duplicate state audit/notification effect |

### 16.4 Public delivery/measurement

| ID | Given | When | Then / invariant |
|---|---|---|---|
| `API-AT-038` **Unknown slot in decision batch** | visitor supplies foreign/unpublished slot | request decisions | no creative or private information |
| `API-AT-039` **Oversized decision batch** | browser requests thousands at once | submit public request | bounded 400/413/429 per policy |
| `API-AT-040` **Empty campaign inventory** | no approved eligible campaign | request decisions | explicit empty decision not error or stale paid ad |
| `API-AT-041` **Wrong render occurrence** | event uses mismatched render ID | post event | not qualified as valid observation |
| `API-AT-042` **Expired decision** | submit old decision_ref | post rendered/click | denied or non-qualified with safe response |
| `API-AT-043` **Repeated browser event** | same event id resubmitted | post batch twice | accepted at most once in aggregate |
| `API-AT-044` **Preview simulated event** | staging/editor requests are marked nonbillable | post measurement | no revenue-bearing count |
| `API-AT-045` **Client forged billable event** | send event_type billable | POST measurement | unknown/untrusted type rejected |
| `API-AT-046` **Visible vs rendered distinction** | ad below fold loads but not seen | record rendered only | no viewable count attributed |
| `API-AT-047` **Collector offline** | analytics ingestion endpoint fails | load page | page remains usable; reports disclose gaps |
| `API-AT-048` **Dangerous creative redirect** | destination uses javascript/data scheme | click route | blocked and logged redacted |
| `API-AT-049` **Bot traffic surge** | high-rate forged clicks | ingest signals | rate limited/flagged, no direct charge |

### 16.5 Heatmap, exports, privacy

| ID | Given | When | Then / invariant |
|---|---|---|---|
| `API-AT-050` **R2 feature gate** | R1 site calls heatmap aggregate endpoint | GET restricted route | feature unavailable, no R2 data leak |
| `API-AT-051` **Heatmap authorization** | ordinary advertiser attempts heatmap fetch | GET admin heatmaps | denied even if knows layout ID |
| `API-AT-052` **Geometry mismatch** | client requests overlay for wrong layout | compare aggregates | version mismatch called out, no silent mapping |
| `API-AT-053` **Low cohort suppression** | heatmap bin below privacy threshold | request aggregate | suppressed per approved privacy policy |
| `API-AT-054` **Consent withdrawn** | visitor opts out of optional behavior data | send R2 event | drop optional data and honor policy; core ads unaffected |
| `API-AT-055` **Export download revocation** | export created then membership revoked | download export | denied regardless of stored job ownership |
| `API-AT-056` **Expired artifact** | authorized request after retention expiration | download | 410 without unprotected file URL |
| `API-AT-057` **Queue retry idempotent** | export generation job retried | run job twice | one authorized artifact/reconciled status |
| `API-AT-058` **Replay gate** | R4 replay disabled | GET session | not accessible, no raw session data |

### 16.6 Webhook/ops/release

| ID | Given | When | Then / invariant |
|---|---|---|---|
| `API-AT-059` **Outbound signature integrity** | receiver alters one body byte | verify signature | constant-time check rejects |
| `API-AT-060` **Outbound replay attempt** | receiver sees same attempt ID twice | verify duplicate | reject/replay-safe without duplicate side effects |
| `API-AT-061` **Webhook key rotation** | overlap ends and old key replayed | verify old signature | reject expired key |
| `API-AT-062` **Outbound SSRF localhost** | admin enters loopback/private endpoint | save subscription | fails destination validation |
| `API-AT-063` **Redirect to metadata IP** | remote endpoint redirects to internal host | dispatch webhook | redirect denied; no internal request |
| `API-AT-064` **DNS rebinding** | hostname changes to private IP at connect | dispatch | effective address blocked |
| `API-AT-065` **Webhook retry on 503** | receiver temporarily fails | dispatch async | bounded backoff, durable audit, no business-transaction rollback |
| `API-AT-066` **Webhook permanently gone** | receiver consistently 410/invalid | process retry policy | disabled/quarantined with admin diagnostics |
| `API-AT-067` **Webhook secret exposure** | normal GET subscriptions | inspect response/logs | no signing secret disclosed |
| `API-AT-068` **Unauth job retry** | attacker guesses job ID | POST retry route | denied, no queue event |
| `API-AT-069` **Multisite org A and site B** | cross-site role tries scoped resource | GET/POST/export | no cross-site access or cache bleed |
| `API-AT-070` **Native WooCommerce missing** | disable WC plugin then bootstrap | visit admin and public renderer | plugin operates; checkout explicitly unavailable |
| `API-AT-071` **REST permalink variations** | site uses alternate REST prefix | bootstrap frontend | rest_url-derived endpoint still works |
| `API-AT-072` **Schema upgrade compatibility** | v1 old client receives additive field | deserialize | backward compatible safe behavior |
| `API-AT-073` **Dependency outage fail closed** | transaction engine unavailable during purchase | attempt hold/payment | no unsafe booking or fabricated success |
| `API-AT-074` **Health diagnostics redaction** | operator exports diagnostics | inspect bundle | no financial secrets, raw visitor records or private tokens |

### 16.7 Contract verification matrix by execution boundary

| Boundary | Required test fixture and oracle |
|---|---|
| Controller → authorization | Two sites, two advertiser orgs, mixed memberships, deleted/revoked roles; no false authorization |
| Controller → application command | Invalid/wrong-version payload; no action before permission or state check |
| Commerce callback → ledger | Duplicate/reordered/late events, verified provider mapping; append-once ledger |
| Layout → published pointer | Concurrent CAS, paid contract impact, no cross-tenant layout publish |
| Gutenberg/theme → public renderer | Four blocks + PHP+shortcode parity; previews remain nonbillable |
| Delivery → measurement | 500-slot page, bounded batches, distinct render instances, fraudulent event rejection |
| Measurement → analytics | Explicit metric definitions, sample counts, raw vs qualified views, data freshness |
| R2 geometry → heatmaps | Page version, device class, full scroll height, consent and cohort suppression |
| Reporting → export | Current permission at request and download, protected artifact expiry |
| Outbox → webhook | Signatures, retries, rotation, SSRF checks, tenant filters and dead-letter handling |
| Platform → REST | WP cookie/nonce, application passwords where approved, alternate prefix, cache/CDN |

### 16.8 Testing implementation rules

- API contract tests run against WordPress REST request/response plumbing and a real supported transactional database for concurrency paths, not only mocked controllers.
- PHPUnit covers authorization, DTO validation, idempotency, transition service orchestration and signed payload test vectors. Playwright covers portal, Layout Studio, Gutenberg, checkout, responsive error states and accessible recovery flows.
- Load tests simulate 500 configured slots, 100 sidebar, four header, 196 content, 200 footer and 1000+ stress slots; measure actual requests, memory and event queues; do not declare 500 ad impressions from slot count.
- Inbound callback fixtures are constructed from authoritative documented provider payloads rather than fictional signatures.
- CI compares registered route manifest, OpenAPI, PHP request schemas and TypeScript types; missing `permission_callback`, undocumented auth, broken release gate or missing negative-test annotation fails CI.
- Status reports distinguish `specified`, `implemented`, `unit_tested`, `integration_tested`, `e2e_tested`, `security_reviewed` and `released`. A definition in this document never counts as tested.

---

## 17. GPT-6 High implementation work packages

Each package is a bounded unit with entry contracts, target files, expected outputs, cross-cutting obligations, verification commands and evidence. Proposed names `API-WP-01..18` are local to this document, not replacements for phase/work package IDs elsewhere.

| Package | Title | Required result | Verification evidence | Release |
|---|---|---|---|---|
| `API-WP-01` | **REST kernel and response conventions** | WP REST namespace/controller bootstrap; response/error serializer; request ID | Auth callbacks and consistent `WP_Error` tests | R1 |
| `API-WP-02` | **API schema validation and typed clients** | Versioned schema components; DTO mapping; TS client build | Contract snapshot and unknown field tests | R1 |
| `API-WP-03` | **Authorization middleware and context** | Trusted site/actor/org request scope; resource policy | Cross-org/cross-site and nonce tests | R1 |
| `API-WP-04` | **Advertiser organization endpoints** | Membership, invitations, safe org projections | Owner revocation/non-escalation tests | R1 |
| `API-WP-05` | **Public storefront catalog** | Published offers, format filters, safe previews | Anonymous projection and pagination tests | R1 |
| `API-WP-06` | **Inventory and Layout Studio API** | Zones, containers, generated slots, drafts and publish CAS | 500/1000 slot, contract-remedy, race tests | R1 |
| `API-WP-07` | **Gutenberg editor endpoints** | Four block inspectors, previews and placement validation | Editor role denial and no-billing-preview tests | R1 |
| `API-WP-08` | **Campaign, creative and moderation API** | Organization drafts, exact-version approvals and lifecycle | Independent state-machine and XSS tests | R1 |
| `API-WP-09` | **Quotes and transactional holds** | Immutable quotes, locked availability and reservation commands | Exclusive-slot race and TTL tests | R1 |
| `API-WP-10` | **Commerce and WooCommerce adapter** | Orders, checkout, provider evidence, remedies and ledger projections | Duplicate webhook and late payment tests | R1 |
| `API-WP-11` | **Public ad decision and click API** | Bounded batches, opaque decisions, eligibility and safe redirects | Forged token, stale stop and density tests | R1 |
| `API-WP-12` | **Measurement ingestion API** | Versioned event schema, dedup, rate budgets and async handoff | Nonbillable preview and collector outage tests | R1 |
| `API-WP-13` | **Reports and protected export API** | Aggregate projections, async jobs, protected downloads | Recheck-on-download and cohort guards | R1 |
| `API-WP-14` | **Outbound webhook dispatcher** | Subscription management, HMAC signing, SSRF defense and retries | Signature vectors, DNS rebinding, secret rotation | R1 |
| `API-WP-15` | **R2 heatmap APIs** | Geometry anchors, normalized aggregates and suppression | Consent, layout version and restricted access tests | R2 |
| `API-WP-16` | **R3 metered and conversion extensions** | Reconciled qualified billing queries and attribution interfaces | No charge from raw browser event; dispute proofs | R3 |
| `API-WP-17` | **R4 replay and federation gates** | Protected replay and approved network contracts | Consent, cross-site and feature-disabled tests | R4 |
| `API-WP-18` | **Release/compatibility/doc finalization** | OpenAPI, examples, manifest scan, changelog and production packaging | WP compatibility, API E2E and license gates | R1–R4 |

### 17.1 Implementation discipline for coding agents

For each `API-WP-*` task, GPT-6 High SHALL:

1. Read relevant `FR-*`, `AT-*`, `BR-*`, `DP-*`, `IC-*`, `DM-*`, `DB-*` contracts and approved release flag; name exact IDs in the work report.
2. Inspect existing repository code and do not assume class names, Composer packages or provider methods exist.
3. Produce a small change with final filenames, PHP/TypeScript interfaces, schema changes if any, permission policy, migration effect and test plan.
4. Implement server authorization before enabling the frontend affordance; do not infer permissions from React visibility.
5. Use approved data models/ports; no controller SQL or alternate unsanctioned financial state.
6. Add positive, denial, malformed-input, stale-revision, retry and tenant-isolation tests relevant to the route.
7. Run actual lint/static/tests/contract scripts in the environment; report exact commands, results and failures rather than fabricated success.
8. Preserve public API backward compatibility and release gates; do not expose R2/R3/R4 routes as operational early.
9. Update OpenAPI, typed clients, theme/Gutenberg examples and docs together; add changelog for public contract changes.
10. If critical decisions (tax, refund, compliance, signature profile, version floor, quotas) remain unresolved, fail closed for affected function and raise a documented decision instead of guessing.

### 17.2 Completion evidence template

```text
Package: API-WP-XX
Requirements and policies: FR-..., AT-..., BR-..., IC-..., D05-..., DB-...
Routes/versions changed: ...
Target module owner and application service: ...
Authentication + object authorization + release gates: ...
Request/response examples and schema changes: ...
Idempotency / CAS / locks / jobs / cache implications: ...
Privacy and field classification: ...
UI loading/error/denied states: ...
Tests added and exact execution output: ...
Supported WP/WC/database combinations actually exercised: ...
Backward compatibility and migration effect: ...
Open decisions, blockers and follow-ups: ...
```

## 18. Open API design decisions and approval register

Open decisions below are **blockers for the dependent endpoint/profile**, not license for the coding model to choose defaults without review. Each proposed owner records decision outcome, affected versions, migration/test impacts and sign-off.

| ID | Decision needed | Responsible approval | Blocks |
|---|---|---|---|
| `API-DEC-001` | Exact supported WordPress, PHP, WooCommerce, database and browser versions | WordPress Lead + QA | Compatibility/test matrix |
| `API-DEC-002` | Public ID canonical alphabet/length and case handling | Tech Lead + DB Lead | Published typed ID contract |
| `API-DEC-003` | Concrete API request/body, collection page and decision-batch quotas | Performance + Security + AdTech | Public/API load budgeting |
| `API-DEC-004` | Whether resource updates mandate both `If-Match` and expected version in body | API Lead + Frontend Lead | CAS client/server parity |
| `API-DEC-005` | Final status mapping for disabled releases vs inaccessible protected resources | Security + Product | Error taxonomy stability |
| `API-DEC-006` | Final role bundles and elevated/split-approval operations | Security + Product | Membership/capability deployment |
| `API-DEC-007` | Native checkout adapter fallback if WooCommerce absent | Product + Finance | Payments UX beyond WC |
| `API-DEC-008` | Payment provider callback profiles, signatures and verification workflow | Finance + Security | External gateway integration |
| `API-DEC-009` | Hold TTL, expiry/grace policy, late settlement remedies | Finance + AdTech | Quote/checkout/recovery |
| `API-DEC-010` | Supported geo/targeting data and consent/legal basis | Privacy + AdTech | Public decision context |
| `API-DEC-011` | Event dedup, timestamp skew, event qualification and R3 billing model | Measurement + Finance | Metered billing / raw event retention |
| `API-DEC-012` | R2 heatmap privacy threshold, cohort suppression and retention | Privacy + Behavioral Analytics | Heatmap release |
| `API-DEC-013` | Full-page geometry version/anchor schema and dynamic content support | Frontend + Analytics | Heatmap compatibility |
| `API-DEC-014` | Export job TTL, download streaming limits and storage requirements | Security + DevOps | Export release |
| `API-DEC-015` | Outbound webhook signed protocol key formats and test vectors | Security + Integration Lead | External subscriber integration |
| `API-DEC-016` | Webhook endpoint confirmation and SSRF DNS/redirect handling implementation | Security + DevOps | Outbound webhook enablement |
| `API-DEC-017` | Webhook retry count, dead-letter threshold and log retention | Operations + Security | Dispatcher rollout |
| `API-DEC-018` | Provider-independent monetary statuses versus Woo statuses | Finance + Backend | Commerce DTOs |
| `API-DEC-019` | Approved public cache TTL and campaign stop freshness SLA | AdTech + Operations | Delivery/cache guarantees |
| `API-DEC-020` | R4 replay access review, masking and session export prohibitions | Privacy + Security | Session API enablement |
| `API-DEC-021` | Cross-site network federation authorization in R4 | Security + Multisite Lead | Network routes |
| `API-DEC-022` | Version sunset, forward compatibility and contract-generation toolchain | API Lead + Release | Public SDK commitment |

---

## 19. Primary implementation references and licensing notes

These references explain platform APIs and **do not supersede** AdPilot business requirements:

- WordPress custom REST endpoints, `permission_callback`, schemas and controller design: <https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/> and <https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/>.
- WordPress REST cookie authentication and nonces: <https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/> and <https://developer.wordpress.org/apis/security/nonces/>. A WordPress nonce is not authentication/authorization nor guaranteed single-use.
- WordPress `register_rest_route()`: <https://developer.wordpress.org/reference/functions/register_rest_route/>.
- WordPress API authentication models: <https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/>.
- WooCommerce REST/HPOS-compatible entity access and webhook protocol: <https://developer.woocommerce.com/docs/features/high-performance-order-storage> and <https://woocommerce.github.io/woocommerce-rest-api-docs/> (verify actual version and gateway adapters at build time).
- WordPress privacy and plugin release requirements: <https://developer.wordpress.org/plugins/privacy/> and <https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/>.
- General secure design: OWASP API Security Top 10, ASVS and SSRF prevention guidance: <https://owasp.org/API-Security/> and <https://cheatsheetseries.owasp.org/cheatsheets/Server_Side_Request_Forgery_Prevention_Cheat_Sheet.html>.

The API implementation must use GPL-compatible distributed dependencies; license identity and version resolution require an audited dependency register and SBOM. WordPress, provider and security guidance must be rechecked at release time. The outbound AdPilot signature proposal in this document is **not** described as a WordPress or WooCommerce standard.

---

## 20. Readiness and acceptance decision

**Architectural ready-for-implementation status:** proposed if all endpoint registry entries have module owner, policy, release and schema responsibilities; confirmed only after Product/Architecture/Security/Finance/UX review. **Production readiness:** independent of specification completion and conditional on actual running test evidence, compatibility and external adapter verification.

### 20.1 Go/no-go checklist

- Every route is registered under the correct WordPress namespace/hook with a non-omitted permission callback.
- No REST route permits cross-site or cross-organization data access.
- Financial and reservation mutations are transactionally consistent and retry-safe.
- Published layout updates protect sold contracts and stable slot identities.
- Gutenberg and theme output use shared rendering/eligibility services and editor previews are nonbillable.
- Public decision and tracking APIs are bounded, token-limited, abuse-resistant and never provide direct billing authority.
- R2 heatmaps and R4 replay are gated by release, role, privacy purpose and consent policy.
- WooCommerce optionality, HPOS and provider callbacks are verified with version-matched documentation.
- Outbound webhooks have signature test vectors, SSRF controls, verified subscriptions, redacted logs and deterministic retries.
- All response DTOs have server-tested schemas, permissions, money/time semantics, pagination and error contracts.
- API specs, tests, generated clients, docs, release notes and actual source manifest agree.
- Any unresolved API-DEC blocking a feature is recorded and that feature remains disabled.

### 20.2 Handoff

**ADP-DOC-010 — Event Schemas, Background Jobs, Outbox & Asynchronous Processing Specification** will define durable events, job state/provenance, retries, leases, versioned upcasters, metrics ingestion, reconciliation handoffs and operational backpressure using the `IC-014`, `IC-020`, `IC-021`, and `IC-022` boundaries. It must refine, not contradict, the endpoint and event contracts above.

## Appendix A — All 129 canonical functional requirements mapped to HTTP contracts

This index reuses each `FR-*` and source release tag exactly from ADP-DOC-002. `AT-*` is the original requirement-level acceptance test (formed by substituting the original `FR-` prefix with `AT-`). Endpoint-family ranges are **navigation anchors**, not a promise that each requirement gets its own HTTP endpoint. For example, a public PHP function, database migration or performance rule may be fulfilled by non-HTTP implementation plus the listed integration boundary. The exact obligation remains in Documents 01–02, including optional release limits.

| FR | Source release | Primary endpoint family / in-process path | Owning module | Existing acceptance |
|---|---|---|---|---|
| `FR-INV-001` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-001` |
| `FR-INV-002` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-002` |
| `FR-INV-003` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-003` |
| `FR-INV-004` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-004` |
| `FR-INV-005` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-005` |
| `FR-INV-006` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-006` |
| `FR-INV-007` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-007` |
| `FR-INV-008` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-008` |
| `FR-INV-009` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-009` |
| `FR-INV-010` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-010` |
| `FR-INV-011` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-011` |
| `FR-INV-012` | `R2` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-012` |
| `FR-INV-013` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-013` |
| `FR-INV-014` | `R1` | `INV-001..014, LAY-001..016` | Inventory / Layout | `AT-INV-014` |
| `FR-INT-001` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-001` |
| `FR-INT-002` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-002` |
| `FR-INT-003` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-003` |
| `FR-INT-004` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-004` |
| `FR-INT-005` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-005` |
| `FR-INT-006` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-006` |
| `FR-INT-007` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-007` |
| `FR-INT-008` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-008` |
| `FR-INT-009` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-009` |
| `FR-INT-010` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-010` |
| `FR-INT-011` | `R2` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-011` |
| `FR-INT-012` | `R1` | `BLOCK-001..004, LAY-013..016, DEL-001` | Layout / WordPress Integration | `AT-INT-012` |
| `FR-MKT-001` | `R1` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-001` |
| `FR-MKT-002` | `R1` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-002` |
| `FR-MKT-003` | `R1` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-003` |
| `FR-MKT-004` | `R1` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-004` |
| `FR-MKT-005` | `R1` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-005` |
| `FR-MKT-006` | `R1` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-006` |
| `FR-MKT-007` | `R1` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-007` |
| `FR-MKT-008` | `R1` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-008` |
| `FR-MKT-009` | `R1` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-009` |
| `FR-MKT-010` | `R2` | `CAT-001..008, ORG-001..016` | Advertisers / Marketplace | `AT-MKT-010` |
| `FR-CAM-001` | `R1` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-001` |
| `FR-CAM-002` | `R1` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-002` |
| `FR-CAM-003` | `R1` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-003` |
| `FR-CAM-004` | `R1` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-004` |
| `FR-CAM-005` | `R1` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-005` |
| `FR-CAM-006` | `R1` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-006` |
| `FR-CAM-007` | `R1` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-007` |
| `FR-CAM-008` | `R1` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-008` |
| `FR-CAM-009` | `R2` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-009` |
| `FR-CAM-010` | `R1` | `CAM-001..014, CRE-001..010` | Campaigns / Creatives / Moderation | `AT-CAM-010` |
| `FR-COM-001` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-001` |
| `FR-COM-002` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-002` |
| `FR-COM-003` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-003` |
| `FR-COM-004` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-004` |
| `FR-COM-005` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-005` |
| `FR-COM-006` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-006` |
| `FR-COM-007` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-007` |
| `FR-COM-008` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-008` |
| `FR-COM-009` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-009` |
| `FR-COM-010` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-010` |
| `FR-COM-011` | `R3` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-011` |
| `FR-COM-012` | `R4` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-012` |
| `FR-COM-013` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-013` |
| `FR-COM-014` | `R1` | `PRC-001..006, RES-001..006, ORD-001..012` | Pricing / Reservations / Commerce | `AT-COM-014` |
| `FR-DEL-001` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-001` |
| `FR-DEL-002` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-002` |
| `FR-DEL-003` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-003` |
| `FR-DEL-004` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-004` |
| `FR-DEL-005` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-005` |
| `FR-DEL-006` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-006` |
| `FR-DEL-007` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-007` |
| `FR-DEL-008` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-008` |
| `FR-DEL-009` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-009` |
| `FR-DEL-010` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-010` |
| `FR-DEL-011` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-011` |
| `FR-DEL-012` | `R2/R3` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-012` |
| `FR-DEL-013` | `R1` | `DEL-001..003, MET-001..003` | Delivery / Targeting | `AT-DEL-013` |
| `FR-MET-001` | `R1` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-001` |
| `FR-MET-002` | `R1` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-002` |
| `FR-MET-003` | `R1` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-003` |
| `FR-MET-004` | `R1` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-004` |
| `FR-MET-005` | `R1` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-005` |
| `FR-MET-006` | `R1` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-006` |
| `FR-MET-007` | `R2` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-007` |
| `FR-MET-008` | `R2` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-008` |
| `FR-MET-009` | `R3` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-009` |
| `FR-MET-010` | `R3` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-010` |
| `FR-MET-011` | `R3` | `MET-001..003, REP-001..004` | Measurement / Reporting | `AT-MET-011` |
| `FR-HEAT-001` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-001` |
| `FR-HEAT-002` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-002` |
| `FR-HEAT-003` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-003` |
| `FR-HEAT-004` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-004` |
| `FR-HEAT-005` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-005` |
| `FR-HEAT-006` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-006` |
| `FR-HEAT-007` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-007` |
| `FR-HEAT-008` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-008` |
| `FR-HEAT-009` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-009` |
| `FR-HEAT-010` | `R4` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-010` |
| `FR-HEAT-011` | `R2` | `HEAT-001..007, MET-001` | Behavioral Analytics / Privacy | `AT-HEAT-011` |
| `FR-REP-001` | `R1` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-001` |
| `FR-REP-002` | `R1` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-002` |
| `FR-REP-003` | `R1` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-003` |
| `FR-REP-004` | `R1` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-004` |
| `FR-REP-005` | `R2` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-005` |
| `FR-REP-006` | `R3` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-006` |
| `FR-REP-007` | `R3` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-007` |
| `FR-REP-008` | `R2` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-008` |
| `FR-REP-009` | `R2` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-009` |
| `FR-REP-010` | `R1` | `REP-001..008, HEAT-001..005` | Reporting / Analytics | `AT-REP-010` |
| `FR-UX-001` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-001` |
| `FR-UX-002` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-002` |
| `FR-UX-003` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-003` |
| `FR-UX-004` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-004` |
| `FR-UX-005` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-005` |
| `FR-UX-006` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-006` |
| `FR-UX-007` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-007` |
| `FR-UX-008` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-008` |
| `FR-UX-009` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-009` |
| `FR-UX-010` | `R2/R4` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-010` |
| `FR-UX-011` | `R1` | `CAT-*, ORG-*, LAY-*, BLOCK-*, CAM-*, REP-*` | Presentation + owner module | `AT-UX-011` |
| `FR-OPS-001` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-001` |
| `FR-OPS-002` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-002` |
| `FR-OPS-003` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-003` |
| `FR-OPS-004` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-004` |
| `FR-OPS-005` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-005` |
| `FR-OPS-006` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-006` |
| `FR-OPS-007` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-007` |
| `FR-OPS-008` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-008` |
| `FR-OPS-009` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-009` |
| `FR-OPS-010` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-010` |
| `FR-OPS-011` | `R4` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-011` |
| `FR-OPS-012` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-012` |
| `FR-OPS-013` | `R1` | `OPS-001..018, ORG-*, DEL-001` | Platform / Identity / Integrations | `AT-OPS-013` |

## Appendix B — Canonical business rules and API enforcement map

The following `BR-*` identifiers remain unchanged. Rules are paraphrased briefly for mapping; **exact legal/normative wording remains in Documents 01–04**. Every rule has a pre-existing `AT-BR-*` acceptance and API-level implementation fixture.

| Rule | API enforcement | Primary existing acceptance |
|---|---|---|
| `BR-001` | CAT-005 / PRC-005 / RES-001: availability and current publishability gate | `AT-BR-001` |
| `BR-002` | RES-001..003: TTL and transactional explicit extension/replacement | `AT-BR-002` |
| `BR-003` | CRE-007 / ORD-001..004 / DEL-001: payment never equals approval | `AT-BR-003` |
| `BR-004` | ORD-009..011 / CRE-008: paid rejection remedy and ledger preservation | `AT-BR-004` |
| `BR-005` | RES-001 / ORD-001: sorted capacity locks, exclusivity and overlap | `AT-BR-005` |
| `BR-006` | LAY-005..012: sold-layout impact approval and audit | `AT-BR-006` |
| `BR-007` | CRE-003..009 / DEL-001: exact version approval and fingerprint | `AT-BR-007` |
| `BR-008` | CAM-011..013 / DEL-001..003: stop freshness and current eligibility | `AT-BR-008` |
| `BR-009` | BLOCK-002 / LAY-006 / MET-001: preview and synthetic-event exclusion | `AT-BR-009` |
| `BR-010` | LAY-005 / DEL-001: density and network-specific policy gate | `AT-BR-010` |
| `BR-011` | REP-004 / HEAT-001..007: advertiser aggregates only | `AT-BR-011` |
| `BR-012` | MET-001 / OPS-015..016: retention, minimization and purging | `AT-BR-012` |
| `BR-013` | REP-001..004 / MET-001: denominators, qualifier, estimate metadata | `AT-BR-013` |
| `BR-014` | PRC-005 / ORD-001..012: exact integer minor units and currency mapping | `AT-BR-014` |
| `BR-015` | PRC-005 / RES-001 / CAM-010: UTC and unambiguous DST resolved interval | `AT-BR-015` |
| `BR-016` | BLOCK-001..004 / LAY-005: responsive/a11y checks and policy gates | `AT-BR-016` |
| `BR-017` | OPS-008..016: approved third-party transfer/privacy/verification | `AT-BR-017` |
| `BR-018` | LAY-012 / CAM-012 / ORD-010..011: authorized auditable high-risk override | `AT-BR-018` |

## Appendix C — Individually referenced NFRs, invariants and acceptance gates

These remain source-owned and are not changed by this document. The mapping is a navigation and API test responsibility, not a declaration that verification passed.

| Baseline ID | API-specific verification anchor |
|---|---|
| `NFR-001` | Layer/controller boundaries, OpenAPI and no repository bypass |
| `NFR-002` | Typed DTO/versioning and build/contract tests |
| `NFR-003` | Progressive 500-slot decision batches and non-React public renderer |
| `NFR-004` | Bounded transport, latency, pagination and tested quotas |
| `NFR-005` | Idempotency, retries, backpressure and graceful degradation |
| `NFR-006` | Verified payment, immutable ledger, transactional slot booking |
| `NFR-007` | Scoped authentication, input validation, signing and SSRF |
| `NFR-008` | Purpose-limited collector, safe exports and consent |
| `NFR-009` | WCAG keyboard/ARIA states and equivalent heatmap table |
| `NFR-010` | WP multisite/classic-block/WooCommerce-optional compatibility |
| `NFR-011` | Locale-aware UI while preserving exact UTC and money |
| `NFR-012` | License approval and bundled API-client source compliance |
| `NFR-013` | Public documentation/SDK route contract parity |
| `NFR-014` | Operations health, logs, redaction and queue lag |
| `NFR-015` | Schema evolution, migrations, API compatibility |
| `NFR-016` | Release gating and reliability under failure |

### C.2 Canonical invariant and product acceptance references

| Baseline | API contract verification approach |
|---|---|
| `INV-01` | Trustworthy verified finance; source wording prevails |
| `INV-02` | Separate approval/payment/delivery; source wording prevails |
| `INV-03` | No overselling; source wording prevails |
| `INV-04` | No sold-layout silent change; source wording prevails |
| `INV-05` | No unapproved creatives; source wording prevails |
| `INV-06` | Stable slots vs render occurrence; source wording prevails |
| `INV-07` | Privacy and data minimization; source wording prevails |
| `INV-08` | Measurement qualification; source wording prevails |
| `INV-09` | Tenant/isolation security; source wording prevails |
| `INV-10` | Release gating and operational safety; source wording prevails |
| `AC-001` | Catalog and advertiser onboarding; source wording prevails |
| `AC-002` | Campaign wizard and approval; source wording prevails |
| `AC-003` | Booking and hold; source wording prevails |
| `AC-004` | Payment verification; source wording prevails |
| `AC-005` | Creative moderation; source wording prevails |
| `AC-006` | Scheduling; source wording prevails |
| `AC-007` | Header/side/content/footer layout; source wording prevails |
| `AC-008` | Gutenberg block; source wording prevails |
| `AC-009` | Theme PHP/shortcode; source wording prevails |
| `AC-010` | Progressive render; source wording prevails |
| `AC-011` | Reporting; source wording prevails |
| `AC-012` | Role/tenant security; source wording prevails |
| `AC-013` | Accessibility; source wording prevails |
| `AC-014` | Privacy/retention; source wording prevails |
| `AC-015` | Installation/release diagnostics; source wording prevails |

## Appendix D — `IC-001..024` contract-to-transport mapping

Inter-module contracts live within the application even where a REST projection exists. This index prevents a REST endpoint from being mistaken for a new authority over a foreign module.

| Architecture contract | HTTP or in-process adapter |
|---|---|
| `IC-001` | All authenticated routes via trusted context resolver; no client-granted identity |
| `IC-002` | ORG-* projections |
| `IC-003` | INV-* and CAT-* safe inventory |
| `IC-004` | LAY-*, BLOCK-*, DEL-001 published layout |
| `IC-005` | CAT-005, PRC-005, RES-001 |
| `IC-006` | PRC-* immutable quotes |
| `IC-007` | RES-* hold commands |
| `IC-008` | RES-* contracts and DEL-001 read-only allocation |
| `IC-009` | CRE-*, DEL-001/003 approved creative versions |
| `IC-010` | CAM-*, DEL-001 fresh campaign eligibility |
| `IC-011` | ORD-* verified WooCommerce/provider adapter (not client money) |
| `IC-012` | ORD-* finance projections |
| `IC-013` | DEL-001 batch decision |
| `IC-014` | MET-001 untrusted telemetry |
| `IC-015` | REP-* aggregates; R3 metered reconciliation |
| `IC-016` | HEAT-002 geometry mapping; LAY-* source |
| `IC-017` | HEAT-003..005 privacy-filtered heatmaps |
| `IC-018` | REP-005..008 exports |
| `IC-019` | LAY-005, DEL-001, MET-001, CRE-007 policies |
| `IC-020` | OPS-006 plus internal Action Scheduler job API |
| `IC-021` | OPS-009..013 outbound webhook and integration events |
| `IC-022` | Internal notification jobs, scoped delivery |
| `IC-023` | OPS-005 redacted diagnostics |
| `IC-024` | OPS-014, public safe feature availability |

## Appendix E — Route and test inventory audit method

`API-AT-*` tests are new contracts specific to Document 09; original `AT-*` tests remain authoritative. Release scope in the route registry is independent from overall `v1` API version. The implementation's CI should generate a route manifest from WordPress `rest_get_server()->get_routes()` after bootstrap and compare route/verb/security/release/schema metadata against an approved OpenAPI artifact, followed by a separate actual request test suite.

**Structural verification for this document** checks: unique route IDs, unique `API-AT-*` identifiers, coverage of `FR-*`, `BR-*`, `NFR-*`, `INV-*`, `AC-*`, and `IC-*` registries, and balanced Markdown code fences. That is not runtime route verification. Before release, perform the explicit security, functional, compatibility and load tests against the built plugin.

---

**Document-end checkpoint:** Proposed API and webhooks implementation specification completed for review. Confirm open decisions before activating dependent routes. Next in sequence: ADP-DOC-010 — Event Schemas, Background Jobs, Outbox & Asynchronous Processing Specification.

