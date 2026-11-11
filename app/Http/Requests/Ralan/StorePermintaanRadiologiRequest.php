<?php

namespace App\Http\Requests\Ralan;

use App\Support\AturanPermintaanPenunjang;
use Illuminate\Foundation\Http\FormRequest;

/** Dipakai rute /ralan dan /ranap. */
class StorePermintaanRadiologiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('kd_jenis_prw_rad') && $this->has('kd_jenis_prw')) {
            $this->merge([
                'kd_jenis_prw_rad' => $this->kd_jenis_prw,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'no_rawat'           => 'required',
            'kd_jenis_prw_rad'   => 'required|array|min:1',
            'kd_jenis_prw_rad.*' => AturanPermintaanPenunjang::kode('jns_perawatan_radiologi'),
        ];
    }

    public function messages(): array
    {
        return AturanPermintaanPenunjang::PESAN;
    }
}
