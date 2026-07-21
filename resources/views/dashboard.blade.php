@extends('layouts.app')

@section('title', 'Home')
@section('page-title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Selamat datang di Sistem Rekam Medik Elektronik RSU Nirwana Banjarbaru</p>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft">
                <i class="fas fa-users text-primary"></i>
            </div>
            <div class="stat-content">
                <h3>{{ number_format($stats['totalPasien'] ?? 0) }}</h3>
                <p>Total Pasien</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft">
                <i class="fas fa-calendar-check text-success"></i>
            </div>
            <div class="stat-content">
                <h3>{{ number_format($stats['kunjunganHariIni'] ?? 0) }}</h3>
                <p>Kunjungan Hari Ini</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft">
                <i class="fas fa-user-check text-warning"></i>
            </div>
            <div class="stat-content">
                <h3>{{ number_format($stats['sudahDiperiksa'] ?? 0) }}</h3>
                <p>Sudah Diperiksa</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-soft">
                <i class="fas fa-clock text-info"></i>
            </div>
            <div class="stat-content">
                <h3>{{ number_format($stats['belumDiperiksa'] ?? 0) }}</h3>
                <p>Belum Diperiksa</p>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row g-4">
    <!-- Recent Patients -->
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Pasien Terbaru</h5>
                <a href="{{ route('ralan.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Tanggal Daftar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pasienTerbaru as $item)
                            <tr>
                                <td><strong>{{ $item->no_rkm_medis }}</strong></td>
                                <td>{{ $item->pasien->nm_pasien ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->tgl_registrasi)->format('d M Y') }}</td>
                                <td>
                                    @if($item->stts === 'Sudah' || $item->stts === 'Bayar')
                                        <span class="badge badge-success">{{ $item->stts }}</span>
                                    @elseif($item->stts === 'Belum')
                                        <span class="badge badge-warning">{{ $item->stts }}</span>
                                    @elseif($item->stts === 'Batal')
                                        <span class="badge badge-danger">{{ $item->stts }}</span>
                                    @else
                                        <span class="badge badge-info">{{ $item->stts }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('ralan.index', ['no_rawat' => $item->no_rawat]) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Belum ada data pasien</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Schedule -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">{{ $isAdmin ? 'Jadwal Dokter Hari Ini' : 'Jadwal Praktik Saya Hari Ini' }}</h5>
            </div>
            <div class="card-body">
                @forelse($jadwalDokter as $jdwl)
                <div class="schedule-item d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <div class="schedule-avatar bg-primary-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-user-md text-primary"></i>
                    </div>
                    <div class="schedule-info grow">
                        <h6 class="mb-0">{{ $jdwl->dokter->nm_dokter ?? $jdwl->kd_dokter }}</h6>
                        <small class="text-muted">{{ $jdwl->poliklinik->nm_poli ?? $jdwl->kd_poli }}</small>
                    </div>
                    <span class="badge badge-success">{{ substr($jdwl->jam_mulai, 0, 5) }} - {{ substr($jdwl->jam_selesai, 0, 5) }}</span>
                </div>
                @empty
                <div class="text-center py-3 text-muted">
                    Tidak ada jadwal dokter hari ini
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection