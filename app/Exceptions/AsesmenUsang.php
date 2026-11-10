<?php

namespace App\Exceptions;

use App\Services\Igd\AsesmenIgdService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/** Asesmen sudah berubah (lewat aplikasi atau Khanza desktop) sejak form dibuka; simpan ditolak agar tidak menimpa diam-diam. */
class AsesmenUsang extends RuntimeException
{
    public function __construct(string $nama, Carbon $waktu)
    {
        $jam = AsesmenIgdService::waktu($waktu);
        parent::__construct("Asesmen sudah diubah {$nama} pukul {$jam}. Muat ulang untuk melihat perubahan.");
    }

    public function render(): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $this->getMessage()], 409);
    }
}
