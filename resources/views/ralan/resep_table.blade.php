@php
    $pesanKunci = $pesanKunci ?? null;
    $angka = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $kelasStatus = ['menunggu apotek' => 'resep-status-tunggu', 'diterima apotek' => 'resep-status-proses', 'diserahkan' => 'resume-status-ok'];
@endphp
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
            @forelse($daftarResep as $resep)
                <tr>
                    <td colspan="4" class="resep-kepala">
                        <span class="fw-semibold">Resep {{ $resep->no_resep }}</span>
                        · {{ \Carbon\Carbon::parse($resep->tgl_peresepan . ' ' . $resep->jam_peresepan)->format('d/m H.i') }}
                        · {{ $resep->nm_dokter ?? $resep->kd_dokter }}
                        <span class="resume-status {{ $kelasStatus[$resep->status_teks] ?? 'resep-status-tunggu' }} ms-1">{{ $resep->status_teks }}</span>
                    </td>
                </tr>
                @foreach($resep->items as $item)
                <tr>
                    <td><i class="fas fa-pills text-primary" aria-hidden="true"></i> {{ $item->nama_brng }}</td>
                    <td class="text-center">{{ $angka($item->jml) }} {{ $item->kode_sat }}</td>
                    <td>{{ $item->aturan_pakai }}</td>
                    <td class="text-center">
                        @unless($resep->divalidasi || $pesanKunci)
                            <button class="btn btn-danger btn-xs" onclick="hapusObat('{{ $item->no_resep }}', '{{ $item->kode_brng }}')" aria-label="Hapus {{ $item->nama_brng }}">
                                <i class="fa fa-trash" aria-hidden="true"></i>
                            </button>
                        @endunless
                    </td>
                </tr>
                @endforeach

                @foreach($resep->racikan as $racik)
                <tr class="bg-light">
                    <td class="fw-bold">
                        <i class="fas fa-mortar-pestle text-info" aria-hidden="true"></i> {{ $racik->nama_racik }}
                        <span class="badge bg-info text-white">{{ $racik->nm_racik ?? $racik->kd_racik }}</span>
                    </td>
                    <td class="text-center fw-bold">{{ $racik->jml_dr }}</td>
                    <td class="fw-bold">{{ $racik->aturan_pakai }}</td>
                    <td class="text-center">
                        @unless($resep->divalidasi || $pesanKunci)
                            <button class="btn btn-danger btn-xs" onclick="hapusRacikan('{{ $racik->no_resep }}', '{{ $racik->no_racik }}')" aria-label="Hapus racikan {{ $racik->nama_racik }}">
                                <i class="fa fa-trash" aria-hidden="true"></i>
                            </button>
                        @endunless
                    </td>
                </tr>
                @foreach($racik->detail as $detail)
                <tr class="table-sm">
                    <td style="padding-left: 30px;" class="text-muted">- {{ $detail->nama_brng }}</td>
                    <td class="text-center text-muted small">{{ $angka($detail->jml) }}</td>
                    <td colspan="2" class="text-muted small">Dosis per bungkus {{ $detail->kandungan }}</td>
                </tr>
                @endforeach
                @endforeach
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted p-4">Belum ada resep untuk kunjungan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
