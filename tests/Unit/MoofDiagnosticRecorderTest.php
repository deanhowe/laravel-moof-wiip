<?php

use DeanHowe\Laravel\Moof\Diagnostics\RayIntegration;
use DeanHowe\Laravel\Moof\Diagnostics\Recorder;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

function moofWiipFakeAcceptedDiagnostics(): HttpFactory
{
    $http = new HttpFactory;
    Http::swap($http);
    $http->fake(function (HttpRequest $request) {
        $body = json_decode($request->body(), true, flags: JSON_THROW_ON_ERROR);

        return HttpFactory::response([
            'event_id' => $body['event_id'],
            'queued' => true,
        ], 202);
    });

    return $http;
}

function moofWiipDecodeDiagnosticsRequest(HttpRequest $request): array
{
    return json_decode($request->body(), true, flags: JSON_THROW_ON_ERROR);
}

it('submits one bounded structured diagnostic to the local Forge intake', function () {
    $http = moofWiipFakeAcceptedDiagnostics();
    $eventId = (new Recorder('http://127.0.0.1:3030/api/v1/diagnostics/php', 'php:tests'))->capture(
        ['request started', ['attempt' => 2]],
        ['file' => 'app/Example.php', 'line' => 42],
    );

    expect($eventId)->toBeString();

    $http->assertSent(function (HttpRequest $request) use ($eventId): bool {
        $body = moofWiipDecodeDiagnosticsRequest($request);

        return $request->url() === 'http://127.0.0.1:3030/api/v1/diagnostics/php'
            && $body['event_id'] === $eventId
            && $body['source'] === 'php:tests'
            && $body['payload'] === [
                'values' => ['request started', ['attempt' => 2]],
                'callsite' => ['file' => 'app/Example.php', 'line' => 42],
            ];
    });
});

it('redacts private fields and credential-shaped text before sending diagnostics', function () {
    $http = moofWiipFakeAcceptedDiagnostics();
    (new Recorder('http://127.0.0.1:3030/api/v1/diagnostics/php', 'php:tests'))->capture([
        'token' => 'secret-token-value',
        'private_key' => 'private-key-value',
        'profile' => [
            'name' => 'Test Person',
            'email' => 'private@example.test',
            'note' => 'Authorization: Bearer abc.def password=hunter2 private_key=abc123 to private@example.test',
        ],
    ]);

    $http->assertSent(function (HttpRequest $request): bool {
        return moofWiipDecodeDiagnosticsRequest($request)['payload']['values'] === [
            'token' => '[redacted]',
            'private_key' => '[redacted]',
            'profile' => [
                'name' => '[redacted]',
                'email' => '[redacted]',
                'note' => 'Authorization: Bearer [redacted] password=[redacted] private_key=[redacted] to [redacted-email]',
            ],
        ];
    });
});

it('captures safe plain object properties without exposing secret fields', function () {
    $http = moofWiipFakeAcceptedDiagnostics();
    $context = (object) [
        'label' => 'worker state',
        'access_token' => 'do-not-store-this',
    ];

    (new Recorder('http://127.0.0.1:3030/api/v1/diagnostics/php', 'php:tests'))->capture([$context]);

    $http->assertSent(function (HttpRequest $request): bool {
        return moofWiipDecodeDiagnosticsRequest($request)['payload']['values'][0] === [
            '__class' => 'stdClass',
            'properties' => [
                'label' => 'worker state',
                'access_token' => '[redacted]',
            ],
        ];
    });
});

it('sends only a compact truncation marker when the payload exceeds its byte limit', function () {
    $http = moofWiipFakeAcceptedDiagnostics();
    (new Recorder('http://127.0.0.1:3030/api/v1/diagnostics/php', 'php:tests'))->capture(
        array_fill(0, 40, str_repeat('x', 2048)),
    );

    $http->assertSent(function (HttpRequest $request): bool {
        $payload = moofWiipDecodeDiagnosticsRequest($request)['payload'];
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);

        return strlen($encoded) <= 8192
            && $payload['values']['truncated'] === true
            && $payload['values']['original_bytes'] > 8192;
    });
});

it('refuses non-loopback endpoints without making a network request', function () {
    $http = moofWiipFakeAcceptedDiagnostics();
    $eventId = (new Recorder('https://diagnostics.example.test/api/v1/diagnostics/php', 'php:tests'))->capture(['private']);

    expect($eventId)->toBeNull();
    $http->assertNothingSent();
});

it('returns null when the Forge intake does not durably accept the event', function () {
    $http = new HttpFactory;
    Http::swap($http);
    $http->fake(HttpFactory::response(['message' => 'unavailable'], 503));

    expect((new Recorder('http://127.0.0.1:3030/api/v1/diagnostics/php', 'php:tests'))->capture(['hello']))->toBeNull();
    $http->assertSentCount(1);
});

it('exposes the configured local recorder through the global moof helper', function () {
    $previousContainer = Container::getInstance();
    $application = new Application(sys_get_temp_dir());
    $application->instance('config', new Repository([
        'moof' => [
            'diagnostics' => [
                'enabled' => true,
                'endpoint' => 'http://127.0.0.1:3030/api/v1/diagnostics/php',
                'source' => 'php:helper-test',
            ],
        ],
    ]));
    $http = moofWiipFakeAcceptedDiagnostics();
    Container::setInstance($application);

    try {
        $eventId = moof('helper capture');
        expect($eventId)->toBeString();

        $http->assertSent(function (HttpRequest $request) use ($eventId): bool {
            $body = moofWiipDecodeDiagnosticsRequest($request);

            return $body['event_id'] === $eventId
                && $body['source'] === 'php:helper-test'
                && $body['payload']['values'] === ['helper capture']
                && $body['payload']['callsite']['file'] === 'MoofDiagnosticRecorderTest.php';
        });
    } finally {
        Container::setInstance($previousContainer);
    }
});

it('disables legacy Ray output when Moof diagnostics replace it', function () {
    $ray = ray();
    $ray->enable();

    try {
        (new RayIntegration)->disableLegacyClient(false);
        expect($ray->enabled())->toBeTrue();

        (new RayIntegration)->disableLegacyClient(true);
        expect($ray->disabled())->toBeTrue();
    } finally {
        $ray->enable();
    }
});
