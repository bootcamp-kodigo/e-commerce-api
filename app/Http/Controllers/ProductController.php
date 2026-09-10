<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Productos',
    description: 'Endpoints para gestión del catálogo de productos'
)]
class ProductController extends Controller
{
    #[OA\Get(
        path: '/products',
        summary: 'Listar productos',
        description: 'Retorna una lista paginada de productos activos',
        operationId: 'getProducts',
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Número de página',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Productos por página',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de productos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Product')
                        ),
                        new OA\Property(property: 'total', type: 'integer', example: 12),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'last_page', type: 'integer', example: 1),
                    ]
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->paginate(15);

        return response()->json($products);
    }

    #[OA\Post(
        path: '/products',
        summary: 'Crear producto',
        description: 'Crea un nuevo producto en el catálogo',
        operationId: 'createProduct',
        tags: ['Productos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sku', 'name', 'price', 'stock'],
                properties: [
                    new OA\Property(property: 'sku', type: 'string', example: 'LAPTOP-001'),
                    new OA\Property(property: 'name', type: 'string', example: 'Laptop Gaming Pro'),
                    new OA\Property(property: 'description', type: 'string', example: 'Laptop de alto rendimiento con procesador Intel i7'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 1299.99),
                    new OA\Property(property: 'stock', type: 'integer', example: 25),
                    new OA\Property(property: 'image_url', type: 'string', format: 'url', example: 'https://example.com/images/laptop.jpg'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Producto creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Producto creado exitosamente.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación'
            ),
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'message' => 'Producto creado exitosamente.',
            'data' => $product,
        ], 201);
    }

    #[OA\Get(
        path: '/products/{product}',
        summary: 'Obtener producto',
        description: 'Retorna los detalles de un producto específico',
        operationId: 'getProduct',
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(
                name: 'product',
                in: 'path',
                required: true,
                description: 'ID del producto',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalles del producto',
                content: new OA\JsonContent(ref: '#/components/schemas/Product')
            ),
            new OA\Response(
                response: 404,
                description: 'Producto no encontrado'
            ),
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    #[OA\Put(
        path: '/products/{product}',
        summary: 'Actualizar producto',
        description: 'Actualiza los datos de un producto existente',
        operationId: 'updateProduct',
        tags: ['Productos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'product',
                in: 'path',
                required: true,
                description: 'ID del producto',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'sku', type: 'string', example: 'LAPTOP-001'),
                    new OA\Property(property: 'name', type: 'string', example: 'Laptop Gaming Pro'),
                    new OA\Property(property: 'description', type: 'string', example: 'Laptop de alto rendimiento'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 1299.99),
                    new OA\Property(property: 'stock', type: 'integer', example: 25),
                    new OA\Property(property: 'image_url', type: 'string', format: 'url', example: 'https://example.com/images/laptop.jpg'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Producto actualizado exitosamente.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 404,
                description: 'Producto no encontrado'
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación'
            ),
        ]
    )]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json([
            'message' => 'Producto actualizado exitosamente.',
            'data' => $product,
        ]);
    }

    #[OA\Delete(
        path: '/products/{product}',
        summary: 'Eliminar producto',
        description: 'Elimina un producto del catálogo (soft delete)',
        operationId: 'deleteProduct',
        tags: ['Productos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'product',
                in: 'path',
                required: true,
                description: 'ID del producto',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto eliminado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Producto eliminado exitosamente.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 404,
                description: 'Producto no encontrado'
            ),
        ]
    )]
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente.',
        ]);
    }
}
