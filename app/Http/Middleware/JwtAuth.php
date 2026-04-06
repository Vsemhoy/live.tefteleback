<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class JwtAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Symfony\Component\HttpFoundation\Response)  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $accessToken = $request->cookie('access_token');
        if (! $accessToken) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $secret = config('jwt.secret');
            $decoded = JWT::decode($accessToken, new Key($secret, config('jwt.algo')));
            $userId = $decoded->sub;

            // Attach user to request via auth guard
            $user = User::find($userId);
            if (! $user) {
                throw new \Exception('User not found');
            }
            Auth::guard('api')->setUser($user);

            return $next($request);
        } catch (\Exception $e) {
            // Token might be expired, try to refresh
            $refreshToken = $request->cookie('refresh_token');
            if (! $refreshToken) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Check refresh token in Redis
            $key = "refresh_token:{$refreshToken}";
            $userId = Redis::get($key);
            if (! $userId) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Refresh token is valid, generate new access token
            $user = User::find($userId);
            if (! $user) {
                Redis::del($key);

                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $newAccessToken = JWT::encode([
                'sub' => $user->id,
                'iat' => time(),
                'exp' => time() + config('jwt.ttl'),
            ], config('jwt.secret'), config('jwt.algo'));

            // Set new access token cookie
            $cookieMinute = config('jwt.ttl') / 60; // convert seconds to minutes
            $response = $next($request);
            $response->cookie(
                'access_token',
                $newAccessToken,
                $cookieMinute,
                '/',
                null,
                false, // secure: set to true in production with HTTPS
                true,  // httpOnly
                false, // raw
                config('session.same_site', 'lax')
            );

            // Optionally rotate refresh token (uncomment if needed)
            // $newRefreshToken = Str::random(60);
            // Redis::setex("refresh_token:{$newRefreshToken}", config('jwt.refresh_ttl'), $user->id);
            // Redis::del($key);
            // $response->cookie(
            //     'refresh_token',
            //     $newRefreshToken,
            //     config('jwt.refresh_ttl') / 60,
            //     '/',
            //     null,
            //     false,
            //     true,
            //     false,
            //     config('session.same_site', 'lax')
            // );

            // Attach user to request for the refreshed token via auth guard
            Auth::guard('api')->setUser($user);

            return $response;
        }
    }
}
