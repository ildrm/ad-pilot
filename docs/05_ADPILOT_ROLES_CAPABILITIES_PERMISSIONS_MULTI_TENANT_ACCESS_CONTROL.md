# AdPilot — Roles, Capabilities, Permission Matrix, Multi-Tenant Authorization & Access-Control Specification

**Document ID:** ADP-DOC-005  
**Filename:** `05_ADPILOT_ROLES_CAPABILITIES_PERMISSIONS_MULTI_TENANT_ACCESS_CONTROL.md`  
**Version:** 1.0.0 (proposed normative specification; product sign-off required)  
**Status:** Ready for implementation planning; unresolved business/security decisions remain explicit  
**Date:** 2026-10-11  
**Product:** AdPilot — Self-Service Advertising Marketplace & Behavioral Analytics for WordPress  
**Primary audience:** GPT-6 High implementation agents, WordPress/PHP architects, API developers, React/Gutenberg engineers, security/privacy specialists, QA, DevOps, Product Owner  
**Accountable owner:** Application Security Lead + Technical Lead  
**Required reviewers:** WordPress Lead, Product Owner, Backend Lead, UX Lead, Finance Lead, Privacy Lead, QA Lead, Multisite/Operations Lead

> **Implementation status:** This is a specification, not a claim that the plugin, permissions, security controls, or tests already exist. All proposed names and code examples are implementation contracts to create and validate. Code shown is illustrative unless an example explicitly states it is production-complete.

---

## 1. Purpose, authority, and scope

### 1.1 Purpose

Define the complete authorization system for AdPilot: WordPress site capabilities, organization-scoped advertiser membership, object ownership, publisher delegated authority, state-dependent permissions, protected data classes, REST/CLI/background job authorization, multisite isolation, audit requirements, frontend permission-aware UX, and automated assurance. A user must **never** obtain authority simply by knowing an ID, controlling a Gutenberg attribute, being authenticated, passing a nonce, calling a shortcoded PHP rendering function, or acting through a cached browser session.

### 1.2 Governing documents and non-negotiable baseline

| Source | Status and preserved obligations |
|---|---|
| `ADP-DOC-001` — PRD | Normative source of 129 `FR-*`, `BR-001..018`, `NFR-001..016`, `AC-001..015`, `INV-01..10`, decisions `DEC-001..012`, releases `R1..R4`. |
| `ADP-DOC-002` — Feature catalog | Preserves 129 `AT-*` requirement acceptance cases, 18 `AT-BR-*` and `E2E-01..17`. |
| `ADP-DOC-003` — Personas and journeys | Defines `PERS-01..16`, `JTBD-01..24`, `UJ-01..28`, `WF-01..18`, `UI-01..20`, `UXA-01..28`. |
| `ADP-DOC-004` — Policies/state machines | Preserves `DP-AUTH`, `DP-OVR`, other `DP-*`, `SM-*`, `PERM-001..016`, `SCN-*`, and the independent order/approval/delivery states. |
| **This document** | Freezes proposed capability catalog and policy contracts; creates supplementary `D05-*` identifiers without renaming or weakening prior obligations. |

**Precedence:** A conflict with Documents 01–04 is a specification defect. Log it with impact analysis; do not silently reinterpret the earlier requirement. Domain policy is enforced by authoritative server services, never exclusively by UI controls. R1 must be tenant-safe even if R4 centralized network administration is not yet implemented. R2 heatmaps and R4 replay must not be treated as available R1 features.

### 1.3 What is not authorized by this document

- Arbitrary access to other WordPress sites in a multisite installation.
- An advertiser's ability to edit WordPress posts or to become a WordPress Administrator.
- A support agent's ability to impersonate, replay, or export private customer data by default.
- A network super administrator's automatic entitlement to access cross-site AdPilot customer data through plugin APIs.
- A token, signed URL, nonce, or frontend feature flag serving as sufficient authorization on its own.
- Unrestricted execution of advertiser-provided HTML, JavaScript, or PHP.
- Financial, inventory or behavioral-data access from a mere campaign-manager membership.

### 1.4 Normative language and release semantics

`MUST`/`SHALL` means mandatory for a release in which the relevant feature is offered. `SHOULD` permits a documented approved deviation. `MAY` identifies optional functionality. `R1`: core marketplace and fixed/time-based sales; `R2`: aggregated heatmaps; `R3`: metered billing/advanced monetization; `R4`: multisite federation and optional replay. Absent features must fail closed and not appear as active controls.

### 1.5 Security goals and measurable outcomes

| Goal | Required evidence |
|---|---|
| No cross-organization data access | Positive and negative direct-object REST tests across at least two organizations and mixed memberships. |
| No cross-site record access | Two-site multisite tests for reads, writes, exports, queues, cache keys and public delivery. |
| No privilege escalation | Tests for unauthorized role/membership mutations, high-risk grants, and self-approval. |
| No stale authorization | Revocation effective on the next authoritative request, export download or job authorization check. |
| No unauthorized sensitive data release | Fields and row scopes enforced at query, serializer and signed-artifact download layers. |
| No unreviewed financial or allocation override | Explicit capability, reason, affected-resource versions, dual control where configured, and audit evidence. |
| No false ad/billing privileges | Gutenberg/shortcodes/PHP/public endpoints may request rendering but cannot alter paid delivery or count preview events. |
| Usable failures | Consistent 401/403/404/409 responses with nonleaking UI recovery; accessible action controls. |

---

## 2. Identity architecture and separation of authorities

### 2.1 Five independent authority dimensions

Authorization decision = **identity authentication** AND **correct WordPress site** AND **operation permission** AND **target resource scope** AND **applicable business/data policy**. A sixth dimension — an explicit elevated approval or purpose — applies for certain operations.

1. **Identity:** WordPress user ID, verified service principal, or permitted anonymous public caller; session/provider identity is validated at the boundary.
2. **Site:** `blog_id`/site context taken from trusted runtime/routing, not solely from request payload.
3. **Operation:** Primitive WP capability (publisher plane) or organization membership permission (advertiser plane), or a tightly defined public/service action.
4. **Resource:** Tenant site, organization, ownership chain, state/version, placement and campaign contract associations.
5. **Domain/data policy:** Account status, creative approval, booking state, consent, privacy-purpose, data classification, financial guard, current site policy.
6. **Elevation:** Separate privileged grant, reason, approval workflow, and audit for irreversible or sensitive operations.

A WordPress capability by itself does not authorize reading a foreign organization's private campaign. Conversely, an organization Owner permission does not authorize WordPress settings or site inventory edits.

### 2.2 Identity categories

| Principal class | Authentication | Authorization source | Example |
|---|---|---|---|
| Publisher user | WP session/application password where appropriately supported | WP site capabilities + AdPilot policy + resource scope | Site AdOps editor |
| Advertiser user | WP session and active organization membership | Org membership permission + site/org/resource checks | Agency manager of two clients |
| Dual-role user | Single WP identity | Publisher and advertiser permissions evaluated **separately per requested operation** | Publisher analyst who owns a personal ad account |
| Anonymous visitor | None | Explicit allowlisted public action; rate-limit and consent gate as relevant | Public placement catalog read |
| Internal scheduled job | Verified server-originated job registration/payload | Operation-specific service grant + persisted work provenance + fresh domain state | Campaign activation job |
| Inbound provider webhook | Verified provider-specific signature/secret plus idempotency | Constrained provider operation, mapped site/order and replay checks | Checkout payment notification |
| Multisite network operator (R4) | WP network identity | Approved network-plane permission + explicitly targeted-site policy | Network-wide configuration audit |

### 2.3 Critical scoping rules

- Every persisted AdPilot domain record SHALL have a site scope (direct `site_id`/`blog_id` or enforced storage namespace); organization-owned resources also carry and validate `organization_id`.
- IDs are opaque identifiers, **not access-control credentials**.
- Parent-child associations must be verified on every access: creative → campaign → organization/site; invoice → order → organization/site; slot → container → zone → site; heatmap → layout version → site.
- An actor with memberships in two organizations must select an **active organization context** for advertiser actions. The chosen org is validated against current memberships on each request.
- Direct access to a third organization's ID is denied even if the requester has a valid JWT/cookie/nonce or a similarly numbered record in another site.
- Publisher staff must use site-authorized publisher operations rather than borrowing advertiser membership to inspect campaigns.
- Role checks by literal name (`administrator`, `editor`, etc.) are prohibited as authorization decisions; use capabilities and policies.

### 2.4 R1 versus R4 network architecture

**R1:** WordPress multisite installations may host the plugin with site-local resource boundaries, but network-wide inventory aggregation, federation, shared campaigns, transfers and centrally delegated access are **R4**, subject to `DEC-009`. **R4:** A separately approved network permission layer may perform explicit site-targeted tasks; no implicit cross-site data visibility. Each site remains its own boundary unless a documented, consent- and contract-compatible federation policy permits otherwise.

---

## 3. Principals and business role definitions

### 3.1 Publisher and operational roles

| Business role | Personas | Boundary / permitted work | Explicit default exclusions |
|---|---|---|---|
| Site Administrator | `PERS-01`, `PERS-13` | Site settings, site identity/role policies, inventory, campaigns, moderation, reports and diagnostics, subject to differentiated sensitive guards. | Cross-site data, unrestricted replay, undocumented contract overrides. |
| Advertising Operations Manager | `PERS-02` | Inventory editing, schedule maintenance, campaign operation and delegated publication. | Finance adjustments, role grants, restricted replay. |
| Creative Moderator / Reviewer | `PERS-08` | Approve/reject immutable creative versions, log reasons, report unsafe creatives; optional narrowly granted stop. | Prices, payments, ledger writes, organization roles. |
| Publisher Finance Manager | `PERS-09` | Orders, settlement evidence, invoice controls, approved refunds and reconciliations. | Moderation approval, raw visitor tracking, role grants. |
| Analytics Manager | `PERS-10` | Authorized aggregate campaign and placement analytics, R2 aggregate heatmaps. | R4 replay or raw telemetry without separate explicit grant. |
| WordPress Content Editor | `PERS-11` | WordPress-authorized post editing and, with AdPilot grant, insertion of published zone references. | New sellable inventory, paid-slot overrides, campaign state changes. |
| Support / Incident Operator | `PERS-16` | Redacted health, ticket-scoped support metadata, escalation workflows. | Impersonation, unrestricted exports, raw sessions, finance writes. |
| Theme/Integration Developer | `PERS-12` | Trusted source-controlled code calling documented render APIs. | No authorization from shortcode parameters; code access governed separately. |
| Network Super Administrator | `PERS-13` | R4 approved network-plane governance and explicit delegated site operations. | Invisible federation of site/advertiser/visitor data. |

### 3.2 Advertiser organization roles

| Membership role | Persona | Default organization grants | Default exclusions |
|---|---|---|---|
| `owner` | `PERS-05` | Manage organization profile, member invitations and approved grants, draft/manage campaigns, quotes, checkout, invoices, scoped reports. | Site admin functions, restricted visitor data, other orgs. |
| `campaign_manager` | `PERS-04` | Draft/edit/submit own org campaigns, manage eligible creatives, purchase if commercial policy grants it, view approved campaign reporting. | Owner promotion, membership administration, publisher finance/settings. |
| `analyst` | `PERS-06` | View authorized aggregate campaign/placement reports; request limited exports. | Campaign mutation, billing credentials, session replay. |
| `billing` | `PERS-07` | View org invoices/payments, initiate authorized billing disputes, checkout only when independently permitted. | Campaign moderation, targeting edits, org members. |

An owner may be a member of several organizations; ownership is represented per membership record, not as a global WordPress `adpilot_owner` role. A team with one member has the same authorization safeguards as a larger organization.

### 3.3 Public roles

`PERS-14` public prospective buyer can read only published product terms and nonidentifying availability. `PERS-15` visitor may request eligible public advertisements and emit appropriately constrained measurement events, with legal/consent gating for nonessential tracking. Neither category has a blanket permission to inspect AdPilot REST records.

### 3.4 Roles are bundles; permissions are decisions

Role names provide useful administrator UX defaults. The engine MUST authorize by operation + tenant + resource + state, **not** by asking only whether a user has a chosen role name. Role assignments never bypass business rules `BR-001..018`, privacy restrictions, financial reconciliation or current creative/campaign status.

---

## 4. WordPress capability model and registration

### 4.1 Separation: site capabilities versus organization permissions

**Site-plane primitive capabilities** belong to WordPress site roles/users and are checked through `current_user_can()` or equivalent site-context evaluation. **Advertiser-plane permissions** belong to AdPilot's site-and-organization membership records; they are *not* automatically materialized as global WP capabilities because membership can differ across organizations. A generic WP capability `adpilot_access_portal` MAY be used for menu presentation, but shall never be treated as proof of a current membership or ownership.

For operational visibility, the permission inspector shows: (a) WordPress capability grant; (b) current tenant; (c) active membership; (d) resource scope; (e) state/policy gates; (f) elevated approval prerequisites.

### 4.2 Canonical capability catalog (preserving Document 04)

The following 22 keys from Document 04 are **reserved public capability names**. Their actual availability is controlled by release and scoped policy. `Org-API` indicates the same operation name may be used in a policy decision for a membership, but a WP user capability alone does **not** confer org access.

