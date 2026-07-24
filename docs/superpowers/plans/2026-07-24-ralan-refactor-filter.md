# Ralan Refactor + Payer Filter + ICD Diagnosa/Prosedur Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor modul ralan ke arsitektur pragmatis (Service/Request/Support), selaraskan UI ke Bootstrap 5, lalu implementasikan filter pemeriksaan per penjamin, search ICD tanpa titik, dan primer otomatis via validcode dengan staging-table UX.

**Architecture:** Controller tipis → FormRequest (validasi) → Service (logika/DB) → JSON/view. Logika murni (mapping penjamin, normalisasi search, penentuan prioritas) diekstrak ke `App\Support\*` sebagai kelas static yang bisa diunit-test tanpa DB. Frontend distandarkan ke Bootstrap 5.3.3; JS besar dipecah dari `index.blade.php` ke file di `public/js/ralan/`.

**Tech Stack:** Laravel 11 (PHP 8.2+), PHPUnit, Bootstrap 5.3.3, Select2 4.1.0-rc, jQuery 3.7.1, FontAwesome 6. DB MySQL Khanza (`db_test_rev`), tanpa tabel baru.

**Spec:** `docs/superpowers/specs/2026-07-24-ralan-refactor-filter-design.md`

---

## Catatan eksekusi

- Branch sudah dibuat: `feature/ralan-refactor-filter`.
- Unit test 3 helper adalah TDD murni (extends `PHPUnit\Framework\TestCase`, tanpa boot Laravel/DB). Jalankan: `php artisan test --testsuite=Unit`.
- Flow DB-bound (filter, simpan diagnosa) diverifikasi manual lewat browser + `php artisan tinker` ke `db_test_rev`. Feature test sqlite `:memory:` tidak punya skema Khanza, jadi tidak diandalkan.
- Commit di akhir tiap task. Pesan commit pakai Conventional Commits.

---

# FASE 0 — Hotfix Cache

## Task 0.1: Ganti cache driver ke file

**Files:**
- Modify: `.env`
- Modify: `config/cache.php` (default store)

- [ ] **Step 1: Ubah `.env`**

Ganti baris:
```
CACHE_STORE=database
```
menjadi:
```
CACHE_STORE=file
```

- [ ] **Step 2: Pastikan default `config/cache.php` membaca env**

Buka `config/cache.php`, konfirmasi baris default sudah:
```php
'default' => env('CACHE_STORE', 'database'),
```
Jika masih `'database'` literal, ubah ke `env('CACHE_STORE', 'file')`. Jika sudah pakai `env(...)`, biarkan.

- [ ] **Step 3: Clear config cache & verifikasi login**

Run:
```bash
php artisan config:clear
php artisan cache:clear
```
Expected: tidak error. Lalu buka `/login` di browser, login → tidak muncul `SQLSTATE[42S02] ... Table 'db_test_rev.cache' doesn't exist`. Dashboard tampil.

- [ ] **Step 4: Commit**

```bash
git add .env config/cache.php
git commit -m "fix(cache): switch CACHE_STORE to file to fix missing cache table on login"
```

---

# FASE 1 — Refactor Arsitektur + Standardisasi UI (behavior-preserving)

> F1 memindahkan logika lama ke Service/Request TANPA mengubah perilaku. Perilaku baru menyusul di F2. Setelah tiap refactor, uji manual bahwa fungsi lama masih jalan.

## Task 1.1: Buat helper `KodeSearch` (TDD)

**Files:**
- Create: `app/Support/KodeSearch.php`
- Test: `tests/Unit/Support/KodeSearchTest.php`

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Unit/Support/KodeSearchTest.php`:
```php
<?php

namespace Tests\Unit\Support;

use App\Support\KodeSearch;
use PHPUnit\Framework\TestCase;

class KodeSearchTest extends TestCase
{
    public function test_normalize_removes_dots(): void
    {
        $this->assertSame('9952', KodeSearch::normalize('99.52'));
        $this->assertSame('A031', KodeSearch::normalize('A03.1'));
    }

    public function test_normalize_removes_spaces(): void
    {
        $this->assertSame('9952', KodeSearch::normalize(' 99 52 '));
    }

