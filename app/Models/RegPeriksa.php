<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegPeriksa extends Model
{
    protected $table = 'reg_periksa';
    protected $primaryKey = 'no_rawat';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'no_rkm_medis', 'no_rkm_medis');
    }

    public function poliklinik()
    {
        return $this->belongsTo(Poliklinik::class, 'kd_poli', 'kd_poli');
    }

    public function penjab()
    {
        return $this->belongsTo(Penjab::class, 'kd_pj', 'kd_pj');
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'kd_dokter', 'kd_dokter');
    }

    public function pemeriksaanRalan() {
        return $this->hasOne(PemeriksaanRalan::class, 'no_rawat', 'no_rawat');
    }

    public function pemeriksaanRanap() {
        return $this->hasOne(PemeriksaanRanap::class, 'no_rawat', 'no_rawat');
    }

    public function detailObat() {
        return $this->hasMany(DetailPemberianObat::class, 'no_rawat', 'no_rawat');
    }

    public function detailLab() {
        return $this->hasMany(DetailPeriksaLab::class, 'no_rawat', 'no_rawat');
    }

    public function gambarRadiologi() {
        return $this->hasMany(GambarRadiologi::class, 'no_rawat', 'no_rawat');
    }

    public function resepObat()
    {
        return $this->hasMany(ResepObat::class, 'no_rawat', 'no_rawat');
    }
}