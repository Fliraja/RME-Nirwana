<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RmeResumeRalan extends Model
{
    protected $table = 'rme_resume_ralan';
    protected $primaryKey = 'no_rawat';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tgl_kontrol'       => 'date:Y-m-d',
            'diverifikasi_pada' => 'datetime',
            'dibuat_pada'       => 'datetime',
        ];
    }

    public function verifikator()
    {
        return $this->belongsTo(Dokter::class, 'diverifikasi_oleh', 'kd_dokter');
    }
}
