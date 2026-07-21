<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateLaboratorium extends Model
{
    protected $table = 'template_laboratorium';
    protected $primaryKey = 'id_template'; 
    public $incrementing = false;
    public $timestamps = false;

    public function detailPeriksa() {
        return $this->hasMany(DetailPeriksaLab::class, 'id_template', 'id_template');
    }

    public function jenisPerawatan() {
        return $this->belongsTo(JnsPerawatanLab::class, 'kd_jenis_prw', 'kd_jenis_prw');
    }
}