    public function test_normalize_leaves_plain_term(): void
    {
        $this->assertSame('9952', KodeSearch::normalize('9952'));
        $this->assertSame('demam', KodeSearch::normalize('demam'));
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --testsuite=Unit --filter=KodeSearchTest`
Expected: FAIL (`Class "App\Support\KodeSearch" not found`).

- [ ] **Step 3: Implementasi minimal**

Create `app/Support/KodeSearch.php`:
```php
<?php

namespace App\Support;

class KodeSearch
{
    /**
     * Buang titik & spasi supaya "99.52" cocok saat diketik "9952".
     */
    public static function normalize(string $term): string
    {
        return str_replace(['.', ' '], '', $term);
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --testsuite=Unit --filter=KodeSearchTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Support/KodeSearch.php tests/Unit/Support/KodeSearchTest.php
git commit -m "feat(support): add KodeSearch dotless normalizer with unit tests"
```

## Task 1.2: Buat helper `PenjaminResolver` (TDD)

**Files:**
- Create: `app/Support/PenjaminResolver.php`
- Test: `tests/Unit/Support/PenjaminResolverTest.php`

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Unit/Support/PenjaminResolverTest.php`:
```php
<?php

namespace Tests\Unit\Support;

use App\Support\PenjaminResolver;
use PHPUnit\Framework\TestCase;

class PenjaminResolverTest extends TestCase
{
    public function test_klasifikasi(): void
    {
        $this->assertSame('umum', PenjaminResolver::klasifikasi('UM'));
        $this->assertSame('bpjs', PenjaminResolver::klasifikasi('BPJ'));
        $this->assertSame('pihak_ketiga', PenjaminResolver::klasifikasi('KPP'));
        $this->assertSame('pihak_ketiga', PenjaminResolver::klasifikasi('-'));
        $this->assertSame('pihak_ketiga', PenjaminResolver::klasifikasi(null));
    }

    public function test_lab_prefix(): void
    {
        // Ralan
        $this->assertSame('RJU', PenjaminResolver::labPrefix('Ralan', 'UM'));
        $this->assertSame('RJB', PenjaminResolver::labPrefix('Ralan', 'BPJ'));
        $this->assertSame('RJA', PenjaminResolver::labPrefix('Ralan', 'KPP'));
        // Ranap
        $this->assertSame('RIU', PenjaminResolver::labPrefix('Ranap', 'UM'));
        $this->assertSame('RIB', PenjaminResolver::labPrefix('Ranap', 'BPJ'));
        $this->assertSame('RIA', PenjaminResolver::labPrefix('Ranap', '003'));
    }

    public function test_radiologi_prefix(): void
    {
        $this->assertSame('RAD.RJ', PenjaminResolver::radiologiPrefix('Ralan', 'UM'));
        $this->assertSame('RAD.RI', PenjaminResolver::radiologiPrefix('Ranap', 'UM'));
        // BPJS selalu RAD.B tanpa peduli ralan/ranap
        $this->assertSame('RAD.B', PenjaminResolver::radiologiPrefix('Ralan', 'BPJ'));
        $this->assertSame('RAD.B', PenjaminResolver::radiologiPrefix('Ranap', 'BPJ'));
        // Pihak ketiga selalu RAD.P
        $this->assertSame('RAD.P', PenjaminResolver::radiologiPrefix('Ralan', 'KPP'));
        $this->assertSame('RAD.P', PenjaminResolver::radiologiPrefix('Ranap', 'CJ'));
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --testsuite=Unit --filter=PenjaminResolverTest`
Expected: FAIL (`Class "App\Support\PenjaminResolver" not found`).

- [ ] **Step 3: Implementasi minimal**

Create `app/Support/PenjaminResolver.php`:
```php
<?php

namespace App\Support;

class PenjaminResolver
{
    public const UMUM = 'umum';
    public const BPJS = 'bpjs';
    public const PIHAK_KETIGA = 'pihak_ketiga';

    public static function klasifikasi(?string $kdPj): string
    {
        return match ($kdPj) {
            'UM'  => self::UMUM,
            'BPJ' => self::BPJS,
            default => self::PIHAK_KETIGA,
        };
    }

    private static function isRanap(?string $statusLanjut): bool
    {
        return strtolower($statusLanjut ?? '') === 'ranap';
    }

    /** Prefix katalog lab: RI/RJ + U/B/A */
    public static function labPrefix(?string $statusLanjut, ?string $kdPj): string
    {
        $prefix = self::isRanap($statusLanjut) ? 'RI' : 'RJ';
        $suffix = match (self::klasifikasi($kdPj)) {
            self::UMUM => 'U',
            self::BPJS => 'B',
            default    => 'A',
        };

        return $prefix . $suffix;
    }

    /** Prefix katalog radiologi: RAD.RJ / RAD.RI / RAD.B / RAD.P */
    public static function radiologiPrefix(?string $statusLanjut, ?string $kdPj): string
    {
        return match (self::klasifikasi($kdPj)) {
            self::BPJS         => 'RAD.B',
            self::PIHAK_KETIGA => 'RAD.P',
            default            => self::isRanap($statusLanjut) ? 'RAD.RI' : 'RAD.RJ',
        };
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --testsuite=Unit --filter=PenjaminResolverTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Support/PenjaminResolver.php tests/Unit/Support/PenjaminResolverTest.php
git commit -m "feat(support): add PenjaminResolver payer->prefix mapping with unit tests"
```

## Task 1.3: Buat helper `PrioritasResolver` (TDD)

**Files:**
- Create: `app/Support/PrioritasResolver.php`
- Test: `tests/Unit/Support/PrioritasResolverTest.php`

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Unit/Support/PrioritasResolverTest.php`:
```php
<?php

namespace Tests\Unit\Support;

use App\Support\PrioritasResolver;
use PHPUnit\Framework\TestCase;

class PrioritasResolverTest extends TestCase
{
    public function test_valid_item_becomes_primary_regardless_of_input_order(): void
    {
        $items = [
            ['kode' => 'A', 'validcode' => '0'],
            ['kode' => 'B', 'validcode' => '0'],
            ['kode' => 'C', 'validcode' => '1'],
        ];
        // C primer (1); sisanya urut input: A=2, B=3
        $this->assertSame(['C' => 1, 'A' => 2, 'B' => 3], PrioritasResolver::hitung($items));
    }

    public function test_first_valid_wins_when_multiple_valid(): void
    {
        $items = [
            ['kode' => 'A', 'validcode' => '1'],
            ['kode' => 'B', 'validcode' => '1'],
        ];
        $this->assertSame(['A' => 1, 'B' => 2], PrioritasResolver::hitung($items));
    }

    public function test_fallback_first_item_when_no_valid(): void
    {
        $items = [
            ['kode' => 'A', 'validcode' => '0'],
            ['kode' => 'B', 'validcode' => '0'],
        ];
        $this->assertSame(['A' => 1, 'B' => 2], PrioritasResolver::hitung($items));
    }

    public function test_dedupe_keeps_first_occurrence(): void
    {
        $items = [
            ['kode' => 'A', 'validcode' => '0'],
            ['kode' => 'A', 'validcode' => '0'],
            ['kode' => 'B', 'validcode' => '1'],
        ];
        $this->assertSame(['B' => 1, 'A' => 2], PrioritasResolver::hitung($items));
    }

    public function test_empty(): void
    {
        $this->assertSame([], PrioritasResolver::hitung([]));
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --testsuite=Unit --filter=PrioritasResolverTest`
Expected: FAIL (`Class "App\Support\PrioritasResolver" not found`).

- [ ] **Step 3: Implementasi minimal**

Create `app/Support/PrioritasResolver.php`:
```php
<?php

namespace App\Support;

class PrioritasResolver
{
    /**
     * Tentukan prioritas: primer = item pertama ber-validcode '1';
     * jika tidak ada, item pertama. Sisanya 2,3,4,... urut input.
     *
     * @param array<int, array{kode:string, validcode:string}> $items terurut (existing dulu, lalu baru)
     * @return array<string, int> map kode => prioritas
     */
    public static function hitung(array $items): array
    {
        // Dedupe, pertahankan kemunculan pertama.
        $seen = [];
        $ordered = [];
        foreach ($items as $it) {
            if (isset($seen[$it['kode']])) {
                continue;
            }
            $seen[$it['kode']] = true;
            $ordered[] = $it;
        }

        if ($ordered === []) {
            return [];
        }

        $primerIndex = null;
        foreach ($ordered as $i => $it) {
            if ((string) $it['validcode'] === '1') {
                $primerIndex = $i;
                break;
            }
        }
        if ($primerIndex === null) {
            $primerIndex = 0;
        }

        $result = [$ordered[$primerIndex]['kode'] => 1];
        $next = 2;
        foreach ($ordered as $i => $it) {
            if ($i === $primerIndex) {
                continue;
            }
            $result[$it['kode']] = $next++;
        }

        return $result;
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --testsuite=Unit --filter=PrioritasResolverTest`
Expected: PASS (5 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Support/PrioritasResolver.php tests/Unit/Support/PrioritasResolverTest.php
git commit -m "feat(support): add PrioritasResolver validcode-based primary resolver with unit tests"
```

## Task 1.4: Ekstrak `LaboratoriumService` (behavior-preserving)

**Files:**
- Create: `app/Services/Ralan/LaboratoriumService.php`
- Modify: `app/Http/Controllers/LaboratoriumController.php`

> Pindahkan logika dari controller apa adanya (masih pakai filter `kd_pj` lama — perilaku belum berubah). Filter prefix menyusul di F2.

- [ ] **Step 1: Buat service dengan logika lama**

Create `app/Services/Ralan/LaboratoriumService.php`:
```php
<?php

namespace App\Services\Ralan;

use App\Models\PermintaanLab;
use App\Models\PermintaanPemeriksaanLab;
use App\Models\PermintaanDetailPermintaanLab;
use App\Models\TemplateLaboratorium;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaboratoriumService
{
    /** @return array<int, array{id:string, text:string}> */
    public function cariPemeriksaan(string $search, ?string $noRawat): array
    {
        $kdPj = '-';
        if ($noRawat) {
            $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first();
            if ($reg) {
                $kdPj = $reg->kd_pj;
            }
        }

        $rows = DB::table('jns_perawatan_lab')
            ->where('status', '1')
            ->where(function ($q) use ($kdPj) {
                $q->where('kd_pj', $kdPj)->orWhere('kd_pj', '-');
            })
            ->where(function ($q) use ($search) {
                $q->where('kd_jenis_prw', 'like', "%$search%")
                  ->orWhere('nm_perawatan', 'like', "%$search%");
            })
            ->orderByRaw("FIELD(kd_pj, ?, '-')", [$kdPj])
            ->limit(20)
            ->get();

        return $rows->map(fn ($p) => [
            'id'   => $p->kd_jenis_prw,
            'text' => $p->kd_jenis_prw . ' - ' . $p->nm_perawatan,
        ])->all();
    }

    public function simpanPermintaan(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tgl = Carbon::now()->format('Y-m-d');
            $jam = Carbon::now()->format('H:i:s');
            $noOrder = 'PL' . str_replace('-', '', $tgl) . $this->nextNumber($tgl);

            $reg = DB::table('reg_periksa')->where('no_rawat', $data['no_rawat'])->first();
            $statusLanjut = ($reg && strtolower($reg->status_lanjut) === 'ranap') ? 'ranap' : 'ralan';

            PermintaanLab::create([
                'noorder'            => $noOrder,
                'no_rawat'           => $data['no_rawat'],
                'tgl_permintaan'     => $tgl,
                'jam_permintaan'     => $jam,
                'tgl_sampel'         => null,
                'jam_sampel'         => null,
                'tgl_hasil'          => null,
                'jam_hasil'          => null,
                'dokter_perujuk'     => Auth::user()->decrypted_id,
                'status'             => $statusLanjut,
                'informasi_tambahan' => $data['informasi_tambahan'] ?? '-',
                'diagnosa_klinis'    => $data['diagnosa_klinis'] ?? '-',
            ]);

            foreach ($data['kd_jenis_prw'] as $kdJenis) {
                PermintaanPemeriksaanLab::create([
                    'noorder'      => $noOrder,
                    'kd_jenis_prw' => $kdJenis,
                    'stts_bayar'   => 'Belum',
                ]);

                foreach (($data['detail_lab'][$kdJenis] ?? []) as $idTemplate) {
                    PermintaanDetailPermintaanLab::create([
                        'noorder'      => $noOrder,
                        'kd_jenis_prw' => $kdJenis,
                        'id_template'  => $idTemplate,
                        'stts_bayar'   => 'Belum',
                    ]);
                }
            }

            return [
                'status'  => 'success-lab',
                'message' => 'Permintaan Lab berhasil dikirim dengan nomor: ' . $noOrder,
                'noorder' => $noOrder,
            ];
        });
    }

    public function templates(string $kdJenisPrw)
    {
        return TemplateLaboratorium::where('kd_jenis_prw', $kdJenisPrw)
            ->select('id_template', 'Pemeriksaan')
            ->get();
    }

    public function hapus(?string $noorder, ?string $kdJenis, ?string $idTemplate): array
    {
        return DB::transaction(function () use ($noorder, $kdJenis, $idTemplate) {
            $isProcessed = DB::table('permintaan_pemeriksaan_lab')
                ->where('noorder', $noorder)
                ->where('stts_bayar', 'Sudah')
                ->exists();

            if ($isProcessed) {
                return ['status' => 'error', 'message' => 'Data sudah diproses lab!', 'code' => 403];
            }

            if ($noorder && $kdJenis && $idTemplate) {
                DB::table('permintaan_detail_permintaan_lab')
                    ->where(['noorder' => $noorder, 'kd_jenis_prw' => $kdJenis, 'id_template' => $idTemplate])
                    ->delete();
                $msg = 'Item detail berhasil dihapus.';
            } elseif ($noorder && $kdJenis) {
                DB::table('permintaan_detail_permintaan_lab')->where(['noorder' => $noorder, 'kd_jenis_prw' => $kdJenis])->delete();
                DB::table('permintaan_pemeriksaan_lab')->where(['noorder' => $noorder, 'kd_jenis_prw' => $kdJenis])->delete();
                $msg = 'Jenis pemeriksaan berhasil dihapus.';
            } else {
                DB::table('permintaan_detail_permintaan_lab')->where('noorder', $noorder)->delete();
                DB::table('permintaan_pemeriksaan_lab')->where('noorder', $noorder)->delete();
                DB::table('permintaan_lab')->where('noorder', $noorder)->delete();
                $msg = 'Seluruh order berhasil dibatalkan.';
            }

            if (DB::table('permintaan_pemeriksaan_lab')->where('noorder', $noorder)->count() === 0) {
                DB::table('permintaan_lab')->where('noorder', $noorder)->delete();
            }

            return ['status' => 'success', 'message' => $msg];
        });
    }

    private function nextNumber(string $date): string
    {
        $lastNo = DB::table('permintaan_lab')
            ->where('tgl_permintaan', $date)
            ->max(DB::raw('CONVERT(RIGHT(noorder, 4), signed)')) ?? 0;

        return sprintf('%04s', ($lastNo + 1));
    }
}
```

- [ ] **Step 2: Tipiskan controller memanggil service**

Replace seluruh isi `app/Http/Controllers/LaboratoriumController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\PermintaanLab;
use App\Services\Ralan\LaboratoriumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaboratoriumController extends Controller
{
    public function __construct(private LaboratoriumService $service) {}

    public function getLabPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);

        $pasien = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->where('no_rawat', $no_rawat)
            ->first();

        $riwayat = PermintaanLab::with(['pemeriksaan.jenisPerawatan'])
            ->where('no_rawat', $no_rawat)
            ->where('tgl_permintaan', date('Y-m-d'))
            ->get();

        return view('ralan.lab', compact('pasien', 'riwayat'));
    }

    public function getPemeriksaan(Request $request)
    {
        return response()->json(
            $this->service->cariPemeriksaan($request->search ?? '', $request->no_rawat)
        );
    }

    public function storePermintaanLab(Request $request)
    {
        $request->validate([
            'no_rawat'     => 'required',
            'kd_jenis_prw' => 'required|array|min:1',
        ]);

        return response()->json($this->service->simpanPermintaan($request->all()));
    }

    public function getTemplates($kd_jenis_prw)
    {
        return response()->json($this->service->templates($kd_jenis_prw));
    }

    public function destroyLab(Request $request)
    {
        $res = $this->service->hapus($request->noorder, $request->kd_jenis_prw, $request->id_template);
        $code = $res['code'] ?? 200;
        unset($res['code']);

        return response()->json($res, $code);
    }
}
```

- [ ] **Step 3: Verifikasi manual**

Buka ralan → pilih pasien → tab Lab. Cari pemeriksaan (masih muncul), kirim permintaan, hapus. Harus jalan seperti sebelum refactor.

- [ ] **Step 4: Commit**

```bash
git add app/Services/Ralan/LaboratoriumService.php app/Http/Controllers/LaboratoriumController.php
git commit -m "refactor(lab): extract LaboratoriumService, thin controller (behavior-preserving)"
```

## Task 1.5: Ekstrak `RadiologiService` (behavior-preserving)

**Files:**
- Create: `app/Services/Ralan/RadiologiService.php`
- Modify: `app/Http/Controllers/RadiologiController.php`

- [ ] **Step 1: Buat service dengan logika lama**

Create `app/Services/Ralan/RadiologiService.php`:
```php
<?php

namespace App\Services\Ralan;

use App\Models\PermintaanRadiologi;
use App\Models\PermintaanPemeriksaanRadiologi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RadiologiService
{
    /** @return array<int, array{id:string, text:string}> */
    public function cariPemeriksaan(string $search, ?string $noRawat): array
    {
        $kdPj = '-';
        if ($noRawat) {
            $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first();
            if ($reg) {
                $kdPj = $reg->kd_pj;
            }
        }

        $rows = DB::table('jns_perawatan_radiologi')
            ->where('status', '1')
            ->where(function ($q) use ($kdPj) {
                $q->where('kd_pj', $kdPj)->orWhere('kd_pj', '-');
            })
            ->where(function ($q) use ($search) {
                $q->where('kd_jenis_prw', 'like', "%$search%")
                  ->orWhere('nm_perawatan', 'like', "%$search%");
            })
            ->orderByRaw("FIELD(kd_pj, ?, '-')", [$kdPj])
            ->limit(20)
            ->get();

        return $rows->map(fn ($p) => [
            'id'   => $p->kd_jenis_prw,
            'text' => $p->kd_jenis_prw . ' - ' . $p->nm_perawatan,
        ])->all();
    }

    public function simpanPermintaan(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tgl = Carbon::now()->format('Y-m-d');
            $jam = Carbon::now()->format('H:i:s');
            $noOrder = 'PR' . str_replace('-', '', $tgl) . $this->nextNumber($tgl);

            $reg = DB::table('reg_periksa')->where('no_rawat', $data['no_rawat'])->first();
            $statusLanjut = ($reg && strtolower($reg->status_lanjut) === 'ranap') ? 'ranap' : 'ralan';

            PermintaanRadiologi::create([
                'noorder'            => $noOrder,
                'no_rawat'           => $data['no_rawat'],
                'tgl_permintaan'     => $tgl,
                'jam_permintaan'     => $jam,
                'tgl_sampel'         => null,
                'jam_sampel'         => null,
                'tgl_hasil'          => null,
                'jam_hasil'          => null,
                'dokter_perujuk'     => Auth::user()->decrypted_id,
                'status'             => $statusLanjut,
                'informasi_tambahan' => $data['informasi_tambahan'] ?? '-',
                'diagnosa_klinis'    => $data['diagnosa_klinis'] ?? '-',
            ]);

            foreach ($data['kd_jenis_prw_rad'] as $kdJenis) {
                PermintaanPemeriksaanRadiologi::create([
                    'noorder'      => $noOrder,
                    'kd_jenis_prw' => $kdJenis,
                    'stts_bayar'   => 'Belum',
                ]);
            }

            return [
                'status'  => 'success-rad',
                'message' => 'Permintaan Radiologi berhasil dikirim dengan nomor: ' . $noOrder,
                'noorder' => $noOrder,
            ];
        });
    }

    public function hapus(string $noorder, ?string $kdJenisPrw): array
    {
        return DB::transaction(function () use ($noorder, $kdJenisPrw) {
            $isProcessed = DB::table('permintaan_pemeriksaan_radiologi')
                ->where('noorder', $noorder)
                ->where('stts_bayar', 'Sudah')
                ->exists();

            if ($isProcessed) {
                return ['status' => 'error', 'message' => 'Gagal! Data sudah diproses.', 'code' => 403];
            }

            if ($kdJenisPrw) {
                DB::table('permintaan_pemeriksaan_radiologi')->where(['noorder' => $noorder, 'kd_jenis_prw' => $kdJenisPrw])->delete();
            } else {
                DB::table('permintaan_pemeriksaan_radiologi')->where('noorder', $noorder)->delete();
                DB::table('permintaan_radiologi')->where('noorder', $noorder)->delete();
            }

            if (DB::table('permintaan_pemeriksaan_radiologi')->where('noorder', $noorder)->count() === 0) {
                DB::table('permintaan_radiologi')->where('noorder', $noorder)->delete();
            }

            return ['status' => 'success-hapus-rad', 'message' => 'Data berhasil dihapus'];
        });
    }

    private function nextNumber(string $date): string
    {
        $lastNo = DB::table('permintaan_radiologi')
            ->where('tgl_permintaan', $date)
            ->max(DB::raw('CONVERT(RIGHT(noorder, 4), signed)')) ?? 0;

        return sprintf('%04s', ($lastNo + 1));
    }
}
```

- [ ] **Step 2: Tipiskan controller**

Replace seluruh isi `app/Http/Controllers/RadiologiController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\PermintaanRadiologi;
use App\Services\Ralan\RadiologiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RadiologiController extends Controller
{
    public function __construct(private RadiologiService $service) {}

    public function getRadiologiPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);

        $pasien = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->where('no_rawat', $no_rawat)
            ->first();

        $riwayat = PermintaanRadiologi::with(['pemeriksaan.jenisPerawatan'])
            ->where('no_rawat', $no_rawat)
            ->where('tgl_permintaan', date('Y-m-d'))
            ->get();

        return view('ralan.radiologi', compact('pasien', 'riwayat'));
    }

    public function getPemeriksaanRadiologi(Request $request)
    {
        return response()->json(
            $this->service->cariPemeriksaan($request->search ?? '', $request->no_rawat)
        );
    }

    public function storePermintaanRadiologi(Request $request)
    {
        $request->validate([
            'no_rawat'         => 'required',
            'kd_jenis_prw_rad' => 'required|array|min:1',
        ]);

        return response()->json($this->service->simpanPermintaan($request->all()));
    }

    public function destroyRadiologi($noorder, $kd_jenis_prw = null)
    {
        $res = $this->service->hapus($noorder, $kd_jenis_prw);
        $code = $res['code'] ?? 200;
        unset($res['code']);

        return response()->json($res, $code);
    }
}
```

- [ ] **Step 3: Verifikasi manual**

Tab Radiologi: cari, kirim, hapus. Harus jalan seperti sebelumnya.

- [ ] **Step 4: Commit**

```bash
git add app/Services/Ralan/RadiologiService.php app/Http/Controllers/RadiologiController.php
git commit -m "refactor(radiologi): extract RadiologiService, thin controller (behavior-preserving)"
```

## Task 1.6: Ekstrak `DiagnosaProsedurService` (behavior-preserving)

**Files:**
- Create: `app/Services/Ralan/DiagnosaProsedurService.php`
- Modify: `app/Http/Controllers/DiagnosaProsedurController.php`

> Pindahkan logika lama apa adanya (search tanpa dotless, prioritas lama). Perilaku baru menyusul di F2.

- [ ] **Step 1: Buat service dengan logika lama**

Create `app/Services/Ralan/DiagnosaProsedurService.php`:
```php
<?php

namespace App\Services\Ralan;

use App\Models\DiagnosaPasien;
use App\Models\ProsedurPasien;
use Illuminate\Support\Facades\DB;

class DiagnosaProsedurService
{
    public function dataPasien(string $noRawat): array
    {
        $diagnosa = DiagnosaPasien::with('penyakit')
            ->where('no_rawat', $noRawat)
            ->orderBy('prioritas', 'asc')
            ->get();

        $prosedur = ProsedurPasien::with('icd9')
            ->where('no_rawat', $noRawat)
            ->orderBy('prioritas', 'asc')
            ->get();

        return compact('diagnosa', 'prosedur');
    }

    /** @return array<int, array{id:string, text:string}> */
    public function searchIcd10(string $search): array
    {
        $rows = DB::table('penyakit')
            ->where(function ($q) use ($search) {
                $q->where('kd_penyakit', 'like', "%$search%")
                  ->orWhere('nm_penyakit', 'like', "%$search%");
            })
            ->limit(20)
            ->get();

        return $rows->map(fn ($i) => [
            'id'   => $i->kd_penyakit,
            'text' => $i->kd_penyakit . ' - ' . $i->nm_penyakit,
        ])->all();
    }

    /** @return array<int, array{id:string, text:string}> */
    public function searchIcd9(string $search): array
    {
        $rows = DB::table('icd9')
            ->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%$search%")
                  ->orWhere('deskripsi_panjang', 'like', "%$search%")
                  ->orWhere('deskripsi_pendek', 'like', "%$search%");
            })
            ->limit(20)
            ->get();

        return $rows->map(fn ($i) => [
            'id'   => $i->kode,
            'text' => $i->kode . ' - ' . ($i->deskripsi_panjang ?: $i->deskripsi_pendek),
        ])->all();
    }

    private function statusFor(string $noRawat): string
    {
        $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first();

        return ($reg && strtolower($reg->status_lanjut) === 'ranap') ? 'Ranap' : 'Ralan';
    }

    public function simpanDiagnosa(string $noRawat, array $kdList, ?string $prioritas, ?string $statusPenyakit): array
    {
        $status = $this->statusFor($noRawat);

        DB::transaction(function () use ($noRawat, $kdList, $prioritas, $statusPenyakit, $status) {
            foreach ($kdList as $index => $kd) {
                $hasPrimary = DB::table('diagnosa_pasien')
                    ->where('no_rawat', $noRawat)->where('prioritas', '1')->exists();

                $prio = ($prioritas && $index === 0) ? $prioritas : ($hasPrimary ? '2' : '1');

                DB::table('diagnosa_pasien')->updateOrInsert(
                    ['no_rawat' => $noRawat, 'kd_penyakit' => $kd, 'status' => $status],
                    ['prioritas' => $prio, 'status_penyakit' => $statusPenyakit ?? 'Lama']
                );
            }
        });

        return ['status' => 'success-diagnosa', 'message' => 'Diagnosa ICD-10 berhasil disimpan'];
    }

    public function simpanProsedur(string $noRawat, array $kodeList, ?string $jumlah): array
    {
        $status = $this->statusFor($noRawat);

        DB::transaction(function () use ($noRawat, $kodeList, $jumlah, $status) {
            foreach ($kodeList as $kd) {
                $hasPrimary = DB::table('prosedur_pasien')
                    ->where('no_rawat', $noRawat)->where('prioritas', '1')->exists();

                DB::table('prosedur_pasien')->updateOrInsert(
                    ['no_rawat' => $noRawat, 'kode' => $kd, 'status' => $status],
                    ['prioritas' => $hasPrimary ? '2' : '1', 'jumlah' => $jumlah ?? 1]
                );
            }
        });

        return ['status' => 'success-prosedur', 'message' => 'Prosedur ICD-9 berhasil disimpan'];
    }

    public function hapusDiagnosa(string $noRawat, string $kdPenyakit): array
    {
        DB::table('diagnosa_pasien')
            ->where('no_rawat', $noRawat)->where('kd_penyakit', $kdPenyakit)->delete();

        return ['status' => 'success-hapus-diagnosa', 'message' => 'Diagnosa berhasil dihapus'];
    }

    public function hapusProsedur(string $noRawat, string $kode): array
    {
        DB::table('prosedur_pasien')
            ->where('no_rawat', $noRawat)->where('kode', $kode)->delete();

        return ['status' => 'success-hapus-prosedur', 'message' => 'Prosedur berhasil dihapus'];
    }
}
```

- [ ] **Step 2: Tipiskan controller**

Replace seluruh isi `app/Http/Controllers/DiagnosaProsedurController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Services\Ralan\DiagnosaProsedurService;
use Illuminate\Http\Request;

class DiagnosaProsedurController extends Controller
{
    public function __construct(private DiagnosaProsedurService $service) {}

    public function index($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        $data = $this->service->dataPasien($no_rawat);

        return view('ralan.diagnosa-prosedur', array_merge($data, ['no_rawat' => $no_rawat]));
    }

    public function searchIcd10(Request $request)
    {
        return response()->json($this->service->searchIcd10($request->search ?? ''));
    }

    public function searchIcd9(Request $request)
    {
        return response()->json($this->service->searchIcd9($request->search ?? ''));
    }

    public function storeDiagnosa(Request $request)
    {
        $request->validate([
            'no_rawat'    => 'required',
            'kd_penyakit' => 'required|array|min:1',
        ]);

        return response()->json($this->service->simpanDiagnosa(
            $request->no_rawat,
            $request->kd_penyakit,
            $request->prioritas,
            $request->status_penyakit
        ));
    }

    public function storeProsedur(Request $request)
    {
        $request->validate([
            'no_rawat' => 'required',
            'kode'     => 'required|array|min:1',
        ]);

        return response()->json($this->service->simpanProsedur(
            $request->no_rawat,
            $request->kode,
            $request->jumlah
        ));
    }

    public function destroyDiagnosa($no_rawat, $kd_penyakit)
    {
        return response()->json($this->service->hapusDiagnosa(str_replace('-', '/', $no_rawat), $kd_penyakit));
    }

    public function destroyProsedur($no_rawat, $kode)
    {
        return response()->json($this->service->hapusProsedur(str_replace('-', '/', $no_rawat), $kode));
    }
}
```

- [ ] **Step 3: Verifikasi manual**

Tab Diagnosa & Prosedur: search ICD-10/ICD-9, simpan, hapus. Harus jalan seperti sebelumnya.

- [ ] **Step 4: Commit**

```bash
git add app/Services/Ralan/DiagnosaProsedurService.php app/Http/Controllers/DiagnosaProsedurController.php
git commit -m "refactor(diagnosa): extract DiagnosaProsedurService, thin controller (behavior-preserving)"
```

## Task 1.7: Ekstrak `DashboardService`

**Files:**
- Create: `app/Services/Ralan/DashboardService.php`
- Modify: `app/Http/Controllers/DashboardController.php`

- [ ] **Step 1: Buat service**

Create `app/Services/Ralan/DashboardService.php` — pindahkan seluruh isi method `index()` dari `DashboardController` menjadi method `data(): array` yang mengembalikan `compact('stats', 'pasienTerbaru', 'jadwalDokter', 'isAdmin')`. Salin persis kueri yang ada sekarang (cache 5 menit, filter kd_dokter, jadwal hari ini):
```php
<?php

namespace App\Services\Ralan;

use App\Models\Jadwal;
use App\Models\Pasien;
use App\Models\RegPeriksa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function data(): array
    {
        $isAdmin = session('role') === 'admin';
        $kd_dokter = $isAdmin ? null : (Auth::user()->decrypted_id ?? null);
        $today = Carbon::today()->toDateString();

        $cacheKey = 'dashboard_stats_' . ($isAdmin ? 'admin' : ($kd_dokter ?? 'guest'));

        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($isAdmin, $kd_dokter, $today) {
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            $queryTotalKunjungan = RegPeriksa::whereDate('tgl_registrasi', $today)->where('stts', '!=', 'Batal');
            $queryPasienPoliBulanIni = RegPeriksa::whereMonth('tgl_registrasi', $currentMonth)
                ->whereYear('tgl_registrasi', $currentYear)
                ->where('stts', '!=', 'Batal');
            $queryBelumDiperiksa = RegPeriksa::whereDate('tgl_registrasi', $today)->where('stts', 'Belum');

            if (!$isAdmin && $kd_dokter) {
                $queryTotalKunjungan->where('kd_dokter', $kd_dokter);
                $queryPasienPoliBulanIni->where('kd_dokter', $kd_dokter);
                $queryBelumDiperiksa->where('kd_dokter', $kd_dokter);
            }

            return [
                'totalPasien'        => Pasien::count(),
                'kunjunganHariIni'   => $queryTotalKunjungan->count(),
                'pasienPoliBulanIni' => $queryPasienPoliBulanIni->count(),
                'belumDiperiksa'     => $queryBelumDiperiksa->count(),
            ];
        });

        $queryPasien = RegPeriksa::with(['pasien'])->orderByDesc('tgl_registrasi');
        if (!$isAdmin && $kd_dokter) {
            $queryPasien->where('kd_dokter', $kd_dokter);
        }
        $pasienTerbaru = $queryPasien->limit(5)->get();

        $dayNameUpper = mb_strtoupper(Carbon::now()->locale('id')->dayName);
        $dayNames = $dayNameUpper === 'MINGGU' ? ['MINGGU', 'AKHAD'] : [$dayNameUpper];

        $queryJadwal = Jadwal::with(['poliklinik', 'dokter'])->whereIn('hari_kerja', $dayNames);
        if (!$isAdmin && $kd_dokter) {
            $queryJadwal->where('kd_dokter', $kd_dokter);
        }
        $jadwalDokter = $queryJadwal->get();

        return compact('stats', 'pasienTerbaru', 'jadwalDokter', 'isAdmin');
    }
}
```

- [ ] **Step 2: Tipiskan controller**

Replace `app/Http/Controllers/DashboardController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Services\Ralan\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    public function index()
    {
        return view('dashboard', $this->service->data());
    }
}
```

- [ ] **Step 3: Pastikan route dashboard memakai controller**

Buka `routes/web.php`. Jika route `/dashboard` masih closure `return view('dashboard')`, ganti ke controller:
```php
use App\Http\Controllers\DashboardController;
// ...
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
```
(Tambahkan `use` statement bila belum ada.)

- [ ] **Step 4: Verifikasi manual**

Login → dashboard tampil dengan stat, pasien terbaru, jadwal. Tidak error cache.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ralan/DashboardService.php app/Http/Controllers/DashboardController.php routes/web.php
git commit -m "refactor(dashboard): extract DashboardService, thin controller"
```

## Task 1.8: FormRequest untuk lab, radiologi, diagnosa, prosedur

**Files:**
- Create: `app/Http/Requests/Ralan/StorePermintaanLabRequest.php`
- Create: `app/Http/Requests/Ralan/StorePermintaanRadiologiRequest.php`
- Create: `app/Http/Requests/Ralan/StoreDiagnosaRequest.php`
- Create: `app/Http/Requests/Ralan/StoreProsedurRequest.php`
- Modify: 3 controller terkait (ganti `$request->validate` → typed FormRequest)

- [ ] **Step 1: Buat 4 FormRequest**

Create `app/Http/Requests/Ralan/StorePermintaanLabRequest.php`:
```php
<?php

namespace App\Http\Requests\Ralan;

use Illuminate\Foundation\Http\FormRequest;

class StorePermintaanLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'     => 'required',
            'kd_jenis_prw' => 'required|array|min:1',
        ];
    }
}
```

Create `app/Http/Requests/Ralan/StorePermintaanRadiologiRequest.php`:
```php
<?php

namespace App\Http\Requests\Ralan;

use Illuminate\Foundation\Http\FormRequest;

class StorePermintaanRadiologiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'         => 'required',
            'kd_jenis_prw_rad' => 'required|array|min:1',
        ];
    }
}
```

Create `app/Http/Requests/Ralan/StoreDiagnosaRequest.php`:
```php
<?php

namespace App\Http\Requests\Ralan;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat'    => 'required',
            'kd_penyakit' => 'required|array|min:1',
        ];
    }
}
```

Create `app/Http/Requests/Ralan/StoreProsedurRequest.php`:
```php
<?php

