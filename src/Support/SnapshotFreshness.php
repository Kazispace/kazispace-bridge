<?php

namespace Kazispace\Bridge\Support;

final class SnapshotFreshness
{
    public static function isUtc(mixed $value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value) === 1;
    }

    public static function toSqlUtc(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value)
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s');
        }
        if (! is_string($value) || $value === '') {
            return null;
        }

        $utc = new \DateTimeZone('UTC');
        if (self::isUtc($value)) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, $utc);
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) === 1) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $utc);
        } else {
            return null;
        }
        if ($parsed === false) {
            return null;
        }
        $errors = \DateTimeImmutable::getLastErrors();
        if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
            return null;
        }

        return $parsed->format('Y-m-d H:i:s');
    }

    public static function isNewer(mixed $stored, string $incoming): bool
    {
        $storedAt = self::toSqlUtc($stored);
        $incomingAt = self::toSqlUtc($incoming);
        if ($storedAt === null || $incomingAt === null) {
            return false;
        }

        return $incomingAt > $storedAt;
    }
}
