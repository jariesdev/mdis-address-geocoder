<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ExportCustomerCSV;
use App\Jobs\FindCustomerCoordinate;
use App\Jobs\ImportMdb;
use App\Models\CustomerImport;
use App\Repositories\CustomerInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;

class CustomerImportController extends Controller
{
    /**
     * @var CustomerInterface
     */
    private $customerRepository;

    public function __construct(CustomerInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function index()
    {
        $imports = CustomerImport::all();
        return JsonResource::collection($imports);
    }

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
        File::move(storage_path("uploads/{$filename}"), storage_path("uploads/imports/{$customerImport->id}.{$filename}"));
        $customerImport->update([
            'file' => $newFilename,
        ]);

        dispatch(new ImportMdb(storage_path($customerImport->file), $customerImport->table_name, $customerImport))
            ->chain([
                new FindCustomerCoordinate($customerImport),
                new ExportCustomerCSV($customerImport),
            ]);

        return new JsonResource($customerImport);
    }

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
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$filePath}.part", $filePath);
        }
        return response()->json([
            'OK' => 1,
            'file' => $request->get('name'),
            'info' => 'Upload successful.',
        ]);
    }
}
