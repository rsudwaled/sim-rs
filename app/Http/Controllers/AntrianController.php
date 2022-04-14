<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\AntrianBPJSController;
use App\Models\Dokter;
use App\Models\JadwalPoli;
use App\Models\Poliklinik;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;


class AntrianController extends Controller
{
    public function tambah()
    {
        $poli = Poliklinik::get();

        return view('simrs.antrian_tambah', [
            'poli' => $poli,
        ]);
    }

    public function ref_poli()
    {
        $polis = Poliklinik::get();
        return view('simrs.antrian_ref_poli', [
            'polis' => $polis,
        ]);
    }
    public static function get_poli_bpjs()
    {
        $api = new AntrianBPJSController();
        $poli = $api->ref_poli()->response;
        foreach ($poli as $value) {
            if ($value->kdpoli == $value->kdsubspesialis) {
                $subpesialis = 0;
            } else {
                $subpesialis = 1;
            }
            Poliklinik::updateOrCreate(
                [
                    'kodepoli' => $value->kdpoli,
                    'kodesubspesialis' => $value->kdsubspesialis,
                ],
                [
                    'namapoli' => $value->nmpoli,
                    'namasubspesialis' => $value->nmsubspesialis,
                    'subspesialis' => $subpesialis,
                ]
            );
        }
        Alert::success('Success Title', 'Success Message');
        return redirect()->route('antrian.ref.poli');
    }
    public function ref_dokter()
    {
        $dokters = Dokter::get();
        return view('simrs.antrian_ref_dokter', compact('dokters'));
    }
    public static function get_dokter_bpjs()
    {
        $api = new AntrianBPJSController();
        $poli = $api->ref_dokter()->response;
        foreach ($poli as $value) {
            Dokter::updateOrCreate(
                [
                    'kodedokter' => $value->kodedokter,
                ],
                [
                    'namadokter' => $value->namadokter,
                ]
            );
        }
        return redirect()->route('antrian.ref.dokter');
    }
    public function ref_jadwaldokter()
    {
        $poli = Poliklinik::get();
        $jadwals = JadwalPoli::get();
        return view('simrs.antrian_ref_jadwal', [
            'poli' => $poli,
            'jadwals' => $jadwals,
        ]);
    }
    public static function get_jadwal_bpjs(Request $request)
    {
        $api = new AntrianBPJSController();
        $jadwals = $api->ref_jadwal_dokter($request);
        if (isset($jadwals->response)) {
            foreach ($jadwals->response as  $jadwal) {
                JadwalPoli::updateOrCreate([
                    'kodepoli' => $jadwal->kodepoli,
                    'kodesubspesialis' => $jadwal->kodesubspesialis,
                    'kodedokter' => $jadwal->kodedokter,
                    'hari' => $jadwal->hari,
                ], [
                    'namapoli' => $jadwal->namapoli,
                    'namasubspesialis' => $jadwal->namasubspesialis,
                    'namadokter' => $jadwal->namadokter,
                    'namahari' => $jadwal->namahari,
                    'jadwal' => $jadwal->jadwal,
                    'libur' => $jadwal->libur,
                    'kapasitaspasien' => $jadwal->kapasitaspasien,
                ]);
            }
            Alert::success('Success Title', 'Success Message');
        } else {
            Alert::error('Error Title', 'Error Message');
        }
        return redirect()->route('antrian.ref.jadwaldokter');
    }
}
