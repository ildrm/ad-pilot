<?php
/** Intentionally retain contracts, ledger, inventory and audit on uninstall. */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
// Destructive erasure requires a separately authorized, audited lifecycle job.
