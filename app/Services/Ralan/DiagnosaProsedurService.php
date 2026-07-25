<?php

namespace App\Services\Ralan;

use App\Models\DiagnosaPasien;
use App\Models\ProsedurPasien;
use App\Support\KodeSearch;
use App\Support\PrioritasResolver;
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

    public function simpanDiagnosa(string $noRawat, array $kdList): array
    {
        $status = $this->statusFor($noRawat);

        DB::transaction(function () use ($noRawat, $kdList, $status) {
            $max = (int) DB::table('diagnosa_pasien')
                ->where('no_rawat', $noRawat)->where('status', $status)->max('prioritas');

            foreach (array_values($kdList) as $i => $kd) {
                DB::table('diagnosa_pasien')->updateOrInsert(
                    ['no_rawat' => $noRawat, 'kd_penyakit' => $kd, 'status' => $status],
                    ['prioritas' => $max + $i + 1] // status_penyakit dibiarkan null (diisi manual bila perlu)
                );
            }

            $this->recomputeDiagnosa($noRawat, $status);
        });

        return ['status' => 'success-diagnosa', 'message' => 'Diagnosa ICD-10 berhasil disimpan'];
    }

    public function simpanProsedur(string $noRawat, array $kodeList, array $jumlahList): array
    {
        $status = $this->statusFor($noRawat);

        DB::transaction(function () use ($noRawat, $kodeList, $jumlahList, $status) {
            $max = (int) DB::table('prosedur_pasien')
                ->where('no_rawat', $noRawat)->where('status', $status)->max('prioritas');

            foreach (array_values($kodeList) as $i => $kd) {
                $jumlah = $jumlahList[$i] ?? null;
                $jumlah = ($jumlah === '' || $jumlah === null) ? '1' : $jumlah; // kolom NOT NULL, default qty 1

                DB::table('prosedur_pasien')->updateOrInsert(
                    ['no_rawat' => $noRawat, 'kode' => $kd, 'status' => $status],
                    ['prioritas' => $max + $i + 1, 'jumlah' => $jumlah]
                );
            }

            $this->recomputeProsedur($noRawat, $status);
        });

        return ['status' => 'success-prosedur', 'message' => 'Prosedur ICD-9 berhasil disimpan'];
    }

    public function hapusDiagnosa(string $noRawat, string $kdPenyakit): array
    {
        $status = $this->statusFor($noRawat);

        DB::transaction(function () use ($noRawat, $kdPenyakit, $status) {
            DB::table('diagnosa_pasien')
                ->where('no_rawat', $noRawat)->where('kd_penyakit', $kdPenyakit)->where('status', $status)->delete();
            $this->recomputeDiagnosa($noRawat, $status);
        });

        return ['status' => 'success-hapus-diagnosa', 'message' => 'Diagnosa berhasil dihapus'];
    }

    public function hapusProsedur(string $noRawat, string $kode): array
    {
        $status = $this->statusFor($noRawat);

        DB::transaction(function () use ($noRawat, $kode, $status) {
            DB::table('prosedur_pasien')
                ->where('no_rawat', $noRawat)->where('kode', $kode)->where('status', $status)->delete();
            $this->recomputeProsedur($noRawat, $status);
        });

        return ['status' => 'success-hapus-prosedur', 'message' => 'Prosedur berhasil dihapus'];
    }

    /** Primer = kode ber-validcode '1' pertama; sisanya urut. */
    private function recomputeDiagnosa(string $noRawat, string $status): void
    {
        $items = DB::table('diagnosa_pasien as dp')
            ->leftJoin('penyakit as p', 'p.kd_penyakit', '=', 'dp.kd_penyakit')
            ->where('dp.no_rawat', $noRawat)->where('dp.status', $status)
            ->orderBy('dp.prioritas')
            ->selectRaw("dp.kd_penyakit as kode, COALESCE(p.validcode, '0') as validcode")
            ->get()
            ->map(fn ($r) => ['kode' => $r->kode, 'validcode' => $r->validcode])
            ->all();

        foreach (PrioritasResolver::hitung($items) as $kode => $prio) {
            DB::table('diagnosa_pasien')
                ->where(['no_rawat' => $noRawat, 'kd_penyakit' => $kode, 'status' => $status])
                ->update(['prioritas' => $prio]);
        }
    }

    private function recomputeProsedur(string $noRawat, string $status): void
    {
        $items = DB::table('prosedur_pasien as pp')
            ->leftJoin('icd9 as i', 'i.kode', '=', 'pp.kode')
            ->where('pp.no_rawat', $noRawat)->where('pp.status', $status)
            ->orderBy('pp.prioritas')
            ->selectRaw("pp.kode as kode, COALESCE(i.validcode, '0') as validcode")
            ->get()
            ->map(fn ($r) => ['kode' => $r->kode, 'validcode' => $r->validcode])
            ->all();

        foreach (PrioritasResolver::hitung($items) as $kode => $prio) {
            DB::table('prosedur_pasien')
                ->where(['no_rawat' => $noRawat, 'kode' => $kode, 'status' => $status])
                ->update(['prioritas' => $prio]);
        }
    }
}
