/* Ranap Core JS - Sistem Rekam Medis Elektronik Rawat Inap Nirwana
 * Mengelola pemuatan asynchronous seluruh tab (SOAP, TTV, SBAR, Resep, Lab, Radiologi, Resume)
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
        maxItems: opts.multiple ? (opts.maxItems || null) : 1,
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
        },
        onItemRemove: function(value) {
            if (typeof opts.onItemRemove === 'function') {
                opts.onItemRemove(value, this);
            }
        },
        onChange: function(value) {
            if (typeof opts.onChange === 'function') {
                opts.onChange(value, this);
            }
        }
    });
}

function clearRemoteSelect(selector) {
    var el = document.querySelector(selector);
    if (el && el.tomselect) el.tomselect.clear();
}

/* ===== Draf form otomatis ===== */
// Draf berisi data klinis pasien: simpan di sessionStorage (hilang saat tab ditutup / logout), bukan localStorage.
var draftStore = {
    get: function(k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } },
    set: function(k, v) { try { sessionStorage.setItem(k, v); } catch (e) {} },
    remove: function(k) { try { sessionStorage.removeItem(k); } catch (e) {} }
};

// Bersihkan draf lama yang sempat tertulis di localStorage oleh versi sebelumnya.
try {
    Object.keys(localStorage).forEach(function(k) {
        if (k.indexOf('rme_draft_') === 0) localStorage.removeItem(k);
    });
} catch (e) {}

function pulihkanDraf($form, data) {
    $.each(data, function(name, val) {
        var $field = $form.find('[name="' + name + '"]');
        if (!$field.length) return;
        if ($field.is(':radio, :checkbox')) {
            $field.filter(function() { return this.value === val; }).prop('checked', true).trigger('change');
        } else if (!$field.is(':hidden')) {
            $field.val(val);
        }
    });
}

function initAutoSaveDraft(formSelector, storageKey, alertContainerSelector, onRestoreCallback) {
    var $form = $(formSelector);
    if (!$form.length || !currentSafeNoRawat) return;

    var fullKey = storageKey + '_' + currentSafeNoRawat;

    var savedDraft = draftStore.get(fullKey);
    if (savedDraft) {
        try {
            var draftObj = JSON.parse(savedDraft);
            if (draftObj && draftObj.data && draftObj.time) {
                var alertHtml = '<div class="alert alert-info py-2 px-3 mb-3 d-flex justify-content-between align-items-center draft-alert" id="alert-' + fullKey + '">' +
                    '<div><i class="fas fa-history me-1 text-primary"></i> <strong>Draf Ditemukan</strong> <small class="text-muted">(' + draftObj.time + ')</small></div>' +
                    '<div class="d-flex gap-2">' +
                    '<button type="button" class="btn btn-sm btn-primary btn-restore-draft"><i class="fas fa-undo me-1"></i> Pulihkan Draf</button>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary btn-dismiss-draft"><i class="fas fa-times"></i> Abaikan</button>' +
                    '</div></div>';

                var $container = $(alertContainerSelector);
                if ($container.length && !$('#alert-' + fullKey).length) {
                    $container.prepend(alertHtml);

                    $('#alert-' + fullKey + ' .btn-restore-draft').on('click', function() {
                        if (typeof onRestoreCallback === 'function') {
                            onRestoreCallback(draftObj.data);
                        } else {
                            pulihkanDraf($form, draftObj.data);
                        }
                        tampilkanSukses('Draf berhasil dipulihkan.');
                        $('#alert-' + fullKey).remove();
                    });

                    $('#alert-' + fullKey + ' .btn-dismiss-draft').on('click', function() {
                        draftStore.remove(fullKey);
                        $('#alert-' + fullKey).remove();
                    });
                }
            }
        } catch (e) {
            draftStore.remove(fullKey);
        }
    }

    var autoSaveTimer = null;
    $form.off('input.autosave change.autosave').on('input.autosave change.autosave', 'input, textarea, select', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            var dataObj = {};
            $form.serializeArray().forEach(function(item) {
                if (item.name !== '_token') {
                    dataObj[item.name] = item.value;
                }
            });

            var nowStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            draftStore.set(fullKey, JSON.stringify({ data: dataObj, time: nowStr }));
        }, 1500);
    });
}

function clearAutoSaveDraft(storageKey) {
    if (!currentSafeNoRawat) return;
    var fullKey = storageKey + '_' + currentSafeNoRawat;
    draftStore.remove(fullKey);
    $('#alert-' + fullKey).remove();
}

/* ===== Reload fragmen tabel riwayat (form tab & Tom Select tidak dimuat ulang) ===== */
var ranapTables = {
    soap:      '#ranap-soap-table',
    ttv:       '#ranap-ttv-table',
    resep:     '#resep-table-container',
    lab:       '#ranap-lab-table',
    radiologi: '#ranap-rad-table'
};
var ranapTableXhr = {};

