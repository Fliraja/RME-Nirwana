@php
    $isSaved = !empty($resume);

    // Deteksi opsi kondisi pulang
    $selectedKondisi = 'sembuh';
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
        } else {
            $selectedKondisi = 'sembuh';
        }
    }

    // Default pre-fill jika belum ada resume
    $defaultRiwayatPenyakit = $isSaved ? $resume->jalannya_penyakit : ($latestKeluhan ?? ($soapTerakhir->keluhan ?? ''));
    $defaultPemeriksaanFisik = $isSaved ? $resume->pemeriksaan_fisik : ($latestPemeriksaan ?? ($soapTerakhir->pemeriksaan ?? ''));

    // Diagnosa Primer
    $defaultKdDiagnosaPrimer = $isSaved ? ($resume->kd_diagnosa_utama ?? '') : ($diagnosaList->first()->kd_penyakit ?? '');
    $defaultDiagnosaPrimer = $isSaved ? ($resume->diagnosa_utama ?? '') : ($diagnosaList->first()->penyakit->nm_penyakit ?? $kamar->diagnosa_awal ?? '');

    // Diagnosa Sekunder (List 1-4)
    $sekunderItems = [];
    if ($isSaved) {
        if (!empty($resume->kd_diagnosa_sekunder) || !empty($resume->diagnosa_sekunder)) {
            $sekunderItems[] = ['kd' => $resume->kd_diagnosa_sekunder ?? '', 'nm' => $resume->diagnosa_sekunder ?? ''];
        }
        if (!empty($resume->kd_diagnosa_sekunder2) || !empty($resume->diagnosa_sekunder2)) {
            $sekunderItems[] = ['kd' => $resume->kd_diagnosa_sekunder2 ?? '', 'nm' => $resume->diagnosa_sekunder2 ?? ''];
        }
        if (!empty($resume->kd_diagnosa_sekunder3) || !empty($resume->diagnosa_sekunder3)) {
            $sekunderItems[] = ['kd' => $resume->kd_diagnosa_sekunder3 ?? '', 'nm' => $resume->diagnosa_sekunder3 ?? ''];
        }
        if (!empty($resume->kd_diagnosa_sekunder4) || !empty($resume->diagnosa_sekunder4)) {
            $sekunderItems[] = ['kd' => $resume->kd_diagnosa_sekunder4 ?? '', 'nm' => $resume->diagnosa_sekunder4 ?? ''];
        }
    } else {
        foreach ($diagnosaList->skip(1)->take(4) as $dSekunder) {
            $sekunderItems[] = [
                'kd' => $dSekunder->kd_penyakit ?? '',
                'nm' => $dSekunder->penyakit->nm_penyakit ?? ''
            ];
        }
    }

    $defaultTindakan = $isSaved 
        ? $resume->tindakan_dan_operasi 
        : ($latestRtl ?? ($soapTerakhir->rtl ?? ($prosedurList->map(function($p) { return ($p->icd9->deskripsi_panjang ?? $p->kode); })->implode("\n"))));
    $defaultPenunjang = $isSaved ? $resume->pemeriksaan_penunjang : ($ringkasanPenunjang ?? '');
    $defaultEdukasi = $isSaved ? $resume->edukasi : ($latestInstruksi ?? ($soapTerakhir->instruksi ?? ''));
    $defaultObatPulang = $isSaved ? $resume->obat_pulang : '';
    $defaultKontrol = $isSaved && $resume->kontrol ? date('Y-m-d\TH:i', strtotime($resume->kontrol)) : '';

    $selectedDokter = $isSaved ? $resume->kd_dokter : ($dpjp->kd_dokter ?? $currentUserNip ?? '');
    $namaDokterDpjp = $isSaved ? ($resume->dokter->nm_dokter ?? ($dpjp->nm_dokter ?? $selectedDokter)) : ($dpjp->nm_dokter ?? (Auth::user()->dokter_data->nm_dokter ?? $selectedDokter));

    // Siapkan list sekunder dari rekam medis untuk auto-pull
    $autoSekunderItems = [];
    foreach ($diagnosaList->skip(1)->take(4) as $dSekunder) {
        $autoSekunderItems[] = [
            'kd' => $dSekunder->kd_penyakit ?? '',
            'nm' => $dSekunder->penyakit->nm_penyakit ?? ''
        ];
    }

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
        'diagnosa_utama' => $diagnosaList->first()->penyakit->nm_penyakit ?? $kamar->diagnosa_awal ?? '',
        'kd_diagnosa_utama' => $diagnosaList->first()->kd_penyakit ?? '',
        'diagnosa_sekunder_items' => $autoSekunderItems,
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

                {{-- 4. Diagnosa Primer & 5. Diagnosa Sekunder --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center mb-1">
                                <span class="badge bg-primary me-2">4</span> Diagnosa Primer / Utama
                            </label>
                            <small class="text-muted d-block mb-2">Cari berdasarkan kode atau nama ICD-10:</small>
                            <select id="select-resume-icd10-primer" class="form-select form-select-sm" placeholder="Ketik kode / nama ICD-10 diagnosa utama...">
                                @if(!empty($defaultKdDiagnosaPrimer))
                                    <option value="{{ $defaultKdDiagnosaPrimer }}" selected>
                                        {{ $defaultKdDiagnosaPrimer }} - {{ $defaultDiagnosaPrimer }}
                                    </option>
                                @endif
                            </select>
                            {{-- Hidden inputs untuk dikirim ke controller/DB --}}
                            <input type="hidden" name="kd_diagnosa_utama" id="resume_kd_diagnosa_utama" value="{{ $defaultKdDiagnosaPrimer }}">
                            <input type="hidden" name="diagnosa_utama" id="resume_diagnosa_utama" value="{{ $defaultDiagnosaPrimer }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-white shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold text-dark d-flex align-items-center mb-0">
                                    <span class="badge bg-primary me-2">5</span> Diagnosa Sekunder (Maks. 4)
                                </label>
                                <span class="badge bg-light text-secondary border small">Tag Badges</span>
                            </div>
                            <small class="text-muted d-block mb-2">Pilih diagnosa penyerta / komplikasi (maksimal 4):</small>
                            <select id="select-resume-icd10-sekunder" class="form-select form-select-sm" multiple="multiple" placeholder="Ketik kode / nama ICD-10 sekunder...">
                                @foreach($sekunderItems as $sItem)
                                    @if(!empty($sItem['kd']))
                                        <option value="{{ $sItem['kd'] }}" selected>
                                            {{ $sItem['kd'] }} - {{ $sItem['nm'] }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>

                            {{-- 4 pasang hidden input untuk DB resume_pasien_ranap --}}
                            <input type="hidden" name="kd_diagnosa_sekunder" id="resume_kd_diagnosa_sekunder" value="{{ $sekunderItems[0]['kd'] ?? '' }}">
                            <input type="hidden" name="diagnosa_sekunder" id="resume_diagnosa_sekunder" value="{{ $sekunderItems[0]['nm'] ?? '' }}">

                            <input type="hidden" name="kd_diagnosa_sekunder2" id="resume_kd_diagnosa_sekunder2" value="{{ $sekunderItems[1]['kd'] ?? '' }}">
                            <input type="hidden" name="diagnosa_sekunder2" id="resume_diagnosa_sekunder2" value="{{ $sekunderItems[1]['nm'] ?? '' }}">

                            <input type="hidden" name="kd_diagnosa_sekunder3" id="resume_kd_diagnosa_sekunder3" value="{{ $sekunderItems[2]['kd'] ?? '' }}">
                            <input type="hidden" name="diagnosa_sekunder3" id="resume_diagnosa_sekunder3" value="{{ $sekunderItems[2]['nm'] ?? '' }}">

                            <input type="hidden" name="kd_diagnosa_sekunder4" id="resume_kd_diagnosa_sekunder4" value="{{ $sekunderItems[3]['kd'] ?? '' }}">
                            <input type="hidden" name="diagnosa_sekunder4" id="resume_diagnosa_sekunder4" value="{{ $sekunderItems[3]['nm'] ?? '' }}">
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
                                {{-- 1. Sembuh / Membaik --}}
                                <div class="col-md">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'sembuh' ? 'border-success bg-success-subtle text-success-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_sembuh">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_sembuh" value="sembuh" {{ $selectedKondisi == 'sembuh' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-check-circle text-success me-1"></i> Sembuh / Membaik</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Izin dokter</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- 2. APS --}}
                                <div class="col-md">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'aps' ? 'border-warning bg-warning-subtle text-warning-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_aps">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_aps" value="aps" {{ $selectedKondisi == 'aps' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-exclamation-triangle text-warning me-1"></i> Pulang APS</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Permintaan sendiri</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- 3. Dirujuk --}}
                                <div class="col-md">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'dirujuk' ? 'border-primary bg-primary-subtle text-primary-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_dirujuk">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_dirujuk" value="dirujuk" {{ $selectedKondisi == 'dirujuk' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-ambulance text-primary me-1"></i> Dirujuk</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Pindah RS lain</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- 4. Meninggal < 48 Jam --}}
                                <div class="col-md">
                                    <label class="kondisi-pulang-card d-flex align-items-center p-2 rounded border cursor-pointer h-100 transition-all {{ $selectedKondisi == 'meninggal_kurang_48' ? 'border-danger bg-danger-subtle text-danger-emphasis fw-bold shadow-sm' : 'bg-light text-secondary' }}" for="kondisi_meninggal_48_kurang">
                                        <input class="form-check-input radio-kondisi-pulang me-2" type="radio" name="kondisi_pulang_opsi" id="kondisi_meninggal_48_kurang" value="meninggal_kurang_48" {{ $selectedKondisi == 'meninggal_kurang_48' ? 'checked' : '' }}>
                                        <div>
                                            <div class="small fw-semibold"><i class="fas fa-cross text-danger me-1"></i> Meninggal &lt; 48 Jam</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Wafat &lt; 48 jam MRS</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- 5. Meninggal > 48 Jam --}}
                                <div class="col-md">
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
