<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;

class DemoAuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', env('DEMO_USER_EMAIL', 'demo@teftele.com'))
            ->where('status', User::STATUS_ACTIVE)
            ->first();

        if (!$user) {
            return response()->json([
                'error' => 'Demo unavailable. Run php artisan db:seed --class=DemoSeeder'
            ], 503);
        }

        // Короткоживущий токен — 2 часа, с флагом is_demo
        $accessToken = JWT::encode([
            'sub'     => $user->id,
            'is_demo' => true,
            'iat'     => time(),
            'exp'     => time() + 7200, // 2 часа
        ], config('jwt.secret'), config('jwt.algo'));

        $response = response()->json([
            'user' => [
                'id'      => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'avatar'  => null,
                'is_demo' => true,
            ],
            'message' => 'Demo login successful',
        ]);

        // Без refresh token — демо всегда пере-логинится
        $response->cookie(
            'access_token',
            $accessToken,
            120, // 2 часа в минутах
            '/',
            null,
            false,
            true,
            false,
            config('session.same_site', 'lax')
        );

        return $response;
    }
}
