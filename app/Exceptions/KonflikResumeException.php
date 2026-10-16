<?php

namespace App\Exceptions;

use App\Models\RmeResumeRanap;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/** Resume sudah diubah dokter lain sejak form dibuka; simpan ditolak agar tidak menimpa diam-diam. */
class KonflikResumeException extends RuntimeException
{
    public function __construct(RmeResumeRanap $meta)
    {
        $oleh = $meta->pengubah?->nm_dokter ?? $meta->diubah_oleh;
        parent::__construct("Resume sudah diubah {$oleh} pada {$meta->diubah_pada->format('d/m H:i')}. Muat ulang untuk melihat perubahannya.");
    }

    public function render(): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $this->getMessage()], 409);
    }
}
