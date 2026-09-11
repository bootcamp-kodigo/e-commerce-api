# E-commerce API - Registro de Etapas

Este documento registra todas las etapas de desarrollo del proyecto E-commerce API con explicaciones detalladas para facilitar el repaso y aprendizaje.

---

## Etapa 1: Configuración Inicial

### Objetivo
Preparar el entorno de desarrollo, instalar dependencias y configurar los servicios base.

### Qué se hizo

#### 1.1 Configuración de Base de Datos MySQL
- Se cambió la conexión de SQLite a MySQL en el archivo `.env`
- Se habilitaron las extensiones de PHP necesarias:
  - `pdo_mysql` - Driver para conectar con MySQL
  - `sodium` - Requerido por la librería JWT

#### 1.2 Instalación de Paquetes

| Paquete | Versión | Propósito |
|---------|---------|-----------|
| `tymon/jwt-auth` | v2.3.0 | Autenticación mediante tokens JWT |
| `darkaonline/l5-swagger` | v11.1.0 | Documentación automática de la API |
| `stripe/stripe-php` | v21.3.2 | Integración con la pasarela de pagos Stripe |

#### 1.3 Configuración JWT
- Se publicó el archivo de configuración: `config/jwt.php`
- Se generó la clave secreta JWT con `php artisan jwt:secret`
- Se configuró el guard `api` con driver `jwt` en `config/auth.php`
- Se implementó la interfaz `JWTSubject` en el modelo `User`

#### 1.4 Configuración de Variables de Entorno
Se agregaron al `.env` y `.env.example`:
```env
# JWT
JWT_SECRET=
JWT_TTL=60

# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_CURRENCY=usd
```

### Conceptos clave
- **JWT (JSON Web Token)**: Token firmado digitalmente que contiene información del usuario. Permite autenticación sin estado (stateless).
- **Guard**: Mecanismo de autenticación de Laravel. El guard `api` usa JWT en lugar de sesiones.
- **Service Provider**: Clase que registra servicios en el contenedor de Laravel. Los paquetes se auto-descubren.

---

## Etapa 2: Migraciones y Modelos

### Objetivo
Crear la estructura de base de datos con todas las tablas necesarias y sus relaciones.

### Qué se hizo

#### 2.1 Tablas Creadas

| Tabla | Propósito | Campos principales |
|-------|-----------|-------------------|
| `users` | Clientes del sistema | name, email, password, phone, role |
| `products` | Catálogo de productos | sku, name, description, price, stock, image_url, is_active |
| `orders` | Órdenes de compra | user_id, total, status, shipping_address, notes |
| `order_items` | Detalle de productos por orden | order_id, product_id, quantity, price, subtotal |
| `payments` | Registro de transacciones | order_id, stripe_payment_intent_id, amount, currency, status, payment_method, last4 |

#### 2.2 Relaciones entre Modelos

```
User (1) ──── (N) Order
Order (1) ──── (N) OrderItem
Order (1) ──── (0..1) Payment
OrderItem (N) ──── (1) Product
```

#### 2.3 Características adicionales
- **SoftDeletes** en productos: Permite "eliminar" productos sin borrarlos físicamente
- **Índices** en campos de búsqueda frecuente (is_active, user_id, status)
- **Foreign keys** con cascade delete para mantener integridad referencial
- **Configuración de Stripe** en archivo separado: `config/stripe.php`

### Conceptos clave
- **Migración**: Archivo PHP que define cambios en la base de datos. Laravel las ejecuta en orden por fecha.
- **Foreign Key**: Restricción que asegura que un valor exista en otra tabla.
- **Soft Delete**: En lugar de borrar un registro, se marca con `deleted_at`. Los datos persisten pero no se muestran.
- **Cascade Delete**: Al bregar el registro padre, se borran automáticamente los hijos.

---

## Etapa 3: Seeders

### Objetivo
Poblar la base de datos con datos de prueba para desarrollo y testing.

### Qué se hizo

#### 3.1 UserSeeder
Creó 5 usuarios de prueba:
- 1 admin: `admin@example.com`
- 4 customers: `john@example.com`, `jane@example.com`, `carlos@example.com`, `maria@example.com`
- Password para todos: `password123`

