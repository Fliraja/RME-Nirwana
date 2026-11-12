<div class="table-responsive">
    <table class="table table-hover table-striped align-middle mb-0">
        <thead class="table-light">
            <tr class="small">
                <th style="width: 140px;">No. Order</th>
                <th style="width: 130px;">Waktu Order</th>
                <th>Pemeriksaan Diminta</th>
                <th>Diagnosa Klinis</th>
                <th style="width: 110px;">Status</th>
                <th class="text-center" style="width: 80px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riwayatOrder as $order)
                <tr>
                    <td><span class="badge bg-light text-dark border">{{ $order->noorder }}</span></td>
                    <td class="small">{{ $order->tgl_permintaan }} <br><span class="text-muted">{{ $order->jam_permintaan }}</span></td>
                    <td>
                        <ul class="list-unstyled mb-0 small">
                            @foreach($order->pemeriksaan as $p)
                                <li><i class="fas fa-check-circle text-success me-1"></i> {{ $p->jenisPerawatan->nm_perawatan ?? $p->kd_jenis_prw }}</li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="small">{{ $order->diagnosa_klinis ?: '-' }}</td>
                    <td>
                        <span class="resume-status {{ ['menunggu sampel' => 'resep-status-tunggu', 'sampel diambil' => 'resep-status-proses', 'hasil keluar' => 'resume-status-ok'][$order->statusTeks()] }}">{{ $order->statusTeks() }}</span>
                    </td>
                    <td class="text-center">
                        @unless($order->sudahDiproses())
                            <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-lab" data-noorder="{{ $order->noorder }}" title="Batalkan Permintaan">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        @else
                            <span class="text-muted small fst-italic">Diproses</span>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center p-3 text-muted small">
                        Belum ada permintaan laboratorium untuk kunjungan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
