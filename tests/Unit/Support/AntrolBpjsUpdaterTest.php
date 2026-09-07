<?php

namespace Tests\Unit\Support;

use App\Support\AntrolBpjsUpdater;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AntrolBpjsUpdaterTest extends TestCase
{
    public function test_hitung_jam_dokter_tanpa_jam_perawat(): void
    {
        $now = Carbon::createFromTime(10, 30, 0);

        // Jam perawat null
        $this->assertSame('10:30:00', AntrolBpjsUpdater::hitungJamDokter(null, $now));

        // Jam perawat '00:00:00'
        $this->assertSame('10:30:00', AntrolBpjsUpdater::hitungJamDokter('00:00:00', $now));
    }

    public function test_hitung_jam_dokter_kurang_dari_61_detik(): void
    {
        // Perawat periksa jam 08:00:00
        // Dokter submit 30 detik kemudian (08:00:30)
        // Aturan: harus minimal jam_periksa_perawat + 61 detik => 08:01:01
        $jamPerawat = '08:00:00';
        $now = Carbon::createFromTime(8, 0, 30);

        $this->assertSame('08:01:01', AntrolBpjsUpdater::hitungJamDokter($jamPerawat, $now));
    }

    public function test_hitung_jam_dokter_tepat_dan_lebih_dari_61_detik(): void
    {
        $jamPerawat = '08:00:00';

        // Tepat 61 detik (08:01:01)
        $nowTepat = Carbon::createFromTime(8, 1, 1);
        $this->assertSame('08:01:01', AntrolBpjsUpdater::hitungJamDokter($jamPerawat, $nowTepat));

        // Lebih dari 61 detik (08:05:00)
        $nowLebih = Carbon::createFromTime(8, 5, 0);
        $this->assertSame('08:05:00', AntrolBpjsUpdater::hitungJamDokter($jamPerawat, $nowLebih));
    }
}
