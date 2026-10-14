<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResumePasienRanap extends Model
{
    protected $table = 'resume_pasien_ranap';
    protected $primaryKey = 'no_rawat';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'no_rawat',
        'kd_dokter',
        'diagnosa_awal',
        'alasan',
        'keluhan_utama',
        'pemeriksaan_fisik',
        'jalannya_penyakit',
        'pemeriksaan_penunjang',
        'hasil_laborat',
        'tindakan_dan_operasi',
        'obat_di_rs',
        'diagnosa_utama',
        'kd_diagnosa_utama',
        'diagnosa_sekunder',
        'kd_diagnosa_sekunder',
        'diagnosa_sekunder2',
        'kd_diagnosa_sekunder2',
        'diagnosa_sekunder3',
        'kd_diagnosa_sekunder3',
        'diagnosa_sekunder4',
        'kd_diagnosa_sekunder4',
        'prosedur_utama',
        'kd_prosedur_utama',
        'prosedur_sekunder',
        'kd_prosedur_sekunder',
        'prosedur_sekunder2',
        'kd_prosedur_sekunder2',
        'prosedur_sekunder3',
        'kd_prosedur_sekunder3',
        'alergi',
        'diet',
        'lab_belum',
        'edukasi',
        'cara_keluar',
        'ket_keluar',
        'keadaan',
        'ket_keadaan',
        'dilanjutkan',
        'ket_dilanjutkan',
        'kontrol',
        'obat_pulang',
    ];

    public function regPeriksa()
    {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'kd_dokter', 'kd_dokter');
    }
}
