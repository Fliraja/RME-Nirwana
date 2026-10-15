<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover align-middle mb-0" id="tableRiwayatTtv">
        <thead class="table-light">
            <tr class="small">
                <th style="width: 130px;">Waktu</th>
                <th>Tanda Vital</th>
                <th>Status Fisik</th>
                <th>Kesadaran & Alergi</th>
                <th>Catatan SBAR</th>
                <th>Petugas</th>
                <th class="text-center" style="width: 100px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riwayatTtv as $ttv)
                <tr id="row-ttv-{{ str_replace('-', '', $ttv->tgl_perawatan) }}-{{ str_replace(':', '', $ttv->jam_rawat) }}"
                    class="ttv-row-item"
                    data-ttv="{{ json_encode($ttv) }}"
                    title="Klik untuk menyalin data TTV ini ke form input">
                    <td class="small">
                        <span class="fw-bold text-dark d-block">
                            {{ \Carbon\Carbon::parse($ttv->tgl_perawatan)->translatedFormat('d/m/Y') }}
                        </span>
                        <span class="badge bg-light text-muted border">{{ $ttv->jam_rawat }}</span>
                    </td>
                    <td class="small">
                        <div>Suhu: <strong>{{ $ttv->suhu_tubuh ?: '-' }} °C</strong></div>
                        <div>TD: <strong>{{ $ttv->tensi ?: '-' }}</strong></div>
                        <div>Nadi: <strong>{{ $ttv->nadi ?: '-' }} x/m</strong></div>
                        <div>RR: <strong>{{ $ttv->respirasi ?: '-' }} x/m</strong></div>
                        <div>SpO2: <strong>{{ $ttv->spo2 ?: '-' }}%</strong></div>
                    </td>
                    <td class="small">
                        <div>TB: {{ $ttv->tinggi ?: '-' }} cm</div>
                        <div>BB: {{ $ttv->berat ?: '-' }} kg</div>
                        <div>GCS: {{ $ttv->gcs ?: '-' }}</div>
                    </td>
                    <td class="small">
                        <span class="badge bg-light text-dark border mb-1 d-inline-block">
                            {{ $ttv->kesadaran ?: 'Compos Mentis' }}
                        </span>
                        @if(\App\Support\Alergi::ada($ttv->alergi))
                            <div class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> Alergi: {{ $ttv->alergi }}</div>
                        @endif
                    </td>
                    <td class="small">
                        @if($ttv->sbar)
                            <div class="p-2 rounded bg-light border" style="white-space: pre-line; max-width: 350px;">{{ $ttv->sbar }}</div>
                        @else
                            <span class="text-muted fst-italic">-</span>
                        @endif
                    </td>
                    <td class="small">
                        <span class="text-muted">{{ $ttv->nama_petugas ?? $ttv->nip }}</span>
                    </td>
                    <td class="text-center">
                        @if($ttv->nip == $currentUserNip || session('role') === 'admin')
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-warning" title="Edit TTV" onclick='editTtv(@json($ttv))'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" title="Hapus TTV" onclick="hapusTtv('{{ $ttv->tgl_perawatan }}', '{{ $ttv->jam_rawat }}')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        @else
                            <span class="text-muted small fst-italic">Read-only</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center p-4 text-muted small">
                        Belum ada data observasi tanda vital untuk rawat inap ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
