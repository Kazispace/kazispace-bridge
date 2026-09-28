<?php

namespace Kazispace\Bridge\Support;

final class SnapshotFreshness
{
    public static function isUtc(mixed $value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value) === 1;
    }

    public static function isNewer(mixed $stored, string $incoming): bool
    {
        if ($stored instanceof \DateTimeInterface) {
            $stored = $stored->format('Y-m-d\TH:i:s\Z');
        }
        if (! is_string($stored) || $stored === '') {
            return false;
        }

        $storedAt = strtotime($stored);
        $incomingAt = strtotime($incoming);
        if ($storedAt === false || $incomingAt === false) {
            return false;
        }

        return $incomingAt > $storedAt;
    }
}