namespace App\Http\Requests\Ralan;

use Illuminate\Foundation\Http\FormRequest;

class StoreProsedurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat' => 'required',
            'kode'     => 'required|array|min:1',
        ];
    }
}
```

- [ ] **Step 2: Pakai FormRequest di controller**

Di `LaboratoriumController::storePermintaanLab`, ganti signature `Request $request` → `StorePermintaanLabRequest $request` dan hapus blok `$request->validate([...])`. Tambah `use App\Http\Requests\Ralan\StorePermintaanLabRequest;`.

Lakukan sama untuk:
- `RadiologiController::storePermintaanRadiologi` → `StorePermintaanRadiologiRequest`
- `DiagnosaProsedurController::storeDiagnosa` → `StoreDiagnosaRequest`
- `DiagnosaProsedurController::storeProsedur` → `StoreProsedurRequest`

- [ ] **Step 3: Verifikasi manual**

Simpan lab/rad/diagnosa/prosedur tetap jalan; submit kosong → 422 dengan pesan validasi.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Requests/Ralan app/Http/Controllers/LaboratoriumController.php app/Http/Controllers/RadiologiController.php app/Http/Controllers/DiagnosaProsedurController.php
git commit -m "refactor(ralan): move store validation into typed FormRequests"
```

## Task 1.9: Ekstrak SOAP & Vital ke service + AJAX (JSON response)

