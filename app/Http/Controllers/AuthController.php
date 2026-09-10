<?php

namespace App\Http\Controllers;

use App\Contracts\AuthServiceInterface;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Autenticación',
    description: 'Endpoints para registro, login, logout y gestión de tokens JWT'
)]
class AuthController extends Controller
{
    private AuthServiceInterface $authService;

    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    #[OA\Post(
        path: '/auth/register',
        summary: 'Registrar nuevo usuario',
        description: 'Crea un nuevo usuario y retorna un token JWT',
        operationId: 'register',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario registrado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Usuario registrado exitosamente.'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGci...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Usuario registrado exitosamente.',
            ...$result,
        ], 201);
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Iniciar sesión',
        description: 'Autentica al usuario y retorna un token JWT',
        operationId: 'login',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Login exitoso.'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGci...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales inválidas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Credenciales inválidas.'),
                    ]
                )
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->email, $request->password);

        if (!$result) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        return response()->json([
            'message' => 'Login exitoso.',
            ...$result,
        ]);
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Cerrar sesión',
        description: 'Invalida el token JWT actual',
        operationId: 'logout',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión cerrada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Sesión cerrada exitosamente.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }

    #[OA\Get(
        path: '/auth/me',
        summary: 'Obtener usuario autenticado',
        description: 'Retorna los datos del usuario autenticado',
        operationId: 'me',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Datos del usuario',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function me(): JsonResponse
    {
        return response()->json($this->authService->getUser());
    }

    #[OA\Post(
        path: '/auth/refresh',
        summary: 'Renovar token JWT',
        description: 'Renueva el token JWT actual y retorna un nuevo token',
        operationId: 'refresh',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token renovado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Token renovado exitosamente.'),
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGci...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function refresh(): JsonResponse
    {
        $token = $this->authService->refresh();

        return response()->json([
            'message' => 'Token renovado exitosamente.',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }
}
