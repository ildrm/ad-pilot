> Historical development record. The temporary test environment, Python tooling, generated registries and raw evidence referenced below were removed during project cleanup. Current commands are in the repository README.

# Proposed WP-001 baseline for owner review

Status: **PROPOSED; unsigned.** No tax, consent, retention, refund, capacity
or billable-measurement policy is approved by this file.

Adopt the supplied source versions and Document 28 §7's 54-package DAG.
Start with R1; keep R2 heatmaps, R3 metering and R4 replay/federation
unavailable until their own verified implementations and approvals.

Use the canonical single WordPress modular monolith, 18 module boundaries,
domain/application ports and WordPress adapters, React/TypeScript application
UIs and a small public SDK. Keep WooCommerce optional for nonpayment features.
Retain the 24 IC contracts and 24 ADRs as proposals pending their owner review.

Preserve separate commercial, creative, campaign, allocation, delivery,
measurement and ledger states; current actor/site/org/object authorization;
one rendering facade for four blocks/PHP/shortcodes/hooks; stable slots and
immutable layouts; distinct configured/served/rendered/viewable/billable
facts; and per-package WCAG, keyboard, RTL, localization and bounded high-density
performance acceptance.

## Proposed disposition of the 12 PRD decisions

These are review proposals, not chosen answers. Reviewer roles are requested
owners, not claims of consultation. Later decisions gate their affected work.

| ID | Concrete proposed disposition | Requested reviewer / gate |
|---|---|---|
| DEC-001 | Select exact minimum/current WP/PHP/MySQL/MariaDB/browser versions and Woo/theme fixtures. Local PHP 8.5.8 is an observation; PRD PHP 8.2+ is a preference. | WP/Platform/QA; before WP-002 acceptance |
| DEC-002 | Optional R1 WooCommerce checkout, after selecting and testing actual gateway/HPOS support. | Product/Commerce/Finance/Security; before checkout |
| DEC-003 | Explicit authorized manual/complimentary entitlement only; no invented payment/invoice. | Product/Finance; before entitlement behavior |
| DEC-004 | Review image + safe text/native formats; resolve private storage and approved-version contracts. | Creative/Security/UX; before upload/serving |
| DEC-005 | Approve a purpose/region/consent map; no prohibited collection while open. | Privacy/Legal; before collection |
| DEC-006 | Define finite retention, erasure and financial legal holds; no invented day counts. | Privacy/Finance/SRE; before production storage |
| DEC-007 | Define quote/hold/extension/late-capture/cancellation/refund behavior and tests; no invented TTL or refund promise. | Product/Finance/Legal; before commerce |
| DEC-008 | Name and test exact classic/block themes and any supported builders. | WP/QA; before support claims |
| DEC-009 | Site isolation R1, federation R4; separately review exact network authority. | Identity/Security/Architecture |
| DEC-010 | Disable chargeable CPC/CPM until independently certified R3 qualification and reconciliation. | Finance/Measurement |
| DEC-011 | AdPilot remains a working name; slug/trademark/distribution clearance pending. | Product/Legal/Release; before distribution |
| DEC-012 | Review English reference plus localization/RTL infrastructure; name supported locales before claims. | Product/UX/Localization/QA |

## Requested baseline dispositions

| Class | Concrete baseline for approval | Current status |
|---|---|---|
| P0 | R1-first scope, exact source/acceptance/release assignments, feature-specific open decisions | OPEN; Product approval absent |
| A0 | WordPress module boundaries, canonical DAG, proposed contract register; resolve runtime-card inconsistency explicitly | OPEN; Architect approval absent |
| S0 | Current scoped authorization, no arbitrary creative execution, no unapproved collection, unavailable unsafe operations | OPEN; Security/Privacy approval absent |
| F0 | Separate commercial states, exact money, transactional capacity, append-only financial truth, no guessed policy | OPEN; Finance approval absent |

Record actual approver identity, date, source revision, disposition, rationale,
affected IDs and evidence reference. A generated file or named role is not a
signature. Keep private approval material in access-controlled storage.
The tool checks local hashes/records; it cannot authenticate people or judge
legal/accounting evidence. Baseline approval does not resolve every later policy.
