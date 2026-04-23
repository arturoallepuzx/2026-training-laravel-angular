<?php

return [
    'jwt_secret' => env('AUTH_JWT_SECRET', ''),
    'access_ttl_seconds' => (int) env('AUTH_ACCESS_TTL_SECONDS', 900),
    'refresh_ttl_seconds' => (int) env('AUTH_REFRESH_TTL_SECONDS', 2_592_000),
];