#### 3.2 ProductSeeder
Creó 12 productos variados:
- Electrónicos: Laptop, Smartphone, Tablet, Cámara, Monitor
- Accesorios: Auriculares, Smartwatch, Teclado, Mouse, Altavoz, Cargador, Funda
- Precios entre $24.99 y $1,599.99
- Todos con stock disponible

#### 3.3 DatabaseSeeder
Orquestador que ejecuta los seeders en orden:
```php
$this->call([
    UserSeeder::class,
    ProductSeeder::class,
]);
```

### Conceptos clave
- **Seeder**: Clase que inserta datos en la base de datos. Se ejecuta con `php artisan db:seed`.
- **Factory**: Genera datos aleatorios para testing (no se usó en este proyecto).
- **`migrate:fresh --seed`**: Recrea todas las tablas y las puebla con datos.

---

## Etapa 4: Autenticación JWT

### Objetivo
Implementar sistema de autenticación completo con tokens JWT.

### Qué se hizo

#### 4.1 Form Requests de Validación

| Request | Validaciones |
|---------|--------------|
| `RegisterRequest` | name (required), email (required, email, unique), password (required, min:8, confirmed), phone (nullable) |
| `LoginRequest` | email (required, email), password (required) |

Ambos con mensajes de error personalizados en español.

#### 4.2 AuthController

| Método | HTTP | Descripción |
|--------|------|-------------|
| `register()` | POST /api/auth/register | Crea usuario y retorna JWT |
| `login()` | POST /api/auth/login | Valida credenciales y retorna JWT |
| `logout()` | POST /api/auth/logout | Invalida el token actual |
| `me()` | GET /api/auth/me | Retorna usuario autenticado |
| `refresh()` | POST /api/auth/refresh | Renueva el token JWT |

#### 4.3 Rutas configuradas
- Rutas públicas: register, login
- Rutas protegidas con `auth:api`: me, logout, refresh

### Conceptos clave
- **Form Request**: Clase que valida datos antes de que lleguen al controlador. Si falla, retorna 422 automáticamente.
- **`auth:api` middleware**: Verifica que el token JWT sea válido y extrae el usuario.
- **Token refresh**: Permite obtener un nuevo token sin hacer login de nuevo, extendiendo la sesión.

### Flujo de autenticación
```
1. Cliente envía email/password a /api/auth/login
2. Servidor valida y genera token JWT
3. Cliente guarda token y lo envía en header: Authorization: Bearer {token}
4. Servidor valida token en cada request protegido
5. Cuando expira, cliente puede hacer refresh o login de nuevo
```

---

## Etapa 5: CRUD de Productos

### Objetivo
Implementar operaciones completas de creación, lectura, actualización y eliminación de productos.

### Qué se hizo

#### 5.1 Form Requests

| Request | Validaciones |
|---------|--------------|
| `StoreProductRequest` | sku (required, unique), name (required), price (required, numeric, min:0.01), stock (required, integer, min:0), description (nullable), image_url (nullable, url), is_active (nullable, boolean) |
| `UpdateProductRequest` | Mismos campos pero con `sometimes` (opcionales), SKU único excepto para el producto actual |

#### 5.2 ProductController

| Método | HTTP | Descripción | Auth |
|--------|------|-------------|------|
| `index()` | GET /api/products | Lista productos activos con paginación | No |
| `show()` | GET /api/products/{id} | Muestra un producto específico | No |
| `store()` | POST /api/products | Crea un nuevo producto | Sí |
| `update()` | PUT /api/products/{id} | Actualiza un producto | Sí |
| `destroy()` | DELETE /api/products/{id} | Elimina producto (soft delete) | Sí |

#### 5.3 Configuración adicional
- Se modificó `bootstrap/app.php` para que las rutas API retornen 401 JSON en vez de redirigir

