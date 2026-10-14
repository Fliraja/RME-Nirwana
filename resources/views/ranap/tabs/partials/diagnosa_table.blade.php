<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr class="small">
                <th style="width: 22%;">Kode</th>
                <th>Nama Penyakit</th>
                <th style="width: 90px;">Prioritas</th>
                <th style="width: 44px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($diagnosa as $d)
                <tr>
                    <td><span class="badge bg-light text-dark border">{{ $d->kd_penyakit }}</span></td>
                    <td class="small">{{ $d->penyakit->nm_penyakit ?? '-' }}</td>
                    <td>
                        @if($d->prioritas == 1)
                            <span class="badge bg-primary">Primer</span>
                        @else
                            <span class="badge bg-secondary">{{ $d->prioritas }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-diagnosa" data-kd="{{ $d->kd_penyakit }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted small py-2">Belum ada diagnosa terinput</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
