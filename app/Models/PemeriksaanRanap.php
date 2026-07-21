<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemeriksaanRanap extends Model
{
    protected $table = 'pemeriksaan_ranap';
    protected $primaryKey = 'no_rawat'; 
    public $incrementing = false;
    public $timestamps = false;

    public function regPeriksa() {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }
}
