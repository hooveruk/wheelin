<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Employee;
use App\Schedule;

class SchedulerProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('config', function() {


        }
        );
    }
}
