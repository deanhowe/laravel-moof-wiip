<?php

use DeanHowe\Laravel\Moof\Diagnostics\Recorder;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;

it('appends a structured PHP diagnostic to the existing Moof event log', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'moof-events-');
    $database = new PDO('sqlite:'.$databasePath);
    $database->exec(<<<'SQL'
        CREATE TABLE moof_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL UNIQUE,
            topic TEXT NOT NULL,
            event_type TEXT NOT NULL,
            schema_version INTEGER NOT NULL DEFAULT 1,
            source TEXT NOT NULL,
            subject_type TEXT,
            subject_id TEXT,
            correlation_id TEXT,
            causation_id TEXT,
            payload_json TEXT NOT NULL,
            severity TEXT NOT NULL DEFAULT 'info',
            occurred_at INTEGER NOT NULL,
            recorded_at INTEGER NOT NULL
        )
        SQL);

    try {
        $eventId = (new Recorder($databasePath, 'php:tests'))->capture(
            ['request started', ['attempt' => 2]],
            ['file' => 'app/Example.php', 'line' => 42],
        );

        $event = $database->query('SELECT * FROM moof_events')->fetch(PDO::FETCH_ASSOC);

        expect($eventId)->toBeString()
            ->and($event['event_id'])->toBe($eventId)
            ->and($event['topic'])->toBe('php.debug')
            ->and($event['event_type'])->toBe('captured')
            ->and($event['source'])->toBe('php:tests')
            ->and($event['severity'])->toBe('debug')
            ->and(json_decode($event['payload_json'], true, flags: JSON_THROW_ON_ERROR))->toBe([
                'values' => ['request started', ['attempt' => 2]],
                'callsite' => ['file' => 'app/Example.php', 'line' => 42],
            ]);
    } finally {
        unset($database);
        unlink($databasePath);
    }
});

it('redacts private fields and credential-shaped text before storing diagnostics', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'moof-events-');
    $database = new PDO('sqlite:'.$databasePath);
    $database->exec(<<<'SQL'
        CREATE TABLE moof_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL UNIQUE,
            topic TEXT NOT NULL,
            event_type TEXT NOT NULL,
            schema_version INTEGER NOT NULL DEFAULT 1,
            source TEXT NOT NULL,
            subject_type TEXT,
            subject_id TEXT,
            correlation_id TEXT,
            causation_id TEXT,
            payload_json TEXT NOT NULL,
            severity TEXT NOT NULL DEFAULT 'info',
            occurred_at INTEGER NOT NULL,
            recorded_at INTEGER NOT NULL
        )
        SQL);

    try {
        (new Recorder($databasePath, 'php:tests'))->capture([
            'token' => 'secret-token-value',
            'private_key' => 'private-key-value',
            'profile' => [
                'name' => 'Test Person',
                'email' => 'private@example.test',
                'note' => 'Authorization: Bearer abc.def password=hunter2 private_key=abc123 to private@example.test',
            ],
        ]);

        $payload = json_decode(
            $database->query('SELECT payload_json FROM moof_events')->fetchColumn(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($payload['values'])->toBe([
            'token' => '[redacted]',
            'private_key' => '[redacted]',
            'profile' => [
                'name' => '[redacted]',
                'email' => '[redacted]',
                'note' => 'Authorization: Bearer [redacted] password=[redacted] private_key=[redacted] to [redacted-email]',
            ],
        ]);
    } finally {
        unset($database);
        unlink($databasePath);
    }
});

it('captures plain object properties without exposing secret fields', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'moof-events-');
    $database = new PDO('sqlite:'.$databasePath);
    $database->exec(<<<'SQL'
        CREATE TABLE moof_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL UNIQUE,
            topic TEXT NOT NULL,
            event_type TEXT NOT NULL,
            schema_version INTEGER NOT NULL DEFAULT 1,
            source TEXT NOT NULL,
            subject_type TEXT,
            subject_id TEXT,
            correlation_id TEXT,
            causation_id TEXT,
            payload_json TEXT NOT NULL,
            severity TEXT NOT NULL DEFAULT 'info',
            occurred_at INTEGER NOT NULL,
            recorded_at INTEGER NOT NULL
        )
        SQL);

    $context = (object) [
        'label' => 'worker state',
        'access_token' => 'do-not-store-this',
    ];

    try {
        (new Recorder($databasePath, 'php:tests'))->capture([$context]);

        $payload = json_decode(
            $database->query('SELECT payload_json FROM moof_events')->fetchColumn(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($payload['values'][0])->toBe([
            '__class' => 'stdClass',
            'properties' => [
                'label' => 'worker state',
                'access_token' => '[redacted]',
            ],
        ]);
    } finally {
        unset($database);
        unlink($databasePath);
    }
});

it('does not create the Brain database schema when the event table is absent', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'moof-empty-');

    try {
        expect((new Recorder($databasePath, 'php:tests'))->capture(['hello']))->toBeNull()
            ->and(filesize($databasePath))->toBe(0);
    } finally {
        unlink($databasePath);
    }
});