| Capability | Scope | Release | Authorized use / constraint |
|---|---|---|---|
| `adpilot_manage_settings` | Site | R1 | General site-only configuration; cannot alter protected data/privacy settings without their own gates. |
| `adpilot_manage_roles` | Site | R1 | Manage delegable AdPilot site role/capability grants without self-escalation. |
| `adpilot_manage_inventory` | Site | R1 | Create/change zone, container, slot draft; sold inventory policy still applies. |
| `adpilot_publish_layout` | Site | R1 | Publish/restore validated layout versions; sold-contract checks mandatory. |
| `adpilot_override_allocation` | Site elevated | R1 | Reasoned, authorized, auditable exception; may require independent approval. |
| `adpilot_manage_rates` | Site | R1 | Rate card drafts/versions and publish policy. |
| `adpilot_view_campaigns` | Site or Org-API | R1 | Only campaigns permitted within the evaluated scope. |
| `adpilot_manage_campaigns` | Site or Org-API | R1 | Campaign actions allowed by specific state and object policy. |
| `adpilot_moderate_creatives` | Site | R1 | Moderator review of specific immutable creative versions. |
| `adpilot_emergency_stop` | Site elevated | R1 | Suspend eligible delivery; nonfinancial, audited safety stop. |
| `adpilot_view_finance` | Site or Org-API | R1 | Only permitted order/invoice fields and correct tenant. |
| `adpilot_adjust_finance` | Site elevated | R1 | Governed refund/ledger adjustment workflows; never direct mutable ledger overwrite. |
| `adpilot_view_aggregate_analytics` | Site or Org-API | R1; heatmaps R2 | Validated aggregates only; publisher and advertiser scopes are different. |
| `adpilot_export_reports` | Site or Org-API | R1; extended R2+ | Create/download scoped reports with authorization rechecks. |
| `adpilot_view_restricted_analytics` | Site elevated | R2+ | Purpose-limited raw/restricted telemetry; does not itself grant replay. |
| `adpilot_view_replay` | Site elevated | R4 | Separately approved replay access; disabled when module off. |
| `adpilot_manage_privacy` | Site elevated | R1 | Retention, consent integration, permitted personal-data operations. |
| `adpilot_view_diagnostics` | Site | R1 | Redacted health/incident data only. |
| `adpilot_retry_jobs` | Site elevated | R1 | Only approved job classes, with provenance and idempotency. |
| `adpilot_manage_advertiser_members` | Organization or delegated Site | R1 | Org owner/member invitation policy; no publisher-role escalation. |
| `adpilot_purchase_inventory` | Organization | R1 | Create own org quote/hold/checkout; publisher assisted purchases separately audited. |
| `adpilot_insert_blocks` | Site editor/content scope | R1 | Bind published permissible zones in edit-authorized WordPress content only. |

**Note:** The source Document 04 labels these as a *proposed catalog*. Document 05 adopts their spellings and constrains semantics, subject to sign-off. At least the org-scoped keys must be treated as operation identifiers evaluated by `OrganizationAuthorizationService`; do not grant them universally on WP roles in a way that bypasses membership.

### 4.3 Additional primitive capabilities proposed by Document 05

| Capability | Scope | Why distinct? | Default |
|---|---|---|---|
| `adpilot_view_inventory` | Site | Read unpublished inventory configuration without edit | Site Administrator, AdOps |
| `adpilot_view_orders` | Site | Inspect purchase metadata without changing finance | Site Administrator, Finance |
| `adpilot_view_audit_log` | Site elevated | Read privileged audit entries with redaction | Site Administrator, designated auditor |
| `adpilot_manage_organization_status` | Site | Verify/suspend advertiser account with audit | Site Administrator, delegated AdOps |
| `adpilot_manage_integrations` | Site elevated | Configure secrets and outbound webhooks | Site Administrator only by default |
| `adpilot_manage_ads_policy` | Site elevated | Change moderation/density/targeting policy | Site Administrator only by default |
| `adpilot_approve_financial_override` | Site elevated | Independent secondary approval if configured | Designated approver; not initiator |
| `adpilot_approve_layout_remedy` | Site elevated | Approve sold-slot contractual remediation | Designated policy approver |
| `adpilot_manage_replay_policy` | Site elevated R4 | Explicit replay enablement/retention settings | No default grant until R4 sign-off |
| `adpilot_network_governance` | Network R4 | Network rules and approved cross-site operations | No grant in R1–R3 |

Extend the catalog further only after recording a capability-level design review. Never merge `adpilot_view_replay`, `adpilot_adjust_finance`, and `adpilot_manage_roles` into a generic all-powerful custom role.

### 4.4 WordPress role slugs and default grants

| Role slug / WP role | Type | Default intent | Important details |
|---|---|---|---|
| `administrator` | Core WP | Site administration + AdPilot site operations | Site-scoped business restrictions still enforced. |
| `adpilot_adops` | New WP site role | Operational inventory, layout, campaign operations, basic diagnostics | No automatic finances, roles, privacy, replay. |
| `adpilot_moderator` | New WP site role | Versioned creative moderation and safe review | Emergency stop must be separately granted. |
| `adpilot_finance` | New WP site role | Order view, settlement reporting, approved refund workflow | No site roles, creative approval, replay. |
| `adpilot_analyst` | New WP site role | Authorized publisher aggregates, R2 heatmap aggregates | No raw/replay or finance by default. |
| `adpilot_support` | New WP site role | Redacted diagnostics, limited customer-service status | No impersonation, direct customer-data search, or overrides. |
| `editor` | Core WP | Normal editing; optional AdPilot zone insertion | Plugin **should explicitly opt in** `adpilot_insert_blocks`; editing rights verified per target post. |
| `subscriber` or another existing safe core role | Core WP | WP identity for an advertiser with membership(s) | Must not receive global campaign/billing powers merely by being subscriber. |
| Network Super Admin | WP network status, not ordinary site role | R4 network governance only after separately released | WordPress super-admin semantics demand custom cross-site/data policy checks. |

**Default grant policy:** The WordPress `administrator` role may receive site-plane capabilities needed to administer AdPilot, but **separate high-risk actions still require explicit policy checks, reason, and optional dual approval**. The plugin must never automatically grant raw replay or network governance prior to feature readiness. Other custom roles should receive only their documented columns of capabilities. Users may hold multiple site-role capabilities; sensitive conflicts-of-interest require workflow checks beyond role union.

### 4.5 Role registration and migration contract

- Register custom roles on plugin activation and on controlled versioned capability schema migration; do not call `add_role()`/`add_cap()` on every ordinary request.
- Track `capability_schema_version` separately from database migration version; migrate new/removed caps idempotently, preserving explicitly approved custom assignments when possible.
- Never remove WordPress role assignments or capabilities belonging to another plugin or site administrator's unrelated configuration.
- During deactivation, disable AdPilot routes/handlers without deleting financial records or mutating unrelated roles. Uninstall behavior follows documented retention/removal policy; do not silently destroy accounts or memberships.
- Block feature enablement if required capabilities or schema migrations are inconsistent. Provide admin diagnostics and a repair command with explicit permission.
- Preserve backward compatibility for public capability names; perform mapped migrations and deprecation notices for intentional changes.

### 4.6 Primitive and meta capability policy

Custom **primitive** capabilities are registered on WordPress roles. Object-level **meta decisions** may use a dedicated `AdpilotAuthorizationService` or registered `map_meta_cap` mapping where semantics are fully documented. Examples of proposed meta operations: `adpilot_edit_campaign` (requires object ID), `adpilot_edit_zone` (requires site/zone ID), `adpilot_view_invoice` (requires invoice ID), `adpilot_publish_layout_version` (requires revision + contract-impact checks).

`map_meta_cap()` maps a meta capability to primitive capabilities; it does **not** replace full application checks for organization membership, state, privacy and current commercial contract conditions. WordPress Super Admin has special capability behavior; mandatory site/org/data restrictions must still be explicitly checked. Do not globally register rules that accidentally change core WordPress `edit_post` semantics.

---
## 5. Organization membership: authoritative data contract

### 5.1 Data entities and ownership

R1 SHALL support WordPress identities belonging to multiple advertising organizations. Organization roles are separate from WordPress roles; the active membership and status are read from an authoritative site-scoped store for every protected request.

| Entity | Key fields (proposed) | Integrity requirements |
|---|---|---|
| `Organization` | `id`, `site_id`, `status`, `name`, `owner_policy`, `created_at`, `updated_at`, `version` | `site_id` immutable; suspension takes effect immediately for privileged actions; no organization exists across sites by ID alone. |
| `OrganizationMembership` | `id`, `site_id`, `organization_id`, `wp_user_id`, `role`, `status`, `grant_version`, `created_by`, `created_at`, `updated_at`, `revoked_at` | Unique active membership per (site, org, WP user), referential validation, monotonic version, explicit revocation. |
| `MembershipInvitation` | `id`, `site_id`, `organization_id`, `invited_email_hash`, `role_offered`, `token_hash`, `expires_at`, `status`, `issuer_user_id`, `accepted_user_id`, `created_at` | Opaque high-entropy one-use token; store hash, finite TTL, reject stale or role-changed offer. |
| `ScopedPermissionGrant` | `id`, `site_id`, `organization_id`, `membership_id`, `permission`, `resource_scope`, `expires_at`, `grantor`, `approved_by`, `version` | Optional narrow exceptions only; no wildcard site capability from an org grant. |
| `MembershipAudit` | `id`, `site_id`, `actor_id`, `target_user_id`, `organization_id`, `old_role`, `new_role`, `reason`, `event_at`, `request_correlation_id` | Append-only, redacted, retained per approved policy. |
| `OrganizationContext` | Request-scoped `site_id`, `organization_id`, `membership_version`, `acting_user_id` | Resolved server-side; never persisted as the sole grant. |

**Naming:** Existing proposed `adpilot_memberships` from the architecture plan remains the authoritative table; the detailed schema document will specify exact SQL types, indexes, and constraints. Never implement authorization from a JSON usermeta list of organization IDs without authoritative membership/state checks.

### 5.2 Organization status and member status

- Organization statuses: `pending_verification`, `active`, `suspended`, `closed` (exact lifecycle documented by advertiser domain). Default effective privileged access requires `active`; pending organizations may access only expressly approved onboarding operations.
- Membership statuses: `invited`, `active`, `suspended`, `revoked`, `expired`. An invitation record is not an active membership.
- Member role changes increment `grant_version` and invalidate server-side permission cache entries and previously issued protected export links.
- Enforce one or more approved active owners per organization; **last-owner removal** cannot succeed without a safe ownership-transfer/closure process.
- Site administrator actions suspending an org do not erase already settled financial records or silently cancel existing contract remedies.

### 5.3 Organization roles as permission sets

Org operation names are identifiers within the AdPilot authorization service, not interchangeable with WP role slugs.

| Operation ID | Owner | Campaign Manager | Analyst | Billing | Additional guards |
|---|---:|---:|---:|---:|---|
| `org.profile.view` | Y | Y | Y | Y | Own organization, safe fields only |
| `org.profile.edit` | Y | — | — | — | Approved mutable fields; version check |
| `org.members.list` | Y | — | — | — | No sensitive invite token fields |
| `org.members.invite` | Y | — | — | — | Role ceiling; invitation rate limit |
| `org.members.change_role` | Y | — | — | — | Cannot grant site capabilities or silently demote last owner |
| `org.members.revoke` | Y | — | — | — | Prevent last-owner removal; self-removal special case |
| `org.campaign.list` | Y | Y | Y (read) | Limited | Own org only, field projection differs |
| `org.campaign.create` | Y | Y | — | — | Organization active, quotas/policies |
| `org.campaign.edit` | Y | Y | — | — | Editable state, version and asset ownership |
| `org.campaign.submit` | Y | Y | — | — | Required fields, asset/policy validation |
| `org.creative.upload` | Y | Y | — | — | Asset quotas, malware/content policy |
| `org.creative.edit` | Y | Y | — | — | New version requires moderation |
| `org.inventory.quote` | Y | Y | — | Y (conditional) | Public rates; own order context |
| `org.inventory.hold` | Y | Y (policy) | — | Conditional | Commercial mandate + capacity + TTL |
| `org.checkout.start` | Y | Y (policy) | — | Conditional | Separate spend approval, current quote |
| `org.billing.view` | Y | Limited | — | Y | Invoices/settlement scoped, field whitelist |
| `org.billing.dispute` | Y | — | — | Y | Valid paid order, correct organization |
| `org.analytics.view` | Y | Y | Y | Limited | Aggregate only, no raw visitor data |
| `org.analytics.export` | Y | Policy | Y | Limited | Export-specific grant, privacy minimums |
| `org.notifications.manage` | Y | Own | Own | Own | Only own preferences unless delegated |

`Conditional` means an independent organization policy or explicit scoped grant is required; it is **not** a blanket default. Commercial spending controls/approval thresholds must be specified in a separate product decision before implementing money-dependent permissions. If the policy is undefined, deny protected spend authorization rather than invent approval ceilings.

### 5.4 Organization context switching

1. List organizations where the user has a current active/pending-authorized membership; return only safe identifiers, names and membership roles.
2. User chooses active organization in the advertiser portal; store only a *preference* (UI state/server session), not an authority.
3. Every advertiser request carries an intended organization context through an allowed route/path/header; resolver loads and validates fresh membership and current status.
4. Changing context clears cached tenant-specific frontend state, query caches, optimistic mutations and export/download links.
5. Browser back/navigation must not silently restore protected cached data from a different organization.
6. Unauthorized context returns a nonenumerating denial, reselect guidance, and does not leak foreign organization existence.
7. A user who loses all memberships retains their WordPress account but loses advertiser resource access; other WP site privileges, if any, remain independently governed.

### 5.5 Invitation, acceptance and role-change protocols

**Invite:** Owner issues invite for an allowed organization role; backend verifies ownership, active org status, quotas, address normalization, duplicate pending invitations, and throttling. Token is generated cryptographically, stored as hash, and delivered using a privacy-safe message. The invitation URL gives no authority beyond presenting acceptance after identity proof.

**Accept:** Invitee signs in or creates a WordPress identity, demonstrates mailbox/control as configured, submits token, and confirms scoped role. Backend checks exact tenant, token hash, expiration, current invite version, owner/organization state and idempotency; creates or activates one membership in a transaction; marks invite consumed. Reject acceptance by a different identity when an invitation is identity-bound unless explicitly governed reassignment occurs.

**Role change/revocation:** Validate actor owner/admin authority, last-owner invariant, conflict-of-interest policy, requested role ceiling and current membership version; update once; emit invalidation and auditable event. Revalidate pending exports, in-progress payments, approvals and background jobs according to ownership/status. Revocation must not modify already completed financial evidence.

**Invitation attacks:** Enumerating known emails, invite replay, role tampering, expired invite reuse, stolen links, change role after invite issuance, race accepting same invite twice, and impersonation by support must all have negative automated tests.

### 5.6 Cross-organization agency scenarios

- Agency user can be `campaign_manager` in Org A, `analyst` in Org B, and have no role in Org C.
- Listing campaigns with an Org A context must not include Org B campaigns even though the same WP user has a separate B membership.
- An Org B analyst cannot call Org A write endpoints using an Org B read token, or use Org A session state to write to Org B.
- An advertiser's report must not leak account identities via `GROUP BY`, date filter inference, totals, export metadata, or aggregate cohort sizes.
- Publisher reporting can legitimately aggregate authorized organizations at the same site, but cannot expose data from other multisite sites by ID coincidence.

---

## 6. Object/resource authorization and mandatory scope predicates

### 6.1 Resource classification by owner

