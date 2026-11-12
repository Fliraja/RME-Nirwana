# design.md: RME RSU Nirwana

Sumber: gaya yang sudah berjalan di aplikasi (`public/css/admin.css`, `resources/views/layouts/*`, tab ralan/ranap). Dokumen ini mencatat gaya itu dan aturan pakainya, tidak membuat arah baru. Perubahan desain wajib memperbarui file ini.

## 1. Identitas

- **Produk:** rekam medis elektronik RSU Nirwana Banjarbaru, dipakai dokter di poli (ralan) dan bangsal (ranap). Mungkin perawat nanti.
- **Watak:** alat kerja klinis. Tenang, padat, mudah dipindai saat antrean pasien panjang. Data pasien adalah fokus; dekorasi tidak punya tempat.
- **Dial:** ENERGY 1 / RHYTHM 1 / MOTION 1. Alasan: dokter mengerjakan puluhan pasien per sesi, jadi tata letak yang sama di setiap tab membuat tangan hafal posisi tombol. Keseragaman ini disengaja.
- **Motif identitas:** kartu datar `ralan-card` (border tipis, tanpa bayangan) dengan kepala kartu berisi judul di kiri dan aksi di kanan; hijau RS hanya untuk aksi utama dan status "selesai".
- **Form panjang (resume ranap):** dial tetap 1/1/1. Agar tidak terasa polos, form panjang memakai ikon isian berlatar hijau muda dan penanda progres. Alasan: dokter perlu tahu "sudah sampai mana" di form 10 isian, dan user meminta tampilan "tidak polos, tapi tidak bikin sakit mata".

## 2. Warna

Token di `:root` `admin.css`. Palet inti: hijau RS + slate. Kuning/merah/biru hanya untuk status.

| Token | Hex | Pakai untuk |
|---|---|---|
| `--primary` | `#15a362` | ikon, garis tab aktif, border pilihan aktif, latar lembut (`--primary-soft`). **Bukan latar teks putih.** |
| `--primary-dark` | `#0d7a4a` | latar tombol aksi utama (teks putih 5,38:1), teks/link hijau di atas putih (5,38:1) |
| `--secondary` / `--gray-700` | `#334155` | teks utama (9,45:1 di `#f1f5f9`) |
| `--gray-500` | `#64748b` | teks sekunder, label kolom (4,76:1 di putih) |
| `--gray-400` | `#94a3b8` | **hanya** garis, ikon nonaktif, placeholder dekoratif. Gagal kontras untuk teks (2,56:1). |
| `--gray-100` | `#f1f5f9` | latar halaman, latar ringkasan |
| status kuning | teks `#92400e` di `#fef3c7` (6,37:1) | "belum diverifikasi", perlu perhatian |
| status hijau | teks `#166534` di `#dcfce7` (6,49:1) | "diverifikasi", selesai |
| status merah | teks `#be123c` di putih (6,29:1) | error, alergi, aksi hapus |
| status biru | `--info` `#0ea5e9` latar lembut | info netral (banner "ada data baru") |
| hijau muda ikon | teks `#0d7a4a` di `#dcfce7` (4,90:1) | kotak ikon isian resume ranap |
| hijau sangat muda | `#f0fdf4`; teks `#334155` (9,89:1), `#166534` (6,81:1), `#64748b` (4,55:1) | latar kotak referensi |
| isi bar progres | `#0d7a4a` di jalur `#e2e8f0` (4,37:1, batas komponen UI 3:1) | bar progres. `#15a362` di jalur ini hanya 2,64:1, jangan dipakai. |

**Temuan yang harus diperbaiki saat menyentuh bagian terkait:**
- Teks putih di atas `#15a362` hanya 3,26:1 (gagal AA untuk teks normal). Tombol aksi utama memakai `--primary-dark`.
- Hover tab memakai `#0d6efd` (biru Bootstrap) dan beberapa view memakai `text-primary`/`bg-primary` bawaan Bootstrap (biru). Di bagian baru dipakai token hijau; bagian lama dirapikan bila tersentuh.