it('limits diagnostics at the configured per source rate', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'moof-events-');
    $database = new PDO('sqlite:'.$databasePath);
    $database->exec(<<<'SQL'
        CREATE TABLE moof_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL UNIQUE,
            topic TEXT NOT NULL,
            event_type TEXT NOT NULL,
            schema_version INTEGER NOT NULL DEFAULT 1,
            source TEXT NOT NULL,
            subject_type TEXT,
            subject_id TEXT,
            correlation_id TEXT,
            causation_id TEXT,
            payload_json TEXT NOT NULL,
            severity TEXT NOT NULL DEFAULT 'info',
            occurred_at INTEGER NOT NULL,
            recorded_at INTEGER NOT NULL
        )
        SQL);

    try {
        $recorder = new Recorder($databasePath, 'php:tests', 1);
        $firstEventId = $recorder->capture(['first']);
        $secondEventId = $recorder->capture(['second']);
        $storedEvents = (int) $database->query("SELECT COUNT(*) FROM moof_events WHERE topic = 'php.debug'")->fetchColumn();

        expect($firstEventId)->toBeString()
            ->and($secondEventId)->toBeNull()
            ->and($storedEvents)->toBe(1);
    } finally {
        unset($database);
        unlink($databasePath);
    }
});

it('stores only a compact truncation marker when the payload exceeds its byte limit', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'moof-events-');
    $database = new PDO('sqlite:'.$databasePath);
    $database->exec(<<<'SQL'
        CREATE TABLE moof_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL UNIQUE,
            topic TEXT NOT NULL,
            event_type TEXT NOT NULL,
            schema_version INTEGER NOT NULL DEFAULT 1,
            source TEXT NOT NULL,
            subject_type TEXT,
            subject_id TEXT,
            correlation_id TEXT,
            causation_id TEXT,
            payload_json TEXT NOT NULL,
            severity TEXT NOT NULL DEFAULT 'info',
            occurred_at INTEGER NOT NULL,
            recorded_at INTEGER NOT NULL
        )
        SQL);

    try {
        (new Recorder($databasePath, 'php:tests'))->capture(array_fill(0, 40, str_repeat('x', 2048)));

        $payloadJson = $database->query('SELECT payload_json FROM moof_events')->fetchColumn();
        $payload = json_decode($payloadJson, true, flags: JSON_THROW_ON_ERROR);

        expect(strlen($payloadJson))->toBeLessThanOrEqual(8192)
            ->and($payload['values']['truncated'])->toBeTrue()
            ->and($payload['values']['original_bytes'])->toBeGreaterThan(8192);
    } finally {
        unset($database);
        unlink($databasePath);
    }
});

it('collapses identical diagnostic captures within the same minute', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'moof-events-');
    $database = new PDO('sqlite:'.$databasePath);
    $database->exec(<<<'SQL'
        CREATE TABLE moof_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL UNIQUE,
            topic TEXT NOT NULL,
            event_type TEXT NOT NULL,
            schema_version INTEGER NOT NULL DEFAULT 1,
            source TEXT NOT NULL,
            subject_type TEXT,
            subject_id TEXT,
            correlation_id TEXT,
            causation_id TEXT,
            payload_json TEXT NOT NULL,
            severity TEXT NOT NULL DEFAULT 'info',
            occurred_at INTEGER NOT NULL,
            recorded_at INTEGER NOT NULL
        )
        SQL);

    try {
        $recorder = new Recorder($databasePath, 'php:tests');
        $firstEventId = $recorder->capture(['same diagnostic'], ['file' => 'Example.php', 'line' => 9]);
        $secondEventId = $recorder->capture(['same diagnostic'], ['file' => 'Example.php', 'line' => 9]);
        $storedEvents = (int) $database->query("SELECT COUNT(*) FROM moof_events WHERE topic = 'php.debug'")->fetchColumn();

        expect($firstEventId)->toBeString()
            ->and($secondEventId)->toBe($firstEventId)
            ->and($storedEvents)->toBe(1);
    } finally {
        unset($database);
        unlink($databasePath);
    }
});

it('exposes the configured recorder through the global moof helper', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'moof-events-');
    $database = new PDO('sqlite:'.$databasePath);
    $database->exec(<<<'SQL'
        CREATE TABLE moof_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL UNIQUE,
            topic TEXT NOT NULL,
            event_type TEXT NOT NULL,
            schema_version INTEGER NOT NULL DEFAULT 1,
            source TEXT NOT NULL,
            subject_type TEXT,
            subject_id TEXT,
            correlation_id TEXT,
            causation_id TEXT,
            payload_json TEXT NOT NULL,
            severity TEXT NOT NULL DEFAULT 'info',
            occurred_at INTEGER NOT NULL,
            recorded_at INTEGER NOT NULL
        )
        SQL);

    $previousContainer = Container::getInstance();
    $application = new Application(sys_get_temp_dir());
    $application->instance('config', new Repository([
        'moof' => [
            'diagnostics' => [
                'enabled' => true,
                'database_path' => $databasePath,
                'source' => 'php:helper-test',
                'rate_limit_per_minute' => 30,
            ],
        ],
    ]));
    Container::setInstance($application);

    try {
        $eventId = moof('helper capture');
        $event = $database->query('SELECT event_id, source, payload_json FROM moof_events')->fetch(PDO::FETCH_ASSOC);
        $payload = json_decode($event['payload_json'], true, flags: JSON_THROW_ON_ERROR);

        expect($eventId)->toBe($event['event_id'])
            ->and($event['source'])->toBe('php:helper-test')
            ->and($payload['values'])->toBe(['helper capture'])
            ->and($payload['callsite']['file'])->toBe('MoofDiagnosticRecorderTest.php');
    } finally {
        Container::setInstance($previousContainer);
        unset($database);
        unlink($databasePath);
    }
});
