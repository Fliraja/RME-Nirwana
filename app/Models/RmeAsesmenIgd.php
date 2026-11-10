<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Jejak asesmen IGD yang ditulis lewat aplikasi (penulis pertama, pengubah terakhir). */
class RmeAsesmenIgd extends Model
{
    protected $table = 'rme_asesmen_igd';
    protected $primaryKey = 'no_rawat';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
            'diubah_pada' => 'datetime',
        ];
    }

    public function pembuat()
    {
        return $this->belongsTo(Dokter::class, 'dibuat_oleh', 'kd_dokter');
    }

    public function pengubah()
    {
        return $this->belongsTo(Dokter::class, 'diubah_oleh', 'kd_dokter');
    }
}
