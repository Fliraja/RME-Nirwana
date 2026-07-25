<?php

namespace App\Http\Requests\Ralan;

use Illuminate\Foundation\Http\FormRequest;

class StorePermintaanRadiologiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'         => 'required',
            'kd_jenis_prw_rad' => 'required|array|min:1',
        ];
    }
}
