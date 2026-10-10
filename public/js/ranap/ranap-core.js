/* Ranap Core JS - Sistem Rekam Medis Elektronik Rawat Inap Nirwana
 * Mengelola pemuatan asynchronous seluruh tab (SOAP, TTV, SBAR, Diagnosa, Resep, Lab, Radiologi)
 * Berjalan modular tanpa full page reload.
 */

let rowCount = 0;

function ranapUrl(path) {
    const base = (window.RANAP && window.RANAP.baseUrl) ? window.RANAP.baseUrl.replace(/\/+$/, '') : '';
    return base + (path.startsWith('/') ? path : '/' + path);
}

function tampilkanError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: message
        });
    } else {
        alert('Gagal: ' + message);
    }
}

function tampilkanSukses(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: message,
            timer: 1500,
            showConfirmButton: false
        });
    } else {
        alert('Berhasil: ' + message);
    }
}

/* ===== TomSelect Remote Helper ===== */
function initRemoteSelect(selector, opts) {
    var el = document.querySelector(selector);
    if (!el) return null;
    if (el.tomselect) el.tomselect.destroy();
    var minLen = opts.minLen || 2;
    return new TomSelect(el, {
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        maxItems: opts.multiple ? null : 1,
        maxOptions: 50,
        loadThrottle: 200,
        placeholder: opts.placeholder || 'Ketik untuk mencari...',
        plugins: opts.multiple ? ['remove_button'] : [],
        load: function(query, callback) {
            if (query.length < minLen) return callback();
            var params = new URLSearchParams({ search: query });
            if (opts.withNoRawat) params.append('no_rawat', currentNoRawat);
            fetch(opts.url + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(json) { callback(json); })
                .catch(function() { callback(); });
        },
        shouldLoad: function(q) { return q.length >= minLen; },
        onItemAdd: function(value, $item) {
            if (typeof opts.onItemAdd === 'function') {
                var data = this.options[value] || { id: value, text: value };
                opts.onItemAdd(data, this);
            }
        }
    });
}

function clearRemoteSelect(selector) {
    var el = document.querySelector(selector);
    if (el && el.tomselect) el.tomselect.clear();
}

/* ===== Tab State Management ===== */
var loadedTabs = {
    riwayat: true,
    soap: false,
    vital: false,
    diagnosa: false,
    resep: false,
    lab: false,
    radiologi: false,
    resume: false
};

var loadingTabs = {
    riwayat: false,
    soap: false,
    vital: false,
    diagnosa: false,
    resep: false,
    lab: false,
    radiologi: false,
    resume: false
};

/* ===== 1. Tab SOAP CPPT ===== */
function loadSoap(forceReload = false) {
    if (!currentNoRawat) return;
    if (loadedTabs.soap && !forceReload) return;
    if (loadingTabs.soap) return;

    if (!loadedTabs.soap) {
        $('#content-soap').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat Catatan SOAP Ranap...</p></div>');
    }

    loadingTabs.soap = true;
    var url = ranapUrl('/ranap/soap/' + currentSafeNoRawat);
    $.get(url, function(data) {
        $('#content-soap').html(data);
        loadedTabs.soap = true;
        loadingTabs.soap = false;
        initSoapHandlers();
    }).fail(function(xhr) {
        loadingTabs.soap = false;
        if (!loadedTabs.soap) $('#content-soap').html('<div class="alert alert-danger">Gagal memuat form SOAP (HTTP ' + xhr.status + ').</div>');
    });
}

function initSoapHandlers() {
    $('#formSoapRanap').off('submit').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnSimpanSoap');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

        $.ajax({
            url: ranapUrl('/ranap/soap/simpan'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan SOAP');
                tampilkanSukses(res.message || 'Catatan SOAP berhasil disimpan.');
                resetFormSoap();
                loadSoap(true);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan SOAP');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.';
                tampilkanError(err);
            }
        });
    });

    // Klik badan tabel untuk langsung mengisi form input (memudahkan entri CPPT)
    $('#tableRiwayatSoap tbody').off('click', 'tr.soap-row-item').on('click', 'tr.soap-row-item', function(e) {
        if ($(e.target).closest('button, .btn, a').length) {
            return;
        }
        var soapData = $(this).data('soap');
        if (soapData) {
            salinSoapKeForm(soapData, this);
        }
    });
}

function salinSoapKeForm(soap, rowEl) {
    $('#soap_keluhan').val(soap.keluhan || '');
    $('#soap_pemeriksaan').val(soap.pemeriksaan || '');
    $('#soap_penilaian').val(soap.penilaian || '');
    $('#soap_rtl').val(soap.rtl || '');
    $('#soap_instruksi').val(soap.instruksi || '');
    $('#soap_evaluasi').val(soap.evaluasi || '');

    // Tetap mode tambah baru (CPPT baru dengan tgl & jam sekarang)
    $('#mode_edit_soap').val('0');
    $('#tgl_perawatan_edit_soap').val('');
    $('#jam_rawat_edit_soap').val('');

    var tglJam = (soap.tgl_perawatan || '') + ' ' + (soap.jam_rawat || '');
    $('#titleFormSoap').html('<i class="fas fa-copy me-1 text-primary"></i> Form Catatan SOAP (Salin dari ' + tglJam + ')');
    $('#badgeModeSoap').removeClass('bg-warning text-dark bg-light text-muted').addClass('bg-primary text-white').text('Mode: Salin ke Baru');
    $('#btnBatalEditSoap').removeClass('d-none').html('<i class="fas fa-undo me-1"></i> Bersihkan Form');
    $('#btnSimpanSoap').removeClass('btn-warning').addClass('btn-primary').html('<i class="fas fa-save me-1"></i> Simpan SOAP Baru');

    $('#tableRiwayatSoap tbody tr').removeClass('table-primary table-warning');
    if (rowEl) {
        $(rowEl).addClass('table-primary');
    }

    $('html, body').animate({ scrollTop: $('#titleFormSoap').offset().top - 100 }, 300);

    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        });
        Toast.fire({
            icon: 'info',
            title: 'Data SOAP disalin ke form input'
        });
    }
}

