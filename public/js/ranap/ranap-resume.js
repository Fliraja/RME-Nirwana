/* Tab Resume rawat inap: referensi per isian, progres, baris diagnosis/prosedur, simpan draf/selesai.
   Dipanggil loadResume() di ranap-core.js setelah fragmen tab dimuat. */

var RESUME_LABEL_STATUS = { belum: 'Belum dibuat', draf: 'Draf', selesai: 'Selesai' };

function barisResumeRanapHtml(nama, nilai) {
    var $baris = $(
        '<div class="resume-baris">' +
        '<span class="resume-baris-label"></span>' +
        '<input type="text" class="form-control form-control-sm" maxlength="80">' +
        '<div class="btn-group btn-group-sm" role="group">' +
        '<button type="button" class="btn btn-outline-secondary resume-naik" title="Naikkan urutan" aria-label="Naikkan">↑</button>' +
        '<button type="button" class="btn btn-outline-secondary resume-turun" title="Turunkan urutan" aria-label="Turunkan">↓</button>' +
        '<button type="button" class="btn btn-outline-danger resume-hapus" title="Hapus baris" aria-label="Hapus baris">✕</button>' +
        '</div></div>'
    );
    $baris.find('input').attr('name', nama + '[]').attr('aria-label', nama === 'diagnosa' ? 'Diagnosis' : 'Prosedur').val(nilai || '');
    return $baris;
}

function nomoriBarisResumeRanap() {
    $('#wrapperResumeRanap .resume-daftar').each(function() {
        var $daftar = $(this);
        var maks = parseInt($daftar.data('maks'), 10) || 5;
        var $baris = $daftar.children('.resume-baris');
        $baris.each(function(i) {
            $(this).find('.resume-baris-label').text(i === 0 ? 'Utama' : 'Sekunder ' + i).toggleClass('utama', i === 0);
        });
        $daftar.nextAll('.resume-tambah').first().prop('disabled', $baris.length >= maks);
    });
}

/** Isi baris daftar dengan teks: pakai baris kosong pertama, atau tambah baris bila masih di bawah batas. */
function tambahKeDaftarResume($daftar, teks) {
    var $kosong = $daftar.find('input').filter(function() { return !this.value.trim(); }).first();
    if ($kosong.length) {
        $kosong.val(teks);
        return true;
    }
    var maks = parseInt($daftar.data('maks'), 10) || 5;
    if ($daftar.children('.resume-baris').length >= maks) {
        tampilkanError('Maksimal ' + maks + ' baris.');
        return false;
    }
    $daftar.append(barisResumeRanapHtml($daftar.data('nama'), teks));
    return true;
}

function isiDaftarResume($daftar, nilai) {
    $daftar.empty();
    (nilai.length ? nilai : ['']).forEach(function(teks) {
        $daftar.append(barisResumeRanapHtml($daftar.data('nama'), teks));
    });
}

function hitungProgresResume() {
    var $form = $('#formResumePasienRanap');
    var jumlah = 0;
    $('#wrapperResumeRanap [data-isian]').each(function() {
        var $isian = $(this);
        var terisi = String($isian.data('kolom')).split(' ').some(function(nama) {
            var $field = $form.find('[name="' + nama + '"]');
            if ($field.is(':radio')) return $field.filter(':checked').length > 0;
            return $field.toArray().some(function(el) { return el.value.trim() !== ''; });
        });
        if (terisi) jumlah++;
        $isian.find('.resume-isian-tanda')
            .toggleClass('terisi', terisi).toggleClass('kosong', !terisi)
            .html(terisi ? '<i class="fas fa-check me-1" aria-hidden="true"></i>terisi' : 'belum diisi');
    });
    $('#resumeProgresTeks').text(jumlah + ' dari 10 isian terisi');
    $('#wrapperResumeRanap .resume-progres-jalur').attr('aria-valuenow', jumlah).children('span').css('width', (jumlah * 10) + '%');
}

/** Chip yang teksnya sudah ada di daftar disembunyikan agar tidak dipakai dua kali. */
function sembunyikanChipTerpakai() {
    $('#wrapperResumeRanap .resume-ref[data-target="diagnosa"], #wrapperResumeRanap .resume-ref[data-target="prosedur"]').each(function() {
        var nama = $(this).data('target');
        var ada = $('#resume-ranap-' + nama + ' input').toArray().map(function(el) { return el.value.trim().toLowerCase(); });
        $(this).find('.resume-chip').each(function() {
            $(this).toggleClass('d-none', ada.indexOf(String($(this).attr('data-teks')).toLowerCase()) !== -1);
        });
    });
}

