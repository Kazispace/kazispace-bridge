<?php

namespace Kazispace\Bridge\Support;

final class ResultApply
{
    public static function accepts(mixed $storedObservedAt, string $incomingObservedAt): bool
    {
        if ($storedObservedAt === null) {
            return true;
        }

        return SnapshotFreshness::isNewer($storedObservedAt, $incomingObservedAt);
    }

    /**
     * @return array{stored: true, snapshot_updated: bool}
     */
    public static function response(bool $snapshotUpdated): array
    {
        return [
            'stored' => true,
            'snapshot_updated' => $snapshotUpdated,
        ];
    }
}
