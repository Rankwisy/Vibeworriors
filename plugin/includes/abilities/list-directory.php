<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/list-directory', [
    'label'       => __('List Directory', 'vibewarrior'),
    'description' => __('List files and subdirectories. Defaults to the WordPress root. Supports glob patterns, recursion, and depth limits.', 'vibewarrior'),
    'category'    => 'filesystem',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'           => ['type' => 'string', 'description' => 'Directory to list. Defaults to WordPress root.', 'default' => ''],
            'pattern'        => ['type' => 'string', 'description' => 'Glob pattern to filter entries (e.g. "*.php").', 'default' => ''],
            'recursive'      => ['type' => 'boolean', 'default' => false],
            'max_depth'      => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10, 'default' => 3],
            'include_hidden' => ['type' => 'boolean', 'default' => false],
            'limit'          => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5000, 'default' => 500],
        ],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'      => ['type' => 'string'],
            'entries'   => ['type' => 'array'],
            'total'     => ['type' => 'integer'],
            'truncated' => ['type' => 'boolean'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_list_directory',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => [
            'instructions' => 'AI-written PHP plugins live in wp-content/vibewarrior-sandbox/. Check wp-content/vibewarrior-sandbox/.crashed to see if safe mode is active.',
            'readonly'    => true,
            'destructive' => false,
            'idempotent'  => true,
        ],
    ],
]);

function vibewarrior_list_directory(array $input): array|WP_Error
{
    $raw_path = (string) ($input['path'] ?? '');
    $base     = $raw_path !== '' ? $raw_path : ABSPATH;

    $resolved = vibewarrior_resolve_path($base);
    if (is_wp_error($resolved)) {
        return $resolved;
    }

    if (! is_dir($resolved)) {
        return new WP_Error('not_a_directory', sprintf(__('Not a directory: %s', 'vibewarrior'), $resolved));
    }

    $pattern        = (string) ($input['pattern'] ?? '');
    $recursive      = ($input['recursive'] ?? false) === true;
    $max_depth      = max(1, min(10, (int) ($input['max_depth'] ?? 3)));
    $include_hidden = ($input['include_hidden'] ?? false) === true;
    $limit          = max(1, min(5000, (int) ($input['limit'] ?? 500)));

    $entries   = [];
    $truncated = false;

    if ($recursive) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $iterator->setMaxDepth($max_depth - 1);

        foreach ($iterator as $item) {
            if (count($entries) >= $limit) {
                $truncated = true;
                break;
            }
            $name = $item->getFilename();
            if (! $include_hidden && str_starts_with($name, '.')) {
                continue;
            }
            if ($pattern && ! fnmatch($pattern, $name)) {
                continue;
            }
            $entries[] = vibewarrior_build_entry($item->getPathname(), $item->isDir());
        }
    } else {
        $dh = opendir($resolved);
        if ($dh === false) {
            return new WP_Error('open_failed', sprintf(__('Cannot open directory: %s', 'vibewarrior'), $resolved));
        }

        $dirs = [];
        $files = [];
        while (($name = readdir($dh)) !== false) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            if (! $include_hidden && str_starts_with($name, '.')) {
                continue;
            }
            if ($pattern && ! fnmatch($pattern, $name)) {
                continue;
            }
            $full = $resolved . '/' . $name;
            if (is_dir($full)) {
                $dirs[] = $full;
            } else {
                $files[] = $full;
            }
        }
        closedir($dh);

        sort($dirs);
        sort($files);

        foreach (array_merge($dirs, $files) as $path) {
            if (count($entries) >= $limit) {
                $truncated = true;
                break;
            }
            $entries[] = vibewarrior_build_entry($path, is_dir($path));
        }
    }

    return [
        'path'      => $resolved,
        'entries'   => $entries,
        'total'     => count($entries),
        'truncated' => $truncated,
    ];
}

function vibewarrior_build_entry(string $path, bool $is_dir): array
{
    $stat = @stat($path);
    return [
        'name'     => basename($path),
        'path'     => $path,
        'type'     => $is_dir ? 'directory' : 'file',
        'size'     => $stat ? (int) $stat['size'] : null,
        'perms'    => $stat ? substr(sprintf('%o', $stat['mode']), -4) : null,
        'modified' => $stat ? (int) $stat['mtime'] : null,
    ];
}