| Resource | Authoritative scope | Parent chain to validate | Read/write implications |
|---|---|---|---|
| Zone, container, slot | Site | Slot → container → zone → site | Publisher only for unpublished edits; public published availability is a separate projection. |
| Layout draft/version | Site | Layout version → binding → site | Publishing also checks commercial impact, not only capability. |
| Rate card | Site | Rate card version → slot/package → site | Published rates publicly readable; unpublished negotiations restricted. |
| Advertiser organization | Site + org | Membership → org → site | Org-specific membership; publisher verification separately granted. |
| Campaign | Site + org | Campaign → org → site | Publisher operations or active org member with matching action. |
| Creative/version | Site + org | Version → creative → campaign → org/site | Editors of an org cannot approve; moderators cannot change owner. |
| Quote/hold/reservation | Site + org | Reservation → quote → campaign/order → org/site | Expiration/contract state and requester identity required. |
| Order/payment/invoice | Site + org | Invoice → order → org/site; provider reference | Financial field projection distinct from campaign details; no mutable settlement via GET. |
| Delivery decision/event | Site + campaign/slot | Render instance → site/slot and campaign | Public request can render authorized content but cannot inspect backend decision secrets. |
| Heatmap aggregate | Site + page/layout/version | Aggregate → layout snapshot → site | Publisher R2 only; advertiser gets only approved aggregate campaign reports. |
| Raw behavioral event/replay | Site + restricted data class | Session/event → site, consent version | Elevated, purpose-limited R2/R4; NEVER advertiser default. |
| Report export | Site + org or publisher report scope | Export → requesting actor + site + scope snapshot | Permission rechecked at download; artifact access not public. |
| Queue job | Site + operation class | Job → site + provenance + target entity | Internal service authority, current domain state, bounded retries. |
| Audit log | Site + protected class | Event → site + actor/subject | Redacted privileged read; no end-user arbitrary search. |

### 6.2 Core predicates

Required logical guards (examples; service identifiers introduced by this document):

```text
P01 authenticated_user(actor)
P02 trusted_site(site_id) and resource.site_id == site_id
P03 permitted_operation(actor, operation, site_or_org_plane)
P04 organization_membership_active(actor, organization_id, site_id)
P05 organization_not_suspended(organization_id)
P06 resource_chain_valid(resource.site_id, resource.organization_id, parents)
P07 resource_state_allows(operation)
P08 financial_or_contract_policy_allows(operation, current_versions)
P09 data_class_and_purpose_allows(actor, operation, resource)
P10 required_dual_approval_and_reason_present(operation)
P11 feature_release_enabled(operation, site)
P12 target_wp_content_capability_allows(post_id, current_user)
P13 current_invitation_or_grant_version_matches()
P14 service_principal_and_job_provenance_valid()
P15 public_projection_fields_and_filters_allowlisted()
```

**Evaluation order:** Validate request shape → resolve authenticated principal (or explicit public operation) → derive site context → resolve target by site-scoped repository → resolve org/membership or site capabilities → enforce state/contract/data policy → apply purpose/approval constraints → execute one authorized use case with safe transaction boundary → emit audit. Exact order may be optimized if it does not leak existence or violate consistency, but no step may be skipped.

### 6.3 Authorization decision types

```text
ALLOW
DENY_UNAUTHENTICATED
DENY_CAPABILITY
DENY_SCOPE
DENY_INACTIVE_ORGANIZATION
DENY_STALE_MEMBERSHIP
DENY_RESOURCE_STATE
DENY_PRIVACY_POLICY
DENY_FEATURE_UNAVAILABLE
DENY_ADDITIONAL_APPROVAL_REQUIRED
DENY_RATE_LIMITED
```

The public REST response uses a small stable error taxonomy (`authentication_required`, `scope_denied`, `forbidden`, `resource_not_found`, `state_conflict`, `additional_approval_required`, `feature_unavailable`) and suppresses sensitive internal reasons. Internal audit/event records may retain a specific policy code if permitted by data controls.

**Anti-enumeration:** For a foreign/private object ID, both nonexistent IDs and out-of-scope IDs SHOULD normally use indistinguishable public `404` responses where endpoint contracts permit. Authentication-required uses `401`; authenticated known-but-denied site operations generally use `403`. Document 04's `403 scope_denied` is a valid normative response for endpoints designed that way; a `404` nonenumerating mapping is allowed only if the canonical API contract and tests are updated consistently. Do not expose other advertisers' identity in timing, errors, counts, or pagination metadata.

### 6.4 Context-aware policy engine API contract

```php
interface AuthorizationService {
    public function decide(
        ActorContext $actor,
        Operation $operation,
        ResourceReference $resource,
        RequestContext $context
    ): AuthorizationDecision;
}

final class AuthorizationDecision {
    public readonly bool $allowed;
    public readonly string $code;
    public readonly ?string $auditReason;
    public readonly array $safeUserHints;
}
```

Additional interfaces: `ActorResolver`, `SiteContextResolver`, `MembershipRepository`, `ResourceOwnershipResolver`, `CapabilityRegistry`, `PolicyRegistry`, `RestrictedDataPolicy`, `ElevationApprovalService`, `AuthorizationAuditSink`, `PermissionCacheInvalidator`.

**No authorization bypass:** Request contexts originating from REST, WP Admin, WP-CLI, cron, a public PHP wrapper, shortcode or Gutenberg block must all invoke the same sensitive-command policy boundary. The actor for a background job is a validated service identity, **not** an arbitrary `user_id` taken from the job payload.

### 6.5 Example decision algorithm (illustrative)

```text
authorize(actor, operation, reference, request):
  assert_known_operation(operation)
  validate_request_shape(request)
  site = derive_trusted_site_context(request.runtime)
  require_feature_enabled(operation.feature, site)
  if operation.public:
      require_public_allowlist(operation)
      require_safe_projection_and_rate_limit(operation, request)
      return public_policy_verdict(operation, site, request)
  require_valid_identity(actor)
  resource = repository.find_by_site(site, reference)
  if !resource: deny_non_enumerating()
  verify_parent_chain(resource, site)
  if operation.publisher_plane:
      require_site_capability(actor, operation.primitive_cap, site)
  else if operation.advertiser_plane:
      require_current_membership(actor, site, resource.org, operation.org_permission)
  else if operation.service_plane:
      require_verified_service_provenance(actor, operation, resource)
  else:
      deny()
  require_current_resource_state(operation, resource)
  require_contract_policy(operation, resource)
  require_protected_data_policy(operation, actor, resource)
  require_elevation_if_sensitive(operation, actor, resource, request)
  return ALLOW_WITH_SCOPED_QUERY_AND_FIELD_PROJECTION
```

The repository read must be scoped by trusted site (and, for advertiser data reads, preferably by authorized org). This is not merely an after-query filtering suggestion. Rechecking at mutation commit is mandatory when state changes concurrently.

### 6.6 Query-level and serializer-level enforcement

- Repositories querying advertiser resources SHALL require `site_id` AND authorized `organization_id` (or explicit publisher scope) in the SQL predicate.
- For multi-membership list views, only an allowlisted set of org IDs from the authoritative membership resolver is permitted; user-provided IDs must be intersected, never unioned unconditionally.
- Count, filter facets, totals, `GROUP BY`, search suggestion, export and pagination metadata must use the **same** scope predicate as rows.
- Field projections: advertiser campaign reports exclude competitor identifiers, raw IP addresses, visitor replay links and restricted fraud logic; billing profiles exclude secrets and unrelated finance fields; support reports are redacted.
- A filtered out row is not permission to infer it through total counts or descriptive error messages.
- Pre-signed artifacts, reports and cached queries are bound to site, principal, scope, permission version, purpose and expiry; download reauthorization is required.
- Enforce `LIMIT`/bounded cursor pagination; never leak whole-table collections before PHP filtering.

### 6.7 Cross-object mutation cases

| Mutation | Minimum enforced relationship |
|---|---|
| Attach creative to campaign | Same site + org; editable campaign; trusted version; reviewer remains separate. |
| Purchase slot | Same site; active publishable inventory; org buyer with commercial permission; current quote/hold; lock/availability policies. |
| Add campaign to allocation | Publisher AdOps; paid contract/reservation matches slot/site; active eligible campaign; version checks. |
| Publish layout | Publisher permission; slot IDs and contracts analyzed; explicit remedy/approval where required. |
| Approve creative | Publisher moderator permission; exact version; reviewer conflict-of-interest check where configured. |
| Issue refund | Publisher finance permission; ledger/payment reference, amount bounds, reason, provider reconciliation. |
| Request report | Publisher or org scope; approved metrics/fields; export limits and minimum cohort constraints where relevant. |
| Update privacy settings | Elevated site capability; validated impact and compliant retention/migration; complete audit. |
| Replay session (R4) | Site-specific elevated replay grant; approved purpose/case; consent and replay module enabled. |

---

## 7. Permission-aware UI/UX behavior and feature discovery

### 7.1 Core UX rule: policy truth before visual affordance

Frontend checks may tailor navigation and button visibility, but cannot serve as the authoritative authorization layer. Fetch a **scoped capability snapshot** (allowed operation affordances) from a dedicated authenticated REST endpoint; treat it as short-lived UX information only. The backend still rechecks the command.

### 7.2 Required permission states for every protected screen

| State | Required user experience |
|---|---|
| Anonymous | Explain sign-in requirement; preserve non-sensitive draft data and intended safe return URL. |
| No site capability | Accessible denied-state explanation without target details; no active privileged controls. |
| Not a member / wrong organization | Offer organization switch if eligible; do not identify foreign organizations. |
| Membership revoked while page open | Cancel pending sensitive operations; clear protected cached data; announce access change accessibly. |
| Suspended organization | Explain general account restriction and supported appeal/support path, excluding private investigative details. |
| Needs secondary approval | Show request/approval status and eligible next step; do not claim completion. |
| Feature not in enabled release | Hide ordinary unavailable controls; show clear roadmap label only where product policy permits. |
| Conflict/stale version | Present current accessible data and explicit refresh/retry path; never silently overwrite. |
| Service degraded | Show safe read-only state where possible; cannot represent uncertain payment/reservation state as success. |
| Operation allowed | Show only actions applicable to current resource state and permitted scope. |

### 7.3 Screens requiring tailored authorization UX

- **Advertiser portal / Org picker:** Active org name, current membership label, ability to switch, no cross-org dashboard caching.
- **Campaign wizard:** Check permitted edit/submit actions each step; preserve draft on expired quote; separate billing approver when configured.
- **Member management:** Role ceiling descriptions, confirmation for revocation/ownership transfer, accessible pending invitations, no raw token display.
- **Layout Studio:** Distinguish `can_edit_draft`, `can_publish`, `can_override_sold_slot`, `requires_contract_remedy`; read-only sold-slot diagnostics for insufficient privilege.
- **Creative review:** Moderator can approve exact version but cannot edit financial contract; review trail visible only to eligible publisher staff.
- **Finance console:** Show separate view, initiate, approve, execute and reconcile affordances; protect against an initiator approving the same adjustment under dual-control policy.
- **Gutenberg inspector:** Editors select approved zones only; forbidden slot parameters cannot appear in saved attributes; block previews never bill.
- **Heatmap explorer:** R2 analytics role sees eligible aggregates; unavailable restricted/replay actions remain denied and unadvertised as ordinary functionality.
- **Support diagnostics:** Redacted ticket-scoped information and escalation requests, not an “impersonate advertiser” shortcut.
- **Permissions manager:** Simulate potential grants, show source of current authority, diff grants before save, audit and enforce no-self-escalation.

### 7.4 Accessibility and privacy requirements

- Denial and state-change messages use text, proper ARIA/live-region semantics where necessary, not color alone.
- Role/member tables are keyboard-operable and responsive; complex grant diff/confirmation dialogs trap focus appropriately and restore it.
- Never prefill restricted data from a prior organization after switching tenants.
- Form validation distinguishes technical denial from eligibility/business-state rejection.
- Public frontend must not inject hidden admin metadata or permission-map internals into HTML.
- Make permission descriptions translatable, RTL-safe, and understandable to nontechnical publishers.

---
## 8. Authorization at every execution boundary

### 8.1 REST API and authenticated AJAX

All AdPilot REST endpoints register a `permission_callback`; public routes explicitly return a public authorization decision, not a blanket `__return_true` except where the route is independently safe and protected by its own public-policy logic. WordPress identity/session authentication is distinct from authorization. Cross-origin and browser-session protections must be configured for the deployment; REST nonces mitigate relevant CSRF risks but **do not confer identity or permission**.

| Route class | Example endpoint | Required rule |
|---|---|---|
| Public catalog | `GET /adpilot/v1/marketplace/placements` | Published-projection allowlist; sanitized fields and filters, bounded pagination; rate limits. |
| Public ad selection | `POST /adpilot/v1/delivery/decisions` | Strict validated site/render context; current delivery policy; bounded batches; no secrets. |
| Public event ingestion | `POST /adpilot/v1/delivery/events` | Signed/validated limited measurement envelope, anti-abuse rules; cannot authorize a bill. |
| My organizations | `GET /adpilot/v1/me/organizations` | Authenticated WP user; active/pending-allowed memberships only. |
| Organization members | `GET/POST /adpilot/v1/organizations/{org}/members` | `org.members.list/invite` + site/org predicate; no foreign org. |
| Organization campaign | `PATCH /adpilot/v1/organizations/{org}/campaigns/{id}` | Current member + `org.campaign.edit` + ownership + state/version. |
| Campaign submission | `POST /adpilot/v1/organizations/{org}/campaigns/{id}/submit` | `org.campaign.submit`, exact campaign/org, creative checks. |
| Quote/hold | `POST /adpilot/v1/organizations/{org}/reservations` | `org.inventory.hold`, quote validity, transactional capacity. |
| Checkout start | `POST /adpilot/v1/organizations/{org}/checkout` | Commercial grant, authoritative order mapping; idempotency. |
| Publisher inventory | `POST /adpilot/v1/admin/zones` | Site `adpilot_manage_inventory` + site policy. |
| Layout publication | `POST /adpilot/v1/admin/layouts/{id}/publish` | `adpilot_publish_layout`, current revision, contract-impact gate. |
| Creative moderation | `POST /adpilot/v1/admin/creative-versions/{id}/review` | `adpilot_moderate_creatives`, exact version/decision. |
| Refund | `POST /adpilot/v1/admin/orders/{id}/refunds` | `adpilot_adjust_finance`, approved amount/reason and provider evidence. |
| Publisher analytics | `GET /adpilot/v1/admin/reports` | `adpilot_view_aggregate_analytics`, site-scoped filters. |
| Heatmap (R2) | `GET /adpilot/v1/admin/heatmaps` | Site aggregate access; layout/version and cohort privacy policy. |
| Replay (R4) | `GET /adpilot/v1/admin/replays/{id}` | Elevated replay grant, purpose, site and consent/retention policy. |
| Export download | `GET /adpilot/v1/exports/{id}/download` | Recheck same scope, current user, permission version and artifact policy. |
| Diagnostics | `GET /adpilot/v1/admin/diagnostics` | Site `adpilot_view_diagnostics`, redacted response. |

