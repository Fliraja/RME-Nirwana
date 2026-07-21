<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\Jadwal;
use App\Models\Pasien;
use App\Models\RegPeriksa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $isAdmin = session('role') === 'admin';
        $kd_dokter = $isAdmin ? null : (Auth::user()->decrypted_id ?? null);
        $today = Carbon::today()->toDateString();

        $cacheKey = 'dashboard_stats_' . ($isAdmin ? 'admin' : ($kd_dokter ?? 'guest'));

        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($isAdmin, $kd_dokter, $today) {
            $queryTotalKunjungan = RegPeriksa::whereDate('tgl_registrasi', $today)->where('stts', '!=', 'Batal');
            $querySudahDiperiksa = RegPeriksa::whereDate('tgl_registrasi', $today)->whereIn('stts', ['Sudah', 'Bayar']);
            $queryBelumDiperiksa = RegPeriksa::whereDate('tgl_registrasi', $today)->where('stts', 'Belum');

            if (!$isAdmin && $kd_dokter) {
                $queryTotalKunjungan->where('kd_dokter', $kd_dokter);
                $querySudahDiperiksa->where('kd_dokter', $kd_dokter);
                $queryBelumDiperiksa->where('kd_dokter', $kd_dokter);
            }

            return [
                'totalPasien' => Pasien::count(),
                'kunjunganHariIni' => $queryTotalKunjungan->count(),
                'sudahDiperiksa' => $querySudahDiperiksa->count(),
                'belumDiperiksa' => $queryBelumDiperiksa->count(),
            ];
        });

        $queryPasien = RegPeriksa::with(['pasien'])
            ->orderByDesc('tgl_registrasi');

        if (!$isAdmin && $kd_dokter) {
            $queryPasien->where('kd_dokter', $kd_dokter);
        }

        $pasienTerbaru = $queryPasien->limit(5)->get();

        $dayNameUpper = mb_strtoupper(Carbon::now()->locale('id')->dayName);
        $dayNames = $dayNameUpper === 'MINGGU' ? ['MINGGU', 'AKHAD'] : [$dayNameUpper];

        $queryJadwal = Jadwal::with(['poliklinik', 'dokter'])
            ->whereIn('hari_kerja', $dayNames);

        if (!$isAdmin && $kd_dokter) {
            $queryJadwal->where('kd_dokter', $kd_dokter);
        }

        $jadwalDokter = $queryJadwal->get();

        return view('dashboard', compact('stats', 'pasienTerbaru', 'jadwalDokter', 'isAdmin'));
    }
}
