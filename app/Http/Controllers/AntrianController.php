<?php

namespace App\Http\Controllers;

use App\Models\Poliklinik;
use Illuminate\Http\Request;

class AntrianController extends Controller
{
    //
    public function ref_poli()
    {
        $polis = Poliklinik::get();
        return view('simrs.antrian_ref_poli', [
            'polis' => $polis,
        ]);
    }
}
