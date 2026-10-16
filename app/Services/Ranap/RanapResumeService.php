<?php

namespace App\Services\Ranap;

use App\Models\RegPeriksa;
use App\Models\ResumePasienRanap;
use App\Support\Alergi;
use Illuminate\Support\Facades\DB;

class RanapResumeService
{
    /**
     * Data lembar cetak resume: hanya identitas, kamar, DPJP, dan isi resume tersimpan.
     */
    public function getPdfData(string $noRawat): ?array
    {
        $resume = ResumePasienRanap::with('dokter')->find($noRawat);
        if (!$resume) {
            return null;
        }

        $reg = RegPeriksa::with([
                'kamarInap' => fn ($q) => $q->orderBy('tgl_masuk')->orderBy('jam_masuk'),
                'kamarInap.kamar.bangsal', 'pasien', 'penjab', 'dokter', 'dpjpRanap.dokter',
            ])
            ->where('no_rawat', $noRawat)
            ->first();

        $kamarList = $reg?->kamarInap ?? collect();

        return [
            'resume'       => $resume,
            'reg'          => $reg,
            'kamarMasuk'   => $kamarList->first(),
            'kamarAkhir'   => $kamarList->last(),
            'lamaRawat'    => (int) $kamarList->sum('lama'),
            'dpjp'         => $reg?->dpjpRanap->first()?->dokter ?? $reg?->dokter,
            'alergiPasien' => Alergi::terakhirRanap($noRawat),
            'kodeDiagnosa' => DB::table('diagnosa_pasien')->where('no_rawat', $noRawat)->where('status', 'Ranap')->orderBy('prioritas')->pluck('kd_penyakit')->all(),
            'kodeProsedur' => DB::table('prosedur_pasien')->where('no_rawat', $noRawat)->where('status', 'Ranap')->orderBy('prioritas')->pluck('kode')->all(),
        ];
    }
}
