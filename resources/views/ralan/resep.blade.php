<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-tabs nav-pills mb-3 bg-light p-2 rounded" id="pills-tab-resep" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active btn-sm" id="pills-umum-tab" data-bs-toggle="pill" data-bs-target="#resep-umum" type="button" role="tab">
                    <i class="fas fa-pills me-1"></i> Obat Umum (Non-Racikan)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link btn-sm" id="pills-racikan-tab" data-bs-toggle="pill" data-bs-target="#resep-racikan" type="button" role="tab">
                    <i class="fas fa-mortar-pestle me-1"></i> Obat Racikan
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContentResep">
            <div class="tab-pane fade show active" id="resep-umum" role="tabpanel">
                <div class="card border-success mb-3">
                    <div class="card-header bg-success text-white py-2">
                        <i class="fas fa-pills me-1"></i> Form Resep Obat Umum
                    </div>
                    <div class="card-body">
                        <div id="formResepObat">
                            @csrf
                            <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">
                            
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="small fw-bold">Nama Obat <span class="text-danger">*</span></label>
                                    <select name="kode_obat" class="form-control form-control-sm kd_obat_ajax" style="width:100%"></select>
                                </div>
                                <div class="col-md-2">
                                    <label class="small fw-bold">Jumlah <span class="text-danger">*</span></label>
                                    <input type="number" name="jumlah" class="form-control form-control-sm" value="10" min="1">
                                </div>
                                <div class="col-md-5">
                                    <label class="small fw-bold">Aturan Pakai <span class="text-danger">*</span></label>
                                    <select name="aturan_pakai" class="form-control form-control-sm select2-aturan" style="width:100%">
                                        <option value=""></option>
                                        @foreach($masterAturan as $atp)
                                            <option value="{{ $atp->aturan }}">{{ $atp->aturan }}</option>
                                        @endforeach
                                        <option value="lainnya">--- Aturan Lainnya (Ketik Manual) ---</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mt-2 d-none" id="aturanManualUmum">
                                <div class="col-md-12">
                                    <label class="small fw-bold">Aturan Pakai Manual</label>
                                    <input type="text" name="aturan_pakai_lainnya" class="form-control form-control-sm" placeholder="Contoh: 3x1 sesudah makan">
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" id="btnTambahObat" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i> Tambah Obat
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="resetFormUmum()">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="resep-racikan" role="tabpanel">
                <div id="formResepRacikan">
                    @csrf
                    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">
                    
                    <div class="card border-success mb-3">
                        <div class="card-header bg-success text-white py-2">
                            <i class="fas fa-mortar-pestle me-1"></i> Informasi Racikan
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="small fw-bold">Nama Racikan <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_racik" class="form-control form-control-sm" placeholder="Contoh: Racikan Flu">
                                </div>
                                <div class="col-md-2">
                                    <label class="small fw-bold">Metode <span class="text-danger">*</span></label>
                                    <select name="kd_racik" class="form-control form-control-sm">
                                        @foreach($masterMetode as $m)
                                            <option value="{{ $m->kd_racik }}">{{ $m->nm_racik }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="small fw-bold">Jml Racik <span class="text-danger">*</span></label>
                                    <input type="number" name="jml_dr" id="jml_dr" class="form-control form-control-sm" value="10" min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold">Aturan Pakai <span class="text-danger">*</span></label>
                                    <select name="aturan_racik" class="form-control form-control-sm select2-aturan-racik" style="width:100%">
                                        <option value=""></option>
                                        @foreach($masterAturan as $atp)
                                            <option value="{{ $atp->aturan }}">{{ $atp->aturan }}</option>
                                        @endforeach
                                        <option value="lainnya">--- Aturan Lainnya (Ketik Manual) ---</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mt-2 d-none" id="aturanManualRacik">
                                <div class="col-md-12">
                                    <label class="small fw-bold">Aturan Pakai Manual</label>
                                    <input type="text" name="aturan_racik_lainnya" class="form-control form-control-sm" placeholder="Contoh: 3x1 sesudah makan">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-secondary mb-3">
                        <div class="card-header bg-secondary text-white py-2 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-list me-1"></i> Komposisi Obat Racikan</span>
                            <button type="button" class="btn btn-warning btn-sm" id="btnTambahBarisObat">
                                <i class="fas fa-plus me-1"></i> Tambah Baris
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0" id="tableKomposisi">
                                    <thead class="bg-light">
                                        <tr class="small text-center">
                                            <th width="35%">Nama Obat</th>
                                            <th width="10%">P1</th>
                                            <th width="10%">P2</th>
                                            <th width="20%">Kandungan</th>
                                            <th width="15%">Jml Stok</th>
                                            <th width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="text-center text-muted">
                                            <td colspan="6" class="py-3">
                                                <i class="fas fa-info-circle me-1"></i> 
                                                Belum ada komposisi obat. Klik tombol "Tambah Baris" untuk menambahkan.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" id="btnSimpanRacikan" class="btn btn-success text-white">
                            <i class="fas fa-save me-1"></i> Simpan Resep Racikan
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetFormRacikan()">
                            <i class="fas fa-redo me-1"></i> Reset Form
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="card border-success">
            <div class="card-header bg-success text-white py-2">
                <i class="fas fa-list-alt me-1"></i> Daftar Resep Pasien
            </div>
            <div class="card-body p-0">
                <div id="tabel-resep-container">
                    @include('ralan.resep_table') 
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styling tambahan untuk konsistensi */
.nav-pills .nav-link {
    border-radius: 0.25rem;
    transition: all 0.3s ease;
}

.nav-pills .nav-link:hover {
    background-color: #e9ecef;
}

.nav-pills .nav-link.active {
    background-color: #0d6efd;
    color: white !important;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.card-header {
    font-weight: 600;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}

/* Responsiveness */
@media (max-width: 768px) {
    .row > [class*='col-'] {
        margin-bottom: 0.5rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
    }
    
    .d-flex.gap-2 > .btn {
        width: 100%;
    }
}
</style>