## 3. Tipografi

- **Inter** (self-hosted `vendor/inter-4`), fallback system sans. Alasan: sudah dipakai di seluruh aplikasi; angka dan huruf kecil jelas untuk nilai lab, tensi, dosis.
- Ukuran dasar 14px, line-height 1.6. Label kolom 11–12px tebal `--gray-500`. Judul kartu 0.9rem/600. Tab 0.8rem/500.
- Tidak ada huruf kapital berjarak lebar sebagai hiasan; huruf kapital hanya untuk label pendek bila memang dibaca sebagai label.

## 4. Jarak, radius, elevasi

- Spasi kelipatan 4px; badan kartu 1rem, kepala kartu 0.7rem 1rem, jarak antar kartu 0.75–1rem.
- Radius: kartu `--radius` 0.5rem, input/tombol `--radius-sm` 0.375rem, chip usulan 999px (satu-satunya bentuk kapsul, membedakan "usulan" dari isian).
- Elevasi: kartu kerja tanpa bayangan (border saja). Bayangan hanya untuk lapisan yang menumpuk (dropdown Tom Select, modal, toast).

## 5. Komponen

- **Kartu kerja (`ralan-card`):** kepala = judul + badge status di kiri, aksi di kanan; badan = isi.
- **Tombol:** satu aksi utama per kartu (latar `--primary-dark`, teks putih). Aksi lain outline slate. Hapus = outline merah. Label kata kerja spesifik ("Simpan & Verifikasi", "Cetak PDF", "Perbarui"), tanpa panah hiasan.
- **Badge status:** hanya untuk status nyata (otomatis/diverifikasi, sumber data). Bentuk kotak radius kecil, tanpa titik/glow.
- **Badge sumber:** abu-abu lembut (`#f1f5f9`, teks `#475569`), menyebut asal data ("dari SOAP · A", "surat kontrol BPJS").
- **Chip usulan:** border putus-putus hijau, teks `--primary-dark`, diawali "+". Klik = menambah ke isian.
- **Baris isian berurutan (diagnosis/prosedur):** label posisi di kiri (Utama hijau tebal, Sekunder abu), input, tombol ↑ ↓ ✕ berlabel aksesibel.
- **Pilihan tunggal (tindak lanjut):** kotak radio berbingkai; aktif = border hijau + latar `--primary-soft`. Terkunci = hanya pilihan aktif yang bisa dipilih, sisanya redup + teks sumber di bawahnya.
- **Banner info:** latar biru lembut, satu kalimat + satu tombol aksi.
- **Ringkasan salinan:** baris label (kiri, 150px) + isi 1–2 baris + tautan "ubah" yang membuka textarea di tempat.
- **Kartu isian (`.resume-isian`, resume ranap):** satu kartu per isian, border tipis tanpa bayangan. Kepala: nomor + ikon Font Awesome yang relevan dalam kotak `.resume-isian-ikon` (28px, radius 7px, latar `#dcfce7`, ikon `#0d7a4a`), judul 600, lalu tanda di kanan `.resume-isian-tanda`: `.terisi` (ikon centang + "terisi", `#166534`) atau `.kosong` ("belum diisi", `#92400e`). Tanda selalu berisi teks, tidak hanya warna. Badan: isian, lalu kotak referensi.
- **Kotak referensi (`.resume-ref`):** latar `#f0fdf4`, radius `--radius-sm`, tanpa border. Tiap item (`.resume-ref-item`) diawali badge sumber `.resume-ref-sumber` (latar putih, border `#d1fae5`, teks `#166534`, contoh "Asesmen IGD · keluhan", "Visite dr. A · 15/10 · O"). Catatan bukan dokter memakai `.resume-ref-sumber.non-dokter` (latar `#f1f5f9`, teks `#475569`, berisi "Bidan" atau "Perawat") dan selalu di bawah catatan dokter. Aksi: tombol kecil outline "Pakai", chip usulan untuk diagnosis/prosedur, "Pakai semua" untuk daftar obat.
- **Bar progres (`.resume-progres`):** tinggi 6px, jalur `#e2e8f0`, isi `#0d7a4a`, didampingi teks "x dari 10 isian terisi" (`--gray-500`). Teks wajib ada; bar hanya penguat.
- **Bilah simpan menempel (`.resume-bilah-simpan`):** `position: sticky; bottom: 0`, latar putih, border atas, berisi waktu simpan terakhir di kiri dan aksi di kanan (satu aksi utama, satu outline). Untuk form panjang agar tombol simpan selalu terjangkau.
- **Notifikasi:** toast yang sudah ada (`tampilkanSukses`/`tampilkanError`) dan SweetAlert untuk konfirmasi hapus.
- **Daftar resep ralan/ranap:** semua resep kunjungan, terbaru di atas (resep terbuka bisa dibuat kemarin dan ditambah hari ini). Tiap resep diawali baris `.resep-kepala` (`#475569`): "Resep no · dd/mm HH.MM · dokter" + status teks `.resume-status` dengan warna kartu resep IGD: "menunggu apotek" `.resep-status-tunggu` (`#475569`/`#f1f5f9`), "diterima apotek" `.resep-status-proses` (`#075985`/`#e0f2fe`), "diserahkan" `.resume-status-ok`. Tombol hapus hanya selama menunggu apotek. Kosong: "Belum ada resep untuk kunjungan ini."
- **Status permintaan lab/radiologi ralan dan ranap:** "Diproses" dan hilangnya tombol hapus mengikuti aturan server (`tgl_sampel` terisi atau hasil sudah ditagih), bukan status bayar. Kolom Status tabel ranap memakai teks kartu IGD: "menunggu sampel" `.resep-status-tunggu`, "sampel diambil" `.resep-status-proses`, "hasil keluar" `.resume-status-ok`.
- **Kotak kunci rawat inap di tab ralan (`.kunci-ranap`):** sama dengan `.igd-terkunci` (garis putus-putus `#cbd5e1`, latar `#f8fafc`, teks `#475569`, ikon gembok). Bila pasien sudah ranap, form permintaan di tab Lab, Radiologi, dan Resep diganti kotak "Pasien sudah rawat inap sejak … Permintaan baru lewat modul Ranap. Daftar di bawah tetap bisa dibaca."; tombol hapus di tabelnya tidak dirender. Server juga menolak (409).
- **Tab Riwayat (IGD, ralan, ranap):** satu partial `riwayat/kunjungan.blade.php`, data dari `App\Services\Kunjungan\RiwayatKunjungan`. Satu kartu per kunjungan (`.riwayat-kartu`, border `#e5e7eb`, tanpa bayangan), terbaru di atas, 5 per halaman. Kepala: tanggal 600 `#334155`, chip jenis abu (`#475569` di `#f1f5f9`): nama poli / "IGD" / "Rawat inap · n hari (dd/mm s.d. dd/mm)" atau "Rawat inap · sejak dd/mm"; "kunjungan ini" hijau `.resume-status-ok` pada kunjungan yang sedang dibuka; "pulang: Membaik" abu; meta abu `#64748b` (dokter atau "masuk lewat IGD · DPJP …", no. rawat). Badan dua kolom (1,2 : 1), satu kolom di bawah 768px. Kiri isi klinis per jenis: poli = S/O/A/P; IGD = keluhan utama, pemeriksaan (TTV ringkas + ket. fisik), diagnosis, tatalaksana dari `penilaian_medis_igd` ("Asesmen IGD belum ditulis." bila kosong); ranap = diagnosis masuk → akhir (`kamar_inap`), ringkasan asesmen IGD, CPPT dokter terakhir (A dan P) + `<details>` "Lihat n catatan CPPT dokter"; catatan perawat/bidan tidak diringkas. Kanan: lab "n pemeriksaan" + badge kuning "x di luar rujukan" dan barisnya, "Lihat semua hasil" per tanggal/paket; radiologi = nama pemeriksaan + teks hasil (lebih dari 220 karakter dipotong + "Baca hasil lengkap"); obat dari apotek dijumlah per barang, BHP disaring dari nama, 5 pertama lalu "Lihat n obat lainnya". Label isian 0,75rem/600 `#64748b`, isi 0,85rem `#334155`. Tautan `<summary>` hijau `#0d7a4a` 600, fokus outline 2px, 44px di layar sentuh. Kosong: "Belum ada kunjungan untuk pasien ini."; per bagian "Tidak ada hasil lab." dan sejenisnya. Halaman bernomor hijau `#0d7a4a` (bukan biru Bootstrap). Tombol outline "Cetak 5 riwayat terakhir"; PDF memakai data yang sama (kepala kunjungan hijau `#0d7a4a` di `#eef5f1`).
- **Hapus SOAP/TTV ranap pada baris gabungan:** satu baris `pemeriksaan_ranap` bisa berisi SOAP dan TTV (umum dari Khanza desktop) dan tampil di kedua tab. Hapus di satu tab hanya mengosongkan bagiannya; baris dibuang bila bagian lain (TTV, alergi, atau SBAR untuk tab SOAP; SOAP untuk tab TTV) kosong. Toast menyebutnya: "TTV & SBAR dihapus. Catatan SOAP pada jam yang sama tetap disimpan." / "Catatan SOAP dihapus. TTV pada jam yang sama tetap disimpan." Nilai kosong satu sumber di `App\Services\Ranap\BarisPemeriksaanRanap`.
- **Batas panjang SOAP/TTV (ralan dan ranap):** angka dari kolom Khanza, satu sumber `App\Support\BatasIsianPemeriksaan` (SOAP ranap 400, Plan 500; SOAP ralan 2000; TTV 3 sampai 10; alergi ranap 400). Textarea SOAP membawa `data-batas`; penghitung `.batas-isian` muncul mulai 75% batas, kanan bawah, "305 dari 400 karakter" abu `#64748b` (4,76:1). Lewat batas: teks merah tebal `#be123c` di kiri "Lebih 17 karakter dari batas 400. Ringkas dulu sebelum menyimpan.", kolom `is-invalid` + `aria-invalid`, simpan ditahan dan fokus pindah ke kolom itu. Tidak memakai `maxlength` di textarea supaya teks tempelan tidak terpotong diam-diam. Input TTV memakai `maxlength`. Galat 422 server tampil di bawah kolomnya ("Subjek (Keluhan) paling banyak 400 karakter, sekarang 417.") dan hilang saat kolom diketik ulang; toast "SOAP belum disimpan. Perbaiki isian yang ditandai merah."