function muatReferensiResume() {
    var $kotak = $('#wrapperResumeRanap .resume-ref');
    $kotak.html('<div class="resume-ref-kosong"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat referensi…</div>');

    $.get(ranapUrl('/ranap/resume/referensi/' + currentSafeNoRawat), function(html) {
        var $isi = $('<div>').html(html);
        $kotak.each(function() {
            var $blok = $isi.find('[data-ref-isi="' + $(this).data('ref') + '"]');
            $(this).html($blok.length ? $blok.html() : '');
        });
        sembunyikanChipTerpakai();
    }).fail(function() {
        $kotak.html('<div class="resume-ref-kosong">Referensi gagal dimuat. ' +
            '<button type="button" class="btn btn-link btn-sm p-0 align-baseline resume-ref-coba">Coba lagi</button></div>');
    });
}

function tampilkanGalatResume(errors) {
    var $form = $('#formResumePasienRanap');
    var $pertama = null;
    $.each(errors, function(kunci, pesan) {
        var $field = kunci.indexOf('diagnosa') === 0 ? $('#resume-ranap-diagnosa')
            : kunci.indexOf('prosedur') === 0 ? $('#resume-ranap-prosedur')
            : kunci === 'kondisi_pulang_opsi' ? $form.find('[data-isian="kondisi"] .row').first()
            : $form.find('[name="' + kunci + '"]');
        if (!$field.length) return;
        $field.addClass('is-invalid');
        $('<div class="resume-galat" role="alert">').text(pesan[0]).insertAfter($field.last());
        if (!$pertama) $pertama = $field.first();
    });
    if ($pertama) $pertama[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function bersihkanGalatResume() {
    $('#formResumePasienRanap .resume-galat').remove();
    $('#formResumePasienRanap .is-invalid').removeClass('is-invalid');
}

function pulihkanDrafResume(data) {
    var $form = $('#formResumePasienRanap');
    pulihkanDraf($form, data);
    ['diagnosa', 'prosedur'].forEach(function(nama) {
        var nilai = data[nama + '[]'];
        if (nilai === undefined) return;
        isiDaftarResume($('#resume-ranap-' + nama), Array.isArray(nilai) ? nilai : [nilai]);
    });
    nomoriBarisResumeRanap();
    sembunyikanChipTerpakai();
    hitungProgresResume();
}

function initResumeHandlers() {
    var $wrapper = $('#wrapperResumeRanap');
    var $form = $('#formResumePasienRanap');
    if (!$wrapper.length) return;

    if ($wrapper.data('boleh') === 1) {
        initAutoSaveDraft('#formResumePasienRanap', 'rme_draft_resume', '#formResumePasienRanap', pulihkanDrafResume);
    }
    nomoriBarisResumeRanap();
    hitungProgresResume();
    muatReferensiResume();

    $form.off('input.resume change.resume').on('input.resume change.resume', function() {
        hitungProgresResume();
        sembunyikanChipTerpakai();
    });

    $('#btnMuatUlangReferensi').off('click').on('click', muatReferensiResume);
    $wrapper.off('click', '.resume-ref-coba').on('click', '.resume-ref-coba', muatReferensiResume);

    $('#btnResumeKeCppt').off('click').on('click', function() {
        $('#soap-tab').tab('show');
    });

    // Kondisi pulang
    $form.off('change', '.radio-kondisi-pulang').on('change', '.radio-kondisi-pulang', function() {
        $form.find('.resume-kondisi').removeClass('aktif');
        $(this).closest('.resume-kondisi').addClass('aktif');
        var dirujuk = this.value === 'dirujuk';
        $('#containerNamaRujukan').toggleClass('d-none', !dirujuk);
        if (dirujuk) $('#resume_nama_faskes_rujukan').trigger('focus');
    });

    // "Pakai": isi kolom kosong, atau tambahkan di baris baru agar tulisan dokter tidak tertimpa.
    $wrapper.off('click', '.resume-pakai').on('click', '.resume-pakai', function() {
        var $btn = $(this);
        var target = $btn.data('target') || $btn.closest('.resume-ref').data('target');
        var $field = $form.find('[name="' + target + '"]');
        if (!$field.length) return;

        if ($field.attr('type') === 'date') {
            $field.val(String($btn.attr('data-tanggal') || '').substring(0, 10));
        } else {
            var lama = $field.val().trim();
            var teks = String($btn.attr('data-teks'));
            $field.val(lama ? lama + '\n' + teks : teks);
        }
        $field.trigger('input');
        $btn.prop('disabled', true).text('Dipakai');
    });

    $wrapper.off('click', '.resume-chip').on('click', '.resume-chip', function() {
        var nama = $(this).closest('.resume-ref').data('target');
        var $daftar = $('#resume-ranap-' + nama);
        if (tambahKeDaftarResume($daftar, String($(this).attr('data-teks')))) {
            nomoriBarisResumeRanap();
            sembunyikanChipTerpakai();
            $form.trigger('input');
        }
    });

    // Baris diagnosis/prosedur
    $wrapper.off('click', '.resume-tambah').on('click', '.resume-tambah', function() {
        var $daftar = $(this).prevAll('.resume-daftar').first();
        var $kosong = $daftar.find('input').filter(function() { return !this.value.trim(); }).first();
        if (!$kosong.length && tambahKeDaftarResume($daftar, '')) {
            nomoriBarisResumeRanap();
            $kosong = $daftar.find('input').last();
        }
        $kosong.trigger('focus');
    });
    $wrapper.off('click', '.resume-naik').on('click', '.resume-naik', function() {
        var $baris = $(this).closest('.resume-baris');
        $baris.prev('.resume-baris').before($baris);
        nomoriBarisResumeRanap();
        $baris.find('.resume-naik').trigger('focus');
        $form.trigger('input');
    });
    $wrapper.off('click', '.resume-turun').on('click', '.resume-turun', function() {
        var $baris = $(this).closest('.resume-baris');
        $baris.next('.resume-baris').after($baris);
        nomoriBarisResumeRanap();
        $baris.find('.resume-turun').trigger('focus');
        $form.trigger('input');
    });
    $wrapper.off('click', '.resume-hapus').on('click', '.resume-hapus', function() {
        var $daftar = $(this).closest('.resume-daftar');
        $(this).closest('.resume-baris').remove();
        if (!$daftar.children('.resume-baris').length) $daftar.append(barisResumeRanapHtml($daftar.data('nama'), ''));
        nomoriBarisResumeRanap();
        sembunyikanChipTerpakai();
        $form.trigger('input');
    });

    // Simpan draf / tandai selesai
    $form.off('click', 'button[type="submit"]').on('click', 'button[type="submit"]', function() {
        $form.data('selesai', $(this).data('selesai'));
    });
    $form.off('submit').on('submit', function(e) {
        e.preventDefault();
        var selesai = String($form.data('selesai')) === '1';
        var $btn = selesai ? $('#btnTandaiSelesai') : $('#btnSimpanDraf');
        var labelAsli = $btn.html();
        $form.find('button[type="submit"]').prop('disabled', true);
        $btn.html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Menyimpan…');
        bersihkanGalatResume();

        var data = $form.serializeArray();
        data.push({ name: 'selesai', value: selesai ? 1 : 0 });

        $.ajax({ url: ranapUrl('/ranap/store-resume-pasien'), type: 'POST', data: $.param(data) })
            .done(function(res) {
                tampilkanSukses(res.message);
                clearAutoSaveDraft('rme_draft_resume');
                $form.find('[name="diubah_pada"]').val(res.diubah_pada);
                var jam = res.diubah_pada.substring(8, 10) + '/' + res.diubah_pada.substring(5, 7) + ' ' + res.diubah_pada.substring(11, 16);
                $('#resumeInfoSimpan').text('Tersimpan ' + jam);
                $('#resumeDiubah').text('Terakhir diubah oleh Anda, ' + jam);
                $('#badgeStatusResume').text(RESUME_LABEL_STATUS[res.status_resume])
                    .toggleClass('resume-status-ok', res.status_resume === 'selesai')
                    .toggleClass('resume-status-tunggu', res.status_resume !== 'selesai');
                $('#btnCetakResumePdf').removeClass('disabled').removeAttr('aria-disabled tabindex');
            })
            .fail(function(xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) {
                    tampilkanGalatResume(res.errors);
                    tampilkanError('Periksa isian yang ditandai merah.');
                } else if (xhr.status === 409) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Resume sudah diubah',
                        text: res.message + ' Isian di layar Anda belum tersimpan.',
                        showCancelButton: true,
                        confirmButtonText: 'Muat ulang resume',
                        cancelButtonText: 'Tetap di sini',
                        confirmButtonColor: '#0d7a4a'
                    }).then(function(hasil) {
                        if (hasil.isConfirmed) loadResume(true);
                    });
                } else {
                    tampilkanError(res.message || 'Gagal menyimpan resume (HTTP ' + xhr.status + ').');
                }
            })
            .always(function() {
                $form.find('button[type="submit"]').prop('disabled', false);
                $btn.html(labelAsli);
            });
    });
}