function editSoap(soap) {
    $('#mode_edit_soap').val('1');
    $('#tgl_perawatan_edit_soap').val(soap.tgl_perawatan);
    $('#jam_rawat_edit_soap').val(soap.jam_rawat);

    $('#soap_keluhan').val(soap.keluhan || '');
    $('#soap_pemeriksaan').val(soap.pemeriksaan || '');
    $('#soap_penilaian').val(soap.penilaian || '');
    $('#soap_rtl').val(soap.rtl || '');
    $('#soap_instruksi').val(soap.instruksi || '');
    $('#soap_evaluasi').val(soap.evaluasi || '');

    $('#titleFormSoap').html('<i class="fas fa-edit me-1 text-warning"></i> Edit Catatan SOAP (' + soap.tgl_perawatan + ' ' + soap.jam_rawat + ')');
    $('#badgeModeSoap').removeClass('bg-light text-muted bg-primary text-white').addClass('bg-warning text-dark').text('Mode: Edit');
    $('#btnBatalEditSoap').removeClass('d-none').html('<i class="fas fa-times me-1"></i> Batal Edit');
    $('#btnSimpanSoap').removeClass('btn-primary').addClass('btn-warning').html('<i class="fas fa-sync-alt me-1"></i> Perbarui Catatan SOAP');

    $('#tableRiwayatSoap tbody tr').removeClass('table-primary table-warning');
    var safeTgl = (soap.tgl_perawatan || '').replace(/-/g, '');
    var safeJam = (soap.jam_rawat || '').replace(/:/g, '');
    $('#row-soap-' + safeTgl + '-' + safeJam).addClass('table-warning');

    $('html, body').animate({ scrollTop: $('#titleFormSoap').offset().top - 100 }, 300);
}

function resetFormSoap() {
    $('#mode_edit_soap').val('0');
    $('#tgl_perawatan_edit_soap').val('');
    $('#jam_rawat_edit_soap').val('');
    $('#formSoapRanap')[0].reset();
    $('#tableRiwayatSoap tbody tr').removeClass('table-primary table-warning');

    $('#titleFormSoap').html('<i class="fas fa-notes-medical me-2 text-primary"></i>Catatan Perkembangan Pasien Terintegrasi (CPPT / SOAP)');
    $('#badgeModeSoap').removeClass('bg-warning text-dark bg-primary text-white').addClass('bg-light text-muted').text('Mode: Tambah Baru');
    $('#btnBatalEditSoap').addClass('d-none').html('<i class="fas fa-times me-1"></i> Batal Edit');
    $('#btnSimpanSoap').removeClass('btn-warning').addClass('btn-primary').html('<i class="fas fa-save me-1"></i> Simpan SOAP');
}

function hapusSoap(tgl, jam) {
    Swal.fire({
        title: 'Hapus Catatan SOAP?',
        text: 'Catatan SOAP pada ' + tgl + ' ' + jam + ' akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: ranapUrl('/ranap/soap/hapus'),
                type: 'POST',
                data: {
                    _token: window.RANAP.csrfToken,
                    no_rawat: currentNoRawat,
                    tgl: tgl,
                    jam: jam
                },
                success: function(res) {
                    if (res.status === 'success') {
                        tampilkanSukses(res.message);
                        loadSoap(true);
                    } else {
                        tampilkanError(res.message);
                    }
                },
                error: function(xhr) {
                    tampilkanError('Gagal menghapus data.');
                }
            });
        }
    });
}

/* ===== 2. Tab TTV & SBAR ===== */
function loadVital(forceReload = false) {
    if (!currentNoRawat) return;
    if (loadedTabs.vital && !forceReload) return;
    if (loadingTabs.vital) return;

    if (!loadedTabs.vital) {
        $('#content-vital-sign').html('<div class="text-center p-5"><div class="spinner-border text-danger"></div><p class="mt-2 text-muted">Memuat Form TTV & Catatan SBAR...</p></div>');
    }

    loadingTabs.vital = true;
    var url = ranapUrl('/ranap/get-vital-pasien/' + currentSafeNoRawat);
    $.get(url, function(data) {
        $('#content-vital-sign').html(data);
        loadedTabs.vital = true;
        loadingTabs.vital = false;
        initTtvHandlers();
    }).fail(function(xhr) {
        loadingTabs.vital = false;
        if (!loadedTabs.vital) $('#content-vital-sign').html('<div class="alert alert-danger">Gagal memuat form TTV (HTTP ' + xhr.status + ').</div>');
    });
}

