<?php

namespace Kazispace\Bridge\Support;

final class Signature
{
    public static function sign(string $secret, string $timestamp, string $rawBody): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
    }

    public static function matches(string $secret, string $timestamp, string $rawBody, string $provided): bool
    {
        $expected = self::sign($secret, $timestamp, $rawBody);

        return hash_equals($expected, $provided);
    }
}
