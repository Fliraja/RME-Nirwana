<div id="formPermintaanRadiologiRanap">
    {{-- BAGIAN 1: FORM PERMINTAAN RADIOLOGI --}}
    <div class="ralan-card mb-4">
        <div class="ralan-card-head">
            <span class="fw-bold text-dark">
                <i class="fas fa-x-ray me-2 text-primary"></i>Form Permintaan Pemeriksaan Radiologi (Rawat Inap)
            </span>
        </div>
        <div class="ralan-card-body">
            @csrf
            <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Pilih Tindakan / Pemeriksaan Radiologi</label>
                    <select name="kd_jenis_prw_rad[]" id="select-radiologi" class="form-select" multiple="multiple" style="width: 100%"></select>
                    <small class="text-muted fst-italic">*Pemeriksaan otomatis disesuaikan dengan katalog Radiologi Rawat Inap</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Diagnosa Klinis / Indikasi Pemeriksaan</label>
                    <textarea name="diagnosa_klinis" class="form-control form-control-sm" rows="2" placeholder="Diagnosa klinis & posisi foto rontgen yang diminta..."></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold text-muted">Informasi Tambahan / Catatan Posisi</label>
                    <textarea name="informasi_tambahan" class="form-control form-control-sm" rows="2" placeholder="Catatan kondisi pasien (misal: posisi supine / AP / lateral, pasien dengan traksi, dsb)..."></textarea>
                </div>
            </div>

            <div class="text-end mt-3 pt-2 border-top">
                <button type="button" id="btnSimpanRadiologi" class="btn btn-primary px-4">
                    <i class="fas fa-paper-plane me-1"></i> Kirim Permintaan Radiologi
                </button>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: RIWAYAT PERMINTAAN RADIOLOGI HARI INI --}}
    <div class="ralan-card mb-4">
        <div class="ralan-card-head">
            <span class="fw-bold text-dark">
                <i class="fas fa-clock me-2 text-primary"></i>Riwayat Permintaan Radiologi Hari Ini
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="reloadRanapTable('radiologi')">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
        <div class="ralan-card-body p-0">
            <div id="ranap-rad-table">
                @include('ranap.tabs.partials.radiologi_table')
            </div>
        </div>
    </div>

    {{-- BAGIAN 3: HASIL RADIOLOGI (GALERI GAMBAR & EKSPERTISE BACAAN) --}}
    <div class="row g-3">
        {{-- Galeri Foto Hasil Rontgen/Scan --}}
        <div class="col-lg-6">
            <div class="ralan-card h-100">
                <div class="ralan-card-head">
                    <span class="fw-bold text-dark">
                        <i class="fas fa-images me-2 text-primary"></i>Galeri Foto Radiologi (Hasil Rontgen / Scan)
                    </span>
                    <span class="badge bg-light text-muted border">{{ count($hasilRadiologi['gambar']) }} Gambar</span>
                </div>
                <div class="ralan-card-body">
                    @if(empty($hasilRadiologi['gambar']) || count($hasilRadiologi['gambar']) == 0)
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-image fa-3x mb-2 d-block text-secondary" style="opacity: 0.3;"></i>
                            Belum ada file gambar hasil radiologi yang diunggah.
                        </div>
                    @else
                        <div class="row g-2">
                            @foreach($hasilRadiologi['gambar'] as $img)
                                <div class="col-sm-6 col-md-4">
                                    <div class="card border p-1 text-center shadow-none">
                                        <a href="{{ config('app.simrs_url') }}/radiologi/{{ $img->lokasi_gambar }}" target="_blank" title="Klik untuk membuka ukuran penuh">
                                            <img src="{{ config('app.simrs_url') }}/radiologi/{{ $img->lokasi_gambar }}" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                                        </a>
                                        <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">
                                            {{ \Carbon\Carbon::parse($img->tgl_periksa)->translatedFormat('d/m/Y') }} {{ $img->jam }}
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Laporan Hasil Ekspertise Dokter Spesialis Radiologi --}}
        <div class="col-lg-6">
            <div class="ralan-card h-100">
                <div class="ralan-card-head">
                    <span class="fw-bold text-dark">
                        <i class="fas fa-file-signature me-2 text-success"></i>Hasil Ekspertise Radiologi (Bacaan Dokter Spesialis)
                    </span>
                </div>
                <div class="ralan-card-body">
                    @if(empty($hasilRadiologi['expertise']) || count($hasilRadiologi['expertise']) == 0)
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-notes-medical fa-3x mb-2 d-block text-secondary" style="opacity: 0.3;"></i>
                            Belum ada catatan ekspertise radiologi terverifikasi untuk nomor rawat ini.
                        </div>
                    @else
                        @foreach($hasilRadiologi['expertise'] as $exp)
                            <div class="p-3 mb-3 border rounded bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <span class="badge bg-primary">Laporan Bacaan</span>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($exp->tgl_periksa)->translatedFormat('d F Y') }} {{ $exp->jam }}
                                    </small>
                                </div>
                                <div class="small text-dark" style="white-space: pre-line; line-height: 1.6;">
                                    {{ $exp->hasil }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
