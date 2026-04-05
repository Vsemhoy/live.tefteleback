<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceCorsHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Получаем ответ ДО выполнения остальных middleware
        $response = $next($request);
        
        // Полностью перезаписываем заголовки
        $response->headers->replace([
            'Access-Control-Allow-Origin' => $request->header('Origin'),
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            'Access-Control-Allow-Credentials' => 'true',
        ]);

        return $response;
    }
}