<?php

use App\Http\Controllers\API\AntrianBPJSController;
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

Route::prefix('antrian')->group(function () {
    Route::get('signature', [AntrianBPJSController::class, 'signature']);
    Route::prefix('ref')->group(function () {
        Route::get('poli', [AntrianBPJSController::class, 'ref_poli']);
        Route::get('dokter', [AntrianBPJSController::class, 'ref_dokter']);
        Route::get('jadwal', [AntrianBPJSController::class, 'ref_jadwal_dokter']);
        Route::post('updatejadwal', [AntrianBPJSController::class, 'update_jadwal_dokter']);
    });
    Route::post('tambah', [AntrianBPJSController::class, 'tambah_antrian']);
    Route::post('update', [AntrianBPJSController::class, 'update_antrian']);
    Route::post('batal', [AntrianBPJSController::class, 'batal_antrian_bpjs']);
    Route::post('listtask', [AntrianBPJSController::class, 'list_waktu_task']);
});
