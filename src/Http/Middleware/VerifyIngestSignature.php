<?php

namespace Kazispace\Bridge\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kazispace\Bridge\Support\Signature;
use Symfony\Component\HttpFoundation\Response;

class VerifyIngestSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('kazispace.hmac_secret');
        $timestamp = (string) $request->header('X-Kazispace-Timestamp', '');
        $provided = (string) $request->header('X-Kazispace-Signature', '');

        if ($secret === '' || $timestamp === '' || $provided === '') {
            return response()->json(['error' => 'missing signature'], 401);
        }

        if (! ctype_digit($timestamp)) {
            return response()->json(['error' => 'invalid timestamp'], 401);
        }

        $skew = abs(time() - (int) $timestamp);
        if ($skew > (int) config('kazispace.timestamp_skew_seconds', 300)) {
            return response()->json(['error' => 'timestamp expired'], 401);
        }

        if (! Signature::matches($secret, $timestamp, $request->getContent(), $provided)) {
            return response()->json(['error' => 'bad signature'], 401);
        }

        return $next($request);
    }
}
