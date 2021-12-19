<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BatchCustomerInsert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Collection
     */
    private $customerData;
    /**
     * @var CustomerImport
     */
    private $customerImport;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $customerData, CustomerImport $customerImport)
    {
        $this->customerData = $customerData;
        $this->customerImport = $customerImport;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cacheKey = "imports.{$this->customerImport->id}.record-counter";
        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, 0, now()->addDay());
        }

        $this->customerData->each(function (array $data) use ($cacheKey) {
            Customer::query()->updateOrCreate(
                [
                    'refid' => Arr::pull($data, 'refid'),
                    'customer_import_id' => Arr::pull($data, 'customer_import_id'),
                ],
                $data
            );
            Cache::increment($cacheKey);
        });
    }
}
