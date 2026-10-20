# AdPilot application assets

The production console and advertiser portal mount into WordPress's `#adpilot-app`.
React is supplied exclusively by `wp.element`; no React runtime, CDN font, or remote
image is bundled. `assets/admin.js`, `assets/admin.css`, and `assets/blocks.js` are
generated from these sources by `npm run build`.

## Verification

```sh
npm ci
npm test
npm run typecheck
npm run build
```

The nine transport tests cover authenticated JSON requests, both WordPress REST URL
styles, off-origin nonce protection, conflict envelopes, recoverable network errors,
request cancellation, exact integer-string money formatting, and stable hold keys.

## Main workflows

- Inventory creates zones and bounded containers with stable logical slot IDs.
- Layout Studio references selected zones, displays a bounded site schematic, and
  exposes paginated persisted slot identities. Saving, validating, and publishing
  are separate server-confirmed operations. Published geometry cannot be edited.
- Campaign creation uses an organization membership, a paginated published-slot
  chooser, an exact creative version, and a schedule. Approval, entitlement, and
  delivery remain separately visible.
- Moderation decisions include a reason and identify the submitted creative digest.
- Operator organization verification and manual complimentary activation call the
  corresponding capability-protected server actions.
- Fixed-window rates require an exact minor-unit string and explicit tax policy.
  Marketplace booking progresses through authoritative quote, idempotent temporary
  hold, and unpaid order. Paid checkout remains visibly unavailable while its
  adapter and financial certification are gated by the server.
- Four Gutenberg blocks save compact references and return `null` from `save`.
  Published slot selectors are paginated; editor previews do not request delivery
  or measurement.

## Boundaries

The Studio is an explicitly synthetic, nonbillable schematic with at most 120
representative cells and bounded pixel geometry. It does not mount 500 live ads.
The slot navigator provides the exact server identities independently of preview.
Browser render claims remain labeled as untrusted, nonbillable measurements.
No revenue is invented when financial aggregates are unavailable.

All application and editor strings use the WordPress `adpilot` text domain. Local
translation catalogs, expert assistive-technology review, and the complete release
compatibility matrix remain separate release evidence requirements.
