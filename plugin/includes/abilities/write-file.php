<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/write-file', [
    'label'       => __('Write File', 'vibewarrior'),
    'description' => __('Write content to a file on the server filesystem. PHP files can only be written to the vibewarrior-sandbox directory. Non-PHP files may be written anywhere under the WordPress root.', 'vibewarrior'),
    'category'    => 'filesystem',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'             => ['type' => 'string', 'description' => 'Destination path (relative to ABSPATH or absolute).', 'minLength' => 1],
            'content'          => ['type' => 'string', 'description' => 'File content. UTF-8 text or base64-encoded binary.'],
            'encoding'         => ['type' => 'string', 'enum' => ['utf-8', 'base64'], 'default' => 'utf-8'],
            'create_directories' => ['type' => 'boolean', 'default' => true],
        ],
        'required'             => ['path', 'content'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'             => ['type' => 'string'],
            'bytes_written'    => ['type' => 'integer'],
            'created'          => ['type' => 'boolean'],
            'created_dirs'     => ['type' => 'array'],
            'size'             => ['type' => 'integer'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_write_file',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => [
            'instructions' => 'PHP files (*.php) can ONLY be written to wp-content/vibewarrior-sandbox/. If a sandbox file causes a fatal error, the system enters safe mode and disables all sandbox files automatically.',
            'readonly'    => false,
            'destructive' => true,
            'idempotent'  => false,
        ],
    ],
]);

function vibewarrior_write_file(array $input): array|WP_Error
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

    $encoding = $input['encoding'] ?? 'utf-8';
    $raw      = (string) $input['content'];
    if ($encoding === 'base64') {
        $decoded = base64_decode($raw, true);
        if ($decoded === false) {
            return new WP_Error('invalid_base64', __('Content is not valid base64.', 'vibewarrior'));
        }
        $raw = $decoded;
    }

    $created_dirs = [];
    $dir          = dirname($resolved);
    $created      = ! file_exists($resolved);

    if (! is_dir($dir)) {
        if (! ($input['create_directories'] ?? true)) {
            return new WP_Error('dir_missing', sprintf(__('Directory does not exist: %s', 'vibewarrior'), $dir));
        }
        // Walk up and record which dirs we create
        $missing = [];
        $check   = $dir;
        while (! is_dir($check)) {
            $missing[] = $check;
            $check     = dirname($check);
        }
        wp_mkdir_p($dir);
        $created_dirs = array_reverse($missing);
    }

    $written = file_put_contents($resolved, $raw, LOCK_EX);
    if ($written === false) {
        return new WP_Error('write_failed', sprintf(__('Failed to write file: %s', 'vibewarrior'), $resolved));
    }

    if ($created) {
        chmod($resolved, 0644);
    }

    return [
        'path'          => $resolved,
        'bytes_written' => $written,
        'created'       => $created,
        'created_dirs'  => $created_dirs,
        'size'          => filesize($resolved),
    ];
}
