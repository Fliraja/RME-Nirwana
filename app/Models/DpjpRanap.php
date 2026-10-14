<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DpjpRanap extends Model
{
    protected $table = 'dpjp_ranap';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'no_rawat',
        'kd_dokter',
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
