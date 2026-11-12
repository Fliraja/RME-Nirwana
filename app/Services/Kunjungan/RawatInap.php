<?php

namespace App\Services\Kunjungan;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Status rawat inap satu kunjungan, dipakai bersama IGD dan rawat jalan.
 * Setelah pasien naik ranap, permintaan baru lewat modul Ranap supaya tidak tercatat sebagai order rawat jalan.
 */
class RawatInap
{
    /** Status Ranap di registrasi atau sudah punya kamar (sama dengan badge daftar IGD). */
    public function sudahRanap(string $noRawat): bool
    {
        return DB::table('reg_periksa')->where('no_rawat', $noRawat)->where('status_lanjut', 'Ranap')->exists()
            || DB::table('kamar_inap')->where('no_rawat', $noRawat)->exists();
    }

    /**
     * Waktu masuk kamar pertama, atau null bila belum ada baris kamar_inap.
     * Status Ranap tanpa kamar sengaja tidak jatuh ke jam daftar: jam keluar dari IGD/poli tidak diketahui.
     */
    public function masukKamar(string $noRawat): ?Carbon
    {
        $kamar = DB::table('kamar_inap')->where('no_rawat', $noRawat)
            ->orderBy('tgl_masuk')->orderBy('jam_masuk')->first(['tgl_masuk', 'jam_masuk']);

        return $kamar ? Carbon::parse($kamar->tgl_masuk . ' ' . $kamar->jam_masuk) : null;
    }

    /** Pesan kunci permintaan, atau null bila pasien belum rawat inap. */
    public function pesanKunci(string $noRawat): ?string
    {
        if (!$this->sudahRanap($noRawat)) {
            return null;
        }

        $sejak = $this->masukKamar($noRawat);
        $waktu = $sejak
            ? 'sejak ' . $sejak->format($sejak->isToday() ? 'H.i' : 'd/m H.i')
            : '(kamar belum tercatat)';

        return "Pasien sudah rawat inap {$waktu}. Permintaan baru lewat modul Ranap.";
    }
}
