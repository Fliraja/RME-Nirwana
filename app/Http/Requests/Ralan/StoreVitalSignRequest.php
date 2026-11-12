<?php

namespace App\Http\Requests\Ralan;

use App\Support\BatasIsianPemeriksaan as Batas;
use Illuminate\Foundation\Http\FormRequest;

class StoreVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat' => 'required',
        ] + Batas::aturan(Batas::TTV + ['alergi' => Batas::ALERGI_RALAN]);
    }

    public function attributes(): array
    {
        return Batas::label();
    }
}
