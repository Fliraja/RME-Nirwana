<?php

namespace App\Http\Controllers;

use App\Models\ResepObat;
use App\Models\RegPeriksa;
use App\Services\Obat\StokObatService;
use App\Models\ResepDokter;
use App\Exceptions\ResepTerkunci;
use App\Services\Kunjungan\RawatInap;
use App\Services\Resep\ResepService;
use Illuminate\Http\Request;
use App\Models\MasterAturanPakai;
use App\Support\NomorUrut;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ResepController extends Controller
{
    /** resep_dokter.aturan_pakai dan resep_dokter_racikan.aturan_pakai varchar(150); MySQL sesi non-strict memotong diam-diam. */
    private const ATURAN_MAKS = 150;

    public function __construct(private ResepService $resep, private RawatInap $rawatInap) {}

    private static function pesanAturan(string $kolom): array
    {
        return [
            "{$kolom}.required" => 'Aturan pakai wajib diisi.',
            "{$kolom}.max"      => 'Aturan pakai paling banyak ' . self::ATURAN_MAKS . ' karakter.',
        ];
    }

    public function getResepPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);

        // Per kunjungan, bukan hari ini: resep terbuka bisa dibuat kemarin dan ditambah hari ini.
        $daftarResep = $this->resep->resepKunjungan($no_rawat);

        $masterAturan = MasterAturanPakai::orderBy('aturan')->get();
        $masterMetode = DB::table('metode_racik')->get();
        $pasien = RegPeriksa::where('no_rawat', $no_rawat)->first();

        return view('ralan.resep', compact('daftarResep', 'pasien', 'masterAturan', 'masterMetode') + ['pesanKunci' => $this->rawatInap->pesanKunci($no_rawat)]);
    }

    public function getResepTable($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        $daftarResep = $this->resep->resepKunjungan($no_rawat);

        return view('ralan.resep_table', compact('daftarResep') + ['pesanKunci' => $this->rawatInap->pesanKunci($no_rawat)]);
    }

    public function storeResepObat(Request $request)
    {
        $request->validate([
            'no_rawat'            => 'required',
            'obat'                => 'required|array|min:1',
            'obat.*.kode_obat'    => 'required',
            'obat.*.jumlah'       => 'required|numeric|min:1',
            'obat.*.aturan_pakai' => 'required|string|max:' . self::ATURAN_MAKS,
        ], self::pesanAturan('obat.*.aturan_pakai'));

        return $this->jalankan(function () use ($request) {
            $hasil = $this->resep->tambahObat($request->no_rawat, $request->obat, Auth::user()->decrypted_id);

            return [
                'status'   => 'success-obat',
                'message'  => count($request->obat) . ' obat berhasil disimpan ke resep',
                'no_resep' => $hasil['no_resep'],
            ];
        });
    }

    public function storeResepRacikan(Request $request)
    {
        $request->validate([
            'no_rawat' => 'required',
            'nama_racik' => 'required',
            'kd_racik' => 'required',
            'jml_dr' => 'required|numeric|min:1',
            'aturan_racik' => 'required|string|max:' . self::ATURAN_MAKS,
            'detail_obat'   => 'required|array|min:1',
            'detail_obat.*.kode_brng' => 'required',
            'detail_obat.*.p1'        => 'required|numeric',
            'detail_obat.*.p2'        => 'required|numeric',
            'detail_obat.*.kandungan' => 'required',
            'detail_obat.*.jml'       => 'required|numeric',
        ], self::pesanAturan('aturan_racik'));

        return $this->jalankan(function () use ($request) {
            $hasil = $this->resep->tambahRacikan($request->no_rawat, [
                'nama_racik'   => $request->nama_racik,
                'kd_racik'     => $request->kd_racik,
                'jml_dr'       => $request->jml_dr,
                'aturan_pakai' => $request->aturan_racik,
                'keterangan'   => $request->keterangan ?? '-',
                'detail'       => $request->detail_obat,
            ], Auth::user()->decrypted_id);

            return [
                'status'   => 'success-racik',
                'message'  => 'Resep racikan berhasil ditambahkan',
                'no_resep' => $hasil['no_resep'],
                'no_racik' => $hasil['no_racik'],
            ];
        });
    }

    public function deleteResepObat($no_resep, $kode_brng)
    {
        return $this->jalankan(function () use ($no_resep, $kode_brng) {
            $this->resep->hapusObat($no_resep, $kode_brng);

            return ['status' => 'success-hapus-obat', 'message' => 'Item resep berhasil dihapus'];
        });
    }

    public function deleteResepRacikan($no_resep, $no_racik)
    {
        return $this->jalankan(function () use ($no_resep, $no_racik) {
            $this->resep->hapusRacikan($no_resep, (int) $no_racik);

            return ['status' => 'success-hapus-racikan', 'message' => 'Satu kelompok racikan berhasil dihapus'];
        });
    }

    /** Penolakan yang bisa dibaca dokter (sudah divalidasi apotek, stok habis) dikirim sebagai 409. */
    private function jalankan(callable $aksi)
    {
        try {
            return response()->json($aksi());
        } catch (ResepTerkunci $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 409);
        }
    }

    public function getObat(Request $request)
    {
        return response()->json(app(StokObatService::class)->cari((string) $request->search));
    }
}
