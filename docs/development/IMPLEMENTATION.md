# Implementation status — development preview 0.1.0

The plugin has working publisher and advertiser interfaces, but the complete 54-package product is not implemented or accepted. All 29 original specifications are preserved unchanged.

Implemented paths include site-bound PHP storage and authorization, compact inventory and immutable layout publication, native creative versions/review, campaign eligibility, fixed-price quotes, transactional holds, synthetic settlement application, shared rendering and a progressive browser runtime.

Before project cleanup, 24 local verification checks passed, including database race tests and clean ZIP installation. The delegated Chrome UI review passed at 1440, 768 and 375 pixels; its written report is preserved in [UI_REVIEW.md](UI_REVIEW.md). Raw logs, screenshots, generated JSON registries and the disposable WordPress/database installation were removed. These historical results do not certify the current release or a real payment gateway.

Runtime PHP and shipped assets are retained. The TypeScript source and build configuration are needed to rebuild the WordPress console/editor bundles. PHP/frontend tests and CI remain; Python governance and one-off verification automation have been removed. Use the [repository README](../../README.md) for current commands.

No package is labeled accepted or released. Required policy/ADR and independent release approvals remain unsigned. R2–R4 features remain unavailable. Major remaining work includes media quarantine, secure team invitations/ownership transfer, sold-inventory impact migration/rollback, full campaign targeting/lifecycle, qualified analytics/exports, notifications/providers, refunds/invoices and release certification.

## Package ledger