### Conceptos clave
- **Paginación**: Laravel divide automáticamente los resultados en páginas. Retorna metadata: current_page, per_page, total, etc.
- **Route Model Binding**: Laravel convierte automáticamente `{product}` en una instancia del modelo Product.
- **Soft Delete**: `delete()` no borra físicamente, solo marca `deleted_at`. El producto no aparece en consultas pero los datos persisten.

---

## Etapa 6: Órdenes de Compra

### Objetivo
Implementar creación de órdenes con validación de stock y cálculo automático de totales.

### Qué se hizo

#### 6.1 StoreOrderRequest
Validaciones:
- `items` (required, array, min:1)
- `items.*.product_id` (required, exists:products)
- `items.*.quantity` (required, integer, min:1)
- `shipping_address` (required, max:500)
- `notes` (nullable, max:1000)

#### 6.2 OrderController

| Método | HTTP | Descripción |
|--------|------|-------------|
| `index()` | GET /api/orders | Lista órdenes del usuario autenticado |
| `show()` | GET /api/orders/{id} | Muestra detalle de una orden (solo si pertenece al usuario) |
| `store()` | POST /api/orders | Crea una nueva orden |

#### 6.3 Lógica de negocio en store()
```
1. Iterar sobre items
2. Validar que cada producto exista y tenga stock suficiente
3. Calcular subtotal por item y total de la orden
4. Crear la orden con status "pending"
5. Crear los order_items relacionados
6. Decrementar stock de cada producto
```

Todo dentro de una **transacción de base de datos** para garantizar atomicidad.

### Conceptos clave
- **Transacción DB**: Grupo de operaciones que se ejecutan juntas. Si una falla, todas se revierten.
- **Atomicidad**: Propiedad que garantiza que todas las operaciones de una transacción se completan o ninguna.
- **Eager Loading**: `with('items.product')` carga las relaciones en una sola consulta, evitando el problema N+1.

---

## Etapa 7: Integración Stripe

### Objetivo
Implementar procesamiento de pagos con Stripe usando PaymentIntents.

### Qué se hizo

#### 7.1 StripeService
Servicio que encapsula toda la lógica de Stripe:

| Método | Descripción |
|--------|-------------|
| `createPaymentIntent()` | Crea PaymentIntent en Stripe y registra Payment en BD |
| `confirmPayment()` | Consulta el estado del PaymentIntent en Stripe |
| `updatePaymentStatus()` | Actualiza Payment y Order según estado del pago |
| `convertToCents()` | Convierte monto a centavos (Stripe requiere enteros) |

#### 7.2 PaymentController

| Método | HTTP | Descripción |
|--------|------|-------------|
| `createPaymentIntent()` | POST /api/payments/create-intent/{order} | Crea PaymentIntent para una orden |
| `confirmPayment()` | POST /api/payments/confirm | Confirma el pago consultando Stripe |

#### 7.3 Flujo de pagos
```
1. Cliente crea orden → status: "pending"
2. Cliente solicita PaymentIntent → Stripe retorna client_secret
3. Frontend usa Stripe.js con client_secret para procesar pago
4. Cliente confirma pago → sistema consulta Stripe y actualiza estados
5. Si pago exitoso → order.status = "paid", payment.status = "succeeded"
```

### Conceptos clave
- **PaymentIntent**: Objeto de Stripe que representa la intención de cobrar. Guía el flujo de pago.
- **client_secret**: Token temporal que usa Stripe.js en el frontend para procesar el pago de forma segura.
- **Webhook** (no implementado): Notificación que Stripe envía a tu servidor cuando cambia el estado de un pago.

---

## Etapa 8: Documentación Swagger/OpenAPI

### Objetivo
Documentar todos los endpoints de la API usando Swagger/OpenAPI.

### Qué se hizo

#### 8.1 Configuración L5Swagger
- Título: "E-commerce API"
- Security scheme para JWT Bearer token
- Host: `http://localhost:8000/api`

#### 8.2 Documentación base en Controller.php
- Info de la API (título, versión, descripción)
- Server de desarrollo
- Security scheme para Bearer Auth

#### 8.3 Documentación por Controller

