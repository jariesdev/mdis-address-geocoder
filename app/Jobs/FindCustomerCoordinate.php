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
use Illuminate\Support\Facades\Log;

class FindCustomerCoordinate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var CustomerImport
     */
    private $customerImport;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CustomerImport $customerImport)
    {
        $this->customerImport = $customerImport;
    }

    public function middleware()
    {
        return [
            new RateLimited('nominatim'),
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customerImport->update([
            'status' => 'coordinate-searching',
        ]);

        $cacheKey = "imports.{$this->customerImport->id}.success-counter";
        Cache::put($cacheKey, 0);
        $cacheElapseKey = "imports.{$this->customerImport->id}.success-elapse-counter";
        Cache::put($cacheElapseKey, 0);

        Customer::query()
            ->where('customer_import_id', $this->customerImport->id)
            ->where(function ($builder){
                $builder
                    ->whereNull('latitude')
                    ->orWhereNull('longitude');
            })
            ->chunk(1000, function (Collection $customers) use ($cacheElapseKey, $cacheKey) {
                $customers->each(function (Customer $customer) use ($cacheElapseKey, $cacheKey) {
                    Cache::increment($cacheElapseKey);
                    try {
                        $coordinate = $this->findCustomerCoordinate($customer);
                        if($coordinate) {
                            Cache::increment($cacheKey);
                            $customer->update([
                                'latitude' => $coordinate->latitude,
                                'longitude' => $coordinate->longitude,
                                'geocoder_data' => $coordinate->data,
                            ]);
                        }
                    }catch (\Throwable $exception) {
                        $customer->update([
                            'geocoder_data' => json_encode([
                                'success' => false,
                                'message' => $exception->getMessage(),
                            ]),
                        ]);
                    }
                });
            });

        $this->customerImport->update([
            'status' => 'coordinate-located',
            'success_count' => Cache::pull($cacheKey, 0),
        ]);
    }

    private function findCustomerCoordinate(Customer $customer)
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
