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

                    <div class="row g-3 mb-3">
                        <div class="col-md-2 col-sm-4 col-6">
                            <label class="form-label small fw-semibold text-muted">Suhu Tubuh (°C)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="suhu_tubuh" id="ttv_suhu_tubuh" class="form-control" placeholder="36.5">
                                <span class="input-group-text">°C</span>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-4 col-6">
                            <label class="form-label small fw-semibold text-muted">Tensi (TD)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="tensi" id="ttv_tensi" class="form-control" placeholder="120/80">
                                <span class="input-group-text">mmHg</span>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-4 col-6">
                            <label class="form-label small fw-semibold text-muted">Nadi</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="nadi" id="ttv_nadi" class="form-control" placeholder="80">
                                <span class="input-group-text">x/m</span>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-4 col-6">
                            <label class="form-label small fw-semibold text-muted">Respirasi (RR)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="respirasi" id="ttv_respirasi" class="form-control" placeholder="20">
                                <span class="input-group-text">x/m</span>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-4 col-6">
                            <label class="form-label small fw-semibold text-muted">SpO2</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="spo2" id="ttv_spo2" class="form-control" placeholder="98">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-4 col-6">
                            <label class="form-label small fw-semibold text-muted">GCS (E,V,M)</label>
                            <input type="text" name="gcs" id="ttv_gcs" class="form-control form-control-sm" placeholder="15">
                        </div>

                        <div class="col-md-2 col-sm-4 col-6">
                            <label class="form-label small fw-semibold text-muted">Tinggi (TB)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="tinggi" id="ttv_tinggi" class="form-control" placeholder="165">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-4 col-6">
                            <label class="form-label small fw-semibold text-muted">Berat (BB)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="berat" id="ttv_berat" class="form-control" placeholder="60">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-8 col-12">
                            <label class="form-label small fw-semibold text-muted">Kesadaran</label>
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

                        <div class="col-md-4 col-sm-12">
                            <label class="form-label small fw-semibold text-muted">Riwayat Alergi</label>
                            <input type="text" name="alergi" id="ttv_alergi" class="form-control form-control-sm" placeholder="Alergi obat / makanan...">
                        </div>
                    </div>

                    {{-- Catatan SBAR Terintegrasi --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">
                                <i class="fas fa-comments me-1 text-primary"></i> Catatan Komunikasi SBAR (Situation, Background, Assessment, Recommendation)
                            </label>
                            <textarea name="sbar" id="ttv_sbar" class="form-control" rows="3" placeholder="S: Kondisi saat ini / keluhan utama...&#10;B: Riwayat klinis & terapi yang sudah masuk...&#10;A: Penilaian kondisi kritis atau stabil...&#10;R: Rekomendasi tindakan / instruksi dokter jaga..."></textarea>
                            <small class="text-muted fst-italic">*Catatan SBAR akan otomatis tersinkronisasi pada tabel catatan_sbar_ranap dengan timestamp yang sama.</small>
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

    {{-- Tabel Riwayat TTV & SBAR --}}
    <div class="col-lg-12">
        <div class="ralan-card">
            <div class="ralan-card-head">
                <span class="fw-bold">
                    <i class="fas fa-history me-2 text-primary"></i>Riwayat Observasi Tanda-Tanda Vital & SBAR
                </span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadVital(true)">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0" id="tableRiwayatTtv">
                    <thead class="table-light">
                        <tr class="small">
                            <th style="width: 130px;">Waktu</th>
                            <th>Tanda Vital</th>
                            <th>Status Fisik</th>
                            <th>Kesadaran & Alergi</th>
                            <th>Catatan SBAR</th>
                            <th>Petugas</th>
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayatTtv as $ttv)
                            <tr id="row-ttv-{{ str_replace('-', '', $ttv->tgl_perawatan) }}-{{ str_replace(':', '', $ttv->jam_rawat) }}">
                                <td class="small">
                                    <span class="fw-bold text-dark d-block">
                                        {{ \Carbon\Carbon::parse($ttv->tgl_perawatan)->translatedFormat('d/m/Y') }}
                                    </span>
                                    <span class="badge bg-light text-muted border">{{ $ttv->jam_rawat }}</span>
                                </td>
                                <td class="small">
                                    <div>Suhu: <strong>{{ $ttv->suhu_tubuh ?: '-' }} °C</strong></div>
                                    <div>TD: <strong>{{ $ttv->tensi ?: '-' }}</strong></div>
                                    <div>Nadi: <strong>{{ $ttv->nadi ?: '-' }} x/m</strong></div>
                                    <div>RR: <strong>{{ $ttv->respirasi ?: '-' }} x/m</strong></div>
                                    <div>SpO2: <strong>{{ $ttv->spo2 ?: '-' }}%</strong></div>
                                </td>
                                <td class="small">
                                    <div>TB: {{ $ttv->tinggi ?: '-' }} cm</div>
                                    <div>BB: {{ $ttv->berat ?: '-' }} kg</div>
                                    <div>GCS: {{ $ttv->gcs ?: '-' }}</div>
                                </td>
                                <td class="small">
                                    <span class="badge bg-light text-dark border mb-1 d-inline-block">
                                        {{ $ttv->kesadaran ?: 'Compos Mentis' }}
                                    </span>
                                    @if($ttv->alergi)
                                        <div class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> Alergi: {{ $ttv->alergi }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($ttv->sbar)
                                        <div class="p-2 rounded bg-light border" style="white-space: pre-line; max-width: 350px;">{{ $ttv->sbar }}</div>
                                    @else
                                        <span class="text-muted fst-italic">-</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <span class="text-muted">{{ $ttv->nama_petugas ?? $ttv->nip }}</span>
                                </td>
                                <td class="text-center">
                                    @if($ttv->nip == $currentUserNip || session('role') === 'admin')
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning" title="Edit TTV" onclick='editTtv(@json($ttv))'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" title="Hapus TTV" onclick="hapusTtv('{{ $ttv->tgl_perawatan }}', '{{ $ttv->jam_rawat }}')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-muted small fst-italic">Read-only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center p-4 text-muted small">
                                    Belum ada data observasi tanda vital untuk rawat inap ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
