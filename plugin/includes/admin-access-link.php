<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST endpoint: consume a one-time admin-access token and log the user in.
 */
add_action('rest_api_init', static function (): void {
    register_rest_route('vibewarrior/v1', '/admin-access', [
        'methods'             => 'GET',
        'callback'            => 'vibewarrior_consume_admin_access_token',
        'permission_callback' => '__return_true',
    ]);
});

function vibewarrior_consume_admin_access_token(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    $token = sanitize_text_field($request->get_param('token') ?? '');
    if (! $token) {
        return new WP_Error('missing_token', __('Missing token parameter.', 'vibewarrior'), ['status' => 400]);
    }

    $stored = get_transient('vibewarrior_admin_access_' . hash('sha256', $token));
    if (! $stored || ! is_array($stored)) {
        return new WP_Error('invalid_token', __('Token is invalid or has expired.', 'vibewarrior'), ['status' => 403]);
    }

    delete_transient('vibewarrior_admin_access_' . hash('sha256', $token));

    $user_id = $stored['user_id'] ?? 0;
    $user    = get_user_by('id', $user_id);
    if (! $user || ! user_can($user, 'manage_options')) {
        return new WP_Error('invalid_user', __('User not found or lacks administrator privileges.', 'vibewarrior'), ['status' => 403]);
    }

    wp_set_auth_cookie($user_id, false);
    wp_set_current_user($user_id);

    $redirect = $stored['admin_path'] ?? '';
    $dest     = $redirect ? admin_url(ltrim($redirect, '/')) : admin_url();

    return new WP_REST_Response(null, 302, ['Location' => $dest]);
}