| Controller | Endpoints documentados |
|------------|------------------------|
| AuthController | register, login, logout, me, refresh |
| ProductController | index, show, store, update, destroy |
| OrderController | index, show, store |
| PaymentController | createPaymentIntent, confirmPayment |

#### 8.4 Schemas de modelos
Archivo `app/Http/Swagger/Schemas.php` con definiciones OpenAPI para:
- User
- Product
- Order
- OrderItem
- Payment

### Conceptos clave
- **OpenAPI**: Estándar para describir APIs REST. Permite generar documentación interactiva.
- **Swagger UI**: Interfaz web que consume la especificación OpenAPI y permite probar los endpoints.
- **Anotaciones PHP**: Comentarios especiales que L5Swagger convierte en especificación OpenAPI.
- **Schemas**: Definiciones de estructuras de datos reutilizables en la documentación.

---

## Etapa 9: Manejo Global de Errores

### Objetivo
Centralizar el manejo de errores para retornar respuestas JSON consistentes.

### Qué se hizo

#### 9.1 ApiExceptionHandler
Clase que intercepta todas las excepciones y las formatea en JSON:

| Tipo de excepción | Código | Respuesta |
|-------------------|--------|-----------|
| ValidationException | 422 | `{"message": "Error de validación", "errors": {...}}` |
| AuthenticationException | 401 | `{"message": "No autenticado"}` |
| NotFoundHttpException | 404 | `{"message": "Recurso no encontrado"}` |
| MethodNotAllowedHttpException | 405 | `{"message": "Método no permitido"}` |
| HttpException | Varios | `{"message": "..."}` |
| Otros | 500 | `{"message": "..."}` + detalles en modo debug |

#### 9.2 Configuración en bootstrap/app.php
```php
->withExceptions(function (Exceptions $exceptions): void {
    ApiExceptionHandler::register($exceptions);
})
```

### Conceptos clave
- **Manejo centralizado**: Todas las excepciones pasan por un solo punto, garantizando consistencia.
- **Modo debug**: En desarrollo se incluye información detallada del error. En producción solo el mensaje.
- **Status codes HTTP**: Códigos estándar que indican el tipo de error (401, 404, 422, 500, etc.)

---

## Etapa 10: README y .env.example

### Objetivo
Crear documentación completa del proyecto para otros desarrolladores.

### Qué se hizo

#### 10.1 README.md
Secciones incluidas:
- Descripción y características
- Requisitos de instalación
- Instrucciones paso a paso
- Documentación de endpoints
- Credenciales de prueba
- Flujo de uso con ejemplos
- Estructura del proyecto
- Configuración de Stripe
- Manejo de errores

#### 10.2 .env.example
Variables documentadas:
- Configuración de la aplicación
- Base de datos MySQL
- JWT (secret y TTL)
- Stripe (keys y webhook secret)

### Conceptos clave
- **.env.example**: Plantilla de variables de entorno. Otros desarrolladores la copian a .env y la completan.
- **README**: Primera impresión del proyecto. Debe ser claro y completo.

---

## Refactorización SOLID

### Objetivo
Aplicar principios SOLID para mejorar la arquitectura del código.

### Problemas identificados
1. **AuthController** tenía lógica de negocio (creación de usuarios, generación de tokens)
2. **PaymentServiceInterface** dependía de tipos de Stripe (PaymentIntent)
3. **ProductController** accedía directamente al modelo (inconsistencia)
4. **Form Requests** tenían mensajes de validación duplicados

### Refactorización 1: AuthService

**Antes:**
```php
class AuthController {
    public function register() {
        $user = User::create([...]);
        $token = JWTAuth::fromUser($user);
        // ...
    }
}
```

**Después:**
```php
class AuthService implements AuthServiceInterface {
    public function register(array $data): array {
        $user = User::create([...]);
        $token = JWTAuth::fromUser($user);
        return ['user' => $user, 'token' => $token, ...];
    }
}

class AuthController {
    public function register(RegisterRequest $request) {
        $result = $this->authService->register($request->validated());
        return response()->json([...$result]);
    }
}
```

**Principio aplicado:** Single Responsibility - AuthService maneja autenticación, AuthController solo HTTP.

