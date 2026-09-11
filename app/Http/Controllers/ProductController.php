<?php

namespace App\Http\Controllers;

use App\Contracts\ProductServiceInterface;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Productos',
    description: 'Endpoints para gestión del catálogo de productos'
)]
class ProductController extends Controller
{
    private ProductServiceInterface $productService;

    public function __construct(ProductServiceInterface $productService)
    {
        $this->productService = $productService;
    }

    #[OA\Get(
        path: '/products',
        summary: 'Listar productos',
        description: 'Retorna una lista de productos activos',
        operationId: 'getProducts',
        tags: ['Productos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de productos',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Product')
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $products = $this->productService->getActiveProducts();

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
        $product = $this->productService->createProduct($request->validated());

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
    public function show(int $product): JsonResponse
    {
        $productModel = $this->productService->getProduct($product);

        if (!$productModel) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        return response()->json($productModel);
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
    public function update(UpdateProductRequest $request, int $product): JsonResponse
    {
        $productModel = $this->productService->updateProduct($product, $request->validated());

        if (!$productModel) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Producto actualizado exitosamente.',
            'data' => $productModel,
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
    public function destroy(int $product): JsonResponse
    {
        $deleted = $this->productService->deleteProduct($product);

        if (!$deleted) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Producto eliminado exitosamente.',
        ]);
    }
}
