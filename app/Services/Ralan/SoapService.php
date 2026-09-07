<?php

namespace App\Services\Ralan;

use App\Models\PemeriksaanRalan;
use App\Models\RegPeriksa;
use App\Support\AntrolBpjsUpdater;
use Illuminate\Support\Facades\Auth;

class SoapService
{
    public function simpan(array $data): void
    {
        PemeriksaanRalan::updateOrCreate(
            ['no_rawat' => $data['no_rawat']],
            [
                'tgl_perawatan' => date('Y-m-d'),
                'jam_rawat'     => date('H:i:s'),
                'keluhan'       => $data['keluhan'] ?? '',
                'pemeriksaan'   => $data['objek'] ?? '',
                'penilaian'     => $data['penilaian'] ?? '',
                'rtl'           => $data['plan'] ?? '',
                'instruksi'     => $data['instruksi'] ?? '',
                'nip'           => Auth::user()->decrypted_id,
                'kesadaran'     => 'Compos Mentis',
                'spo2'          => '-',
                'lingkar_perut' => '-',
                'evaluasi'      => '-',
            ]
        );

        $kdPj = $data['kd_pj'] ?? RegPeriksa::where('no_rawat', $data['no_rawat'])->value('kd_pj');
        AntrolBpjsUpdater::tandaiDokter($data['no_rawat'], $kdPj);
    }
}
