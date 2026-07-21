<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-wrapper">
            <div class="logo-icon">
                <img src="{{ asset('img/icon.png') }}" alt="" style="height: 40px; width: 40px;">
            </div>
            <div class="logo-text">
                <h5 class="mb-0">RSU Nirwana</h5>
                <small>Banjarbaru</small>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-divider">
                <span>MENU UTAMA</span>
            </li>
            
            <li class="nav-item">
                <a href="{{ route('ralan.index') }}" class="nav-link {{ request()->is('ralan*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Rawat Jalan</span>
                </a>
            </li>
            
            {{-- <li class="nav-item">
                <a href="#" class="nav-link {{ request()->is('rekam-medis*') ? 'active' : '' }}">
                    <i class="fas fa-file-medical"></i>
                    <span>Rekam Medis</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="#" class="nav-link {{ request()->is('jadwal-dokter*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Jadwal Dokter</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="#" class="nav-link {{ request()->is('antrian*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Antrian</span>
                </a>
            </li> --}}
            
            {{-- <li class="nav-divider">
                <span>LAPORAN</span>
            </li> --}}

            {{-- <li class="nav-item {{ request()->is('report*') ? 'has-submenu-open' : '' }}">
                <a href="#submenu-laporan" data-toggle="collapse" 
                class="nav-link {{ request()->is('report*') ? '' : 'collapsed' }}" 
                aria-expanded="{{ request()->is('report*') ? 'true' : 'false' }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                
                <ul id="submenu-laporan" class="collapse {{ request()->is('report*') ? 'show' : '' }}">
                    <!-- Submenu items -->
                    <li class="nav-item">
                        <a href="{{ route('report.soap-index') }}" 
                        class="nav-link {{ request()->is('report/soap*') ? 'active' : '' }}">
                            <i class="fas fa-file-medical"></i>
                            <span>Laporan SOAP</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="{{ route('report.vitalsign-index') }}" 
                        class="nav-link {{ request()->is('report/vitalsign*') ? 'active' : '' }}">
                            <i class="fas fa-heartbeat"></i>
                            <span>Laporan Vital Sign</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="{{ route('report.lab-index') }}" 
                        class="nav-link {{ request()->is('report/lab*') ? 'active' : '' }}">
                            <i class="fas fa-flask"></i>
                            <span>Laporan Lab</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="{{ route('report.radiologi-index') }}" 
                        class="nav-link {{ request()->is('report/radiologi*') ? 'active' : '' }}">
                            <i class="fas fa-x-ray"></i>
                            <span>Laporan Radiologi</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="{{ route('report.resep-index') }}" 
                        class="nav-link {{ request()->is('report/resep*') ? 'active' : '' }}">
                            <i class="fas fa-prescription"></i>
                            <span>Laporan Resep</span>
                        </a>
                    </li>
                </ul>
            </li> --}}
            
            {{-- <li class="nav-item">
                <a href="#" class="nav-link {{ request()->is('pengaturan*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </li> --}}
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-md"></i>
            </div>
           @php
                if (session('role') == 'admin') {
                    $userName = Auth::user()->usere ?? 'Administrator'; 
                    $userRole = "Super Admin";
                } else {
                    $userName = Auth::user()->dokter_data->nm_dokter ?? 'User';
                    $userRole = Auth::user()->decrypted_id ?? '-';
                }
            @endphp

            <div class="user-details">
                <div class="profile-name" style="font-weight: bold;">{{ $userName }}</div>
                <div class="profile-role" style="font-size: 0.85em; color: #666;">
                    <i class="fas fa-circle text-success" style="font-size: 10px;"></i> {{ $userRole }}
                </div>
            </div>
        </div>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>