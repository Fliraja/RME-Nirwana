<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataBarang extends Model
{
    protected $table = 'databarang';
    protected $primaryKey = 'kode_brng'; 
    public $incrementing = false;
    public $timestamps = false;

    public function detailPemberian() {
        return $this->hasMany(DetailPemberianObat::class, 'kode_brng', 'kode_brng');
    }

    public function resepDokter()
    {
        return $this->hasMany(ResepDokter::class, 'kode_brng', 'kode_brng');
    }

    public function detailRacikan()
    {
        return $this->hasMany(ResepDokterRacikanDetail::class, 'kode_brng', 'kode_brng');
    }
}
