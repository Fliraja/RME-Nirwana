<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Komponen racikan yang diberikan apotek; kunci: tgl_perawatan + jam + no_rawat + no_racik + kode_brng. Hanya dibaca. */
class DetailObatRacikan extends Model
{
    protected $table = 'detail_obat_racikan';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = ['*'];
}
