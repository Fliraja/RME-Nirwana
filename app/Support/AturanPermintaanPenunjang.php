<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Validasi permintaan lab/radiologi bersama IGD, ralan, dan ranap.
 * Kode di luar daftar aktif, kode dobel, dan sub-item dobel menabrak kunci Khanza (500); sub-item milik
 * paket lain malah tersimpan. Syarat aktif (status = '1') sama dengan daftar pencarian.
 */
final class AturanPermintaanPenunjang
{
    /** Proyek tanpa folder lang/: pesan validasi yang sampai ke dokter ditulis di sini. */
    public const PESAN = [
        'kd_jenis_prw.*.exists'       => 'Pemeriksaan tidak ditemukan atau sudah tidak aktif. Muat ulang halaman lalu pilih lagi.',
        'kd_jenis_prw.*.distinct'     => 'Pemeriksaan yang sama dipilih dua kali.',
        'kd_jenis_prw_rad.*.exists'   => 'Pemeriksaan tidak ditemukan atau sudah tidak aktif. Muat ulang halaman lalu pilih lagi.',
        'kd_jenis_prw_rad.*.distinct' => 'Pemeriksaan yang sama dipilih dua kali.',
        'detail_lab.*.*.distinct'     => 'Sub-item lab yang sama dipilih dua kali.',
    ];

    /** Aturan untuk tiap kode pemeriksaan; $tabel = jns_perawatan_lab atau jns_perawatan_radiologi. */
    public static function kode(string $tabel): array
    {
        return ['string', 'distinct', Rule::exists($tabel, 'kd_jenis_prw')->where('status', '1')];
    }

    /** Aturan detail_lab: sub-item (id_template) harus milik paket yang dikirim bersamanya. */
    public static function subItemLab(): array
    {
        return [
            'detail_lab'     => ['nullable', 'array', self::subItemSePaket(...)],
            'detail_lab.*'   => 'array',
            'detail_lab.*.*' => 'distinct',
        ];
    }

    private static function subItemSePaket(string $atribut, mixed $nilai, Closure $gagal): void
    {
        $pasangan = collect(is_array($nilai) ? $nilai : [])
            ->flatMap(fn ($id, $kd) => collect(is_array($id) ? $id : [$id])->map(fn ($i) => [(string) $kd, (string) $i]));
        if ($pasangan->isEmpty()) {
            return;
        }

        $milik = DB::table('template_laboratorium')->whereIn('id_template', $pasangan->pluck(1)->unique())
            ->pluck('kd_jenis_prw', 'id_template');
        if ($pasangan->contains(fn ($p) => (string) ($milik[$p[1]] ?? '') !== $p[0])) {
            $gagal('Sub-item lab tidak cocok dengan paketnya. Muat ulang halaman lalu pilih lagi.');
        }
    }
}
