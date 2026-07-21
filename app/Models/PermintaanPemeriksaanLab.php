<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanPemeriksaanLab extends Model
{
    protected $table = 'permintaan_pemeriksaan_lab';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['noorder', 'kd_jenis_prw', 'stts_bayar'];

    public function jenisPerawatan() {
        return $this->belongsTo(JnsPerawatanLab::class, 'kd_jenis_prw', 'kd_jenis_prw');
    }

    public function detailTemplate() {
        return $this->hasMany(PermintaanDetailPermintaanLab::class, 'noorder', 'noorder')
                    ->whereColumn('kd_jenis_prw', 'kd_jenis_prw');
    }
}
