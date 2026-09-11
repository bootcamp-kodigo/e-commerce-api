<?php

namespace App\Services;

use App\Contracts\ProductServiceInterface;
use App\Models\Product;

class ProductService implements ProductServiceInterface
{
    public function getActiveProducts()
    {
        return Product::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getProduct(int $id): ?Product
    {
        return Product::find($id);
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function updateProduct(int $id, array $data): ?Product
    {
        $product = Product::find($id);

        if (!$product) {
            return null;
        }

        $product->update($data);

        return $product;
    }

    public function deleteProduct(int $id): bool
    {
        $product = Product::find($id);

        if (!$product) {
            return false;
        }

        return $product->delete();
    }
}
