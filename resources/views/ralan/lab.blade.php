<div id="formPermintaanLab">
    @csrf
    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">

    @if($pesanKunci)
    @include('ralan.partials.kunci_ranap')
    @else
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="fw-bold">Pilih Pemeriksaan Lab</label>
                <select name="kd_jenis_prw[]" id="select-lab" class="form-select" multiple="multiple" style="width: 100%"></select>
                <small class="text-muted fst-italic">*Bisa pilih lebih dari satu pemeriksaan</small>
            </div>

            <div class="mb-3 mt-3">
                <label class="fw-bold">Diagnosa Klinis</label>
                <textarea name="diagnosa_klinis" class="form-control" rows="2" placeholder="Masukkan diagnosa klinis..."></textarea>
            </div>

            <div class="mb-3 mt-2">
                <label class="fw-bold">Informasi Tambahan</label>
                <textarea name="informasi_tambahan" class="form-control" rows="2" placeholder="Catatan untuk petugas lab..."></textarea>
            </div>
        </div>

        <div class="col-md-6">
            @include('partials.lab_subitem_wadah')
        </div>
    </div>

    <div class="text-end mt-3">
        <button type="button" id="btnSimpanLab" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Kirim Permintaan Lab
        </button>
    </div>
    @endif

    <hr>

    <h6 class="fw-bold mt-4">Permintaan Lab Kunjungan Ini</h6>
    <div id="tabel-riwayat-lab">
        @include('ralan.lab_table') 
    </div>
</div>