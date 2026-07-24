@extends('layouts.app')

@section('title', 'Pemeriksaan')
@section('page-title', 'Rawat Jalan')

@section('content')
<div class="container-fluid">

    @if($action == 'view' && $detailPasien)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0">Detail Pasien</h4>
                    </div>
                    <div class="card-body">
                        <!-- Info Pasien -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-muted" style="width: 40%">Nama Lengkap</td>
                                            <td class="fw-semibold">{{ $detailPasien->pasien->nm_pasien }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">No. RM</td>
                                            <td class="fw-semibold">{{ $detailPasien->no_rkm_medis }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">No. Rawat</td>
                                            <td class="fw-semibold">{{ $detailPasien->no_rawat }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-muted" style="width: 40%">Umur</td>
                                            <td class="fw-semibold">{{ $detailPasien->pasien->umur }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Jenis Bayar</td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    {{ $detailPasien->penjab->png_jawab }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Status</td>
                                            <td>
                                                <span class="badge {{ $detailPasien->stts == 'Sudah' ? 'bg-success' : 'bg-warning' }}">
                                                    {{ $detailPasien->stts }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="bg-light rounded p-3">
                            <ul class="nav nav-tabs border-0 mb-3" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="riwayat-tab" data-bs-toggle="tab" href="#riwayat">
                                        <i class="fas fa-history me-1"></i> RIWAYAT
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="soap-tab" data-bs-toggle="tab" href="#pemeriksaan-soap">
                                        <i class="fas fa-notes-medical me-1"></i> SOAP
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="diagnosa-prosedur-tab" data-bs-toggle="tab" href="#diagnosa-prosedur">
                                        <i class="fas fa-stethoscope me-1"></i> DIAGNOSA & PROSEDUR
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="vital-sign-tab" data-bs-toggle="tab" href="#pemeriksaan-vital-sign">
                                        <i class="fas fa-heartbeat me-1"></i> VITAL-SIGN
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="permintaan-lab-tab" data-bs-toggle="tab" href="#permintaan-lab">
                                        <i class="fas fa-flask me-1"></i> PERMINTAAN LAB
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="permintaan-radiologi-tab" data-bs-toggle="tab" href="#permintaan-radiologi">
                                        <i class="fas fa-x-ray me-1"></i> PERMINTAAN RAD
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="resep-tab" data-bs-toggle="tab" href="#resep">
                                        <i class="fas fa-prescription me-1"></i> RESEP
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content bg-white rounded p-3" id="myTabContent">
                                <div class="tab-pane fade show active" id="riwayat" role="tabpanel">
                                    @if(isset($riwayat))
                                        @include('ralan.riwayat', ['riwayat' => $riwayat])
                                    @else
                                        <p class="text-center mt-3">Data riwayat tidak ditemukan.</p>
                                    @endif
                                </div>

                                <div role="tabpanel" class="tab-pane fade" id="pemeriksaan-soap">
                                    <div id="content-soap">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary"></div>
                                            <p>Memuat Form SOAP...</p>
                                        </div>
                                    </div>
                                </div>

                                <div role="tabpanel" class="tab-pane fade" id="diagnosa-prosedur">
                                    <div id="content-diagnosa-prosedur">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary"></div>
                                            <p>Memuat Form Diagnosa & Prosedur...</p>
                                        </div>
                                    </div>
                                </div>

                                <div role="tabpanel" class="tab-pane fade" id="pemeriksaan-vital-sign">
                                    <div id="content-vital-sign">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary"></div>
                                            <p>Memuat Form Vital Sign...</p>
                                        </div>
                                    </div>
                                </div>

                                <div role="tabpanel" class="tab-pane fade" id="permintaan-lab">
                                    <div id="content-lab">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary"></div>
                                            <p>Memuat Form Permintaan Lab...</p>
                                        </div>
                                    </div>
                                </div>

                                <div role="tabpanel" class="tab-pane fade" id="permintaan-radiologi">
                                    <div id="content-radiologi">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary"></div>
                                            <p>Memuat Form Permintaan Radiologi...</p>
                                        </div>
                                    </div>
                                </div>

                                <div role="tabpanel" class="tab-pane fade" id="resep">
                                    <div id="content-resep">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary"></div>
                                            <p>Memuat Form Peresepan...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex gap-2">
                            <a href="{{ route('ralan.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(!$action)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="card-title">Pasien {{ $nama_dokter }}</h4>
                        <p class="text-muted mb-0">Tanggal : <strong>{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</strong></p>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST" action="{{ route('ralan.index') }}" class="mb-4">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label">Pilih Tanggal</label>
                                    <input type="date" class="form-control" name="tanggal" value="{{ $tanggal }}">
                                </div>

                                @if(session('role') === 'admin')
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label">Pilih Dokter</label>
                                    <select class="form-select select2" name="kd_dokter">
                                        <option value="">-- Semua Dokter --</option>
                                        @foreach($listDokter as $dr)
                                            <option value="{{ $dr->kd_dokter }}" {{ $selected_dokter == $dr->kd_dokter ? 'selected' : '' }}>
                                                {{ $dr->nm_dokter }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <div class="col-md-2 col-sm-12 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table id="datatable_ralan" class="table table-bordered table-striped table-hover nowrap w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Pasien</th>
                                        <th>Poli Tujuan</th>
                                        <th>No. Antrian</th>
                                        <th>Status</th>
                                        <th>Jenis Bayar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($daftarPasien as $pasien)
                                        <tr>
                                            <td>
                                                <a href="{{ route('ralan.index', ['action' => 'view', 'no_rawat' => $pasien->no_rawat]) }}" class="text-primary fw-bold text-decoration-none">
                                                    {{ Str::limit(strtoupper($pasien->pasien->nm_pasien), 20) }}
                                                </a>
                                                <div class="small text-muted">{{ $pasien->no_rawat }}</div>
                                            </td>
                                            <td>
                                                {{ $pasien->poliklinik->nm_poli }}
                                            @if(session('role') === 'admin')
                                                   <br> <small class="text-muted">{{ $pasien->dokter->nm_dokter }}</small>
                                            @endif
                                            </td>
                                            <td><span class="badge bg-info text-dark">{{ $pasien->no_reg }}</span></td>
                                            <td>
                                                <span class="badge {{ $pasien->stts == 'Sudah' ? 'bg-success' : 'bg-warning' }}">
                                                    {{ $pasien->stts }}
                                                </span>
                                            </td>
                                            <td>{{ $pasien->penjab->png_jawab }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Tidak ada data pasien untuk tanggal ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
@push('styles')
<style>
.nav-tabs .nav-link {
    border: none;
    background: transparent;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.25rem;
    transition: all 0.3s;
}
 
.nav-tabs .nav-link:hover {
    color: #0d6efd;
    background: rgba(13, 110, 253, 0.08);
    border-radius: 0.375rem 0.375rem 0 0;
}

.nav-tabs .nav-link.active {
    background: white;
    color: #0d6efd;
    border-radius: 0.375rem 0.375rem 0 0;
    box-shadow: 0 -2px 4px rgba(0,0,0,0.05);
} 

.select2-container--default .select2-selection--single {
    height: 38px !important;
    padding: 5px 12px;
    border: 1px solid #dee2e6 !important;
    border-radius: 0.375rem !important; 
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #86b7fe !important;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #212529 !important;
    line-height: 26px !important;
    padding-left: 0 !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
    right: 10px !important;
}

.select2-container--default .select2-selection--multiple {
    border: 1px solid #ced4da !important;
    border-radius: 0.375rem !important;
    min-height: 38px !important;
    padding: 5px 12px !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #02a410 !important;
    border: none !important;
    color: white !important;
    border-radius: 0.25rem !important;
    padding: 0.25rem 0.75rem !important;
    margin: 0.25rem 0.25rem 0.25rem 0 !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: white !important;
    margin-right: 0.5rem !important;
    font-weight: bold !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #fff !important;
    background-color: transparent !important;
}

.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #86b7fe !important;
    outline: 0 !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}

.select2-container--default .select2-search--inline .select2-search__field {
    margin-top: 0 !important;
    padding: 0.25rem !important;
}

.select2-dropdown {
    border: 1px solid #86b7fe !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    z-index: 9999;
}

.select2-search--dropdown .select2-search__field {
    padding: 8px !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 4px !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #22d5de !important;
    color: white !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #e7f1ff !important;
    color: #22d5de !important;
}

.select2-results__option {
    padding: 8px 12px !important;
}

.checkbox-controls {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e9ecef;
}

.checkbox-controls .btn-check-control {
    font-size: 0.8rem;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    transition: all 0.2s;
}

.checkbox-controls .btn-check-all {
    background-color: #198754;
    color: white;
    border: none;
}

.checkbox-controls .btn-check-all:hover {
    background-color: #157347;
}

.checkbox-controls .btn-uncheck-all {
    background-color: #dc3545;
    color: white;
    border: none;
}

.checkbox-controls .btn-uncheck-all:hover {
    background-color: #bb2d3b;
}

.checkbox-controls .badge-count {
    background-color: #6c757d;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.group-template {
    
    border-radius: 0.5rem;
    padding: 0.95rem;
    margin-bottom: 1rem;
}

.group-template .group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #0d6efd;
}

.group-template .group-title {
    font-weight: 600;
    color: #0d6efd;
    font-size: 0.9rem;
}

.form-check {
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.form-check:hover {
    background-color: #f8f9fa;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.form-check-label {
    cursor: pointer;
    user-select: none;
}

#pills-tab-resep .nav-link {
    color: #495057;
    background-color: #e9ecef;
    margin-right: 5px;
}

#pills-tab-resep .nav-link.active {
    color: #fff;
    background-color: #198754; 
}

</style>
@endpush
@push('scripts')
<script>
    var currentNoRawat = "{{ $detailPasien->no_rawat ?? '' }}";
    var currentSafeNoRawat = currentNoRawat.replace(/\//g, '-');
    window.RALAN = {
        csrf: "{{ csrf_token() }}",
        routes: {
            searchIcd10: "{{ route('ralan.search-icd10') }}",
            searchIcd9: "{{ route('ralan.search-icd9') }}",
            searchObat: "{{ route('ralan.search-obat') }}",
            searchLab: "{{ route('ralan.search-lab') }}",
            searchRadiologi: "{{ route('ralan.search-radiologi') }}",
            storeResepObat: "{{ route('ralan.store-resep-obat') }}",
            storeResepRacikan: "{{ route('ralan.store-resep-racikan') }}",
            storeLab: "{{ route('ralan.store-lab') }}",
            storeRadiologi: "{{ route('ralan.store-radiologi') }}",
            storeDiagnosa: "{{ route('ralan.store-diagnosa') }}",
            storeProsedur: "{{ route('ralan.store-prosedur') }}",
            soapSimpan: "{{ route('ralan.soap.simpan') }}",
            vitalSimpan: "{{ route('ralan.store-vital') }}"
        }
    };
</script>
<script src="{{ asset('js/ralan/ralan-core.js') }}"></script>
@endpush
