<?php

use App\Http\Controllers\Web\CustomerImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', [CustomerImportController::class, 'index'])->name('imports.index');
Route::get('/imports/create', [CustomerImportController::class, 'create'])->name('imports.create');
Route::get('/imports/{customerImport}/customers', [CustomerImportController::class, 'customers'])->name('imports.customers.index');
