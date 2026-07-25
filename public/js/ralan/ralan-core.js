/* Ralan core JS — dipisah dari resources/views/ralan/index.blade.php.
 * Route & CSRF disuntik dari blade lewat window.RALAN (lihat bootstrap inline di index.blade.php).
 * Variabel global: currentNoRawat, currentSafeNoRawat (didefinisikan di bootstrap). */

let rowCount = 0;

function tampilkanError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: message
        });
    } else if (typeof swal !== 'undefined') {
        swal("Gagal!", message, "error");
    } else {
        alert(message);
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
    } else if (typeof swal !== 'undefined') {
        swal("Berhasil!", message, "success");
    } else {
        alert(message);
    }
}

/* ===== Tom Select helpers (pengganti Select2) ===== */
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

var loadedTabs = {
    soap: false,
    diagnosa: false,
    vital: false,
    lab: false,
    radiologi: false,
    resep: false
};

var loadingTabs = {
    soap: false,
    diagnosa: false,
    vital: false,
    lab: false,
    radiologi: false,
    resep: false
};

function loadSoap(forceReload = false) {
    if (currentNoRawat === "") return;
    if (loadedTabs.soap && !forceReload) return;
    if (loadingTabs.soap) return;

    if (!loadedTabs.soap) {
        $('#content-soap').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p>Memuat Form SOAP...</p></div>');
    }

    loadingTabs.soap = true;
    $.get('/ralan/soap/' + currentSafeNoRawat, function(data) {
        $('#content-soap').html(data);
        loadedTabs.soap = true;
        loadingTabs.soap = false;
    }).fail(function() {
        loadingTabs.soap = false;
        if (!loadedTabs.soap) $('#content-soap').html('<div class="alert alert-danger">Gagal memuat form SOAP.</div>');
    });
}

function loadVital(forceReload = false) {
    if (currentNoRawat === "") return;
    if (loadedTabs.vital && !forceReload) return;
    if (loadingTabs.vital) return;

    if (!loadedTabs.vital) {
        $('#content-vital-sign').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p>Memuat Form Vital Sign...</p></div>');
    }

    loadingTabs.vital = true;
    $.get('/ralan/get-vital-pasien/' + currentSafeNoRawat, function(data) {
        $('#content-vital-sign').html(data);
        loadedTabs.vital = true;
        loadingTabs.vital = false;
    }).fail(function() {
        loadingTabs.vital = false;
        if (!loadedTabs.vital) $('#content-vital-sign').html('<div class="alert alert-danger">Gagal memuat form Vital Sign.</div>');
    });
}

function loadResep(forceReload = false) {
    if (currentNoRawat === "") return;
    if (loadedTabs.resep && !forceReload) return;
    if (loadingTabs.resep) return;

    if (!loadedTabs.resep) {
        $('#content-resep').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p>Memuat data resep...</p></div>');
    }

    loadingTabs.resep = true;
    $.ajax({
        url: '/ralan/get-resep-pasien/' + currentSafeNoRawat,
        method: 'GET',
        success: function(data) {
            $('#content-resep').html(data);
            loadedTabs.resep = true;
            loadingTabs.resep = false;
            setTimeout(function() { initSelect2(); }, 100);
        },
        error: function() {
            loadingTabs.resep = false;
            if (!loadedTabs.resep) $('#content-resep').html('<div class="alert alert-danger">Gagal memuat data resep.</div>');
        }
    });
}

function loadFormLab(forceReload = false) {
    if (currentNoRawat === "") return;
    if (loadedTabs.lab && !forceReload) return;
    if (loadingTabs.lab) return;

    if (!loadedTabs.lab) {
        $('#content-lab').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p>Memuat Form Permintaan Lab...</p></div>');
    }

    loadingTabs.lab = true;
    $.ajax({
        url: '/ralan/get-lab-pasien/' + currentSafeNoRawat,
        method: 'GET',
        success: function(data) {
            $('#content-lab').html(data);
            loadedTabs.lab = true;
            loadingTabs.lab = false;
            setTimeout(function() { initSelect2Lab(); }, 100);
        },
        error: function() {
            loadingTabs.lab = false;
            if (!loadedTabs.lab) $('#content-lab').html('<div class="alert alert-danger">Gagal memuat form permintaan lab.</div>');
        }
    });
}

function loadFormRadiologi(forceReload = false) {
    if (currentNoRawat === "") return;
    if (loadedTabs.radiologi && !forceReload) return;
    if (loadingTabs.radiologi) return;

    if (!loadedTabs.radiologi) {
        $('#content-radiologi').html('<div class="text-center p-5"><div class="spinner-border text-info"></div><p>Memuat Form Permintaan Radiologi...</p></div>');
    }

    loadingTabs.radiologi = true;
    $.ajax({
        url: '/ralan/get-radiologi-pasien/' + currentSafeNoRawat,
        method: 'GET',
        success: function(data) {
            $('#content-radiologi').html(data);
            loadedTabs.radiologi = true;
            loadingTabs.radiologi = false;
            setTimeout(function() { initSelect2Radiologi(); }, 100);
        },
        error: function() {
            loadingTabs.radiologi = false;
            if (!loadedTabs.radiologi) $('#content-radiologi').html('<div class="alert alert-danger">Gagal memuat form permintaan radiologi.</div>');
        }
    });
}

