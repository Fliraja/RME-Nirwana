<div id="formPermintaanLab">
    @csrf
    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="fw-bold">Pilih Pemeriksaan Lab</label>
                <select name="kd_jenis_prw[]" id="select-lab" class="form-control" multiple="multiple" style="width: 100%"></select>
                <small class="text-muted italic">*Bisa pilih lebih dari satu pemeriksaan</small>
            </div>

            <div class="form-group mt-3">
                <label class="fw-bold">Diagnosa Klinis</label>
                <textarea name="diagnosa_klinis" class="form-control" rows="2" placeholder="Masukkan diagnosa klinis..."></textarea>
            </div>
            
            <div class="form-group mt-2">
                <label class="fw-bold">Informasi Tambahan</label>
                <textarea name="informasi_tambahan" class="form-control" rows="2" placeholder="Catatan untuk petugas lab..."></textarea>
            </div>
        </div>

        <div class="col-md-6">
            <div id="container-template-lab" class="card shadow-none border p-3" style="min-height: 200px; background-color: #f9f9f9;">
                <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-microscope"></i> Detail Item Pemeriksaan</h6>
                <div id="detail-pemeriksaan-placeholder" class="text-center text-muted mt-4">
                    <i class="fas fa-arrow-left fa-3x mb-3" style="opacity: 0.3;"></i>
                    <p>Pilih jenis pemeriksaan di samping untuk melihat detail item (jika ada).</p>
                </div>
                <div id="list-template-checkbox"></div>
            </div>
        </div>
    </div>

    <div class="text-right mt-3">
        <button type="button" id="btnSimpanLab" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Kirim Permintaan Lab
        </button>
    </div>

    <hr>

    <h6 class="fw-bold mt-4">Riwayat Permintaan Hari Ini</h6>
    <div id="tabel-riwayat-lab">
        @include('ralan.lab_table') 
    </div>
</div>