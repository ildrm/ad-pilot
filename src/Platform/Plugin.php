<?php
namespace AdPilot\Platform;

use AdPilot\Core\{Audit, SystemClock};
use AdPilot\Identity\Organizations;
use AdPilot\Inventory\Inventory;
use AdPilot\Layout\Layouts;
use AdPilot\Creatives\Creatives;
use AdPilot\Reservations\Reservations;
use AdPilot\Campaigns\Campaigns;
use AdPilot\Delivery\{Delivery, Renderer, Token};
use AdPilot\Pricing\Pricing;
use AdPilot\Commerce\{Commerce, WooCommerce};

final class Plugin
{
    private static array $sites = [];
    public const CAPS = ['adpilot_manage_settings', 'adpilot_manage_roles', 'adpilot_manage_inventory', 'adpilot_publish_layout',
        'adpilot_view_inventory', 'adpilot_view_campaigns', 'adpilot_manage_campaigns', 'adpilot_moderate_creatives', 'adpilot_emergency_stop',
        'adpilot_manage_rates', 'adpilot_view_finance', 'adpilot_view_orders', 'adpilot_adjust_finance', 'adpilot_view_aggregate_analytics',
        'adpilot_export_reports', 'adpilot_manage_privacy', 'adpilot_view_diagnostics', 'adpilot_retry_jobs', 'adpilot_insert_blocks',
        'adpilot_view_audit_log', 'adpilot_manage_organization_status', 'adpilot_manage_integrations', 'adpilot_manage_ads_policy'];

