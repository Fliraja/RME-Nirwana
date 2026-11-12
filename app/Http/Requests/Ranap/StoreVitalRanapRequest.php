<?php

namespace App\Http\Requests\Ranap;

use App\Support\BatasIsianPemeriksaan as Batas;
use Illuminate\Foundation\Http\FormRequest;

class StoreVitalRanapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'               => 'required|string',
            'kesadaran'              => 'nullable|string|max:30',
            'sbar'                   => 'nullable|string',
            'mode_edit_ttv'          => 'nullable|in:0,1',
            'tgl_perawatan_edit_ttv' => 'nullable|date_format:Y-m-d',
            'jam_rawat_edit_ttv'     => 'nullable|date_format:H:i:s',
        ] + Batas::aturan(Batas::TTV + ['alergi' => Batas::ALERGI_RANAP]);
    }

    public function attributes(): array
    {
        return Batas::label();
    }
}
