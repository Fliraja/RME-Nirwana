<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover align-middle mb-0" id="tableRiwayatSoap">
        <thead class="table-light">
            <tr class="small">
                <th style="width: 140px;">Waktu & Dokter</th>
                <th>Subjektif (S)</th>
                <th>Objektif (O)</th>
                <th>Asesmen (A)</th>
                <th>Plan (P)</th>
                <th>Instruksi</th>
                <th>Evaluasi</th>
                <th class="text-center" style="width: 110px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riwayatSoap as $soap)
                <tr id="row-soap-{{ str_replace('-', '', $soap->tgl_perawatan) }}-{{ str_replace(':', '', $soap->jam_rawat) }}"
                    class="soap-row-item"
                    data-soap="{{ json_encode($soap) }}"
                    title="Klik untuk menyalin data SOAP ini ke form input">
                    <td class="small">
                        <span class="fw-bold text-dark d-block">
                            {{ \Carbon\Carbon::parse($soap->tgl_perawatan)->translatedFormat('d/m/Y') }}
                        </span>
                        <span class="badge bg-light text-muted border mb-1">{{ $soap->jam_rawat }}</span>
                        <small class="text-muted d-block"><i class="fas fa-user-md me-1"></i>{{ $soap->nama_petugas ?? $soap->nip }}</small>
                    </td>
                    <td class="small">{{ $soap->keluhan ?: '-' }}</td>
                    <td class="small">{{ $soap->pemeriksaan ?: '-' }}</td>
                    <td class="small"><strong>{{ $soap->penilaian ?: '-' }}</strong></td>
                    <td class="small">{{ $soap->rtl ?: '-' }}</td>
                    <td class="small">{{ $soap->instruksi ?: '-' }}</td>
                    <td class="small">{{ $soap->evaluasi ?: '-' }}</td>
                    <td class="text-center">
                        @if($soap->nip == $currentUserNip || session('role') === 'admin')
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-warning" title="Edit Catatan" onclick='editSoap(@json($soap))'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" title="Hapus Catatan" onclick="hapusSoap('{{ $soap->tgl_perawatan }}', '{{ $soap->jam_rawat }}')">
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
                    <td colspan="8" class="text-center p-4 text-muted small">
                        Belum ada catatan perkembangan SOAP untuk rawat inap ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
