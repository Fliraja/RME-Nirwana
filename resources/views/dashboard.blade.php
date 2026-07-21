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
                <h3>1,234</h3>
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
                <h3>56</h3>
                <p>Kunjungan Hari Ini</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft">
                <i class="fas fa-user-md text-warning"></i>
            </div>
            <div class="stat-content">
                <h3>12</h3>
                <p>Dokter Aktif</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-soft">
                <i class="fas fa-clipboard-list text-info"></i>
            </div>
            <div class="stat-content">
                <h3>8</h3>
                <p>Dalam Antrian</p>
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
                <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
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
                            <tr>
                                <td><strong>RM-2024-0001</strong></td>
                                <td>Ahmad Fadillah</td>
                                <td>15 Aug 2025</td>
                                <td><span class="badge badge-success">Aktif</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>RM-2024-0002</strong></td>
                                <td>Siti Rahmawati</td>
                                <td>15 Aug 2025</td>
                                <td><span class="badge badge-success">Aktif</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>RM-2024-0003</strong></td>
                                <td>Muhammad Rizky</td>
                                <td>14 Aug 2025</td>
                                <td><span class="badge badge-warning">Pending</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>RM-2024-0004</strong></td>
                                <td>Nur Hidayah</td>
                                <td>14 Aug 2025</td>
                                <td><span class="badge badge-success">Aktif</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>RM-2024-0005</strong></td>
                                <td>Bambang Susanto</td>
                                <td>13 Aug 2025</td>
                                <td><span class="badge badge-success">Aktif</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
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
                <h5 class="card-title">Jadwal Dokter Hari Ini</h5>
            </div>
            <div class="card-body">
                <div class="schedule-item d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <div class="schedule-avatar bg-primary-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-user-md text-primary"></i>
                    </div>
                    <div class="schedule-info grow">
                        <h6 class="mb-0">dr. Hendra Wijaya, Sp.PD</h6>
                        <small class="text-muted">Poli Penyakit Dalam</small>
                    </div>
                    <span class="badge badge-success">08:00 - 12:00</span>
                </div>
                <div class="schedule-item d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <div class="schedule-avatar bg-success-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-user-md text-success"></i>
                    </div>
                    <div class="schedule-info grow">
                        <h6 class="mb-0">dr. Sari Melati, Sp.A</h6>
                        <small class="text-muted">Poli Anak</small>
                    </div>
                    <span class="badge badge-success">09:00 - 14:00</span>
                </div>
                <div class="schedule-item d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <div class="schedule-avatar bg-warning-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-user-md text-warning"></i>
                    </div>
                    <div class="schedule-info grow">
                        <h6 class="mb-0">dr. Budi Santoso, Sp.B</h6>
                        <small class="text-muted">Poli Bedah</small>
                    </div>
                    <span class="badge badge-warning">13:00 - 17:00</span>
                </div>
                <div class="schedule-item d-flex align-items-center gap-3">
                    <div class="schedule-avatar bg-info-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-user-md text-info"></i>
                    </div>
                    <div class="schedule-info grow">
                        <h6 class="mb-0">dr. Dewi Kusuma, Sp.OG</h6>
                        <small class="text-muted">Poli Kandungan</small>
                    </div>
                    <span class="badge badge-info">14:00 - 18:00</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection