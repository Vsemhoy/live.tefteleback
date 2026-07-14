<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

class PreventDemoWrites
{
    private array $SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, Closure $next)
    {
        // Только для мутирующих запросов
        if (in_array($request->method(), $this->SAFE_METHODS)) {
            return $next($request);
        }

        // Проверяем is_demo в JWT
        try {
            $token = $request->cookie('access_token');
            if ($token) {
                $decoded = JWT::decode($token, new Key(config('jwt.secret'), config('jwt.algo')));
                if (!empty($decoded->is_demo)) {
                    return response()->json([
                        'error'   => 'demo_readonly',
                        'message' => 'Демо-режим только для чтения. Зарегистрируйтесь чтобы создавать записи.',
                    ], 403);
                }
            }
        } catch (\Exception $e) {
            // Невалидный токен — пропускаем, auth middleware разберётся
        }

        return $next($request);
    }
}
