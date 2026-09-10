<?php

namespace App\Contracts;

use App\Models\Product;

interface ProductServiceInterface
{
    public function getActiveProducts(int $perPage = 15);

    public function getProduct(int $id): ?Product;

    public function createProduct(array $data): Product;

    public function updateProduct(int $id, array $data): ?Product;

    public function deleteProduct(int $id): bool;
}
