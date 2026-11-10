<?php

namespace App\Http\Requests\Igd;

use App\Services\Igd\AsesmenIgdService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsesmenIgdRequest extends FormRequest
{
    /**
     * Batas masuk akal tanda vital (keputusan builder, spec 6.3): [min, max, desimal, label, satuan].
     * Panjang kolom Khanza 5 karakter, jadi nilai disimpan apa adanya setelah koma diganti titik.
     */
    public const BATAS_VITAL = [
        'nadi' => [20, 300, false, 'Nadi', 'x/menit'],
        'rr'   => [4, 80, false, 'RR', 'x/menit'],
        'suhu' => [30, 45, true, 'Suhu', '°C'],
        'spo'  => [30, 100, false, 'SpO2', '%'],
        'bb'   => [0.5, 400, true, 'BB', 'kg'],
        'tb'   => [30, 250, true, 'TB', 'cm'],
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ganti = [];
        foreach (array_keys(self::BATAS_VITAL) as $k) {
            if (is_string($this->input($k))) {
                $ganti[$k] = str_replace(',', '.', trim($this->input($k)));
            }
        }
        foreach (['td', 'gcs'] as $k) {
            if (is_string($this->input($k))) {
                $ganti[$k] = preg_replace('/\s+/', '', $this->input($k));
            }
        }
        $this->merge($ganti);
    }

    public function rules(): array
    {
        $aturan = [
            'no_rawat'      => 'required|string|max:17',
            'sidik'         => 'nullable|string|size:40',
            'anamnesis'     => ['required', Rule::in(AsesmenIgdService::PILIHAN['anamnesis'])],
            'hubungan'      => 'nullable|required_if:anamnesis,Alloanamnesis|string|max:100',
            'keadaan'       => ['required', Rule::in(AsesmenIgdService::PILIHAN['keadaan'])],
            'kesadaran'     => ['required', Rule::in(AsesmenIgdService::PILIHAN['kesadaran'])],
            'keluhan_utama' => 'required|string|max:2000',
            'rps'           => 'nullable|string|max:2000',
            'rpd'           => 'nullable|string|max:1000',
            'rpk'           => 'nullable|string|max:1000',
            'rpo'           => 'nullable|string|max:1000',
            'alergi'        => 'nullable|string|max:100',
            'gcs'           => 'nullable|string|max:10',
            'td'            => ['nullable', 'string', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'diagnosis'     => 'required|string|max:500',
        ];
        foreach (AsesmenIgdService::ORGAN as $organ) {
            $aturan[$organ] = ['required', Rule::in(AsesmenIgdService::PILIHAN['organ'])];
        }
        foreach (['ket_fisik', 'ket_lokalis', 'ekg', 'rad', 'lab', 'tata'] as $teks) {
            $aturan[$teks] = 'nullable|string|max:10000';
        }
        foreach (self::BATAS_VITAL as $k => [$min, $maks, $desimal]) {
            $aturan[$k] = [
                'nullable', 'string', 'max:5',
                $desimal ? 'regex:/^\d{1,3}(\.\d{1,2})?$/' : 'regex:/^\d{1,3}$/',
                function (string $atribut, mixed $nilai, \Closure $gagal) use ($min, $maks) {
                    if (is_numeric($nilai) && ((float) $nilai < $min || (float) $nilai > $maks)) {
                        $gagal($this->pesanVital($atribut));
                    }
                },
            ];
        }

        return $aturan;
    }

    public function messages(): array
    {
        $pesan = [
            'anamnesis.required'      => 'Pilih autoanamnesis atau alloanamnesis.',
            'anamnesis.in'            => 'Pilih autoanamnesis atau alloanamnesis.',
            'hubungan.required_if'    => 'Alloanamnesis: tulis hubungan pemberi keterangan dengan pasien.',
            'hubungan.max'            => 'Hubungan paling panjang 100 karakter.',
            'keadaan.required'        => 'Pilih keadaan umum.',
            'keadaan.in'              => 'Pilih keadaan umum.',
            'kesadaran.required'      => 'Pilih tingkat kesadaran.',
            'kesadaran.in'            => 'Pilih tingkat kesadaran.',
            'keluhan_utama.required'  => 'Keluhan utama wajib diisi.',
            'keluhan_utama.max'       => 'Keluhan utama paling panjang 2000 karakter.',
            'diagnosis.required'      => 'Diagnosis wajib diisi.',
            'diagnosis.max'           => 'Diagnosis paling panjang 500 karakter.',
            'rps.max'                 => 'Riwayat penyakit sekarang paling panjang 2000 karakter.',
            'rpd.max'                 => 'Riwayat penyakit dahulu paling panjang 1000 karakter.',
            'rpk.max'                 => 'Riwayat penyakit keluarga paling panjang 1000 karakter.',
            'rpo.max'                 => 'Riwayat pengobatan paling panjang 1000 karakter.',
            'alergi.max'              => 'Alergi paling panjang 100 karakter.',
            'gcs.max'                 => 'GCS paling panjang 10 karakter, mis. E4V5M6 atau 15.',
            'td.regex'                => 'Tekanan darah ditulis sistolik/diastolik, mis. 120/80.',
            'sidik.size'              => 'Form tidak lengkap. Muat ulang tab Asesmen.',
        ];
        foreach (AsesmenIgdService::ORGAN as $organ) {
            $label = AsesmenIgdService::LABEL_ORGAN[$organ];
            $pesan["{$organ}.required"] = "Pilih hasil pemeriksaan {$label}.";
            $pesan["{$organ}.in"] = "Pilih hasil pemeriksaan {$label}.";
        }
        foreach (['ket_fisik', 'ket_lokalis', 'ekg', 'rad', 'lab', 'tata'] as $teks) {
            $pesan["{$teks}.max"] = 'Isian ini paling panjang 10.000 karakter.';
        }
        foreach (array_keys(self::BATAS_VITAL) as $k) {
            $pesan["{$k}.max"] = $pesan["{$k}.regex"] = $this->pesanVital($k);
        }

        return $pesan;
    }

    private function pesanVital(string $kolom): string
    {
        [$min, $maks, $desimal, $label, $satuan] = self::BATAS_VITAL[$kolom];
        $angka = fn ($n) => str_replace('.', ',', (string) $n);

        return "{$label} diisi angka {$angka($min)} sampai {$angka($maks)} {$satuan}" . ($desimal ? ', boleh dengan koma.' : '.');
    }
}
