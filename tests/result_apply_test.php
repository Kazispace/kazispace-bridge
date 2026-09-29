<?php

require __DIR__.'/../src/Support/SnapshotFreshness.php';
require __DIR__.'/../src/Support/ResultApply.php';

use Kazispace\Bridge\Support\ResultApply;

function expect_apply(bool $condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, $message.PHP_EOL);
        exit(1);
    }
}

expect_apply(ResultApply::accepts(null, '2026-09-28T02:00:00Z'), 'first snapshot is applied');
expect_apply(
    ResultApply::accepts('2026-09-28 02:00:00', '2026-09-28T02:00:01Z'),
    'newer utc instant is applied over a mysql timestamp'
);
expect_apply(
    ! ResultApply::accepts('2026-09-28 03:00:00', '2026-09-28T02:00:00Z'),
    'older report does not update the snapshot, curve, or alerts'
);

$stale = ResultApply::response(false);
expect_apply($stale['stored'] === true, 'stale report still returns stored');
expect_apply($stale['snapshot_updated'] === false, 'stale report marks the snapshot unchanged');

$fresh = ResultApply::response(true);
expect_apply($fresh['stored'] === true && $fresh['snapshot_updated'] === true, 'applied report marks the snapshot updated');

echo "ok\n";
