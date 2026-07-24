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
