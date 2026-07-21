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
3. **Todo #3 dan #5 punya ambiguitas/dependency yang harus diklarifikasi ke partner sebelum eksekusi** — sudah ditandai di bagian masing-masing, jangan langsung mulai coding sebelum ini dijawab.
4. Belum ada test otomatis (`tests/Feature` cuma berisi `ExampleTest.php` bawaan Laravel) — untuk item finansial-sensitif (todo #2), sangat disarankan nulis Feature test sebelum deploy.

---

## 1. Pertanyaan Terbuka (Jawab Dulu Sebelum Eksekusi Sebagian Item)

| # | Pertanyaan | Kenapa penting | Blocking untuk |
|---|---|---|---|
| Q1 | App ini rencananya bakal nangani Rawat Inap juga, atau selamanya cuma Ralan? | Nentuin apakah todo #2 perlu logic ralan/ranap penuh, atau cukup filter payer doang | Todo #2 |
| Q2 | "Hide" di todo #3 maksudnya hide untuk role tertentu, kondisi tertentu, atau bikin mode simple/advanced toggle? | Tanpa ini, effort estimation & desain form bisa salah arah total | Todo #3 |
| Q3 | Push data ke SATUSEHAT dari app legacy Khanza itu triggernya **inline call pas form lama disubmit**, atau **cron/scheduler yang poll tabel** `diagnosa_pasien`/`prosedur_pasien`? | Kalau inline-call dan RME-Nirwana insert langsung ke DB via Eloquent, push SATUSEHAT bisa **tidak pernah jalan** — silent fail ke Kemenkes | Todo #5 (blocking penuh) |
| Q4 | Field "Assesmen (Diagnosa)" freetext yang sudah ada di tab SOAP (`pemeriksaan_ralan.penilaian`) — tetap dipertahankan sebagai catatan naratif, atau digantikan sepenuhnya oleh tab diagnosa terstruktur baru? | Nentuin UX overlap antara 2 tab | Todo #4 |
| Q5 | "Perbaikan UI yang diperlukan" (todo #6) — ada daftar konkret dari partner, atau general polish sesuai temuan audit? | Tanpa spesifik, item ini gampang jadi scope creep tanpa akhir | Todo #6 |

Rekomendasi: kirim 5 pertanyaan ini ke partner coding lo dulu sebelum mulai todo #3 dan #5. Todo #1, #2, #4 (versi dasar), #7 bisa jalan duluan karena gak butuh jawaban itu.

---

## 2. Todo #1 — Dashboard Real Data

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
- Field "Assesmen (Diagnosa)" di tab SOAP (freetext, kolom `pemeriksaan_ralan.penilaian`) itu **terpisah total** dari `diagnosa_pasien` (ICD-10 terstruktur) — dua-duanya independen di DB. Lihat **Q4**: apa dua-duanya tetap coexist?

### Effort & risiko
Menengah-tinggi. Bukan karena teknis susah (pola CRUD-nya konsisten dengan modul lab/radiologi yang sudah ada), tapi karena ini titik masuk data yang dikonsumsi todo #5 — kalau struktur data di sini salah, dampaknya nyambung ke pelaporan SATUSEHAT.

---

## 6. Todo #5 — Penyesuaian Service SatuSehat PHP

### Temuan paling kritis dari seluruh audit ini
Project `RME-Nirwana` **tidak punya kode/kredensial SatuSehat sama sekali** — gak ada di `.env`, gak ada service/folder terkait. Semua tabel `satu_sehat_*` (condition, procedure, encounter, observation, dst) memang ada di DB `db_test_rev`, tapi proses yang **mengisi**-nya (push ke FHIR API Kemenkes) hidup di aplikasi legacy Khanza (CodeIgniter) yang **di luar folder project ini** — gak bisa gue baca/audit dari sini.

Ini bikin todo #5 **tidak bisa di-spec detail sampai Q3 terjawab**: apakah trigger push SatuSehat legacy itu inline-call pas form lama disubmit, atau cron/scheduler yang polling tabel `diagnosa_pasien`/`prosedur_pasien` cari baris yang belum punya pasangan di `satu_sehat_condition`/`satu_sehat_procedure`.

### Dua skenario & rencana masing-masing

**Skenario A — Legacy pakai cron/poller (baca tabel, bukan inline call dari form):**
Ini kasus paling ringan. Kalau begini, RME-Nirwana **tidak perlu ubah apa-apa di sisi SatuSehat** — cukup pastikan insert ke `diagnosa_pasien`/`prosedur_pasien` dari todo #4 formatnya identik dengan yang dihasilkan form legacy (kolom, format `status`, dll — sudah dicek di bagian 5, formatnya jelas). Cron legacy bakal otomatis nemu baris baru dan push-nya jalan seperti biasa.

**Skenario B — Legacy inline-call function pas form CI disubmit (kemungkinan besar berdasarkan pola aplikasi Khanza yang umum):**
Ini butuh kerja tambahan karena insert langsung dari Eloquent Laravel **skip proses itu total**. Tiga opsi:
1. **Panggil endpoint legacy via HTTP internal** dari Laravel setelah insert sukses (butuh legacy expose endpoint internal, atau bikin endpoint baru khusus untuk ini).
2. **Replikasi logic call SatuSehat langsung di Laravel** (butuh baca source code legacy dulu — access ke folder itu, di luar scope audit ini).
3. **Tabel antrian/flag** — insert row penanda di tabel terpisah (atau kolom flag) yang di-poll oleh cron baru/existing, paling loose-coupling tapi butuh koordinasi jadwal cron.

### Rekomendasi konkret
Sebelum nulis kode apapun untuk item ini: **minta partner coding lo cari & kirim source code trigger SatuSehat di app legacy** (biasanya nama file/fungsi ada kata `satusehat`, `satu_sehat`, `condition`, `pushCondition`, atau di dalam controller yang handle simpan diagnosa CI). Itu satu file yang menentukan apakah item ini 1 hari kerja atau 1 minggu kerja.

### Observability (berlaku di semua skenario)
Tambahkan logging eksplisit tiap kali diagnosa/prosedur baru diinput dari RME-Nirwana — minimal `Log::info()` dengan `no_rawat` dan kode yang diinput, supaya kalau ada laporan "data gak nyampe ke SatuSehat", ada jejak audit dari sisi RME-Nirwana untuk bandingin sama tabel `satu_sehat_condition`/`satu_sehat_procedure`.

### Effort & risiko
Tidak bisa diestimasi sampai Q3 terjawab. **Risiko tertinggi di seluruh to-do list** — kalau salah asumsi, kegagalannya silent (kelihatan berhasil di RME-Nirwana, tapi gak pernah lapor ke Kemenkes).

---

## 7. Todo #6 — Perbaikan UI

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

## 9. Urutan Pengerjaan yang Disarankan

```
1. Todo #2 (filter payer lab/radiologi)   — bug finansial nyata, no dependency, mulai duluan
2. Todo #1 (dashboard real data)           — independent, quick win
   └─ sambil jalan, terapkan rekomendasi cache dari Todo #7 poin 1
3. Todo #4 versi dasar (tab diagnosa/prosedur, tanpa nunggu Todo #5)
   └─ sambil jalan, terapkan rekomendasi search dari Todo #7 poin 2
4. [PARALEL] Investigasi Q3 (mekanisme trigger SatuSehat legacy) — mulai dari awal,
   jangan nunggu Todo #4 selesai, karena ini yang paling makan waktu tunggu jawaban
5. Todo #5 (penyesuaian SatuSehat)         — baru bisa di-eksekusi penuh setelah Q3 & Todo #4 selesai
6. Todo #3 (form racikan)                  — tunggu jawaban Q2, bisa dikerjain kapan aja setelah itu
7. Todo #6 (UI polish)                     — opportunistic, sambil ngerjain item lain yang nyentuh file sama
```

---

## 10. Risk Register

| Risiko | Kemungkinan | Dampak | Mitigasi |
|---|---|---|---|
| Push SatuSehat silent-fail karena insert langsung dari Laravel skip trigger legacy | Tinggi (sampai Q3 dijawab) | Tinggi — kepatuhan pelaporan ke Kemenkes | Investigasi Q3 sebelum todo #5 dieksekusi, tambah logging observability |
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
