<div id="formPermintaanRad">
    @csrf
    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="fw-bold">Pilih Pemeriksaan Radiologi</label>
                <select name="kd_jenis_prw_rad[]" id="select-rad" class="form-control" multiple="multiple" style="width: 100%"></select>
                <small class="text-muted italic">*Bisa pilih lebih dari satu pemeriksaan</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label class="fw-bold">Diagnosa Klinis</label>
                <textarea name="diagnosa_klinis" class="form-control" rows="3" placeholder="Masukkan diagnosa klinis..."></textarea>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label class="fw-bold">Informasi Tambahan</label>
                <textarea name="informasi_tambahan" class="form-control" rows="3" placeholder="Catatan untuk petugas radiologi..."></textarea>
            </div>
        </div>
    </div>

    <div class="text-right mt-3">
        <button type="button" id="btnSimpanRad" class="btn btn-success text-white">
            <i class="fas fa-paper-plane"></i> Kirim Permintaan Radiologi
        </button>
    </div>

    <hr>

    <h6 class="fw-bold mt-4"><i class="fas fa-history"></i> Riwayat Permintaan Radiologi Hari Ini</h6>
    <div id="tabel-riwayat-rad">
        @include('ralan.radiologi_table') 
    </div>
</div>