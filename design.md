# design.md: RME RSU Nirwana

Sumber: gaya yang sudah berjalan di aplikasi (`public/css/admin.css`, `resources/views/layouts/*`, tab ralan/ranap). Dokumen ini mencatat gaya itu dan aturan pakainya, tidak membuat arah baru. Perubahan desain wajib memperbarui file ini.

## 1. Identitas

- **Produk:** rekam medis elektronik RSU Nirwana Banjarbaru, dipakai dokter di poli (ralan) dan bangsal (ranap). Mungkin perawat nanti.
- **Watak:** alat kerja klinis. Tenang, padat, mudah dipindai saat antrean pasien panjang. Data pasien adalah fokus; dekorasi tidak punya tempat.
- **Dial:** ENERGY 1 / RHYTHM 1 / MOTION 1. Alasan: dokter mengerjakan puluhan pasien per sesi, jadi tata letak yang sama di setiap tab membuat tangan hafal posisi tombol. Keseragaman ini disengaja.
- **Motif identitas:** kartu datar `ralan-card` (border tipis, tanpa bayangan) dengan kepala kartu berisi judul di kiri dan aksi di kanan; hijau RS hanya untuk aksi utama dan status "selesai".

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
- **Notifikasi:** toast yang sudah ada (`tampilkanSukses`/`tampilkanError`) dan SweetAlert untuk konfirmasi hapus.

## 6. State

Setiap bagian yang memuat data punya:
- **Memuat:** spinner + teks "Memuat …" di dalam area kartu (pola yang sudah ada di tab ralan/ranap).
- **Kosong:** kalimat yang menyebut langkah berikutnya ("Isi Penilaian (A) di SOAP untuk menyusun resume.") + tautan ke tempatnya.
- **Error:** kalimat penyebab + tombol "Coba lagi". Error validasi tampil di dekat kolomnya, isian tidak hilang.

## 7. Aksesibilitas

- Semua kontrol bisa dicapai dengan Tab sesuai urutan visual, diaktifkan dengan Enter/Space; modal tertutup dengan Escape.
- Fokus terlihat: outline 2px `--primary-dark` dengan offset 2px. `outline: none` tanpa pengganti dilarang (perbaiki `.search-input:focus` bila tersentuh).
- Tombol ikon (↑ ↓ ✕) wajib `aria-label`. Status tidak hanya dibedakan warna: badge selalu berisi teks.

## 8. Responsif

- Breakpoint Bootstrap 5 (576/768/992/1200). Target utama layar desktop poli; tetap rapi di tablet dan ponsel.
- Tab ralan sudah bergulir horizontal di layar sempit; isi tab tidak boleh membuat halaman bergulir horizontal.
- Di bawah 768px: kartu bertumpuk satu kolom, label ringkasan pindah ke atas isinya, tombol kepala kartu turun ke baris sendiri.
- Target sentuh minimal 44px untuk tombol dan pilihan radio.

## 9. Gerak

Hanya transisi hover/fokus singkat (yang sudah ada, `--transition`). Tidak ada animasi masuk.

## 10. Teks

- Bahasa Indonesia, kalimat pendek, istilah klinis yang dipakai dokter (SOAP, TTV, DPJP).
- Tanpa tanda pisah panjang (em dash). Pakai koma, titik, titik dua, atau tanda kurung.
- Tidak ada klaim tanpa dasar (misalnya tanda tangan elektronik sebelum ada TTE).

## 11. Tema

Hanya terang, sama dengan aplikasi sekarang. Mode gelap belum diminta; bila nanti diminta, kedua mode wajib diuji penuh.
