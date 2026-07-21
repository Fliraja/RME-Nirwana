# Catatan Perubahan: To Do #2 — Filter Otomatis Pemeriksaan Lab & Radiologi

**Branch:** `feat/todo-2-lab-radiologi-filter`  
**Tanggal:** 21 Juli 2026  

---

## 1. Ringkasan Perubahan

Menambahkan filter otomatis berdasarkan penjamin (`kd_pj`) pasien pada pencarian pemeriksaan Lab dan Radiologi untuk mencegah kesalahan pemulihan tarif (misal tarif Umum terpilih untuk pasien BPJS). Serta memperbaiki penentuan status layanan (ralan/ranap) secara dinamis.

---

## 2. Rincian File yang Diubah

### A. `app/Http/Controllers/LaboratoriumController.php`
- **`getPemeriksaan(Request $request)`**:
  - Menerima parameter `no_rawat` dari request Select2.
  - Membaca `kd_pj` dari tabel `reg_periksa` berdasarkan `no_rawat`.
  - Mengurutkan hasil pencarian mengutamakan tarif spesifik penjamin pasien (`FIELD(kd_pj, ?, '-')`) dengan fallback ke tarif generik (`kd_pj = '-'`).
- **`storePermintaanLab(Request $request)`**:
  - Mengubah `'status' => 'ralan'` hardcoded menjadi penentuan dinamis berdasarkan `reg_periksa.status_lanjut` (`ranap` / `ralan`).

### B. `app/Http/Controllers/RadiologiController.php`
- **`getPemeriksaanRadiologi(Request $request)`**:
  - Menerima parameter `no_rawat` dari request Select2.
  - Membaca `kd_pj` dari tabel `reg_periksa` berdasarkan `no_rawat`.
  - Mengurutkan hasil pencarian mengutamakan tarif spesifik penjamin pasien (`FIELD(kd_pj, ?, '-')`) dengan fallback ke tarif generik (`kd_pj = '-'`).
- **`storePermintaanRadiologi(Request $request)`**:
  - Mengubah `'status' => 'ralan'` hardcoded menjadi penentuan dinamis berdasarkan `reg_periksa.status_lanjut` (`ranap` / `ralan`).

### C. `resources/views/ralan/index.blade.php`
- Memperbarui fungsi `initSelect2Lab()` dan `initSelect2Radiologi()` untuk mengirimkan `no_rawat: currentNoRawat` ke payload AJAX Select2.

---

## 3. Hasil Pengujian / Status
- [x] Pencarian Lab & Radiologi memprioritaskan kode tarif penjamin pasien (`kd_pj`).
- [x] Fallback ke tarif generik (`kd_pj = '-'`) tetap aktif untuk item tanpa varian penjamin khusus.
- [x] Status pendaftaran order lab/radiologi fleksibel mendukung `ralan` maupun `ranap`.