function loadDiagnosaProsedur(forceReload = false) {
    if (currentNoRawat === "") return;
    if (loadedTabs.diagnosa && !forceReload) return;
    if (loadingTabs.diagnosa) return;

    if (!loadedTabs.diagnosa) {
        $('#content-diagnosa-prosedur').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p>Memuat Form Diagnosa & Prosedur...</p></div>');
    }

    loadingTabs.diagnosa = true;
    $.ajax({
        url: '/ralan/get-diagnosa-prosedur/' + currentSafeNoRawat,
        method: 'GET',
        success: function(data) {
            $('#content-diagnosa-prosedur').html(data);
            loadedTabs.diagnosa = true;
            loadingTabs.diagnosa = false;
            setTimeout(function() { initSelect2DiagnosaProsedur(); }, 100);
        },
        error: function() {
            loadingTabs.diagnosa = false;
            if (!loadedTabs.diagnosa) $('#content-diagnosa-prosedur').html('<div class="alert alert-danger">Gagal memuat form Diagnosa & Prosedur.</div>');
        }
    });
}

function stagingKode(tbodySel) {
    return $(tbodySel + ' tr[data-kode]').map(function() { return $(this).data('kode'); }).get();
}

function addStagingRow(tbodySel, data, withJumlah) {
    var kode = String(data.id);
    if ($(tbodySel + ' tr[data-kode="' + kode.replace(/"/g, '') + '"]').length) return;
    $(tbodySel + ' .staging-empty').remove();
    var nama = String(data.text).replace(kode + ' - ', '');
    var jumlahCell = withJumlah
        ? '<td><input type="number" class="form-control form-control-sm staging-jumlah" min="1" value="1"></td>'
        : '';
    $(tbodySel).append(
        '<tr data-kode="' + kode + '">' +
        '<td><span class="badge bg-light text-dark border">' + kode + '</span></td>' +
        '<td class="small">' + nama + '</td>' +
        jumlahCell +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 staging-remove"><i class="fas fa-times"></i></button></td>' +
        '</tr>'
    );
}

function initSelect2DiagnosaProsedur() {
    initRemoteSelect('#select-icd10', {
        url: window.RALAN.routes.searchIcd10,
        multiple: false,
        placeholder: 'Ketik kode / nama penyakit...',
        onItemAdd: function(data, ts) {
            addStagingRow('#staging-diagnosa', data, false);
            ts.clear(true);
            ts.clearOptions();
        }
    });
    initRemoteSelect('#select-icd9', {
        url: window.RALAN.routes.searchIcd9,
        multiple: false,
        placeholder: 'Ketik kode / deskripsi prosedur...',
        onItemAdd: function(data, ts) {
            addStagingRow('#staging-prosedur', data, true);
            ts.clear(true);
            ts.clearOptions();
        }
    });
}

function initSelect2() {
    initRemoteSelect('.kd_obat_ajax', {
        url: window.RALAN.routes.searchObat,
        multiple: false,
        minLen: 3,
        placeholder: 'Ketik Nama Obat / Kode Obat...'
    });
    initAturanSelect('.select2-aturan');
    initAturanSelect('.select2-aturan-racik');
}

/* Aturan pakai: pilih dari master, atau ketik baru (create). */
function initAturanSelect(selector) {
    var el = document.querySelector(selector);
    if (!el) return null;
    if (el.tomselect) el.tomselect.destroy();
    return new TomSelect(el, {
        create: true,
        createOnBlur: true,
        maxItems: 1,
        placeholder: 'Pilih / ketik Aturan Pakai...',
        persist: false
    });
}

function initSelect2Lab() {
    initRemoteSelect('#select-lab', {
        url: window.RALAN.routes.searchLab,
        multiple: true,
        withNoRawat: true,
        placeholder: 'Cari Pemeriksaan Lab...'
    });
}

function initSelect2Radiologi() {
    initRemoteSelect('#select-rad', {
        url: window.RALAN.routes.searchRadiologi,
        multiple: true,
        withNoRawat: true,
        placeholder: 'Cari Pemeriksaan Radiologi...'
    });
}

function updateCheckboxCounter(groupElement) {
    let total = groupElement.find('.form-check-input').length;
    let checked = groupElement.find('.form-check-input:checked').length;
    let counter = groupElement.find('.badge-count');

    counter.html(`<i class="fas fa-check-circle"></i> ${checked} / ${total} dipilih`);

    if (checked === 0) {
        counter.removeClass('bg-success bg-warning').addClass('bg-secondary');
    } else if (checked === total) {
        counter.removeClass('bg-secondary bg-warning').addClass('bg-success');
    } else {
        counter.removeClass('bg-secondary bg-success').addClass('bg-warning');
    }
}

function hapusObat(no_resep, kode_brng) {
    console.log('Hapus obat called:', no_resep, kode_brng);

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus obat ini?',
            text: "Data akan dihapus secara permanen",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                prosesHapusObat(no_resep, kode_brng);
            }
        });
    } else if (typeof swal !== 'undefined') {
        swal({
            title: "Hapus obat ini?",
            text: "Data akan dihapus secara permanen",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                prosesHapusObat(no_resep, kode_brng);
            }
        });
    } else {
        if (confirm('Hapus obat ini?')) {
            prosesHapusObat(no_resep, kode_brng);
        }
    }
}