function initTtvHandlers() {
    $('#formTtvRanap').off('submit').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnSimpanTtv');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

        $.ajax({
            url: ranapUrl('/ranap/store-vital'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan TTV & SBAR');
                tampilkanSukses(res.message || 'Data TTV & SBAR berhasil disimpan.');
                resetFormTtv();
                loadVital(true);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan TTV & SBAR');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.';
                tampilkanError(err);
            }
        });
    });

    // Klik badan tabel untuk langsung mengisi form input (memudahkan entri TTV)
    $('#tableRiwayatTtv tbody').off('click', 'tr.ttv-row-item').on('click', 'tr.ttv-row-item', function(e) {
        if ($(e.target).closest('button, .btn, a').length) {
            return;
        }
        var ttvData = $(this).data('ttv');
        if (ttvData) {
            salinTtvKeForm(ttvData, this);
        }
    });
}

function salinTtvKeForm(ttv, rowEl) {
    $('#ttv_suhu_tubuh').val(ttv.suhu_tubuh || '');
    $('#ttv_tensi').val(ttv.tensi || '');
    $('#ttv_nadi').val(ttv.nadi || '');
    $('#ttv_respirasi').val(ttv.respirasi || '');
    $('#ttv_spo2').val(ttv.spo2 || '');
    $('#ttv_gcs').val(ttv.gcs || '');
    $('#ttv_tinggi').val(ttv.tinggi || '');
    $('#ttv_berat').val(ttv.berat || '');
    $('#ttv_kesadaran').val(ttv.kesadaran || 'Compos Mentis');
    $('#ttv_alergi').val(ttv.alergi || '');
    $('#ttv_sbar').val(ttv.sbar || '');

    // Tetap mode tambah baru (pencatatan TTV baru)
    $('#mode_edit_ttv').val('0');
    $('#tgl_perawatan_edit_ttv').val('');
    $('#jam_rawat_edit_ttv').val('');

    var tglJam = (ttv.tgl_perawatan || '') + ' ' + (ttv.jam_rawat || '');
    $('#titleFormTtv').html('<i class="fas fa-copy me-1 text-primary"></i> Form TTV & SBAR (Salin dari ' + tglJam + ')');
    $('#badgeModeTtv').removeClass('bg-warning text-dark bg-light text-muted').addClass('bg-primary text-white').text('Mode: Salin ke Baru');
    $('#btnBatalEditTtv').removeClass('d-none').html('<i class="fas fa-undo me-1"></i> Bersihkan Form');
    $('#btnSimpanTtv').removeClass('btn-warning text-dark').addClass('btn-primary').html('<i class="fas fa-save me-1"></i> Simpan TTV & SBAR');

    $('#tableRiwayatTtv tbody tr').removeClass('table-primary table-warning');
    if (rowEl) {
        $(rowEl).addClass('table-primary');
    }

    $('html, body').animate({ scrollTop: $('#titleFormTtv').offset().top - 100 }, 300);

    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        });
        Toast.fire({
            icon: 'info',
            title: 'Data TTV disalin ke form input'
        });
    }
}

function editTtv(ttv) {
    $('#mode_edit_ttv').val('1');
    $('#tgl_perawatan_edit_ttv').val(ttv.tgl_perawatan);
    $('#jam_rawat_edit_ttv').val(ttv.jam_rawat);

    $('#ttv_suhu_tubuh').val(ttv.suhu_tubuh || '');
    $('#ttv_tensi').val(ttv.tensi || '');
    $('#ttv_nadi').val(ttv.nadi || '');
    $('#ttv_respirasi').val(ttv.respirasi || '');
    $('#ttv_spo2').val(ttv.spo2 || '');
    $('#ttv_gcs').val(ttv.gcs || '');
    $('#ttv_tinggi').val(ttv.tinggi || '');
    $('#ttv_berat').val(ttv.berat || '');
    $('#ttv_kesadaran').val(ttv.kesadaran || 'Compos Mentis');
    $('#ttv_alergi').val(ttv.alergi || '');
    $('#ttv_sbar').val(ttv.sbar || '');

    $('#titleFormTtv').html('<i class="fas fa-edit me-1 text-warning"></i> Edit TTV & SBAR (' + ttv.tgl_perawatan + ' ' + ttv.jam_rawat + ')');
    $('#badgeModeTtv').removeClass('bg-light text-muted bg-primary text-white').addClass('bg-warning text-dark').text('Mode: Edit');
    $('#btnBatalEditTtv').removeClass('d-none').html('<i class="fas fa-times me-1"></i> Batal Edit');
    $('#btnSimpanTtv').removeClass('btn-primary btn-danger').addClass('btn-warning text-dark').html('<i class="fas fa-sync-alt me-1"></i> Perbarui TTV & SBAR');

    $('#tableRiwayatTtv tbody tr').removeClass('table-primary table-warning');
    var rowId = '#row-ttv-' + (ttv.tgl_perawatan || '').replace(/-/g, '') + '-' + (ttv.jam_rawat || '').replace(/:/g, '');
    $(rowId).addClass('table-warning');

    $('html, body').animate({ scrollTop: $('#titleFormTtv').offset().top - 100 }, 300);
}

