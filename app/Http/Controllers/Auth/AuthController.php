<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Uid\Ulid;
use Illuminate\Support\Facades\Validator;

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

    if (!Auth::guard('api')->attempt($credentials)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $user = Auth::guard('api')->user();
    $token = JWT::encode([
        'sub' => $user->id,
        'iat' => time(),
        'exp' => time() + config('jwt.ttl')
    ], config('jwt.secret'), config('jwt.algo'));

    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => config('jwt.ttl')
    ]);
}

    public function validate(Request $request, JwtService $jwt)
    {
        $token = $request->bearerToken();
        if (!$payload = $jwt->validateToken($token)) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return response()->json($payload);
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

    public function me(Request $request, JwtService $jwt)
    {

        
        try {
            $user = $request->user();
            
            if (!$user) {
                throw new \Exception('User not found', 401);
            }
            Log::info('ME request', ['ip' => $request->ip(), 'user_id' => $user->id ?? null]);

            $token = $request->bearerToken();
            $payload = $jwt->validateToken($token);
            $expiresAt = now()->addSeconds(config('jwt.ttl'));

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'token_metadata' => [
                    'issued_at' => date('Y-m-d H:i:s', $payload['iat']),
                    'expires_at' => date('Y-m-d H:i:s', $payload['exp']),
                    'valid_for' => $payload['exp'] - time(),
                    'expires_timestamp' => $expiresAt->timestamp * 1000 // JavaScript timestamp
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // 1. Инвалидация текущего JWT-токена (если используется)
            if ($token = $request->bearerToken()) {
                app(JwtService::class)->invalidateToken($token);
            }

            // 2. Очистка аутентификационной сессии
            auth()->logout();
            
            // 3. Ответ для клиента
            return response()->json([
                'message' => 'Successfully logged out',
                'should_clear' => true // Флаг для фронта
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Logout failed',
                'message' => $e->getMessage()
            ], 500);
        }
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

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = 1;
        $user->save(); 
        // Опционально: создать API токен (если используешь Sanctum)
    // Генерируем JWT-токен (как в login)
        $token = JWT::encode([
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + config('jwt.ttl') // например, 3600 (1 час)
        ], config('jwt.secret'), config('jwt.algo'));

        return response()->json([
            'status' => 'success',
            'message' => 'Регистрация успешна',
            'user' => $user->only('id', 'name', 'email'),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl')
        ], 201);
    }
    

    public function changePass(Request $request, JwtService $jwt)
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new \Exception('Пользователь не найден', 401);
            }

            Log::info('Change password request', [
                'ip' => $request->ip(),
                'user_id' => $user->id
            ]);



            // dd(Hash::make($request->password), $user->password);
            // 1. Проверяем старый пароль
            $oldPassword = $request->input('old_password');
            // dd($oldPassword);
            // dd(Hash::make($oldPassword), $user->password);
            // if (!Hash::check($oldPassword, $user->password)) {
            if (password_verify($oldPassword, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Неверный текущий пароль 3'
                ], 400);
            }

            // 2. Валидация нового пароля
            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
                'email' => 'ignore',
                'password2' => 'ignore'
            ], [
                'password.confirmed' => 'Новые пароли не совпадают.',
                'password.min' => 'Пароль должен быть не менее :min символов.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // 3. Хешируем и сохраняем новый пароль
            $user->password = bcrypt($request->password);
            $user->save(); // или $user->update() — но лучше save()

            // 4. Генерируем новый JWT-токен
            $newToken = JWT::encode([
                'sub' => $user->id,
                'iat' => time(),
                'exp' => time() + config('jwt.ttl')
            ], config('jwt.secret'), config('jwt.algo'));

            $expiresAt = now()->addSeconds(config('jwt.ttl'));

            return response()->json([
                'status' => 'success',
                'message' => 'Пароль успешно изменён',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl'),
                'token_metadata' => [
                    'issued_at' => date('Y-m-d H:i:s', time()),
                    'expires_at' => date('Y-m-d H:i:s', time() + config('jwt.ttl')),
                    'valid_for' => config('jwt.ttl'),
                    'expires_timestamp' => $expiresAt->timestamp * 1000
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Change password error', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Не удалось изменить пароль'
            ], $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }
}