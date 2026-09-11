<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'E-commerce API',
    version: '1.0.0',
    description: 'API REST para sistema de comercio electrónico con autenticación JWT y pagos con Stripe',
    contact: new OA\Contact(
        name: 'API Support',
        email: 'support@ecommerce-api.com'
    )
)]
#[OA\Server(
    url: 'http://localhost:8000/api',
    description: 'Servidor de desarrollo'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'JWT Authorization header. Ejemplo: "Authorization: Bearer {token}"'
)]
abstract class Controller
{
    //
}