function resetFormTtv() {
    $('#mode_edit_ttv').val('0');
    $('#tgl_perawatan_edit_ttv').val('');
    $('#jam_rawat_edit_ttv').val('');
    $('#formTtvRanap')[0].reset();

    $('#titleFormTtv').html('<i class="fas fa-heartbeat me-2 text-primary"></i>Form Tanda-Tanda Vital (TTV) & Catatan SBAR');
    $('#badgeModeTtv').removeClass('bg-warning bg-primary text-white text-dark').addClass('bg-light text-muted').text('Mode: Tambah Baru');
    $('#btnBatalEditTtv').addClass('d-none').html('<i class="fas fa-times me-1"></i> Batal Edit');
    $('#btnSimpanTtv').removeClass('btn-warning text-dark btn-danger').addClass('btn-primary').html('<i class="fas fa-save me-1"></i> Simpan TTV & SBAR');
    $('#tableRiwayatTtv tbody tr').removeClass('table-primary table-warning');
}

function hapusTtv(tgl, jam) {
    Swal.fire({
        title: 'Hapus Data TTV & SBAR?',
        text: 'Data observasi vital sign ini akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: ranapUrl('/ranap/vital/hapus'),
                type: 'POST',
                data: {
                    _token: window.RANAP.csrfToken,
                    no_rawat: currentNoRawat,
                    tgl: tgl,
                    jam: jam
                },
                success: function(res) {
                    if (res.status === 'success') {
                        tampilkanSukses(res.message);
                        loadVital(true);
                    } else {
                        tampilkanError(res.message);
                    }
                },
                error: function(xhr) {
                    tampilkanError('Gagal menghapus data.');
                }
            });
        }
    });
}

/* ===== 3. Tab Diagnosa & Prosedur ===== */
function loadDiagnosa(forceReload = false) {
    if (!currentNoRawat) return;
    if (loadedTabs.diagnosa && !forceReload) return;
    if (loadingTabs.diagnosa) return;

    if (!loadedTabs.diagnosa) {
        $('#content-diagnosa-prosedur').html('<div class="text-center p-5"><div class="spinner-border text-info"></div><p class="mt-2 text-muted">Memuat Diagnosa & Prosedur...</p></div>');
    }

    loadingTabs.diagnosa = true;
    var url = ranapUrl('/ranap/get-diagnosa-prosedur/' + currentSafeNoRawat);
    $.get(url, function(data) {
        $('#content-diagnosa-prosedur').html(data);
        loadedTabs.diagnosa = true;
        loadingTabs.diagnosa = false;
        initDiagnosaHandlers();
    }).fail(function(xhr) {
        loadingTabs.diagnosa = false;
        if (!loadedTabs.diagnosa) $('#content-diagnosa-prosedur').html('<div class="alert alert-danger">Gagal memuat diagnosa (HTTP ' + xhr.status + ').</div>');
    });
}

