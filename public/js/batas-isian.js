/*
 * Batas panjang isian SOAP/TTV (kolom Khanza pendek, lihat App\Support\BatasIsianPemeriksaan).
 * Textarea bertanda data-batas="400" mendapat penghitung yang muncul mulai 75% batas.
 * Form yang masih punya isian kelebihan tidak dikirim. Galat 422 dari server ditaruh
 * di bawah kolomnya lewat window.tampilkanGalatIsian.
 * Memakai delegasi di document karena form dimuat lewat AJAX.
 */
(function () {
    var AMBANG_TAMPIL = 0.75;

    function wadahPesan(el) {
        var id = (el.id || el.name) + '-batas';
        var wadah = document.getElementById(id);
        if (!wadah) {
            wadah = document.createElement('div');
            wadah.id = id;
            wadah.className = 'batas-isian';
            wadah.setAttribute('aria-live', 'polite');
            el.insertAdjacentElement('afterend', wadah);
            tambahKeterangan(el, id);
        }
        return wadah;
    }

    function tambahKeterangan(el, id) {
        var lama = (el.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
        if (lama.indexOf(id) === -1) lama.push(id);
        el.setAttribute('aria-describedby', lama.join(' '));
    }

    function periksa(el) {
        var batas = parseInt(el.getAttribute('data-batas'), 10);
        if (!batas) return true;

        var jumlah = el.value.length;
        var lebih = jumlah - batas;
        var wadah = document.getElementById((el.id || el.name) + '-batas');

        if (lebih > 0) {
            wadah = wadahPesan(el);
            wadah.textContent = 'Lebih ' + lebih + ' karakter dari batas ' + batas + '. Ringkas dulu sebelum menyimpan.';
            wadah.classList.add('lebih');
            el.classList.add('is-invalid');
            el.setAttribute('aria-invalid', 'true');
            return false;
        }

        el.removeAttribute('aria-invalid');
        if (!el.dataset.galatServer) el.classList.remove('is-invalid');

        if (jumlah >= batas * AMBANG_TAMPIL) {
            wadah = wadahPesan(el);
            wadah.textContent = jumlah + ' dari ' + batas + ' karakter';
            wadah.classList.remove('lebih');
        } else if (wadah) {
            wadah.textContent = '';
            wadah.classList.remove('lebih');
        }
        return true;
    }

    function hapusGalatServer(el) {
        if (!el.dataset.galatServer) return;
        delete el.dataset.galatServer;
        el.classList.remove('is-invalid');
        var pesan = document.getElementById((el.id || el.name) + '-galat');
        if (pesan) pesan.remove();
    }

    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el.form) return;
        hapusGalatServer(el);
        if (el.hasAttribute('data-batas')) periksa(el);
    });

    // reset() tidak memicu input; penghitung dan galat ikut dibersihkan.
    document.addEventListener('reset', function (e) {
        var isian = e.target.querySelectorAll('[data-batas], [data-galat-server]');
        setTimeout(function () {
            for (var i = 0; i < isian.length; i++) {
                hapusGalatServer(isian[i]);
                if (isian[i].hasAttribute('data-batas')) periksa(isian[i]);
            }
        });
    });

    // Fase capture: jalan sebelum handler submit jQuery di form maupun di document.
    document.addEventListener('submit', function (e) {
        var isian = e.target.querySelectorAll('[data-batas]');
        var pertama = null;
        for (var i = 0; i < isian.length; i++) {
            if (!periksa(isian[i]) && !pertama) pertama = isian[i];
        }
        if (!pertama) return;

        e.preventDefault();
        e.stopImmediatePropagation();
        pertama.focus();
    }, true);

    /** errors = xhr.responseJSON.errors dari validasi Laravel. Mengembalikan jumlah kolom yang ditandai. */
    window.tampilkanGalatIsian = function (form, errors) {
        var ditandai = 0;
        Object.keys(errors || {}).forEach(function (nama) {
            var el = form.querySelector('[name="' + nama + '"]:not([type="hidden"])');
            if (!el) return;
            hapusGalatServer(el);
            var id = (el.id || el.name) + '-galat';
            var pesan = document.createElement('div');
            pesan.id = id;
            pesan.className = 'invalid-feedback d-block';
            pesan.textContent = errors[nama][0];
            el.insertAdjacentElement('afterend', pesan);
            el.dataset.galatServer = '1';
            el.classList.add('is-invalid');
            tambahKeterangan(el, id);
            if (!ditandai) el.focus();
            ditandai++;
        });
        return ditandai;
    };
})();
