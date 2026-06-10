<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/read-file', [
    'label'       => __('Read File', 'vibewarrior'),
    'description' => __('Read the contents of a file from the WordPress filesystem. Returns text as UTF-8; binary files are base64-encoded.', 'vibewarrior'),
    'category'    => 'filesystem',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'   => ['type' => 'string', 'description' => 'File path relative to WordPress root (ABSPATH) or absolute.', 'minLength' => 1],
            'offset' => ['type' => 'integer', 'description' => 'Byte offset to start reading from.', 'minimum' => 0, 'default' => 0],
            'limit'  => ['type' => 'integer', 'description' => 'Maximum bytes to read. Default: 1 MiB.', 'minimum' => 1, 'default' => 1_048_576],
        ],
        'required'             => ['path'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'path'      => ['type' => 'string'],
            'content'   => ['type' => 'string'],
            'encoding'  => ['type' => 'string', 'enum' => ['utf-8', 'base64']],
            'size'      => ['type' => 'integer'],
            'bytes_read' => ['type' => 'integer'],
            'truncated' => ['type' => 'boolean'],
            'mime_type' => ['type' => 'string'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_read_file',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

function vibewarrior_read_file(array $input): array|WP_Error
{
    $resolved = vibewarrior_resolve_path((string) $input['path']);
    if (is_wp_error($resolved)) {
        return $resolved;
    }

    if (! is_file($resolved)) {
        return new WP_Error('not_a_file', sprintf(__('Not a file: %s', 'vibewarrior'), $resolved));
    }
    if (! is_readable($resolved)) {
        return new WP_Error('not_readable', sprintf(__('File is not readable: %s', 'vibewarrior'), $resolved));
    }

    $size   = filesize($resolved);
    $offset = max(0, (int) ($input['offset'] ?? 0));
    $limit  = max(1, (int) ($input['limit'] ?? 1_048_576));

    $fh = fopen($resolved, 'rb');
    if ($fh === false) {
        return new WP_Error('open_failed', __('Failed to open file for reading.', 'vibewarrior'));
    }
    if ($offset > 0) {
        fseek($fh, $offset);
    }
    $raw = fread($fh, $limit);
    fclose($fh);

    $bytes_read = strlen($raw);
    $truncated  = ($offset + $bytes_read) < $size;

    $mime = mime_content_type($resolved) ?: 'application/octet-stream';

    // Determine encoding
    if (mb_check_encoding($raw, 'UTF-8') && ! str_contains($mime, 'image') && ! str_contains($mime, 'audio') && ! str_contains($mime, 'video')) {
        $encoding = 'utf-8';
        $content  = $raw;
    } else {
        $encoding = 'base64';
        $content  = base64_encode($raw);
    }

    return [
        'path'       => $resolved,
        'content'    => $content,
        'encoding'   => $encoding,
        'size'       => $size,
        'bytes_read' => $bytes_read,
        'truncated'  => $truncated,
        'mime_type'  => $mime,
    ];
}
