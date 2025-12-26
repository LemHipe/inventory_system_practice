<?php

namespace App\Providers;

use App\Domain\Auth\Repositories\UserRepositoryInterface;
use App\Domain\Auth\Services\AuthService;
use Domain\Chat\Repositories\ChatRepositoryInterface;
use Domain\Chat\Repositories\MessageRepositoryInterface;
use Domain\Chat\Services\ChatService;
use App\Domain\Inventory\Repositories\InventoryRepositoryInterface;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\Warehouse\Repositories\WarehouseRepositoryInterface;
use App\Domain\Dispatch\Repositories\DispatchRepositoryInterface;
use App\Application\Services\WarehouseService;
use App\Application\Services\DispatchService;
use Illuminate\Support\ServiceProvider;
use App\Infrastructure\Persistence\EloquentUserRepository;
use Infrastructure\Chat\Repositories\EloquentChatRepository;
use Infrastructure\Chat\Repositories\EloquentMessageRepository;
use App\Infrastructure\Persistence\EloquentInventoryRepository;
use App\Infrastructure\Persistence\EloquentWarehouseRepository;
use App\Infrastructure\Persistence\EloquentDispatchRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(InventoryRepositoryInterface::class, EloquentInventoryRepository::class);
        $this->app->bind(ChatRepositoryInterface::class, EloquentChatRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, EloquentMessageRepository::class);

        $this->app->bind(AuthService::class, fn ($app) => new AuthService());

        $this->app->bind(InventoryService::class, fn ($app) => new InventoryService(
            $app->make(InventoryRepositoryInterface::class)
        ));

        $this->app->bind(ChatService::class, fn ($app) => new ChatService(
            $app->make(ChatRepositoryInterface::class),
            $app->make(MessageRepositoryInterface::class)
        ));

        $this->app->bind(WarehouseRepositoryInterface::class, EloquentWarehouseRepository::class);
        $this->app->bind(DispatchRepositoryInterface::class, EloquentDispatchRepository::class);

        $this->app->bind(WarehouseService::class, fn ($app) => new WarehouseService(
            $app->make(WarehouseRepositoryInterface::class)
        ));

        $this->app->bind(DispatchService::class, fn ($app) => new DispatchService(
            $app->make(DispatchRepositoryInterface::class),
            $app->make(InventoryRepositoryInterface::class)
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
