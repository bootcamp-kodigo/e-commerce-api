## Descripción: 

Diseñar y desarrollar APIs RESTful seguras con Laravel 12, implementando autenticación robusta, integración con pasarelas de pago como Stripe, documentación profesional mediante Swagger/OpenAPI, y gestión de bases de datos relacionales con MySQL, aplicando las mejores prácticas de arquitectura, validación de datos y manejo de errores en aplicaciones de comercio electrónico.

## Título de la tarea: API de E-commerce Segura con Swagger Completo

### 📚 Caso de Estudio
Desarrollarás una **API REST** para un e-commerce básico que permita gestionar las operaciones fundamentales de una tienda en línea.

El sistema debe permitir la administración de:

* Clientes registrados.
* Un catálogo de productos.
* Procesamiento de compras mediante la pasarela de pago **Stripe**.

El flujo del sistema debe cubrir todo el proceso desde que un cliente se registra, navega por el catálogo de productos, hasta que realiza una compra y procesa el pago de forma segura.

La API debe estar completamente documentada mediante **Swagger/OpenAPI**, permitiendo que aplicaciones frontend o móviles puedan integrarse fácilmente con los servicios desarrollados.

Este proyecto simula un escenario real de desarrollo backend donde se requiere crear servicios seguros, escalables y correctamente documentados para sistemas de comercio electrónico.

### ⚙️ Indicaciones
#### Requisitos Técnicos
1. **Framework y Versión:** Utilizar Laravel 12 con PHP 8.2 o superior.
2. **Base de Datos:** MySQL con las siguientes tablas principales (la estructura puede modificarse si es necesario):
    * users (clientes del sistema)
    * products (catálogo de productos)
    * orders (órdenes de compra)
    * order_items (detalle de productos por orden)
    * payments (registro de transacciones de Stripe)
3. **Autenticación:** Implementar autenticación segura mediante tokens JWT.
4. **Documentación:** Integrar Swagger/OpenAPI utilizando el paquete darkaonline/l5-swagger o similar, documentando todos los endpoints.
5. **Pasarela de Pago:** Integrar Stripe utilizando el paquete oficial stripe/stripe-php.
6. **Funcionalidades mínimas:**
    * CRUD completo de productos (con autenticación para crear, editar y eliminar).
    * Registro y autenticación de usuarios/clientes.
    * Listado público de productos.
    * Creación de órdenes de compra.
    * Procesamiento de pagos con Stripe.
    * Consulta del historial de compras por usuario.
7. **Validaciones:** Implementar validaciones usando Form Requests de Laravel.
8. **Manejo de errores:** Gestionar respuestas de error consistentes y descriptivas en formato JSON.
9. **Variables de entorno:** Configurar correctamente credenciales de Stripe y base de datos en el archivo .env.
10. **Seeders:** Crear seeders para poblar la base de datos con productos de ejemplo.

### 📦 Entregable

Repositorio público en GitHub que contenga:

* Código completo del proyecto Laravel 12.
* Archivo **README.md** con instrucciones claras de instalación y configuración.
* Documentación de endpoints accesible mediante **Swagger UI** (ruta **/api/documentation**).
* Migraciones de base de datos.
* Seeders con datos de ejemplo.
* Archivo **.env.example** con las variables necesarias documentadas.

**Formato de entrega:** Enlace al repositorio público de GitHub.

### Criterios de calificación

* API RESTful bien estructurada con controladores, modelos y rutas organizados siguiendo convenciones de Laravel. Código limpio y modular.
* Todas las tablas creadas correctamente con relaciones bien definidas, migraciones limpias, índices apropiados y seeders funcionales.
* Documentación completa y detallada de todos los endpoints con ejemplos, esquemas de request/response, códigos de estado y autenticación.
* Validaciones completas en todos los endpoints con Form Requests, mensajes de error descriptivos y códigos HTTP apropiados
* Integración completa de Stripe con manejo de pagos exitosos y fallidos, webhooks, registro de transacciones y manejo robusto de errores.
