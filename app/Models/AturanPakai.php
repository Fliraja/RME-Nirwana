<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Aturan pakai yang ditulis apotek saat validasi resep; kunci: tgl_perawatan + jam + no_rawat + kode_brng. Hanya dibaca. */
class AturanPakai extends Model
{
    protected $table = 'aturan_pakai';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = ['*'];
}
