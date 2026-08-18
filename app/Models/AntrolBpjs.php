<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AntrolBpjs extends Model
{
    protected $table = 'antrol_bpjs';
    protected $primaryKey = 'no_rawat';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'no_rawat',
        'jam_periksa_perawat',
        'jam_periksa_dokter',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relation ke RegPeriksa jika ada
    public function regPeriksa()
    {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }
}
