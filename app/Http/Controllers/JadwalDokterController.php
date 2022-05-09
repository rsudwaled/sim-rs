<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\JadwalPoli;
use App\Models\Poliklinik;
use Illuminate\Http\Request;

class JadwalDokterController extends Controller
{
    public function index()
    {
        $poli = Poliklinik::where('status', 1)->get();
        $dokters = Dokter::get();
        $jadwals = JadwalPoli::get();
        return view('simrs.jadwaldokter_index', [
            'poli' => $poli,
            'jadwals' => $jadwals,
            'dokters' => $dokters,
        ]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {

    }

    public function edit($id)
    {
        $jadwal = JadwalPoli::find($id);
        return response()->json($jadwal);
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
