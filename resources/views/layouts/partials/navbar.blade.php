<nav class="top-navbar">
    <div class="navbar-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="#">@yield('title', 'Home')</a></li>
                <li class="breadcrumb-item active" aria-current="page">@yield('page-title', 'Dashboard')</li>
            </ol>
        </nav>
    </div>
    
    <div class="navbar-right">
        <div class="navbar-search d-none d-lg-block">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari pasien, rekam medis..." class="search-input">
            </div>
        </div>
        
        <div class="navbar-item dropdown">
            <button class="user-dropdown-btn" data-bs-toggle="dropdown">
                <div class="user-avatar-sm">
                    <i class="fas fa-user"></i>
                </div>
                @php
                    if (session('role') == 'admin') {
                        $userName = 'Administrator'; 
                    } else {
                        $userName = Auth::user()->dokter_data->nm_dokter ?? 'User';
                    }
                @endphp

                <span class="user-name d-none d-md-inline">{{ $userName }}</span>
                <i class="fas fa-chevron-down ms-1"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end user-menu">
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item text-danger" 
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt me-2"></i> Keluar
                </a>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</nav>