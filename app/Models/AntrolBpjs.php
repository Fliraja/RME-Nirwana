<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AntrolBpjs extends Model
{
    protected $table = 'antrol_bpjs';
    protected $primaryKey = 'no_rawat';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $guarded = [];
}
