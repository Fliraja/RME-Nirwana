<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemeriksaanRalan extends Model
{
    protected $table = 'pemeriksaan_ralan';
    protected $primaryKey = 'no_rawat'; 
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    public function regPeriksa() {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }
}
