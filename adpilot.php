<?php
/**
 * Plugin Name: AdPilot
 * Description: Self-hosted advertising inventory, moderated campaigns and progressive delivery.
 * Version: 0.1.0
 * Author: Shahin Ilderemi
 * Author URI:  https://ildrm.com
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * Text Domain: adpilot
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ADPILOT_VERSION', '0.1.0');
define('ADPILOT_FILE', __FILE__);
require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/src/functions.php';

register_activation_hook(__FILE__, [AdPilot\Platform\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [AdPilot\Platform\Plugin::class, 'deactivate']);
add_action('plugins_loaded', static function (): void {
    AdPilot\Platform\Plugin::boot();
});
