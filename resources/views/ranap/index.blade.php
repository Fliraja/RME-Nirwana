@extends('layouts.app')

@section('title', 'Pasien Rawat Inap')
@section('page-title', 'Daftar Pasien Rawat Inap')

@section('content')
<div class="container-fluid">
    {{-- Header Banner & Statistik --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1">Pasien Rawat Inap</h3>
            <p class="text-muted mb-0">
                Dokter: <strong>{{ $nama_dokter }}</strong> &nbsp;|&nbsp; 
                Sistem Rekam Medis Rawat Inap
            </p>
        </div>
    </div>

    {{-- Cards Statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">PASIEN AKTIF DIRAWAT</div>
                        <h3 class="fw-bold text-primary mb-0 mt-1">{{ number_format($stats['totalPasienAktif'] ?? 0) }}</h3>
                    </div>
                    <div class="avatar bg-primary-soft text-primary rounded p-3" style="background: rgba(13,110,253,0.1);">
                        <i class="fas fa-procedures fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card shadow-sm border-0 border-start border-success border-4">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">MASUK HARI INI</div>
                        <h3 class="fw-bold text-success mb-0 mt-1">{{ number_format($stats['masukHariIni'] ?? 0) }}</h3>
                    </div>
                    <div class="avatar bg-success-soft text-success rounded p-3" style="background: rgba(25,135,84,0.1);">
                        <i class="fas fa-sign-in-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">PULANG HARI INI</div>
                        <h3 class="fw-bold text-warning mb-0 mt-1">{{ number_format($stats['pulangHariIni'] ?? 0) }}</h3>
                    </div>
                    <div class="avatar bg-warning-soft text-warning rounded p-3" style="background: rgba(255,193,7,0.1);">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content: Nav Tabs Pasien Aktif vs Berdasarkan Tanggal Keluar --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom pt-3 pb-0">
            <ul class="nav nav-tabs card-header-tabs" id="ranapListTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ request('tab') != 'pulang' ? 'active fw-bold text-primary' : 'text-muted' }}" id="aktif-tab" data-bs-toggle="tab" href="#tab-aktif" role="tab">
                        <i class="fas fa-bed me-1"></i> Pasien Aktif (Belum Pulang)
                        <span class="badge bg-primary ms-1">{{ count($pasienAktif) }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('tab') == 'pulang' ? 'active fw-bold text-primary' : 'text-muted' }}" id="pulang-tab" data-bs-toggle="tab" href="#tab-pulang" role="tab">
                        <i class="fas fa-calendar-alt me-1"></i> Berdasarkan Tanggal Keluar
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="ranapListTabsContent">
                {{-- TAB 1: PASIEN AKTIF --}}
                <div class="tab-pane fade {{ request('tab') != 'pulang' ? 'show active' : '' }}" id="tab-aktif" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="fw-bold mb-0 text-dark">Daftar Pasien Sedang Dirawat</h5>
                        <form method="GET" action="{{ route('ranap.index') }}" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama, RM, bangsal..." value="{{ request('search') }}" style="min-width: 250px;">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search'))
                                <a href="{{ route('ranap.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                            @endif
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle border mb-0" id="tablePasienAktif">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Nama Pasien & Rekam Medis</th>
                                    <th>Bangsal / Kamar</th>
                                    <th>Bed & Kelas</th>
                                    <th>Tgl Masuk</th>
                                    <th>Cara Bayar</th>
                                    <th>Dokter DPJP</th>
                                    <th class="text-center" style="width: 140px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pasienAktif as $idx => $p)
                                    @php
                                        $lamaHari = \Carbon\Carbon::parse($p->tgl_masuk)->diffInDays(\Carbon\Carbon::now());
                                    @endphp
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>
                                            <a href="{{ route('ranap.index', ['action' => 'view', 'no_rawat' => $p->no_rawat]) }}" class="fw-bold text-primary text-decoration-none d-block">
                                                {{ $p->nm_pasien }}
                                            </a>
                                            <small class="text-muted">
                                                RM: <strong>{{ $p->no_rkm_medis }}</strong> &nbsp;|&nbsp; 
                                                {{ $p->jk == 'L' ? 'Laki-Laki' : 'Perempuan' }}, {{ $p->umur }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ $p->nm_bangsal }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $p->kd_kamar }}
                                            </span>
                                            <small class="d-block text-muted">{{ $p->kelas }}</small>
                                        </td>
                                        <td>
                                            <span>{{ \Carbon\Carbon::parse($p->tgl_masuk)->translatedFormat('d M Y') }}</span>
                                            <small class="d-block text-muted">{{ $p->jam_masuk }} ({{ $lamaHari == 0 ? 'Hari ini' : $lamaHari . ' hari' }})</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">{{ $p->png_jawab }}</span>
                                        </td>
                                        <td>
                                            <small class="text-dark fw-semibold">{{ $p->dpjp ?? '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('ranap.index', ['action' => 'view', 'no_rawat' => $p->no_rawat]) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-stethoscope me-1"></i> Periksa
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center p-4 text-muted">
                                            <i class="fas fa-procedures fa-3x mb-2 d-block text-secondary" style="opacity: 0.3;"></i>
                                            Tidak ada data pasien rawat inap yang aktif saat ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB 2: PASIEN BERDASARKAN TANGGAL KELUAR --}}
                <div class="tab-pane fade {{ request('tab') == 'pulang' ? 'show active' : '' }}" id="tab-pulang" role="tabpanel">
                    <form method="GET" action="{{ route('ranap.index') }}" class="mb-4">
                        <input type="hidden" name="tab" value="pulang">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <label class="col-form-label fw-bold small text-muted">Tanggal Keluar:</label>
                            </div>
                            <div class="col-auto">
                                <input type="date" name="tanggal_keluar" class="form-control form-control-sm" value="{{ $tanggalKeluar }}">
                            </div>
                            <div class="col-auto">
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama / RM..." value="{{ request('search') }}">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-filter me-1"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle border mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Nama Pasien</th>
                                    <th>No. RM</th>
                                    <th>Bangsal / Kamar</th>
                                    <th>Tgl Masuk</th>
                                    <th>Tgl Keluar</th>
                                    <th>Lama</th>
                                    <th>Status Pulang</th>
                                    <th>Cara Bayar</th>
                                    <th class="text-center" style="width: 140px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pasienPulang as $idx => $p)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>
                                            <a href="{{ route('ranap.index', ['action' => 'view', 'no_rawat' => $p->no_rawat]) }}" class="fw-bold text-primary text-decoration-none">
                                                {{ $p->nm_pasien }}
                                            </a>
                                            <small class="d-block text-muted">{{ $p->jk == 'L' ? 'Laki-Laki' : 'Perempuan' }}, {{ $p->umur }}</small>
                                        </td>
                                        <td>{{ $p->no_rkm_medis }}</td>
                                        <td>{{ $p->nm_bangsal }} ({{ $p->kd_kamar }})</td>
                                        <td>{{ \Carbon\Carbon::parse($p->tgl_masuk)->translatedFormat('d/m/Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($p->tgl_keluar)->translatedFormat('d/m/Y') }}</td>
                                        <td>{{ $p->lama ?? '-' }} hari</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $p->stts_pulang }}</span>
                                        </td>
                                        <td>
                                            <small>{{ $p->png_jawab }}</small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('ranap.index', ['action' => 'view', 'no_rawat' => $p->no_rawat]) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i> Lihat RME
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center p-4 text-muted">
                                            Tidak ada riwayat pasien keluar pada tanggal {{ \Carbon\Carbon::parse($tanggalKeluar)->translatedFormat('d F Y') }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