**Route schema:** specify HTTP method, path template, auth method, required policy operation, required resource chain, input fields/validation, output field projection, HTTP statuses, idempotency key rules, pagination, rate-limit bucket, audit category, negative tests and release gate. Any route without a documented permission callback is a release blocker.

### 8.2 WordPress admin actions, forms and menus

- Administrative menus must use suitable WordPress capabilities for UI discovery, but server callbacks independently call `AuthorizationService` before any sensitive read/write.
- Validate the correct action nonce for state-changing browser requests, but follow with capability and object checks; nonce verification is **not** sufficient.
- Avoid GET side effects; no privileged changes from query parameters in a menu URL.
- When a role has read-only permission, display permitted reports without embedding writable endpoint credentials or hidden fields capable of altering settings.
- Restrict uploaded creative handling by MIME/content scan, organization ownership, quota and creative version state independently of media-library permissions.

### 8.3 Gutenberg, shortcodes, block attributes, theme PHP functions

- Gutenberg `adpilot/ad-slot`, `adpilot/ad-zone`, `adpilot/ad-repeater`, `adpilot/ad-showcase` are dynamic and use one shared rendering/eligibility service.
- An editor must satisfy `current_user_can('edit_post', $post_id)` (or equivalent target-content meta capability) **and** `adpilot_insert_blocks` to author permitted bindings. Editing Gutenberg content does not grant permission to create exclusive inventory or alter rates.
- The server validates the referenced zone/slot/site and approved publishing status on every render; block attributes `force`, `override`, `campaign_id`, arbitrary `site_id`, `billing=false`, `ignore_targeting` have **no** privileged meaning.
- Shortcode parameters and PHP render arguments are untrusted input for eligibility purposes even if invoked by trusted template code; they may select *which permitted zone* is requested, not redefine commercial policies.
- Calling `adpilot_render_zone()` can return safe ad markup, but cannot approve creative versions, modify campaigns, falsify impressions, or reveal admin statistics.
- A permitted PHP theme developer's underlying authority to deploy trusted PHP belongs to WordPress/hosting governance; AdPilot does not provide a PHP interpreter or admin arbitrary-code UI.
- Gutenberg editor/staging previews are marked nonbillable by authoritative server context. No client-controlled `preview=true/false` switch alone establishes billing status.

### 8.4 WooCommerce checkout and payment webhooks

- R1 WooCommerce checkout is an optional adapter; plugin bootstrap remains operational without WooCommerce (`FR-OPS-012`).
- AdPilot quote/hold/order ownership is resolved from durable server order mapping and validated site/org IDs; trust neither customer-submitted `order_id` nor redirect query parameters.
- The WooCommerce customer ID does not automatically grant membership in the AdPilot organization associated with an order.
- Payment confirmation comes from authoritative provider/WooCommerce state and verified hooks, not a browser return page.
- Webhook endpoints use provider-specific signatures/secrets and replay/idempotency validation. They may apply only predefined payment state changes; they cannot approve creative versions, grant organization roles or rewrite site settings.
- Finance adjustments require proper site capability + allowed payment/ledger transition, not simply a WooCommerce general edit-order capability.
- A provider callback affecting an order in another site is rejected even if its signature is technically valid for a different integration instance.

### 8.5 Background processing and WP-Cron/Action Scheduler

Define an explicit **service authority catalog**, for example `campaign.activate`, `reservation.expire`, `report.aggregate`, `export.generate`, `payment.reconcile`, `privacy.purge`, with exactly the resource actions the worker may perform.

Every job payload carries site ID, job type/schema version, target ID, originating domain command/event ID, operation correlation ID, creation timestamp and optional approved actor provenance. Validate all fields and server-origin registration. Before acting, select the correct site context and reload the target/current policy. Avoid `wp_set_current_user(admin_id)` as a shortcut for scheduled tasks. When an actor-initiated job is still subject to membership after enqueue, recheck that membership; when a system obligation must proceed after revocation (for example releasing an expired hold), authorize from the approved service-operation policy instead. Neither scenario lets arbitrary stale actor grants bypass policy.

Workers SHALL be idempotent. Logs and failure queues must exclude secrets and raw visitor fields. Retry endpoints must require `adpilot_retry_jobs` **and** job-class allowlist, bounded attempts and incident reason. A requeued finance job must not duplicate a payment or ledger effect.

### 8.6 WP-CLI and maintenance commands

- Interactive operator-triggered WP-CLI commands changing AdPilot data require explicit operator identity/role or separately authorized administrative execution context; WP-CLI running as the OS web user is not automatically a business authorization proof.
- Commands accepting `--url`, `--blog`, `--org`, `--campaign` must validate site and target-scope explicitly.
- Provide read-only diagnostics mode with redacted outputs; sensitive exports require elevated permission and destination safeguards.
- Automated deployment/migration commands can run as a controlled deployment principal with restricted command family; never reuse this power as a user-facing API.
- Maintenance/repair utilities require confirmation, audit and rollback planning, especially for financial and reservation state.

### 8.7 Exports, files and signed downloads

The export service must record authorized scope and current permission revision at creation and reauthorize the actor upon every download attempt. Private files must not be publicly accessible in `uploads` through a guessable name. Serve via a restricted controller or appropriately protected storage with an unguessable ephemeral URL that remains subject to current entitlement checks (or revocation by the trusted delivery layer). Never rely on expiration alone to handle mid-window membership revocation. Exported reports use scoped fields, bounded data windows, cohort suppression as appropriate, content type and safe filename handling, and explicit export purpose. Staging/debug exports must not include production secrets or visitor recordings.

### 8.8 Webhooks and outbound integrations

- Every outgoing webhook subscription is owned by one site and registered by an actor with `adpilot_manage_integrations` or a narrower approved grant.
- Subscriber can select only allowlisted event classes and approved payload projections; protect against SSRF and secret disclosure.
- Sign outbound payloads; delivery job may read only the predefined data class of its event.
- Reverify endpoint ownership/enablement on retries; maintain independent secrets per integration/site.
- Secrets should be stored via a supported secure credential strategy; redacted in admin, logs, export, support diagnostics and error messages.

### 8.9 Caches, CDN, transient snapshots and frontend state

- Cache keys must include site ID, permission/membership version and applicable organization ID for private views. Do not persist personally scoped results under a public shared cache key.
- Public ads may be CDN cached only where decisions are safe for the cache scope; campaign eligibility needs freshness/invalidation and emergency-deny behavior (`BR-008`).
- Heatmap dashboards/reports are never served from a public-page cache as if they were public content.
- Permission snapshots used by React are hints with short TTL; stale optimistic UI is acceptable only until the authoritative server rejects the action, at which point private data is cleared.
- Logouts, suspension and membership changes trigger invalidation signals and at least authoritative next-request denial.
- Service workers/PWA caches (if introduced) must not cache protected advertiser or publisher API responses for offline reuse unless a separately threat-modeled, encrypted and bounded policy is approved.

---

## 9. Multisite / multi-tenant isolation contract

### 9.1 Scope model

**WordPress multisite:** users may exist at network level and have different per-site roles. AdPilot resources are site-local by default. WordPress `switch_to_blog()` changes the site/database context; it does **not** make the caller authorized for the switched site's AdPilot resources. Any code switching site context MUST use a `try/finally` style restoration and explicit target-site authorization; this prevents cross-site global state leakage.

**Advertiser multitenancy:** within one site, multiple advertisers use distinct organizations. Their reports, assets, contracts, invitations, invoices, billing, campaigns and saved drafts are isolated by org scope. A WordPress Super Admin or site Administrator may be permitted to operate site-owned records but cannot treat other sites or sensitive replay as automatically authorized by that status alone.

### 9.2 Mandatory site-specific checks

- Confirm blog/site exists, is active, supported, and belongs to the expected network when applicable.
- Derive site from trusted routing/runtime; an arbitrary `site_id` sent in a REST body cannot establish context.
- Enforce site ID at the repository/query boundary and validate aggregate/dependent entities' site relationships.
- Use per-site roles/capabilities or explicit approved network scopes, never a requester's generic logged-in status as site membership.
- On a network-admin action (R4), require `adpilot_network_governance`, approved operation/site list, additional approval when sensitive, and detailed audit of all touched sites.
- Protect site-specific integration credentials, WooCommerce mappings, queues, exports, cache entries, URL signatures and measurement data from cross-site use.
- Maintain support for data separation after site archival/migration and avoid silently reassigning a site ID's financial evidence.

### 9.3 Site switching reference pattern (illustrative)

```php
function adpilot_run_for_site( int $target_blog_id, callable $work ) {
    $actor = adpilot_actor_from_trusted_context();
    adpilot_authorize_network_or_site_operation( $actor, $target_blog_id, 'site.read_diagnostics' );
    switch_to_blog( $target_blog_id );
    try {
        return $work( $target_blog_id ); // Each sensitive query still enforces target site/resource.
    } finally {
        restore_current_blog();
    }
}
```

The example describes a *required* switching pattern and is not a copy-ready security implementation: error handling, API contracts, currently switched stacks, job identity, and cross-site policy must be filled in by the implementation team. Never rely only on a switch having occurred.

### 9.4 Multi-tenant storage options and decision

R1 default: site-prefixed custom tables under `$wpdb->prefix` with every org-owned row explicitly tied to its site and organization, or a formally reviewed shared-table alternative with enforced composite scoping. R4 centralized federation may introduce global index tables but cannot retroactively weaken source-site ownership. A global org UUID is not itself a cross-site data access right. The dedicated Database Schema document will freeze physical keys and referential constraints.

### 9.5 Cross-site test matrix

| Scenario | Expected result |
|---|---|
| User Admin on Site A, subscriber on B calls B private report with A token | B denies absent B authorization. |
| Same numeric campaign ID exists on A and B | Scope resolver returns only targeted site record, never whichever matches first. |
| Network Super Admin requests B visitor replay before R4 grant | Denied, even if core WP super-admin capability checks appear permissive. |
| `switch_to_blog(B)` callback throws | Site context restored in finally; later A requests use A prefix/site. |
| A payment callback signed for A references B order ID | Reject; no B state change. |
| Site A analytics worker receives B event payload | Reject or quarantine; no cross-site aggregate. |
| A export link copied to user on Site B | Deny. |
| User belongs Org A on Site 1 and same-named Org A on Site 2 | Membership does not transfer across sites. |
| Network admin attempts bulk migration of sold inventory | Requires R4 feature, site-specific contract policy and per-site audit. |

---

## 10. Sensitive and high-impact operations; elevation and separation of duties

### 10.1 Operations requiring elevated review

| Operation | Required controls | Release |
|---|---|---|
| Site capability delegation | `adpilot_manage_roles`, grant ceiling, no self-escalation, audit | R1 |
| Allocation override of sold slot | `adpilot_override_allocation`, reason, current contract-impact model, approved remedy, audit (`BR-018`) | R1 |
| Layout version publish affecting sold inventory | `adpilot_publish_layout`, contract impact, optional independent `adpilot_approve_layout_remedy` | R1 |
| Manual/complimentary contract authorization | Site-approved policy, distinct contract type, not fabricated payment | R1, `DEC-003`/`D04-DEC-09` |
| Emergency stop | `adpilot_emergency_stop`, incident reason, immediate invalidation and audit; safer rapid suspension path | R1 |
| Refund/ledger adjustment | `adpilot_adjust_finance`, trusted source, amount bounds, immutable ledger, optional `adpilot_approve_financial_override` | R1 |
| Change privacy/retention or integration secrets | `adpilot_manage_privacy` / `adpilot_manage_integrations`, audit and safeguards | R1 |
| Restricted event export | `adpilot_view_restricted_analytics` + dedicated export authorization, purpose, retention | R2+ |
| Replay access or enablement | `adpilot_view_replay` / `adpilot_manage_replay_policy`, recorded purpose, opt-in module, consent state | R4 |
| Cross-site federation or access | `adpilot_network_governance` with explicit site scope and data class | R4 |

### 10.2 Separation-of-duties rules

- A person who creates a payment adjustment must not approve their own adjustment when dual approval is required; grant union cannot satisfy two distinct approval actors.
- Advertiser cannot approve their own submitted creative solely from organization membership, even if their WP account also has other site permissions. Conflict-of-interest review policy for legitimate dual-role actors must be explicit and audited.
- A content editor cannot approve a sold-slot commercial substitution by editing the Gutenberg block.
- An AdOps user cannot rewrite a financial contract to make their preferred layout publishable.
- An emergency stop can prioritize safety and execute rapidly, but reactivation still requires normal entitlement/moderation/financial gates.
- Access to restricted analytics and access to identifiable session replay remain independent.
- Support agent escalation grants do not automatically allow impersonation or extraction of data.

### 10.3 Exceptional access request lifecycle

`requested → policy_checked → pending_secondary_approval (if required) → approved_for_scope → executed_once → audit_finalized`, with denial, expiration and cancellation paths. Each request includes actor, scope, affected resources, requested operation, reason, immutable request hash, expiry and approver(s). Execution rechecks current permissions and the underlying business state. Approval after resource/version change is stale and cannot be applied without re-review. External incident workflows cannot circumvent actual authorization.

### 10.4 No unrestricted “break glass”

If an emergency operational mode is offered, define a narrowly restricted set of safe actions (e.g. stop delivery, disable compromised integration), with short duration, incident ID, second-party oversight where viable and post-incident audit. Do **not** provide a magic bypass for private reports, refund authorization, session replay or cross-tenant access.

---

## 11. Authentication, credential lifecycle and session security

### 11.1 Primary user authentication

Use WordPress authentication/session mechanisms for human users; allow compatible two-factor plugins and site security controls. Do not create a parallel password database, password reset system or home-grown session cookie without a separately approved need and design. Advertiser portal can use WordPress authentication without exposing WordPress admin dashboards to customers. Protect WP Application Password use by appropriate capability, HTTPS, scope and owner policy; application passwords are still subject to the same authorization decisions.

### 11.2 Sessions, CSRF and authorization freshness

