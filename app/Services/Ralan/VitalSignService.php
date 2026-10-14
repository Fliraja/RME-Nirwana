<?php

namespace App\Services\Ralan;

use App\Models\PemeriksaanRalan;
use Illuminate\Support\Facades\Auth;

class VitalSignService
{
    public function simpan(array $data): void
    {
        $ttv = [
            'tgl_perawatan' => date('Y-m-d'),
            'jam_rawat'     => date('H:i:s'),
            'suhu_tubuh'    => $data['suhu_tubuh'] ?? '-',
            'tensi'         => $data['tensi'] ?? '-',
            'nadi'          => $data['nadi'] ?? '-',
            'respirasi'     => $data['respirasi'] ?? '-',
            'tinggi'        => $data['tinggi'] ?? '-',
            'berat'         => $data['berat'] ?? '-',
            'spo2'          => $data['spo2'] ?? '-',
            'gcs'           => $data['gcs'] ?? '-',
            'kesadaran'     => $data['kesadaran'] ?? 'Compos Mentis',
            'alergi'        => $data['alergi'] ?? '-',
            'nip'           => Auth::user()->decrypted_id,
        ];

        // Baris dipakai bersama SOAP: hanya kolom TTV yang diubah agar isian SOAP tetap.
        $query = PemeriksaanRalan::where('no_rawat', $data['no_rawat']);
        if ($query->exists()) {
            $query->update($ttv);
        } else {
            PemeriksaanRalan::create($ttv + [
                'no_rawat'  => $data['no_rawat'],
                'penilaian' => '',
                'rtl'       => '',
                'instruksi' => '',
                'evaluasi'  => '',
            ]);
        }
    }
}