## 6. State

Setiap bagian yang memuat data punya:
- **Memuat:** spinner + teks "Memuat …" di dalam area kartu (pola yang sudah ada di tab ralan/ranap).
- **Kosong:** kalimat yang menyebut langkah berikutnya ("Isi Penilaian (A) di SOAP untuk menyusun resume.") + tautan ke tempatnya.
- **Error:** kalimat penyebab + tombol "Coba lagi". Error validasi tampil di dekat kolomnya, isian tidak hilang.
- **Referensi resume ranap:** memuat = spinner kecil di tiap kotak referensi; kosong = "Belum ada catatan untuk bagian ini."; gagal = "Referensi gagal dimuat." + "Coba lagi". Form tetap bisa diisi selama referensi memuat atau gagal.

## 7. Aksesibilitas

- Semua kontrol bisa dicapai dengan Tab sesuai urutan visual, diaktifkan dengan Enter/Space; modal tertutup dengan Escape.
- Fokus terlihat: outline 2px `--primary-dark` dengan offset 2px. `outline: none` tanpa pengganti dilarang (perbaiki `.search-input:focus` bila tersentuh).
- Tombol ikon (↑ ↓ ✕) wajib `aria-label`. Status tidak hanya dibedakan warna: badge selalu berisi teks.

## 8. Responsif

