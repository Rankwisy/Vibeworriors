<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/disable-file', [
    'label'       => __('Disable Sandbox File', 'vibewarrior'),
    'description' => __('Disable a sandbox PHP file by appending ".disabled" so it is no longer loaded. Safer than deleting: the file stays on disk for later re-use.', 'vibewarrior'),
    'category'    => 'filesystem',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'path' => ['type' => 'string', 'description' => 'Path to the sandbox file (relative or absolute, must be inside vibewarrior-sandbox/).', 'minLength' => 1],
        ],
        'required'             => ['path'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'original_path' => ['type' => 'string'],
            'disabled_path' => ['type' => 'string'],
            'disabled'      => ['type' => 'boolean'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_disable_file',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => ['readonly' => false, 'destructive' => false, 'idempotent' => true],
    ],
]);

function vibewarrior_disable_file(array $input): array|WP_Error
{
    $resolved = vibewarrior_resolve_path((string) $input['path']);
    if (is_wp_error($resolved)) {
        return $resolved;
    }

    $check = vibewarrior_validate_sandbox_path($resolved);
    if (is_wp_error($check)) {
        return $check;
    }

    if (! is_file($resolved)) {
        return new WP_Error('not_a_file', sprintf(__('Not a file: %s', 'vibewarrior'), $resolved));
    }

    if (str_ends_with($resolved, '.disabled')) {
        return ['original_path' => $resolved, 'disabled_path' => $resolved, 'disabled' => false];
    }

    $target = $resolved . '.disabled';
    if (file_exists($target)) {
        return new WP_Error('already_exists', sprintf(__('Disabled version already exists: %s', 'vibewarrior'), $target));
    }

    if (! rename($resolved, $target)) {
        return new WP_Error('rename_failed', __('Failed to rename sandbox file.', 'vibewarrior'));
    }

    return ['original_path' => $resolved, 'disabled_path' => $target, 'disabled' => true];
}
