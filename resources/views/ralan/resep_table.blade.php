<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover mt-2">
        <thead class="bg-light">
            <tr class="small">
                <th>Nama Obat / Racikan</th>
                <th width="15%">Jumlah / Komposisi</th>
                <th width="25%">Aturan Pakai</th>
                <th width="5%">Aksi</th>
            </tr>
        </thead>
        <tbody class="small">
            @if($resep)
                @foreach($resep->resepDokter as $item)
                <tr>
                    <td><i class="fas fa-pills text-primary"></i> {{ $item->dataBarang->nama_brng }}</td>
                    <td class="text-center">{{ $item->jml }}</td>
                    <td>{{ $item->aturan_pakai }}</td>
                    <td class="text-center">
                        <button class="btn btn-danger btn-xs" onclick="hapusObat('{{ $item->no_resep }}', '{{ $item->kode_brng }}')">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @endforeach

                @foreach($resep->resepRacikan as $racik)
                <tr class="bg-light">
                    <td class="fw-bold">
                        <i class="fas fa-mortar-pestle text-info"></i> {{ $racik->nama_racik }} 
                        <span class="badge bg-info text-white">{{ $racik->metode->nm_racik ?? $racik->kd_racik }}</span>
                    </td>
                    <td class="text-center fw-bold">{{ $racik->jml_dr }}</td>
                    <td class="fw-bold">{{ $racik->aturan_pakai }}</td>
                    <td class="text-center">
                        <button class="btn btn-danger btn-xs" onclick="hapusRacikan('{{ $racik->no_resep }}', '{{ $racik->no_racik }}')">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @foreach($racik->detailRacikan as $detail)
                <tr class="table-sm">
                    <td style="padding-left: 30px;" class="text-muted italic">
                        - {{ $detail->dataBarang->nama_brng }}
                    </td>
                    <td class="text-center text-muted small">
                        {{ $detail->p1 }}/{{ $detail->p2 }} ({{ $detail->jml }})
                    </td>
                    <td colspan="2" class="text-muted small">Kandungan: {{ $detail->kandungan }}</td>
                </tr>
                @endforeach
                @endforeach
            @else
                <tr>
                    <td colspan="4" class="text-center text-muted p-4">Belum ada resep yang dibuat hari ini.</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>