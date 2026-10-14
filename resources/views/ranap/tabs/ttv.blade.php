<div class="row">
    {{-- Form Input TTV & SBAR --}}
    <div class="col-lg-12 mb-4">
        <div class="ralan-card">
            <div class="ralan-card-head">
                <span class="fw-bold" id="titleFormTtv">
                    <i class="fas fa-heartbeat me-2 text-primary"></i>Form Tanda-Tanda Vital (TTV) & Catatan SBAR
                </span>
                <span class="badge bg-light text-muted border" id="badgeModeTtv">Mode: Tambah Baru</span>
            </div>
            <div class="ralan-card-body">
                <form id="formTtvRanap">
                    @csrf
                    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat ?? '' }}">
                    <input type="hidden" name="mode_edit_ttv" id="mode_edit_ttv" value="0">
                    <input type="hidden" name="tgl_perawatan_edit_ttv" id="tgl_perawatan_edit_ttv" value="">
                    <input type="hidden" name="jam_rawat_edit_ttv" id="jam_rawat_edit_ttv" value="">

                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="mb-3">
                                <label class="small fw-bold">Tensi (mmHg)</label>
                                <input type="text" name="tensi" id="ttv_tensi" class="form-control form-control-sm" placeholder="120/80">
                            </div>
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="mb-3">
                                <label class="small fw-bold">Suhu (°C)</label>
                                <input type="text" name="suhu_tubuh" id="ttv_suhu_tubuh" class="form-control form-control-sm" placeholder="36.5">
                            </div>
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="mb-3">
                                <label class="small fw-bold">Nadi (/mnt)</label>
                                <input type="text" name="nadi" id="ttv_nadi" class="form-control form-control-sm" placeholder="80">
                            </div>
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="mb-3">
                                <label class="small fw-bold">Respirasi (/mnt)</label>
                                <input type="text" name="respirasi" id="ttv_respirasi" class="form-control form-control-sm" placeholder="20">
                            </div>
                        </div>

                        <div class="col-md-3 col-12">
                            <div class="mb-3">
                                <label class="small fw-bold">Kesadaran</label>
                                <select name="kesadaran" id="ttv_kesadaran" class="form-select form-select-sm">
                                    <option value="Compos Mentis">Compos Mentis</option>
                                    <option value="Somnolence">Somnolence</option>
                                    <option value="Sopor">Sopor</option>
                                    <option value="Coma">Coma</option>
                                    <option value="Alert">Alert</option>
                                    <option value="Confusion">Confusion</option>
                                    <option value="Voice">Voice</option>
                                    <option value="Pain">Pain</option>
                                    <option value="Unresponsive">Unresponsive</option>
                                    <option value="Apatis">Apatis</option>
                                    <option value="Delirium">Delirium</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-2 col-6">
                            <div class="mb-3">
                                <label class="small fw-bold">Tinggi (cm)</label>
                                <input type="text" name="tinggi" id="ttv_tinggi" class="form-control form-control-sm" placeholder="165">
                            </div>
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="mb-3">
                                <label class="small fw-bold">Berat (Kg)</label>
                                <input type="text" name="berat" id="ttv_berat" class="form-control form-control-sm" placeholder="60">
                            </div>
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="mb-3">
                                <label class="small fw-bold">SpO2 (%)</label>
                                <input type="text" name="spo2" id="ttv_spo2" class="form-control form-control-sm" placeholder="98">
                            </div>
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="mb-3">
                                <label class="small fw-bold">GCS (E,V,M)</label>
                                <input type="text" name="gcs" id="ttv_gcs" class="form-control form-control-sm" placeholder="15">
                            </div>
                        </div>

                        <div class="col-md-4 col-12">
                            <div class="mb-3">
                                <label class="small fw-bold">Riwayat Alergi</label>
                                <input type="text" name="alergi" id="ttv_alergi" class="form-control form-control-sm" placeholder="Alergi obat / makanan...">
                            </div>
                        </div>
                    </div>

                    {{-- Catatan SBAR Terintegrasi --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="small fw-bold">
                                    <i class="fas fa-comments me-1 text-primary"></i> Catatan SBAR (Situation, Background, Assessment, Recommendation)
                                </label>
                                <textarea name="sbar" id="ttv_sbar" class="form-control form-control-sm" rows="3" placeholder="Masukkan Catatan SBAR disini..."></textarea>
                                <!-- <small class="text-muted fst-italic">*Catatan SBAR akan otomatis tersinkronisasi pada tabel catatan_sbar_ranap dengan timestamp yang sama.</small> -->
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2 mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-secondary btn-sm d-none" id="btnBatalEditTtv" onclick="resetFormTtv()">
                            <i class="fas fa-times me-1"></i> Batal Edit
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm px-4" id="btnSimpanTtv">
                            <i class="fas fa-save me-1"></i> Simpan TTV & SBAR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<style>
    .ttv-row-item {
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    .ttv-row-item:hover {
        background-color: #eef6ff !important;
    }
</style>

    {{-- Tabel Riwayat TTV & SBAR --}}
    <div class="col-lg-12">
        <div class="ralan-card">
            <div class="ralan-card-head d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold">
                        <i class="fas fa-history me-2 text-primary"></i>Riwayat Observasi Tanda-Tanda Vital & SBAR
                    </span>
                    <span class="badge bg-light text-muted border ms-2 small fw-normal">
                        <i class="fas fa-mouse-pointer me-1 text-primary"></i>Klik baris untuk salin ke form
                    </span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="reloadRanapTable('ttv')">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>

            <div id="ranap-ttv-table">
                @include('ranap.tabs.partials.ttv_table')
            </div>
        </div>
    </div>
</div>
