<?php

return [
    'secret' => env('JWT_SECRET', 'your_fallback_secret'),
    'algo' => 'HS256',
    'ttl' => 43200, // access token TTL (seconds) - 12 hours
    'refresh_ttl' => 2592000, // refresh token TTL (seconds) - 30 days
];
