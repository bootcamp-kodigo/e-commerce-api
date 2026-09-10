# E-commerce API

API REST para sistema de comercio electrónico con autenticación JWT, integración con Stripe para pagos y documentación Swagger completa.

## Características

- Autenticación JWT (registro, login, logout, refresh)
- CRUD completo de productos
- Gestión de órdenes de compra
- Integración con Stripe para procesamiento de pagos
- Documentación Swagger/OpenAPI completa
- Manejo global de errores
- Validaciones con Form Requests
- Base de datos MySQL con relaciones bien definidas

## Requisitos

- PHP 8.2 o superior
- Composer
- MySQL 8.0 o superior
- Extensión de PHP: `pdo_mysql`, `sodium`

## Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd e-commerce-api
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar variables de entorno

Copiar el archivo `.env.example` a `.env`:

```bash
cp .env.example .env
```

Editar el archivo `.env` con tus credenciales:

```env
# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_commerce
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

# Stripe (obtener de https://dashboard.stripe.com/test/apikeys)
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_CURRENCY=usd
```

### 4. Generar clave de la aplicación

```bash
php artisan key:generate
```

### 5. Generar clave JWT

```bash
php artisan jwt:secret
```

### 6. Crear la base de datos

Crear una base de datos MySQL vacía con el nombre especificado en `.env`:

```sql
CREATE DATABASE e_commerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Ejecutar migraciones y seeders

```bash
php artisan migrate:fresh --seed
```

Esto creará todas las tablas y poblará la base de datos con datos de prueba.

### 8. Generar documentación Swagger

```bash
php artisan l5-swagger:generate
```

### 9. Iniciar el servidor

```bash
php artisan serve
```

La API estará disponible en: `http://localhost:8000/api`

## Documentación API

Una vez iniciado el servidor, puedes acceder a la documentación interactiva de Swagger UI en:

```
http://localhost:8000/api/documentation
```

## Endpoints Principales

### Autenticación

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/api/auth/register` | Registrar nuevo usuario | No |
| POST | `/api/auth/login` | Iniciar sesión | No |
| POST | `/api/auth/logout` | Cerrar sesión | Sí |
| GET | `/api/auth/me` | Obtener usuario actual | Sí |
| POST | `/api/auth/refresh` | Renovar token JWT | Sí |

### Productos

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/products` | Listar productos | No |
| GET | `/api/products/{id}` | Ver producto | No |
| POST | `/api/products` | Crear producto | Sí |
| PUT | `/api/products/{id}` | Actualizar producto | Sí |
| DELETE | `/api/products/{id}` | Eliminar producto | Sí |

### Órdenes

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/orders` | Listar órdenes del usuario | Sí |
| GET | `/api/orders/{id}` | Ver orden | Sí |
| POST | `/api/orders` | Crear orden | Sí |

### Pagos

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/api/payments/create-intent/{order}` | Crear PaymentIntent | Sí |
| POST | `/api/payments/confirm` | Confirmar pago | Sí |

## Credenciales de Prueba

Después de ejecutar los seeders, puedes usar estas credenciales:

| Usuario | Email | Password | Rol |
|---------|-------|----------|-----|
| Admin | admin@example.com | password123 | admin |
| Cliente 1 | john@example.com | password123 | customer |
| Cliente 2 | jane@example.com | password123 | customer |
| Cliente 3 | carlos@example.com | password123 | customer |
| Cliente 4 | maria@example.com | password123 | customer |

## Flujo de Uso

### 1. Autenticación

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'

# Respuesta: { "token": "eyJ0eXAi...", "token_type": "bearer", ... }
```

### 2. Listar Productos (público)

```bash
curl http://localhost:8000/api/products
```

### 3. Crear Orden

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"product_id": 1, "quantity": 2},
      {"product_id": 3, "quantity": 1}
    ],
    "shipping_address": "Calle Principal 123, Ciudad",
    "notes": "Entregar en horario de oficina"
  }'
```

### 4. Procesar Pago

```bash
# Crear PaymentIntent
curl -X POST http://localhost:8000/api/payments/create-intent/1 \
  -H "Authorization: Bearer {token}"

# Respuesta: { "client_secret": "pi_xxx_secret_xxx", ... }

# Usar client_secret con Stripe.js en el frontend
# Luego confirmar el pago
curl -X POST http://localhost:8000/api/payments/confirm \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"payment_intent_id": "pi_xxx"}'
```

## Estructura del Proyecto

```
app/
├── Exceptions/
│   └── ApiExceptionHandler.php    # Manejo global de errores
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php     # Autenticación JWT
│   │   ├── ProductController.php  # CRUD productos
│   │   ├── OrderController.php    # Gestión de órdenes
│   │   └── PaymentController.php  # Pagos con Stripe
│   ├── Requests/                  # Form Requests (validaciones)
│   └── Swagger/
│       └── Schemas.php            # Schemas OpenAPI
├── Models/
│   ├── User.php
│   ├── Product.php
│   ├── Order.php
│   ├── OrderItem.php
│   └── Payment.php
└── Services/
    └── StripeService.php          # Lógica de Stripe
```

## Base de Datos

### Tablas

- **users**: Clientes del sistema (con rol customer/admin)
- **products**: Catálogo de productos
- **orders**: Órdenes de compra
- **order_items**: Detalle de productos por orden
- **payments**: Registro de transacciones de Stripe

### Relaciones

```
User ──hasMany──> Orders
Order ──belongsTo──> User
Order ──hasMany──> OrderItems
Order ──hasOne──> Payment
OrderItem ──belongsTo──> Order
OrderItem ──belongsTo──> Product
Product ──hasMany──> OrderItems
Payment ──belongsTo──> Order
```

## Configuración de Stripe

1. Crear cuenta en [Stripe](https://stripe.com)
2. Obtener las API keys de modo test en [Dashboard → Developers → API keys](https://dashboard.stripe.com/test/apikeys)
3. Configurar en `.env`:
   - `STRIPE_KEY`: Publishable key (pk_test_...)
   - `STRIPE_SECRET`: Secret key (sk_test_...)
   - `STRIPE_WEBHOOK_SECRET`: Webhook secret (opcional, para webhooks)

### Tarjetas de prueba

| Número | Descripción |
|--------|-------------|
| 4242 4242 4242 4242 | Éxito |
| 4000 0000 0000 0002 | Rechazada |

Usar cualquier fecha futura y cualquier CVC de 3 dígitos.

## Manejo de Errores

Todos los errores se retornan en formato JSON consistente:

```json
{
  "message": "Descripción del error",
  "errors": {
    "campo": ["Mensaje de error específico"]
  }
}
```

| Código | Descripción |
|--------|-------------|
| 400 | Bad Request |
| 401 | No autenticado |
| 403 | No autorizado |
| 404 | Recurso no encontrado |
| 405 | Método no permitido |
| 422 | Error de validación |
| 500 | Error interno del servidor |

## Licencia

Este proyecto fue desarrollado como parte de un bootcamp de desarrollo.
