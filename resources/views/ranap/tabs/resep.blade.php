<div class="row ralan-resep">
    <div class="col-md-12">
        {{-- Nav pills Non-Racikan vs Racikan --}}
        <ul class="nav nav-pills mb-3 bg-light p-2 rounded" id="pills-tab-resep" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active btn-sm" id="pills-umum-tab" data-bs-toggle="pill" data-bs-target="#resep-umum" type="button" role="tab">
                    <i class="fas fa-pills me-1 text-primary"></i> Obat Non-Racikan (Reguler)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link btn-sm" id="pills-racikan-tab" data-bs-toggle="pill" data-bs-target="#resep-racikan" type="button" role="tab">
                    <i class="fas fa-mortar-pestle me-1 text-success"></i> Obat Racikan
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContentResep">
            {{-- SUBTAB 1: NON RACIKAN --}}
            <div class="tab-pane fade show active" id="resep-umum" role="tabpanel">
                <div class="ralan-card mb-3">
                    <div class="ralan-card-head">
                        <span class="fw-bold text-dark"><i class="fas fa-pills me-2 text-primary"></i>Form Resep Obat Non-Racikan (Ranap)</span>
                    </div>
                    <div class="ralan-card-body">
                        <div id="formResepObat">
                            @csrf
                            <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">

                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="small fw-semibold text-muted">Nama Obat (Ketik untuk cari)</label>
                                    <select name="kode_obat" class="kd_obat_ajax"></select>
                                </div>
                                <div class="col-md-2">
                                    <label class="small fw-semibold text-muted">Jumlah</label>
                                    <input type="number" name="jumlah" class="form-control form-control-sm" value="1" min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-semibold text-muted">Aturan Pakai</label>
                                    <select name="aturan_pakai" class="select2-aturan">
                                        <option value=""></option>
                                        @foreach($masterAturan as $atp)
                                            <option value="{{ $atp->aturan }}">{{ $atp->aturan }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1 d-grid">
                                    <button type="button" id="btnTambahObat" class="btn btn-success btn-sm" title="Tambah ke staging">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-sm align-middle mb-2">
                                    <thead>
                                        <tr class="small text-muted">
                                            <th>Obat</th>
                                            <th style="width: 90px;">Jumlah</th>
                                            <th>Aturan Pakai</th>
                                            <th style="width: 44px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="staging-obat" data-cols="4" data-empty="Belum ada obat dipilih">
                                        <tr class="staging-empty"><td colspan="4" class="text-center text-muted small py-2">Belum ada obat dipilih</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" id="btnSimpanResepObat" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-save me-1"></i> Simpan Resep Obat Ranap
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUBTAB 2: OBAT RACIKAN --}}
            <div class="tab-pane fade" id="resep-racikan" role="tabpanel">
                <div id="formResepRacikan">
                    @csrf
                    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">
                    
                    <div class="ralan-card mb-3">
                        <div class="ralan-card-head">
                            <span class="fw-bold text-dark"><i class="fas fa-mortar-pestle me-2 text-success"></i>Informasi Racikan</span>
                        </div>
                        <div class="ralan-card-body">
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="small fw-bold">Nama Racikan <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_racik" class="form-control form-control-sm" placeholder="Contoh: Racikan Sesak / Batuk">
                                </div>
                                <div class="col-md-2">
                                    <label class="small fw-bold">Metode <span class="text-danger">*</span></label>
                                    <select name="kd_racik" class="form-select form-select-sm">
                                        @foreach($masterMetode as $m)
                                            <option value="{{ $m->kd_racik }}">{{ $m->nm_racik }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="small fw-bold">Jml Bungkus <span class="text-danger">*</span></label>
                                    <input type="number" name="jml_dr" class="form-control form-control-sm" value="10" min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold">Aturan Pakai <span class="text-danger">*</span></label>
                                    <select name="aturan_racik" class="select2-aturan-racik form-select form-select-sm">
                                        <option value=""></option>
                                        @foreach($masterAturan as $atp)
                                            <option value="{{ $atp->aturan }}">{{ $atp->aturan }}</option>
                                        @endforeach
                                        <option value="lainnya">Lainnya...</option>
                                    </select>
                                    <input type="text" name="aturan_racik_lainnya" class="form-control form-control-sm mt-1 d-none" placeholder="Tulis aturan pakai custom...">
                                </div>
                                <div class="col-12">
                                    <label class="small fw-bold">Keterangan Tambahan</label>
                                    <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Contoh: diminum sesudah makan">
                                </div>
                            </div>

                            <hr class="my-3">

                            <h6 class="small fw-bold text-muted mb-2">Bahan-Bahan Obat Racikan:</h6>
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-5">
                                    <label class="small text-muted">Bahan Obat</label>
                                    <select id="racik_kode_brng" class="kd_obat_racik_ajax"></select>
                                </div>
                                <div class="col-md-2">
                                    <label class="small text-muted">P1 / P2</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" id="racik_p1" class="form-control" value="1" min="1">
                                        <span class="input-group-text">/</span>
                                        <input type="number" id="racik_p2" class="form-control" value="1" min="1">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="small text-muted">Kandungan (mg)</label>
                                    <input type="text" id="racik_kandungan" class="form-control form-control-sm" placeholder="Dosis...">
                                </div>
                                <div class="col-md-2">
                                    <label class="small text-muted">Jml Diambil</label>
                                    <input type="number" id="racik_jml" class="form-control form-control-sm" value="1" min="1" step="any">
                                </div>
                                <div class="col-md-1 d-grid">
                                    <button type="button" id="btnTambahBahanRacik" class="btn btn-success btn-sm">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-2">
                                    <thead class="table-light">
                                        <tr class="small text-muted">
                                            <th>Bahan Obat</th>
                                            <th style="width: 80px;">P1/P2</th>
                                            <th style="width: 100px;">Kandungan</th>
                                            <th style="width: 80px;">Jml</th>
                                            <th style="width: 44px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="staging-bahan-racik" data-cols="5" data-empty="Belum ada bahan ditambahkan">
                                        <tr class="staging-empty"><td colspan="5" class="text-center text-muted small py-2">Belum ada bahan ditambahkan</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" id="btnSimpanResepRacikan" class="btn btn-success btn-sm w-100 mt-2">
                                <i class="fas fa-save me-1"></i> Simpan Resep Racikan Ranap
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN 2: DAFTAR RESEP HARI INI DARI DOKTER --}}
        <div class="ralan-card mb-4">
            <div class="ralan-card-head">
                <span class="fw-bold text-dark">
                    <i class="fas fa-clipboard-list me-1 text-primary"></i> Antrean Resep Hari Ini (Validasi Apotek)
                </span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadResep(true)">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
            <div class="ralan-card-body p-0">
                <div id="resep-table-container">
                    @if($resep)
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="badge bg-primary">No. Resep: {{ $resep->no_resep }}</span>
                                    <span class="badge bg-secondary ms-1">Jam: {{ $resep->jam_peresepan }}</span>
                                    <span class="badge bg-info text-dark ms-1">Status: {{ strtoupper($resep->status) }}</span>
                                </div>
                            </div>

                            {{-- Tabel Obat Non Racikan --}}
                            @if($resep->resepDokter && $resep->resepDokter->isNotEmpty())
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr class="small">
                                                <th>Nama Obat (Non-Racikan)</th>
                                                <th style="width: 80px;">Jumlah</th>
                                                <th>Aturan Pakai</th>
                                                <th style="width: 50px;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($resep->resepDokter as $dr)
                                                <tr>
                                                    <td class="small fw-semibold">{{ $dr->dataBarang->nama_brng ?? $dr->kode_brng }}</td>
                                                    <td class="small">{{ $dr->jml }}</td>
                                                    <td class="small">{{ $dr->aturan_pakai }}</td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-resep-obat" data-noresep="{{ $resep->no_resep }}" data-kode="{{ $dr->kode_brng }}">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            {{-- Tabel Obat Racikan --}}
                            @if($resep->resepRacikan && $resep->resepRacikan->isNotEmpty())
                                <h6 class="small fw-bold text-muted mb-2">Resep Racikan:</h6>
                                @foreach($resep->resepRacikan as $racik)
                                    <div class="border rounded p-2 mb-2 bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold small">{{ $racik->nama_racik }} ({{ $racik->jml_dr }} bungkus, {{ $racik->aturan_pakai }})</span>
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-racikan" data-noresep="{{ $resep->no_resep }}" data-noracik="{{ $racik->no_racik }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @else
                        <div class="text-center p-3 text-muted small">
                            Belum ada resep dokter yang dibuat hari ini untuk pasien ini.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- BAGIAN 3: RIWAYAT REALISASI PEMBERIAN OBAT DI BANGSAL (HASIL) --}}
        <div class="ralan-card">
            <div class="ralan-card-head">
                <span class="fw-bold text-dark">
                    <i class="fas fa-syringe me-2 text-primary"></i>Riwayat Pemberian Obat di Ruang Perawatan (Realisasi Bangsal)
                </span>
                <span class="badge bg-light text-success border">
                    <i class="fas fa-check-circle me-1"></i> Data Real-Time Perawat
                </span>
            </div>
            <div class="ralan-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small">
                                <th style="width: 50px;">No</th>
                                <th style="width: 130px;">Waktu Diberikan</th>
                                <th>Nama Obat / Terapi</th>
                                <th style="width: 100px;">Jumlah</th>
                                <th style="width: 100px;">Satuan</th>
                                <th>Biaya Satuan</th>
                                <th>Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayatPemberian as $idx => $pemberian)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td class="small">
                                        <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($pemberian->tgl_perawatan)->translatedFormat('d/m/Y') }}</span>
                                        <small class="text-muted d-block">{{ $pemberian->jam }}</small>
                                    </td>
                                    <td class="small fw-semibold text-primary">
                                        <i class="fas fa-capsules me-1"></i> {{ $pemberian->nama_brng }}
                                    </td>
                                    <td class="small fw-bold">{{ $pemberian->jml }}</td>
                                    <td class="small text-muted">{{ $pemberian->kode_sat }}</td>
                                    <td class="small">Rp {{ number_format($pemberian->biaya_obat, 0, ',', '.') }}</td>
                                    <td class="small fw-bold">Rp {{ number_format($pemberian->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center p-4 text-muted">
                                        <i class="fas fa-pills fa-3x mb-2 d-block text-secondary" style="opacity: 0.3;"></i>
                                        Belum ada riwayat obat yang tercatat telah diberikan kepada pasien di bangsal.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
