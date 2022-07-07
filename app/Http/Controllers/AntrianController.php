<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\AntrianBPJSController;
use App\Http\Controllers\API\VclaimBPJSController;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPoli;
use App\Models\Pasien;
use App\Models\Poliklinik;
use App\Models\Provinsi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;


class AntrianController extends Controller
{
    // console antrian
    public function console()
    {
        $poliklinik = Poliklinik::with(['antrians', 'jadwals'])->where('status', 1)->get();
        return view('simrs.antrian_console', [
            'poliklinik' => $poliklinik,
        ]);
    }
    public function console_jadwaldokter($poli, $tanggal)
    {
        $poli = Poliklinik::with(['antrians', 'jadwals'])->firstWhere('kodesubspesialis', $poli);
        $jadwals = $poli->jadwals->where('hari', Carbon::parse($tanggal)->dayOfWeek)
            ->where('kodesubspesialis', $poli->kodesubspesialis);
        return response()->json($jadwals);
    }
    public function tambah_offline($poli, $dokter, $jadwal)
    {
        $tanggal = Carbon::now()->format('Y-m-d');
        $antrian_poli = Antrian::where('tanggalperiksa', $tanggal)
            ->where('kodepoli', $poli)
            ->count();
        $antrian_tgl = Antrian::where('tanggalperiksa', $tanggal)
            ->count();
        $antrian_dokter = Antrian::where('tanggalperiksa', $tanggal)
            ->where('kodepoli', $poli)
            ->where('kodedokter', $dokter)
            ->count();
        $nomorantrean = $poli . '-' .    str_pad($antrian_poli + 1, 3, '0', STR_PAD_LEFT);
        $angkaantrean = $antrian_tgl + 1;
        $kodebooking = strtoupper(uniqid(6));

        $poli = Poliklinik::where('kodesubspesialis', $poli)->first();
        $jadwal = $poli->jadwals->where('hari', Carbon::parse($tanggal)->dayOfWeek)->where('kodedokter', $dokter)->first();
        $dokter = Dokter::where('kodedokter', $dokter)->first();

        if ($antrian_dokter >= $jadwal->kapasitaspasien) {
            Alert::error('Error', 'Antrian poliklinik jadwal dokter tersebut telah penuh');
            return redirect()->route('antrian.console');
        }
        $antrian = Antrian::create([
            "kodebooking" => $kodebooking,
            "nik" => 'Offline',
            "nohp" => 'Offline',
            "kodepoli" => $poli->kodesubspesialis,
            "norm" => 'Offline',
            "pasienbaru" => 2,
            "tanggalperiksa" => Carbon::now()->format('Y-m-d'),
            "kodedokter" => $dokter->kodedokter,
            "jampraktek" => $jadwal->jadwal,
            "jeniskunjungan" => 'Offline',
            "jenispasien" => 'Offline',
            "namapoli" =>  $poli->namasubspesialis,
            "namadokter" => $dokter->namadokter,
            "nomorantrean" =>  $nomorantrean,
            "angkaantrean" =>  $angkaantrean,
            "estimasidilayani" => 0,
            "taskid" => 1,
            "user" => 'System',
            "keterangan" => 'Offline',
        ]);
        try {
            // $connector = new WindowsPrintConnector('Printer Receipt');
            $connector = new WindowsPrintConnector("smb://PRINTER:qweqwe@192.168.2.133/Printer Receipt");
            // $connector = new WindowsPrintConnector("smb://PRINTER:qweqwe@ANTRIAN/Printer Receipt");
            $printer = new Printer($connector);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("RSUD Waled\n");
            $printer->setEmphasis(false);
            $printer->text("Melayani Dengan Sepenuh Hati\n");
            $printer->text("------------------------------------------------\n");
            $printer->text("Karcis Antrian Pendaftaran Offline\n");
            $printer->text("Antrian Pendaftaran / Antrian Poliklinik :\n");
            $printer->setTextSize(2, 2);
            $printer->text($antrian->angkaantrean . " / " .  $antrian->nomorantrean . "\n");
            $printer->setTextSize(1, 1);
            $printer->text("Kode Booking : " . $antrian->kodebooking . "\n\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Silahkan menunggu di Loket Pendaftaran\n");
            $printer->cut();
            $printer->close();
        } catch (Exception $e) {
            Alert::error('Error', 'Error Message : ' . $e->getMessage());
            return redirect()->route('antrian.console');
        }
        Alert::success('Success', 'Antrian Berhasil Ditambahkan');
        return redirect()->route('antrian.console');
    }
    public function update_offline(Request $request)
    {
        // validation
        $request->validate([
            'antrianid' => 'required',
            'statuspasien' => 'required',
            'nik' => 'required',
            'nomorkk' => 'required',
            'nama' => 'required',
            'nohp' => 'required',
            'jeniskunjungan' => 'required',
            'tanggalperiksa' => 'required',
            'kodepoli' => 'required',
            'kodedokter' => 'required',
        ]);
        if ($request->statuspasien == "BARU") {
            $request->validate([
                'jeniskelamin' => 'required',
                'tanggallahir' => 'required',
                'alamat' => 'required',
                'kodeprop' => 'required',
            ]);
        }
        // init
        $antrian = Antrian::find($request->antrianid);
        $poli = Poliklinik::where('kodesubspesialis', $request->kodepoli)->first();
        $api = new AntrianBPJSController();
        if (isset($request->nomorreferensi)) {
            $jenispasien = 'JKN';
            $request['keterangan'] = "Silahkan menunggu diruang tunggu poliklinik";
            $request['taskid'] = 3;
        } else {
            $jenispasien = 'NON JKN';
            $request['keterangan'] = "Silahkan untuk membayar biaya pendaftaran diloket pembayaran";
            $request['taskid'] = 2;
        }
        $request['kodebooking'] = $antrian->kodebooking;
        $request['nomorantrean'] = $antrian->nomorantrean;
        $request['angkaantrean'] = $antrian->angkaantrean;
        $request['jenispasien'] = $jenispasien;
        $request['estimasidilayani'] = 0;
        $request['sisakuotajkn'] = 5;
        $request['sisakuotanonjkn'] = 5;
        $request['kuotajkn'] = 20;
        $request['kuotanonjkn'] = 20;
        // update pasien baru
        if ($request->statuspasien == "BARU") {
            $request['pasienbaru'] = 1;
            $pasien = Pasien::count();
            $request['norm'] =  Carbon::now()->format('Y') . str_pad($pasien + 1, 4, '0', STR_PAD_LEFT);
            $pasien = Pasien::create(
                [
                    "nik" => $request->nik,
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
        }
        // update pasien lama
        else {
            $pasien = Pasien::firstWhere('norm', $request->norm);
            $pasien->update([
                "nomorkartu" => $request->nomorkartu,
            ]);
            $request['pasienbaru'] = 0;
        }
        $request['namapoli'] = $poli->namapoli;
        $request['kodepoli'] = $poli->kodepoli;
        $res_antrian = $api->tambah_antrian($request);
        if ($res_antrian->metadata->code == 200) {
            $antrian->update([
                "nomorkartu" => $request->nomorkartu,
                "nik" => $request->nik,
                "nohp" => $request->nohp,
                "norm" => $pasien->norm,
                "jampraktek" => $request->jampraktek,
                "jeniskunjungan" => $request->jeniskunjungan,
                "nomorreferensi" => $request->nomorreferensi,
                "jenispasien" => $jenispasien,
                "namapoli" => $request->namapoli,
                "namadokter" => $request->namadokter,
                "taskid" => $request->taskid,
                "keterangan" => $request->keterangan,
                "user" => Auth::user()->name,
                "status_api" => 1,
            ]);
            Alert::success('Success', 'Success Message : ' . $request->keterangan);
            return redirect()->back();
        } else {
            Alert::error('Error', 'Error Message : ' . $res_antrian->metadata->message);
            return redirect()->back();
        }
    }
    // pendaftaran
    public function pendaftaran(Request $request)
    {
        if ($request->tanggal == null) {
            $request['tanggal'] = Carbon::now()->format('Y-m-d');
        }
        $polis = Poliklinik::where('status', 1)->get();
        $antrians = Antrian::with(['pasien'])
            ->where('tanggalperiksa', $request->tanggal)
            ->get();
        $provinsis = Provinsi::get();
        return view('simrs.antrian_pendaftaran', [
            'antrians' => $antrians,
            'request' => $request,
            'polis' => $polis,
            'provinsis' => $provinsis,
        ]);
    }
    public function panggil_pendaftaran($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        $request['kodebooking'] = $antrian->kodebooking;
        $request['taskid'] = 2;
        $vclaim = new AntrianBPJSController();
        $response = $vclaim->update_antrian($request);
        $antrian->update([
            'taskid' => 2,
            'status_api' => 1,
            'keterangan' => "Panggilan ke loket pendaftaran",
            'user' => Auth::user()->name,
        ]);
        Alert::success('Success', 'Panggilan Berhasil ' . $response->metadata->message);
        return redirect()->back();
    }
    public function cari_pasien($nik)
    {
        $pasien = Pasien::where('nik', $nik)->first();
        if ($pasien == null) {
            $code = 201;
            $message = "Pasien Tidak Ditemukan. Silahkan daftarkan pasien.";
        } else {
            $message = "Pasien Ditemukan";
            $code = 200;
        }
        $response = [
            "response" => $pasien,
            "metadata" => [
                "message" => $message,
                "code" => $code,
            ]
        ];
        return $response;
    }
    public function update_pendaftaran_online(Request $request)
    {
        // validation
        $request->validate([
            'antrianidOn' => 'required',
            'statuspasienOn' => 'required',
            'nikOn' => 'required',
            'nomorkkOn' => 'required',
            'nomorkkOn' => 'required',
            'namaOn' => 'required',
            'nohpOn' => 'required',
            'jeniskelaminOn' => 'required',
            'tanggallahirOn' => 'required',
            'alamatOn' => 'required',
            'kodepropOn' => 'required',
        ]);
        // init
        $antrian = Antrian::firstWhere('id', $request->antrianidOn);
        // update antrian bpjs
        $request['kodebooking'] = $antrian->kodebooking;
        $request['taskid'] = 3;
        if ($antrian->jenispasien == "JKN") {
            $request['status_api'] = 1;
            $request['keterangan'] = "Silahkan melakukan menunggu di poliklinik untuk dilayani";
        } else {
            $request['status_api'] = 0;
            $request['keterangan'] = "Silahkan melakukan pembayaran pendaftaran ke loket pembayaran";
        }

        $vclaim = new AntrianBPJSController();
        $response = $vclaim->update_antrian($request);
        if ($response->metadata->code == 200) {
            // update pasien
            $pasien = Pasien::firstWhere('nik', $request->nikOn);
            $pasien->update([
                "nomorkk" => $request->nomorkkOn,
                "nama" => $request->namaOn,
                "nohp" => $request->nohpOn,
                "nomorkartu" => $request->nomorkartuOn,
                "nomorreferensi" => $request->nomorreferensiOn,
                "jeniskelamin" => $request->jeniskelaminOn,
                "tanggallahir" => $request->tanggallahirOn,
                "alamat" => $request->alamatOn,
                "rt" => $request->rtOn,
                "rw" => $request->rwOn,
                "kodeprop" => $request->kodepropOn,
                "kodedati2" => $request->kodedati2On,
                "kodekec" => $request->kodekecOn,
                "namakel" => $request->namakelOn,
            ]);
            // update antrian simrs
            $antrian->update([
                'taskid' => 3,
                'status_api' => $request->status_api,
                'keterangan' => $request->keterangan,
                'user' => Auth::user()->name,
            ]);
            Alert::success('Success', "Pendaftaran Berhasil. " . $request->keterangan . " " . $response->metadata->message);
            return redirect()->back();
        }
        // jika gagal update antrian bpjs
        else {
            Alert::error('Error', "Pendaftaran Gagal.\n" . $response->metadata->message);
            return redirect()->back();
        }
    }
    public function batal_antrian($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        $request['kodebooking'] = $antrian->kodebooking;
        $request['keterangan'] = "Dibatalkan oleh admin user " . Auth::user()->name;
        $vclaim = new AntrianBPJSController();
        $response = $vclaim->batal_antrian($request);
        Alert::success('Success', "Antrian berhasil dibatalkan. " . $response->metadata->message);
        return redirect()->back();
    }
    // kasir
    public function pembayaran(Request $request)
    {
        if ($request->tanggal == null) {
            $request['tanggal'] = Carbon::now()->format('Y-m-d');
        }
        $polis = Poliklinik::where('status', 1)->get();
        $antrians = Antrian::where('taskid', '>=', 2)->get();
        return view('simrs.antrian_pembayaran', [
            'antrians' => $antrians,
            'request' => $request,
            'polis' => $polis,
        ]);
    }
    public function update_pembayaran(Request $request)
    {
        $antrian = Antrian::find($request->antrianid);
        $antrian->update([
            "taskid" => 3,
            "user" => Auth::user()->name,
            "status_api" => 1,
        ]);
        Alert::success('Success', 'Pembayaran berhasil diupdate');
        return redirect()->back();
    }
    // poliklinik
    public function poli(Request $request)
    {
        if ($request->tanggal == null) {
            $request['tanggal'] = Carbon::now()->format('Y-m-d');
        }
        $antrians = Antrian::where('taskid', '>=', 3)->get();
        $polis = Poliklinik::where('status', 1)->get();
        $dokters = Dokter::get();
        return view('simrs.antrian_poli', [
            'antrians' => $antrians,
            'request' => $request,
            'polis' => $polis,
            'dokters' => $dokters,
        ]);
    }
    public function panggil_poli($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        $request['kodebooking'] = $antrian->kodebooking;
        $request['taskid'] = 4;
        $request['keterangan'] = "Panggilan ke poliklinik yang anda pilih";
        $vclaim = new AntrianBPJSController();
        $response = $vclaim->update_antrian($request);
        $antrian->update([
            'taskid' => $request->taskid,
            'status_api' => 1,
            'keterangan' => $request->keterangan,
            'user' => Auth::user()->name,
        ]);
        Alert::success('Success', 'Panggilan Berhasil ' . $response->metadata->message);
        return redirect()->back();
    }
    public function edit($id)
    {
        $antrian = Antrian::find($id);
        return response()->json($antrian);
    }
    public function lanjut_farmasi($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        $request['kodebooking'] = $antrian->kodebooking;
        $request['taskid'] = 5;
        $request['keterangan'] = "Silahkan tunggu di farmasi";
        $vclaim = new AntrianBPJSController();
        $response = $vclaim->update_antrian($request);
        $antrian->update([
            'taskid' => $request->taskid,
            'status_api' => 1,
            'keterangan' => $request->keterangan,
            'user' => Auth::user()->name,
        ]);
        Alert::success('Success', "Antrian Berhasil Dilanjutkan ke Farmasi.\n" . $response->metadata->message);
        return redirect()->back();
    }
    public function selesai($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        $request['kodebooking'] = $antrian->kodebooking;
        $request['taskid'] = 5;
        $request['keterangan'] = "Antrian selesai, semoga cepat sembuh";
        $vclaim = new AntrianBPJSController();
        $response = $vclaim->update_antrian($request);
        $antrian->update([
            'taskid' => $request->taskid,
            'status_api' => 2,
            'keterangan' => $request->keterangan,
            'user' => Auth::user()->name,
        ]);
        Alert::success('Success', "Antrian Selesai. Semoga cepat sembuh.\n" . $response->metadata->message);
        return redirect()->back();
    }
    // farmasi
    public function farmasi(Request $request)
    {
        if ($request->tanggal == null) {
            $request['tanggal'] = Carbon::now()->format('Y-m-d');
        }
        $antrians = Antrian::where('taskid', '>=', 3)->get();
        $polis = Poliklinik::where('status', 1)->get();
        $dokters = Dokter::get();
        return view('simrs.antrian_farmasi', [
            'antrians' => $antrians,
            'request' => $request,
            'polis' => $polis,
            'dokters' => $dokters,
        ]);
    }
    public function racik_farmasi($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        $request['kodebooking'] = $antrian->kodebooking;
        $request['taskid'] = 6;
        $request['keterangan'] = "Proses peracikan obat";
        $vclaim = new AntrianBPJSController();
        $response = $vclaim->update_antrian($request);
        $antrian->update([
            'taskid' => $request->taskid,
            'status_api' => 1,
            'keterangan' => $request->keterangan,
            'user' => Auth::user()->name,
        ]);
        Alert::success('Proses', 'Proses Peracikan Obat ' . $response->metadata->message);
        return redirect()->back();
    }
    public function selesai_farmasi($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        $request['kodebooking'] = $antrian->kodebooking;
        $request['taskid'] = 7;
        $request['keterangan'] = "Selesai peracikan obat";
        $vclaim = new AntrianBPJSController();
        $response = $vclaim->update_antrian($request);
        $antrian->update([
            'taskid' => $request->taskid,
            'status_api' => 1,
            'keterangan' => $request->keterangan,
            'user' => Auth::user()->name,
        ]);
        Alert::success('Success', 'Selesai Peracikan Obat ' . $response->metadata->message);
        return redirect()->back();
    }
    // farmasi
    public function show($kodebooking, Request $request)
    {
        dd($request->all());
        $antrian = Antrian::firstWhere('kodebooking', $kodebooking);
        $poli = Poliklinik::get();
        return view('simrs.antrian_baru_offline', [
            'poli' => $poli,
            'antrian' => $antrian,
        ]);
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
    // admin pendaftaran
    public function laporan(Request $request)
    {
        if ($request->tanggal == null) {
            $tanggal_awal = Carbon::now()->startOfMonth()->format('Y-m-d');
            $tanggal_akhir = Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            $tanggal = explode(' - ', $request->tanggal);
            $tanggal_awal = Carbon::parse($tanggal[0])->format('Y-m-d');
            $tanggal_akhir = Carbon::parse($tanggal[1])->format('Y-m-d');
        }
        $antrians = Antrian::with(['pasien'])
            ->whereBetween('tanggalperiksa', [$tanggal_awal, $tanggal_akhir])
            ->get();
        return view('simrs.antrian_laporan', [
            'antrians' => $antrians,
            'request' => $request,
        ]);
    }



    public function checkin_update(Request $request)
    {
        // checking request
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'code' => 400,
                    'message' => $validator->errors()->first(),
                ],
            ];
            return $response;
        }
        // cari antrian
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        if (isset($antrian)) {
            $api = new AntrianBPJSController();
            $response = json_decode(json_encode($api->checkin_antrian($request)));
            return $response;
        }
        // jika antrian tidak ditemukan
        else {
            return $response = [
                'metadata' => [
                    'code' => 400,
                    'message' => "Antrian tidak ditemukan",
                ],
            ];
        }
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
    public function tambah()
    {
        $poli = Poliklinik::get();
        return view('simrs.antrian_tambah', [
            'poli' => $poli,
        ]);
    }
}
