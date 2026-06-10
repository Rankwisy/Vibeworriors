<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// ---------------------------------------------------------------------------
// Path helpers
// ---------------------------------------------------------------------------

/**
 * Resolve a user-supplied path to an absolute path within ABSPATH.
 *
 * @param string $path      Relative (to ABSPATH) or absolute path.
 * @param bool   $must_exist Whether the target must already exist.
 * @return string|WP_Error  Absolute path or WP_Error.
 */
function vibewarrior_resolve_path(string $path, bool $must_exist = true): string|WP_Error
{
    $base = rtrim(ABSPATH, '/\\');

    // Absolute paths must still be under ABSPATH
    if (! str_starts_with($path, '/') && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
        $path = $base . '/' . ltrim($path, '/\\');
    }

    $real = realpath($path);

    if ($real === false) {
        if ($must_exist) {
            return new WP_Error('path_not_found', sprintf(__('Path not found: %s', 'vibewarrior'), $path));
        }
        // For non-existing paths, normalise manually
        $real = str_replace('\\', '/', $path);
    } else {
        $real = str_replace('\\', '/', $real);
    }

    $base_norm = str_replace('\\', '/', $base);

    if (! str_starts_with($real, $base_norm)) {
        return new WP_Error('path_outside_root', __('Path is outside the WordPress root directory.', 'vibewarrior'));
    }

    return $real;
}

/**
 * Return the absolute path to the VibeWarrior sandbox directory.
 */
function vibewarrior_get_sandbox_dir(): string
{
    return str_replace('\\', '/', WP_CONTENT_DIR) . '/' . VIBEWARRIOR_SANDBOX_DIR_NAME;
}

/**
 * Validate that a path lives inside the sandbox directory.
 *
 * @return true|WP_Error
 */
function vibewarrior_validate_sandbox_path(string $path): true|WP_Error
{
    $sandbox = vibewarrior_get_sandbox_dir();
    $norm    = str_replace('\\', '/', $path);

    if (! str_starts_with($norm, $sandbox)) {
        return new WP_Error(
            'path_outside_sandbox',
            sprintf(
                /* translators: %s: sandbox path */
                __('PHP files can only be written to the sandbox directory: %s', 'vibewarrior'),
                $sandbox
            )
        );
    }

    return true;
}

/**
 * Check that a PHP file path is inside the sandbox.
 *
 * @return true|WP_Error
 */
function vibewarrior_check_php_sandbox(string $path): true|WP_Error
{
    return vibewarrior_validate_sandbox_path($path);
}

// ---------------------------------------------------------------------------
// Permission callback (shared by all abilities)
// ---------------------------------------------------------------------------

function vibewarrior_permission_callback(): bool
{
    if (! is_user_logged_in()) {
        return false;
    }
    return current_user_can('manage_options');
}

// ---------------------------------------------------------------------------
// Feature flags
// ---------------------------------------------------------------------------

/**
 * Whether AI abilities are currently enabled and domain matches.
 */
function vibewarrior_is_enabled(): bool
{
    $enabled = get_option('vibewarrior_enabled', false);
    if (! $enabled) {
        return false;
    }
    return ! vibewarrior_is_domain_mismatch();
}

/**
 * Whether abilities are enabled but the saved domain differs from current site URL.
 */
function vibewarrior_is_domain_mismatch(): bool
{
    if (! get_option('vibewarrior_enabled', false)) {
        return false;
    }
    $saved = get_option('vibewarrior_domain', '');
    if (! $saved) {
        return false;
    }
    $current = wp_parse_url(home_url(), PHP_URL_HOST);
    return $saved !== $current;
}

// ---------------------------------------------------------------------------
// Environment detection
// ---------------------------------------------------------------------------

function vibewarrior_looks_like_production(): bool
{
    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    if (! $host) {
        return false;
    }
    $local_patterns = ['localhost', '127.0.0.1', '::1', '.local', '.test', '.dev', '.example'];
    foreach ($local_patterns as $pattern) {
        if (str_contains($host, $pattern)) {
            return false;
        }
    }
    // Has a public TLD — likely production
    return (bool) preg_match('/\.(com|net|org|io|app|co|ai|dev|info|biz|me)$/i', $host);
}

// ---------------------------------------------------------------------------
// Application Passwords status
// ---------------------------------------------------------------------------

