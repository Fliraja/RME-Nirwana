<div class="row">
    {{-- Form Input SOAP Ranap --}}
    <div class="col-lg-12 mb-4">
        <div class="card border shadow-none" style="background-color: #fafbfc;">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-primary" id="titleFormSoap">
                    <i class="fas fa-plus-circle me-1"></i> Form Catatan Perkembangan Pasien Terintegrasi (CPPT / SOAP)
                </h6>
                <span class="badge bg-light text-muted border" id="badgeModeSoap">Mode: Tambah Baru</span>
            </div>
            <div class="card-body p-3">
                <form id="formSoapRanap">
                    @csrf
                    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat ?? '' }}">
                    <input type="hidden" name="mode_edit_soap" id="mode_edit_soap" value="0">
                    <input type="hidden" name="tgl_perawatan_edit_soap" id="tgl_perawatan_edit_soap" value="">
                    <input type="hidden" name="jam_rawat_edit_soap" id="jam_rawat_edit_soap" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">
                                <span class="badge bg-primary me-1">S</span> Subjektif (Keluhan Pasien / Anamnesis)
                            </label>
                            <textarea name="keluhan" id="soap_keluhan" class="form-control" rows="3" placeholder="Keluhan utama, riwayat perjalanan penyakit hari ini..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">
                                <span class="badge bg-success me-1">O</span> Objektif (Pemeriksaan Fisik & Penunjang)
                            </label>
                            <textarea name="pemeriksaan" id="soap_pemeriksaan" class="form-control" rows="3" placeholder="Hasil pemeriksaan fisik, status lokalis, penunjang..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">
                                <span class="badge bg-danger me-1">A</span> Asesmen (Penilaian Klinis / Diagnosa Kerja)
                            </label>
                            <textarea name="penilaian" id="soap_penilaian" class="form-control" rows="3" placeholder="Diagnosis kerja, diagnosis banding, perbaikan/perburukan..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">
                                <span class="badge bg-warning text-dark me-1">P</span> Plan (Rencana Terapi / RTL)
                            </label>
                            <textarea name="rtl" id="soap_rtl" class="form-control" rows="3" placeholder="Rencana pengobatan, tindakan, monitoring, discharge planning..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">
                                <i class="fas fa-clipboard-list me-1 text-info"></i> Instruksi Medis
                            </label>
                            <textarea name="instruksi" id="soap_instruksi" class="form-control" rows="2" placeholder="Instruksi khusus kepada perawat / tim medis..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">
                                <i class="fas fa-check-circle me-1 text-secondary"></i> Evaluasi Kondisi
                            </label>
                            <textarea name="evaluasi" id="soap_evaluasi" class="form-control" rows="2" placeholder="Evaluasi harian perkembangan terapi..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2 mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-secondary d-none" id="btnBatalEditSoap" onclick="resetFormSoap()">
                            <i class="fas fa-times me-1"></i> Batal Edit
                        </button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSimpanSoap">
                            <i class="fas fa-save me-1"></i> Simpan SOAP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat SOAP Ranap --}}
    <div class="col-lg-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-stream me-1 text-primary"></i> Riwayat Perkembangan Pasien (CPPT Ranap)
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadSoap(true)">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle border mb-0" id="tableRiwayatSoap">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px;">Waktu & Dokter</th>
                        <th>Subjektif (S)</th>
                        <th>Objektif (O)</th>
                        <th>Asesmen (A)</th>
                        <th>Plan (P)</th>
                        <th>Instruksi</th>
                        <th class="text-center" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riwayatSoap as $soap)
                        <tr id="row-soap-{{ str_replace('-', '', $soap->tgl_perawatan) }}-{{ str_replace(':', '', $soap->jam_rawat) }}">
                            <td class="small">
                                <span class="fw-bold text-dark d-block">
                                    {{ \Carbon\Carbon::parse($soap->tgl_perawatan)->translatedFormat('d/m/Y') }}
                                </span>
                                <span class="badge bg-light text-muted border mb-1">{{ $soap->jam_rawat }}</span>
                                <small class="text-muted d-block"><i class="fas fa-user-md me-1"></i>{{ $soap->nama_petugas ?? $soap->nip }}</small>
                            </td>
                            <td class="small">{{ $soap->keluhan ?: '-' }}</td>
                            <td class="small">{{ $soap->pemeriksaan ?: '-' }}</td>
                            <td class="small"><strong>{{ $soap->penilaian ?: '-' }}</strong></td>
                            <td class="small">{{ $soap->rtl ?: '-' }}</td>
                            <td class="small">{{ $soap->instruksi ?: '-' }}</td>
                            <td class="text-center">
                                @if($soap->nip == $currentUserNip || session('role') === 'admin')
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-warning" title="Edit Catatan" onclick='editSoap(@json($soap))'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" title="Hapus Catatan" onclick="hapusSoap('{{ $soap->tgl_perawatan }}', '{{ $soap->jam_rawat }}')">
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
                            <td colspan="7" class="text-center p-4 text-muted">
                                Belum ada catatan perkembangan SOAP untuk rawat inap ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
