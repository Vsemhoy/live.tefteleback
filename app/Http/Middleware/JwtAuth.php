<?php

namespace App\Http\Middleware;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtService;
use Closure;

class JwtAuth
{
    public function handle($request, Closure $next)
    {
        if (!$token = $request->bearerToken()) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        $jwt = app(JwtService::class);
        if ($jwt->isTokenInvalid($token)) {
            return response()->json(['error' => 'Token revoked'], 401);
        }

        if (!$jwt->validateToken($token)) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // ✅ Один раз вызываем $next(), модифицируем ответ
        $response = $next($request);
        // $response->headers->remove('Access-Control-Allow-Origin');

        return $response; // ✅ Возвращаем изменённый ответ
    }

    // public function handle($request, Closure $next)
    // {
    //     if (!$token = $request->bearerToken()) {
    //         return response()->json(['error' => 'Token not provided'], 401);
    //     }

    //     $jwt = app(JwtService::class);
    //     if ($jwt->isTokenInvalid($token)) {
    //             return response()->json(['error' => 'Token revoked'], 401);
    //         }

    //     if (!$jwt->validateToken($token)) {
    //         return response()->json(['error' => 'Invalid token'], 401);
    //     }
        
    //     $response = $next($request);
    //     $response->headers->remove('Access-Control-Allow-Origin');
    //     return $next($request);
    // }
}