{{-- File: resources/views/ralan/diagnosa-prosedur.blade.php --}}

<div class="row mt-3">
    <!-- Section Diagnosa (ICD-10) -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <i class="fas fa-stethoscope me-2"></i>
                <h6 class="mb-0 fw-bold">Diagnosa (ICD-10)</h6>
            </div>
            <div class="card-body">
                <form id="form-diagnosa" class="mb-3">
                    @csrf
                    <input type="hidden" name="no_rawat" value="{{ $no_rawat }}">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cari Kode / Nama Penyakit (ICD-10)</label>
                        <select id="select-icd10" name="kd_penyakit[]" class="form-select" multiple="multiple" style="width: 100%;"></select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prioritas</label>
                            <select name="prioritas" class="form-select">
                                <option value="1">1 - Diagnosa Utama</option>
                                <option value="2" selected>2 - Diagnosa Sekunder</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status Penyakit</label>
                            <select name="status_penyakit" class="form-select">
                                <option value="Lama" selected>Lama</option>
                                <option value="Baru">Baru</option>
                            </select>
                        </div>
                    </div>

                    <button type="button" id="btn-simpan-diagnosa" class="btn btn-primary w-100">
                        <i class="fas fa-plus-circle me-1"></i> Tambah Diagnosa
                    </button>
                </form>

                <h6 class="fw-bold mt-4 mb-2">Daftar Diagnosa Pasien</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle" id="table-diagnosa">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;">Kode</th>
                                <th>Nama Penyakit</th>
                                <th style="width: 15%;">Prioritas</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 10%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($diagnosa as $d)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $d->kd_penyakit }}</span></td>
                                    <td>{{ $d->penyakit->nm_penyakit ?? '-' }}</td>
                                    <td>
                                        @if($d->prioritas == '1')
                                            <span class="badge bg-danger">Utama</span>
                                        @else
                                            <span class="badge bg-info text-dark">Sekunder</span>
                                        @endif
                                    </td>
                                    <td>{{ $d->status_penyakit ?? '-' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-diagnosa" data-kd="{{ $d->kd_penyakit }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada diagnosa terinput</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Prosedur (ICD-9) -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white d-flex align-items-center">
                <i class="fas fa-procedures me-2"></i>
                <h6 class="mb-0 fw-bold">Prosedur / Tindakan (ICD-9)</h6>
            </div>
            <div class="card-body">
                <form id="form-prosedur" class="mb-3">
                    @csrf
                    <input type="hidden" name="no_rawat" value="{{ $no_rawat }}">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Cari Kode / Deskripsi Prosedur (ICD-9)</label>
                        <select id="select-icd9" name="kode[]" class="form-select" multiple="multiple" style="width: 100%;"></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" value="1" min="1">
                    </div>

                    <button type="button" id="btn-simpan-prosedur" class="btn btn-success w-100">
                        <i class="fas fa-plus-circle me-1"></i> Tambah Prosedur
                    </button>
                </form>

                <h6 class="fw-bold mt-4 mb-2">Daftar Prosedur Pasien</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle" id="table-prosedur">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;">Kode</th>
                                <th>Deskripsi Prosedur</th>
                                <th style="width: 15%;">Jumlah</th>
                                <th style="width: 10%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($prosedur as $p)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $p->kode }}</span></td>
                                    <td>{{ $p->icd9->deskripsi_panjang ?? ($p->icd9->deskripsi_pendek ?? '-') }}</td>
                                    <td>{{ $p->jumlah ?? 1 }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-prosedur" data-kode="{{ $p->kode }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada prosedur terinput</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
