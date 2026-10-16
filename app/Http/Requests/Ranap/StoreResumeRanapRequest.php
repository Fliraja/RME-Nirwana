<?php

namespace App\Http\Requests\Ranap;

use Illuminate\Foundation\Http\FormRequest;

class StoreResumeRanapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'              => 'required|string',
            'diagnosa_awal'         => 'nullable|string|max:100',
            'jalannya_penyakit'     => 'nullable|string',
            'pemeriksaan_fisik'     => 'nullable|string',
            'pemeriksaan_penunjang' => 'nullable|string',
            'diagnosa'              => 'nullable|array|max:5',
            'diagnosa.*'            => 'nullable|string|max:80',
            'prosedur'              => 'nullable|array|max:4',
            'prosedur.*'            => 'nullable|string|max:80',
            'diagnosa_utama'        => 'nullable|string|max:200',
            'diagnosa_sekunder'     => 'nullable|string|max:200',
            'diagnosa_sekunder2'    => 'nullable|string|max:200',
            'diagnosa_sekunder3'    => 'nullable|string|max:200',
            'diagnosa_sekunder4'    => 'nullable|string|max:200',
            'prosedur_utama'        => 'nullable|string|max:200',
            'prosedur_sekunder'     => 'nullable|string|max:200',
            'prosedur_sekunder2'    => 'nullable|string|max:200',
            'prosedur_sekunder3'    => 'nullable|string|max:200',
            'tindakan_dan_operasi'  => 'nullable|string',
            'kondisi_pulang_opsi'   => 'required|in:sembuh,aps,dirujuk,meninggal_kurang_48,meninggal_lebih_48',
            'nama_faskes_rujukan'   => 'nullable|required_if:kondisi_pulang_opsi,dirujuk|string|max:50',
            'obat_pulang'           => 'nullable|string',
            'edukasi'               => 'nullable|string',
            'kontrol'               => 'nullable|string',
            'kd_dokter'             => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'kondisi_pulang_opsi.required'    => 'Pilih kondisi pasien waktu pulang.',
            'kondisi_pulang_opsi.in'          => 'Kondisi pulang tidak dikenali.',
            'nama_faskes_rujukan.required_if' => 'Isi nama faskes rujukan tujuan.',
            'nama_faskes_rujukan.max'         => 'Nama faskes rujukan maksimal 50 karakter.',
            'diagnosa_awal.max'               => 'Diagnosa awal masuk maksimal 100 karakter.',
        ];
    }
}
