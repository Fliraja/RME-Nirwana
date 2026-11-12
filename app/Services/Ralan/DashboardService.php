<?php

namespace App\Services\Ralan;

use App\Models\Jadwal;
use App\Models\Pasien;
use App\Models\RegPeriksa;
use App\Services\Igd\IgdPasienService;
use App\Support\DokterIgd;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private IgdPasienService $igd) {}

    public function data(): array
    {
        $isAdmin = session('role') === 'admin';
        $kd_dokter = $isAdmin ? null : (Auth::user()->decrypted_id ?? null);
        $igd = !$isAdmin && DokterIgd::adalah($kd_dokter);

        $stats = $igd ? $this->statistikIgd() : $this->statistikPoli($isAdmin, $kd_dokter);
        $pasienTerbaru = $igd ? $this->terbaruIgd() : $this->terbaruPoli($isAdmin, $kd_dokter);

        $dayNameUpper = mb_strtoupper(Carbon::now()->locale('id')->dayName);
        $dayNames = $dayNameUpper === 'MINGGU' ? ['MINGGU', 'AKHAD'] : [$dayNameUpper];

        $queryJadwal = Jadwal::with(['poliklinik', 'dokter'])->whereIn('hari_kerja', $dayNames);
        if (!$isAdmin && $kd_dokter) {
            $queryJadwal->where('kd_dokter', $kd_dokter);
        }
        $jadwalDokter = $queryJadwal->get();

        return compact('stats', 'pasienTerbaru', 'jadwalDokter', 'isAdmin', 'igd');
    }

    private function statistikPoli(bool $isAdmin, ?string $kd_dokter): array
    {
        $cacheKey = 'dashboard_stats_' . ($isAdmin ? 'admin' : ($kd_dokter ?? 'guest'));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($isAdmin, $kd_dokter) {
            $today = Carbon::today()->toDateString();
            $dokter = fn ($q) => (!$isAdmin && $kd_dokter) ? $q->where('kd_dokter', $kd_dokter) : $q;

            return [
                'totalPasien'  => Pasien::count(),
                'bulanIni'     => $dokter(RegPeriksa::whereMonth('tgl_registrasi', Carbon::now()->month)
                    ->whereYear('tgl_registrasi', Carbon::now()->year)->where('stts', '!=', 'Batal'))->count(),
                'hariIni'      => $dokter(RegPeriksa::whereDate('tgl_registrasi', $today)->where('stts', '!=', 'Batal'))->count(),
                'perluDitulis' => $dokter(RegPeriksa::whereDate('tgl_registrasi', $today)->where('stts', 'Belum'))->count(),
            ];
        });
    }

    /**
     * Daftar IGD dipakai bersama semua dokter jaga, jadi angkanya untuk seluruh IGD, bukan per dokter pendaftar.
     * "Asesmen belum" memakai rentang bawaan daftar IGD (kemarin ikut sampai 09.00) agar sama dengan daftarnya.
     */
    private function statistikIgd(): array
    {
        return Cache::remember('dashboard_stats_igd', now()->addMinutes(5), function () {
            $now = Carbon::now();
            [$dari, $sampai] = $this->igd->rentangBawaan($now);
            $igd = fn () => DB::table('reg_periksa')->where('kd_poli', config('rme.igd.kd_poli'))->where('stts', '<>', 'Batal');

            return [
                'totalPasien'  => Pasien::count(),
                'bulanIni'     => $igd()->whereYear('tgl_registrasi', $now->year)->whereMonth('tgl_registrasi', $now->month)->count(),
                'hariIni'      => $igd()->where('tgl_registrasi', $now->toDateString())->count(),
                'perluDitulis' => $this->igd->daftar($dari->toDateString(), $sampai->toDateString())
                    ->filter(fn ($b) => !$b->ada_asesmen && !$b->rawat_inap)->count(),
            ];
        });
    }

    /** @return \Illuminate\Support\Collection<int, object{no_rkm_medis, nm_pasien, tgl_registrasi, tautan, status, kelas}> */
    private function terbaruPoli(bool $isAdmin, ?string $kd_dokter)
    {
        return RegPeriksa::with('pasien')
            ->where('stts', '!=', 'Batal')
            ->when(!$isAdmin && $kd_dokter, fn ($q) => $q->where('kd_dokter', $kd_dokter))
            ->orderByDesc('tgl_registrasi')->orderByDesc('jam_reg')
            ->limit(5)->get()
            ->map(fn ($r) => (object) [
                'no_rkm_medis'   => $r->no_rkm_medis,
                'nm_pasien'      => $r->pasien->nm_pasien ?? '-',
                'tgl_registrasi' => $r->tgl_registrasi,
                'tautan'         => $r->status_lanjut === 'Ranap'
                    ? route('ranap.index', ['action' => 'view', 'no_rawat' => $r->no_rawat])
                    : route('ralan.index', ['action' => 'view', 'no_rawat' => $r->no_rawat]),
                'status'         => $r->status_lanjut === 'Ranap' ? 'Rawat inap' : $r->stts,
                'kelas'          => match (true) {
                    $r->status_lanjut === 'Ranap' => 'igd-status-ranap',
                    in_array($r->stts, ['Sudah', 'Bayar'], true) => 'resume-status-ok',
                    default => 'resume-status-tunggu',
                },
            ]);
    }

    private function terbaruIgd()
    {
        $now = Carbon::now();

        return $this->igd->daftar($now->copy()->subDays(30)->toDateString(), $now->toDateString())
            ->take(5)
            ->map(fn ($b) => (object) [
                'no_rkm_medis'   => $b->no_rkm_medis,
                'nm_pasien'      => $b->nm_pasien,
                'tgl_registrasi' => $b->tgl_registrasi,
                'tautan'         => route('igd.pasien', str_replace('/', '-', $b->no_rawat)),
                'status'         => $b->rawat_inap ? 'Rawat inap' : ($b->ada_asesmen ? 'Asesmen terisi' : 'Asesmen belum'),
                'kelas'          => $b->rawat_inap ? 'igd-status-ranap' : ($b->ada_asesmen ? 'resume-status-ok' : 'resume-status-tunggu'),
            ]);
    }
}