function reloadRanapTable(nama) {
    var target = ranapTables[nama];
    if (!currentNoRawat || !target || !$(target).length) return;

    // Simpan/hapus beruntun: batalkan request lama agar respons terakhir yang tampil.
    if (ranapTableXhr[nama]) ranapTableXhr[nama].abort();

    var req = $.get(ranapUrl('/ranap/table/' + nama + '/' + currentSafeNoRawat))
        .done(function(html) { $(target).html(html); })
        .fail(function(xhr, status) {
            if (status !== 'abort') tampilkanError('Gagal memuat ulang tabel (HTTP ' + xhr.status + ').');
        })
        .always(function() {
            if (ranapTableXhr[nama] === req) ranapTableXhr[nama] = null;
        });
    ranapTableXhr[nama] = req;
}

function kosongkanStaging(tbody) {
    var $tbody = $(tbody);
    var cols = $tbody.data('cols') || 3;
    var emptyTxt = $tbody.data('empty') || 'Belum ada data';
    $tbody.html('<tr class="staging-empty"><td colspan="' + cols + '" class="text-center text-muted small py-2">' + emptyTxt + '</td></tr>');
}

/* ===== Tab State Management ===== */
var loadedTabs = {
    riwayat: true,
    soap: false,
    vital: false,
    resep: false,
    lab: false,
    radiologi: false,
    resume: false
};

var loadingTabs = {
    riwayat: false,
    soap: false,
    vital: false,
    resep: false,
    lab: false,
    radiologi: false,
    resume: false
};

// Reload paksa saat tab masih dimuat: tandai, lalu jalankan sekali setelah selesai.
var pendingTabs = {};

function tundaReload(tab, forceReload) {
    if (forceReload) pendingTabs[tab] = true;
}

function jalankanReloadTertunda(tab, loader) {
    if (!pendingTabs[tab]) return;
    pendingTabs[tab] = false;
    loader(true);
}

/* ===== 1. Tab SOAP CPPT ===== */
function loadSoap(forceReload = false) {
    if (!currentNoRawat) return;
    if (loadedTabs.soap && !forceReload) return;
    if (loadingTabs.soap) return tundaReload('soap', forceReload);

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
    }).always(function() {
        jalankanReloadTertunda('soap', loadSoap);
    });
}

function initSoapHandlers() {
    initAutoSaveDraft('#formSoapRanap', 'rme_draft_soap', '#content-soap .ralan-card-body');

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
                clearAutoSaveDraft('rme_draft_soap');
                resetFormSoap();
                reloadRanapTable('soap');
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan SOAP');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.';
                tampilkanError(err);
            }
        });
    });

    // Klik badan tabel untuk langsung mengisi form input (memudahkan entri CPPT)
    // Delegasi di container: tetap berlaku setelah isi tabel diganti reloadRanapTable.
    $('#ranap-soap-table').off('click', 'tr.soap-row-item').on('click', 'tr.soap-row-item', function(e) {
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
                        reloadRanapTable('soap');
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
    if (loadingTabs.vital) return tundaReload('vital', forceReload);

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
    }).always(function() {
        jalankanReloadTertunda('vital', loadVital);
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
                reloadRanapTable('ttv');
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan TTV & SBAR');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.';
                tampilkanError(err);
            }
        });
    });

    // Klik badan tabel untuk langsung mengisi form input (memudahkan entri TTV)
    // Delegasi di container: tetap berlaku setelah isi tabel diganti reloadRanapTable.
    $('#ranap-ttv-table').off('click', 'tr.ttv-row-item').on('click', 'tr.ttv-row-item', function(e) {
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
                        reloadRanapTable('ttv');
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

/* ===== 4. Tab Resep & Obat ===== */
function loadResep(forceReload = false) {
    if (!currentNoRawat) return;
    if (loadedTabs.resep && !forceReload) return;
    if (loadingTabs.resep) return tundaReload('resep', forceReload);

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
    }).always(function() {
        jalankanReloadTertunda('resep', loadResep);
    });
}

function resetFormRacikanRanap() {
    var $tab = $('#content-resep');
    $tab.find('input[name="nama_racik"], input[name="keterangan"]').val('');
    $tab.find('select[name="kd_racik"]').prop('selectedIndex', 0);
    $tab.find('input[name="jml_dr"]').val(10);
    // change -> handler aturan_racik ikut menyembunyikan & mengosongkan input "lainnya".
    $tab.find('select[name="aturan_racik"]').val('').trigger('change');
    kosongkanStaging('#staging-bahan-racik');
}