- Breakpoint Bootstrap 5 (576/768/992/1200). Target utama layar desktop poli; tetap rapi di tablet dan ponsel.
- Tab ralan sudah bergulir horizontal di layar sempit; isi tab tidak boleh membuat halaman bergulir horizontal.
- Di bawah 768px: kartu bertumpuk satu kolom, label ringkasan pindah ke atas isinya, tombol kepala kartu turun ke baris sendiri.
- Target sentuh minimal 44px untuk tombol dan pilihan radio.
- Resume ranap: tablet tegak (768px) adalah acuan utama. Kartu isian satu kolom di semua lebar; di bawah 576px badge sumber referensi pindah ke baris sendiri di atas teksnya, dan bilah simpan menumpuk tombol selebar penuh.

## 9. Gerak

Hanya transisi hover/fokus singkat (yang sudah ada, `--transition`). Tidak ada animasi masuk.

## 10. Teks

- Bahasa Indonesia, kalimat pendek, istilah klinis yang dipakai dokter (SOAP, TTV, DPJP).
- Tanpa tanda pisah panjang (em dash). Pakai koma, titik, titik dua, atau tanda kurung.
- Tidak ada klaim tanpa dasar (misalnya tanda tangan elektronik sebelum ada TTE).

## 11. Tema

Hanya terang, sama dengan aplikasi sekarang. Mode gelap belum diminta; bila nanti diminta, kedua mode wajib diuji penuh.

