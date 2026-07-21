<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JnsPerawatanLab extends Model
{
    protected $table = 'jns_perawatan_lab';
    protected $primaryKey = 'kd_jenis_prw';
    public $incrementing = false;

    public function templates() {
        return $this->hasMany(TemplateLaboratorium::class, 'kd_jenis_prw', 'kd_jenis_prw');
    }
}
