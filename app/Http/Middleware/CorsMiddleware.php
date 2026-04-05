<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// class CorsMiddleware
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
//      */
//     public function handle($request, Closure $next)
//     {
//         $response = $next($request);

//         $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:3002');
//         $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE');
//         $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
//         return $response;
//     }
// }



// class CorsMiddleware
// {
//     public function handle(Request $request, Closure $next)
//     {
//         $response = $next($request);

//         // Разрешённые источники
//         $allowedOrigins = [
//             'http://localhost:3002',
//             // добавь другие домены
//         ];

//         $origin = $request->header('Origin');

//         if (in_array($origin, $allowedOrigins)) {
//             $response->header('Access-Control-Allow-Origin', $origin);
//             $response->header('Access-Control-Allow-Credentials', 'true');
//         }

//         $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
//         $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-Token');

//         if ($request->isMethod('options')) {
//             return response('', 200)->withHeaders($response->headers->all());
//         }

//         return $response;
//     }
// }

// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;

class CorsMiddleware
{

        // if ($request->isMethod('options')) {
        //     return response('', 204);
        // }
//         if ($request->isMethod('options')) {
//     return response('', 204)->withHeaders([
//         'Access-Control-Allow-Origin' => $origin,
//         'Access-Control-Allow-Credentials' => 'true',
//         'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
//         'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-XSRF-Token'
//     ]);
// }
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $allowedOrigins = explode(',', env('ALLOWED_ORIGINS', ''));
        // header_remove('Access-Control-Allow-Origin');
        // Проверяем Origin из заголовков
        $origin = $request->header('Origin');

        
        if (in_array($origin, $allowedOrigins)) {
            if (str_contains($origin, 'localhost')){
                $response->headers->remove('Access-Control-Allow-Origin');
                $response->headers->remove('Access-Control-Allow-Methods');
                $response->headers->remove('Access-Control-Allow-Headers');

                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }
            // вот эту срань включить, если нет хедера на проде
            // $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else {
            // return response('', 404)->withHeaders($response->headers->all());
            if ($_SERVER['HTTP_HOST'] != 'okkioserv'){
                // return response('Origin not allowed - ' . $_SERVER['HTTP_HOST'] , 403);
            }
        }

        if (str_contains($origin, 'localhost')){
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-Token');
        }
        // Обработка preflight-запросов
        if ($request->isMethod('options')) {
            return response('', 200)->withHeaders($response->headers->all());
        }

        return $response;
    }
}

//  public function handle($request, Closure $next)
//     {
//         // 1. Получаем текущий Origin из запроса
//         $origin = $request->header('Origin');
        
//         // 2. Список разрешенных доменов
//         $allowedOrigins = [
//             'http://localhost:3002',
//             'http://okkioserv.test'
//         ];

//         // 3. Выполняем запрос
//         $response = $next($request);

//         // 4. Если Origin разрешен - устанавливаем заголовки
//         if (in_array($origin, $allowedOrigins)) {
//             // Удаляем все существующие CORS-заголовки
//             $response->headers->remove('Access-Control-Allow-Origin');
//             $response->headers->remove('Access-Control-Allow-Methods');
//             $response->headers->remove('Access-Control-Allow-Headers');

//             // Устанавливаем наши корректные заголовки
//             $response->headers->set('Access-Control-Allow-Origin', $origin);
//             $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
//             $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
//             $response->headers->set('Access-Control-Allow-Credentials', 'true');
//         }

//         return $response;
//     }
// }