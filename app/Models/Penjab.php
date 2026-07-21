<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjab extends Model
{
    protected $table = 'penjab';
    protected $primaryKey = 'kd_pj';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}