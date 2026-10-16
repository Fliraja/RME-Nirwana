<?php

namespace App\Http\Requests\Ranap;

use Illuminate\Foundation\Http\FormRequest;

class StoreResumeRanapRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hak tulis (dokter IGD penerima + DPJP) dicek di ResumeRanapService.
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'              => 'required|string|exists:reg_periksa,no_rawat',
            'keluhan_utama'         => 'nullable|string',
            'alasan'                => 'nullable|string|max:100',
            'diagnosa_awal'         => 'nullable|string|max:100',
            'jalannya_penyakit'     => 'nullable|string',
            'pemeriksaan_fisik'     => 'nullable|string',
            'hasil_laborat'         => 'nullable|string',
            'pemeriksaan_penunjang' => 'nullable|string',
            'diagnosa'              => 'nullable|array|max:5',
            'diagnosa.0'            => 'required_if:selesai,1,true|nullable|string|max:80',
            'diagnosa.*'            => 'nullable|string|max:80',
            'prosedur'              => 'nullable|array|max:4',
            'prosedur.*'            => 'nullable|string|max:80',
            'obat_di_rs'            => 'nullable|string',
            'kondisi_pulang_opsi'   => 'required_if:selesai,1,true|nullable|in:sembuh,membaik,aps,dirujuk,meninggal_kurang_48,meninggal_lebih_48',
            'nama_faskes_rujukan'   => 'nullable|required_if:kondisi_pulang_opsi,dirujuk|string|max:50',
            'obat_pulang'           => 'nullable|string',
            'edukasi'               => 'nullable|string',
            'kontrol'               => 'nullable|date',
            'diet'                  => 'nullable|string',
            'lab_belum'             => 'nullable|string',
            'selesai'               => 'nullable|boolean',
            'diubah_pada'           => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'diagnosa.0.required_if'          => 'Isi diagnosis utama sebelum menandai resume selesai.',
            'diagnosa.*.max'                  => 'Satu baris diagnosis maksimal 80 karakter.',
            'diagnosa.max'                    => 'Diagnosis maksimal 5 baris.',
            'prosedur.*.max'                  => 'Satu baris prosedur maksimal 80 karakter.',
            'prosedur.max'                    => 'Prosedur maksimal 4 baris.',
            'kondisi_pulang_opsi.required_if' => 'Pilih kondisi pasien waktu pulang sebelum menandai resume selesai.',
            'kondisi_pulang_opsi.in'          => 'Kondisi pulang tidak dikenali.',
            'nama_faskes_rujukan.required_if' => 'Isi nama faskes rujukan tujuan.',
            'nama_faskes_rujukan.max'         => 'Nama faskes rujukan maksimal 50 karakter.',
            'alasan.max'                      => 'Alasan dirawat maksimal 100 karakter.',
            'diagnosa_awal.max'               => 'Diagnosa awal masuk maksimal 100 karakter.',
            'kontrol.date'                    => 'Tanggal kontrol tidak valid.',
        ];
    }
}
