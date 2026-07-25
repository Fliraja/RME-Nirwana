<?php

namespace App\Services\Ralan;

use App\Models\PemeriksaanRalan;
use Illuminate\Support\Facades\Auth;

class VitalSignService
{
    public function simpan(array $data): void
    {
        PemeriksaanRalan::updateOrCreate(
            ['no_rawat' => $data['no_rawat']],
            [
                'tgl_perawatan' => date('Y-m-d'),
                'jam_rawat'     => date('H:i:s'),
                'suhu_tubuh'    => $data['suhu_tubuh'] ?? '-',
                'tensi'         => $data['tensi'] ?? '-',
                'nadi'          => $data['nadi'] ?? '-',
                'respirasi'     => $data['respirasi'] ?? '-',
                'tinggi'        => $data['tinggi'] ?? '-',
                'berat'         => $data['berat'] ?? '-',
                'gcs'           => $data['gcs'] ?? '-',
                'kesadaran'     => $data['kesadaran'] ?? 'Compos Mentis',
                'alergi'        => $data['alergi'] ?? '-',
                'nip'           => Auth::user()->decrypted_id,
            ]
        );
    }
}
