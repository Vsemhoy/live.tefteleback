<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;

class JwtGuard
{
    protected $provider;
    protected $request;
    protected $user;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    public function check()
    {
        return !is_null($this->user());
    }

    public function guest()
    {
        return !$this->check();
    }

    // public function user()
    // {
    //     if (!is_null($this->user)) {
    //         return $this->user;
    //     }

    //     $token = $this->getTokenFromRequest();
        
    //     if (empty($token)) {
    //         return null;
    //     }

    //     try {
    //         $decoded = JWT::decode(
    //             $token,
    //             new Key(config('jwt.secret'), 'HS256')
    //         );
            
    //         return $this->user = $this->provider->retrieveById($decoded->sub);
    //     } catch (\Exception $e) {
    //         return null;
    //     }
    // }


    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();
        
        if (empty($token)) {
            return null;
        }

        try {
            $decoded = JWT::decode(
                $token,
                new Key(config('jwt.secret'), 'HS256')
            );
            
            // Возвращаем не только ID, но и самого пользователя
            $this->user = $this->provider->retrieveById($decoded->sub);
            return $this->user;
            
        } catch (\Exception $e) {
            return null;
        }
    }


    public function id()
    {
        return $this->user() ? $this->user()->getAuthIdentifier() : null;
    }

    public function validate(array $credentials = [])
    {
        // Реализация при необходимости
        return false;
    }

    protected function getTokenFromRequest()
    {
        return $this->request->bearerToken();
    }



    public function attempt(array $credentials = [], $remember = false)
    {
        // 1. Проверяем credentials через провайдер
        $user = $this->provider->retrieveByCredentials($credentials);
        
        if (!$user) {
            return false;
        }

        // 2. Проверяем пароль
        if ($this->provider->validateCredentials($user, $credentials)) {
            $this->setUser($user);
            return true;
        }

        return false;
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    protected function cacheUser($user)
    {
        Cache::put('jwt_user_'.$user->id, $user, now()->addMinutes(5));
    }
    
}