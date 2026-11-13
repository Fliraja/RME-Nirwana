/*
 * Sub-item paket lab di form permintaan, dipakai IGD, ralan, dan ranap.
 * Sub-item dicentang semua secara bawaan. Paket besar seperti kimia darah sering hanya diambil
 * sebagian, jadi tiap paket punya "Pilih semua" dan "Kosongkan", dan daftar berhenti di BARIS_MAKS
 * baris lalu berlanjut ke kolom di sampingnya. Checkbox bernama detail_lab[kd][], nama yang dibaca
 * LaboratoriumService.
 */
(function () {
    'use strict';

    var BARIS_MAKS = 8;

    function cariGrup(wadah, kode) {
        return wadah.querySelector('.lab-subitem-grup[data-kd="' + CSS.escape(kode) + '"]');
    }

    function perbaruiHitungan(grup) {
        var semua = grup.querySelectorAll('input[type="checkbox"]');
        var dipilih = grup.querySelectorAll('input[type="checkbox"]:checked').length;
        grup.querySelector('.lab-subitem-hitung').textContent = dipilih + ' dari ' + semua.length + ' dipilih';
        grup.classList.toggle('lab-subitem-kosong', dipilih === 0);
    }

    function buatGrup(kode, nama, daftar) {
        var grup = document.createElement('fieldset');
        grup.className = 'lab-subitem-grup';
        grup.dataset.kd = kode;
        grup.innerHTML =
            '<legend class="lab-subitem-judul"><span class="lab-subitem-nama"></span> <span class="lab-subitem-hitung"></span></legend>' +
            '<div class="lab-subitem-aksi">' +
            '<button type="button" class="btn btn-sm" data-centang="semua">Pilih semua</button>' +
            '<button type="button" class="btn btn-sm" data-centang="kosong">Kosongkan</button>' +
            '</div>' +
            '<div class="lab-subitem-daftar"></div>';
        grup.querySelector('.lab-subitem-nama').textContent = nama;

        var isi = grup.querySelector('.lab-subitem-daftar');
        isi.style.setProperty('--baris', Math.min(daftar.length, BARIS_MAKS));
        daftar.forEach(function (t) {
            var id = 'lab-sub-' + kode.replace(/[^A-Za-z0-9_-]/g, '') + '-' + t.id_template;
            var baris = document.createElement('div');
            baris.className = 'form-check';
            baris.innerHTML = '<input class="form-check-input" type="checkbox" checked><label class="form-check-label"></label>';
            var cb = baris.querySelector('input');
            cb.id = id;
            cb.name = 'detail_lab[' + kode + '][]';
            cb.value = t.id_template;
            var lb = baris.querySelector('label');
            lb.htmlFor = id;
            lb.textContent = t.Pemeriksaan;
            isi.appendChild(baris);
        });

        grup.addEventListener('change', function () { perbaruiHitungan(grup); });
        grup.addEventListener('click', function (e) {
            var t = e.target.closest('[data-centang]');
            if (!t) {
                return;
            }
            var nyala = t.dataset.centang === 'semua';
            grup.querySelectorAll('input[type="checkbox"]').forEach(function (c) { c.checked = nyala; });
            perbaruiHitungan(grup);
        });
        perbaruiHitungan(grup);
        return grup;
    }

    window.LabSubItem = {
        /**
         * Muat sub-item paket ke wadah. masihDipilih (opsional) dicek setelah data tiba, supaya paket
         * yang sudah dihapus dari pilihan selama memuat tidak muncul lagi.
         */
        muat: function (wadah, url, kode, nama, masihDipilih) {
            if (!wadah || cariGrup(wadah, kode)) {
                return;
            }
            fetch(url + '/' + encodeURIComponent(kode), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (daftar) {
                    if (!daftar || !daftar.length || cariGrup(wadah, kode)) {
                        return;
                    }
                    if (masihDipilih && !masihDipilih(kode)) {
                        return;
                    }
                    wadah.appendChild(buatGrup(kode, nama, daftar));
                })
                .catch(function () { /* tanpa sub-item: seluruh paket tetap dikirim */ });
        },

        hapus: function (wadah, kode) {
            var grup = wadah && cariGrup(wadah, kode);
            grup && grup.remove();
        },

        kosongkan: function (wadah) {
            wadah && wadah.querySelectorAll('.lab-subitem-grup').forEach(function (g) { g.remove(); });
        },

        /** Nama paket pertama yang semua sub-itemnya tidak dicentang, atau null. */
        paketKosong: function (wadah) {
            var nama = wadah && wadah.querySelector('.lab-subitem-kosong .lab-subitem-nama');
            return nama ? nama.textContent : null;
        },

        /** { kd_jenis_prw: [id_template, ...] } untuk paket yang punya sub-item. */
        kumpulkan: function (wadah) {
            var hasil = {};
            wadah && wadah.querySelectorAll('.lab-subitem-grup').forEach(function (g) {
                hasil[g.dataset.kd] = Array.from(g.querySelectorAll('input:checked')).map(function (c) { return c.value; });
            });
            return hasil;
        },

        /** Kalimat galat untuk paket tanpa sub-item tercentang. */
        pesanKosong: function (nama) {
            return 'Pilih minimal satu item untuk ' + nama + ', atau hapus pemeriksaannya.';
        }
    };
})();
