<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/create-admin-access-link', [
    'label'       => __('Create Admin Access Link', 'vibewarrior'),
    'description' => __('Create a temporary one-time login URL for the current administrator. Useful for browser automation tools that need to open the WordPress admin without a password.', 'vibewarrior'),
    'category'    => 'admin',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'expires_in'      => ['type' => 'integer', 'description' => 'URL expiry in seconds.', 'minimum' => 30, 'maximum' => 600, 'default' => 300],
            'session_expires_in' => ['type' => 'integer', 'description' => 'Browser session duration in seconds.', 'minimum' => 60, 'maximum' => 3600, 'default' => 1800],
            'admin_path'      => ['type' => 'string', 'description' => 'Optional wp-admin relative path to redirect to after login (e.g. "post-new.php").', 'default' => ''],
        ],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'login_url'       => ['type' => 'string'],
            'expires_at'      => ['type' => 'integer'],
            'session_expires' => ['type' => 'integer'],
            'redirect_to'     => ['type' => 'string'],
            'one_time'        => ['type' => 'boolean'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_create_admin_access_link',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => [
            'instructions' => 'Do not paste this URL into public logs, issue trackers, or user-visible pages. It grants one-time admin access without a password.',
            'readonly'    => false,
            'destructive' => false,
            'idempotent'  => false,
        ],
    ],
]);

function vibewarrior_create_admin_access_link(array $input): array|WP_Error
{
    $expires_in      = max(30, min(600, (int) ($input['expires_in'] ?? 300)));
    $session_expires = max(60, min(3600, (int) ($input['session_expires_in'] ?? 1800)));
    $admin_path      = sanitize_text_field($input['admin_path'] ?? '');

    // Reject external redirects
    if ($admin_path && str_contains($admin_path, '://')) {
        return new WP_Error('invalid_redirect', __('admin_path must be a relative wp-admin path, not an external URL.', 'vibewarrior'));
    }

    $token    = wp_generate_password(32, false);
    $hash_key = 'vibewarrior_admin_access_' . hash('sha256', $token);

    set_transient($hash_key, [
        'user_id'         => get_current_user_id(),
        'admin_path'      => $admin_path,
        'session_expires' => $session_expires,
    ], $expires_in);

    $login_url = add_query_arg('token', rawurlencode($token), rest_url('vibewarrior/v1/admin-access'));

    return [
        'login_url'       => $login_url,
        'expires_at'      => time() + $expires_in,
        'session_expires' => $session_expires,
        'redirect_to'     => $admin_path ? admin_url($admin_path) : admin_url(),
        'one_time'        => true,
    ];
}
