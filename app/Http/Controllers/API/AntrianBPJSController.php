<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Poliklinik;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        $url = $this->baseUrl . "antrean/getlisttask";
        $signature = $this->signature();
        $client = new Client();
        $response = $client->request('POST', $url, [
            'headers' => $signature,
            'body' => json_encode([
                "kodebooking" => "16032021A001",
            ]),
        ]);
        $response = json_decode($response->getBody());
        $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
        $response->response = json_decode($decrypt);
        return $response;
    }
    public function dashboard_tanggal(Request $request)
    {
    }
    public function dashboard_bulan(Request $request)
    {
    }

    // function WS RS
    // public function token(Request $request)
    // {
    //     if (Auth::attempt(['email' => $request->header('x-username'), 'password' => $request->header('x-password')])) {
    //         $user = Auth::user();
    //         $success['token'] =  $user->createToken('MyApp')->plainTextToken;
    //         $response = [
    //             "response" => [
    //                 "token" => $success['token'],
    //             ],
    //             "metadata" => [
    //                 "code" => 200,
    //                 "message" => "OK"
    //             ]
    //         ];
    //         return $response;
    //     } else {
    //         $response = [
    //             "metadata" => [
    //                 "code" => 201,
    //                 "message" => "Unauthorized"
    //             ]
    //         ];
    //         return $response;
    //     }
    // }
    // public function status_antrian(Request $request)
    // {
    //     $jadwals = $this->ref_jadwal_dokter($request);
    //     if (isset($jadwals->response)) {
    //         $jadwal = collect($jadwals->response)->where('kodedokter', $request->kodedokter)->first();
    //         if (empty($jadwal)) {
    //             $response = [
    //                 "metadata" => [
    //                     "code" => 201,
    //                     "message" => "Tidak ada jadwal dokter dihari tersebut."
    //                 ]
    //             ];
    //             return $response;
    //         }
    //         $antrian = Antrian::where('kodepoli', $request->kodepoli)
    //             ->where('tanggalperiksa', $request->tanggalperiksa);
    //         $antrians = $antrian->count();
    //         $response = [
    //             "response" => [
    //                 "namapoli" => $jadwal->namapoli,
    //                 "namadokter" => $jadwal->namadokter,
    //                 "totalantrean" => $antrians,
    //                 "sisaantrean" => $jadwal->kapasitaspasien - $antrians,
    //                 "antreanpanggil" => "A-01",
    //                 "sisakuotajkn" => $jadwal->kapasitaspasien - $antrians,
    //                 "kuotajkn" => $jadwal->kapasitaspasien,
    //                 "sisakuotanonjkn" => $jadwal->kapasitaspasien - $antrians,
    //                 "kuotanonjkn" => $jadwal->kapasitaspasien,
    //                 "keterangan" => ""
    //             ],
    //             "metadata" => [
    //                 "message" => "Ok",
    //                 "code" => 200
    //             ]
    //         ];
    //         return $response;
    //     } else {
    //         return  $jadwals;
    //     }
    // }
    public function ambil_antrian(Request $request)
    {
        $antrians = Antrian::where('kodepoli', $request->kodepoli)
            ->where('tanggalperiksa', $request->tanggalperiksa)
            ->count();
        // get jadwal
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
            return  $jadwals;
        }
        // pasien baru / lama
        if (isset($request->norm)) {
            $request['pasienbaru'] = 0;
        } else {
            $request['norm'] = "PASIEN BARU";
            $request['pasienbaru'] = 1;
        }
        // pasien jkn / nonjkn
        // dd($request->nomorreferensi);
        if (isset($request->nomorreferensi)) {
            $request['jenispasien'] = 'JKN';
        } else {
            $request['jenispasien'] = 'NON JKN';
        }
        $request['kuotajkn'] = $jadwal->kapasitaspasien;
        $request['jampraktek'] = $jadwal->jadwal;
        $request['sisakuotajkn'] = $jadwal->kapasitaspasien - 1 - $antrians;
        $request['kuotanonjkn'] = $jadwal->kapasitaspasien;
        $request['sisakuotanonjkn'] = $jadwal->kapasitaspasien - 1 - $antrians;
        $request['namapoli'] = Poliklinik::where('kodesubspesialis', $request->kodepoli)->first()->namasubspesialis;
        $request['namadokter'] = Dokter::where('kodedokter', $request->kodedokter)->first()->namadokter;
        $request['estimasidilayani'] = 0;
        $request['keterangan'] = 'Peserta harap 30 menit lebih awal guna checkin dan pencatatan administrasi.';
        $request['nomorantrean'] = $request->kodepoli . '-' . $antrians + 1;
        $request['angkaantrean'] = $antrians + 1;
        $request['kodebooking'] = strtoupper(uniqid(6));

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
                "status_bpjs" => 1
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
            return $response;
        } else {
            return  $response;
        }


        //tambah antrian bpjs

    }
    // public function sisa_antrian(Request $request)
    // {
    //     $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
    //     $antrian['sisaantrean'] = "";
    //     $antrian['antreanpanggil'] = "";
    //     $antrian['waktutunggu'] = "";
    //     $antrian['keterangan'] = "";
    //     $response = [
    //         "response" => [
    //             "nomorantrean" => $antrian->nomorantrean,
    //             "namapoli" => $antrian->namapoli,
    //             "namadokter" => $antrian->namadokter,
    //             "sisaantrean" => $antrian->sisaantrean,
    //             "antreanpanggil" => $antrian->antreanpanggil,
    //             "waktutunggu" => $antrian->waktutunggu,
    //             "keterangan" => $antrian->keterangan,
    //         ],
    //         "metadata" => [
    //             "message" => "Ok",
    //             "code" => 200
    //         ]
    //     ];
    //     return $response;
    // }
    // public function batal_antrian(Request $request)
    // {
    //     $response = $this->batal_antrian_bpjs($request);
    //     if ($response->metadata->code == 200) {
    //         Antrian::where('kodebooking', $request->kodebooking)->update([
    //             "taskid" => 99,
    //             "keterangan" => $request->keterangan,
    //         ]);
    //         $response = [
    //             "metadata" => [
    //                 "message" => "Ok",
    //                 "code" => 200,
    //             ],
    //         ];
    //         return $response;
    //     } else {
    //         return $response;
    //     }
    // }
    public function checkin_antrian(Request $request)
    {
        Antrian::where('kodebooking', $request->kodebooking)->update([
            'taskid' => $request->taskid,
            'checkin' => $request->waktu,
            'keterangan' => 'Pasien sudah checkin',
        ]);
        $response = [
            "metadata" => [
                "message" => "Ok",
                "code" => 200,
            ],
        ];
        return $response;
    }
    public function info_pasien_baru(Request $request)
    {
        try {
            $request['norm'] = random_int(1, 999999);
            $request['status'] = 0;
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
            return $response;
        } catch (\Throwable $th) {
            $response = [
                "metadata" => [
                    "message" => "Error Code " . $th->getCode(),
                    "code" => 400,
                ],
            ];
            return $response;
        }
    }
    // public function jadwal_operasi_rs(Request $request)
    // {
    //     $jadwalops = JadwalOperasi::whereBetween('tanggaloperasi', [$request->tanggalawal, $request->tanggalakhir])->get();
    //     $jadwals = [];
    //     foreach ($jadwalops as  $jadwalop) {
    //         $jadwals[] = [
    //             "kodebooking" => $jadwalop->kodebooking,
    //             "tanggaloperasi" => $jadwalop->tanggaloperasi,
    //             "jenistindakan" => $jadwalop->jenistindakan,
    //             "kodepoli" => $jadwalop->kodepoli,
    //             "namapoli" => $jadwalop->namapoli,
    //             "terlaksana" => $jadwalop->terlaksana,
    //             "nopeserta" => $jadwalop->nopeserta,
    //             "lastupdate" => $jadwalop->lastupdate,
    //         ];
    //     }
    //     $response = [
    //         "response" => [
    //             "list" => $jadwals
    //         ],
    //         "metadata" => [
    //             "message" => "Ok",
    //             "code" => 200
    //         ]
    //     ];
    //     return $response;
    // }
    // public function jadwal_operasi_pasien(Request $request)
    // {
    //     $jadwalops = JadwalOperasi::where('nopeserta', $request->nopeserta)->get();
    //     $jadwals = [];
    //     foreach ($jadwalops as  $jadwalop) {
    //         $jadwals[] = [
    //             "kodebooking" => $jadwalop->kodebooking,
    //             "tanggaloperasi" => $jadwalop->tanggaloperasi,
    //             "jenistindakan" => $jadwalop->jenistindakan,
    //             "kodepoli" => $jadwalop->kodepoli,
    //             "namapoli" => $jadwalop->namapoli,
    //             "terlaksana" => $jadwalop->terlaksana,
    //             "nopeserta" => $jadwalop->nopeserta,
    //             "lastupdate" => $jadwalop->lastupdate,
    //         ];
    //     }
    //     $response = [
    //         "response" => [
    //             "list" => $jadwals
    //         ],
    //         "metadata" => [
    //             "message" => "Ok",
    //             "code" => 200
    //         ]
    //     ];
    //     return $response;
    // }
}
