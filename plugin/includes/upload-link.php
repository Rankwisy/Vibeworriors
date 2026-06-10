<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST endpoint: receive a file upload via a signed token URL.
 */
add_action('rest_api_init', static function (): void {
    register_rest_route('vibewarrior/v1', '/upload', [
        'methods'             => ['PUT', 'POST'],
        'callback'            => 'vibewarrior_handle_upload',
        'permission_callback' => '__return_true',
    ]);
});

function vibewarrior_handle_upload(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    $raw_token = sanitize_text_field($request->get_param('token') ?? '');
    if (! $raw_token) {
        return new WP_Error('missing_token', __('Missing token parameter.', 'vibewarrior'), ['status' => 400]);
    }

    $payload = vibewarrior_verify_upload_token($raw_token);
    if (is_wp_error($payload)) {
        $payload->add_data(['status' => 403]);
        return $payload;
    }

    $dest     = (string) ($payload['path'] ?? '');
    $max      = (int) ($payload['max_bytes'] ?? 536_870_912);
    $overwrite = (bool) ($payload['overwrite'] ?? false);
    $create_dirs = (bool) ($payload['create_directories'] ?? true);

    if (! $dest) {
        return new WP_Error('missing_path', __('Upload token payload is missing the destination path.', 'vibewarrior'), ['status' => 400]);
    }

    // Read body (raw PUT or multipart POST)
    $body = null;
    $files = $request->get_file_params();
    if (! empty($files['file']['tmp_name'])) {
        $body = file_get_contents($files['file']['tmp_name']);
    } else {
        $body = $request->get_body();
    }

    if ($body === false || $body === '') {
        return new WP_Error('empty_body', __('Request body is empty.', 'vibewarrior'), ['status' => 400]);
    }

    if (strlen($body) > $max) {
        return new WP_Error('too_large', sprintf(__('Upload exceeds limit of %s.', 'vibewarrior'), size_format($max)), ['status' => 413]);
    }

    if (! $overwrite && file_exists($dest)) {
        return new WP_Error('file_exists', __('Destination file already exists and overwrite is not permitted.', 'vibewarrior'), ['status' => 409]);
    }

    if ($create_dirs) {
        $dir = dirname($dest);
        if (! is_dir($dir)) {
            wp_mkdir_p($dir);
        }
    }

    $written = file_put_contents($dest, $body, LOCK_EX);
    if ($written === false) {
        return new WP_Error('write_failed', __('Failed to write uploaded file.', 'vibewarrior'), ['status' => 500]);
    }

    return new WP_REST_Response([
        'path'    => $dest,
        'bytes'   => $written,
        'created' => ! file_exists($dest . '.bak'),
    ], 200);
}
