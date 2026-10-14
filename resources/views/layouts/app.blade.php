<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - RME RSU Nirwana</title>
    
    <link rel="icon" href="{{ asset('img/icon.png') }}" type="image/x-icon">

    <!-- Aset vendor di-host lokal (public/vendor), versi dipatok di nama folder -->
    <link rel="stylesheet" href="{{ asset('vendor/inter-4/inter.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-5.3.3/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-6.5.1/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/tom-select-2.3.1/tom-select.bootstrap5.min.css') }}">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <script src="{{ asset('js/admin.js') }}"></script>
    
    @stack('styles')
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Navbar -->
            @include('layouts.partials.navbar')
            
            <!-- Page Content -->
            <div class="content-wrapper">
                @yield('content')
            </div>
            
            <!-- Footer -->
            @include('layouts.partials.footer')
        </div>
    </div>
    
    <!-- Scripts -->
    @include('layouts.partials.scripts')
    @stack('scripts')
</body>
</html>