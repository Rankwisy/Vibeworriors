<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// ---------------------------------------------------------------------------
// Ability collection
// ---------------------------------------------------------------------------

function vibewarrior_collect_public_abilities(): array
{
    if (! function_exists('wp_get_abilities')) {
        return [];
    }

    $all = wp_get_abilities();
    $out = [];

    foreach ($all as $name => $ability) {
        $meta = $ability['meta'] ?? [];
        $mcp  = $meta['mcp'] ?? [];
        if (! ($mcp['public'] ?? false)) {
            continue;
        }
        $out[$name] = $ability;
    }

    uasort($out, static fn($a, $b) => strcmp($a['label'] ?? $a['name'], $b['label'] ?? $b['name']));

    return $out;
}

// ---------------------------------------------------------------------------
// Sandbox admin actions
// ---------------------------------------------------------------------------

function vibewarrior_handle_sandbox_actions(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $action = sanitize_text_field($_POST['vibewarrior_sandbox_action'] ?? '');
    if (! $action) {
        return;
    }

    check_admin_referer('vibewarrior_sandbox_action');

    $sandbox = vibewarrior_get_sandbox_dir();
    $file    = sanitize_file_name($_POST['vibewarrior_sandbox_file'] ?? '');
    $path    = $sandbox . '/' . $file;

    switch ($action) {
        case 'delete':
            if (file_exists($path)) {
                unlink($path);
            }
            break;

        case 'disable':
            if (file_exists($path) && ! str_ends_with($path, '.disabled')) {
                rename($path, $path . '.disabled');
            }
            break;

        case 'enable':
            if (str_ends_with($file, '.disabled')) {
                $enabled = substr($path, 0, -9); // remove .disabled
                rename($path, $enabled);
            }
            break;

        case 'exit_safe_mode':
            $crashed = $sandbox . '/.crashed';
            if (file_exists($crashed)) {
                unlink($crashed);
            }
            break;
    }

    wp_safe_redirect(admin_url('admin.php?page=vibewarrior-sandbox'));
    exit;
}

add_action('admin_post_vibewarrior_sandbox_action', 'vibewarrior_handle_sandbox_actions');

// ---------------------------------------------------------------------------
// Sandbox page
// ---------------------------------------------------------------------------

function vibewarrior_render_sandbox_page(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions.', 'vibewarrior'));
    }

    $sandbox = vibewarrior_get_sandbox_dir();
    if (! is_dir($sandbox)) {
        wp_mkdir_p($sandbox);
    }

    $crashed_marker = $sandbox . '/.crashed';
    $in_safe_mode   = file_exists($crashed_marker);

    echo '<div class="wrap">';
    vibewarrior_render_admin_header(__('Sandbox', 'vibewarrior'));

    if ($in_safe_mode) {
        echo '<div class="notice notice-error inline"><p>';
        printf(
            /* translators: %s: crashed file */
            esc_html__('Safe mode is active. The file %s caused a fatal error and was disabled automatically.', 'vibewarrior'),
            '<code>' . esc_html(trim((string) file_get_contents($crashed_marker))) . '</code>'
        );
        echo '</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="vibewarrior_sandbox_action">';
        echo '<input type="hidden" name="vibewarrior_sandbox_action" value="exit_safe_mode">';
        wp_nonce_field('vibewarrior_sandbox_action');
        submit_button(__('Exit Safe Mode', 'vibewarrior'), 'primary', 'submit', false);
        echo '</form></div>';
    }

    echo '<h2>' . esc_html__('Sandbox Files', 'vibewarrior') . '</h2>';
    echo '<p>' . esc_html__('PHP files created by AI are isolated here. You can enable, disable, or delete them.', 'vibewarrior') . '</p>';

    vibewarrior_render_sandbox_list($sandbox);

    echo '</div>';
}