function initDiagnosaHandlers() {
    var tsDiagnosa = initRemoteSelect('#select-icd10', {
        url: ranapUrl('/ralan/search-icd10'),
        placeholder: 'Ketik kode / nama ICD-10...',
        onItemAdd: function(item, ts) {
            var parts = (item.text || '').split(' - ');
            var kode = parts[0] || item.id;
            var nama = parts.slice(1).join(' - ') || kode;

            if ($('#staging-diagnosa tr[data-kd="' + kode + '"]').length > 0) {
                tampilkanError('Penyakit ini sudah ada di daftar staging.');
                ts.clear();
                return;
            }

            $('#staging-diagnosa .staging-empty').remove();
            var row = '<tr data-kd="' + kode + '">' +
                '<td><span class="badge bg-light text-dark border">' + kode + '</span><input type="hidden" name="kd_penyakit[]" value="' + kode + '"></td>' +
                '<td class="small">' + nama + '</td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 btn-remove-row"><i class="fas fa-times"></i></button></td>' +
                '</tr>';
            $('#staging-diagnosa').append(row);
            ts.clear();
        }
    });

    var tsProsedur = initRemoteSelect('#select-icd9', {
        url: ranapUrl('/ralan/search-icd9'),
        placeholder: 'Ketik kode / nama ICD-9...',
        onItemAdd: function(item, ts) {
            var parts = (item.text || '').split(' - ');
            var kode = parts[0] || item.id;
            var nama = parts.slice(1).join(' - ') || kode;

            if ($('#staging-prosedur tr[data-kd="' + kode + '"]').length > 0) {
                tampilkanError('Prosedur ini sudah ada di daftar staging.');
                ts.clear();
                return;
            }

            $('#staging-prosedur .staging-empty').remove();
            var row = '<tr data-kd="' + kode + '">' +
                '<td><span class="badge bg-light text-dark border">' + kode + '</span><input type="hidden" name="kode[]" value="' + kode + '"></td>' +
                '<td class="small">' + nama + '</td>' +
                '<td><input type="number" name="jumlah[]" class="form-control form-control-sm text-center" value="1" min="1"></td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 btn-remove-row"><i class="fas fa-times"></i></button></td>' +
                '</tr>';
            $('#staging-prosedur').append(row);
            ts.clear();
        }
    });

    $(document).off('click', '.btn-remove-row').on('click', '.btn-remove-row', function() {
        var $tbody = $(this).closest('tbody');
        $(this).closest('tr').remove();
        if ($tbody.children('tr').length === 0) {
            var cols = $tbody.data('cols') || 3;
            var emptyTxt = $tbody.data('empty') || 'Belum ada data';
            $tbody.html('<tr class="staging-empty"><td colspan="' + cols + '" class="text-center text-muted small py-2">' + emptyTxt + '</td></tr>');
        }
    });

    $('#btn-simpan-diagnosa').off('click').on('click', function() {
        var kdList = [];
        $('#staging-diagnosa input[name="kd_penyakit[]"]').each(function() {
            kdList.push($(this).val());
        });

        if (kdList.length === 0) {
            tampilkanError('Pilih minimal 1 diagnosa sebelum menyimpan.');
            return;
        }

        $.ajax({
            url: ranapUrl('/ralan/store-diagnosa'),
            type: 'POST',
            data: {
                _token: window.RANAP.csrfToken,
                no_rawat: currentNoRawat,
                kd_penyakit: kdList
            },
            success: function(res) {
                tampilkanSukses(res.message);
                loadDiagnosa(true);
            },
            error: function(xhr) {
                tampilkanError(xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menyimpan diagnosa.');
            }
        });
    });

    $('#btn-simpan-prosedur').off('click').on('click', function() {
        var kodeList = [];
        var jumlahList = [];
        $('#staging-prosedur input[name="kode[]"]').each(function() {
            kodeList.push($(this).val());
        });
        $('#staging-prosedur input[name="jumlah[]"]').each(function() {
            jumlahList.push($(this).val());
        });

        if (kodeList.length === 0) {
            tampilkanError('Pilih minimal 1 prosedur sebelum menyimpan.');
            return;
        }

        $.ajax({
            url: ranapUrl('/ralan/store-prosedur'),
            type: 'POST',
            data: {
                _token: window.RANAP.csrfToken,
                no_rawat: currentNoRawat,
                kode: kodeList,
                jumlah: jumlahList
            },
            success: function(res) {
                tampilkanSukses(res.message);
                loadDiagnosa(true);
            },
            error: function(xhr) {
                tampilkanError(xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menyimpan prosedur.');
            }
        });
    });

    $(document).off('click', '.btn-hapus-diagnosa').on('click', '.btn-hapus-diagnosa', function() {
        var kd = $(this).data('kd');
        Swal.fire({
            title: 'Hapus Diagnosa?',
            text: 'Diagnosa ' + kd + ' akan dihapus dari rekam rawat inap ini.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: ranapUrl('/ralan/delete-diagnosa/' + currentSafeNoRawat + '/' + kd),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadDiagnosa(true);
                    }
                });
            }
        });
    });

    $(document).off('click', '.btn-hapus-prosedur').on('click', '.btn-hapus-prosedur', function() {
        var kd = $(this).data('kd');
        Swal.fire({
            title: 'Hapus Prosedur?',
            text: 'Prosedur ' + kd + ' akan dihapus dari rekam rawat inap ini.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: ranapUrl('/ralan/delete-prosedur/' + currentSafeNoRawat + '/' + kd),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadDiagnosa(true);
                    }
                });
            }
        });
    });
}

/* ===== 4. Tab Resep & Obat ===== */
function loadResep(forceReload = false) {
    if (!currentNoRawat) return;
    if (loadedTabs.resep && !forceReload) return;
    if (loadingTabs.resep) return;

    if (!loadedTabs.resep) {
        $('#content-resep').html('<div class="text-center p-5"><div class="spinner-border text-warning"></div><p class="mt-2 text-muted">Memuat Form Peresepan & Riwayat Obat...</p></div>');
    }

    loadingTabs.resep = true;
    var url = ranapUrl('/ranap/get-resep-pasien/' + currentSafeNoRawat);
    $.get(url, function(data) {
        $('#content-resep').html(data);
        loadedTabs.resep = true;
        loadingTabs.resep = false;
        initResepHandlers();
    }).fail(function(xhr) {
        loadingTabs.resep = false;
        if (!loadedTabs.resep) $('#content-resep').html('<div class="alert alert-danger">Gagal memuat resep (HTTP ' + xhr.status + ').</div>');
    });
}

