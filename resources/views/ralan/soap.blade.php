{{-- File: resources/views/ralan/partials/tab-soap.blade.php --}}

<form action="{{ route('ralan.soap.simpan') }}" method="POST" id="formSoap">
    @csrf
    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">
    
    <div class="row mt-3">
        <div class="col-md-6 mb-3">
            <label class="fw-bold mb-2">Subjek (Keluhan)</label>
            <textarea name="keluhan" class="form-control" rows="5">{{ $pasien->pemeriksaanRalan->keluhan ?? '' }}</textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="fw-bold mb-2">Objek (Pemeriksaan Fisik)</label>
            <textarea name="objek" class="form-control" rows="5">{{ $pasien->pemeriksaanRalan->pemeriksaan ?? '' }}</textarea>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="fw-bold mb-2">Assesmen (Diagnosa)</label>
            <textarea name="penilaian" class="form-control" rows="4">{{ $pasien->pemeriksaanRalan->penilaian ?? '' }}</textarea>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fw-bold mb-2">Plan (Terapi/Tindakan)</label>
            <textarea name="plan" class="form-control" rows="4">{{ $pasien->pemeriksaanRalan->rtl ?? '' }}</textarea>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fw-bold mb-2">Instruksi / RTL</label>
            <textarea name="instruksi" class="form-control" rows="4">{{ $pasien->pemeriksaanRalan->instruksi ?? '' }}</textarea>
        </div>
    </div>

    <button type="submit" class="btn btn-primary px-4 shadow-sm mb-4">
        <i class="fas fa-save me-2"></i> Simpan Data SOAP
    </button>
</form>

<hr>

<h5 class="mb-3">Data Terinput</h5>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Tgl/Jam Update</th>
                <th>Subjek</th>
                <th>Objek</th>
                <th>Assesmen</th>
                <th>Plan</th>
                <th>Instruksi</th>
            </tr>
        </thead>
        <tbody>
            @if($pasien->pemeriksaanRalan)
                <tr>
                    <td>{{ $pasien->pemeriksaanRalan->tgl_perawatan }} {{ $pasien->pemeriksaanRalan->jam_rawat }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->keluhan }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->pemeriksaan }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->penilaian }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->rtl }}</td>
                    <td>{{ $pasien->pemeriksaanRalan->instruksi }}</td>
                </tr>
            @else
                <tr><td colspan="4" class="text-center">Belum ada data SOAP.</td></tr>
            @endif
        </tbody>
    </table>
</div>