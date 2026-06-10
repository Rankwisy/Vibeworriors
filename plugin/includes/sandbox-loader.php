<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var string|null Tracks the sandbox file currently being loaded (for crash detection). */
$vibewarrior_current_sandbox_file = null;

/**
 * Shutdown handler: detects fatal errors during sandbox file loading and marks the
 * crashing file so safe mode activates on the next request.
 */
function vibewarrior_sandbox_crash_handler(): void
{
    global $vibewarrior_current_sandbox_file;

    $error = error_get_last();
    if (! $error || ! in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    if ($vibewarrior_current_sandbox_file !== null) {
        $crashed_marker = vibewarrior_get_sandbox_dir() . '/.crashed';
        file_put_contents($crashed_marker, $vibewarrior_current_sandbox_file);
    }
}

/**
 * Load all enabled PHP files from the sandbox directory.
 * When abilities are disabled, skip crash-recovery overhead and load directly.
 */
add_action('plugins_loaded', static function (): void {
    global $vibewarrior_current_sandbox_file;

    $sandbox_dir = vibewarrior_get_sandbox_dir();
    if (! is_dir($sandbox_dir)) {
        return;
    }

    // Check safe mode
    $crashed_marker = $sandbox_dir . '/.crashed';
    $safe_mode      = false;

    if (isset($_GET['vibewarrior_safe_mode']) && current_user_can('manage_options')) {
        $safe_mode = true;
    } elseif (file_exists($crashed_marker)) {
        $safe_mode = true;
    }

    if ($safe_mode) {
        // Notify admin
        add_action('admin_notices', static function () use ($crashed_marker): void {
            $crashed_file = file_exists($crashed_marker) ? trim((string) file_get_contents($crashed_marker)) : '';
            echo '<div class="notice notice-error"><p>';
            printf(
                esc_html__('VibeWarrior Sandbox: Safe mode is active. %s caused a fatal error. Fix or delete the file, then remove %s to resume normal operation.', 'vibewarrior'),
                '<code>' . esc_html(basename($crashed_file)) . '</code>',
                '<code>' . esc_html($crashed_marker) . '</code>'
            );
            echo '</p></div>';
        });
        return;
    }

    $abilities_enabled = vibewarrior_is_enabled();

    if ($abilities_enabled) {
        register_shutdown_function('vibewarrior_sandbox_crash_handler');
    }

    $files = glob($sandbox_dir . '/*.php') ?: [];
    foreach ($files as $file) {
        if ($abilities_enabled) {
            $vibewarrior_current_sandbox_file = $file;
        }
        require_once $file;
    }

    if ($abilities_enabled) {
        $vibewarrior_current_sandbox_file = null;
    }
}, 5);
