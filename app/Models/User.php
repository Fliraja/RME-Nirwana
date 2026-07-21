<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user';
    protected $primaryKey = 'id_user';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function dokter()
    {
        $decryptedId = $this->getDecryptedIdAttribute();
        return $this->hasOne(Dokter::class, 'kd_dokter', 'id_user')
                    ->where('kd_dokter', $decryptedId);
    }
    
    public function getAuthPassword()
    {
        return $this->password; 
    }

    public function getDecryptedIdAttribute()
    {
        return session('decrypted_id');
    }

    public function getDokterDataAttribute()
    {
        $decryptedId = $this->decrypted_id;
        
        if (!$decryptedId) {
            return null;
        }
        
        if (!isset($this->relations['dokter_cache'])) {
            $this->relations['dokter_cache'] = Dokter::where('kd_dokter', $decryptedId)->first();
        }
        
        return $this->relations['dokter_cache'];
    }
}