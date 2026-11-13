/*
 * Isian aturan pakai resep ralan dan ranap: pilih dari master_aturan_pakai atau ketik baru.
 * Aturan baru disimpan server ke master bila muat di kolomnya (ResepService::simpanAturan).
 * Batas 150 = resep_dokter.aturan_pakai varchar(150); server juga menolak di atasnya (ResepController).
 */
(function () {
    'use strict';

    var MAKS = 150;

    window.AturanPakai = {
        MAKS: MAKS,

        pasang: function (el) {
            if (!el || !window.TomSelect) {
                return null;
            }
            if (el.tomselect) {
                el.tomselect.destroy();
            }
            return new TomSelect(el, {
                create: true,
                createOnBlur: true,
                createFilter: function (input) {
                    var teks = input.trim();
                    return teks.length > 0 && teks.length <= MAKS;
                },
                maxItems: 1,
                persist: false,
                placeholder: 'Pilih atau ketik aturan pakai',
                render: {
                    option_create: function (data, escape) {
                        return '<div class="create">Pakai aturan baru: <strong>' + escape(data.input) + '</strong></div>';
                    },
                    // Teks yang muat sudah ditawarkan sebagai "Pakai aturan baru"; pesan hanya untuk teks kepanjangan.
                    no_results: function (data) {
                        if (data.input.trim().length <= MAKS) {
                            return null;
                        }
                        return '<div class="no-results">Aturan pakai paling banyak ' + MAKS + ' karakter, sekarang ' + data.input.trim().length + '.</div>';
                    }
                }
            });
        }
    };
})();
