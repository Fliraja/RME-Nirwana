<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\ResepObat;
use App\Models\RegPeriksa;
use Illuminate\Http\Request;
use App\Models\PermintaanLab;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PemeriksaanRalan;
use Illuminate\Support\Facades\DB;
use App\Models\PermintaanRadiologi;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function indexSoap(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai ?? date('Y-m-d');
        $tgl_selesai = $request->tgl_selesai ?? date('Y-m-d');
        $filter_nip = $request->nip; // Mengambil input filter dokter

        $query = PemeriksaanRalan::with(['regPeriksa.pasien']);

        if ($isAdmin) {
            if ($filter_nip) {
                $query->where('nip', $filter_nip);
            }
            $listDokter = \App\Models\Dokter::where('status', '1')->orderBy('nm_dokter', 'asc')->get();
        } else {
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('nip', $nip_auth);
            $listDokter = collect(); 
        }

        $laporan = $query->whereBetween('tgl_perawatan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_perawatan', 'asc')
            ->orderBy('jam_rawat', 'asc')
            ->get();

        return view('report.soap_index', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'listDokter', 'filter_nip'));
    }

    public function pdfSoap(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai;
        $tgl_selesai = $request->tgl_selesai;
        $filter_nip = $request->nip;

        $query = PemeriksaanRalan::with(['regPeriksa.pasien']);

        if ($isAdmin) {
            if ($filter_nip) {
                $query->where('nip', $filter_nip);
                $dokterData = \App\Models\Dokter::where('kd_dokter', $filter_nip)->first();
                $nama_dokter = $dokterData ? $dokterData->nm_dokter : 'Semua Dokter';
            } else {
                $nama_dokter = 'Semua Dokter';
            }
        } else {
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('nip', $nip_auth);
            $nama_dokter = Auth::user()->dokter_data->nm_dokter ?? 'Dokter';
        }

        $laporan = $query->whereBetween('tgl_perawatan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_perawatan', 'asc')
            ->get();

        $pdf = PDF::loadView('report.soap_pdf', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'nama_dokter'))
                    ->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_SOAP_'.$nama_dokter.'_'.$tgl_mulai.'.pdf');
    }

    public function indexVitalSign(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai ?? date('Y-m-d');
        $tgl_selesai = $request->tgl_selesai ?? date('Y-m-d');
        $filter_nip = $request->nip;

        $query = PemeriksaanRalan::with(['regPeriksa.pasien']);

        if ($isAdmin) {
            if ($filter_nip) {
                $query->where('nip', $filter_nip);
            }
            $listDokter = Dokter::where('status', '1')->orderBy('nm_dokter', 'asc')->get();
        } else {
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('nip', $nip_auth);
            $listDokter = collect();
        }

        $laporan = $query->whereBetween('tgl_perawatan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_perawatan', 'asc')
            ->orderBy('jam_rawat', 'asc')
            ->get();

        return view('report.vitalsign_index', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'listDokter', 'filter_nip'));
    }

    public function pdfVitalSign(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai;
        $tgl_selesai = $request->tgl_selesai;
        $filter_nip = $request->nip;

        $query = PemeriksaanRalan::with(['regPeriksa.pasien']);

        if ($isAdmin) {
            if ($filter_nip) {
                $query->where('nip', $filter_nip);
                $dokterData = Dokter::where('kd_dokter', $filter_nip)->first();
                $nama_dokter = $dokterData ? $dokterData->nm_dokter : 'Semua Dokter';
            } else {
                $nama_dokter = 'Semua Dokter';
            }
        } else {
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('nip', $nip_auth);
            $nama_dokter = Auth::user()->dokter_data->nm_dokter ?? 'Dokter';
        }

        $laporan = $query->whereBetween('tgl_perawatan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_perawatan', 'asc')
            ->get();

        $pdf = Pdf::loadView('report.vitalsign_pdf', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'nama_dokter'))
                    ->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Vital_Sign_'.$nama_dokter.'_'.$tgl_mulai.'.pdf');
    }

    public function indexLab(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai ?? date('Y-m-d');
        $tgl_selesai = $request->tgl_selesai ?? date('Y-m-d');
        $filter_dokter = $request->dokter_perujuk; 

        $query = PermintaanLab::with([
                'regPeriksa.pasien', 
                'pemeriksaan.jenisPerawatan',
                'pemeriksaan.detailTemplate.template' 
            ]);

        if ($isAdmin) {
            if ($filter_dokter) {
                $query->where('dokter_perujuk', $filter_dokter);
            }
            $listDokter = Dokter::where('status', '1')->orderBy('nm_dokter', 'asc')->get();
        } else {
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('dokter_perujuk', $nip_auth);
            $listDokter = collect();
        }

        $laporan = $query->whereBetween('tgl_permintaan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_permintaan', 'asc')
            ->orderBy('jam_permintaan', 'asc')
            ->get();

        return view('report.lab_index', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'listDokter', 'filter_dokter'));
    }

    public function pdfLab(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai;
        $tgl_selesai = $request->tgl_selesai;
        $filter_dokter = $request->dokter_perujuk;

        $query = PermintaanLab::with([
                'regPeriksa.pasien', 
                'pemeriksaan.jenisPerawatan',
                'pemeriksaan.detailTemplate.template'
            ]);

        if ($isAdmin) {
            if ($filter_dokter) {
                $query->where('dokter_perujuk', $filter_dokter);
                $dr = \App\Models\Dokter::where('kd_dokter', $filter_dokter)->first();
                $nama_dokter = $dr ? $dr->nm_dokter : 'Semua Dokter';
            } else {
                $nama_dokter = 'Semua Dokter';
            }
        } else {
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('dokter_perujuk', $nip_auth);
            $nama_dokter = Auth::user()->dokter_data->nm_dokter ?? 'Dokter';
        }

        $laporan = $query->whereBetween('tgl_permintaan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_permintaan', 'asc')
            ->get();

        $pdf = Pdf::loadView('report.lab_pdf', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'nama_dokter'))
                    ->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Permintaan_Lab_'.$nama_dokter.'.pdf');
    }

    public function indexRadiologi(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai ?? date('Y-m-d');
        $tgl_selesai = $request->tgl_selesai ?? date('Y-m-d');
        $filter_dokter = $request->dokter_perujuk;

        $query = PermintaanRadiologi::with(['regPeriksa.pasien', 'pemeriksaan.jenisPerawatan']);

        if ($isAdmin) {
            // Admin bisa filter dokter tertentu atau melihat semua
            if ($filter_dokter) {
                $query->where('dokter_perujuk', $filter_dokter);
            }
            $listDokter = \App\Models\Dokter::where('status', '1')->orderBy('nm_dokter', 'asc')->get();
        } else {
            // Dokter hanya bisa melihat permintaannya sendiri
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('dokter_perujuk', $nip_auth);
            $listDokter = collect();
        }

        $laporan = $query->whereBetween('tgl_permintaan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_permintaan', 'asc')
            ->orderBy('jam_permintaan', 'asc')
            ->get();

        return view('report.radiologi_index', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'listDokter', 'filter_dokter'));
    }

    public function pdfRadiologi(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai;
        $tgl_selesai = $request->tgl_selesai;
        $filter_dokter = $request->dokter_perujuk;

        $query = PermintaanRadiologi::with(['regPeriksa.pasien', 'pemeriksaan.jenisPerawatan']);

        if ($isAdmin) {
            if ($filter_dokter) {
                $query->where('dokter_perujuk', $filter_dokter);
                $dr = Dokter::where('kd_dokter', $filter_dokter)->first();
                $nama_dokter = $dr ? $dr->nm_dokter : 'Semua Dokter';
            } else {
                $nama_dokter = 'Semua Dokter';
            }
        } else {
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('dokter_perujuk', $nip_auth);
            $nama_dokter = Auth::user()->dokter_data->nm_dokter ?? 'Dokter';
        }

        $laporan = $query->whereBetween('tgl_permintaan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_permintaan', 'asc')
            ->get();

        $pdf = Pdf::loadView('report.radiologi_pdf', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'nama_dokter'))
                    ->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Radiologi_'.$nama_dokter.'_'.$tgl_mulai.'.pdf');
    }

    public function indexResep(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai ?? date('Y-m-d');
        $tgl_selesai = $request->tgl_selesai ?? date('Y-m-d');
        $filter_kd_dokter = $request->kd_dokter; 

        $query = ResepObat::with([
                'regPeriksa.pasien',
                'resepDokter.dataBarang',
                'resepRacikan.detailRacikan.dataBarang',
                'resepRacikan.metodeRacik'
            ]);

        if ($isAdmin) {
            if ($filter_kd_dokter) {
                $query->where('kd_dokter', $filter_kd_dokter);
            }
            $listDokter = Dokter::where('status', '1')->orderBy('nm_dokter', 'asc')->get();
        } else {
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('kd_dokter', $nip_auth);
            $listDokter = collect();
        }

        $laporan = $query->whereBetween('tgl_peresepan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_peresepan', 'asc')
            ->orderBy('jam_peresepan', 'asc')
            ->get();

        return view('report.resep_index', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'listDokter', 'filter_kd_dokter'));
    }

    public function pdfResep(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $tgl_mulai = $request->tgl_mulai;
        $tgl_selesai = $request->tgl_selesai;
        $filter_kd_dokter = $request->kd_dokter;

        $query = ResepObat::with([
                'regPeriksa.pasien',
                'resepDokter.dataBarang',
                'resepRacikan.detailRacikan.dataBarang',
                'resepRacikan.metodeRacik'
            ]);

        if ($isAdmin) {
            if ($filter_kd_dokter) {
                $query->where('kd_dokter', $filter_kd_dokter);
                $dr = \App\Models\Dokter::where('kd_dokter', $filter_kd_dokter)->first();
                $nama_dokter = $dr ? $dr->nm_dokter : 'Semua Dokter';
            } else {
                $nama_dokter = 'Semua Dokter';
            }
        } else {
            $nip_auth = Auth::user()->decrypted_id;
            $query->where('kd_dokter', $nip_auth);
            $nama_dokter = Auth::user()->dokter_data->nm_dokter ?? 'Dokter';
        }

        $laporan = $query->whereBetween('tgl_peresepan', [$tgl_mulai, $tgl_selesai])
            ->orderBy('tgl_peresepan', 'asc')
            ->get();

        $pdf = Pdf::loadView('report.resep_pdf', compact('laporan', 'tgl_mulai', 'tgl_selesai', 'nama_dokter'))
                    ->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Resep_'.$nama_dokter.'_'.$tgl_mulai.'.pdf');
    }

    public function pdfRiwayat($no_rkm_medis)
    {
        $detailPasien = RegPeriksa::with(['pasien', 'penjab'])
            ->where('no_rkm_medis', $no_rkm_medis)
            ->first();

        if (!$detailPasien) {
            return back()->with('error', 'Data pasien tidak ditemukan');
        }

        $riwayat = RegPeriksa::with([
                'poliklinik',
                'dokter',
                'pemeriksaanRalan',
                'pemeriksaanRanap',
                'detailObat.barang',
                'detailLab.template',
                'gambarRadiologi'
            ])
            ->where('no_rkm_medis', $no_rkm_medis)
            ->where('stts', '!=', 'Batal')
            ->orderBy('tgl_registrasi', 'DESC')
            ->limit(5) 
            ->get();

        $pdf = Pdf::loadView('report.riwayat_pdf', compact('detailPasien', 'riwayat'))
                ->setPaper('a4', 'landscape');

        return $pdf->stream('Riwayat_Medis_'.$no_rkm_medis.'.pdf');
    }
}
