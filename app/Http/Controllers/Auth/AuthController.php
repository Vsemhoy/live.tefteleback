<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LedLayer;
use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
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

        // Store refresh token in database
        RefreshToken::create([
            'token' => $refreshToken,
            'user_id' => $user->id,
            'expires_at' => now()->addSeconds(config('jwt.refresh_ttl')),
        ]);

        // Set cookies
        $cookieMinute = config('jwt.ttl') / 60;
        $refreshCookieMinute = config('jwt.refresh_ttl') / 60;

        $response = response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => null,
                'is_demo' => (bool) $user->is_demo,
            ],
            'message' => 'Login successful',
        ]);

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

    public function me(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $accessToken = $request->cookie('access_token');
        if ($accessToken) {
            try {
                $payload = JWT::decode($accessToken, new Key(config('jwt.secret'), config('jwt.algo')));
                $iat = $payload->iat;
                $exp = $payload->exp;
            } catch (\Exception $e) {
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
                'is_demo' => (bool) $user->is_demo,
            ],
        ];

        if ($iat !== null && $exp !== null) {
            $response['token_metadata'] = [
                'issued_at' => date('Y-m-d H:i:s', $iat),
                'expires_at' => date('Y-m-d H:i:s', $exp),
                'valid_for' => $exp - time(),
                'expires_timestamp' => $exp * 1000,
            ];
        }

        return response()->json($response);
    }

    public function logout(Request $request)
    {
        try {
            $refreshToken = $request->cookie('refresh_token');
            if ($refreshToken) {
                RefreshToken::where('token', $refreshToken)->delete();
            }

            $cookieMinute = config('jwt.ttl') / 60;
            $refreshCookieMinute = config('jwt.refresh_ttl') / 60;

            $response = response()->json([
                'message' => 'Successfully logged out',
                'should_clear' => true,
            ]);

            $response->cookie(
                'access_token',
                '',
                0,
                '/',
                null,
                false,
                true,
                false,
                config('session.same_site', 'lax')
            );

            $response->cookie(
                'refresh_token',
                '',
                0,
                '/',
                null,
                false,
                true,
                false,
                config('session.same_site', 'lax')
            );

            Auth::logout();

            return $response;

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Logout failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        if (! $refreshToken) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $tokenRecord = RefreshToken::where('token', $refreshToken)->first();
        if (! $tokenRecord || $tokenRecord->expires_at->isPast()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $user = User::find($tokenRecord->user_id);
        if (! $user) {
            $tokenRecord->delete();

            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Delete old refresh token
        $tokenRecord->delete();

        // Generate new access token
        $accessToken = JWT::encode([
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + config('jwt.ttl'),
        ], config('jwt.secret'), config('jwt.algo'));

        $cookieMinute = config('jwt.ttl') / 60;
        $response = response()->json([
            'message' => 'Token refreshed',
        ]);

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

        return $response;
    }

    public function signup(Request $request)
    {
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

        // Create base layer for the user (TЗ 6. Seeder — base layer при регистрации)
        LedLayer::create([
            'id' => Str::ulid(),
            'user_id' => $user->id,
            'name' => 'Base',
            'type' => 'base',
        ]);

        // Generate access token
        $accessToken = JWT::encode([
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + config('jwt.ttl'),
        ], config('jwt.secret'), config('jwt.algo'));

        // Generate refresh token
        $refreshToken = Str::random(60);

        // Store refresh token in database
        RefreshToken::create([
            'token' => $refreshToken,
            'user_id' => $user->id,
            'expires_at' => now()->addSeconds(config('jwt.refresh_ttl')),
        ]);

        // Set cookies
        $cookieMinute = config('jwt.ttl') / 60;
        $refreshCookieMinute = config('jwt.refresh_ttl') / 60;

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
    }

    public function changePass(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user) {
                throw new \Exception('Пользователь не найден', 401);
            }

            if ($user->is_demo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Demo users cannot change password',
                ], 403);
            }

            Log::info('Change password request', [
                'ip' => $request->ip(),
                'user_id' => $user->id,
            ]);

            $oldPassword = $request->input('old_password');
            if (password_verify($oldPassword, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Неверный текущий пароль',
                ], 400);
            }

            $user->password = bcrypt($request->password);
            $user->save();

            // Generate new access token
            $accessToken = JWT::encode([
                'sub' => $user->id,
                'iat' => time(),
                'exp' => time() + config('jwt.ttl'),
            ], config('jwt.secret'), config('jwt.algo'));

            // Generate new refresh token
            $refreshToken = Str::random(60);

            // Delete old refresh token
            $oldRefreshToken = $request->cookie('refresh_token');
            if ($oldRefreshToken) {
                RefreshToken::where('token', $oldRefreshToken)->delete();
            }

            // Store new refresh token in database
            RefreshToken::create([
                'token' => $refreshToken,
                'user_id' => $user->id,
                'expires_at' => now()->addSeconds(config('jwt.refresh_ttl')),
            ]);

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
