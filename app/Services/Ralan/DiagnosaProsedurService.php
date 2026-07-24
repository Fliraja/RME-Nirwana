<?php

namespace App\Services\Ralan;

use App\Models\DiagnosaPasien;
use App\Models\ProsedurPasien;
use App\Support\KodeSearch;
use Illuminate\Support\Facades\DB;

class DiagnosaProsedurService
{
    public function dataPasien(string $noRawat): array
    {
        $diagnosa = DiagnosaPasien::with('penyakit')
            ->where('no_rawat', $noRawat)
            ->orderBy('prioritas', 'asc')
            ->get();

        $prosedur = ProsedurPasien::with('icd9')
            ->where('no_rawat', $noRawat)
            ->orderBy('prioritas', 'asc')
            ->get();

        return compact('diagnosa', 'prosedur');
    }

    /** @return array<int, array{id:string, text:string}> */
    public function searchIcd10(string $search): array
    {
        $normalized = KodeSearch::normalize($search);

        $rows = DB::table('penyakit')
            ->where(function ($q) use ($search, $normalized) {
                $q->where('kd_penyakit', 'like', "%$search%")
                  ->orWhere('nm_penyakit', 'like', "%$search%")
                  ->orWhereRaw("REPLACE(kd_penyakit, '.', '') LIKE ?", ["%$normalized%"]);
            })
            ->limit(20)
            ->get();

        return $rows->map(fn ($i) => [
            'id'   => $i->kd_penyakit,
            'text' => $i->kd_penyakit . ' - ' . $i->nm_penyakit,
        ])->all();
    }

    /** @return array<int, array{id:string, text:string}> */
    public function searchIcd9(string $search): array
    {
        $normalized = KodeSearch::normalize($search);

        $rows = DB::table('icd9')
            ->where(function ($q) use ($search, $normalized) {
                $q->where('kode', 'like', "%$search%")
                  ->orWhere('deskripsi_panjang', 'like', "%$search%")
                  ->orWhere('deskripsi_pendek', 'like', "%$search%")
                  ->orWhereRaw("REPLACE(kode, '.', '') LIKE ?", ["%$normalized%"]);
            })
            ->limit(20)
            ->get();

        return $rows->map(fn ($i) => [
            'id'   => $i->kode,
            'text' => $i->kode . ' - ' . ($i->deskripsi_panjang ?: $i->deskripsi_pendek),
        ])->all();
    }

    private function statusFor(string $noRawat): string
    {
        $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first();

        return ($reg && strtolower($reg->status_lanjut) === 'ranap') ? 'Ranap' : 'Ralan';
    }

    public function simpanDiagnosa(string $noRawat, array $kdList, ?string $prioritas, ?string $statusPenyakit): array
    {
        $status = $this->statusFor($noRawat);

        DB::transaction(function () use ($noRawat, $kdList, $prioritas, $statusPenyakit, $status) {
            foreach ($kdList as $index => $kd) {
                $hasPrimary = DB::table('diagnosa_pasien')
                    ->where('no_rawat', $noRawat)->where('prioritas', '1')->exists();

                $prio = ($prioritas && $index === 0) ? $prioritas : ($hasPrimary ? '2' : '1');

                DB::table('diagnosa_pasien')->updateOrInsert(
                    ['no_rawat' => $noRawat, 'kd_penyakit' => $kd, 'status' => $status],
                    ['prioritas' => $prio, 'status_penyakit' => $statusPenyakit ?? 'Lama']
                );
            }
        });

        return ['status' => 'success-diagnosa', 'message' => 'Diagnosa ICD-10 berhasil disimpan'];
    }

    public function simpanProsedur(string $noRawat, array $kodeList, ?string $jumlah): array
    {
        $status = $this->statusFor($noRawat);

        DB::transaction(function () use ($noRawat, $kodeList, $jumlah, $status) {
            foreach ($kodeList as $kd) {
                $hasPrimary = DB::table('prosedur_pasien')
                    ->where('no_rawat', $noRawat)->where('prioritas', '1')->exists();

                DB::table('prosedur_pasien')->updateOrInsert(
                    ['no_rawat' => $noRawat, 'kode' => $kd, 'status' => $status],
                    ['prioritas' => $hasPrimary ? '2' : '1', 'jumlah' => $jumlah ?? 1]
                );
            }
        });

        return ['status' => 'success-prosedur', 'message' => 'Prosedur ICD-9 berhasil disimpan'];
    }

    public function hapusDiagnosa(string $noRawat, string $kdPenyakit): array
    {
        DB::table('diagnosa_pasien')
            ->where('no_rawat', $noRawat)->where('kd_penyakit', $kdPenyakit)->delete();

        return ['status' => 'success-hapus-diagnosa', 'message' => 'Diagnosa berhasil dihapus'];
    }

    public function hapusProsedur(string $noRawat, string $kode): array
    {
        DB::table('prosedur_pasien')
            ->where('no_rawat', $noRawat)->where('kode', $kode)->delete();

        return ['status' => 'success-hapus-prosedur', 'message' => 'Prosedur berhasil dihapus'];
    }
}
