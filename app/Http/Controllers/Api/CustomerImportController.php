<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CustomerImportResource;
use App\Jobs\ExportCustomerCSV;
use App\Jobs\FindCustomerCoordinate;
use App\Jobs\ImportMdb;
use App\Models\CustomerImport;
use App\Repositories\CustomerInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class CustomerImportController extends Controller
{
    /**
     * @var CustomerInterface
     */
    private $customerRepository;

    /**
     * @param  CustomerInterface  $customerRepository
     */
    public function __construct(CustomerInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $imports = CustomerImport::all();
        return CustomerImportResource::collection($imports);
    }

    /**
     * @param  Request  $request
     * @return JsonResource
     */
    public function store(Request $request)
    {
        $filename = $request->post('file');
        $tableName = $request->post('table_name');

        $customerImport = CustomerImport::query()
            ->create([
                'table_name' => $tableName,
                'status' => 'pending',
            ]);

        $newFilename = "uploads/imports/{$customerImport->id}.{$filename}";
        File::move(storage_path("uploads/{$filename}"),
            storage_path("uploads/imports/{$customerImport->id}.{$filename}"));
        $customerImport->update([
            'file' => $newFilename,
        ]);

        dispatch(new ImportMdb($customerImport))
            ->chain([
                new FindCustomerCoordinate($customerImport),
            ]);

        return new JsonResource($customerImport);
    }

    /**
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function chunkUpload(Request $request)
    {
        if (empty($_FILES) || $_FILES['file']['error']) {
            die('{"OK": 0, "info": "Failed to move uploaded file."}');
        }

        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

        $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : $_FILES["file"]["name"];
        $filePath = storage_path("uploads/$fileName");

// Open temp file
        $out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
        if ($out) {
            // Read binary input stream and append it to temp file
            $in = @fopen($_FILES['file']['tmp_name'], "rb");

            if ($in) {
                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }
            } else {
                return response()->json([
                    'OK' => 0,
                    'info' => 'Failed to open input stream.',
                ]);
            }

            @fclose($in);
            @fclose($out);

            @unlink($_FILES['file']['tmp_name']);
        } else {
            return response()->json([
                'OK' => 0,
                'info' => 'Failed to open output stream.',
            ]);
        }

// Check if file has been uploaded
        $tablesNames = [];
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$filePath}.part", $filePath);
            $tablesNames = $this->getMdbTables($filePath);
        }

        return response()->json([
            'OK' => 1,
            'file' => $request->get('name'),
            'info' => 'Upload successful.',
            'tables' => $tablesNames,
        ]);
    }

    /**
     * @param  string  $filePath
     * @return array
     */
    private function getMdbTables(string $filePath)
    {
        $process = (new Process(['mdb-tables', $filePath]));
        $process->run();
        return explode(PHP_EOL, trim($process->getOutput()));
    }

    /**
     * @param  Request  $request
     * @param  CustomerImport  $customerImport
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function customers(Request $request, CustomerImport $customerImport)
    {
        $query = $customerImport->customers();
        if(filter_var($request->get('onlyEmptyCoordinates'), FILTER_VALIDATE_BOOLEAN)) {
            $query->where(function (Builder $builder) {
                $builder
                    ->whereNull('latitude')
                    ->orWhereNull('longitude');
            });
        }
        $customers = $query->simplePaginate($request->get('perPage', 15));
        return JsonResource::collection($customers);
    }

    /**
     * @param  Request  $request
     * @param  CustomerImport  $customerImport
     * @return JsonResource
     */
    public function generateCsv(Request $request, CustomerImport $customerImport)
    {
        dispatch(new ExportCustomerCSV($customerImport));
        return JsonResource::make($customerImport);
    }

    /**
     * @param  Request  $request
     * @param  CustomerImport  $customerImport
     * @return JsonResource
     */
    public function downloadCSV(Request $request, CustomerImport $customerImport)
    {
        return response()->download(storage_path($customerImport->csv_path));
    }

    public function locateCustomers(Request $request, CustomerImport $customerImport)
    {
        dispatch(new FindCustomerCoordinate($customerImport));
        return JsonResource::make($customerImport);
    }
}
