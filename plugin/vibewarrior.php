<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

/**
 * Plugin Name:       VibeWarrior
 * Plugin URI:        https://vibewarrior.com
 * Description:       Direct WordPress access for AI agents. Runs PHP inside your WordPress process — full access to functions, database, and filesystem. For development and staging environments only.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            VibeWarrior
 * Author URI:        https://vibewarrior.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vibewarrior
 * Domain Path:       /languages
 */

if (! defined('ABSPATH')) {
    exit;
}

define('VIBEWARRIOR_VERSION', '1.0.0');
define('VIBEWARRIOR_PLUGIN_FILE', __FILE__);
define('VIBEWARRIOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIBEWARRIOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIBEWARRIOR_SANDBOX_DIR_NAME', 'vibewarrior-sandbox');

// ---------------------------------------------------------------------------
// Vendor / MCP Adapter bootstrap
// ---------------------------------------------------------------------------

$vibewarrior_autoloader = VIBEWARRIOR_PLUGIN_DIR . 'vendor/autoload_packages.php';

if (! file_exists($vibewarrior_autoloader)) {
    // Release ZIP is missing vendor/ — show a clear admin notice and stop.
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        printf(
            /* translators: %s: download URL */
            esc_html__('VibeWarrior: the vendor directory is missing. Please download a release build from %s instead of cloning the repository directly.', 'vibewarrior'),
            '<a href="https://github.com/Rankwisy/Vibeworriors/releases" target="_blank">GitHub Releases</a>'
        );
        echo '</p></div>';
    });

    return;
}

require_once $vibewarrior_autoloader;

// ---------------------------------------------------------------------------
// Core includes
// ---------------------------------------------------------------------------

require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/helpers.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/admin-page.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/connect-page.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/sandbox-loader.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/admin-access-link.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/upload-link.php';

// Abilities
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/discover-abilities.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/execute-php.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/read-file.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/write-file.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/edit-file.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/delete-file.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/list-directory.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/disable-file.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/enable-file.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/run-wp-cli.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/create-admin-access-link.php';
require_once VIBEWARRIOR_PLUGIN_DIR . 'includes/abilities/create-upload-link.php';

// ---------------------------------------------------------------------------
// MCP Adapter — initialise singleton and register REST routes
// ---------------------------------------------------------------------------

add_action('plugins_loaded', static function (): void {
    if (! function_exists('wp_mcp_adapter')) {
        return;
    }

    $adapter = wp_mcp_adapter();
    $adapter->set_server_info([
        'name'    => 'VibeWarrior',
        'version' => VIBEWARRIOR_VERSION,
    ]);

    // Primary route
    $adapter->register_rest_route('vibewarrior');

    // Build and set server instructions
    $instructions = vibewarrior_build_server_instructions();
    if ($instructions) {
        $adapter->set_server_instructions($instructions);
    }
});

// ---------------------------------------------------------------------------
// Admin menu
// ---------------------------------------------------------------------------

add_action('admin_menu', static function (): void {
    add_menu_page(
        __('VibeWarrior', 'vibewarrior'),
        __('VibeWarrior', 'vibewarrior'),
        'manage_options',
        'vibewarrior',
        'vibewarrior_render_connect_page',
        'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="9" fill="#7c3aed"/><text x="10" y="14" text-anchor="middle" font-size="11" font-weight="bold" fill="#fff" font-family="Arial">V</text></svg>'),
        80
    );

    add_submenu_page(
        'vibewarrior',
        __('Connect', 'vibewarrior'),
        __('Connect', 'vibewarrior'),
        'manage_options',
        'vibewarrior',
        'vibewarrior_render_connect_page'
    );

    add_submenu_page(
        'vibewarrior',
        __('AI Abilities', 'vibewarrior'),
        __('AI Abilities', 'vibewarrior'),
        'manage_options',
        'vibewarrior-abilities',
        'vibewarrior_render_settings_page'
    );

    add_submenu_page(
        'vibewarrior',
        __('Sandbox', 'vibewarrior'),
        __('Sandbox', 'vibewarrior'),
        'manage_options',
        'vibewarrior-sandbox',
        'vibewarrior_render_sandbox_page'
    );
});

// ---------------------------------------------------------------------------
// Admin bar indicator
// ---------------------------------------------------------------------------

add_action('admin_bar_menu', static function (WP_Admin_Bar $admin_bar): void {
    if (! current_user_can('manage_options')) {
        return;
    }
    if (! vibewarrior_is_enabled()) {
        return;
    }
    $admin_bar->add_node([
        'id'    => 'vibewarrior-status',
        'title' => '<span style="background:#7c3aed;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">⚡ VW Active</span>',
        'href'  => admin_url('admin.php?page=vibewarrior'),
        'meta'  => ['title' => __('VibeWarrior is active', 'vibewarrior')],
    ]);
}, 999);

// ---------------------------------------------------------------------------
// Domain-mismatch admin notice
// ---------------------------------------------------------------------------

add_action('admin_notices', static function (): void {
    if (! current_user_can('manage_options')) {
        return;
    }
    if (! vibewarrior_is_domain_mismatch()) {
        return;
    }
    echo '<div class="notice notice-warning is-dismissible"><p>';
    printf(
        /* translators: %s: settings page URL */
        esc_html__('VibeWarrior: AI abilities are enabled for a different domain. Visit %s to reconfigure.', 'vibewarrior'),
        '<a href="' . esc_url(admin_url('admin.php?page=vibewarrior')) . '">' . esc_html__('VibeWarrior Settings', 'vibewarrior') . '</a>'
    );
    echo '</p></div>';
});

// ---------------------------------------------------------------------------
// Result unwrapping: surface WP_Error from abilities to MCP clients
// ---------------------------------------------------------------------------

add_filter('mcp_adapter_ability_result', static function (mixed $result, string $name): mixed {
    if (! is_wp_error($result)) {
        return $result;
    }

    $detail = $result->get_error_data();
    $message = $result->get_error_message();

    if (is_array($detail) || is_object($detail)) {
        $message .= ' ' . wp_json_encode($detail);
    } elseif (is_string($detail) && $detail !== '') {
        $message .= ' ' . $detail;
    }

    throw new RuntimeException($message);
}, 10, 2);
