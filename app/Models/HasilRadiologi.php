<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilRadiologi extends Model
{
    protected $table = 'hasil_radiologi';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'no_rawat',
        'tgl_periksa',
        'jam',
        'hasil',
    ];

    public function regPeriksa()
    {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }
}