function initResepHandlers() {
    // Hapus baris staging obat dan bahan racikan.
    $(document).off('click', '.btn-remove-row').on('click', '.btn-remove-row', function() {
        var $tbody = $(this).closest('tbody');
        $(this).closest('tr').remove();
        if ($tbody.children('tr').length === 0) {
            kosongkanStaging($tbody);
        }
    });

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
            url: ranapUrl('/ranap/store-resep-obat'),
            type: 'POST',
            data: {
                _token: window.RANAP.csrfToken,
                no_rawat: currentNoRawat,
                obat: obatList
            },
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Resep Obat Ranap');
                tampilkanSukses(res.message);
                kosongkanStaging('#staging-obat');
                reloadRanapTable('resep');
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
            url: ranapUrl('/ranap/store-resep-racikan'),
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
                resetFormRacikanRanap();
                reloadRanapTable('resep');
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
                    url: ranapUrl('/ranap/delete-resep-obat/' + noResep + '/' + kodeBrng),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        reloadRanapTable('resep');
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
                    url: ranapUrl('/ranap/delete-resep-racikan/' + noResep + '/' + noRacik),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        reloadRanapTable('resep');
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
    if (loadingTabs.lab) return tundaReload('lab', forceReload);

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
    }).always(function() {
        jalankanReloadTertunda('lab', loadLab);
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
            url: ranapUrl('/ranap/store-permintaan-lab'),
            type: 'POST',
            data: formData,
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Kirim Permintaan Lab');
                tampilkanSukses(res.message);
                clearRemoteSelect('#select-lab');
                $('#formPermintaanLabRanap textarea').val('');
                $('#list-template-checkbox').empty();
                $('#detail-pemeriksaan-placeholder').removeClass('d-none');
                reloadRanapTable('lab');
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
                    url: ranapUrl('/ranap/delete-lab/' + noorder),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        reloadRanapTable('lab');
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
    if (loadingTabs.radiologi) return tundaReload('radiologi', forceReload);

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
    }).always(function() {
        jalankanReloadTertunda('radiologi', loadRadiologi);
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
            url: ranapUrl('/ranap/store-permintaan-radiologi'),
            type: 'POST',
            data: payload,
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Kirim Permintaan Radiologi');
                tampilkanSukses(res.message);
                clearRemoteSelect('#select-radiologi');
                $('#formPermintaanRadiologiRanap textarea').val('');
                reloadRanapTable('radiologi');
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
                    url: ranapUrl('/ranap/delete-radiologi/' + noorder),
                    type: 'DELETE',
                    data: { _token: window.RANAP.csrfToken },
                    success: function(res) {
                        tampilkanSukses(res.message);
                        reloadRanapTable('radiologi');
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
    if (loadingTabs.resume) return tundaReload('resume', forceReload);

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
    }).always(function() {
        jalankanReloadTertunda('resume', loadResume);
    });
}

