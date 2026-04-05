<?php

return [
    'secret' => env('JWT_SECRET', 'your_fallback_secret'),
    'algo' => 'HS256',
    'ttl' => 3600,
];