function prosesHapusObat(no_resep, kode_brng) {
    $.ajax({
        url: '/ralan/delete-resep-obat/' + no_resep + '/' + kode_brng,
        method: 'POST',
        data: {
            _token: window.RALAN.csrf,
            _method: 'DELETE'
        },
        success: function(response) {
            console.log('Delete response:', response);

            var isSuccess = false;
            if (response.status === 'success' ||
                response.status === 'success-obat' ||
                response.success === true ||
                response.message === 'Item resep berhasil dihapus') {
                isSuccess = true;
            }

            if(isSuccess) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: response.message || 'Data berhasil dihapus',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        loadResep(true);
                    });
                } else if (typeof swal !== 'undefined') {
                    swal("Terhapus!", response.message || 'Data berhasil dihapus', "success");
                    setTimeout(function() {
                        loadResep(true);
                    }, 1000);
                } else {
                    alert('Data berhasil dihapus');
                    loadResep(true);
                }
            } else {
                tampilkanError(response.message || "Gagal menghapus obat");
            }
        },
        error: function(xhr) {
            console.error('Delete error:', xhr);
            var errorMsg = "Gagal menghapus obat";

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.status === 404) {
                errorMsg = "Data tidak ditemukan";
            } else if (xhr.status === 500) {
                errorMsg = "Terjadi kesalahan server";
            }

            tampilkanError(errorMsg);
        }
    });
}

function hapusRacikan(no_resep, no_racik) {
    console.log('Hapus Racikan called:', no_resep, no_racik);

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Racikan ini?',
            text: "Data akan dihapus secara permanen",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                prosesHapusRacikan(no_resep, no_racik);
            }
        });
    } else if (typeof swal !== 'undefined') {
        swal({
            title: "Hapus Racikan ini?",
            text: "Data akan dihapus secara permanen",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                prosesHapusRacikan(no_resep, no_racik);
            }
        });
    } else {
        if (confirm('Hapus racikan ini?')) {
            prosesHapusRacikan(no_resep, no_racik);
        }
    }
}

function prosesHapusRacikan(no_resep, no_racik) {
    $.ajax({
        url: '/ralan/delete-resep-racikan/' + no_resep + '/' + no_racik,
        method: 'POST',
        data: {
            _token: window.RALAN.csrf,
            _method: 'DELETE'
        },
        success: function(response) {
            console.log('Delete response:', response);

            var isSuccess = false;
            if (response.status === 'success' ||
                response.status === 'success-hapus-racikan' ||
                response.success === true ||
                response.message === 'Item resep berhasil dihapus') {
                isSuccess = true;
            }

            if(isSuccess) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: response.message || 'Data berhasil dihapus',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        loadResep(true);
                    });
                } else if (typeof swal !== 'undefined') {
                    swal("Terhapus!", response.message || 'Data berhasil dihapus', "success");
                    setTimeout(function() {
                        loadResep(true);
                    }, 1000);
                } else {
                    alert('Data berhasil dihapus');
                    loadResep(true);
                }
            } else {
                tampilkanError(response.message || "Gagal menghapus obat");
            }
        },
        error: function(xhr) {
            console.error('Delete error:', xhr);
            var errorMsg = "Gagal menghapus obat";

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.status === 404) {
                errorMsg = "Data tidak ditemukan";
            } else if (xhr.status === 500) {
                errorMsg = "Terjadi kesalahan server";
            }

            tampilkanError(errorMsg);
        }
    });
}

