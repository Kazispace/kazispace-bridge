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

expect(SnapshotFreshness::toSqlUtc('2026-09-28T02:00:01Z') === '2026-09-28 02:00:01', 'z form becomes sql utc');
expect(SnapshotFreshness::isNewer('2026-09-28T02:00:00Z', '2026-09-28T03:00:00Z'), 'later snapshot is newer');
expect(! SnapshotFreshness::isNewer('2026-09-28T03:00:00Z', '2026-09-28T02:00:00Z'), 'older snapshot is not newer');
expect(! SnapshotFreshness::isNewer('2026-09-28T02:00:00Z', '2026-09-28T02:00:00Z'), 'equal snapshot is not newer');
expect(
    SnapshotFreshness::isNewer(new DateTimeImmutable('2026-09-28T02:00:00Z'), '2026-09-28T02:00:01Z'),
    'datetime storage can be compared'
);

$previous = date_default_timezone_get();
date_default_timezone_set('Pacific/Kiritimati');
try {
    expect(
        SnapshotFreshness::isNewer('2026-09-28 02:00:00', '2026-09-28T02:00:01Z'),
        'mysql wall clock is compared as utc'
    );
    expect(
        SnapshotFreshness::toSqlUtc('2026-09-28T02:00:01Z') === '2026-09-28 02:00:01',
        'sql utc does not follow the default timezone'
    );
    $shanghai = new DateTimeImmutable('2026-09-28 10:00:00', new DateTimeZone('Asia/Shanghai'));
    expect(
        SnapshotFreshness::isNewer($shanghai, '2026-09-28T02:00:01Z'),
        'non-utc datetime is converted before comparison'
    );
} finally {
    date_default_timezone_set($previous);
}

echo "ok\n";
