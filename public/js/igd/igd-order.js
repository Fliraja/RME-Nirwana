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

        akar.addEventListener('click', function (ev) {
            var t = ev.target.closest('[data-hapus-order]');
            t && hapus(t);
        });
    });
})();
