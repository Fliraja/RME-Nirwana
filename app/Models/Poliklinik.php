<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poliklinik extends Model
{
    protected $table = 'poliklinik';
    protected $primaryKey = 'kd_poli';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}