- Require HTTPS for authenticated deployment; enforce secure cookie attributes according to WordPress/site configuration.
- Use WordPress nonces for relevant browser-admin/REST CSRF protection; a nonce is not single-use and never an authority grant.
- Refresh server-side membership/capability state on protected actions, including report downloads and state transitions.
- Define bounded permission cache TTL and event-based invalidation; do not depend on logout alone after role revocation.
- Invalidate high-risk active sessions or demand reauthentication/step-up verification upon sensitive role change or suspected compromise as approved by security policy.
- Revalidate permissions after long-running editors, slow checkout or network reconnection.
- Never expose payment provider access tokens, provider secrets, or raw session-replay credentials to client applications.

### 11.3 Password changes, lost access and account recovery

Rely on WordPress's authentication/recovery mechanisms and any supported hardened provider. Organization ownership transfer or invitation recovery requires independent identity proof and administrator-approved procedures. Support tickets, invoice numbers, campaign URLs and screenshots are insufficient proof to transfer ownership or disclose private data.

### 11.4 Signed links, invitation links and bearer material

Tokens for invitations, downloads, webhook subscriptions, or secondary approvals are generated using cryptographically secure randomness; only hashes or protected encrypted forms are persisted as appropriate. Bind to site, resource, operation, expiry, and where feasible to authenticated actor. Enforce one-time use for true one-use tokens with atomic consume. WordPress nonces are **not** substitutes for one-time invitation or payment idempotency tokens.

### 11.5 Rate limiting and abuse protection

Protect login-adjacent flows, invitation issue/accept, sensitive object lookup, public inventory browsing, checkout, export creation, replay access, event ingestion and webhook handling. Use distinct rate-limit buckets by site, route, authenticated principal and trusted network attributes when available, with reasonable fail-safe behavior and privacy-respecting logging. Do not block authorized users based on a simplistic raw IP match without appeal/operational controls.

### 11.6 Support and privileged access

Support uses approved impersonation-free assistance by default: redacted diagnostics, reproduce-with-fixture workflows, customer-approved export or narrowly scoped one-time support access. If impersonation is ever proposed in R4, require explicit product/security/privacy review, customer approval, transparent session banners, audit and prohibition on accessing password/payment values. It is not an R1 feature.

---
## 12. Authorization audit, evidence and privacy classification

### 12.1 Auditable action taxonomy

| Category | Required action/event fields | Data cautions |
|---|---|---|
| Membership and grants | Inviter, member, site, org, old/new grant/version, approver, reason | Never log invitation token or credential. |
| Layout and allocation | Site, layout version, affected slots/campaigns, contract impact, override decision | Preserve commercial impact evidence but not unrelated private ads. |
| Creative moderation | Reviewer, exact asset version/checksum, moderation decision, reason | Store approved redacted creative reference; never execute submitted code. |
| Finance | Actor, invoice/order, ledger reference, amount in exact currency units, external authoritative evidence, approver | Avoid bank/card secrets and provider access tokens. |
| Privacy/tracking | Actor, purpose, consent/retention setting revision, site, compliance decision | No raw visitor input content. |
| Restricted analytics | Actor, resource/data classification, time range, approved purpose, export/replay access | Retention and breach-monitoring controls. |
| Sensitive export | Requester, export ID, filter scope hash, data class, created/downloaded/revoked timestamps | Signed link/token hashes only. |
| Service operations | Service/job ID, signed provenance, site, operation, idempotency, result | Do not log entire secret-bearing payload. |
| Security denial | Principal class, nonreversible correlation, site, operation, safe reason, timestamp | Minimize enumeration detail and personal data. |

### 12.2 Audit record envelope (proposed)

```json
{
  "schema": "adpilot.audit.v1",
  "event_id": "opaque_uuid",
  "site_id": "trusted_site_reference",
  "organization_id": "optional_authorized_scope",
  "actor_type": "wp_user|service|provider",
  "actor_id": "controlled_reference",
  "operation": "adpilot.override_allocation",
  "resource_type": "slot_allocation",
  "resource_id": "opaque_reference",
  "decision": "allow|deny|pending_approval",
  "policy_codes": ["DP-AUTH", "DP-OVR"],
  "reason_code": "administrative_remedy",
  "correlation_id": "opaque_trace_id",
  "request_version": "etag_or_expected_revision",
  "approval_reference": "optional_opaque_id",
  "occurred_at_utc": "2026-10-11T00:00:00Z"
}
```

The JSON is an illustrative schema, not real event data. Exact audit field types, encryption/retention and access pathways are governed by the Database, Security, Privacy and Operations documents. Financial audit events cannot be edited to hide earlier changes; use append-only corrections. A logging failure on a required auditable mutation must result in a safe transaction failure or durable outbox approach, **not** silently proceed unaudited.

### 12.3 Data classes and access tiers

| Classification | Examples | Access rule |
|---|---|---|
| Public | Published placement names, accepted formats, safe price terms | Explicit public read; sanitized projection only. |
| Organization confidential | Draft campaigns, organization member list, invoices, account-level reports | Active matching membership and action-specific grant. |
| Publisher internal | Global inventory, internal moderation notes, aggregated publisher revenue | Site-scoped publisher capability, field policies. |
| Finance restricted | Payment settlement references, refunds, adjustment trail | Explicit finance capabilities plus transaction/audit controls. |
| Security restricted | Integration secrets, fraud signals, detailed security logs | Narrow operator/purpose access; secrets normally never readable in UI. |
| Behavioral restricted | Raw interaction traces, sensitive analytics metadata | Elevated privacy-approved roles; raw fields minimized/retained briefly. |
| Replay highly restricted (R4) | Recorded navigation/DOM-derived replay data | Specific elevated replay permission, purpose and consent/retention checks. |
| Legal/audit evidence | Signed contract revisions, dispute actions, financial record history | Audited authorized process, retention obligations and minimal disclosure. |

### 12.4 Audit completeness and tamper concerns

Audit reports should detect unexplained state transitions, missing expected review/approval records, duplicate provider callbacks, membership revocation mismatches and privileged command use without incident/reason. Database administrators or server operators with underlying direct database access are an infrastructure trust boundary beyond ordinary WordPress role controls. Document this residual risk and use backups, integrity monitoring, least-privilege database access and external logging if required by commercial sensitivity.

---

## 13. Permission matrix: contract and interpretation

### 13.1 Matrix semantics

`Y`: included in proposed default role bundle, subject to every target/state/site/privacy gate. `N`: no default authority. `E`: explicit elevated grant/approval required, not automatically included merely by role membership. `O`: organization membership grants scoped to selected org. `C`: conditional delegation or secondary policy. `P`: explicit safe public operation. Matrix cells are **permission affordances** rather than a permit to ignore target-object checks. Values for roles are defaults to finalize with Product/Security sign-off.

Publisher columns: `ADM` Site Admin, `OPS` AdOps, `MOD` Moderator, `FIN` Finance, `ANA` Publisher Analyst, `EDT` Content Editor, `SUP` Support. Advertiser columns: `OWN` Owner, `MGR` Manager, `AAN` Advertiser Analyst, `BIL` Billing. `PUB` = anonymous/public. `NET` = R4 network operator.

**Important:** Document 04's operation-level matrix is authoritative on permitted/conditional actions. This section refines those defaults and may not widen access without an approved decision. In particular: R1 replay is never exposed, publishers' unrestricted revenue and advertisers' restricted visitor data are different data classes, and a publisher “view all” capability is scoped to **the current site**, not the whole WordPress network.

### 13.2 Scope and policy interpretation for matrix rows

Every row must include an `operation_code` mapped to a REST/CLI/domain use case and its explicit `target_scope`; a display capability may be broader than a business operation but cannot weaken a domain policy. When no match exists, use `DENY_UNMAPPED_OPERATION` and add a test before introducing the feature. A role bundle is not authorization evidence by itself.

### 13.3 Matrix-generated baseline operations

The complete matrix below is a normative proposal for implementation. R1 operations outside the matrix require a formal amendment; R2–R4 columns are disabled until their release gates pass.

| Operation | Release | Plane | ADM | OPS | MOD | FIN | ANA | EDT | SUP | OWN | MGR | AAN | BIL | PUB | NET |
|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| Browse public published ad catalog | R1 | Public | Y | Y | Y | Y | Y | Y | Y | P | P | P | P | P | N |
| Inspect publisher inventory drafts | R1 | Site | Y | Y | N | N | N | N | N | N | N | N | N | N | N |
| Create/change zone/container/slot draft | R1 | Site | Y | Y | N | N | N | N | N | N | N | N | N | N | N |
| Publish layout with no sold conflict | R1 | Site | Y | C | N | N | N | N | N | N | N | N | N | N | N |
| Publish sold-layout contract remedy | R1 | Site elevated | E | E | N | C | N | N | N | N | N | N | N | N | N |
| Exceptional allocation override | R1 | Site elevated | E | E | N | N | N | N | N | N | N | N | N | N | N |
| Edit/publish rate cards | R1 | Site | Y | C | N | Y | N | N | N | N | N | N | N | N | N |
| View site campaign operations | R1 | Site | Y | Y | C | C | C | N | N | N | N | N | N | N | N |
| Modify publisher-side campaign schedule | R1 | Site | Y | Y | N | N | N | N | N | N | N | N | N | N | N |
| Moderate an immutable creative version | R1 | Site | Y | N | Y | N | N | N | N | N | N | N | N | N | N |
| Emergency-stop unsafe advertising | R1 | Site elevated | Y | C | C | N | N | N | C | N | N | N | N | N | N |
| Verify/suspend advertiser organization | R1 | Site | Y | C | N | N | N | N | N | N | N | N | N | N | N |
| Grant publisher site capabilities | R1 | Site elevated | E | N | N | N | N | N | N | N | N | N | N | N | N |
| View publisher finance details | R1 | Site | Y | C | N | Y | N | N | N | N | N | N | N | N | N |
| Start governed refund/adjustment | R1 | Site elevated | E | N | N | Y | N | N | N | N | N | N | N | N | N |
| Approve independent financial adjustment | R1 | Site elevated | E | N | N | E | N | N | N | N | N | N | N | N | N |
| View publisher aggregate reports | R1 | Site | Y | C | N | C | Y | N | N | N | N | N | N | N | N |
| Request publisher report export | R1 | Site | Y | C | N | C | Y | N | N | N | N | N | N | N | N |
| View publisher heatmap aggregate | R2 | Site | Y | C | N | N | Y | N | N | N | N | N | N | N | N |
| View restricted/raw measurements | R2 | Site elevated | E | N | N | N | E | N | N | N | N | N | N | N | N |
| View session replay | R4 | Site elevated | E | N | N | N | E | N | N | N | N | N | N | N | N |
| Enable/configure session replay | R4 | Site elevated | E | N | N | N | N | N | N | N | N | N | N | N | N |
| Manage privacy and retention policy | R1 | Site elevated | E | N | N | N | C | N | N | N | N | N | N | N | N |
| Manage provider integration secrets | R1 | Site elevated | E | N | N | N | N | N | N | N | N | N | N | N | N |
| View redacted diagnostics | R1 | Site | Y | C | N | N | N | N | Y | N | N | N | N | N | N |
| Retry eligible failed job | R1 | Site elevated | E | C | N | C | N | N | C | N | N | N | N | N | N |
| Insert permitted AdPilot block in owned post | R1 | WP content | Y | Y | N | N | N | Y | N | N | N | N | N | N | N |
| Use public PHP/shortcode rendering API | R1 | Public renderer | P | P | P | P | P | P | P | P | P | P | P | P | N |
| View own organization profile | R1 | Organization | N | N | N | N | N | N | N | O | O | O | O | N | N |
| Edit own organization profile | R1 | Organization | N | N | N | N | N | N | N | O | N | N | N | N | N |
| Invite organization member | R1 | Organization | N | N | N | N | N | N | N | O | N | N | N | N | N |
| Change organization membership role | R1 | Organization | N | N | N | N | N | N | N | O | N | N | N | N | N |
| Revoke organization member | R1 | Organization | N | N | N | N | N | N | N | O | N | N | N | N | N |
| Transfer organization ownership | R1 | Organization elevated | E | N | N | N | N | N | N | C | N | N | N | N | N |
| Create/edit own organization campaign draft | R1 | Organization | N | N | N | N | N | N | N | O | O | N | N | N | N |
| Submit own organization campaign | R1 | Organization | N | N | N | N | N | N | N | O | O | N | N | N | N |
| Upload/revise own creative | R1 | Organization | N | N | N | N | N | N | N | O | O | N | N | N | N |
| View own organization campaign aggregates | R1 | Organization | N | N | N | N | N | N | N | O | O | O | C | N | N |
| Export own organization campaign aggregates | R1 | Organization | N | N | N | N | N | N | N | O | C | O | C | N | N |
| View own org invoices / settlements | R1 | Organization | N | N | N | N | N | N | N | O | C | N | O | N | N |
| Create own org quote | R1 | Organization | N | N | N | N | N | N | N | O | O | N | C | N | N |
| Create exclusive inventory hold | R1 | Organization | N | N | N | N | N | N | N | O | C | N | C | N | N |
| Initiate checkout / commercial order | R1 | Organization | N | N | N | N | N | N | N | O | C | N | C | N | N |
| Raise own organization billing dispute | R1 | Organization | N | N | N | N | N | N | N | O | N | N | O | N | N |
| View foreign organization campaign | R1 | Foreign org | N | N | N | N | N | N | N | N | N | N | N | N | N |
| Edit WordPress roles from organization portal | R1 | Forbidden | N | N | N | N | N | N | N | N | N | N | N | N | N |
| Directly access visitor replay as advertiser | R4 | Forbidden | N | N | N | N | N | N | N | N | N | N | N | N | N |
| View private cross-site resources | R1 | Foreign site | N | N | N | N | N | N | N | N | N | N | N | N | N |
| Modify network-governed defaults | R4 | Network | N | N | N | N | N | N | N | N | N | N | N | N | E |

### 13.4 Privilege calculation rules for the matrix

- `N` remains denied even if the UI displays a stale button. `O` is validated against the exact current organization; `C` never grants automatically.
- `E` is a separately elevated publisher grant, verified approval/workflow and audit as applicable; it cannot be inferred from `ADM`.
- `NET` applies to R4-only network-governed operations after enabled release and explicit site mapping.
- A user who is simultaneously `ADM` and an advertiser `OWN` must invoke a chosen permission plane. Combining roles must not remove anti-self-approval or scope restrictions.
- Public API rendering always re-evaluates contract, creative, schedule, density, state and tracking rules.
- Site administrator may access an advertiser campaign through a **publisher site-management use case**, not by pretending to be its org owner.

