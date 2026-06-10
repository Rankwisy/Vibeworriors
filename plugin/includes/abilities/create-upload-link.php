<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/create-upload-link', [
    'label'       => __('Create Upload Link', 'vibewarrior'),
    'description' => __('Creates a temporary self-authenticated URL that external tools can use to upload one file into the WordPress filesystem. Useful for uploading large ZIPs, plugins, themes, or media via curl.', 'vibewarrior'),
    'category'    => 'filesystem',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'             => ['type' => 'string', 'description' => 'Destination file path (relative to ABSPATH or absolute).', 'minLength' => 1],
            'expires_in'       => ['type' => 'integer', 'description' => 'Seconds before the URL expires.', 'minimum' => 30, 'maximum' => 3600, 'default' => 900],
            'max_bytes'        => ['type' => 'integer', 'description' => 'Maximum upload size in bytes.', 'minimum' => 1, 'default' => 536_870_912],
            'overwrite'        => ['type' => 'boolean', 'default' => false],
            'create_directories' => ['type' => 'boolean', 'default' => true],
        ],
        'required'             => ['path'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'upload_url'   => ['type' => 'string'],
            'method'       => ['type' => 'string'],
            'path'         => ['type' => 'string'],
            'expires_at'   => ['type' => 'integer'],
            'max_bytes'    => ['type' => 'integer'],
            'overwrite'    => ['type' => 'boolean'],
            'curl_examples' => ['type' => 'array'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_create_upload_link',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => [
            'instructions' => implode("\n", [
                'Use when a file is too large or inconvenient to send through MCP JSON transport.',
                'Recommended curl form: curl -X PUT --data-binary @/path/to/local-file "$upload_url"',
                'PHP files (*.php) can ONLY be uploaded to wp-content/vibewarrior-sandbox/.',
            ]),
            'readonly'    => false,
            'destructive' => false,
            'idempotent'  => false,
        ],
    ],
]);

function vibewarrior_create_upload_link(array $input): array|WP_Error
{
    $resolved = vibewarrior_resolve_path((string) $input['path'], must_exist: false);
    if (is_wp_error($resolved)) {
        return $resolved;
    }

    $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        $check = vibewarrior_check_php_sandbox($resolved);
        if (is_wp_error($check)) {
            return $check;
        }
    }

    $expires_in      = max(30, min(3600, (int) ($input['expires_in'] ?? 900)));
    $max_bytes       = max(1, (int) ($input['max_bytes'] ?? 536_870_912));
    $expires_at      = time() + $expires_in;
    $overwrite       = ($input['overwrite'] ?? false) === true;
    $create_dirs     = ($input['create_directories'] ?? true) !== false;

    $payload = [
        'path'               => $resolved,
        'expires_at'         => $expires_at,
        'max_bytes'          => $max_bytes,
        'overwrite'          => $overwrite,
        'create_directories' => $create_dirs,
    ];

    $token = vibewarrior_sign_upload_payload($payload);
    if (is_wp_error($token)) {
        return $token;
    }

    $upload_url = add_query_arg('token', rawurlencode($token), rest_url('vibewarrior/v1/upload'));

    return [
        'upload_url'    => $upload_url,
        'method'        => 'PUT',
        'path'          => $resolved,
        'expires_at'    => $expires_at,
        'max_bytes'     => $max_bytes,
        'overwrite'     => $overwrite,
        'curl_examples' => [
            'curl -X PUT --data-binary @/path/to/local-file ' . escapeshellarg($upload_url),
            'curl -F file=@/path/to/local-file ' . escapeshellarg($upload_url),
        ],
    ];
}
