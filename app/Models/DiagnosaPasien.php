<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosaPasien extends Model
{
    protected $table = 'diagnosa_pasien';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function penyakit()
    {
        return $this->belongsTo(Penyakit::class, 'kd_penyakit', 'kd_penyakit');
    }
}
