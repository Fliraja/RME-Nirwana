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
- **Perubahan:**
  1. Filter data berdasarkan dokter yang sedang login (`Auth::user()->decrypted_id`) untuk role non-admin (hanya tampilkan data pasien & jadwal dokter bersangkutan). Role admin tetap melihat data global.
  2. Mengganti metric stat card:
     - `kunjunganHariIni`: Total registrasi pasien dokter hari ini.
     - `sudahDiperiksa`: Jumlah pasien hari ini dengan `stts = 'Sudah'` / `'Bayar'` (menggantikan "Dokter Aktif").
     - `belumDiperiksa`: Jumlah pasien hari ini dengan `stts = 'Belum'` (menggantikan "Dalam Antrian").
  3. Filter daftar "Pasien Terbaru" dan "Jadwal Praktik" khusus untuk dokter yang sedang login.

### D. `resources/views/dashboard.blade.php`
- **Perubahan:**
  - Mengganti label card "Dokter Aktif" menjadi **"Sudah Diperiksa"** (`fa-user-check`).
  - Mengganti label card "Dalam Antrian" menjadi **"Belum Diperiksa"** (`fa-clock`) agar dokter tidak keliru berasumsi soal antrian farmasi/kasir.
  - Mengubah judul widget jadwal menjadi **"Jadwal Praktik Saya Hari Ini"** jika user adalah dokter.

### E. `.env`
- **Perubahan:** Mengubah `CACHE_STORE=database` menjadi `CACHE_STORE=file`.
- **Tujuan:** Menghindari error `Table 'db_test_rev.cache' doesn't exist` karena skema DB Khanza SIMRS tidak memiliki tabel `cache`.

---

## 3. Hasil Pengujian / Status
- [x] Data dashboard terisolasi per dokter (role non-admin hanya melihat data miliknya).
- [x] Judul & parameter stat card disesuaikan kebutuhan dokter (Sudah Diperiksa vs Belum Diperiksa).
- [x] Cache statistik aktif (file driver) terpisah per id dokter (`dashboard_stats_{kd_dokter}`).
