# Runtime and test setup

The WordPress plugin runs on PHP with InnoDB-backed WordPress storage. WooCommerce is optional and checkout remains gated. Composer, Node and Python are not runtime requirements. Node is used only to rebuild the console/editor assets; PHP ZipArchive is used only for packaging.

The earlier disposable local environment verified WordPress 7.1.3, PHP 8.5.8 and MariaDB 13.0.2 on macOS arm64. That environment and its credentials/database files were removed during cleanup, and its preview services were stopped. No bundled WordPress installation or local credentials are needed in the plugin repository.

Install the built ZIP on your own disposable WordPress site for integration tests. Set its WordPress environment type to local and use a synthetic administrator named adpilot-dev. The integration fixtures deliberately require that login and a local environment; they add synthetic business records and must not run on a production site.

From this repository, replace the WordPress path below with your disposable installation:

```sh
wp --path=/path/to/disposable-wordpress eval-file tests/integration/scenarios.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/security.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/commerce.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/revisions.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/operations.php
wp --path=/path/to/disposable-wordpress eval-file tests/integration/high-density.php
```

The high-density fixture publishes synthetic test pages and records their URLs in preview-pages.json beside the WordPress directory. It checks 500 light placeholders and bounded query counts.

For the exclusive reservation race, run reservations.php to prepare a fresh fixture. Run race-worker.php a and race-worker.php b concurrently in separate WP-CLI processes. Exactly one must print WINNER and the other LOSER capacity_unavailable. These helpers write race-fixture.json beside the disposable WordPress directory.

For lock-expiry regression, run expiry-worker.php prepare, then expiry-worker.php lock in one process. As soon as expiry-lock-ready exists beside WordPress, run expiry-worker.php contend in another process; it must reject the expired quote and create no hold. Re-prepare for each run. These manual PHP fixtures replace the removed Python race launchers.

PHP syntax/domain tests and frontend transport tests run without WordPress; see the repository README. Remote CI and a full WordPress/PHP/database/theme/browser/WooCommerce/accessibility matrix remain separate verification work.
