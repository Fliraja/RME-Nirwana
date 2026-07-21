<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover small">
        <thead class="bg-light">
            <tr>
                <th width="15%">No. Order</th>
                <th width="15%">Waktu Order</th>
                <th width="30%">Pemeriksaan (Jasa)</th>
                <th width="30%">Diagnosa Klinis & Catatan</th>
                <th width="10%" class="text-center">Aksi Order</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riwayat as $row)
                @foreach($row->pemeriksaan as $index => $item)
                <tr>
                    @if($index === 0)
                        <td rowspan="{{ $row->pemeriksaan->count() }}" class="align-middle fw-bold">
                            {{ $row->noorder }}
                        </td>
                        <td rowspan="{{ $row->pemeriksaan->count() }}" class="align-middle text-center">
                            {{ $row->tgl_permintaan }} <br>
                            <span class="text-muted small">{{ $row->jam_permintaan }}</span>
                        </td>
                    @endif

                    <td class="align-middle">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>{{ $item->jenisPerawatan->nm_perawatan }}</span>
                            @if($item->stts_bayar == 'Belum')
                                <a href="javascript:void(0)" class="text-danger p-1" 
                                   onclick="hapusRadiologi('{{ $row->noorder }}', '{{ $item->kd_jenis_prw }}')" 
                                   title="Hapus Pemeriksaan ini">
                                    <i class="fa fa-times-circle"></i>
                                </a>
                            @endif
                        </div>
                    </td>

                    @if($index === 0)
                        <td rowspan="{{ $row->pemeriksaan->count() }}" class="align-middle">
                            <strong>Klinis:</strong> {{ $row->diagnosa_klinis }} <br>
                            <strong>Info:</strong> <span class="text-muted">{{ $row->informasi_tambahan }}</span>
                        </td>
                        
                        <td rowspan="{{ $row->pemeriksaan->count() }}" class="text-center align-middle">
                            @if($item->stts_bayar == 'Belum')
                                <button class="btn btn-danger btn-sm" onclick="hapusRadiologi('{{ $row->noorder }}')" title="Batalkan Seluruh Order">
                                    <i class="fa fa-trash"></i>
                                </button>
                            @else
                                <span class="badge bg-success"><i class="fa fa-check"></i> Diproses</span>
                            @endif
                        </td>
                    @endif
                </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                        Belum ada riwayat permintaan radiologi hari ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>