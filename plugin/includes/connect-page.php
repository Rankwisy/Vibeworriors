<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// ---------------------------------------------------------------------------
// Save settings
// ---------------------------------------------------------------------------

add_action('admin_post_vibewarrior_save_settings', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions.', 'vibewarrior'));
    }

    check_admin_referer('vibewarrior_save_settings');

    $enabled = isset($_POST['vibewarrior_enabled']) && $_POST['vibewarrior_enabled'] === '1';
    update_option('vibewarrior_enabled', $enabled);

    if ($enabled) {
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        update_option('vibewarrior_domain', $host);
    }

    wp_safe_redirect(admin_url('admin.php?page=vibewarrior&saved=1'));
    exit;
});

// ---------------------------------------------------------------------------
// Connect / settings page render
// ---------------------------------------------------------------------------

function vibewarrior_render_connect_page(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions.', 'vibewarrior'));
    }

    $enabled      = (bool) get_option('vibewarrior_enabled', false);
    $saved        = isset($_GET['saved']);
    $mcp_url      = rest_url('mcp/vibewarrior');
    $app_pw_status = vibewarrior_app_passwords_status();
    $is_production = vibewarrior_looks_like_production();

    // Create an application password for the current user if none exist for VW
    $current_user = wp_get_current_user();
    $existing_passwords = WP_Application_Passwords::get_user_application_passwords($current_user->ID);
    $vw_password = null;
    foreach ($existing_passwords as $pw) {
        if (str_starts_with($pw['name'], 'VibeWarrior')) {
            $vw_password = $pw;
            break;
        }
    }

    echo '<div class="wrap">';
    vibewarrior_render_admin_header(__('Connect AI Agent', 'vibewarrior'));

    if ($saved) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'vibewarrior') . '</p></div>';
    }

    if ($is_production) {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>' . esc_html__('Warning:', 'vibewarrior') . '</strong> ';
        echo esc_html__('VibeWarrior appears to be running on a production site. This plugin is designed for development and staging environments only. Enabling it on production gives AI direct access to your database and filesystem.', 'vibewarrior');
        echo '</p></div>';
    }

    // ---------------------------------------------------------------------------
    // Enable / Disable toggle
    // ---------------------------------------------------------------------------
    echo '<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:24px;margin-bottom:24px;">';
    echo '<h2 style="margin-top:0;">' . esc_html__('AI Abilities', 'vibewarrior') . '</h2>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="vibewarrior_save_settings">';
    wp_nonce_field('vibewarrior_save_settings');

    echo '<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">';
    echo '<input type="checkbox" name="vibewarrior_enabled" value="1"' . checked($enabled, true, false) . '>';
    echo '<span style="font-size:15px;">' . esc_html__('Enable AI abilities (PHP execution, filesystem, database access)', 'vibewarrior') . '</span>';
    echo '</label>';
    echo '<p class="description">' . esc_html__('When enabled, authenticated AI agents can execute PHP and interact with your WordPress site directly.', 'vibewarrior') . '</p>';

    submit_button(__('Save Settings', 'vibewarrior'));
    echo '</form></div>';

    // ---------------------------------------------------------------------------
    // MCP endpoint info
    // ---------------------------------------------------------------------------
    echo '<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:24px;margin-bottom:24px;">';
    echo '<h2 style="margin-top:0;">' . esc_html__('MCP Endpoint', 'vibewarrior') . '</h2>';
    echo '<p>' . esc_html__('Configure your AI agent or MCP client to connect to this endpoint:', 'vibewarrior') . '</p>';
    echo '<code style="display:block;background:#f4f4f4;padding:12px;border-radius:4px;font-size:14px;">' . esc_html($mcp_url) . '</code>';

    echo '<h3 style="margin-top:20px;">' . esc_html__('Authentication', 'vibewarrior') . '</h3>';
    echo '<p>' . esc_html__('Use WordPress Application Passwords for authentication. The AI agent must send requests with:', 'vibewarrior') . '</p>';
    echo '<ul style="margin-left:20px;">';
    echo '<li>' . esc_html__('Username: your WordPress admin username', 'vibewarrior') . '</li>';
    echo '<li>' . esc_html__('Password: an Application Password (created below)', 'vibewarrior') . '</li>';
    echo '</ul>';

    if (! $app_pw_status['available']) {
        echo '<div class="notice notice-error inline"><p>' . esc_html($app_pw_status['reason']) . '</p></div>';
    }

    echo '</div>';

    // ---------------------------------------------------------------------------
    // Client configuration examples
    // ---------------------------------------------------------------------------
    $auth_header = 'Authorization: Basic ' . base64_encode($current_user->user_login . ':YOUR_APP_PASSWORD');

    echo '<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:24px;margin-bottom:24px;">';
    echo '<h2 style="margin-top:0;">' . esc_html__('Client Configuration', 'vibewarrior') . '</h2>';

    // Claude Desktop
    echo '<h3>Claude Desktop <code>claude_desktop_config.json</code></h3>';
    $claude_config = [
        'mcpServers' => [
            'vibewarrior' => [
                'command' => 'npx',
                'args'    => ['-y', 'mcp-remote', $mcp_url],
                'env'     => [
                    'MCP_REMOTE_HEADER_AUTHORIZATION' => 'Basic ' . base64_encode($current_user->user_login . ':YOUR_APP_PASSWORD'),
                ],
            ],
        ],
    ];
    echo '<pre style="background:#1a1a2e;color:#e0e0e0;padding:16px;border-radius:6px;overflow-x:auto;font-size:12px;">';
    echo esc_html(wp_json_encode($claude_config, JSON_PRETTY_PRINT));
    echo '</pre>';

    // Cursor / VS Code
    echo '<h3>Cursor / VS Code <code>mcp.json</code></h3>';
    $cursor_config = [
        'servers' => [
            'vibewarrior' => [
                'type'    => 'http',
                'url'     => $mcp_url,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($current_user->user_login . ':YOUR_APP_PASSWORD'),
                ],
            ],
        ],
    ];
    echo '<pre style="background:#1a1a2e;color:#e0e0e0;padding:16px;border-radius:6px;overflow-x:auto;font-size:12px;">';
    echo esc_html(wp_json_encode($cursor_config, JSON_PRETTY_PRINT));
    echo '</pre>';

    // Direct HTTP
    echo '<h3>' . esc_html__('Direct HTTP (curl test)', 'vibewarrior') . '</h3>';
    echo '<pre style="background:#1a1a2e;color:#e0e0e0;padding:16px;border-radius:6px;overflow-x:auto;font-size:12px;">';
    $curl = 'curl -s -X POST \\' . "\n";
    $curl .= '  -H "Content-Type: application/json" \\' . "\n";
    $curl .= '  -H "' . $auth_header . '" \\' . "\n";
    $curl .= '  -d \'{"jsonrpc":"2.0","method":"tools/list","id":1}\' \\' . "\n";
    $curl .= '  ' . $mcp_url;
    echo esc_html($curl);
    echo '</pre>';

    echo '</div>';

    // ---------------------------------------------------------------------------
    // Quick status
    // ---------------------------------------------------------------------------
    echo '<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:24px;">';
    echo '<h2 style="margin-top:0;">' . esc_html__('Status', 'vibewarrior') . '</h2>';
    echo '<table class="form-table">';

    $rows = [
        __('AI Abilities', 'vibewarrior')        => $enabled ? '<span style="color:#46b450;">&#10004; ' . esc_html__('Enabled', 'vibewarrior') . '</span>' : '<span style="color:#dc3232;">&#10008; ' . esc_html__('Disabled', 'vibewarrior') . '</span>',
        __('Application Passwords', 'vibewarrior') => $app_pw_status['available'] ? '<span style="color:#46b450;">&#10004; ' . esc_html__('Available', 'vibewarrior') . '</span>' : '<span style="color:#dc3232;">&#10008; ' . esc_html($app_pw_status['reason']) . '</span>',
        __('PHP Version', 'vibewarrior')          => PHP_VERSION,
        __('WordPress Version', 'vibewarrior')    => get_bloginfo('version'),
        __('MCP Endpoint', 'vibewarrior')         => '<code>' . esc_html($mcp_url) . '</code>',
        __('Sandbox Directory', 'vibewarrior')    => '<code>' . esc_html(vibewarrior_get_sandbox_dir()) . '</code>',
        __('Environment', 'vibewarrior')          => $is_production ? '<span style="color:#dc3232;">' . esc_html__('Production (not recommended)', 'vibewarrior') . '</span>' : '<span style="color:#46b450;">' . esc_html__('Development / Staging', 'vibewarrior') . '</span>',
    ];

    foreach ($rows as $label => $value) {
        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>' . $value . '</td></tr>';
    }
    echo '</table></div>';

    echo '</div>'; // .wrap
}
