> Historical development record. The temporary test environment, Python tooling, generated registries and raw evidence referenced below were removed during project cleanup. Current commands are in the repository README.

# Adversarial development review

Scope: the full organization authorization/membership, reservation, exact-money,
order/settlement, native creative, storage and corresponding REST paths.
Method: local source review using the adversarial-reviewer skill's Saboteur,
New Hire and Security Auditor perspectives. This is an agent self-review,
separate from the delegated browser evaluation. It is not independent security,
financial, privacy or human release certification.

**Verdict: BLOCK production release; development preview may be inspected.**

| Finding | Perspectives / severity | Resolution and evidence |
| --- | --- | --- |
| A hold captured time before waiting on its slot lock, allowing an expired quote to pass. | Saboteur + Security Auditor; critical | Re-read the immutable quote and server clock after acquiring the serialization lock. `scripts/expiry_race.py` exercises a real lock wait with two WordPress processes and asserts zero holds after expiry. |
| Fractional gateway totals were accepted for zero-decimal currencies. | Saboteur + Security Auditor; critical | Exact `Money::fromDecimal` enforces the configured precision without floats or rounding; domain regressions cover JPY, USD, excess precision and exponent notation. Actual gateway certification is still open. |
| Public package listing included rates linked to unpublished inventory. | Security Auditor; warning | A site-bound catalog predicate requires effective published rates plus published slot and zone; public filtered and unfiltered regression checks cover the refusal. |
| Generic repository updates could overwrite approved creative content or issued quote money. | Saboteur + Security Auditor; critical | Quotes are append-only, creative content updates are refused, and the documented CRE-003 route creates independent immutable successors with fresh approval. Regression checks preserve the old digest/review history. |
| Membership mutation checked authority before acquiring its organization lock. | Security Auditor; warning | Organization and current actor membership are checked under the mutation lock. Creative, quote and campaign creation also repeat current authorization inside their owning transaction. |
| A new operator lacked an accurate support matrix and a list of unsupported commercial/rendering paths. | New Hire; warning | README, runtime and developer notes identify the exact observed environment, fixture commands, supported paths and open gates. |

Open blockers include incomplete creative quarantine/scanning, legal payment
and tax policies, full reconciliation/refund remedies, campaign/contract impact
workflows, reporting/notification coverage and release certification. A second
distinct capture, reversals and provider out-of-order semantics require the
full finance package; current synthetic settlement tests cover one exact
capture, duplicate replay, amount mismatch and late capture only. The Woo
adapter remains gated.

Verification boundaries: local InnoDB races are real; gateway captures are
synthetic. The browser test is actual Chrome at three widths; full keyboard,
screen-reader, RTL, multiple browser and accessibility matrices are not
certified. No security finding is dismissed merely because the preview is
local. Production remains blocked by the unresolved work and source policies.
