<?php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'], // или ['GET','POST','OPTIONS'] и др.
    'allowed_origins' => ['https://okfront.okkio.ru'], // обязательно точное значение
    'allowed_headers' => ['*'],
    'supports_credentials' => false, // если нужны куки/авторизация
];