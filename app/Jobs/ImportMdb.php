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
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

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
    private $ctr = 0;

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

        $this->onQueue('heavy');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->customerImport) {
            $this->customerImport->update([
                'status' => 'importing',
            ]);
        }

        $process = Process::fromShellCommandline("mdb-export {$this->mdbPath} {$this->tableName} > {$this->mdbPath}.csv");
        $process->run();

        if ($process->getExitCode() === 0) {
            $this->importFromCsv("{$this->mdbPath}.csv");
        }

        if ($this->customerImport) {
            $this->customerImport->update([
                'total' => $this->ctr,
                'status' => 'imported',
            ]);
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

    private function importFromCsv(string $csvPath)
    {
        $this->ctr = 0;
        $cacheKey = "imports.{$this->customerImport->id}.record-counter";
        $file = fopen($csvPath, 'r');
        fgetcsv($file); // skip first line (headers)
        while (($line = fgetcsv($file)) !== false) {
            Customer::query()->updateOrCreate(
                [
                    'refid' => Arr::get($line, 0,)
                ],
                [
                    'street' => $this->cleanString(Arr::get($line, 1)),
                    'barangay_name' => $this->cleanString(Arr::get($line, 2)),
                    'municipality_name' => $this->cleanString(Arr::get($line, 3)),
                    'province_name' => $this->cleanString(Arr::get($line, 4)),
                    'region' => $this->cleanString(Arr::get($line, 5)),
                    'island' => $this->cleanString(Arr::get($line, 6)),
                    'source_db' => basename($this->mdbPath),
                    'source_table' => $this->tableName,
                    'source_index' => ++$this->ctr,
                    'customer_import_id' => optional($this->customerImport)->id,
                ]
            );
            Cache::increment($cacheKey);
        }
        Cache::forget($cacheKey);
        fclose($file);
    }
}
