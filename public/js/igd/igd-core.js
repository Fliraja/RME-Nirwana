/* Halaman pasien IGD: memuat isi tab lewat AJAX (tab aktif saat halaman dibuka, tab lain saat pertama dibuka). */
(function () {
    'use strict';

    function tampilkanMemuat(wadah, label) {
        wadah.innerHTML = '<div class="igd-state" role="status"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Memuat ' + label + '…</div>';
    }

    function tampilkanGagal(wadah, label, ulang) {
        wadah.innerHTML = '<div class="igd-state igd-state-gagal" role="alert">' + label.charAt(0).toUpperCase() + label.slice(1) +
            ' gagal dimuat. <button type="button" class="btn btn-sm btn-outline-secondary ms-2">Coba lagi</button></div>';
        wadah.querySelector('button').addEventListener('click', ulang);
    }

    function muatTab(tombol, srcLain, diam) {
        var src = srcLain || tombol.dataset.src;
        var panel = document.querySelector(tombol.dataset.bsTarget);
        var wadah = panel && panel.querySelector('.igd-isi-tab');
        if (!src || !wadah) {
            return;
        }

        var label = tombol.dataset.label || tombol.textContent.trim().toLowerCase();
        if (!diam) {
            tampilkanMemuat(wadah, label);
        }

        fetch(src, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error(res.status);
                }
                return res.text();
            })
            .then(function (html) {
                wadah.innerHTML = html;
                tombol.dataset.dimuat = '1';
                document.dispatchEvent(new CustomEvent('igd:tab-dimuat', { detail: { panel: panel, wadah: wadah, tombol: tombol } }));
            })
            .catch(function () {
                tampilkanGagal(wadah, label, function () { muatTab(tombol, srcLain); });
            });
    }

    var tombolTab = document.querySelectorAll('.igd-tabs [data-src]');

    tombolTab.forEach(function (tombol) {
        tombol.addEventListener('shown.bs.tab', function () {
            if (tombol.dataset.dimuat !== '1') {
                muatTab(tombol);
            }
        });

        // Tautan halaman (pagination) di dalam tab dimuat ulang di tempat, bukan pindah halaman.
        var panel = document.querySelector(tombol.dataset.bsTarget);
        panel && panel.addEventListener('click', function (e) {
            var a = e.target.closest('.pagination a[href]');
            if (a) {
                e.preventDefault();
                muatTab(tombol, a.href);
            }
        });

        if (tombol.classList.contains('active')) {
            muatTab(tombol);
        }
    });

    /** Dipakai modul tab (mis. igd-order.js) untuk memuat ulang isi tab setelah simpan/hapus tanpa spinner penuh. */
    window.IgdTab = {
        muatUlang: function (idPanel) {
            var tombol = document.querySelector('.igd-tabs [data-bs-target="#' + idPanel + '"]');
            tombol && muatTab(tombol, null, true);
        }
    };
})();
