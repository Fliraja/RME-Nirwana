# Catatan Perubahan: To Do #1 — Dashboard Real Data

**Branch:** `feat/todo-1-dashboard`  
**Tanggal:** 21 Juli 2026  

---

## 1. Ringkasan Perubahan

Mengganti data hardcoded pada halaman Dashboard dengan data aktual dari database (`db_test_rev`) secara efisien menggunakan query Eloquent & caching 5 menit untuk statistik.

---

## 2. Rincian File yang Diubah

### A. `app/Models/Jadwal.php`
- **Perubahan:** Menambahkan relasi `dokter()` (`belongsTo(Dokter::class, 'kd_dokter', 'kd_dokter')`).
- **Tujuan:** Memungkinkan eager loading relasi dokter dari tabel `jadwal` ke tabel `dokter`.

### B. `routes/web.php`
- **Perubahan:** 
  - Mengimpor `App\Http\Controllers\DashboardController`.
  - Mengubah route `GET /dashboard` yang sebelumnya berupa Closure `view('dashboard')` menjadi `[DashboardController::class, 'index']`.
- **Tujuan:** Menghubungkan endpoint `/dashboard` ke controller agar logika pengambilan data dijalankan.

### C. `app/Http/Controllers/DashboardController.php`
- **Perubahan:** Mengimplementasikan method `index()` untuk mengambil data berikut:
  1. **Statistik (Cache 5 Menit):**
     - `totalPasien`: Jumlah total baris di tabel `pasien`.
     - `kunjunganHariIni`: Jumlah registrasi hari ini (`reg_periksa.tgl_registrasi` = hari ini) dengan status `stts != 'Batal'`.
     - `dokterAktif`: Jumlah dokter aktif (`dokter.status = '1'`).
     - `dalamAntrian`: Pasien terdaftar hari ini dengan `stts = 'Belum'`.
  2. **Pasien Terbaru:** 5 transaksi `reg_periksa` terbaru beserta data `pasien`.
  3. **Jadwal Dokter Hari Ini:** Data dari tabel `jadwal` berdasarkan hari kerja aktif (hari ini dalam Bahasa Indonesia, misal `SENIN`, `SELASA`), di-join dengan `poliklinik` dan `dokter`.
- **Tujuan:** Menyediakan data riil dan ter-cache untuk tampilan dashboard.

### D. `resources/views/dashboard.blade.php`
- **Perubahan:**
  - Mengganti angka hardcoded (1.234, 56, 12, 8) dengan variabel `$stats['...']`.
  - Mengganti dummy tabel "Pasien Terbaru" dengan loop `@forelse($pasienTerbaru as $item)` dan tombol link aksi ke modul Ralan.
  - Mengganti dummy list "Jadwal Dokter Hari Ini" dengan loop `@forelse($jadwalDokter as $jdwl)` yang menampilkan nama dokter, poliklinik, serta jam kerja.
- **Tujuan:** Menampilkan data nyata secara dinamis di antarmuka UI.

---

## 3. Hasil Pengujian / Status
- [x] Route `/dashboard` mengarah ke `DashboardController@index`.
- [x] Query data berjalan tanpa error.
- [x] Cache statistik aktif selama 5 menit untuk mengurangi beban tabel `pasien` (85k) dan `reg_periksa` (285k).
