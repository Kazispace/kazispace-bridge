<?php

namespace Kazispace\Bridge\Support;

final class CurveWindow
{
    public const COLUMNS = ['observed_at', 'soc', 'soh', 'pack_voltage', 'temp_max', 'speed'];

    public const ROW_LIMIT = null;

    public function __construct(public readonly string $from, public readonly string $to) {}

    public static function resolve(mixed $from, mixed $to, string $now): self
    {
        $hasFrom = is_string($from) && $from !== '';
        $hasTo = is_string($to) && $to !== '';
        if (! $hasFrom && ! $hasTo) {
            if (! self::isUtc($now)) {
                throw new InvalidCurveWindow('from and to must be YYYY-MM-DDTHH:MM:SSZ');
            }
            $end = new \DateTimeImmutable($now);
            $start = $end->sub(new \DateInterval('PT24H'));

            return new self($start->format('Y-m-d\TH:i:s\Z'), $end->format('Y-m-d\TH:i:s\Z'));
        }

        if (! $hasFrom || ! $hasTo || ! self::isUtc($from) || ! self::isUtc($to)) {
            throw new InvalidCurveWindow('from and to must be YYYY-MM-DDTHH:MM:SSZ');
        }
        if ($from > $to) {
            throw new InvalidCurveWindow('from must not be later than to');
        }

        return new self($from, $to);
    }

    private static function isUtc(mixed $value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value) === 1;
    }
}
