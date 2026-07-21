<div class="table-responsive">
    <table id="riwayatmedis" class="table table-bordered table-striped table-hover" style="width:100%">
        <thead>
            <tr class="bg-light">
                <th>Tanggal</th>
                <th>Nomor Rawat</th>
                <th>Klinik/Dokter</th>
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
                    <td>{{ $item->tgl_registrasi }}</td>
                    <td><small class="badge bg-secondary">{{ $item->no_rawat }}</small></td>
                    <td>
                        <strong>{{ $item->status_lanjut == 'Ralan' ? ($item->poliklinik->nm_poli ?? '-') : 'Rawat Inap' }}</strong><br>
                        <small class="text-muted">({{ $item->dokter->nm_dokter ?? '-' }})</small>
                    </td>
                    <td>
                        <strong>Keluhan:</strong> {{ $soap->keluhan ?? '-' }} <br>
                        <strong>Pemeriksaan:</strong> {{ $soap->pemeriksaan ?? '-' }}
                    </td>
                    <td>{{ $soap->penilaian ?? '-' }}</td>
                    <td>
                        <strong>Terapi:</strong> {{ $soap->rtl ?? '-' }} <br>
                        <strong>Rencana:</strong> {{ $soap->instruksi ?? '-' }}
                    </td>
                    <td>
                        <ul class="list-unstyled mb-0">
                            @foreach($item->detailObat as $obat)
                                <li><i class="fas fa-pills me-1 text-primary"></i> {{ $obat->barang->nama_brng ?? 'Obat' }} ({{ $obat->jml }})</li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        <ul class="list-unstyled mb-0">
                            @foreach($item->detailLab as $lab)
                                <li>
                                    <small><strong>{{ $lab->template->Pemeriksaan ?? '-' }}:</strong></small><br>
                                    {{ $lab->nilai }} {{ $lab->template->satuan ?? '' }} 
                                    <small class="text-muted">(Ref: {{ $lab->nilai_rujukan }})</small>
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($item->gambarRadiologi as $rad)
                                <a href="{{ config('app.simrs_url') }}/radiologi/{{ $rad->lokasi_gambar }}" target="_blank">
                                    <img src="{{ config('app.simrs_url') }}/radiologi/{{ $rad->lokasi_gambar }}" 
                                         class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                </a>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Belum ada riwayat medis.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-3 d-flex justify-content-end">
        {{ $riwayat->links() }}
    </div>
    <a href="{{ route('report.riwayat-pdf', $detailPasien->no_rkm_medis) }}" target="_blank" class="btn btn-danger btn-sm">
        <i class="fas fa-file-pdf"></i> Cetak 5 Riwayat Terakhir
    </a>
</div>