**Files:**
- Create: `app/Services/Ralan/SoapService.php`
- Create: `app/Services/Ralan/VitalSignService.php`
- Create: `app/Http/Requests/Ralan/StoreSoapRequest.php`
- Create: `app/Http/Requests/Ralan/StoreVitalSignRequest.php`
- Modify: `app/Http/Controllers/RalanController.php` (`storeSOAP`, `storeVital`)

- [ ] **Step 1: Buat 2 service**

Create `app/Services/Ralan/SoapService.php`:
```php
<?php

namespace App\Services\Ralan;

use App\Models\PemeriksaanRalan;
use Illuminate\Support\Facades\Auth;

class SoapService
{
    public function simpan(array $data): void
    {
        PemeriksaanRalan::updateOrCreate(
            ['no_rawat' => $data['no_rawat']],
            [
                'tgl_perawatan' => date('Y-m-d'),
                'jam_rawat'     => date('H:i:s'),
                'keluhan'       => $data['keluhan'] ?? '',
                'pemeriksaan'   => $data['objek'] ?? '',
                'penilaian'     => $data['penilaian'] ?? '',
                'rtl'           => $data['plan'] ?? '',
                'instruksi'     => $data['instruksi'] ?? '',
                'nip'           => Auth::user()->decrypted_id,
                'kesadaran'     => 'Compos Mentis',
                'spo2'          => '-',
                'lingkar_perut' => '-',
                'evaluasi'      => '-',
            ]
        );
    }
}
```