function hapusLab(noorder, kd_jenis_prw = null, id_template = null) {
    let title = 'Hapus Order?';
    let text = "Seluruh pemeriksaan dalam nomor order ini akan dihapus.";
    let url = `/ralan/delete-lab/${noorder}`;

    if (kd_jenis_prw && !id_template) {
        title = 'Hapus Pemeriksaan?';
        text = "Jenis pemeriksaan ini dan detailnya akan dihapus.";
        url += `/${kd_jenis_prw}`;
    } else if (id_template) {
        title = 'Hapus Item?';
        text = "Hanya item pemeriksaan ini yang akan dihapus.";
        url += `/${kd_jenis_prw}/${id_template}`;
    }

    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                method: 'POST',
                data: { _token: window.RALAN.csrf, _method: 'DELETE' },
                success: function(res) {
                    tampilkanSukses(res.message);
                    loadFormLab(true);
                },
                error: function(xhr) {
                    tampilkanError(xhr.responseJSON?.message || "Gagal menghapus.");
                }
            });
        }
    });
}

function hapusRadiologi(noorder, kd_jenis_prw = null) {
    let title = 'Hapus Order?';
    let text = "Seluruh pemeriksaan dalam nomor order ini akan dihapus.";
    let url = `/ralan/delete-radiologi/${noorder}`;

    if (kd_jenis_prw) {
        title = 'Hapus Pemeriksaan?';
        text = "Jenis pemeriksaan ini akan dihapus dari order.";
        url += `/${kd_jenis_prw}`;
    }

    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: window.RALAN.csrf,
                    _method: 'DELETE'
                },
                success: function(res) {
                    tampilkanSukses(res.message);
                    loadFormRadiologi(true);
                },
                error: function(xhr) {
                    tampilkanError(xhr.responseJSON?.message || "Gagal menghapus.");
                }
            });
        }
    });
}

function resetFormUmum() {
    var form = $('#formResepObat');
    clearRemoteSelect('.kd_obat_ajax');
    form.find('input[name="jumlah"]').val('1');
    clearRemoteSelect('.select2-aturan');
}

function resetFormRacikan() {
    var form = $('#formResepRacikan');
    form.find('input[name="nama_racik"]').val('');
    form.find('select[name="kd_racik"]').prop('selectedIndex', 0);
    form.find('input[name="jml_dr"]').val('10');
    clearRemoteSelect('.select2-aturan-racik');
    $('#aturanManualRacik').addClass('d-none');
    form.find('input[name="aturan_racik_lainnya"]').val('');

    $('#tableKomposisi tbody').html(
        '<tr class="text-center text-muted">' +
        '<td colspan="6" class="py-3">' +
        '<i class="fas fa-info-circle me-1"></i> ' +
        'Belum ada komposisi obat. Klik tombol "Tambah Baris" untuk menambahkan.' +
        '</td>' +
        '</tr>'
    );

    rowCount = 0;
}

function hitungJmlBaris(tr) {
    let p1 = parseFloat(tr.find('.p1').val()) || 0;
    let p2 = parseFloat(tr.find('.p2').val()) || 1;
    let jml_dr = parseFloat($('#jml_dr').val()) || 0;
    let hasil = (p1 / p2) * jml_dr;
    tr.find('.jml-hitung').val(hasil.toFixed(2));
}

function hapusBaris(rowId) {
    console.log('Hapus baris:', rowId);
    $('#row_' + rowId).remove();

    if ($('#tableKomposisi tbody tr').length === 0) {
        $('#tableKomposisi tbody').html(
            '<tr class="text-center text-muted">' +
            '<td colspan="6" class="py-3">' +
            '<i class="fas fa-info-circle me-1"></i> ' +
            'Belum ada komposisi obat. Klik tombol "Tambah Baris" untuk menambahkan.' +
            '</td>' +
            '</tr>'
        );
    }
}

$('a[href="#pemeriksaan-soap"]').on('shown.bs.tab', function (e) {
    loadSoap();
});

$('a[href="#diagnosa-prosedur"]').on('shown.bs.tab', function (e) {
    loadDiagnosaProsedur();
});

$('a[href="#pemeriksaan-vital-sign"]').on('shown.bs.tab', function (e) {
    loadVital();
});

$('a[href="#resep"]').on('shown.bs.tab', function (e) {
    loadResep();
});

$('a[href="#permintaan-lab"]').on('shown.bs.tab', function (e) {
    loadFormLab();
});

$('a[href="#permintaan-radiologi"]').on('shown.bs.tab', function (e) {
    loadFormRadiologi();
});

