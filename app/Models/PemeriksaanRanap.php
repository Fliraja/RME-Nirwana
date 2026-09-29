<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemeriksaanRanap extends Model
{
    protected $table = 'pemeriksaan_ranap';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'no_rawat',
        'tgl_perawatan',
        'jam_rawat',
        'suhu_tubuh',
        'tensi',
        'nadi',
        'respirasi',
        'tinggi',
        'berat',
        'spo2',
        'gcs',
        'kesadaran',
        'keluhan',
        'pemeriksaan',
        'alergi',
        'penilaian',
        'rtl',
        'instruksi',
        'evaluasi',
        'nip',
    ];

    public function regPeriksa()
    {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }

    public function pegawai()
    {
        return $this->belongsTo(User::class, 'nip', 'nik');
    }

    public function sbar()
    {
        return $this->hasOne(CatatanSbarRanap::class, 'no_rawat', 'no_rawat')
            ->whereColumn('tgl_perawatan', 'tgl_perawatan')
            ->whereColumn('jam_rawat', 'jam_rawat');
    }
}