Create `app/Services/Ralan/VitalSignService.php`:
```php
<?php

namespace App\Services\Ralan;

use App\Models\PemeriksaanRalan;
use Illuminate\Support\Facades\Auth;

class VitalSignService
{
    public function simpan(array $data): void
    {
        PemeriksaanRalan::updateOrCreate(
            ['no_rawat' => $data['no_rawat']],
            [
                'tgl_perawatan' => date('Y-m-d'),
                'jam_rawat'     => date('H:i:s'),
                'suhu_tubuh'    => $data['suhu_tubuh'] ?? '-',
                'tensi'         => $data['tensi'] ?? '-',
                'nadi'          => $data['nadi'] ?? '-',
                'respirasi'     => $data['respirasi'] ?? '-',
                'tinggi'        => $data['tinggi'] ?? '-',
                'berat'         => $data['berat'] ?? '-',
                'gcs'           => $data['gcs'] ?? '-',
                'kesadaran'     => $data['kesadaran'] ?? 'Compos Mentis',
                'alergi'        => $data['alergi'] ?? '-',
                'nip'           => Auth::user()->decrypted_id,
            ]
        );
    }
}
```

- [ ] **Step 2: Buat 2 FormRequest**

Create `app/Http/Requests/Ralan/StoreSoapRequest.php` dan `StoreVitalSignRequest.php`, keduanya `authorize(): true` dan `rules(): ['no_rawat' => 'required']` (pola sama seperti Task 1.8).

- [ ] **Step 3: Ubah controller balikin JSON**

Di `app/Http/Controllers/RalanController.php`:
- Tambah `use App\Services\Ralan\SoapService; use App\Services\Ralan\VitalSignService; use App\Http\Requests\Ralan\StoreSoapRequest; use App\Http\Requests\Ralan\StoreVitalSignRequest;`
- Ganti `storeSOAP`:
```php
public function storeSOAP(StoreSoapRequest $request, SoapService $service)
{
    try {
        $service->simpan($request->all());
        return response()->json(['status' => 'success-soap', 'message' => 'Data SOAP berhasil disimpan']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan SOAP: ' . $e->getMessage()], 500);
    }
}
```
- Ganti `storeVital`:
```php
public function storeVital(StoreVitalSignRequest $request, VitalSignService $service)
{
    try {
        $service->simpan($request->all());
        return response()->json(['status' => 'success-vital', 'message' => 'Data Vital Sign berhasil disimpan']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan Vital Sign: ' . $e->getMessage()], 500);
    }
}
```

> Frontend AJAX untuk SOAP/Vital dikerjakan di Task 1.13 (bersama pembaruan blade). Untuk sekarang controller sudah balikin JSON.

- [ ] **Step 4: Commit**

```bash
git add app/Services/Ralan/SoapService.php app/Services/Ralan/VitalSignService.php app/Http/Requests/Ralan/StoreSoapRequest.php app/Http/Requests/Ralan/StoreVitalSignRequest.php app/Http/Controllers/RalanController.php
git commit -m "refactor(soap-vital): extract services + FormRequests, return JSON for AJAX"
```

## Task 1.10: Tema Select2 Bootstrap 5 + pindah CSS inline

**Files:**
- Modify: `public/css/admin.css` (tambah blok select2 + gaya dari resep)
- Modify: `resources/views/ralan/resep.blade.php` (hapus blok `<style>`)

- [ ] **Step 1: Tambah gaya Select2 BS5 ke `admin.css`**

Tambahkan di akhir `public/css/admin.css`:
```css
/* ===== Select2 x Bootstrap 5 alignment ===== */
.select2-container .select2-selection--single {
    height: calc(2.25rem + 2px);
    display: flex;
    align-items: center;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
.select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
    padding-left: 0.75rem;
}
.select2-container .select2-selection--single .select2-selection__arrow {
    height: calc(2.25rem);
}
.select2-container--default .select2-selection--multiple {
    min-height: calc(2.25rem + 2px);
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
.select2-container--open .select2-dropdown {
    border-color: #86b7fe;
}
.select2-search--dropdown .select2-search__field {
    padding: 0.375rem 0.5rem;
}
.select2-container { width: 100% !important; }

/* ===== dipindah dari resep.blade.php ===== */
.nav-pills .nav-link { border-radius: 0.25rem; transition: all 0.3s ease; }
.nav-pills .nav-link:hover { background-color: #e9ecef; }
.nav-pills .nav-link.active { background-color: #0d6efd; color: #fff !important; }
```

- [ ] **Step 2: Hapus `<style>` dari `resep.blade.php`**

Hapus seluruh blok `<style>...</style>` (baris 176-224) di `resources/views/ralan/resep.blade.php`. Sisakan penutup `</div>` markup di atasnya.

- [ ] **Step 3: Verifikasi manual**

Buka tab Lab/Rad/Resep → kotak Select2 tingginya normal, ikon kaca pembesar di search dropdown tidak turun. Tab Resep pill tetap bergaya.

- [ ] **Step 4: Commit**

```bash
git add public/css/admin.css resources/views/ralan/resep.blade.php
git commit -m "style(ralan): add select2 bootstrap5 theme, move inline resep styles to admin.css"
```

## Task 1.11: Standardisasi kelas Bootstrap 5 di lab & radiologi blade

**Files:**
- Modify: `resources/views/ralan/lab.blade.php`
- Modify: `resources/views/ralan/radiologi.blade.php`

- [ ] **Step 1: Perbaiki `lab.blade.php`**

Ganti:
- `<select ... class="form-control" multiple>` → `class="form-select"` (baris select-lab).
- `<div class="form-group">` → `<div class="mb-3">`.
- `<small class="text-muted italic">` → `<small class="text-muted fst-italic">`.
- `<div class="text-right mt-3">` → `<div class="text-end mt-3">`.

- [ ] **Step 2: Perbaiki `radiologi.blade.php`**

