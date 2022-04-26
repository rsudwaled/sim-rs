<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VclaimBPJSController extends Controller
{
    public $baseUrl = 'https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev/';
    // public $baseUrl = 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/';

    public static function signature()
    {
        $cons_id =  env('VCLAIM_CONS_ID');
        $secretKey = env('VCLAIM_SECRET_KEY');
        $userkey = env('VCLAIM_USER_KEY');

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

    public function ref_provinsi()
    {
        $url = $this->baseUrl . "referensi/propinsi";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        if ($response->metaData->code == 200) {
            $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
            $response->response = json_decode($decrypt);
        }
        return $response;
    }
    public function ref_kabupaten(Request $request)
    {
        $url = $this->baseUrl . "referensi/kabupaten/propinsi/" . $request->provinsi;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        if ($response->metaData->code == 200) {
            $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
            $response->response = json_decode($decrypt);
        }
        return $response;
    }
    public function ref_kecamatan(Request $request)
    {
        $url = $this->baseUrl . "referensi/kecamatan/kabupaten/" . $request->kabupaten;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        $response = json_decode($response);
        if ($response->metaData->code == 200) {
            $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response->response);
            $response->response = json_decode($decrypt);
        }
        return $response;
    }
}
