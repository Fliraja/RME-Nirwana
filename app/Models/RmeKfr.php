<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Isian formulir KFR yang tidak punya kolom di tabel Khanza (penunjang, target, edukasi). */
class RmeKfr extends Model
{
    protected $table = 'rme_kfr';
    protected $primaryKey = 'no_rawat';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'disimpan_pada' => 'datetime',
        ];
    }
}
