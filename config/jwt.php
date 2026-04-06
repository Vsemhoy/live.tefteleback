<?php

return [
    'secret' => env('JWT_SECRET', 'your_fallback_secret'),
    'algo' => 'HS256',
    'ttl' => 3600, // access token TTL (seconds)
    'refresh_ttl' => 86400, // refresh token TTL (seconds) - 24 hours
];
