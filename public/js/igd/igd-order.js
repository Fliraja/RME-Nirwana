/* Tab Permintaan IGD: form lab/radiologi, batalkan permintaan, dan angka di label tab. */
(function () {
    'use strict';

    var cfg = window.IGD || {};
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;

    function toast(ikon, teks) {
        if (window.Swal) {
            Swal.fire({ toast: true, position: 'top-end', icon: ikon, title: teks, showConfirmButton: false, timer: ikon === 'success' ? 2500 : 6000 });
        } else {
            alert(teks);
        }
    }

    function kirim(url, metode, data) {
        return fetch(url, {
            method: metode,
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data)
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (json) {
                if (!res.ok) {
                    var pesan = json.message || 'Permintaan gagal diproses (HTTP ' + res.status + ').';
                    if (json.errors) {
                        pesan = Object.values(json.errors)[0][0];
                    }
                    throw new Error(pesan);
                }
                return json;
            });
        });
    }

    function pilihanJarak(el, url, denganTemplate) {
        if (!el || !window.TomSelect) {
            return null;
        }
        return new TomSelect(el, {
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            maxOptions: 50,
            loadThrottle: 300,
            plugins: ['remove_button'],
            shouldLoad: function (q) { return q.length >= 2; },
            load: function (q, cb) {
                var p = new URLSearchParams({ search: q, no_rawat: cfg.noRawat });
                fetch(url + '?' + p.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); }).then(cb).catch(function () { cb(); });
            },
            onItemAdd: denganTemplate,
            onItemRemove: function (nilai) {
                var grup = el.closest('form').querySelector('.igd-template [data-kd="' + CSS.escape(nilai) + '"]');
                grup && grup.remove();
            }
        });
    }

    var BARIS_MAKS = 8;

    function perbaruiHitungan(grup) {
        var semua = grup.querySelectorAll('input[type="checkbox"]');
        var dipilih = grup.querySelectorAll('input[type="checkbox"]:checked').length;
        grup.querySelector('.igd-template-hitung').textContent = dipilih + ' dari ' + semua.length + ' dipilih';
        grup.classList.toggle('igd-template-kosong', dipilih === 0);
    }

    /*
     * Sub-item lab dicentang semua secara bawaan (sama dengan ralan). Paket besar seperti kimia darah
     * sering hanya diambil sebagian, jadi tiap paket punya "Pilih semua" dan "Kosongkan", dan daftar
     * berhenti di BARIS_MAKS baris lalu berlanjut ke kolom di sampingnya.
     */
    function muatTemplate(form, kode, nama) {
        var wadah = form.querySelector('.igd-template');
        fetch(cfg.routes.templateLab + '/' + encodeURIComponent(kode), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (daftar) {
                if (!daftar || !daftar.length) {
                    return;
                }
                var grup = document.createElement('fieldset');
                grup.className = 'igd-template-grup';
                grup.dataset.kd = kode;
                grup.innerHTML =
                    '<legend class="igd-template-judul"><span class="igd-template-nama"></span> <span class="igd-template-hitung"></span></legend>' +
                    '<div class="igd-template-aksi">' +
                    '<button type="button" class="btn btn-sm igd-btn-garis" data-centang="semua">Pilih semua</button>' +
                    '<button type="button" class="btn btn-sm igd-btn-garis" data-centang="kosong">Kosongkan</button>' +
                    '</div>' +
                    '<div class="igd-template-daftar"></div>';
                grup.querySelector('.igd-template-nama').textContent = nama;

                var isi = grup.querySelector('.igd-template-daftar');
                isi.style.setProperty('--baris', Math.min(daftar.length, BARIS_MAKS));
                daftar.forEach(function (t) {
                    var id = 'igd-tpl-' + kode.replace(/[^A-Za-z0-9_-]/g, '') + '-' + t.id_template;
                    var baris = document.createElement('div');
                    baris.className = 'form-check';
                    baris.innerHTML = '<input class="form-check-input" type="checkbox" checked><label class="form-check-label"></label>';
                    var cb = baris.querySelector('input');
                    cb.id = id;
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
                wadah.appendChild(grup);
            })
            .catch(function () { /* tanpa sub-item: seluruh paket tetap dikirim */ });
    }

    function setelahBerubah(pesan) {
        toast('success', pesan);
        window.IgdTab && window.IgdTab.muatUlang('tab-order');
    }

    function pasangForm(form, ts) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var jenis = form.dataset.jenis;
            var kode = ts ? ts.getValue() : [];
            if (!kode.length) {
                toast('error', 'Pilih minimal satu pemeriksaan ' + (jenis === 'lab' ? 'laboratorium' : 'radiologi') + '.');
                ts && ts.focus();
                return;
            }

            var data = {
                no_rawat: cfg.noRawat,
                kd_jenis_prw: kode,
                diagnosa_klinis: form.querySelector('[name="diagnosa_klinis"]').value,
                informasi_tambahan: form.querySelector('[name="informasi_tambahan"]').value
            };
            if (jenis === 'lab') {
                var kosong = form.querySelector('.igd-template-kosong .igd-template-nama');
                if (kosong) {
                    toast('error', 'Pilih minimal satu item untuk ' + kosong.textContent + ', atau hapus pemeriksaannya.');
                    return;
                }
                data.detail_lab = {};
                form.querySelectorAll('.igd-template-grup').forEach(function (g) {
                    data.detail_lab[g.dataset.kd] = Array.from(g.querySelectorAll('input:checked')).map(function (c) { return c.value; });
                });
            }

            var tombol = form.querySelector('[type="submit"]');
            var teksAsli = tombol.textContent;
            tombol.disabled = true;
            tombol.textContent = 'Mengirim…';

            kirim(jenis === 'lab' ? cfg.routes.simpanLab : cfg.routes.simpanRadiologi, 'POST', data)
                .then(function (res) { setelahBerubah(res.message || 'Permintaan terkirim.'); })
                .catch(function (err) {
                    tombol.disabled = false;
                    tombol.textContent = teksAsli;
                    toast('error', err.message);
                });
        });
    }

    function hapus(tombol) {
        var jenis = tombol.dataset.hapusOrder;
        var noorder = tombol.dataset.noorder;
        var lanjut = window.Swal
            ? Swal.fire({
                title: 'Batalkan permintaan ' + (jenis === 'lab' ? 'lab' : 'radiologi') + '?',
                text: 'Permintaan ' + noorder + ' akan dihapus dari daftar penunjang.',
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Tidak',
                confirmButtonColor: '#be123c'
            }).then(function (r) { return r.isConfirmed; })
            : Promise.resolve(confirm('Batalkan permintaan ' + noorder + '?'));

        lanjut.then(function (ya) {
            if (!ya) {
                return;
            }
            tombol.disabled = true;
            kirim(cfg.routes.hapus + '/' + jenis + '/' + encodeURIComponent(noorder), 'DELETE', { no_rawat: cfg.noRawat })
                .then(function (res) { setelahBerubah(res.message || 'Permintaan dibatalkan.'); })
                .catch(function (err) {
                    tombol.disabled = false;
                    toast('error', err.message);
                });
        });
    }

    /* ===== Resep: daftar tunggu item lalu kirim sekali, racikan dengan bahan ===== */

    function escapeHtml(t) {
        return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function renderOpsiBarang(data, escape) {
        if (!data.tingkat) {
            return '<div>' + escape(data.text) + '</div>';
        }
        var ikon = { aman: 'fa-circle-check', tipis: 'fa-triangle-exclamation', habis: 'fa-ban' }[data.tingkat] || '';
        return '<div class="opsi-barang"><span class="opsi-barang-nama">' + escape(data.text) + '</span>' +
            '<span class="stok-badge stok-' + escape(data.tingkat) + '"><i class="fas ' + ikon + '" aria-hidden="true"></i> ' + escape(data.label) + '</span></div>';
    }

    /* onKosong: pilihan dihapus (Backspace / ganti obat), supaya obat lama tidak ikut terkirim dari kotak yang kosong. */
    function pilihanObat(el, onPilih, onKosong) {
        if (!el || !window.TomSelect) {
            return null;
        }
        return new TomSelect(el, {
            valueField: 'id', labelField: 'text', searchField: 'text', maxItems: 1, maxOptions: 50, loadThrottle: 300,
            sortField: [{ field: 'urut', direction: 'asc' }, { field: '$score' }],
            render: { option: renderOpsiBarang },
            shouldLoad: function (q) { return q.length >= 3; },
            load: function (q, cb) {
                fetch(cfg.routes.cariObat + '?' + new URLSearchParams({ search: q }).toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); }).then(cb).catch(function () { cb(); });
            },
            onItemAdd: function (nilai) { onPilih && onPilih(this.options[nilai] || { id: nilai, text: nilai }); },
            onItemRemove: function () { onKosong && onKosong(); }
        });
    }

    function angkaId(n) {
        return String(Math.round(n * 100) / 100).replace('.', ',');
    }

    function pasangResep(form) {
        var ts = pilihanObat(form.querySelector('#igd-select-obat'), function (opsi) {
            dipilih = opsi;
            cekStok();
            form.querySelector('#igd-jumlah-obat').focus();
        }, function () {
            dipilih = null;
            cekStok();
        });
        var dipilih = null;
        var daftar = [];
        var inJumlah = form.querySelector('#igd-jumlah-obat');
        var inAturan = form.querySelector('#igd-aturan-obat');
        var peringatan = form.querySelector('.igd-stok-peringatan');
        var kirimBtn = form.querySelector('[type="submit"]');

        function cekStok() {
            var j = parseFloat(String(inJumlah.value).replace(',', '.')) || 0;
            peringatan.textContent = dipilih && dipilih.stok !== undefined && j > dipilih.stok
                ? 'Jumlah melebihi stok apotek (' + angkaId(dipilih.stok) + ' ' + (dipilih.satuan || '') + '). Tetap bisa dikirim.'
                : '';
        }

        function gambar() {
            var ul = form.querySelector('.igd-tunggu-list');
            ul.innerHTML = daftar.map(function (it, i) {
                return '<li class="igd-item"><span class="igd-item-nama">' + escapeHtml(it.nama) + '</span>' +
                    '<span class="igd-item-jml">' + angkaId(it.jumlah) + ' ' + escapeHtml(it.satuan) + '</span>' +
                    '<span class="igd-item-aturan">' + escapeHtml(it.aturan_pakai) + '</span>' +
                    '<button type="button" class="btn btn-sm igd-btn-hapus" data-buang="' + i + '" aria-label="Keluarkan ' + escapeHtml(it.nama) + ' dari daftar"><i class="fas fa-xmark" aria-hidden="true"></i></button></li>';
            }).join('');
            form.querySelector('[data-tunggu-kosong]').classList.toggle('d-none', daftar.length > 0);
            kirimBtn.disabled = daftar.length === 0;
            kirimBtn.textContent = daftar.length ? 'Kirim resep (' + daftar.length + ' item)' : 'Kirim resep';
        }

        function tambah() {
            var j = parseFloat(String(inJumlah.value).replace(',', '.'));
            if (!dipilih) {
                toast('error', 'Pilih obat atau BHP dulu.');
                ts && ts.focus();
                return;
            }
            if (!(j > 0)) {
                toast('error', 'Jumlah harus lebih dari 0.');
                inJumlah.focus();
                return;
            }
            var item = { kode_obat: dipilih.id, nama: dipilih.text, satuan: dipilih.satuan || '', jumlah: j, aturan_pakai: inAturan.value.trim() };
            var ada = daftar.findIndex(function (x) { return x.kode_obat === item.kode_obat; });
            ada >= 0 ? (daftar[ada] = item) : daftar.push(item);
            gambar();
            dipilih = null;
            if (ts) {
                ts.clear(true);
                ts.clearOptions();
                ts.close();
            }
            inJumlah.value = 1;
            inAturan.value = '';
            peringatan.textContent = '';
            ts && ts.focus();
        }

        inJumlah.addEventListener('input', cekStok);
        [inJumlah, inAturan].forEach(function (el) {
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    tambah();
                }
            });
        });
        form.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-aturan]');
            if (chip) {
                inAturan.value = chip.dataset.aturan;
                inAturan.focus();
            }
            if (e.target.closest('[data-tambah-item]')) {
                tambah();
            }
            var buang = e.target.closest('[data-buang]');
            if (buang) {
                daftar.splice(parseInt(buang.dataset.buang, 10), 1);
                gambar();
            }
        });
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!daftar.length) {
                return;
            }
            kirimBtn.disabled = true;
            kirimBtn.textContent = 'Mengirim…';
            kirim(cfg.routes.simpanObat, 'POST', {
                no_rawat: cfg.noRawat,
                obat: daftar.map(function (x) { return { kode_obat: x.kode_obat, jumlah: x.jumlah, aturan_pakai: x.aturan_pakai }; })
            }).then(function (res) { setelahBerubah(res.message || 'Resep terkirim.'); })
              .catch(function (err) { toast('error', err.message); gambar(); });
        });
    }

    /* Dosis per bungkus: "1/3", "0,5", atau "2". Jumlah bahan = dosis x jumlah racikan (rumus ralan). */
    function bacaDosis(teks) {
        var t = String(teks || '').trim().replace(',', '.');
        var m = t.match(/^(\d+(?:\.\d+)?)\s*\/\s*(\d+(?:\.\d+)?)$/);
        if (m) {
            return { p1: parseFloat(m[1]), p2: parseFloat(m[2]) };
        }
        var n = parseFloat(t);
        return n > 0 && /^\d+(\.\d+)?$/.test(t) ? { p1: n, p2: 1 } : null;
    }

    function pasangRacikan(form) {
        var ul = form.querySelector('.igd-bahan-list');
        var inJml = form.querySelector('#igd-racik-jumlah');
        var urut = 0;

        function hitung(li) {
            var d = bacaDosis(li.querySelector('[data-dosis]').value);
            var j = parseInt(inJml.value, 10) || 0;
            li.querySelector('[data-hasil]').textContent = d && j && li.kode ? '= ' + angkaId(d.p1 / d.p2 * j) + ' ' + (li.satuan || '') : '';
        }

        function tambahBahan() {
            urut++;
            var li = document.createElement('li');
            li.className = 'igd-bahan';
            li.innerHTML = '<div class="igd-bahan-obat"><label class="visually-hidden" for="igd-bahan-' + urut + '">Bahan ' + urut + '</label>' +
                '<select id="igd-bahan-' + urut + '" placeholder="Ketik nama bahan..."></select></div>' +
                '<div class="igd-bahan-dosis"><label class="visually-hidden" for="igd-dosis-' + urut + '">Dosis per bungkus bahan ' + urut + '</label>' +
                '<input type="text" id="igd-dosis-' + urut + '" data-dosis maxlength="10" class="form-control form-control-sm" placeholder="1/3"></div>' +
                '<span class="igd-bahan-hasil" data-hasil></span>' +
                '<button type="button" class="btn btn-sm igd-btn-hapus" data-buang-bahan aria-label="Hapus bahan ' + urut + '"><i class="fas fa-xmark" aria-hidden="true"></i></button>';
            ul.appendChild(li);
            li.ts = pilihanObat(li.querySelector('select'), function (opsi) {
                // Bahan yang sama dua kali ditolak Khanza; arahkan dokter ke baris yang sudah ada.
                var lama = Array.prototype.find.call(ul.querySelectorAll('.igd-bahan'), function (x) {
                    return x !== li && x.kode === opsi.id;
                });
                if (lama) {
                    li.kode = null;
                    li.satuan = '';
                    setTimeout(function () { li.ts.clear(true); });
                    toast('warning', opsi.text + ' sudah ada di daftar bahan. Ubah dosisnya di baris itu.');
                    lama.querySelector('[data-dosis]').focus();
                    hitung(li);
                    return;
                }
                li.kode = opsi.id;
                li.satuan = opsi.satuan || '';
                li.querySelector('[data-dosis]').focus();
                hitung(li);
            }, function () {
                li.kode = null;
                li.satuan = '';
                hitung(li);
            });
            li.querySelector('[data-dosis]').addEventListener('input', function () { hitung(li); });
        }

        inJml.addEventListener('input', function () { ul.querySelectorAll('.igd-bahan').forEach(hitung); });
        form.addEventListener('click', function (e) {
            if (e.target.closest('[data-tambah-bahan]')) {
                tambahBahan();
            }
            var b = e.target.closest('[data-buang-bahan]');
            b && b.closest('.igd-bahan').remove();
        });
        tambahBahan();

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var jml = parseInt(inJml.value, 10);
            var nama = form.querySelector('#igd-racik-nama').value.trim();
            var aturan = form.querySelector('#igd-racik-aturan').value.trim();
            if (!nama || !aturan || !(jml >= 1)) {
                toast('error', 'Isi nama racikan, jumlah, dan aturan pakai.');
                return;
            }
            var detail = [];
            var salah = null;
            ul.querySelectorAll('.igd-bahan').forEach(function (li) {
                var dosisTeks = li.querySelector('[data-dosis]').value.trim();
                var d = bacaDosis(dosisTeks);
                if (!li.kode || !d) {
                    salah = salah || 'Setiap bahan perlu obat dan dosis per bungkus (contoh 1/3 atau 0,5).';
                    return;
                }
                detail.push({ kode_brng: li.kode, p1: d.p1, p2: d.p2, kandungan: dosisTeks, jml: Math.round(d.p1 / d.p2 * jml * 100) / 100 });
            });
            if (salah || !detail.length) {
                toast('error', salah || 'Tambahkan minimal satu bahan.');
                return;
            }
            var btn = form.querySelector('[type="submit"]');
            btn.disabled = true;
            kirim(cfg.routes.simpanRacikan, 'POST', {
                no_rawat: cfg.noRawat, nama_racik: nama, kd_racik: form.querySelector('#igd-racik-metode').value,
                jml_dr: jml, aturan_pakai: aturan, keterangan: '', detail: detail
            }).then(function (res) { setelahBerubah(res.message || 'Racikan terkirim.'); })
              .catch(function (err) { btn.disabled = false; toast('error', err.message); });
        });
    }

    function hapusResep(tombol) {
        var jenis = tombol.dataset.hapusResep;
        var lanjut = window.Swal
            ? Swal.fire({ title: jenis === 'racikan' ? 'Hapus racikan dari resep?' : 'Hapus item dari resep?', icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Tidak', confirmButtonColor: '#be123c' })
                .then(function (r) { return r.isConfirmed; })
            : Promise.resolve(confirm('Hapus dari resep?'));
        lanjut.then(function (ya) {
            if (!ya) {
                return;
            }
            tombol.disabled = true;
            kirim(cfg.routes.hapusResep + '/' + encodeURIComponent(tombol.dataset.noResep) + '/' + jenis + '/' + encodeURIComponent(tombol.dataset.kode), 'DELETE', { no_rawat: cfg.noRawat })
                .then(function (res) { setelahBerubah(res.message || 'Dihapus dari resep.'); })
                .catch(function (err) { tombol.disabled = false; toast('error', err.message); });
        });
    }

    document.addEventListener('igd:tab-dimuat', function (e) {
        if (e.detail.panel.id !== 'tab-order') {
            return;
        }
        var akar = e.detail.wadah.querySelector('.igd-permintaan');
        if (!akar) {
            return;
        }

        var label = document.querySelector('[data-jumlah-tab]');
        if (label) {
            var n = parseInt(akar.dataset.jumlah || '0', 10);
            label.textContent = n;
            label.classList.toggle('d-none', n === 0);
        }

        var formLab = akar.querySelector('#igd-form-lab');
        if (formLab) {
            var tsLab = pilihanJarak(formLab.querySelector('select'), cfg.routes.cariLab, function (nilai) {
                var opsi = this.options[nilai];
                muatTemplate(formLab, nilai, opsi ? opsi.text : nilai);
            });
            pasangForm(formLab, tsLab);
        }
        var formRad = akar.querySelector('#igd-form-radiologi');
        if (formRad) {
            pasangForm(formRad, pilihanJarak(formRad.querySelector('select'), cfg.routes.cariRadiologi));
        }

        var formResep = akar.querySelector('#igd-form-resep');
        formResep && pasangResep(formResep);
        var formRacik = akar.querySelector('#igd-form-racikan');
        formRacik && pasangRacikan(formRacik);

        akar.addEventListener('click', function (ev) {
            var t = ev.target.closest('[data-hapus-order]');
            t && hapus(t);
            var r = ev.target.closest('[data-hapus-resep]');
            r && hapusResep(r);
        });
    });
})();
