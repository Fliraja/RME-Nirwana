<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanSbarRanap extends Model
{
    protected $table = 'catatan_sbar_ranap';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'no_rawat',
        'tgl_perawatan',
        'jam_rawat',
        'sbar',
        'nip',
    ];

    public function regPeriksa()
    {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }
}
