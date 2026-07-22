# Rencana Implementasi & Spec — To-Do List RME-Nirwana

**Project:** RME-Nirwana (Rekam Medik Elektronik, RSU Nirwana)
**Stack:** Laravel 12, Blade + jQuery + Select2 + SweetAlert2 + DomPDF (tanpa Livewire)
**Database:** MySQL `db_test_rev` — skema existing SIMRS Khanza, dikonsumsi langsung (tidak ada migration lokal untuk tabel master)
**Scope saat ini:** hanya modul Rawat Jalan (semua route `/ralan/*`), belum ada `/ranap/*`
**Skala data (hasil cek langsung):** `reg_periksa` 285.138 baris, `pasien` 85.060, `resep_obat` 102.502, `diagnosa_pasien` 214.685, `databarang` 3.798, `penyakit` (ICD-10) 56.263, `icd9` 5.497
**Disusun:** hasil audit langsung ke source code + skema DB aktual (bukan asumsi/template generik)

---

## 0. Ringkasan Eksekutif

Temuan penting sebelum masuk ke rencana per item:

1. **Zero migration baru dibutuhkan.** Semua tabel yang diperlukan untuk 7 item to-do (termasuk item 4 & 5 yang kelihatannya "fitur baru") **sudah ada** di skema Khanza — `diagnosa_pasien`, `prosedur_pasien`, `penyakit` (ICD-10), `icd9`, `satu_sehat_condition`, `satu_sehat_procedure`, kolom `kd_pj` di `jns_perawatan_lab`/`jns_perawatan_radiologi`. Ini murni pekerjaan application-layer (Controller, Model, Blade, JS), bukan schema design dari nol.
2. **Todo #2 bukan cuma soal UX, tapi bug finansial nyata.** Sudah dibuktikan dengan data: pemeriksaan yang sama (misal "ALBUMIN") punya 2+ baris tarif berbeda tergantung `kd_pj`, dan search saat ini mengembalikan semuanya tanpa filter.
3. **Update 22 Juli 2026:** Q1, Q2, Q4, Q5 sudah dijawab Rafli (lihat bagian 1). Todo #3 dan #6 di-backlog atas permintaan Rafli. Todo #5 ternyata bukan cuma soal trigger teknis — ada keputusan arsitektur soal siapa yang boleh trigger submission ke SatuSehat (dokter vs RM), lihat analisis di bagian 6.
4. Belum ada test otomatis (`tests/Feature` cuma berisi `ExampleTest.php` bawaan Laravel) — untuk item finansial-sensitif (todo #2), sangat disarankan nulis Feature test sebelum deploy.

---

## 1. Keputusan (Update 22 Juli 2026 — jawaban Rafli, belum didiskusikan ke partner)

| # | Pertanyaan | Jawaban | Dampak ke rencana |
|---|---|---|---|
| Q1 | Scope Ranap ke depan? | **Ya**, RME-Nirwana rencananya bakal nangani Ranap juga | Desain fix Todo #2 (dynamic `status_lanjut`, bukan hardcode) sudah forward-compatible dari awal — gak perlu redesign |
| Q2 | Maksud "hide" racikan? | Konsep peracikan obat **belum matang** di sisi Rafli/RS | **Todo #3 di-backlog** — skip dulu sampai konsepnya jelas |
| Q3 | Mekanisme trigger SatuSehat legacy? | Pakai **webservice** (REST call langsung, bukan cron poller). Saat ini yang input ICD-9/10 adalah **RM**, bukan dokter | Lihat analisis arsitektur lengkap di bagian 6 (Todo #5) — ini yang paling substansial berubah |
| Q4 | Freetext Assesmen vs tab diagnosa baru? | **Ganti sepenuhnya** ke tab diagnosa terstruktur, meskipun beda tabel/kolom | Todo #4: field freetext di SOAP dihapus dari form input aktif |
| Q5 | List UI konkret? | Belum ada, ditunda | **Todo #6 di-backlog** — nunggu Rafli ajukan halaman spesifik |

**Status Todo #1: ✅ Selesai** (dikerjain Rafli langsung, di luar sesi ini).

**Todo aktif sekarang:** #5. **Backlog (di-park):** #3, #6. **Selesai:** #1, #2, #4.

---

## 2. Todo #1 — Dashboard Real Data

> ✅ **STATUS: SELESAI** — dikerjain Rafli, di luar sesi ini. Bagian di bawah disimpan sebagai referensi/dokumentasi.

### Temuan
`DashboardController::index()` cuma `return view('dashboard')`. Semua angka (1.234 pasien, 56 kunjungan, 12 dokter aktif, jadwal dokter) hardcoded di `resources/views/dashboard.blade.php`.

### Sumber data (sudah tersedia, tidak perlu tabel baru)

| Widget | Query dasar | Tabel |
|---|---|---|
| Total Pasien | `Pasien::count()` | `pasien` (85.060 baris — jangan `count()` tanpa cache, lumayan berat kalau dipanggil tiap request) |
| Kunjungan Hari Ini | `RegPeriksa::whereDate('tgl_registrasi', today())->where('stts','!=','Batal')->count()` | `reg_periksa`, sudah ada index di `tgl_registrasi` |
| Dokter Aktif | `Dokter::where('status','1')->count()` | `dokter` |
| Dalam Antrian | `RegPeriksa::whereDate('tgl_registrasi', today())->where('stts','Belum')->count()` (cek dulu value pasti kolom `stts` — kemungkinan `'Sudah'`/`'Belum'` berdasarkan pola di `RalanController`) | `reg_periksa` |
| Pasien Terbaru | `RegPeriksa::with(['pasien'])->orderByDesc('tgl_registrasi')->limit(5)->get()` | `reg_periksa` + `pasien` |
| Jadwal Dokter Hari Ini | `Jadwal::with(['poliklinik'])->where('hari_kerja', Carbon::now()->locale('id')->dayName)->get()` lalu join manual ke `Dokter` (relasi `dokter()` belum ada di model `Jadwal`, lihat catatan di bawah) | `jadwal` (kolom: `kd_dokter, hari_kerja, jam_mulai, jam_selesai, kd_poli, kuota`) |

### Yang perlu ditambahkan di kode
- Tambah `belongsTo(Dokter::class, 'kd_dokter', 'kd_dokter')` di `app/Models/Jadwal.php` — saat ini cuma ada relasi ke `Poliklinik`.
- Cek isi kolom `hari_kerja` di tabel `jadwal` (apakah `"Senin"`, `"SENIN"`, atau numerik) sebelum nulis query `where()`-nya — jangan asumsi.
- Pindahkan logic ke `DashboardController::index()`, jangan taruh query di Blade.

### Rekomendasi tambahan (nyambung ke todo #7)
Karena `pasien` (85k) dan `reg_periksa` (285k) itu tabel besar, cache angka-angka stat card (bukan data listing) pakai `Cache::remember('dashboard-stats', now()->addMinutes(5), fn() => ...)`. `.env` sudah punya `REDIS_HOST` tersedia tapi `CACHE_STORE=database` — kalau redis-server jalan di Laragon, pertimbangkan switch `CACHE_STORE=redis` biar cache gak nambah beban tabel `cache` di MySQL yang sama.

### Effort & risiko
Rendah. Semua data read-only, gak ada resiko ke data existing. ~0.5–1 hari kerja.

---

## 3. Todo #2 — Filter Otomatis Pemeriksaan Lab/Radiologi

> ✅ **STATUS: SELESAI** — dikerjakan & ditest di branch `feature/todo-2-filter-pemeriksaan`.

### Bukti bug (bukan asumsi — hasil query langsung ke DB)

`jns_perawatan_lab` dan `jns_perawatan_radiologi` punya kolom `kd_pj` (FK ke `penjab`) yang membedakan tarif per penjamin. Data aktif (`status='1'`):

- **Lab:** 94 baris generik (`kd_pj='-'`) + 72 khusus BPJS + 30 khusus Umum
- **Radiologi:** 211 generik + 103 BPJS + 206 Umum
- Kolom `kelas` **selalu `-`** pada baris aktif → rawat inap/kelas kamar **tidak** memengaruhi pemilihan kode lab/radiologi. Yang memengaruhi cuma `kd_pj`.

Contoh nyata duplikasi:
```
kd_jenis_prw=17U  | ALBUMIN | kd_pj=-   | total_byr=46000
kd_jenis_prw=B17B | ALBUMIN | kd_pj=BPJ | total_byr=30000
```
Search sekarang (`LaboratoriumController::getPemeriksaan`, `RadiologiController::getPemeriksaanRadiologi`) query `jns_perawatan_lab`/`jns_perawatan_radiologi` **tanpa filter `kd_pj` sama sekali**. Dokter bisa pilih kode tarif Umum untuk pasien BPJS (atau sebaliknya) → klaim BPJS berpotensi ditolak/salah tagih.

**Bug tambahan yang ketemu sekalian:** `LaboratoriumController::storePermintaanLab()` dan `RadiologiController::storePermintaanRadiologi()` hardcode `'status' => 'ralan'` di setiap insert ke `permintaan_lab`/`permintaan_radiologi`, padahal kolom itu memang dipakai untuk bedain ralan/ranap (`permintaan_lab.status` sudah punya 50 baris `'ralan'` dan 2 baris `'ranap'` dari data historis). Kalau nanti Ranap benar-benar dipakai di app ini, order-nya bakal salah tag secara diam-diam. Perbaiki sekalian di item ini karena satu file yang sama.

> ✅ **Update:** Rafli konfirmasi app ini emang direncanain nangani Ranap juga ke depan (jawaban Q1). Desain di bawah ini udah forward-compatible dari awal (pakai `status_lanjut` dinamis buat nentuin `'ralan'`/`'ranap'`, bukan hardcode) — gak ada perubahan desain, tinggal dieksekusi.

### Desain solusi

**Sumber kebenaran filter:** `reg_periksa.kd_pj` milik pasien yang sedang ditangani (bukan input manual dokter) — ambil dari `no_rawat` yang sudah ada di context form.

**Query filter (pengganti `getPemeriksaan`/`getPemeriksaanRadiologi`):**
```php
$regPeriksa = RegPeriksa::where('no_rawat', $request->no_rawat)->first();
$kdPj = $regPeriksa->kd_pj;

$pemeriksaan = DB::table('jns_perawatan_lab')
    ->where('status', '1')
    ->where(function ($q) use ($kdPj) {
        $q->where('kd_pj', $kdPj)->orWhere('kd_pj', '-');
    })
    ->where(function ($q) use ($search) {
        $q->where('kd_jenis_prw', 'like', "%$search%")
          ->orWhere('nm_perawatan', 'like', "%$search%");
    })
    ->orderByRaw("FIELD(kd_pj, ?, '-')", [$kdPj]) // baris spesifik payer muncul duluan
    ->limit(20)
    ->get();
```
Kenapa `orWhere('kd_pj', '-')` tetap dimasukkan: banyak `nm_perawatan` cuma punya varian generik (`kd_pj='-'`) tanpa varian BPJS/Umum spesifik — kalau filter ketat cuma `kd_pj = $kdPj`, item itu hilang total dari pencarian. Jadi logikanya: **utamakan baris spesifik payer pasien, fallback ke baris generik kalau gak ada.**

**Route & request perlu berubah:** endpoint `search-lab` dan `search-radiologi` saat ini cuma terima param `search`. Perlu tambah `no_rawat` (atau `kd_pj` langsung) supaya controller bisa filter. Di JS (`index.blade.php`, fungsi `initSelect2Lab`/`initSelect2Radiologi`), tambahkan `no_rawat: currentNoRawat` ke `data: function(params)` pada config Select2 ajax.

**Fix hardcoded status:**
```php
'status' => $regPeriksa->status_lanjut === 'Ranap' ? 'ranap' : 'ralan',
```
Ambil `$regPeriksa` dari `no_rawat` yang sudah divalidasi di awal method.

### File yang disentuh
- `app/Http/Controllers/LaboratoriumController.php` (`getPemeriksaan`, `storePermintaanLab`)
- `app/Http/Controllers/RadiologiController.php` (`getPemeriksaanRadiologi`, `storePermintaanRadiologi`)
- `routes/web.php` — tidak perlu route baru, cukup terima param tambahan
- `resources/views/ralan/index.blade.php` — bagian `initSelect2Lab()` dan `initSelect2Radiologi()`

### Testing plan
Tulis Feature test minimal: pasien dengan `kd_pj=BPJ` search "ALBUMIN" → harus dapat kode `B17B` (bukan `17U`) di posisi pertama. Pasien dengan `kd_pj` yang gak punya varian spesifik → tetap dapat hasil generik. Ini item finansial-sensitif, jangan skip test-nya.

### Effort & risiko
Menengah. Logic-nya sendiri simpel, tapi harus hati-hati sama kasus `kd_pj` pasien yang gak match manapun (mis. asuransi custom kayak `ADR`, `KPP`, dll yang mungkin gak punya baris spesifik di `jns_perawatan_lab`) — fallback ke generik menangani ini secara default.

---

## 4. Todo #3 — Penyesuaian Form Resep Racikan

> 🔸 **STATUS: BACKLOG** — konsep peracikan obat belum matang di sisi RS (jawaban Q2), ditunda sampai ada arahan lebih jelas dari Rafli. Bagian di bawah disimpan sebagai referensi kalau nanti dilanjutkan.

### Temuan
Form racikan (`resep.blade.php` + JS di `index.blade.php`) memang panjang: field `nama_racik`, `kd_racik` (metode racik), `jml_dr`, `aturan_racik` (+ opsi manual "lainnya"), dan tabel komposisi dinamis per baris obat (`p1`, `p2` — rasio dosis, `kandungan`, `jml` hasil hitung otomatis). **Dicek langsung ke skema `resep_dokter_racikan` dan `resep_dokter_racikan_detail` — tidak ada satupun kolom status verifikasi** (`stts_verif`, `tgl_verif`, approval flag, dll). Jadi "proses verifikasi form yang panjang dan lama" itu bukan mekanisme sistem yang bisa dibaca dari DB — kemungkinan besar itu keluhan proses kerja manusia (dokter/apoteker perlu waktu lama isi & cross-check form ini), bukan bug kode.

### Kenapa item ini belum bisa langsung di-spec detail
Kalimat todo-nya ("Hide jika memerlukan proses verifikasi form yang panjang dan lama") ambigu secara teknis — "hide" ini trigger-nya apa? Lihat **Q2** di bagian 1. Dua interpretasi paling masuk akal:

**Opsi A — Role/permission gate:** form racikan detail cuma muncul untuk role tertentu (misal apoteker/farmasi), dokter di RME cuma input racikan level dasar (nama racikan + instruksi umum), detail komposisi diisi belakangan oleh farmasi di sistem lain.

**Opsi B — Progressive disclosure:** form tetap satu, tapi default-nya collapsed/simple (cuma nama racik + jumlah + instruksi), tombol "Tambah Detail Komposisi" baru expand tabel `p1/p2/kandungan` kalau racikan butuh detail dosis presisi (racikan puyer anak sering butuh, racikan simpel kadang enggak).

### Rekomendasi
Jangan mulai coding item ini sebelum Q2 terjawab. Kalau harus tebak salah satu, Opsi B lebih aman secara data (gak mengubah siapa yang boleh input apa) dan lebih cepat diimplementasi (murni Alpine.js/jQuery show-hide, gak nyentuh Controller/backend sama sekali).

### Effort & risiko
Belum bisa diestimasi akurat sampai Q2 terjawab. Opsi B: rendah (~0.5 hari, pure frontend). Opsi A: menengah-tinggi (butuh definisi role baru + kemungkinan perubahan alur farmasi di luar scope RME-Nirwana).

---

## 5. Todo #4 — Tab Input Diagnosa & Prosedur

> ✅ **STATUS: SELESAI** — dikerjakan & ditest di branch `feature/todo-4-tab-diagnosa-prosedur`.

### Temuan (infrastruktur DB sudah siap 100%)

| Tabel | Kolom | Fungsi |
|---|---|---|
| `diagnosa_pasien` | `no_rawat, kd_penyakit, status, prioritas, status_penyakit` | Diagnosis per kunjungan. **Primary key komposit**: `(no_rawat, kd_penyakit, status)` |
| `penyakit` | `kd_penyakit, nm_penyakit, ...` | Master ICD-10 (56.263 baris) |
| `prosedur_pasien` | `no_rawat, kode, status, prioritas, jumlah` | Prosedur per kunjungan. **PK komposit**: `(no_rawat, kode, status)` |
| `icd9` | `kode, deskripsi_panjang, deskripsi_pendek, ...` | Master ICD-9-CM prosedur (5.497 baris) |
| `template_pemeriksaan_dokter_prosedur` | `no_template, kode, urut` | Bundling kode prosedur per template — pola identik dengan `TemplateLaboratorium` yang sudah dipakai di tab Lab, tinggal reuse UX-nya |
| `satu_sehat_condition` | `no_rawat, kd_penyakit, status, id_condition` | Tracking hasil push FHIR Condition (diisi proses lain, lihat todo #5) |
| `satu_sehat_procedure` | `no_rawat, kode, status, id_procedure` | Tracking hasil push FHIR Procedure |

Kolom `status` di `diagnosa_pasien`/`prosedur_pasien` menyimpan `'Ralan'`/`'Ranap'` (sudah ada data historis dengan value ini) — **harus diisi dari `reg_periksa.status_lanjut`, bukan hardcode**, sama seperti fix yang dilakukan di todo #2.

### Rencana implementasi

**Model baru:**
- `app/Models/DiagnosaPasien.php` — table `diagnosa_pasien`, composite key (Eloquent gak native support composite PK, pakai `$primaryKey` custom atau query manual via `DB::table` seperti pola yang sudah dipakai di `LaboratoriumController`)
- `app/Models/ProsedurPasien.php` — table `prosedur_pasien`, sama
- `app/Models/Penyakit.php` — table `penyakit`, PK `kd_penyakit`
- `app/Models/Icd9.php` — table `icd9`, PK `kode`

**Controller baru:** `app/Http/Controllers/DiagnosaProsedurController.php`
- `getDiagnosaProsedurPasien($no_rawat)` — tampilkan partial tab, pola sama persis kayak `getSoapPasien`/`getVitalPasien`
- `searchIcd10(Request $request)` — search ke `penyakit`, `limit(20)`, pola sama seperti `getObat`/`getPemeriksaan`
- `searchIcd9(Request $request)` — search ke `icd9`
- `storeDiagnosa(Request $request)` — insert ke `diagnosa_pasien` pakai `updateOrCreate` dengan composite key, `status` diambil dari `RegPeriksa::status_lanjut`
- `storeProsedur(Request $request)` — sama untuk `prosedur_pasien`
- `getTemplateProsedur($no_template)` — reuse pola `getTemplates()` dari `LaboratoriumController` untuk `template_pemeriksaan_dokter_prosedur`

**Routes (tambah di `routes/web.php`, grup `multi.auth` yang sama):**
```php
Route::get('/ralan/get-diagnosa-prosedur/{no_rawat}', [DiagnosaProsedurController::class, 'index'])->name('ralan.get-diagnosa-prosedur');
Route::get('/ralan/search-icd10', [DiagnosaProsedurController::class, 'searchIcd10'])->name('ralan.search-icd10');
Route::get('/ralan/search-icd9', [DiagnosaProsedurController::class, 'searchIcd9'])->name('ralan.search-icd9');
Route::post('/ralan/store-diagnosa', [DiagnosaProsedurController::class, 'storeDiagnosa'])->name('ralan.store-diagnosa');
Route::post('/ralan/store-prosedur', [DiagnosaProsedurController::class, 'storeProsedur'])->name('ralan.store-prosedur');
Route::delete('/ralan/delete-diagnosa/{no_rawat}/{kd_penyakit}', [DiagnosaProsedurController::class, 'destroyDiagnosa'])->name('ralan.delete-diagnosa');
Route::delete('/ralan/delete-prosedur/{no_rawat}/{kode}', [DiagnosaProsedurController::class, 'destroyProsedur'])->name('ralan.delete-prosedur');
```

**UI:** tambah tab baru "DIAGNOSA & PROSEDUR" di `resources/views/ralan/index.blade.php`, disisipkan di `<ul class="nav nav-tabs">` (rekomendasi posisi: setelah SOAP, sebelum VITAL-SIGN — karena diagnosis biasanya diisi bareng assessment). Ikuti pola lazy-load AJAX yang sama seperti tab lain (`$('a[href="#diagnosa-prosedur"]').on('shown.bs.tab', ...)`), bukan Livewire (project ini gak pakai Livewire sama sekali, konsisten aja).

Partial view baru: `resources/views/ralan/diagnosa-prosedur.blade.php` — dua search box (Select2 AJAX ke `search-icd10` dan `search-icd9`, minimum input length 2-3 kayak pola lab/obat), plus checkbox "Diagnosa Utama" (map ke kolom `prioritas`).

### Poin penting yang gampang kelewat
- **Composite primary key** — insert dobel ke `(no_rawat, kd_penyakit, status)` yang sama akan error duplicate key kalau gak pakai `updateOrCreate`/`upsert` dengan benar. Jangan pakai `->create()` polos.
- Field "Assesmen (Diagnosa)" di tab SOAP (freetext, kolom `pemeriksaan_ralan.penilaian`) itu **terpisah total** dari `diagnosa_pasien` (ICD-10 terstruktur) — dua-duanya independen di DB. **Keputusan (jawaban Q4): field freetext ini diganti sepenuhnya oleh tab diagnosa terstruktur.** Implikasi implementasi:
  - Hapus textarea "Assesmen (Diagnosa)" dari `resources/views/ralan/soap.blade.php`, sisain Subjek/Objek/Plan/Instruksi (relayout kolomnya, misal 3 kolom jadi lebih lebar).
  - **Jangan hapus data lama** di kolom `pemeriksaan_ralan.penilaian` — biarin dormant di DB, kalau ada data historis tetap bisa ditampilin read-only di tab Riwayat, cuma dihapus dari form input aktif.
  - Controller yang handle `ralan.soap.simpan` berhenti nerima/nulis field `penilaian` dari request baru (cek nama file controller-nya dulu — belum sempat diaudit langsung route ini).

### Effort & risiko
Menengah-tinggi. Bukan karena teknis susah (pola CRUD-nya konsisten dengan modul lab/radiologi yang sudah ada), tapi karena ini titik masuk data yang dikonsumsi todo #5 — kalau struktur data di sini salah, dampaknya nyambung ke pelaporan SATUSEHAT.

---

## 6. Todo #5 — Penyesuaian Service SatuSehat PHP

### Update konteks (jawaban Q3)
SatuSehat di app legacy Khanza terhubung lewat **webservice** (REST call ke FHIR API SatuSehat) — bukan cron/poller. Saat ini yang input ICD-9/10 adalah **RM (rekam medis)**, bukan dokter; RM secara efektif jadi trigger webservice itu lewat alur legacy mereka.

Ini geser sifat todo #5 dari "cari tahu mekanisme teknis" jadi **keputusan arsitektur**: kalau dokter sekarang yang input ICD-9/10 di RME-Nirwana (todo #4) dan itu langsung nyambung ke webservice yang sama, submission ke SatuSehat nasional bakal jalan **tanpa RM ikut mereview** sama sekali. Ini pertanyaan yang lebih penting daripada sekadar "gimana manggil webservice-nya" — jadi diuraikan trade-off-nya dulu di bawah.

### Analisis trade-off: dokter input langsung vs RM sebagai coder

**Alasan bagus buat dokter input langsung:**
- Diagnosis dikode oleh orang yang benar-benar mendiagnosis — lebih presisi klinis dibanding RM menerka dari catatan freetext dokter.
- Data nyampe ke SatuSehat lebih cepat (real-time), gak nunggu siklus coding RM yang mungkin baru jalan di akhir hari.
- Ngurangin satu langkah transkripsi manual (RM baca catatan dokter → translate ke kode) yang selama ini jadi sumber salah baca.

**Risiko yang jujur harus diakui:**
- `penyakit` (ICD-10) punya **56.263 kode** — ada nuansa laterality, initial vs subsequent encounter, kombinasi kode, tingkat spesifisitas. Ini keahlian RM yang certified buat coding, bukan keahlian klinis dokter. Dokter under time pressure di poli yang rame, milih dari autocomplete, realistis risiko pilih kode "yang mirip" tapi kurang presisi lebih tinggi dibanding RM yang emang dilatih buat ini.
- Kode ICD-10/ICD-9 yang kurang presisi bukan cuma masalah kepatuhan SatuSehat — **langsung mempengaruhi klaim BPJS** (grouper INA-CBG sensitif ke kombinasi kode buat nentuin tarif klaim). Salah kode = potensi klaim ditolak/kurang bayar, kerugian finansial nyata buat RS.
- Kalau webservice-nya nembak otomatis begitu data masuk, gak ada human checkpoint antara "dokter klik pilih kode" dan "data landing di sistem nasional Kemenkes". RM hari ini, seburuk apapun prosesnya, minimal jadi filter manusia sebelum submit keluar.

### Rekomendasi
**Jangan langsung full-swing dokter-input → auto-submit SatuSehat.** Pisahkan dua hal:

1. **"Dokter pilih diagnosis/prosedur"** (todo #4) → insert ke `diagnosa_pasien`/`prosedur_pasien` langsung, cepat, gak nunggu apa-apa. Tetap dapat semua manfaat kecepatan & akurasi klinis di atas.
2. **"Submit ke SatuSehat"** → **tetap lewat langkah RM**, tapi RM gak perlu coding dari nol lagi — tinggal **review & konfirmasi** kode yang udah dipilih dokter (jauh lebih cepat daripada nge-code dari freetext), baru RM yang trigger webservice-nya lewat alur mereka yang sudah ada.

Keuntungan desain ini:
- Gak perlu bongkar/reverse-engineer webservice trigger legacy — trigger-nya tetap sama persis kayak sekarang (RM yang jalanin), cuma sekarang kerjaan RM jadi *review* bukan *coding dari nol*. Kerjaan RM jadi lebih ringan & cepat per pasien, bukan hilang.
- Ada safety net manusia sebelum data keluar ke sistem pemerintah — kalau dokter salah pilih kode, ketahan di RM dulu, bukan langsung nyangkut di SatuSehat.
- Bisa diaudit incremental — kalau nanti terbukti dokter jarang salah pilih kode, baru realistis dipertimbangkan mempercepat/otomasi langkah RM.

**Yang perlu Rafli cek ke tim RM/partner:** apakah tool RM yang existing buat coding udah baca dari tabel `diagnosa_pasien`/`prosedur_pasien` langsung (kalau iya, begitu dokter input otomatis muncul di worklist RM, gak perlu kerjaan tambahan), atau RM punya alur/tabel input terpisah yang ujung-ujungnya juga nulis ke tabel yang sama — kalau yang kedua, perlu tau persis di titik mana webservice-nya di-trigger. Juga worth dicek: kolom `status_penyakit` di `diagnosa_pasien` (belum sempat dicek isinya di audit ini) — ada kemungkinan itu udah berfungsi sebagai flag semacam "Sementara"/"Ditegakkan" yang bisa dipakai sebagai penanda draft-vs-final secara natural, tanpa nambah kolom baru sama sekali.

### Observability (berlaku di semua kondisi)
Tetap tambahkan logging eksplisit tiap kali diagnosa/prosedur baru diinput dokter dari RME-Nirwana (`Log::info()` dengan `no_rawat` + kode) — supaya ada jejak audit dari sisi RME-Nirwana yang bisa dibandingkan sama tabel `satu_sehat_condition`/`satu_sehat_procedure` kapanpun ada laporan data gak nyambung.

### Effort & risiko
Menengah. Desain "RM review dulu" ini lebih ringan secara teknis dibanding coding ulang integrasi webservice dari nol (gak nyentuh webservice-nya sama sekali), tapi perlu dikonfirmasi ke tim RM soal alur kerja mereka sebelum dieksekusi — ini bukan keputusan teknis doang, ada dampak proses kerja manusia yang perlu dikomunikasikan duluan.

---

## 7. Todo #6 — Perbaikan UI

> 🔸 **STATUS: BACKLOG** — belum ada list konkret dari partner (jawaban Q5), ditunda sampai Rafli ajukan halaman spesifik yang perlu diperbaiki.

Item ini di source todo-nya paling generik ("Perbaikan UI yang diperlukan") — lihat **Q5**. Dari audit langsung ke kode, ini beberapa temuan konkret yang bisa jadi starting point kalau partner lo gak punya list spesifik:

- CSS Select2 di-override manual dengan `!important` bertumpuk langsung di `<style>` block dalam `index.blade.php` (bukan file CSS terpisah) — susah di-maintain, sebaiknya dipindah ke `resources/css` biar bisa di-compile Vite.
- Handling notifikasi sukses/error ada 3 lapis fallback (`Swal` → `swal` → native `alert()`) di hampir semua fungsi JS — kemungkinan sisa migrasi dari SweetAlert1 ke SweetAlert2 yang belum dibersihkan. Kalau SweetAlert2 sudah pasti terpasang, fallback ini bisa disederhanakan, ngurangin baris kode berulang di banyak fungsi.
- Loading state ("Memuat Form...") konsisten di semua tab AJAX — ini sudah bagus, gak perlu diubah.

### Effort & risiko
Tergantung isi jawaban Q5. Kalau cuma cleanup di atas: rendah, ~1 hari. Kalau ternyata partner punya list UI issue lain, perlu di-scope ulang.

---

## 8. Todo #7 — Optimizing Performance

### Temuan dari cek langsung index & skala data
Kabar baik: index dasar (PK dan FK utama: `no_rawat`, `kd_pj`, `tgl_registrasi`, `no_rkm_medis`, `status_lanjut`) **sudah ada** di tabel-tabel inti peninggalan skema Khanza. Jadi masalah performa di sini kemungkinan besar bukan soal index yang hilang, tapi pola query di level aplikasi.

### Rekomendasi konkret (bukan generic advice)

1. **Cache dashboard stats** (todo #1) — jangan hit `COUNT(*)` ke `pasien` (85k) dan `reg_periksa` (285k) tiap kali dashboard dibuka. `Cache::remember(..., 5 menit)`.
2. **Search ICD-10 (`penyakit`, 56.263 baris)** untuk todo #4 — pola `LIKE '%search%'` dengan leading wildcard gak bisa pakai index sama sekali (full scan tiap search). Tetap pertahankan `limit(20)` + `minimumInputLength: 2-3` di Select2 (pola ini sudah dipakai konsisten di lab/obat/radiologi, ikuti aja), tapi kalau masih terasa lambat di production, pertimbangkan FULLTEXT index di `penyakit.nm_penyakit`.
3. **Eager loading sudah cukup disiplin** — `RalanController::getRiwayatPasien` sudah pakai `with([...])` 7 relasi sekaligus, gak ada N+1 yang kelihatan jelas di controller existing. Yang perlu diawasi: controller **baru** (todo #4) harus ikut pola yang sama, jangan lupa `with()` pas nampilin riwayat diagnosa/prosedur gabungan.
4. **`Auth::user()->decrypted_id` dipanggil berkali-kali per request** di beberapa controller (tiap method manggil ulang, bukan di-cache per-request) — bukan bottleneck besar karena cuma baca session, tapi kalau mau rapi bisa di-resolve sekali di awal method.
5. **Redis nganggur** — `.env` sudah punya konfigurasi `REDIS_HOST`/`REDIS_CLIENT` tapi `CACHE_STORE` dan `SESSION_DRIVER` masih `database`/`file`. Kalau redis-server jalan di Laragon, pindah ke situ bakal lebih ringan buat cache dashboard + session, ketimbang nambah beban ke `db_test_rev` yang sama dipakai buat data operasional.
6. **Report PDF (`ReportController`)** — semua `pdfSoap`, `pdfLab`, `pdfRadiologi`, dll ambil data dengan `whereBetween` tanggal lalu `->get()` (load semua ke memory sekaligus). Untuk rentang tanggal panjang di tabel besar (`resep_obat` 102k+), pertimbangkan `chunk()` atau batasi rentang tanggal maksimal di validasi request, biar gak OOM pas admin generate laporan bulanan/tahunan.

### Effort & risiko
Rendah-menengah, bisa dikerjain incremental bareng item lain (item 2 & 4 tambah query baru — sekalian terapkan pola cache/index-aware dari awal, jangan nunggu jadi masalah baru dioptimasi belakangan).

---

## 9. Urutan Pengerjaan yang Disarankan (Update 22 Juli 2026)

```
✅ Todo #1 (dashboard real data)            — SELESAI

1. Todo #2 (filter payer lab/radiologi)     — bug finansial nyata, no dependency, kerjain duluan
2. Todo #4 (tab diagnosa & prosedur)        — bisa jalan paralel sama #2, gak saling blocking
   └─ sambil jalan, terapkan rekomendasi search dari Todo #7 poin 2
   └─ sekalian hapus field freetext "Assesmen (Diagnosa)" di tab SOAP (keputusan Q4)
3. Todo #5 (integrasi hasil todo #4 ke alur SatuSehat via RM) — mulai koordinasi ke tim RM/partner
   dari sekarang (poin "Yang perlu Rafli cek" di bagian 6), eksekusi penuh setelah Todo #4 ada
   bentuknya buat ditest end-to-end

🔸 Todo #3 (form racikan)  — BACKLOG, tunggu konsep matang (Q2)
🔸 Todo #6 (UI polish)     — BACKLOG, tunggu list konkret dari Rafli (Q5)
```

---

## 10. Risk Register

| Risiko | Kemungkinan | Dampak | Mitigasi |
|---|---|---|---|
| Dokter input ICD-9/10 langsung tanpa review RM → kode kurang presisi lolos ke SatuSehat & klaim BPJS | Menengah (kalau desain "RM review dulu" di bagian 6 gak diikuti) | Tinggi — kepatuhan pelaporan ke Kemenkes + potensi klaim BPJS ditolak/kurang bayar | Terapkan desain RM-review-dulu (bagian 6), jangan auto-submit langsung dari input dokter; konfirmasi ke tim RM soal alur existing sebelum eksekusi |
| Filter payer lab/radiologi salah pilih fallback, item hilang dari search buat payer tanpa varian spesifik | Menengah | Menengah — dokter komplain item gak ketemu | Fallback ke `kd_pj='-'` sudah didesain, tapi wajib ditest manual pakai beberapa `kd_pj` asuransi custom (ADR, KPP, dll) sebelum deploy |
| Composite primary key `diagnosa_pasien`/`prosedur_pasien` kena duplicate error kalau insert gak hati-hati | Menengah | Rendah — error kelihatan langsung, gak silent | Pakai `updateOrCreate`/`upsert`, ditest dengan insert dobel di kondisi sama |
| Scope todo #3 dan #6 melar karena ambiguitas deskripsi | Tinggi kalau Q2/Q5 gak dijawab dulu | Rendah-menengah — buang waktu development | Jangan mulai coding sebelum partner jawab |
| Report PDF timeout/OOM di rentang tanggal panjang pas data makin gede | Rendah sekarang, naik seiring waktu | Menengah | Terapkan rekomendasi todo #7 poin 6 |

---

## 11. Testing & Rollback

- **Testing:** `tests/Feature` cuma ada `ExampleTest.php` bawaan. Prioritas nulis test baru: filter payer (todo #2, paling kritis finansial), insert diagnosa/prosedur dengan composite key (todo #4).
- **Rollback:** karena **tidak ada migration baru** di seluruh rencana ini (semua tabel sudah ada), rollback cukup lewat `git revert` kode aplikasi — tidak ada resiko schema drift ke DB Khanza yang dipakai bareng sistem lain. Ini poin bagus buat dikomunikasikan ke partner: perubahan ini murni application-layer, resiko ke data existing rendah.

---

*Dokumen ini dibuat berdasarkan audit langsung ke source code (`app/`, `resources/views/`, `routes/`) dan skema database aktual (`db_test_rev`) per 21 Juli 2026. Bukan template generik — semua nomor, nama tabel, dan nama kolom di atas hasil query langsung.*
