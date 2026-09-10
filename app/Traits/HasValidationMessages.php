<?php

namespace App\Traits;

trait HasValidationMessages
{
    protected function getProductMessages(): array
    {
        return [
            'sku.required' => 'El SKU es obligatorio.',
            'sku.unique' => 'Este SKU ya está registrado.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio debe ser mayor a 0.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
            'image_url.url' => 'La URL de la imagen no es válida.',
        ];
    }

    protected function getAuthMessages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }

    protected function getOrderMessages(): array
    {
        return [
            'items.required' => 'La orden debe contener al menos un producto.',
            'items.array' => 'Los items deben ser un arreglo válido.',
            'items.min' => 'La orden debe contener al menos un producto.',
            'items.*.product_id.required' => 'El ID del producto es obligatorio.',
            'items.*.product_id.exists' => 'Uno o más productos no existen.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'items.*.quantity.min' => 'La cantidad mínima es 1.',
            'shipping_address.required' => 'La dirección de envío es obligatoria.',
            'shipping_address.max' => 'La dirección no puede exceder 500 caracteres.',
        ];
    }
}