## 14. Authorization test suite — Given/When/Then contracts

Tests below extend `PERM-001..016`, `SCN-AUTH-*`, `AT-OPS-001`, `AC-009` and the overall Doc 02/03 acceptance catalogs. Tests are **planned**, not executed; each requires a deterministic fixture, expected HTTP/business code, actual runtime evidence, DB assertion and data-leak review before marking PASS.

### D05-T-001 — Foreign campaign ID in advertiser REST route

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-001; BR-011`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Advertiser A signed in, Org B active with private campaign.
- **When:** A requests B campaign by guessed ID.
- **Then:** Nonenumerating denial; no body/metadata leak.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-002 — Revoked member stale browser tab

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-002; UJ-06`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Manager has loaded own-org edit form.
- **When:** Owner revokes manager; manager submits form.
- **Then:** Server denies; protected UI cache cleared.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-003 — Billing member creative approval

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-003`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Billing user can read invoices.
- **When:** User posts creative approval API command.
- **Then:** Denied and audited if policy requires.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-004 — Manager escalates to org owner

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-004`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Campaign manager signed in.
- **When:** Calls membership update API for self.
- **Then:** Denied before update; no role grant.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-005 — Editor forged shortcode force override

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-005; FR-INT-010`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** WP editor authors page.
- **When:** Inserts force/ignore_approval shortcode attrs.
- **Then:** Attrs ignored/rejected; normal eligibility.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-006 — Moderator changes settlement

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-006`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Moderator approved for creatives.
- **When:** Posts financial adjustment.
- **Then:** 403; immutable finance unchanged.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-007 — Analytics role replay access

**Release:** R4. **Related:** `PERM-007`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Publisher analyst holds aggregate permission.
- **When:** Requests R4 replay without elevation.
- **Then:** Denied even if module installed.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-008 — Site admin foreign site

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-008`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Admin Site A only.
- **When:** Requests Site B report by ID.
- **Then:** Cross-site denial; no existence leak.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-009 — Revoked user signed export download

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-009`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Analyst generated report and signed link.
- **When:** Grant revoked; link opened.
- **Then:** Denied, artifact invalidated/re-authorized.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-010 — Support tries raw session by ticket

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-010`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Support holds redacted diagnostic role.
- **When:** Requests raw replay by ticket ID.
- **Then:** Denied; only escalated secure workflow allowed.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-011 — Valid nonce no capability

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-011`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Low privileged WP user with valid nonce.
- **When:** Calls site publish layout endpoint.
- **Then:** Denied; nonce adds zero authority.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-012 — Anonymous catalog injection

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-012`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Anonymous requests catalog.
- **When:** Adds internal fields/filter to query.
- **Then:** Reject/strip; only public schema fields.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-013 — Allocation override no reason

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-013; BR-018`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Elevated admin with valid login.
- **When:** Submits paid-slot override missing reason.
- **Then:** Reject; audit denied attempt.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-014 — Job after actor revocation

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-014`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Actor requested state-changing background task.
- **When:** Actor revoked; worker retries.
- **Then:** Check service policy and actor grant as relevant; no stale grant bypass.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-015 — Theme function cannot force ads

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-015; BR-007`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Trusted theme uses public ad rendering function.
- **When:** Passes campaign not approved in params.
- **Then:** Never serves unapproved creative.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-016 — Aggregate filter leaks foreign org

**Release:** R1 (or relevant later feature gate). **Related:** `PERM-016; BR-011`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Advertiser analyst has own report.
- **When:** Uses other org filter and group totals.
- **Then:** Scoped aggregation without foreign count/total.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-017 — User active in two orgs switches scope

**Release:** R1 (or relevant later feature gate). **Related:** `D05-AUTH`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** User is Manager A and Analyst B.
- **When:** Switch to B, attempt edit B campaign.
- **Then:** Denied edit, B reporting only; A data cleared.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-018 — No membership at all

**Release:** R1 (or relevant later feature gate). **Related:** `D05-AUTH`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Authenticated WordPress subscriber.
- **When:** Calls org invoices endpoint.
- **Then:** Denied; WP login alone insufficient.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-019 — Org suspended mid checkout

**Release:** R1 (or relevant later feature gate). **Related:** `D05-AUTH; DP-COM`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Owner holds active quote.
- **When:** Site suspends org before checkout start.
- **Then:** Checkout denied; payment not created.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-020 — Last owner revocation

**Release:** R1 (or relevant later feature gate). **Related:** `D05-ORG`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Single active owner.
- **When:** Attempts remove own only owner membership.
- **Then:** Denied; offer secure transfer/closure.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-021 — Invite replay

**Release:** R1 (or relevant later feature gate). **Related:** `D05-ORG`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Invitation accepted successfully.
- **When:** Reuse invite token.
- **Then:** No second membership or role update.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-022 — Invite role tamper

**Release:** R1 (or relevant later feature gate). **Related:** `D05-ORG`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Analyst invite token is valid.
- **When:** Alter accept payload role to owner.
- **Then:** Bound invite role prevails; escalate denied.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-023 — Invite expired

**Release:** R1 (or relevant later feature gate). **Related:** `D05-ORG`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Old owner invitation expired.
- **When:** User submits stale token.
- **Then:** Deny; no account membership.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-024 — Member invite different site

**Release:** R1 (or relevant later feature gate). **Related:** `D05-ORG; DEC-009`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Invitation Site A.
- **When:** Use token on Site B.
- **Then:** Deny; site-bound token.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-025 — Membership revocation during long export

**Release:** R1 (or relevant later feature gate). **Related:** `D05-EXP`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Analyst export queued.
- **When:** Grant revoked before generation/download.
- **Then:** Worker and download enforce current policy.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-026 — Report filter count inference

**Release:** R1 (or relevant later feature gate). **Related:** `D05-DATA`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Own org with few events.
- **When:** Query foreign org facet/date/limit.
- **Then:** No foreign counts, facets or timing disclosure.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-027 — Report file published under uploads

**Release:** R1 (or relevant later feature gate). **Related:** `D05-EXP`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Export generated.
- **When:** Guess raw file path.
- **Then:** No public delivery of restricted file.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-028 — Read-only analyst sends PATCH

**Release:** R1 (or relevant later feature gate). **Related:** `D05-UI`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Publisher analytics user.
- **When:** PATCH rate card from browser console.
- **Then:** 403; no mutation.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-029 — GET side-effect attempt

**Release:** R1 (or relevant later feature gate). **Related:** `D05-REST`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Site editor visits crafted link.
- **When:** URL requests role change.
- **Then:** No mutation; CSRF safeguards.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-030 — Cross-site identical numeric IDs

**Release:** R1 (or relevant later feature gate). **Related:** `D05-MULTI`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Site A and Site B campaigns same ID.
- **When:** Request B ID from A.
- **Then:** Only A-scoped lookup; no B data.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-031 — Site switch exception recovery

**Release:** R1 (or relevant later feature gate). **Related:** `D05-MULTI`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Network worker switches from A to B.
- **When:** Callback throws exception.
- **Then:** Restore correct site context in finally.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-032 — Super admin bypass attempt

**Release:** R1 (or relevant later feature gate). **Related:** `D05-MULTI`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** WP network Super Admin no R4 federation policy.
- **When:** Request restricted B replay via site A API.
- **Then:** Explicit AdPilot tenant/data restriction denies.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-033 — Forged service job payload

**Release:** R1 (or relevant later feature gate). **Related:** `D05-JOB`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Anonymous knows job class name.
- **When:** Call job execution endpoint with ID.
- **Then:** No service provenance; denied.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-034 — Job cross-site payload

**Release:** R1 (or relevant later feature gate). **Related:** `D05-JOB`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Worker Site A queue.
- **When:** Payload target belongs to Site B.
- **Then:** Quarantine/deny; no B changes.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-035 — Revoked site grant queued job

**Release:** R1 (or relevant later feature gate). **Related:** `D05-JOB`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Publisher initiates future sensitive export.
- **When:** Grant revoked before run.
- **Then:** Cannot run solely on stale actor grant.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-036 — Duplicate provider webhook

**Release:** R1 (or relevant later feature gate). **Related:** `D05-WEBHOOK; DP-COM`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Provider sends same authorized event twice.
- **When:** Two concurrent receipts.
- **Then:** One idempotent financial effect.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-037 — Provider forged signature

**Release:** R1 (or relevant later feature gate). **Related:** `D05-WEBHOOK`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Attacker posts paid status.
- **When:** Invalid signature/replay proof.
- **Then:** Reject; no payment mutation.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-038 — Provider site mismatch

**Release:** R1 (or relevant later feature gate). **Related:** `D05-WEBHOOK`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Valid webhook signed for Site A.
- **When:** Order mapped to Site B.
- **Then:** Reject; no cross-site payment effect.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-039 — Payment redirect spoof

**Release:** R1 (or relevant later feature gate). **Related:** `D05-WEBHOOK; BR-003`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Advertiser opens return URL paid=true.
- **When:** No authoritative provider settlement.
- **Then:** Order remains truthful; no paid claim.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-040 — Owner creates publisher capability

**Release:** R1 (or relevant later feature gate). **Related:** `D05-PRIV`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Advertiser Owner controls org membership.
- **When:** Attempts assign adpilot_manage_settings.
- **Then:** Denied; organization plane cannot issue WP site caps.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-041 — Administrator self-approval

**Release:** R1 (or relevant later feature gate). **Related:** `D05-SOD`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Finance initiator is also approved role-holder.
- **When:** Attempts approve own high-risk adjustment.
- **Then:** Denied when dual-control required.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-042 — Sold-slot override without contract impact review

**Release:** R1 (or relevant later feature gate). **Related:** `D05-OVERRIDE; BR-006`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Authorized AdOps editing layout.
- **When:** Attempt publish commercial meaning change.
- **Then:** Denied until contract-remedy workflow approved.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-043 — Emergency stop then reactivation

**Release:** R1 (or relevant later feature gate). **Related:** `D05-STOP`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Moderator stops unsafe creative.
- **When:** Other staff attempts enable without reapproval.
- **Then:** Still blocked by normal gates.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-044 — Consent denial and raw collection

**Release:** R1 (or relevant later feature gate). **Related:** `D05-PRV; BR-017`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Visitor declines nonessential analytics.
- **When:** Replay SDK attempts to record.
- **Then:** Not initialized/collection prevented.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-045 — Raw replay leakage to advertiser

**Release:** R1 (or relevant later feature gate). **Related:** `D05-PRV`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Advertiser Analyst asks for heatmap data.
- **When:** Changes API URL to /replays.
- **Then:** Denied regardless report permission.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-046 — Stale cached report after org switch

**Release:** R1 (or relevant later feature gate). **Related:** `D05-CACHE`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** User views A report then switches to B.
- **When:** Browser back to A report.
- **Then:** Protected cached data cleared; A request guarded.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-047 — Shared CDN report cache

**Release:** R1 (or relevant later feature gate). **Related:** `D05-CACHE`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Publisher reads private dashboard.
- **When:** Anonymous requests same cache key.
- **Then:** No private response; cache private/vary isolation.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-048 — Public ad endpoint override

**Release:** R1 (or relevant later feature gate). **Related:** `D05-DEL`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Anonymous requests ad with force_campaign_id.
- **When:** Campaign paused or foreign site.
- **Then:** No forced rendering, policy rechecked.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-049 — Staging billable preview

**Release:** R1 (or relevant later feature gate). **Related:** `D05-DEL; BR-009`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Editor previews Gutenberg block on staging.
- **When:** Tracking event posted.
- **Then:** Excluded from paid counters.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-050 — Malformed payload and wildcard filters

**Release:** R1 (or relevant later feature gate). **Related:** `D05-REST`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Client posts unknown role, org, fields.
- **When:** Resolver validates typed route schema.
- **Then:** Reject unknown roles and invalid filters; no leakage.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-051 — Webhook subscription SSRF

**Release:** R1 (or relevant later feature gate). **Related:** `D05-INT`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Site editor tries own endpoint.
- **When:** URL resolves private address.
- **Then:** Reject per integration policy.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-052 — Security log leaks credentials

**Release:** R1 (or relevant later feature gate). **Related:** `D05-LOG`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Authorized support requests diagnostics.
- **When:** Secret exists in integration configuration.
- **Then:** Redacted response; no tokens.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-053 — Role schema upgrade regression

**Release:** R1 (or relevant later feature gate). **Related:** `D05-MIG`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Existing explicit grants present.
- **When:** Plugin upgrades capabilities.
- **Then:** Preserve intended grants; remove deprecated only via migration.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-054 — Deactivate/reactivate roles

**Release:** R1 (or relevant later feature gate). **Related:** `D05-MIG`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Plugin deactivated then reactivated.
- **When:** Inspect WordPress roles/org memberships.
- **Then:** No unauthorized duplicate/grant escalation.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-055 — Right-to-left role controls

**Release:** R1 (or relevant later feature gate). **Related:** `D05-UX`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Arabic/Farsi locale.
- **When:** Use organization members and permission editor.
- **Then:** Readable, keyboard-safe, proper mirrored layout.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-056 — Browser keyboard-only membership change

**Release:** R1 (or relevant later feature gate). **Related:** `D05-UX`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Owner navigates by keyboard.
- **When:** Invites analyst and revokes permission.
- **Then:** Success with accessible feedback/confirmation.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-057 — Mass assignment in REST

**Release:** R1 (or relevant later feature gate). **Related:** `D05-REST`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Manager PATCH campaign with organization_id of B.
- **When:** Server ignores/rejects protected fields.
- **Then:** Cannot change owner/tenant.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-058 — Upload asset foreign campaign

**Release:** R1 (or relevant later feature gate). **Related:** `D05-CREATIVE`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Manager A has creative upload grant.
- **When:** Attach asset to B campaign.
- **Then:** Denied; no orphaned public file.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-059 — Expired privileged approval

**Release:** R1 (or relevant later feature gate). **Related:** `D05-OVERRIDE`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Override approval expires.
- **When:** Execute after expiry or changed resource version.
- **Then:** Denied; fresh approval required.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-060 — Advertiser member bypasses cache via ID

**Release:** R1 (or relevant later feature gate). **Related:** `D05-DATA`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Org A user guesses B zone/private rate.
- **When:** Calls GET with exact resource id.
- **Then:** Public projection only or denied.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-061 — Reopen exported artifact with removed permission

**Release:** R1 (or relevant later feature gate). **Related:** `D05-EXP`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** User previously downloaded report.
- **When:** Copy link to another member.
- **Then:** Recheck actor/site/scope; deny.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-062 — CLI --url escalation

