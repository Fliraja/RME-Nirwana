<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResepDokterRacikan extends Model
{
    protected $table = 'resep_dokter_racikan';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'no_resep',
        'no_racik',
        'nama_racik',
        'kd_racik',
        'jml_dr',
        'aturan_pakai',
        'keterangan'
    ];

    public function resepObat()
    {
        return $this->belongsTo(ResepObat::class, 'no_resep', 'no_resep');
    }

    public function metodeRacik()
    {
        return $this->belongsTo(MetodeRacik::class, 'kd_racik', 'kd_racik');
    }

    public function detailRacikan()
    {
        return $this->hasMany(ResepDokterRacikanDetail::class, 'no_resep', 'no_resep')
                    ->whereColumn('no_racik', 'no_racik'); 
    }
}