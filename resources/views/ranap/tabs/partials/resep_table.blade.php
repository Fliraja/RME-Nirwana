@php
    $angka = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $kelasStatus = ['menunggu apotek' => 'resep-status-tunggu', 'diterima apotek' => 'resep-status-proses', 'diserahkan' => 'resume-status-ok'];
@endphp
@forelse($daftarResep as $resep)
    <div class="p-3 {{ $loop->last ? '' : 'border-bottom' }}">
        <div class="resep-kepala mb-2">
            <span class="fw-semibold">Resep {{ $resep->no_resep }}</span>
            · {{ \Carbon\Carbon::parse($resep->tgl_peresepan . ' ' . $resep->jam_peresepan)->format('d/m H.i') }}
            · {{ $resep->nm_dokter ?? $resep->kd_dokter }}
            <span class="resume-status {{ $kelasStatus[$resep->status_teks] ?? 'resep-status-tunggu' }} ms-1">{{ $resep->status_teks }}</span>
        </div>

        {{-- Tabel Obat Non Racikan --}}
        @if($resep->items->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small">
                            <th>Nama Obat (Non-Racikan)</th>
                            <th style="width: 80px;">Jumlah</th>
                            <th>Aturan Pakai</th>
                            <th style="width: 50px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resep->items as $dr)
                            <tr>
                                <td class="small fw-semibold">{{ $dr->nama_brng }}</td>
                                <td class="small">{{ $angka($dr->jml) }} {{ $dr->kode_sat }}</td>
                                <td class="small">{{ $dr->aturan_pakai }}</td>
                                <td class="text-center">
                                    @unless($resep->divalidasi)
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-resep-obat" data-noresep="{{ $resep->no_resep }}" data-kode="{{ $dr->kode_brng }}" aria-label="Hapus {{ $dr->nama_brng }}">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                        </button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Tabel Obat Racikan --}}
        @if($resep->racikan->isNotEmpty())
            <h6 class="small fw-bold text-muted mb-2">Resep Racikan:</h6>
            @foreach($resep->racikan as $racik)
                <div class="border rounded p-2 mb-2 bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold small">{{ $racik->nama_racik }} ({{ $racik->jml_dr }} bungkus, {{ $racik->aturan_pakai }})</span>
                        @unless($resep->divalidasi)
                            <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-racikan" data-noresep="{{ $resep->no_resep }}" data-noracik="{{ $racik->no_racik }}" aria-label="Hapus racikan {{ $racik->nama_racik }}">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            </button>
                        @endunless
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@empty
    <div class="text-center p-3 text-muted small">
        Belum ada resep untuk kunjungan ini.
    </div>
@endforelse
