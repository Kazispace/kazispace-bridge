<?php

require __DIR__.'/../src/Support/CurveWindow.php';
require __DIR__.'/../src/Support/InvalidCurveWindow.php';

use Kazispace\Bridge\Support\CurveWindow;
use Kazispace\Bridge\Support\InvalidCurveWindow;

function expect_curve(bool $condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, $message.PHP_EOL);
        exit(1);
    }
}

$window = CurveWindow::resolve(null, null, '2026-09-28T03:00:00Z');
expect_curve($window->from === '2026-09-27T03:00:00Z', 'missing window starts 24 hours earlier');
expect_curve($window->to === '2026-09-28T03:00:00Z', 'missing window ends at now');
expect_curve(CurveWindow::ROW_LIMIT === null, 'a 24 hour window is not capped at 500 rows');

$explicit = CurveWindow::resolve('2026-09-28T00:00:00Z', '2026-09-28T06:00:00Z', '2026-09-28T03:00:00Z');
expect_curve($explicit->from === '2026-09-28T00:00:00Z' && $explicit->to === '2026-09-28T06:00:00Z', 'explicit window is kept');

$failed = false;
try {
    CurveWindow::resolve('2026-09-28T06:00:00Z', '2026-09-28T00:00:00Z', '2026-09-28T03:00:00Z');
} catch (InvalidCurveWindow) {
    $failed = true;
}
expect_curve($failed, 'reversed window is rejected');

$failed = false;
try {
    CurveWindow::resolve('2026-09-28 00:00:00', '2026-09-28T06:00:00Z', '2026-09-28T03:00:00Z');
} catch (InvalidCurveWindow) {
    $failed = true;
}
expect_curve($failed, 'non-utc window is rejected');

expect_curve(! in_array('frames', CurveWindow::COLUMNS, true), 'curve columns do not include raw frames');
expect_curve(in_array('soc', CurveWindow::COLUMNS, true), 'curve columns include decoded soc');

echo "ok\n";
