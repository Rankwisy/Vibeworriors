<?php

// SPDX-FileCopyrightText: 2026 VibeWarrior <hello@vibewarrior.com>
// SPDX-License-Identifier: GPL-2.0-or-later

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// ---------------------------------------------------------------------------
// run-wp-cli (sync + async)
// ---------------------------------------------------------------------------

wp_register_ability('vibewarrior/run-wp-cli', [
    'label'       => __('Run WP-CLI Command', 'vibewarrior'),
    'description' => __('Execute a WP-CLI command on the server. Supports both synchronous (blocking) and asynchronous (background) execution.', 'vibewarrior'),
    'category'    => 'code',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'args'  => [
                'type'        => 'array',
                'description' => 'WP-CLI command arguments as an array (e.g. ["post", "list", "--format=json"]).',
                'items'       => ['type' => 'string'],
                'minItems'    => 1,
            ],
            'async' => ['type' => 'boolean', 'description' => 'Run in background and return a job ID immediately.', 'default' => false],
        ],
        'required'             => ['args'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type'       => 'object',
        'properties' => [
            'stdout'    => ['type' => 'string'],
            'stderr'    => ['type' => 'string'],
            'exit_code' => ['type' => 'integer'],
            'job_id'    => ['type' => 'string'],
            'async'     => ['type' => 'boolean'],
        ],
    ],
    'execute_callback'    => 'vibewarrior_run_wp_cli',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => ['readonly' => false, 'destructive' => true, 'idempotent' => false],
    ],
]);

// ---------------------------------------------------------------------------
// get-wp-cli-job (poll async jobs)
// ---------------------------------------------------------------------------

wp_register_ability('vibewarrior/get-wp-cli-job', [
    'label'       => __('Get WP-CLI Job Status', 'vibewarrior'),
    'description' => __('Query the status and output of an asynchronous WP-CLI job started with vibewarrior/run-wp-cli.', 'vibewarrior'),
    'category'    => 'code',
    'input_schema' => [
        'type'       => 'object',
        'properties' => [
            'job_id' => ['type' => 'string', 'description' => 'Job ID returned by run-wp-cli with async=true.', 'pattern' => '^[0-9a-f]{16}$'],
            'offset' => ['type' => 'integer', 'description' => 'Byte offset for partial log reads.', 'minimum' => 0, 'default' => 0],
            'limit'  => ['type' => 'integer', 'description' => 'Maximum bytes of log to return.', 'minimum' => 1, 'default' => 65536],
        ],
        'required'             => ['job_id'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type' => 'object',
    ],
    'execute_callback'    => 'vibewarrior_get_wp_cli_job',
    'permission_callback' => 'vibewarrior_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp'          => ['public' => true],
        'annotations'  => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

// ---------------------------------------------------------------------------
// Implementations
// ---------------------------------------------------------------------------

function vibewarrior_find_wp_cli(): string|WP_Error
{
    $candidates = ['wp', 'wp-cli', '/usr/local/bin/wp', '/usr/bin/wp'];
    foreach ($candidates as $c) {
        $out = shell_exec('which ' . escapeshellarg($c) . ' 2>/dev/null');
        if ($out && trim($out)) {
            return trim($out);
        }
    }
    return new WP_Error('wp_cli_not_found', __('WP-CLI executable not found on this server.', 'vibewarrior'));
}

function vibewarrior_run_wp_cli(array $input): array|WP_Error
{
    $args = (array) $input['args'];
    foreach ($args as $arg) {
        if (! is_string($arg)) {
            return new WP_Error('invalid_args', __('All WP-CLI arguments must be strings.', 'vibewarrior'));
        }
    }

    $wp_cli = vibewarrior_find_wp_cli();
    if (is_wp_error($wp_cli)) {
        return $wp_cli;
    }

    // Build command
    $cmd_parts = [$wp_cli];
    if (posix_getuid() === 0) {
        $cmd_parts[] = '--allow-root';
    }
    foreach ($args as $arg) {
        $cmd_parts[] = escapeshellarg($arg);
    }
    $cmd_parts[] = '--path=' . escapeshellarg(ABSPATH);
    $cmd = implode(' ', $cmd_parts);

    $async = ($input['async'] ?? false) === true;

    if ($async) {
        $job_id  = bin2hex(random_bytes(8));
        $log_dir = sys_get_temp_dir();
        $log_file = $log_dir . '/vibewarrior-job-' . $job_id . '.log';
        $exit_file = $log_dir . '/vibewarrior-job-' . $job_id . '.exit';

        $bg_cmd = $cmd . ' > ' . escapeshellarg($log_file) . ' 2>&1; echo $? > ' . escapeshellarg($exit_file);
        shell_exec($bg_cmd . ' &');

        return ['async' => true, 'job_id' => $job_id, 'stdout' => '', 'stderr' => '', 'exit_code' => null];
    }

    // Synchronous
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $proc = proc_open($cmd, $descriptors, $pipes);
    if (! is_resource($proc)) {
        return new WP_Error('proc_failed', __('Failed to start WP-CLI process.', 'vibewarrior'));
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $timeout = microtime(true) + 120;

    while (true) {
        $read = [$pipes[1], $pipes[2]];
        $write = $except = [];
        if (stream_select($read, $write, $except, 0, 50_000) > 0) {
            foreach ($read as $stream) {
                $chunk = fread($stream, 4096);
                if ($chunk !== false) {
                    if ($stream === $pipes[1]) {
                        $stdout .= $chunk;
                    } else {
                        $stderr .= $chunk;
                    }
                }
            }
        }
        if (feof($pipes[1]) && feof($pipes[2])) {
            break;
        }
        if (microtime(true) > $timeout) {
            proc_terminate($proc);
            break;
        }
    }

    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit_code = proc_close($proc);

    return ['async' => false, 'stdout' => $stdout, 'stderr' => $stderr, 'exit_code' => $exit_code];
}

function vibewarrior_get_wp_cli_job(array $input): array|WP_Error
{
    $job_id = preg_replace('/[^0-9a-f]/', '', (string) ($input['job_id'] ?? ''));
    if (strlen($job_id) !== 16) {
        return new WP_Error('invalid_job_id', __('Invalid job ID format.', 'vibewarrior'));
    }

    $log_dir  = sys_get_temp_dir();
    $log_file = $log_dir . '/vibewarrior-job-' . $job_id . '.log';
    $exit_file = $log_dir . '/vibewarrior-job-' . $job_id . '.exit';

    if (! file_exists($log_file)) {
        return new WP_Error('job_not_found', sprintf(__('Job not found: %s', 'vibewarrior'), $job_id));
    }

    $offset = max(0, (int) ($input['offset'] ?? 0));
    $limit  = max(1, (int) ($input['limit'] ?? 65536));

    $fh  = fopen($log_file, 'rb');
    if ($fh === false) {
        return new WP_Error('read_failed', __('Failed to read job log.', 'vibewarrior'));
    }
    if ($offset > 0) {
        fseek($fh, $offset);
    }
    $log = (string) fread($fh, $limit);
    fclose($fh);

    $done      = file_exists($exit_file);
    $exit_code = $done ? (int) trim((string) file_get_contents($exit_file)) : null;

    return [
        'job_id'    => $job_id,
        'done'      => $done,
        'exit_code' => $exit_code,
        'log'       => $log,
        'log_size'  => (int) filesize($log_file),
    ];
}
