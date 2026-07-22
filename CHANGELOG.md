# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased] - 2026-07-22

### Added
- **Tab Input Diagnosa (ICD-10) & Prosedur (ICD-9) Terstruktur (Todo #4)**:
  - Membuat Model Eloquent `Penyakit`, `Icd9`, `DiagnosaPasien`, dan `ProsedurPasien`.
  - Membuat `DiagnosaProsedurController` untuk handle pencarian Autocomplete Select2 ICD-10/ICD-9, simpan data diagnosa & prosedur per kunjungan, serta hapus item.
  - Partial view `resources/views/ralan/diagnosa-prosedur.blade.php` dan tab baru "DIAGNOSA & PROSEDUR" di modul Rawat Jalan.
  - Feature test `tests/Feature/DiagnosaProsedurTest.php`.
- **Feature Test Filter Pemeriksaan**: Menambahkan `tests/Feature/PemeriksaanFilterTest.php` untuk memvalidasi filter pemeriksaan lab & radiologi.

### Changed
- **Form SOAP Input (Todo #4)**:
  - Menghapus textarea Assesmen (`penilaian`) dari form input aktif SOAP pada `resources/views/ralan/soap.blade.php`, digantikan penuh oleh tab Diagnosa & Prosedur terstruktur (sesuai Keputusan Q4). Data historis tetap aman di DB.
- **Filter Otomatis Lab & Radiologi (Todo #2)**:
  - Filtering pencarian lab (`LaboratoriumController::getPemeriksaan`) & radiologi (`RadiologiController::getPemeriksaanRadiologi`) mengutamakan kode tarif spesifik penjamin pasien (`kd_pj`) dari `reg_periksa`, fallback ke kode generik (`kd_pj='-'`).
  - Penentuan `status` order permintaan lab (`storePermintaanLab`) dan radiologi (`storePermintaanRadiologi`) secara dinamis (`'ranap'` vs `'ralan'`) sesuai `status_lanjut` pasien.
  - Penyesuaian `Select2` AJAX di `resources/views/ralan/index.blade.php` mengirimkan parameter `no_rawat`.
