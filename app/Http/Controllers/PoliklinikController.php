<?php

namespace App\Http\Controllers;

use App\Models\JadwalPoli;
use App\Models\Poliklinik;
use Illuminate\Http\Request;

class PoliklinikController extends Controller
{
    public function index()
    {
        $polis = Poliklinik::get();
        return view('simrs.poli_index', [
            'polis' => $polis
        ]);
    }
    public function jadwaldokter()
    {
        $poli = Poliklinik::where('status', 1)->get();
        $jadwals = JadwalPoli::get();
        return view('simrs.jadwaldokter_index', [
            'poli' => $poli,
            'jadwals' => $jadwals,
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
        $poli = Poliklinik::find($id);
        if ($poli->status == '0') {
            $status = 1;
        } else {
            $status = 0;
        }
        $poli->update([
            'status' => $status,
        ]);
        return redirect()->route('poli.index');
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
    }

    public function destroy($id)
    {
        //
    }
}