### Refactorización 2: PaymentServiceInterface sin Stripe

**Antes:**
```php
interface PaymentServiceInterface {
    public function createPaymentIntent(Order $order): PaymentIntent; // Depende de Stripe
}
```

**Después:**
```php
interface PaymentServiceInterface {
    public function createPayment(Order $order): array; // Retorna array genérico
    public function getPaymentStatus(string $paymentId): array;
}
```

**Principio aplicado:** Dependency Inversion - La interfaz no depende de implementaciones específicas.

### Refactorización 3: ProductService

**Antes:**
```php
class ProductController {
    public function index() {
        $products = Product::where('is_active', true)->paginate(15);
    }
}
```

**Después:**
```php
class ProductService implements ProductServiceInterface {
    public function getActiveProducts(int $perPage = 15) {
        return Product::where('is_active', true)->orderBy('name')->paginate($perPage);
    }
}

class ProductController {
    public function index() {
        $products = $this->productService->getActiveProducts();
    }
}
```

**Principio aplicado:** Consistencia - Todos los controllers usan servicios.

### Refactorización 4: Mensajes centralizados

**Antes:**
```php
class StoreProductRequest {
    public function messages() {
        return ['sku.required' => 'El SKU es obligatorio.', ...];
    }
}

class UpdateProductRequest {
    public function messages() {
        return ['sku.unique' => 'Este SKU ya está registrado.', ...]; // Duplicado
    }
}
```

**Después:**
```php
trait HasValidationMessages {
    protected function getProductMessages(): array {
        return ['sku.required' => 'El SKU es obligatorio.', ...];
    }
}

class StoreProductRequest {
    use HasValidationMessages;
    public function messages() { return $this->getProductMessages(); }
}
```

**Principio aplicado:** Reutilización - Mensajes definidos una vez, usados en múltiples clases.

### Estructura final después de SOLID

```
app/
├── Contracts/                          # 5 interfaces
│   ├── AuthServiceInterface.php
│   ├── OrderServiceInterface.php
│   ├── PaymentServiceInterface.php
│   ├── ProductServiceInterface.php
│   └── TransactionServiceInterface.php
├── Services/                           # 6 servicios
│   ├── AuthService.php
│   ├── OrderService.php
│   ├── PaymentService.php              # Orquestador
│   ├── ProductService.php
│   ├── StripeService.php               # Solo Stripe
│   └── TransactionService.php          # Solo BD
├── Traits/
│   └── HasValidationMessages.php
└── Http/
    ├── Controllers/                    # Thin controllers
    └── Requests/                       # Usan trait
```

### Principios SOLID aplicados

| Principio | Descripción | Ejemplo en el proyecto |
|-----------|-------------|------------------------|
| **S** - Single Responsibility | Cada clase tiene una sola razón para cambiar | AuthService solo maneja autenticación |
| **O** - Open/Closed | Puedes extender sin modificar | Puedes agregar otro gateway de pago sin tocar controllers |
| **L** - Liskov Substitution | Las implementaciones son intercambiables | StripeService puede reemplazarse por otro PaymentServiceInterface |
| **I** - Interface Segregation | Interfaces específicas, no generales | Cada servicio tiene su propia interfaz |
| **D** - Dependency Inversion | Depender de abstracciones, no implementaciones | Controllers dependen de interfaces, no de servicios concretos |

---

## Resumen de Endpoints

| Categoría | Método | Endpoint | Auth | Descripción |
|-----------|--------|----------|------|-------------|
| Auth | POST | /api/auth/register | No | Registrar usuario |
| Auth | POST | /api/auth/login | No | Iniciar sesión |
| Auth | POST | /api/auth/logout | Sí | Cerrar sesión |
| Auth | GET | /api/auth/me | Sí | Usuario actual |
| Auth | POST | /api/auth/refresh | Sí | Renovar token |
| Products | GET | /api/products | No | Listar productos |
| Products | GET | /api/products/{id} | No | Ver producto |
| Products | POST | /api/products | Sí | Crear producto |
| Products | PUT | /api/products/{id} | Sí | Actualizar producto |
| Products | DELETE | /api/products/{id} | Sí | Eliminar producto |
| Orders | GET | /api/orders | Sí | Listar órdenes |
| Orders | GET | /api/orders/{id} | Sí | Ver orden |
| Orders | POST | /api/orders | Sí | Crear orden |
| Payments | POST | /api/payments/create-intent/{order} | Sí | Crear PaymentIntent |
| Payments | POST | /api/payments/confirm | Sí | Confirmar pago |

