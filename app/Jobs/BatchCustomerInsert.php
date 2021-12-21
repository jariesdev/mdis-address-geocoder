<?php

namespace App\Jobs;

use App\Models\CustomerImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use SplFileObject;

class BatchCustomerInsert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var CustomerImport
     */
    private $customerImport;
    /**
     * @var string
     */
    private $csvFile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $csvFile, CustomerImport $customerImport)
    {
        $this->customerImport = $customerImport;
        $this->csvFile = $csvFile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $counterCacheKey = "imports.{$this->customerImport->id}.record-counter";
        if (!Cache::has($counterCacheKey)) {
            Cache::put($counterCacheKey, 0, now()->addDay());
        }

        $query = sprintf(
            "LOAD DATA INFILE '%s' INTO TABLE `customers` ".
            'FIELDS TERMINATED BY \',\' '.
            'OPTIONALLY ENCLOSED BY \'\"\' '.
            'LINES TERMINATED BY \'\n\' '.
            "(refid, street, barangay_name, municipality_name, province_name, region, island) ".
            "SET customer_import_id = %d, source_db = '%s', source_table = '%s'",
            $this->csvFile,
            $this->customerImport->id,
            basename($this->customerImport->file),
            $this->customerImport->table_name
        );
        $affected = DB::connection()->getPdo()->exec($query);
        Cache::increment($counterCacheKey, $affected);

        $batchCacheKey = "imports.{$this->customerImport->id}.import-batch-remaining";
        Cache::decrement($batchCacheKey);
        if (Cache::get($batchCacheKey, 0) === 0) {
            $this->customerImport->update([
                'total' => Cache::pull($counterCacheKey, 0),
                'status' => 'imported',
            ]);
        }

        File::delete($this->csvFile);
    }

    private function getRowCount(string $csvFile): int
    {
        $file = new SplFileObject($csvFile);
        $file->seek(PHP_INT_MAX);
        return $file->key() + 1;
    }
}