$(document).ready(function() {
    console.log('=== RESEP MODULE LOADED ===');
    console.log('Current no_rawat:', currentNoRawat);
    console.log('SweetAlert2:', typeof Swal !== 'undefined');
    console.log('SweetAlert1:', typeof swal !== 'undefined');

    $(document).on('submit', '#formSoap', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.RALAN.routes.soapSimpan,
            method: 'POST',
            data: form.serialize(),
            success: function(res) {
                btn.prop('disabled', false).html(originalText);
                tampilkanSukses(res.message || 'Data SOAP berhasil disimpan');
                loadSoap(true);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                tampilkanError(xhr.responseJSON?.message || 'Gagal menyimpan SOAP.');
            }
        });
    });

    $(document).on('submit', '#formVital', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.RALAN.routes.vitalSimpan,
            method: 'POST',
            data: form.serialize(),
            success: function(res) {
                btn.prop('disabled', false).html(originalText);
                tampilkanSukses(res.message || 'Data Vital Sign berhasil disimpan');
                loadVital(true);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                tampilkanError(xhr.responseJSON?.message || 'Gagal menyimpan Vital Sign.');
            }
        });
    });

    // Tambah obat ke daftar staging (belum simpan)
    $(document).on('click', '#btnTambahObat', function() {
        var form = $('#formResepObat');
        var obatEl = document.querySelector('.kd_obat_ajax');
        var obatTs = obatEl ? obatEl.tomselect : null;
        var kode = obatTs ? obatTs.getValue() : '';
        var jumlah = form.find('input[name="jumlah"]').val();
        var aturanEl = document.querySelector('.select2-aturan');
        var aturan = (aturanEl && aturanEl.tomselect) ? aturanEl.tomselect.getValue() : form.find('select[name="aturan_pakai"]').val();

        if (!kode) { tampilkanError('Silakan pilih obat terlebih dahulu.'); return; }
        if (!jumlah || jumlah <= 0) { tampilkanError('Jumlah obat harus lebih dari 0.'); return; }
        if (!aturan) { tampilkanError('Silakan isi aturan pakai.'); return; }
        if ($('#staging-obat tr[data-kode="' + kode + '"]').length) { tampilkanError('Obat sudah ada di daftar.'); return; }

        var nama = (obatTs.options[kode] ? obatTs.options[kode].text : kode);
        var esc = function(s) { return $('<div>').text(s).html(); };

        $('#staging-obat .staging-empty').remove();
        $('#staging-obat').append(
            '<tr data-kode="' + esc(kode) + '" data-jumlah="' + esc(jumlah) + '" data-aturan="' + esc(aturan) + '">' +
            '<td class="small">' + esc(nama) + '</td>' +
            '<td class="small">' + esc(jumlah) + '</td>' +
            '<td class="small">' + esc(aturan) + '</td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 staging-remove"><i class="fas fa-times"></i></button></td>' +
            '</tr>'
        );

        resetFormUmum();
    });

    // Simpan semua obat non-racikan sekaligus
    $(document).on('click', '#btnSimpanResepObat', function() {
        var rows = $('#staging-obat tr[data-kode]');
        if (!rows.length) { tampilkanError('Belum ada obat di daftar.'); return; }

        var obat = [];
        rows.each(function() {
            obat.push({
                kode_obat: $(this).attr('data-kode'),
                jumlah: $(this).attr('data-jumlah'),
                aturan_pakai: $(this).attr('data-aturan')
            });
        });

        var btn = $(this);
        var original = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.RALAN.routes.storeResepObat,
            method: 'POST',
            data: {
                _token: window.RALAN.csrf,
                no_rawat: $('#formResepObat input[name="no_rawat"]').val(),
                obat: obat
            },
            success: function(res) {
                btn.prop('disabled', false).html(original);
                tampilkanSukses(res.message || 'Resep obat berhasil disimpan');
                loadResep(true);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(original);
                tampilkanError(xhr.responseJSON?.message || 'Gagal menyimpan resep.');
            }
        });
    });

    $(document).on('change', '.select2-aturan', function() {
        console.log('Aturan pakai umum changed:', $(this).val());

        if ($(this).val() == 'lainnya') {
            $('#aturanManualUmum').removeClass('d-none');
            $('#aturanManualUmum input').focus();
        } else {
            $('#aturanManualUmum').addClass('d-none');
            $('#aturanManualUmum input').val('');
        }
    });

    $(document).on('change', '.select2-aturan-racik', function() {
        console.log('Aturan pakai racikan changed:', $(this).val());

        if ($(this).val() == 'lainnya') {
            $('#aturanManualRacik').removeClass('d-none');
            $('#aturanManualRacik input').focus();
        } else {
            $('#aturanManualRacik').addClass('d-none');
            $('#aturanManualRacik input').val('');
        }
    });

    $(document).on('click', '#btnTambahBarisObat', function() {
        console.log('=== TAMBAH BARIS OBAT RACIKAN ===');

        if ($('#tableKomposisi tbody tr td[colspan="6"]').length > 0) {
            $('#tableKomposisi tbody').html('');
        }

        rowCount++;
        console.log('Row count:', rowCount);

        let row = `
            <tr id="row_${rowCount}">
                <td>
                    <select name="detail_obat[${rowCount}][kode_brng]" class="form-control form-control-sm kd_obat_racik_${rowCount}" style="width:100%"></select>
                </td>
                <td>
                    <input type="number" step="0.01" name="detail_obat[${rowCount}][p1]" class="form-control form-control-sm p1" value="1" min="0">
                </td>
                <td>
                    <input type="number" step="0.01" name="detail_obat[${rowCount}][p2]" class="form-control form-control-sm p2" value="1" min="1">
                </td>
                <td>
                    <input type="text" name="detail_obat[${rowCount}][kandungan]" class="form-control form-control-sm" placeholder="mg/ml">
                </td>
                <td>
                    <input type="text" name="detail_obat[${rowCount}][jml]" class="form-control form-control-sm jml-hitung" readonly>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(${rowCount})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#tableKomposisi tbody').append(row);

        (function(rc) {
            initRemoteSelect('.kd_obat_racik_' + rc, {
                url: window.RALAN.routes.searchObat,
                multiple: false,
                minLen: 3,
                placeholder: 'Ketik Nama Obat...'
            });
            hitungJmlBaris($('#row_' + rc));
        })(rowCount);
    });

    $(document).on('input', '.p1, .p2, #jml_dr', function() {
        let tr = $(this).closest('tr');

        if($(this).attr('id') === 'jml_dr') {
            $('#tableKomposisi tbody tr').each(function() {
                if (!$(this).find('td[colspan]').length) {
                    hitungJmlBaris($(this));
                }
            });
        } else {
            hitungJmlBaris(tr);
        }
    });

    $(document).on('click', '#btnSimpanRacikan', function(e) {
        e.preventDefault();
        console.log('=== SIMPAN RACIKAN ===');

        var form = $('#formResepRacikan');
        var namaRacik = form.find('input[name="nama_racik"]').val();
        var jmlDr = form.find('input[name="jml_dr"]').val();
        var aturanSelect = form.find('select[name="aturan_racik"]').val();
        var aturanManual = form.find('input[name="aturan_racik_lainnya"]').val();

        if (!namaRacik) {
            tampilkanError("Nama racikan harus diisi");
            return false;
        }

        if (!jmlDr || jmlDr <= 0) {
            tampilkanError("Jumlah racik harus lebih dari 0");
            return false;
        }

        var aturanRacik = aturanSelect === 'lainnya' ? aturanManual : aturanSelect;
        if (!aturanRacik) {
            tampilkanError("Aturan pakai harus diisi");
            return false;
        }

        var jumlahBaris = $('#tableKomposisi tbody tr:not(:has(td[colspan]))').length;
        if (jumlahBaris === 0) {
            tampilkanError("Tambahkan minimal 1 obat untuk komposisi racikan");
            return false;
        }

        var formData = form.find('input, select').serialize();

        var btn = $(this);
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.RALAN.routes.storeResepRacikan,
            method: "POST",
            data: formData,
            success: function(response) {
                console.log('Success:', response);
                btn.prop('disabled', false).html(originalText);

                if(response.status === 'success' || response.status === 'success-racik') {
                    tampilkanSukses(response.message || 'Resep racikan berhasil disimpan');
                    resetFormRacikan();
                    loadResep(true);
                } else {
                    tampilkanError(response.message || "Gagal menyimpan resep racikan");
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                btn.prop('disabled', false).html(originalText);

                var errorMsg = "Gagal menyimpan resep racikan";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 422) {
                    errorMsg = "Data yang diinput tidak valid";
                } else if (xhr.status === 500) {
                    errorMsg = "Terjadi kesalahan server";
                }

                tampilkanError(errorMsg);
            }
        });
    });

    $(document).on('change', '#select-lab', function() {
        console.log('Select lab changed:', $(this).val());

        let selectedValues = $(this).val();
        let container = $('#list-template-checkbox');
        let placeholder = $('#detail-pemeriksaan-placeholder');

        if (!selectedValues || selectedValues.length === 0) {
            container.empty();
            placeholder.show();
            return;
        }

        placeholder.hide();

        container.find('.group-template').each(function() {
            let kd = $(this).data('kd');
            if (!selectedValues.includes(kd)) {
                $(this).remove();
            }
        });

        selectedValues.forEach(function(kd) {
            if (container.find(`.group-template[data-kd="${kd}"]`).length === 0) {
                $.get(`/ralan/get-templates-lab/${kd}`, function(data) {
                    if (data.length > 0) {
                        let html = `<div class="group-template mb-3" data-kd="${kd}">
                            <div class="group-header">
                                <div class="group-title">
                                    <i class="fas fa-vial"></i> ${kd}
                                </div>
                                <div class="badge-count bg-secondary">
                                    <i class="fas fa-check-circle"></i> 0 / ${data.length} dipilih
                                </div>
                            </div>
                            <div class="checkbox-controls">
                                <button type="button" class="btn btn-check-all btn-check-control btn-pilih-semua" data-kd="${kd}">
                                    <i class="fas fa-check-double"></i> Pilih Semua
                                </button>
                                <button type="button" class="btn btn-uncheck-all btn-check-control btn-batalkan-semua" data-kd="${kd}">
                                    <i class="fas fa-times"></i> Batalkan Semua
                                </button>
                            </div>
                            <div class="checkbox-list">`;

                        data.forEach(function(item) {
                            html += `
                                <div class="form-check small">
                                    <input class="form-check-input" type="checkbox"
                                        name="detail_lab[${kd}][]"
                                        value="${item.id_template}"
                                        id="chk_${item.id_template}"
                                        data-kd="${kd}">
                                    <label class="form-check-label" for="chk_${item.id_template}">
                                        ${item.Pemeriksaan}
                                    </label>
                                </div>`;
                        });

                        html += `</div>`;
                        container.append(html);
                    }
                });
            }
        });
    });

    $(document).on('change', '.group-template .form-check-input', function() {
        let groupElement = $(this).closest('.group-template');
        updateCheckboxCounter(groupElement);
    });

    $(document).on('click', '.btn-pilih-semua', function() {
        let kd = $(this).data('kd');
        let groupElement = $(this).closest('.group-template');

        groupElement.find(`.form-check-input[data-kd="${kd}"]`).prop('checked', true);
        updateCheckboxCounter(groupElement);

        $(this).html('<i class="fas fa-check"></i> Terpilih!');
        setTimeout(() => {
            $(this).html('<i class="fas fa-check-double"></i> Pilih Semua');
        }, 1000);
    });

    $(document).on('click', '.btn-batalkan-semua', function() {
        let kd = $(this).data('kd');
        let groupElement = $(this).closest('.group-template');

        groupElement.find(`.form-check-input[data-kd="${kd}"]`).prop('checked', false);
        updateCheckboxCounter(groupElement);

        $(this).html('<i class="fas fa-check"></i> Dibatalkan!');
        setTimeout(() => {
            $(this).html('<i class="fas fa-times"></i> Batalkan Semua');
        }, 1000);
    });

    $(document).on('click', '#btnSimpanLab', function(e) {
        e.preventDefault();
        console.log('=== SIMPAN LAB CLICKED ===');

        let btn = $(this);
        let formContainer = $('#formPermintaanLab');

        let data = {
            _token: $('meta[name="csrf-token"]').attr('content') || window.RALAN.csrf,
            no_rawat: formContainer.find('input[name="no_rawat"]').val(),
            kd_jenis_prw: $('#select-lab').val(),
            diagnosa_klinis: formContainer.find('textarea[name="diagnosa_klinis"]').val(),
            informasi_tambahan: formContainer.find('textarea[name="informasi_tambahan"]').val(),
            detail_lab: {}
        };

        console.log('Data before checkbox mapping:', data);

        formContainer.find('input[type="checkbox"]:checked').each(function() {
            let name = $(this).attr('name');
            let match = name.match(/\[(.*?)\]/);
            if (match) {
                let kd = match[1];
                if (!data.detail_lab[kd]) data.detail_lab[kd] = [];
                data.detail_lab[kd].push($(this).val());
            }
        });

        console.log('Data after checkbox mapping:', data);

        if (!data.kd_jenis_prw || data.kd_jenis_prw.length === 0) {
            tampilkanError("Pilih minimal satu pemeriksaan lab.");
            return;
        }

        let originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');

        $.ajax({
            url: window.RALAN.routes.storeLab,
            method: "POST",
            data: data,
            success: function(res) {
                console.log('=== LAB SAVE SUCCESS ===');
                console.log('Response:', res);

                btn.prop('disabled', false).html(originalText);

                tampilkanSukses(res.message || 'Permintaan lab berhasil disimpan');

                clearRemoteSelect('#select-lab');
                formContainer.find('textarea').val('');
                $('#list-template-checkbox').empty();
                $('#detail-pemeriksaan-placeholder').show();

                loadFormLab(true);
            },
            error: function(xhr) {
                console.error('=== LAB SAVE ERROR ===');
                console.error('Response:', xhr.responseText);

                btn.prop('disabled', false).html(originalText);

                let errorMsg = "Terjadi kesalahan saat menyimpan.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 422) {
                    errorMsg = "Data yang diinput tidak valid";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errors = xhr.responseJSON.errors;
                        errorMsg += ":\n" + Object.values(errors).flat().join("\n");
                    }
                }

                tampilkanError(errorMsg);
            }
        });
    });

    $(document).on('click', '#btnSimpanRad', function(e) {
        e.preventDefault();
        console.log('=== SIMPAN RADIOLOGI CLICKED ===');

        let btn = $(this);
        let formContainer = $('#formPermintaanRad'); // ID div utama radiologi

        let data = {
            _token: $('meta[name="csrf-token"]').attr('content') || window.RALAN.csrf,
            no_rawat: formContainer.find('input[name="no_rawat"]').val(),
            kd_jenis_prw_rad: $('#select-rad').val(),
            diagnosa_klinis: formContainer.find('textarea[name="diagnosa_klinis"]').val(),
            informasi_tambahan: formContainer.find('textarea[name="informasi_tambahan"]').val()
        };

        if (!data.kd_jenis_prw_rad || data.kd_jenis_prw_rad.length === 0) {
            tampilkanError("Pilih minimal satu pemeriksaan radiologi.");
            return;
        }

        let originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');

        $.ajax({
            url: window.RALAN.routes.storeRadiologi,
            method: "POST",
            data: data,
            success: function(res) {
                console.log('=== RADIOLOGI SAVE SUCCESS ===');
                btn.prop('disabled', false).html(originalText);

                tampilkanSukses(res.message || 'Permintaan radiologi berhasil disimpan');

                clearRemoteSelect('#select-rad');
                formContainer.find('textarea').val('');

                loadFormRadiologi(true);
            },
            error: function(xhr) {
                console.error('=== RADIOLOGI SAVE ERROR ===');
                btn.prop('disabled', false).html(originalText);
                tampilkanError(xhr.responseJSON?.message || "Gagal menyimpan permintaan radiologi.");
            }
        });
    });

    $(document).on('click', '.staging-remove', function() {
        var tbody = $(this).closest('tbody');
        $(this).closest('tr').remove();
        if (tbody.find('tr[data-kode]').length === 0) {
            var cols = tbody.data('cols') || 3;
            var empty = tbody.data('empty') || 'Kosong';
            tbody.append('<tr class="staging-empty"><td colspan="' + cols + '" class="text-center text-muted small py-2">' + empty + '</td></tr>');
        }
    });

    $(document).on('click', '#btn-simpan-diagnosa', function() {
        var kd = stagingKode('#staging-diagnosa');
        if (!kd.length) { tampilkanError('Belum ada diagnosa dipilih.'); return; }

        var btn = $(this);
        var original = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.RALAN.routes.storeDiagnosa,
            method: 'POST',
            data: { _token: window.RALAN.csrf, no_rawat: currentNoRawat, kd_penyakit: kd },
            success: function(res) {
                btn.prop('disabled', false).html(original);
                tampilkanSukses(res.message);
                loadDiagnosaProsedur(true);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(original);
                tampilkanError(xhr.responseJSON?.message || 'Gagal menyimpan diagnosa.');
            }
        });
    });

    $(document).on('click', '#btn-simpan-prosedur', function() {
        var rows = $('#staging-prosedur tr[data-kode]');
        if (!rows.length) { tampilkanError('Belum ada prosedur dipilih.'); return; }

        var kode = [], jumlah = [];
        rows.each(function() {
            kode.push($(this).data('kode'));
            jumlah.push($(this).find('.staging-jumlah').val() || '');
        });

        var btn = $(this);
        var original = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.RALAN.routes.storeProsedur,
            method: 'POST',
            data: { _token: window.RALAN.csrf, no_rawat: currentNoRawat, kode: kode, jumlah: jumlah },
            success: function(res) {
                btn.prop('disabled', false).html(original);
                tampilkanSukses(res.message);
                loadDiagnosaProsedur(true);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(original);
                tampilkanError(xhr.responseJSON?.message || 'Gagal menyimpan prosedur.');
            }
        });
    });

    $(document).on('click', '.btn-hapus-diagnosa', function() {
        let kd = $(this).data('kd');
        if (confirm('Hapus diagnosa ini?')) {
            $.ajax({
                url: '/ralan/delete-diagnosa/' + currentSafeNoRawat + '/' + kd,
                method: 'DELETE',
                data: { _token: window.RALAN.csrf },
                success: function(res) {
                    tampilkanSukses(res.message);
                    loadDiagnosaProsedur(true);
                },
                error: function(xhr) {
                    tampilkanError(xhr.responseJSON?.message || "Gagal menghapus diagnosa.");
                }
            });
        }
    });

    $(document).on('click', '.btn-hapus-prosedur', function() {
        let kode = $(this).data('kode');
        if (confirm('Hapus prosedur ini?')) {
            $.ajax({
                url: '/ralan/delete-prosedur/' + currentSafeNoRawat + '/' + kode,
                method: 'DELETE',
                data: { _token: window.RALAN.csrf },
                success: function(res) {
                    tampilkanSukses(res.message);
                    loadDiagnosaProsedur(true);
                },
                error: function(xhr) {
                    tampilkanError(xhr.responseJSON?.message || "Gagal menghapus prosedur.");
                }
            });
        }
    });

    // Background preloading removed to prevent server bottleneck.
    // Tabs will load instantly via lazy loading when clicked.

    console.log('=== ALL EVENT HANDLERS REGISTERED ===');
});

window.hapusObat = hapusObat;
window.hapusRacikan = hapusRacikan;
window.hapusLab = hapusLab;
window.hapusRadiologi = hapusRadiologi;
window.hapusBaris = hapusBaris;
window.resetFormUmum = resetFormUmum;
window.resetFormRacikan = resetFormRacikan;