---

## Comandos Útiles

```bash
# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# Migrar y poblar base de datos
php artisan migrate:fresh --seed

# Generar documentación Swagger
php artisan l5-swagger:generate

# Iniciar servidor
php artisan serve

# Ver rutas
php artisan route:list

# Limpiar caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## Credenciales de Prueba

| Usuario | Email | Password | Rol |
|---------|-------|----------|-----|
| Admin | admin@example.com | password123 | admin |
| Cliente 1 | john@example.com | password123 | customer |
| Cliente 2 | jane@example.com | password123 | customer |
| Cliente 3 | carlos@example.com | password123 | customer |
| Cliente 4 | maria@example.com | password123 | customer |

---

## Tarjetas de Prueba Stripe

| Número | Descripción |
|--------|-------------|
| 4242 4242 4242 4242 | Pago exitoso |
| 4000 0000 0000 0002 | Pago rechazado |

Usar cualquier fecha futura y CVC de 3 dígitos.

---

## Documentación Interactiva

Una vez iniciado el servidor, acceder a:
```
http://localhost:8000/api/documentation
```

Desde Swagger UI puedes:
- Ver todos los endpoints documentados
- Probar los endpoints directamente
- Autenticarte con el botón "Authorize"
- Ver schemas de request/response

---

## Preguntas Frecuentes

### ¿Cómo cambio la contraseña de un usuario?
```bash
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $user->password = bcrypt('nueva_password');
>>> $user->save();
```

### ¿Cómo genero un nuevo token JWT manualmente?
```bash
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $token = auth()->login($user);
>>> echo $token;
```

### ¿Cómo reseteo completamente la base de datos?
```bash
php artisan migrate:fresh --seed
```

### ¿Dónde están las Stripe keys?
En el archivo `.env`:
```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
```

---

## Cambio de Paquete JWT

### Contexto
Inicialmente se instaló `php-open-source-saver/jwt-auth` v2.9.3, pero posteriormente se cambió a `tymon/jwt-auth` v2.3.0 para alinearse con el contenido del bootcamp.

### Proceso de Migración

1. **Desinstalación del paquete anterior:**
   ```bash
   composer remove php-open-source-saver/jwt-auth
   ```

2. **Instalación de tymon/jwt-auth:**
   ```bash
   composer require tymon/jwt-auth
   ```
   - Se instaló la versión v2.3.0, compatible con Laravel 13 y PHP 8.5

3. **Actualización de namespaces en el código:**
   - `app/Services/AuthService.php`: Cambiar `PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth` a `Tymon\JWTAuth\Facades\JWTAuth`
   - `app/Models/User.php`: Cambiar `PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject` a `Tymon\JWTAuth\Contracts\JWTSubject`

4. **Actualización de configuración:**
   - `config/jwt.php`: Actualizar los providers de `PHPOpenSourceSaver\...` a `Tymon\JWTAuth\...`

5. **Regeneración de clave secreta:**
   ```bash
   php artisan jwt:secret --force
   ```

### Resultado
Todos los endpoints de autenticación funcionaron correctamente con el nuevo paquete:
- ✅ Login
- ✅ Register
- ✅ Logout
- ✅ Refresh token
- ✅ Obtener usuario autenticado

### Nota
Ambos paquetes tienen la misma API y funcionalidad. `php-open-source-saver/jwt-auth` es un fork mantenido de `tymon/jwt-auth` para versiones más recientes de Laravel. La decisión de cambiar fue para mantener consistencia con el contenido del bootcamp.

---

## Cambio en el Enfoque de Autenticación

### Contexto
Inicialmente se usó `JWTAuth::attempt()` directamente en el `AuthService`, pero posteriormente se cambió a `auth('api')->attempt()` para alinearse con las convenciones de Laravel y el contenido del bootcamp.

### Diferencia entre los enfoques

#### Enfoque inicial: `JWTAuth::attempt()`
```php
// app/Services/AuthService.php
use Tymon\JWTAuth\Facades\JWTAuth;

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
```

**Características:**
- Llama directamente al facade del paquete JWT
- No pasa por el sistema de guards de Laravel
- Más acoplado al paquete específico

#### Enfoque actual: `auth('api')->attempt()`
```php
// app/Services/AuthService.php
public function login(string $email, string $password): ?array
{
    $credentials = ['email' => $email, 'password' => $password];

    if (!$token = auth('api')->attempt($credentials)) {
        return null;
    }

    return [
        'user' => auth('api')->user(),
        'token' => $token,
        'token_type' => 'bearer',
        'expires_in' => auth('api')->factory()->getTTL() * 60,
    ];
}
```

**Características:**
- Usa el guard `api` configurado en `config/auth.php`
- Más idiomático de Laravel
- Si cambias de JWT a otro driver (ej: Sanctum), solo cambias el guard
- `auth('api')->user()` obtiene el usuario autenticado
- `auth('api')->factory()->getTTL()` obtiene el TTL del config

### Cambios realizados en AuthService

| Método | Antes | Después |
|--------|-------|---------|
| `register()` | `JWTAuth::fromUser($user)` | `auth('api')->login($user)` |
| `login()` | `JWTAuth::attempt($credentials)` | `auth('api')->attempt($credentials)` |
| `logout()` | `JWTAuth::invalidate(JWTAuth::getToken())` | `auth('api')->logout()` |
| `refresh()` | `JWTAuth::refresh(JWTAuth::getToken())` | `auth('api')->refresh()` |
| `getUser()` | `auth()->user()` | `auth('api')->user()` |
| `expires_in` | `config('jwt.ttl') * 60` | `auth('api')->factory()->getTTL() * 60` |

### Ventajas del nuevo enfoque

1. **Sigue las convenciones de Laravel**: Usa el sistema de guards estándar
2. **Más flexible**: Si cambias de paquete JWT, no tienes que cambiar todo el código
3. **Más fácil de testear**: Puedes mockear el guard fácilmente
4. **Consistente con el bootcamp**: Usa el mismo enfoque que se enseña en clase

### Resultado
Todos los endpoints de autenticación funcionan correctamente con el nuevo enfoque:
- ✅ Login con `auth('api')->attempt()`
- ✅ Register con `auth('api')->login()`
- ✅ Logout con `auth('api')->logout()`
- ✅ Refresh con `auth('api')->refresh()`
- ✅ Obtener usuario con `auth('api')->user()`

---

## Eliminación de Paginación en Endpoints

### Contexto
Inicialmente, los endpoints `GET /api/products` y `GET /api/orders` retornaban resultados paginados usando el método `paginate()` de Laravel. Posteriormente se decidió simplificar estos endpoints para que retornen arrays simples sin paginación.

### Cambios realizados

#### 1. Interfaces (Contracts)

**Antes:**
```php
// app/Contracts/ProductServiceInterface.php
public function getActiveProducts(int $perPage = 15);

