<?php

namespace App\Http\Requests\Ralan;

use App\Support\BatasIsianKfr;
use App\Support\BatasIsianPemeriksaan as Batas;
use Illuminate\Foundation\Http\FormRequest;

class StoreSoapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat' => 'required',
            'kfr'      => ['nullable', 'array'],
        ] + Batas::aturan(Batas::SOAP_RALAN) + BatasIsianKfr::aturan('kfr.');
    }

    public function attributes(): array
    {
        return Batas::label() + BatasIsianKfr::label('kfr.');
    }
}