Ganti:
- `<select ... class="form-control" multiple>` → `class="form-select"`.
- `<div class="form-group">` → `<div class="mb-3">` (semua).
- `<div class="text-right mt-3">` → `<div class="text-end mt-3">`.

- [ ] **Step 3: Verifikasi manual**

Tab Lab & Radiologi tampil rapi, select2 konsisten, tombol kirim di kanan.

- [ ] **Step 4: Commit**

```bash
git add resources/views/ralan/lab.blade.php resources/views/ralan/radiologi.blade.php
git commit -m "style(ralan): standardize lab & radiologi forms to Bootstrap 5 classes"
```

## Task 1.12: Standardisasi Bootstrap 5 di Vital Sign blade

**Files:**
- Modify: `resources/views/ralan/vital-sign.blade.php`

- [ ] **Step 1: Ganti kelas & alert BS4 → BS5**

Di `resources/views/ralan/vital-sign.blade.php`:
- Alert: `data-dismiss="alert"` → `data-bs-dismiss="alert"`, `<button ... class="close">&times;</button>` → `<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`.
- Semua `class="form-group"` → `class="mb-3"`.
- Semua `font-weight-bold` → `fw-bold`.
- Semua `<select ... class="form-control form-control-sm">` → `class="form-select form-select-sm"`.
- `mr-1` → `me-1` (di ikon `<i class="fas fa-save mr-1">` dan `fa-history mr-1`).
- `text-left` → `text-start` (kolom alergi tabel).

- [ ] **Step 2: Verifikasi manual**

Tab Vital Sign tampil rapi; dropdown Kesadaran bergaya form-select; tombol tutup alert berfungsi (klik x menutup).

- [ ] **Step 3: Commit**

```bash
git add resources/views/ralan/vital-sign.blade.php
git commit -m "style(vital): migrate vital-sign form to Bootstrap 5 classes and alert markup"
```

## Task 1.13: Pisah JS dari `index.blade.php` + AJAX SOAP/Vital

**Files:**
- Create: `public/js/ralan/ralan-core.js`
- Modify: `resources/views/ralan/index.blade.php` (pindahkan blok `<script>`)
- Modify: `resources/views/layouts/partials/scripts.blade.php` atau `index.blade.php` (muat file JS baru)
- Modify: `resources/views/ralan/soap.blade.php` (form → AJAX)
- Modify: `resources/views/ralan/vital-sign.blade.php` (form → AJAX)

> Ini task besar & berisiko. Kerjakan hati-hati, verifikasi tiap tab setelahnya.

- [ ] **Step 1: Ekstrak JS ralan ke file terpisah**

Pindahkan seluruh isi blok `@push('scripts') <script> ... </script> @endpush` dari `resources/views/ralan/index.blade.php` ke file baru `public/js/ralan/ralan-core.js`.

Catatan penting: blok JS memakai Blade directive (`{{ route(...) }}`, `{{ csrf_token() }}`). Sebelum memindah, ganti nilai-nilai itu menjadi variabel global yang di-set dari blade. Di `index.blade.php`, sebelum memuat `ralan-core.js`, tambahkan:
```html
<script>
  window.RALAN = {
    routes: {
      searchLab: "{{ route('ralan.search-lab') }}",
      searchRadiologi: "{{ route('ralan.search-radiologi') }}",
      searchIcd10: "{{ route('ralan.search-icd10') }}",
      searchIcd9: "{{ route('ralan.search-icd9') }}",
      storeDiagnosa: "{{ route('ralan.store-diagnosa') }}",
      storeProsedur: "{{ route('ralan.store-prosedur') }}",
      soapSimpan: "{{ route('ralan.soap.simpan') }}",
      vitalSimpan: "{{ route('ralan.store-vital') }}"
    },
    csrf: "{{ csrf_token() }}"
  };
</script>
<script src="{{ asset('js/ralan/ralan-core.js') }}"></script>
```
Lalu di `ralan-core.js`, ganti tiap `"{{ route('x') }}"` → `window.RALAN.routes.x` dan `"{{ csrf_token() }}"` → `window.RALAN.csrf`.

- [ ] **Step 2: Verifikasi semua tab masih jalan**

Muat ulang halaman ralan (hard refresh, Ctrl+F5). Uji setiap tab: SOAP, Vital, Lab, Radiologi, Resep, Diagnosa&Prosedur. Pastikan select2, simpan, hapus, lazy-load tab semua berfungsi seperti sebelum pemindahan. Cek Console tidak ada error `route is not defined` / `RALAN is undefined`.

- [ ] **Step 3: AJAX-kan form SOAP**

Di `resources/views/ralan/soap.blade.php`, ubah `<form action=... method="POST" id="formSoap">` menjadi tanpa submit native. Tambahkan handler di `ralan-core.js`:
```js
$(document).on('submit', '#formSoap', function (e) {
    e.preventDefault();
    const form = $(this);
    $.ajax({
        url: window.RALAN.routes.soapSimpan,
        method: 'POST',
        data: form.serialize(),
        success: function (res) {
            tampilkanSukses(res.message);
            // reload konten tab SOAP saja
            loadSoap(true);
        },
        error: function (xhr) {
            tampilkanError(xhr.responseJSON?.message || 'Gagal menyimpan SOAP.');
        }
    });
});
```
Gunakan fungsi loader tab SOAP yang sudah ada (samakan namanya dengan yang dipakai lazy-load tab SOAP di `ralan-core.js`; jika bernama lain, sesuaikan). Jika belum ada loader khusus, reload konten `#content-soap` via endpoint `ralan.get-soap`.

- [ ] **Step 4: AJAX-kan form Vital Sign**

Di `resources/views/ralan/vital-sign.blade.php`, bungkus form dengan `id="formVital"` (tambahkan bila belum ada). Tambah handler di `ralan-core.js` mengikuti pola SOAP, pakai `window.RALAN.routes.vitalSimpan` dan loader tab Vital.

- [ ] **Step 5: Verifikasi manual**

Simpan SOAP & Vital → muncul toast sukses, konten tab ter-refresh, TANPA reload seluruh halaman (URL tidak berubah, tab lain tetap kondisinya).

- [ ] **Step 6: Commit**

```bash
git add public/js/ralan/ralan-core.js resources/views/ralan/index.blade.php resources/views/ralan/soap.blade.php resources/views/ralan/vital-sign.blade.php
git commit -m "refactor(ralan): extract JS to public/js/ralan-core.js, AJAX-ify SOAP & Vital forms"
```

---

# FASE 2 — Perilaku Baru

## Task 2.1: Filter lab per prefix penjamin

**Files:**
- Modify: `app/Services/Ralan/LaboratoriumService.php` (`cariPemeriksaan`)

- [ ] **Step 1: Ganti logika `cariPemeriksaan`**

Ganti method `cariPemeriksaan` di `LaboratoriumService` menjadi:
```php
public function cariPemeriksaan(string $search, ?string $noRawat): array
{
    $kdPj = null;
    $statusLanjut = null;
    if ($noRawat) {
        $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first();
        if ($reg) {
            $kdPj = $reg->kd_pj;
            $statusLanjut = $reg->status_lanjut;
        }
    }

    $target = \App\Support\PenjaminResolver::labPrefix($statusLanjut, $kdPj);

    $rows = DB::table('jns_perawatan_lab')
        ->where('status', '1')
        ->where(function ($q) use ($target) {
            $q->where('kd_jenis_prw', 'like', $target . '%')
              ->orWhereRaw("kd_jenis_prw NOT REGEXP '^R[IJ][UAB]'"); // legacy tetap muncul
        })
        ->where(function ($q) use ($search) {
            $q->where('kd_jenis_prw', 'like', "%$search%")
              ->orWhere('nm_perawatan', 'like', "%$search%");
        })
        ->orderByRaw('CASE WHEN kd_jenis_prw LIKE ? THEN 0 ELSE 1 END', [$target . '%'])
        ->orderBy('kd_jenis_prw')
        ->limit(30)
        ->get();

    return $rows->map(fn ($p) => [
        'id'   => $p->kd_jenis_prw,
        'text' => $p->kd_jenis_prw . ' - ' . $p->nm_perawatan,
    ])->all();
}
```
Tambahkan `use App\Support\PenjaminResolver;` di atas (atau pakai FQN seperti di atas).

- [ ] **Step 2: Verifikasi manual dgn tinker + browser**

Verifikasi target prefix per payer via tinker (contoh pasien UM ralan → `RJU`, BPJ → `RJB`, pihak ketiga → `RJA`). Di browser, buka tab Lab untuk pasien UM lalu BPJS, ketik "darah" → daftar berbeda sesuai prefix, plus kode legacy tetap ada. Kode payer lain (mis. `RJB` untuk pasien UM) TIDAK muncul.

- [ ] **Step 3: Commit**

```bash
git add app/Services/Ralan/LaboratoriumService.php
git commit -m "feat(lab): filter pemeriksaan by payer prefix (RI/RJ + U/B/A), keep legacy codes"
```

## Task 2.2: Filter radiologi per prefix penjamin

**Files:**
- Modify: `app/Services/Ralan/RadiologiService.php` (`cariPemeriksaan`)

- [ ] **Step 1: Ganti logika `cariPemeriksaan`**

