# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased] - 2026-07-22

### Added
- **Feature Test Filter Pemeriksaan**: Menambahkan `tests/Feature/PemeriksaanFilterTest.php` untuk memvalidasi filter pemeriksaan lab & radiologi.

### Changed
- **Filter Otomatis Lab & Radiologi (Todo #2)**:
  - Filtering pencarian lab (`LaboratoriumController::getPemeriksaan`) & radiologi (`RadiologiController::getPemeriksaanRadiologi`) mengutamakan kode tarif spesifik penjamin pasien (`kd_pj`) dari `reg_periksa`, fallback ke kode generik (`kd_pj='-'`).
  - Penentuan `status` order permintaan lab (`storePermintaanLab`) dan radiologi (`storePermintaanRadiologi`) secara dinamis (`'ranap'` vs `'ralan'`) sesuai `status_lanjut` pasien.
  - Penyesuaian `Select2` AJAX di `resources/views/ralan/index.blade.php` mengirimkan parameter `no_rawat`.
