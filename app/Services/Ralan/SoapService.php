<?php

namespace App\Services\Ralan;

use App\Models\PemeriksaanRalan;
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
    }
}
