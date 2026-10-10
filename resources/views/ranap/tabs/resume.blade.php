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
    $defaultRiwayatPenyakit = $isSaved ? $resume->jalannya_penyakit : ($soapAwal->keluhan ?? $soapTerakhir->keluhan ?? '');
    $defaultPemeriksaanFisik = $isSaved ? $resume->pemeriksaan_fisik : ($soapTerakhir->pemeriksaan ?? $soapAwal->pemeriksaan ?? '');
    $defaultDiagnosaPrimer = $isSaved ? $resume->diagnosa_utama : ($diagnosaList->first()->penyakit->nm_penyakit ?? $kamar->diagnosa_awal ?? '');
    $defaultKdDiagnosaPrimer = $isSaved ? $resume->kd_diagnosa_utama : ($diagnosaList->first()->kd_penyakit ?? '');
    
    $sekunderItem1 = $diagnosaList->skip(1)->first();
    $defaultDiagnosaSekunder = $isSaved ? $resume->diagnosa_sekunder : ($sekunderItem1->penyakit->nm_penyakit ?? '');
    $defaultKdDiagnosaSekunder = $isSaved ? $resume->kd_diagnosa_sekunder : ($sekunderItem1->kd_penyakit ?? '');

    $defaultTindakan = $isSaved ? $resume->tindakan_dan_operasi : ($prosedurList->map(function($p) { return ($p->icd9->deskripsi_panjang ?? $p->kode); })->implode("\n"));
    $defaultObatRs = $isSaved ? $resume->obat_di_rs : implode(', ', $obatRsList ?? []);
    $defaultPenunjang = $isSaved ? $resume->pemeriksaan_penunjang : '';
    $defaultEdukasi = $isSaved ? $resume->edukasi : '';
    $defaultObatPulang = $isSaved ? $resume->obat_pulang : '';
    $defaultKontrol = $isSaved && $resume->kontrol ? date('Y-m-d\TH:i', strtotime($resume->kontrol)) : '';

    $selectedDokter = $isSaved ? $resume->kd_dokter : ($dpjp->kd_dokter ?? $currentUserNip ?? '');
@endphp

