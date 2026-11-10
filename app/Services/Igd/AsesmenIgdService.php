<?php

namespace App\Services\Igd;

use App\Exceptions\AsesmenUsang;
use App\Models\Dokter;
use App\Models\RegPeriksa;
use App\Models\RmeAsesmenIgd;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Asesmen medis IGD (`penilaian_medis_igd`) beserta jejak penulis/pengubah dan pencegahan saling timpa. */
class AsesmenIgdService
{
    public const ORGAN = ['kepala', 'mata', 'gigi', 'leher', 'thoraks', 'abdomen', 'genital', 'ekstremitas'];

    public const LABEL_ORGAN = [
        'kepala' => 'kepala', 'mata' => 'mata', 'gigi' => 'gigi dan mulut', 'leher' => 'leher',
        'thoraks' => 'thoraks', 'abdomen' => 'abdomen', 'genital' => 'genital dan anus', 'ekstremitas' => 'ekstremitas',
    ];

    /** Nilai enum kolom Khanza, urut seperti di tabel. */
    public const PILIHAN = [
        'anamnesis' => ['Autoanamnesis', 'Alloanamnesis'],
        'keadaan'   => ['Sehat', 'Sakit Ringan', 'Sakit Sedang', 'Sakit Berat'],
        'kesadaran' => ['Compos Mentis', 'Apatis', 'Somnolen', 'Sopor', 'Koma'],
        'organ'     => ['Normal', 'Abnormal', 'Tidak Diperiksa'],
    ];

    /** Kolom isian (selain no_rawat, tanggal, kd_dokter); kolom NOT NULL, jadi isian kosong disimpan ''. */
    public const ISIAN = [
        'anamnesis', 'hubungan', 'keluhan_utama', 'rps', 'rpd', 'rpk', 'rpo', 'alergi', 'keadaan', 'gcs', 'kesadaran',
        'td', 'nadi', 'rr', 'suhu', 'spo', 'bb', 'tb', 'kepala', 'mata', 'gigi', 'leher', 'thoraks', 'abdomen', 'genital',
        'ekstremitas', 'ket_fisik', 'ket_lokalis', 'ekg', 'rad', 'lab', 'diagnosis', 'tata',
    ];

    /** @return array{asesmen: ?object, meta: ?RmeAsesmenIgd, sidik: ?string, penulis: ?string, jejak: ?string} */
    public function data(string $noRawat): array
    {
        $asesmen = DB::table('penilaian_medis_igd')->where('no_rawat', $noRawat)->first();
        $meta = $asesmen ? RmeAsesmenIgd::with(['pembuat', 'pengubah'])->find($noRawat) : null;
        $penulis = $asesmen ? $this->namaDokter($asesmen->kd_dokter) : null;

        return [
            'asesmen' => $asesmen,
            'meta'    => $meta,
            'sidik'   => $asesmen ? $this->sidik($asesmen) : null,
            'penulis' => $penulis,
            'jejak'   => $asesmen ? $this->jejak($asesmen, $meta, $penulis) : null,
        ];
    }

    /** Data cetak asesmen; null bila asesmen belum pernah ditulis. Tanda tangan = dokter penulis (kd_dokter). */
    public function dataPdf(string $noRawat): ?array
    {
        $asesmen = DB::table('penilaian_medis_igd')->where('no_rawat', $noRawat)->first();
        if (!$asesmen) {
            return null;
        }

        return [
            'asesmen' => $asesmen,
            'reg'     => RegPeriksa::with(['pasien', 'penjab'])->where('no_rawat', $noRawat)->first(),
            'dokter'  => Dokter::find($asesmen->kd_dokter),
        ];
    }

    /** Sidik isi baris, supaya perubahan dari Khanza desktop (yang tidak menyentuh meta) juga terdeteksi. */
    public function sidik(object $baris): string
    {
        $nilai = array_map(fn ($k) => (string) ($baris->{$k} ?? ''), array_merge(['no_rawat', 'tanggal', 'kd_dokter'], self::ISIAN));

        return sha1(implode("\x1f", $nilai));
    }

