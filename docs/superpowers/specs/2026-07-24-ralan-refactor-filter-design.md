# Design: Refactor Ralan + Filter Pemeriksaan + Diagnosa/Prosedur ICD

**Tanggal:** 2026-07-24
**Branch:** `feature/ralan-refactor-filter`
**Status:** Draft — menunggu review

## 1. Latar Belakang & Tujuan

Modul Rawat Jalan (ralan) di RME-Nirwana sudah berjalan, tapi:

- Logika bisnis menumpuk di controller (fat controller), susah dirawat & di-reuse.
- UI form tidak konsisten: campur kelas Bootstrap 4 & 5 padahal yang dimuat Bootstrap 5.3.3. Select2 tanpa tema BS5 → kotak search rusak (ikon kaca pembesar turun).
- `index.blade.php` monolitik 1800 baris (HTML + JS + CSS jadi satu).
- Login error karena `CACHE_STORE=database` tanpa tabel `cache`.
- Fitur yang diminta belum benar/lengkap: filter pemeriksaan lab/radiologi per penjamin, tab diagnosa/prosedur ICD dengan primer otomatis.

Tujuan: rapikan arsitektur (pragmatis), selaraskan UI, lalu implementasikan perilaku baru yang sudah dirundingkan.

## 2. Ruang Lingkup

**Termasuk** (controller ralan + dashboard):
`LaboratoriumController`, `RadiologiController`, `DiagnosaProsedurController`, `DashboardController`, `RalanController` (SOAP + Vital Sign), `ReseController`/resep bila sempat.

**Tidak termasuk:** Report (sudah tidak dipakai; hanya cetak PDF riwayat yang masih nyangkut, dibiarkan), Login/Auth, controller di luar ralan.

**Tanpa tabel database baru.** Semua memakai skema Khanza yang ada (`db_test_rev`).

## 3. Arsitektur (Clean Architecture Pragmatis)

Controller jadi tipis: terima `Request` (validasi) → panggil `Service` (logika) → kembalikan JSON/view.

```
app/
  Services/Ralan/
    LaboratoriumService.php
    RadiologiService.php
    DiagnosaProsedurService.php
    SoapService.php
    VitalSignService.php
    DashboardService.php
  Support/
    PenjaminResolver.php      // kd_pj + status_lanjut -> prefix pemeriksaan
    KodeSearch.php            // normalisasi pencarian tanpa titik
    PrioritasResolver.php     // hitung primer via validcode
  Http/Requests/Ralan/
    StorePermintaanLabRequest.php
    StorePermintaanRadiologiRequest.php
    StoreDiagnosaRequest.php
    StoreProsedurRequest.php
    StoreSoapRequest.php
    StoreVitalSignRequest.php
```

Eloquent/Query Builder tetap berada di dalam Service (tidak menambah lapisan Repository/DTO).

## 4. Logika Filter Pemeriksaan (`PenjaminResolver`)

Payer/penjamin ditentukan dari `reg_periksa.kd_pj`; jenis rawat dari `reg_periksa.status_lanjut` (`Ralan`/`Ranap`).

Klasifikasi penjamin:
- `UM` → Umum
- `BPJ` → BPJS
- selain itu (`-`, `003`, `KPP`, dst) → Pihak Ketiga

### 4.1 Laboratorium
- Prefix: `Ranap` → `RI`, `Ralan` → `RJ`.
- Suffix: Umum → `U`, BPJS → `B`, Pihak Ketiga → `A`.
- Target prefix = prefix + suffix (mis. `RJA`).
- Query:
  ```sql
  status = '1'
  AND (
    kd_jenis_prw LIKE '{target}%'
    OR kd_jenis_prw NOT REGEXP '^R[IJ][UAB]'   -- kode legacy di luar skema tetap muncul
  )
  AND (kd_jenis_prw LIKE '%{search}%' OR nm_perawatan LIKE '%{search}%')
  ORDER BY (kd_jenis_prw LIKE '{target}%') DESC
  ```