function vibewarrior_render_sandbox_list(string $sandbox): void
{
    $files = array_merge(
        glob($sandbox . '/*.php') ?: [],
        glob($sandbox . '/*.php.disabled') ?: []
    );

    if (empty($files)) {
        echo '<p>' . esc_html__('No sandbox files yet. Ask your AI agent to create a plugin.', 'vibewarrior') . '</p>';
        return;
    }

    usort($files, static fn($a, $b) => filemtime($b) - filemtime($a));

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('File', 'vibewarrior') . '</th>';
    echo '<th>' . esc_html__('Status', 'vibewarrior') . '</th>';
    echo '<th>' . esc_html__('Modified', 'vibewarrior') . '</th>';
    echo '<th>' . esc_html__('Size', 'vibewarrior') . '</th>';
    echo '<th>' . esc_html__('Actions', 'vibewarrior') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($files as $file) {
        $basename = basename($file);
        $disabled = str_ends_with($basename, '.disabled');
        $status   = $disabled
            ? '<span style="color:#999;">' . esc_html__('Disabled', 'vibewarrior') . '</span>'
            : '<span style="color:#46b450;">' . esc_html__('Active', 'vibewarrior') . '</span>';
        $modified = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($file));
        $size     = size_format(filesize($file));

        echo '<tr>';
        echo '<td><code>' . esc_html($basename) . '</code></td>';
        echo '<td>' . $status . '</td>';
        echo '<td>' . esc_html($modified) . '</td>';
        echo '<td>' . esc_html($size) . '</td>';
        echo '<td>';

        $nonce_field = wp_create_nonce('vibewarrior_sandbox_action');

        if ($disabled) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline;">';
            echo '<input type="hidden" name="action" value="vibewarrior_sandbox_action">';
            echo '<input type="hidden" name="vibewarrior_sandbox_action" value="enable">';
            echo '<input type="hidden" name="vibewarrior_sandbox_file" value="' . esc_attr($basename) . '">';
            echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce_field) . '">';
            submit_button(__('Enable', 'vibewarrior'), 'small', 'submit', false);
            echo '</form> ';
        } else {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline;">';
            echo '<input type="hidden" name="action" value="vibewarrior_sandbox_action">';
            echo '<input type="hidden" name="vibewarrior_sandbox_action" value="disable">';
            echo '<input type="hidden" name="vibewarrior_sandbox_file" value="' . esc_attr($basename) . '">';
            echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce_field) . '">';
            submit_button(__('Disable', 'vibewarrior'), 'small', 'submit', false);
            echo '</form> ';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline;" onsubmit="return confirm(\'' . esc_js(__('Delete this file?', 'vibewarrior')) . '\');">';
        echo '<input type="hidden" name="action" value="vibewarrior_sandbox_action">';
        echo '<input type="hidden" name="vibewarrior_sandbox_action" value="delete">';
        echo '<input type="hidden" name="vibewarrior_sandbox_file" value="' . esc_attr($basename) . '">';
        echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce_field) . '">';
        submit_button(__('Delete', 'vibewarrior'), 'small delete', 'submit', false);
        echo '</form>';

        echo '</td></tr>';
    }

    echo '</tbody></table>';
}

// ---------------------------------------------------------------------------
// Abilities page
// ---------------------------------------------------------------------------

function vibewarrior_render_settings_page(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions.', 'vibewarrior'));
    }

    echo '<div class="wrap">';
    vibewarrior_render_admin_header(__('AI Abilities', 'vibewarrior'));

    $abilities = vibewarrior_collect_public_abilities();

    if (empty($abilities)) {
        echo '<p>' . esc_html__('No public abilities registered.', 'vibewarrior') . '</p>';
        echo '</div>';
        return;
    }

    echo '<p>' . esc_html__('These abilities are exposed to connected AI agents via the MCP interface.', 'vibewarrior') . '</p>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('Name', 'vibewarrior') . '</th>';
    echo '<th>' . esc_html__('Label', 'vibewarrior') . '</th>';
    echo '<th>' . esc_html__('Category', 'vibewarrior') . '</th>';
    echo '<th>' . esc_html__('Description', 'vibewarrior') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($abilities as $name => $ability) {
        echo '<tr>';
        echo '<td><code>' . esc_html($name) . '</code></td>';
        echo '<td>' . esc_html($ability['label'] ?? '') . '</td>';
        echo '<td>' . esc_html($ability['category'] ?? '') . '</td>';
        echo '<td>' . esc_html($ability['description'] ?? '') . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}
