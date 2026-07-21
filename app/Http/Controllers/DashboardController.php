<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\Jadwal;
use App\Models\Pasien;
use App\Models\RegPeriksa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('dashboard_stats', now()->addMinutes(5), function () {
            $today = Carbon::today()->toDateString();
            return [
                'totalPasien' => Pasien::count(),
                'kunjunganHariIni' => RegPeriksa::whereDate('tgl_registrasi', $today)
                    ->where('stts', '!=', 'Batal')
                    ->count(),
                'dokterAktif' => Dokter::where('status', '1')->count(),
                'dalamAntrian' => RegPeriksa::whereDate('tgl_registrasi', $today)
                    ->where('stts', 'Belum')
                    ->count(),
            ];
        });

        $pasienTerbaru = RegPeriksa::with(['pasien'])
            ->orderByDesc('tgl_registrasi')
            ->limit(5)
            ->get();

        $dayNameUpper = mb_strtoupper(Carbon::now()->locale('id')->dayName);
        $dayNames = $dayNameUpper === 'MINGGU' ? ['MINGGU', 'AKHAD'] : [$dayNameUpper];

        $jadwalDokter = Jadwal::with(['poliklinik', 'dokter'])
            ->whereIn('hari_kerja', $dayNames)
            ->get();

        return view('dashboard', compact('stats', 'pasienTerbaru', 'jadwalDokter'));
    }
}
