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
    let rowCount = 0;
    
    function tampilkanError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: message
            });
        } else if (typeof swal !== 'undefined') {
            swal("Gagal!", message, "error");
        } else {
            alert(message);
        }
    }

    function tampilkanSukses(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: message,
                timer: 1500,
                showConfirmButton: false
            });
        } else if (typeof swal !== 'undefined') {
            swal("Berhasil!", message, "success");
        } else {
            alert(message);
        }
    }

    function loadResep() {
        console.log('Loading resep for:', currentNoRawat);
        
        if (currentNoRawat === "") {
            console.error('No rawat is empty');
            return;
        }
        
        $('#content-resep').html(
            '<div class="text-center p-5">' +
            '<div class="spinner-border text-primary"></div>' +
            '<p>Memuat data resep...</p>' +
            '</div>'
        );
        
        $.ajax({
            url: '/ralan/get-resep-pasien/' + currentSafeNoRawat,
            method: 'GET',
            success: function(data) {
                console.log('Resep loaded successfully');
                $('#content-resep').html(data);
                
                setTimeout(function() {
                    initSelect2();
                }, 150);
            },
            error: function(xhr) {
                console.error('Error loading resep:', xhr);
                $('#content-resep').html(
                    '<div class="alert alert-danger">' +
                    'Gagal memuat data resep. Silakan refresh halaman.' +
                    '</div>'
                );
            }
        });
    }

    function loadFormLab() {
        console.log('Loading form lab for:', currentNoRawat);
        
        if (currentNoRawat === "") {
            console.error('No rawat is empty');
            return;
        }
        
        $('#content-lab').html(
            '<div class="text-center p-5">' +
            '<div class="spinner-border text-primary"></div>' +
            '<p>Memuat Form Permintaan Lab...</p>' +
            '</div>'
        );
        
        $.ajax({
            url: '/ralan/get-lab-pasien/' + currentSafeNoRawat,
            method: 'GET',
            success: function(data) {
                console.log('Form lab loaded successfully');
                $('#content-lab').html(data);
                
                setTimeout(function() {
                    initSelect2Lab();
                }, 150);
            },
            error: function(xhr) {
                console.error('Error loading form lab:', xhr);
                $('#content-lab').html(
                    '<div class="alert alert-danger">' +
                    'Gagal memuat form permintaan lab. Silakan refresh halaman.' +
                    '</div>'
                );
            }
        });
    }

    function loadFormRadiologi() {
        console.log('Loading form radiologi for:', currentNoRawat);
        
        if (currentNoRawat === "") {
            console.error('No rawat is empty');
            return;
        }
        
        $('#content-radiologi').html(
            '<div class="text-center p-5">' +
            '<div class="spinner-border text-info"></div>' +
            '<p>Memuat Form Permintaan Radiologi...</p>' +
            '</div>'
        );
        
        $.ajax({
            url: '/ralan/get-radiologi-pasien/' + currentSafeNoRawat,
            method: 'GET',
            success: function(data) {
                console.log('Form radiologi loaded successfully');
                $('#content-radiologi').html(data);
                
                setTimeout(function() {
                    initSelect2Radiologi();
                }, 150);
            },
            error: function(xhr) {
                console.error('Error loading form radiologi:', xhr);
                $('#content-radiologi').html(
                    '<div class="alert alert-danger">' +
                    'Gagal memuat form permintaan radiologi. Silakan refresh halaman.' +
                    '</div>'
                );
            }
        });
    }

    function loadDiagnosaProsedur() {
        console.log('Loading diagnosa prosedur for:', currentNoRawat);
        if (currentNoRawat === "") return;
        
        $('#content-diagnosa-prosedur').html(
            '<div class="text-center p-5">' +
            '<div class="spinner-border text-primary"></div>' +
            '<p>Memuat Form Diagnosa & Prosedur...</p>' +
            '</div>'
        );
        
        $.ajax({
            url: '/ralan/get-diagnosa-prosedur/' + currentSafeNoRawat,
            method: 'GET',
            success: function(data) {
                console.log('Diagnosa Prosedur loaded successfully');
                $('#content-diagnosa-prosedur').html(data);
                setTimeout(function() {
                    initSelect2DiagnosaProsedur();
                }, 150);
            },
            error: function(xhr) {
                console.error('Error loading diagnosa prosedur:', xhr);
                $('#content-diagnosa-prosedur').html(
                    '<div class="alert alert-danger">Gagal memuat form Diagnosa & Prosedur.</div>'
                );
            }
        });
    }

    function initSelect2DiagnosaProsedur() {
        console.log('Initializing select2 diagnosa & prosedur...');
        if ($('#select-icd10').length > 0) {
            if ($('#select-icd10').hasClass('select2-hidden-accessible')) {
                $('#select-icd10').select2('destroy');
            }
            $('#select-icd10').select2({
                placeholder: 'Ketik Kode / Nama Penyakit (ICD-10)...',
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('ralan.search-icd10') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { search: params.term }; },
                    processResults: function(data) { return { results: data }; },
                    cache: true
                }
            });
        }

        if ($('#select-icd9').length > 0) {
            if ($('#select-icd9').hasClass('select2-hidden-accessible')) {
                $('#select-icd9').select2('destroy');
            }
            $('#select-icd9').select2({
                placeholder: 'Ketik Kode / Deskripsi Prosedur (ICD-9)...',
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('ralan.search-icd9') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { search: params.term }; },
                    processResults: function(data) { return { results: data }; },
                    cache: true
                }
            });
        }
    }

    function initSelect2() {
        console.log('Initializing select2...');
        
        if ($('.kd_obat_ajax').length > 0 && $('.kd_obat_ajax').hasClass('select2-hidden-accessible')) {
            $('.kd_obat_ajax').select2('destroy');
        }
        
        if ($('.select2-aturan').length > 0 && $('.select2-aturan').hasClass('select2-hidden-accessible')) {
            $('.select2-aturan').select2('destroy');
        }

        if ($('.select2-aturan-racik').length > 0 && $('.select2-aturan-racik').hasClass('select2-hidden-accessible')) {
            $('.select2-aturan-racik').select2('destroy');
        }

        if ($('.kd_obat_ajax').length > 0) {
            $('.kd_obat_ajax').select2({
                placeholder: 'Ketik Nama Obat / Kode Obat...',
                minimumInputLength: 3,
                ajax: {
                    url: "{{ route('ralan.search-obat') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { search: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                }
            });
        }

        if ($('.select2-aturan').length > 0) {
            $('.select2-aturan').select2({
                width: '100%',
                placeholder: 'Pilih Aturan Pakai...',
                allowClear: true
            });
        }

        if ($('.select2-aturan-racik').length > 0) {
            $('.select2-aturan-racik').select2({
                width: '100%',
                placeholder: 'Pilih Aturan Pakai...',
                allowClear: true
            });
        }
        
        console.log('Select2 initialized');
    }

    function initSelect2Lab() {
        console.log('Initializing select2 lab...');
        
        if ($('#select-lab').length > 0) {
            if ($('#select-lab').hasClass('select2-hidden-accessible')) {
                $('#select-lab').select2('destroy');
            }
            
            $('#select-lab').select2({
                placeholder: 'Cari Pemeriksaan Lab...',
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('ralan.search-lab') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { 
                            search: params.term,
                            no_rawat: currentNoRawat
                        };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                }
            });
            
            console.log('Select2 lab initialized');
        }
    }

    function initSelect2Radiologi() {
        console.log('Initializing select2 radiologi...');
        
        if ($('#select-rad').length > 0) {
            if ($('#select-rad').hasClass('select2-hidden-accessible')) {
                $('#select-rad').select2('destroy');
            }
            
            $('#select-rad').select2({
                placeholder: 'Cari Pemeriksaan Radiologi...',
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('ralan.search-radiologi') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { 
                            search: params.term,
                            no_rawat: currentNoRawat
                        };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                }
            });
            
            console.log('Select2 radiologi initialized');
        }
    }

    function updateCheckboxCounter(groupElement) {
        let total = groupElement.find('.form-check-input').length;
        let checked = groupElement.find('.form-check-input:checked').length;
        let counter = groupElement.find('.badge-count');
        
        counter.html(`<i class="fas fa-check-circle"></i> ${checked} / ${total} dipilih`);
        
        if (checked === 0) {
            counter.removeClass('bg-success bg-warning').addClass('bg-secondary');
        } else if (checked === total) {
            counter.removeClass('bg-secondary bg-warning').addClass('bg-success');
        } else {
            counter.removeClass('bg-secondary bg-success').addClass('bg-warning');
        }
    }

    function hapusObat(no_resep, kode_brng) {
        console.log('Hapus obat called:', no_resep, kode_brng);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus obat ini?',
                text: "Data akan dihapus secara permanen",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    prosesHapusObat(no_resep, kode_brng);
                }
            });
        } else if (typeof swal !== 'undefined') {
            swal({
                title: "Hapus obat ini?",
                text: "Data akan dihapus secara permanen",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    prosesHapusObat(no_resep, kode_brng);
                }
            });
        } else {
            if (confirm('Hapus obat ini?')) {
                prosesHapusObat(no_resep, kode_brng);
            }
        }
    }

    function prosesHapusObat(no_resep, kode_brng) {
        $.ajax({
            url: '/ralan/delete-resep-obat/' + no_resep + '/' + kode_brng,
            method: 'POST',
            data: { 
                _token: "{{ csrf_token() }}",
                _method: 'DELETE'
            },
            success: function(response) {
                console.log('Delete response:', response);
                
                var isSuccess = false;
                if (response.status === 'success' || 
                    response.status === 'success-obat' || 
                    response.success === true ||
                    response.message === 'Item resep berhasil dihapus') {
                    isSuccess = true;
                }
                
                if(isSuccess) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: response.message || 'Data berhasil dihapus',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function() {
                            loadResep();
                        });
                    } else if (typeof swal !== 'undefined') {
                        swal("Terhapus!", response.message || 'Data berhasil dihapus', "success");
                        setTimeout(function() {
                            loadResep();
                        }, 1000);
                    } else {
                        alert('Data berhasil dihapus');
                        loadResep();
                    }
                } else {
                    tampilkanError(response.message || "Gagal menghapus obat");
                }
            },
            error: function(xhr) {
                console.error('Delete error:', xhr);
                var errorMsg = "Gagal menghapus obat";
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMsg = "Data tidak ditemukan";
                } else if (xhr.status === 500) {
                    errorMsg = "Terjadi kesalahan server";
                }
                
                tampilkanError(errorMsg);
            }
        });
    }

    function hapusRacikan(no_resep, no_racik) {
        console.log('Hapus Racikan called:', no_resep, no_racik);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Racikan ini?',
                text: "Data akan dihapus secara permanen",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    prosesHapusRacikan(no_resep, no_racik);
                }
            });
        } else if (typeof swal !== 'undefined') {
            swal({
                title: "Hapus Racikan ini?",
                text: "Data akan dihapus secara permanen",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    prosesHapusRacikan(no_resep, no_racik);
                }
            });
        } else {
            if (confirm('Hapus racikan ini?')) {
                prosesHapusRacikan(no_resep, no_racik);
            }
        }
    }

    function prosesHapusRacikan(no_resep, no_racik) {
        $.ajax({
            url: '/ralan/delete-resep-racikan/' + no_resep + '/' + no_racik,
            method: 'POST',
            data: { 
                _token: "{{ csrf_token() }}",
                _method: 'DELETE'
            },
            success: function(response) {
                console.log('Delete response:', response);
                
                var isSuccess = false;
                if (response.status === 'success' || 
                    response.status === 'success-hapus-racikan' || 
                    response.success === true ||
                    response.message === 'Item resep berhasil dihapus') {
                    isSuccess = true;
                }
                
                if(isSuccess) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: response.message || 'Data berhasil dihapus',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function() {
                            loadResep();
                        });
                    } else if (typeof swal !== 'undefined') {
                        swal("Terhapus!", response.message || 'Data berhasil dihapus', "success");
                        setTimeout(function() {
                            loadResep();
                        }, 1000);
                    } else {
                        alert('Data berhasil dihapus');
                        loadResep();
                    }
                } else {
                    tampilkanError(response.message || "Gagal menghapus obat");
                }
            },
            error: function(xhr) {
                console.error('Delete error:', xhr);
                var errorMsg = "Gagal menghapus obat";
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMsg = "Data tidak ditemukan";
                } else if (xhr.status === 500) {
                    errorMsg = "Terjadi kesalahan server";
                }
                
                tampilkanError(errorMsg);
            }
        });
    }

    function hapusLab(noorder, kd_jenis_prw = null, id_template = null) {
        let title = 'Hapus Order?';
        let text = "Seluruh pemeriksaan dalam nomor order ini akan dihapus.";
        let url = `/ralan/delete-lab/${noorder}`;

        if (kd_jenis_prw && !id_template) {
            title = 'Hapus Pemeriksaan?';
            text = "Jenis pemeriksaan ini dan detailnya akan dihapus.";
            url += `/${kd_jenis_prw}`;
        } else if (id_template) {
            title = 'Hapus Item?';
            text = "Hanya item pemeriksaan ini yang akan dihapus.";
            url += `/${kd_jenis_prw}/${id_template}`;
        }

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: { _token: "{{ csrf_token() }}", _method: 'DELETE' },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadFormLab(); 
                    },
                    error: function(xhr) {
                        tampilkanError(xhr.responseJSON?.message || "Gagal menghapus.");
                    }
                });
            }
        });
    }

    function hapusRadiologi(noorder, kd_jenis_prw = null) {
        let title = 'Hapus Order?';
        let text = "Seluruh pemeriksaan dalam nomor order ini akan dihapus.";
        let url = `/ralan/delete-radiologi/${noorder}`;

        if (kd_jenis_prw) {
            title = 'Hapus Pemeriksaan?';
            text = "Jenis pemeriksaan ini akan dihapus dari order.";
            url += `/${kd_jenis_prw}`;
        }

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: { 
                        _token: "{{ csrf_token() }}", 
                        _method: 'DELETE' 
                    },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadFormRadiologi(); 
                    },
                    error: function(xhr) {
                        tampilkanError(xhr.responseJSON?.message || "Gagal menghapus.");
                    }
                });
            }
        });
    }

    function resetFormUmum() {
        var form = $('#formResepObat');
        form.find('select[name="kode_obat"]').val(null).trigger('change');
        form.find('input[name="jumlah"]').val('10');
        form.find('select[name="aturan_pakai"]').val(null).trigger('change');
        $('#aturanManualUmum').addClass('d-none');
        form.find('input[name="aturan_pakai_lainnya"]').val('');
    }

    function resetFormRacikan() {
        var form = $('#formResepRacikan');
        form.find('input[name="nama_racik"]').val('');
        form.find('select[name="kd_racik"]').prop('selectedIndex', 0);
        form.find('input[name="jml_dr"]').val('10');
        form.find('select[name="aturan_racik"]').val(null).trigger('change');
        $('#aturanManualRacik').addClass('d-none');
        form.find('input[name="aturan_racik_lainnya"]').val('');
        
        $('#tableKomposisi tbody').html(
            '<tr class="text-center text-muted">' +
            '<td colspan="6" class="py-3">' +
            '<i class="fas fa-info-circle me-1"></i> ' +
            'Belum ada komposisi obat. Klik tombol "Tambah Baris" untuk menambahkan.' +
            '</td>' +
            '</tr>'
        );
        
        rowCount = 0;
    }

    function hitungJmlBaris(tr) {
        let p1 = parseFloat(tr.find('.p1').val()) || 0;
        let p2 = parseFloat(tr.find('.p2').val()) || 1;
        let jml_dr = parseFloat($('#jml_dr').val()) || 0;
        let hasil = (p1 / p2) * jml_dr;
        tr.find('.jml-hitung').val(hasil.toFixed(2));
    }

    function hapusBaris(rowId) {
        console.log('Hapus baris:', rowId);
        $('#row_' + rowId).remove();
        
        if ($('#tableKomposisi tbody tr').length === 0) {
            $('#tableKomposisi tbody').html(
                '<tr class="text-center text-muted">' +
                '<td colspan="6" class="py-3">' +
                '<i class="fas fa-info-circle me-1"></i> ' +
                'Belum ada komposisi obat. Klik tombol "Tambah Baris" untuk menambahkan.' +
                '</td>' +
                '</tr>'
            );
        }
    }

    $('a[href="#pemeriksaan-soap"]').on('shown.bs.tab', function (e) {
        if (currentNoRawat !== "") {
            $.get('/ralan/soap/' + currentSafeNoRawat, function(data) {
                $('#content-soap').html(data);
            });
        }
    });

    $('a[href="#diagnosa-prosedur"]').on('shown.bs.tab', function (e) {
        console.log('Diagnosa Prosedur tab shown');
        loadDiagnosaProsedur();
    });

    $('a[href="#pemeriksaan-vital-sign"]').on('shown.bs.tab', function (e) {
        if (currentNoRawat !== "") {
            $.get('/ralan/get-vital-pasien/' + currentSafeNoRawat, function(data) {
                $('#content-vital-sign').html(data);
            });
        }
    });

    $('a[href="#resep"]').on('shown.bs.tab', function (e) {
        console.log('Resep tab shown');
        loadResep();
    });

    $('a[href="#permintaan-lab"]').on('shown.bs.tab', function (e) {
        console.log('Lab tab shown');
        loadFormLab();
    });

    $('a[href="#permintaan-radiologi"]').on('shown.bs.tab', function (e) {
        console.log('Radiologi tab shown');
        loadFormRadiologi();
    });

    $(document).ready(function() {
        console.log('=== RESEP MODULE LOADED ===');
        console.log('Current no_rawat:', currentNoRawat);
        console.log('SweetAlert2:', typeof Swal !== 'undefined');
        console.log('SweetAlert1:', typeof swal !== 'undefined');

        $(document).on('click', '#btnTambahObat', function(e) {
            e.preventDefault();
            console.log('=== TAMBAH OBAT UMUM ===');
            
            var form = $('#formResepObat');
            var kodeObat = form.find('select[name="kode_obat"]').val();
            var jumlah = form.find('input[name="jumlah"]').val();
            var aturanPakaiSelect = form.find('select[name="aturan_pakai"]').val();
            var aturanPakaiManual = form.find('input[name="aturan_pakai_lainnya"]').val();
            
            if (!kodeObat) {
                tampilkanError("Silakan pilih obat terlebih dahulu");
                return false;
            }

            if (!jumlah || jumlah <= 0) {
                tampilkanError("Jumlah obat harus lebih dari 0");
                return false;
            }

            var aturanPakai = aturanPakaiSelect === 'lainnya' ? aturanPakaiManual : aturanPakaiSelect;
            
            if (!aturanPakai) {
                tampilkanError("Silakan isi aturan pakai");
                return false;
            }
            
            var formData = {
                _token: $('meta[name="csrf-token"]').attr('content') || form.find('input[name="_token"]').val(),
                no_rawat: form.find('input[name="no_rawat"]').val(),
                kode_obat: kodeObat,
                jumlah: jumlah,
                aturan_pakai: aturanPakai
            };
            
            var btn = $(this);
            var originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
            
            $.ajax({
                url: "{{ route('ralan.store-resep-obat') }}",
                method: "POST",
                data: formData,
                success: function(response) {
                    console.log('Success:', response);
                    btn.prop('disabled', false).html(originalText);
                    
                    if(response.status === 'success' || response.status === 'success-obat') {
                        tampilkanSukses(response.message);
                        resetFormUmum();
                        setTimeout(function() {
                            loadResep();
                        }, 1000);
                    } else {
                        tampilkanError(response.message || "Gagal menyimpan resep");
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    btn.prop('disabled', false).html(originalText);
                    
                    var errorMsg = "Gagal menyimpan resep";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        errorMsg = "Data yang diinput tidak valid";
                    } else if (xhr.status === 500) {
                        errorMsg = "Terjadi kesalahan server";
                    }
                    
                    tampilkanError(errorMsg);
                }
            });
        });

        $(document).on('change', '.select2-aturan', function() {
            console.log('Aturan pakai umum changed:', $(this).val());
            
            if ($(this).val() == 'lainnya') {
                $('#aturanManualUmum').removeClass('d-none');
                $('#aturanManualUmum input').focus();
            } else {
                $('#aturanManualUmum').addClass('d-none');
                $('#aturanManualUmum input').val('');
            }
        });

        $(document).on('change', '.select2-aturan-racik', function() {
            console.log('Aturan pakai racikan changed:', $(this).val());
            
            if ($(this).val() == 'lainnya') {
                $('#aturanManualRacik').removeClass('d-none');
                $('#aturanManualRacik input').focus();
            } else {
                $('#aturanManualRacik').addClass('d-none');
                $('#aturanManualRacik input').val('');
            }
        });

        $(document).on('click', '#btnTambahBarisObat', function() {
            console.log('=== TAMBAH BARIS OBAT RACIKAN ===');
            
            if ($('#tableKomposisi tbody tr td[colspan="6"]').length > 0) {
                $('#tableKomposisi tbody').html('');
            }
            
            rowCount++;
            console.log('Row count:', rowCount);
            
            let row = `
                <tr id="row_${rowCount}">
                    <td>
                        <select name="detail_obat[${rowCount}][kode_brng]" class="form-control form-control-sm kd_obat_racik_${rowCount}" style="width:100%"></select>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="detail_obat[${rowCount}][p1]" class="form-control form-control-sm p1" value="1" min="0">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="detail_obat[${rowCount}][p2]" class="form-control form-control-sm p2" value="1" min="1">
                    </td>
                    <td>
                        <input type="text" name="detail_obat[${rowCount}][kandungan]" class="form-control form-control-sm" placeholder="mg/ml">
                    </td>
                    <td>
                        <input type="text" name="detail_obat[${rowCount}][jml]" class="form-control form-control-sm jml-hitung" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(${rowCount})">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $('#tableKomposisi tbody').append(row);
            
            setTimeout(function() {
                $('.kd_obat_racik_' + rowCount).select2({
                    placeholder: 'Ketik Nama Obat...',
                    minimumInputLength: 3,
                    ajax: {
                        url: "{{ route('ralan.search-obat') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { search: params.term };
                        },
                        processResults: function(data) {
                            return { results: data };
                        },
                        cache: true
                    }
                });
                
                hitungJmlBaris($('#row_' + rowCount));
            }, 100);
        });

        $(document).on('input', '.p1, .p2, #jml_dr', function() {
            let tr = $(this).closest('tr');
            
            if($(this).attr('id') === 'jml_dr') {
                $('#tableKomposisi tbody tr').each(function() {
                    if (!$(this).find('td[colspan]').length) {
                        hitungJmlBaris($(this));
                    }
                });
            } else {
                hitungJmlBaris(tr);
            }
        });

        $(document).on('click', '#btnSimpanRacikan', function(e) {
            e.preventDefault();
            console.log('=== SIMPAN RACIKAN ===');
            
            var form = $('#formResepRacikan');
            var namaRacik = form.find('input[name="nama_racik"]').val();
            var jmlDr = form.find('input[name="jml_dr"]').val();
            var aturanSelect = form.find('select[name="aturan_racik"]').val();
            var aturanManual = form.find('input[name="aturan_racik_lainnya"]').val();
            
            if (!namaRacik) {
                tampilkanError("Nama racikan harus diisi");
                return false;
            }
            
            if (!jmlDr || jmlDr <= 0) {
                tampilkanError("Jumlah racik harus lebih dari 0");
                return false;
            }
            
            var aturanRacik = aturanSelect === 'lainnya' ? aturanManual : aturanSelect;
            if (!aturanRacik) {
                tampilkanError("Aturan pakai harus diisi");
                return false;
            }
            
            var jumlahBaris = $('#tableKomposisi tbody tr:not(:has(td[colspan]))').length;
            if (jumlahBaris === 0) {
                tampilkanError("Tambahkan minimal 1 obat untuk komposisi racikan");
                return false;
            }
            
            var formData = form.find('input, select').serialize();
            
            var btn = $(this);
            var originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
            
            $.ajax({
                url: "{{ route('ralan.store-resep-racikan') }}",
                method: "POST",
                data: formData,
                success: function(response) {
                    console.log('Success:', response);
                    btn.prop('disabled', false).html(originalText);
                    
                    if(response.status === 'success' || response.status === 'success-racik') {
                        tampilkanSukses(response.message || 'Resep racikan berhasil disimpan');
                        setTimeout(function() {
                            resetFormRacikan();
                            loadResep();
                        }, 2000);
                    } else {
                        tampilkanError(response.message || "Gagal menyimpan resep racikan");
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    btn.prop('disabled', false).html(originalText);
                    
                    var errorMsg = "Gagal menyimpan resep racikan";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        errorMsg = "Data yang diinput tidak valid";
                    } else if (xhr.status === 500) {
                        errorMsg = "Terjadi kesalahan server";
                    }
                    
                    tampilkanError(errorMsg);
                }
            });
        });

        $(document).on('change', '#select-lab', function() {
            console.log('Select lab changed:', $(this).val());
            
            let selectedValues = $(this).val(); 
            let container = $('#list-template-checkbox');
            let placeholder = $('#detail-pemeriksaan-placeholder');

            if (!selectedValues || selectedValues.length === 0) {
                container.empty();
                placeholder.show();
                return;
            }

            placeholder.hide();
            
            container.find('.group-template').each(function() {
                let kd = $(this).data('kd');
                if (!selectedValues.includes(kd)) {
                    $(this).remove();
                }
            });

            selectedValues.forEach(function(kd) {
                if (container.find(`.group-template[data-kd="${kd}"]`).length === 0) {
                    $.get(`/ralan/get-templates-lab/${kd}`, function(data) {
                        if (data.length > 0) {
                            let html = `<div class="group-template mb-3" data-kd="${kd}">
                                <div class="group-header">
                                    <div class="group-title">
                                        <i class="fas fa-vial"></i> ${kd}
                                    </div>
                                    <div class="badge-count bg-secondary">
                                        <i class="fas fa-check-circle"></i> 0 / ${data.length} dipilih
                                    </div>
                                </div>
                                <div class="checkbox-controls">
                                    <button type="button" class="btn btn-check-all btn-check-control btn-pilih-semua" data-kd="${kd}">
                                        <i class="fas fa-check-double"></i> Pilih Semua
                                    </button>
                                    <button type="button" class="btn btn-uncheck-all btn-check-control btn-batalkan-semua" data-kd="${kd}">
                                        <i class="fas fa-times"></i> Batalkan Semua
                                    </button>
                                </div>
                                <div class="checkbox-list">`;
                            
                            data.forEach(function(item) {
                                html += `
                                    <div class="form-check small">
                                        <input class="form-check-input" type="checkbox" 
                                            name="detail_lab[${kd}][]" 
                                            value="${item.id_template}" 
                                            id="chk_${item.id_template}"
                                            data-kd="${kd}">
                                        <label class="form-check-label" for="chk_${item.id_template}">
                                            ${item.Pemeriksaan}
                                        </label>
                                    </div>`;
                            });
                            
                            html += `</div>`;
                            container.append(html);
                        }
                    });
                }
            });
        });

        $(document).on('change', '.group-template .form-check-input', function() {
            let groupElement = $(this).closest('.group-template');
            updateCheckboxCounter(groupElement);
        });

        $(document).on('click', '.btn-pilih-semua', function() {
            let kd = $(this).data('kd');
            let groupElement = $(this).closest('.group-template');
            
            groupElement.find(`.form-check-input[data-kd="${kd}"]`).prop('checked', true);
            updateCheckboxCounter(groupElement);
            
            $(this).html('<i class="fas fa-check"></i> Terpilih!');
            setTimeout(() => {
                $(this).html('<i class="fas fa-check-double"></i> Pilih Semua');
            }, 1000);
        });

        $(document).on('click', '.btn-batalkan-semua', function() {
            let kd = $(this).data('kd');
            let groupElement = $(this).closest('.group-template');
            
            groupElement.find(`.form-check-input[data-kd="${kd}"]`).prop('checked', false);
            updateCheckboxCounter(groupElement);
            
            $(this).html('<i class="fas fa-check"></i> Dibatalkan!');
            setTimeout(() => {
                $(this).html('<i class="fas fa-times"></i> Batalkan Semua');
            }, 1000);
        });

        $(document).on('click', '#btnSimpanLab', function(e) {
            e.preventDefault();
            console.log('=== SIMPAN LAB CLICKED ===');
            
            let btn = $(this);
            let formContainer = $('#formPermintaanLab');
            
            let data = {
                _token: $('meta[name="csrf-token"]').attr('content') || "{{ csrf_token() }}",
                no_rawat: formContainer.find('input[name="no_rawat"]').val(),
                kd_jenis_prw: $('#select-lab').val(),
                diagnosa_klinis: formContainer.find('textarea[name="diagnosa_klinis"]').val(),
                informasi_tambahan: formContainer.find('textarea[name="informasi_tambahan"]').val(),
                detail_lab: {}
            };

            console.log('Data before checkbox mapping:', data);

            formContainer.find('input[type="checkbox"]:checked').each(function() {
                let name = $(this).attr('name'); 
                let match = name.match(/\[(.*?)\]/);
                if (match) {
                    let kd = match[1];
                    if (!data.detail_lab[kd]) data.detail_lab[kd] = [];
                    data.detail_lab[kd].push($(this).val());
                }
            });

            console.log('Data after checkbox mapping:', data);

            if (!data.kd_jenis_prw || data.kd_jenis_prw.length === 0) {
                tampilkanError("Pilih minimal satu pemeriksaan lab.");
                return;
            }

            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');

            $.ajax({
                url: "{{ route('ralan.store-lab') }}",
                method: "POST",
                data: data,
                success: function(res) {
                    console.log('=== LAB SAVE SUCCESS ===');
                    console.log('Response:', res);
                    
                    btn.prop('disabled', false).html(originalText);
                    
                    tampilkanSukses(res.message || 'Permintaan lab berhasil disimpan');
                    setTimeout(function() {
                            loadFormLab();
                        }, 2000);
                    
                    $('#select-lab').val(null).trigger('change');
                    formContainer.find('textarea').val('');
                    $('#list-template-checkbox').empty();
                    $('#detail-pemeriksaan-placeholder').show();
                },
                error: function(xhr) {
                    console.error('=== LAB SAVE ERROR ===');
                    console.error('Response:', xhr.responseText);
                    
                    btn.prop('disabled', false).html(originalText);
                    
                    let errorMsg = "Terjadi kesalahan saat menyimpan.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        errorMsg = "Data yang diinput tidak valid";
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            let errors = xhr.responseJSON.errors;
                            errorMsg += ":\n" + Object.values(errors).flat().join("\n");
                        }
                    }
                    
                    tampilkanError(errorMsg);
                }
            });
        });

        $(document).on('click', '#btnSimpanRad', function(e) {
            e.preventDefault();
            console.log('=== SIMPAN RADIOLOGI CLICKED ===');
            
            let btn = $(this);
            let formContainer = $('#formPermintaanRad'); // ID div utama radiologi
            
            let data = {
                _token: $('meta[name="csrf-token"]').attr('content') || "{{ csrf_token() }}",
                no_rawat: formContainer.find('input[name="no_rawat"]').val(),
                kd_jenis_prw_rad: $('#select-rad').val(),
                diagnosa_klinis: formContainer.find('textarea[name="diagnosa_klinis"]').val(),
                informasi_tambahan: formContainer.find('textarea[name="informasi_tambahan"]').val()
            };

            if (!data.kd_jenis_prw_rad || data.kd_jenis_prw_rad.length === 0) {
                tampilkanError("Pilih minimal satu pemeriksaan radiologi.");
                return;
            }

            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');

            $.ajax({
                url: "{{ route('ralan.store-radiologi') }}",
                method: "POST",
                data: data,
                success: function(res) {
                    console.log('=== RADIOLOGI SAVE SUCCESS ===');
                    btn.prop('disabled', false).html(originalText);
                    
                    tampilkanSukses(res.message || 'Permintaan radiologi berhasil disimpan');
                    
                    setTimeout(function() {
                        loadFormRadiologi();
                    }, 1500);
                    
                    $('#select-rad').val(null).trigger('change');
                    formContainer.find('textarea').val('');
                },
                error: function(xhr) {
                    console.error('=== RADIOLOGI SAVE ERROR ===');
                    btn.prop('disabled', false).html(originalText);
                    tampilkanError(xhr.responseJSON?.message || "Gagal menyimpan permintaan radiologi.");
                }
        $(document).on('click', '#btn-simpan-diagnosa', function(e) {
            e.preventDefault();
            let btn = $(this);
            let form = $('#form-diagnosa');
            let kd_penyakit = $('#select-icd10').val();

            if (!kd_penyakit || kd_penyakit.length === 0) {
                tampilkanError("Pilih minimal satu diagnosa ICD-10.");
                return;
            }

            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: "{{ route('ralan.store-diagnosa') }}",
                method: "POST",
                data: form.serialize(),
                success: function(res) {
                    btn.prop('disabled', false).html(originalText);
                    tampilkanSukses(res.message);
                    loadDiagnosaProsedur();
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    tampilkanError(xhr.responseJSON?.message || "Gagal menyimpan diagnosa.");
                }
            });
        });

        $(document).on('click', '#btn-simpan-prosedur', function(e) {
            e.preventDefault();
            let btn = $(this);
            let form = $('#form-prosedur');
            let kode = $('#select-icd9').val();

            if (!kode || kode.length === 0) {
                tampilkanError("Pilih minimal satu prosedur ICD-9.");
                return;
            }

            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: "{{ route('ralan.store-prosedur') }}",
                method: "POST",
                data: form.serialize(),
                success: function(res) {
                    btn.prop('disabled', false).html(originalText);
                    tampilkanSukses(res.message);
                    loadDiagnosaProsedur();
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    tampilkanError(xhr.responseJSON?.message || "Gagal menyimpan prosedur.");
                }
            });
        });

        $(document).on('click', '.btn-hapus-diagnosa', function() {
            let kd = $(this).data('kd');
            if (confirm('Hapus diagnosa ini?')) {
                $.ajax({
                    url: '/ralan/delete-diagnosa/' + currentSafeNoRawat + '/' + kd,
                    method: 'DELETE',
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadDiagnosaProsedur();
                    },
                    error: function(xhr) {
                        tampilkanError(xhr.responseJSON?.message || "Gagal menghapus diagnosa.");
                    }
                });
            }
        });

        $(document).on('click', '.btn-hapus-prosedur', function() {
            let kode = $(this).data('kode');
            if (confirm('Hapus prosedur ini?')) {
                $.ajax({
                    url: '/ralan/delete-prosedur/' + currentSafeNoRawat + '/' + kode,
                    method: 'DELETE',
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadDiagnosaProsedur();
                    },
                    error: function(xhr) {
                        tampilkanError(xhr.responseJSON?.message || "Gagal menghapus prosedur.");
                    }
                });
            }
        });

        console.log('=== ALL EVENT HANDLERS REGISTERED ===');
    });

    window.hapusObat = hapusObat;
    window.hapusBaris = hapusBaris;
    window.resetFormUmum = resetFormUmum;
    window.resetFormRacikan = resetFormRacikan;
</script>
@endpush