<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProsedurPasien extends Model
{
    protected $table = 'prosedur_pasien';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function icd9()
    {
        return $this->belongsTo(Icd9::class, 'kode', 'kode');
    }
}
