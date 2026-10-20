<?php
namespace AdPilot\Platform;

use AdPilot\Core\{Input, Problem};

final class Settings
{
    public static function read(): array
    {
        $stored = get_option('adpilot_configuration', []);
        return ['row_version' => (int) ($stored['row_version'] ?? 1), 'settings' => ($stored['settings'] ?? []) + [
            'emergency_stop' => false, 'manual_entitlements_enabled' => false, 'manual_policy_ref' => '',
            'measurement_enabled' => false, 'measurement_policy_ref' => '', 'event_retention_days' => null, 'commerce_enabled' => false,
            'commerce_policy_ref' => '', 'self_service_signup' => false,
        ]];
    }

    public static function save(array $input): array
    {
        global $wpdb;
        $current = self::read();
        if (Input::integer($input, 'row_version', 1, PHP_INT_MAX) !== $current['row_version']) {
            throw new Problem('version_conflict', 'Settings changed. Reload before retrying.', 409);
        }
        $values = $input['settings'] ?? null;
        if (!is_array($values)) {
            throw new Problem('invalid_settings', 'Settings must be an object.');
        }
        $allowed = ['emergency_stop', 'manual_entitlements_enabled', 'manual_policy_ref', 'measurement_enabled', 'measurement_policy_ref', 'event_retention_days', 'self_service_signup'];
        foreach ($values as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                throw new Problem('setting_unavailable', "Setting {$key} is unavailable or requires its dedicated policy workflow.", 422);
            }
            if ($key === 'event_retention_days') {
                $values[$key] = Input::integer($values, $key, 1, 3650);
            } elseif (str_ends_with($key, '_ref')) {
                $values[$key] = Input::text($values, $key, 191, false);
            } elseif (!is_bool($value)) {
                throw new Problem('invalid_settings', "{$key} must be boolean.");
            }
        }
        $next = ['row_version' => $current['row_version'] + 1, 'settings' => array_replace($current['settings'], $values)];
        foreach (['manual_entitlements' => 'manual_policy_ref', 'measurement' => 'measurement_policy_ref'] as $feature => $reference) {
            if ($next['settings'][$feature . '_enabled'] && $next['settings'][$reference] === '') {
                throw new Problem('policy_required', 'Record the applicable policy reference before enabling this operation.', 409);
            }
        }
        if ($next['settings']['measurement_enabled'] && (!$next['settings']['event_retention_days'] || !has_filter('adpilot_consent_verdict'))) {
            throw new Problem('consent_adapter_required', 'Install an authoritative consent adapter before enabling collection.', 409);
        }
        $raw = get_option('adpilot_configuration', null);
        if ($raw === null) {
            if (!add_option('adpilot_configuration', $next, '', false)) {
                throw new Problem('version_conflict', 'Settings changed. Reload before retrying.', 409);
            }
        } else {
            $changed = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s", maybe_serialize($next), 'adpilot_configuration', maybe_serialize($raw)));
            if ($changed !== 1) {
                throw new Problem('version_conflict', 'Settings changed. Reload before retrying.', 409);
            }
            wp_cache_delete('adpilot_configuration', 'options');
            wp_cache_delete('alloptions', 'options');
        }
        return $next;
    }

    public static function measurementAllowed(): bool
    {
        $settings = self::read()['settings'];
        if (!$settings['measurement_enabled'] || wp_get_environment_type() !== 'production' || is_preview() || is_admin()) {
            return false;
        }
        // Only a trusted server integration can grant purpose-specific collection.
        $verdict = apply_filters('adpilot_consent_verdict', ['analytics' => false], ['site_id' => get_current_blog_id()]);
        return is_array($verdict) && ($verdict['analytics'] ?? false) === true;
    }

    public static function availability(): array
    {
        return [
            'inventory' => ['available' => true, 'release' => 'R1'], 'layout_studio' => ['available' => true, 'release' => 'R1'],
            'native_creatives' => ['available' => true, 'release' => 'R1'],
            'media_uploads' => ['available' => false, 'release' => 'R1', 'reason' => 'Private quarantine and format policy verification pending.'],
            'checkout' => ['available' => false, 'release' => 'R1', 'reason' => class_exists('WooCommerce') ? 'Gateway, tax and financial certification pending.' : 'WooCommerce is not installed. Nonpayment features remain available.'],
            'heatmaps' => ['available' => false, 'release' => 'R2', 'reason' => 'Privacy and geometry certification pending.'],
            'metered_billing' => ['available' => false, 'release' => 'R3', 'reason' => 'Independent metric and ledger certification required.'],
            'replay' => ['available' => false, 'release' => 'R4', 'reason' => 'Disabled pending explicit privacy approval.'],
            'federation' => ['available' => false, 'release' => 'R4', 'reason' => 'Site isolation is enforced.'],
            'outbound_webhooks' => ['available' => false, 'reason' => 'P28-DEC-012 release contract remains unresolved.'],
        ];
    }
}