## 12. Modul IGD

Mockup acuan: susunan B (tab Permintaan | Asesmen | Riwayat; label tab "Order" diganti "Permintaan" atas permintaan user), disetujui user 2026-11-10 dengan catatan "jangan sepolos itu". Dial tetap 1/1/1; kehidupan datang dari ikon isian hijau muda, status berupa teks, dan kepala pasien yang padat informasi, bukan dari dekorasi.

- **Daftar pasien IGD:** kartu `ralan-card` berisi filter (cari nama/no. RM/no. rawat, tombol cepat "Hari ini" | "Kemarin", rentang tanggal, tombol "Tampilkan" `#0d7a4a`) dan tabel. Rentang bawaan sebelum 09.00 mencakup kemarin dan hari ini (dokter jaga malam melengkapi asesmen sampai sekitar 08.00). Tombol cepat berupa dua tautan berdempet (border `#cbd5e1`, teks `#334155` 10,35:1, hover hijau); yang cocok dengan rentang aktif bertanda `aria-current` dengan teks tebal `#166534` di `#f0fdf4` (6,81:1), border `#15a362`. Pencarian ikut terbawa saat pindah rentang. Tinggi 36px, 44px di layar sentuh. Status per baris berupa badge teks: "Rawat inap" (abu `#475569` di `#f1f5f9`), "Asesmen terisi" (status hijau), "Asesmen belum" (status kuning). Nama pasien adalah tautan hijau `#0d7a4a`.
- **Kepala pasien (`.igd-kepala`):** nama 1.05rem/600 + "RM · no. rawat" abu; chip identitas abu (`#475569` di `#f1f5f9`); chip alergi merah `#be123c` di `#fff1f2` (5,72:1) dengan ikon segitiga, hanya bila alergi benar-benar ada. Chip abu `#475569` di `#f1f5f9` 6,92:1. Di kanan: "Di IGD sejak HH.MM" dan lama di IGD dalam angka besar (1.15rem/600), atau "Rawat inap sejak dd/mm HH.MM" + "di IGD x j y m" setelah pasien naik bangsal. Bila status Ranap sudah diisi tetapi kamar belum tercatat, cukup "Rawat inap, kamar belum tercatat" tanpa lama di IGD (jam keluar IGD belum diketahui, jam daftar tidak dipakai sebagai gantinya). Di bawah 576px blok waktu pindah ke kiri di bawah identitas.
- **Tab IGD (`.igd-tabs`):** ikon Font Awesome per tab; aktif = teks `#0d7a4a` tebal dengan garis bawah `#15a362` 2px; hover hijau. Tidak memakai biru Bootstrap. Tanda di label tab berupa teks kecil ("belum" kuning untuk asesmen yang belum ditulis). Di bawah 576px ikon tab disembunyikan dan jarak dirapatkan (di bawah 360px huruf 0,75rem) supaya ketiga tab terlihat utuh tanpa digeser sampai lebar 320px; label dan tanda tetap tampil.
- **Isi tab dimuat saat pertama dibuka:** memuat = spinner kecil + "Memuat riwayat…"; gagal = kalimat merah + tombol "Coba lagi"; tautan halaman di dalam tab dimuat ulang di tempat.
- **Kerangka halaman:** `admin.css` memberi `.main-content { min-width: 0 }` untuk semua modul (dulu hanya IGD), agar tabel lebar bergulir di dalam kartunya, bukan menggeser seluruh halaman.
- **Stok di pilihan obat/BHP (ralan, ranap, IGD):** tiap opsi Tom Select menampilkan badge di kanan nama. Hijau "stok 350 amp" (`#166534` di `#dcfce7`, 6,49:1) bila stok di atas `stokminimal`; kuning "sisa 44 btl, hampir habis" (`#92400e` di `#fef3c7`, 6,37:1) bila 0 < stok ≤ `stokminimal`; abu "habis" (`#475569` di `#f1f5f9`, 6,92:1; `#64748b` gagal 4,34:1) bila stok 0 atau tidak ada di gudang APT. Opsi habis tidak bisa dipilih (`aria-disabled`), nama abu `#64748b` di putih (4,76:1), dan selalu di urutan terakhir. Badge selalu berisi ikon + teks.
- **Tab Permintaan (kartu lab/radiologi/resep):** kartu `ralan-card` dengan kepala: ikon dalam kotak hijau muda 28px (`#0d7a4a` di `#dcfce7`), judul + "n permintaan" abu, tombol tambah di kanan (lab: aksi utama hijau; radiologi/resep: outline). Form permintaan terbuka di dalam kartu (collapse, latar `#f8fafc`, label `#64748b` 4,55:1), bukan modal, supaya tetap terbaca bersama daftar. Tiap permintaan: baris meta abu (tanggal-jam · dokter · Dx klinis), badge status teks: "menunggu sampel" abu (`#475569`/`#f1f5f9`), "sampel diambil" biru (`#075985`/`#e0f2fe`, 6,59:1), "hasil keluar" hijau, "hasil keluar, n di luar rujukan" kuning; tombol "Batalkan" merah teks hanya bila sampel belum diambil, dengan konfirmasi SweetAlert. Label tab menampilkan jumlah permintaan (abu; hijau saat tab aktif), disembunyikan bila 0.
- **Terkunci rawat inap:** kotak bergaris putus-putus `#cbd5e1` berikon gembok di atas kartu: "Pasien sudah rawat inap sejak HH.MM. Permintaan baru lewat modul Ranap." Waktunya mengikuti jejak asesmen: jam saja bila hari ini, "dd/mm HH.MM" bila lain hari; tanpa kamar tercatat menjadi "Pasien sudah rawat inap (kamar belum tercatat)." Tombol tambah dan Batalkan tidak dirender; server juga menolak (409).
- **Sub-item lab di form permintaan:** tiap paket dalam kotak bergaris tipis: nama paket hijau + "x dari y dipilih" (merah tebal bila 0), tombol "Pilih semua" dan "Kosongkan". Sub-item tercentang semua secara bawaan, paling banyak 8 baris ke bawah lalu berlanjut ke kolom di sampingnya; kolom yang tidak muat bergulir di dalam kotak paket, bukan menggeser halaman. Kirim ditolak dengan pesan bila ada paket yang semua sub-itemnya kosong. Alasan: paket besar seperti kimia darah (37 item) sering hanya diambil beberapa.
- **Kartu resep IGD:** satu resep untuk obat/cairan yang dipakai di IGD, BHP, dan obat pulang. Form "Tambah item" berupa daftar tunggu: pilih obat (badge stok), jumlah, aturan pakai (daftar dari master + chip putus-putus "+ di IGD", "+ BHP"), Enter atau "Tambah ke daftar"; fokus kembali ke kotak cari; tombol utama "Kirim resep (n item)" mengirim semuanya sekali. Jumlah di atas stok memberi peringatan kuning, tidak memblokir. "Racikan" punya form sendiri: nama, bentuk (metode racik), jumlah, aturan, bahan dengan dosis per bungkus ("1/3", "0,5") dan jumlah bahan terhitung otomatis. Bahan yang sudah ada di baris lain tidak bisa dipilih lagi: pilihan di baris baru dikosongkan, fokus pindah ke dosis baris lama, toast kuning "X sudah ada di daftar bahan. Ubah dosisnya di baris itu." (server juga menolak 409 dengan pesan serupa). Daftar resep: status "menunggu apotek" (abu) / "diterima apotek" (biru) / "diserahkan" (hijau); item bisa dihapus selama belum divalidasi; setelah divalidasi tampil "diberikan apotek" berisi barang, jumlah, dan aturan dari apotek.
- **Tab Asesmen (`penilaian_medis_igd`):** enam kartu isian bernomor I s.d. VI (pola `.resume-isian`: ikon hijau muda 28px, judul 600). Di atas kartu satu baris jejak abu `#475569`: "Ditulis dr. A 08.20 · diubah dr. B 09.05" (tanggal ikut bila lain hari; "(Khanza desktop)" bila asesmen dibuat di Khanza dan belum pernah diubah lewat aplikasi). Label isian wajib diberi kata "wajib" abu, bukan tanda bintang.
- **Tombol pilihan berjajar (`.igd-pilih`):** untuk kolom enum (anamnesis, keadaan umum, kesadaran, 8 organ). Radio asli yang disembunyikan secara visual, jadi Tab masuk grup, panah memilih, spasi memilih yang difokus; fokus = outline 2px `#0d7a4a` pada kotak pilihan. Belum ada yang terpilih saat asesmen baru. Kotak 40px (44px di layar sentuh), border `#cbd5e1`, radius kecil. Terpilih = ikon centang + teks tebal: hijau (`#166534` di `#f0fdf4`, border `#15a362`), untuk organ "Abnormal" kuning (`#92400e` di `#fef3c7`, border `#d97706` 3,19:1) dan "Tidak diperiksa" abu (`#334155` di `#f1f5f9`, border `#64748b`) supaya temuan menonjol saat dipindai. Galat = border pilihan merah `#be123c` + kalimat merah di bawah grup.
- **Pemeriksaan organ:** baris label 110px + tiga pilihan; dua kolom bila lebar cukup (minimal 360px per kolom), satu kolom di tablet tegak, label di atas pilihan di bawah 576px. Tombol outline "Tandai semua Normal" hanya mengisi organ yang belum dipilih; labelnya berubah menjadi "Tandai sisanya Normal (n)" lalu "Semua organ sudah dipilih" (nonaktif). Ada organ Abnormal dan keterangan fisik kosong → pengingat kuning di bawah keterangan fisik ("Abnormal: Abdomen. Tulis temuannya di keterangan fisik."), tidak memblokir simpan.
- **Tanda vital:** grid kotak kecil (minimal 120px, dua kolom di ponsel) dengan satuan di kanan (mmHg, x/menit, °C, %, kg, cm); contoh format hanya untuk TD dan GCS. Koma desimal diterima.
- **Status lokalis:** gambar Khanza `img/semua.png` (506×334) di kiri, keterangan di kanan; bertumpuk di bawah 992px.
- **Bilah simpan asesmen:** pola `.resume-bilah-simpan`; kiri "Tersimpan HH.MM" / "Belum disimpan" / "Ditulis di Khanza desktop", di bawahnya pesan merah: jumlah isian yang perlu diperbaiki (turun saat diperbaiki), atau pesan 409 "Asesmen sudah diubah dr. X pukul HH.MM. Muat ulang untuk melihat perubahan." dengan tombol outline "Muat ulang" (konfirmasi SweetAlert, isian lain tidak hilang sebelum dikonfirmasi). Kanan satu aksi utama "Simpan asesmen". Enter di isian satu baris tidak menyimpan. Setelah simpan, tanda "belum" di tab hilang dan chip alergi di kepala pasien ikut diperbarui.
- **Referensi asesmen (Radiologi, Laboratorium, Tatalaksana):** kotak `.resume-ref` di bawah isiannya. Tiap baris: badge sumber (`ekspertise`, `di luar rujukan`, `menunggu hasil`, `resep`), teks, tombol "Pakai". "Pakai" menambah di baris baru, tidak menimpa ketikan; baris yang sudah ada di isian (dipakai atau diketik sendiri) bertanda "Dipakai" dan nonaktif. "Pakai semua" di kanan bawah kotak hanya bila ada dua baris atau lebih yang belum dipakai. Tatalaksana memakai barang dan aturan dari apotek setelah resep divalidasi. Memuat = spinner + "Memuat referensi…"; kosong = kalimat yang menyebut apa yang belum ada; gagal = "Referensi gagal dimuat. Isian tetap bisa diketik." + "Coba lagi" (tautan hijau). Referensi dimuat ulang setiap tab Asesmen dibuka lagi, isian tidak disentuh. Di bawah 576px badge naik ke baris sendiri di atas teks.
- **Cetak asesmen:** tombol outline "Cetak PDF" di bilah simpan, kiri dari "Simpan asesmen", membuka PDF di tab baru dari asesmen tersimpan terakhir. Sebelum asesmen pernah disimpan tombolnya nonaktif dengan keterangan untuk pembaca layar "Simpan asesmen dulu sebelum mencetak."; aktif tanpa muat ulang setelah simpan pertama. Di ponsel kedua tombol satu baris, masing-masing setengah lebar, 44px.
- **PDF asesmen (A4, dompdf, hanya tabel):** kop logo RS, judul "PENILAIAN AWAL MEDIS GAWAT DARURAT (IGD)", identitas dua kolom, lalu I s.d. VI dalam tabel bergaris `#999` dengan kepala seksi hijau `#0d7a4a` di `#eef5f1`. Tanda vital 2 baris × 4 tanpa terlipat, 8 organ 2 baris × 4, gambar lokalis 5,6 cm di samping keterangannya (selalu dicetak). Isian kosong tercetak "-". Tatalaksana dicetak per baris supaya bisa bersambung ke halaman 2; dua baris terakhirnya selalu satu blok dengan tanda tangan (nama dokter penulis saja, tanpa SIP atau kode dokter dan tanpa QR, atas permintaan user 2026-11-11). Isi rata-rata prod muat 1 halaman; tatalaksana sampai sekitar 10 baris masih 1 halaman.
- **Tautan resume ranap:** di kepala pasien yang sudah rawat inap, di bawah "di IGD x j y m", tautan hijau `#0d7a4a` berikon "Tulis resume ranap" ke tab Resume modul Ranap (`ranap?action=view&no_rawat=…#resume`). Alasan: daftar Ranap hanya memuat pasien DPJP, sedangkan dokter IGD boleh menulis resume ranap pasien yang masuk lewat IGD.
- **Item resep yang tidak diberikan apotek:** setelah resep divalidasi, item resep dokter yang tidak ada di pemberian apotek tampil di bawah daftar "diberikan apotek" dengan label kuning `#92400e` (7,09:1) berikon segitiga "tidak diberikan apotek, tanyakan ke apotek atau minta ulang". Obat yang sudah ada di resep yang menunggu apotek tidak bisa ditambahkan lagi; pesan menyebut item lama (nama, jumlah, aturan) dan meminta menghapusnya dulu bila perlu diubah.
