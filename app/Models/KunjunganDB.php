<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganDB extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'ts_kunjungan';
    protected $primaryKey = 'kode_kunjungan';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'counter',
        // 'kode_kunjungan',
        'no_rm',
        'kode_unit',
        'tgl_masuk',
        'tgl_keluar',
        'kode_paramedis',
        'status_kunjungan',
    ];

    // public $timestamps = false;
}