Ganti method `cariPemeriksaan` di `RadiologiService` menjadi:
```php
public function cariPemeriksaan(string $search, ?string $noRawat): array
{
    $kdPj = null;
    $statusLanjut = null;
    if ($noRawat) {
        $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first();
        if ($reg) {
            $kdPj = $reg->kd_pj;
            $statusLanjut = $reg->status_lanjut;
        }
    }

    $target = \App\Support\PenjaminResolver::radiologiPrefix($statusLanjut, $kdPj);

    $rows = DB::table('jns_perawatan_radiologi')
        ->where('status', '1')
        ->where(function ($q) use ($target) {
            $q->where('kd_jenis_prw', 'like', $target . '%')
              ->orWhere(function ($q2) {
                  $q2->where('kd_jenis_prw', 'not like', 'RAD.RJ%')
                     ->where('kd_jenis_prw', 'not like', 'RAD.RI%')
                     ->where('kd_jenis_prw', 'not like', 'RAD.B%')
                     ->where('kd_jenis_prw', 'not like', 'RAD.P%'); // legacy tetap muncul
              });
        })
        ->where(function ($q) use ($search) {
            $q->where('kd_jenis_prw', 'like', "%$search%")
              ->orWhere('nm_perawatan', 'like', "%$search%");
        })
        ->orderByRaw('CASE WHEN kd_jenis_prw LIKE ? THEN 0 ELSE 1 END', [$target . '%'])
        ->orderBy('kd_jenis_prw')
        ->limit(30)
        ->get();

    return $rows->map(fn ($p) => [
        'id'   => $p->kd_jenis_prw,
        'text' => $p->kd_jenis_prw . ' - ' . $p->nm_perawatan,
    ])->all();
}
```

- [ ] **Step 2: Verifikasi manual**

Pasien UM ralan → `RAD.RJ` muncul di atas; UM ranap → `RAD.RI`; BPJS → `RAD.B` (ralan & ranap); pihak ketiga → `RAD.P`. Legacy (RJK/RNK) tetap muncul di bawah.

- [ ] **Step 3: Commit**

```bash
git add app/Services/Ralan/RadiologiService.php
git commit -m "feat(radiologi): filter pemeriksaan by payer prefix (RAD.RJ/RI/B/P), keep legacy codes"
```

## Task 2.3: Search ICD-10/ICD-9 tanpa titik

**Files:**
- Modify: `app/Services/Ralan/DiagnosaProsedurService.php` (`searchIcd10`, `searchIcd9`)

- [ ] **Step 1: Tambah match dotless di `searchIcd10`**

Ganti isi `searchIcd10`:
```php
public function searchIcd10(string $search): array
{
    $normalized = \App\Support\KodeSearch::normalize($search);

    $rows = DB::table('penyakit')
        ->where(function ($q) use ($search, $normalized) {
            $q->where('kd_penyakit', 'like', "%$search%")
              ->orWhere('nm_penyakit', 'like', "%$search%")
              ->orWhereRaw("REPLACE(kd_penyakit, '.', '') LIKE ?", ["%$normalized%"]);
        })
        ->limit(20)
        ->get();

    return $rows->map(fn ($i) => [
        'id'   => $i->kd_penyakit,
        'text' => $i->kd_penyakit . ' - ' . $i->nm_penyakit,
    ])->all();
}
```

- [ ] **Step 2: Tambah match dotless di `searchIcd9`**

Ganti isi `searchIcd9`:
```php
public function searchIcd9(string $search): array
{
    $normalized = \App\Support\KodeSearch::normalize($search);

    $rows = DB::table('icd9')
        ->where(function ($q) use ($search, $normalized) {
            $q->where('kode', 'like', "%$search%")
              ->orWhere('deskripsi_panjang', 'like', "%$search%")
              ->orWhere('deskripsi_pendek', 'like', "%$search%")
              ->orWhereRaw("REPLACE(kode, '.', '') LIKE ?", ["%$normalized%"]);
        })
        ->limit(20)
        ->get();

    return $rows->map(fn ($i) => [
        'id'   => $i->kode,
        'text' => $i->kode . ' - ' . ($i->deskripsi_panjang ?: $i->deskripsi_pendek),
    ])->all();
}
```

- [ ] **Step 3: Verifikasi manual**

Tab Diagnosa: ketik `9952` di ICD-9 → `99.52` muncul. Ketik `A031` di ICD-10 → `A03.1` muncul. Search nama biasa tetap jalan.

- [ ] **Step 4: Commit**

```bash
git add app/Services/Ralan/DiagnosaProsedurService.php
git commit -m "feat(icd): dotless code search so 9952 matches 99.52"
```

## Task 2.4: Primer otomatis via validcode + jumlah per prosedur

**Files:**
- Modify: `app/Services/Ralan/DiagnosaProsedurService.php` (`simpanDiagnosa`, `simpanProsedur`, `hapusDiagnosa`, `hapusProsedur`; tambah `recomputeDiagnosa`, `recomputeProsedur`)

- [ ] **Step 1: Tambah method recompute + ganti simpan/hapus**

Di `DiagnosaProsedurService`, tambahkan `use App\Support\PrioritasResolver;` lalu ganti/ tambah method berikut:
```php
public function simpanDiagnosa(string $noRawat, array $kdList, ?string $prioritas, ?string $statusPenyakit): array
{
    $status = $this->statusFor($noRawat);

    DB::transaction(function () use ($noRawat, $kdList, $statusPenyakit, $status) {
        $max = (int) DB::table('diagnosa_pasien')
            ->where('no_rawat', $noRawat)->where('status', $status)->max('prioritas');

        foreach (array_values($kdList) as $i => $kd) {
            DB::table('diagnosa_pasien')->updateOrInsert(
                ['no_rawat' => $noRawat, 'kd_penyakit' => $kd, 'status' => $status],
                ['prioritas' => $max + $i + 1, 'status_penyakit' => $statusPenyakit ?? 'Lama']
            );
        }

        $this->recomputeDiagnosa($noRawat, $status);
    });

    return ['status' => 'success-diagnosa', 'message' => 'Diagnosa ICD-10 berhasil disimpan'];
}

public function simpanProsedur(string $noRawat, array $kodeList, array $jumlahList): array
{
    $status = $this->statusFor($noRawat);

    DB::transaction(function () use ($noRawat, $kodeList, $jumlahList, $status) {
        $max = (int) DB::table('prosedur_pasien')
            ->where('no_rawat', $noRawat)->where('status', $status)->max('prioritas');

        foreach (array_values($kodeList) as $i => $kd) {
            $jumlah = $jumlahList[$i] ?? null;
            $jumlah = ($jumlah === '' || $jumlah === null) ? null : $jumlah;

            DB::table('prosedur_pasien')->updateOrInsert(
                ['no_rawat' => $noRawat, 'kode' => $kd, 'status' => $status],
                ['prioritas' => $max + $i + 1, 'jumlah' => $jumlah]
            );
        }

        $this->recomputeProsedur($noRawat, $status);
    });

    return ['status' => 'success-prosedur', 'message' => 'Prosedur ICD-9 berhasil disimpan'];
}

public function hapusDiagnosa(string $noRawat, string $kdPenyakit): array
{
    $status = $this->statusFor($noRawat);
    DB::transaction(function () use ($noRawat, $kdPenyakit, $status) {
        DB::table('diagnosa_pasien')
            ->where('no_rawat', $noRawat)->where('kd_penyakit', $kdPenyakit)->where('status', $status)->delete();
        $this->recomputeDiagnosa($noRawat, $status);
    });

    return ['status' => 'success-hapus-diagnosa', 'message' => 'Diagnosa berhasil dihapus'];
}

public function hapusProsedur(string $noRawat, string $kode): array
{
    $status = $this->statusFor($noRawat);
    DB::transaction(function () use ($noRawat, $kode, $status) {
        DB::table('prosedur_pasien')
            ->where('no_rawat', $noRawat)->where('kode', $kode)->where('status', $status)->delete();
        $this->recomputeProsedur($noRawat, $status);
    });

    return ['status' => 'success-hapus-prosedur', 'message' => 'Prosedur berhasil dihapus'];
}

private function recomputeDiagnosa(string $noRawat, string $status): void
{
    $items = DB::table('diagnosa_pasien as dp')
        ->leftJoin('penyakit as p', 'p.kd_penyakit', '=', 'dp.kd_penyakit')
        ->where('dp.no_rawat', $noRawat)->where('dp.status', $status)
        ->orderBy('dp.prioritas')
        ->selectRaw("dp.kd_penyakit as kode, COALESCE(p.validcode, '0') as validcode")
        ->get()
        ->map(fn ($r) => ['kode' => $r->kode, 'validcode' => $r->validcode])
        ->all();

    foreach (PrioritasResolver::hitung($items) as $kode => $prio) {
        DB::table('diagnosa_pasien')
            ->where(['no_rawat' => $noRawat, 'kd_penyakit' => $kode, 'status' => $status])
            ->update(['prioritas' => $prio]);
    }
}

private function recomputeProsedur(string $noRawat, string $status): void
{
    $items = DB::table('prosedur_pasien as pp')
        ->leftJoin('icd9 as i', 'i.kode', '=', 'pp.kode')
        ->where('pp.no_rawat', $noRawat)->where('pp.status', $status)
        ->orderBy('pp.prioritas')
        ->selectRaw("pp.kode as kode, COALESCE(i.validcode, '0') as validcode")
        ->get()
        ->map(fn ($r) => ['kode' => $r->kode, 'validcode' => $r->validcode])
        ->all();

    foreach (PrioritasResolver::hitung($items) as $kode => $prio) {
        DB::table('prosedur_pasien')
            ->where(['no_rawat' => $noRawat, 'kode' => $kode, 'status' => $status])
            ->update(['prioritas' => $prio]);
    }
}
```

- [ ] **Step 2: Ubah controller prosedur kirim array jumlah**

Di `DiagnosaProsedurController::storeProsedur`, ganti pemanggilan service:
```php
return response()->json($this->service->simpanProsedur(
    $request->no_rawat,
    $request->kode,
    $request->jumlah ?? []
));
```
Pastikan `$request->jumlah` berupa array (dari staging table di Task 2.5). Bila `null`, kirim `[]`.

Di `storeDiagnosa`, hapus argumen `$request->prioritas` (dropdown prioritas dibuang di UI). Ubah signature service `simpanDiagnosa` agar tidak lagi butuh `$prioritas` — hapus parameter `$prioritas` dari method dan pemanggilan. (Panggil `simpanDiagnosa($request->no_rawat, $request->kd_penyakit, $request->status_penyakit)`.)

