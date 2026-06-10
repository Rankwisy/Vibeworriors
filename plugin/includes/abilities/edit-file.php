<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/edit-file', [
    'label'       => __('Edit File', 'vibewarrior'),
    'description' => __('Edit a file by replacing an exact string occurrence. Requires the exact text to match, including all whitespace and indentation.', 'vibewarrior'),
    'category'    => 'filesystem',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'        => ['type' => 'string', 'description' => 'Path to the file (relative to ABSPATH or absolute).', 'minLength' => 1],
            'old_string'  => ['type' => 'string', 'description' => 'Exact text to find (must be unique in the file unless replace_all is true).'],
            'new_string'  => ['type' => 'string', 'description' => 'Replacement text (empty string deletes the matched section).'],
            'replace_all' => ['type' => 'boolean', 'description' => 'Replace all occurrences instead of requiring a unique match.', 'default' => false],
        ],
        'required'             => ['path', 'old_string', 'new_string'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'         => ['type' => 'string'],
            'replacements' => ['type' => 'integer'],
            'size'         => ['type' => 'integer'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_edit_file',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => ['readonly' => false, 'destructive' => false, 'idempotent' => true],
    ],
]);

function vibewarrior_edit_replace_first(string $haystack, string $needle, string $replacement): string
{
    $pos = strpos($haystack, $needle);
    if ($pos === false) {
        return $haystack;
    }
    return substr($haystack, 0, $pos) . $replacement . substr($haystack, $pos + strlen($needle));
}

function vibewarrior_edit_file(array $input): array|WP_Error
{
    $resolved = vibewarrior_resolve_path((string) $input['path']);
    if (is_wp_error($resolved)) {
        return $resolved;
    }

    if (! is_file($resolved)) {
        return new WP_Error('not_a_file', sprintf(__('Not a file: %s', 'vibewarrior'), $resolved));
    }
    if (! is_readable($resolved) || ! is_writable($resolved)) {
        return new WP_Error('not_writable', sprintf(__('File is not readable/writable: %s', 'vibewarrior'), $resolved));
    }

    $old = (string) $input['old_string'];
    $new = (string) $input['new_string'];

    if ($old === $new) {
        return new WP_Error('identical_strings', __('old_string and new_string are identical — no edit needed.', 'vibewarrior'));
    }

    $content = file_get_contents($resolved);
    if ($content === false) {
        return new WP_Error('read_failed', __('Failed to read file.', 'vibewarrior'));
    }

    $replace_all  = ($input['replace_all'] ?? false) === true;
    $count        = substr_count($content, $old);

    if ($count === 0) {
        return new WP_Error('string_not_found', __('old_string was not found in the file.', 'vibewarrior'));
    }

    if (! $replace_all && $count > 1) {
        return new WP_Error(
            'ambiguous_match',
            sprintf(
                /* translators: %d: occurrence count */
                __('old_string appears %d times. Set replace_all=true or provide a more specific match.', 'vibewarrior'),
                $count
            )
        );
    }

    if ($replace_all) {
        $updated       = str_replace($old, $new, $content);
        $replacements  = $count;
    } else {
        $updated      = vibewarrior_edit_replace_first($content, $old, $new);
        $replacements = 1;
    }

    $written = file_put_contents($resolved, $updated, LOCK_EX);
    if ($written === false) {
        return new WP_Error('write_failed', __('Failed to write edited file.', 'vibewarrior'));
    }

    return [
        'path'         => $resolved,
        'replacements' => $replacements,
        'size'         => (int) filesize($resolved),
    ];
}
