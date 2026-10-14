<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr class="small">
                <th style="width: 22%;">Kode</th>
                <th>Deskripsi</th>
                <th style="width: 70px;">Jumlah</th>
                <th style="width: 44px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($prosedur as $p)
                <tr>
                    <td><span class="badge bg-light text-dark border">{{ $p->kode }}</span></td>
                    <td class="small">{{ $p->icd9->deskripsi_panjang ?? ($p->icd9->deskripsi_pendek ?? '-') }}</td>
                    <td class="text-center small">{{ $p->jumlah ?? 1 }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-prosedur" data-kd="{{ $p->kode }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted small py-2">Belum ada prosedur terinput</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
