@if($resep)
    <div class="p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <span class="badge bg-primary">No. Resep: {{ $resep->no_resep }}</span>
                <span class="badge bg-secondary ms-1">Jam: {{ $resep->jam_peresepan }}</span>
                <span class="badge bg-info text-dark ms-1">Status: {{ strtoupper($resep->status) }}</span>
            </div>
        </div>

        {{-- Tabel Obat Non Racikan --}}
        @if($resep->resepDokter && $resep->resepDokter->isNotEmpty())
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
                        @foreach($resep->resepDokter as $dr)
                            <tr>
                                <td class="small fw-semibold">{{ $dr->dataBarang->nama_brng ?? $dr->kode_brng }}</td>
                                <td class="small">{{ $dr->jml }}</td>
                                <td class="small">{{ $dr->aturan_pakai }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-resep-obat" data-noresep="{{ $resep->no_resep }}" data-kode="{{ $dr->kode_brng }}">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Tabel Obat Racikan --}}
        @if($resep->resepRacikan && $resep->resepRacikan->isNotEmpty())
            <h6 class="small fw-bold text-muted mb-2">Resep Racikan:</h6>
            @foreach($resep->resepRacikan as $racik)
                <div class="border rounded p-2 mb-2 bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold small">{{ $racik->nama_racik }} ({{ $racik->jml_dr }} bungkus, {{ $racik->aturan_pakai }})</span>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-racikan" data-noresep="{{ $resep->no_resep }}" data-noracik="{{ $racik->no_racik }}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@else
    <div class="text-center p-3 text-muted small">
        Belum ada resep dokter yang dibuat hari ini untuk pasien ini.
    </div>
@endif
