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
use App\Models\SuratKontrol;
// use App\Models\SEP;
use App\Models\TarifLayananDB;
use App\Models\TarifLayananDetailDB;
use App\Models\TracerDB;
use App\Models\TransaksiDB;
use App\Models\UnitDB;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

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
        if (is_null($request->nomorkartu) || is_null($request->nomorreferensi)) {
            $request->nomorkartu = "";
            $request->nomorreferensi = "";
        }
        $client = new Client();
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
        // cek request
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "taskid" => "required",
            "waktu" => "required|numeric",
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'code' => 201,
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
        // cek request
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "keterangan" => "required",
        ]);
        if ($validator->fails()) {
            return $response = [
                'metadata' => [
                    'code' => 201,
                    'message' => $validator->errors()->first(),
                ],
            ];
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
        // cek request
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
        if ($response->metadata->code == 200) {
            $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
            $response->response = json_decode($decrypt);
        }
        return $response;
    }
    public function dashboard_tanggal(Request $request)
    {
        // cek request
        $validator = Validator::make(request()->all(), [
            "tanggal" => "required",
            "waktu" => "required",
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'code' => 201,
                    'message' => $validator->errors()->first(),
                ],
            ];
            return $response;
        }
        // proses
        $url = $this->baseUrl . "dashboard/waktutunggu/tanggal/" . $request->tanggal . "/waktu/" . $request->waktu;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        return $response;
    }
    public function dashboard_bulan(Request $request)
    {
        // cek request
        $validator = Validator::make(request()->all(), [
            "bulan" => "required",
            "tahun" => "required",
            "waktu" => "required",
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'code' => 201,
                    'message' => $validator->errors()->first(),
                ],
            ];
            return $response;
        }
        // proses
        $url = $this->baseUrl . "dashboard/waktutunggu/bulan/" . $request->bulan . "/tahun/" . $request->tahun . "/waktu/" . $request->waktu;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        return $response;
    }

    // function WS RS
    public function token(Request $request)
    {
        if (Auth::attempt(['username' => $request->header('x-username'), 'password' => $request->header('x-password')])) {
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
                    "message" => "Unauthorized (Username dan Password Salah)"
                ]
            ];
            return $response;
        }
    }
    public function auth_token(Request $request)
    {
        $aktif = Auth::user();
        if (empty($aktif)) {
            if ($request->hasHeader('x-token')) {
                if ($request->hasHeader('x-username')) {
                    // $user = User::where('username', $request->header('x-username'))->first();
                    $credentials = $request->header('x-token');
                    $token = PersonalAccessToken::findToken($credentials);
                    if (!$token) {
                        return $response = [
                            "metadata" => [
                                "code" => 201,
                                "message" => "Unauthorized (Token Salah)"
                            ]
                        ];
                    } else {
                        $user = $token->tokenable;
                        if ($user->username != $request->header('x-username')) {
                            return $response = [
                                "metadata" => [
                                    "code" => 201,
                                    "message" => "Unauthorized (Username tidak sesuai dengan token)"
                                ]
                            ];
                        } else {
                            return $response = [
                                "metadata" => [
                                    "code" => 200,
                                    "message" => "OK"
                                ]
                            ];
                        }
                    }
                } else {
                    return $response = [
                        "metadata" => [
                            "code" => 201,
                            "message" => "Silahkan isi header dengan x-username"
                        ]
                    ];
                }
            } else {
                return $response = [
                    "metadata" => [
                        "code" => 201,
                        "message" => "Silahkan isi header dengan x-token"
                    ]
                ];
            }
        } else {
            return $response = [
                "metadata" => [
                    "code" => 200,
                    "message" => "OK"
                ]
            ];
        }
    }
    public function status_antrian(Request $request)
    {
        // auth token
        $auth = $this->auth_token($request);
        if ($auth['metadata']['code'] != 200) {
            return $auth;
        }
        // check tanggal
        $time = Carbon::parse($request->tanggalperiksa)->endOfDay();
        if ($time->isPast()) {
            return [
                "metadata" => [
                    "code" => 201,
                    "message" => "Tanggal periksa sudah terlewat"
                ]
            ];
        } else {
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
                $antrianjkn = Antrian::where('kodepoli', $request->kodepoli)
                    ->where('tanggalperiksa', $request->tanggalperiksa)
                    ->where('jenispasien', "JKN")->count();
                $antriannonjkn = Antrian::where('kodepoli', $request->kodepoli)
                    ->where('tanggalperiksa', $request->tanggalperiksa)
                    ->where('jenispasien', "NON JKN")->count();
                $response = [
                    "response" => [
                        "namapoli" => $jadwal->namapoli,
                        "namadokter" => $jadwal->namadokter,
                        "totalantrean" => $antrians,
                        "sisaantrean" => $jadwal->kapasitaspasien - $antrians,
                        "antreanpanggil" => $nomorantean,
                        "sisakuotajkn" => $jadwal->kapasitaspasien * 80 / 100 -  $antrianjkn,
                        "kuotajkn" => $jadwal->kapasitaspasien * 80 / 100,
                        "sisakuotanonjkn" => ($jadwal->kapasitaspasien * 20 / 100) - $antriannonjkn,
                        "kuotanonjkn" =>  $jadwal->kapasitaspasien  * 20 / 100,
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
    }
    public function ambil_antrian(Request $request)
    {
        // auth token
        $auth = $this->auth_token($request);
        if ($auth['metadata']['code'] != 200) {
            return $auth;
        }
        // checking request
        $validator = Validator::make(request()->all(), [
            "nik" => "required|numeric|digits:16",
            "nohp" => "required|numeric",
            "kodepoli" => "required",
            // "norm" => "required",
            "tanggalperiksa" => "required",
            "kodedokter" => "required",
            "jampraktek" => "required",
            "jeniskunjungan" => "required|numeric",
            // "nomorreferensi" => "numeric",
            "nomorkartu" => "required|numeric|digits:13",
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'code' => 201,
                    'message' => $validator->errors()->first(),
                ],
            ];
            return $response;
        }
        // check backdate
        $time = Carbon::parse($request->tanggalperiksa)->endOfDay();
        if ($time->isPast()) {
            return [
                "metadata" => [
                    "code" => 201,
                    "message" => "Tanggal periksa sudah terlewat"
                ]
            ];
        }
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
        // proses ambil antrian
        $pasien = PasienDB::where('nik_Bpjs', $request->nik)->first();
        $vclaim = new VclaimBPJSController();
        // cek pasien baru hit info pasien baru
        if (empty($pasien)) {
            // $pasienbaru = $this->info_pasien_baru($request);
            // $request['norm'] = $pasienbaru->response->norm;
            // $request['pasienbaru'] = 1;
            // $request['nama'] = "Belum Didaftarkan";
            return $response = [
                "metadata" => [
                    "message" => "Pasien Baru",
                    "code" => 202,
                ],
            ];
        }
        // cek no kartu sesuai tidak
        else if ($pasien->no_Bpjs != $request->nomorkartu || $pasien->nik_bpjs != $request->nik) {
            return $response = [
                "metadata" => [
                    "message" => "NIK atau Nomor Kartu Tidak Sesuai dengan Data RM, (" . $pasien->no_Bpjs . ", " . $pasien->nik_bpjs . ")",
                    "code" => 201,
                ],
            ];
        }
        // cek pasien lama
        else {
            // cek jika jkn
            if (isset($request->nomorreferensi)) {
                $request['jenispasien'] = 'JKN';
                // cek keaktifan peserta
                try {
                    $response = $vclaim->peserta_nik($request);
                    $peserta = $response;
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
                } catch (\Throwable $th) {
                    return $response;
                }
                // pembuatan surat kontrol
                try {
                    $response = $vclaim->rujukan_nomor($request);
                    $rujukan = $response;
                    $request['jenisrujukan'] = $rujukan->response->asalFaskes;
                    $response = $vclaim->rujukan_jumlah_sep($request);
                    $jumlah_sep_rujukan = $response->response->jumlahSEP;
                    // jika jenis kunjungan "kontrol(3)" dan jumlah sep rujukan lebih dari 0
                    // if ($jumlah_sep_rujukan != null) {
                    //     if ($jumlah_sep_rujukan != 0) {
                    //         // cek hari ini
                    //         if ($time->isToday()) {
                    //             return [
                    //                 "metadata" => [
                    //                     "code" => 201,
                    //                     "message" => "Tanggal periksa tidak bisa untuk hari ini untuk membuat surat kontrol"
                    //                 ]
                    //             ];
                    //         }
                    //         if ($request->jeniskunjungan == 3) {
                    //             // buat surat control
                    //             $response = $vclaim->insert_rencana_kontrol($request);
                    //             // jika gagal buat surat kontrol
                    //             if ($response->metaData->code == 200) {
                    //                 $suratkontrol = $response->response;
                    //             } else {
                    //                 return $response;
                    //             }
                    //         } else {
                    //             // dd($jumlah_sep_rujukan);
                    //             return [
                    //                 "metadata" => [
                    //                     "message" => "Rujukan anda sudah digunakan untuk kunjungan pertama, untuk kunjungan berikutnya silahkan pilih jenis kunjungan Kontrol(3)",
                    //                     "code" => 201,
                    //                 ],
                    //             ];
                    //         }
                    //     }
                    // }

                    // error jika jenis kunjungan bukan "kontrol(3)" dan jumlah sep rujukan lebih dari 0
                    // else if ($request->jeniskunjungan != 3 && ($jumlah_sep_rujukan != null ||  $jumlah_sep_rujukan != 0)) {

                    // }
                } catch (\Throwable $th) {
                    return $response;
                }
            }
            // jika non-jkn harus pilih jenis kunjungan kontrol(3)
            else {
                $request['jenispasien'] = 'NON JKN';
                // error harus harus pilih jenis kunjungan kontrol(3)
                if ($request->jeniskunjungan != 3) {
                    return [
                        "metadata" => [
                            "message" => "Anda mendaftar tanpa surat Rujukan atau NON-JKN silahkan pilih jenis kunjungan Kontrol(3)",
                            "code" => 201,
                        ],
                    ];
                }
            }

            // ambil data pasien
            $request['norm'] = $pasien->no_rm;
            $request['nama'] = $pasien->nama_px;
            $request['pasienbaru'] = 0;

            // cek jadwal
            $jadwals = $this->ref_jadwal_dokter($request);
            if (isset($jadwals->response)) {
                $jadwal = collect($jadwals->response)->where('kodedokter', $request->kodedokter)->first();
                // jika jadwal tidak ada
                if (empty($jadwal)) {
                    $response = [
                        "metadata" => [
                            "code" => 201,
                            "message" => "Tidak ada jadwal dokter dihari tersebut."
                        ]
                    ];
                    return $response;
                }
                // ambil data jadwal
                else {
                    $request['namapoli'] = $jadwal->namapoli;
                    $request['namadokter'] = $jadwal->namadokter;
                }
            } else {
                // jika error
                return $jadwals;
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
            $antrianjkn = Antrian::where('kodepoli', $request->kodepoli)
                ->where('tanggalperiksa', $request->tanggalperiksa)
                ->where('jenispasien', "JKN")->count();
            $antriannonjkn = Antrian::where('kodepoli', $request->kodepoli)
                ->where('tanggalperiksa', $request->tanggalperiksa)
                ->where('jenispasien', "NON JKN")->count();
            $response = $this->tambah_antrian($request);
            if ($response->metadata->code == 200) {
                //tambah antrian database
                if (isset($suratkontrol)) {
                    $request["nomorsuratkontrol"] = $suratkontrol->noSuratKontrol;
                }
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
                    "nomorsuratkontrol" => $request->nomorsuratkontrol,
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
                        // "sisakuotajkn" => $request->sisakuotajkn,
                        // "kuotajkn" => $request->kuotajkn,
                        // "sisakuotanonjkn" => $request->sisakuotanonjkn,
                        // "kuotanonjkn" => $request->kuotanonjkn,
                        "sisakuotajkn" => $jadwal->kapasitaspasien * 80 / 100 -  $antrianjkn,
                        "kuotajkn" => $jadwal->kapasitaspasien * 80 / 100,
                        "sisakuotanonjkn" => ($jadwal->kapasitaspasien * 20 / 100) - $antriannonjkn,
                        "kuotanonjkn" =>  $jadwal->kapasitaspasien  * 20 / 100,

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
    }
    public function info_pasien_baru(Request $request)
    {
        // auth token
        $auth = $this->auth_token($request);
        if ($auth['metadata']['code'] != 200) {
            return $auth;
        }
        // end auth token
        // checking request
        $validator = Validator::make(request()->all(), [
            "nik" => "required",
            "nomorkartu" => "required",
            "nomorkk" => "required",
            "nama" => "required",
            "jeniskelamin" => "required",
            "tanggallahir" => "required",
            "nohp" => "required",
            "alamat" => "required",
        ]);
        if ($validator->fails()) {
            return [
                'metadata' => [
                    'code' => 201,
                    'message' => $validator->errors()->first(),
                ],
            ];
        }
        $pasien = PasienDB::where('nik_bpjs', $request->nik)->first();
        // cek jika pasien baru
        if (empty($pasien)) {
            // proses pendaftaran baru
            try {
                // checking norm terakhir
                $pasien_terakhir = PasienDB::latest()->first()->no_rm;
                $request['status'] = 1;
                $request['norm'] = $pasien_terakhir + 1;
                // insert pasien
                $pasien = PasienDB::updateOrCreate(
                    [
                        "no_Bpjs" => $request->nomorkartu,
                        "nik_bpjs" => $request->nik,
                        "no_rm" => $request->norm,
                    ],
                    [
                        // "nomorkk" => $request->nomorkk,
                        "nama_px" => $request->nama,
                        "jenis_kelamin" => $request->jeniskelamin,
                        "tgl_lahir" => $request->tanggallahir,
                        "no_tlp" => $request->nohp,
                        "alamat" => $request->alamat,
                        "kode_propinsi" => $request->kodeprop,
                        // "namaprop" => $request->namaprop,
                        "kode_kabupaten" => $request->kodedati2,
                        // "namadati2" => $request->namadati2,
                        "kode_kecamatan" => $request->kodekec,
                        // "namakec" => $request->namakec,
                        "kode_desa" => $request->kodekel,
                        // "namakel" => $request->namakel,
                        // "rw" => $request->rw,
                        // "rt" => $request->rt,
                        // "status" => $request->status,
                    ]
                );
                return  $response = [
                    "response" => [
                        "norm" => $request->norm,
                    ],
                    "metadata" => [
                        "message" => "Ok",
                        "code" => 200,
                    ],
                ];
            } catch (\Throwable $th) {
                $response = [
                    "metadata" => [
                        "message" => "Gagal Error Code " . $th->getMessage(),
                        "code" => 201,
                    ],
                ];
                return $response;
            }
        }
        // cek jika pasien lama
        else {
            return $response = [
                "response" => [
                    "norm" => $pasien->no_rm,
                ],
                "metadata" => [
                    "message" => "Ok",
                    "code" => 200,
                ],
            ];
        }
    }
    public function sisa_antrian(Request $request)
    {
        // auth token
        $auth = $this->auth_token($request);
        if ($auth['metadata']['code'] != 200) {
            return $auth;
        }
        // end auth token
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        // antrian ditermukan
        if ($antrian) {
            $sisaantrean = Antrian::where('taskid', "<=", 3)
                ->where('tanggalperiksa', $antrian->tanggalperiksa)
                ->where('taskid', ">=", 1)
                ->count();
            $antreanpanggil =  Antrian::where('taskid', "<=", 3)
                ->where('taskid', ">=", 1)
                ->where('tanggalperiksa', $antrian->tanggalperiksa)
                ->first();
            if (empty($antreanpanggil)) {
                $antreanpanggil['nomorantrean'] = '';
            }
            $antrian['waktutunggu'] = "5";
            $antrian['keterangan'] = "Info antrian anda";
            $response = [
                "response" => [
                    "nomorantrean" => $antrian->nomorantrean,
                    "namapoli" => $antrian->namapoli,
                    "namadokter" => $antrian->namadokter,
                    "sisaantrean" => $sisaantrean,
                    "antreanpanggil" => $antreanpanggil['nomorantrean'],
                    "waktutunggu" => $antrian->waktutunggu * 60 * ($sisaantrean),
                    "keterangan" => $antrian->keterangan,
                ],
                "metadata" => [
                    "message" => "Ok",
                    "code" => 200
                ]
            ];
            return $response;
        }
        // antrian tidak ditermukan
        else {
            return $response = [
                "metadata" => [
                    "message" => "Antrian tidak ditemukan",
                    "code" => 201,
                ],
            ];
        }
    }
    public function batal_antrian(Request $request)
    {
        // auth token
        $auth = $this->auth_token($request);
        if ($auth['metadata']['code'] != 200) {
            return $auth;
        }
        // cek request
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "keterangan" => "required",
        ]);
        if ($validator->fails()) {
            return $response = [
                'metadata' => [
                    'code' => 201,
                    'message' => $validator->errors()->first(),
                ],
            ];
        }
        $response = $this->batal_antrian_bpjs($request);
        Antrian::where('kodebooking', $request->kodebooking)->update([
            "taskid" => 99,
            "status_api" => 1,
            "keterangan" => $request->keterangan,
        ]);
        return $response;
    }
    public function checkin_antrian(Request $request)
    {
        // cek printer
        $connector = new WindowsPrintConnector("smb://PRINTER:qweqwe@192.168.2.133/Printer Receipt");
        $printer = new Printer($connector);
        $printer->close();
        // auth token
        $auth = $this->auth_token($request);
        if ($auth['metadata']['code'] != 200) {
            return $auth;
        }
        // checking request
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "waktu" => "required",
        ]);
        if ($validator->fails()) {
            return $response = [
                'metaData' => [
                    'code' => 400,
                    'message' => $validator->errors()->first(),
                ],
            ];
        }
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        // jika antrian ditemukan
        if (isset($antrian)) {
            $unit = UnitDB::firstWhere('KDPOLI', $antrian->kodepoli);
            $tarifkarcis = TarifLayananDetailDB::firstWhere('KODE_TARIF_DETAIL', $unit->kode_tarif_karcis);
            $tarifadm = TarifLayananDetailDB::firstWhere('KODE_TARIF_DETAIL', $unit->kode_tarif_adm);
            // jika pasien jkn
            if ($antrian->jenispasien == "JKN") {
                $request['status_api'] = 1;
                // jika pasien lama
                if ($antrian->pasienbaru == 0) {
                    $request['taskid'] = 3;
                    $request['keterangan'] = "Silahkan menunggu panggilan di poliklinik";
                }
                // jika pasien baru
                else if ($antrian->pasienbaru == 1) {
                    $request['taskid'] = 1;
                    $request['keterangan'] = "Silahkan menunggu panggilan di loket pendaftaran pasien baru";
                }
                // api vclaim
                $vclaim = new VclaimBPJSController();
                // rujukan
                $request['noKartu'] = $antrian->nomorkartu;
                $request['tglSep'] = Carbon::now()->format('Y-m-d');
                $request['noMR'] = $antrian->norm;
                $request['nik'] = $antrian->nik;
                $request['nohp'] = $antrian->nohp;
                $request['kodedokter'] = $antrian->kodedokter;
                $request['nomorreferensi'] = $antrian->nomorreferensi;
                $data = $vclaim->rujukan_nomor($request);
                if ($data->metaData->code == 200) {
                    $rujukan = $data->response->rujukan;
                    $peserta = $rujukan->peserta;
                    $diganosa = $rujukan->diagnosa;
                    $tujuan = $rujukan->poliRujukan;
                    // tujuan rujukan
                    $request['ppkPelayanan'] = "1018R001";
                    $request['jnsPelayanan'] = "2";
                    // peserta
                    $request['klsRawatHak'] = $peserta->hakKelas->kode;
                    $request['klsRawatNaik'] = "";
                    // $request['pembiayaan'] = $peserta->jenisPeserta->kode;
                    // $request['penanggungJawab'] =  $peserta->jenisPeserta->keterangan;
                    // asal rujukan
                    $request['asalRujukan'] = $data->response->asalFaskes;
                    $request['tglRujukan'] = $rujukan->tglKunjungan;
                    $request['noRujukan'] =   $antrian->nomorreferensi;
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
                // insert sep
                $vclaim = new VclaimBPJSController();
                $request['nomorsuratkontrol'] = $antrian->nomorsuratkontrol;
                $sep = $vclaim->insert_sep($request);
                if ($sep->metaData->code == 200) {
                    $printer = new Printer($connector);
                    $sep = $sep->response;
                    $printer->setFont(1);
                    $request["nomorsep"] = $sep->sep->noSep;
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    $printer->setEmphasis(true);
                    $printer->text("KARTU SEP BPJS\n");
                    $printer->text("BADAN RUSUD WALED\n");
                    $printer->setEmphasis(false);
                    $printer->text("---------------------------------------------\n");
                    $printer->setJustification(Printer::JUSTIFY_LEFT);
                    $printer->text("No SEP : " . $sep->sep->noSep . "\n");
                    $printer->text("Tgl SEP : " . $sep->sep->tglSep . "\n");
                    $printer->text("No Kartu : " . $sep->sep->peserta->noKartu . "\n");
                    $printer->text("Nama Peserta : " . $sep->sep->peserta->nama . "\n");
                    $printer->text("Tgl Lahir : " . $sep->sep->peserta->tglLahir . "\n");
                    $printer->text("Telepon : \n");
                    $printer->text("Jenis Peserta : " . $sep->sep->peserta->jnsPeserta . "\n");
                    $printer->text("COB : -\n");
                    $printer->text("Jenis Pelayanan : " . $sep->sep->jnsPelayanan . "\n");
                    $printer->text("Kelas Rawat : " . $sep->sep->kelasRawat . "\n");
                    $printer->text("Poli / Spesialis : " . $sep->sep->poli . "\n");
                    $printer->text("Faskes Perujuk : -\n");
                    $printer->text("Diagnosa Awal : " . $sep->sep->diagnosa . "\n");
                    $printer->text("Catatan : " . $sep->sep->catatan . "\n\n");
                    $printer->text("Cetakan : " . Carbon::now() . "\n");
                    $printer->cut();
                    $printer->close();
                }
                // gagal buat sep
                else {
                    return [
                        "metadata" => [
                            "message" => $sep->metaData->message,
                            "code" => 201,
                        ],
                    ];
                }
                // rj jkn tipe transaki 2 status layanan 2 status layanan detail opn
                $tipetransaksi = 2;
                $statuslayanan = 2;
                // rj jkn masuk ke tagihan penjamin
                $tagihanpenjamin = $tarifkarcis->TOTAL_TARIF_NEW;
                $totalpenjamin =  $tarifkarcis->TOTAL_TARIF_NEW + $tarifadm->TOTAL_TARIF_NEW;
                $tagihanpribadi = 0;
                $totalpribadi =  0;
            }
            // jika pasien non jkn
            else {
                $request['taskid'] = 1;
                $request['status_api'] = 0;
                $request['keterangan'] = "Silahkan menunggu panggilan di loket pendaftaran pasien baru";
                // rj umum tipe transaki 1 status layanan 1 status layanan detail opn
                $tipetransaksi = 1;
                $statuslayanan = 1;
                // rj umum masuk ke tagihan pribadi
                $tagihanpenjamin = 0;
                $totalpenjamin =  0;
                $tagihanpribadi = $tarifkarcis->TOTAL_TARIF_NEW;
                $totalpribadi = $tarifkarcis->TOTAL_TARIF_NEW + $tarifadm->TOTAL_TARIF_NEW;
            }
            // print antrian
            try {
                if ($antrian->pasienbaru == 1) {
                    $pasienbaru = "BARU";
                } else {
                    $pasienbaru = "LAMA";
                }
                $printer = new Printer($connector);
                $printer->setFont(1);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setEmphasis(true);
                $printer->text("RSUD Waled\n");
                $printer->setEmphasis(false);
                $printer->text("Melayani Dengan Sepenuh Hati\n");
                $printer->text("------------------------------------------------\n");
                $printer->text("Karcis Antrian Rawat Jalan\n");
                $printer->text("Nomor / Angka /Jenis Pasien :\n");
                $printer->setTextSize(2, 2);
                $printer->text($antrian->nomorantrean . "/" . $antrian->angkaantrean . "/" . $antrian->jenispasien . " " . $pasienbaru . "\n");
                $printer->setTextSize(1, 1);
                $printer->text("Kode Booking : " . $antrian->kodebooking . "\n\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->text("No RM : " . $antrian->norm . "\n");
                $printer->text("NIK : " . $antrian->nik . "\n");
                if ($antrian->nomorkartu != "") {
                    $printer->text("No Peserta : " . $antrian->nomorkartu . "\n");
                }
                if ($antrian->nomorreferensi != "") {
                    $printer->text("No Rujukan : " . $antrian->nomorreferensi . "\n");
                }
                $printer->text("Nama : " . $antrian->nama . "\n\n");
                $printer->text("Poliklinik : " . $antrian->namapoli . "\n");
                $printer->text("Kunjungan : " . $antrian->jeniskunjungan . "\n");
                $printer->text("Dokter : " . $antrian->namadokter . "\n");
                $printer->text("Tanggal : " . Carbon::parse($antrian->tanggalperiksa)->format('d M Y') . "\n");
                $printer->text("Print : " . Carbon::now() . "\n\n");
                $printer->text("Terima kasih atas kepercayaan anda. \n");
                $printer->cut();
                $printer->close();
            } catch (\Throwable $th) {
                //throw $th;
            }
            // update antrian bpjs
            $response = $this->update_antrian($request);
            // jika antrian berhasil diupdate di bpjs
            if ($response->metadata->code == 200) {
                $request['waktu'] = Carbon::createFromTimestamp($request->waktu / 1000)->toDateTimeString();
                $request['waktu'] = Carbon::parse($request->waktu);
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
                        'tgl_masuk' => $request->waktu,
                        'kode_paramedis' => $antrian->kodedokter,
                        'status_kunjungan' => 1,
                    ]
                );
                // insert layanan header dan detail karcis admin konsul 25 + 5 = 30
                $kunjungan = KunjunganDB::where('no_rm', $antrian->norm)->where('counter', $kunjunganbaru->counter)->first();
                $trx_lama = TransaksiDB::where('unit', $unit->kode_unit)
                    ->whereBetween('tgl', [Carbon::now()->startOfDay(), [Carbon::now()->endOfDay()]])
                    ->count();
                $kodelayanan = $unit->KDPOLI . $request->waktu->format('y') . $request->waktu->format('m') . $request->waktu->format('d')  . str_pad($trx_lama + 1, 6, '0', STR_PAD_LEFT);
                $trx_baru = TransaksiDB::create([
                    'tgl' => $request->waktu,
                    'no_trx_layanan' => $kodelayanan,
                    'unit' => $unit->kode_unit,
                ]);
                // insert layanan header
                $layananbaru = LayananDB::create(
                    [
                        'kode_layanan_header' => $kodelayanan,
                        'tgl_entry' => $request->waktu,
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
                        'id_layanan_detail' => "DET" . $request->waktu->yearIso . $request->waktu->month . $request->waktu->day .  "001",
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
                        'tgl_layanan_detail' =>  $request->waktu,
                    ]
                );
                // insert layanan detail admin
                $adm = LayananDetailDB::create(
                    [
                        'id_layanan_detail' => "DET" . $request->waktu->yearIso . $request->waktu->month . $request->waktu->day .  "01",
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
                        'tgl_layanan_detail' =>  $request->waktu,
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
                    "status_api" => $request->status_api,
                    "nomorsep" => $request->nomorsep,
                    "keterangan" => $request->keterangan,
                    "taskid1" => $request->waktu,
                ]);
                return [
                    "metadata" => [
                        "message" => "Ok",
                        "code" => 200,
                    ],
                ];
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
    public function jadwal_operasi_rs(Request $request)
    {
        // auth token
        $auth = $this->auth_token($request);
        if ($auth['metadata']['code'] != 200) {
            return $auth;
        }
        // checking request
        $validator = Validator::make(request()->all(), [
            "tanggalawal" => "required",
            "tanggalakhir" => "required",
        ]);
        if ($validator->fails()) {
            return [
                'metadata' => [
                    'code' => 201,
                    'message' => $validator->errors()->first(),
                ],
            ];
        }
        // end auth token
        $jadwalops = JadwalOperasi::whereBetween('tanggaloperasi', [$request->tanggalawal, $request->tanggalakhir])->get();
        $jadwals = [];
        foreach ($jadwalops as  $jadwalop) {
            if ($jadwalop->terlaksana == "0") {
                $terlaksana = "Belum";
            } else {
                $terlaksana = "Sudah";
            }
            $jadwals[] = [
                "kodebooking" => $jadwalop->kodebooking,
                "tanggaloperasi" => $jadwalop->tanggaloperasi,
                "jenistindakan" => $jadwalop->jenistindakan,
                "kodepoli" => $jadwalop->kodepoli,
                "namapoli" => $jadwalop->namapoli,
                "terlaksana" => $terlaksana,
                "nopeserta" => $jadwalop->nopeserta,
                "lastupdate" => Carbon::parse($jadwalop->updated_at)->format('Y-m-d H:i:s'),
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
        // auth token
        $auth = $this->auth_token($request);
        if ($auth['metadata']['code'] != 200) {
            return $auth;
        }
        // checking request
        $validator = Validator::make(request()->all(), [
            "nopeserta" => "required|digits:13",
        ]);
        if ($validator->fails()) {
            return [
                'metadata' => [
                    'code' => 201,
                    'message' => $validator->errors()->first(),
                ],
            ];
        }
        // end auth token
        $jadwalops = JadwalOperasi::where('nopeserta', $request->nopeserta)
            ->where('tanggaloperasi', '>=', Carbon::now()->format('Y-m-d'))
            ->get();

        $jadwals = [];
        foreach ($jadwalops as  $jadwalop) {
            if ($jadwalop->terlaksana == "0") {
                $terlaksana = "Belum";
            } else {
                $terlaksana = "Sudah";
            }
            $jadwals[] = [
                "kodebooking" => $jadwalop->kodebooking,
                "tanggaloperasi" => $jadwalop->tanggaloperasi,
                "jenistindakan" => $jadwalop->jenistindakan,
                "kodepoli" => $jadwalop->kodepoli,
                "namapoli" => $jadwalop->namapoli,
                "terlaksana" => $terlaksana,
                "nopeserta" => $jadwalop->nopeserta,
                "lastupdate" => Carbon::parse($jadwalop->updated_at)->format('Y-m-d H:i:s'),
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
