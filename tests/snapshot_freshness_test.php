<?php

require __DIR__.'/../src/Support/SnapshotFreshness.php';

use Kazispace\Bridge\Support\SnapshotFreshness;

function expect(bool $condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, $message.PHP_EOL);
        exit(1);
    }
}

expect(SnapshotFreshness::isUtc('2026-09-28T02:00:00Z'), 'utc timestamp accepted');
expect(! SnapshotFreshness::isUtc('2026-09-28 02:00:00'), 'space-separated timestamp rejected');
expect(! SnapshotFreshness::isUtc(null), 'null timestamp rejected');

expect(SnapshotFreshness::isNewer('2026-09-28T02:00:00Z', '2026-09-28T03:00:00Z'), 'later snapshot is newer');
expect(! SnapshotFreshness::isNewer('2026-09-28T03:00:00Z', '2026-09-28T02:00:00Z'), 'older snapshot is not newer');
expect(! SnapshotFreshness::isNewer('2026-09-28T02:00:00Z', '2026-09-28T02:00:00Z'), 'equal snapshot is not newer');
expect(
    SnapshotFreshness::isNewer(new DateTimeImmutable('2026-09-28T02:00:00Z'), '2026-09-28T02:00:01Z'),
    'datetime storage can be compared'
);

echo "ok\n";
