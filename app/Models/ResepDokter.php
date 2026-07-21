<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResepDokter extends Model
{
    protected $table = 'resep_dokter';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'no_resep',
        'kode_brng',
        'jml',
        'aturan_pakai'
    ];

    public function resepObat()
    {
        return $this->belongsTo(ResepObat::class, 'no_resep', 'no_resep');
    }

    public function dataBarang()
    {
        return $this->belongsTo(DataBarang::class, 'kode_brng', 'kode_brng');
    }
}