// app/Contracts/OrderServiceInterface.php
public function getUserOrders(User $user, int $perPage = 15);
```

**Después:**
```php
// app/Contracts/ProductServiceInterface.php
public function getActiveProducts();

// app/Contracts/OrderServiceInterface.php
public function getUserOrders(User $user);
```

#### 2. Servicios

**Antes:**
```php
// app/Services/ProductService.php
public function getActiveProducts(int $perPage = 15)
{
    return Product::where('is_active', true)
        ->orderBy('name')
        ->paginate($perPage);
}

// app/Services/OrderService.php
public function getUserOrders(User $user, int $perPage = 15)
{
    return $user->orders()
        ->with(['items.product', 'payment'])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
}
```

**Después:**
```php
// app/Services/ProductService.php
public function getActiveProducts()
{
    return Product::where('is_active', true)
        ->orderBy('name')
        ->get();
}

// app/Services/OrderService.php
public function getUserOrders(User $user)
{
    return $user->orders()
        ->with(['items.product', 'payment'])
        ->orderBy('created_at', 'desc')
        ->get();
}
```

#### 3. Documentación Swagger

**Antes:**
```php
// app/Http/Controllers/ProductController.php
#[OA\Get(
    path: '/products',
    description: 'Retorna una lista paginada de productos activos',
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', ...),
        new OA\Parameter(name: 'per_page', in: 'query', ...),
    ],
    responses: [
        new OA\Response(
            response: 200,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'data', type: 'array', ...),
                    new OA\Property(property: 'total', type: 'integer'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                ]
            )
        ),
    ]
)]

