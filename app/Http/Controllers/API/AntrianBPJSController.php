<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalOperasi;
use App\Models\KunjunganDB;
use App\Models\LayananDB;
use App\Models\LayananDetailDB;
use App\Models\Pasien;
use App\Models\PasienDB;
use App\Models\Poliklinik;
use App\Models\TarifLayananDB;
use App\Models\TarifLayananDetailDB;
use App\Models\TracerDB;
use App\Models\TransaksiDB;
use App\Models\UnitDB;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AntrianBPJSController extends Controller
{
    // function WS BPJS
    public $baseUrl = 'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/';

    public static function signature()
    {
        $cons_id =  env('ANTRIAN_CONS_ID');
        $secretKey = env('ANTRIAN_SECRET_KEY');
        $userkey = env('ANTRIAN_USER_KEY');

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $signature = hash_hmac('sha256', $cons_id . "&" . $tStamp, $secretKey, true);
        $encodedSignature = base64_encode($signature);

        $response = array(
            'user_key' => $userkey,
            'x-cons-id' => $cons_id,
            'x-timestamp' => $tStamp,
            'x-signature' => $encodedSignature,
            'decrypt_key' => $cons_id . $secretKey . $tStamp,
        );
        return $response;
    }
    public static function stringDecrypt($key, $string)
    {
        $encrypt_method = 'AES-256-CBC';
        $key_hash = hex2bin(hash('sha256', $key));
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
        $output = \LZCompressor\LZString::decompressFromEncodedURIComponent($output);
        return $output;
    }
    public function ref_poli()
    {
        $url = $this->baseUrl . "ref/poli";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
        $response->response = json_decode($decrypt);
        return $response;
    }
    public function ref_dokter()
    {
        $url = $this->baseUrl . "ref/dokter";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
        $response->response = json_decode($decrypt);
        return $response;
    }
    public function ref_jadwal_dokter(Request $request)
    {
        $request['kodepoli'] = $request->kodepoli;
        $request['tanggal'] = $request->tanggalperiksa;
        $url = $this->baseUrl . "jadwaldokter/kodepoli/" . $request->kodepoli . "/tanggal/" . $request->tanggal;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        if ($response->metadata->code == 200) {
            $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
            $response->response = json_decode($decrypt);
        }
        return $response;
    }
    public function update_jadwal_dokter(Request $request)
    {
        $url = $this->baseUrl . "jadwaldokter/updatejadwaldokter";
        $signature = $this->signature();
        $client = new Client();
        $response = $client->request('POST', $url, [
            'headers' => $signature,
            'body' => json_encode([
                "kodepoli" => $request->kodepoli,
                "kodesubspesialis" => $request->kodesubspesialis,
                "kodedokter" => $request->kodedokter,
                "jadwal" => [
                    [
                        "hari" => "1",
                        "buka" => "08:00",
                        "tutup" => "10:00"
                    ],
                    [
                        "hari" => "2",
                        "buka" => "15:00",
                        "tutup" => "17:00"
                    ]
                ]
            ]),
        ]);
        $response = json_decode($response->getBody());
        return $response;
    }
    public function tambah_antrian(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            // "nomorkartu" =>  "required",
            // "nomorreferensi" =>  "required",
            "nik" =>  "required",
            "nohp" => "required",
            "kodepoli" =>  "required",
            "norm" =>  "required",
            "pasienbaru" =>  "required",
            "tanggalperiksa" =>  "required",
            "kodedokter" =>  "required",
            "jampraktek" =>  "required",
            "jeniskunjungan" => "required",
            "jenispasien" =>  "required",
            "namapoli" =>  "required",
            "namadokter" =>  "required",
            "nomorantrean" =>  "required",
            "angkaantrean" =>  "required",
            "estimasidilayani" =>  "required",
            "sisakuotajkn" =>  "required",
            "kuotajkn" => "required",
            "sisakuotanonjkn" => "required",
            "kuotanonjkn" => "required",
            "keterangan" =>  "required",
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'code' => 400,
                    'message' => $validator->errors()->first(),
                ],
            ];
            return json_decode(json_encode($response));
        }
        $url = $this->baseUrl . "antrean/add";
        $signature = $this->signature();
        $client = new Client();
        if (is_null($request->nomorkartu) || is_null($request->nomorreferensi)) {
            $request->nomorkartu = "";
            $request->nomorreferensi = "";
        }
        // dd($request->all());
        $response = $client->request('POST', $url, [
            'headers' => $signature,
            'body' => json_encode([
                "kodebooking" => $request->kodebooking,
                "nomorkartu" => $request->nomorkartu,
                "nik" => $request->nik,
                "nohp" => $request->nohp,
                "kodepoli" => $request->kodepoli,
                "norm" => $request->norm,
                "pasienbaru" => $request->pasienbaru,
                "tanggalperiksa" => $request->tanggalperiksa,
                "kodedokter" => $request->kodedokter,
                "jampraktek" => $request->jampraktek,
                "jeniskunjungan" => $request->jeniskunjungan,
                "nomorreferensi" => $request->nomorreferensi,
                "jenispasien" => $request->jenispasien,
                "namapoli" => $request->namapoli,
                "namadokter" => $request->namadokter,
                "nomorantrean" => $request->nomorantrean,
                "angkaantrean" => $request->angkaantrean,
                "estimasidilayani" => $request->estimasidilayani,
                "sisakuotajkn" => $request->sisakuotajkn,
                "kuotajkn" => $request->kuotajkn,
                "sisakuotanonjkn" => $request->sisakuotanonjkn,
                "kuotanonjkn" => $request->kuotanonjkn,
                "keterangan" => $request->keterangan,
            ]),
        ]);
        $response = json_decode($response->getBody());
        return $response;
    }
    public function update_antrian(Request $request)
    {
        $request['waktu'] = Carbon::now();
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "taskid" => "required",
            "waktu" => "required",
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'code' => 400,
                    'message' => $validator->errors()->first(),
                ],
            ];
            return json_decode(json_encode($response));
        }
        $url = $this->baseUrl . "antrean/updatewaktu";
        $signature = $this->signature();
        $client = new Client();
        $response = $client->request('POST', $url, [
            'headers' => $signature,
            'body' => json_encode([
                "kodebooking" => $request->kodebooking,
                "taskid" => $request->taskid,
                "waktu" => $request->waktu,
            ]),
        ]);
        $response = json_decode($response->getBody());
        return $response;
    }
    public function batal_antrian_bpjs(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "keterangan" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $url = $this->baseUrl . "antrean/batal";
        $signature = $this->signature();
        $client = new Client();
        $response = $client->request('POST', $url, [
            'headers' => $signature,
            'body' => json_encode([
                "kodebooking" => $request->kodebooking,
                "keterangan" => $request->keterangan,
            ]),
        ]);
        $response = json_decode($response->getBody());
        return $response;
    }
    public function list_waktu_task(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $url = $this->baseUrl . "antrean/getlisttask";
        $signature = $this->signature();
        $client = new Client();
        $response = $client->request('POST', $url, [
            'headers' => $signature,
            'body' => json_encode([
                "kodebooking" => $request->kodebooking,
            ]),
        ]);
        $response = json_decode($response->getBody());
        $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
        $response->response = json_decode($decrypt);
        return $response;
    }
    public function dashboard_tanggal(Request $request)
    {
        // tinggal masalah waktunya aja ????
        $request['taskid'] = 1;
        $url = $this->baseUrl . "/dashboard/waktutunggu/tanggal/" . $request->tanggal . "/waktu/1627873951000";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        if ($response->metadata->code == 200) {
            $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
            $response->response = json_decode($decrypt);
        }
        return $response;
    }
    public function dashboard_bulan(Request $request)
    {
        // tinggal masalah waktunya aja ????
        // tinggal masalah waktunya aja ????
        $request['taskid'] = 1;
        $url = $this->baseUrl . "/waktutunggu/bulan/{Parameter1}/tahun/{Parameter2}/waktu/{Parameter3}";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        if ($response->metadata->code == 200) {
            $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
            $response->response = json_decode($decrypt);
        }
        return $response;
    }

    // function WS RS
    public function token(Request $request)
    {
        if (Auth::attempt(['email' => $request->header('x-username'), 'password' => $request->header('x-password')])) {
            $user = Auth::user();
            $success['token'] =  $user->createToken('MyApp')->plainTextToken;
            $response = [
                "response" => [
                    "token" => $success['token'],
                ],
                "metadata" => [
                    "code" => 200,
                    "message" => "OK"
                ]
            ];
            return $response;
        } else {
            $response = [
                "metadata" => [
                    "code" => 201,
                    "message" => "Unauthorized"
                ]
            ];
            return $response;
        }
    }
    public function status_antrian(Request $request)
    {
        $jadwals = $this->ref_jadwal_dokter($request);
        if (isset($jadwals->response)) {
            $jadwal = collect($jadwals->response)->where('kodedokter', $request->kodedokter)->first();
            if (empty($jadwal)) {
                $response = [
                    "metadata" => [
                        "code" => 201,
                        "message" => "Tidak ada jadwal dokter dihari tersebut."
                    ]
                ];
                return $response;
            }
            $antrian = Antrian::where('kodepoli', $request->kodepoli)
                ->where('tanggalperiksa', $request->tanggalperiksa);
            $antrians = $antrian->count();
            $antreanpanggil =  Antrian::where('kodepoli', $request->kodepoli)
                ->where('tanggalperiksa', $request->tanggalperiksa)
                ->where('taskid', 4)->first();
            if (isset($antreanpanggil)) {
                $nomorantean = $antreanpanggil->nomorantrian;
            } else {
                $nomorantean = 0;
            }
            $response = [
                "response" => [
                    "namapoli" => $jadwal->namapoli,
                    "namadokter" => $jadwal->namadokter,
                    "totalantrean" => $antrians,
                    "sisaantrean" => $jadwal->kapasitaspasien - $antrians,
                    "antreanpanggil" => $nomorantean,
                    "sisakuotajkn" => $jadwal->kapasitaspasien - $antrians,
                    "kuotajkn" => $jadwal->kapasitaspasien,
                    "sisakuotanonjkn" => $jadwal->kapasitaspasien - $antrians,
                    "kuotanonjkn" => $jadwal->kapasitaspasien,
                    "keterangan" => "Informasi antrian poliklinik",
                ],
                "metadata" => [
                    "message" => "Ok",
                    "code" => 200
                ]
            ];
            return $response;
        } else {
            return  $jadwals;
        }
    }
    public function ambil_antrian(Request $request)
    {
        // checking request
        $validator = Validator::make(request()->all(), [
            "nik" => "required",
            "nohp" => "required",
            "kodepoli" => "required",
            // "norm" => "required",
            "tanggalperiksa" => "required",
            "kodedokter" => "required",
            "jampraktek" => "required",
            "jeniskunjungan" => "required",
            // "nomorreferensi" => "required",
            // "nomorkartu" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        // cek jika jenis pasien jkn
        if (isset($request->nomorreferensi)) {
            $vclaim = new VclaimBPJSController();
            $peserta = $vclaim->peserta_nik($request);
            if ($peserta->metaData->code == 200) {
                $peserta_aktif = $peserta->response->peserta->statusPeserta->kode;
                $peserta_nomorkartu = $peserta->response->peserta->noKartu;
                // jika data pasien salah / berbeda
                if ($peserta_nomorkartu != $request->nomorkartu) {
                    return $response = [
                        "metadata" => [
                            "message" => "NIK dan nomor kartu peserta tidak sesuai",
                            "code" => 201,
                        ],
                    ];
                }
                // jika peserta jkn tidak aktif nilai 0
                else if ($peserta_aktif != 0) {
                    return $response = [
                        "metadata" => [
                            "message" => $peserta->response->peserta->statusPeserta->keterangan,
                            "code" => 201,
                        ],
                    ];
                }
                // jika peserta jkn aktif dan sesuai
                else if ($peserta_aktif == 0) {
                    $request['jenispasien'] = 'JKN';
                }
            } else {
                return $peserta;
            }
            // cek sep pertama atw bukan
            $monitoring = $vclaim->monitoring_pelayanan_peserta($request);
            if ($monitoring->metaData->code == 200) {
                $monitoring_response = $monitoring->response;
                dd($monitoring_response);
            }
            // $suratkontrol = $vclaim->insert_rencana_kontrol($request);
        }
        // cek jika jenis pasien non jkn
        else {
            $request['jenispasien'] = 'NON JKN';
        }
        // cek norm pasien lama
        $pasien = Pasien::where('nik', $request->nik)->first();
        if ($pasien) {
            $request['norm'] = $pasien->norm;
            $request['nama'] = $pasien->nama;
            $request['pasienbaru'] = 0;
        }
        // cek norm pasien baru dan insert pasien baru
        else {
            $pasienbaru = $this->info_pasien_baru($request);
            $request['norm'] = $pasienbaru->response->norm;
            $request['pasienbaru'] = 1;
            $request['nama'] = "Belum Didaftarkan";
        }
        // cek jadwal
        $jadwals = $this->ref_jadwal_dokter($request);
        if (isset($jadwals->response)) {
            $jadwal = collect($jadwals->response)->where('kodedokter', $request->kodedokter)->first();
            if (empty($jadwal)) {
                $response = [
                    "metadata" => [
                        "code" => 201,
                        "message" => "Tidak ada jadwal dokter dihari tersebut."
                    ]
                ];
                return $response;
            }
        } else {
            return $jadwals;
        }
        $request['namapoli'] = $jadwal->namapoli;
        $request['namadokter'] = $jadwal->namadokter;
        // cek duplikasi nik antrian
        $antrian_nik = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->where('nik', $request->nik)
            ->where('taskid', '<=', 4)
            ->count();
        if ($antrian_nik) {
            return $response = [
                "metadata" => [
                    "message" => "Terdapat antrian dengan nomor NIK yang sama pada tanggal tersebut yang belum selesai.",
                    "code" => 201,
                ],
            ];
        }
        //  cek nik
        $antrians = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->count();
        $antrian_poli = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->where('kodepoli', $request->kodepoli)
            ->count();
        $antrian_dokter = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->where('kodepoli', $request->kodepoli)
            ->where('kodedokter', $request->kodedokter)
            ->count();
        $request['nomorantrean'] = $request->kodepoli . "-" .  str_pad($antrian_poli + 1, 3, '0', STR_PAD_LEFT);
        $request['angkaantrean'] = $antrians + 1;
        $request['kodebooking'] = strtoupper(uniqid());
        // estimasi
        $request['estimasidilayani'] = 0;
        $request['sisakuotajkn'] = $jadwal->kapasitaspasien -  $antrian_dokter;
        $request['kuotajkn'] = $jadwal->kapasitaspasien;
        $request['sisakuotanonjkn'] = $jadwal->kapasitaspasien -  $antrian_dokter;
        $request['kuotanonjkn'] = $jadwal->kapasitaspasien;
        $request['keterangan'] = "Antrian berhasil dibuat";
        //tambah antrian bpjs
        $response = $this->tambah_antrian($request);
        if ($response->metadata->code == 200) {
            //tambah antrian database
            $antrian = Antrian::create([
                "kodebooking" => $request->kodebooking,
                "nomorkartu" => $request->nomorkartu,
                "nik" => $request->nik,
                "nohp" => $request->nohp,
                "kodepoli" => $request->kodepoli,
                "norm" => $request->norm,
                "pasienbaru" => $request->pasienbaru,
                "tanggalperiksa" => $request->tanggalperiksa,
                "kodedokter" => $request->kodedokter,
                "jampraktek" => $request->jampraktek,
                "jeniskunjungan" => $request->jeniskunjungan,
                "nomorreferensi" => $request->nomorreferensi,
                "jenispasien" => $request->jenispasien,
                "namapoli" => $request->namapoli,
                "namadokter" => $request->namadokter,
                "nomorantrean" => $request->nomorantrean,
                "angkaantrean" => $request->angkaantrean,
                "estimasidilayani" => $request->estimasidilayani,
                "sisakuotajkn" => $request->sisakuotajkn,
                "kuotajkn" => $request->kuotajkn,
                "sisakuotanonjkn" => $request->sisakuotanonjkn,
                "kuotanonjkn" => $request->kuotanonjkn,
                "keterangan" => $request->keterangan,
                "status_bpjs" => 1,
                "user" => "System Antrian",
                "nama" => $request->nama,
            ]);
            $response = [
                "response" => [
                    "nomorantrean" => $request->nomorantrean,
                    "angkaantrean" => $request->angkaantrean,
                    "kodebooking" => $request->kodebooking,
                    "norm" => $request->norm,
                    "namapoli" => $request->namapoli,
                    "namadokter" => $request->namadokter,
                    "estimasidilayani" => $request->estimasidilayani,
                    "sisakuotajkn" => $request->sisakuotajkn,
                    "kuotajkn" => $request->kuotajkn,
                    "sisakuotanonjkn" => $request->sisakuotanonjkn,
                    "kuotanonjkn" => $request->kuotanonjkn,
                    "keterangan" => $request->keterangan,
                ],
                "metadata" => [
                    "message" => "Ok",
                    "code" => 200
                ]
            ];
            return json_decode(json_encode($response));
        } else {
            return $response;
        }
    }
    public function sisa_antrian(Request $request)
    {
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        $sisaantrean = Antrian::where('taskid', "<=", 1)
            ->where('tanggalperiksa', $antrian->tanggalperiksa)
            ->count();
        $antreanpanggil =  Antrian::where('taskid', 2)
            ->where('tanggalperiksa', $antrian->tanggalperiksa)
            ->first();
        $antrian['waktutunggu'] = "Error";
        $antrian['keterangan'] = "Info antrian anda";
        $response = [
            "response" => [
                "nomorantrean" => $antrian->nomorantrean,
                "namapoli" => $antrian->namapoli,
                "namadokter" => $antrian->namadokter,
                "sisaantrean" => $sisaantrean,
                "antreanpanggil" => $antreanpanggil->angkaantrean . "-" . $antreanpanggil->nomorantrean,
                "waktutunggu" => $antrian->waktutunggu,
                "keterangan" => $antrian->keterangan,
            ],
            "metadata" => [
                "message" => "Ok",
                "code" => 200
            ]
        ];
        return $response;
    }
    public function batal_antrian(Request $request)
    {
        $response = $this->batal_antrian_bpjs($request);
        if ($response->metadata->code == 200) {
            Antrian::where('kodebooking', $request->kodebooking)->update([
                "taskid" => 99,
                "keterangan" => $request->keterangan,
            ]);
            $response = [
                "metadata" => [
                    "message" => "Ok",
                    "code" => 200,
                ],
            ];
            return $response;
        } else {
            return $response;
        }
    }
    public function checkin_antrian(Request $request)
    {
        $now = Carbon::now();
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        $unit = UnitDB::firstWhere('KDPOLI', $antrian->kodepoli);
        // jika antrian ditemukan
        if (isset($antrian)) {
            // jika pasien lama
            if ($antrian->jenispasien == "JKN") {
                if ($antrian->pasienbaru == 0) {
                    $request['taskid'] = 3;
                    $request['keterangan'] = "Silahkan menunggu panggilan di poliklinik";
                }
                // jika pasien baru
                else if ($antrian->pasienbaru == 1) {
                    $request['taskid'] = 1;
                    $request['keterangan'] = "Silahkan menunggu panggilan di loket pendaftaran pasien baru";
                }
            } else {
                $request['taskid'] = 1;
                $request['keterangan'] = "Silahkan menunggu panggilan di loket pendaftaran pasien baru";
            }
            // hitung counter kunjungan
            $kunjungan = KunjunganDB::where('no_rm', $antrian->norm)->orderBy('counter', 'DESC')->first();
            if (empty($kunjungan)) {
                $counter = 1;
            } else {
                $counter = $kunjungan->counter + 1;
            }
            // insert ts kunjungan
            $kunjunganbaru = KunjunganDB::create(
                [
                    'counter' => $counter,
                    'no_rm' => $antrian->norm,
                    'kode_unit' => $unit->kode_unit,
                    'tgl_masuk' => $now,
                    'kode_paramedis' => $antrian->kodedokter,
                    'status_kunjungan' => 1,
                ]
            );
            // insert layanan header dan detail karcis admin konsul 25 + 5 = 30
            $kunjungan = KunjunganDB::where('no_rm', $antrian->norm)->where('counter', $kunjunganbaru->counter)->first();
            $trx_lama = TransaksiDB::where('unit', $unit->kode_unit)
                ->whereBetween('tgl', [Carbon::now()->startOfDay(), [Carbon::now()->endOfDay()]])
                ->count();
            $kodelayanan = $unit->KDPOLI . $now->format('y') . $now->format('m') . $now->format('d')  . str_pad($trx_lama + 1, 6, '0', STR_PAD_LEFT);
            $trx_baru = TransaksiDB::create([
                'tgl' => $now,
                'no_trx_layanan' => $kodelayanan,
                'unit' => $unit->kode_unit,
            ]);
            $tarifkarcis = TarifLayananDetailDB::firstWhere('KODE_TARIF_DETAIL', $unit->kode_tarif_karcis);
            $tarifadm = TarifLayananDetailDB::firstWhere('KODE_TARIF_DETAIL', $unit->kode_tarif_adm);
            // jika pasien jkn
            if ($antrian->jenispasien == "JKN") {
                // rj jkn tipe transaki 2 status layanan 2 status layanan detail opn
                $tipetransaksi = 2;
                $statuslayanan = 2;
                // rj jkn masuk ke tagihan penjamin
                $tagihanpenjamin = $tarifkarcis->TOTAL_TARIF_NEW;
                $totalpenjamin =  $tarifkarcis->TOTAL_TARIF_NEW + $tarifadm->TOTAL_TARIF_NEW;
                $tagihanpribadi = 0;
                $totalpribadi =  0;
                // jika jkn pake sep
                $request['noKartu'] = $antrian->nomorkartu;
                $request['tglSep'] = $antrian->tanggalperiksa;
                $request['ppkPelayanan'] = "1018R001";
                $request['jnsPelayanan'] = "2";

                $vclaim = new VclaimBPJSController();
                $request['nik'] = $antrian->nik;
                // peserta sep
                $request['noMR'] = $antrian->norm;
                // $peserta = $vclaim->peserta_nik($request);
                // if ($peserta->metaData->code == 200) {
                //     $peserta = $peserta->response->peserta;

                // }
                // rujukan
                $request['nomorreferensi'] = $antrian->nomorreferensi;
                $request['nomorreferensi'] = "110912030622P000119";
                $data = $vclaim->rujukan_nomor($request);
                if ($data->metaData->code == 200) {
                    $rujukan = $data->response->rujukan;
                    $peserta = $rujukan->peserta;
                    $diganosa = $rujukan->diagnosa;
                    $tujuan = $rujukan->poliRujukan;
                    // peserta
                    $request['klsRawatHak'] = $peserta->hakKelas->kode;
                    $request['klsRawatNaik'] = "";
                    $request['pembiayaan'] = "1";
                    $request['penanggungJawab'] = "Pribadi";
                    // rujukan
                    $request['asalRujukan'] = $data->response->asalFaskes;
                    $request['tglRujukan'] = $rujukan->tglKunjungan;
                    $request['noRujukan'] =  $rujukan->tglKunjungan;
                    $request['ppkRujukan'] = $rujukan->provPerujuk->kode;
                    // diagnosa
                    $request['catatan'] =  $diganosa->nama;
                    $request['diagAwal'] =  $diganosa->kode;
                    // poli tujuan
                    $request['tujuan'] =  $tujuan->kode;
                    $request['eksekutif'] =  0;
                    // dpjp
                    $request['dpjpLayan'] =  $antrian->kodedokter;
                }
                // rujukan aktif
                // jadwal, dokter, kuota
                // jika pertama
                // tujuan kunjungan =  normal
                // kode penunjang = ""
                // kode penunjang = ""
                // assesment pelayanan = ""
                // surat kontrol jika pasien kunjungan kedua atau lebih
                $vclaim = new VclaimBPJSController();
                $sep = $vclaim->insert_sep($request);
                // databse sep ts_sep
                dd($data);
            }
            // jika pasien non jkn
            else {
                // rj umum tipe transaki 1 status layanan 1 status layanan detail opn
                $tipetransaksi = 1;
                $statuslayanan = 1;
                // rj umum masuk ke tagihan pribadi
                $tagihanpenjamin = 0;
                $totalpenjamin =  0;
                $tagihanpribadi = $tarifkarcis->TOTAL_TARIF_NEW;
                $totalpribadi = $tarifkarcis->TOTAL_TARIF_NEW + $tarifadm->TOTAL_TARIF_NEW;
            }
            // insert layanan header
            $layananbaru = LayananDB::create(
                [
                    'kode_layanan_header' => $kodelayanan,
                    'tgl_entry' => $now,
                    'kode_kunjungan' => $kunjungan->kode_kunjungan,
                    'kode_unit' => $unit->kode_unit,
                    'kode_tipe_transaksi' => $tipetransaksi,
                    'status_layanan' => $statuslayanan,
                    'pic' => '1271',
                    'keterangan' => 'Layanan header melalui antrian sistem',
                ]
            );
            // insert layanan detail karcis
            $karcis = LayananDetailDB::create(
                [
                    'id_layanan_detail' => "DET" . $now->yearIso . $now->month . $now->day .  "001",
                    'row_id_header' => $layananbaru->id,
                    'kode_layanan_header' => $layananbaru->kode_layanan_header,
                    'kode_tarif_detail' => $tarifkarcis->KODE_TARIF_DETAIL,
                    'total_tarif' => $tarifkarcis->TOTAL_TARIF_NEW,
                    'jumlah_layanan' => 1,
                    'tagihan_pribadi' => $tagihanpribadi,
                    'tagihan_penjamin' => $tagihanpenjamin,
                    'total_layanan' => $tarifkarcis->TOTAL_TARIF_NEW,
                    'grantotal_layanan' => $tarifkarcis->TOTAL_TARIF_NEW,
                    'kode_dokter1' => $antrian->kodedokter, // ambil dari mt paramdeis
                    'tgl_layanan_detail' =>  $now,
                ]
            );
            // insert layanan detail admin
            $adm = LayananDetailDB::create(
                [
                    'id_layanan_detail' => "DET" . $now->yearIso . $now->month . $now->day .  "01",
                    'row_id_header' => $layananbaru->id,
                    'kode_layanan_header' => $layananbaru->kode_layanan_header,
                    'kode_tarif_detail' => $tarifadm->KODE_TARIF_DETAIL,
                    'total_tarif' => $tarifadm->TOTAL_TARIF_NEW,
                    'jumlah_layanan' => 1,
                    'tagihan_pribadi' => $tagihanpribadi,
                    'tagihan_penjamin' => $tagihanpenjamin,
                    'total_layanan' => $tarifadm->TOTAL_TARIF_NEW,
                    'grantotal_layanan' => $tarifadm->TOTAL_TARIF_NEW,
                    'kode_dokter1' => 0,
                    'tgl_layanan_detail' =>  $now,
                ]
            );
            // update layanan header total tagihan
            $layananbaru->update([
                'total_layanan' => $tarifkarcis->TOTAL_TARIF_NEW + $tarifadm->TOTAL_TARIF_NEW,
                'tagihan_pribadi' => $totalpribadi,
                'tagihan_penjamin' => $totalpenjamin,
            ]);
            // insert tracer tc_tracer_header
            $tracerbaru = TracerDB::create([
                'kode_kunjungan' => $kunjungan->kode_kunjungan,
                'tgl_tracer' => Carbon::now()->format('Y-m-d'),
                'id_status_tracer' => 1,
                'cek_tracer' => "N",
            ]);

            // update antrian
            Antrian::where('kodebooking', $request->kodebooking)->update([
                "taskid" => $request->taskid,
                "keterangan" => $request->keterangan,
                "checkin" => Carbon::now(),
            ]);
            // update antrian bpjs
            $response = $this->update_antrian($request);
            // jika antrian berhasil diupdate di bpjs
            if ($response->metadata->code == 200) {

                $berhasil = [
                    "metadata" => [
                        "message" => "Ok",
                        "code" => 200,
                    ],
                ];
                return $berhasil;
            }
            // jika antrian gagal diupdate di bpjs
            else {
                return $response;
            }
        }
        // jika antrian tidak ditemukan
        else {
            return [
                "metadata" => [
                    "message" => "Kode booking tidak ditemukan",
                    "code" => 201,
                ],
            ];
        }
    }
    public function info_pasien_baru(Request $request)
    {
        // checking request
        $validator = Validator::make(request()->all(), [
            "nik" => "required",
        ]);
        if ($validator->fails()) {
            $response = [
                'metaData' => [
                    'code' => 400,
                    'message' => $validator->errors()->first(),
                ],
            ];
            return $response;
        }
        try {
            $request['norm'] = random_int(1, 999999);
            $request['status'] = 1;
            $pasien = Pasien::updateOrCreate(
                [
                    "norm" => $request->norm,
                    "nomorkartu" => $request->nomorkartu,
                    "nik" => $request->nik,
                ],
                [
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
                    "rw" => $request->rw,
                    "rt" => $request->rt,
                    "status" => $request->status,
                ]
            );
            $response = [
                "response" => [
                    "norm" => $pasien->norm,
                ],
                "metadata" => [
                    "message" => "Ok",
                    "code" => 200,
                ],
            ];
            return json_decode(json_encode($response));
        } catch (\Throwable $th) {
            $response = [
                "metadata" => [
                    "message" => "Error Code " . $th->getMessage(),
                    "code" => 400,
                ],
            ];
            return $response;
        }
    }
    public function jadwal_operasi_rs(Request $request)
    {
        $jadwalops = JadwalOperasi::whereBetween('tanggaloperasi', [$request->tanggalawal, $request->tanggalakhir])->get();
        $jadwals = [];
        foreach ($jadwalops as  $jadwalop) {
            $jadwals[] = [
                "kodebooking" => $jadwalop->kodebooking,
                "tanggaloperasi" => $jadwalop->tanggaloperasi,
                "jenistindakan" => $jadwalop->jenistindakan,
                "kodepoli" => $jadwalop->kodepoli,
                "namapoli" => $jadwalop->namapoli,
                "terlaksana" => $jadwalop->terlaksana,
                "nopeserta" => $jadwalop->nopeserta,
                "lastupdate" => $jadwalop->lastupdate,
            ];
        }
        $response = [
            "response" => [
                "list" => $jadwals
            ],
            "metadata" => [
                "message" => "Ok",
                "code" => 200
            ]
        ];
        return $response;
    }
    public function jadwal_operasi_pasien(Request $request)
    {
        $jadwalops = JadwalOperasi::where('nopeserta', $request->nopeserta)->get();
        $jadwals = [];
        foreach ($jadwalops as  $jadwalop) {
            $jadwals[] = [
                "kodebooking" => $jadwalop->kodebooking,
                "tanggaloperasi" => $jadwalop->tanggaloperasi,
                "jenistindakan" => $jadwalop->jenistindakan,
                "kodepoli" => $jadwalop->kodepoli,
                "namapoli" => $jadwalop->namapoli,
                "terlaksana" => $jadwalop->terlaksana,
                "nopeserta" => $jadwalop->nopeserta,
                "lastupdate" => $jadwalop->lastupdate,
            ];
        }
        $response = [
            "response" => [
                "list" => $jadwals
            ],
            "metadata" => [
                "message" => "Ok",
                "code" => 200
            ]
        ];
        return $response;
    }
}
