<?php

namespace App\Http\Controllers;

use App\Services\Igd\IgdPasienService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IgdController extends Controller
{
    public function __construct(private IgdPasienService $pasien) {}

    public function index(Request $request)
    {
        [$bawaanDari, $bawaanSampai] = $this->pasien->rentangBawaan(now());

        $dari = $this->tanggal($request->query('dari')) ?? $bawaanDari->toDateString();
        $sampai = $this->tanggal($request->query('sampai')) ?? $bawaanSampai->toDateString();
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $cari = mb_substr(trim((string) $request->query('cari')), 0, 60);

        return view('igd.index', [
            'daftarPasien' => $this->pasien->daftar($dari, $sampai, $cari),
            'dari'         => $dari,
            'sampai'       => $sampai,
            'cari'         => $cari,
        ]);
    }

    private function tanggal(?string $nilai): ?string
    {
        if (!$nilai || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $nilai)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
