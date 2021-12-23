<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('imports', CustomerImportController::class)->only(['index', 'store']);
Route::post('imports/chunk-upload', [CustomerImportController::class, 'chunkUpload'])
    ->name('imports.chunk-upload');
Route::post('imports/{customerImport}/locate-customers', [CustomerImportController::class, 'locateCustomers'])
    ->name('imports.customers.generate');
Route::post('imports/{customerImport}/generate', [CustomerImportController::class, 'generateCsv'])
    ->name('imports.customers.generate');
Route::get('imports/{customerImport}/customers', [CustomerImportController::class, 'customers'])
    ->name('imports.customers.index');
Route::get('imports/{customerImport}/download-csv', [CustomerImportController::class, 'downloadCSV'])
    ->name('imports.download-csv');
Route::post('customers/batch-update', [CustomerController::class, 'batchUpdate'])->name('customers.batch-update');
Route::resource('customers', CustomerController::class)->only(['store']);