function initResumeHandlers() {
    initAutoSaveDraft('#formResumePasienRanap', 'rme_draft_resume', '#formResumePasienRanap');

    // Toggle container input faskes rujukan & styling aktif card
    $('input.radio-kondisi-pulang').off('change').on('change', function() {
        var val = $(this).val();

        // Update visual card active state
        $('.kondisi-pulang-card').removeClass('border-success bg-success-subtle text-success-emphasis border-warning bg-warning-subtle text-warning-emphasis border-primary bg-primary-subtle text-primary-emphasis border-danger bg-danger-subtle text-danger-emphasis fw-bold shadow-sm')
            .addClass('bg-light text-secondary');

        var $card = $(this).closest('.kondisi-pulang-card');
        $card.removeClass('bg-light text-secondary').addClass('fw-bold shadow-sm');

        if (val === 'sembuh') {
            $card.addClass('border-success bg-success-subtle text-success-emphasis');
        } else if (val === 'aps') {
            $card.addClass('border-warning bg-warning-subtle text-warning-emphasis');
        } else if (val === 'dirujuk') {
            $card.addClass('border-primary bg-primary-subtle text-primary-emphasis');
        } else {
            $card.addClass('border-danger bg-danger-subtle text-danger-emphasis');
        }

        if (val === 'dirujuk') {
            $('#containerNamaRujukan').removeClass('d-none');
            $('#resume_nama_faskes_rujukan').focus();
        } else {
            $('#containerNamaRujukan').addClass('d-none');
        }
    });

    // TomSelect Diagnosa Primer (Single)
    var tsResumePrimer = initRemoteSelect('#select-resume-icd10-primer', {
        url: ranapUrl('/ralan/search-icd10'),
        placeholder: 'Ketik kode / nama ICD-10 diagnosa utama...',
        onItemAdd: function(item, ts) {
            var parts = (item.text || '').split(' - ');
            var kode = parts[0] || item.id;
            var nama = parts.slice(1).join(' - ') || kode;
            $('#resume_kd_diagnosa_utama').val(kode);
            $('#resume_diagnosa_utama').val(nama);
        },
        onChange: function(value, ts) {
            if (!value) {
                $('#resume_kd_diagnosa_utama').val('');
                $('#resume_diagnosa_utama').val('');
            }
        }
    });

    // Helper sinkronisasi 4 pasang hidden input diagnosa sekunder
    function syncResumeSekunderHidden(tsInstance) {
        var values = tsInstance ? tsInstance.getValue() : [];
        if (!Array.isArray(values)) {
            values = values ? [values] : [];
        }

        for (var i = 1; i <= 4; i++) {
            var suffix = i === 1 ? '' : i;
            var val = values[i - 1];
            if (val && tsInstance.options[val]) {
                var item = tsInstance.options[val];
                var parts = (item.text || '').split(' - ');
                var kode = parts[0] || item.id;
                var nama = parts.slice(1).join(' - ') || kode;
                $('#resume_kd_diagnosa_sekunder' + suffix).val(kode);
                $('#resume_diagnosa_sekunder' + suffix).val(nama);
            } else {
                $('#resume_kd_diagnosa_sekunder' + suffix).val('');
                $('#resume_diagnosa_sekunder' + suffix).val('');
            }
        }
    }

    // TomSelect Diagnosa Sekunder (Multiple, max 4 items)
    var tsResumeSekunder = initRemoteSelect('#select-resume-icd10-sekunder', {
        url: ranapUrl('/ralan/search-icd10'),
        placeholder: 'Ketik kode / nama ICD-10 sekunder (maks. 4)...',
        multiple: true,
        maxItems: 4,
        onChange: function(value, ts) {
            syncResumeSekunderHidden(ts);
        }
    });

    // Tombol Tarik Data Auto Resume (Ambil data terbaru dari SOAP, diagnosa, dan obat)
    $('#btnTarikDataAutoResume').off('click').on('click', function() {
        var rawData = $(this).attr('data-auto');
        var autoData = {};
        try {
            autoData = rawData ? JSON.parse(rawData) : {};
        } catch (e) {
            autoData = {};
        }

        Swal.fire({
            title: 'Tarik Data Rekam Medis Terbaru?',
            text: 'Form akan diisi dengan data SOAP ranap terakhir, diagnosa ICD, obat, serta hasil Lab & Radiologi terbaru.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Tarik Data Terbaru',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                if (autoData.riwayat_penyakit) $('#resume_riwayat_penyakit').val(autoData.riwayat_penyakit);
                if (autoData.pemeriksaan_fisik) $('#resume_pemeriksaan_fisik').val(autoData.pemeriksaan_fisik);
                if (autoData.pemeriksaan_penunjang) $('#resume_pemeriksaan_penunjang').val(autoData.pemeriksaan_penunjang);
                if (autoData.tindakan) $('#resume_tindakan_operasi').val(autoData.tindakan);
                if (autoData.edukasi) $('#resume_edukasi').val(autoData.edukasi);

                // Set Diagnosa Primer
                if (tsResumePrimer) {
                    tsResumePrimer.clear(true);
                    if (autoData.kd_diagnosa_utama) {
                        var kdPrim = autoData.kd_diagnosa_utama;
                        var nmPrim = autoData.diagnosa_utama || kdPrim;
                        tsResumePrimer.addOption({ id: kdPrim, text: kdPrim + ' - ' + nmPrim });
                        tsResumePrimer.setValue(kdPrim);
                    } else {
                        $('#resume_kd_diagnosa_utama').val('');
                        $('#resume_diagnosa_utama').val('');
                    }
                }

                // Set Diagnosa Sekunder (Maks 4 items)
                if (tsResumeSekunder) {
                    tsResumeSekunder.clear(true);
                    var sekunderList = autoData.diagnosa_sekunder_items || [];
                    var sekunderValues = [];
                    sekunderList.slice(0, 4).forEach(function(item) {
                        if (item.kd) {
                            var kdSek = item.kd;
                            var nmSek = item.nm || kdSek;
                            tsResumeSekunder.addOption({ id: kdSek, text: kdSek + ' - ' + nmSek });
                            sekunderValues.push(kdSek);
                        }
                    });
                    if (sekunderValues.length > 0) {
                        tsResumeSekunder.setValue(sekunderValues);
                    } else {
                        syncResumeSekunderHidden(tsResumeSekunder);
                    }
                }

                tampilkanSukses('Data rekam medis terbaru berhasil ditarik ke form.');
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
                clearAutoSaveDraft('rme_draft_resume');
                $('#btnCetakResumePdf').removeClass('disabled');
                // Form sudah berisi nilai tersimpan; cukup perbarui status, tanpa reload tab (~20 query).
                $('#badgeStatusResume')
                    .removeClass('bg-warning text-dark').addClass('bg-success')
                    .html('<i class="fas fa-check-circle me-1"></i>Sudah Tersimpan');
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
