<div class="table-responsive">
    <table id="riwayatmedis" class="table table-bordered table-striped table-hover align-middle" style="width:100%">
        <thead>
            <tr class="table-light">
                <th style="width: 100px;">Tanggal</th>
                <th style="width: 130px;">Nomor Rawat</th>
                <th>Unit / DPJP</th>
                <th>Keluhan & Pemeriksaan</th>
                <th>Diagnosa (Penilaian)</th>
                <th>Terapi & Rencana</th>
                <th>Obat</th>
                <th>Laboratorium</th>
                <th>Radiologi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riwayat as $item)
                @php
                    $soap = ($item->status_lanjut == 'Ralan') ? $item->pemeriksaanRalan : $item->pemeriksaanRanap;
                @endphp
                <tr>
                    <td class="text-nowrap small">
                        <strong>{{ \Carbon\Carbon::parse($item->tgl_registrasi)->translatedFormat('d/m/Y') }}</strong>
                        <span class="d-block badge {{ $item->status_lanjut == 'Ranap' ? 'bg-primary' : 'bg-secondary' }}">
                            {{ $item->status_lanjut }}
                        </span>
                    </td>
                    <td>
                        <small class="badge bg-light text-dark border">{{ $item->no_rawat }}</small>
                    </td>
                    <td>
                        <strong>{{ $item->status_lanjut == 'Ralan' ? ($item->poliklinik->nm_poli ?? 'Poli') : 'Rawat Inap' }}</strong><br>
                        <small class="text-muted"><i class="fas fa-user-md me-1"></i>{{ $item->dokter->nm_dokter ?? '-' }}</small>
                    </td>
                    <td class="small">
                        <strong>S:</strong> {{ $soap->keluhan ?? '-' }} <br>
                        <strong>O:</strong> {{ $soap->pemeriksaan ?? '-' }}
                    </td>
                    <td class="small">
                        <strong>A:</strong> {{ $soap->penilaian ?? '-' }}
                    </td>
                    <td class="small">
                        <strong>P (Terapi):</strong> {{ $soap->rtl ?? '-' }} <br>
                        <strong>Instruksi:</strong> {{ $soap->instruksi ?? '-' }}
                    </td>
                    <td>
                        <ul class="list-unstyled mb-0 small">
                            @forelse($item->detailObat as $obat)
                                <li><i class="fas fa-pills me-1 text-primary"></i> {{ $obat->barang->nama_brng ?? 'Obat' }} ({{ $obat->jml }})</li>
                            @empty
                                <li class="text-muted fst-italic">-</li>
                            @endforelse
                        </ul>
                    </td>
                    <td>
                        <ul class="list-unstyled mb-0 small">
                            @forelse($item->detailLab as $lab)
                                <li>
                                    <small class="fw-bold">{{ $lab->template->Pemeriksaan ?? '-' }}:</small>
                                    {{ $lab->nilai }} {{ $lab->template->satuan ?? '' }} 
                                    <small class="text-muted">({{ $lab->nilai_rujukan }})</small>
                                </li>
                            @empty
                                <li class="text-muted fst-italic">-</li>
                            @endforelse
                        </ul>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            @forelse($item->gambarRadiologi as $rad)
                                <a href="{{ config('app.simrs_url') }}/radiologi/{{ $rad->lokasi_gambar }}" target="_blank" title="Klik untuk memperbesar">
                                    <img src="{{ config('app.simrs_url') }}/radiologi/{{ $rad->lokasi_gambar }}" 
                                         class="img-thumbnail" style="width: 48px; height: 48px; object-fit: cover;">
                                </a>
                            @empty
                                <span class="text-muted fst-italic small">-</span>
                            @endforelse
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center p-4 text-muted">Belum ada riwayat medis sebelumnya.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            @if(isset($detailPasien))
                <a href="{{ route('report.riwayat-pdf', $detailPasien->no_rkm_medis) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-file-pdf me-1"></i> Cetak 5 Riwayat Terakhir
                </a>
            @endif
        </div>
        <div>
            {{ $riwayat->links() }}
        </div>
    </div>
</div>
