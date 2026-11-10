/* Tab Asesmen IGD: tombol pilihan, tandai Normal, simpan dengan sidik isi (409 bila asesmen berubah sejak dibuka). */
(function () {
    'use strict';

    var cfg = window.IGD || {};
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;

    function toast(ikon, teks) {
        if (window.Swal) {
            Swal.fire({ toast: true, position: 'top-end', icon: ikon, title: teks, showConfirmButton: false, timer: ikon === 'success' ? 2500 : 6000 });
        }
    }

    function el(tag, kelas, teks) {
        var e = document.createElement(tag);
        if (kelas) { e.className = kelas; }
        if (teks) { e.textContent = teks; }
        return e;
    }

    function siapkan(form) {
        var pesan = form.querySelector('[data-pesan]');
        var info = form.querySelector('[data-info-simpan]');
        var tombolSimpan = form.querySelector('[data-simpan]');
        var tombolNormal = form.querySelector('[data-semua-normal]');
        var organ = Array.prototype.slice.call(form.querySelectorAll('.igd-pilih-organ'));
        var ketFisik = form.querySelector('[name="ket_fisik"]');
        var ingat = form.querySelector('[data-ingat-abnormal]');

        function dipilih(grup) {
            return grup.querySelector('input:checked');
        }

        function perbaruiOrgan() {
            var kosong = organ.filter(function (g) { return !dipilih(g); }).length;
            tombolNormal.disabled = kosong === 0;
            tombolNormal.textContent = kosong === organ.length ? 'Tandai semua Normal'
                : kosong === 0 ? 'Semua organ sudah dipilih' : 'Tandai sisanya Normal (' + kosong + ')';

            var abnormal = organ.filter(function (g) { var p = dipilih(g); return p && p.value === 'Abnormal'; })
                .map(function (g) { return g.querySelector('legend').firstChild.textContent.trim(); });
            var perlu = abnormal.length > 0 && ketFisik.value.trim() === '';
            ingat.hidden = !perlu;
            ingat.textContent = perlu ? 'Abnormal: ' + abnormal.join(', ') + '. Tulis temuannya di keterangan fisik.' : '';
        }

        function perbaruiHubungan() {
            var allo = form.querySelector('[name="anamnesis"][value="Alloanamnesis"]').checked;
            form.querySelector('[data-hubungan]').hidden = !allo;
        }

        function hapusGalat(kolom) {
            var galat = form.querySelector('[data-galat="' + kolom + '"]');
            galat && (galat.textContent = '');
            var grup = form.querySelector('fieldset[data-kolom="' + kolom + '"]');
            grup && grup.classList.remove('igd-pilih-galat');
            var isian = form.querySelector('input[type="text"][name="' + kolom + '"], textarea[name="' + kolom + '"]');
            if (isian) {
                isian.classList.remove('is-invalid');
                isian.removeAttribute('aria-invalid');
            }
        }

        function tampilkanGalat(errors) {
            Object.keys(errors).forEach(function (kolom) {
                var galat = form.querySelector('[data-galat="' + kolom + '"]');
                galat && (galat.textContent = errors[kolom][0]);
                var grup = form.querySelector('fieldset[data-kolom="' + kolom + '"]');
                grup && grup.classList.add('igd-pilih-galat');
                var isian = form.querySelector('input[type="text"][name="' + kolom + '"], textarea[name="' + kolom + '"]');
                if (isian) {
                    isian.classList.add('is-invalid');
                    isian.setAttribute('aria-invalid', 'true');
                }
            });

            // Fokus ke isian pertama yang salah sesuai urutan di layar.
            var pertama = Array.prototype.find.call(form.querySelectorAll('[data-galat]'), function (g) { return g.textContent !== ''; });
            if (pertama) {
                var wadah = pertama.closest('fieldset') || pertama.parentElement;
                var sasaran = wadah.querySelector('input:not([type="hidden"]), textarea');
                wadah.scrollIntoView({ block: 'center' });
                sasaran && sasaran.focus({ preventScroll: true });
            }
        }

        /** Angka "n isian perlu diperbaiki" ikut turun saat dokter memperbaiki isian, hilang saat semua beres. */
        function hitungUlangGalat() {
            if (pesan.dataset.jenis !== 'galat') {
                return;
            }
            var n = Array.prototype.filter.call(form.querySelectorAll('[data-galat]'), function (g) { return g.textContent !== ''; }).length;
            n ? tulisPesan(n + ' isian perlu diperbaiki sebelum simpan.', false, 'galat') : tulisPesan('');
        }

        function tulisPesan(teks, denganMuatUlang, jenis) {
            pesan.textContent = teks;
            pesan.dataset.jenis = jenis || '';
            if (denganMuatUlang) {
                var b = el('button', 'btn btn-sm igd-btn-garis ms-2', 'Muat ulang');
                b.type = 'button';
                b.addEventListener('click', muatUlang);
                pesan.appendChild(b);
            }
        }

        function muatUlang() {
            var lanjut = window.Swal
                ? Swal.fire({ title: 'Muat ulang asesmen?', text: 'Isian di layar ini yang belum tersimpan akan hilang. Salin dulu bagian yang ingin dipertahankan.',
                    icon: 'warning', showCancelButton: true, confirmButtonText: 'Muat ulang', cancelButtonText: 'Batal', confirmButtonColor: '#0d7a4a' })
                    .then(function (r) { return r.isConfirmed; })
                : Promise.resolve(confirm('Muat ulang asesmen? Isian yang belum tersimpan hilang.'));
            lanjut.then(function (ya) { ya && window.IgdTab.muatUlang('tab-asesmen'); });
        }

        function perbaruiKepala(alergi) {
            var tanda = document.querySelector('[data-tanda-belum]');
            tanda && tanda.remove();

            var chips = document.querySelector('.igd-kepala .igd-chips');
            var chip = chips && chips.querySelector('.igd-chip-alergi');
            if (!chips) {
                return;
            }
            if (!alergi) {
                chip && chip.remove();
                return;
            }
            if (!chip) {
                chip = el('li', 'igd-chip igd-chip-alergi');
                chips.appendChild(chip);
            }
            chip.innerHTML = '<i class="fas fa-triangle-exclamation" aria-hidden="true"></i> ';
            chip.appendChild(document.createTextNode('Alergi: ' + alergi));
        }

        function simpan() {
            var data = {};
            new FormData(form).forEach(function (v, k) { data[k] = v; });
            Object.keys(data).forEach(hapusGalat);
            tulisPesan('');
            tombolSimpan.disabled = true;
            tombolSimpan.textContent = 'Menyimpan…';

            fetch(cfg.routes.simpanAsesmen, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(data)
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (json) { return { status: res.status, json: json }; });
            }).then(function (r) {
                if (r.status === 200) {
                    form.querySelector('[name="sidik"]').value = r.json.sidik;
                    form.querySelector('[data-jejak]').innerHTML = '<i class="fas fa-user-doctor" aria-hidden="true"></i> ';
                    form.querySelector('[data-jejak]').appendChild(document.createTextNode(r.json.jejak));
                    var jam = new Date();
                    info.textContent = 'Tersimpan ' + String(jam.getHours()).padStart(2, '0') + '.' + String(jam.getMinutes()).padStart(2, '0');
                    perbaruiKepala(r.json.alergi);
                    toast('success', 'Asesmen tersimpan.');
                } else if (r.status === 422 && r.json.errors) {
                    var n = Object.keys(r.json.errors).length;
                    tampilkanGalat(r.json.errors);
                    tulisPesan(n + ' isian perlu diperbaiki sebelum simpan.', false, 'galat');
                } else if (r.status === 409) {
                    tulisPesan(r.json.message, true);
                } else {
                    tulisPesan((r.json.message || 'Asesmen gagal disimpan') + ' (HTTP ' + r.status + '). Isian tetap di layar; coba simpan lagi.');
                }
            }).catch(function () {
                tulisPesan('Koneksi terputus, asesmen belum tersimpan. Isian tetap di layar; coba simpan lagi.');
            }).finally(function () {
                tombolSimpan.disabled = false;
                tombolSimpan.textContent = 'Simpan asesmen';
            });
        }

        form.addEventListener('change', function (e) {
            if (e.target.name) {
                hapusGalat(e.target.name);
            }
            if (e.target.name === 'anamnesis') {
                perbaruiHubungan();
                hapusGalat('hubungan');
            }
            perbaruiOrgan();
            hitungUlangGalat();
        });
        form.addEventListener('input', function (e) {
            e.target.name && hapusGalat(e.target.name);
            e.target === ketFisik && perbaruiOrgan();
            hitungUlangGalat();
        });
        // Enter di isian satu baris tidak menyimpan; simpan hanya lewat tombol supaya asesmen setengah jadi tidak terkirim.
        form.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.matches('input[type="text"]')) {
                e.preventDefault();
            }
        });
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            simpan();
        });
        tombolNormal.addEventListener('click', function () {
            organ.forEach(function (g) {
                if (!dipilih(g)) {
                    g.querySelector('input[value="Normal"]').checked = true;
                    hapusGalat(g.dataset.kolom);
                }
            });
            perbaruiOrgan();
            hitungUlangGalat();
        });

        perbaruiHubungan();
        perbaruiOrgan();
    }

    document.addEventListener('igd:tab-dimuat', function (e) {
        if (e.detail.panel.id !== 'tab-asesmen') {
            return;
        }
        var form = e.detail.wadah.querySelector('#formAsesmenIgd');
        form && siapkan(form);
    });
})();
