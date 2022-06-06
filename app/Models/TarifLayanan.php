<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifLayanan extends Model
{
    use HasFactory;

    protected $fillable = [
       'kodetarif',
       'nosk',
       'namatarif',
       'tarifkelompokid',
       'tarifvclaimid',
       'keterangan',
       'status',
       'userid',
    ];

}
