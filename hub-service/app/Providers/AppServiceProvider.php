<?php

namespace App\Providers;

use App\Services\Cache\CacheService;
use App\Services\Checklist\ChecklistService;
use App\Services\Checklist\GermanyChecklist;
use App\Services\Checklist\USAChecklist;
use App\Services\EventProcessors\EmployeeCreatedProcessor;
use App\Services\EventProcessors\EmployeeDeletedProcessor;
use App\Services\EventProcessors\EmployeeUpdatedProcessor;
use App\Services\EventProcessors\EventProcessorRegistry;
use App\Services\HrServiceClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheService::class);
        $this->app->singleton(HrServiceClient::class);

        $this->app->singleton(ChecklistService::class, function ($app) {
            $service = new ChecklistService(
                $app->make(CacheService::class),
                $app->make(HrServiceClient::class)
            );

            $service->registerChecklist(new USAChecklist());
            $service->registerChecklist(new GermanyChecklist());

            return $service;
        });

        $this->app->singleton(EventProcessorRegistry::class, function ($app) {
            $registry = new EventProcessorRegistry();
            $cacheService = $app->make(CacheService::class);

            $registry->register(new EmployeeCreatedProcessor($cacheService));
            $registry->register(new EmployeeUpdatedProcessor($cacheService));
            $registry->register(new EmployeeDeletedProcessor($cacheService));

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}
