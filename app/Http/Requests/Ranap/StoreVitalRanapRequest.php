<?php

namespace App\Http\Requests\Ranap;

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
            'suhu_tubuh'             => 'nullable|string|max:10',
            'tensi'                  => 'nullable|string|max:10',
            'nadi'                   => 'nullable|string|max:10',
            'respirasi'              => 'nullable|string|max:10',
            'spo2'                   => 'nullable|string|max:10',
            'gcs'                    => 'nullable|string|max:10',
            'tinggi'                 => 'nullable|string|max:10',
            'berat'                  => 'nullable|string|max:10',
            'kesadaran'              => 'nullable|string|max:30',
            'alergi'                 => 'nullable|string|max:100',
            'sbar'                   => 'nullable|string',
            'mode_edit_ttv'          => 'nullable|in:0,1',
            'tgl_perawatan_edit_ttv' => 'nullable|date_format:Y-m-d',
            'jam_rawat_edit_ttv'     => 'nullable|date_format:H:i:s',
        ];
    }
}
