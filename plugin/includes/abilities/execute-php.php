<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wp_register_ability('vibewarrior/execute-php', [
    'label'       => __('Execute PHP', 'vibewarrior'),
    'description' => __('Execute arbitrary PHP code inside the WordPress process. Has full access to $wpdb, all WordPress functions, and all loaded plugins. Code runs synchronously and results are returned immediately.', 'vibewarrior'),
    'category'    => 'code',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'code' => [
                'type'        => 'string',
                'description' => 'PHP code to execute. Do not include the opening <?php tag. Use return to return a value.',
                'minLength'   => 1,
            ],
        ],
        'required'             => ['code'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'return_value'   => ['description' => 'Value returned by the code (via return statement).'],
            'output'         => ['type' => 'string', 'description' => 'Captured stdout/echo output.'],
            'execution_time' => ['type' => 'number', 'description' => 'Execution time in milliseconds.'],
            'warnings'       => ['type' => 'array', 'description' => 'PHP warnings, notices, and deprecations.'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_execute_php',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => [
            'instructions' => implode("\n", [
                'IMPORTANT: This executes real PHP inside your WordPress site.',
                'Never call exit() or die() — it will terminate the entire WordPress process.',
                'Never create infinite loops — there is a 30-second time limit.',
                'Use return to get a value back. echo/print output is also captured.',
                'The code runs as the current authenticated WordPress user.',
                'Code executed here is temporary — use vibewarrior/write-file to persist PHP.',
            ]),
            'readonly'    => false,
            'destructive' => true,
            'idempotent'  => false,
        ],
    ],
]);

function vibewarrior_execute_php(array $input): array|WP_Error
{
    if (! vibewarrior_is_enabled()) {
        return new WP_Error('abilities_disabled', __('AI abilities are disabled. Enable them in the VibeWarrior settings.', 'vibewarrior'));
    }

    $code   = (string) $input['code'];
    $warnings = [];

    set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
        $warnings[] = sprintf('[%d] %s', $errno, $errstr);
        return true;
    });

    $start = microtime(true);
    $return_value = null;

    ob_start();
    try {
        // phpcs:ignore Squiz.PHP.Eval.Discouraged
        $return_value = eval($code);
    } catch (Throwable $e) {
        restore_error_handler();
        ob_end_clean();
        return new WP_Error(
            'php_exception',
            sprintf('%s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine())
        );
    }

    $output     = ob_get_clean();
    $elapsed_ms = round((microtime(true) - $start) * 1000, 2);

    restore_error_handler();

    // Ensure return value is serialisable
    if ($return_value !== null && ! is_scalar($return_value) && ! is_array($return_value)) {
        $return_value = print_r($return_value, true);
    }

    return [
        'return_value'   => $return_value,
        'output'         => (string) $output,
        'execution_time' => $elapsed_ms,
        'warnings'       => $warnings,
    ];
}