    public static function activate(bool $networkWide = false): void
    {
        if ($networkWide && is_multisite()) {
            wp_die(esc_html__('Activate AdPilot separately for each site. Network-wide provisioning is not certified.', 'adpilot'));
        }
        Schema::migrate($GLOBALS['wpdb']);
        $admin = get_role('administrator');
        foreach (self::CAPS as $cap) {
            $admin?->add_cap($cap);
        }
        $roles = [
            'adpilot_adops' => ['adpilot_view_inventory', 'adpilot_manage_inventory', 'adpilot_publish_layout', 'adpilot_view_campaigns', 'adpilot_manage_campaigns', 'adpilot_view_diagnostics'],
            'adpilot_moderator' => ['adpilot_moderate_creatives'],
            'adpilot_finance' => ['adpilot_view_finance', 'adpilot_view_orders'],
            'adpilot_analyst' => ['adpilot_view_aggregate_analytics'],
            'adpilot_support' => ['adpilot_view_diagnostics'],
        ];
        foreach ($roles as $slug => $caps) {
            add_role($slug, ucwords(str_replace('_', ' ', $slug)), array_fill_keys(['read', ...$caps], true));
        }
        update_option('adpilot_capability_schema_version', 1, false);
        if (!wp_next_scheduled('adpilot_maintenance')) {
            wp_schedule_event(time() + 300, 'hourly', 'adpilot_maintenance');
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('adpilot_maintenance');
    }

    public static function services(): array
    {
        $site = get_current_blog_id();
        if (!isset(self::$sites[$site])) {
            $store = new WpStore($GLOBALS['wpdb'], $site);
            $clock = new SystemClock();
            $audit = new Audit($store, $clock);
            $organizations = new Organizations($store, $audit);
            $inventory = new Inventory($store, $audit);
            $layouts = new Layouts($store, $audit, $clock);
            $creatives = new Creatives($store, $organizations, $audit, $clock);
            $reservations = new Reservations($store, $audit, $clock);
            $campaigns = new Campaigns($store, $organizations, $inventory, $reservations, $audit);
            $tokens = new Token(wp_salt('auth'), $clock, $site);
            $delivery = new Delivery($store, $clock, $tokens, static fn(): bool => Settings::read()['settings']['emergency_stop']);
            $renderer = new Renderer($store);
            $pricing = new Pricing($store, $organizations, $inventory, $audit, $clock);
            $commerce = new Commerce($store, $audit, $clock);
            $woo = new WooCommerce($store, $commerce);
            $outbox = new Outbox($store, $clock);
            self::$sites[$site] = compact('store', 'clock', 'audit', 'organizations', 'inventory', 'layouts', 'creatives', 'reservations', 'campaigns', 'tokens', 'delivery', 'renderer', 'pricing', 'commerce', 'woo', 'outbox');
        }
        return self::$sites[$site];
    }

    public static function boot(): void
    {
        add_action('before_woocommerce_init', static function (): void {
            if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', ADPILOT_FILE, true);
            }
        });
        add_action('woocommerce_payment_complete', static function (int $orderId): void {
            try { self::services()['woo']->paymentComplete($orderId); } catch (\Throwable) {
                // Keep payment failure visible for reconciliation; never invent settlement.
                update_option('adpilot_payment_reconciliation_required', true, false);
            }
        });
        add_action('init', [self::class, 'register']);
        add_action('rest_api_init', static function (): void {
            if ((int) get_option('adpilot_schema_version') === Schema::VERSION) {
                (new \AdPilot\Presentation\Rest(self::services()))->register();
            }
        });
        add_action('admin_menu', static function (): void {
            if (array_filter(self::CAPS, 'current_user_can')) {
                add_menu_page(__('AdPilot', 'adpilot'), __('AdPilot', 'adpilot'), 'read', 'adpilot', [self::class, 'admin'], 'dashicons-megaphone', 30);
            }
        });
        add_action('admin_enqueue_scripts', static function (string $hook): void {
            if ($hook === 'toplevel_page_adpilot') {
                self::appAssets('admin');
            }
        });
        add_action('adpilot_maintenance', static function (): void {
            // Bounded expiry; it never resurrects holds or deletes financial facts.
            $s = self::services();
            $s['outbox']->drain();
            $retention = Settings::read()['settings']['event_retention_days'];
            if (is_int($retention) && $retention > 0) {
                global $wpdb;
                $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'adpilot_delivery_events WHERE site_id=%d AND occurred_at<%s LIMIT 1000', get_current_blog_id(), gmdate('Y-m-d H:i:s', time() - $retention * DAY_IN_SECONDS)));
            }
            foreach ($s['store']->many('inventory_holds', ['status' => 'active'], 100) as $hold) {
                if ($hold['expires_at'] <= gmdate('Y-m-d H:i:s')) {
                    $s['store']->transaction(function () use ($s, $hold): void {
                        $current = $s['store']->one('inventory_holds', ['id' => $hold['id']], true);
                        if ($current && $current['status'] === 'active' && $current['expires_at'] <= gmdate('Y-m-d H:i:s')) {
                            $s['store']->update('inventory_holds', $current, ['status' => 'expired']);
                            $s['audit']->record(0, 'hold.expired', 'hold', $hold['public_id']);
                        }
                    });
                }
            }
        });
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('adpilot jobs run', static function (): void {
                \WP_CLI::log(wp_json_encode(self::services()['outbox']->drain()));
            });
            \WP_CLI::add_command('adpilot diagnostics', static function (): void {
                \WP_CLI::log(wp_json_encode(['version' => ADPILOT_VERSION, 'schema' => get_option('adpilot_schema_version'), 'transactional' => self::services()['store']->transactional(), 'features' => Settings::availability()]));
            });
        }
    }

    public static function register(): void
    {
        load_plugin_textdomain('adpilot', false, dirname(plugin_basename(ADPILOT_FILE)) . '/languages');
        $url = plugin_dir_url(ADPILOT_FILE);
        wp_register_script('adpilot-runtime', $url . 'assets/runtime.js', [], ADPILOT_VERSION, true);
        wp_register_style('adpilot-runtime', $url . 'assets/runtime.css', [], ADPILOT_VERSION);
        wp_add_inline_script('adpilot-runtime', 'window.adpilotRuntime=' . wp_json_encode(['restUrl' => rest_url('adpilot/v1/'), 'advertisementLabel' => __('Advertisement', 'adpilot')]) . ';', 'before');
        wp_register_script('adpilot-blocks', $url . 'assets/blocks.js', ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-api-fetch'], ADPILOT_VERSION, true);
        wp_set_script_translations('adpilot-blocks', 'adpilot', dirname(ADPILOT_FILE) . '/languages');
        wp_add_inline_script('adpilot-blocks', 'window.adpilotEditor=' . wp_json_encode(['restUrl' => rest_url('adpilot/v1/'), 'nonce' => wp_create_nonce('wp_rest'), 'canViewInventory' => current_user_can('adpilot_view_inventory')]) . ';', 'before');
        foreach (['ad-slot', 'ad-zone', 'ad-repeater', 'ad-showcase'] as $block) {
            register_block_type(dirname(ADPILOT_FILE) . '/blocks/' . $block, ['editor_script' => 'adpilot-blocks',
                'render_callback' => static function (array $attributes) use ($block): string {
                    $isEditor = defined('REST_REQUEST') && REST_REQUEST && current_user_can('adpilot_insert_blocks');
                    $kind = !empty($attributes['slotId']) ? 'slot' : (!empty($attributes['containerId']) ? 'container' : 'zone');
                    $id = $attributes[$kind . 'Id'] ?? '';
                    return self::safeRender($kind, $id, $isEditor);
                }]);
        }
        foreach (['slot', 'zone', 'container', 'repeater', 'showcase'] as $kind) {
            add_shortcode('adpilot_' . $kind, static function (array|string $attributes = []) use ($kind): string {
                $attributes = is_array($attributes) ? $attributes : [];
                $resolved = in_array($kind, ['repeater', 'showcase'], true) ? 'zone' : $kind;
                return self::safeRender($resolved, (string) ($attributes['id'] ?? $attributes[$resolved . '_id'] ?? ''));
            });
        }
        add_shortcode('adpilot_portal', static function (): string {
            if (!is_user_logged_in()) {
                return '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Sign in to your advertiser account', 'adpilot') . '</a></p>';
            }
            self::appAssets('portal');
            return '<div id="adpilot-app" class="adp-root"></div>';
        });
        // Explicit theme integration action; no automatic insertion into content.
        add_action('adpilot_render_zone', static function (string $zone): void { echo self::safeRender('zone', $zone); });
    }

    public static function safeRender(string $kind, string $id, bool $preview = false): string
    {
        if ((int) get_option('adpilot_schema_version') !== Schema::VERSION || is_feed()) {
            return '';
        }
        try {
            return self::services()['renderer']->render($kind, $id, $preview || is_preview());
        } catch (\Throwable) {
            return ''; // Public content remains readable if ad storage is unavailable.
        }
    }

    public static function appAssets(string $mode): void
    {
        wp_enqueue_style('adpilot-admin', plugin_dir_url(ADPILOT_FILE) . 'assets/admin.css', [], ADPILOT_VERSION);
        wp_enqueue_script('adpilot-admin', plugin_dir_url(ADPILOT_FILE) . 'assets/admin.js', ['wp-element', 'wp-i18n'], ADPILOT_VERSION, true);
        wp_set_script_translations('adpilot-admin', 'adpilot', dirname(ADPILOT_FILE) . '/languages');
        $caps = array_values(array_filter(self::CAPS, 'current_user_can'));
        wp_add_inline_script('adpilot-admin', 'window.adpilotConfig=' . wp_json_encode(['restUrl' => rest_url('adpilot/v1/'), 'nonce' => wp_create_nonce('wp_rest'), 'mode' => $mode, 'capabilities' => $caps, 'userId' => get_current_user_id(), 'siteId' => get_current_blog_id()]) . ';', 'before');
    }

    public static function admin(): void
    {
        if (!array_filter(self::CAPS, 'current_user_can')) {
            return;
        }
        if ((int) get_option('adpilot_schema_version') !== Schema::VERSION) {
            echo '<div class="notice notice-error"><p>' . esc_html__('AdPilot storage needs activation or a reviewed upgrade.', 'adpilot') . '</p></div>';
        }
        echo '<div id="adpilot-app" class="adp-root"></div>';
    }
}
