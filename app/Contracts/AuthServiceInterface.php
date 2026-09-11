<?php

namespace App\Contracts;

use App\Models\User;

interface AuthServiceInterface
{
    public function register(array $data): array;

    public function login(string $email, string $password): ?array;

    public function logout(): void;

    public function refresh(): string;

    public function getUser(): User;
}
