<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/delete-file', [
    'label'       => __('Delete File', 'vibewarrior'),
    'description' => __('Delete a file or directory from the server filesystem. Non-empty directories require recursive=true.', 'vibewarrior'),
    'category'    => 'filesystem',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'      => ['type' => 'string', 'description' => 'Path to delete (relative to ABSPATH or absolute).', 'minLength' => 1],
            'recursive' => ['type' => 'boolean', 'description' => 'Delete non-empty directories recursively.', 'default' => false],
        ],
        'required'             => ['path'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'         => ['type' => 'string'],
            'type'         => ['type' => 'string'],
            'deleted'      => ['type' => 'boolean'],
            'items_removed' => ['type' => 'integer'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_delete_file',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => ['readonly' => false, 'destructive' => true, 'idempotent' => true],
    ],
]);

function vibewarrior_delete_file(array $input): array|WP_Error
{
    $resolved = vibewarrior_resolve_path((string) $input['path'], must_exist: false);
    if (is_wp_error($resolved)) {
        return $resolved;
    }

    // Protect critical directories
    $protected = [
        str_replace('\\', '/', ABSPATH),
        str_replace('\\', '/', ABSPATH . 'wp-admin'),
        str_replace('\\', '/', ABSPATH . 'wp-includes'),
        str_replace('\\', '/', WP_CONTENT_DIR . '/mu-plugins'),
    ];
    if (in_array(rtrim($resolved, '/'), $protected, true)) {
        return new WP_Error('protected_path', __('This path is protected and cannot be deleted.', 'vibewarrior'));
    }

    if (! file_exists($resolved) && ! is_link($resolved)) {
        return ['path' => $resolved, 'type' => 'not_found', 'deleted' => true, 'items_removed' => 0];
    }

    $recursive = ($input['recursive'] ?? false) === true;
    $removed   = 0;

    if (is_link($resolved) || is_file($resolved)) {
        if (! unlink($resolved)) {
            return new WP_Error('delete_failed', sprintf(__('Failed to delete file: %s', 'vibewarrior'), $resolved));
        }
        return ['path' => $resolved, 'type' => 'file', 'deleted' => true, 'items_removed' => 1];
    }

    // Directory
    if (! $recursive) {
        $items = array_diff((array) scandir($resolved), ['.', '..']);
        if (! empty($items)) {
            return new WP_Error('dir_not_empty', __('Directory is not empty. Set recursive=true to delete recursively.', 'vibewarrior'));
        }
        rmdir($resolved);
        return ['path' => $resolved, 'type' => 'directory', 'deleted' => true, 'items_removed' => 1];
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isLink() || $item->isFile()) {
            unlink($item->getPathname());
        } else {
            rmdir($item->getPathname());
        }
        $removed++;
    }

    rmdir($resolved);
    $removed++;

    return ['path' => $resolved, 'type' => 'directory', 'deleted' => true, 'items_removed' => $removed];
}