| Package | Release | Development state | Implemented | Remaining |
| --- | --- | --- | --- | --- |
| P28-WP-001 | R1 | PARTIAL | Governance/source hashes and baseline proposal | Signed decisions and WP001/WP002 dependency resolution Required upstream and owner acceptance remain unsigned. |
| P28-WP-002 | R1 | PARTIAL | Local fixture and archive install check | Complete runtime/theme/multisite/Woo matrix Required upstream and owner acceptance remain unsigned. |
| P28-WP-003 | R1 | PARTIAL | Pinned tools, build, SBOM and CI definition | Remote CI, signing and complete supply-chain review Required upstream and owner acceptance remain unsigned. |
| P28-WP-004 | R1 | PARTIAL | WordPress bootstrap and site-scoped services/ports | Full module/port contract and failure/recovery acceptance Required upstream and owner acceptance remain unsigned. |
| P28-WP-005 | R1 | PARTIAL | 23 InnoDB persistence units, checksum/downgrade guard | Remaining schema units and upgrade/backfill/restore matrix Required upstream and owner acceptance remain unsigned. |
| P28-WP-006 | R1 | PARTIAL | Capability routes and live organization policy | Complete actor/object/capability and concurrent revocation matrix Required upstream and owner acceptance remain unsigned. |
| P28-WP-007 | R1 | PARTIAL | Organizations, verification/suspension, member role/revoke | Secure invitations, ownership transfer and profile lifecycle Required upstream and owner acceptance remain unsigned. |
| P28-WP-008 | R1 | PARTIAL | Server consent gate and finite raw event retention | Privacy exporter/eraser, legal holds and retention jobs Required upstream and owner acceptance remain unsigned. |
| P28-WP-009 | R1 | PARTIAL | Typed versioned settings, safe defaults and readiness flags | Full policy registry and readiness dependencies Required upstream and owner acceptance remain unsigned. |
| P28-WP-010 | R1 | PARTIAL | Transactional typed outbox, retry leases and scheduler | Full inbox/job provenance, adapters and recovery matrix Required upstream and owner acceptance remain unsigned. |
| P28-WP-011 | R1 | PARTIAL | Scoped tokens, semantic responsive components and browser PASS | Complete interaction/keyboard/screen-reader component acceptance Required upstream and owner acceptance remain unsigned. |
| P28-WP-012 | R1 | PARTIAL | Working publisher and advertiser shells | Every destination and shell persistence/error boundary case Required upstream and owner acceptance remain unsigned. |
| P28-WP-013 | R1 | PARTIAL | Stable slots, compact containers and paginated search | Bulk slot lifecycle, binding/exclusion and capacity classes Required upstream and owner acceptance remain unsigned. |
| P28-WP-014 | R1 | PARTIAL | Draft/save/validate/publish and immutable snapshots | Full compiler, draft history/undo and controlled rollback Required upstream and owner acceptance remain unsigned. |
| P28-WP-015 | R1 | PARTIAL | Conservative published-geometry immutability guard | Contract impact/migration/reapproval, sold-inventory remedies and rollback Required upstream and owner acceptance remain unsigned. |
| P28-WP-016 | R1 | PARTIAL | Compact Studio, real 4-zone/4-container/500-slot inspector | Complete editing/drag/keyboard/undo and impact workflow Required upstream and owner acceptance remain unsigned. |
| P28-WP-017 | R1 | PARTIAL | Shared server renderer with light bounded placeholders | All context adapters, empty states and snapshot compatibility Required upstream and owner acceptance remain unsigned. |
| P28-WP-018 | R1 | PARTIAL | PHP helpers, shortcodes and explicit theme action | Traditional widgets, full context adapters and theme matrix Required upstream and owner acceptance remain unsigned. |
| P28-WP-019 | R1 | PARTIAL | Four dynamic block names, published selectors and previews | Real repeater/query-loop intervals and curated showcase semantics Required upstream and owner acceptance remain unsigned. |
| P28-WP-020 | R1 | PARTIAL | Immutable native versions/digests and CRE-003 successor route | Private raster quarantine, scanning/decode, canonical manifest and purge Required upstream and owner acceptance remain unsigned. |
| P28-WP-021 | R1 | PARTIAL | Reasoned exact-version reviews and delivery stop | Policy bundles, creative suspension/fences and full moderation state graph Required upstream and owner acceptance remain unsigned. |
| P28-WP-022 | R1 | PARTIAL | Drafts, separate eligibility gates, pause/resume/stop, paid/manual allocation | Complete campaign submission/changes/completion/cancel and selection lifecycle Required upstream and owner acceptance remain unsigned. |
| P28-WP-023 | R1 | PARTIAL | Exact fixed-window rate/quote snapshots and explicit no-tax policy | Approved tax/currency adapters, complete price/rate/discount lifecycle Required upstream and owner acceptance remain unsigned. |
| P28-WP-024 | R1 | PARTIAL | Transactional exclusive holds, retry identity and real race/expiry proof | Full binding/exclusion/capacity and cancellation/recovery policies Required upstream and owner acceptance remain unsigned. |
| P28-WP-025 | R1 | PARTIAL | Gated optional Woo CRUD/HPOS and settlement boundary | Actual Woo/gateway/HPOS, reversal and tax certification Required upstream and owner acceptance remain unsigned. |
| P28-WP-026 | R1 | PARTIAL | Append-only exact capture, duplicate replay and late-capture remediation | Refund/reversal/remedy/invoice/discrepancy/reconciliation lifecycle Required upstream and owner acceptance remain unsigned. |
| P28-WP-027 | R1 | PARTIAL | Published paginated placement discovery and quote/hold flow | Complete placement terms/estimates/packages/filters Required upstream and owner acceptance remain unsigned. |
| P28-WP-028 | R1 | PARTIAL | Portal native creative/campaign and gated quote/hold/order flow | Upload/wizard persistence, teams, invoices, notifications and checkout Required upstream and owner acceptance remain unsigned. |
| P28-WP-029 | R1 | PARTIAL | Publisher inventory/Studio/moderation/settings/reports | Complete finance/remedy/admin/operator workspaces Required upstream and owner acceptance remain unsigned. |
| P28-WP-030 | R1 | PARTIAL | Current joined delivery approval/org/contract/schedule/stop gates | Full contextual targeting, allocation policy, cache fences and arbitration Required upstream and owner acceptance remain unsigned. |
| P28-WP-031 | R1 | PARTIAL | Near-viewport runtime with bounded batches/queue/concurrency | Full backpressure/session stop and browser matrix Required upstream and owner acceptance remain unsigned. |
| P28-WP-032 | R1 | PARTIAL | Signed provenance, dedup, served vs untrusted claims, no billing | Qualified analytics, bot policy and aggregation/late-event pipeline Required upstream and owner acceptance remain unsigned. |
| P28-WP-033 | R1 | PARTIAL | Scoped counts/campaign projections and unknown revenue null | Bounded exports, aggregates and full metric semantics Required upstream and owner acceptance remain unsigned. |
| P28-WP-034 | R1 | PARTIAL | Diagnostics, redacted audit, CLI and retry | Notifications/preferences, support bundles and redacted exports Required upstream and owner acceptance remain unsigned. |
| P28-WP-035 | R1 | PARTIAL | Core ports and local controlled event hook | Signed extension contracts, provider registry/manifests, SDK/isolation Required upstream and owner acceptance remain unsigned. |
| P28-WP-036 | R1 | PARTIAL | Translation setup, extracted POT and logical CSS | Full gettext coverage, translations, locale/RTL certification Required upstream and owner acceptance remain unsigned. |
| P28-WP-037 | R1 | PARTIAL | Live REST isolation, token/SQL/immutability and release denial tests | Full threat plan, independent security/privacy review and scanners Required upstream and owner acceptance remain unsigned. |
| P28-WP-038 | R1 | PARTIAL | Real 500-slot/9-query render, one-winner/expiry races and Chrome review | Complete load/reliability/browser/theme/multisite benchmarks Required upstream and owner acceptance remain unsigned. |
| P28-WP-039 | R1 | PARTIAL | Focused PHP/frontend/integration tests and delegated UI PASS | Formal full R1 QA, assistive technology and accessibility acceptance Required upstream and owner acceptance remain unsigned. |
| P28-WP-040 | R1 | PARTIAL | Deterministic dev ZIP, clean install, SBOM and runbook | Production signing, restore, staged rollout and support certification Required upstream and owner acceptance remain unsigned. |
| P28-WP-041 | R2 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-042 | R2 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-043 | R2 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-044 | R2 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-045 | R2 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-046 | R3 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-047 | R3 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-048 | R3 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-049 | R3 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-050 | R3 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-051 | R4 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-052 | R4 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-053 | R4 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |
| P28-WP-054 | R4 | NOT IMPLEMENTED | Unavailable at the backend. | Implement complete package after accepted prerequisites and explicit release adoption. |

See [DEVELOPER.md](DEVELOPER.md), [RUNTIME.md](RUNTIME.md) and the preserved [baseline proposal](BASELINE_PROPOSAL.md).
