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
use Illuminate\Support\Facades\DB;
use PDO;

class ImportMdb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    private $mdbPath;
    /**
     * @var string
     */
    private $tableName;
    /**
     * @var CustomerImport
     */
    private $customerImport;

    /**
     * Create a new job instance.
     *
     * @param  string  $mdbPath
     * @param  string  $tableName
     */
    public function __construct(string $mdbPath, string $tableName, CustomerImport $customerImport = null)
    {
        $this->mdbPath = $mdbPath;
        $this->tableName = $tableName;
        $this->customerImport = $customerImport;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $connection = $this->getConnection();

        $sql = "SELECT * FROM {$this->tableName}";
        $results = odbc_exec($connection, $sql);

        $ctr = 0;
        while ($row = odbc_fetch_object($results)) {
            Customer::query()->updateOrCreate(
                [
                    'refid' => $row->REFID,
                ],
                [
                    'street' => $this->cleanString($row->STREET),
                    'barangay_name' => $this->cleanString($row->BARANGAYNAME),
                    'municipality_name' => $this->cleanString($row->MUNICIPALITYNAME),
                    'province_name' => $this->cleanString($row->PROVINCENAME),
                    'region' => $this->cleanString($row->REGION),
                    'island' => $this->cleanString($row->ISLAND),
                    'source_db' => basename($this->mdbPath),
                    'source_table' => $this->tableName,
                    'source_index' => $ctr++,
                    'customer_import_id' => optional($this->customerImport)->id,
                ]
            );
            if($ctr > 1000) { break; }
        }
    }

    protected function getConnection()
    {
        $driver = 'MDBTools';
        return odbc_connect("Driver=$driver; DBQ=$this->mdbPath;", null, null);;
    }

    protected function cleanString(string $str = null)
    {
        $str = preg_replace('/[ ]{2,}/', ' ', $str);
        $str = preg_replace('/[\x00-\x1F\x7F]/', '', $str);
        return $str;
    }
}
