<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResepObat extends Model
{
    protected $table = 'resep_obat';
    
    protected $primaryKey = 'no_resep';

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'no_resep',
        'tgl_perawatan',
        'jam',
        'no_rawat',
        'kd_dokter',
        'tgl_peresepan',
        'jam_peresepan',
        'status',
        'tgl_penyerahan',
        'jam_penyerahan',
    ];

    public function regPeriksa()
    {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }

    public function resepDokter()
    {
        return $this->hasMany(ResepDokter::class, 'no_resep', 'no_resep');
    }

    public function resepRacikan()
    {
        return $this->hasMany(ResepDokterRacikan::class, 'no_resep', 'no_resep');
    }
}