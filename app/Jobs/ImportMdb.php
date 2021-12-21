<?php

namespace App\Jobs;

use App\Models\CustomerImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use SplFileObject;
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
    public function __construct(CustomerImport $customerImport = null)
    {
        $this->mdbPath = storage_path($customerImport->file);
        $this->tableName = $customerImport->table_name;
        $this->customerImport = $customerImport;
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

        $csvFilename = sprintf("%s.csv", pathinfo($this->mdbPath, PATHINFO_FILENAME));
        $csvPath = pathinfo($this->mdbPath, PATHINFO_DIRNAME).'/'.$csvFilename;
        $process = Process::fromShellCommandline(sprintf(
            'mdb-export -eH %s %s > %s',
            $this->mdbPath,
            $this->tableName,
            $csvPath
        ));
        $process->run();
        if ($process->getExitCode() === 0) {
            $files = $this->splitCsv($csvPath);
            if (count($files) > 1) {
                File::delete($csvPath);
            }
            $this->importFromCsv($files);
        }

//        if($this->customerImport) {
//            $this->customerImport->update([
//                'total' =>  $this->ctr,
//                'status' => 'imported',
//            ]);
//        }
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

    private function importFromCsv(array $csvFiles)
    {
        $cacheKey = "imports.{$this->customerImport->id}.import-batch-remaining";
        Cache::put($cacheKey, 0, now()->addDay());
        foreach ($csvFiles as $file) {
            dispatch(new BatchCustomerInsert($file, $this->customerImport));
            Cache::increment($cacheKey);
        }
    }

    private function splitCsv(string $csvFile): array
    {
        $rowPerFile = 100000;
        $rowCount = $this->getRowCount($csvFile);

        if ($rowCount <= $rowPerFile) {
            return [$csvFile];
        }

        $srcFile = new SplFileObject($csvFile);
        $files = [];
        $fileCounter = 1;
        $filename = pathinfo($csvFile, PATHINFO_FILENAME);
        $destFile = new SplFileObject(storage_path("uploads/imports/{$filename}-{$fileCounter}.csv"), 'w+');
        foreach ($srcFile as $index => $line) {
            $destFile->fwrite($line);

            if ((($index + 1) % $rowPerFile) === 0) {
                $files[] = $destFile->getRealPath();
                $fileCounter += 1;
                $destFile = new SplFileObject(storage_path("uploads/imports/{$filename}-{$fileCounter}.csv"), 'w+');
            }
        }
        $files[] = $destFile->getRealPath();

        return $files;
    }

    private function getRowCount(string $csvFile): int
    {
        $file = new SplFileObject($csvFile);
        $file->seek(PHP_INT_MAX);
        return $file->key() + 1;
    }
}
