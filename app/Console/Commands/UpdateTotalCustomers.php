<?php

namespace App\Console\Commands;

use App\Models\CustomerImport;
use Illuminate\Console\Command;

class UpdateTotalCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:update-total';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update total count.';

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
        CustomerImport::query()
            ->each(function (CustomerImport $customerImport) {
                $customerImport->update([
                    'success_count' => $customerImport->customers()->whereNotNull('latitude')->count(),
                ]);
            });

        $this->info('updated');
        return 0;
    }
}
