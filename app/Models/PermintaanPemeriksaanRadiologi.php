<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanPemeriksaanRadiologi extends Model
{
    protected $table = 'permintaan_pemeriksaan_radiologi';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'noorder', 
        'kd_jenis_prw', 
        'stts_bayar'
    ];

    public function jenisPerawatan()
    {
        return $this->belongsTo(JnsPerawatanRadiologi::class, 'kd_jenis_prw', 'kd_jenis_prw');
    }
}
