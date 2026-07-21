<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanDetailPermintaanLab extends Model
{
    protected $table = 'permintaan_detail_permintaan_lab';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['noorder', 'kd_jenis_prw', 'id_template', 'stts_bayar'];

    public function template() {
        return $this->belongsTo(TemplateLaboratorium::class, 'id_template', 'id_template');
    }
}
