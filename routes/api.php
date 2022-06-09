<?php

use App\Http\Controllers\API\AntrianBPJSController;
use App\Http\Controllers\API\VclaimBPJSController;
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
    Route::get('dashboard_tanggal', [AntrianBPJSController::class, 'dashboard_tanggal']);
});

Route::get('token', [AntrianBPJSController::class, 'token']);
Route::prefix('wsrs')->group(function () {
    Route::post('ambil_antrian', [AntrianBPJSController::class, 'ambil_antrian']);
    Route::post('status_antrian', [AntrianBPJSController::class, 'status_antrian']);
    Route::post('sisa_antrian', [AntrianBPJSController::class, 'sisa_antrian']);
    Route::post('batal_antrian', [AntrianBPJSController::class, 'batal_antrian']);
    Route::post('checkin_antrian', [AntrianBPJSController::class, 'checkin_antrian']);
    Route::post('info_pasien_baru', [AntrianBPJSController::class, 'info_pasien_baru']);
    Route::post('jadwal_operasi_rs', [AntrianBPJSController::class, 'jadwal_operasi_rs']);
    Route::post('jadwal_operasi_pasien', [AntrianBPJSController::class, 'jadwal_operasi_pasien']);
});

Route::prefix('vclaim')->group(function () {
    // ref
    Route::get('ref_provinsi', [VclaimBPJSController::class, 'ref_provinsi']);
    Route::post('ref_kabupaten', [VclaimBPJSController::class, 'ref_kabupaten']);
    Route::post('ref_kecamatan', [VclaimBPJSController::class, 'ref_kecamatan']);
    // monitoring
    Route::get('monitoring_pelayanan_peserta', [VclaimBPJSController::class, 'monitoring_pelayanan_peserta']);
    // peserta cek
    Route::get('peserta_nomorkartu', [VclaimBPJSController::class, 'peserta_nomorkartu']);
    Route::get('peserta_nik', [VclaimBPJSController::class, 'peserta_nik']);
    // rujukan
    Route::get('rujukan_jumlah_sep', [VclaimBPJSController::class, 'rujukan_jumlah_sep']);
    // sep
    Route::post('insert_sep', [VclaimBPJSController::class, 'insert_sep']);
    // surat kontrol
    Route::post('insert_rencana_kontrol', [VclaimBPJSController::class, 'insert_rencana_kontrol']);
});
