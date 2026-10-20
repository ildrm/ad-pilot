=== AdPilot ===
Contributors: ildrm
Tags: advertising, inventory, campaigns
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted advertising inventory, moderated native creatives and progressive delivery.

== Description ==

DEVELOPMENT PREVIEW. This is an installable implementation in development,
not the completed or certified 29-document product.

Included: publisher console, advertiser portal, compact inventory and Layout
Studio, immutable publication, organization-scoped campaigns, native text
creative review, explicit complimentary entitlements, fixed-price quotes and
transactional exclusive holds, exact settlement application logic, and shared
block/shortcode/PHP rendering with progressive browser loading.

WooCommerce is optional. Online checkout is unavailable until the operator's
gateway, jurisdiction/tax and settlement policy are independently verified.
The adapter uses WooCommerce order CRUD and declares HPOS compatibility; a
full WooCommerce compatibility run has not yet been completed.

Media quarantine/uploads, legal invoices/refunds, advanced report exports,
R2 heatmaps, R3 metered billing and R4 replay/network features are not available.
Analytics collection is off by default and requires a trusted server consent
adapter, explicit policy reference and finite event retention.

== Installation ==

1. Build or obtain adpilot-0.1.0.zip and install through Plugins > Add New.
2. Activate on an individual site with InnoDB storage. WooCommerce is optional.
3. Open AdPilot; create zones and compact containers, then publish a layout.
4. Insert an AdPilot dynamic block or use [adpilot_zone id="ZONE_PUBLIC_ID"].
5. Add [adpilot_portal] to a page for authenticated advertiser access.
6. Provision/verify an organization, submit a native creative and review it.
7. Record the manual entitlement policy before granting a reasoned,
   capacity-checked complimentary campaign. A payment never approves a creative.

Deactivation and uninstall preserve all advertising records. Destructive
erasure requires a separate authorized lifecycle procedure.

== Frequently Asked Questions ==

= Does this need WooCommerce or a hosted service? =
No. Nonpayment functions run entirely within WordPress.

= Does a configured slot count as an impression? =
No. Configuration, selection, served facts and untrusted browser render claims
are separate. No browser signal creates a charge.

= Is the compatibility matrix certified? =
Only the local fixture is verified: WordPress 7.1.3, PHP 8.5.8 and MariaDB
13.0.2 on macOS arm64. Minimum-version, theme, multisite, Woo/HPOS, browser and
assistive-technology matrices still require complete verification.

== Changelog ==

= 0.1.0 =
* Initial development implementation with executable domain, REST, real DB
  race and high-density rendering checks. Formal release approval pending.
