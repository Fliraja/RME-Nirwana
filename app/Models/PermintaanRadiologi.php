<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanRadiologi extends Model
{
    protected $table = 'permintaan_radiologi';
    protected $primaryKey = 'noorder';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'noorder', 
        'no_rawat', 
        'tgl_permintaan', 
        'jam_permintaan', 
        'tgl_sampel', 
        'jam_sampel', 
        'tgl_hasil', 
        'jam_hasil', 
        'dokter_perujuk', 
        'status', 
        'informasi_tambahan', 
        'diagnosa_klinis'   
    ];

    public function pemeriksaan()
    {
        return $this->hasMany(PermintaanPemeriksaanRadiologi::class, 'noorder', 'noorder');
    }

    public function regPeriksa()
    {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }
}
