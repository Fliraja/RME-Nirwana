<?php

namespace App\Http\Requests\Ralan;

use App\Support\TindakLanjutResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifikasiResumeRalanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'              => 'required|string|exists:reg_periksa,no_rawat',
            'diagnosa'              => 'required|array|min:1|max:5',
            'diagnosa.0'            => 'required|string|max:80',
            'diagnosa.*'            => 'nullable|string|max:80',
            'prosedur'              => 'nullable|array|max:4',
            'prosedur.*'            => 'nullable|string|max:80',
            'keluhan_utama'         => 'nullable|string|max:5000',
            'jalannya_penyakit'     => 'nullable|string|max:5000',
            'hasil_laborat'         => 'nullable|string|max:20000',
            'pemeriksaan_penunjang' => 'nullable|string|max:20000',
            'obat_pulang'           => 'nullable|string|max:5000',
            'tindak_lanjut'         => ['required', Rule::in(TindakLanjutResolver::PILIHAN)],
            'tgl_kontrol'           => 'nullable|required_if:tindak_lanjut,kontrol|date',
            'ket_tindak_lanjut'     => 'nullable|required_if:tindak_lanjut,dirujuk|string|max:100',
            'sidik'                 => 'required|string|size:40',
        ];
    }

    public function messages(): array
    {
        return [
            'diagnosa.required'             => 'Diagnosis utama wajib diisi.',
            'diagnosa.0.required'           => 'Diagnosis utama wajib diisi.',
            'diagnosa.max'                  => 'Diagnosis maksimal 5 baris.',
            'diagnosa.*.max'                => 'Satu diagnosis maksimal 80 karakter.',
            'prosedur.max'                  => 'Prosedur maksimal 4 baris.',
            'prosedur.*.max'                => 'Satu prosedur maksimal 80 karakter.',
            'tindak_lanjut.required'        => 'Pilih tindak lanjut pasien.',
            'tindak_lanjut.in'              => 'Tindak lanjut tidak dikenali.',
            'tgl_kontrol.required_if'       => 'Isi tanggal kontrol ulang.',
            'tgl_kontrol.date'              => 'Tanggal kontrol tidak valid.',
            'ket_tindak_lanjut.required_if' => 'Isi nama RS atau faskes tujuan rujukan.',
            'ket_tindak_lanjut.max'         => 'Keterangan maksimal 100 karakter.',
            'sidik.*'                       => 'Data form tidak lengkap, muat ulang tab Resume.',
            'no_rawat.*'                    => 'Kunjungan tidak ditemukan.',
            'max'                           => 'Isian terlalu panjang.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $tl = app(TindakLanjutResolver::class)->tentukan((string) $this->input('no_rawat'), null);
        $this->merge(['_tl_terkunci' => $tl['terkunci']]);
    }

    public function withValidator($validator): void
    {
        // Tindak lanjut dari data Khanza tidak butuh tanggal/keterangan dari form.
        if ($this->input('_tl_terkunci')) {
            $validator->setRules(array_merge($validator->getRules(), [
                'tgl_kontrol'       => ['nullable', 'date'],
                'ket_tindak_lanjut' => ['nullable', 'string', 'max:100'],
            ]));
        }
    }
}
