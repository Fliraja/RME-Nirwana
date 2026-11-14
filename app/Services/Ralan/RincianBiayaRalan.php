<?php

namespace App\Services\Ralan;

use Illuminate\Support\Facades\DB;

/**
 * Rincian biaya di resume rawat jalan, diambil dari tabel billing Khanza yang ditulis kasir saat nota
 * dibuat (nota_jalan). Sebelum ada nota, rincian tidak dicetak dan barisnya diisi kasir dengan tangan.
 * Baris Ttl* adalah subtotal Khanza, jadi tidak ikut dijumlah.
 */
final class RincianBiayaRalan
{
    public const BARIS = [
        'administrasi' => 'Administrasi',
        'jasa_dokter'  => 'Jasa Dokter',
        'tindakan'     => 'Tindakan',
        'apotek'       => 'Apotek',
        'laboratorium' => 'Laboratorium',
        'lain'         => 'Lain-lain',
    ];

    /** @return array{baris: array<string, float>, total: float}|null */
    public function untuk(string $noRawat): ?array
    {
        if (!DB::table('nota_jalan')->where('no_rawat', $noRawat)->exists()) {
            return null;
        }

        $baris = array_fill_keys(array_keys(self::BARIS), 0.0);
        foreach (DB::table('billing')->where('no_rawat', $noRawat)->get(['status', 'nm_perawatan', 'totalbiaya']) as $b) {
            $kelompok = self::kelompok((string) $b->status, (string) $b->nm_perawatan);
            if ($kelompok !== null) {
                $baris[$kelompok] += (float) $b->totalbiaya;
            }
        }

        return ['baris' => $baris, 'total' => array_sum($baris)];
    }

    /**
     * Item tindakan dokter/perawat masuk Jasa Dokter bila namanya konsultasi atau jasa dokter (nama tarif
     * di prod: "Konsultasi Dokter Spesialis …", "Jasa Dokter Spesialis … (BPJS)"); selain itu Tindakan.
     */
    public static function kelompok(string $status, string $nama): ?string
    {
        return match ($status) {
            'Registrasi', 'Administrasi' => 'administrasi',
            'Ralan Dokter', 'Ralan Dokter Paramedis', 'Ralan Paramedis'
                => preg_match('/^\s*(konsultasi|jasa\s*dokter)/i', $nama) ? 'jasa_dokter' : 'tindakan',
            'Obat', 'Resep Pulang', 'Retur Obat' => 'apotek',
            'Laborat' => 'laboratorium',
            'Radiologi', 'Operasi', 'Tambahan', 'Potongan' => 'lain',
            default => null,
        };
    }
}
