<?php

namespace App\Http\Requests\Ralan;

use Illuminate\Foundation\Http\FormRequest;

class StoreProsedurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat' => 'required',
            'kode'     => 'required|array|min:1',
        ];
    }
}
