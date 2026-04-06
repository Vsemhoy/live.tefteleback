<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // public function login(Request $request, JwtService $jwt)
    // {
    //     $credentials = $request->only('email', 'password');

    //     if (!Auth::attempt($credentials)) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }

    //     $user = Auth::user();
    //     $token = $jwt->generateToken([
    //         'uid' => $user->id,
    //         'role' => $user->role // если есть роли
    //     ]);

    //     return response()->json([
    //         'access_token' => $token,
    //         'token_type' => 'bearer',
    //         'expires_in' => 3600
    //     ]);
    // }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('api')->user();

        // Generate access token
        $accessToken = JWT::encode([
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + config('jwt.ttl'),
        ], config('jwt.secret'), config('jwt.algo'));

        // Generate refresh token
        $refreshToken = Str::random(60);

        // Store refresh token in Redis with user id and expiry
        Redis::setex(
            "refresh_token:{$refreshToken}",
            config('jwt.refresh_ttl'),
            $user->id
        );

        // Set cookies
        $cookieMinute = config('jwt.ttl') / 60; // access token TTL in minutes
        $refreshCookieMinute = config('jwt.refresh_ttl') / 60; // refresh token TTL in minutes

        $response = response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => null, // We don't have avatar field, set to null
            ],
            'message' => 'Login successful',
        ]);

        $response->cookie(
            'access_token',
            $accessToken,
            $cookieMinute,
            '/',
            null,
            false, // secure: set to true in production with HTTPS
            true,  // httpOnly
            false, // raw
            config('session.same_site', 'lax')
        );

        $response->cookie(
            'refresh_token',
            $refreshToken,
            $refreshCookieMinute,
            '/',
            null,
            false, // secure: set to true in production with HTTPS
            true,  // httpOnly
            false, // raw
            config('session.same_site', 'lax')
        );

        return $response;
    }

    public function validate(Request $request)
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        try {
            $payload = JWT::decode($token, new Key(config('jwt.secret'), config('jwt.algo')));

            return response()->json((array) $payload);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }

    //     public function me()
    // {
    //     $user = Auth::guard('api')->user();

    //     if (!$user) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }

    //     // Возвращаем только нужные поля пользователя
    //     return response()->json([
    //         'user' => [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'email' => $user->email
    //             // Другие поля по необходимости
    //         ],
    //         'token_info' => [
    //             'iat' => $user->token()->iat,
    //             'exp' => $user->token()->exp
    //         ]
    //     ]);
    // }

    public function me(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Get access token from cookie
        $accessToken = $request->cookie('access_token');
        if ($accessToken) {
            try {
                $payload = JWT::decode($accessToken, new Key(config('jwt.secret'), config('jwt.algo')));
                $iat = $payload->iat;
                $exp = $payload->exp;
            } catch (\Exception $e) {
                // If token is invalid, we still return user info but without token metadata
                $iat = null;
                $exp = null;
            }
        } else {
            $iat = null;
            $exp = null;
        }

        $response = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];

        if ($iat !== null && $exp !== null) {
            $response['token_metadata'] = [
                'issued_at' => date('Y-m-d H:i:s', $iat),
                'expires_at' => date('Y-m-d H:i:s', $exp),
                'valid_for' => $exp - time(),
                'expires_timestamp' => $exp * 1000, // JavaScript timestamp
            ];
        }

        return response()->json($response);
    }

    public function logout(Request $request)
    {
        try {
            // Get refresh token from cookie
            $refreshToken = $request->cookie('refresh_token');
            if ($refreshToken) {
                // Remove refresh token from Redis
                Redis::del("refresh_token:{$refreshToken}");
            }

            // Clear cookies
            $cookieMinute = config('jwt.ttl') / 60; // access token TTL in minutes
            $refreshCookieMinute = config('jwt.refresh_ttl') / 60; // refresh token TTL in minutes

            $response = response()->json([
                'message' => 'Successfully logged out',
                'should_clear' => true,
            ]);

            // Clear access token cookie
            $response->cookie(
                'access_token',
                '',
                0, // Expire now
                '/',
                null,
                false, // secure: set to true in production with HTTPS
                true,  // httpOnly
                false, // raw
                config('session.same_site', 'lax')
            );

            // Clear refresh token cookie
            $response->cookie(
                'refresh_token',
                '',
                0, // Expire now
                '/',
                null,
                false, // secure: set to true in production with HTTPS
                true,  // httpOnly
                false, // raw
                config('session.same_site', 'lax')
            );

            // Clear session guard (if used)
            Auth::logout();

            return $response;

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Logout failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refresh(Request $request)
    {
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

        $user = User::find($userId);
        if (! $user) {
            Redis::del($key);

            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Generate new access token
        $accessToken = JWT::encode([
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + config('jwt.ttl'),
        ], config('jwt.secret'), config('jwt.algo'));

        // Optionally rotate refresh token (uncomment if needed)
        // $newRefreshToken = Str::random(60);
        // Redis::setex("refresh_token:{$newRefreshToken}", config('jwt.refresh_ttl'), $user->id);
        // Redis::del($key);

        // Set new access token cookie
        $cookieMinute = config('jwt.ttl') / 60; // convert seconds to minutes
        $response = response()->json([
            'message' => 'Token refreshed',
        ]);

        $response->cookie(
            'access_token',
            $accessToken,
            $cookieMinute,
            '/',
            null,
            false, // secure: set to true in production with HTTPS
            true,  // httpOnly
            false, // raw
            config('session.same_site', 'lax')
        );

        // If rotating refresh token, set new refresh token cookie here
        // $response->cookie('refresh_token', $newRefreshToken, $refreshCookieMinute, '/', null, false, true, false, config('session.same_site', 'lax'));

        return $response;
    }

    /**
     * Регистрация нового пользователя
     */
    public function signup(Request $request)
    {
        // Валидация входных данных
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:32|unique:users,name',
            'email' => 'required|email|max:128|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'Имя пользователя обязательно.',
            'name.unique' => 'Пользователь с таким именем уже существует.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = 1;
        $user->save();

        // Generate access token
        $accessToken = JWT::encode([
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + config('jwt.ttl'),
        ], config('jwt.secret'), config('jwt.algo'));

        // Generate refresh token
        $refreshToken = Str::random(60);

        // Store refresh token in Redis with user id and expiry
        Redis::setex(
            "refresh_token:{$refreshToken}",
            config('jwt.refresh_ttl'),
            $user->id
        );

        // Set cookies
        $cookieMinute = config('jwt.ttl') / 60; // access token TTL in minutes
        $refreshCookieMinute = config('jwt.refresh_ttl') / 60; // refresh token TTL in minutes

        $response = response()->json([
            'status' => 'success',
            'message' => 'Регистрация успешна',
            'user' => $user->only('id', 'name', 'email'),
        ], 201);

        $response->cookie(
            'access_token',
            $accessToken,
            $cookieMinute,
            '/',
            null,
            false, // secure: set to true in production with HTTPS
            true,  // httpOnly
            false, // raw
            config('session.same_site', 'lax')
        );

        $response->cookie(
            'refresh_token',
            $refreshToken,
            $refreshCookieMinute,
            '/',
            null,
            false, // secure: set to true in production with HTTPS
            true,  // httpOnly
            false, // raw
            config('session.same_site', 'lax')
        );

        return $response;
    }

    public function changePass(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user) {
                throw new \Exception('Пользователь не найден', 401);
            }

            Log::info('Change password request', [
                'ip' => $request->ip(),
                'user_id' => $user->id,
            ]);

            // 1. Проверяем старый пароль
            $oldPassword = $request->input('old_password');
            if (password_verify($oldPassword, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Неверный текущий пароль',
                ], 400);
            }

            // 2. Валидация нового пароля

            // 3. Хешируем и сохраняем новый пароль
            $user->password = bcrypt($request->password);
            $user->save();

            // 4. Генерируем новые токены
            $accessToken = JWT::encode([
                'sub' => $user->id,
                'iat' => time(),
                'exp' => time() + config('jwt.ttl'),
            ], config('jwt.secret'), config('jwt.algo'));

            $refreshToken = Str::random(60);

            // Удаляем старый refresh token из Redis (если есть)
            $oldRefreshToken = $request->cookie('refresh_token');
            if ($oldRefreshToken) {
                Redis::del("refresh_token:{$oldRefreshToken}");
            }

            // Сохраняем новый refresh token в Redis
            Redis::setex(
                "refresh_token:{$refreshToken}",
                config('jwt.refresh_ttl'),
                $user->id
            );

            // Устанавливаем куки
            $cookieMinute = config('jwt.ttl') / 60;
            $refreshCookieMinute = config('jwt.refresh_ttl') / 60;

            $response = response()->json([
                'status' => 'success',
                'message' => 'Пароль успешно изменён',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 200);

            $response->cookie(
                'access_token',
                $accessToken,
                $cookieMinute,
                '/',
                null,
                false,
                true,
                false,
                config('session.same_site', 'lax')
            );

            $response->cookie(
                'refresh_token',
                $refreshToken,
                $refreshCookieMinute,
                '/',
                null,
                false,
                true,
                false,
                config('session.same_site', 'lax')
            );

            return $response;

        } catch (\Exception $e) {
            Log::error('Change password error', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Не удалось изменить пароль',
            ], $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }
}
