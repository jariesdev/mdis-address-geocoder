<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

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
            new WithoutOverlapping('nominatim'),
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Customer::query()
            ->where('customer_import_id', $this->customerImport->id)
            ->chunk(1000, function (Collection $customers) {
                $customers->each(function (Customer $customer) {
                    $coordinate = $this->findCustomerCoordinate($customer);
                    if($coordinate) {
                        $customer->update([
                            'latitude' => $coordinate->latitude,
                            'longitude' => $coordinate->longitude,
                            'geocoder_data' => $coordinate->data,
                        ]);
                    }
                });
            });
    }

    private function findCustomerCoordinate(Customer $customer)
    {
        $url = config('nominatim.url');
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
