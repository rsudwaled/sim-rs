<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antrian extends Model
{
    use HasFactory;

    protected $fillable = [
        "kodebooking",
        "nomorkartu",
        "nik",
        "nohp",
        "kodepoli",
        "norm",
        "tanggalperiksa",
        "kodedokter",
        "jampraktek",
        "jeniskunjungan",
        "nomorreferensi",
        "jenispasien",
        "namapoli",
        "pasienbaru",
        "namadokter",
        "nomorantrean",
        "angkaantrean",
        "estimasidilayani",
        "sisakuotajkn",
        "kuotajkn",
        "sisakuotanonjkn",
        "kuotanonjkn",
        "taskid",
        "keterangan",
        "status_api",
    ];
}