// app/Http/Controllers/OrderController.php
#[OA\Get(
    path: '/orders',
    description: 'Retorna una lista paginada de órdenes del usuario autenticado',
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', ...),
    ],
    responses: [
        new OA\Response(
            response: 200,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'data', type: 'array', ...),
                    new OA\Property(property: 'total', type: 'integer'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                ]
            )
        ),
    ]
)]
```

**Después:**
```php
// app/Http/Controllers/ProductController.php
#[OA\Get(
    path: '/products',
    description: 'Retorna una lista de productos activos',
    responses: [
        new OA\Response(
            response: 200,
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/Product')
            )
        ),
    ]
)]

// app/Http/Controllers/OrderController.php
#[OA\Get(
    path: '/orders',
    description: 'Retorna una lista de órdenes del usuario autenticado',
    responses: [
        new OA\Response(
            response: 200,
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/Order')
            )
        ),
    ]
)]
```

### Diferencia en las respuestas

**Antes (con paginación):**
```json
{
  "current_page": 1,
  "data": [
    { "id": 1, "name": "Producto 1", ... },
    { "id": 2, "name": "Producto 2", ... }
  ],
  "total": 12,
  "per_page": 15,
  "last_page": 1
}
```

**Después (sin paginación):**
```json
[
  { "id": 1, "name": "Producto 1", ... },
  { "id": 2, "name": "Producto 2", ... }
]
```

### Razón del cambio
- Simplificar la estructura de respuesta
- Facilitar el consumo de la API en etapas iniciales
- Reducir complejidad innecesaria cuando el volumen de datos es bajo

### Archivos modificados
- `app/Contracts/ProductServiceInterface.php`
- `app/Contracts/OrderServiceInterface.php`
- `app/Services/ProductService.php`
- `app/Services/OrderService.php`
- `app/Http/Controllers/ProductController.php` (documentación Swagger)
- `app/Http/Controllers/OrderController.php` (documentación Swagger)

### Resultado
Los endpoints ahora retornan arrays simples sin metadatos de paginación, facilitando el consumo de la API.

---

## Próximos Pasos (Opcional)

Si quieres continuar mejorando el proyecto:

1. **Webhooks de Stripe**: Implementar endpoint para recibir notificaciones de Stripe
2. **Tests**: Escribir tests unitarios y de integración
3. **Roles y Permisos**: Implementar sistema de autorización más granular
4. **Rate Limiting**: Proteger endpoints contra abuso
5. **CORS**: Configurar políticas de cross-origin
6. **Logging**: Implementar logs estructurados
7. **Caching**: Cachear consultas frecuentes
8. **Queue**: Procesar emails y tareas pesadas en background
9. **File Upload**: Permitir subir imágenes de productos
10. **Search**: Implementar búsqueda full-text en productos

---

## Recursos Adicionales

- [Documentación Laravel](https://laravel.com/docs)
- [Documentación JWT Auth](https://jwt-auth.readthedocs.io/)
- [Documentación Stripe](https://stripe.com/docs)
- [Documentación L5 Swagger](https://github.com/DarkaOnLine/L5-Swagger)
- [Principios SOLID](https://blog.cleancoder.com/uncle-bob/2020/10/18/Solid-Relevance.html)

---

*Documento generado como parte del desarrollo del proyecto E-commerce API.*
