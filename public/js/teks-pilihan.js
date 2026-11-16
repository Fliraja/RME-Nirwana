/*
 * Teks kotak cari Tom Select (obat, lab, radiologi) di IGD, ralan, dan ranap.
 * Tom Select tidak punya pengaturan bawaan global, jadi tiap pemanggil memasang render ini sendiri;
 * tanpa itu dropdown menulis "No results found".
 */
(function () {
    'use strict';

    window.TeksPilihan = {
        // render.no_results, contoh: "parasetamol tidak ditemukan di daftar obat."
        tidakDitemukan: function (daftar) {
            return function (data, escape) {
                return '<div class="no-results"><strong>' + escape(data.input) + '</strong> tidak ditemukan di ' + escape(daftar) + '.</div>';
            };
        }
    };
})();
