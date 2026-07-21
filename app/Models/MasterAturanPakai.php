<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterAturanPakai extends Model
{
    protected $table = 'master_aturan_pakai';

    protected $primaryKey = 'aturan';
    public $incrementing = false;
    protected $keyType = 'string';
    
    public $timestamps = false;

    protected $fillable = [
        'aturan'
    ];
}