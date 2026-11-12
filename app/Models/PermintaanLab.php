<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanLab extends Model
{
    protected $table = 'permintaan_lab';
    protected $primaryKey = 'noorder';
    public $incrementing = false; 
    public $timestamps = false;

    protected $fillable = [
        'noorder', 'no_rawat', 'tgl_permintaan', 'jam_permintaan',
        'tgl_sampel', 'jam_sampel', 'tgl_hasil', 'jam_hasil',
        'dokter_perujuk', 'status', 'informasi_tambahan', 'diagnosa_klinis'
    ];

    public function pemeriksaan() {
        return $this->hasMany(PermintaanPemeriksaanLab::class, 'noorder', 'noorder');
    }

    public function regPeriksa()
    {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }

    /**
     * Sama dengan aturan LaboratoriumService::hapus: sampel diambil (Khanza hanya mengisi tgl_sampel)
     * atau hasil sudah ditagih. Setelah itu permintaan tidak bisa dibatalkan.
     */
    public function sudahDiproses(): bool
    {
        return ($this->tgl_sampel && $this->tgl_sampel !== '0000-00-00')
            || $this->pemeriksaan->contains('stts_bayar', 'Sudah');
    }

    /** Teks status yang sama dengan kartu permintaan IGD. */
    public function statusTeks(): string
    {
        if ($this->tgl_hasil && $this->tgl_hasil !== '0000-00-00') {
            return 'hasil keluar';
        }

        return $this->sudahDiproses() ? 'sampel diambil' : 'menunggu sampel';
    }
}
