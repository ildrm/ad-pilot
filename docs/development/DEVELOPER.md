# Development API and rendering paths

Runtime modules are loaded through `src/autoload.php`. Domain services depend
on the site-bound `Core\Store` port; `Platform\WpStore` owns prepared WordPress
SQL and transactions. `Platform\Plugin::services()` creates services per site.
`Presentation\Rest` exposes WordPress REST routes with capability checks and
live organization membership. A site administrator has no implicit advertiser
organization membership. Never pass a browser-supplied internal database ID
as a trusted organization or payment fact.

All browser mutations use the WordPress REST nonce for cookie authentication.
REST lists use page/per_page, at most 100 rows, with server-side search. Minor
money units travel as canonical integer strings. Contract timestamps are RFC3339
inputs normalized to UTC; delivery and booking intervals are half open.
Hold POST requires a stable `Idempotency-Key` header of 16–128 characters.
Browser redirect success never settles an order.

The documented CRE-003 route is implemented for native text:
`POST /wp-json/adpilot/v1/organizations/{org_id}/creatives/{creative_id}/versions`.
Supply all `name`, `title`, `body`, `destination_url` fields as JSON. It creates
a new draft with a new public ID and next family version. Submit/review that
exact version separately. Prior content and review history stay immutable;
campaign selection does not silently switch to the successor.

For classic themes, template parts or an explicitly registered theme action:

```php
echo adpilot_render_zone('ZONE_PUBLIC_ID');
echo adpilot_render_container('CONTAINER_PUBLIC_ID');
echo adpilot_render_slot('SLOT_PUBLIC_ID');
do_action('adpilot_render_zone', 'ZONE_PUBLIC_ID');
```

Helpers return escaped shared-render markup, or an empty string when an
unpublished/missing reference, storage failure, feed or unready schema makes
rendering unavailable. They return strings rather than echoing. Do not count a
placeholder as delivery. The browser runtime asks for current eligible content
only near the viewport; each render occurrence has a distinct identity.

Shortcode examples:

```text
[adpilot_zone id="ZONE_PUBLIC_ID"]
[adpilot_container id="CONTAINER_PUBLIC_ID"]
[adpilot_slot id="SLOT_PUBLIC_ID"]
[adpilot_portal]
```

Insert `adpilot/ad-slot` or `adpilot/ad-zone` in the block/Site Editor with a
published placement reference. All four requested block names are registered,
but repeater/showcase currently resolve to the shared zone renderer. Full
query-loop interval injection, curated showcase behavior, traditional widget
adapter and automatic content injection are not implemented. No block,
shortcode or helper can grant an entitlement or creative approval.

`adpilot_consent_verdict` is a trusted server-side integration point. It must
return an explicit purpose-specific analytics verdict; client booleans do not
grant collection. Measurement remains off in the development fixture and in
previews/admin. A policy reference and finite retention are required before
enabling it in production. Raw browser render claims are untrusted and
nonbillable. No R2–R4 or public outbound-webhook route is registered.

The schema contains 23 implemented R1 persistence units. Migration 001 has an
immutable checksum: do not edit its definitions after installation. Future
schema work must add an explicit reviewed migration, backfill, rollback/restore
and compatibility procedure. Deactivation/uninstall retain business records.
Hooks and core ports are not a completed signed provider SDK; arbitrary
advertiser HTML, JavaScript and attachments are rejected.
