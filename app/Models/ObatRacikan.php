<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Racikan yang diberikan apotek; kunci: tgl_perawatan + jam + no_rawat + no_racik. Hanya dibaca. */
class ObatRacikan extends Model
{
    protected $table = 'obat_racikan';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = ['*'];
}
