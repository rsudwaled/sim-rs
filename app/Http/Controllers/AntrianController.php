<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\AntrianBPJSController;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPoli;
use App\Models\Poliklinik;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $antrians = Antrian::where('pasienbaru', '!=', 0)->get();
        return view('simrs.antrian_pendaftaran', [
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
        return view('simrs.antrian_baru_online', [
            'poli' => $poli,
            'antrian' => $antrian,
        ]);
    }
    public function simpan_baru_online(Request $request)
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
            'namaprop' => 'required',
            'kodedati2' => 'required',
            'namadati2' => 'required',
            'kodekec' => 'required',
            'namakec' => 'required',
            'kodekel' => 'required',
            'namakel' => 'required',
            'rt' => 'required',
            'rw' => 'required',
        ]);
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