function initResepHandlers() {
    var tsObat = initRemoteSelect('#formResepObat .kd_obat_ajax', {
        url: ranapUrl('/ralan/search-obat'),
        placeholder: 'Ketik nama obat / kode...'
    });

    var tsBahanRacik = initRemoteSelect('#racik_kode_brng', {
        url: ranapUrl('/ralan/search-obat'),
        placeholder: 'Ketik nama bahan obat...'
    });

    $('#btnTambahObat').off('click').on('click', function() {
        if (!tsObat || !tsObat.getValue()) {
            tampilkanError('Pilih obat terlebih dahulu.');
            return;
        }
        var kode = tsObat.getValue();
        var nama = tsObat.options[kode] ? tsObat.options[kode].text : kode;
        var jumlah = $('#formResepObat input[name="jumlah"]').val();
        var aturan = $('#formResepObat select[name="aturan_pakai"]').val();

        if (!jumlah || jumlah <= 0) {
            tampilkanError('Jumlah obat minimal 1.');
            return;
        }
        if (!aturan) {
            tampilkanError('Pilih aturan pakai obat.');
            return;
        }

        $('#staging-obat .staging-empty').remove();
        var row = '<tr>' +
            '<td><span class="fw-semibold">' + nama + '</span><input type="hidden" class="stg-kode" value="' + kode + '"></td>' +
            '<td><input type="number" class="form-control form-control-sm stg-jml" value="' + jumlah + '" min="1"></td>' +
            '<td><input type="text" class="form-control form-control-sm stg-aturan" value="' + aturan + '"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 btn-remove-row"><i class="fas fa-times"></i></button></td>' +
            '</tr>';
        $('#staging-obat').append(row);
        tsObat.clear();
        $('#formResepObat input[name="jumlah"]').val(1);
    });

    $('#btnSimpanResepObat').off('click').on('click', function() {
        var obatList = [];
        $('#staging-obat tr:not(.staging-empty)').each(function() {
            obatList.push({
                kode_obat: $(this).find('.stg-kode').val(),
                jumlah: $(this).find('.stg-jml').val(),
                aturan_pakai: $(this).find('.stg-aturan').val()
            });
        });

        if (obatList.length === 0) {
            tampilkanError('Belum ada obat yang ditambahkan ke staging.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

        $.ajax({
            url: ranapUrl('/ralan/store-resep-obat'),
            type: 'POST',
            data: {
                _token: window.RANAP.csrfToken,
                no_rawat: currentNoRawat,
                obat: obatList
            },
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Resep Obat Ranap');
                tampilkanSukses(res.message);
                loadResep(true);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Resep Obat Ranap');
                tampilkanError(xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menyimpan resep.');
            }
        });
    });

    $('select[name="aturan_racik"]').on('change', function() {
        if ($(this).val() === 'lainnya') {
            $('input[name="aturan_racik_lainnya"]').removeClass('d-none').focus();
        } else {
            $('input[name="aturan_racik_lainnya"]').addClass('d-none').val('');
        }
    });

    $('#btnTambahBahanRacik').off('click').on('click', function() {
        if (!tsBahanRacik || !tsBahanRacik.getValue()) {
            tampilkanError('Pilih bahan obat racikan.');
            return;
        }
        var kode = tsBahanRacik.getValue();
        var nama = tsBahanRacik.options[kode] ? tsBahanRacik.options[kode].text : kode;
        var p1 = $('#racik_p1').val() || 1;
        var p2 = $('#racik_p2').val() || 1;
        var kandungan = $('#racik_kandungan').val() || '-';
        var jml = $('#racik_jml').val() || 1;

        $('#staging-bahan-racik .staging-empty').remove();
        var row = '<tr>' +
            '<td><span class="fw-semibold">' + nama + '</span><input type="hidden" class="racik-stg-kode" value="' + kode + '"></td>' +
            '<td class="text-center">' + p1 + '/' + p2 + '<input type="hidden" class="racik-stg-p1" value="' + p1 + '"><input type="hidden" class="racik-stg-p2" value="' + p2 + '"></td>' +
            '<td><input type="text" class="form-control form-control-sm racik-stg-kandungan" value="' + kandungan + '"></td>' +
            '<td><input type="number" class="form-control form-control-sm racik-stg-jml" value="' + jml + '" step="any"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 btn-remove-row"><i class="fas fa-times"></i></button></td>' +
            '</tr>';
        $('#staging-bahan-racik').append(row);
        tsBahanRacik.clear();
        $('#racik_kandungan').val('');
        $('#racik_jml').val(1);
    });

    $('#btnSimpanResepRacikan').off('click').on('click', function() {
        var namaRacik = $('input[name="nama_racik"]').val();
        var kdRacik = $('select[name="kd_racik"]').val();
        var jmlDr = $('input[name="jml_dr"]').val();
        var aturanRacik = $('select[name="aturan_racik"]').val();
        var aturanLainnya = $('input[name="aturan_racik_lainnya"]').val();
        var keterangan = $('input[name="keterangan"]').val();

        if (!namaRacik) {
            tampilkanError('Nama racikan wajib diisi.');
            return;
        }
        if (!aturanRacik) {
            tampilkanError('Aturan pakai racikan wajib diisi.');
            return;
        }

        var detailBahan = [];
        $('#staging-bahan-racik tr:not(.staging-empty)').each(function() {
            detailBahan.push({
                kode_brng: $(this).find('.racik-stg-kode').val(),
                p1: $(this).find('.racik-stg-p1').val(),
                p2: $(this).find('.racik-stg-p2').val(),
                kandungan: $(this).find('.racik-stg-kandungan').val(),
                jml: $(this).find('.racik-stg-jml').val()
            });
        });

        if (detailBahan.length === 0) {
            tampilkanError('Tambahkan minimal 1 bahan obat ke dalam racikan.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

        $.ajax({
            url: ranapUrl('/ralan/store-resep-racikan'),
            type: 'POST',
            data: {
                _token: window.RANAP.csrfToken,
                no_rawat: currentNoRawat,
                nama_racik: namaRacik,
                kd_racik: kdRacik,
                jml_dr: jmlDr,
                aturan_racik: aturanRacik,
                aturan_racik_lainnya: aturanLainnya,
                keterangan: keterangan,
                detail_obat: detailBahan
            },
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Resep Racikan Ranap');
                tampilkanSukses(res.message);
                loadResep(true);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Resep Racikan Ranap');
                tampilkanError(xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menyimpan racikan.');
            }
        });
    });

    $(document).off('click', '.btn-hapus-resep-obat').on('click', '.btn-hapus-resep-obat', function() {
        var noResep = $(this).data('noresep');
        var kodeBrng = $(this).data('kode');
        Swal.fire({
            title: 'Hapus Item Obat?',
            text: 'Obat ini akan dihapus dari resep antrean farmasi.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: ranapUrl('/ralan/delete-resep-obat/' + noResep + '/' + kodeBrng),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadResep(true);
                    }
                });
            }
        });
    });

    $(document).off('click', '.btn-hapus-racikan').on('click', '.btn-hapus-racikan', function() {
        var noResep = $(this).data('noresep');
        var noRacik = $(this).data('noracik');
        Swal.fire({
            title: 'Hapus Racikan?',
            text: 'Seluruh resep racikan ini akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: ranapUrl('/ralan/delete-resep-racikan/' + noResep + '/' + noRacik),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadResep(true);
                    }
                });
            }
        });
    });
}