function vibewarrior_app_passwords_status(): array
{
    if (! class_exists('WP_Application_Passwords')) {
        return ['available' => false, 'reason' => __('Application Passwords are not available on this WordPress version.', 'vibewarrior')];
    }
    if (! WP_Application_Passwords::is_in_use()) {
        return ['available' => false, 'reason' => __('Application Passwords are disabled on this site.', 'vibewarrior')];
    }
    return ['available' => true, 'reason' => ''];
}

// ---------------------------------------------------------------------------
// Server instructions
// ---------------------------------------------------------------------------

function vibewarrior_build_server_instructions(): string
{
    $lines = [
        'You are connected to a WordPress site via VibeWarrior.',
        '',
        'ENVIRONMENT ORIENTATION',
        '- Use vibewarrior/discover-abilities first to see all available tools.',
        '- Use vibewarrior/execute-php to run WordPress functions directly.',
        '- Use vibewarrior/list-directory to explore the filesystem.',
        '- Use vibewarrior/read-file and vibewarrior/write-file for file operations.',
        '',
        'BEST PRACTICES',
        '- Prefer WordPress functions (get_posts, update_option, $wpdb->get_results) over raw SQL or file manipulation.',
        '- Always use wp_insert_post, wp_update_post, etc. to maintain data integrity.',
        '- Sandbox PHP files live in wp-content/vibewarrior-sandbox/ — check .crashed for safe-mode status.',
        '- PHP execution is direct and bypasses the sandbox. Review code carefully before running.',
        '',
        'SAFETY',
        '- This plugin is designed for development and staging environments only.',
        '- Never call exit() or die() — it will terminate the entire WordPress request.',
        '- Avoid infinite loops — execution has a 30-second time limit.',
    ];

    $domain = get_option('vibewarrior_domain', '');
    if ($domain) {
        $lines[] = '';
        $lines[] = 'SITE: ' . home_url();
    }

    return implode("\n", $lines);
}

// ---------------------------------------------------------------------------
// Admin UI helper
// ---------------------------------------------------------------------------

function vibewarrior_render_admin_header(string $subtitle = ''): void
{
    ?>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding:16px 0;border-bottom:1px solid #e0e0e0;">
        <div style="width:36px;height:36px;background:linear-gradient(135deg,#7c3aed,#3b82f6);border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <span style="color:#fff;font-weight:800;font-size:18px;">V</span>
        </div>
        <div>
            <strong style="font-size:16px;color:#1a1a2e;">VibeWarrior</strong>
            <?php if ($subtitle) : ?>
                <span style="color:#666;margin-left:8px;">&mdash; <?php echo esc_html($subtitle); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Upload-link signing helpers
// ---------------------------------------------------------------------------

function vibewarrior_sign_upload_payload(array $payload): string|WP_Error
{
    $secret = get_option('vibewarrior_upload_secret', '');
    if (! $secret) {
        $secret = wp_generate_password(64, false);
        update_option('vibewarrior_upload_secret', $secret);
    }
    $json = wp_json_encode($payload);
    $sig  = hash_hmac('sha256', (string) $json, $secret);
    return base64_encode($json . '.' . $sig);
}

function vibewarrior_verify_upload_token(string $token): array|WP_Error
{
    $decoded = base64_decode($token, true);
    if ($decoded === false) {
        return new WP_Error('invalid_token', __('Invalid upload token.', 'vibewarrior'));
    }
    $pos = strrpos($decoded, '.');
    if ($pos === false) {
        return new WP_Error('invalid_token', __('Malformed upload token.', 'vibewarrior'));
    }
    $json = substr($decoded, 0, $pos);
    $sig  = substr($decoded, $pos + 1);

    $secret   = get_option('vibewarrior_upload_secret', '');
    $expected = hash_hmac('sha256', $json, $secret);
    if (! hash_equals($expected, $sig)) {
        return new WP_Error('invalid_signature', __('Upload token signature mismatch.', 'vibewarrior'));
    }

    $payload = json_decode($json, true);
    if (! is_array($payload)) {
        return new WP_Error('invalid_payload', __('Could not decode upload token payload.', 'vibewarrior'));
    }
    if (($payload['expires_at'] ?? 0) < time()) {
        return new WP_Error('token_expired', __('Upload token has expired.', 'vibewarrior'));
    }

    return $payload;
}
