<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BatchCustomerCoordinateSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Collection
     */
    private $customers;
    /**
     * @var CustomerImport
     */
    public $customerImport;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $customers, CustomerImport $customerImport)
    {
        $this->customers = $customers;
        $this->customerImport = $customerImport;
    }

    public function middleware()
    {
        return [
            new RateLimited('nominatim'),
        ];
    }

    public function retryUntil()
    {
        return now()->addMinutes(1);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $successCacheKey = "imports.{$this->customerImport->id}.success-counter";
        if (!Cache::has($successCacheKey)) {
            Cache::put($successCacheKey, 0);
        }
        $cacheElapseKey = "imports.{$this->customerImport->id}.success-elapse-counter";
        if (!Cache::has($cacheElapseKey)) {
            Cache::put($cacheElapseKey, 0);
        }

        $this->customers
            ->each(function (Customer $customer) use ($cacheElapseKey, $successCacheKey) {
                Cache::increment($cacheElapseKey);
                try {
                    $coordinate = $this->findCustomerCoordinate($customer);
                    if ($coordinate) {
                        Cache::increment($successCacheKey);
                        $customer->update([
                            'latitude' => $coordinate->latitude,
                            'longitude' => $coordinate->longitude,
                            'geocoder_data' => $coordinate->data,
                        ]);
                    }
                } catch (\Throwable $exception) {
                    $customer->update([
                        'geocoder_data' => json_encode([
                            'success' => false,
                            'message' => $exception->getMessage(),
                        ]),
                    ]);
                }
            });

        $batchCacheKey = "imports.{$this->customerImport->id}.coordinate-batch-search-remaining";
        Cache::decrement($batchCacheKey);
        if(Cache::get($batchCacheKey, 0) === 0) {
            $this->customerImport->update([
                'status' => 'coordinate-located',
                'success_count' => Cache::pull($successCacheKey, 0),
            ]);
        }
    }

    /**
     * @param  Customer  $customer
     * @return object|null
     */
    private function findCustomerCoordinate(Customer $customer): ?object
    {
        $url = config('nominatim.url').'/search';
        $result = Http::get($url, [
            'street' => $customer->street,
            'city' => $customer->municipality_name,
//            'state' => $customer->region,
            'country' => 'PH',
            'format' => 'json',
        ]);

        if (is_array($result->json()) && count($result->json()) > 0) {
            $r = Arr::first($result->json());
            return (object) [
                'latitude' => $r['lat'],
                'longitude' => $r['lon'],
                'data' => json_encode($r),
            ];
        } else {
            return null;
        }
    }
}
