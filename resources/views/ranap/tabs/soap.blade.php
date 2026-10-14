<div class="row">
    {{-- Form Input SOAP Ranap --}}
    <div class="col-lg-12 mb-4">
        <div class="ralan-card">
            <div class="ralan-card-head">
                <span class="fw-bold" id="titleFormSoap">
                    <i class="fas fa-notes-medical me-2 text-primary"></i>Catatan Perkembangan Pasien Terintegrasi (CPPT / SOAP)
                </span>
                <span class="badge bg-light text-muted border" id="badgeModeSoap">Mode: Tambah Baru</span>
            </div>
            <div class="ralan-card-body">
                <form id="formSoapRanap">
                    @csrf
                    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat ?? '' }}">
                    <input type="hidden" name="mode_edit_soap" id="mode_edit_soap" value="0">
                    <input type="hidden" name="tgl_perawatan_edit_soap" id="tgl_perawatan_edit_soap" value="">
                    <input type="hidden" name="jam_rawat_edit_soap" id="jam_rawat_edit_soap" value="">

                    <div class="row mt-2">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold mb-2">Subjek (Keluhan)</label>
                            <textarea name="keluhan" id="soap_keluhan" class="form-control" rows="5" placeholder="Keluhan utama, riwayat keluhan saat ini..."></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold mb-2">Objek (Pemeriksaan Fisik)</label>
                            <textarea name="pemeriksaan" id="soap_pemeriksaan" class="form-control" rows="5" placeholder="Hasil pemeriksaan fisik, status lokalis, penunjang..."></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold mb-2">Assesmen (Diagnosa)</label>
                            <textarea name="penilaian" id="soap_penilaian" class="form-control" rows="4" placeholder="Diagnosis kerja, diagnosis banding..."></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold mb-2">Plan (Terapi/Tindakan)</label>
                            <textarea name="rtl" id="soap_rtl" class="form-control" rows="4" placeholder="Rencana pengobatan, tindakan, monitoring..."></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold mb-2">Instruksi / RTL</label>
                            <textarea name="instruksi" id="soap_instruksi" class="form-control" rows="4" placeholder="Instruksi khusus kepada perawat / tim medis..."></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="fw-bold mb-2">Evaluasi</label>
                            <textarea name="evaluasi" id="soap_evaluasi" class="form-control" rows="3" placeholder="Evaluasi harian / perkembangan respon terapi..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2 mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-secondary btn-sm d-none" id="btnBatalEditSoap" onclick="resetFormSoap()">
                            <i class="fas fa-times me-1"></i> Batal Edit
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm px-4" id="btnSimpanSoap">
                            <i class="fas fa-save me-1"></i> Simpan SOAP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<style>
    .soap-row-item {
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    .soap-row-item:hover {
        background-color: #eef6ff !important;
    }
</style>

    {{-- Tabel Riwayat SOAP Ranap --}}
    <div class="col-lg-12">
        <div class="ralan-card">
            <div class="ralan-card-head d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold">
                        <i class="fas fa-history me-2 text-primary"></i>Riwayat Catatan CPPT / SOAP
                    </span>
                    <span class="badge bg-light text-muted border ms-2 small fw-normal">
                        <i class="fas fa-mouse-pointer me-1 text-primary"></i>Klik baris untuk salin ke form
                    </span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="reloadRanapTable('soap')">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
            <div id="ranap-soap-table">
                @include('ranap.tabs.partials.soap_table')
            </div>
        </div>
    </div>
</div>
