@php
    $isSaved = !empty($resume);

    // Deteksi opsi kondisi pulang
    // Resume baru: dokter wajib memilih sendiri, tidak ada pilihan bawaan.
    $selectedKondisi = null;
    $namaRujukan = '';
    if ($isSaved) {
        if ($resume->cara_keluar === 'Pulang Atas Permintaan Sendiri') {
            $selectedKondisi = 'aps';
        } elseif ($resume->keadaan === 'Meninggal' && str_contains($resume->ket_keadaan, '<')) {
            $selectedKondisi = 'meninggal_kurang_48';
        } elseif ($resume->keadaan === 'Meninggal') {
            $selectedKondisi = 'meninggal_lebih_48';
        } elseif ($resume->cara_keluar === 'Pindah RS' || $resume->dilanjutkan === 'RS Lain') {
            $selectedKondisi = 'dirujuk';
            $namaRujukan = $resume->ket_keluar ?: $resume->ket_dilanjutkan;
        } elseif ($resume->keadaan === 'Sembuh') {
            $selectedKondisi = 'sembuh';
        } else {
            $selectedKondisi = 'membaik';
        }
    }

    // Default pre-fill jika belum ada resume
    $defaultRiwayatPenyakit = $isSaved ? $resume->jalannya_penyakit : ($latestKeluhan ?? ($soapTerakhir->keluhan ?? ''));
    $defaultPemeriksaanFisik = $isSaved ? $resume->pemeriksaan_fisik : ($latestPemeriksaan ?? ($soapTerakhir->pemeriksaan ?? ''));

    // Diagnosa Pasien (Utama + Sekunder 1-4, format baris seperti Ralan)
    if ($isSaved) {
        $savedDiag = array_values(array_filter([
            $resume->diagnosa_utama ?? '',
            $resume->diagnosa_sekunder ?? '',
            $resume->diagnosa_sekunder2 ?? '',
            $resume->diagnosa_sekunder3 ?? '',
            $resume->diagnosa_sekunder4 ?? '',
        ], 'strlen'));
        $formDiagnosa = !empty($savedDiag) ? $savedDiag : [''];
    } else {
        $pecahanSoap = !empty($latestPenilaian) ? \App\Support\DiagnosaSplitter::pecah($latestPenilaian) : null;
        if ($pecahanSoap && (!empty($pecahanSoap['utama']) || !empty($pecahanSoap['sekunder']))) {
            $formDiagnosa = array_values(array_filter(array_merge([$pecahanSoap['utama']], $pecahanSoap['sekunder']), 'strlen'));
        } elseif ($diagnosaList->isNotEmpty()) {
            $formDiagnosa = $diagnosaList->take(5)->map(fn($d) => $d->penyakit->nm_penyakit ?? $d->kd_penyakit)->filter()->values()->all();
        } elseif (!empty($kamar->diagnosa_awal)) {
            $formDiagnosa = [$kamar->diagnosa_awal];
        } else {
            $formDiagnosa = [''];
        }
    }

    // Prosedur / Tindakan (Utama + Sekunder 1-3, format baris seperti Ralan)
    if ($isSaved) {
        $savedPros = array_values(array_filter([
            $resume->prosedur_utama ?? '',
            $resume->prosedur_sekunder ?? '',
            $resume->prosedur_sekunder2 ?? '',
            $resume->prosedur_sekunder3 ?? '',
        ], 'strlen'));
        $formProsedur = !empty($savedPros) ? $savedPros : [];
    } else {
        if ($prosedurList->isNotEmpty()) {
            $formProsedur = $prosedurList->take(4)->map(fn($p) => $p->icd9->deskripsi_panjang ?? ($p->icd9->deskripsi_pendek ?? $p->kode))->filter()->values()->all();
        } else {
            $formProsedur = [];
        }
    }

    // Usulan prosedur dari daftar tindakan ranap yang tercatat
    $usulanProsedur = $prosedurList->map(fn($p) => $p->icd9->deskripsi_panjang ?? ($p->icd9->deskripsi_pendek ?? $p->kode))->filter()->unique()->values()->all();

    $defaultTindakan = $isSaved 
        ? $resume->tindakan_dan_operasi 
        : ($latestRtl ?? ($soapTerakhir->rtl ?? ($prosedurList->map(function($p) { return ($p->icd9->deskripsi_panjang ?? $p->kode); })->implode("\n"))));
    $defaultPenunjang = $isSaved ? $resume->pemeriksaan_penunjang : ($ringkasanPenunjang ?? '');
    $defaultEdukasi = $isSaved ? $resume->edukasi : ($latestInstruksi ?? ($soapTerakhir->instruksi ?? ''));
    $defaultObatPulang = $isSaved ? $resume->obat_pulang : '';
    $defaultKontrol = $isSaved && $resume->kontrol ? date('Y-m-d\TH:i', strtotime($resume->kontrol)) : '';

    $selectedDokter = $isSaved ? $resume->kd_dokter : ($dpjp->kd_dokter ?? $currentUserNip ?? '');
    $namaDokterDpjp = $isSaved ? ($resume->dokter->nm_dokter ?? ($dpjp->nm_dokter ?? $selectedDokter)) : ($dpjp->nm_dokter ?? (Auth::user()->dokter_data->nm_dokter ?? $selectedDokter));

    // Data diagnosa & prosedur untuk auto-pull
    $autoDiagPecah = !empty($latestPenilaian) ? \App\Support\DiagnosaSplitter::pecah($latestPenilaian) : null;
    $autoDiagList = ($autoDiagPecah && (!empty($autoDiagPecah['utama']) || !empty($autoDiagPecah['sekunder'])))
        ? array_values(array_filter(array_merge([$autoDiagPecah['utama']], $autoDiagPecah['sekunder']), 'strlen'))
        : ($diagnosaList->isNotEmpty() ? $diagnosaList->take(5)->map(fn($d) => $d->penyakit->nm_penyakit ?? $d->kd_penyakit)->filter()->values()->all() : array_values(array_filter([$kamar->diagnosa_awal ?? ''])));

    $autoProsedurList = $prosedurList->isNotEmpty()
        ? $prosedurList->take(4)->map(fn($p) => $p->icd9->deskripsi_panjang ?? ($p->icd9->deskripsi_pendek ?? $p->kode))->filter()->values()->all()
        : [];

    // Terapi / Tindakan terbaru dari Plan (RTL) SOAP, atau daftar tindakan/obat
    $latestPlanTerapi = $latestRtl ?? ($soapTerakhir->rtl ?? '');
    if (empty($latestPlanTerapi)) {
        $arrTindakanObat = [];
        $tindakanIcd = $prosedurList->map(function($p) { return ($p->icd9->deskripsi_panjang ?? $p->kode); })->filter()->implode("\n");
        if (!empty($tindakanIcd)) $arrTindakanObat[] = "[PROSEDUR/TINDAKAN]\n" . $tindakanIcd;
        if (!empty($obatRsList)) $arrTindakanObat[] = "[TERAPI OBAT RS]\n" . implode(', ', $obatRsList);
        $latestPlanTerapi = implode("\n\n", $arrTindakanObat);
    }

    // Data rekam medis terbaru untuk auto-pull
    $latestDataJson = json_encode([
        'riwayat_penyakit' => $latestKeluhan ?? ($soapTerakhir->keluhan ?? ''),
        'pemeriksaan_fisik' => $latestPemeriksaan ?? ($soapTerakhir->pemeriksaan ?? ''),
        'pemeriksaan_penunjang' => $ringkasanPenunjang ?? '',
        'diagnosa_list' => $autoDiagList,
        'prosedur_list' => $autoProsedurList,
        'tindakan' => $latestPlanTerapi,
        'edukasi' => $latestInstruksi ?? ($soapTerakhir->instruksi ?? ''),
    ]);
