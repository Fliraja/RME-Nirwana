<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPemberianObat extends Model
{
    protected $table = 'detail_pemberian_obat';
    protected $primaryKey = 'no_rawat'; 
    public $incrementing = false;
    public $timestamps = false;

    public function regPeriksa() {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }

    public function barang() {
        return $this->belongsTo(DataBarang::class, 'kode_brng', 'kode_brng');
    }
}
