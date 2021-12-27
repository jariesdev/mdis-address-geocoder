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
use League\Csv\Reader;
use League\Csv\Writer;

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

        $this->onQueue('heavy');
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \League\Csv\CannotInsertRecord
     */
    public function handle()
    {
        $this->customerImport->update([
            'status' => 'generating-csv',
        ]);

        $filename = storage_path("exports/{$this->customerImport->table_name}-{$this->customerImport->id}-location.csv");
        $csv = Writer::createFromPath($filename, 'w');
        $header = [
            'REFID',
            'STREET',
            'BARANGAYNAME',
            'MUNICIPALITYNAME',
            'PROVINCENAME',
            'REGION',
            'ISLAND',
            'LATITUDE',
            'LONGITUDE',
        ];
        $csv->insertOne($header);

        Customer::query()
            ->whereNotNull('latitude')
            ->where('customer_import_id', $this->customerImport->id)
            ->chunk(10000, function (Collection $customers) use ($csv) {
                $records = $customers->map(function (Customer $customer)  {
                    return $customer->only([
                        'refid',
                        'street',
                        'barangay_name',
                        'municipality_name',
                        'province_name',
                        'region',
                        'island',
                        'latitude',
                        'longitude',
                    ]);
                });
                $csv->insertAll($records);
            });

        $this->customerImport->update([
            'status' => 'completed',
            'csv_path' => "exports/{$this->customerImport->table_name}-{$this->customerImport->id}-location.csv",
        ]);
    }
}
