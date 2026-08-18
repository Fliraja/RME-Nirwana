<?php

namespace Tests\Feature;

use App\Models\AntrolBpjs;
use App\Services\Ralan\AntrolBpjsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AntrolBpjsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $antrolService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->antrolService = new AntrolBpjsService();
    }

    /**
     * Test 1: Insert jam perawat pertama kali (hanya 1x)
     */
    public function test_insert_jam_perawat_once_success()
    {
        // Setup: buat data reg_periksa dan penjab dengan kd_pj = BPJ
        DB::table('penjab')->insert([
            'kd_pj' => 'BPJ',
            'png_jawab' => 'BPJS Kesehatan',
        ]);

        DB::table('reg_periksa')->insert([
            'no_rawat' => 'TEST001',
            'no_rkm_medis' => 'RM001',
            'kd_pj' => 'BPJ',
        ]);

        DB::table('antrol_bpjs')->insert([
            'no_rawat' => 'TEST001',
            'jam_periksa_perawat' => '00:00:00',
            'jam_periksa_dokter' => '00:00:00',
        ]);

        // Action: Set jam perawat
        $jam = '10:30:45';
        $result = $this->antrolService->insertJamPeriksaPerawatOnce('TEST001', $jam);

        // Assert: berhasil
        $this->assertTrue($result['success']);

        // Verify: jam perawat tersimpan
        $antrol = DB::table('antrol_bpjs')
            ->where('no_rawat', 'TEST001')
            ->first();
        $this->assertEquals('10:30:45', $antrol->jam_periksa_perawat);
    }

    /**
     * Test 2: Insert jam perawat 2x harus SKIP (prevent duplicate)
     */
    public function test_insert_jam_perawat_twice_skip_second()
    {
        DB::table('penjab')->insert(['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS']);
        DB::table('reg_periksa')->insert(['no_rawat' => 'TEST002', 'no_rkm_medis' => 'RM002', 'kd_pj' => 'BPJ']);
        DB::table('antrol_bpjs')->insert([
            'no_rawat' => 'TEST002',
            'jam_periksa_perawat' => '10:30:45',
            'jam_periksa_dokter' => '00:00:00',
        ]);

        // Coba set lagi dengan jam berbeda
        $result = $this->antrolService->insertJamPeriksaPerawatOnce('TEST002', '11:00:00');

        // Assert: gagal (skip), jam tidak berubah
        $this->assertFalse($result['success']);

        $antrol = DB::table('antrol_bpjs')->where('no_rawat', 'TEST002')->first();
        $this->assertEquals('10:30:45', $antrol->jam_periksa_perawat); // Tidak berubah
    }

    /**
     * Test 3: Upsert jam dokter - CEPAT (< 1 menit dari perawat)
     */
    public function test_upsert_jam_dokter_fast_submission()
    {
        DB::table('penjab')->insert(['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS']);
        DB::table('reg_periksa')->insert(['no_rawat' => 'TEST003', 'no_rkm_medis' => 'RM003', 'kd_pj' => 'BPJ']);
        DB::table('antrol_bpjs')->insert([
            'no_rawat' => 'TEST003',
            'jam_periksa_perawat' => '10:30:45',
            'jam_periksa_dokter' => '00:00:00',
        ]);

        // Dokter submit pada 10:30:50 (hanya 5 detik setelah perawat)
        $jam_submit = '10:30:50';
        $result = $this->antrolService->upsertAntrolBpjsJam('TEST003', 'jam_periksa_dokter', $jam_submit);

        // Assert: berhasil, jam dipaksa ke minimal (jam_perawat + 61 detik)
        $this->assertTrue($result['success']);
        
        // Cek jam final = 10:31:46 (10:30:45 + 61 detik)
        $antrol = DB::table('antrol_bpjs')->where('no_rawat', 'TEST003')->first();
        $this->assertEquals('10:31:46', $antrol->jam_periksa_dokter);
    }

    /**
     * Test 4: Upsert jam dokter - LAMA (≥ 1 menit dari perawat)
     */
    public function test_upsert_jam_dokter_slow_submission()
    {
        DB::table('penjab')->insert(['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS']);
        DB::table('reg_periksa')->insert(['no_rawat' => 'TEST004', 'no_rkm_medis' => 'RM004', 'kd_pj' => 'BPJ']);
        DB::table('antrol_bpjs')->insert([
            'no_rawat' => 'TEST004',
            'jam_periksa_perawat' => '10:30:45',
            'jam_periksa_dokter' => '00:00:00',
        ]);

        // Dokter submit pada 10:35:00 (4 menit 15 detik setelah perawat)
        $jam_submit = '10:35:00';
        $result = $this->antrolService->upsertAntrolBpjsJam('TEST004', 'jam_periksa_dokter', $jam_submit);

        // Assert: berhasil, jam = realtime submit (tidak diubah)
        $this->assertTrue($result['success']);

        $antrol = DB::table('antrol_bpjs')->where('no_rawat', 'TEST004')->first();
        $this->assertEquals('10:35:00', $antrol->jam_periksa_dokter); // Pakai realtime
    }

    /**
     * Test 5: Multiple tabs scenario - PENTING UNTUK CONCURRENT ACCESS
     * Tab 1 & Tab 2 sama-sama open data pasien TEST005 (konkurensi)
     * Service handle via "insertJamPeriksaPerawatOnce" logic (hanya 1x)
     */
    public function test_multiple_tabs_concurrent_access()
    {
        DB::table('penjab')->insert(['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS']);
        DB::table('reg_periksa')->insert(['no_rawat' => 'TEST005', 'no_rkm_medis' => 'RM005', 'kd_pj' => 'BPJ']);
        DB::table('antrol_bpjs')->insert([
            'no_rawat' => 'TEST005',
            'jam_periksa_perawat' => '00:00:00',
            'jam_periksa_dokter' => '00:00:00',
        ]);

        // Simulasi Tab 1: Perawat set jam pada tab 1 dengan jam 10:00:00
        $result1 = $this->antrolService->insertJamPeriksaPerawatOnce('TEST005', '10:00:00');

        sleep(1); // Delay 1 detik (simulasi delay antar tab)

        // Simulasi Tab 2: Perawat buka tab 2, coba set jam lagi (harus skip)
        $result2 = $this->antrolService->insertJamPeriksaPerawatOnce('TEST005', '10:00:30');

        // Assert: Tab 1 berhasil, Tab 2 skip
        $this->assertTrue($result1['success']);
        $this->assertFalse($result2['success']);

        // Verify data: jam tidak berubah
        $antrol = DB::table('antrol_bpjs')->where('no_rawat', 'TEST005')->first();
        $this->assertEquals('10:00:00', $antrol->jam_periksa_perawat);
    }

    /**
     * Test 6: Non-BPJS patient harus diabaikan
     */
    public function test_non_bpjs_patient_ignored()
    {
        DB::table('penjab')->insert(['kd_pj' => 'UMUM', 'png_jawab' => 'Umum']);
        DB::table('reg_periksa')->insert(['no_rawat' => 'TEST006', 'no_rkm_medis' => 'RM006', 'kd_pj' => 'UMUM']);

        $result = $this->antrolService->insertJamPeriksaPerawatOnce('TEST006', '10:00:00');

        // Assert: gagal (bukan BPJS)
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Bukan pasien BPJS', $result['message']);
    }
}