    /** @return array{sidik: string, jejak: string, alergi: string} */
    public function simpan(string $noRawat, array $data, string $kdDokter, ?string $sidikLama): array
    {
        return DB::transaction(function () use ($noRawat, $data, $kdDokter, $sidikLama) {
            // Simpan pertama belum punya baris untuk dikunci; kunci baris registrasi agar simpan bersamaan
            // untuk pasien yang sama antre, lalu simpan kedua terkena cek sidik di bawah (pola PR #17).
            DB::table('reg_periksa')->where('no_rawat', $noRawat)->lockForUpdate()->value('no_rawat');
            $lama = DB::table('penilaian_medis_igd')->where('no_rawat', $noRawat)->lockForUpdate()->first();
            $meta = RmeAsesmenIgd::find($noRawat);

            if ($lama && $this->sidik($lama) !== $sidikLama) {
                throw $this->usang($lama, $meta);
            }

            $isi = $this->isi($data);
            $sekarang = now()->startOfSecond();

            if ($lama) {
                DB::table('penilaian_medis_igd')->where('no_rawat', $noRawat)->update($isi);
            } else {
                DB::table('penilaian_medis_igd')->insert(['no_rawat' => $noRawat, 'tanggal' => $sekarang, 'kd_dokter' => $kdDokter] + $isi);
            }

            if ($meta) {
                $meta->update(['diubah_oleh' => $kdDokter, 'diubah_pada' => $sekarang]);
            } else {
                // Asesmen dari Khanza desktop: penulis dan waktu tulis pertama diambil dari barisnya.
                $meta = RmeAsesmenIgd::create([
                    'no_rawat'    => $noRawat,
                    'dibuat_oleh' => $lama->kd_dokter ?? $kdDokter,
                    'dibuat_pada' => $lama->tanggal ?? $sekarang,
                    'diubah_oleh' => $kdDokter,
                    'diubah_pada' => $sekarang,
                ]);
            }

            $baru = DB::table('penilaian_medis_igd')->where('no_rawat', $noRawat)->first();
            $meta->load(['pembuat', 'pengubah']);

            return [
                'sidik'  => $this->sidik($baru),
                'jejak'  => $this->jejak($baru, $meta, $this->namaDokter($baru->kd_dokter)),
                'alergi' => $baru->alergi,
            ];
        });
    }

    private function isi(array $data): array
    {
        $isi = [];
        foreach (self::ISIAN as $kolom) {
            $isi[$kolom] = trim((string) ($data[$kolom] ?? ''));
        }
        if ($isi['anamnesis'] !== 'Alloanamnesis') {
            $isi['hubungan'] = '';
        }

        return $isi;
    }

    /** "Ditulis dr. A 08.20 · diubah dr. B 09.05"; asesmen dari Khanza desktop tanpa jejak aplikasi ditandai sumbernya. */
    private function jejak(object $asesmen, ?RmeAsesmenIgd $meta, ?string $penulis): string
    {
        $teks = 'Ditulis ' . ($penulis ?? $asesmen->kd_dokter) . ' ' . self::waktu(Carbon::parse($asesmen->tanggal));
        if (!$meta) {
            return $teks . ' (Khanza desktop)';
        }

        $diubah = $meta->diubah_oleh !== $meta->dibuat_oleh || !$meta->diubah_pada->equalTo($meta->dibuat_pada);

        return $diubah
            ? $teks . ' · diubah ' . ($meta->pengubah?->nm_dokter ?? $meta->diubah_oleh) . ' ' . self::waktu($meta->diubah_pada)
            : $teks;
    }

    /** Pengubah terakhir yang diketahui: jejak aplikasi bila ada, selain itu penulis baris Khanza. */
    private function usang(object $lama, ?RmeAsesmenIgd $meta): AsesmenUsang
    {
        return $meta
            ? new AsesmenUsang($this->namaDokter($meta->diubah_oleh) ?? $meta->diubah_oleh, $meta->diubah_pada)
            : new AsesmenUsang($this->namaDokter($lama->kd_dokter) ?? $lama->kd_dokter, Carbon::parse($lama->tanggal));
    }

    private function namaDokter(string $kd): ?string
    {
        return Dokter::query()->whereKey($kd)->value('nm_dokter');
    }

    /** Jam saja bila hari ini; tanggal ikut bila lain hari (jaga malam melewati tengah malam). */
    public static function waktu(Carbon $waktu): string
    {
        return $waktu->isToday() ? $waktu->format('H.i') : $waktu->format('d/m H.i');
    }
}
