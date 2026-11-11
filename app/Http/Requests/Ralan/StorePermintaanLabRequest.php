<?php

namespace App\Http\Requests\Ralan;

use App\Support\AturanPermintaanPenunjang;
use Illuminate\Foundation\Http\FormRequest;

/** Dipakai rute /ralan dan /ranap. */
class StorePermintaanLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'       => 'required',
            'kd_jenis_prw'   => 'required|array|min:1',
            'kd_jenis_prw.*' => AturanPermintaanPenunjang::kode('jns_perawatan_lab'),
        ] + AturanPermintaanPenunjang::subItemLab();
    }

    public function messages(): array
    {
        return AturanPermintaanPenunjang::PESAN;
    }
}
