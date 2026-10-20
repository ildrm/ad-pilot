> Historical development record. The temporary test environment, Python tooling, generated registries and raw evidence referenced below were removed during project cleanup. Current commands are in the repository README.

# AdPilot initial repository audit

Date: 2026-10-20. Began in `AUDIT`, then implemented safe child
`P29-SL-001-01` of `P28-WP-001`. No production application was changed.
Initial repository: `main`, commit `515defba713ee2f6cecc975ba4e04ac918c756ca`.

## Source and repository evidence

All 29 supplied documents were read programmatically to hash their actual
bytes, index sections and extract canonical definitions and decision records:
4,773,625 bytes, 46,842 lines, 3,509 headings outside fenced examples.
Document 29's embedded reference hashes for Documents 01–28 match these copies.
The original source files were preserved unchanged.

Following Document 29 §2.3, semantic context was loaded selectively: all
document metadata and top-level outlines; PRD scope/invariants/requirements/
decisions; architecture ADRs/contracts/questions; Document 28's readiness,
package register, initial acceptance cards and decision table; Document 29's
audit/execution/evidence/conflict/review/governance-test sections. This is not
a claim that every future package's detailed specification has received
semantic review. Reload exact relevant sections before implementing each one.

The codebase-memory graph reported documentation nodes and containment edges,
without application symbols. Coverage was checked for README/license and
docs/src/tests/assets scopes; filesystem and Git inventory corroborated the
absence of application code. Initially only `LICENSE` and `README.md` were
tracked, alongside the user's untracked `docs/` and `.DS_Store`. No plugin,
manifests, migrations, tests, CI, WordPress fixture or previous evidence ledger
existed. No reset, staging, commit, branch switch or source rewrite was done.

## Extracted baseline

| Artifact | Actual contents |
|---|---|
| `source-manifest.json` | 29 SHA-256/size/line records; policy revision null |
| `source-index.json` | 3,509 section ranges excluding fenced example headings |
| `catalog.json` | 54 packages, 188 PRD definitions, 24 IC contracts, 24 ADR proposals |
| `decisions.json` | 572 distinct numbered definitions, all OPEN |
| `progress.json` | WP-001 BLOCKED; WP-002..054 NOT_STARTED; four unsigned baseline slots |

The 188 definitions comprise 129 FR, 18 BR, 10 INV, 16 NFR and 15 AC records.
`FR-DEL-012` retains `R2/R3`; `FR-UX-010` retains `R2/R4`.

The 572 decision records include all 12 PRD, 36 program and 20 orchestration
decisions. Related IDs are preserved, not merged as interchangeable approvals.
ADR proposals are separate. Unnumbered questions such as Document 07 §18
remain indexed and require package review. Only Document 28's explicit 36
decision-to-package mappings are automatically enforced; other records are
marked `REQUIRES_PACKAGE_REVIEW`. `next` screens prerequisites and cannot
establish the full Definition of Ready or authorize implementation.

## Environment observed, not an approved support matrix

| Component | Observation |
|---|---|
| Host | macOS Darwin 25.6.0, arm64 |
| PHP / Composer | 8.5.8 / 2.10.2 |
| Node / npm | v26.5.0 / 11.17.0 |
| Python | 3.14.5 |
| WP-CLI | 2.12.0; CLI info succeeds with a dependency deprecation on this PHP |
| WordPress / WooCommerce | No installed fixture or compatibility test |
| MySQL / MariaDB | No engine/version verified; WP-CLI reports no MySQL binary |
| Docker | Client 29.8.2; daemon cannot be reached at the configured socket |
| Browser / assistive technology | No product fixture or test executed |

Docker was initially denied by the sandbox; a permitted read-only retry
confirmed an unreachable daemon. No containers were started. Local tool
versions do not imply WordPress/PHP/database/browser compatibility.

## Blockers and safe dispositions

| Finding | Source | Owner and disposition |
|---|---|---|
| Baseline approvals absent | Doc 28 §7.1 WP-001; Doc 29 §9 WP-001 | Product/Architecture and P0/S0/F0/A0 owners; proposals only |
| Runtime prerequisite inconsistency | Doc 28 §7 table versus §7.1 WP-001 card | Architect/WP/QA: table places WP-002 after WP-001; card requires confirmed runtime matrix in WP-001. Preserve table DAG; matrix unknown; no fabricated dependency completion |
| Runtime matrix unset | DEC-001; P28-DEC-001; P29-DEC-005 | WP/Platform/QA; select exact versions and real fixtures |
| Tax, precision, holds and remedies unresolved | DEC-007; P28-DEC-002..006; Doc 21 §17 | Product/Finance/Legal; no invented financial policy or live checkout |
| Consent and retention unresolved | DEC-005/006; P28-DEC-010/011 | Privacy/Legal/Finance/SRE; no prohibited collection |
| Outbound webhook release conflict | P28-DEC-012; Doc 29 §12 | Product/Architecture/Security/SRE; no functional subscription/egress |
| Approval pointer and active creative-version gaps | Doc 22 §21.1; Doc 29 §12 | Creatives/DB/API/Architecture; no guessed migration or ambiguous serving |
| Editor slot/showcase lookup gap | P28-DEC-014; Doc 16 §21 | WP/API/Identity/UX; no invented route or DTO |
| R2 contributors, events and geometry gaps | P28-DEC-027..030 | Privacy/Data/Layout/Measurement; R2 unavailable |
| R3 billing/disputes and R4 replay/network gates | P28-DEC-031..036 | Finance/Privacy/Security/Identity; later capabilities unavailable |

Independent safe source/audit/fixture work proceeded in the WP-001 child.
These blockers prevent parent acceptance and premature dependent execution.

## Next action

See `REVIEW.md`, `P29-SL-001-01.md` and `evidence/manifest.json` for review,
scope, actual commands/exits and hashes. Internal review is not independent
human approval. No product, WordPress compatibility, finance race,
accessibility, privacy, deployment, restore or release is certified.

Review `BASELINE_PROPOSAL.md`, record actual baseline dispositions, resolve
the runtime prerequisite inconsistency, and complete WP-001's exit gate.
WP-002 is the next dependent package, subject to its runtime decision and
a usable fixture environment.
