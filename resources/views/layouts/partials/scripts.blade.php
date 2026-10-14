<script src="{{ asset('vendor/jquery-3.7.1/jquery.min.js') }}"></script>

<script src="{{ asset('vendor/bootstrap-5.3.3/js/bootstrap.bundle.min.js') }}"></script>

<script src="{{ asset('vendor/sweetalert2-11.26.25/sweetalert2.all.min.js') }}"></script>

<script src="{{ asset('vendor/tom-select-2.3.1/tom-select.complete.min.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const wrapper = document.querySelector('.wrapper');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                wrapper.classList.toggle('sidebar-collapsed');
                
                if (window.innerWidth < 992) {
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                }
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
        
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }); 

    $(document).ready(function() {
        @if(session('success-soap'))
            Swal.fire({
                title: 'Berhasil!',
                text: "{{ session('success-soap') }}",
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                timerProgressBar: true,
                background: '#ffffff',
                iconColor: '#28a745',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
        @endif

        @if(session('error-soap'))
            Swal.fire({
                title: 'Gagal!',
                text: "{{ session('error-soap') }}",
                icon: 'error',
                confirmButtonColor: '#d33',
            });
        @endif

        @if(session('success-vital'))
            Swal.fire({
                title: 'Berhasil!',
                text: "{{ session('success-vital') }}",
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                timerProgressBar: true,
                background: '#ffffff',
                iconColor: '#28a745',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
        @endif

        @if(session('error-vital'))
            Swal.fire({
                title: 'Gagal!',
                text: "{{ session('error-vital') }}",
                icon: 'error',
                confirmButtonColor: '#d33',
            });
        @endif
    }); 
</script>