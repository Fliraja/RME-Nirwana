<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RmeResumeRanap extends Model
{
    protected $table = 'rme_resume_ranap';
    protected $primaryKey = 'no_rawat';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'diubah_pada'  => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    public function pengubah()
    {
        return $this->belongsTo(Dokter::class, 'diubah_oleh', 'kd_dokter');
    }

    public function penanda()
    {
        return $this->belongsTo(Dokter::class, 'selesai_oleh', 'kd_dokter');
    }
}
