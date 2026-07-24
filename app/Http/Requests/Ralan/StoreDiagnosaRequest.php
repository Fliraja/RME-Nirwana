<?php

namespace App\Http\Requests\Ralan;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'    => 'required',
            'kd_penyakit' => 'required|array|min:1',
        ];
    }
}
