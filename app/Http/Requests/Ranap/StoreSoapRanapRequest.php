<?php

namespace App\Http\Requests\Ranap;

use Illuminate\Foundation\Http\FormRequest;

class StoreSoapRanapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'                => 'required|string',
            'keluhan'                 => 'nullable|string',
            'pemeriksaan'             => 'nullable|string',
            'penilaian'               => 'nullable|string',
            'rtl'                     => 'nullable|string',
            'instruksi'               => 'nullable|string',
            'evaluasi'                => 'nullable|string',
            'mode_edit_soap'          => 'nullable|in:0,1',
            'tgl_perawatan_edit_soap' => 'nullable|date_format:Y-m-d',
            'jam_rawat_edit_soap'     => 'nullable|date_format:H:i:s',
        ];
    }
}
