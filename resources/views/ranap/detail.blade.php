@extends('layouts.app')

@section('title', 'Pemeriksaan Rawat Inap')
@section('page-title', 'Rawat Inap - Rekam Medis Pasien')

@section('content')
<div class="container-fluid">
    {{-- Header Pasien --}}
    @include('ranap.partials.patient_header')

    {{-- Tabs Workspace --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <div class="bg-light rounded p-3">
                <ul class="nav nav-tabs ralan-tabs border-0 mb-3" id="ranapTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="riwayat-tab" data-bs-toggle="tab" href="#riwayat">
                            <i class="fas fa-history me-1 text-primary"></i> RIWAYAT
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="soap-tab" data-bs-toggle="tab" href="#pemeriksaan-soap">
                            <i class="fas fa-notes-medical me-1 text-success"></i> SOAP (CPPT)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="vital-sign-tab" data-bs-toggle="tab" href="#pemeriksaan-vital-sign">
                            <i class="fas fa-heartbeat me-1 text-danger"></i> TTV & SBAR
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="diagnosa-prosedur-tab" data-bs-toggle="tab" href="#diagnosa-prosedur">
                            <i class="fas fa-stethoscope me-1 text-info"></i> DIAGNOSA
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="resep-tab" data-bs-toggle="tab" href="#resep">
                            <i class="fas fa-prescription me-1 text-warning"></i> RESEP & OBAT
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="permintaan-lab-tab" data-bs-toggle="tab" href="#permintaan-lab">
                            <i class="fas fa-flask me-1 text-purple" style="color: #6f42c1;"></i> LAB & HASIL
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="permintaan-radiologi-tab" data-bs-toggle="tab" href="#permintaan-radiologi">
                            <i class="fas fa-x-ray me-1 text-teal" style="color: #20c997;"></i> RADIOLOGI
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="ranapTabContent">
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
                                <p class="mt-2 text-muted">Memuat Form & Riwayat SOAP Ranap...</p>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 3: TTV & SBAR --}}
                    <div role="tabpanel" class="tab-pane fade" id="pemeriksaan-vital-sign">
                        <div id="content-vital-sign">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2 text-muted">Memuat Form TTV & Catatan SBAR...</p>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 4: Diagnosa & Prosedur --}}
                    <div role="tabpanel" class="tab-pane fade" id="diagnosa-prosedur">
                        <div id="content-diagnosa-prosedur">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2 text-muted">Memuat Diagnosa & Prosedur Ranap...</p>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 5: Resep Obat & Log Bangsal --}}
                    <div role="tabpanel" class="tab-pane fade" id="resep">
                        <div id="content-resep">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2 text-muted">Memuat Form Peresepan & Riwayat Pemberian Obat...</p>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 6: Lab & Hasil --}}
                    <div role="tabpanel" class="tab-pane fade" id="permintaan-lab">
                        <div id="content-lab">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2 text-muted">Memuat Form Permintaan & Hasil Tes Lab...</p>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 7: Radiologi, Gambar & Ekspertise --}}
                    <div role="tabpanel" class="tab-pane fade" id="permintaan-radiologi">
                        <div id="content-radiologi">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2 text-muted">Memuat Form Permintaan & Hasil Radiologi...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('ranap.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Pasien
                </a>
                <span class="text-muted small">
                    Pasien: <strong>{{ $detailPasien->pasien->nm_pasien }}</strong> ({{ $detailPasien->no_rawat }})
                </span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css">
<style>
    .ralan-tabs .nav-link {
        font-weight: 600;
        font-size: 0.88rem;
        color: #495057;
        border-radius: 8px;
        margin-right: 6px;
        padding: 10px 16px;
        transition: all 0.2s ease;
    }
    .ralan-tabs .nav-link:hover {
        background-color: #e9ecef;
        color: #0d6efd;
    }
    .ralan-tabs .nav-link.active {
        background-color: #ffffff;
        color: #0d6efd;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .badge-soft {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
</style>
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
