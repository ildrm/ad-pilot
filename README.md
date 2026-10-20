# AdPilot

AdPilot is a self-hosted WordPress advertising plugin for publishers who want to manage advertising inventory, review advertiser creatives, schedule campaigns, and render eligible ads through their own WordPress installation.

The repository includes a publisher console, an authenticated advertiser portal, a visual Layout Studio, native text creative moderation, fixed-price quoting, exclusive inventory holds, and a lightweight public delivery runtime. WordPress provides the user accounts, administration environment, REST infrastructure, and React/editor libraries.

**Current version: 0.1.0 — development preview.** The plugin is installable, but the complete 54-package implementation program is unfinished. Payment certification, private media uploads, several R1 workflows, and the R2–R4 subsystems remain outstanding. The [implementation ledger](docs/development/IMPLEMENTATION.md) records those boundaries; the original specifications describe the intended product, including features that are not yet available.

## Contents

- [Current capabilities and boundaries](#current-capabilities-and-boundaries)
- [Requirements and compatibility](#requirements-and-compatibility)
- [Installation](#installation)
- [Quick start: first native campaign](#quick-start-first-native-campaign)
- [Inventory and Layout Studio](#inventory-and-layout-studio)
- [Rendering ads in WordPress](#rendering-ads-in-wordpress)
- [Organizations and permissions](#organizations-and-permissions)
- [Creatives and campaign eligibility](#creatives-and-campaign-eligibility)
- [Pricing, reservations, and commerce](#pricing-reservations-and-commerce)
- [Settings reference](#settings-reference)
- [Privacy and measurement](#privacy-and-measurement)
- [REST API](#rest-api)
- [Background processing and operations](#background-processing-and-operations)
- [Data lifecycle, upgrades, and removal](#data-lifecycle-upgrades-and-removal)
- [Development and packaging](#development-and-packaging)
- [Testing and CI](#testing-and-ci)
- [Architecture and source layout](#architecture-and-source-layout)
- [Localization](#localization)
- [Troubleshooting](#troubleshooting)
- [Documentation map](#documentation-map)
- [Contributing](#contributing)
- [License and third-party software](#license-and-third-party-software)

## Current capabilities and boundaries

| Area | Available in this development build | Important boundary |
| --- | --- | --- |
| Publisher console | Overview, inventory, Layout Studio, campaigns, native creative moderation, reports, settings, and diagnostics | The full planned finance and operator workspaces are incomplete. |
| Advertiser portal | Organization selection, placement browsing, native creative drafts/submission, campaign drafts, basic reports, and quote/hold/order flow | Requires a WordPress login and current organization membership. Full onboarding, team invitations, invoices, and notifications are incomplete. |
| Inventory | Zones, compact grid containers, stable logical slot IDs, paginated lists, and search | Bulk lifecycle controls and complete placement-binding/capacity policies are incomplete. |
| Layout publication | Draft, save, validation, immutable published revisions, and responsive container settings | Published container geometry is frozen. Sold-inventory migration, complete impact review, undo/redo, and controlled rollback are not implemented. |
| Creatives | Native text content, destination validation, immutable versions, and reasoned exact-version review | Raster upload/quarantine, video, advertiser HTML, and advertiser JavaScript are unavailable. The successor-version API exists; a complete editing wizard does not. |
| Campaign delivery | Separate approval, financial, and delivery states; explicit complimentary allocation; paid-allocation application service; pause, resume, and stop | Full lifecycle automation, targeting, pacing, rotation, and allocation policies remain incomplete. |
| Pricing and reservations | Exact fixed-window prices, quote snapshots, expiring idempotent holds, and exclusive interval conflict checks | The current tax policy supports only an explicitly recorded not-applicable case. Other tax jurisdictions and pricing models need further implementation. |
| Commerce | Optional WooCommerce CRUD/HPOS adapter and settlement application logic | Checkout is unavailable by default. Real gateway, tax, refund, reversal, invoice, and reconciliation certification is outstanding. |
| WordPress integration | PHP helpers, shortcodes, four registered dynamic block names, and an explicit theme action | Repeater/showcase currently use the shared renderer; full interval injection, curated showcase behavior, and a dedicated traditional widget adapter are incomplete. |
| Reporting | Scoped counts and basic campaign projections; served facts distinguished from browser render claims | Measurement is off by default. Qualified analytics, advanced exports, attribution, and financial aggregates are incomplete. Browser signals never create charges. |
| Later releases | Backend availability reports explain unavailable features | R2 heatmaps, R3 metered billing, and R4 replay/federation are unavailable. Outbound webhooks and a complete provider SDK are also unavailable. |

The plugin runs without a hosted AdPilot service. Its shipped interface uses WordPress React, system fonts, and scoped styles. No separate React runtime, remote font library, or third-party analytics script is bundled.

## Requirements and compatibility

### Installed plugin

| Component | Requirement or current behavior |
| --- | --- |
| WordPress | The plugin header declares WordPress **6.7 or newer**. This minimum is not a certified compatibility matrix. |
| PHP | **8.2 or newer**, with WordPress's normal PHP/database requirements and `mbstring` for text validation. |
| Database | MySQL/MariaDB-backed WordPress storage with **InnoDB** for all AdPilot tables. Mutations require transactional storage. |
| Browser | JavaScript enabled and modern browser APIs for the publisher console, advertiser portal, and progressive public ad loading. Use HTTPS for deployed interfaces, including browser cryptographic APIs used by booking. |
| Theme | Placement through supported blocks, shortcodes, or explicit PHP/theme integration. A full classic/block-theme compatibility matrix has not been certified. |
| WooCommerce | Optional. Inventory, moderation, complimentary campaigns, and rendering do not require it. Installing it alone does not enable certified checkout. |
| Multisite | Activate separately for each site. Network-wide activation is rejected; federation and network provisioning are not certified. |

Python, Composer, Node.js, and npm are not requirements for running the installed plugin. Node/npm are used to rebuild frontend assets from source. PHP's `zip` extension is required by the repository's packaging command, not by ordinary ad rendering.

### Verification status

An earlier disposable environment exercised WordPress **7.1.3**, PHP **8.5.8**, and MariaDB **13.0.2** on macOS arm64. The responsive interface was reviewed in Chrome at desktop, tablet, and mobile widths. Those results are limited to the paths and environment tested; they do not establish complete minimum-version, browser, theme, assistive-technology, WooCommerce, or multisite support.

That disposable WordPress installation, database, credentials, preview services, and raw generated evidence were removed during repository cleanup. There is no preconfigured local site or default administrator password in this checkout.

## Installation

### Install a packaged ZIP

1. Obtain a built `adpilot-0.1.0.zip`, or build one using the [development instructions](#development-and-packaging).
2. In your WordPress dashboard, open **Plugins → Add New → Upload Plugin**.
3. Select the ZIP, install it, and activate **AdPilot** on the individual site.
4. Open the **AdPilot** administration menu as an administrator.
5. In **Settings**, review runtime diagnostics, transactional storage, and feature availability before configuring campaigns.

The archive contains a single `adpilot/` directory. It includes the plugin bootstrap, runtime PHP, block metadata, shipped JavaScript/CSS, translations, and license/readme files. It excludes frontend source, build scripts, tests, specifications, development notes, dependencies, and local environment files.

Activation creates the implemented site-scoped schema, grants the plugin's capabilities to the WordPress administrator role, registers limited publisher roles, and schedules the plugin's maintenance event.

### Install from a source checkout

The repository retains compiled files under `assets/`. For a source installation, place the project at `wp-content/plugins/adpilot/`, confirm those assets exist, and activate it through WordPress. Rebuild the assets whenever you change their TypeScript/CSS source. Use the packaged ZIP for a distribution containing only plugin runtime files.

Do not expect the checkout to create WordPress, a database, accounts, sample inventory, campaigns, or a public portal page automatically.

## Quick start: first native campaign

This workflow demonstrates an explicitly authorized **complimentary** native campaign. It exercises the available delivery path without requiring checkout. Use a disposable site when evaluating the development preview.

### 1. Publish a placement

1. Open **AdPilot → Inventory** and create a zone, such as `Homepage sponsors`.
2. Add a container to that zone. For an initial test, use one logical slot and a simple grid.
3. Open **Layout Studio**, create a new draft, and include the zone/container.
4. Save changes, validate the saved draft, and publish the layout.
5. Insert an **Ad Zone** or **Ad Slot** block into a page and select the published reference. A shortcode or PHP helper can also render the placement.

Publication makes inventory available for placement and booking. It does not create a campaign or guarantee that an ad will appear.

### 2. Create the advertiser workspace

1. Create and publish a WordPress page containing the **Shortcode** block with `[adpilot_portal]`.
2. Sign in with the WordPress account that should own the advertiser organization.
3. Create the organization from the portal's **Organization** screen. Organization creation requires either the publisher's organization-management capability or the **Advertiser organization signup** setting to be enabled.
4. As a publisher with organization-management permission, open **Campaigns → Advertiser organizations** and verify the organization with a recorded reason.

For a one-account evaluation, an administrator can create an organization and becomes its owner. For another advertiser, enable organization signup, let that advertiser create their own organization while signed in, and then verify it as the publisher. Creating an organization from the publisher console assigns ownership to the account performing the action; it does not provision membership for an arbitrary other user.

Organization signup does not create WordPress accounts or enable WordPress's public user registration. Account provisioning remains a separate site responsibility. Secure invitations and ownership-transfer workflows are unfinished.

### 3. Submit and review a creative

1. In the advertiser portal, select the organization and open **Creatives**.
2. Create a text creative with an internal name, display title, body copy, and destination URL.
3. Save the draft and submit it for review.
4. As a publisher moderator, open **Creatives**, review that exact version, and approve it with a reason.

An approved organization does not imply an approved creative. Every creative version has its own content digest and review disposition.

### 4. Create and activate the campaign

1. In the advertiser portal, create a campaign draft referencing the organization, published slot, exact creative version, and a start/end schedule.
2. As the publisher, open **Settings**, record the applicable approved manual-activation policy reference, and enable **Manual campaign activation**.
3. In **Campaigns**, choose **Manual activation** for the draft and record the allocation reason.
4. Open the placement page during the campaign's schedule and scroll the placement near the viewport.

Activation requires a verified organization, an approved creative version, and available capacity for the entire exclusive interval. It creates a complimentary reservation; it does not record a payment. Creating a campaign draft alone does not reserve inventory or make the campaign eligible.

If the placement remains empty, follow the [delivery troubleshooting checklist](#troubleshooting). Measurement can remain disabled throughout this workflow; advertising delivery and analytics consent are separate concerns.

## Inventory and Layout Studio

### Inventory concepts

| Object | Meaning |
| --- | --- |
| Zone | A named grouping of advertising inventory, such as a header or sidebar region. |
| Container | A compact grid definition belonging to a zone, with a slot count and responsive presentation settings. |
| Logical slot | A stable sellable position materialized from a container and ordinal. |
| Layout draft | A versioned document referencing existing zones and containers. |
| Published revision | An immutable saved layout snapshot with a current publication pointer. |
| Render occurrence | One appearance of a placement on a page; repeated occurrences have distinct DOM/render identities. |

Public object IDs are 26-character uppercase alphanumeric identifiers. They are distinct from WordPress post IDs and internal database keys. Use the public IDs returned by inventory screens/API responses when configuring integrations.

### Responsive configuration and bounds

The current container compiler accepts:

| Field | Current range or format |
| --- | --- |
| `slot_count` | 1–5,000 logical slots per container under the current operational quota |
| `columns` | 1–24 |
| `tablet_columns` | 1–12 |
| `mobile_columns` | 1–6 |
| `gap` | 0–64 pixels |
| `aspect_ratio` | Two positive integers from 1 to 99, separated by `/`, such as `4/1` |

There is no 500-slot product cap. A 500-slot fixture is retained as an integration test. The current renderer retrieves at most 5,000 slot rows per render call, so large zones spanning multiple containers need consideration of that bound. Rendering containers individually preserves their own responsive grid configuration.

The Studio canvas is a synthetic, nonbillable schematic with bounded representative cells. It does not load one live creative per configured slot. Paginated slot controls expose persisted logical identities separately from the schematic.

Save, validate, and publish are separate operations. Updates use `row_version` to detect conflicts. If someone changes a record while you edit it, reload its current state before retrying. Published container geometry and materialized sequence length cannot be casually resized; complete contract-impact migration and rollback workflows remain future work.

## Rendering ads in WordPress

All supported paths use the shared PHP renderer and current delivery eligibility checks. Placement configuration cannot grant creative approval, a paid entitlement, or a complimentary allocation.

### Gutenberg and the Site Editor

| Block title | Registered name | Current behavior |
| --- | --- | --- |
| Ad Slot | `adpilot/ad-slot` | References a published logical slot. |
| Ad Zone | `adpilot/ad-zone` | References published zone/container inventory. |
| Ad Repeater | `adpilot/ad-repeater` | Registered dynamic placement; full query-loop interval behavior is incomplete. |
| Ad Showcase | `adpilot/ad-showcase` | Registered dynamic placement; full curated showcase behavior is incomplete. |

The blocks store compact references and render on the server. Editor previews are nonbillable and do not load live advertising. The custom capability `adpilot_insert_blocks` protects placement validation/preview endpoints; WordPress editors/authors do not automatically receive it from this plugin.

An `interval` attribute on the repeater is not proof of implemented automatic insertion into a query loop, article list, or product grid. Those integrations still need their adapters.

### Shortcodes

Replace the uppercase placeholders below with actual public IDs:

```text
[adpilot_slot id="SLOT_PUBLIC_ID"]
[adpilot_zone id="ZONE_PUBLIC_ID"]
[adpilot_container id="CONTAINER_PUBLIC_ID"]
```

The shortcode handlers also accept the corresponding `slot_id`, `zone_id`, or `container_id` attribute. The `id` form takes precedence when both are supplied.

The following aliases currently render a zone through the shared renderer:

```text
[adpilot_repeater id="ZONE_PUBLIC_ID"]
[adpilot_showcase id="ZONE_PUBLIC_ID"]
```

For the authenticated advertiser application:

```text
[adpilot_portal]
```

Anonymous portal visitors receive a WordPress sign-in link. The portal is separate from public catalog API access and requires current organization permissions for advertiser operations. Use one portal application mount per page.

### PHP helpers and theme action

Use helpers in a theme template after WordPress/plugins have loaded:

```php
<?php
if (function_exists('adpilot_render_zone')) {
    echo adpilot_render_zone('ZONE_PUBLIC_ID');
}
```

Available signatures:

```text
adpilot_render_slot(string $slot_id, array $options = []): string;
adpilot_render_zone(string $zone_id, array $options = []): string;
adpilot_render_container(string $container_id, array $options = []): string;
```

Helpers return escaped markup; the caller chooses whether to echo it. The implemented option is `['preview' => true]`, which returns a nonbillable preview label. Missing/unpublished inventory, an unready schema, feeds, or a rendering/storage failure can produce an empty string so that the rest of the page remains readable.

The plugin also registers an explicit WordPress action that prints a zone:

```php
<?php
do_action('adpilot_render_zone', 'ZONE_PUBLIC_ID');
```

There is no automatic insertion into every theme, product grid, or content loop. Place integrations explicitly and retain normal `wp_head()`/`wp_footer()` behavior so WordPress can load registered assets.

### Public runtime behavior

Server output contains lightweight placement placeholders. The browser runtime observes placements near the viewport and requests current decisions in batches of up to 20, with at most two decision requests in flight and a bounded queue. A configured slot or placeholder is not an impression.

Eligible native text is inserted with text nodes and sponsored link attributes. Each occurrence receives a distinct render identity, including when server HTML is reused from a page cache. A page can repeat a placement without reusing its occurrence ID.

New decisions and signed event/click claims recheck current campaign eligibility. Continuous removal of a creative already painted into a visitor's page is not a completed stop-propagation feature in this build. If a decision request fails, the advertising area can remain empty while the underlying page stays usable.

## Organizations and permissions

AdPilot separates publisher capabilities from advertiser organization membership. Being a WordPress administrator grants publisher capabilities, but does not implicitly grant access to every organization's advertiser routes.

### Publisher roles

Activation grants the plugin capability set to the administrator role and creates these limited WordPress roles:

| Role slug | Initial capabilities |
| --- | --- |
| `adpilot_adops` | View/manage inventory, publish layouts, view/manage campaigns, and view diagnostics |
| `adpilot_moderator` | Moderate creatives |
| `adpilot_finance` | View orders and financial ledger data |
| `adpilot_analyst` | View aggregate analytics |
| `adpilot_support` | View diagnostics |

Each custom role also receives WordPress `read`. Menu destinations and REST operations check the relevant capability. Inventory publication, organization status, moderation, rates, financial reads, settings, privacy configuration, emergency stop, job retry, and block insertion have distinct capability boundaries.

Some declared capabilities reserve future functionality. For example, the presence of an export or financial-adjustment capability does not mean a complete export/refund workflow exists. The authoritative current capability list and initial role grants are in [Plugin.php](src/Platform/Plugin.php).

### Advertiser organization roles

| Membership role | Read organization data | Write campaigns/creatives | Purchase and view finance | Manage members |
| --- | --- | --- | --- | --- |
| `owner` | Yes | Yes | Yes | Yes |
| `manager` | Yes | Yes | No | No |
| `analyst` | Yes | No | No | No |
| `billing` | Yes | No | Yes | No |

Authorization uses current membership and organization state. Revoked memberships and suspended/closed organizations cannot perform normal advertiser operations. Purchase additionally requires organization verification. An owner cannot remove the last active owner through the implemented member-change path.

Member role/revocation endpoints exist for current memberships. Secure invitation, arbitrary member provisioning, and full ownership-transfer workflows are not complete. Organization creation assigns an active owner membership to the current WordPress user.

## Creatives and campaign eligibility

### Native creative content

The implemented native format requires:

| Field | Validation |
| --- | --- |
| `name` | Required internal name; up to 191 characters |
| `title` | Required display title; up to 120 characters |
| `body` | Required body copy; up to 600 characters |
| `destination_url` | Valid HTTP(S) URL without embedded username/password; maximum 2,048 characters |

Text is validated and stripped of HTML tags, and the native content JSON has a 4 KiB bound. There is no raster attachment pipeline. Advertiser HTML, script content, and attachment-based creatives are rejected.

A version starts as `draft`, can be submitted as `submitted`, and can receive `approved`, `rejected`, or `changes_requested` after a reasoned review. Only draft versions can be submitted. To revise rejected/changed/approved material, create a successor version through:

```text
POST /adpilot/v1/organizations/{org_id}/creatives/{creative_id}/versions
```

Supply a complete new native payload. The successor gets its own public ID, family version number, digest, and draft status. It does not inherit approval, overwrite prior bytes/reviews, or automatically replace a campaign's selected creative. The complete UI editing and creative-selection workflow remains unfinished.

### Eligibility is the intersection of separate states

A campaign can serve only when the relevant current conditions agree:

1. Its slot and zone are published.
2. Its advertiser organization is verified.
3. Its exact selected creative version is approved and its content digest is valid.
4. Its financial state is `paid` or explicitly `complimentary`.
5. An active matching reservation covers that slot and interval.
6. The campaign is active and both campaign/contract schedules include the current time.
7. Delivery is not stopped by the applicable campaign or site control.

Campaign creation records an unfunded draft. Publisher manual activation creates a capacity-checked complimentary entitlement only when the policy is enabled. Paid allocation requires a reconciled paid order and compatible reservation in the same organization; payment does not approve a creative.

Schedules use half-open intervals: `[start, end)`. The start is included and the end is excluded. API input timestamps require an explicit timezone and second precision, such as `2030-01-15T09:00:00Z` or `2030-01-15T12:30:00+03:30`; they are normalized to UTC. The UI converts browser-local schedule inputs before sending them.

Publisher pause, resume, and emergency-stop actions require a reason. Resuming still depends on current approval and entitlement. Full campaign cancellation, completion automation, remedy handling, and the planned state-machine surface are incomplete.

## Pricing, reservations, and commerce

### Fixed-window pricing

Publish rates from a published slot's **Publish fixed rate** action in Inventory. The implemented model prices the entire requested booking window at the fixed quoted amount; it does not automatically calculate per-day charges or meter clicks/impressions.

Money crosses the API as exact canonical integer strings in minor units. For example, `"1500"` with currency `USD` and precision `2` represents USD 15.00. Floating-point amounts, ambiguous leading-zero integers, and gateway values exceeding configured precision are rejected rather than silently rounded.

A rate records currency precision, quote lifetime, hold lifetime, and a policy reference. Quote/hold TTLs are currently bounded to 60–3,600 seconds; currency precision is bounded to 0–4. The only implemented tax case is `explicitly_not_applicable` with an actual policy reference. A general tax calculation adapter and jurisdiction-specific workflows are unfinished.

### Booking sequence

| Step | What it establishes |
| --- | --- |
| Published rate | An immutable fixed-price offer definition |
| Quote | A server-calculated immutable snapshot of the requested slot, schedule, amount, currency, terms, and expiry |
| Hold | A temporary exclusive capacity claim, subject to expiry and conflicts |
| Advertising order | An unpaid commercial record tied to a hold and quote |
| Verified capture | A trusted money fact recorded through the commerce boundary |
| Campaign allocation | Separate mapping to current approved creative and compatible contracted inventory |

The portal's **View rates & reserve** flow implements quote → hold → order. Holds require an `Idempotency-Key` of 16–128 characters. Reuse the same key when retrying the same hold request; a key cannot be reused for a different quote. The slot row serializes conflicting writers, and expiry is checked after acquiring the lock. Adjacent half-open intervals do not overlap.

An expired or released hold cannot be used as a current entitlement. The settlement application records a late verified capture as requiring remediation instead of reviving expired inventory. Its synthetic tests cover amount mismatch, duplicate replay, and late capture; they do not certify a real gateway.

### WooCommerce status

The optional adapter uses supported WooCommerce order CRUD, declares HPOS compatibility, and listens for authoritative payment completion. Checkout readiness defaults to false, and ordinary settings cannot enable the commerce fields. The frontend continues to report checkout as unavailable in this preview.

Installing WooCommerce, creating an order, visiting a success URL, or passing a browser payment flag is insufficient to claim paid delivery. Real gateway/tax integration, reversals, distinct additional captures, refunds, legal invoices, and full reconciliation/remedy workflows need implementation and verification before commercial rollout.

## Settings reference

Settings are site-specific, versioned, and exposed through **AdPilot → Settings** and the protected settings API. Changes require the current `row_version`.

| Setting | Default | Meaning and guard |
| --- | --- | --- |
| `emergency_stop` | `false` | Stops new eligible delivery decisions/claims when enabled. Changing it requires `adpilot_emergency_stop` in addition to settings permission. |
| `manual_entitlements_enabled` | `false` | Permits reasoned complimentary allocation only when an applicable policy reference exists. |
| `manual_policy_ref` | Empty string | Reference to the publisher's approved complimentary-allocation policy. |
| `measurement_enabled` | `false` | Allows measurement only with a policy reference, finite retention, registered trusted consent adapter, and a permitted request context. |
| `measurement_policy_ref` | Empty string | Reference to the applicable measurement/privacy policy. |
| `event_retention_days` | `null` | No configured event retention until explicitly set; accepted configured values are 1–3,650 days. Required before enabling measurement. |
| `self_service_signup` | `false` | Lets authenticated WordPress users create their own advertiser organization. It does not register WordPress user accounts. |
| `commerce_enabled` | `false` | Returned as a commerce placeholder; unavailable through ordinary settings updates. |
| `commerce_policy_ref` | Empty string | Returned as a commerce placeholder; requires a future dedicated certification workflow. |

Privacy-related fields also require `adpilot_manage_privacy`. A configuration reference records which real policy applies; entering a string does not constitute independent policy approval or certification.

The settings screen includes runtime diagnostics and backend feature availability. A present menu or declared capability is not sufficient evidence that a feature is enabled.

## Privacy and measurement

Ad serving can work while measurement collection is disabled. The current collector requires all of the following:

- `measurement_enabled` is true.
- A nonempty measurement policy reference and finite event retention are configured.
- A trusted server integration is registered on `adpilot_consent_verdict`.
- That integration returns `['analytics' => true]` for the current request/site.
- WordPress reports the environment as `production`, and the request is outside the preview/admin exclusions.

The consent filter receives the default verdict `['analytics' => false]` and context containing `site_id`. A real consent-management integration must derive a purpose-specific verdict from authoritative state. The plugin does not bundle a consent-management platform, and a browser boolean is not an authorization to collect data.

The current measurement vocabulary distinguishes a server `served` fact from an untrusted `rendered_claim` and a recorded `click`. Signed short-lived decision references bind claims to current site/campaign/version/render provenance. Deduplication is enforced through an event-key index. None of these events creates a charge or establishes certified viewability, billable CPC/CPM, or attribution.

The hourly maintenance callback deletes at most 1,000 raw events older than the configured retention per run. A complete privacy exporter/eraser, legal-hold workflow, aggregate retention system, heatmap pipeline, and replay system are not implemented. Configure production collection only within the available policy and implementation boundaries.

## REST API

The current namespace is **`adpilot/v1`**. With pretty REST URLs, the base path is:

```text
https://example.test/wp-json/adpilot/v1/
```

WordPress query-style REST URLs are also supported by the frontend client, for example `https://example.test/index.php?rest_route=/adpilot/v1/catalog/placements`.

### Request and response conventions

- Publisher routes enforce the operation's WordPress capability. Advertiser routes additionally check current organization membership/object scope.
- The bundled applications use same-origin WordPress cookie authentication with `X-WP-Nonce`. Keep REST responses and personalized portal/bootstrap content out of shared caches.
- Mutation payloads use JSON; the registered handler bounds request bodies to 64 KiB. More specific domain limits can be smaller.
- Paginated lists use `page` and `per_page`, with a default of 50 and maximum of 100 rows per page. Supported resources accept `search`; not every route supports every filter.
- List envelopes contain `items`, `total`, `page`, and `per_page`.
- Versioned edits require the current `row_version` where implemented. Hold requests also require `Idempotency-Key`.
- Object references use public IDs; money uses integer strings; timestamp inputs include a timezone.
- Successful registered responses receive `Cache-Control: private, no-store`. Errors use WordPress REST error envelopes with `adpilot_` codes and HTTP status data.

Example public catalog request:

```sh
curl 'https://example.test/wp-json/adpilot/v1/catalog/placements?per_page=25&page=1'
```

Example native creative request body for an authorized organization route:

```json
{
  "name": "Spring sponsor text",
  "title": "A message from our sponsor",
  "body": "A short, plain-text message for this placement.",
  "destination_url": "https://example.test/sponsor"
}
```

### Route guide

All paths below are relative to the namespace base. Braced segments are placeholders.

| Area | Selected implemented routes |
| --- | --- |
| Identity | `GET me`, `GET my-organizations` |
| Organizations | `POST organizations`, `GET organizations/{org_id}`, `GET organizations/{org_id}/members` |
| Publisher organization actions | `GET admin/organizations`, `POST admin/organizations/{org_id}/actions/verify`, `POST admin/organizations/{org_id}/actions/suspend` |
| Member actions | `POST organizations/{org_id}/members/{member_id}/actions/change-role`, `POST .../actions/revoke` |
| Inventory | `GET/POST admin/zones`, `PATCH admin/zones/{zone_id}`, `GET/POST admin/containers`, `PATCH admin/containers/{container_id}`, `GET admin/slots` |
| Catalog | `GET catalog/placements`, `GET catalog/formats`, `GET catalog/packages` |
| Layouts | `GET admin/layouts`, `POST admin/layouts/drafts`, `GET/PATCH admin/layouts/drafts/{draft_id}`, `POST .../actions/validate`, `POST .../actions/publish` |
| Native creatives | `GET/POST organizations/{org_id}/creatives`, `POST organizations/{org_id}/creatives/{creative_id}/versions`, `POST .../actions/submit-review` |
| Moderation | `GET admin/creative-reviews`, `POST admin/creative-reviews/{review_id}/actions/approve`, `POST .../actions/reject`, `POST .../actions/request-changes` |
| Campaigns | `GET admin/campaigns`, `GET/POST organizations/{org_id}/campaigns`, `POST admin/campaigns/{campaign_id}/actions/{schedule\|pause\|resume\|emergency-stop}` |
| Rates/quotes | `GET/POST admin/rate-cards`, `POST organizations/{org_id}/quotes`, `GET organizations/{org_id}/quotes/{quote_id}` |
| Holds | `POST organizations/{org_id}/holds`, `GET organizations/{org_id}/holds/{hold_id}`, `POST .../actions/release` |
| Orders/ledger | `GET/POST organizations/{org_id}/orders`, `GET organizations/{org_id}/orders/{order_id}`, `POST .../actions/start-checkout` (gated), `GET admin/orders`, `GET admin/ledger` |
| Delivery/measurement | `POST delivery/decisions`, `POST delivery/click`, `POST measurement/events`, `GET measurement/client-config` |
| Reports | `GET admin/reports/overview`, `GET organizations/{org_id}/reports/campaigns` |
| Configuration/operations | `GET/PATCH admin/settings`, `GET admin/diagnostics`, `GET admin/feature-availability`, `GET public/feature-capabilities`, `GET admin/audit-log`, `POST admin/jobs/{job_id}/actions/retry` |
| Editor | `GET editor/zones`, `GET editor/zones/{zone_id}/preview`, `POST editor/placement-config/validate` |

This is a navigation guide, not a claim that every route in the specifications is implemented. [Rest.php](src/Presentation/Rest.php) contains the authoritative current route registration and permission mapping. R2–R4 routes and outbound webhook endpoints are not registered.

## Background processing and operations

Activation schedules the hourly **`adpilot_maintenance`** event, initially due approximately five minutes later. Its bounded callback dispatches committed local outbox events, applies configured raw-event retention, and processes a batch of expired active holds. Expired holds are excluded by authoritative capacity checks even before a maintenance pass updates their stored status.

The outbox uses schema-versioned site envelopes, processing leases, bounded retry attempts, and a local `adpilot_domain_event` action. Dispatch is at least once; extension consumers must deduplicate by `event_id`. It is not a complete external queue, notification service, or provider platform.

When WP-CLI loads the active plugin, these custom commands are available:

```sh
wp --path=/path/to/wordpress adpilot diagnostics
wp --path=/path/to/wordpress adpilot jobs run
```

`adpilot diagnostics` reports the plugin/schema versions, transactional readiness, and feature availability. `adpilot jobs run` drains the outbox; it does not execute the entire maintenance callback, purge all expired data, or certify delivery.

Monitor Settings diagnostics for pending background events and the next scheduled maintenance run. Ensure the site's normal WordPress cron execution is functioning. If a maintenance backlog exceeds a batch, processing requires subsequent runs. A complete scheduled-job dashboard, notification pipeline, and support-bundle export remain unfinished.

Public rendering catches ad-storage failures and returns an empty result. REST errors suppress raw SQL, server paths, secrets, and request contents. Site-level PHP/WordPress diagnostics are still needed to investigate an underlying failure.

## Data lifecycle, upgrades, and removal

### Storage and migration

The implemented schema has 23 plugin persistence tables under the site's WordPress table prefix, for example `wp_adpilot_zones`. It covers organizations/memberships, inventory/layouts, creative versions/reviews, campaigns, rates/quotes, holds/reservations, orders/ledger, delivery events, audit, outbox, and migration records.

Repositories bind themselves to the current WordPress site and include site predicates. Switching sites requires the corresponding site-bound service context. Site-specific configuration, capability/schema version markers, and cron state also live in WordPress options/roles.

Schema installation/migration runs on activation. Migration 001 has a recorded checksum, and activation refuses a mismatched recorded definition or an unsupported downgrade. Do not edit an installed migration definition or manually lower version options to force old code to run. New schema work needs an explicit reviewed migration and recovery plan.

### Updates and backups

Back up the WordPress database, plugin files, site configuration, and any custom integration before testing an upgrade. Exercise changes on a disposable/staging installation first, including schema and role behavior. No production backup/restore certification or general one-click rollback is supplied by this preview.

### Deactivation and uninstall

Deactivation clears the plugin's own scheduled maintenance hook and retains business data. The uninstall handler deliberately preserves inventory, organizations, contracts, ledger, audit records, options, and role/capability state. Deleting the plugin files is therefore not a data-erasure operation.

A complete destructive lifecycle/erasure workflow is not implemented. Any separately authorized removal procedure must account for financial retention and related WordPress metadata; the plugin does not provide a blanket purge command.

## Development and packaging

Run commands from the repository root. The examples use ordinary command names; if your local environment requires a wrapper such as RTK, prefix them with that wrapper.

### Toolchain

| Tool | Purpose |
| --- | --- |
| Node.js and npm | Install pinned build dependencies and compile the TypeScript/React source. CI selects Node.js 24. |
| PHP CLI 8.2+ | Run domain checks, syntax lint, and the packager. `proc_open` must be available for the lint subprocesses. |
| PHP `mbstring` | Run the same text-validation paths used by the plugin. |
| PHP `zip` / `ZipArchive` | Create the installable ZIP. |
| WordPress, InnoDB database, and WP-CLI | Run the optional integration fixtures against a disposable local site. |

There is no Composer dependency installation or Python toolchain. Development packages are pinned in [package-lock.json](package-lock.json): esbuild 0.25.11 and TypeScript 5.9.3. `node_modules/` and `dist/` are generated and ignored by Git.

### Install dependencies and verify source

```sh
npm ci
npm run typecheck
npm test
npm run lint:php
npm run test:php
```

### Build assets

```sh
npm run build
```

The build compiles `frontend/app.tsx` and `frontend/blocks.tsx` to minified ES2020 bundles under `assets/`, and copies `frontend/admin.css` to `assets/admin.css`. React remains supplied by `wp.element`. The public `assets/runtime.js` and `assets/runtime.css` are maintained directly and are separate from the console/editor build.

Edit the TypeScript/CSS source and rebuild its outputs together. Avoid hand-editing generated `assets/admin.js`, `assets/blocks.js`, or the copied admin stylesheet.

### Create the plugin ZIP

```sh
npm run package
```

This rebuilds the frontend and invokes the PHP packager. The current output is:

```text
dist/adpilot-0.1.0.zip
```

The archive version is read from the plugin header. The packager uses an explicit input list, sorted paths, normalized timestamps/permissions, and prints the ZIP's SHA-256. Repeating packaging against unchanged inputs produces the same archive in the same packaging environment.

If the shipped assets are already current, the PHP packager can run without npm or Node:

```sh
php scripts/plugin.php package
```

That command does not compile frontend changes or run tests. To lint PHP without installing frontend dependencies:

```sh
php scripts/plugin.php lint
php tests/php/run.php
```

### npm command reference

| Command | Action |
| --- | --- |
| `npm ci` | Install the dependency versions pinned by the lockfile |
| `npm run build` | Build console/editor assets and copy admin CSS |
| `npm run typecheck` | Run strict TypeScript checking without emitting files |
| `npm test` | Run frontend transport/formatting/idempotency tests |
| `npm run lint:php` | Syntax-check root, runtime, script, and test PHP files |
| `npm run test:php` | Run standalone PHP domain checks |
| `npm run package` | Build assets, then create the runtime-only ZIP |

No development server or bundled database is started by these commands. Install the plugin in your own WordPress environment to view its interfaces.

## Testing and CI

### Tests without WordPress

The standalone PHP runner covers identifiers, timestamp/URL validation, exact money/precision/overflow, membership policy, layout bounds/stable slot identity, and core eligibility gates. It currently has 27 checks.

The frontend Node runner currently has 9 tests covering same-origin authenticated transport, both REST URL styles, nonce protection, conflict/network/cancellation handling, exact money formatting, and stable hold keys. It needs the npm development dependencies installed.

PHP syntax lint is intentionally narrower than WPCS, PHPStan, a security audit, or a WordPress runtime test. Passing it does not establish complete release readiness.

### WordPress integration fixtures

Use only a disposable installation configured with WordPress environment type `local`, an active AdPilot plugin, InnoDB tables, and a synthetic administrator login named **`adpilot-dev`**. These fixtures create synthetic users, inventory, contracts, orders, and pages. They are not read-only health checks and must not be run against a production database.

From this repository, substitute the path to that WordPress installation:

```sh
wp --path=/path/to/disposable-wordpress eval-file tests/integration/scenarios.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/security.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/commerce.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/revisions.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/operations.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/high-density.php
```

| Fixture | Focus |
| --- | --- |
| `scenarios.php` | Publication, native review/complimentary delivery, signed click validation, and emergency-stop refusal |
| `security.php` | Live REST organization isolation, role boundaries, revocation, token tampering, SQL search literals, and absent later-release routes |
| `commerce.php` | Synthetic exact settlement, replay, mismatches, late capture, independent approval, and exclusive paid entitlement use |
| `revisions.php` | Immutable successor creative versions, exact review history, public catalog scope, and quote immutability |
| `operations.php` | Migration reruns, local outbox dispatch, and non-destructive deactivate/reactivate behavior |
| `high-density.php` | A 4 + 100 + 196 + 200 slot layout, light placeholders, bounded queries, unique occurrences, and immutable published geometry |
| `reservations.php` / `race-worker.php` | Prepare and contend for one exclusive interval with separate PHP processes |
| `expiry-worker.php` | Reject a quote that expires while a separate process holds its slot lock |

The race and expiry helpers require coordination between processes. [RUNTIME.md](docs/development/RUNTIME.md) explains their modes and temporary fixture files. Prepare a fresh race fixture for each run; replaying an old idempotent request does not test a new capacity race.

### Continuous integration and evidence limits

[The GitHub Actions workflow](.github/workflows/checks.yml) defines PHP 8.2, 8.3, 8.4, and 8.5 jobs with Node.js 24. It runs PHP lint/domain checks, frontend typechecking/tests/build, and packaging, then uploads development artifacts. The action references are pinned to commits.

The workflow definition is not a record of a completed remote CI run. Its jobs do not provision WordPress/MariaDB or execute the integration, concurrency, browser, WooCommerce, accessibility, or recovery matrices. Historical local results and the written UI review are described in the development notes; generated raw evidence was removed during cleanup.

## Architecture and source layout

AdPilot's PHP services are composed per WordPress site. Domain services depend on a small storage/clock boundary; the WordPress infrastructure implements prepared queries, site scope, optimistic row versions, and transactions. Native REST, theme helpers, shortcodes, and blocks connect to those services rather than creating independent entitlement rules.

```text
ad-pilot/
├── adpilot.php                 Plugin header, constants, hooks, and bootstrap
├── uninstall.php               Data-preserving uninstall handler
├── src/
│   ├── Core/                   Storage/clock ports, validation, IDs, audit
│   ├── Identity/               Organization and membership authorization
│   ├── Inventory/              Zones, containers, stable slot generation
│   ├── Layout/                 Drafts, validation, immutable publication
│   ├── Creatives/              Native versions, submission, moderation
│   ├── Campaigns/              Campaign actions and eligibility
│   ├── Pricing/                Exact money, rates, and quotes
│   ├── Reservations/           Exclusive holds and complimentary allocation
│   ├── Commerce/               Orders, capture ledger, optional Woo adapter
│   ├── Delivery/               Decisions, signed claims, shared renderer
│   ├── Platform/               WordPress composition, schema, store, jobs, settings
│   ├── Presentation/           REST registration and permission boundaries
│   ├── autoload.php            Local PHP autoloader
│   └── functions.php           Public rendering helpers
├── blocks/                     Four block.json registrations
├── assets/                     Shipped console/editor/runtime JS and CSS
├── frontend/                   TypeScript/React source, styles, transport tests
├── languages/                  Translation template and future catalogs
├── scripts/
│   ├── build.mjs               Frontend asset build
│   └── plugin.php              PHP lint and ZIP packaging commands
├── tests/
│   ├── php/                    Standalone domain checks and assertions
│   └── integration/            Disposable WordPress/InnoDB fixtures
├── docs/                       29 product/engineering specifications
│   └── development/            Current ledger and preserved written reviews
├── .github/workflows/checks.yml Development CI definition
├── package.json                Build/test command definitions
├── package-lock.json           Pinned frontend development dependencies
├── tsconfig.json               Strict TypeScript configuration
├── readme.txt                  WordPress plugin readme
├── THIRD_PARTY_NOTICES.md       Dependency/license notices
└── LICENSE                     GPL license text
```

The PHP autoloader is local to the plugin; a Composer-generated vendor tree is not needed. A complete signed extension/provider registry and external worker SDK have not been implemented.

## Localization

The WordPress text domain is **`adpilot`**. Plugin text-domain loading and script translation registration point to `languages/`, and [adpilot.pot](languages/adpilot.pot) is the translation template. The console uses a shared translation wrapper and CSS logical properties where implemented.

Complete gettext coverage, translated catalogs, locale/number/currency review, RTL behavior, and assistive-technology acceptance are unfinished. The presence of a POT file or logical CSS is not a claim that every string or layout has been localized and certified. Keep text-domain and translation references current when changing interface text.

## Troubleshooting

| Symptom | Checks and current explanation |
| --- | --- |
| AdPilot menu is missing | Confirm the plugin is active on the current site and the current user has at least one AdPilot capability. Activation grants administrator capabilities and creates limited custom roles. |
| Storage needs activation / REST routes are absent | Compare the installed plugin and schema versions. Activation owns schema setup; do not bypass migration checks by editing options. Investigate the activation error or deploy the matching code/schema through a reviewed upgrade. |
| Mutation fails with transactional-storage errors | Confirm all AdPilot tables use InnoDB and exist under the current site's prefix. Readiness is checked across the implemented table set. |
| Interface is blank or unstyled | Confirm shipped `assets/` files exist, WordPress can serve them, and no optimizer is breaking their dependencies. After frontend source changes, run the build. Check browser console/network failures and standard theme asset hooks. |
| REST request returns 403 | Check WordPress authentication/nonce, the specific publisher capability, current organization membership/role, and organization suspension. An administrator is not automatically an advertiser member. Reload an authenticated app to obtain a current nonce. |
| Organization creation is denied | Self-service organization signup defaults to off. Enable it as the publisher when appropriate, or create your own organization with the publisher organization-management capability. Creating memberships for another arbitrary account is not a completed workflow. |
| No placements appear in the marketplace | Publish the relevant layout/inventory first. For booking, a currently effective published rate must also exist. Search/pagination may be needed to locate a slot beyond the first page. |
| Published container cannot be edited | Geometry is intentionally frozen in this build. New inventory/draft references can be created; complete sold-impact migration and rollback are unfinished. |
| A page shows placeholders but no ad | Verify publication, organization verification, exact creative approval, an active compatible reservation, campaign financial/delivery states, schedule/UTC boundaries, and site/campaign stop controls. Scroll near the placement and check its decision request. JavaScript, request blockers, storage errors, or an unsupported reference can leave it empty. |
| Future campaign is active but not visible | Delivery still respects `[start, end)` in UTC. An active state does not override its schedule. |
| Creative cannot be edited or resubmitted | Submitted versions are immutable. Use the successor-version API for revised content, submit that draft, and review it independently. A complete revision/selection UI is unfinished. |
| Quote expired / hold expired | Obtain a fresh quote/hold as appropriate. The server clock is authoritative, including after lock waits. Retry the same hold request with its original key; use a new request/key for a different quote. |
| Reservation returns a capacity conflict | Another current hold or active contract overlaps the exclusive slot interval. Manual complimentary allocation cannot preempt it. Refresh availability and choose a compatible interval. |
| Checkout is unavailable with WooCommerce installed | The adapter and public availability remain gated pending gateway/tax/financial work. Installing WooCommerce or editing the normal settings does not complete that work. |
| Reports show zero events while ads display | Collection defaults to off and also requires trusted analytics consent, policy/retention configuration, and production context. Local/preview activity is excluded. Placeholder counts are not measurement events. |
| Revenue is shown as unavailable | The current overview returns unknown revenue/currency as null. A complete financial aggregate/reporting implementation is outstanding. |
| Maintenance/outbox backlog persists | Inspect diagnostics, the pending count, and WordPress cron operation. The CLI job command drains only the bounded local outbox. Large retention/expiry backlogs require further maintenance runs. |
| Packaging reports missing ZipArchive | Enable the PHP CLI `zip` extension for the packaging environment. It is separate from the PHP process that may run WordPress. |
| `npm` build/test dependencies are missing | Run `npm ci` from the repository root. `node_modules/` is intentionally not committed. The installed ZIP itself uses the prebuilt assets. |
| An old localhost preview URL no longer opens | The disposable preview/database environment was removed during cleanup. Install the plugin on your own WordPress site; this repository no longer starts or ships a local site. |
| Deleting the plugin did not remove its tables | Uninstall deliberately retains business records and metadata. A general destructive purge is not implemented. |

## Documentation map

### Current development references

- [Implementation status and all 54 work packages](docs/development/IMPLEMENTATION.md)
- [PHP/rendering integration and domain boundaries](docs/development/DEVELOPER.md)
- [Disposable WordPress test setup and concurrency fixture modes](docs/development/RUNTIME.md)
- [Preserved written UI review](docs/development/UI_REVIEW.md)
- [Historical backend development review](docs/development/DEVELOPMENT_REVIEW.md)
- [Baseline proposal awaiting owner decisions](docs/development/BASELINE_PROPOSAL.md)
- [Frontend source guide](frontend/README.md)

Historical audit/review notes can refer to Python tools, generated JSON registries, screenshots, logs, or a disposable environment removed during cleanup. Use this README for current commands and paths.

### Original specifications

These 29 documents are preserved as the product and engineering reference set. Read feature claims against the current implementation ledger before treating a specified endpoint or workflow as available.

| Document | Subject |
| --- | --- |
| [01](docs/01_ADPILOT_PRODUCT_REQUIREMENTS_DOCUMENT.md) | Product requirements and release scope |
| [02](docs/02_ADPILOT_DETAILED_FEATURE_CATALOG_TRACEABILITY_ACCEPTANCE.md) | Feature catalog, traceability, and acceptance |
| [03](docs/03_ADPILOT_USER_PERSONAS_JTBD_USER_JOURNEYS_WORKFLOWS.md) | Personas, journeys, and workflows |
| [04](docs/04_ADPILOT_BUSINESS_RULES_DOMAIN_POLICIES_STATE_MACHINES_PERMISSIONS.md) | Business rules, policies, and state machines |
| [05](docs/05_ADPILOT_ROLES_CAPABILITIES_PERMISSIONS_MULTI_TENANT_ACCESS_CONTROL.md) | Roles, capabilities, and access control |
| [06](docs/06_ADPILOT_SYSTEM_ARCHITECTURE_ADRS_MODULE_BOUNDARIES.md) | Architecture, ADRs, and module boundaries |
| [07](docs/07_ADPILOT_DOMAIN_MODEL_MODULE_CONTRACTS_APPLICATION_SERVICES.md) | Domain model and application contracts |
| [08](docs/08_ADPILOT_DATABASE_SCHEMA_MIGRATIONS_INDEXES_DATA_LIFECYCLE.md) | Database schema, migrations, and data lifecycle |
| [09](docs/09_ADPILOT_REST_API_WEBHOOKS_INTEGRATION_CONTRACTS.md) | Intended REST/webhook/integration contracts |
| [10](docs/10_ADPILOT_EVENT_SCHEMAS_BACKGROUND_JOBS_OUTBOX_ASYNC_PROCESSING.md) | Events, outbox, and background jobs |
| [11](docs/11_ADPILOT_UX_ARCHITECTURE_NAVIGATION_INTERACTION_DESIGN.md) | UX architecture and navigation |
| [12](docs/12_ADPILOT_VISUAL_DESIGN_SYSTEM_COMPONENT_LIBRARY_FRONTEND_TOKENS.md) | Visual system, components, and tokens |
| [13](docs/13_ADPILOT_ADMINISTRATOR_CONSOLE_UI_DASHBOARD_IMPLEMENTATION.md) | Publisher administration console |
| [14](docs/14_ADPILOT_ADVERTISER_PORTAL_MARKETPLACE_CAMPAIGN_PURCHASING_UI.md) | Advertiser portal and purchasing UI |
| [15](docs/15_ADPILOT_LAYOUT_STUDIO_VISUAL_INVENTORY_HIGH_DENSITY_PLACEMENT_DESIGNER.md) | Layout Studio and high-density inventory |
| [16](docs/16_ADPILOT_GUTENBERG_BLOCKS_EDITOR_INTEGRATION_SITE_EDITOR.md) | Gutenberg and Site Editor integration |
| [17](docs/17_ADPILOT_THEME_INTEGRATION_PHP_RENDERING_API_SHORTCODES_HOOKS.md) | Theme helpers, shortcodes, and hooks |
| [18](docs/18_ADPILOT_ADVERTISING_DELIVERY_ENGINE_TARGETING_SELECTION_RUNTIME_SDK.md) | Delivery, targeting, and runtime |
| [19](docs/19_ADPILOT_MEASUREMENT_ATTRIBUTION_ANALYTICS_REPORTING.md) | Measurement, attribution, and reporting |
| [20](docs/20_ADPILOT_HEATMAP_INTELLIGENCE_BEHAVIORAL_ANALYTICS_PRIVACY_VISUALIZATION.md) | Planned heatmaps and behavioral analytics |
| [21](docs/21_ADPILOT_PRICING_RESERVATIONS_COMMERCE_FINANCIAL_LIFECYCLE.md) | Pricing, reservations, and commerce |
| [22](docs/22_ADPILOT_CREATIVE_MANAGEMENT_MODERATION_POLICY_APPROVAL_WORKFLOWS.md) | Creative management and moderation |
| [23](docs/23_ADPILOT_SECURITY_PRIVACY_COMPLIANCE_THREAT_MODELING.md) | Security, privacy, and threat modeling |
| [24](docs/24_ADPILOT_QUALITY_ASSURANCE_TEST_AUTOMATION_RELEASE_CERTIFICATION.md) | QA and release certification |
| [25](docs/25_ADPILOT_DEPLOYMENT_OPERATIONS_OBSERVABILITY_UPGRADE_DISASTER_RECOVERY.md) | Deployment, operations, upgrades, and recovery |
| [26](docs/26_ADPILOT_CONFIGURATION_FEATURE_FLAGS_LOCALIZATION_MULTISITE_ADMINISTRATION.md) | Configuration, localization, and multisite |
| [27](docs/27_ADPILOT_EXTENSIBILITY_THIRD_PARTY_INTEGRATIONS_DEVELOPER_SDK.md) | Planned extensibility and developer SDK |
| [28](docs/28_ADPILOT_IMPLEMENTATION_ROADMAP_DEPENDENCY_GRAPH_MILESTONES_GPT6_HIGH_EXECUTION.md) | Implementation roadmap and dependency graph |
| [29](docs/29_ADPILOT_MASTER_IMPLEMENTATION_SUPER_MEGA_PROMPT_MULTI_AGENT_ORCHESTRATION.md) | Audit/execution and work-package orchestration |

## Contributing

Start with the implementation ledger and the relevant source specification. Keep work scoped to a reviewable dependency-ordered package, and distinguish implemented behavior from proposed contracts and owner acceptance.

For a code change:

1. Preserve publisher capability, current organization membership, and site/object scope checks.
2. Keep money exact, schedules explicit, and creative versions/quotes/ledger facts immutable.
3. Use the shared rendering and eligibility services; an integration must not create a separate approval or entitlement shortcut.
4. Add or update meaningful behavior tests, and run the checks appropriate to the affected layer.
5. Rebuild generated assets when their source changes. Update translations and documentation where the behavior changes.
6. Keep credentials, private data, WordPress installations, database directories, caches, and generated release archives out of source control and distribution inputs.
7. Record migration/API/policy impacts and remaining release gates. Do not mark incomplete or uncertified work as released.

Report a reproducible issue with plugin/WordPress/PHP/database versions, affected role/workflow, relevant error code, and reproduction steps. Redact credentials, personal records, REST nonces, and payment-provider secrets from shared diagnostics.

## License and third-party software

The plugin header declares **GPL-2.0-or-later**. See [LICENSE](LICENSE) for the license text and [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) for development dependency notices.

WordPress supplies React and editor packages at runtime. esbuild and TypeScript are development tools excluded from the plugin ZIP. Their pinned dependency metadata remains in the npm lockfile.
