{{-- File: resources/views/ralan/partials/tab-soap.blade.php --}}

<form action="{{ route('ralan.soap.simpan') }}" method="POST" id="formSoap">
    @csrf
    @php($batas = \App\Support\BatasIsianPemeriksaan::SOAP_RALAN)
    <input type="hidden" name="no_rawat" value="{{ $pasien->no_rawat }}">
    
    <div class="row mt-3">
        <div class="col-md-6 mb-3">
            <label class="fw-bold mb-2" for="ralan_soap_keluhan">Subjek (Keluhan)</label>
            <textarea name="keluhan" id="ralan_soap_keluhan" data-batas="{{ $batas['keluhan'] }}" class="form-control" rows="5">{{ $pasien->pemeriksaanRalan->keluhan ?? '' }}</textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="fw-bold mb-2" for="ralan_soap_objek">Objek (Pemeriksaan Fisik)</label>
            <textarea name="objek" id="ralan_soap_objek" data-batas="{{ $batas['objek'] }}" class="form-control" rows="5">{{ $pasien->pemeriksaanRalan->pemeriksaan ?? '' }}</textarea>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="fw-bold mb-2" for="ralan_soap_penilaian">Assesmen (Diagnosa)</label>
            <textarea name="penilaian" id="ralan_soap_penilaian" data-batas="{{ $batas['penilaian'] }}" class="form-control" rows="4">{{ $pasien->pemeriksaanRalan->penilaian ?? '' }}</textarea>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fw-bold mb-2" for="ralan_soap_plan">Plan (Terapi/Tindakan)</label>
            <textarea name="plan" id="ralan_soap_plan" data-batas="{{ $batas['plan'] }}" class="form-control" rows="4">{{ $pasien->pemeriksaanRalan->rtl ?? '' }}</textarea>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fw-bold mb-2" for="ralan_soap_instruksi">Instruksi / RTL</label>
            <textarea name="instruksi" id="ralan_soap_instruksi" data-batas="{{ $batas['instruksi'] }}" class="form-control" rows="4">{{ $pasien->pemeriksaanRalan->instruksi ?? '' }}</textarea>
        </div>
    </div>

    <button type="submit" class="btn btn-primary px-4 shadow-sm mb-4">
        <i class="fas fa-save me-2"></i> Simpan Data SOAP
    </button>
</form>

<hr>

<h5 class="mb-3">Data Terinput</h5>
<div id="ralan-soap-table">
    @include('ralan.partials.soap_table')
</div>