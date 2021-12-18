<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ExportCustomerCSV implements ShouldQueue
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

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $filename = storage_path('test-export-'.time().'.csv');
        file_put_contents($filename, implode(',', [
            'REFID',
            'STREET',
            'BARANGAYNAME',
            'MUNICIPALITYNAME',
            'PROVINCENAME',
            'REGION',
            'ISLAND',
            'LATITUDE',
            'LONGITUDE',
        ]));

        Customer::query()
            ->where('customer_import_id', $this->customerImport->id)
            ->chunk(10000, function (Collection $customers) use ($filename) {
                $handle = fopen($filename, "a") or die("Unable to open file!");
                $customers->each(function (Customer $customer) use ($handle) {
                    fputcsv($handle, $customer->only([
                        'refid',
                        'street',
                        'barangay_name',
                        'municipality_name',
                        'province_name',
                        'region',
                        'island',
                        'latitude',
                        'longitude',
                    ]));
                });
            });
    }
}