> Sesuaikan definisi `simpanDiagnosa` di Step 1: hapus param `?string $prioritas` sehingga signature-nya `simpanDiagnosa(string $noRawat, array $kdList, ?string $statusPenyakit)`.

- [ ] **Step 3: Verifikasi manual dgn tinker**

Ambil satu `no_rawat` uji. Simpan 3 diagnosa di mana hanya yang ke-3 `validcode=1` → cek `diagnosa_pasien`: yang ke-3 `prioritas=1`, lainnya 2 & 3. Simpan prosedur dengan jumlah berbeda → `jumlah` tersimpan per baris; kosong → `null`. Hapus salah satu → prioritas dihitung ulang.

- [ ] **Step 4: Commit**

```bash
git add app/Services/Ralan/DiagnosaProsedurService.php app/Http/Controllers/DiagnosaProsedurController.php
git commit -m "feat(diagnosa): auto-primary via validcode + per-procedure jumlah, recompute on save/delete"
```

## Task 2.5: Staging-table UX diagnosa & prosedur

**Files:**
- Modify: `resources/views/ralan/diagnosa-prosedur.blade.php`
- Modify: `public/js/ralan/ralan-core.js`

- [ ] **Step 1: Ubah blade — Select2 single + tabel staging + buang dropdown prioritas**

Ganti isi `resources/views/ralan/diagnosa-prosedur.blade.php`:
- Diagnosa: `<select id="select-icd10">` jadi single (hapus `multiple`, ganti `name` jadi tanpa `[]`), hapus blok dropdown "Prioritas" (baris 21-28), pertahankan dropdown `status_penyakit`. Ganti tombol "Tambah Diagnosa" → tombol dipindah ke tiap baris staging; tambah tombol **Simpan Semua Diagnosa**. Tambah `<table id="staging-diagnosa">` (kolom: Kode, Nama, Aksi) di atas tabel tersimpan.
- Prosedur: `<select id="select-icd9">` jadi single (hapus `multiple`). Hapus input Jumlah tunggal. Tambah `<table id="staging-prosedur">` (kolom: Kode, Deskripsi, Jumlah [input number], Aksi) + tombol **Simpan Semua Prosedur**.
- Tabel "Daftar ... Pasien" (tersimpan) tetap seperti sekarang.

Struktur staging diagnosa (contoh markup):
```html
<div class="d-flex gap-2 mb-2">
  <select id="select-icd10" class="form-select" style="width: 100%;"></select>
</div>
<div class="mb-2">
  <label class="form-label fw-bold">Status Penyakit</label>
  <select id="status-penyakit-diagnosa" class="form-select">
    <option value="Lama" selected>Lama</option>
    <option value="Baru">Baru</option>
  </select>
</div>
<table class="table table-sm table-bordered" id="staging-diagnosa">
  <thead class="table-light"><tr><th>Kode</th><th>Nama</th><th style="width:60px;"></th></tr></thead>
  <tbody></tbody>
</table>
<button type="button" id="btn-simpan-diagnosa" class="btn btn-primary w-100">
  <i class="fas fa-save me-1"></i> Simpan Semua Diagnosa
</button>
```
Struktur staging prosedur serupa, dengan kolom Jumlah `<input type="number" class="form-control form-control-sm staging-jumlah" min="1" value="1">`.

- [ ] **Step 2: JS — tambah item ke staging saat pilih**

Di `ralan-core.js`, ubah select2 diagnosa/prosedur (di `initSelect2DiagnosaProsedur`) menjadi single-select, dan pada event `select2:select` tambahkan baris ke staging table lalu kosongkan select:
```js
$('#select-icd10').on('select2:select', function (e) {
    const d = e.params.data;
    if ($('#staging-diagnosa tbody tr[data-kode="' + d.id + '"]').length) { $(this).val(null).trigger('change'); return; }
    $('#staging-diagnosa tbody').append(
        '<tr data-kode="' + d.id + '">' +
        '<td>' + d.id + '</td><td>' + d.text.replace(d.id + ' - ', '') + '</td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger staging-remove"><i class="fas fa-times"></i></button></td>' +
        '</tr>'
    );
    $(this).val(null).trigger('change');
});

$('#select-icd9').on('select2:select', function (e) {
    const d = e.params.data;
    if ($('#staging-prosedur tbody tr[data-kode="' + d.id + '"]').length) { $(this).val(null).trigger('change'); return; }
    $('#staging-prosedur tbody').append(
        '<tr data-kode="' + d.id + '">' +
        '<td>' + d.id + '</td><td>' + d.text.replace(d.id + ' - ', '') + '</td>' +
        '<td><input type="number" class="form-control form-control-sm staging-jumlah" min="1" value="1"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger staging-remove"><i class="fas fa-times"></i></button></td>' +
        '</tr>'
    );
    $(this).val(null).trigger('change');
});

$(document).on('click', '.staging-remove', function () { $(this).closest('tr').remove(); });
```

- [ ] **Step 3: JS — Simpan Semua kirim batch**

Ganti handler `#btn-simpan-diagnosa` & `#btn-simpan-prosedur` di `ralan-core.js`:
```js
$(document).on('click', '#btn-simpan-diagnosa', function () {
    const rows = $('#staging-diagnosa tbody tr');
    if (!rows.length) { tampilkanError('Belum ada diagnosa di daftar.'); return; }
    const kd_penyakit = rows.map(function () { return $(this).data('kode'); }).get();
    $.ajax({
        url: window.RALAN.routes.storeDiagnosa,
        method: 'POST',
        data: {
            _token: window.RALAN.csrf,
            no_rawat: currentNoRawat,           // gunakan variabel no_rawat aktif yg dipakai tab ini
            kd_penyakit: kd_penyakit,
            status_penyakit: $('#status-penyakit-diagnosa').val()
        },
        success: function (res) { tampilkanSukses(res.message); loadDiagnosaProsedur(true); },
        error: function (xhr) { tampilkanError(xhr.responseJSON?.message || 'Gagal menyimpan diagnosa.'); }
    });
});

$(document).on('click', '#btn-simpan-prosedur', function () {
    const rows = $('#staging-prosedur tbody tr');
    if (!rows.length) { tampilkanError('Belum ada prosedur di daftar.'); return; }
    const kode = [], jumlah = [];
    rows.each(function () { kode.push($(this).data('kode')); jumlah.push($(this).find('.staging-jumlah').val()); });
    $.ajax({
        url: window.RALAN.routes.storeProsedur,
        method: 'POST',
        data: { _token: window.RALAN.csrf, no_rawat: currentNoRawat, kode: kode, jumlah: jumlah },
        success: function (res) { tampilkanSukses(res.message); loadDiagnosaProsedur(true); },
        error: function (xhr) { tampilkanError(xhr.responseJSON?.message || 'Gagal menyimpan prosedur.'); }
    });
});
```
Catatan: `currentNoRawat` harus berisi `no_rawat` asli (format dengan `/`). Di blade `diagnosa-prosedur` sudah ada `{{ $no_rawat }}`; set ke variabel global saat tab dimuat, mis. simpan di `data-no-rawat` pada container form dan baca `$('#form-diagnosa').data('no-rawat')`. Sesuaikan dengan pola yang ada (`currentSafeNoRawat` dipakai untuk URL delete). Untuk store, gunakan no_rawat asli; ambil dari hidden input `input[name=no_rawat]` yang sudah ada di blade.

- [ ] **Step 4: Verifikasi manual**

Tab Diagnosa & Prosedur:
- Pilih beberapa ICD-10 → tiap pilihan langsung jadi baris staging; hapus baris jalan; Simpan Semua → tersimpan, primer otomatis benar (yang validcode=1), staging kosong, daftar tersimpan ter-refresh.
- Prosedur: tiap baris punya input Jumlah; isi beda-beda; Simpan Semua → jumlah tersimpan per baris.

- [ ] **Step 5: Commit**

```bash
git add resources/views/ralan/diagnosa-prosedur.blade.php public/js/ralan/ralan-core.js
git commit -m "feat(diagnosa): staging-table UX for multi diagnosa/prosedur with per-row jumlah"
```

## Task 2.6: Jalankan seluruh unit test & smoke test akhir

- [ ] **Step 1: Unit test**

Run: `php artisan test --testsuite=Unit`
Expected: PASS semua (KodeSearch, PenjaminResolver, PrioritasResolver, + Example).

- [ ] **Step 2: Smoke test manual seluruh tab**

Untuk 2 pasien (satu UM, satu BPJS): buka semua tab (SOAP, Vital, Lab, Radiologi, Resep, Diagnosa&Prosedur). Simpan di tiap tab, hapus data, cek tidak ada error di Console & Network 200. Login ulang untuk memastikan cache aman.

- [ ] **Step 3: Commit (bila ada perbaikan kecil)**

```bash
git add -A
git commit -m "test(ralan): final unit run + smoke fixes"
```

---

## Ringkasan mapping spec → task

| Spec | Task |
|---|---|
| Cache hotfix | 0.1 |
| KodeSearch / PenjaminResolver / PrioritasResolver | 1.1 / 1.2 / 1.3 |
| Service lab/rad/diagnosa/dashboard/soap/vital | 1.4–1.7, 1.9 |
| FormRequest | 1.8, 1.9 |
| Select2 BS5 + CSS pindah | 1.10 |
| Standardisasi BS5 lab/rad/vital | 1.11, 1.12 |
| Split index JS + AJAX SOAP/Vital | 1.13 |
| Filter prefix lab | 2.1 |
| Filter prefix radiologi | 2.2 |
| Search dotless ICD | 2.3 |
| Primer validcode + jumlah per prosedur | 2.4 |
| Staging-table UX | 2.5 |
| Verifikasi akhir | 2.6 |
