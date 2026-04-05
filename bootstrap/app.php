<?php

use App\Http\Middleware\CorsMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: null,
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api'
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(CorsMiddleware::class);
        $middleware->alias([
            'auth.jwt' => \App\Http\Middleware\JwtAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Можно оставить пустым
    })
    ->create();
// use App\Http\Middleware\CorsMiddleware;
// use Illuminate\Foundation\Application;
// use Illuminate\Foundation\Configuration\Exceptions;
// use Illuminate\Foundation\Configuration\Middleware;

// return Application::configure(basePath: dirname(__DIR__))
//     ->withRouting(
//         web: null,
//         api: __DIR__.'/../routes/api.php',
//         commands: __DIR__.'/../routes/console.php',
//         health: '/up',
//         apiPrefix: 'api'
//     )
//     ->withMiddleware(function (Middleware $middleware) {
//         // Добавляем CORS middleware в начало
//         $middleware->prepend(CorsMiddleware::class);

//         $middleware->alias([
//             'auth.jwt' => \App\Http\Middleware\JwtAuth::class,
//         ]);
//     })->create();
    // ->withMiddleware(function (Middleware $middleware) {
    //     // Регистрируем JWT-мидлвар
    //     // $middleware->alias([
    //     //     'auth.jwt' => \App\Http\Middleware\JwtAuth::class,
    //     // ]);
    //     // $middleware->append(\App\Http\Middleware\ForceCorsHeaders::class); // Добавляем ПЕРВЫМ
    
    //     // $middleware->alias([
    //     //     'auth.jwt' => \App\Http\Middleware\JwtAuth::class,
    //     // ]);
    // })
    // ->withExceptions(function (Exceptions $exceptions) {
    //     // Кастомная обработка ошибок для API

    //     $exceptions->render(function (Throwable $e) {
    //         return response()->json([
    //             'error' => $e->getMessage()
    //         ], $e->getCode() ?: 500);
    //     });
    // })
    // ->create();

// return Application::configure(basePath: dirname(__DIR__))
//     ->withRouting(
//         web: null, // Отключаем веб-роуты полностью
//         commands: __DIR__.'/../routes/console.php',
//         health: '/up',
//         api: __DIR__.'/../routes/api.php', // Явно включаем API
//         apiPrefix: 'api' // Префикс для API
//     )
//     ->withMiddleware(function (Middleware $middleware) {
//         // Добавляем CORS middleware глобально
//         $middleware->prepend(CorsMiddleware::class);
//     })
//     ->withMiddleware(function (Middleware $middleware) {
//         // Регистрируем JWT-мидлвар
//         $middleware->alias([
//             'auth.jwt' => \App\Http\Middleware\JwtAuth::class,
//         ]);
//     })
//     ->withExceptions(function (Exceptions $exceptions) {
//         // Кастомная обработка ошибок для API
        
//         $exceptions->render(function (Throwable $e) {
//             return response()->json([
//                 'error' => $e->getMessage()
//             ], $e->getCode() ?: 500);
//         });
//     })
//     ->create();

    // return Application::configure(basePath: dirname(__DIR__))
    // ->withRouting(
    //     web: false, // Отключаем веб-роуты
    //     api: __DIR__.'/../routes/api.php', // Путь к API-роутам
    //     apiPrefix: 'api', // Префикс для API (опционально)
    //     commands: __DIR__.'/../routes/console.php',
    //     health: '/up',
    // )
    // ->withMiddleware(function (Middleware $middleware) {
    //     $middleware->alias([
    //         'auth.jwt' => \App\Http\Middleware\JwtAuth::class,
    //     ]);
    // })

// use Illuminate\Foundation\Application;
// use Illuminate\Foundation\Configuration\Exceptions;
// use Illuminate\Foundation\Configuration\Middleware;

// return Application::configure(basePath: dirname(__DIR__))
//     ->withRouting(
//         // web: false, // Отключаем веб-роуты полностью
//         // web: __DIR__.'/../routes/web.php',
//         commands: __DIR__.'/../routes/console.php',
//         health: '/up',
//         api: __DIR__.'/../routes/api.php',
//         apiPrefix: 'api' // Префикс для API
//     )
//     ->withMiddleware(function (Middleware $middleware): void {
//         //
//     })
//     ->withExceptions(function (Exceptions $exceptions): void {
//         //
//     })->create();
