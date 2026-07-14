<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LedLayer;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoAuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::firstOrCreate(
            ['email' => env('DEMO_USER_EMAIL', 'demo@teftele.com')],
            [
                'name' => env('DEMO_USER_NAME', 'Demo User'),
                'password' => Hash::make(Str::random(32)),
                'status' => User::STATUS_ACTIVE,
                'is_demo' => true,
                'demo_seeded_at' => now(),
            ]
        );

        if (! $user->is_demo || $user->demo_seeded_at === null) {
            $user->forceFill([
                'is_demo' => true,
                'status' => User::STATUS_ACTIVE,
                'demo_seeded_at' => $user->demo_seeded_at ?? now(),
            ])->save();
        }

        LedLayer::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'base'],
            ['id' => (string) Str::ulid(), 'name' => 'Base']
        );

        $accessToken = JWT::encode([
            'sub' => $user->id,
            'is_demo' => true,
            'iat' => time(),
            'exp' => time() + ((int) env('DEMO_SESSION_TTL_SECONDS', 7200)),
        ], config('jwt.secret'), config('jwt.algo'));

        $response = response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => null,
                'is_demo' => true,
            ],
            'message' => 'Demo login successful',
        ]);

        $response->cookie(
            'access_token',
            $accessToken,
            (int) ceil(((int) env('DEMO_SESSION_TTL_SECONDS', 7200)) / 60),
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