### 4.2 Radiologi
- BPJS → `RAD.B` (ralan maupun ranap).
- Pihak Ketiga → `RAD.P`.
- Umum → `Ralan` = `RAD.RJ`, `Ranap` = `RAD.RI`.
- Query:
  ```sql
  status = '1'
  AND (
    kd_jenis_prw LIKE '{target}%'
    OR (kd_jenis_prw NOT LIKE 'RAD.RJ%' AND kd_jenis_prw NOT LIKE 'RAD.RI%'
        AND kd_jenis_prw NOT LIKE 'RAD.B%' AND kd_jenis_prw NOT LIKE 'RAD.P%') -- legacy tetap muncul
  )
  AND (kd_jenis_prw LIKE '%{search}%' OR nm_perawatan LIKE '%{search}%')
  ```

Catatan data: lab punya 196 kode `status=1`, radiologi 522; 4 prefix `RAD.*` masing-masing 103 kode aktif; ~110 kode radiologi aktif berada di luar 4 prefix (RJK/RNK/RAK) — tetap ditampilkan sesuai keputusan.

## 5. Search Tanpa Titik (`KodeSearch`)

Kode ICD dipisah titik (`A03.1`, `99.52`). Dokter mengetik tanpa titik (`9952`) harus tetap ketemu.

- `normalize($term)` = `str_replace(['.', ' '], '', $term)`.
- ICD-10 (`penyakit`): match `kd_penyakit` / `nm_penyakit` LIKE, plus `REPLACE(kd_penyakit,'.','') LIKE '%{normalized}%'`.
- ICD-9 (`icd9`): match `kode` / `deskripsi_panjang` / `deskripsi_pendek` LIKE, plus `REPLACE(kode,'.','') LIKE '%{normalized}%'`.

Search mengembalikan semua kode (valid maupun tidak); `validcode` hanya memengaruhi penentuan primer, bukan hasil pencarian.

## 6. Diagnosa & Prosedur ICD (`PrioritasResolver`)

Field `validcode` (`enum '0'/'1'`) ada di tabel `penyakit` dan `icd9`. Hanya `validcode='1'` yang boleh jadi primer (prioritas 1). Dokter TIDAK memilih urutan — sistem yang menentukan.

### 6.1 Aturan Primer
Saat simpan (dan saat hapus), hitung ulang seluruh set milik pasien untuk `status` (Ralan/Ranap) tertentu:

1. Susun daftar terurut: kode yang sudah tersimpan (urut `prioritas` asc) lalu kode baru (urut input), dedupe.
2. Primer = kode pertama yang `validcode='1'`.
3. Jika tidak ada satupun `validcode='1'` → primer = kode pertama diinput (fallback).
4. Primer → `prioritas=1`; sisanya `2,3,4,…` mengikuti urutan.

### 6.2 Penyimpanan
- Diagnosa (`diagnosa_pasien`): `no_rawat`, `kd_penyakit`, `status`, `prioritas`, `status_penyakit` (Lama/Baru, satu nilai batch).
- Prosedur (`prosedur_pasien`): `no_rawat`, `kode`, `status`, `prioritas`, `jumlah` (nullable, per baris).
- `updateOrInsert` supaya idempotent; recompute prioritas setelah insert & setelah delete.
- Delete scope by `status` (Ralan/Ranap sesuai `reg_periksa`) supaya tidak menghapus lintas jenis rawat.

## 7. UI / Frontend

Target: konsisten Bootstrap 5.3.3.

### 7.1 Standardisasi
- Ganti kelas BS4 → BS5 di semua form ralan: `form-group`→`mb-3`, `font-weight-bold`→`fw-bold`, `mr-*`/`ml-*`→`me-*`/`ms-*`, `text-left/right`→`text-start/end`, `<select class="form-control">`→`form-select`.
- Vital Sign: perbaiki alert BS4 (`data-dismiss`+`.close`) → BS5 (`data-bs-dismiss`+`.btn-close`).
- Tambah tema Select2 Bootstrap-5 (CSS di `admin.css`) → tinggi kotak search benar, ikon kaca pembesar tidak turun.
- Pindahkan `<style>` inline dari `resep.blade.php` ke `admin.css`.

