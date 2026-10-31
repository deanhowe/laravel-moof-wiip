<?php

$appEnvironment = env('APP_ENV', 'production');
$isLocalEnvironment = in_array($appEnvironment, ['local', 'dev', 'development'], true);
$appDebugEnabled = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
$diagnosticsEnabled = filter_var(env('MOOF_DIAGNOSTICS_ENABLED', $isLocalEnvironment && $appDebugEnabled), FILTER_VALIDATE_BOOL);
$home = env('HOME', getenv('HOME') ?: '');

return [
    'diagnostics' => [
        'enabled' => $diagnosticsEnabled,
        'disable_legacy_ray' => filter_var(
            env('MOOF_DIAGNOSTICS_DISABLE_LEGACY_RAY', $diagnosticsEnabled),
            FILTER_VALIDATE_BOOL,
        ),
        'database_path' => env(
            'MOOF_DB',
            env(
                'MOOF_DB_PATH',
                rtrim($home, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.moof-git'.DIRECTORY_SEPARATOR.'brain'.DIRECTORY_SEPARATOR.'moof.db',
            ),
        ),
        'source' => env('MOOF_DIAGNOSTICS_SOURCE', 'php:laravel'),
        'rate_limit_per_minute' => env('MOOF_DIAGNOSTICS_RATE_LIMIT', 30),
    ],
];
