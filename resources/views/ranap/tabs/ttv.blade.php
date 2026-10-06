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
                            <tr id="row-ttv-{{ str_replace('-', '', $ttv->tgl_perawatan) }}-{{ str_replace(':', '', $ttv->jam_rawat) }}"
                                class="ttv-row-item"
                                data-ttv="{{ json_encode($ttv) }}"
                                title="Klik untuk menyalin data TTV ini ke form input">
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