/* ===== 5. Tab Laboratorium ===== */
function loadLab(forceReload = false) {
    if (!currentNoRawat) return;
    if (loadedTabs.lab && !forceReload) return;
    if (loadingTabs.lab) return;

    if (!loadedTabs.lab) {
        $('#content-lab').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat Form Permintaan & Hasil Lab...</p></div>');
    }

    loadingTabs.lab = true;
    var url = ranapUrl('/ranap/get-lab-pasien/' + currentSafeNoRawat);
    $.get(url, function(data) {
        $('#content-lab').html(data);
        loadedTabs.lab = true;
        loadingTabs.lab = false;
        initLabHandlers();
    }).fail(function(xhr) {
        loadingTabs.lab = false;
        if (!loadedTabs.lab) $('#content-lab').html('<div class="alert alert-danger">Gagal memuat lab (HTTP ' + xhr.status + ').</div>');
    });
}

function initLabHandlers() {
    var tsLab = initRemoteSelect('#select-lab', {
        url: ranapUrl('/ralan/search-pemeriksaan-lab'),
        placeholder: 'Pilih pemeriksaan laboratorium ranap...',
        multiple: true,
        withNoRawat: true,
        onItemAdd: function(item) {
            $('#detail-pemeriksaan-placeholder').addClass('d-none');
            $.get(ranapUrl('/ralan/get-templates-lab/' + item.id), function(templates) {
                if (templates && templates.length > 0) {
                    var html = '<div class="mb-3 border-bottom pb-2 item-template-group" data-kd="' + item.id + '">';
                    html += '<div class="fw-bold small text-primary mb-1">' + item.text + '</div>';
                    templates.forEach(function(t) {
                        html += '<div class="form-check form-check-sm mb-1">' +
                            '<input class="form-check-input" type="checkbox" name="id_template[' + item.id + '][]" value="' + t.id_template + '" id="tpl_' + t.id_template + '" checked>' +
                            '<label class="form-check-label small" for="tpl_' + t.id_template + '">' + t.Pemeriksaan + '</label>' +
                            '</div>';
                    });
                    html += '</div>';
                    $('#list-template-checkbox').append(html);
                }
            });
        }
    });

    $('#btnSimpanLab').off('click').on('click', function() {
        var kdList = tsLab ? tsLab.getValue() : [];
        if (!kdList || kdList.length === 0) {
            tampilkanError('Pilih minimal 1 jenis pemeriksaan laboratorium.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Mengirim...');

        var formData = $('#formPermintaanLabRanap form, #formPermintaanLabRanap').find('input, textarea, select').serialize();

        $.ajax({
            url: ranapUrl('/ralan/store-permintaan-lab'),
            type: 'POST',
            data: formData,
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Kirim Permintaan Lab');
                tampilkanSukses(res.message);
                loadLab(true);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Kirim Permintaan Lab');
                tampilkanError(xhr.responseJSON ? xhr.responseJSON.message : 'Gagal mengirim order lab.');
            }
        });
    });

    $(document).off('click', '.btn-hapus-lab').on('click', '.btn-hapus-lab', function() {
        var noorder = $(this).data('noorder');
        Swal.fire({
            title: 'Batalkan Permintaan Lab?',
            text: 'Order ' + noorder + ' akan dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Batalkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: ranapUrl('/ralan/delete-lab/' + noorder),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadLab(true);
                    }
                });
            }
        });
    });
}

/* ===== 6. Tab Radiologi ===== */
function loadRadiologi(forceReload = false) {
    if (!currentNoRawat) return;
    if (loadedTabs.radiologi && !forceReload) return;
    if (loadingTabs.radiologi) return;

    if (!loadedTabs.radiologi) {
        $('#content-radiologi').html('<div class="text-center p-5"><div class="spinner-border text-teal"></div><p class="mt-2 text-muted">Memuat Form Permintaan & Hasil Radiologi...</p></div>');
    }

    loadingTabs.radiologi = true;
    var url = ranapUrl('/ranap/get-radiologi-pasien/' + currentSafeNoRawat);
    $.get(url, function(data) {
        $('#content-radiologi').html(data);
        loadedTabs.radiologi = true;
        loadingTabs.radiologi = false;
        initRadiologiHandlers();
    }).fail(function(xhr) {
        loadingTabs.radiologi = false;
        if (!loadedTabs.radiologi) $('#content-radiologi').html('<div class="alert alert-danger">Gagal memuat radiologi (HTTP ' + xhr.status + ').</div>');
    });
}

