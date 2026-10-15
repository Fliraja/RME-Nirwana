<?php

namespace App\Support;

use App\Models\RmeResumeRalan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TindakLanjutResolver
{
    public const PILIHAN = ['pulang', 'kontrol', 'rawat_inap', 'dirujuk', 'aps', 'meninggal'];

    /** Rawat inap hanya dari registrasi Khanza, tidak bisa dipilih manual. */
    public const PILIHAN_MANUAL = ['pulang', 'kontrol', 'dirujuk', 'aps', 'meninggal'];

    /** @return array{nilai:string, terkunci:bool, sumber:?string, detail:array<string,string>, tgl_kontrol:?string, ket:?string, label:string} */
    public function tentukan(string $noRawat, ?RmeResumeRalan $meta = null): array
    {
        $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first(['stts', 'status_lanjut']);

        if ($reg && $reg->stts === 'Meninggal') {
            return $this->hasil('meninggal', true, 'status registrasi', ['Status' => 'Meninggal']);
        }

        if ($reg && ($reg->status_lanjut === 'Ranap' || $reg->stts === 'Dirawat')) {
            $kamar = DB::table('kamar_inap')->where('no_rawat', $noRawat)
                ->orderBy('tgl_masuk')->orderBy('jam_masuk')->first(['tgl_masuk', 'kd_kamar']);
            $detail = $kamar ? ['Tanggal masuk' => self::tanggal($kamar->tgl_masuk), 'Kamar' => $kamar->kd_kamar] : [];
            return $this->hasil('rawat_inap', true, 'registrasi rawat inap', $detail);
        }

        $rujuk = DB::table('rujuk')->where('no_rawat', $noRawat)
            ->orderByDesc('tgl_rujuk')->first(['no_rujuk', 'rujuk_ke', 'tgl_rujuk']);
        if ($rujuk) {
            return $this->hasil('dirujuk', true, 'data rujukan', [
                'No. rujukan' => (string) $rujuk->no_rujuk,
                'Tujuan'      => (string) $rujuk->rujuk_ke,
                'Tanggal'     => self::tanggal($rujuk->tgl_rujuk),
            ], ket: $rujuk->rujuk_ke);
        }

        $kontrol = DB::table('bridging_sep as s')
            ->join('bridging_surat_kontrol_bpjs as k', 'k.no_sep', '=', 's.no_sep')
            ->where('s.no_rawat', $noRawat)
            ->orderByDesc('k.tgl_surat')->first(['k.no_surat', 'k.tgl_rencana']);
        if ($kontrol) {
            return $this->hasil('kontrol', true, 'surat kontrol BPJS', [
                'No. surat'       => (string) $kontrol->no_surat,
                'Rencana kontrol' => self::tanggal($kontrol->tgl_rencana),
            ], tgl: $kontrol->tgl_rencana);
        }

        if ($meta && in_array($meta->tindak_lanjut, self::PILIHAN_MANUAL, true)) {
            return $this->hasil($meta->tindak_lanjut, false, null, [],
                $meta->tindak_lanjut === 'kontrol' ? $meta->tgl_kontrol?->format('Y-m-d') : null,
                in_array($meta->tindak_lanjut, ['dirujuk', 'aps'], true) ? $meta->ket_tindak_lanjut : null);
        }

        return $this->hasil('pulang', false, null, []);
    }

    public static function label(string $nilai, ?string $tgl = null, ?string $ket = null): string
    {
        return match ($nilai) {
            'kontrol'    => $tgl ? 'Pulang, kontrol ulang ' . Carbon::parse($tgl)->translatedFormat('l, d F Y') : 'Pulang, kontrol ulang',
            'rawat_inap' => 'Dilanjutkan rawat inap',
            'dirujuk'    => $ket ? "Dirujuk ke {$ket}" : 'Dirujuk',
            'aps'        => 'Pulang atas permintaan sendiri',
            'meninggal'  => 'Meninggal dunia',
            default      => 'Pulang',
        };
    }

    private function hasil(string $nilai, bool $terkunci, ?string $sumber, array $detail, ?string $tgl = null, ?string $ket = null): array
    {
        return [
            'nilai'       => $nilai,
            'terkunci'    => $terkunci,
            'sumber'      => $sumber,
            'detail'      => $detail,
            'tgl_kontrol' => $tgl,
            'ket'         => $ket,
            'label'       => self::label($nilai, $tgl, $ket),
        ];
    }

    private static function tanggal(?string $tgl): string
    {
        return $tgl ? Carbon::parse($tgl)->translatedFormat('d F Y') : '-';
    }
}
