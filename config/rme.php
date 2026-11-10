<?php

return [
    'igd' => [
        'kd_poli' => 'IGDK',
        // Dokter IGD dikenali dari awalan kode dokter; kolom spesialis di tabel dokter tidak konsisten.
        'prefix_dokter' => 'DU.',
    ],

    'obat' => [
        // Satu-satunya gudang yang berisi stok nyata; pemberian obat dari IGD juga memotong stok ini.
        'kd_bangsal_stok' => 'APT',
    ],
];
