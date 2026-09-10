<?php

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
        ]);

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function login(string $email, string $password): ?array
    {
        $credentials = ['email' => $email, 'password' => $password];

        if (!$token = JWTAuth::attempt($credentials)) {
            return null;
        }

        return [
            'user' => auth()->user(),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function refresh(): string
    {
        return JWTAuth::refresh(JWTAuth::getToken());
    }

    public function getUser(): User
    {
        return auth()->user();
    }
}
