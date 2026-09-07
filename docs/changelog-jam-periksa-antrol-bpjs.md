# Log Perubahan: Implementasi Jam Periksa Antrol BPJS

**Branch**: `feat/jam-periksa-antrol-bpjs`  
**Tanggal**: 2026-09-04  
**Dokumen Referensi**: [`docs/implementasi-jam-periksa-antrol-bpjs.md`](file:///d:/laragon/www/RME-Nirwana/docs/implementasi-jam-periksa-antrol-bpjs.md)

---

## 1. Ringkasan Perubahan
Pencatatan jam periksa pasien BPJS ke tabel `antrol_bpjs`:
- **Jam Periksa Perawat (`jam_periksa_perawat`)**: Dicatat saat perawat/user mengklik nama pasien di daftar antrean rawat jalan (AJAX fast-send) dengan fallback server-side saat halaman detail terbuka. Bersifat *first-write-wins* (hanya mengisi jika kolom masih `NULL` atau `'00:00:00'`).
- **Jam Periksa Dokter (`jam_periksa_dokter`)**: Dicatat saat dokter menyimpan SOAP rawat jalan (`SoapService::simpan`). Selalu memperbarui ke jam submit terbaru dengan aturan BPJS: jeda minimal 61 detik di atas `jam_periksa_perawat`.
- **Keamanan Database**: Tidak ada truncate tabel, drop data, migration, maupun perubahan struktur tabel (aman untuk database produksi rumah sakit).

---

## 2. File yang Ditambahkan (New Files)

### 1. [`app/Models/AntrolBpjs.php`](file:///d:/laragon/www/RME-Nirwana/app/Models/AntrolBpjs.php)
- **Tujuan**: Model Eloquent untuk berinteraksi dengan tabel legacy `antrol_bpjs`.
- **Konfigurasi**:
  - `table = 'antrol_bpjs'`
  - `primaryKey = 'no_rawat'`
  - `incrementing = false`
  - `timestamps = false`
  - `guarded = []`

### 2. [`app/Support/AntrolBpjsUpdater.php`](file:///d:/laragon/www/RME-Nirwana/app/Support/AntrolBpjsUpdater.php)
- **Tujuan**: Service / helper class berisi logika update atomik jam periksa antrol BPJS.
- **Method**:
  - `tandaiPerawat(string $noRawat, ?string $kdPj, ?Carbon $waktu = null): void`
    - Cek guard `PenjaminResolver::BPJS`. Abaikan jika bukan BPJS.
    - Update atomik `jam_periksa_perawat` dengan kondisi `WHERE no_rawat = ? AND (jam_periksa_perawat IS NULL OR jam_periksa_perawat = '00:00:00')`.
    - Dilindungi `try/catch` agar tidak mengganggu transaksi/alur utama jika terjadi kendala pada tabel `antrol_bpjs`.
  - `hitungJamDokter(?string $jamPerawat, Carbon $now): string`
    - Pure function menghitung jam dokter: jika `jam_periksa_perawat` ada dan `now` < `jamPerawat + 61 detik`, kembalikan `jamPerawat + 61 detik`. Jika tidak, kembalikan `now`.
  - `tandaiDokter(string $noRawat, ?string $kdPj, ?Carbon $waktu = null): void`
    - Cek guard `PenjaminResolver::BPJS`.
    - Mengambil nilai `jam_periksa_perawat` terkini.
    - Menghitung waktu dokter dengan aturan 61 detik.
    - Update `jam_periksa_dokter` (overwrite waktu terbaru).
    - Dilindungi `try/catch`.

### 3. [`tests/Unit/Support/AntrolBpjsUpdaterTest.php`](file:///d:/laragon/www/RME-Nirwana/tests/Unit/Support/AntrolBpjsUpdaterTest.php)
- **Tujuan**: Unit testing logika perhitungan 61 detik jam dokter.
- **Test Cases**:
  - `test_hitung_jam_dokter_tanpa_jam_perawat`: Memastikan jam dokter sesuai waktu submit jika jam perawat kosong/`00:00:00`.
  - `test_hitung_jam_dokter_kurang_dari_61_detik`: Memastikan jika dokter submit < 61 detik dari perawat, jam dokter digeser otomatis menjadi `jam_perawat + 61 detik`.
  - `test_hitung_jam_dokter_tepat_dan_lebih_dari_61_detik`: Memastikan jika jeda >= 61 detik, jam submit aktual yang dicatat.

---

## 3. File yang Diubah (Modified Files)

### 1. [`routes/web.php`](file:///d:/laragon/www/RME-Nirwana/routes/web.php)
- **Perubahan**: Menambahkan route endpoint AJAX `POST /ralan/tandai-perawat` di dalam group middleware `multi.auth`:
  ```php
  Route::post('/ralan/tandai-perawat', [RalanController::class, 'tandaiPerawat'])->name('ralan.tandai-perawat');
  ```

### 2. [`app/Http/Controllers/RalanController.php`](file:///d:/laragon/www/RME-Nirwana/app/Http/Controllers/RalanController.php)
- **Perubahan**:
  - Import `App\Support\AntrolBpjsUpdater`.
  - Pada method `index()` saat `action == 'view'`, panggil `AntrolBpjsUpdater::tandaiPerawat($detailPasien->no_rawat, $detailPasien->kd_pj)` sebagai safety-net server-side.
  - Menambahkan method `tandaiPerawat(Request $request)` untuk menangani AJAX dari klik link nama pasien di browser.

### 3. [`resources/views/ralan/index.blade.php`](file:///d:/laragon/www/RME-Nirwana/resources/views/ralan/index.blade.php)
- **Perubahan**:
  - Pada tabel pendaftaran pasien (`<tbody>`), menambahkan class `pasien-link` serta atribut `data-no-rawat` dan `data-kd-pj` pada link nama pasien.
  - Mendaftarkan route `tandaiPerawat: "{{ route('ralan.tandai-perawat') }}"` pada objek `window.RALAN.routes`.

### 4. [`public/js/ralan/ralan-core.js`](file:///d:/laragon/www/RME-Nirwana/public/js/ralan/ralan-core.js)
- **Perubahan**: Menambahkan event listener click pada `.pasien-link`:
  - Jika pasien adalah BPJS (`kd_pj === 'BPJ'`), kirim request POST via `fetch` dengan header CSRF dan flag `keepalive: true` ke endpoint `tandaiPerawat`.
  - Navigasi tetap berlanjut ke detail pasien tanpa delay.

### 5. [`app/Services/Ralan/SoapService.php`](file:///d:/laragon/www/RME-Nirwana/app/Services/Ralan/SoapService.php)
- **Perubahan**:
  - Import `App\Models\RegPeriksa` dan `App\Support\AntrolBpjsUpdater`.
  - Setelah `PemeriksaanRalan::updateOrCreate(...)`, ambil `kd_pj` pasien dan panggil `AntrolBpjsUpdater::tandaiDokter($data['no_rawat'], $kdPj)`.

---

## 4. Hasil Verifikasi
- **Branch**: Berada di branch terpisah `feat/jam-periksa-antrol-bpjs` sehingga memudahkan perbandingan (diff) dengan branch `main`.
- **PHP Lint**: Seluruh file PHP baru dan termodifikasi lolos pengecekan sintaks tanpa error (`php -l`).
- **Unit Testing**:
  - `Tests\Unit\Support\AntrolBpjsUpdaterTest`: **PASS** (3 test, 5 assertion).
  - `Tests\Unit\Support\PenjaminResolverTest`: **PASS** (3 test, 17 assertion).

---

## 5. Panduan Cara Testing (Database Non-Produksi)

Telah dibuat 1 record pasien dummy (tanpa truncate tabel manapun):
- **No. Rawat**: `2026/09/04/999901`
- **Nama Pasien**: `IR ASKAN NOOR` (No. RM: `400370`)
- **Penjamin**: `BPJ` (BPJS)
- **Dokter**: `dr. Rizki Agmalia Sorayya` (Kode: `1992083020161101`)
- **Poli**: `APS` (APS dan Rujukan)
- **Tanggal**: Hari ini (`2026-09-04`)

### Skenario Test UI (Browser):
1. Buka menu rawat jalan: `http://192.168.10.3/RME-Nirwana/public/ralan?tanggal=2026-09-04`
2. Temukan baris pasien `IR ASKAN NOOR` (`2026/09/04/999901`).
3. **Test Perawat**: Klik nama pasien.
   - Cek database: `SELECT jam_periksa_perawat FROM antrol_bpjs WHERE no_rawat = '2026/09/04/999901';`
   - Jam harus terisi sesuai waktu klik.
   - Refresh / klik ulang: jam tidak boleh berubah (first-write-wins).
4. **Test Dokter**: Masuk tab SOAP, isi form, klik **Simpan SOAP**.
   - Cek database: `SELECT jam_periksa_perawat, jam_periksa_dokter FROM antrol_bpjs WHERE no_rawat = '2026/09/04/999901';`
   - `jam_periksa_dokter` terisi dengan selisih minimal 61 detik di atas `jam_periksa_perawat`.

### Skenario Reset Data Dummy (Jika Ingin Test Ulang):
```sql
UPDATE antrol_bpjs 
SET jam_periksa_perawat = NULL, jam_periksa_dokter = NULL 
WHERE no_rawat = '2026/09/04/999901';
```

