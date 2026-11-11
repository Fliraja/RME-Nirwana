<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderIgdTerkunci;
use App\Exceptions\ResepTerkunci;
use App\Services\Igd\IgdOrderService;
use App\Services\Ralan\LaboratoriumService;
use App\Services\Ralan\RadiologiService;
use App\Services\Resep\ResepService;
use App\Support\AturanPermintaanPenunjang;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/** Tab Permintaan di halaman pasien IGD: lab, radiologi (dan resep di tahap berikutnya). */
class IgdOrderController extends Controller
{
    public function __construct(
        private IgdOrderService $order,
        private LaboratoriumService $lab,
        private RadiologiService $radiologi,
        private ResepService $resep,
    ) {}

    public function fragmen(string $no_rawat)
    {
        $noRawat = IgdController::noRawat($no_rawat);

        try {
            $this->order->pastikanPasienIgd($noRawat);
        } catch (OrderIgdTerkunci $e) {
            abort($e->status, $e->getMessage());
        }

        $lab =$this->order->daftarLab($noRawat);
        $radiologi = $this->order->daftarRadiologi($noRawat);
        $resep = $this->order->daftarResep($noRawat);

        return view('igd.tabs.permintaan', [
            'noRawat'        => $noRawat,
            'lab'            => $lab,
            'radiologi'      => $radiologi,
            'resep'          => $resep,
            'jumlah'         => $lab->count() + $radiologi->count() + $resep->count(),
            'aturanPakai'    => DB::table('master_aturan_pakai')->orderBy('aturan')->pluck('aturan'),
            'metodeRacik'    => DB::table('metode_racik')->orderBy('kd_racik')->get(),
            'pesanTerkunci'  => $this->order->pesanTerkunci($noRawat),
            'usulanDiagnosa' => $this->order->usulanDiagnosaKlinis($noRawat),
        ]);
    }

    public function simpanLab(Request $request)
    {
        $data = $request->validate(
            $this->aturanPermintaan('jns_perawatan_lab') + AturanPermintaanPenunjang::subItemLab(),
            AturanPermintaanPenunjang::PESAN,
        );

        return $this->jalankan($data['no_rawat'], fn () => $this->lab->simpanPermintaan($this->bersihkan($data)));
    }

    public function hapusLab(Request $request, string $noorder)
    {
        $noRawat = (string) $request->validate(['no_rawat' => 'required|string'])['no_rawat'];

        return $this->jalankan($noRawat, function () use ($noorder, $noRawat) {
            $this->order->pastikanMilik('permintaan_lab', $noorder, $noRawat);

            return $this->lab->hapus($noorder, null, null);
        });
    }

    public function simpanRadiologi(Request $request)
    {
        $data = $request->validate($this->aturanPermintaan('jns_perawatan_radiologi'), AturanPermintaanPenunjang::PESAN);

        return $this->jalankan($data['no_rawat'], fn () => $this->radiologi->simpanPermintaan($this->bersihkan($data)));
    }

    public function hapusRadiologi(Request $request, string $noorder)
    {
        $noRawat = (string) $request->validate(['no_rawat' => 'required|string'])['no_rawat'];

        return $this->jalankan($noRawat, function () use ($noorder, $noRawat) {
            $this->order->pastikanMilik('permintaan_radiologi', $noorder, $noRawat);

            return $this->radiologi->hapus($noorder, null);
        });
    }

    public function simpanObat(Request $request)
    {
        $data = $request->validate([
            'no_rawat'            => 'required|string',
            'obat'                => 'required|array|min:1',
            'obat.*.kode_obat'    => 'required|string|max:15',
            'obat.*.jumlah'       => 'required|numeric|gt:0',
            'obat.*.aturan_pakai' => 'nullable|string|max:150',
        ]);
        $items = array_map(fn ($o) => ['aturan_pakai' => trim((string) ($o['aturan_pakai'] ?? ''))] + $o, $data['obat']);

        return $this->jalankan($data['no_rawat'], fn () => [
            'status'  => 'success',
            'message' => count($items) . ' item masuk resep, menunggu apotek.',
        ] + $this->resep->tambahObat($data['no_rawat'], $items, session('decrypted_id')));
    }

    public function simpanRacikan(Request $request)
    {
        $data = $request->validate([
            'no_rawat'           => 'required|string',
            'nama_racik'         => 'required|string|max:100',
            'kd_racik'           => 'required|string|exists:metode_racik,kd_racik',
            'jml_dr'             => 'required|integer|min:1',
            'aturan_pakai'       => 'required|string|max:150',
            'keterangan'         => 'nullable|string|max:50',
            'detail'             => 'required|array|min:1',
            'detail.*.kode_brng' => 'required|string|max:15',
            'detail.*.p1'        => 'required|numeric|gt:0',
            'detail.*.p2'        => 'required|numeric|gt:0',
            'detail.*.kandungan' => 'required|string|max:10',
            'detail.*.jml'       => 'required|numeric|gt:0',
        ]);
        $data['keterangan'] = trim((string) ($data['keterangan'] ?? '')) ?: '-';

        return $this->jalankan($data['no_rawat'], fn () => [
            'status'  => 'success',
            'message' => 'Racikan masuk resep, menunggu apotek.',
        ] + $this->resep->tambahRacikan($data['no_rawat'], $data, session('decrypted_id')));
    }

    public function hapusObat(Request $request, string $noResep, string $kodeBrng)
    {
        $noRawat = (string) $request->validate(['no_rawat' => 'required|string'])['no_rawat'];

        return $this->jalankan($noRawat, function () use ($noResep, $kodeBrng, $noRawat) {
            $this->order->pastikanResepMilik($noResep, $noRawat);
            $this->resep->hapusObat($noResep, $kodeBrng);

            return ['status' => 'success', 'message' => 'Item dihapus dari resep.'];
        });
    }

    public function hapusRacikan(Request $request, string $noResep, string $noRacik)
    {
        $noRawat = (string) $request->validate(['no_rawat' => 'required|string'])['no_rawat'];

        return $this->jalankan($noRawat, function () use ($noResep, $noRacik, $noRawat) {
            $this->order->pastikanResepMilik($noResep, $noRawat);
            $this->resep->hapusRacikan($noResep, (int) $noRacik);

            return ['status' => 'success', 'message' => 'Racikan dihapus dari resep.'];
        });
    }

    private function aturanPermintaan(string $tabelJenis): array
    {
        return [
            'no_rawat'           => 'required|string',
            'kd_jenis_prw'       => 'required|array|min:1',
            'kd_jenis_prw.*'     => AturanPermintaanPenunjang::kode($tabelJenis),
            'diagnosa_klinis'    => 'nullable|string|max:80',
            'informasi_tambahan' => 'nullable|string|max:60',
        ];
    }

    private function bersihkan(array $data): array
    {
        $data['diagnosa_klinis'] = trim((string) ($data['diagnosa_klinis'] ?? '')) ?: '-';
        $data['informasi_tambahan'] = trim((string) ($data['informasi_tambahan'] ?? '')) ?: '-';

        return $data;
    }

    /** Penjaga IGD lalu aksi service; penolakan service (sudah diproses) dikirim sebagai 409. */
    private function jalankan(string $noRawat, callable $aksi)
    {
        try {
            $this->order->pastikanBolehOrder($noRawat);
            $hasil = $aksi();
        } catch (OrderIgdTerkunci $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->status);
        } catch (ResepTerkunci $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 409);
        }

        $kode = $hasil['code'] ?? 200;
        unset($hasil['code']);

        return response()->json($hasil, $kode >= 400 ? 409 : 200);
    }
}
