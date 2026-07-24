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
                            <ul class="nav nav-tabs ralan-tabs border-0 mb-3" id="myTab" role="tablist">
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

                            <div class="tab-content ralan-panel p-3" id="myTabContent">
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
