<?php

$appEnvironment = env('APP_ENV', 'production');
$isLocalEnvironment = in_array($appEnvironment, ['local', 'dev', 'development'], true);
$appDebugEnabled = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
$diagnosticsEnabled = filter_var(env('MOOF_DIAGNOSTICS_ENABLED', $isLocalEnvironment && $appDebugEnabled), FILTER_VALIDATE_BOOL);
return [
    'diagnostics' => [
        'enabled' => $diagnosticsEnabled,
        'disable_legacy_ray' => filter_var(
            env('MOOF_DIAGNOSTICS_DISABLE_LEGACY_RAY', $diagnosticsEnabled),
            FILTER_VALIDATE_BOOL,
        ),
        'endpoint' => env('MOOF_DIAGNOSTICS_URL', 'http://127.0.0.1:3030/api/v1/diagnostics/php'),
        'source' => env('MOOF_DIAGNOSTICS_SOURCE', 'php:laravel'),
    ],
];
