/*
 * Warna tombol SweetAlert untuk semua modul, supaya tidak ditulis ulang di tiap panggilan.
 * Bawaan SweetAlert ungu #7066e0; modul dulu memakai merah sendiri (#d33) dengan batal biru atau abu.
 *   Swal       : tombol OK / aksi utama hijau #0d7a4a (teks putih 5,38:1), batal abu #64748b (4,76:1).
 *   SwalHapus  : konfirmasi hapus atau batalkan, tombol merah #be123c (6,29:1), ikon peringatan, ada Batal.
 * Pemanggil tidak memberi confirmButtonColor/cancelButtonColor sendiri.
 */
(function () {
    'use strict';

    if (!window.Swal) {
        return;
    }

    var Dasar = window.Swal.mixin({
        confirmButtonColor: '#0d7a4a',
        cancelButtonColor: '#64748b',
        cancelButtonText: 'Batal'
    });

    window.Swal = Dasar;
    window.SwalHapus = Dasar.mixin({
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#be123c'
    });
})();
