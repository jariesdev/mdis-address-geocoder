<?php

namespace App\Providers;

use App\Jobs\BatchCustomerCoordinateSearch;
use App\Repositories\CustomerInterface;
use App\Repositories\CustomerRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use PDO;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        CustomerInterface::class => CustomerRepository::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Connection::resolverFor('mdbtools', function ($connection, $database, $prefix, $config) {
            $driver = Arr::get($config, 'driver');
            $database = Arr::get($config, 'database');
            $mdbPath = $database;
            $pdo = new PDO("odbc:Driver=$driver;DBQ=$mdbPath;");
            return new Connection($pdo);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        RateLimiter::for('nominatim', function (BatchCustomerCoordinateSearch $job) {
            return Limit::perMinute(1)->by($job->customerImport->id);
        });
    }
}
