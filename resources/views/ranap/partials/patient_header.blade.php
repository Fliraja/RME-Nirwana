<!-- Info Pasien Rawat Inap -->
<div class="row mb-4">
    <div class="col-md-6">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr>
                    <td class="fw-bold text-muted" style="width: 35%">Nama Lengkap</td>
                    <td class="fw-semibold">{{ $detailPasien->pasien->nm_pasien }}</td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted">No. RM</td>
                    <td class="fw-semibold">{{ $detailPasien->no_rkm_medis }}</td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted">No. Rawat</td>
                    <td class="fw-semibold">{{ $detailPasien->no_rawat }}</td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted">JK / Umur</td>
                    <td class="fw-semibold">
                        {{ $detailPasien->pasien->jk == 'L' ? 'Laki-Laki' : 'Perempuan' }}, {{ $detailPasien->pasien->umur }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr>
                    <td class="fw-bold text-muted" style="width: 35%">Kamar / Bangsal</td>
                    <td class="fw-semibold">
                        {{ $kamarAktif->kd_kamar ?? '-' }} - {{ $kamarAktif->kamar->bangsal->nm_bangsal ?? 'Bangsal' }}
                        <span class="badge bg-light text-dark border ms-1">{{ $kamarAktif->kamar->kelas ?? 'Kelas' }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted">Dokter DPJP</td>
                    <td class="fw-semibold">
                        @if($detailPasien->dpjpRanap && $detailPasien->dpjpRanap->isNotEmpty())
                            {{ $detailPasien->dpjpRanap->first()->dokter->nm_dokter ?? $detailPasien->dokter->nm_dokter }}
                        @else
                            {{ $detailPasien->dokter->nm_dokter ?? '-' }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted">Tgl Masuk</td>
                    <td class="fw-semibold">
                        {{ $kamarAktif ? \Carbon\Carbon::parse($kamarAktif->tgl_masuk)->translatedFormat('d F Y') . ' (' . $kamarAktif->jam_masuk . ')' : '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="fw-bold text-muted">Jenis Bayar</td>
                    <td>
                        <span class="badge bg-primary">
                            {{ $detailPasien->penjab->png_jawab ?? '-' }}
                        </span>
                        <span class="badge {{ ($kamarAktif && $kamarAktif->stts_pulang == '-') ? 'bg-success' : 'bg-secondary' }} ms-1">
                            {{ ($kamarAktif && $kamarAktif->stts_pulang == '-') ? 'Aktif Dirawat' : 'Pulang (' . ($kamarAktif->stts_pulang ?? 'Keluar') . ')' }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
