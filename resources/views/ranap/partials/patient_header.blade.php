<div class="card shadow-sm mb-4 border-0">
    <div class="card-body p-4 bg-white rounded">
        <div class="row align-items-center">
            <div class="col-lg-7 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-start gap-3">
                    <div class="avatar-lg bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; font-size: 24px; background: rgba(13, 110, 253, 0.1);">
                        <i class="fas fa-user-injured text-primary"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h4 class="mb-0 fw-bold text-dark">{{ $detailPasien->pasien->nm_pasien }}</h4>
                            <span class="badge bg-secondary">{{ $detailPasien->no_rkm_medis }}</span>
                            <span class="badge {{ $detailPasien->pasien->jk == 'L' ? 'bg-info' : 'bg-danger' }}">
                                {{ $detailPasien->pasien->jk == 'L' ? 'Laki-Laki' : 'Perempuan' }}
                            </span>
                        </div>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-barcode me-1"></i> No. Rawat: <strong>{{ $detailPasien->no_rawat }}</strong> &nbsp;|&nbsp;
                            <i class="fas fa-birthday-cake me-1"></i> Umur: {{ $detailPasien->pasien->umur }} &nbsp;|&nbsp;
                            <i class="fas fa-credit-card me-1"></i> Cara Bayar: <span class="badge bg-primary">{{ $detailPasien->penjab->png_jawab ?? '-' }}</span>
                        </p>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-success py-1 px-2">
                                <i class="fas fa-bed me-1"></i> {{ $kamarAktif->kamar->bangsal->nm_bangsal ?? 'Bangsal' }}
                            </span>
                            <span class="badge bg-light text-dark border py-1 px-2">
                                Kamar: <strong>{{ $kamarAktif->kd_kamar ?? '-' }}</strong> ({{ $kamarAktif->kamar->kelas ?? 'Kelas' }})
                            </span>
                            <span class="badge bg-light text-dark border py-1 px-2">
                                <i class="fas fa-calendar-check me-1 text-success"></i> Masuk: {{ $kamarAktif ? \Carbon\Carbon::parse($kamarAktif->tgl_masuk)->translatedFormat('d M Y') . ' ' . $kamarAktif->jam_masuk : '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 col-md-12 text-lg-end">
                <div class="d-flex flex-column align-items-lg-end align-items-start gap-1">
                    <div class="small text-muted">
                        Dokter DPJP:
                    </div>
                    <div class="fw-bold text-dark fs-6">
                        <i class="fas fa-user-md text-primary me-1"></i>
                        @if($detailPasien->dpjpRanap && $detailPasien->dpjpRanap->isNotEmpty())
                            {{ $detailPasien->dpjpRanap->first()->dokter->nm_dokter ?? $detailPasien->dokter->nm_dokter }}
                        @else
                            {{ $detailPasien->dokter->nm_dokter ?? 'Dokter DPJP' }}
                        @endif
                    </div>
                    <div class="mt-2">
                        @if($kamarAktif && $kamarAktif->stts_pulang == '-')
                            <span class="badge bg-success-soft text-success border border-success px-3 py-2">
                                <i class="fas fa-circle me-1 small animate-pulse"></i> Aktif Rawat Inap
                            </span>
                        @else
                            <span class="badge bg-secondary text-white px-3 py-2">
                                <i class="fas fa-sign-out-alt me-1"></i> Pulang ({{ $kamarAktif->stts_pulang ?? 'Keluar' }})
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
