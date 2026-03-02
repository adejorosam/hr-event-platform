<?php

namespace App\Providers;

use App\Models\Employee;
use App\Observers\EmployeeObserver;
use App\Services\EventPublisher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventPublisher::class, function () {
            return new EventPublisher();
        });
    }

    public function boot(): void
    {
        Employee::observe(EmployeeObserver::class);
    }
}
