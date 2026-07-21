<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPeriksaLab extends Model
{
    protected $table = 'detail_periksa_lab';
    protected $primaryKey = 'no_rawat'; 
    public $incrementing = false;
    public $timestamps = false;

    public function regPeriksa() {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }

    public function template() {
        return $this->belongsTo(TemplateLaboratorium::class, 'id_template', 'id_template');
    }
}
