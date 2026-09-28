<?php

return [
    'hmac_secret' => env('KAZISPACE_BRIDGE_HMAC_SECRET', ''),
    'timestamp_skew_seconds' => (int) env('KAZISPACE_BRIDGE_SKEW_SECONDS', 300),
];