**Release:** R1 (or relevant later feature gate). **Related:** `D05-CLI`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Operator with site A authority.
- **When:** CLI command --url site B --refund X.
- **Then:** Target-site authorization denied.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-063 — Public catalog excessive filters

**Release:** R1 (or relevant later feature gate). **Related:** `D05-RATE`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Anonymous requests wildcard query and huge page size.
- **When:** Repeated calls.
- **Then:** Validated allowed filters, bounded pagination, rate limits.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### D05-T-064 — Consent revoked during replay

**Release:** R1 (or relevant later feature gate). **Related:** `D05-PRV`. **Layer:** API/integration; add browser or job variant where applicable.

- **Given:** Eligible R4 replay recording active.
- **When:** Visitor revokes consent.
- **Then:** Collection halts and retention policy applies.
- **Evidence:** Store request/actor/site/org fixture, permitted response projection, audit assertion, unchanged protected data and effective permission-version check.

### 14.1 Property-based and mutation testing requirements

Randomize cross-org and cross-site IDs, role combinations, session versions, pending invites, state transitions and query-filter sets. Assert no mutation crosses site/org, role ceilings hold, auth is consistent across API/CLI/jobs, and all claims of authority are defeated by missing required predicates. Mutation tests must prove authorization guards are tested, not merely present. Fuzz unknown role slugs, unexpected JSON properties, malformed signatures, stale ETags and incorrect nested parents.

### 14.2 Recommended permission test fixture topology

`Site-A` with Org-A, Org-B and one suspended Org-C; `Site-B` with Org-D; two campaigns each with paid/approved variations; one sold-layout conflict; two WP admins with disjoint site roles; one dual-role user; moderator, finance, publisher analyst, support, WP content editor; advertiser owner/manager/analyst/billing in each org; one revoked member and one expired invite; one queued export and one pending webhook. Build each fixture via public domain factories, not by bypassing state invariants arbitrarily.

### 14.3 Required test assertions

Each negative test must assert: correct denial code/HTTP mapping, no prohibited DB effects, no cross-tenant response fields (including counts), no unauthorized notification/webhook, no stale cache leak, correct audit where required, and accessible UI recovery when visible to a person. Each positive test must assert least-privilege success for an authorized actor, not only denial for everyone.

---

## 15. Bidirectional requirements and policy traceability

### 15.1 All 129 PRD functional requirement identifiers

This crosswalk references **all canonical requirement IDs exactly once**. The column “authorization plane” identifies the access-control review boundary, **not** a replacement of the complete functional behavior/acceptance tests in Doc 02. Implementers must still open the original requirement and `AT-*` row before coding.

| Canonical FR | Release | Authorization plane and restriction | Policy owners | Existing acceptance |
|---|---|---|---|---|
| `FR-INV-001` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-001` |
| `FR-INV-002` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-002` |
| `FR-INV-003` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-003` |
| `FR-INV-004` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-004` |
| `FR-INV-005` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-005` |
| `FR-INV-006` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-006` |
| `FR-INV-007` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-007` |
| `FR-INV-008` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-008` |
| `FR-INV-009` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-009` |
| `FR-INV-010` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-010` |
| `FR-INV-011` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-011` |
| `FR-INV-012` | R2 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-012` |
| `FR-INV-013` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-013` |
| `FR-INV-014` | R1 | Site Inventory/Layout | DP-AUTH, DP-INV, DP-LAY | `AT-INV-014` |
| `FR-INT-001` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-001` |
| `FR-INT-002` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-002` |
| `FR-INT-003` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-003` |
| `FR-INT-004` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-004` |
| `FR-INT-005` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-005` |
| `FR-INT-006` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-006` |
| `FR-INT-007` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-007` |
| `FR-INT-008` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-008` |
| `FR-INT-009` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-009` |
| `FR-INT-010` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-010` |
| `FR-INT-011` | R2 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-011` |
| `FR-INT-012` | R1 | WP content editor / Site integrator | DP-AUTH, DP-DEL, DP-LAY | `AT-INT-012` |
| `FR-MKT-001` | R1 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-001` |
| `FR-MKT-002` | R1 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-002` |
| `FR-MKT-003` | R1 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-003` |
| `FR-MKT-004` | R1 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-004` |
| `FR-MKT-005` | R1 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-005` |
| `FR-MKT-006` | R1 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-006` |
| `FR-MKT-007` | R1 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-007` |
| `FR-MKT-008` | R1 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-008` |
| `FR-MKT-009` | R1 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-009` |
| `FR-MKT-010` | R2 | Public / Advertiser Org / Publisher | DP-AUTH, DP-PRC | `AT-MKT-010` |
| `FR-CAM-001` | R1 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-001` |
| `FR-CAM-002` | R1 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-002` |
| `FR-CAM-003` | R1 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-003` |
| `FR-CAM-004` | R1 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-004` |
| `FR-CAM-005` | R1 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-005` |
| `FR-CAM-006` | R1 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-006` |
| `FR-CAM-007` | R1 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-007` |
| `FR-CAM-008` | R1 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-008` |
| `FR-CAM-009` | R2 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-009` |
| `FR-CAM-010` | R1 | Advertiser Org / Publisher Moderator | DP-AUTH, DP-MOD, DP-CRT | `AT-CAM-010` |
| `FR-COM-001` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-001` |
| `FR-COM-002` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-002` |
| `FR-COM-003` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-003` |
| `FR-COM-004` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-004` |
| `FR-COM-005` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-005` |
| `FR-COM-006` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-006` |
| `FR-COM-007` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-007` |
| `FR-COM-008` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-008` |
| `FR-COM-009` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-009` |
| `FR-COM-010` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-010` |
| `FR-COM-011` | R3 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-011` |
| `FR-COM-012` | R4 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-012` |
| `FR-COM-013` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-013` |
| `FR-COM-014` | R1 | Advertiser Org / Publisher Finance | DP-AUTH, DP-COM, DP-RES, DP-FIN | `AT-COM-014` |
| `FR-DEL-001` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-001` |
| `FR-DEL-002` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-002` |
| `FR-DEL-003` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-003` |
| `FR-DEL-004` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-004` |
| `FR-DEL-005` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-005` |
| `FR-DEL-006` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-006` |
| `FR-DEL-007` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-007` |
| `FR-DEL-008` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-008` |
| `FR-DEL-009` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-009` |
| `FR-DEL-010` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-010` |
| `FR-DEL-011` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-011` |
| `FR-DEL-012` | R2/R3 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-012` |
| `FR-DEL-013` | R1 | Public renderer / Publisher AdOps | DP-AUTH, DP-DEL, DP-DEN | `AT-DEL-013` |
| `FR-MET-001` | R1 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-001` |
| `FR-MET-002` | R1 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-002` |
| `FR-MET-003` | R1 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-003` |
| `FR-MET-004` | R1 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-004` |
| `FR-MET-005` | R1 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-005` |
| `FR-MET-006` | R1 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-006` |
| `FR-MET-007` | R2 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-007` |
| `FR-MET-008` | R2 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-008` |
| `FR-MET-009` | R3 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-009` |
| `FR-MET-010` | R3 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-010` |
| `FR-MET-011` | R3 | Public collector / Publisher Measurement | DP-AUTH, DP-MET, DP-RET | `AT-MET-011` |
| `FR-HEAT-001` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-001` |
| `FR-HEAT-002` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-002` |
| `FR-HEAT-003` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-003` |
| `FR-HEAT-004` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-004` |
| `FR-HEAT-005` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-005` |
| `FR-HEAT-006` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-006` |
| `FR-HEAT-007` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-007` |
| `FR-HEAT-008` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-008` |
| `FR-HEAT-009` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-009` |
| `FR-HEAT-010` | R4 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-010` |
| `FR-HEAT-011` | R2 | Publisher Analytics (R2) | DP-AUTH, DP-HEAT, DP-PRV | `AT-HEAT-011` |
| `FR-REP-001` | R1 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-001` |
| `FR-REP-002` | R1 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-002` |
| `FR-REP-003` | R1 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-003` |
| `FR-REP-004` | R1 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-004` |
| `FR-REP-005` | R2 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-005` |
| `FR-REP-006` | R3 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-006` |
| `FR-REP-007` | R3 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-007` |
| `FR-REP-008` | R2 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-008` |
| `FR-REP-009` | R2 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-009` |
| `FR-REP-010` | R1 | Advertiser Org / Publisher Analytics | DP-AUTH, DP-REP, DP-RET | `AT-REP-010` |
| `FR-UX-001` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-001` |
| `FR-UX-002` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-002` |
| `FR-UX-003` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-003` |
| `FR-UX-004` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-004` |
| `FR-UX-005` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-005` |
| `FR-UX-006` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-006` |
| `FR-UX-007` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-007` |
| `FR-UX-008` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-008` |
| `FR-UX-009` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-009` |
| `FR-UX-010` | R2/R4 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-010` |
| `FR-UX-011` | R1 | Authenticated site/org actor | DP-AUTH, DP-UX | `AT-UX-011` |
| `FR-OPS-001` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-001` |
| `FR-OPS-002` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-002` |
| `FR-OPS-003` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-003` |
| `FR-OPS-004` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-004` |
| `FR-OPS-005` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-005` |
| `FR-OPS-006` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-006` |
| `FR-OPS-007` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-007` |
| `FR-OPS-008` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-008` |
| `FR-OPS-009` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-009` |
| `FR-OPS-010` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-010` |
| `FR-OPS-011` | R4 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-011` |
| `FR-OPS-012` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-012` |
| `FR-OPS-013` | R1 | Publisher Security/Operations | DP-AUTH, DP-PRV, DP-OVR | `AT-OPS-013` |

### 15.2 Existing business rules and security applications

| BR | Authorization implication | Existing negative acceptance |
|---|---|---|
| `BR-001` | DP-INV with site/org/actor policy boundaries | `AT-BR-001`, `SCN-BR-001` |
| `BR-002` | DP-RES with site/org/actor policy boundaries | `AT-BR-002`, `SCN-BR-002` |
| `BR-003` | DP-MOD with site/org/actor policy boundaries | `AT-BR-003`, `SCN-BR-003` |
| `BR-004` | DP-REM with site/org/actor policy boundaries | `AT-BR-004`, `SCN-BR-004` |
| `BR-005` | DP-RES with site/org/actor policy boundaries | `AT-BR-005`, `SCN-BR-005` |
| `BR-006` | DP-LAY / DP-OVR with site/org/actor policy boundaries | `AT-BR-006`, `SCN-BR-006` |
| `BR-007` | DP-CRT with site/org/actor policy boundaries | `AT-BR-007`, `SCN-BR-007` |
| `BR-008` | DP-DEL with site/org/actor policy boundaries | `AT-BR-008`, `SCN-BR-008` |
| `BR-009` | DP-MET with site/org/actor policy boundaries | `AT-BR-009`, `SCN-BR-009` |
| `BR-010` | DP-DEN with site/org/actor policy boundaries | `AT-BR-010`, `SCN-BR-010` |
| `BR-011` | DP-REP / DP-AUTH with site/org/actor policy boundaries | `AT-BR-011`, `SCN-BR-011` |
| `BR-012` | DP-RET with site/org/actor policy boundaries | `AT-BR-012`, `SCN-BR-012` |
| `BR-013` | DP-REP with site/org/actor policy boundaries | `AT-BR-013`, `SCN-BR-013` |
| `BR-014` | DP-FIN with site/org/actor policy boundaries | `AT-BR-014`, `SCN-BR-014` |
| `BR-015` | DP-TIM with site/org/actor policy boundaries | `AT-BR-015`, `SCN-BR-015` |
| `BR-016` | DP-UX with site/org/actor policy boundaries | `AT-BR-016`, `SCN-BR-016` |
| `BR-017` | DP-PRV with site/org/actor policy boundaries | `AT-BR-017`, `SCN-BR-017` |
| `BR-018` | DP-OVR / DP-AUTH with site/org/actor policy boundaries | `AT-BR-018`, `SCN-BR-018` |

### 15.3 Nonfunctional requirements mapping

| NFR | Authorization interpretation / verification focus |
|---|---|
| `NFR-001` | Module boundaries and authorization architecture |
| `NFR-002` | Maintainable policy services and migration |
| `NFR-003` | Public renderer safe permissions |
| `NFR-004` | Low-cost server-side scope checking |
| `NFR-005` | Resilient policy and revocation |
| `NFR-006` | Financial authority/SoD |
| `NFR-007` | Security, authentication, least privilege |
| `NFR-008` | Consent/data classification |
| `NFR-009` | Accessible permissions UX |
| `NFR-010` | WordPress/multisite compatibility |
| `NFR-011` | Translated RTL-safe access UI |
| `NFR-012` | Auditing and diagnostics |
| `NFR-013` | Stable integration permission contracts |
| `NFR-014` | GPL-compatible authorization dependencies |
| `NFR-015` | Upgrade and grant migration safety |
| `NFR-016` | Human-centered denied/conflict recovery |

### 15.4 R1 product acceptance anchors

`AC-001..003` high-density layout and block permissions; `AC-004..005` safe delivery and event contexts; `AC-006..008` purchase/booking/payment/approval separation; `AC-009` advertiser tenant isolation; `AC-010..011` supported theme/render/measurement; `AC-012` privacy/consent; `AC-013..015` quality/operational/release obligations. **Exact original acceptance language remains in the PRD**, and its detailed test version remains in Document 02. This specification adds permission assertions for each without rewriting those criteria.

### 15.5 Journey, workflow and persona alignment

| Canonical journeys | Authorization focus |
|---|---|
| `UJ-01..06` | Public marketplace, org onboarding, commerce, membership; apply scoped actor privileges and accessible deny/recovery. |
| `UJ-07..08` | Creative moderation and availability; apply scoped actor privileges and accessible deny/recovery. |
| `UJ-09..12` | Layout publishing and Gutenberg/theme binding; apply scoped actor privileges and accessible deny/recovery. |
| `UJ-13..17` | Campaign delivery, reporting, finance and exceptions; apply scoped actor privileges and accessible deny/recovery. |
| `UJ-18..24` | Consent, heatmaps, incident and advanced financial workflows; apply scoped actor privileges and accessible deny/recovery. |
| `UJ-25..28` | Integration, support and later-release specialist workflows; apply scoped actor privileges and accessible deny/recovery. |

All `WF-01..18` and `UXA-01..28` remain canonical in Document 03; for every implementation story the work-package template must link the **individual** relevant `WF-*`, `UJ-*`, and `UXA-*` IDs rather than relying only on these group headings. Original `PERM-001..016` are covered individually by `D05-T-001..016`.

