<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'], // или ['GET','POST','OPTIONS'] и др.
    'allowed_origins' => ['https://teftele.com', 'https://www.teftele.com'], // обязательно точное значение
    'allowed_headers' => ['*'],
    'supports_credentials' => true, // если нужны куки/авторизация
];
