<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasienDB extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'mt_pasien';
    protected $primaryKey = 'no_rm';
    public $incrementing = false;
    public $timestamps = false;
}
