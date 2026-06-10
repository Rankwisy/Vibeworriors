<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/discover-abilities', [
    'label'       => __('Discover Abilities', 'vibewarrior'),
    'description' => __('Discover all available WordPress abilities in the system, including their names, labels, and descriptions. Call this first to understand what actions are available.', 'vibewarrior'),
    'category'    => 'meta',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [],
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'abilities'             => ['type' => 'array'],
            'vibewarrior_instructions' => ['type' => 'string'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_discover_abilities',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => [
            'readonly'    => true,
            'destructive' => false,
            'idempotent'  => true,
        ],
    ],
]);

function vibewarrior_discover_abilities(): array
{
    $abilities = vibewarrior_collect_public_abilities();

    $list = [];
    foreach ($abilities as $name => $ability) {
        $list[] = [
            'name'        => $name,
            'label'       => $ability['label'] ?? $name,
            'description' => $ability['description'] ?? '',
            'category'    => $ability['category'] ?? '',
        ];
    }

    $result = ['abilities' => $list];

    if (current_user_can('manage_options')) {
        $result['vibewarrior_instructions'] = vibewarrior_build_server_instructions();
    }

    return $result;
}
