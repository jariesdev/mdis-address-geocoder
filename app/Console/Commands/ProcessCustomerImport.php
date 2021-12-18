<?php

namespace App\Console\Commands;

use App\Jobs\ExportCustomerCSV;
use App\Jobs\FindCustomerCoordinate;
use App\Jobs\ImportMdb;
use App\Models\CustomerImport;
use Illuminate\Console\Command;

class ProcessCustomerImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:import {importId : Id from customer_imports table.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $importId = $this->argument('importId');
        if($customerImport = CustomerImport::query()->find($importId)) {
            dispatch(new ImportMdb(storage_path($customerImport->file), $customerImport->table_name, $customerImport))
                ->chain([
                    new FindCustomerCoordinate($customerImport),
                    new ExportCustomerCSV($customerImport),
                ]);
            $this->info("Importing {$customerImport->table_name} from {$customerImport->file}.");
        }

        return 0;
    }
}
