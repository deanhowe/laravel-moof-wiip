<?php

namespace DeanHowe\Laravel\Moof\Diagnostics;

use BackedEnum;
use DateTimeInterface;
use PDO;
use Throwable;
use UnitEnum;

final class Recorder
{
    private const MAX_PAYLOAD_BYTES = 8192;

    private const MAX_STRING_BYTES = 2048;

    private const MAX_ARRAY_ITEMS = 40;

    private const MAX_TOTAL_NODES = 256;

    private const MAX_DEPTH = 5;

    public function __construct(
        private readonly string $databasePath,
        private readonly string $source,
        private readonly int $rateLimitPerMinute = 30,
    ) {}

    /**
     * Append one bounded debug event to Moof's existing event table.
     *
     * @param  array<mixed>  $values
     * @param  array{file?: string, line?: int|null}  $callsite
     */
    public function capture(array $values, array $callsite = []): ?string
    {
        if (! is_file($this->databasePath) || ! is_readable($this->databasePath) || ! is_writable($this->databasePath)) {
            return null;
        }

        try {
            $database = new PDO('sqlite:'.$this->databasePath, options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2,
            ]);
            $database->exec('PRAGMA busy_timeout = 1500');

            $schema = $database->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'moof_events'");
            $schema->execute();

            if ($schema->fetchColumn() === false || $this->rateLimitPerMinute < 1) {
                return null;
            }

            $now = time();
            $safeCallsite = $this->sanitizeCallsite($callsite);
            $remainingNodes = self::MAX_TOTAL_NODES;
            $payload = json_encode([
                'values' => $this->sanitize($values, 0, $remainingNodes),
                'callsite' => $safeCallsite,
            ], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);

            if (strlen($payload) > self::MAX_PAYLOAD_BYTES) {
                $payload = json_encode([
                    'values' => [
                        'truncated' => true,
                        'original_bytes' => strlen($payload),
                    ],
                    'callsite' => $safeCallsite,
                ], JSON_THROW_ON_ERROR);
            }

            $database->beginTransaction();

            $duplicate = $database->prepare(<<<'SQL'
                SELECT event_id FROM moof_events
                WHERE topic = 'php.debug' AND source = :source
                  AND payload_json = :payload AND recorded_at >= :since
                ORDER BY id DESC LIMIT 1
                SQL);
            $duplicate->execute([
                'source' => $this->source,
                'payload' => $payload,
                'since' => $now - 60,
            ]);
            $existingEventId = $duplicate->fetchColumn();

            if (is_string($existingEventId)) {
                $database->commit();

                return $existingEventId;
            }

            $rate = $database->prepare("SELECT COUNT(*) FROM moof_events WHERE topic = 'php.debug' AND source = :source AND recorded_at >= :since");
            $rate->execute([
                'source' => $this->source,
                'since' => $now - 60,
            ]);

            if ((int) $rate->fetchColumn() >= $this->rateLimitPerMinute) {
                $database->commit();

                return null;
            }

            $eventId = $this->uuid();

            $insert = $database->prepare(<<<'SQL'
                INSERT INTO moof_events (
                    event_id, topic, event_type, schema_version, source, subject_type,
                    subject_id, correlation_id, causation_id, payload_json, severity,
                    occurred_at, recorded_at
                ) VALUES (
                    :event_id, 'php.debug', 'captured', 1, :source, 'php_callsite',
                    NULL, NULL, NULL, :payload, 'debug', :occurred_at, :recorded_at
                )
                SQL);
            $insert->execute([
                'event_id' => $eventId,
                'source' => $this->source,
                'payload' => $payload,
                'occurred_at' => $now,
                'recorded_at' => $now,
            ]);
            $database->commit();

            return $eventId;
        } catch (Throwable) {
            if (isset($database) && $database->inTransaction()) {
                $database->rollBack();
            }

            return null;
        }
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }

    /**
     * @param  array<string, mixed>  $callsite
     * @return array{file: string, line: int|null}
     */
    private function sanitizeCallsite(array $callsite): array
    {
        $file = $callsite['file'] ?? 'unknown';
        $file = is_string($file) && $file !== '' ? $file : 'unknown';

        if (str_starts_with($file, DIRECTORY_SEPARATOR)) {
            $file = basename($file);
        }

        $line = filter_var($callsite['line'] ?? null, FILTER_VALIDATE_INT);

        return [
            'file' => substr($file, 0, 160),
            'line' => $line === false ? null : $line,
        ];
    }

    private function sanitize(mixed $value, int $depth, int &$remainingNodes): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            return '[maximum depth]';
        }

        if ($remainingNodes < 1) {
            return '[node budget reached]';
        }

        $remainingNodes--;

        if (is_string($value)) {
            return $this->sanitizeString($value);
        }

        if (is_int($value) || is_bool($value) || $value === null) {
            return $value;
        }

        if (is_float($value)) {
            return is_finite($value) ? $value : (string) $value;
        }

        if (is_resource($value)) {
            return '[resource: '.get_resource_type($value).']';
        }

        if (is_array($value)) {
            $safe = [];
            $count = 0;

            foreach ($value as $key => $child) {
                if ($count >= self::MAX_ARRAY_ITEMS || $remainingNodes < 1) {
                    $safe['_omitted_items'] = count($value) - $count;
                    break;
                }

                $safeKey = is_int($key) ? $key : substr((string) $key, 0, 100);
                $safe[$safeKey] = is_string($key) && $this->isSensitiveKey($key)
                    ? '[redacted]'
                    : $this->sanitize($child, $depth + 1, $remainingNodes);
                $count++;
            }

            return $safe;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Throwable) {
            return [
                '__class' => get_class($value),
                'message' => $this->sanitizeString($value->getMessage()),
            ];
        }

        if ($value instanceof BackedEnum) {
            return [
                '__class' => get_class($value),
                'value' => $this->sanitize($value->value, $depth + 1, $remainingNodes),
            ];
        }

        if ($value instanceof UnitEnum) {
            return [
                '__class' => get_class($value),
                'name' => $value->name,
            ];
        }

        if (is_object($value)) {
            $properties = get_object_vars($value);

            return $properties === []
                ? ['__class' => get_class($value)]
                : [
                    '__class' => get_class($value),
                    'properties' => $this->sanitize($properties, $depth + 1, $remainingNodes),
                ];
        }

        return '[unsupported value]';
    }

    private function sanitizeString(string $value): string
    {
        $value = preg_replace('#/Users/[^/\s]+#', '/Users/[redacted]', $value) ?? $value;
        $value = preg_replace('/\bAuthorization\s*:\s*Bearer\s+[^\s,;]+/i', 'Authorization: Bearer [redacted]', $value) ?? $value;
        $value = preg_replace('/\bBearer\s+[A-Za-z0-9._~+\/=\-]+/i', 'Bearer [redacted]', $value) ?? $value;
        $value = preg_replace('/\b(password|passwd|secret|private[_-]?key|access[_-]?key|token|api[_-]?key|credential|client[_-]?secret|cookie)\s*[:=]\s*(?:"[^"]*"|\'[^\']*\'|[^\s,;]+)/i', '$1=[redacted]', $value) ?? $value;
        $value = preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', '[redacted-email]', $value) ?? $value;

        return substr($value, 0, self::MAX_STRING_BYTES);
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match('/password|passwd|secret|private[_-]?key|access[_-]?key|token|credential|authorization|cookie|session|email|phone|mobile|address|card[_-]?number|api[_-]?key|client[_-]?secret|ssn|social[_-]?security|tax[_-]?id|bank[_-]?account|(?:^|_)(?:name|first_name|last_name|full_name|display_name|user_name)(?:$|_)/i', $key) === 1;
    }
}
