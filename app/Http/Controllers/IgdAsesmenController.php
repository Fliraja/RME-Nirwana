<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderIgdTerkunci;
use App\Http\Requests\Igd\StoreAsesmenIgdRequest;
use App\Services\Igd\AsesmenIgdReferensi;
use App\Services\Igd\AsesmenIgdService;
use App\Services\Igd\IgdOrderService;
use App\Support\Alergi;

/** Tab Asesmen di halaman pasien IGD. Boleh diubah dokter IGD mana pun, juga setelah pasien rawat inap. */
class IgdAsesmenController extends Controller
{
    public function __construct(private AsesmenIgdService $asesmen, private IgdOrderService $order) {}

    public function fragmen(string $no_rawat)
    {
        $noRawat = IgdController::noRawat($no_rawat);
        $this->pastikanIgd($noRawat);

        return view('igd.tabs.asesmen', $this->asesmen->data($noRawat) + ['noRawat' => $noRawat]);
    }

    public function referensi(string $no_rawat, AsesmenIgdReferensi $referensi)
    {
        $noRawat = IgdController::noRawat($no_rawat);
        $this->pastikanIgd($noRawat);

        return response()->json($referensi->untuk($noRawat));
    }

    public function simpan(StoreAsesmenIgdRequest $request)
    {
        $data = $request->validated();
        $this->pastikanIgd($data['no_rawat']);

        $hasil = $this->asesmen->simpan($data['no_rawat'], $data, (string) session('decrypted_id'), $data['sidik'] ?? null);

        return response()->json([
            'status'  => 'success',
            'message' => 'Asesmen tersimpan.',
            'sidik'   => $hasil['sidik'],
            'jejak'   => $hasil['jejak'],
            'alergi'  => Alergi::ada($hasil['alergi']) ? trim($hasil['alergi']) : null,
        ]);
    }

    private function pastikanIgd(string $noRawat): void
    {
        try {
            $this->order->pastikanPasienIgd($noRawat);
        } catch (OrderIgdTerkunci $e) {
            abort($e->status, $e->getMessage());
        }
    }
}
