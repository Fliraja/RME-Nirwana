@php
    $alergiPasien = \Illuminate\Support\Facades\DB::table('pemeriksaan_ranap')
        ->where('no_rawat', $detailPasien->no_rawat)
        ->whereNotNull('alergi')
        ->where('alergi', '!=', '')
        ->where('alergi', '!=', '-')
        ->where('alergi', '!=', 'tidak ada')
        ->where('alergi', '!=', 'TidakAda')
        ->where('alergi', '!=', 'tidak Ada')
        ->where('alergi', '!=', 'Tidak Ada')
        ->where('alergi', '!=', 'Tidak ada')
        ->orderBy('tgl_perawatan', 'desc')
        ->orderBy('jam_rawat', 'desc')
        ->value('alergi');

    $lamaHari = $kamarAktif ? (int) \Carbon\Carbon::parse($kamarAktif->tgl_masuk)->startOfDay()->diffInDays(\Carbon\Carbon::now()->startOfDay()) : 0;
@endphp

<!-- Info Pasien Rawat Inap -->
<div class="mb-4">
    @if(!empty($alergiPasien))
        <div class="alert alert-danger py-2 px-3 mb-3 d-flex align-items-center gap-2 border-danger shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle fa-lg text-danger"></i>
            <div>
                <strong class="text-uppercase">PERINGATAN ALERGI PASIEN:</strong>
                <span class="fw-bold text-dark">{{ $alergiPasien }}</span>
            </div>
        </div>
    @else
        <div class="mb-2">
            <span class="badge bg-light text-success border">
                <i class="fas fa-check-circle me-1"></i> Tidak Ada Catatan Alergi Obat / Makanan
            </span>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr>
                        <td class="fw-bold text-muted" style="width: 35%">Nama Lengkap</td>
                        <td class="fw-semibold text-primary fs-6">{{ $detailPasien->pasien->nm_pasien }}</td>
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
                            <span class="badge {{ $lamaHari >= 4 ? 'bg-warning text-dark' : 'bg-info text-dark' }} ms-1">
                                Hari ke-{{ $lamaHari + 1 }} ({{ $lamaHari }} malam)
                            </span>
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
</div>
