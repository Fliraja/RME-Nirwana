<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResepDokterRacikanDetail extends Model
{
    protected $table = 'resep_dokter_racikan_detail';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'no_resep',
        'no_racik',
        'kode_brng',
        'p1',
        'p2',
        'kandungan',
        'jml'
    ];

    public function dataBarang()
    {
        return $this->belongsTo(DataBarang::class, 'kode_brng', 'kode_brng');
    }

    public function resepRacikan()
    {
        return $this->belongsTo(ResepDokterRacikan::class, 'no_resep', 'no_resep')
                    ->where('no_racik', $this->no_racik);
    }
}