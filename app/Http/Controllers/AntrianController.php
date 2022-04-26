<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\AntrianBPJSController;
use App\Http\Controllers\API\VclaimBPJSController;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPoli;
use App\Models\Pasien;
use App\Models\Poliklinik;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;


class AntrianController extends Controller
{
    public function tambah()
    {
        $poli = Poliklinik::get();
        return view('simrs.antrian_tambah', [
            'poli' => $poli,
        ]);
    }
    public function console()
    {
        $poliklinik = Poliklinik::with(['antrians'])->where('status', 1)->get();
        return view('simrs.antrian_console', [
            'poliklinik' => $poliklinik,
        ]);
    }
    public function tambah_offline($poli)
    {
        $tanggal = Carbon::now()->format('Y-m-d');
        $antrian_poli = Antrian::where('tanggalperiksa', $tanggal)
            ->where('kodepoli', $poli)
            ->count();
        $antrian_tgl = Antrian::where('tanggalperiksa', $tanggal)
            ->count();
        $nomorantrean = $poli . '-' .    str_pad($antrian_poli + 1, 3, '0', STR_PAD_LEFT);
        $angkaantrean = $antrian_tgl + 1;
        $kodebooking = strtoupper(uniqid(6));

        $antrian = Antrian::create([
            "kodebooking" => $kodebooking,
            "nik" => 'Belum diisi',
            "nohp" => 'Belum diisi',
            "kodepoli" => $poli,
            "norm" => 'Belum diisi',
            "pasienbaru" => 2,
            "tanggalperiksa" => Carbon::now()->format('Y-m-d'),
            "kodedokter" => 'Belum diisi',
            "jampraktek" => 'Belum diisi',
            "jeniskunjungan" => 'Belum diisi',
            "jenispasien" => 'Belum diisi',
            "namapoli" => 'Belum diisi',
            "namadokter" => 'Belum diisi',
            "nomorantrean" =>  $nomorantrean,
            "angkaantrean" =>  $angkaantrean,
            "estimasidilayani" => 0,
            "taskid" => 1,
            "keterangan" => 'Antrian Offline',
            "user" => 'System',
        ]);

        // try {
        // $connector = new WindowsPrintConnector('Printer Receipt');
        // $printer = new Printer($connector);
        // $printer->setJustification(Printer::JUSTIFY_CENTER);
        // $printer->setEmphasis(true);
        // $printer->text("RSUD Waled\n");
        // $printer->setEmphasis(false);
        // $printer->text("Melayani Dengan Sepenuh Hati\n");
        // $printer->text("------------------------------------------------\n");
        // $printer->text("Karcis Antrian Pendaftaran Offline\n");
        // $printer->text("Antrian Pendaftaran / Antrian Poliklinik :\n");
        // $printer->setTextSize(2, 2);
        // $printer->text($antrian->angkaantrean . " / " .  $antrian->nomorantrean . "\n");
        // $printer->setTextSize(1, 1);
        // $printer->text("Kode Booking : " . $antrian->kodebooking . "\n\n");
        // $printer->setJustification(Printer::JUSTIFY_LEFT);
        // $printer->text("Silahkan menunggu di Loket Pendaftaran\n");
        // $printer->cut();
        // $printer->close();
        // } catch (Exception $e) {
        //     Alert::error('Error', 'Error Message : ' . $e->getMessage());
        //     return redirect()->route('antrian.console');
        // }
        Alert::success('Success', 'Antrian Berhasil Ditambahkan');
        return redirect()->route('antrian.console');
    }
    public function display_pendaftaran(Request $request)
    {
        $poliklinik = Poliklinik::with(['antrians'])->where('status', 1)->get();
        return view('simrs.display_pendaftaran', [
            'poliklinik' => $poliklinik,
            'request' => $request,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required',
            'nohp' => 'required',
            'jeniskunjungan' => 'required',
            'tanggalperiksa' => 'required',
            'kodepoli' => 'required',
            'kodedokter' => 'required',
        ]);
        $api = new AntrianBPJSController();
        $response = $api->ambil_antrian($request);
        $response = json_decode(json_encode($response, true));
        if ($response->metadata->code == 200) {
            Alert::success('Success Title', 'Success Message');
            return redirect()->route('antrian.tambah');
        } else {
            Alert::error('Error Title', "Error Message " . $response->metadata->message);
            return redirect()->route('antrian.tambah');
        }
    }
    public function pendaftaran(Request $request)
    {
        if ($request->tanggal == null) {
            $request['tanggal'] = Carbon::now()->format('Y-m-d');
        }
        $polis = Poliklinik::where('status', 1)->get();
        $antrians = Antrian::where('pasienbaru', '!=', 0)->get();
        $api = new VclaimBPJSController();
        $provinsis = $api->ref_provinsi()->response->list;
        return view('simrs.antrian_pendaftaran', [
            'antrians' => $antrians,
            'request' => $request,
            'polis' => $polis,
            'provinsis' => $provinsis,
        ]);
    }
    public function edit($id)
    {
        $antrian = Antrian::find($id);
        return response()->json($antrian);
    }
    public function update_offline(Request $request)
    {
        $request->validate([
            'jeniskunjungan' => 'required',
            'kodepoli' => 'required',
            'tanggalperiksa' => 'required',
            'kodedokter' => 'required',
            'nik' => 'required',
            'nik' => 'required',
            'nomorkk' => 'required',
            'nama' => 'required',
            'jeniskelamin' => 'required',
            'tanggallahir' => 'required',
            'nohp' => 'required',
            'alamat' => 'required',
            'kodeprop' => 'required',
        ]);
        try {
            $antrian = Antrian::find($request->antrianid);
            $pasien = Pasien::count();
            $request['norm'] =  Carbon::now()->format('Y') . str_pad($pasien + 1, 4, '0', STR_PAD_LEFT);
            $api = new AntrianBPJSController();
            $response = $api->ref_jadwal_dokter($request);
            if ($response->metadata->code == '200') {
                $jadwal = collect($response->response)->where('kodedokter', $request->kodedokter)->first();
            } else {
                # code...
            }
            if (isset($request->nomorreferensi)) {
                $jenispasien = 'JKN';
            } else {
                $jenispasien = 'NON JKN';
            }
            $pasien = Pasien::updateOrCreate(
                [
                    "nik" => $request->nik,
                ],
                [
                    "norm" => $request->norm,
                    "nomorkartu" => $request->nomorkartu,
                    "nomorkk" => $request->nomorkk,
                    "nama" => $request->nama,
                    "jeniskelamin" => $request->jeniskelamin,
                    "tanggallahir" => $request->tanggallahir,
                    "nohp" => $request->nohp,
                    "alamat" => $request->alamat,
                    "kodeprop" => $request->kodeprop,
                    "namaprop" => $request->namaprop,
                    "kodedati2" => $request->kodedati2,
                    "namadati2" => $request->namadati2,
                    "kodekec" => $request->kodekec,
                    "namakec" => $request->namakec,
                    "kodekel" => $request->kodekel,
                    "namakel" => $request->namakel,
                    "rt" => $request->rt,
                    "rt" => $request->rt,
                ]
            );
            $antrian->update([
                "nomorkartu" => $request->nomorkartu,
                "nik" => $request->nik,
                "nohp" => $request->nohp,
                "norm" => $pasien->norm,
                "jampraktek" => $jadwal->jadwal,
                "jeniskunjungan" => $request->jeniskunjungan,
                "nomorreferensi" => $request->nomorreferensi,
                "jenispasien" => $jenispasien,
                "namapoli" => $jadwal->namasubspesialis,
                "namadokter" => $jadwal->namadokter,
                "taskid" => 3,
                "user" => Auth::user()->name,
                "status_api" => 1,
            ]);
            Alert::success('Success', 'Success Message');
            return redirect()->back();
        } catch (\Throwable $th) {
            Alert::error('Error', $th);
            return redirect()->back();
        }
    }
    public function poli(Request $request)
    {
        if ($request->tanggal == null) {
            $request['tanggal'] = Carbon::now()->format('Y-m-d');
        }
        $antrians = Antrian::where('taskid', '>=', 3)->get();
        return view('simrs.antrian_poli', [
            'antrians' => $antrians,
            'request' => $request,
        ]);
    }
    public function checkin()
    {
        return view('simrs.antrian_checkin');
    }
    public function checkin_update(Request $request)
    {
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        if ($antrian->norm != "PASIEN BARU") {
            $request['taskid'] = 3;
        } else {
            $request['taskid'] = 1;
        }
        $request['taskid'] = 1;
        $request['waktu'] = Carbon::now();
        $api = new AntrianBPJSController();
        $response = $api->checkin_antrian($request);
        try {
            $connector = new WindowsPrintConnector('Printer Receipt');
            $printer = new Printer($connector);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("RSUD Waled\n");
            $printer->setEmphasis(false);
            $printer->text("Melayani Dengan Sepenuh Hati\n");
            $printer->text("------------------------------------------------\n");
            $printer->text("Karcis Antrian Rawat Jalan\n");
            $printer->text("Nomor Antrian / Jenis Pasien :\n");
            $printer->setTextSize(2, 2);
            $printer->text($antrian->nomorantrean . "/" . $antrian->jenispasien . "\n");
            $printer->setTextSize(1, 1);
            $printer->text("Kode Booking : " . $antrian->kodebooking . "\n\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("No.RM : " . $antrian->norm . "\n");
            $printer->text("Nama : MARWAN DHIAUR RAHMAN\n");
            $printer->text("Poliklinik : " . $antrian->namapoli . "\n");
            $printer->text("Dokter : " . $antrian->namadokter . "\n");
            $printer->text("Tanggal : " . Carbon::parse($antrian->tanggalperiksa)->format('d M Y') . "\n\n");
            $printer->text("Silahkan menunggu di poliklinik tersebut\n");
            $printer->text("Checkin : " . $request->waktu . "\n");
            $printer->cut();
            $printer->close();
        } catch (Exception $e) {
            return $response;
        }
        return $response;
    }
    public function baru_online($kodebooking)
    {
        $antrian = Antrian::firstWhere('kodebooking', $kodebooking);
        $poli = Poliklinik::get();
        $api = new VclaimBPJSController();
        $provinsis = $api->ref_provinsi()->response->list;
        return view('simrs.antrian_baru_online', [
            'poli' => $poli,
            'antrian' => $antrian,
            'provinsis' => $provinsis,
        ]);
    }
    public function simpan_baru_online($kodebooking, Request $request)
    {
        $request->validate([
            'nomorkartu' => 'required',
            'nik' => 'required',
            'nomorkk' => 'required',
            'nama' => 'required',
            'jeniskelamin' => 'required',
            'tanggallahir' => 'required',
            'nohp' => 'required',
            'alamat' => 'required',
            'kodeprop' => 'required',
        ]);

        $api = new AntrianBPJSController();
        $request['taskid'] = 3;
        $request['waktu'] = Carbon::now();
        $request['kodebooking'] = $kodebooking;
        $response = $api->update_antrian($request);
        if ($response->metadata->code == 200) {
            $pasien = Pasien::count();
            $request['norm'] =  Carbon::now()->format('Y') . str_pad($pasien + 1, 4, '0', STR_PAD_LEFT);
            Pasien::create($request->except('_token'));
            $antrian = Antrian::firstWhere('kodebooking', $kodebooking);
            $antrian->update([
                'taskid' => 3,
                'norm' => $pasien->norm,
                'nama' => $pasien->nama,
                'user' => Auth::user()->name,
            ]);
        } else {
            Alert::error('Error', "Error Message " . $response->metadata->message);
        }
        return redirect()->route('antrian.pendaftaran');
    }
    public function baru_offline($kodebooking)
    {
        $antrian = Antrian::firstWhere('kodebooking', $kodebooking);
        $poli = Poliklinik::get();
        return view('simrs.antrian_baru_offline', [
            'poli' => $poli,
            'antrian' => $antrian,
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
        Alert::success('Success Title', 'Success Message');
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