function initRadiologiHandlers() {
    var tsRad = initRemoteSelect('#select-radiologi', {
        url: ranapUrl('/ralan/search-pemeriksaan-radiologi'),
        placeholder: 'Pilih tindakan radiologi ranap...',
        multiple: true,
        withNoRawat: true
    });

    $('#btnSimpanRadiologi').off('click').on('click', function() {
        var kdList = tsRad ? tsRad.getValue() : [];
        if (!kdList || kdList.length === 0) {
            tampilkanError('Pilih minimal 1 tindakan radiologi.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Mengirim...');

        var rawList = Array.isArray(kdList) ? kdList : [kdList];
        var payload = {
            _token: window.RANAP.csrfToken || $('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content'),
            no_rawat: $('#formPermintaanRadiologiRanap input[name="no_rawat"]').val() || currentNoRawat,
            kd_jenis_prw_rad: rawList,
            diagnosa_klinis: $('#formPermintaanRadiologiRanap textarea[name="diagnosa_klinis"]').val(),
            informasi_tambahan: $('#formPermintaanRadiologiRanap textarea[name="informasi_tambahan"]').val()
        };

        $.ajax({
            url: ranapUrl('/ralan/store-permintaan-radiologi'),
            type: 'POST',
            data: payload,
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Kirim Permintaan Radiologi');
                tampilkanSukses(res.message);
                loadRadiologi(true);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Kirim Permintaan Radiologi');
                tampilkanError(xhr.responseJSON ? xhr.responseJSON.message : 'Gagal mengirim order radiologi.');
            }
        });
    });

    $(document).off('click', '.btn-hapus-radiologi').on('click', '.btn-hapus-radiologi', function() {
        var noorder = $(this).data('noorder');
        Swal.fire({
            title: 'Batalkan Permintaan Radiologi?',
            text: 'Order ' + noorder + ' akan dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Batalkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: ranapUrl('/ralan/delete-radiologi/' + noorder),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        loadRadiologi(true);
                    }
                });
            }
        });
    });
}

/* ===== 7. Tab Resume Pasien ===== */
function loadResume(forceReload = false) {
    if (!currentNoRawat) return;
    if (loadedTabs.resume && !forceReload) return;
    if (loadingTabs.resume) return;

    if (!loadedTabs.resume) {
        $('#content-resume').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat Form Resume Medis Pasien...</p></div>');
    }

    loadingTabs.resume = true;
    var url = ranapUrl('/ranap/get-resume-pasien/' + currentSafeNoRawat);
    $.get(url, function(data) {
        $('#content-resume').html(data);
        loadedTabs.resume = true;
        loadingTabs.resume = false;
        initResumeHandlers();
    }).fail(function(xhr) {
        loadingTabs.resume = false;
        if (!loadedTabs.resume) $('#content-resume').html('<div class="alert alert-danger">Gagal memuat resume medis (HTTP ' + xhr.status + ').</div>');
    });
}

function initResumeHandlers() {
    // Toggle container input faskes rujukan
    $('input.radio-kondisi-pulang').off('change').on('change', function() {
        if ($(this).val() === 'dirujuk') {
            $('#containerNamaRujukan').removeClass('d-none');
            $('#resume_nama_faskes_rujukan').focus();
        } else {
            $('#containerNamaRujukan').addClass('d-none');
        }
    });

    // Tombol Tarik Data Auto Resume
    $('#btnTarikDataAutoResume').off('click').on('click', function() {
        Swal.fire({
            title: 'Tarik Ulang Data Rekam Medis?',
            text: 'Data awal dari SOAP, Kamar Inap, dan Obat akan dimuat ulang.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Tarik Data',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                loadResume(true);
            }
        });
    });

    // Submit Form Resume
    $('#formResumePasienRanap').off('submit').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnSimpanResume');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

        var formData = $(this).serialize();

        $.ajax({
            url: ranapUrl('/ranap/store-resume-pasien'),
            type: 'POST',
            data: formData,
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Resume Pasien');
                tampilkanSukses(res.message || 'Resume medis berhasil disimpan.');
                loadResume(true);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Resume Pasien');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menyimpan resume medis.';
                tampilkanError(err);
            }
        });
    });
}

/* ===== Tab Navigation Event Listeners ===== */
$(document).ready(function() {
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr('href');
        if (target === '#pemeriksaan-soap') {
            loadSoap();
        } else if (target === '#pemeriksaan-vital-sign') {
            loadVital();
        } else if (target === '#diagnosa-prosedur') {
            loadDiagnosa();
        } else if (target === '#resep') {
            loadResep();
        } else if (target === '#permintaan-lab') {
            loadLab();
        } else if (target === '#permintaan-radiologi') {
            loadRadiologi();
        } else if (target === '#resume') {
            loadResume();
        }
    });

    // Hash check on load
    if (window.location.hash) {
        var hash = window.location.hash;
        var tabTrigger = $('.nav-tabs a[href="' + hash + '"]');
        if (tabTrigger.length > 0) {
            tabTrigger.tab('show');
        }
    }
});
