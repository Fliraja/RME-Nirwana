<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JnsPerawatanRadiologi extends Model
{
    protected $table = 'jns_perawatan_radiologi';
    protected $primaryKey = 'kd_jenis_prw';
    public $incrementing = false;
    public $timestamps = false;

    public function scopeAktif($query)
    {
        return $query->where('status', '1');
    }
}
