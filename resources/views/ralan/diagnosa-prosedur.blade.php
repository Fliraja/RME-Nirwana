{{-- File: resources/views/ralan/diagnosa-prosedur.blade.php --}}

<div class="row g-3 mt-1" id="diagnosa-prosedur-wrap" data-no-rawat="{{ $no_rawat }}">
    <!-- Diagnosa (ICD-10) -->
    <div class="col-lg-6">
        <div class="ralan-card h-100">
            <div class="ralan-card-head">
                <span><i class="fas fa-stethoscope me-2 text-primary"></i>Diagnosa (ICD-10)</span>
            </div>
            <div class="ralan-card-body">
                <label class="form-label small fw-semibold text-muted">Cari kode / nama penyakit</label>
                <select id="select-icd10" placeholder="Ketik kode / nama penyakit..."></select>

                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle mb-2">
                        <thead>
                            <tr class="small text-muted">
                                <th style="width: 22%;">Kode</th>
                                <th>Nama Penyakit</th>
                                <th style="width: 44px;"></th>
                            </tr>
                        </thead>
                        <tbody id="staging-diagnosa" data-cols="3" data-empty="Belum ada diagnosa dipilih">
                            <tr class="staging-empty"><td colspan="3" class="text-center text-muted small py-2">Belum ada diagnosa dipilih</td></tr>
                        </tbody>
                    </table>
                </div>

                <button type="button" id="btn-simpan-diagnosa" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-save me-1"></i> Simpan Diagnosa
                </button>

                <hr class="my-3">

                <div class="small fw-semibold text-muted mb-2">Diagnosa tersimpan</div>
                <div id="ralan-diagnosa-table">
                    @include('ralan.partials.diagnosa_table')
                </div>
            </div>
        </div>
    </div>

    <!-- Prosedur (ICD-9) -->
    <div class="col-lg-6">
        <div class="ralan-card h-100">
            <div class="ralan-card-head">
                <span><i class="fas fa-briefcase-medical me-2 text-success"></i>Prosedur / Tindakan (ICD-9)</span>
            </div>
            <div class="ralan-card-body">
                <label class="form-label small fw-semibold text-muted">Cari kode / deskripsi prosedur</label>
                <select id="select-icd9" placeholder="Ketik kode / deskripsi prosedur..."></select>

                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle mb-2">
                        <thead>
                            <tr class="small text-muted">
                                <th style="width: 22%;">Kode</th>
                                <th>Deskripsi</th>
                                <th style="width: 74px;">Jumlah</th>
                                <th style="width: 44px;"></th>
                            </tr>
                        </thead>
                        <tbody id="staging-prosedur" data-cols="4" data-empty="Belum ada prosedur dipilih">
                            <tr class="staging-empty"><td colspan="4" class="text-center text-muted small py-2">Belum ada prosedur dipilih</td></tr>
                        </tbody>
                    </table>
                </div>

                <button type="button" id="btn-simpan-prosedur" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-save me-1"></i> Simpan Prosedur
                </button>

                <hr class="my-3">

                <div class="small fw-semibold text-muted mb-2">Prosedur tersimpan</div>
                <div id="ralan-prosedur-table">
                    @include('ralan.partials.prosedur_table')
                </div>
            </div>
        </div>
    </div>
</div>