### 7.2 Split `index.blade.php`
- Shell tab tetap di `index.blade.php`.
- Pindahkan blok `<script>` besar ke `public/js/ralan/*.js` (mis. `ralan-tabs.js`, `ralan-lab.js`, `ralan-diagnosa.js`).
- CSS inline → `admin.css`.
- Verifikasi semua tab tetap berfungsi setelah pemecahan (lazy-load, cache tab, select2).

### 7.3 SOAP & Vital Sign → AJAX
- Ubah submit dari native POST (full page reload) menjadi AJAX, konsisten dengan lab/rad/resep.
- Controller mengembalikan JSON; frontend reload konten tab + tampilkan toast, tanpa reload halaman.

### 7.4 Diagnosa/Prosedur Staging Table
- Select2 (search) → pilih satu kode → baris otomatis muncul di tabel staging di bawahnya.
- Prosedur: tiap baris staging punya input `Jumlah` (default 1, nullable).
- Tombol **Simpan Semua** mengirim batch (array kode + array jumlah) → backend simpan + hitung primer → reload daftar tersimpan.
- Dropdown Prioritas dihapus. `status_penyakit` (Lama/Baru) tetap satu dropdown batch.

## 8. Cache

`CACHE_STORE=file` di `.env` (dan default `config/cache.php`). Tidak membuat tabel `cache`. Caching stats dashboard (5 menit) tetap jalan via file driver.

## 9. Testing

- **Unit test** untuk logika murni tanpa DB:
  - `PenjaminResolver`: mapping (Umum/BPJS/Pihak Ketiga) × (Ralan/Ranap) → prefix lab & radiologi benar.
  - `KodeSearch::normalize`: `9952` → `9952`, `A03.1` → `A031`.
  - `PrioritasResolver`: berbagai kombinasi validcode → primer & urutan benar, termasuk fallback tanpa validcode.
- **Verifikasi manual** flow DB-bound ke `db_test_rev` live (feature test sqlite tidak punya skema Khanza).

## 10. Rencana Fase (1 branch, commit bertahap)

- **F0 — Hotfix cache:** ubah `CACHE_STORE=file`. Pastikan login normal.
- **F1 — Refactor + UI (behavior-preserving):**
  - Ekstrak Service/Request/Helper untuk lab, radiologi, diagnosa-prosedur, dashboard, SOAP, vital sign (pindahkan logika lama apa adanya dulu).
  - Standardisasi UI ke BS5 + tema Select2 + pindah CSS/JS.
  - Split `index.blade.php` + pisah JS.
  - AJAX-kan SOAP & Vital Sign.
- **F2 — Perilaku baru:**
  - Filter prefix payer lab & radiologi (`PenjaminResolver`).
  - Search dotless ICD-10/ICD-9 (`KodeSearch`).
  - Primer otomatis via validcode (`PrioritasResolver`) + jumlah per prosedur.
  - Staging-table UX diagnosa/prosedur.

Satu Pull Request di akhir, commit terurut per fase untuk memudahkan review.

## 11. Risiko & Catatan

- Data lab prefix `RJA/RIA` ber-`kd_pj='-'`; filter prefix menangani ini dengan benar (tidak bergantung `kd_pj`).
- Beberapa kode `RJA0xx` tampak seperti header kategori — mengikuti aturan prefix; bila mengganggu, dievaluasi terpisah.
- Pemecahan `index.blade.php` berisiko memutus binding JS/select2; wajib tes manual tiap tab.
- Koordinasi dengan kontributor lain (Fliraja) karena branch di-push ke repo bersama.
