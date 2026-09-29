<?php

require __DIR__.'/../src/Support/SnapshotFreshness.php';
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
expect_curve($window->sqlFrom === '2026-09-27 03:00:00', 'default window binds sql utc');
expect_curve($window->sqlTo === '2026-09-28 03:00:00', 'default window end binds sql utc');

$explicit = CurveWindow::resolve('2026-09-28T00:00:00Z', '2026-09-28T06:00:00Z', '2026-09-28T03:00:00Z');
expect_curve($explicit->sqlFrom === '2026-09-28 00:00:00' && $explicit->sqlTo === '2026-09-28 06:00:00', 'explicit window binds sql utc');

$day = CurveWindow::resolve('2026-09-27T03:00:00Z', '2026-09-28T03:00:00Z', '2026-09-28T03:00:00Z');
expect_curve($day->sqlFrom === '2026-09-27 03:00:00', 'a 24 hour window is accepted');

$failed = false;
try {
    CurveWindow::resolve('2026-09-27T03:00:00Z', '2026-09-28T03:00:01Z', '2026-09-28T03:00:00Z');
} catch (InvalidCurveWindow) {
    $failed = true;
}
expect_curve($failed, 'a window longer than 24 hours is rejected');

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

$controller = file_get_contents(__DIR__.'/../src/Http/Controllers/ResultController.php');
expect_curve(is_string($controller) && str_contains($controller, '$window->sqlFrom'), 'samples query binds sql utc');
expect_curve(is_string($controller) && str_contains($controller, '$window->sqlTo'), 'samples query binds sql utc end');
expect_curve(is_string($controller) && ! str_contains($controller, 'limit(500)'), 'samples query is not capped at 500 rows');

expect_curve(! in_array('frames', CurveWindow::COLUMNS, true), 'curve columns do not include raw frames');
expect_curve(in_array('soc', CurveWindow::COLUMNS, true), 'curve columns include decoded soc');

echo "ok\n";
