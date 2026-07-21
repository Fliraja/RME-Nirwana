<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover small">
        <thead class="bg-light">
            <tr>
                <th width="15%">No. Order</th>
                <th width="15%">Waktu Order</th>
                <th width="25%">Pemeriksaan (Jasa)</th>
                <th width="35%">Detail Item (Sub)</th>
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
                                   onclick="hapusLab('{{ $row->noorder }}', '{{ $item->kd_jenis_prw }}')" 
                                   title="Hapus Pemeriksaan ini">
                                    <i class="fa fa-times-circle"></i>
                                </a>
                            @endif
                        </div>
                    </td>

                    <td class="align-middle">
                        @php
                            $details = DB::table('permintaan_detail_permintaan_lab as pd')
                                ->join('template_laboratorium as tl', 'pd.id_template', '=', 'tl.id_template')
                                ->where('pd.noorder', $row->noorder)
                                ->where('pd.kd_jenis_prw', $item->kd_jenis_prw)
                                ->select('tl.Pemeriksaan', 'pd.id_template')
                                ->get();
                        @endphp

                        @if($details->count() > 0)
                            <ul class="list-unstyled mb-0">
                                @foreach($details as $d)
                                    <li class="d-flex justify-content-between border-bottom py-1">
                                        <span>- {{ $d->Pemeriksaan }}</span>
                                        @if($item->stts_bayar == 'Belum')
                                            <a href="javascript:void(0)" class="text-warning" 
                                               onclick="hapusLab('{{ $row->noorder }}', '{{ $item->kd_jenis_prw }}', '{{ $d->id_template }}')"
                                               title="Hapus item ini">
                                                <i class="fa fa-minus-square"></i>
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <span class="text-muted italic small">- Umum -</span>
                        @endif
                    </td>

                    @if($index === 0)
                        <td rowspan="{{ $row->pemeriksaan->count() }}" class="text-center align-middle">
                            @if($item->stts_bayar == 'Belum')
                                <button class="btn btn-danger btn-sm" onclick="hapusLab('{{ $row->noorder }}')" title="Batalkan Seluruh Order">
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
                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat Permintaan lab.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>