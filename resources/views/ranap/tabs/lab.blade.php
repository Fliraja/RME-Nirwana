<div id="formPermintaanLabRanap">
    {{-- BAGIAN 1: FORM PERMINTAAN LAB --}}
    <div class="card border shadow-none mb-4" style="background-color: #fafbfc;">
        <div class="card-header bg-white border-bottom">
            <h6 class="mb-0 fw-bold text-primary">
                <i class="fas fa-file-medical me-1"></i> Form Permintaan Pemeriksaan Laboratorium (Rawat Inap)
            </h6>
        </div>
        <div class="card-body p-3">
            @csrf
            <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Pilih Paket / Jenis Pemeriksaan Lab</label>
                        <select name="kd_jenis_prw[]" id="select-lab" class="form-select" multiple="multiple" style="width: 100%"></select>
                        <small class="text-muted fst-italic">*Pemeriksaan otomatis disesuaikan dengan tarif Rawat Inap & Penjamin</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Diagnosa Klinis</label>
                        <textarea name="diagnosa_klinis" class="form-control form-control-sm" rows="2" placeholder="Indikasi / diagnosa klinis..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Informasi Tambahan / Catatan untuk Petugas</label>
                        <textarea name="informasi_tambahan" class="form-control form-control-sm" rows="2" placeholder="Catatan puasa, cito, dsb..."></textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <div id="container-template-lab" class="card shadow-none border p-3 h-100" style="min-height: 200px; background-color: #ffffff;">
                        <h6 class="fw-bold border-bottom pb-2 small text-dark"><i class="fas fa-microscope text-primary me-1"></i> Detail Item / Template Pemeriksaan</h6>
                        <div id="detail-pemeriksaan-placeholder" class="text-center text-muted mt-4">
                            <i class="fas fa-flask fa-3x mb-2" style="opacity: 0.25;"></i>
                            <p class="small mb-0">Pilih jenis pemeriksaan di samping untuk melihat sub-item pemeriksaan.</p>
                        </div>
                        <div id="list-template-checkbox"></div>
                    </div>
                </div>
            </div>

            <div class="text-end mt-3 pt-2 border-top">
                <button type="button" id="btnSimpanLab" class="btn btn-primary px-4">
                    <i class="fas fa-paper-plane me-1"></i> Kirim Permintaan Lab
                </button>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: RIWAYAT PERMINTAAN LAB HARI INI --}}
    <div class="card border shadow-none mb-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="fas fa-clock me-1 text-secondary"></i> Riwayat Permintaan Lab Hari Ini
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadLab(true)">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small">
                            <th style="width: 140px;">No. Order</th>
                            <th style="width: 130px;">Waktu Order</th>
                            <th>Pemeriksaan Diminta</th>
                            <th>Diagnosa Klinis</th>
                            <th style="width: 110px;">Status</th>
                            <th class="text-center" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayatOrder as $order)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $order->noorder }}</span></td>
                                <td class="small">{{ $order->tgl_permintaan }} <br><span class="text-muted">{{ $order->jam_permintaan }}</span></td>
                                <td>
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach($order->pemeriksaan as $p)
                                            <li><i class="fas fa-check-circle text-success me-1"></i> {{ $p->jenisPerawatan->nm_perawatan ?? $p->kd_jenis_prw }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="small">{{ $order->diagnosa_klinis ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $order->pemeriksaan->first() && $order->pemeriksaan->first()->stts == 'Sudah' ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ $order->pemeriksaan->first() ? $order->pemeriksaan->first()->stts : 'Belum' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if(!$order->pemeriksaan->first() || $order->pemeriksaan->first()->stts == 'Belum')
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-hapus-lab" data-noorder="{{ $order->noorder }}" title="Batalkan Permintaan">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @else
                                        <span class="text-muted small fst-italic">Diproses</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-3 text-muted small">
                                    Belum ada permintaan laboratorium yang dibuat hari ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- BAGIAN 3: HASIL PEMERIKSAAN LABORATORIUM RESMI (HASIL) --}}
    <div class="card border shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-success">
                <i class="fas fa-poll-h me-1"></i> Hasil Pemeriksaan Laboratorium Pasien (Terverifikasi)
            </h6>
            <span class="badge bg-success-soft text-success border border-success">
                <i class="fas fa-check-double me-1"></i> Terintegrasi SIMRS
            </span>
        </div>
        <div class="card-body p-3">
            @if(empty($hasilLab))
                <div class="text-center p-4 text-muted">
                    <i class="fas fa-file-medical-alt fa-3x mb-2 d-block text-secondary" style="opacity: 0.3;"></i>
                    Belum ada hasil pemeriksaan laboratorium yang terverifikasi untuk nomor rawat ini.
                </div>
            @else
                <div class="accordion" id="accordionHasilLab">
                    @php $accIdx = 0; @endphp
                    @foreach($hasilLab as $groupTitle => $groupData)
                        @php $accIdx++; @endphp
                        <div class="accordion-item mb-2 border">
                            <h2 class="accordion-header" id="headingLab{{ $accIdx }}">
                                <button class="accordion-button {{ $accIdx == 1 ? '' : 'collapsed' }} py-2 px-3 bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLab{{ $accIdx }}">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <span class="fw-bold text-primary">
                                            <i class="fas fa-vial me-2 text-danger"></i> {{ $groupData['nm_perawatan'] }}
                                        </span>
                                        <span class="badge bg-white text-muted border small">
                                            <i class="fas fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($groupData['tgl_periksa'])->translatedFormat('d F Y') }} - {{ $groupData['jam'] }}
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapseLab{{ $accIdx }}" class="accordion-collapse collapse {{ $accIdx == 1 ? 'show' : '' }}" data-bs-parent="#accordionHasilLab">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered table-striped align-middle mb-0">
                                            <thead class="table-light">
                                                <tr class="small text-muted">
                                                    <th>Item Pemeriksaan</th>
                                                    <th style="width: 160px;">Hasil Uji</th>
                                                    <th style="width: 100px;">Satuan</th>
                                                    <th style="width: 180px;">Nilai Rujukan Normal</th>
                                                    <th>Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($groupData['items'] as $item)
                                                    <tr>
                                                        <td class="fw-semibold small">{{ $item['pemeriksaan'] }}</td>
                                                        <td>
                                                            <span class="fw-bold text-dark">{{ $item['nilai'] }}</span>
                                                        </td>
                                                        <td class="small text-muted">{{ $item['satuan'] ?: '-' }}</td>
                                                        <td class="small">{{ $item['nilai_rujukan'] ?: '-' }}</td>
                                                        <td class="small text-muted">{{ $item['keterangan'] ?: '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
