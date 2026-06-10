<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/enable-file', [
    'label'       => __('Enable Sandbox File', 'vibewarrior'),
    'description' => __('Re-enable a previously disabled sandbox file by removing the ".disabled" suffix from its filename.', 'vibewarrior'),
    'category'    => 'filesystem',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'path' => ['type' => 'string', 'description' => 'Path to the disabled sandbox file. Accepts either the original name or the .disabled version.', 'minLength' => 1],
        ],
        'required'             => ['path'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'disabled_path' => ['type' => 'string'],
            'enabled_path'  => ['type' => 'string'],
            'enabled'       => ['type' => 'boolean'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_enable_file',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => ['readonly' => false, 'destructive' => false, 'idempotent' => true],
    ],
]);

function vibewarrior_enable_file(array $input): array|WP_Error
{
    $raw = (string) $input['path'];

    // Accept both "file.php" and "file.php.disabled"
    if (! str_ends_with($raw, '.disabled')) {
        $raw .= '.disabled';
    }

    $resolved = vibewarrior_resolve_path($raw);
    if (is_wp_error($resolved)) {
        return $resolved;
    }

    $check = vibewarrior_validate_sandbox_path($resolved);
    if (is_wp_error($check)) {
        return $check;
    }

    if (! is_file($resolved)) {
        return new WP_Error('not_found', sprintf(__('Disabled file not found: %s', 'vibewarrior'), $resolved));
    }

    $target = substr($resolved, 0, -9); // strip .disabled

    if (! rename($resolved, $target)) {
        return new WP_Error('rename_failed', __('Failed to rename sandbox file.', 'vibewarrior'));
    }

    return ['disabled_path' => $resolved, 'enabled_path' => $target, 'enabled' => true];
}
