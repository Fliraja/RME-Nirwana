<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tabel Khanza `penilaian_medis_igd`: satu asesmen medis per kunjungan IGD. */
class PenilaianMedisIgd extends Model
{
    protected $table = 'penilaian_medis_igd';
    protected $primaryKey = 'no_rawat';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'kd_dokter', 'kd_dokter');
    }
}