### 15.6 Access-control artifacts required for each implemented operation

| Artifact | Required contents | Owner |
|---|---|---|
| Operation registry entry | Code, release, permission plane, resource class, data class, business state gate | Security + Backend |
| Route/controller contract | Auth mechanism, WP capability/org grant, ownership resolver, status codes | API Engineer |
| Repository scope proof | Site/org WHERE predicates, parent references, field projection, tests | Database Engineer |
| UI affordance contract | Discoverable actions, empty/denied/loading/stale states, accessibility | UX/Frontend |
| Audit specification | Event schema and redaction, reason, correlation, retention | Security/Privacy |
| Negative acceptance | Foreign ID, revoked grant, stale version, mixed-role case, no mutation | QA |

---

## 16. Implementation work packages for GPT-6 High

Implementation MUST proceed in reviewable vertical slices. Do not instruct the coding model to implement the entire authorization subsystem in one unbounded run. Every package requires a clear input contract, module list, migrations, endpoints, UX behavior, tests, security proof and evidence of actually executed commands. Phase mapping follows the main architecture roadmap and the first four documents.

| Package | Deliverable | Lead roles | Primary dependencies | Definition of accepted evidence |
|---|---|---|---|---|
| `D05-WP-01` | Capability registry, role slugs, schema-versioned activation/migrations | WP Engineer, Security | WP bootstrap, `DEC-001` | Fresh install and idempotent upgrade tests, no unrelated WP role mutation. |
| `D05-WP-02` | Actor and trusted site-context resolvers | Backend, WP Engineer | WP-01 | Authentication/session/site tests; request site ID cannot override trusted context. |
| `D05-WP-03` | Organization and membership repositories and indexes | Database, Backend | WP-02; database ADR | Unique membership, site/org parent, status/version consistency tests. |
| `D05-WP-04` | Organization CRUD, invitation acceptance, role-change/revocation | Backend, UX, Security | WP-03 | Token/hash/TTL and double-accept tests; last-owner invariant. |
| `D05-WP-05` | Central operation registry and `AuthorizationService` | Security, Backend | WP-02..04 | Matrix-driven unit tests; default-deny on unmapped operation. |
| `D05-WP-06` | Query-scope and field-projection contracts | Database, API | WP-05 | Cross-org + cross-site list/detail/count/export denial tests. |
| `D05-WP-07` | REST policy and endpoint permission adapters | API, Security | WP-05..06 | Direct IDOR, CSRF and request-shape tests for every registered route. |
| `D05-WP-08` | Permission-aware advertiser portal and organization switcher | UX, React | WP-04..07 | Full screen states, keyboard flow, cache reset, stale grant recovery. |
| `D05-WP-09` | Publisher role/permission administration UX | UX, WP, Security | WP-01/05 | Grant diff, role ceiling, reason, accessible confirmation, rollback. |
| `D05-WP-10` | Gutenberg/theme capability enforcement | Gutenberg, WP Engineer | WP-05/07, rendering engine | Editor target-post check; injection/override negative tests. |
| `D05-WP-11` | Finance/layout elevated approvals and SoD | Finance, Backend, Security | WP-05, Domain Doc 04 | Owner-independent approver, audited override, retry concurrency. |
| `D05-WP-12` | Export storage and reauthorized download | Analytics, Security | WP-06/07 | Revoked/foreign-site link denial; no public artifact. |
| `D05-WP-13` | Webhook/queue/CLI service authority | DevOps, Integrations, Security | WP-05, jobs/payments | Signed provider, idempotency, provenance, site-check tests. |
| `D05-WP-14` | R2 analytics/heatmap access partition | Analytics, Privacy, Security | R2 measurement | Publisher aggregates only, advertiser restricted, purpose/consent enforcement. |
| `D05-WP-15` | R4 replay and federation permission layer | Security, Privacy, Multisite | R4 decision gates | Separate replay grant, case-purpose, cross-site audits, kill switch. |
| `D05-WP-16` | Continuous negative/security testing and operations guides | QA, Security, Docs | All | All 64 `D05-T-*` plus existing `PERM-*` applicable to release, reviewer sign-off. |

### 16.1 Recommended module location

```text
src/
  Modules/Identity/
    Domain/{Actors,Membership,Organization,Permissions}/
    Application/{Commands,Queries,Handlers,Policies}/
    Infrastructure/{Repositories,RoleRegistration,InvitationTokens}/
  Infrastructure/WordPress/{ActorResolver,SiteContext,Capabilities}/
  Infrastructure/Security/{Authorization,Audit,SecretRedaction}/
  Presentation/Rest/{PermissionCallbacks,Controllers,Schema}/
  Presentation/Admin/{Roles,PermissionDiagnostics}/
  PublicApi/Contracts/{AuthorizationOperation,ScopedContext}/
frontend/
  shared/{auth,permissions,tenant-context}/
  advertiser/{organization-switcher,memberships}/
  admin/{role-manager,security-audit}/
tests/{Unit/Authorization,Integration/Permissions,E2E/TenantIsolation,Security/AccessControl}/
```

Directories are illustrative; the later System Architecture and Module Boundaries specifications may refine names without weakening the contract. Keep permissions in the application/domain layer and adapters in WordPress/REST infrastructure; avoid embedding authoritative checks in React components.

### 16.2 Task prompt template

```yaml
work_package: D05-WP-XX
canonical_docs:
  - ADP-DOC-001
  - ADP-DOC-002
  - ADP-DOC-003
  - ADP-DOC-004
  - ADP-DOC-005
requirements: [FR-OPS-001, FR-MKT-004, BR-011, INV-07, AC-009]
proposed_operations: [org.members.invite, org.campaign.edit]
security_contract:
  - trusted_site_context
  - current_org_membership
  - resource_ownership_chain
  - domain_state_policy
  - field_projection_and_audit
negative_tests: [D05-T-001, D05-T-002, D05-T-004]
outputs:
  - code_and_migrations
  - tests_and_actual_results
  - documentation_and_changelog
  - security_privacy_review
acceptance:
  - policy_decisions_default_deny
  - no_foreign_data_leak
  - no_unauthorized_mutations
  - reproducible_test_evidence
```

Do not invent implementations of WordPress functions, provide fake test logs, or claim a test passed without executing it. If a conflict is detected, record the blocking question, evidence and owner rather than silently choosing a permissive interpretation.

---

## 17. Open decisions and product/security sign-off

The existing `DEC-001..012` and `D04-DEC-01..10` remain open where they have not been formally resolved. This document adds the following decisions; **none** silently changes the current PRD.

| New decision ID | Question | Accountable roles | Safe interim rule / release gate |
|---|---|---|---|
| `D05-DEC-01` | Should normal WP Editors receive `adpilot_insert_blocks` automatically? | Product, WP Lead, Security | Require explicit opt-in/grant on installation until decision. |
| `D05-DEC-02` | How much of an org's checkout/spending may a campaign manager or billing user initiate? | Product, Finance, UX | Conditional grants deny nonapproved spending. |
| `D05-DEC-03` | Does org ownership require exactly one owner or allow multiple? | Product, Legal, Security | At least one active owner; safe transfer; no last-owner removal. |
| `D05-DEC-04` | Which high-risk finance and allocation actions require separate approver? | Finance, Security, Product | Enforce separate approver for material adjustments until finalized; no self-approval. |
| `D05-DEC-05` | Which elevated grants are assigned to the default WP Administrator role? | Security, WP Lead | Minimal site ops; protected actions remain policy-gated. |
| `D05-DEC-06` | What exact behavior is required when a membership is revoked during checkout or asynchronous export? | Finance, Security, Product | Block new operations; preserve settled transaction evidence; remediation by authorized staff. |
| `D05-DEC-07` | Which attributes constitute support ticket-scoped temporary access? | Support, Privacy, Security | No impersonation; redacted diagnostics only. |
| `D05-DEC-08` | What is the policy for API/application passwords and independent OAuth adapters? | Security, Integrations | Use WordPress auth and narrowly approved providers; no custom bearer tokens by default. |
| `D05-DEC-09` | Which physical multisite table strategy is supported in R1 and R4? | DB Lead, Multisite Lead | Site isolation with deterministic prefix/site scoping; federation R4. |
| `D05-DEC-10` | Which raw analytics/replay purposes, cohorts, retention and access approvals are lawful? | Privacy, Legal, Analytics | No raw/replay access until reviewed and released. |
| `D05-DEC-11` | Is a publisher staff member allowed to moderate an advertiser campaign belonging to their own org? | Product, Security, AdOps | Require independent review for conflict-of-interest cases. |
| `D05-DEC-12` | What is the export artifact storage/delivery provider and revocation strategy? | Security, DevOps | Use nonpublic server-controlled storage and reauthorize download. |

A decision record must contain ID, proposer, approver, date, alternatives, rationale, relevant earlier identifiers, privacy/finance impact, exact affected files/interfaces, migration implications, acceptance tests and status `OPEN|APPROVED|SUPERSEDED`. An unresolved critical decision disables its dependent feature; it does not justify choosing the least restrictive behavior.

---

## 18. Release-specific authorization gates

### 18.1 R1 — market-ready foundation

Mandatory: WordPress capability schema, custom publisher roles, org membership/isolation, invitation+revocation, public catalog projection, 4 Gutenberg block guards, API/shortcode/theme equivalence, payment/order scope, campaign/creative segregation, site-specific delivery policy, safe reports, nonbillable preview, auditable finance and layout actions, consent/privacy foundations, diagnostic redaction and mandatory negative tests. Run all R1 `D05-T-*`, `PERM-001..016` where their modules exist, `AT-OPS-001`, `AT-MKT-004`, `AC-009` plus applicable PRD acceptance. Do not mark R1 ready if an org-scoped IDOR, finance authorization bypass or cross-site data leak is known.

### 18.2 R2 — visual intelligence

Add aggregate heatmap permissions, privacy purpose/data class restrictions, layout-version scoped reports, cohort protections, restricted analytics elevation where actually implemented, and tests for reidentification attempts, dynamic-layout attribution and export access. Do not authorize replay merely because heatmaps are enabled.

### 18.3 R3 — advanced monetization

Add policy grants for metered billing, disputes, account spend mandates, internal contract-adjustment workflows and reconciled CPM/CPC evidence. Avoid granting the ability to modify billable event counts to a campaign manager, content editor or ordinary analytics viewer. Financial authorization tests need real concurrency and provider-retry fixtures.

### 18.4 R4 — extended network and replay

Enable network governance only after cross-site federation ADR, restricted service identities, data-transfer policies, privacy review, replay purpose/consent, role elevation and incident-response tooling are approved. A network operator may be authorized to aggregate declared nonconfidential metrics, not implicitly access every site's raw visitor or financial records.

### 18.5 Zero-tolerance release defects

- Cross-organization or cross-site protected data disclosure.
- Any path by which advertiser/editor can grant themselves publisher privileges.
- Rendering of unapproved creatives through forged block/shortcode parameters.
- Unauthorized refund, ledger mutation, sold-slot override or campaign approval.
- Public exposure of restricted export/replay artifacts or integration credentials.
- Revoked member retaining server-authorized access through stale session or long-lived signed link.
- Unverified browser/provider events becoming authoritative payment or billable financial effects.

Noncritical wording/UI issues can be prioritized normally, but these classes block release until remediated and retested.

---

## 19. Final consistency audit and implementation handoff

### 19.1 Required completeness checklist

- [ ] All source documents 01–04 remain unchanged by this specification.
- [ ] Exactly 129 canonical `FR-*` IDs mapped to existing `AT-*` tests.
- [ ] All 18 `BR-*`, 16 `NFR-*`, 15 `AC-*`, 16 `PERM-*` anchors preserved.
- [ ] All 16 personas and 28 user journeys remain represented through governed use cases.
- [ ] Publisher site capabilities are separate from advertiser organization membership grants.
- [ ] WordPress Super Admin semantics cannot bypass AdPilot's explicit site/org/data gates.
- [ ] R1 safe default denies R2/R3/R4-only restricted operations.
- [ ] Every REST/admin/CLI/job/webhook/theme integration path has a policy entry and negative test.
- [ ] Permission grants, re-grants, revocations, reasoned overrides, and export downloads generate expected audit evidence.
- [ ] Site/context switching safely restores runtime scope.
- [ ] Frontend permission-aware flows are keyboard and screen-reader accessible and handle stale authority.
- [ ] Dependency/license and security assessments cover any new authorization library.
- [ ] No documentation test is represented as successfully executed without actual output/evidence.

### 19.2 Files/modules to hand off to the next specification

Use this document to define the interface of `AuthorizationService`, WordPress capability registration adapter, organization membership repository, per-resource authorization resolvers, permission-aware React hooks, safe REST/CLI/job wrappers and audit sink. Freeze physical tables/DDL in the later schema document, API payload types in the API document, and all UI states/interaction definitions in the specialized UX/design specifications. Avoid duplicate policy implementations per endpoint.

### 19.3 Official reference documentation (verify current versions at implementation time)

- WordPress Roles and Capabilities: <https://developer.wordpress.org/plugins/users/roles-and-capabilities/>
- WordPress `current_user_can()`: <https://developer.wordpress.org/reference/functions/current_user_can/>
- WordPress `map_meta_cap()`: <https://developer.wordpress.org/reference/functions/map_meta_cap/>
- WordPress REST API custom endpoints and `permission_callback`: <https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/>
- WordPress nonce guidance (not authorization): <https://developer.wordpress.org/apis/security/nonces/>
- WordPress multisite `switch_to_blog()`: <https://developer.wordpress.org/reference/functions/switch_to_blog/>
- WordPress multisite `restore_current_blog()`: <https://developer.wordpress.org/reference/functions/restore_current_blog/>
- WordPress plugin guidelines: <https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/>

These sources support platform implementation assumptions; they do not establish AdPilot business permission defaults, which require Product/Security review.

### 19.4 Next document

**ADP-DOC-006 — System Architecture, Architectural Decision Records & Module Boundaries.** It shall take this identity and access-control contract as a cross-cutting foundation, freeze module dependency direction and runtime composition, and define internal component interfaces, communication flows, deployment assumptions, failure boundaries and quality constraints. It must not introduce backdoor authorization shortcuts or weaken organization/site scoping.

---

**Document-end checkpoint:** Proposed access-control design is documentation only. Before production implementation, review and approve the security/finance/privacy open decisions, generate positive and negative automated evidence, and verify all permission routes against actual supported WordPress and WooCommerce versions.
