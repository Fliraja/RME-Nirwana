<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bangsal extends Model
{
    protected $table = 'bangsal';
    protected $primaryKey = 'kd_bangsal';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'kd_bangsal',
        'nm_bangsal',
        'status',
    ];

    public function kamar()
    {
        return $this->hasMany(Kamar::class, 'kd_bangsal', 'kd_bangsal');
    }
}