@endphp

<div id="wrapperResumeRanap">
    <div class="ralan-card mb-4">
        <div class="ralan-card-head d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-bold text-dark fs-6">
                    <i class="fas fa-file-medical-alt me-2 text-primary"></i>Resume Pasien Pulang (Rawat Inap)
                </span>
                @if($isSaved)
                    <span id="badgeStatusResume" class="badge bg-success ms-2"><i class="fas fa-check-circle me-1"></i>Sudah Tersimpan</span>
                @else
                    <span id="badgeStatusResume" class="badge bg-warning text-dark ms-2"><i class="fas fa-clock me-1"></i>Belum Dibuat</span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('ranap.resume-pdf', ['no_rawat' => str_replace('/', '-', $reg->no_rawat ?? '')]) }}" 
                   target="_blank" 
                   id="btnCetakResumePdf"
                   class="btn btn-sm btn-outline-danger {{ $isSaved ? '' : 'disabled' }}" 
                   title="Cetak Lembar Resmi Resume Medis Pasien (PDF)">
                    <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnTarikDataAutoResume"
                        data-auto="{{ $latestDataJson }}" title="Tarik otomatis data terbaru dari diagnosa, obat, dan SOAP">
                    <i class="fas fa-sync-alt me-1"></i> Tarik Data Rekam Medis
                </button>
            </div>
        </div>

        <div class="ralan-card-body">
            <form id="formResumePasienRanap">
                @csrf
                <input type="hidden" name="no_rawat" value="{{ $reg->no_rawat ?? '' }}">

                {{-- Baris Info DPJP & Diagnosa Masuk --}}
                <div class="row g-3 mb-4 p-3 bg-light rounded border">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Dokter Penanggung Jawab Pelayanan (DPJP Utama)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-user-md text-primary"></i></span>
                            <input type="text" class="form-control form-control-sm bg-light fw-semibold text-dark" value="{{ $namaDokterDpjp }}" readonly disabled>
                        </div>
                        <input type="hidden" name="kd_dokter" id="resume_kd_dokter" value="{{ $selectedDokter }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Diagnosa Awal Masuk</label>
                        <input type="text" name="diagnosa_awal" id="resume_diagnosa_awal" class="form-control form-control-sm" maxlength="100"
                               value="{{ $isSaved ? $resume->diagnosa_awal : ($kamar->diagnosa_awal ?? '') }}"
                               placeholder="Diagnosa saat awal masuk kamar inap...">
                    </div>
                </div>

                {{-- 1. Riwayat Penyakit & 2. Pemeriksaan Fisik --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center">
                                <span class="badge bg-primary me-2">1</span> Riwayat Penyakit
                            </label>
                            <textarea name="jalannya_penyakit" id="resume_riwayat_penyakit" class="form-control" rows="4"
                                      placeholder="Uraikan keluhan utama dan perjalanan riwayat penyakit selama dirawat...">{{ $defaultRiwayatPenyakit }}</textarea>
                            <small class="text-muted fst-italic mt-1 d-block">Mencakup keluhan masuk, onset gejala, dan riwayat perjalanan penyakit.</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center">
                                <span class="badge bg-primary me-2">2</span> Pemeriksaan Fisik
                            </label>
                            <textarea name="pemeriksaan_fisik" id="resume_pemeriksaan_fisik" class="form-control" rows="4"
                                      placeholder="Keadaan umum, status generalis, lokalis, tanda-tanda vital penting...">{{ $defaultPemeriksaanFisik }}</textarea>
                            <small class="text-muted fst-italic mt-1 d-block">Temuan fisik objektif saat perawatan dan menjelang kepulangan.</small>
                        </div>
                    </div>
                </div>

                {{-- 3. Pemeriksaan Penunjang --}}
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center">
                                <span class="badge bg-primary me-2">3</span> Pemeriksaan Penunjang (Laboratorium, Radiologi, Konsultasi, dll)
                            </label>
                            <textarea name="pemeriksaan_penunjang" id="resume_pemeriksaan_penunjang" class="form-control" rows="3"
                                      placeholder="Rangkuman hasil penting laboratorium, rontgen, USG, EKG, konsul spesialis, dll...">{{ $defaultPenunjang }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- 4. Diagnosis (Sistem Ralan: Input Teks Bebas + Urutan Utama/Sekunder) --}}
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold text-dark d-flex align-items-center mb-0">
                                    <span class="badge bg-primary me-2">4</span> Diagnosis Pasien
                                </label>
                                <span class="badge bg-light text-muted border small">Maksimal 5 baris (1 Utama + 4 Sekunder)</span>
                            </div>
                            <small class="text-muted d-block mb-2">Tuliskan diagnosis klinis dokter. Pengkodean ICD-10 dilakukan oleh tim rekam medis / casemix.</small>

                            <div class="resume-daftar" id="resume-ranap-diagnosa" data-nama="diagnosa" data-maks="5">
                                @foreach($formDiagnosa as $teks)
                                    @include('ranap.tabs.partials.resume_baris', ['nama' => 'diagnosa', 'nilai' => $teks])
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-link btn-sm resume-tambah px-0 mt-1" style="color: #0d7a4a; font-weight: 600; text-decoration: none;">
                                <i class="fas fa-plus-circle me-1"></i> Tambah diagnosis
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 5. Prosedur / Tindakan (Sistem Ralan: Input Teks Bebas + Urutan Utama/Sekunder) --}}
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold text-dark d-flex align-items-center mb-0">
                                    <span class="badge bg-primary me-2">5</span> Prosedur / Tindakan Medis
                                </label>
                                <span class="badge bg-light text-muted border small">Maksimal 4 baris (1 Utama + 3 Sekunder)</span>
                            </div>
                            <small class="text-muted d-block mb-2">Tuliskan prosedur, operasi, atau tindakan medis selama perawatan. Pengkodean ICD-9-CM dilakukan oleh tim rekam medis / casemix.</small>

                            <div class="resume-daftar" id="resume-ranap-prosedur" data-nama="prosedur" data-maks="4">
                                @forelse($formProsedur as $teks)
                                    @include('ranap.tabs.partials.resume_baris', ['nama' => 'prosedur', 'nilai' => $teks])
                                @empty
                                    @include('ranap.tabs.partials.resume_baris', ['nama' => 'prosedur', 'nilai' => ''])
                                @endforelse
                            </div>
                            <button type="button" class="btn btn-link btn-sm resume-tambah px-0 mt-1" style="color: #0d7a4a; font-weight: 600; text-decoration: none;">
                                <i class="fas fa-plus-circle me-1"></i> Tambah prosedur
                            </button>

                            @if(!empty($usulanProsedur))
                                <div class="mt-2 pt-2 border-top">
                                    <span class="small text-muted d-block mb-1">Usulan dari tindakan yang tercatat di ranap:</span>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($usulanProsedur as $usulan)
                                            <button type="button" class="resume-chip btn btn-sm" data-teks="{{ $usulan }}">+ {{ $usulan }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 6. Terapi / Tindakan (Menyesuaikan Plan SOAP) --}}
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center">
                                <span class="badge bg-primary me-2">6</span> Terapi / Tindakan (Plan Selama Perawatan)
                            </label>
                            <textarea name="tindakan_dan_operasi" id="resume_tindakan_operasi" class="form-control" rows="4"
                                      placeholder="Rencana pengobatan, terapi obat/injeksi, tindakan medis, fisioterapi, prosedur/operasi yang dilakukan...">{{ $defaultTindakan }}</textarea>
                            <small class="text-muted fst-italic mt-1 d-block">Tarik data otomatis menyalin kolom Plan (Terapi/Tindakan) dari SOAP terakhir.</small>
                        </div>
                    </div>
                </div>

                {{-- 7. Kondisi Pasien Waktu Pulang --}}
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center mb-1">
                                <span class="badge bg-primary me-2">7</span> Kondisi Pasien Waktu Pulang
                            </label>
                            <small class="text-muted d-block mb-3">Pilih salah satu status atau kondisi klinis saat pasien meninggalkan rumah sakit:</small>

                            <div class="row g-2">
                                {{-- 1. Sembuh --}}
                                <div class="col-6 col-lg-4 col-xxl-2">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'sembuh' ? 'border-success bg-success-subtle text-success-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_sembuh">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_sembuh" value="sembuh" {{ $selectedKondisi == 'sembuh' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-check-circle text-success me-1"></i> Sembuh</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Izin dokter</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- 2. Membaik --}}
                                <div class="col-6 col-lg-4 col-xxl-2">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'membaik' ? 'border-success bg-success-subtle text-success-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_membaik">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_membaik" value="membaik" {{ $selectedKondisi == 'membaik' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-arrow-trend-up text-success me-1"></i> Membaik</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Izin dokter</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- 3. APS --}}
                                <div class="col-6 col-lg-4 col-xxl-2">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'aps' ? 'border-warning bg-warning-subtle text-warning-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_aps">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_aps" value="aps" {{ $selectedKondisi == 'aps' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-exclamation-triangle text-warning me-1"></i> Pulang APS</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Permintaan sendiri</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- 4. Dirujuk --}}
                                <div class="col-6 col-lg-4 col-xxl-2">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'dirujuk' ? 'border-primary bg-primary-subtle text-primary-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_dirujuk">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_dirujuk" value="dirujuk" {{ $selectedKondisi == 'dirujuk' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-ambulance text-primary me-1"></i> Dirujuk</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Pindah RS lain</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- 5. Meninggal < 48 Jam --}}
                                <div class="col-6 col-lg-4 col-xxl-2">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'meninggal_kurang_48' ? 'border-danger bg-danger-subtle text-danger-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_meninggal_48_kurang">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_meninggal_48_kurang" value="meninggal_kurang_48" {{ $selectedKondisi == 'meninggal_kurang_48' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-cross text-danger me-1"></i> Meninggal &lt; 48 Jam</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Wafat &lt; 48 jam MRS</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- 6. Meninggal > 48 Jam --}}
                                <div class="col-6 col-lg-4 col-xxl-2">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'meninggal_lebih_48' ? 'border-danger bg-danger-subtle text-danger-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_meninggal_48_lebih">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_meninggal_48_lebih" value="meninggal_lebih_48" {{ $selectedKondisi == 'meninggal_lebih_48' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-cross text-danger me-1"></i> Meninggal &gt; 48 Jam</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Wafat &gt; 48 jam MRS</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Input Tambahan Khusus jika Dirujuk --}}
                            <div class="mt-3 {{ $selectedKondisi == 'dirujuk' ? '' : 'd-none' }}" id="containerNamaRujukan">
                                <label class="form-label small fw-bold text-primary">
                                    <i class="fas fa-hospital me-1"></i> Nama Rumah Sakit / Faskes Rujukan Tujuan
                                </label>
                                <input type="text" name="nama_faskes_rujukan" id="resume_nama_faskes_rujukan" class="form-control form-control-sm" maxlength="50"
                                       value="{{ $namaRujukan }}" placeholder="Contoh: RSUP Dr. Kariadi Semarang, RSUD Tugurejo...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 8. Instruksi Follow-up / Edukasi Pasien Pulang (Menyesuaikan Instruksi SOAP) --}}
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center mb-1">
                                <span class="badge bg-primary me-2">8</span> Instruksi Follow-up & Edukasi Pasien Pulang
                            </label>
                            <small class="text-muted d-block mb-2">Instruksi perawatan lanjutan di rumah, anjuran kontrol, pola diet, istirahat, serta tanda bahaya darurat:</small>
                            <textarea name="edukasi" id="resume_edukasi" class="form-control" rows="4"
                                      placeholder="Instruksi khusus kepada pasien / keluarga, anjuran istirahat, pembatasan aktivitas, edukasi tanda bahaya...">{{ $defaultEdukasi }}</textarea>
                            <small class="text-muted fst-italic mt-1 d-block">Tarik data otomatis menyalin kolom Instruksi / RTL dari SOAP terakhir.</small>

                            {{-- Hidden preserved inputs agar kompatibel dengan schema resume_pasien_ranap --}}
                            <input type="hidden" name="kontrol" id="resume_kontrol" value="{{ $defaultKontrol }}">
                            <input type="hidden" name="obat_pulang" id="resume_obat_pulang" value="{{ $defaultObatPulang }}">
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi Simpan --}}
                <div class="d-flex justify-content-end align-items-center gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2" id="btnSimpanResume">
                        <i class="fas fa-save me-1"></i> Simpan Resume Pasien
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