<div id="wrapperResumeRanap">
    <div class="ralan-card mb-4">
        <div class="ralan-card-head d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-bold text-dark fs-6">
                    <i class="fas fa-file-medical-alt me-2 text-primary"></i>Resume Pasien Pulang (Rawat Inap)
                </span>
                @if($isSaved)
                    <span class="badge bg-success ms-2"><i class="fas fa-check-circle me-1"></i>Sudah Tersimpan</span>
                @else
                    <span class="badge bg-warning text-dark ms-2"><i class="fas fa-clock me-1"></i>Belum Dibuat</span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnTarikDataAutoResume" title="Tarik otomatis data diagnosa, obat, dan SOAP">
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
                        <label class="form-label small fw-bold text-muted">Dokter Penanggung Jawab Pelayanan (DPJP)</label>
                        <select name="kd_dokter" id="resume_kd_dokter" class="form-select form-select-sm">
                            <option value="">-- Pilih Dokter DPJP --</option>
                            @foreach($dokterList as $doc)
                                <option value="{{ $doc->kd_dokter }}" {{ $selectedDokter == $doc->kd_dokter ? 'selected' : '' }}>
                                    {{ $doc->nm_dokter }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Diagnosa Awal Masuk (MRS)</label>
                        <input type="text" name="diagnosa_awal" id="resume_diagnosa_awal" class="form-control form-control-sm"
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
                            <label class="form-label fw-bold text-dark d-flex align-items-center">
                                <span class="badge bg-primary me-2">4</span> Diagnosa Primer / Utama
                            </label>
                            <div class="row g-2">
                                <div class="col-8">
                                    <label class="form-label small text-muted">Nama Diagnosa Primer</label>
                                    <input type="text" name="diagnosa_utama" id="resume_diagnosa_utama" class="form-control form-control-sm"
                                           value="{{ $defaultDiagnosaPrimer }}" placeholder="Nama diagnosa utama...">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small text-muted">Kode ICD-10</label>
                                    <input type="text" name="kd_diagnosa_utama" id="resume_kd_diagnosa_utama" class="form-control form-control-sm"
                                           value="{{ $defaultKdDiagnosaPrimer }}" placeholder="Misal: A09">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center">
                                <span class="badge bg-primary me-2">5</span> Diagnosa Sekunder (Penyerta / Komplikasi)
                            </label>
                            <div class="row g-2 mb-2">
                                <div class="col-8">
                                    <label class="form-label small text-muted">Diagnosa Sekunder 1</label>
                                    <input type="text" name="diagnosa_sekunder" id="resume_diagnosa_sekunder" class="form-control form-control-sm"
                                           value="{{ $defaultDiagnosaSekunder }}" placeholder="Diagnosa sekunder 1...">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small text-muted">Kode ICD-10</label>
                                    <input type="text" name="kd_diagnosa_sekunder" id="resume_kd_diagnosa_sekunder" class="form-control form-control-sm"
                                           value="{{ $defaultKdDiagnosaSekunder }}" placeholder="Misal: I10">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-8">
                                    <input type="text" name="diagnosa_sekunder2" id="resume_diagnosa_sekunder2" class="form-control form-control-sm"
                                           value="{{ $isSaved ? $resume->diagnosa_sekunder2 : '' }}" placeholder="Diagnosa sekunder 2 (opsional)...">
                                </div>
                                <div class="col-4">
                                    <input type="text" name="kd_diagnosa_sekunder2" id="resume_kd_diagnosa_sekunder2" class="form-control form-control-sm"
                                           value="{{ $isSaved ? $resume->kd_diagnosa_sekunder2 : '' }}" placeholder="Kode ICD">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 6. Tindakan / Terapi yang Diberikan --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center">
                                <span class="badge bg-primary me-2">6a</span> Tindakan / Prosedur / Operasi
                            </label>
                            <textarea name="tindakan_dan_operasi" id="resume_tindakan_operasi" class="form-control" rows="3"
                                      placeholder="Uraian tindakan medis, fisioterapi, operasi, atau prosedur yang dilakukan...">{{ $defaultTindakan }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center">
                                <span class="badge bg-primary me-2">6b</span> Terapi Obat Selama di Rumah Sakit
                            </label>
                            <textarea name="obat_di_rs" id="resume_obat_di_rs" class="form-control" rows="3"
                                      placeholder="Daftar injeksi, infus, dan obat oral yang diberikan selama perawatan...">{{ $defaultObatRs }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- 7. Kondisi Pasien Waktu Pulang --}}
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center mb-3">
                                <span class="badge bg-primary me-2">7</span> Kondisi Pasien Waktu Pulang
                            </label>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="d-flex flex-wrap gap-3 p-2 bg-light rounded border">
                                        <div class="form-check">
                                            <input class="form-check-input radio-kondisi-pulang" type="radio" name="kondisi_pulang_opsi" id="kondisi_sembuh" value="sembuh" {{ $selectedKondisi == 'sembuh' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold text-success" for="kondisi_sembuh">
                                                <i class="fas fa-check-circle me-1"></i> Sembuh / Membaik
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input radio-kondisi-pulang" type="radio" name="kondisi_pulang_opsi" id="kondisi_aps" value="aps" {{ $selectedKondisi == 'aps' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold text-warning" for="kondisi_aps">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Pulang Atas Permintaan Sendiri (APS)
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input radio-kondisi-pulang" type="radio" name="kondisi_pulang_opsi" id="kondisi_meninggal_48_kurang" value="meninggal_kurang_48" {{ $selectedKondisi == 'meninggal_kurang_48' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold text-danger" for="kondisi_meninggal_48_kurang">
                                                <i class="fas fa-cross me-1"></i> Meninggal Dunia &lt; 48 Jam
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input radio-kondisi-pulang" type="radio" name="kondisi_pulang_opsi" id="kondisi_meninggal_48_lebih" value="meninggal_lebih_48" {{ $selectedKondisi == 'meninggal_lebih_48' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold text-danger" for="kondisi_meninggal_48_lebih">
                                                <i class="fas fa-cross me-1"></i> Meninggal Dunia &gt; 48 Jam
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input radio-kondisi-pulang" type="radio" name="kondisi_pulang_opsi" id="kondisi_dirujuk" value="dirujuk" {{ $selectedKondisi == 'dirujuk' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold text-primary" for="kondisi_dirujuk">
                                                <i class="fas fa-ambulance me-1"></i> Dirujuk ke Faskes Lain
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                {{-- Input Tambahan Khusus jika Dirujuk --}}
                                <div class="col-md-12 {{ $selectedKondisi == 'dirujuk' ? '' : 'd-none' }}" id="containerNamaRujukan">
                                    <label class="form-label small fw-bold text-primary">Nama Rumah Sakit / Faskes Rujukan Tujuan</label>
                                    <input type="text" name="nama_faskes_rujukan" id="resume_nama_faskes_rujukan" class="form-control form-control-sm"
                                           value="{{ $namaRujukan }}" placeholder="Contoh: RSUP Dr. Kariadi Semarang, RSUD Tugurejo...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 8. Instruksi Follow-up / Tindak Lanjut --}}
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <label class="form-label fw-bold text-dark d-flex align-items-center mb-3">
                                <span class="badge bg-primary me-2">8</span> Instruksi Follow-up / Tindak Lanjut
                            </label>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">
                                        <i class="fas fa-calendar-check text-primary me-1"></i> Jadwal Rencana Kontrol Kembali
                                    </label>
                                    <input type="datetime-local" name="kontrol" id="resume_kontrol" class="form-control form-control-sm"
                                           value="{{ $defaultKontrol }}">
                                    <small class="text-muted fst-italic">Tanggal & jam kontrol poli rawat jalan.</small>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-muted">
                                        <i class="fas fa-info-circle text-primary me-1"></i> Edukasi & Anjuran Pasien Pulang
                                    </label>
                                    <textarea name="edukasi" id="resume_edukasi" class="form-control form-control-sm" rows="2"
                                              placeholder="Edukasi istirahat, diet / nutrisi, tanda bahaya yang harus segera ke IGD...">{{ $defaultEdukasi }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">
                                        <i class="fas fa-pills text-success me-1"></i> Obat Pulang (Resep Obat yang Dibawa Pulang)
                                    </label>
                                    <textarea name="obat_pulang" id="resume_obat_pulang" class="form-control form-control-sm" rows="3"
                                              placeholder="Nama obat pulang, dosis, aturan pakai, jumlah (Contoh: Paracetamol 500mg 3x1 tab sesudah makan, Cefixime 200mg 2x1 cap)...">{{ $defaultObatPulang }}</textarea>
                                </div>
                            </div>
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
