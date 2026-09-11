<?php

namespace App\Providers;

use App\Contracts\AuthServiceInterface;
use App\Contracts\OrderServiceInterface;
use App\Contracts\PaymentServiceInterface;
use App\Contracts\ProductServiceInterface;
use App\Contracts\TransactionServiceInterface;
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\StripeService;
use App\Services\TransactionService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
        $this->app->bind(PaymentServiceInterface::class, StripeService::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
    }

    public function boot(): void
    {
        //
    }
}
