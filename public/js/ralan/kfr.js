/*
 * Formulir KFR di tab SOAP ralan (partial ralan/partials/kfr.blade.php).
 * Dimuat ulang setiap form SOAP dimuat lewat AJAX, jadi handler dipasang sekali di document.
 */
(function () {
    if (window.KfrRalan) return;

    /** Aturan sama dengan App\Support\KfrFormat::pecahInstruksi. */
    function pecahInstruksi(teks) {
        var gabung = String(teks || '').split(/\r\n|\r|\n/)
            .map(function (b) { return b.trim(); })
            .filter(function (b) { return b !== ''; })
            .join(' ').trim();
        if (gabung === '' || gabung === '-') return { frekuensi: '', durasi: '' };
        var koma = gabung.indexOf(',');
        if (koma === -1) return { frekuensi: gabung, durasi: '' };
        return { frekuensi: gabung.slice(0, koma).trim(), durasi: gabung.slice(koma + 1).trim() };
    }

    function perbaruiPratinjau(teks) {
        var hasil = pecahInstruksi(teks);
        $('#kfr-frekuensi').text(hasil.frekuensi || 'belum diisi');
        $('#kfr-durasi').text(hasil.durasi || 'belum diisi');
    }

    $(document).on('input', '#ralan_soap_instruksi', function () {
        if ($('#kfr-kartu').length) perbaruiPratinjau(this.value);
    });

    $(document).on('change', 'input[name="kfr[suspek]"]', function () {
        var ya = this.value === 'Ya' && this.checked;
        var wadah = $('#kfr-ket-suspek-wadah');
        wadah.prop('hidden', !ya);
        if (ya) wadah.find('input').trigger('focus');
    });

    // Tombol cetak nonaktif sebelum formulir tersimpan; aria-disabled supaya tetap terbaca pembaca layar.
    $(document).on('click', '#kfr-kartu .kfr-aksi .btn[aria-disabled="true"]', function (e) {
        e.preventDefault();
    });

    window.KfrRalan = {
        pecahInstruksi: pecahInstruksi,
        /** Dipanggil handler simpan SOAP (ralan-core.js) bila respons membawa kfr. */
        tandaiTersimpan: function (kfr) {
            $('#kfr-status').removeClass('resume-status-tunggu').addClass('resume-status-ok')
                .text('Tersimpan ' + kfr.disimpanPada);
            $('#kfr-catatan-sumber').remove();
        }
    };
})();
