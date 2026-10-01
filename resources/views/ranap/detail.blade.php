@extends('layouts.app')

@section('title', 'Pemeriksaan')
@section('page-title', 'Rawat Inap')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h4 class="card-title mb-0">Detail Pasien Rawat Inap</h4>
                </div>
                <div class="card-body">
                    <!-- Info Pasien -->
                    @include('ranap.partials.patient_header')

                    <div class="bg-light rounded p-3">
                        <ul class="nav nav-tabs ralan-tabs border-0 mb-3" id="ranapTab" role="tablist">
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
                                    <i class="fas fa-stethoscope me-1"></i> DIAGNOSA
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="vital-sign-tab" data-bs-toggle="tab" href="#pemeriksaan-vital-sign">
                                    <i class="fas fa-heartbeat me-1"></i> TTV
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="permintaan-lab-tab" data-bs-toggle="tab" href="#permintaan-lab">
                                    <i class="fas fa-flask me-1"></i> LAB
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="permintaan-radiologi-tab" data-bs-toggle="tab" href="#permintaan-radiologi">
                                    <i class="fas fa-x-ray me-1"></i> RADIOLOGI
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="resep-tab" data-bs-toggle="tab" href="#resep">
                                    <i class="fas fa-prescription me-1"></i> RESEP
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content ralan-panel p-3" id="ranapTabContent">
                            {{-- Tab 1: Riwayat --}}
                            <div role="tabpanel" class="tab-pane fade show active" id="riwayat">
                                <div id="content-riwayat">
                                    @include('ranap.tabs.riwayat')
                                </div>
                            </div>

                            {{-- Tab 2: SOAP --}}
                            <div role="tabpanel" class="tab-pane fade" id="pemeriksaan-soap">
                                <div id="content-soap">
                                    <div class="text-center p-5">
                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2 text-muted">Memuat Form & Riwayat SOAP...</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 3: Diagnosa & Prosedur --}}
                            <div role="tabpanel" class="tab-pane fade" id="diagnosa-prosedur">
                                <div id="content-diagnosa-prosedur">
                                    <div class="text-center p-5">
                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2 text-muted">Memuat Diagnosa & Prosedur...</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 4: TTV & SBAR --}}
                            <div role="tabpanel" class="tab-pane fade" id="pemeriksaan-vital-sign">
                                <div id="content-vital-sign">
                                    <div class="text-center p-5">
                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2 text-muted">Memuat Form TTV & Catatan SBAR...</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 5: Lab & Hasil --}}
                            <div role="tabpanel" class="tab-pane fade" id="permintaan-lab">
                                <div id="content-lab">
                                    <div class="text-center p-5">
                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2 text-muted">Memuat Form Permintaan Lab...</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 6: Radiologi --}}
                            <div role="tabpanel" class="tab-pane fade" id="permintaan-radiologi">
                                <div id="content-radiologi">
                                    <div class="text-center p-5">
                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2 text-muted">Memuat Form Permintaan Radiologi...</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 7: Resep Obat & Log Bangsal --}}
                            <div role="tabpanel" class="tab-pane fade" id="resep">
                                <div id="content-resep">
                                    <div class="text-center p-5">
                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2 text-muted">Memuat Form Peresepan...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <a href="{{ route('ranap.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    window.RANAP = {
        baseUrl: "{{ url('/') }}",
        csrfToken: "{{ csrf_token() }}",
        noRawat: "{{ $detailPasien->no_rawat }}",
        safeNoRawat: "{{ str_replace('/', '-', $detailPasien->no_rawat) }}",
        noRkmMedis: "{{ $detailPasien->no_rkm_medis }}"
    };
    var currentNoRawat = window.RANAP.noRawat;
    var currentSafeNoRawat = window.RANAP.safeNoRawat;
    var currentNoRkmMedis = window.RANAP.noRkmMedis;
</script>
<script src="{{ asset('js/ranap/ranap-core.js') }}"></script>
@endpush
